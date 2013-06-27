<?php

if ( ! class_exists( 'BWPS_Away_Mode_Setup' ) ) {

	class BWPS_Away_Mode_Setup {

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
		 * @return void
		 */
		function execute_activate() {

			global $bwps_hook;

		}

		/**
		 * Execute module deactivation
		 * 
		 * @return void
		 */
		function execute_deactivate() {

			global $bwps_hook;

			delete_site_transient ( 'bwps_away' );

		}

		/**
		 * Execute module uninstall
		 * 
		 * @return void
		 */
		function execute_uninstall() {

			global $bwps_hook;

			$this->execute_deactivate();

			delete_site_option( $this->hook . '_away_mode' );

		}

		/**
		 * Execute module upgrade
		 * 
		 * @return void
		 */
		function execute_upgrade() {
			
			global $bwps_hook;

		}

	}

}

new BWPS_Away_Mode_Setup();