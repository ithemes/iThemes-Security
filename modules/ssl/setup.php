<?php

if ( ! class_exists( 'BWPS_SSL_Setup' ) ) {

	class BWPS_SSL_Setup {

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

			$options = get_site_option( 'bwps_ssl' );

			if ( $options === false ) {

				if ( defined( 'FORCE_SSL_LOGIN' ) && FORCE_SSL_LOGIN === true ) {
					$login = 1;
				} else {
					$login = 0;
				}

				if ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN === true ) {
					$admin = 1;
				} else {
					$admin = 0;
				}

				$defaults = array(
					'frontend'	=> 0,
					'admin'		=> $admin,
					'login'		=> $login,
				);

				add_site_option( 'bwps_ssl', $defaults );

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

			delete_site_option( 'bwps_ssl' );
			delete_metadata( 'post', null, 'bwps_enable_ssl', null, true );

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

new BWPS_SSL_Setup();