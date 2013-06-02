<?php
/*
	Plugin Name: Better WP Security
	Plugin URI: http://bit51.com/software/better-wp-security/
	Description: Protect your WordPress site by hiding vital areas of your site, protecting access to important files, preventing brute-force login attempts, detecting attack attempts and more.
	Version: Dev
	Text Domain: better_wp_security
	Domain Path: /languages
	Author: Bit51
	Author URI: http://bit51.com
	License: GPLv2
	Copyright 2011-2013  Bit51  (email : info@bit51.com)
*/

if ( ! class_exists( 'Bit51_BWPS' ) ) {


	/**
	 * Plugin class used to create plugin object and load both core and needed modules
	 */
	final class Bit51_BWPS {

		private static $instance = null; //instantiated instance of this plugin

		public //see documentation upon instantiation 
			$core,
			$dashboard_menu_title,
			$dashboard_page_name,
			$globals,
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

			//Set plugin defaults
			$this->globals = array(
				'plugin_build'			=> 3062, //plugin build number - used to trigger updates
				'plugin_file'			=> __FILE__, //the main plugin file
				'plugin_access_lvl' 	=> 'manage_options', //Access level required to access plugin options
				'plugin_dir' 			=> plugin_dir_path( __FILE__ ), //the path of the plugin directory
				'plugin_homepage' 		=> 'http://bit51.com/software/better-wp-security/', //The plugins homepage on WordPress.org
				'plugin_hook'			=> 'better_wp_security', //the hook for text calls and other areas
				'plugin_name' 			=> __( 'Better WP Security', 'better_wp_security' ), //the name of the plugin
				'plugin_url' 			=> plugin_dir_url( __FILE__ ), //the URL of the plugin directory
				'support_page' 			=> 'http://wordpress.org/support/plugin/better-wp-security', //address of the WordPress support forums for the plugin
				'wordpress_page'		=> 'http://wordpress.org/extend/plugins/better-wp-security/', //plugin's page in the WordPress.org Repos
			);

			$this->top_level_menu = true; //true if top level menu, else false
			$this->menu_name = __( 'Security', $this->globals['plugin_hook'] ); //main menu item name

			//the following options must only be set if it's a top-level section
			$this->settings_page = true; //when using top_level menus this will always create a "Dashboard" page. Should it create a settings page as well?
			$this->menu_icon = $this->globals['plugin_url'] . 'img/shield-small.png'; //image icon 
			$this->dashboard_menu_title = __( 'Dashboard', $this->globals['plugin_hook'] ); //the name of the dashboard menu item (if different "Dashboard")
			$this->settings_menu_title = __( 'Settings', $this->globals['plugin_hook'] ); //the name of the settings menu item (if different from "Settings")
			$this->dashboard_page_name = __( 'Dashboard', $this->globals['plugin_hook'] ); //page name - appears after plugin name on the dashboard page
			$this->settings_page_name = __( 'Options', $this->globals['plugin_hook'] ); //page name - appears after plugin name on the dashboard page
			

			//load core functionality for admin use
			require_once( $this->globals['plugin_dir'] . 'inc/class-bit51-bwps-core.php' );
			$this->core = Bit51_BWPS_Core::start( $this );

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

			//load BWPS Dashboard module
			require_once( $this->globals['plugin_dir'] . 'modules/bit51-bwps-dashboard/class-bit51-bwps-dashboard.php' );
			Bit51_BWPS_Dashboard::start( $this->core );
			
		}

		/**
		 * Start the plugin
		 * 
		 * @return Bit51_BWPS     The instance of the plugin
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self;
			}

			return self::$instance;

		}

	}

}

Bit51_BWPS::start();
