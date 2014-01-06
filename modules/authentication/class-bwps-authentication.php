<?php

if ( ! class_exists( 'BWPS_Authentication' ) ) {

	class BWPS_Authentication {

		private static $instance = NULL;

		private
			$settings,
			$away_file;

		private function __construct() {

			global $bwps_globals;

			$this->settings  = get_site_option( 'bwps_authentication' );
			$this->away_file = $bwps_globals['upload_dir'] . '/bwps_away.confg'; //override file

			//require strong passwords if turned on
			if ( isset( $this->settings['strong_passwords-enabled'] ) && $this->settings['strong_passwords-enabled'] == 1 ) {
				add_action( 'user_profile_update_errors',  array( $this, 'strong_passwords' ), 0, 3 );
				
				if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'rp' || $_GET['action'] == 'resetpass' ) && isset( $_GET['login'] ) ) {
					add_action( 'login_head', array( $this, 'password_reset' ) );
				}

			}

			//Execute module functions on admin init
			if ( isset( $this->settings['away_mode-enabled'] ) && $this->settings['away_mode-enabled'] === 1 ) {
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

				$test_type  = $this->settings['away_mode-type'];
				$test_start = $this->settings['away_mode-start'];
				$test_end   = $this->settings['away_mode-end'];

			} else {

				$test_type  = $input['away_mode-type'];
				$test_start = $input['away_mode-start'];
				$test_end   = $input['away_mode-end'];

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
		 * Require strong password on password reset screen
		 *
		 * Forces a strong password on the password reset screen (if required)
		 *
		 **/
		function password_reset() {
				
			//determine the minimum role for enforcement
			$minRole = $this->settings['strong_passwords-roll'];
			
			//all the standard roles and level equivalents
			$availableRoles = array(
				"administrator"	=> "8",
				"editor" 		=> "5",
				"author" 		=> "2",
				"contributor" 	=> "1",
				"subscriber" 	=> "0"
			);
				
			//roles and subroles
			$rollists = array(
				"administrator" => array("subscriber", "author", "contributor","editor"),
				"editor" =>  array("subscriber", "author", "contributor"),
				"author" =>  array("subscriber", "contributor"),
				"contributor" =>  array("subscriber"),
				"subscriber" => array()
			);
				
			$enforce = true;
			$args = func_get_args();
			$userID = $_GET['login'];
			
			if ( $userID ) {  //if updating an existing user
			
				if ( $userInfo = get_user_by( 'login', $userID ) ) {
				
					foreach ( $userInfo->roles as $capability => $value ) {
						if ( $availableRoles[$capability] < $availableRoles[$minRole] ) {  
							$enforce = false;  
						}
					}  
				
				} else {  //a new user
			
					if ( in_array( $_POST["role"],  $rollists[$minRole]) ) {  
						$enforce = false;  
					}  
				
				}
			
			} 
			
			if ( $enforce == true ) {
				?>

				<script type="text/javascript">
					jQuery( document ).ready( function( $ ) {
						$( '#resetpassform' ).submit( function() {
							if ( ! $( '#pass-strength-result' ).hasClass( 'strong' ) ) {
								alert( '<?php _e( "Sorry, but you must enter a strong password", "better-wp-security" ); ?>' );
								return false;
							}
						});
					});
				</script>

				<?php 
				}

		}

		/**
		 * Calculates password strength
		 *
		 * Calculates strength of password entered using same algorithm
		 * as WordPress password meter
		 *
		 * @param string $i password to check
		 * @param string $f unknown
		 * @return int numerical strength of the password entered
		 **/
		function password_strength( $i, $f ) {  
		
			$h = 1; $e = 2; $b = 3; $a = 4; $d = 0; $g = null; $c = null; 
			 
			if ( strlen( $i ) < 4 )  
				return $h;  
				
			if ( strtolower( $i ) == strtolower( $f ) )  
				return $e;  
				
			if ( preg_match( "/[0-9]/", $i ) )  
				$d += 10;  
				
			if ( preg_match( "/[a-z]/", $i ) )  
				$d += 26;  
				
			if ( preg_match( "/[A-Z]/", $i ) )  
				$d += 26;  
				
			if ( preg_match( "/[^a-zA-Z0-9]/", $i ) )  
				$d += 31;  
				
			$g = log( pow( $d, strlen( $i ) ) );  
			$c = $g / log( 2 );  
			
			if ( $c < 40 )  
				return $e;  
				
			if ( $c < 56 )  
				return $b;  
				
			return $a;  
			
		}

		/**
		 * Require strong passwords
		 *
		 * Requires new passwords set are strong passwords
		 *
		 * @param object $errors WordPress errors
		 * @return object WordPress error object
		 *
		 **/
		function strong_passwords( $errors ) {  
				
			//determine the minimum role for enforcement
			$minRole = $this->settings['strong_passwords-roll'];
			
			//all the standard roles and level equivalents
			$availableRoles = array(
				'administrator'	=> '8',
				'editor' 		=> '5',
				'author' 		=> '2',
				'contributor' 	=> '1',
				'subscriber' 	=> '0'
			);
				
			//roles and subroles
			$rollists = array(
				'administrator'	=> array( 'subscriber', 'author', 'contributor', 'editor' ),
				'editor' 		=> array( 'subscriber', 'author', 'contributor' ),
				'author' 		=> array( 'subscriber', 'contributor' ),
				'contributor' 	=> array( 'subscriber' ),
				'subscriber' 	=> array(),
			);
				
			$enforce = true;
			$args = func_get_args();
			$userID = $args[2]->user_login; 
			
			if ( $userID ) {  //if updating an existing user
			
				if ( $userInfo = get_user_by( 'login', $userID ) ) {
				
					foreach ( $userInfo->roles as $capability ) {

						if ( $availableRoles[$capability] < $availableRoles[$minRole] ) {  
							$enforce = false;  
						}
						
					}  
				
				} else {  //a new user

					if ( ! empty( $_POST['role'] ) && in_array( $_POST["role"],  $rollists[$minRole]) ) {
						$enforce = false;  
					}  
				
				}
			
			} 
				
			//add to error array if the password does not meet requirements
			if ( $enforce && !$errors->get_error_data( 'pass' ) && $_POST['pass1'] && $this->password_strength( $_POST['pass1'], isset( $_POST['user_login'] ) ? $_POST['user_login'] : $userID ) != 4 ) {  
				$errors->add( 'pass', __( '<strong>ERROR</strong>: You MUST Choose a password that rates at least <em>Strong</em> on the meter. Your setting have NOT been saved.' , 'better-wp-security' ) );  
			}  

			return $errors;  
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
		 * Start the Authentication module
		 *
		 * @return BWPS_Authentication                The instance of the BWPS_Authentication class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}