<?php

if ( ! class_exists( 'BWPS_Away_Mode' ) ) {

	class BWPS_Away_Mode {

		private static $instance = NULL;

		private
			$settings,
			$away_file;

		private function __construct() {

			global $bwps_globals;

			$this->settings  = get_site_option( 'bwps_away_mode' );
			$this->away_file = $bwps_globals['upload_dir'] . '/bwps_away.confg'; //override file

			//Execute module functions on admin init
			if ( isset( $this->settings['enabled'] ) && $this->settings['enabled'] === 1 ) {
				add_action( 'admin_init', array( $this, 'execute_module_functions' ) );
			}

		}

		/**
		 * Check if away mode is active
		 *
		 * @param bool $forms [false] Whether the call comes from the same options form
		 * @param      array  @input[NULL] Input of options to check if calling from form
		 *
		 * @return bool true if locked out else false
		 */
		public function check_away( $form = false, $input = NULL ) {

			if ( $form === false ) {

				$test_type  = $this->settings['type'];
				$test_start = $this->settings['start'];
				$test_end   = $this->settings['end'];

			} else {

				$test_type  = $input['type'];
				$test_start = $input['start'];
				$test_end   = $input['end'];

			}

			$transaway = get_site_transient( 'bwps_away' );

			//if transient indicates away go ahead and lock them out
			if ( $form === false && $transaway === true && file_exists( $this->away_file ) ) {

				return true;

			} else { //check manually

				$current_time = current_time( 'timestamp' );

				if ( $test_type == 1 ) { //set up for daily

					$start = strtotime( date( 'n/j/y', $current_time ) . ' ' . date( 'g:i a', $test_start ) );
					$end   = strtotime( date( 'n/j/y', $current_time ) . ' ' . date( 'g:i a', $test_end ) );

					if ( $start > $end ) { //starts and ends on same calendar day

						if ( strtotime( date( 'n/j/y', $current_time ) . ' ' . date( 'g:i a', $start ) ) <= $current_time ) {

							$start = strtotime( date( 'n/j/y', $current_time ) . ' ' . date( 'g:i a', $start ) );
							$end   = strtotime( date( 'n/j/y', ( $current_time + 86400 ) ) . ' ' . date( 'g:i a', $end ) );

						} else {

							$start = strtotime( date( 'n/j/y', $current_time - 86400 ) . ' ' . date( 'g:i a', $start ) );
							$end   = strtotime( date( 'n/j/y', ( $current_time ) ) . ' ' . date( 'g:i a', $end ) );

						}

					}

					if ( $end < $current_time ) { //make sure to advance the day appropriately

						$start = $start + 86400;
						$end   = $end + 86400;

					}

				} else { //one time settings

					$start = $test_start;
					$end   = $test_end;

				}

				$remaining = $end - $current_time;

				if ( $start <= $current_time && $end >= $current_time && ( $form === true || ( $this->settings['enabled'] === 1 && file_exists( $this->away_file ) ) ) ) { //if away mode is enabled continue

					if ( $form === false ) {

						if ( get_site_transient( 'bwps_away' ) === true ) {
							delete_site_transient( 'bwps_away' );
						}

						set_site_transient( 'bwps_away', true, $remaining );

					}

					return true; //time restriction is current

				}

			}

			return false; //they are allowed to log in

		}

		/**
		 * Execute module functionality
		 *
		 * @return void
		 */
		public function execute_module_functions() {

			//execute lockout if applicable
			if ( $this->check_away() ) {
				wp_redirect( get_option( 'siteurl' ) );
				wp_clear_auth_cookie();
			}

		}

		/**
		 * Start the Away Mode module
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