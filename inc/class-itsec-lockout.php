<?php

if ( ! class_exists( 'ITSEC_Lockout' ) ) {

	class ITSEC_Lockout {

		private static $instance = NULL; //instantiated instance of this plugin

		private 
			$settings,
			$current_time,
			$current_time_gmt;

		function __construct() {

			$this->settings = get_site_option( 'itsec_global' );
			$this->current_time = current_time( 'timestamp' ); 
			$this->current_time_gmt = current_time( 'timestamp', 1 ); 

			//Run database cleanup daily with cron
			if ( ! wp_next_scheduled( 'itsec_purge_lockouts' ) ) {
				wp_schedule_event( time(), 'daily', 'itsec_purge_lockouts' );
			}

			add_action( 'itsec_purge_lockouts', array( $this, 'purge_lockouts' ) );

		}

		/**
		 * Locks out given user or host
		 * 
		 * @param  string $type   The type of lockout (for user reference)
		 * @param  string $reason Reason for lockout, for notifications
		 * @param  string $host   Host to lock out
		 * @param  int    $user   user id to lockout
		 * 
		 * @return void
		 */
		public function lockout( $type, $reason, $host = null, $user = null ) {

			global $wpdb, $itsec_lib;

			//Do we have a good host to lock out or not
			if ( $host != null && ITSEC_Ban_Users_Admin::is_ip_whitelisted( sanitize_text_field( $host ) ) === false && $itsec_lib->validates_ip_address( $host ) === true ) {
				$good_host = sanitize_text_field( $host );
			} else {
				$good_host = false;
			}

			//Do we have a valid user to lockout or not
			if ( $user !== null && $itsec_lib->user_id_exists( intval( $user ) ) === true ) {
				$good_user = intval( $user );
			} else {
				$good_user = false;
			}

			$blacklist_host = false; //assume we're not permanently blcking the host

			//Sanitize the data for later
			$type = sanitize_text_field( $type );
			$reason = sanitize_text_field( $reason );

			if ( $this->settings['blacklist'] === true && $good_host !== false ) { //permanent blacklist

				$host_count = $wpdb->get_var( "SELECT COUNT(*) FROM `" . $wpdb->base_prefix . "itsec_lockouts` WHERE `lockout_expire` > '" . date( 'Y-m-d H:i:s', $this->current_time ) . "' AND lockout_host='" . esc_sql( $host ) . "';" ) + 1;

				if ( $host_count >= $this->settings['blacklist_count'] ) {

					$host_expiration = false;

					ITSEC_Ban_Users_Admin::insert_ip( sanitize_text_field( $host ) ); //Send it to the Ban Users module for banning

					$blacklist_host = true; //flag it so we don't do a temp ban as well

				}

			} 

			//We have temp bans to perform
			if ( $good_host !== false || $good_user !== false ) {

				$exp_seconds = ( intval( $this->settings['lockout_period'] ) * 60 );
				$expiration = date( 'Y-m-d H:i:s', $this->current_time + $exp_seconds );
				$expiration_gmt = date( 'Y-m-d H:i:s', $this->current_time_gmt + $exp_seconds );

				if ( $good_host !== false && $blacklist_host === false ) { //temp lockout host

					$host_expiration = $expiration;

					$wpdb->insert(
						$wpdb->base_prefix . 'itsec_lockouts',
						array(
							'lockout_type'			=> $type,
							'lockout_start'			=> date( 'Y-m-d H:i:s', $this->current_time ),
							'lockout_start_gmt'		=> date( 'Y-m-d H:i:s', $this->current_time_gmt ),
							'lockout_expire'		=> $expiration,
							'lockout_expire_gmt'	=> $expiration_gmt,
							'lockout_host'			=> sanitize_text_field( $host ),
							'lockout_user'			=> '',
						)
					);

				}

				if ( $good_user !== false ) { //blacklist host and temp lockout user

					$user_expiration = $expiration;
					
					$wpdb->insert(
						$wpdb->base_prefix . 'itsec_lockouts',
						array(
							'lockout_type'			=> $type,
							'lockout_start'			=> date( 'Y-m-d H:i:s', $this->current_time ),
							'lockout_start_gmt'		=> date( 'Y-m-d H:i:s', $this->current_time_gmt ),
							'lockout_expire'		=> $expiration,
							'lockout_expire_gmt'	=> $expiration_gmt,
							'lockout_host'			=> '',
							'lockout_user'			=> intval( $user ),
						)
					);

				} 

			}

			if ( $this->settings['email_notifications'] === true ) { //send email notifications
				$this->send_lockout_email( $good_host, $good_user, $host_expiration, $user_expiration, $reason );
			}

		}

		public function check_lockout( $host = null, $user = null ) {

		}

		/**
		 * Purges lockouts more than 7 days old from the database
		 * 
		 * @return void
		 */
		public function purge_lockouts() {

			global $wpdb;
			
			$wpdb->query( "DELETE FROM `" . $wpdb->base_prefix . "itsec_lockouts` WHERE `lockout_expire` < '" . date( 'Y-m-d H:i:s', $this->current_time - 604800 ) . "';" );
			
		}

		private function send_lockout_email( $host, $user, $host_expiration, $user_expiration, $reason ) {

			$plural_text = __( 'has', 'ithemes-security' );

			//Tell which host was locked out
			if ( $host !== false ) {

				$host_expiration_text = __( 'The host has been locked out ', 'ithemes-security' );

				if ( $host_expiration === false ) {
					$host_expiration_text .= __( 'permanently', 'ithemes-security' );
				} else {
					$host_expiration_text .= sprintf( '%s %s', __( 'until', 'ithemes-security' ), sanitize_text_field( $host_expiration ) );
				}

				$host_text = sprintf( '%s, <a href="http://ip-adress.com/ip_tracer/%s">%s</a>, ', __( 'host', 'ithemes-security' ), sanitize_text_field( $host ), sanitize_text_field( $host ) );

			} else {

				$host_expiration_text = '';
				$host_text = '';

			}

			

			$user_object = get_userdata( $user ); //try to get and actual user object

			//Tell them which user was locked out
			if ( $user_object !== false ) {

				if ( $host_text === '' ) {

					$user_expiration_text = sprintf( '%s %s.', __( 'The user has been locked out until', 'ithemes-security' ), sanitize_text_field( $user_expiration ) );

					$user_text = sprintf( '%s, %s, ', __( 'user', 'ithemes-security' ), $user_object->user_login );

				} else {

					$user_expiration_text = sprintf( '%s %s.', __( 'and the user has been locked out until', 'ithemes-security' ), sanitize_text_field( $user_expiration ) );
					$plural_text = __( 'have', 'ithemes-security' );
					$user_text = sprintf( '%s, %s, ', __( 'and a user', 'ithemes-security' ), $user_object->user_login );
				}

			} else {
				$user_expiration_text = '.';
				$user_text = '';
			}

			$body = sprintf( 
				'%s %s %s %s %s %s %s %s. %s %s', 
				__( 'A', 'ithemes-security' ), 
				$host_text, 
				$user_text,
				$plural_text,
				__( ' been locked out of the WordPress site at', 'ithemes-security' ),
				get_option( 'siteurl' ),
				__( 'due to', 'ithemes-security' ),
				sanitize_text_field( $reason ),
				$host_expiration_text,
				$user_expiration_text,
				__( '', 'ithemes-security' )
			);

			die( $body );

			$recipients = $this->settings['notification_email'];
			$subject = '[' . get_option( 'siteurl' ) . '] ' . __( 'Site Lockout Notification', 'ithemes-security' );
			$headers = 'From: ' . get_bloginfo( 'name' )  . ' <' . $toEmail . '>' . "\r\n\\";

			foreach ( $recipients as $recipient ) {



			}

		}

		/**
		 * Start the global lockout instance
		 *
		 * @return ITSEC_Lockout         The instance of the ITSEC_Lockout class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}