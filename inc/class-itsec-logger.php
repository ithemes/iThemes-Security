<?php
/**
 * Handles the writing, maintenance and display of log files
 *
 * @package iThemes-Security
 * @since   4.0
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

			require_once( $itsec_globals['plugin_dir'] . 'inc/lib/class-itsec-wp-list-table.php' ); //used for generating log tables

		}

		/**
		 * Adds a log meta box only if logging is active. Overrides WP Core add_meta_box
		 *
		 * @param string $module   the module requesting the metabox
		 * @param string $title    the title of the metabox
		 * @param array  $callback the function to call back
		 */
		public function add_meta_box( $module, $title, $callback ) {

			if ( $this->settings['log_type'] === 0 || $this->settings['log_type'] == 2 ) {

				add_meta_box(
					'log-' . sanitize_text_field( $module ),
					$title,
					$callback,
					'security_page_toplevel_page_itsec-' . sanitize_text_field( $module ),
					'normal',
					'core'
				);

			}

		}

		/**
		 * Gets events from the logs for a specified module
		 *
		 * @param string $module module or type of events to fetch
		 * @param array  $params array of extra query parameters
		 *
		 * @return bool|mixed false on error, null if no events or array of events
		 */
		public function get_events( $module, $params = array() ) {

			global $wpdb;

			if ( isset( $module ) !== true || strlen( $module ) < 1 ) {
				return false;
			}

			$param_search = '';

			if ( sizeof( $params ) > 0 ) {

				foreach ( $params as $field => $value ) {

					if ( gettype( $value ) != 'integer') {
						$param_search .= " AND `" . esc_sql( $field ) . "`='" . esc_sql( $value ) . "'";
					} else {
						$param_search .= " AND `" . esc_sql( $field ) . "`=" . esc_sql( $value ) . "";
					}

				}

			}

			$items = $wpdb->get_results( "SELECT * FROM `" . $wpdb->base_prefix . "itsec_log` WHERE `log_type` = '" . esc_sql( $module ) . "'" . $param_search . ";", ARRAY_A );

			return $items;

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

					$file_data .= esc_sql( $key ) . '=' . esc_sql( $value );
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