<?php
/*
	Plugin Name: iThemes Security
	Plugin URI: http://ithemes.com
	Description: Protect your WordPress site by hiding vital areas of your site, protecting access to important files, preventing brute-force login attempts, detecting attack attempts and more.
	Version: 4.0 Dev
	Text Domain: ithemes_security
	Domain Path: /languages
	Author: iThemes
	Author URI: http://ithemes.com
	License: GPLv2
	Copyright 2014  iThemes  (email : info@ithemes.com)
*/

if ( ! class_exists( 'Ithemes_ITSEC' ) ) {

	/**
	 * Plugin class used to create plugin object and load both core and needed modules
	 */
	final class Ithemes_ITSEC {

		private static $instance = null; //instantiated instance of this plugin

		public //see documentation upon instantiation 
			$core, $itsec_globals;

		/**
		 * Default plugin execution used for settings defaults and loading components
		 *
		 * @return void
		 */
		private function __construct() {

			global $itsec_globals;

			$upload_dir = wp_upload_dir(); //get the full upload directory array so we can grab the base directory.

			//Set plugin defaults
			$itsec_globals       = array( 
				'plugin_build'       => 4001, //plugin build number - used to trigger updates
				'plugin_access_lvl'  => 'manage_options', //Access level required to access plugin options
				'plugin_name'        => __( 'iThemes Security', 'ithemes-security' ), //the name of the plugin
				'plugin_file'        => __FILE__, //the main plugin file
				'plugin_dir'         => plugin_dir_path( __FILE__ ), //the path of the plugin directory
				'plugin_url'         => plugin_dir_url( __FILE__ ), //the URL of the plugin directory
				'upload_dir'         => $upload_dir['basedir'], // the upload directory for the WordPress installation
				'ithemes_dir'        => $upload_dir['basedir'] . '/ithemes-security', //folder for saving iThemes Security files
				'ithemes_log_dir'    => $upload_dir['basedir'] . '/ithemes-security/logs', //folder for saving iThemes Security logs
				'ithemes_backup_dir' => $upload_dir['basedir'] . '/ithemes-security/backups', //folder for saving iThemes Backup files
			);

			//load core functionality for admin use
			require_once( $itsec_globals['plugin_dir'] . 'inc/class-itsec-core.php' );
			$this->core = ITSEC_Core::start();

		}

		/**
		 * Start the plugin
		 *
		 * @return Ithemes_ITSEC     The instance of the plugin
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self;
			}

			return self::$instance;

		}

	}

}

Ithemes_ITSEC::start();
