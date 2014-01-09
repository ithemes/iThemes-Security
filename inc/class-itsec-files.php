<?php

if ( ! class_exists( 'ITSEC_Files' ) ) {

	class ITSEC_Files {

		private static $instance = NULL; //instantiated instance of this plugin

		private 
			$rewrite_rules,
			$wpconfig_rules,
			$rewrite_lock_file,
			$wpconfig_lock_file;

		/**
		 * Create and manage wp_config.php or .htaccess rewrites
		 *
		 */
		function __construct() {

			add_action( 'plugins_loaded', array( $this, 'file_writer_init' ) );

		}

		/**
		 * Initialize file writer and rules arrays
		 * 
		 * @return void
		 */
		public function file_writer_init() {

			global $itsec_lib;

			$all_rules = array(); //initialize rules array
			$this->rewrite_rules = array(); //rewrite rules that will need to be written
			$this->wpconfig_rules = array(); //wp-config rules that will need to be written
			
			$this->rewrite_lock_file = trailingslashit( ABSPATH ) . 'itsec_rewrites.lock';
			$this->wpconfig_lock_file = trailingslashit( ABSPATH ) . 'itsec_config.lock';

			$all_rules = apply_filters( 'itsec_file_rules', $all_rules );

			if ( sizeof( $all_rules ) > 0 ) {

				foreach ( $all_rules as $rule ) {

					if ( $rule['type'] === 'htaccess' ) {

						$this->rewrite_rules[] = $rule;

					} elseif ( $rule['type'] === 'wpconfig' ) {

						$this->wpconfig_rules[] = $rule;

					}

				}

			}

		}

		/**
		 * Sets rewrite rules (if updated after initialization)
		 * 
		 * @param rules $rules array of rules to add or replace
		 */
		public function set_rewrites( $rules ) {

			if ( is_array( $rules ) ) {

				//Loop through each rule we send and have to find duplicates
				foreach ( $rules as $rule ) {

					$found = false;

					if ( is_array( $rule ) ) {

						if ( sizeof( $this->rewrite_rules ) > 0 ) {

							foreach ( $this->rewrite_rules as $key => $rewrite_rule ) {
								
								if ( $rule['name'] == $rewrite_rule['name'] ) {

									$found = true;
									$this->rewrite_rules[$key] = $rule;

								}

								if ( $found === true ) { //don't keep looping if we don't have to
									break;
								}

							}

						}

						if ( $found === false ) {

							$this->rewrite_rules[] = $rule;

						} else {

							break;

						}

					}

				}

			}

		}

		/**
		 * Sets wp-config.php rules (if updated after initialization)
		 * 
		 * @param rules $rules array of rules to add or replace
		 */
		public function set_wpconfig( $rules ) {

			if ( is_array( $rules ) ) {

				//Loop through each rule we send and have to find duplicates
				foreach ( $rules as $rule ) {

					$found = false;

					if ( is_array( $rule ) ) {

						if ( sizeof( $this->rewrite_rules ) > 0 ) {

							foreach ( $this->wpconfig_rules as $key => $wpconfig_rule ) {
								
								if ( $rule['name'] == $wpconfig_rule['name'] ) {

									$found = true;
									$this->wpconfig_rules[$key] = $rule;

								}

								if ( $found === true ) { //don't keep looping if we don't have to
									break;
								}

							}

						}

						if ( $found === false ) {

							$this->wpconfig_rules[] = $rule;

						} else {

							break;

						}

					}

				}

			}

		}

		/**
		 * Saves all rewrite rules to htaccess or similar file
		 *
		 * @return bool       true on success, false on failure
		 */
		public function save_rewrites() {
			
			if ( $this->get__file_lock( 'htaccess') ) {

				$success = $this->write_rewrites(); //save the return value for success/error flag

			} else { //return false if we can't get a file lock

				return false;

			}

			$this->release_file_lock( 'htaccess');

			return $success;

		}

		/**
		 * Saves all wpconfig rules to wp-config.php
		 * 
		 * @return bool       true on success, false on failure
		 */
		public function save_wpconfig() {

			$success = $this->write_wpconfig(); //save the return value for success/error flag

			return $success;
			
			if ( $this->get__file_lock( 'wpconfig') ) {

				$success = $this->write_wpconfig(); //save the return value for success/error flag

			} else { //return false if we can't get a file lock

				return false;

			}

			$this->release_file_lock( 'wpconfig');

			return $success;

		}

		/**
		 * Execute activation functions
		 * 
		 * @return void
		 */
		public function do_activate() {

			$this->save_wpconfig();
			$this->save_rewrites();

		}

		/**
		 * Execute deactivation functions
		 * 
		 * @return void
		 */
		public function do_deactivate() {

			$this->delete_rewrites();
			$this->save_wpconfig();

		}

		/**
		 * Builds server appropriate rewrite rules
		 * 
		 * @return array|bool The rewrite rules to use or false if there are none
		 */
		private function build_rewrites() {

			$out_values = array();
			$rewrite_rules = $this->rewrite_rules; //only get the htaccess portion

			uasort( $rewrite_rules, array( $this, 'priority_sort' ) ); //sort by priority

			foreach ( $rewrite_rules as $key => $value ) {

				if ( is_array( $value['rules'] ) && sizeof( $value['rules'] ) > 0 ) {

					$out_values[] = "\t# BEGIN " . $value['name']; //add section header

					foreach( $value['rules'] as $rule ) {
						$out_values[] = "\t\t" . $rule; //write all the rules
					}

					$out_values[] = "\t# END " . $value['name']; //add section footer

				}

			}

			if ( sizeof( $out_values ) > 0 ) {
				return $out_values;
			} else {
				return false;
			}

		}

		/**
		 * Delete htaccess rules when plugin is deactivated
		 * 
		 * @return bool true on success of false
		 */
		private function delete_rewrites() {

			global $itsec_lib, $wp_filesystem;

			$rule_open = '# BEGIN iThemes WP Security #';
			$rule_close = '# END iThemes WP Security #';

			$url = wp_nonce_url( 'options.php?page=itsec_creds', 'itsec_write_wpconfig' );

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

			$htaccess_file = $itsec_lib->get_htaccess();

			//Make sure we can write to the file
			$perms = substr( sprintf( '%o', fileperms( $htaccess_file ) ), -4 );

			if ( $perms == '0444' ) {
				@chmod( $htaccess_file, 0644 );
			}

			//make sure the file exists and create it if it doesn't
			if ( ! $wp_filesystem->exists( $htaccess_file ) ) {

				$wp_filesystem->touch( $htaccess_file );

			}

			$htaccess_contents = $wp_filesystem->get_contents( $htaccess_file ); //get the contents of the htaccess or nginx file

			if ( $htaccess_contents === false ) { //we couldn't get the file contents

				return false;

			} else { //write out what we need to.

				$lines = explode( PHP_EOL, $htaccess_contents ); //create an array to make this easier
				$state = false;

				foreach ( $lines as $line_number => $line ) { //for each line in the file

					if ( strpos( $line, $rule_open ) !== false ) { //if we're at the beginning of the section
						$state = true;
					}

					if ( $state == true ) { //as long as we're not in the section keep writing

						unset( $lines[$line_number] );

					}

					if ( strpos( $line, $rule_close ) !== false ) { //see if we're at the end of the section
						$state = true;
					}

				}

				$htaccess_contents = implode( PHP_EOL, $lines );

				if ( ! $wp_filesystem->put_contents( $htaccess_file, $htaccess_contents, FS_CHMOD_FILE ) ) {
					return false;
				}

			}

			//reset file permissions if we changed them
			if ( $perms == '0444' ) {
				@chmod( $htaccess_file, 0444 );
			}

			return true;

		}

		/**
		 * Writes given rules to htaccess or related file
		 *
		 * @return bool true on success, false on failure
		 */
		private function write_rewrites() {

			global $itsec_lib, $wp_filesystem;

			$rules_to_write = $this->build_rewrites(); //String of rules to insert into 

			if ( $rules_to_write === false ) { //if there is nothing to write make sure we clean up the file

				return $this->delete_rewrites();

			}

			$rule_open = '# BEGIN iThemes WP Security #';
			$rule_close = '# END iThemes WP Security #';

			$url = wp_nonce_url( 'options.php?page=itsec_creds', 'itsec_write_wpconfig' );

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

			$htaccess_file = $itsec_lib->get_htaccess();

			//Make sure we can write to the file
			$perms = substr( sprintf( '%o', fileperms( $htaccess_file ) ), -4 );

			if ( $perms == '0444' ) {
				@chmod( $htaccess_file, 0644 );
			}

			//make sure the file exists and create it if it doesn't
			if ( ! $wp_filesystem->exists( $htaccess_file ) ) {

				$wp_filesystem->touch( $htaccess_file );

			}

			$htaccess_contents = $wp_filesystem->get_contents( $htaccess_file ); //get the contents of the htaccess or nginx file

			if ( $htaccess_contents === false ) { //we couldn't get the file contents

				return false;

			} else { //write out what we need to.

				$lines = explode( PHP_EOL, $htaccess_contents ); //create an array to make this easier
				$state = false;

				foreach ( $lines as $line_number => $line ) { //for each line in the file

					if ( strpos( $line, $rule_open ) !== false ) { //if we're at the beginning of the section
						$state = true;
					}

					if ( $state == true ) { //as long as we're not in the section keep writing

						unset( $lines[$line_number] );

					}

					if ( strpos( $line, $rule_close ) !== false ) { //see if we're at the end of the section
						$state = true;
					}

				}

				if ( sizeof( $rules_to_write ) > 0 ) { //make sure we have something to write

					$htaccess_contents = $rule_open . PHP_EOL . implode( PHP_EOL, $rules_to_write ) . implode( PHP_EOL, $lines ) . PHP_EOL . $rule_close . PHP_EOL;

				}

				//Actually write the new content to wp-config.
				if ( $htaccess_contents !== false ) {

					if ( ! $wp_filesystem->put_contents( $htaccess_file, $htaccess_contents, FS_CHMOD_FILE ) ) {
						return false;
					}

				}

			}

			//reset file permissions if we changed them
			if ( $perms == '0444' ) {
				@chmod( $htaccess_file, 0444 );
			}

			return true;

		}

		/**
		 * Writes given rules to wp-config.php
		 *
		 * @return bool true on success, false on failure
		 */
		private function write_wpconfig() {

			global $itsec_lib, $wp_filesystem;

			$url = wp_nonce_url( 'options.php?page=itsec_creds', 'itsec_write_wpconfig' );

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

			$config_file = $itsec_lib->get_config();

			//Make sure we can write to the file
			$perms = substr( sprintf( '%o', fileperms( $config_file ) ), -4 );

			if ( $perms == '0444' ) {
				@chmod( $config_file, 0644 );
			}

			if ( $wp_filesystem->exists( $config_file ) ) { //check wp-config.php exists where we think it should

				$config_contents = $wp_filesystem->get_contents( $config_file ); //get the contents of wp-config.php

				if ( ! $config_contents ) { //we couldn't get wp-config.php contents

					return false;

				} else { //write out what we need to.

					$rules_to_write = ''; //String of rules to insert into wp-config
					$rule_to_replace = ''; //String containing a rule to be replaced
					$rules_to_delete = false; //assume we're not deleting anything to start
					$replace = false; //assume we're note replacing anything to start with

					//build the rules we need to write, replace or delete
					foreach ( $this->wpconfig_rules as $section_rule ) {

						foreach ( $section_rule['rules'] as $rule ) {

							$found = false;

							if ( ( $rule['type'] === 'add' ) && $rule['rule'] !== false ) { //new rule or replacing a rule that doesn't exist

								$rules_to_write .= $rule['rule'] . PHP_EOL;

							} elseif ( $rule['type'] === 'replace' && $rule['rule'] !== false && strpos( $config_contents, $rule['search_text'] ) !== false ) {

								//Replacing a rule that does exist. Note this will only work on one rule at a time
								$replace = $rule['search_text'];
								$rule_to_replace .= $rule['rule'];
								$found = true;

							}

							if ( $found !== true ) {

								//deleting a rule.
								if ( $rules_to_delete === false ) {
									$rules_to_delete = array();
								}

								$rules_to_delete[] = $rule;

							}

						}

					}

					//delete and replace
					if ( $replace !== false || is_array( $rules_to_delete ) ) {

						$config_array = explode( PHP_EOL, $config_contents );

						if ( is_array( $rules_to_delete ) ) {
							$delete_count = 0;
							$delete_total = sizeof( $rules_to_delete );
						} else {
							$delete_total = 0;
							$delete_count = 0;
						}

						foreach ( $config_array as $line_number => $line ) {

							if ( strpos( $line, $replace ) !== false ) {
								$config_array[$line_number] = $rule_to_replace;
							}

							if ( $delete_count < $delete_total ) {
							
								foreach ( $rules_to_delete as $rule ) {

									if ( strpos( $line, $rule['search_text'] ) !== false ) {

										unset( $config_array[$line_number] );

										//delete the following line(s) if they is blank
										$count = 1;
										while ( strlen( trim( $config_array[$line_number + $count] ) ) < 1 ) {
											unset( $config_array[$line_number + 1] );
											$count++;

										}

										$delete_count++;

									}	

								}

							}

						}

						$config_contents = implode( PHP_EOL, $config_array );

					}

					//Adding a new rule or replacing rules that don't exist
					if ( strlen( $rules_to_write ) > 1 ) {

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

			//reset file permissions if we changed them
			if ( $perms == '0444' ) {
				@chmod( $config_file, 0444 );
			}

			return true;

		}

		/**
		 * Attempt to get a lock for atomic operations
		 *
		 * @param string $type type of file lock, htaccess or wpconfig
		 *
		 * @return bool true if lock was achieved, else false
		 */
		private function get__file_lock( $type ) {

			if ( $type === 'htaccess' ) {
				$lock_file = $this->rewrite_lock_file;
			} elseif ( $type === 'wpconfig' ) {
				$lock_file = $this->wpconfig_lock_file;
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
		 * @param string $type type of file lock, htaccess or wpconfig
		 *
		 * @return bool true if released, false otherwise
		 */
		private function release_file_lock( $type ) {

			if ( $type === 'htaccess' ) {
				$lock_file = $this->rewrite_lock_file;
			} elseif ( $type === 'wpconfig' ) {
				$lock_file = $this->wpconfig_lock_file;
			} else {
				return false;
			}

			if ( ! file_exists( $lock_file ) || @unlink( $lock_file ) ) {
				return true;
			}

			return false;

		}

		/**
		 * Sorts given arrays py priority key
		 * 
		 * @param  string $a value a
		 * @param  string $b value b
		 * 
		 * @return int    -1 if a less than b, 0 if they're equal or 1 if a is greater
		 */
		private function priority_sort( $a, $b ) {

			if( $a['priority'] == $b['priority'] ) {
				return 0;
			}

			return $a['priority'] > $b['priority'] ? 1 : -1;

		}

		/**
		 * Start the global library instance
		 *
		 * @return ITSEC_Files          The instance of the ITSEC_Files class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}