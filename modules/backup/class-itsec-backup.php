<?php

if ( ! class_exists( 'ITSEC_Backup' ) ) {

	class ITSEC_Backup {

		private static $instance = null;

		private
			$settings;

		private function __construct() {

			global $itsec_current_time_gmt;

			$this->settings  = get_site_option( 'itsec_backup' );

			if ( $this->settings['enabled'] === true && ! class_exists( 'pb_backupbuddy' ) && ( ( $itsec_current_time_gmt - $this->settings['interval'] * 24 * 60 * 60 ) ) > $this->settings['last_run'] ) {

				add_action( 'init', array( $this, 'do_backup' ) );

			}

		}

		/**
		 * Public function to get lock and call backup
		 * 
		 * @param  boolean $one_time whether this is a one time backup
		 * 
		 * @return mixed false on error or nothing
		 */
		public function do_backup( $one_time = false ) {

			global $itsec_files;

			if ( $itsec_files->get_file_lock( 'backup' ) ) {

				$this->execute_backup( $one_time );
				$itsec_files->release_file_lock( 'backup' );

			} else {
				return false;
			}

		}

		/**
		 * Executes backup function
		 *
		 * @param bool $one_time whether this is a one-time backup
		 * 
		 * @return void
		 */
		private function execute_backup( $one_time = false ) {

			global $wpdb, $itsec_globals, $itsec_logger, $itsec_current_time_gmt, $itsec_current_time;
				
			//get all of the tables
			$tables = $wpdb->get_results( 'SHOW TABLES', ARRAY_N );
				
			$return = '';
				
			//cycle through each table
			foreach( $tables as $table ) {
				
				$num_fields = sizeof( $wpdb->get_results( 'DESCRIBE `' . $table[0] . '`;' ) );
					
				$return.= 'DROP TABLE IF EXISTS `' . $table[0] . '`;';

				$row2 = $wpdb->get_row( 'SHOW CREATE TABLE `' . $table[0] . '`;', ARRAY_N );

				$return.= PHP_EOL . PHP_EOL . $row2[1] . ";" . PHP_EOL . PHP_EOL;

				if ( in_array( substr( $table[0], strlen( $wpdb->prefix ) ), $this->settings['exclude'] ) === false ) {

					$result = $wpdb->get_results( 'SELECT * FROM `' . $table[0] . '`;', ARRAY_N );

					foreach( $result as $row ) {
						
						$return .= 'INSERT INTO `' . $table[0] . '` VALUES(';
								
						for( $j=0; $j < $num_fields; $j++ ) {
								
							$row[$j] = addslashes( $row[$j] );
							$row[$j] = preg_replace( '#' . PHP_EOL . '#', "\n", $row[$j] );
									
							if ( isset( $row[$j] ) ) { 
								$return .= '"' . $row[$j] . '"' ; 
							} else { 
								$return.= '""'; 
							}
									
							if ( $j < ( $num_fields - 1 ) ) { 
								$return .= ','; 
							}
									
						}
								
						$return .= ");" . PHP_EOL;
								
					}

				}
						
				$return .= PHP_EOL . PHP_EOL;
						
			}
					
			$return .= PHP_EOL . PHP_EOL;
				
			//save file
			$file = 'database-backup-' . current_time( 'timestamp' );
			$handle = @fopen( $itsec_globals['ithemes_backup_dir'] . '/' . $file . '.sql', 'w+' );
			@fwrite( $handle, $return );
			@fclose( $handle );
		
			//zip the file
			if ( $this->settings['zip'] === true && class_exists( 'ZipArchive' ) ) {
				
				$zip = new ZipArchive();
				$archive = $zip->open( $itsec_globals['ithemes_backup_dir'] . '/' . $file . '.zip', ZipArchive::CREATE );
				$zip->addFile( $itsec_globals['ithemes_backup_dir'] . '/' . $file . '.sql', $file . '.sql' );
				$zip->close();
			
				//delete .sql and keep zip
				@unlink( $itsec_globals['ithemes_backup_dir'] . '/' . $file . '.sql' );

				$fileext = '.zip';
				
			} else {
			
				$fileext = '.sql';
					
			}

			if ( $this->settings['method'] !== 2 || $one_time === true ) {

				$option = get_site_option( 'itsec_global' );
				
				$attachment = array( $itsec_globals['ithemes_backup_dir'] . '/' . $file . $fileext );
				$body       = __( 'Attached is the backup file for the database powering', 'ithemes-security' ) . ' ' . get_option( 'siteurl' ) . __( ' taken', 'ithemes-security' ) . ' ' . date( 'l, F jS, Y \a\\t g:i a', $itsec_current_time );

				//Setup the remainder of the email
				$recipients = $option['backup_email'];
				$subject    = __( 'Site Database Backup', 'ithemes-security' ) . ' ' . date( 'l, F jS, Y \a\\t g:i a', $itsec_current_time );
				$headers    = 'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>' . "\r\n";

				//Use HTML Content type
				add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

				//Send emails to all recipients
				foreach ( $recipients as $recipient ) {

					if ( is_email( trim( $recipient ) ) ) {
						wp_mail( trim( $recipient ), $subject, $body, $headers, $attachment );
					}

				}

				//Remove HTML Content type
				remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
			
			}

			if ( $this->settings['method'] !== 1 && $one_time === false ) {

				@unlink( $itsec_globals['ithemes_backup_dir'] . '/' . $file . $fileext );

			}

			if ( $one_time === false ) {

				$this->settings['last_run'] = $itsec_current_time_gmt;

				update_site_option( 'itsec_backup', $this->settings );

			}

			$itsec_logger->log_event( 'backup', 3, array( 'status' => 'complete' ) );
				
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
		 * @return 'ITSEC_Backup'                The instance of the 'ITSEC_Backup' class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}