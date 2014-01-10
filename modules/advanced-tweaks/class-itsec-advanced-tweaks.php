<?php

if ( ! class_exists( 'ITSEC_Advanced_Tweaks' ) ) {

	class ITSEC_Advanced_Tweaks {

		private static $instance = NULL;

		private
			$settings;

		private function __construct() {

			$this->settings  = get_site_option( 'itsec_advanced_tweaks' );

			if ( $this->settings['enabled'] == true ) { //make sure the module was enabled

				//remove wp-generator meta tag
				if ( isset( $this->settings['generator_tag'] ) && $this->settings['generator_tag'] == true ) { 
					remove_action( 'wp_head', 'wp_generator' );
				}
				
				//remove wlmanifest link if turned on
				if ( isset( $this->settings['wlwmanifest_header'] ) && $this->settings['wlwmanifest_header'] == true ) {
					remove_action( 'wp_head', 'wlwmanifest_link' );
				}
				
				//remove rsd link from header if turned on
				if ( isset( $this->settings['edituri_header'] ) && $this->settings['edituri_header'] == true ) {
					remove_action( 'wp_head', 'rsd_link' );
				}
				
				//ban extra-long urls if turned on
				if ( isset( $this->settings['long_url_strings'] ) && $this->settings['long_url_strings'] == true && ! is_admin() ) {
				
					if ( 
						! strpos( $_SERVER['REQUEST_URI'], 'infinity=scrolling&action=infinite_scroll' ) &&
						(
							strlen( $_SERVER['REQUEST_URI'] ) > 255 ||
							strpos( $_SERVER['REQUEST_URI'], 'eval(' ) ||
							strpos( $_SERVER['REQUEST_URI'], 'CONCAT' ) ||
							strpos( $_SERVER['REQUEST_URI'], 'UNION+SELECT' ) ||
							strpos( $_SERVER['REQUEST_URI'], 'base64' ) 
						) 

					) {
						@header( 'HTTP/1.1 414 Request-URI Too Long' );
						@header( 'Status: 414 Request-URI Too Long' );
						@header( 'Cache-Control: no-cache, must-revalidate' );
						@header( 'Expires: Thu, 22 Jun 1978 00:28:00 GMT' );
						@header( 'Connection: Close' );
						@exit;
						
					}
					
				}

				//display random number for wordpress version if turned on
				if ( isset( $this->settings['random_version'] ) && $this->settings['random_version'] == true ) {
					add_action( 'plugins_loaded', array( $this, 'random_version' ) );
				}
				
				//remove theme update notifications if turned on
				if ( isset( $this->settings['theme_updates'] ) && $this->settings['theme_updates'] == true ) {
					add_action( 'plugins_loaded', array( $this, 'theme_updates' ) );
				}
				
				//remove plugin update notifications if turned on
				if ( isset( $this->settings['plugin_updates'] ) && $this->settings['plugin_updates'] == true ) {
					add_action( 'plugins_loaded', array( $this, 'public_updates' ) );
				}
				
				//remove core update notifications if turned on
				if ( isset( $this->settings['core_updates'] ) && $this->settings['core_updates'] == true ) {
					add_action( 'plugins_loaded', array( $this, 'core_updates' ) );
				}

				//Disable XML-RPC
				if ( isset( $this->settings['disable_xmlrpc'] ) && $this->settings['disable_xmlrpc'] == true ) {
					add_filter( 'xmlrpc_enabled', '__return_false' );
				}

			}

			add_action( 'wp_print_scripts', array( $this, 'get_jquery_version' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'current_jquery' ) );

		}

		public function current_jquery() {
        
			wp_deregister_script( 'jquery' );
			wp_deregister_script( 'jquery-core' );

			wp_register_script( 'jquery', false, array( 'jquery-core', 'jquery-migrate' ), '1.10.2' );
			wp_register_script( 'jquery-core', '/wp-includes/js/jquery/jquery.js', false, '1.10.2' );
			
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-core' );

		}

		/**
		 * Prevent non-admin users from seeing core updates
		 *
		 * @return void
		 */
		function core_updates() {
		
			if ( ! current_user_can( 'manage_options' ) ) {
			
				remove_action( 'admin_notices', 'update_nag', 3 );
				add_filter( 'pre_site_transient_update_core', create_function( '$a', "return null;" ) );
				wp_clear_scheduled_hook( 'wp_version_check' );
				
			}
			
		}

		/**
		 * Gets the version of jQuery enqueued
		 * 
		 * @return string|array versions of jQuery used ( array if multiple )
		 */
		function get_jquery_version(){

			global $wp_scripts;

			if ( ( is_home() || is_front_page() ) && is_user_logged_in() ) {

				// Get the WP built-in version
				$jquery_ver = $wp_scripts->registered['jquery']->ver;

				update_site_option( 'itsec_jquery_version', $jquery_ver );

			}

		}

		/**
		 * Removes plugin update notification for non-admin users
		 *
		 * @return void
		 */
		function public_updates() {
			
			if ( ! current_user_can( 'manage_options' ) ) {
			
				remove_action( 'load-update-core.php', 'wp_update_plugins' );
				add_filter( 'pre_site_transient_update_plugins', create_function( '$a', "return null;" ) );
				wp_clear_scheduled_hook( 'wp_update_plugins' );
				
			}
			
		}

		/**
		 * Display random WordPress version
		 *
		 * @return void
		 */
		function random_version() {
		
			global $wp_version;
		
			$newVersion = rand( 100,500 );
		
			//always show real version to site administrators
			if ( ! current_user_can( 'manage_options' ) ) {
			
				$wp_version = $newVersion;
				add_filter( 'script_loader_src', array( $this, 'remove_script_version' ), 15, 1 );
				add_filter( 'style_loader_src', array( $this, 	'remove_script_version' ), 15, 1 );
				
			}
			
		}
		
		/**
		 * removes version number on header scripts
		 *
		 * @param string $src script source link
		 * @return string script source link without version
		 */
		function remove_script_version( $src ){

			if ( strpos( $src, 'ver=' ) ) {
				return substr( $src, 0, strpos( $src, 'ver=' ) - 1 );
			} else {
				return $src;
			}
			
		}

		/**
		 * Remove option to update themes for non admins
		 *
		 * @return void
		 */
		function theme_updates() {
		
			if ( ! current_user_can( 'manage_options' ) ) {
			
				remove_action( 'load-update-core.php', 'wp_update_themes' );
				add_filter( 'pre_site_transient_update_themes', create_function( '$a', "return null;" ) );
				wp_clear_scheduled_hook( 'wp_update_themes' );
				
			}
			
		}

		/**
		 * Start the Away Mode module
		 *
		 * @return ITSEC_Advanced_Tweaks                The instance of the ITSEC_Advanced_Tweaks class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}