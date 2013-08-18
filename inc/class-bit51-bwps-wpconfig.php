<?php

if ( ! class_exists( 'Bit51_BWPS_WPConfig' ) ) {

	class Bit51_BWPS_WPConfig {

		private
			$rule_open = '//BEGIN Better WP Security',
			$rule_close = '//END Better WP Security';

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

			global $bwps_globals, $bwps_utilities;

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

		/**
		 * Returns or echos all wp-config.php rules
		 *
		 * @param bool $echo true to echo, false to return
		 * @return mixed rules string if present, false if not, nothing if echoed
		 */
		public function show_all_rules( $echo = false ) {

			$has_rules = false; //whether there are any rules to return

			$rules = $this->rule_open . PHP_EOL;

			foreach ( $this->rules as $action ) {

				if ( $action == 1 ) { //array value 1 to add, 0 to skip

					$rules .= key( $this->rules ) . PHP_EOL;
					$has_rules = true;

				}

			}

			$rules .= $this->rule_close;

			if ( $has_rules === false ) { //there are no rules to write

				return false;

			} elseif ( $echo === true ) { //echo the rules to the user

				echo $rules;

			} else { //return the rules for further processing

				return $rules;

			}

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

			if ( $wp_filesystem->exists( $config_file ) ) { //check wp-config.php exists where we think it should

				$config_contents = $wp_filesystem->get_contents( $config_file ); //get the contents of wp-config.php

    			if ( ! $config_contents ) { //we couldn't get wp-config.php contents

      				return new WP_Error( 'reading_error', __( 'Error when reading wp-config.php', 'better-wp-security' ) ); //return error object

      			} else { //write out what we need to.

				    if ( ( $start = strpos( $config_contents, $this->rule_open ) ) !== false ) {

					    $end = strpos( $config_contents, $this->rule_close ) + strlen( $this->rule_close ) - $start;

					    $config_contents = substr_replace( $config_contents, '', $start, $end );

				    }

					$rules = $this->show_all_rules();

				    if ( $rules !== false ) {

					    $config_contents = str_replace( '<?php' . PHP_EOL, '<?php' . PHP_EOL . $rules, $config_contents );

				    }

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