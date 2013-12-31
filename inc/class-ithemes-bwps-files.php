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
		 * @param string $type         type of file to write: htaccess, wpconfig, activate, deactivate or getrules to just return existing rules
		 * @param string $section_name name of section or feature of rules
		 * @param array  $rules        array of rules to write
		 * @param bool   $insert       merge rules with existing rules
		 */
		function __construct( $type, $section_name = null, $rules = null, $insert = false ) {

			global $bwps_lib;

			$this->type         = $type; //the type of file or getrules
			$this->section_name = $section_name; // set the section name
			$this->insert       = $insert; //Whether we inserting into existing rules or not

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

				case 'deactivate': //plugin deactivation

					if ( $bwps_lib->get_server() != 'nginx' ) {
						$this->lock_file = trailingslashit( ABSPATH ) . 'bwps_htaccess.lock';
						return $this->delete_htaccess();
					}

				case 'activate': //plugin activation

					if ( $bwps_lib->get_server() != 'nginx' ) {
						$this->lock_file = trailingslashit( ABSPATH ) . 'bwps_htaccess.lock';
						return $this->write_htaccess();
					}

				default:

					return false;

			}

			if ( ! is_array( $rules ) ) { //verify rules is an array

				return false;

			} elseif ( isset( $rules['save'] ) && $rules['save'] === false ) { //if save is false we're not saving the rules to the database (the user can't change them later)

				$this->rules = $rules['rules'];

			} else { //make sure the rules themselves were sent as an array

				//Get rules from other sections from the database
				$this->rules = get_site_option( 'bwps_rewrites' );

				if ( is_array( $this->rules ) && $this->insert === true ) { //make sure the rules retrieved from the database are an array

					if ( is_array( $this->rules[$type] ) ) { //see if an array exists for the given type

						if ( is_array( $this->rules[$type][$this->section_name] ) && isset( $this->rules[$type][$this->section_name]['rules'] ) && is_array( $this->rules[$type][$this->section_name]['rules'] ) ) { //see if an array exists for the given feature

							$this->rules[$type][$this->section_name]['rules'] = array_merge( $this->rules[$type][$this->section_name]['rules'], $rules['rules'] );

						} else {

							$this->rules[$type][$this->section_name]['rules'] = $rules['rules'];

						}

					} else {

						$this->rules[$type][$this->section_name]['rules'] = $rules['rules'];

					}

				} else { //saved rules aren't an array so just create our own

					$this->rules[$type][$this->section_name]['rules'] = $rules['rules'];

				}

				//Set the rules priority for sorting or 10 for default
				if ( isset( $rules['priority'] ) ) {
					$this->rules[$type][$this->section_name]['priority'] = $rules['priority'];
				} else {
					$this->rules[$type][$this->section_name]['priority'] = 10;
				}

				update_site_option( 'bwps_rewrites', $this->rules ); //save new array rules to database

			}

			if ( $this->get_lock( $this->type ) === true ) {

				if ( ( $this->type === 'wpconfig' && $this->write_wp_config() === true ) || ( $this->type === 'htaccess' && $this->write_htaccess() === true ) ) {

					$this->release_lock( $this->type );

					return true;

				} else {

					$this->release_lock( $this->type );

				}

			} else { //couldn't get lock

				return false;

			}

			return false;

		}

		/**
		 * Builds server appropriate rewrite rules
		 * 
		 * @return array|bool The rewrite rules to use or false if there are none
		 */
		public function build_htaccess() {

			$saved_contents = get_site_option( 'bwps_rewrites' );
			$out_values = array();

			if ( $saved_contents === false ) {
				return false;
			}

			$rewrite_rules = $saved_contents['htaccess']; //only get the htaccess portion

			uasort( $rewrite_rules, array( $this, 'priority_sort' ) ); //sort by priority

			foreach ( $rewrite_rules as $key => $value ) {

				if ( is_array( $value['rules'] ) && sizeof( $value['rules'] ) > 0 ) {

					$out_values[] = "\t# BEGIN " . $key; //add section header

					foreach( $value['rules'] as $rule ) {
						$out_values[] = "\t\t" . $rule; //write all the rules
					}

					$out_values[] = "\t# END " . $key; //add section footer

				}

			}

			if ( sizeof( $out_values ) > 0 ) {
				return $out_values;
			} else {
				return false;
			}

		}

		/**
		 * Compare values of a and b for sorting
		 * 
		 * @param  string $a value a
		 * @param  string $b value b
		 * @return int    -1 if a less than b, 0 if they're equal or 1 if a is greater
		 */
		private function priority_sort( $a, $b ) {

			if( $a['priority'] == $b['priority'] ) {
				return 0;
			}

			return $a['priority'] > $b['priority'] ? 1 : -1;

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

		/**
		 * Delete htaccess rules when plugin is deactivated
		 * 
		 * @return bool true on success of false
		 */
		public function delete_htaccess() {

			global $bwps_lib, $wp_filesystem;

			$rule_open = '# BEGIN Better WP Security #';
			$rule_close = '# END Better WP Security #';

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

			$htaccess_file = $bwps_lib->get_htaccess();

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
		public function write_htaccess() {

			global $bwps_lib, $wp_filesystem;

			$rules_to_write = $this->build_htaccess(); //String of rules to insert into 

			if ( $rules_to_write === false ) { //if there is nothing to write make sure we clean up the file

				return $this->delete_htaccess();

			}

			$rule_open = '# BEGIN Better WP Security #';
			$rule_close = '# END Better WP Security #';

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

			$htaccess_file = $bwps_lib->get_htaccess();

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
		public function write_wp_config() {

			global $bwps_lib, $wp_filesystem;

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
					$rules_to_delete = false; //assume we're not deleting anything to start
					$replace = false; //assume we're note replacing anything to start with

					//build the rules we need to write, replace or delete
					foreach ( $this->rules['rules'] as $rule ) {

						if ( ( $rule['type'] === 'add' || $rule['type'] === 'replace' ) && $rule['rule'] !== false && strpos( $config_contents, $rule['search_text'] ) === false ) { //new rule or replacing a rule that doesn't exist

							$rules_to_write .= $rule['rule'] . PHP_EOL;

						} elseif ( $rule['type'] === 'replace' && $rule['rule'] !== false && strpos( $config_contents, $rule['search_text'] ) !== false ) {

							//Replacing a rule that does exist. Note this will only work on one rule at a time
							$replace = $rule['search_text'];
							$rules_to_write .= $rule['rule'];

						} elseif ( $rule['type'] === 'delete' && $rule['rule'] === false ) {

							//deleting a rule.
							if ( $rules_to_delete === false ) {
								$rules_to_delete = array();
							}

							$rules_to_delete[] = $rule;

						}

					}

					//Adding a new rule or replacing rules that don't exist
					if ( $replace === false && strlen( $rules_to_write ) > 1 ) {

						$config_contents = str_replace( '<?php' . PHP_EOL, '<?php' . PHP_EOL . $rules_to_write . PHP_EOL, $config_contents );

					} elseif ( $replace !== false || is_array( $rules_to_delete ) ) {

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
								$config_array[$line_number] = $rules_to_write;
							}

							if ( $delete_count < $delete_total ) {
							
								foreach ( $rules_to_delete as $rule ) {

									$found = false;

									if ( strpos( $line, $rule['search_text'] ) !== false ) {

										unset( $config_array[$line_number] );
										$delete_count++;
										$found = true;

									}

									if ( $found === true ) {
										break;
									}

								}

							}

						}

						$config_contents = implode( PHP_EOL, $config_array );

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

	}

}