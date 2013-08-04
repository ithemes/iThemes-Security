<?php

if ( ! class_exists( 'Bit51_BWPS_WPConfig' ) ) {

	class Bit51_BWPS_WPConfig {

		public
			$rules;

		/**
		 * Create and manage wp_config.php
		 * 
		 * @param  [plugin_class]  	  $plugin       Instance of main plugin class
		 * @param  String|array		  $rule         The rule to be added or removed
		 * @param  bool 			  $action 	    true for add, false for delete
		 */
		function __construct() {

			global $bwps_utilities;

			$this->rules = array();

			$this->rules = apply_filters( $bwps_globals['plugin_hook'] . '_wp_config_rules', $this->rules );
			
			if ( $bwps_utilities->get_lock() === true ) {

				if ( $this->write_config() === true ) {

					$bwps_utilities->release_lock();

					return true;

				} else {

					$bwps_utilities->release_lock();

				}

			}

			return new WP_Error( 'writing_error', __( 'Error when writing wp-config.php', 'better-wp-security' ) );
			
		}

		public function show_all_rules() {

		}

		private function build_rules( $rule, $config_contents, $action ) {

			if ( $action === true && strpos( $config_contents, $rule ) === false ) { //if we're adding the rule and it isn't already there

				if ( strpos( $config_contents, '// Added by Better WP Security' ) === false ) { //if there are other Better WP Security rules already present
					
					$config_contents = str_replace( '<?php', '<?php' . PHP_EOL . '// Added by Better WP Security' . PHP_EOL . $rule . PHP_EOL, $config_contents );

				} else {

					$config_contents = str_replace( '// Added by Better WP Security', '// Added by Better WP Security' . PHP_EOL . $rule, $config_contents );

				}

			} elseif ( $action === false ) { //we're deleting a rule

				if ( strpos( $config_contents, $rule ) === false ) { //it's already been deleted

					return false;

				} else {

					$config_contents = str_replace( $rule . PHP_EOL, '', $config_contents );

				}

			}

			return $config_contents;

		}

		public function write_config() {

			global $bwps_utilities;

			$url = wp_nonce_url( 'options.php?page=bwps_creds', 'bwps_write_wpconfig' );

			$form_fields = array ( 'save' );
			$method = ''; 

			if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, $form_fields ) ) ) {
				return false; // stop the normal page form from displaying
			}

			if ( ! WP_Filesystem( $creds ) ) {
    			// our credentials were no good, ask the user for them again
    			request_filesystem_credentials( $url, $method, true, false, $form_fields );
    			return false;
			}

			$config_file = $bwps_utilities->get_config();

			global $wp_filesystem;

			if ( $wp_filesystem->exists( $config_file ) ) { //check for existence

				$config_contents = $wp_filesystem->get_contents( $config_file );

    			if ( ! $config_contents ) {
      				return new WP_Error( 'reading_error', __( 'Error when reading wp-config.php', 'better-wp-security' ) ); //return error object
      			} else {

					$config_contents = $this->build_rules( $config_contents );

      			}

      		}

      		if ( $config_contents !== false ) {

				if ( ! $wp_filesystem->put_contents( $config_file, $config_contents, FS_CHMOD_FILE ) ) {
					return new WP_Error( 'writing_error', __( 'Error when writing wp-config.php', 'better-wp-security' ) ); //return error object
				}

			}

		}

	}

}