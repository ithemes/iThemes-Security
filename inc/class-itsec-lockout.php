<?php

if ( ! class_exists( 'ITSEC_Lockout' ) ) {

	class ITSEC_Lockout {

		private static $instance = NULL; //instantiated instance of this plugin

		private 
			$settings,
			$current_time,
			$current_time_gmt;

		function __construct() {

			$this->settings = get_site_option( 'itsec_global' );
			$this->current_time = current_time( 'timestamp' ); 
			$this->current_time_gmt = current_time( 'timestamp', 1 ); 

		}

		public function lockout( $type, $reason, $host = null, $user = null ) {

			global $wpdb;

			die ( var_dump( ITSEC_Ban_Users_Admin::is_ip_whitelisted( '131.23.02.131' ) ) );

		}

		public function check_lockout( $host = null, $user = null ) {

		}

		public function purge_lockouts() {
			
		}

		private function blacklist_host() {

		}

		private function sent_lockout_email() {

		}

		/**
		 * Start the global lockout instance
		 *
		 * @return ITSEC_Lockout         The instance of the ITSEC_Lockout class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}