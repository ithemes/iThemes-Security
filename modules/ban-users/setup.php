<?php

if ( ! class_exists( 'BWPS_Ban_Users_Setup' ) ) {

	class BWPS_Ban_Users_Setup {

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

			global $bwps_files;

			$options = get_site_option( 'bwps_ban_users' );

			if ( $options === false ) {

				$defaults = array(
					'enabled' => 0,
					'default' => 0,
					'host_list' => 1,
					'agent_list' => 1,
					'white_list' => 1,
				);

				add_site_option( 'bwps_ban_users', $defaults );

			}

			$rewrite_rules = BWPS_Ban_Users_Admin::build_rewrite_rules( array() );

			$bwps_files->set_rewrites( $rewrite_rules );

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

			delete_site_option( 'bwps_ban_users' );

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

new BWPS_Ban_Users_Setup();