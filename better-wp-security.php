<?php
/*
	Plugin Name: Better WP Security
	Plugin URI: http://ithemes.com
	Description: Protect your WordPress site by hiding vital areas of your site, protecting access to important files, preventing brute-force login attempts, detecting attack attempts and more.
	Version: 4.0 Dev
	Text Domain: better_wp_security
	Domain Path: /languages
	Author: iThemes
	Author URI: http://ithemes.com
	License: GPLv2
	Copyright 2011-2013  iThemes  (email : info@ithemes.com)
*/

if ( ! class_exists( 'Ithemes_BWPS' ) ) {


	/**
	 * Plugin class used to create plugin object and load both core and needed modules
	 */
	final class Ithemes_BWPS {

		private static $instance = null; //instantiated instance of this plugin

		public //see documentation upon instantiation 
			$core,
			$dashboard_menu_title,
			$dashboard_page_name,
			$bwps_globals,
			$menu_icon,
			$menu_name,
			$settings_menu_title,
			$settings_page,
			$settings_page_name,
			$top_level_menu;

		/**
		 * Default plugin execution used for settings defaults and loading components
		 * 
		 * @return void
		 */
		private function __construct() {

			global $bwps_globals, $bwps_utilities;

			$upload_dir = wp_upload_dir(); //get the full upload directory array so we can grab the base directory.

			//Set plugin defaults
			$bwps_globals = array(
				'plugin_build'			=> 4001, //plugin build number - used to trigger updates
				'plugin_file'			=> __FILE__, //the main plugin file
				'plugin_access_lvl' 	=> 'manage_options', //Access level required to access plugin options
				'plugin_dir' 			=> plugin_dir_path( __FILE__ ), //the path of the plugin directory
				'plugin_homepage' 		=> 'http://ithemes.com', //The plugins homepage on WordPress.org
				'plugin_hook'			=> 'bwps', //the hook for text calls and other areas
				'plugin_name' 			=> __( 'Better WP Security', 'better_wp_security' ), //the name of the plugin
				'plugin_url' 			=> plugin_dir_url( __FILE__ ), //the URL of the plugin directory
				'support_page' 			=> 'http://wordpress.org/support/plugin/better-wp-security/', //address of the WordPress support forums for the plugin
				'wordpress_page'		=> 'http://wordpress.org/extend/plugins/better-wp-security/', //plugin's page in the WordPress.org Repos
				'upload_dir'			=> $upload_dir['basedir'], // the upload directory for the WordPress installation
			);

			$this->top_level_menu = true; //true if top level menu, else false
			$this->menu_name = __( 'Security', 'better_wp_security' ); //main menu item name

			//the following options must only be set if it's a top-level section
			$this->settings_page = false; //when using top_level menus this will always create a "Dashboard" page. Should it create a settings page as well?
			$this->menu_icon = $bwps_globals['plugin_url'] . 'img/shield-small.png'; //image icon 
			$this->dashboard_menu_title = __( 'Dashboard', 'better_wp_security' ); //the name of the dashboard menu item (if different "Dashboard")
			$this->dashboard_page_name = __( 'Dashboard', 'better_wp_security' ); //page name - appears after plugin name on the dashboard page

			//load core functionality for admin use
			require_once( $bwps_globals['plugin_dir'] . 'inc/class-ithemes-bwps-core.php' );
			$this->core = Ithemes_BWPS_Core::start( $this );

			//load utility functions
			require_once( $bwps_globals['plugin_dir'] . 'inc/class-ithemes-bwps-utilities.php' );
			$bwps_utilities = Ithemes_BWPS_Utilities::start( $this->core );

			//load modules
			$this->load_modules();

			//builds admin menus after modules are loaded
			if ( is_admin() ) {
				$this->core->build_admin(); 
			}
			
		}

		/**
		 * Loads required plugin modules
		 *
		 * Note: Do not modify this area other than to specify modules to load. 
		 * Build all functionality into the appropriate module.
		 * 
		 * @return void
		 */
		public function load_modules() {

			global $bwps_globals;

			//load BWPS Dashboard module
			require_once( $bwps_globals['plugin_dir'] . 'modules/ithemes-bwps-dashboard/class-ithemes-bwps-dashboard.php' );
			Ithemes_BWPS_Dashboard::start( $this->core );

			//load BWPS Dashboard module
			require_once( $bwps_globals['plugin_dir'] . 'modules/bwps-support-page/class-bwps-support-page.php' );
			BWPS_Support_Page::start( $this->core );

			//load Foo Plugins Support module
			require_once( $bwps_globals['plugin_dir'] . 'modules/bwps-foo-support/class-bwps-foo-support.php' );
			BWPS_Foo_Support::start( $this->core );

			//load Away Mode Module
			require_once( $bwps_globals['plugin_dir'] . 'modules/bwps-away-mode/class-bwps-away-mode.php' );
			BWPS_Away_Mode::start( $this->core );

			//load Ban Users Module
			require_once( $bwps_globals['plugin_dir'] . 'modules/bwps-ban-users/class-bwps-ban-users.php' );
			BWPS_Ban_Users::start( $this->core );

			//load Content Directory Module
			require_once( $bwps_globals['plugin_dir'] . 'modules/bwps-content-directory/class-bwps-content-directory.php' );
			BWPS_Content_Directory::start( $this->core );
			
		}

		/**
		 * Start the plugin
		 * 
		 * @return Ithemes_BWPS     The instance of the plugin
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self;
			}

			return self::$instance;

		}

	}

}

Ithemes_BWPS::start();
