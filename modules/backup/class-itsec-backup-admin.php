<?php

if ( ! class_exists( 'ITSEC_Backup_Admin' ) ) {

	class ITSEC_Backup_Admin {

		private static $instance = null;

		private
			$core,
			$settings;

		private function __construct( $core ) {

			$this->core     = $core;
			$this->settings = get_site_option( 'itsec_backup' );

		}

		/**
		 * Start the Intrusion Detection Admin Module
		 *
		 * @param Ithemes_ITSEC_Core $core Instance of core plugin class
		 *
		 * @return ITSEC_Backup_Admin                The instance of the ITSEC_Backup_Admin class
		 */
		public static function start( $core ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}