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

			global $bwps_globals;

			$upload_dir = wp_upload_dir(); //get the full upload directory array so we can grab the base directory.

			//Set plugin defaults
			$bwps_globals = array(
				'paypal_id'				=> 'V647NGJSBC882', //Paypal ID for plugin donations
				'plugin_build'			=> 4001, //plugin build number - used to trigger updates
				'plugin_file'			=> __FILE__, //the main plugin file
				'plugin_access_lvl' 	=> 'manage_options', //Access level required to access plugin options
				'plugin_dir' 			=> plugin_dir_path( __FILE__ ), //the path of the plugin directory
				'plugin_homepage' 		=> 'http://ithemes.com', //The plugins homepage on WordPress.org
				'plugin_hook'			=> 'bwps', //the hook for text calls and other areas
				'plugin_name' 			=> __( 'Better WP Security', 'better_wp_security' ), //the name of the plugin
				'plugin_url' 			=> plugin_dir_url( __FILE__ ), //the URL of the plugin directory
				'support_page' 			=> 'http://wordpress.org/support/plugin/better-wp-security/', //address of the WordPress support forums for the plugin
				'support_email'			=> 'support.bwps@fooplugins.com', //Email address support forms should be sent to
				'wordpress_page'		=> 'http://wordpress.org/extend/plugins/better-wp-security/', //plugin's page in the WordPress.org Repos
				'upload_dir'			=> $upload_dir['basedir'], // the upload directory for the WordPress installation
				'website_feed'			=> 'http://ithemes.com/blog/feed/',//Feed from plugin homepage
				'menu_name'				=> __( 'Security', 'better_wp_security' ), //main menu item name
				'menu_icon'				=> plugin_dir_url( __FILE__ ) . 'img/shield-small.png', //image icon
				'dashboard_title'		=> __( 'Dashboard', 'better_wp_security' ), //the name of the dashboard menu item (if different "Dashboard")
			);

			//load core functionality for admin use
			require_once( $bwps_globals['plugin_dir'] . 'inc/class-ithemes-bwps-core.php' );
			$this->core = Ithemes_BWPS_Core::start( $this );
			
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
