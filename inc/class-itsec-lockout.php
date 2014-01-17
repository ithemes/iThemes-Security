<?php

if ( ! class_exists( 'ITSEC_Lockout' ) ) {

	final class ITSEC_Lockout {

		private static $instance = null; //instantiated instance of this plugin

		private
			$settings,
			$lockout_modules;

		function __construct() {

			$this->settings = get_site_option( 'itsec_global' );

			$this->lockout_modules = array(); //array to hold information on modules using this feature

			//Run database cleanup daily with cron
			if ( ! wp_next_scheduled( 'itsec_purge_lockouts' ) ) {
				wp_schedule_event( time(), 'daily', 'itsec_purge_lockouts' );
			}

			add_action( 'itsec_purge_lockouts', array( $this, 'purge_lockouts' ) );

			//Check for host lockouts
			add_action( 'init', array( $this, 'check_lockout' ) );

			add_action( 'plugins_loaded', array( $this, 'register_modules' ) );

		}

		/**
		 * Checks if the host or user is locked out and executes lockout
		 *
		 * @param  int $user the user id to check
		 *
		 * @return void
		 */
		public function check_lockout( $user = null ) {

			global $wpdb, $itsec_lib, $itsec_current_time_gmt;

			$host = $itsec_lib->get_ip();

			$host_check = $wpdb->get_var( "SELECT `lockout_host` FROM `" . $wpdb->base_prefix . "itsec_lockouts` WHERE `lockout_expire_gmt` > '" . date( 'Y-m-d H:i:s', $itsec_current_time_gmt ) . "' AND `lockout_host`='" . $host . "';" );

			if ( $user !== null && $itsec_lib->user_id_exists( intval( $user ) ) === true ) {

				$user_check = $wpdb->get_var( "SELECT `lockout_user` FROM `" . $wpdb->base_prefix . "itsec_lockouts` WHERE `lockout_expire_gmt` > '" . date( 'Y-m-d H:i:s', $itsec_current_time_gmt ) . "' AND `lockout_user`=" . intval( $user ) . ";" );

			} else {

				$user_check = false;

			}

			if ( $host_check !== null ) {

				$this->execute_lock();

			}

		}

		/**
		 * Executes lockout and logging for modules
		 *
		 * @param string     $module string name of the calling module
		 * @param string $user username of user
		 *
		 * @return void
		 */
		public function do_lockout( $module, $user = null ) {

			global $wpdb, $itsec_lib, $itsec_current_time_gmt, $itsec_current_time;

			$lock_host = null;
			$lock_user = null;
			$options   = $this->lockout_modules[$module];

			if ( isset( $options['host'] ) ) {

				$host = $itsec_lib->get_ip();

				$wpdb->insert(
					$wpdb->base_prefix . 'itsec_temp',
					array(
						'temp_type'     => $options['type'],
						'temp_date'     => date( 'Y-m-d H:i:s', $itsec_current_time ),
						'temp_date_gmt' => date( 'Y-m-d H:i:s', $itsec_current_time_gmt ),
						'temp_host'     => sanitize_text_field( $host ),
					)
				);

				$host_count = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM `" . $wpdb->base_prefix . "itsec_temp` WHERE `temp_date_gmt` > '%s' AND `temp_host`='%s';",
						date( 'Y-m-d H:i:s', $itsec_current_time_gmt - ( $options['period'] * 60 ) ),
						$host
					)
				);

				if ( $host_count >= $options['host'] ) {

					$lock_host = $host;

				}

			}

			if ( $user !== null && isset( $options['user'] ) ) {

				$user_id = username_exists( sanitize_text_field( $user ) );

					if ( $user_id !== null ) {

					$wpdb->insert(
						$wpdb->base_prefix . 'itsec_temp',
						array(
							'temp_type'     => $options['type'],
							'temp_date'     => date( 'Y-m-d H:i:s', $itsec_current_time ),
							'temp_date_gmt' => date( 'Y-m-d H:i:s', $itsec_current_time_gmt ),
							'temp_user'     => intval( $user_id ),
						)
					);

					$user_count = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT COUNT(*) FROM `" . $wpdb->base_prefix . "itsec_temp` WHERE `temp_date_gmt` > '%s' AND `temp_user`=%s;",
							date( 'Y-m-d H:i:s', $itsec_current_time_gmt - ( $options['period'] * 60 ) ),
							$user_id
						)
					);

					if ( $user_count >= $options['user'] ) {

						$lock_user = $user;

					}

				}

			}

			if( $lock_host !== null || $lock_user !== null ) {
				$this->lockout( $options['type'], $options['reason'], $lock_host, $lock_user );
			}

		}

		/**
		 * Executes lockout (locks user out)
		 *
		 * @return void
		 */
		private function execute_lock() {

			wp_clear_auth_cookie();
			@header( 'HTTP/1.0 418 I\'m a teapot' );
			@header( 'Cache-Control: no-cache, must-revalidate' );
			@header( 'Expires: Thu, 22 Jun 1978 00:28:00 GMT' );
			die( $this->settings['lockout_message'] );

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
		private function lockout( $type, $reason, $host = null, $user = null ) {

			global $wpdb, $itsec_lib, $itsec_logger, $itsec_current_time_gmt, $itsec_current_time;

			$host_expiration = null;
			$user_expiration = null;

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
			$type   = sanitize_text_field( $type );
			$reason = sanitize_text_field( $reason );

			if ( $this->settings['blacklist'] === true && $good_host !== false ) { //permanent blacklist

				$blacklist_period = isset( $this->settings['blacklist_period'] ) ? $this->settings['blacklist_period'] * 24 * 60 * 60 : 604800;

				$host_count = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM `" . $wpdb->base_prefix . "itsec_lockouts` WHERE `lockout_expire_gmt` > '%s' AND `lockout_host`='%s';",
						date( 'Y-m-d H:i:s', $itsec_current_time_gmt + $blacklist_period ),
						$host
					)
				);

				if ( $host_count >= $this->settings['blacklist_count'] ) {

					$host_expiration = false;

					ITSEC_Ban_Users_Admin::insert_ip( sanitize_text_field( $host ) ); //Send it to the Ban Users module for banning

					$blacklist_host = true; //flag it so we don't do a temp ban as well

				}

			}

			//We have temp bans to perform
			if ( $good_host !== false || $good_user !== false ) {

				$exp_seconds    = ( intval( $this->settings['lockout_period'] ) * 60 );
				$expiration     = date( 'Y-m-d H:i:s', $itsec_current_time + $exp_seconds );
				$expiration_gmt = date( 'Y-m-d H:i:s', $itsec_current_time_gmt + $exp_seconds );

				if ( $good_host !== false && $blacklist_host === false ) { //temp lockout host

					$host_expiration = $expiration;

					$wpdb->insert(
						$wpdb->base_prefix . 'itsec_lockouts',
						array(
							'lockout_type'      => $type,
							'lockout_start'     => date( 'Y-m-d H:i:s', $itsec_current_time ),
							'lockout_start_gmt' => date( 'Y-m-d H:i:s', $itsec_current_time_gmt ),
							'lockout_expire'    => $expiration, 'lockout_expire_gmt' => $expiration_gmt,
							'lockout_host'      => sanitize_text_field( $host ),
							'lockout_user'      => '',
						)
					);

					$itsec_logger->log_event( $type, 10, array('expires' => $expiration, 'expires_gmt' => $expiration_gmt ), sanitize_text_field( $host ) );

				}

				if ( $good_user !== false ) { //blacklist host and temp lockout user

					$user_expiration = $expiration;

					$wpdb->insert(
						$wpdb->base_prefix . 'itsec_lockouts',
						array(
							'lockout_type'       => $type,
							'lockout_start'      => date( 'Y-m-d H:i:s', $itsec_current_time ),
							'lockout_start_gmt'  => date( 'Y-m-d H:i:s', $itsec_current_time_gmt ),
							'lockout_expire'     => $expiration,
							'lockout_expire_gmt' => $expiration_gmt,
							'lockout_host'       => '',
							'lockout_user'       => intval( $user ),
						)
					);

					$itsec_logger->log_event( $type, 10, array( 'expires' => $expiration, 'expires_gmt' => $expiration_gmt ), '', '', intval( $user ) );

				}

				if ( $this->settings['email_notifications'] === true ) { //send email notifications
					$this->send_lockout_email( $good_host, $good_user, $host_expiration, $user_expiration, $reason );
				}

				$this->execute_lock();

			}

		}

		/**
		 * Purges lockouts more than 7 days old from the database
		 *
		 * @return void
		 */
		public function purge_lockouts() {

			global $wpdb, $itsec_current_time_gmt;

			$wpdb->query( "DELETE FROM `" . $wpdb->base_prefix . "itsec_lockouts` WHERE `lockout_expire_gmt` < '" . date( 'Y-m-d H:i:s', $itsec_current_time_gmt - ( ( $this->settings['blacklist_period'] + 1 ) * 24 * 60 * 60 ) ) . "';" );
			$wpdb->query( "DELETE FROM `" . $wpdb->base_prefix . "itsec_temp` WHERE `temp_date_gmt` < '" . date( 'Y-m-d H:i:s', $itsec_current_time_gmt - 86400 ) . "';" );

		}

		/**
		 * Register modules that will use the lockout service
		 *
		 * @return void
		 */
		public function register_modules() {

			$this->lockout_modules = apply_filters( 'itsec_lockout_modules', $this->lockout_modules );

		}

		/**
		 * Sends an email to notify site admins of lockouts
		 *
		 * @param  string $host            the host to lockout
		 * @param  int    $user            the user id to lockout
		 * @param  string $host_expiration when the host login expires
		 * @param  string $user_expiration when the user lockout expires
		 * @param  string $reason          the reason for the lockout to show to the user
		 *
		 * @return void
		 */
		private function send_lockout_email( $host, $user, $host_expiration, $user_expiration, $reason ) {

			$plural_text = __( 'has', 'ithemes-security' );

			//Tell which host was locked out
			if ( $host !== false ) {

				$host_text = sprintf( '%s, <a href="http://ip-adress.com/ip_tracer/%s"><strong>%s</strong></a>, ', __( 'host', 'ithemes-security' ), sanitize_text_field( $host ), sanitize_text_field( $host ) );

				$host_expiration_text = __( 'The host has been locked out ', 'ithemes-security' );

				if ( $host_expiration === false ) {

					$host_expiration_text .= '<strong>' . __( 'permanently', 'ithemes-security' ) . '</strong>';
					$release_text = sprintf( '%s <a href="%s">%s</a>.', __( 'To release the host lockout you can remove the host from the', 'ithemes-security' ), get_Admin_url( '', 'admin.php?page=toplevel_page_itsec-ban_users' ), __( 'host list', 'ithemes-security' ) );

				} else {

					$host_expiration_text .= sprintf( '<strong>%s %s</strong>', __( 'until', 'ithemes-security' ), sanitize_text_field( $host_expiration ) );
					$release_text = sprintf( '%s <a href="%s">%s</a>.', __( 'To release the lockout please visit', 'ithemes-security' ), get_Admin_url( '', 'admin.php?page=toplevel_page_itsec-ban_users' ), __( 'the admin area', 'ithemes-security' ) );

				}

			} else {

				$host_expiration_text = '';
				$host_text            = '';
				$release_text         = '';

			}

			$user_object = get_userdata( $user ); //try to get and actual user object

			//Tell them which user was locked out and setup the expiration copy
			if ( $user_object !== false ) {

				if ( $host_text === '' ) {

					$user_expiration_text = sprintf( '%s <strong>%s %s</strong>.', __( 'The user has been locked out', 'ithemes-security' ), __( 'until', 'ithemes-security' ), sanitize_text_field( $user_expiration ) );

					$user_text = sprintf( '%s, <strong>%s</strong>, ', __( 'user', 'ithemes-security' ), $user_object->user_login );

					$release_text = sprintf( '%s <a href="%s">%s</a>.', __( 'To release the lockout please visit', 'ithemes-security' ), get_Admin_url( '', 'admin.php?page=toplevel_page_itsec-ban_users' ), __( 'the lockouts page', 'ithemes-security' ) );

				} else {

					$user_expiration_text = sprintf( '%s <strong>%s %s</strong>.', __( 'and the user has been locked out', 'ithemes-security' ), __( 'until', 'ithemes-security' ), sanitize_text_field( $user_expiration ) );
					$plural_text          = __( 'have', 'ithemes-security' );
					$user_text            = sprintf( '%s, <strong>%s</strong>, ', __( 'and a user', 'ithemes-security' ), $user_object->user_login );

					if ( $host_expiration === false ) {

						$release_text .= sprintf( '%s <a href="%s">%s</a>.', __( 'To release the user lockout please visit', 'ithemes-security' ), get_Admin_url( '', 'admin.php?page=toplevel_page_itsec-ban_users' ), __( 'the lockouts page', 'ithemes-security' ) );

					} else {

						$release_text = sprintf( '%s <a href="%s">%s</a>.', __( 'To release the lockouts please visit', 'ithemes-security' ), get_Admin_url( '', 'admin.php?page=toplevel_page_itsec-ban_users' ), __( 'the lockouts page', 'ithemes-security' ) );

					}

				}

			} else {

				$user_expiration_text = '.';
				$user_text            = '';
				$release_text         = '';

			}

			//Put the copy all together
			$body = sprintf( '<p>%s,</p><p>%s %s %s %s %s <a href="%s">%s</a> %s <strong>%s</strong>.</p><p>%s %s</p><p>%s</p><p><em>*%s <a href="%s">%s</a>.</em></p>', __( 'Dear Site Admin', 'ithemes-security' ), __( 'A', 'ithemes-security' ), $host_text, $user_text, $plural_text, __( ' been locked out of the WordPress site at', 'ithemes-security' ), get_option( 'siteurl' ), get_option( 'siteurl' ), __( 'due to', 'ithemes-security' ), sanitize_text_field( $reason ), $host_expiration_text, $user_expiration_text, $release_text, __( 'This email was generated automatically by iThemes Security. To change your email preferences please visit', 'ithemes-security' ), get_Admin_url( '', 'admin.php?page=toplevel_page_itsec-global' ), __( 'the plugin settings', 'ithemes-security' ) );

			//Setup the remainder of the email
			$recipients = $this->settings['notification_email'];
			$subject    = '[' . get_option( 'siteurl' ) . '] ' . __( 'Site Lockout Notification', 'ithemes-security' );
			$headers    = 'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>' . "\r\n";

			//Use HTML Content type
			add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

			//Send emails to all recipients
			foreach ( $recipients as $recipient ) {

				if ( is_email( trim( $recipient ) ) ) {
					wp_mail( trim( $recipient ), $subject, $body, $headers );
				}

			}

			//Remove HTML Content type
			remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

		}

		/**
		 * Set HTML content type for email
		 *
		 * @return string html content type
		 */
		function set_html_content_type() {

			return 'text/html';

		}

		/**
		 * Start the global lockout instance
		 *
		 * @return ITSEC_Lockout         The instance of the ITSEC_Lockout class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}