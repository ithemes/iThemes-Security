<?php

if ( ! class_exists( 'ITSEC_Backup' ) ) {

	class ITSEC_Backup {

		private static $instance = null;

		private
			$settings;

		private function __construct() {

			$this->settings  = get_site_option( 'itsec_backup' );

		}

		/**
		 * Start the Intrusion Detection module
		 *
		 * @return 'ITSEC_Backup'                The instance of the 'ITSEC_Backup' class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}