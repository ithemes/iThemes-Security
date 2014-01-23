<?php

if ( ! class_exists( 'ITSEC_Backup' ) ) {

	class ITSEC_Backup {

		private static $instance = null;

		private
			$settings;

		private function __construct() {

			global $itsec_globals;

			$this->settings  = get_site_option( 'itsec_backup' );

			if ( $this->settings['enabled'] === true && ( ( $itsec_globals->itsec_current_time - $this->settings['interval'] * 24 * 60 * 60 ) ) > $this->settings['last_run'] ) {

				$this->do_backup();

			}

		}

		private function do_backup() {

			

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