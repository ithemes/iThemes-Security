<?php
/**
 * Dispatches setup processes from individual modules
 *
 * @version 1.0
 */

if ( ! class_exists( 'Ithemes_BWPS_Setup' ) ) {

	class Ithemes_BWPS_Setup {

		private
			$hook,
			$update;

		/**
		 * Establish setup object
		 *
		 * Establishes set object and calls appropriate execution function
		 *
		 * @param bool $case [optional] Appropriate execution module to call
		 *
		 **/
		function __construct( $case = false, $upgrading = false ) {

			//Important, this must be manually set in each plugin
			$this->hook = 'bwps';

			if ( ! $case ) {
				die( 'error' );
			}

			switch ( $case ) {
				case 'activate': //active plugin
					$this->activate_execute( $upgrading );
					break;

				case 'upgrade': //active plugin
					$this->upgrade_execute();
					break;

				case 'deactivate': //deactivate plugin
					$this->deactivate_execute();
					break;

				case 'uninstall': //uninstall plugin
					$this->uninstall_execute();
					break;
			}

		}

		/**
		 * Execute setup script for each module installed
		 *
		 * @return void
		 */
		function do_modules() {

			$modules_folder = dirname( __FILE__ ) . '/../modules';

			$modules = scandir( $modules_folder );

			foreach ( $modules as $module ) {

				$module_folder = $modules_folder . '/' . $module;

				if ( $module !== '.' && $module !== '..' && is_dir( $module_folder ) && file_exists( $module_folder . '/setup.php' ) ) {

					require_once( $module_folder . '/setup.php' );

				}

			}

		}

		/**
		 * Public function to activate
		 *
		 **/
		function on_activate() {

			define( 'BWPS_NEW_INSTALL', true );
			new Ithemes_BWPS_Setup( 'activate' );

		}

		/**
		 * Public function to deactivate
		 *
		 **/
		function on_deactivate() {

			$devel = true; //set to true to uninstall for development

			if ( $devel ) {
				$case = 'uninstall';
			} else {
				$case = 'deactivate';
			}

			new Ithemes_BWPS_Setup( $case );
		}

		/**
		 * Public function to uninstall
		 *
		 **/
		function on_uninstall() {

			new Ithemes_BWPS_Setup( 'uninstall' );

		}

		/**
		 * Public function to upgrade
		 *
		 **/
		function on_upgrade() {

			new Ithemes_BWPS_Setup( 'upgrade' );

		}

		/**
		 * Execute activation
		 *
		 * @param  boolean $updating true if the plugin is updating
		 *
		 * @return void
		 */
		function activate_execute( $updating = false ) {

			global $bwps_setup_action;

			//if this is multisite make sure they're network activating or die
			if ( defined( 'BWPS_NEW_INSTALL' ) && BWPS_NEW_INSTALL == true && is_multisite() && ! strpos( $_SERVER['REQUEST_URI'], 'wp-admin/network/plugins.php' ) ) {

				die ( __( '<strong>ERROR</strong>: You must activate this plugin from the network dashboard.', 'better-wp-security' ) );

			}

			$bwps_setup_action = 'activate';

			$this->do_modules();

			do_action( $this->hook . '_set_plugin_data' );

		}

		/**
		 * Update Execution
		 *
		 * @param  string $oldversion Old version number
		 *
		 * @return void
		 */
		function upgrade_execute( $oldversion = '' ) {

			global $bwps_setup_action;

			$bwps_setup_action = 'upgrade';

			$this->do_modules();

		}

		/**
		 * Deactivate execution
		 *
		 **/
		function deactivate_execute( $updating = false ) {

			global $bwps_setup_action;

			$bwps_setup_action = 'deactivate';

			$this->do_modules();

		}

		/**
		 * Uninstall execution
		 *
		 **/
		function uninstall_execute() {

			global $bwps_setup_action;

			$bwps_setup_action = 'uninstall';

			$this->do_modules();

			delete_site_option( $this->hook . '_data' );

			if ( function_exists( 'apc_store' ) ) {
				apc_clear_cache(); //Let's clear APC (if it exists) when big stuff is saved.
			}

		}

	}

}
