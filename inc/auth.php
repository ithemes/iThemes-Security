<?php

if ( ! function_exists( 'wp_authenticate' ) ) {


	/**
	 * Replace WordPress built-in authentication function
	 * 
	 * Replaces WP authentication function to allow for logging
	 * login errors and removing messages if needed
	 *
	 * @param string $username user name
	 * @param string $password user submitted password
	 *
	 * @return object 	WordPress user object
	 *
	 */
	function wp_authenticate( $username, $password ) {
	
		global $bwps, $bwpsoptions;
		
		//if away mode is currently restricting login return to homepage
		if ( $bwps->checkaway() ) {
		
			wp_redirect( get_option( 'siteurl' ) );
			
		}
	
		$username = sanitize_user( $username );
		$password = trim( $password );
	
		$user = apply_filters( 'authenticate', null, $username, $password );
		
		//if they're locked out due to too many bad logins display an error
		if ( $bwpsoptions['ll_enabled'] == 1 && $bwps->checklock( $username ) ) {
		
			do_action( 'wp_login_failed', $username );
					
			return  new WP_Error( 'incorrect_password', __( '<strong>ERROR</strong>: We are sorry , your ability to login has been suspended due to too many recent failed login attempts. Please try again later.', $bwps->hook ) );
					
		}

		//if there is no valud user object
		if ( $user == null ) {
		
			if( $bwpsoptions['ll_enabled'] == 1 ) {
				$bwps->logevent( '1' );
			}
			$user = new WP_Error( 'authentication_failed', __( '<strong>ERROR</strong>: Invalid username or incorrect password.' ) );
			
		}

		$ignore_codes = array( 'empty_username', 'empty_password' );
		
		
		//log if bad logins
		if ( isset( $_POST['wp-submit'] ) && $bwpsoptions['ll_enabled'] == 1 && is_wp_error( $user ) ) {
		
			$bwps->logevent( '1', $username );
			
		} elseif ( is_wp_error( $user ) && ! in_array( $user->get_error_code(), $ignore_codes ) ) {
		
			if ( $bwpsoptions['ll_enabled'] == 1 ) {
				$bwps->logevent( '1', $username );
			}
			do_action( 'wp_login_failed', $username );
			
		}

		return $user; //returns user object or error message
		
	}
	
}
