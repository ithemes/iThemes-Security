<?php

if ( ! class_exists( 'Bit51_BWPS_WPConfig' ) ) {

	class Bit51_BWPS_WPConfig {

		private static $instance = null; //instantiated instance of this plugin

		private 
			$plugin;

		/**
		 * Create and manage wp_config.php
		 * 
		 * @param  [plugin_class]  	  $plugin       Instance of main plugin class
		 * @param  String|array		  $rule         The rule to be added or removed
		 * @param  bool 			  $action 	    true for add, false for delete
		 */
		private function __construct( $plugin, $rule, $action ) {

			global $bwps_globals;

			//attempt to lock the process for safety
			$lock_file = $bwps_globals['upload_dir'] . '/config.lock';

			if ( file_exists( $lock_file ) ) {

				$pid = @file_get_contents( $lock_file );

				if ( @posix_getsid( $pid ) !== false) {



					return false; //file is locked for writing
				
				} 

			}

			@file_put_contents( $lock_file, getmypid() );
			
			$this->plugin = $plugin;

			if ( $this->write_config( $rule, $action ) === true ) {

				unlink( $lock_file );

				return true;

			} else {

				unlink( $lock_file );

				return false;

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

		private function build_rules( $rule, $config_contents, $action ) {

			if ( $action === true && strpos( $config_contents, $rule ) === false ) {

				if ( strpos( $config_contents, '// Added by Better WP Security' ) === false ) {
					
					$rule = '// Added by Better WP Security' . PHP_EOL . $rule . PHP_EOL;
					$config_contents = str_replace( '<?php' . PHP_EOL, '<?php' . PHP_EOL . $rule . PHP_EOL, $config_contents );

				} else {

					$config_contents = str_replace( '// Added by Better WP Security' . PHP_EOL, '// Added by Better WP Security' . PHP_EOL . $rule . PHP_EOL, $config_contents );

				}

			} elseif ( $action === false ) {

				if ( strpos( $config_contents, $rule ) === false ) {

					return true;

				} else {

					$config_contents = str_replace( $rule . PHP_EOL, '', $config_contents );

				}

			}

			return $config_contents;

		}

		public function write_config( $rule, $action ) {

			$url = wp_nonce_url( 'options.php?page=bwps_creds', 'bwps_write_wpconfig' );

			if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, $form_fields ) ) ) {
				return true; // stop the normal page form from displaying
			}

			if ( ! WP_Filesystem($creds) ) {
    			// our credentials were no good, ask the user for them again
    			request_filesystem_credentials( $url, $method, true, false, $form_fields );
    			return true;
			}

			// get the upload directory and make a test.txt file
			$upload_dir = wp_upload_dir();
			$filename = trailingslashit( $upload_dir['path'] ) . 'test.txt';
			$config_file = $this->get_config();

			global $wp_filesystem;

			if ( $wp_filesystem->exists( $config_file ) ) { //check for existence

				$config_contents = $wp_filesystem->get_contents( $config_file );

    			if ( ! $config_contents ) {
      				return new WP_Error( 'reading_error', __( 'Error when reading wp-config.php', 'better-wp-security' ) ); //return error object
      			} else {

      				if ( is_array( $rule ) ) {

      					foreach ( $rule as $single_rule ) {

      						$config_contents = $this->build_rules( $rule, $single_rule, $action );

      					}

      				} else {

      					$config_contents = $this->build_rules( $rule, $config_contents, $action );

      				}

      			}

      		}

			if ( ! $wp_filesystem->put_contents( $config_file, $config_contents, FS_CHMOD_FILE ) ) {
				return new WP_Error( 'writing_error', __( 'Error when writing wp-config.php', 'better-wp-security' ) ); //return error object
			}

		}

		/**
		 * Start the global instance
		 * 
		 * @param  [plugin_class]     $plugin       Instance of main plugin class
		 * @param  String|array		  $rule         The rule to be added or removed
		 * @param  bool 			  $action 	    true for add, false for delete
		 * @return bwps_Core                        The instance of the bwps_Core class
		 */
		public static function start( $plugin, $rule, $action ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $plugin, $rule, $action );
			}

			return self::$instance;

		}

	}

}