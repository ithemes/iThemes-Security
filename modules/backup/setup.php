<?php

if ( ! class_exists( 'ITSEC_Backup_Setup' ) ) {

	class ITSEC_Backup_Setup {

		function __construct() {

			global $itsec_setup_action;

			if ( isset( $itsec_setup_action ) ) {

				switch ( $itsec_setup_action ) {

					case 'activate':
						$this->execute_activate();
						break;
					case 'upgrade':
						$this->execute_upgrade();
						break;
					case 'deactivate':
						$this->execute_deactivate();
						break;
					case 'uninstall':
						$this->execute_uninstall();
						break;

				}

			} else {
				wp_die( 'error' );
			}

		}

		/**
		 * Execute module activation
		 *
		 * @return void
		 */
		function execute_activate() {

			global $itsec_globals;

			$options = get_site_option( 'itsec_backup' );

			if ( $options === false ) {

				$defaults = array(
					'enabled'  => false,
					'interval' => 3,
					'method'   => 3,
					'location' => $itsec_globals['ithemes_backup_dir'],
					'last_run' => 0,
				);

				add_site_option( 'itsec_backup', $defaults );

			}

		}

		/**
		 * Execute module deactivation
		 *
		 * @return void
		 */
		function execute_deactivate() {
		}

		/**
		 * Execute module uninstall
		 *
		 * @return void
		 */
		function execute_uninstall() {

			$this->execute_deactivate();

			delete_site_option( 'itsec_backup' );

		}

		/**
		 * Execute module upgrade
		 *
		 * @return void
		 */
		function execute_upgrade() {

		}

	}

}

new ITSEC_Backup_Setup();