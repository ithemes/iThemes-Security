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

			global $bwps_files;

			$options = get_site_option( 'bwps_advanced_tweaks' );
			$initials = get_site_option( 'bwps_initials' );

			if ( $initials === false ) {

				$initials = array();

			}

			if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT === true ) {
				$initials['file_editor'] = true;
			} else {
				$initials['file_editor'] = false;
			}

			update_site_option( 'bwps_initials', $initials );

			if ( $options === false ) {

				if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT === true ) {
					$enabled = true;
					$file_editor = true;
				} else {
					$enabled = false;
					$file_editor = false;
				}

				$defaults = array(
					'enabled'					=> $enabled,
					'protect_files'				=> false,
					'directory_browsing'		=> false,
					'request_methods'			=> false,
					'suspicious_query_strings'	=> false,
					'non_english_characters'	=> false,
					'long_url_strings'			=> false,
					'write_permissions'			=> false,
					'generator_tag'				=> false,
					'wlwmanifest_header'		=> false,
					'edituri_header'			=> false,
					'theme_updates'				=> false,
					'plugin_updates'			=> false,
					'core_updates'				=> false,
					'comment_spam'				=> false,
					'random_version'			=> false,
					'file_editor'				=> $file_editor,
					'disable_xmlrpc'			=> false,
					'uploads_php'				=> false,
				);

				add_site_option( 'bwps_advanced_tweaks', $defaults );

			}

			$config_rules = BWPS_Advanced_Tweaks_Admin::build_wpconfig_rules( array() );
			$rewrite_rules = BWPS_Advanced_Tweaks_Admin::build_rewrite_rules( array() );

			$bwps_files->set_wpconfig( $config_rules );
			$bwps_files->set_rewrites( $rewrite_rules );

		}

		/**
		 * Execute module deactivation
		 *
		 * @return void
		 */
		function execute_deactivate() {

			global $bwps_lib;

			if ( isset( $bwps_data['htaccess_perms'] ) ) {
				@chmod( $bwps_lib->get_htaccess(), $bwps_data['htaccess_perms'] );
			}

			if ( isset( $bwps_data['config_perms'] ) ) {
				@chmod( $bwps_lib->get_config(),  $bwps_data['config_perms'] );
			}
				

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