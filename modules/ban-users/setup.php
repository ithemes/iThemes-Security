<?php

if ( ! class_exists( 'ITSEC_Ban_Users_Setup' ) ) {

	class ITSEC_Ban_Users_Setup {

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

			global $itsec_files;

			$options = get_site_option( 'itsec_ban_users' );

			if ( $options === false ) {

				$defaults = array(
					'enabled'		=> false,
					'default'		=> false,
					'host_list'		=> array(),
					'agent_list'	=> array(),
					'white_list'	=> array(),
				);

				add_site_option( 'itsec_ban_users', $defaults );

			}

			$rewrite_rules = ITSEC_Ban_Users_Admin::build_rewrite_rules( array() );

			$itsec_files->set_rewrites( $rewrite_rules );

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

			delete_site_option( 'itsec_ban_users' );

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

new ITSEC_Ban_Users_Setup();