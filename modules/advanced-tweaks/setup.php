<?php

if ( ! class_exists( 'BWPS_Advanced_Tweaks_Setup' ) ) {

	class BWPS_Advanced_Tweaks_Setup {

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

			$options = get_site_option( 'bwps_advanced_tweaks' );

			if ( $options === false ) {

				$defaults = array(
					'enabled'					=> 0,
					'protect_files'				=> 0,
					'directory_browsing'		=> 0,
					'request_methods'			=> 0,
					'suspicious_query_strings'	=> 0,
					'non_english_characters'	=> 0,
					'long_url_strings'			=> 0,
					'write_permissions'			=> 0,
					'generator_tag'				=> 0,
					'wlwmanifest_header'		=> 0,
					'edituri_header'			=> 0,
					'theme_updates'				=> 0,
					'plugin_updates'			=> 0,
					'core_updates'				=> 0,
					'comment_spam'				=> 0,
					'random_version'			=> 0,
					'file_editor'				=> 0,
					'disable_xmlrpc'			=> 0,
				);

				add_site_option( 'bwps_advanced_tweaks', $defaults );

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

			delete_site_option( 'bwps_advanced_tweaks' );

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

new BWPS_Advanced_Tweaks_Setup();