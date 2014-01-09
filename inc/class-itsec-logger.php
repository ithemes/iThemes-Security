<?php

if ( ! class_exists( 'ITSEC_Logger' ) ) {

	class ITSEC_Logger {

		private static $instance = NULL; //instantiated instance of this plugin

		function __construct() {

		}

		/**
		 * Start the global library instance
		 *
		 * @return ITSEC_Logger         The instance of the ITSEC_Logger class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}