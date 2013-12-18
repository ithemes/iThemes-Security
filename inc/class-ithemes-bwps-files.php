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
		 * @param string $type         type of file to write: htaccess or wpconfig
		 * @param string $section_name name of section or feature of rules
		 * @param array  $rules        array of rules to write
		 */
		function __construct( $type, $section_name, $rules ) {

			//Verify type is either wpconfig or htaccess
			if ( $type === 'wpconfig' ) {
				$this->lock_file = trailingslashit( ABSPATH ) . 'bwps_wpconfig.lock';
				$this->type      = 'wpconfig';
			} elseif ( $type === 'htaccess' ) {
				$this->lock_file = trailingslashit( ABSPATH ) . 'bwps_htaccess.lock';
				$this->type      = 'htaccess';
			} else {
				return new WP_Error( 'writing_error', __( 'Could not initialize Better WP Security file-writer.', 'better-wp-security' ) );
			}

			$this->section_name = $section_name; // set the section name

			if ( ! is_array( $rules ) ) { //verify rules is an array

				return new WP_Error( 'writing_error', __( 'Could not initialize Better WP Security file-writer.', 'better-wp-security' ) );

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

				return new WP_Error( 'writing_error', __( 'Rules must be entered as an array.', 'better-wp-security' ) );

			}

			if ( $this->get_lock( $this->type ) === true ) {

				if ( ( $this->type === 'wpconfig' && $this->write_wp_config() === true ) || ( $this->type === 'htaccess' && $this->write_htaccess() === true ) ) {

					$this->release_lock( $this->type );

					return true;

				} else {

					$this->release_lock( $this->type );

				}

			}

			return new WP_Error( 'writing_error', __( 'Error when writing wp-config.php', 'better-wp-security' ) );

		}

		private function build_htaccess( $rule, $config_contents, $action ) {

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
		 * Returns or echos all wp-config.php rules that can be reversed or changed manually
		 *
		 * @param bool $echo true to echo, false to return
		 *
		 * @return mixed rules string if present, false if not, nothing if echoed
		 */
		public function build_wp_config( $echo = false ) {

			global $saved_rules;

			if ( is_array( $saved_rules ) ) {

				foreach ( $saved_rules as $rule => $action ) {

					if ( array_key_exists( $rule, $this->rules ) ) {

						$this->rules[$rule] = $action;

					}

				}

			}

			$has_rules = false; //whether there are any rules to return

			$rules = $this->wpconfig_open . PHP_EOL;

			foreach ( $this->rules as $action ) {

				if ( $action == 1 ) { //array value 1 to add, 0 to skip

					$rules .= key( $this->rules ) . PHP_EOL;
					$has_rules = true;

				}

			}

			$rules .= $this->wpconfig_close;

			if ( $has_rules === false ) { //there are no rules to write

				return false;

			} elseif ( $echo === true ) { //echo the rules to the user

				echo $rules;

			} else { //return the rules for further processing

				return $rules;

			}

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

			if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, $form_fields ) ) ) {
				return false; // stop the normal page form from displaying
			}

			if ( ! WP_Filesystem( $creds ) ) {
				// our credentials were no good, ask the user for them again
				request_filesystem_credentials( $url, $method, true, false, $form_fields );

				return false;
			}

			// get the upload directory and make a test.txt file
			$upload_dir  = wp_upload_dir();
			$filename    = trailingslashit( $upload_dir['path'] ) . 'test.txt';
			$config_file = $bwps_lib->get_config();

			global $wp_filesystem;

			if ( $wp_filesystem->exists( $config_file ) ) { //check for existence

				$config_contents = $wp_filesystem->get_contents( $config_file );

				if ( ! $config_contents ) {
					return new WP_Error( 'reading_error', __( 'Error when reading wp-config.php', 'better-wp-security' ) ); //return error object
				} else {

					if ( is_array( $rule ) ) {

						foreach ( $rule as $single_rule ) {

							$config_contents = $this->build_rules( $single_rule, $config_contents, $action );

						}

					} else {

						$config_contents = $this->build_rules( $rule, $config_contents, $action );

					}

				}

			}

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
		 * @return bool|WP_Error true or WordPress error
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

					return new WP_Error( 'reading_error', __( 'Error when reading wp-config.php', 'better-wp-security' ) ); //return error object

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
					return new WP_Error( 'writing_error', __( 'Error when writing wp-config.php', 'better-wp-security' ) ); //return error object
				}

			}

		}

	}

}