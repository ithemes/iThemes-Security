<?php

if ( ! class_exists( 'Ithemes_BWPS_Files' ) ) {

	class Ithemes_BWPS_Files {

		private
			$lock_file,
			$section_name,
			$rules,
			$type;

		/**
		 * Create and manage wp_config.php or .htaccess rewrites
		 *
		 * @param string $type         type of file to write: htaccess, wpconfig or getrules to just return existing rules
		 * @param string $section_name name of section or feature of rules
		 * @param array  $rules        array of rules to write
		 */
		function __construct( $type, $section_name, $rules ) {

			$this->type = $type; //the type of file or getrules
			$this->section_name = $section_name; // set the section name

			//get the correct lock file or just execute a rules return
			switch ( $this->type ) {

				case 'wpconfig': //we're writing to wp-config.php

					$this->lock_file = trailingslashit( ABSPATH ) . 'bwps_wpconfig.lock';
					break;

				case 'htaccess': //we're writing to .htaccess or just displaying rules

					$this->lock_file = trailingslashit( ABSPATH ) . 'bwps_htaccess.lock';
					break;

				case 'getrules': //we're just displaying rules

					return $this->build_htaccess();
					break;

				default:

					return false;

			}

			if ( ! is_array( $rules ) ) { //verify rules is an array

				return false;

			} elseif ( isset( $rules['save'] ) && $rules['save'] === false ) { //if save is false we're not saving the rules to the database (the user can't change them later)

				$this->rules = $rules['rules'];

			} elseif ( is_array( $rules['rules'] ) ) { //make sure the rules themselves were sent as an array

				$this->rules = get_site_option( 'bwps_rewrites' ); //get existing rules from database

				if ( is_array( $this->rules ) ) { //make sure the rules retrieved from the database are an array

					if ( is_array( $this->rules[$type] ) ) { //see if an array exists for the given type

						if ( is_array( $this->rules[$type][$this->section_name] ) ) { //see if an array exists for the given feature

							$this->rules = array_merge( $this->rules[$type][$this->section_name], $rules[$this->section_name] );

						} else {

							$this->rules[$type][$this->section_name] = $rules['rules'];

						}

					} else {

						$this->rules[$type][$this->section_name] = $rules['rules'];

					}

				} else { //saved rules aren't an array so just create our own

					$this->rules[$type][$this->section_name] = $rules['rules'];

				}

				update_site_option( 'bwps_rewrites', $this->rules ); //save new array rules to database

			} else {

				return false;

			}

			if ( $this->get_lock( $this->type ) === true ) {

				if ( ( $this->type === 'wpconfig' && $this->write_wp_config() === true ) || ( $this->type === 'htaccess' && $this->write_htaccess() === true ) ) {

					$this->release_lock( $this->type );

					return true;

				} else {

					$this->release_lock( $this->type );

				}

			} else { //couldn't get lock



			}

			return false;

		}

		public function build_htaccess() {

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

		/**
		 * Attempt to get a lock for atomic operations
		 *
		 * @return bool true if lock was achieved, else false
		 */
		public function get_lock() {

			if ( file_exists( $this->lock_file ) ) {

				$pid = @file_get_contents( $this->lock_file );

				if ( @posix_getsid( $pid ) !== false ) {

					return false; //file is locked for writing

				}

			}

			@file_put_contents( $this->lock_file, getmypid() );

			return true; //file lock was achieved

		}

		/**
		 * Release the lock
		 *
		 * @return bool true if released, false otherwise
		 */
		public function release_lock() {

			if ( ! file_exists( $this->lock_file ) || @unlink( $this->lock_file ) ) {
				return true;
			}

			return false;

		}

		public function write_htaccess() {

			global $bwps_lib;

			$url = wp_nonce_url( 'options.php?page=bwps_creds', 'bwps_write_wpconfig' );

			$form_fields = array( 'save' );
			$method      = '';

			if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, $form_fields ) ) ) {
				return false; // stop the normal page form from displaying
			}

			if ( ! WP_Filesystem( $creds ) ) {
				// our credentials were no good, ask the user for them again
				request_filesystem_credentials( $url, $method, true, false, $form_fields );

				return false;
			}

			$config_file = $bwps_lib->get_config();

			global $wp_filesystem;

			if ( $wp_filesystem->exists( $config_file ) ) { //check wp-config.php exists where we think it should

				$config_contents = $wp_filesystem->get_contents( $config_file ); //get the contents of wp-config.php

				if ( ! $config_contents ) { //we couldn't get wp-config.php contents

					return false;

				} else { //write out what we need to.

					$rules_to_write = ''; //String of rules to insert into wp-config

					foreach ( $this->rules as $check => $rule ) {

						if ( ( $check === 'Comment' && strstr( $config_contents, $rule ) === false ) || strstr( $config_contents, $check ) === false ) {
							$rules_to_write .= $rule . PHP_EOL;
						}

					}

					if ( strlen( $rules_to_write ) > 1 ) { //make sure we have something to write

						$config_contents = str_replace( '<?php' . PHP_EOL, '<?php' . PHP_EOL . $rules_to_write . PHP_EOL, $config_contents );

					}

				}

			}

			//Actually write the new content to wp-config.
			if ( $config_contents !== false ) {

				if ( ! $wp_filesystem->put_contents( $config_file, $config_contents, FS_CHMOD_FILE ) ) {
					return false;
				}

			}

			return true;

		}

		/**
		 * Writes given rules to wp-config.php
		 *
		 * @return bool true on success, false on failure
		 */
		public function write_wp_config() {

			global $bwps_lib;

			$url = wp_nonce_url( 'options.php?page=bwps_creds', 'bwps_write_wpconfig' );

			$form_fields = array( 'save' );
			$method      = '';

			if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, $form_fields ) ) ) {
				return false; // stop the normal page form from displaying
			}

			if ( ! WP_Filesystem( $creds ) ) {
				// our credentials were no good, ask the user for them again
				request_filesystem_credentials( $url, $method, true, false, $form_fields );

				return false;
			}

			$config_file = $bwps_lib->get_config();

			global $wp_filesystem;

			if ( $wp_filesystem->exists( $config_file ) ) { //check wp-config.php exists where we think it should

				$config_contents = $wp_filesystem->get_contents( $config_file ); //get the contents of wp-config.php

				if ( ! $config_contents ) { //we couldn't get wp-config.php contents

					return false;

				} else { //write out what we need to.

					$rules_to_write = ''; //String of rules to insert into wp-config

					foreach ( $this->rules as $check => $rule ) {

						if ( ( $check === 'Comment' && strstr( $config_contents, $rule ) === false ) || strstr( $config_contents, $check ) === false ) {
							$rules_to_write .= $rule . PHP_EOL;
						}

					}

					if ( strlen( $rules_to_write ) > 1 ) { //make sure we have something to write

						$config_contents = str_replace( '<?php' . PHP_EOL, '<?php' . PHP_EOL . $rules_to_write . PHP_EOL, $config_contents );

					}

				}

			}

			//Actually write the new content to wp-config.
			if ( $config_contents !== false ) {

				if ( ! $wp_filesystem->put_contents( $config_file, $config_contents, FS_CHMOD_FILE ) ) {
					return false;
				}

			}

			return true;

		}

	}

}