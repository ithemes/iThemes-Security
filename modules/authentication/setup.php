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
					'strong_passwords-enabled'	=> false,
					'strong_passwords-roll'		=> 'administrator',
					'away_mode-enabled'			=> false,
					'hide_backend-enabled'		=> false,
					'hide_backend-slug'			=> 'wplogin',
					'hide_backend-register'		=> 'register',
					'away_mode-type'			=> false,
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

			global $bwps_lib;

			$data = get_site_option( 'bwps_data' );

			//reset .htaccess permissions to what they were when we started
			if ( isset( $data['htaccess_perms'] ) && file_exists( $bwps_lib->get_htaccess() ) ) {
				@chmod( $bwps_lib->get_htaccess(), $data['htaccess_perms'] );
			}

			//reset config persmissions to what they were when we started
			if ( isset( $data['config_perms'] ) && file_exists( $bwps_lib->get_config() ) ) {
				@chmod( $bwps_lib->get_config(), $data['config_perms'] );
			}

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