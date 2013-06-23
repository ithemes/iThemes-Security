<?php

if ( ! class_exists( 'Bit51_BWPS_WPConfig' ) ) {

	class Bit51_BWPS_WPConfig {

		private static $instance = null; //instantiated instance of this plugin

		private 
			$plugin,
			$rules;

		private function __construct( $plugin ) {

			$this->plugin = $plugin;
			$this->rules = '';

			$this->write_config();

			if ( defined( 'BWPS_WRITE_CONFIG' ) ) {

				die( var_dump( $this->rules ) );

			}

		}

		/**
		 * Gets location of wp-config.php
		 *
		 * Finds and returns path to wp-config.php
		 *
		 * @return string path to wp-config.php
		 *
		 **/
		private function get_config() {
		
			if ( file_exists( trailingslashit( ABSPATH ) . 'wp-config.php' ) ) {
			
				return trailingslashit( ABSPATH ) . 'wp-config.php';
				
			} else {
			
				return trailingslashit( dirname( ABSPATH ) ) . 'wp-config.php';
				
			}
			
		}

		/**
		 * Generate rules for wp-config.php
		 * 
		 * @return void
		 */
		public function get_rules() {

			$this->rules = apply_filters( $this->plugin->globals['plugin_hook'] . '_add_wp_config_rule', $this->rules );

		}

		public function write_config() {

			$url = wp_nonce_url( 'options.php?page=bwps_creds', 'bwps_write_wpconfig' );

			if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, $form_fields ) ) ) {
				return true; // stop the normal page form from displaying
			}

			if ( ! WP_Filesystem($creds) ) {
    			// our credentials were no good, ask the user for them again
    			request_filesystem_credentials($url, $method, true, false, $form_fields);
    			return true;
			}

			// get the upload directory and make a test.txt file
			$upload_dir = wp_upload_dir();
			$filename = trailingslashit( $upload_dir['path'] ) . 'test.txt';
			$config_file = $this->get_config();

			global $wp_filesystem;

			if ( $wp_filesystem->exists ( $config_file ) ) { //check for existence

				$config_contents = $wp_filesystem->get_contents( $config_file );

    			if ( ! $config_contents ) {
      				return new WP_Error( 'reading_error', __( 'Error when reading wp-config.php', 'better-wp-security' ) ); //return error object
      			} else {
      				// @todo build config output
      			}

      		}

			if ( ! $wp_filesystem->put_contents( $config_file, $config_contents, FS_CHMOD_FILE ) ) {
				return new WP_Error( 'writing_error', __( 'Error when writing wp-config.php', 'better-wp-security' ) ); //return error object
			}

		}

		/**
		 * Start the global instance
		 * 
		 * @param  [plugin_class]  $plugin       Instance of main plugin class
		 * @return bwps_Core                     The instance of the bwps_Core class
		 */
		public static function start( $plugin ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $plugin );
			}

			return self::$instance;

		}

	}

}