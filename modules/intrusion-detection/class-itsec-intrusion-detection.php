<?php

if ( ! class_exists( 'ITSEC_Intrusion_Detection' ) ) {

	class ITSEC_Intrusion_Detection {

		private static $instance = null;

		private
			$settings;

		private function __construct() {

			global $itsec_current_time;

			$this->settings = get_site_option( 'itsec_intrusion_detection' );

			add_filter( 'itsec_lockout_modules', array( $this, 'register_lockout' ) );
			add_filter( 'itsec_logger_modules', array( $this, 'register_logger' ) );

			add_action( 'wp_head', array( $this, 'check_404' ) );

			if ( isset( $this->settings['file_change-enabled'] ) && $this->settings['file_change-enabled'] === true && isset( $this->settings['file_change-last_run'] ) && ( $itsec_current_time - 86400 ) > $this->settings['file_change-last_run'] ) {
				add_action( 'init', array( $this, 'execute_file_check' ) );
			} elseif ( isset( $this->settings['file_change-last_run'] ) === false ) {
				$this->settings['file_change-last_run'] = 0;
				update_site_option( 'itsec_intrusion_detection', $this->settings );
			}

		}

		/**
		 * If the page is a WordPress 404 error log it and register for lockout
		 *
		 * @return void
		 */
		public function check_404() {

			global $itsec_lib, $itsec_logger, $itsec_lockout;

			if ( $this->settings['four_oh_four-enabled'] === true && is_404() ) {

				$uri = explode( '?', $_SERVER['REQUEST_URI'] );

				if ( in_array( $uri[0], $this->settings['four_oh_four-white_list'] ) === false ) {

					$itsec_logger->log_event(
						'four_oh_four',
						3,
						array(
							'query_string' => isset( $uri[1] ) ? esc_sql( $uri[1] ) : '',
						),
						$itsec_lib->get_ip(),
						'',
						'',
						esc_sql( $uri[0] ),
						isset( $_SERVER['HTTP_REFERER'] ) ? esq_sql( $_SERVER['HTTP_REFERER'] ) : ''
					);

					$itsec_lockout->do_lockout( 'four_oh_four' );

				}

			}

		}

		/**
		 * Executes file checking
		 *
		 * @param bool $scheduled_call [optional] is this an automatic check
		 *
		 * @return void
		 **/
		public function execute_file_check( $scheduled_call = true ) {

			global $itsec_files, $itsec_logger, $itsec_current_time;

			if ( $itsec_files->get_file_lock( 'file_change' ) ) { //make sure it isn't already running

				//set base memory
				$memory_used = @memory_get_usage();

				$logged_files = get_site_option( 'itsec_local_file_list' );

				//if there are no old files old file list is an empty array
				if ( $logged_files === false ) {

					$logged_files = array();

				}

				$current_files = $this->scan_files(); //scan current files

				$itsec_files->release_file_lock( 'file_change' );

				$files_added          = @array_diff_assoc( $current_files, $logged_files ); //files added
				$files_removed        = @array_diff_assoc( $logged_files, $current_files ); //files deleted
				$current_minus_added  = @array_diff_key( $current_files, $files_added ); //remove all added files from current filelist
				$logged_minus_deleted = @array_diff_key( $logged_files, $files_removed ); //remove all deleted files from old file list
				$files_changed        = array(); //array of changed files

				//compare file hashes and mod dates
				foreach ( $current_minus_added as $current_file => $current_attr ) {

					if ( array_key_exists( $current_file, $logged_minus_deleted ) ) {

						//if attributes differ added to changed files array
						if ( strcmp( $current_attr['mod_date'], $logged_minus_deleted[$current_file]['mod_date'] ) != 0 || strcmp( $current_attr['hash'], $logged_minus_deleted[$current_file]['hash'] ) != 0 ) {

							$files_changed[$current_file]['hash']     = $current_attr['hash'];
							$files_changed[$current_file]['mod_date'] = $current_attr['mod_date'];

						}

					}

				}

				//get count of changes
				$files_added_count   = sizeof( $files_added );
				$files_deleted_count = sizeof( $files_removed );
				$files_changed_count = sizeof( $files_changed );

				//create single array of all changes
				$full_change_list = array(
					'added'   => $files_added,
					'removed' => $files_removed,
					'changed' => $files_changed,
				);

				update_site_option( 'itsec_local_file_list', $current_files );

				$this->settings['file_change-last_run'] = $itsec_current_time;

				update_site_option( 'itsec_intrusion_detection', $this->settings );

				//get new max memory
				$check_memory = @memory_get_peak_usage();
				if ( $check_memory > $memory_used ) {
					$memory_used = $check_memory - $memory_used;
				}

				$full_change_list['memory'] = round( ( $memory_used / 1000000 ), 2 );

				$itsec_logger->log_event(
					'file_change',
					8,
					$full_change_list
				);

				if ( $scheduled_call !== false && $this->settings['file_change-email'] === true ) {

					$email_details = array(
						$files_added_count,
						$files_deleted_count,
						$files_changed_count,
						$full_change_list
					);

					$this->send_notification_email( $email_details );
				}

				$itsec_files->release_file_lock( 'file_change' );

			}

		}

		/**
		 * Get Report Details
		 *
		 * @param array $email_details array of details to build email
		 *
		 * @return string report details
		 *
		 **/
		function get_email_report( $email_details ) {

			global $itsec_current_time;

			//seperate array by category
			$added   = $email_details[3]['added'];
			$removed = $email_details[3]['removed'];
			$changed = $email_details[3]['changed'];
			$report  = '<strong>' . __( 'Scan Time:', 'ithemes-security' ) . '</strong> ' . date( 'l, F jS g:i a e', $itsec_current_time ) . "<br />" . PHP_EOL;
			$report .= '<strong>' . __( 'Files Added:', 'ithemes-security' ) . '</strong> ' . $email_details[0] . "<br />" . PHP_EOL;
			$report .= '<strong>' . __( 'Files Deleted:', 'ithemes-security' ) . '</strong> ' . $email_details[1] . "<br />" . PHP_EOL;
			$report .= '<strong>' . __( 'Files Modified:', 'ithemes-security' ) . '</strong> ' . $email_details[2] . "<br />" . PHP_EOL;
			$report .= '<strong>' . __( 'Memory Used:', 'ithemes-security' ) . '</strong> ' . $email_details[3]['memory'] . " MB<br />" . PHP_EOL;

			$report .= '<h4>' . __( 'Files Added', 'ithemes-security' ) . '</h4>';
			$report .= '<table border="1" style="width: 100%; text-align: center;">' . PHP_EOL;
			$report .= '<tr>' . PHP_EOL;
			$report .= '<th>' . __( 'File', 'ithemes-security' ) . '</th>' . PHP_EOL;
			$report .= '<th>' . __( 'Modified', 'ithemes-security' ) . '</th>' . PHP_EOL;
			$report .= '<th>' . __( 'File Hash', 'ithemes-security' ) . '</th>' . PHP_EOL;
			$report .= '</tr>' . PHP_EOL;

			if ( isset( $added ) && is_array( $added ) && sizeof( $added > 0 ) ) {

				foreach ( $added as $item => $attr ) {

					$report .= '<tr>' . PHP_EOL;
					$report .= '<td>' . $item . '</td>' . PHP_EOL;
					$report .= '<td>' . date( 'l F jS, Y \a\t g:i a e', $attr['mod_date'] ) . '</td>' . PHP_EOL;
					$report .= '<td>' . $attr['hash'] . '</td>' . PHP_EOL;
					$report .= '</tr>' . PHP_EOL;

				}

			} else {

				$report .= '<tr>' . PHP_EOL;
				$report .= '<td colspan="3">' . __( 'No files were added.', 'ithemes-security' ) . '</td>' . PHP_EOL;
				$report .= '</tr>' . PHP_EOL;

			}

			$report .= '</table>' . PHP_EOL;

			$report .= '<h4>' . __( 'Files Deleted', 'ithemes-security' ) . '</h4>';
			$report .= '<table border="1" style="width: 100%; text-align: center;">' . PHP_EOL;
			$report .= '<tr>' . PHP_EOL;
			$report .= '<th>' . __( 'File', 'ithemes-security' ) . '</th>' . PHP_EOL;
			$report .= '<th>' . __( 'Modified', 'ithemes-security' ) . '</th>' . PHP_EOL;
			$report .= '<th>' . __( 'File Hash', 'ithemes-security' ) . '</th>' . PHP_EOL;
			$report .= '</tr>' . PHP_EOL;

			if ( isset( $removed ) && is_array( $removed ) && sizeof( $removed > 0 ) ) {

				foreach ( $removed as $item => $attr ) {

					$report .= '<tr>' . PHP_EOL;
					$report .= '<td>' . $item . '</td>' . PHP_EOL;
					$report .= '<td>' . date( 'l F jS, Y \a\t g:i a e', $attr['mod_date'] ) . '</td>' . PHP_EOL;
					$report .= '<td>' . $attr['hash'] . '</td>' . PHP_EOL;
					$report .= '</tr>' . PHP_EOL;

				}

			} else {

				$report .= '<tr>' . PHP_EOL;
				$report .= '<td colspan="3">' . __( 'No files were removed.', 'ithemes-security' ) . '</td>' . PHP_EOL;
				$report .= '</tr>' . PHP_EOL;

			}

			$report .= '</table>' . PHP_EOL;

			$report .= '<h4>' . __( 'Files Modified', 'ithemes-security' ) . '</h4>';
			$report .= '<table border="1" style="width: 100%; text-align: center;">' . PHP_EOL;
			$report .= '<tr>' . PHP_EOL;
			$report .= '<th>' . __( 'File', 'ithemes-security' ) . '</th>' . PHP_EOL;
			$report .= '<th>' . __( 'Modified', 'ithemes-security' ) . '</th>' . PHP_EOL;
			$report .= '<th>' . __( 'File Hash', 'ithemes-security' ) . '</th>' . PHP_EOL;
			$report .= '</tr>' . PHP_EOL;

			if ( isset( $changed ) && is_array( $changed ) && sizeof( $changed > 0 ) ) {

				foreach ( $changed as $item => $attr ) {

					$report .= '<tr>' . PHP_EOL;
					$report .= '<td>' . $item . '</td>' . PHP_EOL;
					$report .= '<td>' . date( 'l F jS, Y \a\t g:i a e', $attr['mod_date'] ) . '</td>' . PHP_EOL;
					$report .= '<td>' . $attr['hash'] . '</td>' . PHP_EOL;
					$report .= '</tr>' . PHP_EOL;

				}

			} else {

				$report .= '<tr>' . PHP_EOL;
				$report .= '<td colspan="3">' . __( 'No files were changed.', 'ithemes-security' ) . '</td>' . PHP_EOL;
				$report .= '</tr>' . PHP_EOL;

			}

			$report .= '</table>' . PHP_EOL;

			return $report;

		}

		/**
		 * Check file list
		 *
		 * Checks if given file should be included in file check based on exclude/include options
		 *
		 * @param string $file path of file to check from site root
		 *
		 * @return bool true if file should be checked false if not
		 *
		 **/
		private function is_checkable_file( $file ) {

			//get file list from last check
			$file_list = $this->settings['file_change-list'];
			$type_list = $this->settings['file_change-types'];

			$file_list[] = 'itsec_file_change.lock';

			//assume not a directory and not checked
			$flag = false;

			if ( in_array( $file, $file_list ) ) {
				$flag = true;
			}

			if ( ! is_dir( $file ) ) {

				$path_info = pathinfo( $file );

				if ( isset( $path_info['extension'] ) && in_array( '.' . $path_info['extension'], $type_list ) ) {
					$flag = true;
				}

			}

			if ( $this->settings['file_change-method'] === true ) {

				if ( $flag == true ) { //if exclude reverse
					return false;
				} else {
					return true;
				}

			} else { //return flag 

				return $flag;

			}

		}

		/**
		 * Register 404 detection for lockout
		 *
		 * @param  array $lockout_modules array of lockout modules
		 *
		 * @return array                   array of lockout modules
		 */
		public function register_lockout( $lockout_modules ) {

			$lockout_modules['four_oh_four'] = array(
				'type'   => 'four_oh_four',
				'reason' => __( 'too many attempts to access a file that doesn not exist', 'ithemes-security' ),
				'host'   => $this->settings['four_oh_four-error_threshold'],
				'period' => $this->settings['four_oh_four-check_period']
			);

			return $lockout_modules;

		}

		/**
		 * Register 404 and file change detection for logger
		 *
		 * @param  array $logger_modules array of logger modules
		 *
		 * @return array                   array of logger modules
		 */
		public function register_logger( $logger_modules ) {

			$logger_modules['four_oh_four'] = array(
				'type'     => 'four_oh_four',
				'function' => __( '404 Error', 'ithemes-security' ),
			);

			$logger_modules['file_change'] = array(
				'type'     => 'file_change',
				'function' => __( 'File Changes Detected', 'ithemes-security' ),
			);

			return $logger_modules;

		}

		/**
		 * Scans all files in a given path
		 *
		 * @param string $path [optional] path to scan, defaults to WordPress root
		 *
		 * @return array array of files found and their information
		 *
		 */
		private function scan_files( $path = '' ) {

			$time_offset = get_option( 'gmt_offset' ) * 60 * 60;

			$data = array();

			$clean_path = sanitize_text_field( $path );

			if ( $directory_handle = @opendir( ABSPATH . $clean_path ) ) { //get the directory

				while ( ( $item = readdir( $directory_handle ) ) !== false ) { // loop through dirs

					if ( $item != '.' && $item != '..' ) { //don't scan parent/etc

						$relname = $path . $item;

						$absname = ABSPATH . $relname;

						if ( $this->is_checkable_file( $relname ) == true ) { //make sure the user wants this file scanned

							if ( is_dir( $absname ) && filetype( $absname ) == 'dir' ) { //if directory scan it

								$data = array_merge( $data, $this->scan_files( $relname . '/' ) );

							} else { //is file so add to array

								$data[$relname]             = array();
								$data[$relname]['mod_date'] = @filemtime( $absname ) + $time_offset;
								$data[$relname]['hash']     = @md5_file( $absname );

							}

						}

					}

				}

				@closedir( $dirHandle ); //close the directory we're working with

			}

			return $data; // return the files we found in this dir			

		}

		/**
		 * Builds and sends notification email
		 *
		 * @param array $email_details array of details for the email messge
		 *
		 * @return void
		 */
		private function send_notification_email( $email_details ) {

			global $itsec_current_time;

			$global_options = get_site_option( 'itsec_global' );

			$headers = 'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>' . "\r\n";
			$subject = '[' . get_option( 'siteurl' ) . '] ' . __( 'WordPress File Change Warning', 'ithemes-security' ) . ' ' . date( 'l, F jS, Y \a\\t g:i a e', $itsec_current_time );

			$body = '<p>' . __( 'A file (or files) on your site at ', 'ithemes-security' ) . ' ' . get_option( 'siteurl' ) . __( ' have been changed. Please review the report below to verify changes are not the result of a compromise.', 'ithemes-security' ) . '</p>';
			$body .= $this->get_email_report( $email_details ); //get report

			//Use HTML Content type
			add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

			foreach ( $global_options['notification_email'] as $recipient ) {

				if ( is_email( trim( $recipient ) ) ) {
					wp_mail( trim( $recipient ), $subject, $body, $headers );
				}

			}

			//Remove HTML Content type
			remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

		}

		/**
		 * Set HTML content type for email
		 *
		 * @return string html content type
		 */
		public function set_html_content_type() {

			return 'text/html';

		}

		/**
		 * Start the Intrusion Detection module
		 *
		 * @return 'ITSEC_Intrusion_Detection'                The instance of the 'ITSEC_Intrusion_Detection' class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}