<?php

if ( ! class_exists( 'BWPS_SSL' ) ) {

	class BWPS_SSL {

		private static $instance = NULL;

		private
			$settings;

		private function __construct() {

			$this->settings  = get_site_option( 'bwps_ssl' );

		}

		/**
		 * Execute module functionality
		 *
		 * @return void
		 */
		public function execute_module_functions() {

		}

		/**
		 * Start the Away Mode module
		 *
		 * @return BWPS_SSL                The instance of the BWPS_SSL class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}