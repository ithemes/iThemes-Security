<?php

if ( ! class_exists( 'BWPS_Foo_Support_Setup' ) ) {

	class BWPS_Foo_Support_Setup {

		private
			$hook;

		function __construct() {

			global $bwps_setup_action;

			//Important, this must be manually set in each module
			$this->hook = 'bwps';

			if ( isset( $bwps_setup_action ) ) {

				switch ( $bwps_setup_action ) {

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

			delete_site_option( $this->hook . '_licensekey' );
			delete_site_option( $this->hook . '_valid' );
			delete_site_option( $this->hook . '_valid_expires' );
			delete_site_option( $this->hook . '_lasterror' );

		}

		/**
		 * Execute module upgrade
		 *
		 */
		function execute_upgrade() {

		}

	}

}

new BWPS_Foo_Support_Setup();