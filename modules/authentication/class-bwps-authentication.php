<?php

if ( ! class_exists( 'BWPS_Authentication' ) ) {

	class BWPS_Authentication {

		private static $instance = NULL;

		private
			$settings;

		private function __construct() {

			$this->settings  = get_site_option( 'bwps_authentication' );

		}

		/**
		 * Start the Authentication module
		 *
		 * @return BWPS_Authentication                The instance of the BWPS_Authentication class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}