<?php

if ( ! class_exists( 'Ithemes_BWPS_Utilities' ) ) {

	final class Ithemes_BWPS_Utilities {

		private static $instance = NULL; //instantiated instance of this plugin

		public
			$plugin;

		private
			$lock_file;

		/**
		 * Loads core functionality across both admin and frontend.
		 *
		 * @param Ithemes_BWPS $plugin
		 */
		private function __construct( $plugin ) {

			$this->plugin = $plugin; //Allow us to access plugin defaults throughout

			$this->lock_file = trailingslashit( ABSPATH ) . 'config.lock';

			//load file utility classes
			require( dirname( __FILE__ ) . '/class-ithemes-bwps-files.php' );

		}

		/**
		 * Gets location of wp-config.php
		 *
		 * Finds and returns path to wp-config.php
		 *
		 * @return string path to wp-config.php
		 *
		 **/
		public function get_config() {

			if ( file_exists( trailingslashit( ABSPATH ) . 'wp-config.php' ) ) {

				return trailingslashit( ABSPATH ) . 'wp-config.php';

			} else {

				return trailingslashit( dirname( ABSPATH ) ) . 'wp-config.php';

			}

		}

		/**
		 * Attempt to get a lock for atomic operations
		 *
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
		 * Determine whether we're on the login page or not
		 *
		 * @return bool true if is login page else false
		 */
		public function is_login_page() {

			return in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) );

		}

		/**
		 * Release the lock
		 *
		 *
		 * @return bool true if released, false otherwise
		 */
		public function release_lock() {

			if ( @unlink( $this->lock_file ) ) {
				return true;
			}

			return false;

		}

		/**
		 * Gets location of .htaccess
		 *
		 * Finds and returns path to .htaccess
		 *
		 * @return string path to .htaccess
		 *
		 **/
		public function get_htaccess() {

			return ABSPATH . '.htaccess';

		}

		/**
		 * Returns the actual IP address of the user
		 *
		 * @return  String The IP address of the user
		 *
		 * */
		public function get_ip() {

			//Just get the headers if we can or else use the SERVER global
			if ( function_exists( 'apache_request_headers' ) ) {

				$headers = apache_request_headers();

			} else {

				$headers = $_SERVER;

			}

			//Get the forwarded IP if it exists
			if ( array_key_exists( 'X-Forwarded-For', $headers ) && ( filter_var( $headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) || filter_var( $headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) ) {

				$the_ip = $headers['X-Forwarded-For'];

			} else {

				$the_ip = $_SERVER['REMOTE_ADDR'];

			}

			return $the_ip;

		}

		/**
		 * Validates a host list
		 *
		 * @param string $hosts string of hosts to check
		 * @param bool   $ban   true for ban list, false for whitelist
		 *
		 * @return array array of good hosts or false
		 */
		public function validate_bad_hosts( $hosts ) {

			//validate list
			$banhosts      = explode( PHP_EOL, $hosts );
			$list          = array();
			$error_handler = NULL;

			if ( ! empty( $banhosts ) ) {

				foreach ( $banhosts as $host ) {

					$host = filter_var( $host, FILTER_SANITIZE_STRING );

					if ( strlen( $host ) > 0 ) {

						$ipParts   = explode( '.', $host );
						$isIP      = 0;
						$partcount = 1;
						$goodip    = true;
						$foundwild = false;

						foreach ( $ipParts as $part ) {

							if ( $goodip == true ) {

								if ( ( is_numeric( trim( $part ) ) && trim( $part ) <= 255 && trim( $part ) >= 0 ) || trim( $part ) == '*' ) {
									$isIP ++;
								}

								switch ( $partcount ) {

									case 1:

										if ( trim( $part ) == '*' ) {

											$goodip = false;

											if ( ! is_wp_error( $error_handler ) ) { //invalid ip
												$error_handler = new WP_Error();
											}

											$error_handler->add( 'error', __( filter_var( $host, FILTER_SANITIZE_STRING ) . ' is note a valid ip.', 'better_wp_security' ) );

										}

										break;

									case 2:

										if ( trim( $part ) == '*' ) {

											$foundwild = true;

										}

										break;

									default:

										if ( trim( $part ) != '*' ) {

											if ( $foundwild == true ) {

												$goodip = false;

												if ( ! is_wp_error( $error_handler ) ) { //invalid ip
													$error_handler = new WP_Error();
												}

												$error_handler->add( 'error', __( filter_var( $host, FILTER_SANITIZE_STRING ) . ' is note a valid ip.', 'better_wp_security' ) );

											}

										} else {

											$foundwild = true;

										}

										break;

								}

								$partcount ++;

							}

						}

						if ( ip2long( trim( str_replace( '*', '0', $host ) ) ) == false ) { //invalid ip

							if ( ! is_wp_error( $error_handler ) ) {
								$error_handler = new WP_Error();
							}

							$error_handler->add( 'error', __( filter_var( $host, FILTER_SANITIZE_STRING ) . ' is not a valid ip.', 'better_wp_security' ) );

						} elseif ( strlen( $host > 4 && ! in_array( $host, $list ) ) ) {

							$list[] = trim( $host );

						}

					}

				}

			}

			if ( sizeof( $list ) > 1 ) {
				sort( $list );
				$list = array_unique( $list, SORT_STRING );
			}

			if ( is_wp_error( $error_handler ) ) {
				return $error_handler;
			} else {
				return $list;
			}

		}

		public function validate_bad_agents( $agents ) {

		}

		/**
		 * Start the global utilities instance
		 *
		 * @param  [plugin_class]  $plugin       Instance of main plugin class
		 *
		 * @return Ithemes_BWPS_Utilities          The instance of the Ithemes_BWPS_Utilities class
		 */
		public static function start( $plugin ) {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self( $plugin );
			}

			return self::$instance;

		}

	}

}