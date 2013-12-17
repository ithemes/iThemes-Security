<?php

if ( ! class_exists( 'Ithemes_BWPS_Files' ) ) {

	class Ithemes_BWPS_Files {

		private
			$lock_file,
			$section_name,
			$rules,
			$type;

		/**
		 * Create and manage wp_config.php
		 *
		 */
		function __construct( $type, $section_name, $rules ) {

			global $bwps_globals;

			if ( $type  === 'wpconfig' ) {
				$this->lock_file = trailingslashit( ABSPATH ) . 'bwps_wpconfig.lock';
				$this->type = 'wpconfig';
			} elseif ( $type === 'htaccess' ) {
				$this->lock_file = trailingslashit( ABSPATH ) . 'bwps_htaccess.lock';
				$this->type = 'htaccess';
			} else {
				return new WP_Error( 'writing_error', __( 'Could not initialize Better WP Security file-writer.', 'better-wp-security' ) );
			}

			$this->section_name = $section_name;
			$this->rules = $rules;

			$this->wpconfig_rules = apply_filters( $bwps_globals['plugin_hook'] . '_wp_config_rules', $this->rules );

			if ( $this->get_lock( 'wpconfig' ) === true ) {

				if ( $this->write_wp_config() === true ) {

					$this->release_lock( 'wpconfig' );

					return true;

				} else {

					$this->release_lock( 'wpconfig' );

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
		 * Returns or echos all wp-config.php rules
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
		 * @param string $type [htaccess] type of lock: htaccess or wpconfig
		 *
		 * @return bool true if lock was achieved, else false
		 */
		public function get_lock( $type = 'htaccess' ) {

			if ( $type === 'htaccess' ) {
				$lock_file = $this->htaccess_lock;
			} elseif ( $type === 'wpconfig' ) {
				$lock_file = $this->wpconfig_lock;
			} else {
				return false;
			}

			if ( file_exists( $lock_file ) ) {

				$pid = @file_get_contents( $lock_file );

				if ( @posix_getsid( $pid ) !== false ) {

					return false; //file is locked for writing

				}

			}

			@file_put_contents( $lock_file, getmypid() );

			return true; //file lock was achieved

		}

		/**
		 * Release the lock
		 *
		 * @param string $type [htaccess] type of lock: htaccess or wpconfig
		 *
		 * @return bool true if released, false otherwise
		 */
		public function release_lock( $type = 'htaccess' ) {

			if ( $type === 'htaccess' ) {
				$lock_file = $this->htaccess_lock;
			} elseif ( $type === 'wpconfig' ) {
				$lock_file = $this->wpconfig_lock;
			} else {
				return false;
			}

			if ( ! file_exists( $lock_file ) || @unlink( $lock_file ) ) {
				return true;
			}

			return false;

		}

		public function write_htaccess( $rule, $action ) {

			global $bwps_lib;

			$url = wp_nonce_url( 'options.php?page=bwps_creds', 'bwps_write_wpconfig' );

			if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, $form_fields ) ) ) {
				return true; // stop the normal page form from displaying
			}

			if ( ! WP_Filesystem( $creds ) ) {
				// our credentials were no good, ask the user for them again
				request_filesystem_credentials( $url, $method, true, false, $form_fields );

				return true;
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
					return new WP_Error( 'writing_error', __( 'Error when writing wp-config.php', 'better-wp-security' ) ); //return error object
				}

			}

		}

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

					if ( ( $start = strpos( $config_contents, $this->rule_open ) ) !== false ) {

						$end = strpos( $config_contents, $this->rule_close ) + strlen( $this->rule_close ) - $start;

						$config_contents = substr_replace( $config_contents, '', $start, $end );

					}

					$rules = $this->build_wp_config();

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