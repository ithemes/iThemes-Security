<?php

if ( ! class_exists( 'ITSEC_Logger' ) ) {

	final class ITSEC_Logger {

		private static $instance = null; //instantiated instance of this plugin

		private
			$logger_modules,
			$settings;

		function __construct() {

			$this->settings = get_site_option( 'itsec_global' );

			$this->logger_modules = array(); //array to hold information on modules using this feature

			add_action( 'plugins_loaded', array( $this, 'register_modules' ) );

		}

		public function log_event( $module, $priority = 5, $data = array(), $host = '', $username = '', $user = '', $url = '', $referrer = '' ) {

			global $wpdb, $itsec_current_time_gmt, $itsec_current_time;

			$saved_data = array();

			$options = $this->logger_modules[$module];

			foreach ( $data as $key => $value ) {

				$saved_data[esc_sql( $key )] = esc_sql( $value );
			}

			$wpdb->insert(
				$wpdb->base_prefix . 'itsec_log',
				array(
					'log_type'     => $options['type'],
					'log_priority' => intval( $priority ),
					'log_date'     => date( 'Y-m-d H:i:s', $itsec_current_time ),
					'log_date_gmt' => date( 'Y-m-d H:i:s', $itsec_current_time_gmt ),
					'log_host'     => sanitize_text_field( $host ),
					'log_username' => sanitize_text_field( $username ),
					'log_user'     => intval( $user ),
					'log_url'      => esc_sql( $url ),
					'log_referrer' => esc_sql( $referrer ),
					'log_data'     => serialize( $saved_data ),
				)
			);

		}

		public function purge_logs() {

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