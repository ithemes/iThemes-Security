<?php
/**
 * Dispatches setup processes from individual modules
 *
 * @version 1.0
 */

if ( ! class_exists( 'ITSEC_Setup' ) ) {

	class ITSEC_Setup {

		/**
		 * Establish setup object
		 *
		 * Establishes set object and calls appropriate execution function
		 *
		 * @param bool $case [optional] Appropriate execution module to call
		 *
		 **/
		function __construct( $case = false, $upgrading = false ) {

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
		static function on_activate() {

			define( 'ITSEC_DO_ACTIVATION', true );

			new ITSEC_Setup( 'activate' );

		}

		/**
		 * Public function to deactivate
		 *
		 **/
		static function on_deactivate() {

			if ( defined( 'ITSEC_DEVELOPMENT' ) && ITSEC_DEVELOPMENT == true ) { //set ITSEC_DEVELOPMENT to true to reset settings on deactivation for development

				$case = 'uninstall';

			} else {

				$case = 'deactivate';

			}

			new ITSEC_Setup( $case );
		}

		/**
		 * Public function to uninstall
		 *
		 **/
		static function on_uninstall() {

			new ITSEC_Setup( 'uninstall' );

		}

		/**
		 * Public function to upgrade
		 *
		 **/
		static function on_upgrade() {

			new ITSEC_Setup( 'upgrade' );

		}

		/**
		 * Execute activation
		 *
		 * @param  boolean $updating true if the plugin is updating
		 *
		 * @return void
		 */
		function activate_execute( $updating = false ) {

			global $itsec_setup_action, $itsec_files;

			//if this is multisite make sure they're network activating or die
			if ( defined( 'ITSEC_DO_ACTIVATION' ) && ITSEC_DO_ACTIVATION == true && is_multisite() && ! strpos( $_SERVER['REQUEST_URI'], 'wp-admin/network/plugins.php' ) ) {

				die ( __( '<strong>ERROR</strong>: You must activate this plugin from the network dashboard.', 'ithemes-security' ) );

			}

			if ( get_site_option( 'itsec_data' ) === false ) {
				add_site_option( 'itsec_data', array(), false );
			}

			if ( get_site_option( 'itsec_initials' ) === false ) {
				add_site_option( 'itsec_initials', array(), false );
			}

			$options = get_site_option( 'itsec_global' );

			if ( $options === false ) {

				$defaults = array(
					'notification_email'		=> get_option( 'admin_email' ),					
				);

				add_site_option( 'itsec_global', $defaults );

			}

			$itsec_setup_action = 'activate';

			do_action( 'itsec_set_plugin_data' );

			$this->do_modules();

			$itsec_files->do_activate();

		}

		/**
		 * Update Execution
		 *
		 */
		function upgrade_execute() {

			global $itsec_setup_action;

			$itsec_setup_action = 'upgrade';

			$this->do_modules();

		}

		/**
		 * Deactivate execution
		 *
		 **/
		function deactivate_execute() {

			global $itsec_setup_action, $itsec_files;

			$itsec_setup_action = 'deactivate';

			$this->do_modules();

			$itsec_files->do_deactivate();

			flush_rewrite_rules();

			if ( function_exists( 'apc_store' ) ) {
				apc_clear_cache(); //Let's clear APC (if it exists) when big stuff is saved.
			}

		}

		/**
		 * Uninstall execution
		 *
		 **/
		function uninstall_execute() {

			global $itsec_setup_action, $itsec_files;

			$itsec_setup_action = 'uninstall';

			$this->do_modules();

			$itsec_files->do_deactivate();

			delete_site_option( 'itsec_data' );
			delete_site_option( 'itsec_global' );
			delete_site_option( 'itsec_initials' );
			delete_site_option( 'itsec_jquery_version' );

			flush_rewrite_rules();

			if ( function_exists( 'apc_store' ) ) {
				apc_clear_cache(); //Let's clear APC (if it exists) when big stuff is saved.
			}

		}

	}

}
