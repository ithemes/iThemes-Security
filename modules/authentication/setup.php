<?php

if ( ! class_exists( 'BWPS_Authentication_Setup' ) ) {

	class BWPS_Authentication_Setup {

		function __construct() {

			global $bwps_setup_action;

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

			$options = get_site_option( 'bwps_authentication' );

			if ( $options === false ) {

				$defaults = array(
					'strong_passwords-enabled'	=> 0,
					'strong_passwords-roll'		=> 'administrator',
					'away_mode-enabled'			=> 0,
					'away_mode-type'			=> 0,
					'away_mode-start_date'		=> 1,
					'away_mode-start_time'		=> 1,
					'away_mode-end_date'		=> 1,
					'away_mode-end_time'		=> 1,
					
				);

				add_site_option( 'bwps_authentication', $defaults );

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

			delete_site_option( 'bwps_authentication' );

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

new BWPS_Authentication_Setup();