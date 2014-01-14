<?php

if ( ! class_exists( 'ITSEC_Lockout' ) ) {

	class ITSEC_Lockout {

		private static $instance = NULL; //instantiated instance of this plugin

		private 
			$settings


		function __construct() {

			$this->settings = get_site_option( 'itsec_global' );

		}

		public function lockout( $type, $reason, $host = null, $user = null ) {

			global $wpdb;

			

		}

		public function check_lockout( $host = null, $user = null ) {

		}

		public function purge_lockouts() {
			
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