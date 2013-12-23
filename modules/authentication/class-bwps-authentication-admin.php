<?php

if ( ! class_exists( 'BWPS_Authentication_Admin' ) ) {

	class BWPS_Authentication_Admin {

		private static $instance = NULL;

		private function __construct() {

		}

		/**
		 * Start the Authentication module admin
		 *
		 * @param  Ithemes_BWPS_Core $core Instance of core plugin class
		 * @param BWPS_Authentication_Admin $module Instance of the authentication module class
		 *
		 * @return BWPS_Authentication_Admin              The instance of the BWPS_Authentication_Admin class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}