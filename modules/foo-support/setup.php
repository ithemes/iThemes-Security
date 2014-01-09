<?php

if ( ! class_exists( 'ITSEC_Foo_Support_Setup' ) ) {

	class ITSEC_Foo_Support_Setup {

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
		 */
		function execute_activate() {

		}

		/**
		 * Execute module deactivation
		 *
		 */
		function execute_deactivate() {

		}

		/**
		 * Execute module uninstall
		 *
		 */
		function execute_uninstall() {

			$this->execute_deactivate();

			delete_site_option( 'itsec_licensekey' );
			delete_site_option( 'itsec_valid' );
			delete_site_option( 'itsec_valid_expires' );
			delete_site_option( 'itsec_lasterror' );

		}

		/**
		 * Execute module upgrade
		 *
		 */
		function execute_upgrade() {

		}

	}

}

new ITSEC_Foo_Support_Setup();