<?php

if ( ! class_exists( 'Ithemes_BWPS_Logger' ) ) {

	class Ithemes_BWPS_Logger {

		private static $instance = NULL; //instantiated instance of this plugin

		function __construct() {

		}

		/**
		 * Start the global library instance
		 *
		 * @return Ithemes_BWPS_Logger         The instance of the Ithemes_BWPS_Logger class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}