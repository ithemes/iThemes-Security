<?php

if ( ! class_exists( 'BWPS_Ban_Users' ) ) {

	class BWPS_Ban_Users {

		private static $instance = NULL;

		private function __construct() {

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
		 * Start the Ban Users module
		 *
		 * @return BWPS_Away_Mode                The instance of the BWPS_Away_Mode class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}