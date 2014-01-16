<?php

if ( ! class_exists( 'ITSEC_SSL_Setup' ) ) {

	class ITSEC_SSL_Setup {

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

			$options  = get_site_option( 'itsec_ssl' );
			$initials = get_site_option( 'itsec_initials' );

			if ( defined( 'FORCE_SSL_LOGIN' ) && FORCE_SSL_LOGIN === true ) {
				$initials['login'] = true;
			} else {
				$initials['login'] = false;
			}

			if ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN === true ) {
				$initials['admin'] = true;
			} else {
				$initials['admin'] = false;
			}

			update_site_option( 'itsec_initials', $initials );

			if ( $options === false ) {

				if ( defined( 'FORCE_SSL_LOGIN' ) && FORCE_SSL_LOGIN === true ) {
					$login = true;
				} else {
					$login = false;
				}

				if ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN === true ) {
					$admin = true;
				} else {
					$admin = false;
				}

				$defaults = array(
					'frontend' => 0,
					'admin'    => $admin,
					'login'    => $login,
				);

				add_site_option( 'itsec_ssl', $defaults );

			}

		}

		/**
		 * Execute module deactivation
		 *
		 * @return void
		 */
		function execute_deactivate() {

			global $itsec_files;

			$config_rules = ITSEC_Advanced_Tweaks_Admin::build_wpconfig_rules( false );
			$itsec_files->set_wpconfig( $config_rules );

		}

		/**
		 * Execute module uninstall
		 *
		 * @return void
		 */
		function execute_uninstall() {

			$this->execute_deactivate();

			delete_site_option( 'itsec_ssl' );

			delete_metadata( 'post', null, 'itsec_enable_ssl', null, true );

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

new ITSEC_SSL_Setup();