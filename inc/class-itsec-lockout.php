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

		}

		public function lockout( $type, $reason, $host = null, $user = null ) {

			global $wpdb, $itsec_lib;

			if ( $host != null && ITSEC_Ban_Users_Admin::is_ip_whitelisted( sanitize_text_field( $host ) ) === false ) {
				$good_host = $itsec_lib->validates_ip_address( $host );
			} else {
				$good_host = false;
			}

			if ( $user !== null ) {
				$good_user = $itsec_lib->user_id_exists( intval( $user ) );
			} else {
				$good_user = false;
			}

			$blacklist_host = false;

			$type = sanitize_text_field( $type );
			$reason = sanitize_text_field( $reason );

			if ( $this->settings['blacklist'] === true && $good_host === true ) { //permanent blacklist

				$host_count = $wpdb->get_var( "SELECT COUNT(*) FROM `" . $wpdb->base_prefix . "itsec_lockouts` WHERE `lockout_expire` > '" . date( 'Y-m-d H:i:s', $this->current_time ) . "' AND lockout_host='" . esc_sql( $host ) . "';" ) + 1;

				if ( $host_count >= $this->settings['blacklist_count'] ) {

					ITSEC_Ban_Users_Admin::insert_ip( sanitize_text_field( $host ) );

					$blacklist_host = true;

				}

			} 

			if ( $good_host === true || $good_user !== false ) {

				$exp_seconds = ( intval( $this->settings['lockout_period'] ) * 60 );
				$exp = date( 'Y-m-d H:i:s', $this->current_time + $exp_seconds );
				$exp_gmt = date( 'Y-m-d H:i:s', $this->current_time_gmt + $exp_seconds );

				if ( $good_host === true && $blacklist_host === false ) { //temp lockout host

					$wpdb->insert(
						$wpdb->base_prefix . 'itsec_lockouts',
						array(
							'lockout_type'			=> $type,
							'lockout_start'			=> date( 'Y-m-d H:i:s', $this->current_time ),
							'lockout_start_gmt'		=> date( 'Y-m-d H:i:s', $this->current_time_gmt ),
							'lockout_expire'		=> date( 'Y-m-d H:i:s', $this->current_time + $exp_seconds ),
							'lockout_expire_gmt'	=> date( 'Y-m-d H:i:s', $this->current_time_gmt + $exp_seconds ),
							'lockout_host'			=> sanitize_text_field( $host ),
							'lockout_user'			=> '',
						)
					);

				}

				if ( $good_user !== false ) { //blacklist host and temp lockout user
					
					$wpdb->insert(
						$wpdb->base_prefix . 'itsec_lockouts',
						array(
							'lockout_type'			=> $type,
							'lockout_start'			=> date( 'Y-m-d H:i:s', $this->current_time ),
							'lockout_start_gmt'		=> date( 'Y-m-d H:i:s', $this->current_time_gmt ),
							'lockout_expire'		=> date( 'Y-m-d H:i:s', $this->current_time + $exp_seconds ),
							'lockout_expire_gmt'	=> date( 'Y-m-d H:i:s', $this->current_time_gmt + $exp_seconds ),
							'lockout_host'			=> '',
							'lockout_user'			=> intval( $user ),
						)
					);

				} 

			}

		}

		public function check_lockout( $host = null, $user = null ) {

		}

		public function purge_lockouts() {
			
		}

		private function blacklist_host() {

		}

		private function sent_lockout_email() {

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