<?php

if ( ! class_exists( 'ITSEC_Logger' ) ) {

	final class ITSEC_Logger {

		private static $instance = null; //instantiated instance of this plugin

		private
			$log_file,
			$logger_modules,
			$settings;

		function __construct() {

			global $itsec_globals;

			$this->log_file = $itsec_globals['ithemes_log_dir'] . '/event-log.log';

			if ( file_exists( $this->log_file ) !== true ) {

				$header = 'log_type,log_priority,log_function,log_date,log_date_gmt,log_host,log_username,log_user,log_url,log_referrer,log_data' . PHP_EOL;

				error_log( $header, 3, $this->log_file );

			}

			$this->settings = get_site_option( 'itsec_global' );

			$this->logger_modules = array(); //array to hold information on modules using this feature

			add_action( 'plugins_loaded', array( $this, 'register_modules' ) );

			//Run database cleanup daily with cron
			if ( ! wp_next_scheduled( 'itsec_purge_logs' ) ) {
				wp_schedule_event( time(), 'daily', 'itsec_purge_logs' );
			}

			add_action( 'itsec_purge_logs', array( $this, 'purge_logs' ) );

		}

		public function log_event( $module, $priority = 5, $data = array(), $host = '', $username = '', $user = '', $url = '', $referrer = '' ) {

			global $wpdb, $itsec_globals, $itsec_current_time_gmt, $itsec_current_time;

			$sanitized_data = array(); //array of sanitized data

			if ( isset( $this->logger_modules[$module] ) ) {

				$options = $this->logger_modules[$module];

				$file_data = '';

				//Loop to sanitize each piece of data
				foreach ( $data as $key => $value ) {

					$sanitized_data[esc_sql( $key )] = esc_sql( $value );

					$file_data .= esc_sql( $key ) . '=' . esq_sql( $value );
				}

				if( $this->settings['log_type'] === 0 || $this->settings['log_type'] == 2 ) {

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

				if( $this->settings['log_type'] === 1 || $this->settings['log_type'] == 2 ) {

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

		public function purge_logs() {

			global $wpdb, $itsec_current_time_gmt;

			//Clean up the database log first
			if( $this->settings['log_type'] === 0 || $this->settings['log_type'] == 2 ) {

				$wpdb->query( "DELETE FROM `" . $wpdb->base_prefix . "itsec_log` WHERE `log_date_gmt` < '" . date( 'Y-m-d H:i:s', $itsec_current_time_gmt - ( $this->settings['log_rotation'] * 24 * 60 * 60 ) ) . "';" );

			} else {

				$wpdb->query( "DELETE FROM `" . $wpdb->base_prefix . "itsec_log`;" );

			}

			$this->settings['last_purged'] = $itsec_current_time_gmt;

			update_site_option( 'itsec_global', $this->settings );

		}

		/**
		 * Register modules that will use the logger service
		 *
		 * @return void
		 */
		public function register_modules() {

			$this->logger_modules = apply_filters( 'itsec_logger_modules', $this->logger_modules );

		}

		public function save_logs() {

		}

		/**
		 * Start the global library instance
		 *
		 * @return ITSEC_Logger         The instance of the ITSEC_Logger class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}