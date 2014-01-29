<?php

if ( ! class_exists( 'ITSEC_Authentication_Admin' ) ) {

	class ITSEC_Authentication_Admin {

		private static $instance = null;

		private
			$settings,
			$core,
			$module,
			$away_file,
			$page;

		private function __construct( $core, $module ) {

			global $itsec_globals;

			$this->core      = $core;
			$this->module    = $module;
			$this->settings  = get_site_option( 'itsec_authentication' );
			$this->away_file = $itsec_globals['upload_dir'] . '/itsec_away.confg'; //override file

			add_action( 'itsec_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) ); //enqueue scripts for admin page
			add_filter( 'itsec_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( 'itsec_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu
			add_filter( 'itsec_add_dashboard_status', array( $this, 'dashboard_status' ) ); //add information for plugin status
			add_filter( 'itsec_add_sidebar_status', array( $this, 'sidebar_status' ) ); //add information for plugin sidebar status

			//manually save options on multisite
			if ( is_multisite() ) {
				add_action( 'network_admin_edit_itsec_authentication', array( $this, 'save_network_options' ) ); //save multisite options
			}

		}

		/**
		 * Add meta boxes to primary options pages
		 *
		 * @return void
		 */
		public function add_admin_meta_boxes() {

			global $itsec_logger;

			add_meta_box(
				'authentication_description',
				__( 'Description', 'ithemes-security' ),
				array( $this, 'add_module_intro' ),
				'security_page_toplevel_page_itsec-authentication',
				'normal',
				'core'
			);

			add_meta_box(
				'authentication_options',
				__( 'Configure Authentication Security', 'ithemes-security' ),
				array( $this, 'metabox_advanced_settings' ),
				'security_page_toplevel_page_itsec-authentication',
				'advanced',
				'core'
			);

			//Don't attempt to display logs if brute force isn't enabled
			if ( isset( $this->settings['brute_force-enabled'] ) && $this->settings['brute_force-enabled'] === true ) {

				$itsec_logger->add_meta_box(
					'authentication',
					'brute_force',
					__( 'Invalid Login Attempts', 'ithemes-security' ),
					array( $this, 'logs_metabox' )
				);

			}

		}

		/**
		 * Adds tab to plugin administration area
		 *
		 * @param array $tabs array of tabs
		 *
		 * @return mixed array of tabs
		 */
		public function add_admin_tab( $tabs ) {

			$tabs[$this->page] = __( 'Auth', 'ithemes-security' );

			return $tabs;

		}

		/**
		 * Build and echo the away mode description
		 *
		 * @return void
		 */
		public function add_module_intro( $screen ) {

			$content = '<p>' . __( 'The below settings control who and how users can log in to the WordPress Dashboard. Turning on settings below can greatly increase the security of your WordPress website by preventing many of the common attacks that go after weaknesses in the standard login system.', 'ithemes-security' ) . '</p>';
			$content .= '<p>' . __( 'Please keep in mind the following settings are designed to work primarily with the standard login system. If you have any plugins or a theme that has changed anything already please test your site after turning on the below settings to verify there are no conflicts.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * Register subpage for Away Mode
		 *
		 * @param array $available_pages array of ITSEC settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $itsec_globals;

			$this->page = $available_pages[0] . '-authentication';

			$available_pages[] = add_submenu_page( 'itsec', __( 'Authentication', 'ithemes-security' ), __( 'Authentication', 'ithemes-security' ), $itsec_globals['plugin_access_lvl'], $available_pages[0] . '-authentication', array( $this->core, 'render_page' ) );

			return $available_pages;

		}

		/**
		 * Add Away mode Javascript
		 *
		 * @return void
		 */
		public function admin_script() {

			global $itsec_globals;

			if ( strpos( get_current_screen()->id, 'security_page_toplevel_page_itsec-authentication' ) !== false ) {

				wp_enqueue_script( 'itsec_authentication_js', $itsec_globals['plugin_url'] . 'modules/authentication/js/admin-authentication.js', 'jquery', $itsec_globals['plugin_build'] );
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_style( 'jquery-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );

			}

		}

		/**
		 * Echo the Admin User Header
		 */
		public function admin_user_header() {

			$content = '<h2 id="admin_user" class="settings-section-header">' . __( 'Secure Admin User', 'ithemes-security' ) . '</h2>';
			$content .= '<p>' . __( 'This feature will improve the security of your WordPress installation by removing common user attributes that can be used to target your site.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Admin User UserID Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function admin_user_userid( $args ) {

			$content = '<input type="checkbox" id="itsec_authentication_admin_user_userid" name="itsec_authentication[admin_user-userid]" value="1" />';
			$content .= '<label for="itsec_authentication_admin_user_userid"> ' . __( 'Change the ID of the user with ID 1.', 'ithemes-security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Admin User Username Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function admin_user_username( $args ) {

			$content = '<input name="itsec_authentication[admin_user-username]" id="itsec_authentication_admin_user_username" value="" type="text"><br>';
			$content .= '<label for="itsec_authentication_admin_user_username"> ' . __( 'New Admin Username', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'Enter a new username to replace "admin." Please note that if you are logged in as admin you will have to log in again.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Enable Away Mode Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function away_mode_enabled( $args ) {

			//disable the option if away mode is in the past
			if ( isset( $this->settings['away_mode-enabled'] ) && $this->settings['away_mode-enabled'] === true && ( $this->settings['away_mode-type'] == 1 || ( $this->settings['away_mode-end'] > current_time( 'timestamp' ) || $this->settings['away_mode-type'] === 2 ) ) ) {
				$enabled = 1;
			} else {
				$enabled = 0;
			}

			$content = '<input type="checkbox" id="itsec_authentication_away_mode_enabled" name="itsec_authentication[away_mode-enabled]" value="1" ' . checked( 1, $enabled, false ) . '/>';
			$content .= '<label for="itsec_authentication_away_mode_enabled"> ' . __( 'Enable away mode', 'ithemes-security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos End date field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function away_mode_end_date( $args ) {

			$current = current_time( 'timestamp' ); //The current time

			//if saved date is in the past update it to something in the future
			if ( isset( $this->settings['end'] ) && isset( $this->settings['away_mode-enabled'] ) && $current < $this->settings['away_mode-end'] ) {
				$end = $this->settings['away_mode-end'];
			} else {
				$end = strtotime( date( 'n/j/y 12:00 \a\m', ( current_time( 'timestamp' ) + ( 86400 * 2 ) ) ) );
			}

			//Date Field
			$content = '<input class="end_date_field" type="text" id="itsec_authentication_away_mode_end_date" name="itsec_authentication[end][date]" value="' . date( 'm/d/y', $end ) . '"/><br>';
			$content .= '<label class="end_date_field" for="itsec_authentication_away_mode_end_date"> ' . __( 'Set the date at which the admin dashboard should become available', 'ithemes-security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos End time field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function away_mode_end_time( $args ) {

			$current = current_time( 'timestamp' ); //The current time

			//if saved date is in the past update it to something in the future
			if ( isset( $this->settings['away_mode-end'] ) && isset( $this->settings['away_mode-enabled'] ) && $current < $this->settings['away_mode-end'] ) {
				$end = $this->settings['away_mode-end'];
			} else {
				$end = strtotime( date( 'n/j/y 6:00 \a\m', ( current_time( 'timestamp' ) + ( 86400 * 2 ) ) ) );
			}

			//Hour Field
			$content = '<select name="itsec_authentication[end][hour]" id="itsec_authentication_away_mod_end_time">';

			for ( $i = 1; $i <= 12; $i ++ ) {
				$content .= '<option value="' . sprintf( '%02d', $i ) . '" ' . selected( date( 'g', $end ), $i, false ) . '>' . $i . '</option>';
			}

			$content .= '</select>';

			//Minute Field
			$content .= '<select name="itsec_authentication[end][minute]" id="itsec_authentication_away_mod_end_time">';

			for ( $i = 0; $i <= 59; $i ++ ) {

				$content .= '<option value="' . sprintf( '%02d', $i ) . '" ' . selected( date( 'i', $end ), sprintf( '%02d', $i ), false ) . '>' . sprintf( '%02d', $i ) . '</option>';
			}

			$content .= '</select>';

			//AM/PM Field
			$content .= '<select name="itsec_authentication[end][sel]" id="itsec_authentication">';
			$content .= '<option value="am" ' . selected( date( 'a', $end ), 'am', false ) . '>' . __( 'am', 'ithemes-security' ) . '</option>';
			$content .= '<option value="pm" ' . selected( date( 'a', $end ), 'pm', false ) . '>' . __( 'pm', 'ithemes-security' ) . '</option>';
			$content .= '</select><br>';
			$content .= '<label for="itsec_authentication_away_mod_end_time"> ' . __( 'Set the time at which the admin dashboard should become available again.', 'ithemes-security' ) . '</label>';

			echo $content;

		}

		/**
		 * Echo The Away Mode Header
		 */
		public function away_mode_header() {

			$content = '<h2 id="away_mode" class="settings-section-header">' . __( 'Configure Away Mode', 'ithemes-security' ) . '</h2>';

			$content .= '<p>' . __( 'As most sites are only updated at certain times of the day it is not always necessary to provide access to the WordPress dashboard 24 hours a day, 7 days a week. The options below will allow you to disable access to the WordPress Dashboard for the specified period. In addition to limiting exposure to attackers this could also be useful to disable site access based on a schedule for classroom or other reasons.', 'ithemes-security' ) . '</p>';

			if ( preg_match( "/^(G|H)(:| \\h)/", get_option( 'time_format' ) ) ) {
				$currdate = date_i18n( 'l, d F Y' . ' ' . get_option( 'time_format' ), current_time( 'timestamp' ) );
			} else {
				$currdate = date( 'g:i a \o\n l F jS, Y', current_time( 'timestamp' ) );
			}

			$content .= '<p>' . sprintf( __( 'Please note that according to your %sWordPress timezone settings%s your current time is:', 'ithemes-security' ), '<a href="options-general.php">', '</a>' );
			$content .= '<div class="current-time-date">' . $currdate . '</div>';
			$content .= '<p>' . sprintf( __( 'If this is incorrect please correct it on the %sWordPress general settings page%s by setting the appropriate time zone. Failure to set the correct timezone may result in unintended lockouts.', 'ithemes-security' ), '<a href="options-general.php">', '</a>' ) . '</p>';

			echo $content;

			//set information explaining away mode is enabled
			if ( isset( $this->settings['enabled'] ) && $this->settings['enabled'] === 1 && ( $this->settings['type'] === 1 || ( $this->settings['end'] > current_time( 'timestamp' ) ) ) ) {

				$content = '<hr />';

				$content .= sprintf( '<p><strong>%s</strong></p>', __( 'Away mode is currently enabled.', 'ithemes-security' ) );

				//Create the appropriate notification based on daily or one time use
				if ( $this->settings['type'] === 1 ) {

					$content .= sprintf( '<p>' . __( 'The dashboard of this website will become unavailable %s%s%s from %s%s%s until %s%s%s.', 'ithemes-security' ) . '</p>', '<strong>', __( 'every day', 'ithemes-security' ), '</strong>', '<strong>', date_i18n( get_option( 'time_format' ), $this->settings['start'] ), '</strong>', '<strong>', date_i18n( get_option( 'time_format' ), $this->settings['end'] ), '</strong>' );

				} else {

					$content .= sprintf( '<p>' . __( 'The dashboard of this website will become unavailable from %s%s%s on %s%s%s until %s%s%s on %s%s%s.', 'ithemes-security' ) . '</p>', '<strong>', date_i18n( get_option( 'time_format' ), $this->settings['start'] ), '</strong>', '<strong>', date_i18n( get_option( 'date_format' ), $this->settings['start'] ), '</strong>', '<strong>', date_i18n( get_option( 'time_format' ), $this->settings['end'] ), '</strong>', '<strong>', date_i18n( get_option( 'date_format' ), $this->settings['end'] ), '</strong>' );

				}

				$content .= '<p>' . __( 'You will not be able to log into this website when the site is unavailable.', 'ithemes-security' ) . '</p>';

				echo $content;
			}

		}

		/**
		 * echos Start date field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function away_mode_start_date( $args ) {

			$current = current_time( 'timestamp' ); //The current time

			//if saved date is in the past update it to something in the future
			if ( isset( $this->settings['away_mode-start'] ) && isset( $this->settings['away_mode-enabled'] ) && $current < $this->settings['away_mode-end'] ) {
				$start = $this->settings['away_mode-start'];
			} else {
				$start = strtotime( date( 'n/j/y 12:00 \a\m', ( current_time( 'timestamp' ) + ( 86400 ) ) ) );
			}

			//Date Field
			$content = '<input class="start_date_field" type="text" id="itsec_authentication_away_mode_start_date" name="itsec_authentication[start][date]" value="' . date( 'm/d/y', $start ) . '"/><br>';
			$content .= '<label class="start_date_field" for="itsec_authentication_away_mode_start_date"> ' . __( 'Set the date at which the admin dashboard should become unavailable', 'ithemes-security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Start time field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function away_mode_start_time( $args ) {

			$current = current_time( 'timestamp' ); //The current time

			//if saved date is in the past update it to something in the future
			if ( isset( $this->settings['away_mode-start'] ) && isset( $this->settings['away_mode-enabled'] ) && $current < $this->settings['away_mode-end'] ) {
				$start = $this->settings['away_mode-start'];
			} else {
				$start = strtotime( date( 'n/j/y 12:00 \a\m', ( current_time( 'timestamp' ) + ( 86400 ) ) ) );
			}

			//Hour Field
			$content = '<select name="itsec_authentication[start][hour]" id="itsec_authentication_away_mod_start_time">';

			for ( $i = 1; $i <= 12; $i ++ ) {
				$content .= '<option value="' . sprintf( '%02d', $i ) . '" ' . selected( date( 'g', $start ), $i, false ) . '>' . $i . '</option>';
			}

			$content .= '</select>';

			//Minute Field
			$content .= '<select name="itsec_authentication[start][minute]" id="itsec_authentication_away_mod_start_time">';

			for ( $i = 0; $i <= 59; $i ++ ) {

				$content .= '<option value="' . sprintf( '%02d', $i ) . '" ' . selected( date( 'i', $start ), sprintf( '%02d', $i ), false ) . '>' . sprintf( '%02d', $i ) . '</option>';
			}

			$content .= '</select>';

			//AM/PM Field
			$content .= '<select name="itsec_authentication[start][sel]" id="itsec_authentication_away_mod_start_time">';
			$content .= '<option value="am" ' . selected( date( 'a', $start ), 'am', false ) . '>' . __( 'am', 'ithemes-security' ) . '</option>';
			$content .= '<option value="pm" ' . selected( date( 'a', $start ), 'pm', false ) . '>' . __( 'pm', 'ithemes-security' ) . '</option>';
			$content .= '</select><br>';
			$content .= '<label for="itsec_authentication_away_mod_start_time"> ' . __( 'Set the time at which the admin dashboard should become available again.', 'ithemes-security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos type Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function away_mode_type( $args ) {

			$content = '<select name="itsec_authentication[away_mode-type]" id="itsec_authentication_away_mode_type">';
			$content .= '<option value="1" ' . selected( $this->settings['away_mode-type'], 1, false ) . '>' . __( 'Daily', 'ithemes-security' ) . '</option>';
			$content .= '<option value="2" ' . selected( $this->settings['away_mode-type'], 2, false ) . '>' . __( 'One Time', 'ithemes-security' ) . '</option>';
			$content .= '</select><br>';
			$content .= '<label for="itsec_authentication_away_mode_type"> ' . __( 'Select the type of restriction you would like to enable', 'ithemes-security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Check Period Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function brute_force_check_period( $args ) {

			if ( isset( $this->settings['brute_force-check_period'] ) ) {
				$check_period = absint( $this->settings['brute_force-check_period'] );
			} else {
				$check_period = 5;
			}

			$content = '<input class="small-text" name="itsec_authentication[brute_force-check_period]" id="itsec_authentication_brute_force_check_period" value="' . $check_period . '" type="text"> ';
			$content .= '<label for="itsec_authentication_brute_force_check_period"> ' . __( 'Minutes', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'The number of minutes in which bad logins should be remembered.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Enable Brute Force Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function brute_force_enabled( $args ) {

			if ( isset( $this->settings['brute_force-enabled'] ) && $this->settings['brute_force-enabled'] === true ) {
				$enabled = 1;
			} else {
				$enabled = 0;
			}

			$content = '<input type="checkbox" id="itsec_authentication_brute_force_enabled" name="itsec_authentication[brute_force-enabled]" value="1" ' . checked( 1, $enabled, false ) . '/>';
			$content .= '<label for="itsec_authentication_brute_force_enabled"> ' . __( 'Enable brute force protection.', 'ithemes-security' ) . '</label>';

			echo $content;

		}

		/**
		 * Echo the Brute Force Header
		 */
		public function brute_force_header() {

			$content = '<h2 id="brute_force" class="settings-section-header">' . __( 'Brute Force Protection', 'ithemes-security' ) . '</h2>';
			$content .= '<p>' . __( 'If one had unlimited time and wanted to try an unlimited number of password combinations to get into your site they eventually would, right? This method of attach, known as a brute force attack, is something that WordPress is acutely susceptible by default as the system doesn\t care how many attempts a user makes to login. It will always let you try again. Enabling login limits will ban the host user from attempting to login again after the specified bad login threshold has been reached.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Max Attempts per host  Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function brute_force_max_attempts_host( $args ) {

			if ( isset( $this->settings['brute_force-max_attempts_host'] ) ) {
				$max_attempts_host = absint( $this->settings['brute_force-max_attempts_host'] );
			} else {
				$max_attempts_host = 5;
			}

			$content = '<input class="small-text" name="itsec_authentication[brute_force-max_attempts_host]" id="itsec_authentication_brute_force_max_attempts_host" value="' . $max_attempts_host . '" type="text"> ';
			$content .= '<label for="itsec_authentication_brute_force_max_attempts_host"> ' . __( 'Attempts', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'The number of login attempts a user has before their host or computer is locked out of the system. Set to 0 to record bad login attempts without locking out the host.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Max Attempts per user  Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function brute_force_max_attempts_user( $args ) {

			if ( isset( $this->settings['brute_force-max_attempts_user'] ) ) {
				$max_attempts_user = absint( $this->settings['brute_force-max_attempts_user'] );
			} else {
				$max_attempts_user = 10;
			}

			$content = '<input class="small-text" name="itsec_authentication[brute_force-max_attempts_user]" id="itsec_authentication_brute_force_max_attempts_user" value="' . $max_attempts_user . '" type="text"> ';
			$content .= '<label for="itsec_authentication_brute_force_max_attempts_user"> ' . __( 'Attempts', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'The number of login attempts a user has before their username is locked out of the system. Note that this is different from hosts in case an attacker is using multiple computers. In addition, if they are using your login name you could be locked out yourself. Set to zero to log bad login attempts per user without ever locking the user out (this is not recommended)', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * Changes Admin User
		 *
		 * Changes the username and id of the 1st user
		 *
		 * @param string $username the username to change if changing at the same time
		 * @param bool   $id       whether to change the id as well
		 *
		 * @return bool success or failure
		 *
		 **/
		private function change_admin_user( $username = null, $id = false ) {

			global $wpdb;

			//sanitize the username
			$new_user = sanitize_text_field( $username );

			//Get the full user object
			$user_object = get_user_by( 'id', '1' );

			if ( $username !== null && validate_username( $new_user ) && username_exists( $new_user ) === null ) { //there is a valid username to change

				if ( $id === true ) { //we're changing the id too so we'll set the username

					$user_login = $new_user;

				} else { // we're only changing the username

					//query main user table
					$wpdb->query( "UPDATE `" . $wpdb->users . "` SET user_login = '" . esc_sql( $new_user ) . "' WHERE user_login='admin';" );

					if ( is_multisite() ) { //process sitemeta if we're in a multi-site situation

						$oldAdmins = $wpdb->get_var( "SELECT meta_value FROM `" . $wpdb->sitemeta . "` WHERE meta_key = 'site_admins'" );
						$newAdmins = str_replace( '5:"admin"', strlen( $new_user ) . ':"' . esc_sql( $new_user ) . '"', $oldAdmins );
						$wpdb->query( "UPDATE `" . $wpdb->sitemeta . "` SET meta_value = '" . esc_sql( $newAdmins ) . "' WHERE meta_key = 'site_admins'" );

					}

					wp_clear_auth_cookie();

					return true;

				}

			} elseif ( $username !== null ) { //username didn't validate

				return false;

			} else { //only changing the id

				$user_login = $user_object->user_login;

			}

			if ( $id === true ) { //change the user id

				$wpdb->query( "DELETE FROM `" . $wpdb->users . "` WHERE ID = 1;" );

				$wpdb->insert( $wpdb->users, array( 'user_login' => $user_login, 'user_pass' => $user_object->user_pass, 'user_nicename' => $user_object->user_nicename, 'user_email' => $user_object->user_email, 'user_url' => $user_object->user_url, 'user_registered' => $user_object->user_registered, 'user_activation_key' => $user_object->user_activation_key, 'user_status' => $user_object->user_status, 'display_name' => $user_object->display_name ) );

				$new_user = $wpdb->insert_id;

				$wpdb->query( "UPDATE `" . $wpdb->posts . "` SET post_author = '" . $new_user . "' WHERE post_author = 1;" );
				$wpdb->query( "UPDATE `" . $wpdb->usermeta . "` SET user_id = '" . $new_user . "' WHERE user_id = 1;" );
				$wpdb->query( "UPDATE `" . $wpdb->comments . "` SET user_id = '" . $new_user . "' WHERE user_id = 1;" );
				$wpdb->query( "UPDATE `" . $wpdb->links . "` SET link_owner = '" . $new_user . "' WHERE link_owner = 1;" );

				wp_clear_auth_cookie();

				return true;

			}

		}

		/**
		 * Sets the status in the plugin dashboard
		 *
		 * @return void
		 */
		public function dashboard_status( $statuses ) {

			$link = 'admin.php?page=toplevel_page_itsec-authentication';

			if ( $this->settings['strong_passwords-enabled'] === 1 && $this->settings['strong_passwords-roll'] == 'subscriber' ) {

				$status_array = 'safe-high';
				$status       = array( 'text' => __( 'You are enforcing strong passwords for all users.', 'ithemes-security' ), 'link' => $link . '#strong_passwords', );

			} elseif ( $this->settings['strong_passwords-enabled'] === true ) {

				$status_array = 'low';
				$status       = array( 'text' => __( 'You are enforcing strong passwords, but not for all users.', 'ithemes-security' ), 'link' => $link . '#strong_passwords', );

			} else {

				$status_array = 'high';
				$status       = array( 'text' => __( 'You are not enforcing strong passwords for any users.', 'ithemes-security' ), 'link' => $link . '#strong_passwords', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['hide_backend-enabled'] === true ) {

				$status_array = 'safe-medium';
				$status       = array( 'text' => __( 'Your WordPress Dashboard is hidden.', 'ithemes-security' ), 'link' => $link . '#hide_backend', );

			} else {

				$status_array = 'medium';
				$status       = array( 'text' => __( 'Your WordPress Dashboard is using the default addresses. This can make a brute force attack much easier.', 'ithemes-security' ), 'link' => $link . '#hide_backend', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['away_mode-enabled'] === true ) {

				$status_array = 'safe-medium';
				$status       = array( 'text' => __( 'Away Mode is enabled and your WordPress Dashboard is not available when you will not be needing it.', 'ithemes-security' ), 'link' => $link . '#away_mode', );

			} else {

				$status_array = 'medium';
				$status       = array( 'text' => __( 'Your WordPress Dashboard is available 24/7. Do you really update 24 hours a day? Consider using Away Mode.', 'ithemes-security' ), 'link' => $link . '#away_mode', );

			}

			array_push( $statuses[$status_array], $status );

			if ( ! username_exists( 'admin' ) ) {

				$status_array = 'safe-high';
				$status       = array( 'text' => __( 'The <em>admin</em> user has been removed or renamed.', 'ithemes-security' ), 'link' => $link . '#admin_user', );

			} else {

				$status_array = 'high';
				$status       = array( 'text' => __( 'The <em>admin</em> user still exists.', 'ithemes-security' ), 'link' => $link . '#admin_user', );

			}

			array_push( $statuses[$status_array], $status );

			if ( ! username_exists( 'admin' ) ) {

				$status_array = 'safe-medium';
				$status       = array( 'text' => __( 'The user with id 1 has been removed.', 'ithemes-security' ), 'link' => $link . '#admin_user', );

			} else {

				$status_array = 'medium';
				$status       = array( 'text' => __( 'A user with id 1 still exists.', 'ithemes-security' ), 'link' => $link . '#admin_user', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['brute_force-enabled'] === true ) {

				$status_array = 'safe-high';
				$status       = array( 'text' => __( 'Your login area is protected from brute force attacks.', 'ithemes-security' ), 'link' => $link . '#brute_force', );

			} else {

				$status_array = 'high';
				$status       = array( 'text' => __( 'Your login area is not protected from brute force attacks.', 'ithemes-security' ), 'link' => $link . '#brute_force', );

			}

			array_push( $statuses[$status_array], $status );

			return $statuses;

		}

		/**
		 * Empty callback function
		 */
		public function empty_callback_function() {
		}

		/**
		 * echos Hide Backend  Enabled Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function hide_backend_enabled( $args ) {

			if ( ( get_option( 'permalink_structure' ) == '' || get_option( 'permalink_structure' ) == false ) && ! is_multisite() ) {

				$adminurl = is_multisite() ? admin_url() . 'network/' : admin_url();

				$content = sprintf( '<p class="noPermalinks">%s <a href="%soptions-permalink.php">%s</a> %s</p>', __( 'You must turn on', 'ithemes-security' ), $adminurl, __( 'WordPress permalinks', 'ithemes-security' ), __( 'to use this feature.', 'ithemes-security' ) );

			} else {

				if ( isset( $this->settings['hide_backend-enabled'] ) && $this->settings['hide_backend-enabled'] === true ) {
					$enabled = 1;
				} else {
					$enabled = 0;
				}

				$content = '<input type="checkbox" id="itsec_authentication_hide_backend_enabled" name="itsec_authentication[hide_backend-enabled]" value="1" ' . checked( 1, $enabled, false ) . '/>';
				$content .= '<label for="itsec_authentication_hide_backend_enabled"> ' . __( 'Enable the hide backend feature.', 'ithemes-security' ) . '</label>';

			}

			echo $content;

		}

		/**
		 * Echo the Hide Backend Header
		 */
		public function hide_backend_header() {

			$content = '<h2 id="hide_backend" class="settings-section-header">' . __( 'Hide the Login Page', 'ithemes-security' ) . '</h2>';
			$content .= '<p>' . __( 'Hides the login and admin pages making them harder to find by automated attacks and making them easier for users unfamiliar with the WordPress platform.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Hide Backend Slug  Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function hide_backend_slug( $args ) {

			if ( ( get_option( 'permalink_structure' ) == '' || get_option( 'permalink_structure' ) == false ) && ! is_multisite() ) {

				$content = '';

			} else {

				$content = '<input name="itsec_authentication[hide_backend-slug]" id="itsec_authentication_strong_passwords_slug" value="' . sanitize_title( $this->settings['hide_backend-slug'] ) . '" type="text"><br />';
				$content .= '<label for="itsec_authentication_strong_passwords_slug">' . __( 'Login URL:', 'ithemes-security' ) . trailingslashit( get_option( 'siteurl' ) ) . '<span style="color: #4AA02C">' . sanitize_title( $this->settings['hide_backend-slug'] ) . '</span></label>';
				$content .= '<p class="description">' . __( 'The login url slug cannot be "login," "admin," "dashboard," or "wp-login.php" as these are use by default in WordPress.', 'ithemes-security' ) . '</p>';

			}

			echo $content;

		}

		/**
		 * echos Register Slug  Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function hide_backend_register( $args ) {

			if ( ( get_option( 'permalink_structure' ) == '' || get_option( 'permalink_structure' ) == false ) && ! is_multisite() ) {

				$content = '';

			} else {

				$content = '<input name="itsec_authentication[hide_backend-register]" id="itsec_authentication_strong_passwords_register" value="' . ( $this->settings['hide_backend-register'] !== 'wp-register.php' ? sanitize_title( $this->settings['hide_backend-register'] ) : 'wp-register.php' ) . '" type="text"><br />';
				$content .= '<label for="itsec_authentication_strong_passwords_register">' . __( 'Registration URL:', 'ithemes-security' ) . trailingslashit( get_option( 'siteurl' ) ) . '<span style="color: #4AA02C">' . sanitize_title( $this->settings['hide_backend-register'] ) . '</span></label>';

			}

			echo $content;

		}

		/**
		 * Execute admin initializations
		 *
		 * @return void
		 */
		public function initialize_admin() {

			global $itsec_lib;

			//Add Settings sections
			add_settings_section(
				'authentication_brute_force-enabled',
				__( 'Enable Brute Force Protection', 'ithemes-security' ),
				array( $this, 'brute_force_header' ),
				'security_page_toplevel_page_itsec-authentication'
			);

			add_settings_section(
				'authentication_brute_force-settings',
				__( 'Brute Force Protection Settings', 'ithemes-security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_itsec-authentication'
			);

			if ( $itsec_lib->user_id_exists( 1 ) || username_exists( 'admin' ) ) {

				add_settings_section(
					'authentication_admin_user',
					__( 'Secure Admin User',
					    'ithemes-security' ),
					array( $this, 'admin_user_header' ),
					'security_page_toplevel_page_itsec-authentication'
				);

			}

			add_settings_section(
				'authentication_strong_passwords-enabled',
				__( 'Enforce Strong Passwords', 'ithemes-security' ),
				array( $this, 'strong_passwords_header' ),
				'security_page_toplevel_page_itsec-authentication'
			);

			add_settings_section(
				'authentication_strong_passwords-settings',
				__( 'Enforce Strong Passwords', 'ithemes-security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_itsec-authentication'
			);

			add_settings_section(
				'authentication_hide_backend-enabled',
				__( 'Hide Login and Admin', 'ithemes-security' ),
				array( $this, 'hide_backend_header' ),
				'security_page_toplevel_page_itsec-authentication'
			);

			add_settings_section(
				'authentication_hide_backend-settings',
				__( 'Hide Login and Admin', 'ithemes-security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_itsec-authentication'
			);

			add_settings_section(
				'authentication_away_mode-enabled',
				__( 'Away Mode', 'ithemes-security' ),
				array( $this, 'away_mode_header' ),
				'security_page_toplevel_page_itsec-authentication'
			);

			add_settings_section(
				'authentication_away_mode-settings',
				__( 'Away Mode', 'ithemes-security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_itsec-authentication'
			);

			add_settings_section(
				'authentication_other',
				__( 'Other Authentication Tweaks', 'ithemes-security' ),
				array( $this, 'other_header' ),
				'security_page_toplevel_page_itsec-authentication'
			);

			//Brute Force Protection Fields
			add_settings_field(
				'itsec_authentication[brute_force-enabled]',
				__( 'Brute Force Protection', 'ithemes-security' ),
				array( $this, 'brute_force_enabled' ),
				'security_page_toplevel_page_itsec-authentication', 'authentication_brute_force-enabled'
			);

			add_settings_field(
				'itsec_authentication[brute_force-max_attempts_host]',
				__( 'Max Login Attempts Per Host', 'ithemes-security' ),
				array( $this, 'brute_force_max_attempts_host' ),
				'security_page_toplevel_page_itsec-authentication', 'authentication_brute_force-settings'
			);

			add_settings_field(
				'itsec_authentication[brute_force-max_attempts_user]',
				__( 'Max Login Attempts Per User', 'ithemes-security' ),
				array( $this, 'brute_force_max_attempts_user' ),
				'security_page_toplevel_page_itsec-authentication', 'authentication_brute_force-settings'
			);

			add_settings_field(
				'itsec_authentication[brute_force-check_period]',
				__( 'Minutes to Remember Bad Login (check period)', 'ithemes-security' ),
				array( $this, 'brute_force_check_period' ),
				'security_page_toplevel_page_itsec-authentication', 'authentication_brute_force-settings'
			);

			//Admin User Fields

			if ( username_exists( 'admin' ) ) {

				add_settings_field(
					'itsec_authentication[admin_user-username]',
					__( 'Admin Username', 'ithemes-security' ),
					array( $this, 'admin_user_username' ),
					'security_page_toplevel_page_itsec-authentication',
					'authentication_admin_user'
				);

			} else {

				add_settings_field(
					'itsec_authentication[admin_user-username]',
					__( 'Admin Username', 'ithemes-security' ),
					array( $this, 'admin_user_username' ),
					'security_page_toplevel_page_itsec-authentication',
					'authentication_admin_user'
				);

			}

			if ( $itsec_lib->user_id_exists( 1 ) ) {

				add_settings_field(

					'itsec_authentication[admin_user-userid]',
					__( 'User ID 1', 'ithemes-security' ),
					array( $this, 'admin_user_userid' ),
					'security_page_toplevel_page_itsec-authentication',
					'authentication_admin_user'
				);

			}

			//Strong Passwords Fields
			add_settings_field(
				'itsec_authentication[strong_passwords-enabled]',
				__( 'Strong Passwords', 'ithemes-security' ),
				array( $this, 'strong_passwords_enabled' ),
				'security_page_toplevel_page_itsec-authentication',
				'authentication_strong_passwords-enabled'
			);

			add_settings_field(
				'itsec_authentication[strong_passwords-roll]',
				__( 'Select Role for Strong Passwords', 'ithemes-security' ),
				array( $this, 'strong_passwords_role' ),
				'security_page_toplevel_page_itsec-authentication',
				'authentication_strong_passwords-settings'
			);

			//Hide Backend Fields
			add_settings_field(
				'itsec_authentication[hide_backend-enabled]',
				__( 'Hide Backend', 'ithemes-security' ),
				array( $this, 'hide_backend_enabled' ),
				'security_page_toplevel_page_itsec-authentication',
				'authentication_hide_backend-enabled'
			);

			add_settings_field(
				'itsec_authentication[hide_backend-slug]',
				__( 'Login Slug', 'ithemes-security' ),
				array( $this, 'hide_backend_slug' ),
				'security_page_toplevel_page_itsec-authentication',
				'authentication_hide_backend-settings'
			);

			add_settings_field(
				'itsec_authentication[hide_backend-register]',
				__( 'Register Slug', 'ithemes-security' ),
				array( $this, 'hide_backend_register' ),
				'security_page_toplevel_page_itsec-authentication',
				'authentication_hide_backend-settings'
			);

			//Away Mode Fields
			add_settings_field(
				'itsec_authentication[away_mode-enabled]',
				__( 'Away Mode', 'ithemes-security' ),
				array( $this, 'away_mode_enabled' ),
				'security_page_toplevel_page_itsec-authentication',
				'authentication_away_mode-enabled'
			);

			add_settings_field(
				'itsec_authentication[away_mode-type]',
				__( 'Type of Restriction', 'ithemes-security' ),
				array( $this, 'away_mode_type' ),
				'security_page_toplevel_page_itsec-authentication',
				'authentication_away_mode-settings'
			);

			add_settings_field(
				'itsec_authentication[away_mode-start_date]', __( 'Start Date', 'ithemes-security' ),
				array( $this, 'away_mode_start_date' ),
				'security_page_toplevel_page_itsec-authentication',
				'authentication_away_mode-settings'
			);

			add_settings_field(
				'itsec_authentication[away_mode-start_time]', __( 'Start Time', 'ithemes-security' ),
				array( $this, 'away_mode_start_time' ),
				'security_page_toplevel_page_itsec-authentication',
				'authentication_away_mode-settings'
			);

			add_settings_field(
				'itsec_authentication[away_mode-end_date]',
				__( 'End Date', 'ithemes-security' ),
				array( $this, 'away_mode_end_date' ),
				'security_page_toplevel_page_itsec-authentication',
				'authentication_away_mode-settings'
			);

			add_settings_field(
				'itsec_authentication[away_mode-end_time]',
				__( 'End Time', 'ithemes-security' ),
				array( $this, 'away_mode_end_time' ),
				'security_page_toplevel_page_itsec-authentication',
				'authentication_away_mode-settings'
			);

			//Other Settings
			add_settings_field(
				'itsec_authentication[other-login_errors]',
				__( 'Login Error Messages', 'ithemes-security' ),
				array( $this, 'other_login_errors' ),
				'security_page_toplevel_page_itsec-authentication',
				'authentication_other'
			);

			//Register the settings field for the entire module
			register_setting(
				'security_page_toplevel_page_itsec-authentication',
				'itsec_authentication',
				array( $this, 'sanitize_module_input' )
			);

		}

		/**
		 * Render the settings metabox
		 *
		 * @return void
		 */
		public function logs_metabox() {

			require( dirname( __FILE__ ) . '/class-itsec-authentication-log-table.php' );

			echo __( 'Below is the log of all the invalid login attempts in the WordPress Database. To adjust logging options visit the global settings page.', 'ithemes-security' );

			$log_display = new ITSEC_Authentication_Log_Table();
			$log_display->prepare_items();
			$log_display->display();

		}

		/**
		 * Render the settings metabox
		 *
		 * @return void
		 */
		public function metabox_advanced_settings() {

			//set appropriate action for multisite or standard site
			if ( is_multisite() ) {
				$action = 'edit.php?action=itsec_authentication';
			} else {
				$action = 'options.php';
			}

			printf( '<form name="%s" method="post" action="%s">', get_current_screen()->id, $action );

			$this->core->do_settings_sections( 'security_page_toplevel_page_itsec-authentication', false );

			echo '<p>' . PHP_EOL;

			settings_fields( 'security_page_toplevel_page_itsec-authentication' );

			echo '<input class="button-primary" name="submit" type="submit" value="' . __( 'Save Changes', 'ithemes-security' ) . '" />' . PHP_EOL;

			echo '</p>' . PHP_EOL;

			echo '</form>';

		}

		/**
		 * Echo the "Other" Header
		 */
		public function other_header() {

			$content = '<h2 id="other" class="settings-section-header">' . __( 'Other Authentication Tweaks', 'ithemes-security' ) . '</h2>';
			$content .= '<p>' . __( 'Miscellaneous tweaks that can make it harder for an attacker to log into your WordPress website.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Disable Login Errors Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function other_login_errors( $args ) {

			if ( isset( $this->settings['other-login_errors'] ) && $this->settings['other-login_errors'] === true ) {
				$enabled = 1;
			} else {
				$enabled = 0;
			}

			$content = '<input type="checkbox" id="itsec_authentication_other_login_errors" name="itsec_authentication[other-login_errors]" value="1" ' . checked( 1, $enabled, false ) . '/>';
			$content .= '<label for="itsec_authentication_other_login_errors"> ' . __( 'Disable login error messages', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'Prevents error messages from being displayed to a user upon a failed login attempt.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * Sanitize and validate input
		 *
		 * @param  Array $input array of input fields
		 *
		 * @return Array         Sanitized array
		 */
		public function sanitize_module_input( $input ) {

			//process brute force settings
			$input['brute_force-enabled']           = ( isset( $input['brute_force-enabled'] ) && intval( $input['brute_force-enabled'] == 1 ) ? true : false );
			$input['brute_force-max_attempts_host'] = isset( $input['brute_force-max_attempts_host'] ) ? absint( $input['brute_force-max_attempts_host'] ) : 5;
			$input['brute_force-max_attempts_user'] = isset( $input['brute_force-max_attempts_user'] ) ? absint( $input['brute_force-max_attempts_user'] ) : 10;
			$input['brute_force-check_period']      = isset( $input['brute_force-check_period'] ) ? absint( $input['brute_force-check_period'] ) : 5;

			//process strong passwords settings
			$input['strong_passwords-enabled'] = ( isset( $input['strong_passwords-enabled'] ) && intval( $input['strong_passwords-enabled'] == 1 ) ? true : false );
			if ( isset( $input['strong_passwords-roll'] ) && ctype_alpha( wp_strip_all_tags( $input['strong_passwords-roll'] ) ) ) {
				$input['strong_passwords-roll'] = wp_strip_all_tags( $input['strong_passwords-roll'] );
			}

			//Process admin user
			$username    = isset( $input['admin_user-username'] ) ? trim( sanitize_text_field( $input['admin_user-username'] ) ) : null;
			$change_id_1 = ( isset( $input['admin_user-userid'] ) && intval( $input['admin_user-userid'] == 1 ) ? true : false );

			unset( $input['admin_user-username'] );
			unset( $input['admin_user-userid'] );

			$admin_success = true;

			if ( strlen( $username ) >= 1 ) {

				$admin_success = $this->change_admin_user( $username, $change_id_1 );

			} elseif ( $change_id_1 === true ) {

				$admin_success = $this->change_admin_user( null, $change_id_1 );

			}

			//Process hide backend settings
			$input['hide_backend-enabled'] = ( isset( $input['hide_backend-enabled'] ) && intval( $input['hide_backend-enabled'] == 1 ) ? true : false );

			if ( isset( $input['hide_backend-slug'] ) ) {
				$input['hide_backend-slug'] = sanitize_title( $input['hide_backend-slug'] );
			} else {
				$input['hide_backend-slug'] = 'wpplogin';
			}

			if ( isset( $input['hide_backend-register'] ) && $input['hide_backend-register'] !== 'wp-register.php' ) {
				$input['hide_backend-register'] = sanitize_title( $input['hide_backend-register'] );
			} else {
				$input['hide_backend-register'] = 'wp-register.php';
			}

			$forbidden_slugs = array( 'admin', 'login', 'wp-login.php', 'dashboard', 'wp-admin' );

			if ( in_array( $input[''], $forbidden_slugs ) && $input['hide_backend-enabled'] === true ) {

				$type    = 'error';
				$message = __( 'Invalid hide login slug used. The login url slug cannot be "login," "admin," "dashboard," or "wp-login.php" as these are use by default in WordPress.', 'ithemes-security' );

			} else {

				add_rewrite_rule( $input['hide_backend-slug'] . '/?$', 'wp-login.php', 'top' );

			}

			//process away mode settings
			$input['away_mode-enabled'] = ( isset( $input['away_mode-enabled'] ) && intval( $input['away_mode-enabled'] == 1 ) ? true : false );
			$input['away_mode-type']    = ( isset( $input['away_mode-type'] ) && intval( $input['away_mode-type'] == 1 ) ? 1 : 2 );

			//we don't need to process this again if it is a multisite installation
			if ( ! is_multisite() ) {

				$input['away_mode-start'] = strtotime( $input['start']['date'] . ' ' . $input['start']['hour'] . ':' . $input['start']['minute'] . ' ' . $input['start']['sel'] );
				$input['away_mode-end']   = strtotime( $input['end']['date'] . ' ' . $input['end']['hour'] . ':' . $input['end']['minute'] . ' ' . $input['end']['sel'] );
				unset( $input['start'] );
				unset( $input['end'] );

			}

			if ( $admin_success === false ) {

				$type    = 'error';
				$message = __( 'The new admin username you entered is invalid or WordPress could not change the user id or username. Please check the name and try again.', 'ithemes-security' );

			} elseif ( $this->module->check_away( true, $input ) === true ) {

				$input['away_mode-enabled'] = false; //disable away mode

				$type    = 'error';
				$message = __( 'Invalid time listed. The time entered would lock you out of your site now. Please try again.', 'ithemes-security' );

			} elseif ( $input['away_mode-type'] === 2 && $input['away_mode-end'] < $input['away_mode-start'] ) {

				$input['away_mode-enabled'] = false; //disable away mode

				$type    = 'error';
				$message = __( 'Invalid time listed. The start time selected is after the end time selected.', 'ithemes-security' );

			} elseif ( $input['away_mode-type'] === 2 && $input['away_mode-end'] < current_time( 'timestamp' ) ) {

				$input['away_mode-enabled'] = false; //disable away mode

				$type    = 'error';
				$message = __( 'Invalid time listed. The period selected already ended.', 'ithemes-security' );

			} else {

				$type    = 'updated';
				$message = __( 'Settings Updated', 'ithemes-security' );

			}

			if ( $input['away_mode-enabled'] == 1 && ! file_exists( $this->away_file ) ) {

				@file_put_contents( $this->away_file, 'true' );

			} else {

				@unlink( $this->away_file );

			}

			if ( $input['hide_backend-register'] != 'wp-register.php' && $input['hide_backend-enabled'] === true ) {
				add_rewrite_rule( $input['hide_backend-register'] . '/?$', $input['hide_backend-slug'] . '?action=register', 'top' ); //Login rewrite rule
			}

			//process other settings
			$input['other-login_errors'] = ( isset( $input['other-login_errors'] ) && intval( $input['other-login_errors'] == 1 ) ? true : false );

			flush_rewrite_rules();

			add_settings_error( 'itsec_admin_notices', esc_attr( 'settings_updated' ), $message, $type );

			return $input;

		}

		/**
		 * Prepare and save options in network settings
		 *
		 * @return void
		 */
		public function save_network_options() {

			$settings['brute_force-enabled']           = ( isset( $_POST['itsec_authentication']['brute_force-enabled'] ) && intval( $_POST['itsec_authentication']['brute_force-enabled'] == 1 ) ? true : false );
			$settings['brute_force-max_attempts_host'] = isset( $_POST['itsec_authentication']['brute_force-max_attempts_host'] ) ? absint( $_POST['itsec_authentication']['brute_force-max_attempts_host'] ) : 5;
			$settings['brute_force-max_attempts_user'] = isset( $_POST['itsec_authentication']['brute_force-max_attempts_user'] ) ? absint( $_POST['itsec_authentication']['brute_force-max_attempts_user'] ) : 10;
			$settings['brute_force-check_period']      = isset( $_POST['itsec_authentication']['brute_force-check_period'] ) ? absint( $_POST['itsec_authentication']['brute_force-check_period'] ) : 5;

			$settings['strong_passwords-enabled'] = ( isset( $_POST['itsec_authentication']['strong_passwords-enabled'] ) && intval( $_POST['itsec_authentication']['strong_passwords-enabled'] == 1 ) ? true : false );
			if ( isset( $_POST['itsec_authentication']['strong_passwords-roll'] ) && ctype_alpha( wp_strip_all_tags( $_POST['itsec_authentication']['strong_passwords-roll'] ) ) ) {
				$settings['strong_passwords-roll'] = wp_strip_all_tags( $_POST['itsec_authentication']['strong_passwords-roll'] );
			}

			$settings['hide_backend-enabled']  = ( isset( $_POST['itsec_authentication']['hide_backend-enabled'] ) && intval( $_POST['itsec_authentication']['hide_backend-enabled'] == 1 ) ? true : false );
			$settings['hide_backend-slug']     = sanitize_title( $_POST['itsec_authentication']['hide_backend-slug'] );
			$settings['hide_backend-register'] = sanitize_title( $_POST['itsec_authentication']['hide_backend-register'] );

			$settings['away_mode-enabled'] = ( isset( $_POST['itsec_authentication']['away_mode-enabled'] ) && intval( $_POST['itsec_authentication']['away_mode-enabled'] == 1 ) ? true : false );
			$settings['away_mode-type']    = ( isset( $_POST['itsec_authentication']['away_mode-type'] ) && intval( $_POST['itsec_authentication']['away_mode-type'] == 1 ) ? 1 : 2 );
			$settings['away_mode-start']   = strtotime( $_POST['itsec_authentication']['start']['date'] . ' ' . $_POST['itsec_authentication']['start']['hour'] . ':' . $_POST['itsec_authentication']['start']['minute'] . ' ' . $_POST['itsec_authentication']['start']['sel'] );
			$settings['away_mode-end']     = strtotime( $_POST['itsec_authentication']['end']['date'] . ' ' . $_POST['itsec_authentication']['end']['hour'] . ':' . $_POST['itsec_authentication']['end']['minute'] . ' ' . $_POST['itsec_authentication']['end']['sel'] );

			$settings['other-login_errors'] = ( isset( $_POST['itsec_authentication']['other-login_errors'] ) && intval( $_POST['itsec_authentication']['other-login_errors'] == 1 ) ? true : false );

			update_site_option( 'itsec_authentication', $settings ); //we must manually save network options

			//send them back to the away mode options page
			wp_redirect( add_query_arg( array( 'page' => 'toplevel_page_itsec-authentication', 'updated' => 'true' ), network_admin_url( 'admin.php' ) ) );
			exit();

		}

		/**
		 * Sets the status in the plugin sidebar
		 *
		 * @return array $statuses array of sidebar statuses
		 */
		public function sidebar_status( $statuses ) {

			$link = 'admin.php?page=toplevel_page_itsec-authentication';

			$status_array = 'high';
			$status       = array(
				'bad_text'  => __( 'You are not enforcing strong passwords for any users.', 'ithemes-security' ),
				'good_text' => __( 'You are enforcing strong passwords for at least the administrator accounts.', 'ithemes-security' ),
				'option'    => 'itsec_authentication',
				'setting'   => 'strong_passwords-enabled',
				'value'     => 1
			);

			array_push( $statuses[$status_array], $status );

			$status_array = 'high';
			$status       = array(
				'good_text' => __( 'Your login area is protected from brute force attacks.', 'ithemes-security' ),
				'bad_text'  => __( 'Your login area is not protected from brute force attacks.', 'ithemes-security' ),
				'option'    => 'itsec_authentication',
				'setting'   => 'brute_force-enabled',
				'value'     => 1
			);

			array_push( $statuses[$status_array], $status );

			return $statuses;

		}

		/**
		 * echos Enable Strong Passwords Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function strong_passwords_enabled( $args ) {

			if ( isset( $this->settings['strong_passwords-enabled'] ) && $this->settings['strong_passwords-enabled'] === true ) {
				$enabled = 1;
			} else {
				$enabled = 0;
			}

			$content = '<input type="checkbox" id="itsec_authentication_strong_passwords_enabled" name="itsec_authentication[strong_passwords-enabled]" value="1" ' . checked( 1, $enabled, false ) . '/>';
			$content .= '<label for="itsec_authentication_strong_passwords_enabled"> ' . __( 'Enable strong password enforcement.', 'ithemes-security' ) . '</label>';

			echo $content;

		}

		/**
		 * Echo the Strong Passwords Header
		 */
		public function strong_passwords_header() {

			$content = '<h2 id="strong_passwords" class="settings-section-header">' . __( 'Enforce Strong Passwords', 'ithemes-security' ) . '</h2>';
			$content .= '<p>' . __( 'Force users to use strong passwords as rated by the WordPress password meter.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Strong Passwords Role Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function strong_passwords_role( $args ) {

			if ( isset( $this->settings['strong_passwords-roll'] ) ) {
				$roll = $this->settings['strong_passwords-roll'];
			} else {
				$roll = 'administrator';
			}

			$content = '<select name="itsec_authentication[strong_passwords-roll]" id="itsec_authentication_strong_passwords_roll">';
			$content .= '<option value="administrator" ' . selected( $roll, 'administrator', false ) . '>' . translate_user_role( 'Administrator' ) . '</option>';
			$content .= '<option value="editor" ' . selected( $roll, 'editor', false ) . '>' . translate_user_role( 'Editor' ) . '</option>';
			$content .= '<option value="author" ' . selected( $roll, 'author', false ) . '>' . translate_user_role( 'Author' ) . '</option>';
			$content .= '<option value="contributor" ' . selected( $roll, 'contributor', false ) . '>' . translate_user_role( 'Contributor' ) . '</option>';
			$content .= '<option value="subscriber" ' . selected( $roll, 'subscriber', false ) . '>' . translate_user_role( 'Subscriber' ) . '</option>';
			$content .= '</select><br>';
			$content .= '<label for="itsec_authentication_strong_passwords_roll"> ' . __( 'Minimum role at which a user must choose a strong password.' ) . '</label>';

			$content .= '<p class="description"> ' . __( 'For more information on WordPress roles and capabilities please see', 'ithemes-security' ) . ' <a href="http://codex.wordpress.org/Roles_and_Capabilities" target="_blank">http://codex.wordpress.org/Roles_and_Capabilities</a>.</p>';
			$content .= '<p class="warningtext description">' . __( 'Warning: If your site invites public registrations setting the role too low may annoy your members.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * Start the Authentication Admin Module
		 *
		 * @param Ithemes_ITSEC_Core   $core   Instance of core plugin class
		 * @param ITSEC_Authentication $module Instance of the authentication module class
		 *
		 * @return ITSEC_Authentication_Admin                The instance of the ITSEC_Authentication_Admin class
		 */
		public static function start( $core, $module ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $core, $module );
			}

			return self::$instance;

		}

	}

}