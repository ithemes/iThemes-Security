<?php

if ( ! class_exists( 'ITSEC_Files' ) ) {

	class ITSEC_Files {

		private static $instance = null;

		private
			$settings;

		private function __construct() {

			$this->settings  = get_site_option( 'itsec_files' );

		}

		/**
		 * Start the Files module
		 *
		 * @return 'ITSEC_Files'                The instance of the 'ITSEC_Files' class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}