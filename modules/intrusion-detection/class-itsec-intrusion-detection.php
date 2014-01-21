<?php

if ( ! class_exists( 'ITSEC_Intrusion_Detection' ) ) {

	class ITSEC_Intrusion_Detection {

		private static $instance = null;

		private
			$settings;

		private function __construct() {

			$this->settings  = get_site_option( 'itsec_intrusion-detection' );

		}

		/**
		 * Start the Intrusion Detection module
		 *
		 * @return 'ITSEC_Intrusion_Detection'                The instance of the 'ITSEC_Intrusion_Detection' class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}