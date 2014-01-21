<?php
/**
 * Handles the writing, maintenance and display of log files
 *
 * @package iThemes-Security
 * @since 4.0
 */
if ( ! class_exists( 'ITSEC_Logger' ) ) {

	final class ITSEC_Logger {

		private static $instance = null; //instantiated instance of this plugin

		private
			$core,
			$log_file,
			$logger_modules,
			$page,
			$settings;

		function __construct( $core ) {

			global $itsec_globals;

			$this->settings = get_site_option( 'itsec_global' );

			$this->log_file = $itsec_globals['ithemes_log_dir'] . '/event-log.log';

			$this->start_log(); //create a log file if we don't have one

			$this->logger_modules = array(); //array to hold information on modules using this feature

			add_action( 'plugins_loaded', array( $this, 'register_modules' ) );

			//Run database cleanup daily with cron
			if ( ! wp_next_scheduled( 'itsec_purge_logs' ) ) {
				wp_schedule_event( time(), 'daily', 'itsec_purge_logs' );
			}

			add_action( 'itsec_purge_logs', array( $this, 'purge_logs' ) );

			//Setup admin log tab

			$this->core = $core;

			add_action( 'itsec_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_filter( 'itsec_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( 'itsec_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu

			if ( is_admin() ) {
				require_once( $itsec_globals['plugin_dir'] . 'inc/logs/class-itsec-wp-list-table.php' );
				require_once( $itsec_globals['plugin_dir'] . 'inc/logs/class-itsec-logger-tables.php' );
			}

		}

		/**
		 * Add meta boxes to primary options pages
		 *
		 * @param array $available_pages array of available page_hooks
		 */
		public function add_admin_meta_boxes( $available_pages ) {

			add_meta_box(
				'global_description',
				__( 'Description', 'ithemes-security' ),
				array( $this, 'add_module_intro' ),
				'security_page_toplevel_page_itsec-logs',
				'normal',
				'core'
			);

			add_meta_box(
				'global_options',
				__( 'Configure Global Settings', 'ithemes-security' ),
				array( $this, 'metabox_logs' ),
				'security_page_toplevel_page_itsec-logs',
				'advanced',
				'core'
			);

		}

		/**
		 * Add a tab in the admin area
		 *
		 * @param array $tabs array of tab names we're adding to
		 *
		 * @return mixed
		 */
		public function add_admin_tab( $tabs ) {

			$tabs[$this->page] = __( 'Logs', 'ithemes-security' );

			return $tabs;

		}

		/**
		 * Build and echo the away mode description
		 *
		 * @return void
		 */
		public function add_module_intro( $screen ) {

			$content = '<p>' . __( 'The settings below are used throughout the iThemes Security system.', 'ithemes-security' ) . '</p>';
			echo $content;

		}

		/**
		 * Register subpage for logs
		 *
		 * @param array $available_pages array of ITSEC settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $itsec_globals;

			$this->page = $available_pages[0] . '-logs';

			$available_pages[] = add_submenu_page(
				'itsec',
				__( 'Logs', 'ithemes-security' ),
				__( 'Logs', 'ithemes-security' ),
				$itsec_globals['plugin_access_lvl'],

				$available_pages[0] . '-logs', array( $this->core, 'render_page' )
			);

			return $available_pages;

		}

		/**
		 * Empty callback function
		 */
		public function empty_callback_function() {
		}

		/**
		 * Logs events sent by other modules or systems
		 *
		 * @param string $module   the module requesting the log entry
		 * @param int    $priority the priority of the log entry (1-10)
		 * @param array  $data     extra data to log (non-indexed data would be good here)
		 * @param string $host     the remote host triggering the event
		 * @param string $username the username triggering the event
		 * @param string $user     the user id triggering the event
		 * @param string $url      the url triggering the event
		 * @param string $referrer the referrer to the url (if applicable)
		 *
		 * @return void
		 */
		public function log_event( $module, $priority = 5, $data = array(), $host = '', $username = '', $user = '', $url = '', $referrer = '' ) {

			global $wpdb, $itsec_current_time_gmt, $itsec_current_time;

			$sanitized_data = array(); //array of sanitized data

			if ( isset( $this->logger_modules[$module] ) ) {

				$options = $this->logger_modules[$module];

				$file_data = '';

				//Loop to sanitize each piece of data
				foreach ( $data as $key => $value ) {

					$sanitized_data[esc_sql( $key )] = esc_sql( $value );

					$file_data .= esc_sql( $key ) . '=' . esq_sql( $value );
				}

				if ( $this->settings['log_type'] === 0 || $this->settings['log_type'] == 2 ) {

					$wpdb->insert(
						$wpdb->base_prefix . 'itsec_log',
						array(
							'log_type'     => $options['type'],
							'log_priority' => intval( $priority ),
							'log_function' => $options['function'],
							'log_date'     => date( 'Y-m-d H:i:s', $itsec_current_time ),
							'log_date_gmt' => date( 'Y-m-d H:i:s', $itsec_current_time_gmt ),
							'log_host'     => sanitize_text_field( $host ),
							'log_username' => sanitize_text_field( $username ),
							'log_user'     => intval( $user ),
							'log_url'      => esc_sql( $url ),
							'log_referrer' => esc_sql( $referrer ),
							'log_data'     => serialize( $sanitized_data ),
						)
					);

				}

				if ( $this->settings['log_type'] === 1 || $this->settings['log_type'] == 2 ) {

					$message =
						$options['type'] . ',' .
						intval( $priority ) . ',' .
						$options['function'] . ',' .
						date( 'Y-m-d H:i:s', $itsec_current_time ) . ',' .
						date( 'Y-m-d H:i:s', $itsec_current_time_gmt ) . ',' .
						sanitize_text_field( $host ) . ',' .
						sanitize_text_field( $username ) . ',' .
						( intval( $user ) === 0 ? '' : intval( $user ) ) . ',' .
						esc_sql( $url ) . ',' .
						esc_sql( $referrer ) . ',' .
						$file_data;

					error_log( $message . PHP_EOL, 3, $this->log_file );

				}

			}

		}

		/**
		 * Render the settings metabox
		 *
		 * @return void
		 */
		public function metabox_logs() {

			echo "This will be the logs and stuff.";

		}

		/**
		 * Purges database logs and rotates file logs (when needed)
		 *
		 * @return void
		 */
		public function purge_logs() {

			global $wpdb, $itsec_current_time_gmt;

			//Clean up the database log first
			if ( $this->settings['log_type'] === 0 || $this->settings['log_type'] == 2 ) {

				$wpdb->query( "DELETE FROM `" . $wpdb->base_prefix . "itsec_log` WHERE `log_date_gmt` < '" . date( 'Y-m-d H:i:s', $itsec_current_time_gmt - ( $this->settings['log_rotation'] * 24 * 60 * 60 ) ) . "';" );

			} else {

				$wpdb->query( "DELETE FROM `" . $wpdb->base_prefix . "itsec_log`;" );

			}

			if ( ( @file_exists( $this->log_file ) && @filesize( $this->log_file ) >= 10485760 ) ) {
				$this->rotate_log();
			}

		}

		/**
		 * Register modules that will use the logger service
		 *
		 * @return void
		 */
		public function register_modules() {

			$this->logger_modules = apply_filters( 'itsec_logger_modules', $this->logger_modules );

		}

		/**
		 * Rotates the event-log.log file when called
		 *
		 * Adapted from http://www.phpclasses.org/browse/file/49471.html
		 *
		 * @return void
		 */
		private function rotate_log() {

			// rotate
			$path_info      = pathinfo( $this->log_file );
			$base_directory = $path_info['dirname'];
			$base_name      = $path_info['basename'];
			$num_map        = array();

			foreach ( new DirectoryIterator( $base_directory ) as $fInfo ) {

				if ( $fInfo->isDot() || ! $fInfo->isFile() )
					continue;

				if ( preg_match( '/^' . $base_name . '\.?([0-9]*)$/', $fInfo->getFilename(), $matches ) ) {

					$num      = $matches[1];
					$old_file = $fInfo->getFilename();

					if ( $num == '' ) {
						$num = - 1;
					}

					$num_map[$num] = $old_file;

				}

			}

			krsort( $num_map );

			foreach ( $num_map as $num => $old_file ) {

				$new_file = $num + 1;
				@rename( $base_directory . DIRECTORY_SEPARATOR . $old_file, $this->log_file . '.' . $new_file );

			}

			$this->start_log();

		}

		/**
		 * Creates a new log file and adds header information (if needed)
		 *
		 * @return void
		 */
		private function start_log() {

			if ( file_exists( $this->log_file ) !== true ) { //only if current log file doesn't exist

				$header = 'log_type,log_priority,log_function,log_date,log_date_gmt,log_host,log_username,log_user,log_url,log_referrer,log_data' . PHP_EOL;

				@error_log( $header, 3, $this->log_file );

			}

		}

		/**
		 * Start the global library instance
		 *
		 * @param Ithemes_ITSEC_Core $core Instance of core plugin class
		 *
		 * @return ITSEC_Logger         The instance of the ITSEC_Logger class
		 */
		public static function start( $core ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}