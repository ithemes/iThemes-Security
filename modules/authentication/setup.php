<?php

if ( ! class_exists( 'ITSEC_Authentication_Setup' ) ) {

	class ITSEC_Authentication_Setup {

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

			$options = get_site_option( 'itsec_authentication' );

			if ( $options === false ) {

				$defaults = array(
					'brute_force-enabled'			=> false,
					'brute_force-max_attempts_host'	=> 5,
					'brute_force-max_attempts_user'	=> 10,
					'brute_force-check_period'		=> 5,
					'brute_force-lockout_period'	=> 15,
					'brute_force-blacklist'			=> true,
					'brute_force-blacklist_count'	=> 3,
					'strong_passwords-enabled'		=> false,
					'strong_passwords-roll'			=> 'administrator',
					'away_mode-enabled'				=> false,
					'hide_backend-enabled'			=> false,
					'hide_backend-slug'				=> 'wplogin',
					'hide_backend-register'			=> 'wp-register.php',
					'away_mode-type'				=> false,
					'away_mode-start_date'			=> 1,
					'away_mode-start_time'			=> 1,
					'away_mode-end_date'			=> 1,
					'away_mode-end_time'			=> 1,
					'other-login_errors'			=> false,
					
				);

				add_site_option( 'itsec_authentication', $defaults );

			}

		}

		/**
		 * Execute module deactivation
		 *
		 * @return void
		 */
		function execute_deactivate() {

			global $itsec_lib;

			$data = get_site_option( 'itsec_data' );

			//reset .htaccess permissions to what they were when we started
			if ( isset( $data['htaccess_perms'] ) && file_exists( $itsec_lib->get_htaccess() ) ) {
				@chmod( $itsec_lib->get_htaccess(), $data['htaccess_perms'] );
			}

			//reset config persmissions to what they were when we started
			if ( isset( $data['config_perms'] ) && file_exists( $itsec_lib->get_config() ) ) {
				@chmod( $itsec_lib->get_config(), $data['config_perms'] );
			}

		}

		/**
		 * Execute module uninstall
		 *
		 * @return void
		 */
		function execute_uninstall() {

			$this->execute_deactivate();

			delete_site_option( 'itsec_authentication' );

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

new ITSEC_Authentication_Setup();