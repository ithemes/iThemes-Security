<?php

if ( ! class_exists( 'BWPS_Advanced_Tweaks' ) ) {

	class BWPS_Advanced_Tweaks {

		private static $instance = NULL;

		private
			$settings;

		private function __construct() {

			$this->settings  = get_site_option( 'bwps_advanced_tweaks' );

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
		 * @return BWPS_Advanced_Tweaks                The instance of the BWPS_Advanced_Tweaks class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}