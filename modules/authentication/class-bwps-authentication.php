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
			if ( isset( $this->settings['strong_passwords-enabled'] ) && $this->settings['strong_passwords-enabled'] == true ) {
				add_action( 'user_profile_update_errors',  array( $this, 'enforce_strong_password' ), 0, 3 );
				
				if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'rp' || $_GET['action'] == 'resetpass' ) && isset( $_GET['login'] ) ) {
					add_action( 'login_head', array( $this, 'enforce_strong_password' ) );
				}

				add_action( 'admin_enqueue_scripts', array( $this, 'login_script_js' ) );
                add_action( 'login_enqueue_scripts', array( $this, 'login_script_js' ) );

			}

			//Execute module functions on admin init
			if ( isset( $this->settings['away_mode-enabled'] ) && $this->settings['away_mode-enabled'] === true ) {
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
		 * Enqueue script to check password strength
		 * 
		 * @return void
		 */
		public function login_script_js() {

			global $bwps_globals;

			wp_enqueue_script( 'bwps_authentication', $bwps_globals['plugin_url'] . 'modules/authentication/js/authentication.js', 'jquery', $bwps_globals['plugin_build'] );
			
			//make sure the text of the warning is translatable
   			wp_localize_script( 'bwps_authentication', 'strong_password_error_text', array( 'text' => __( 'Sorry, but you must enter a strong password.', 'better-wp-security' ) ) );

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
		function enforce_strong_password( $errors ) {  
				
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
				
			$password_meets_requirements = false;
			$args = func_get_args();
			$userID = isset( $args[2]->user_login ) ? $args[2]->user_login : $_GET['login']; 
			
			if ( $userID ) {  //if updating an existing user
			
				if ( $userInfo = get_user_by( 'login', $userID ) ) {
				
					foreach ( $userInfo->roles as $capability ) {

						if ( $availableRoles[$capability] >= $availableRoles[$minRole] ) {  
							$password_meets_requirements = true;  
						}
						
					}  
				
				} else {  //a new user

					if ( ! empty( $_POST['role'] ) && ! in_array( $_POST["role"],  $rollists[$minRole] ) ) {
						$password_meets_requirements = true;  
					}  
				
				}
			
			} 

			if ( $password_meets_requirements === true ) {
				?>

				<script type="text/javascript">
					jQuery( document ).ready( function() {
						jQuery( '#resetpassform' ).submit( function() {
							if ( ! jQuery( '#pass-strength-result' ).hasClass( 'strong' ) ) {
								alert( '<?php _e( "Sorry, but you must enter a strong password", "better-wp-security" ); ?>' );
								return false;
							}
						} );
					} );
				</script>

				<?php
			}
				
			if ( ! isset( $_GET['action'] ) ) {
			
				//add to error array if the password does not meet requirements
				if ( $password_meets_requirements && ! $errors->get_error_data( 'pass' ) && isset( $_POST['pass1'] ) && isset( $_POST['password_strength'] ) &&  $_POST['password_strength'] != 'strong' ) {  
					$errors->add( 'pass', __( '<strong>ERROR</strong>: You MUST Choose a password that rates at least <em>Strong</em> on the meter. Your setting have NOT been saved.' , 'better-wp-security' ) );  
				}  

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