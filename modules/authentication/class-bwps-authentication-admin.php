<?php

if ( ! class_exists( 'BWPS_Authentication_Admin' ) ) {

	class BWPS_Authentication_Admin {

		private static $instance = NULL;

		private
			$settings,
			$core,
			$module,
			$away_file,
			$page;

		private function __construct( $core, $module ) {

			global $bwps_globals;

			$this->core      = $core;
			$this->module	 = $module;
			$this->settings  = get_site_option( 'bwps_authentication' );
			$this->away_file = $bwps_globals['upload_dir'] . '/bwps_away.confg'; //override file

			add_action( 'bwps_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) ); //enqueue scripts for admin page
			add_filter( 'bwps_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( 'bwps_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu
			add_filter( 'bwps_add_dashboard_status', array( $this, 'dashboard_status' ) ); //add information for plugin status

			//manually save options on multisite
			if ( is_multisite() ) {
				add_action( 'network_admin_edit_bwps_authentication', array( $this, 'save_network_options' ) ); //save multisite options
			}

		}

		/**
		 * Register subpage for Away Mode
		 *
		 * @param array $available_pages array of BWPS settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $bwps_globals;

			$this->page = $available_pages[0] . '-authentication';

			$available_pages[] = add_submenu_page(
				'bwps',
				__( 'Authentication', 'better_wp_security' ),
				__( 'Authentication', 'better_wp_security' ),
				$bwps_globals['plugin_access_lvl'],
				$available_pages[0] . '-authentication',
				array( $this->core, 'render_page' )
			);

			return $available_pages;

		}

		public function add_admin_tab( $tabs ) {

			$tabs[$this->page] = __( 'Auth', 'better_wp_security' );

			return $tabs;

		}

		/**
		 * Add meta boxes to primary options pages
		 *
		 * @param array $available_pages array of available page_hooks
		 */
		public function add_admin_meta_boxes( $available_pages ) {

			add_meta_box(
				'authentication_description',
				__( 'Description', 'better_wp_security' ),
				array( $this, 'add_module_intro' ),
				'security_page_toplevel_page_bwps-authentication',
				'normal',
				'core'
			);

			add_meta_box(
				'authentication_options',
				__( 'Configure Authentication Security', 'better_wp_security' ),
				array( $this, 'metabox_advanced_settings' ),
				'security_page_toplevel_page_bwps-authentication',
				'advanced',
				'core'
			);

		}

		/**
		 * Add Away mode Javascript
		 *
		 * @return void
		 */
		public function admin_script() {

			global $bwps_globals;

			if ( strpos( get_current_screen()->id, 'security_page_toplevel_page_bwps-authentication' ) !== false ) {

				wp_enqueue_script( 'bwps_authentication_js', $bwps_globals['plugin_url'] . 'modules/authentication/js/admin-authentication.js', 'jquery', $bwps_globals['plugin_build'] );
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_style( 'jquery-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );

			}

		}

		/**
		 * Sets the status in the plugin dashboard
		 *
		 * @return void
		 */
		public function dashboard_status( $statuses ) {

			$link = 'admin.php?page=toplevel_page_bwps-authentication';

			if ( $this->settings['strong_passwords-enabled'] === 1 && $this->settings['strong_passwords-roll'] == 'subscriber' ) {

				$status_array = 'safe-medium';
				$status = array(
					'text' => __( 'You are enforcing strong passwords for all users.', 'better_wp_security' ),
					'link' => $link,
				);

			} elseif ( $this->settings['strong_passwords-enabled'] === true  ) {

				$status_array = 'low';
				$status = array(
					'text' => __( 'You are enforcing strong passwords, but not for all users.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'medium';
				$status = array(
					'text' => __( 'You are not enforcing strong passwords for any users.', 'better_wp_security' ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['away_mode-enabled'] === true ) {

				$status_array = 'safe-medium';
				$status = array(
					'text' => __( 'Away Mode is enabled and your WordPress Dashboard is not available when you will not be needing it.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'medium';
				$status = array(
					'text' => __( 'Your WordPress Dashboard is available 24/7. Do you really update 24 hours a day? Consider using Away Mode.', 'better_wp_security' ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			return $statuses;

		}

		/**
		 * Execute admin initializations
		 *
		 * @return void
		 */
		public function initialize_admin() {

			//Add Settings sections
			add_settings_section(
				'authentication_brute_force',
				__( 'Brute Force Protection', 'better_wp_security' ),
				array( $this, 'brute_force_header' ),
				'security_page_toplevel_page_bwps-authentication'
			);

			add_settings_section(
				'authentication_strong_passwords-enabled',
				__( 'Enforce Strong Passwords', 'better_wp_security' ),
				array( $this, 'strong_passwords_header' ),
				'security_page_toplevel_page_bwps-authentication'
			);

			add_settings_section(
				'authentication_strong_passwords-settings',
				__( 'Enforce Strong Passwords', 'better_wp_security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_bwps-authentication'
			);

			add_settings_section(
				'authentication_hide_backend-enabled',
				__( 'Hide Login and Admin', 'better_wp_security' ),
				array( $this, 'hide_backend_header' ),
				'security_page_toplevel_page_bwps-authentication'
			);

			add_settings_section(
				'authentication_hide_backend-settings',
				__( 'Hide Login and Admin', 'better_wp_security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_bwps-authentication'
			);

			add_settings_section(
				'authentication_away_mode-enabled',
				__( 'Away Mode', 'better_wp_security' ),
				array( $this, 'away_mode_header' ),
				'security_page_toplevel_page_bwps-authentication'
			);

			add_settings_section(
				'authentication_away_mode-settings',
				__( 'Away Mode', 'better_wp_security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_bwps-authentication'
			);

			//Strong Passwords Fields
			add_settings_field(
				'bwps_authentication[strong_passwords-enabled]',
				__( 'Enable Strong Passwords', 'better_wp_security' ),
				array( $this, 'strong_passwords_enabled' ),
				'security_page_toplevel_page_bwps-authentication',
				'authentication_strong_passwords-enabled'
			);

			add_settings_field(
				'bwps_authentication[strong_passwords-roll]',
				__( 'Select Roll for Strong Passwords', 'better_wp_security' ),
				array( $this, 'strong_passwords_role' ),
				'security_page_toplevel_page_bwps-authentication',
				'authentication_strong_passwords-settings'
			);

			//Hide Backend Fields
			add_settings_field(
				'bwps_authentication[hide_backend-enabled]',
				__( 'Enable Hide Backend', 'better_wp_security' ),
				array( $this, 'hide_backend_enabled' ),
				'security_page_toplevel_page_bwps-authentication',
				'authentication_hide_backend-enabled'
			);

			add_settings_field(
				'bwps_authentication[hide_backend-slug]',
				__( 'Login Slug', 'better_wp_security' ),
				array( $this, 'hide_backend_slug' ),
				'security_page_toplevel_page_bwps-authentication',
				'authentication_hide_backend-settings'
			);

			add_settings_field(
				'bwps_authentication[hide_backend-register]',
				__( 'Register Slug', 'better_wp_security' ),
				array( $this, 'hide_backend_register' ),
				'security_page_toplevel_page_bwps-authentication',
				'authentication_hide_backend-settings'
			);

			//Away Mode Fields
			add_settings_field(
				'bwps_authentication[away_mode-enabled]',
				__( 'Enable Away Mode', 'better_wp_security' ),
				array( $this, 'away_mode_enabled' ),
				'security_page_toplevel_page_bwps-authentication',
				'authentication_away_mode-enabled'
			);

			add_settings_field(
				'bwps_authentication[away_mode-type]',
				__( 'Type of Restriction', 'better_wp_security' ),
				array( $this, 'away_mode_type' ),
				'security_page_toplevel_page_bwps-authentication',
				'authentication_away_mode-settings'
			);

			add_settings_field(
				'bwps_authentication[away_mode-start_date]',
				__( 'Start Date', 'better_wp_security' ),
				array( $this, 'away_mode_start_date' ),
				'security_page_toplevel_page_bwps-authentication',
				'authentication_away_mode-settings'
			);

			add_settings_field(
				'bwps_authentication[away_mode-start_time]',
				__( 'Start Time', 'better_wp_security' ),
				array( $this, 'away_mode_start_time' ),
				'security_page_toplevel_page_bwps-authentication',
				'authentication_away_mode-settings'
			);

			add_settings_field(
				'bwps_authentication[away_mode-end_date]',
				__( 'End Date', 'better_wp_security' ),
				array( $this, 'away_mode_end_date' ),
				'security_page_toplevel_page_bwps-authentication',
				'authentication_away_mode-settings'
			);

			//end time field
			add_settings_field(
				'bwps_authentication[away_mode-end_time]',
				__( 'End Time', 'better_wp_security' ),
				array( $this, 'away_mode_end_time' ),
				'security_page_toplevel_page_bwps-authentication',
				'authentication_away_mode-settings'
			);

			//Register the settings field for the entire module
			register_setting(
				'security_page_toplevel_page_bwps-authentication',
				'bwps_authentication',
				array( $this, 'sanitize_module_input' )
			);

		}

		/**
		 * Empty callback function
		 */
		public function empty_callback_function() {}

		/**
		 * Echo the Brute Force Header
		 */
		public function brute_force_header() {

			echo '<h2 class="settings-section-header">' . __( 'Brute Force Protection', 'better-wp-security' ) . '</h2>';

		}

		/**
		 * Echo the Strong Passwords Header
		 */
		public function strong_passwords_header() {

			$content =  '<h2 class="settings-section-header">' . __( 'Enforce Strong Passwords', 'better-wp-security' ) . '</h2>';
			$content .= '<p>' . __( 'Force users to use strong passwords as rated by the WordPress password meter.', 'better-wp-security' ) . '</p>';

			echo $content;

		}

		/**
		 * Echo the Hide Backend Header
		 */
		public function hide_backend_header() {

			$content =  '<h2 class="settings-section-header">' . __( 'Hide the Login Page', 'better-wp-security' ) . '</h2>';
			$content .= '<p>' . __( 'Hides the login and admin pages making them harder to find by automated attacks and making them easier for users unfamiliar with the WordPress platform.', 'better-wp-security' ) . '</p>';

			echo $content;

		}

		/**
		 * Echo The Away Mode Header
		 */
		public function away_mode_header() {

			$content =  '<h2 class="settings-section-header">' . __( 'Configure Away Mode', 'better-wp-security' ) . '</h2>';

			$content .= '<p>' . __( 'As most sites are only updated at certain times of the day it is not always necessary to provide access to the WordPress dashboard 24 hours a day, 7 days a week. The options below will allow you to disable access to the WordPress Dashboard for the specified period. In addition to limiting exposure to attackers this could also be useful to disable site access based on a schedule for classroom or other reasons.', 'better_wp_security' ) . '</p>';

			if ( preg_match( "/^(G|H)(:| \\h)/", get_option( 'time_format' ) ) ) {
				$currdate = date_i18n( 'l, d F Y' . ' ' . get_option( 'time_format' ), current_time( 'timestamp' ) );
			} else {
				$currdate = date( 'g:i a \o\n l F jS, Y', current_time( 'timestamp' ) );
			}

			$content .= '<p>' . sprintf( __( 'Please note that according to your %sWordPress timezone settings%s your current time is %s. If this is incorrect please correct it on the %sWordPress general settings page%s by setting the appropriate time zone. Failure to set the correct timezone may result in unintended lockouts.', 'better_wp_security' ), '<a href="options-general.php">', '</a>', '<strong style="color: #f00; font-size: 150%;"><em>' . $currdate . '</em></strong>', '<a href="options-general.php">', '</a>' ) . '</p>';

			echo $content;

			//set information explaining away mode is enabled
			if ( isset( $this->settings['enabled'] ) && $this->settings['enabled'] === 1 && ( $this->settings['type'] === 1 || ( $this->settings['end'] > current_time( 'timestamp' ) ) ) ) {

				$content = '<hr />';

				$content .= sprintf( '<p><strong>%s</strong></p>', __( 'Away mode is currently enabled.', 'better_wp_security' ) );

				//Create the appropriate notification based on daily or one time use
				if ( $this->settings['type'] === 1 ) {

					$content .= sprintf( '<p>' . __( 'The dashboard of this website will become unavailable %s%s%s from %s%s%s until %s%s%s.', 'better_wp_security' ) . '</p>', '<strong>', __( 'every day', 'better_wp_security' ), '</strong>', '<strong>', date_i18n( get_option( 'time_format' ), $this->settings['start'] ), '</strong>', '<strong>', date_i18n( get_option( 'time_format' ), $this->settings['end'] ), '</strong>' );

				} else {

					$content .= sprintf( '<p>' . __( 'The dashboard of this website will become unavailable from %s%s%s on %s%s%s until %s%s%s on %s%s%s.', 'better_wp_security' ) . '</p>', '<strong>', date_i18n( get_option( 'time_format' ), $this->settings['start'] ), '</strong>', '<strong>', date_i18n( get_option( 'date_format' ), $this->settings['start'] ), '</strong>', '<strong>', date_i18n( get_option( 'time_format' ), $this->settings['end'] ), '</strong>', '<strong>', date_i18n( get_option( 'date_format' ), $this->settings['end'] ), '</strong>' );

				}

				$content .= '<p>' . __( 'You will not be able to log into this website when the site is unavailable.', 'better_wp_security' ) . '</p>';

				echo $content;
			}

		}

		/**
		 * echos Enable Strong Passwords Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function strong_passwords_enabled( $args ) {

			if ( isset( $this->settings['strong_passwords-enabled'] ) && $this->settings['strong_passwords-enabled'] === true ) {
				$enabled = 1;
			} else {
				$enabled = 0;
			}

			$content = '<input type="checkbox" id="bwps_authentication_strong_passwords_enabled" name="bwps_authentication[strong_passwords-enabled]" value="1" ' . checked( 1, $enabled, false ) . '/>';
			$content .= '<label for="bwps_authentication_strong_passwords_enabled"> ' . __( 'Check this box to enable strong password enforcement.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Strong Passwords Role Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function strong_passwords_role( $args ) {

			if ( isset( $this->settings['strong_passwords-roll'] ) ) {
				$roll =  $this->settings['strong_passwords-roll'];
			} else {
				$roll = 'administrator';
			}

			$content = '<select name="bwps_authentication[strong_passwords-roll]" id="bwps_authentication_strong_passwords_roll">';
			$content .= '<option value="administrator" ' . selected( $roll, 'administrator', false ) . '>' . translate_user_role( 'Administrator' ) . '</option>';
			$content .= '<option value="editor" ' . selected( $roll, 'editor', false ) . '>' . translate_user_role( 'Editor' ) . '</option>';
			$content .= '<option value="author" ' . selected( $roll, 'author', false ) . '>' . translate_user_role( 'Author' ) . '</option>';
			$content .= '<option value="contributor" ' . selected( $roll, 'contributor', false ) . '>' . translate_user_role( 'Contributor' ) . '</option>';
			$content .= '<option value="subscriber" ' . selected( $roll, 'subscriber', false ) . '>' . translate_user_role( 'Subscriber' ) . '</option>';
			$content .= '</select>';
			$content .= '<label for="bwps_authentication_strong_passwords_roll"> ' . __( 'Minimum role at which a user must choose a strong password. For more information on WordPress roles and capabilities please see', 'better-wp-security' ) . ' <a href="http://codex.wordpress.org/Roles_and_Capabilities" target="_blank">http://codex.wordpress.org/Roles_and_Capabilities</a>.</p></label>';
			$content .= '<p class="warningtext">' . __( 'Warning: If your site invites public registrations setting the role too low may annoy your members.', 'better-wp-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Hide Backend  Enabled Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function hide_backend_enabled( $args ) {

			if ( ( get_option( 'permalink_structure' ) == ''  || get_option( 'permalink_structure' ) == false ) && ! is_multisite() ) {

				$adminurl = is_multisite() ? admin_url() . 'network/' : admin_url();

				$content = sprintf( '<p class="noPermalinks">%s <a href="%soptions-permalink.php">%s</a> %s</p>', __( 'You must turn on', 'better-wp-security' ), $adminurl, __( 'WordPress permalinks', 'better-wp-security' ), __( 'to use this feature.', 'better-wp-security' ) );				

			} else {

				if ( isset( $this->settings['hide_backend-enabled'] ) && $this->settings['hide_backend-enabled'] === true ) {
					$enabled = 1;
				} else {
					$enabled = 0;
				}

				$content = '<input type="checkbox" id="bwps_authentication_hide_backend_enabled" name="bwps_authentication[hide_backend-enabled]" value="1" ' . checked( 1, $enabled, false ) . '/>';
				$content .= '<label for="bwps_authentication_hide_backend_enabled"> ' . __( 'Check this box to enable the hide backend feature.', 'better_wp_security' ) . '</label>';

			} 

			echo $content;

		}

		/**
		 * echos Hide Backend Slug  Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function hide_backend_slug( $args ) {

			if ( ( get_option( 'permalink_structure' ) == ''  || get_option( 'permalink_structure' ) == false ) && ! is_multisite() ) {

				$content = '';

			} else {

				$content = '<input name="bwps_authentication[hide_backend-slug]" id="bwps_authentication_strong_passwords_slug" value="' . sanitize_title( $this->settings['hide_backend-slug'] ) . '" type="text"><br />';
				$content .= '<em><span style="color: #666666;"><strong>' . __( 'Login URL:', 'better-wp-security' ) . '</strong> ' . trailingslashit( get_option( 'siteurl' ) ) . '</span><span style="color: #4AA02C">' . sanitize_title( $this->settings['hide_backend-slug'] ) . '</span></em>';
				$content .= '<p>' . __( 'The login url slug cannot be "login," "admin," "dashboard," or "wp-login.php" as these are use by default in WordPress.', 'better-wp-security' ) . '</p>';

			}

			echo $content;

		}

		/**
		 * echos Register Slug  Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function hide_backend_register( $args ) {

			if ( ( get_option( 'permalink_structure' ) == ''  || get_option( 'permalink_structure' ) == false ) && ! is_multisite() ) {

				$content = '';

			} else {

				$content = '<input name="bwps_authentication[hide_backend-register]" id="bwps_authentication_strong_passwords_register" value="' . sanitize_title( $this->settings['hide_backend-register'] ) . '" type="text"><br />';
				$content .= '<em><span style="color: #666666;"><strong>' . __( 'Registration URL:', 'better-wp-security' ) . '</strong> ' . trailingslashit( get_option( 'siteurl' ) ) . '</span><span style="color: #4AA02C">' . sanitize_title( $this->settings['hide_backend-register'] ) . '</span></em>';

			}

			echo $content;

		}

		/**
		 * echos Enable Away Mode Field
		 *
		 * @param  array $args field arguements
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

			$content = '<input type="checkbox" id="bwps_authentication_away_mode_enabled" name="bwps_authentication[away_mode-enabled]" value="1" ' . checked( 1, $enabled, false ) . '/>';
			$content .= '<label for="bwps_authentication_away_mode_enabled"> ' . __( 'Check this box to enable away mode', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos End date field
		 *
		 * @param  array $args field arguements
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
			$content = '<input class="end_date_field" type="text" id="bwps_authentication_away_mode_end_date" name="bwps_authentication[end][date]" value="' . date( 'm/d/y', $end ) . '"/>';
			$content .= '<label class="end_date_field" for="bwps_authentication_away_mode_end_date"> ' . __( 'Set the date at which the admin dashboard should become available', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos End time field
		 *
		 * @param  array $args field arguements
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
			$content = '<select name="bwps_authentication[end][hour]" id="bwps_authentication_away_mod_end_time">';

			for ( $i = 1; $i <= 12; $i ++ ) {
				$content .= '<option value="' . sprintf( '%02d', $i ) . '" ' . selected( date( 'g', $end ), $i, false ) . '>' . $i . '</option>';
			}

			$content .= '</select>';

			//Minute Field
			$content .= '<select name="bwps_authentication[end][minute]" id="bwps_authentication_away_mod_end_time">';

			for ( $i = 0; $i <= 59; $i ++ ) {

				$content .= '<option value="' . sprintf( '%02d', $i ) . '" ' . selected( date( 'i', $end ), sprintf( '%02d', $i ), false ) . '>' . sprintf( '%02d', $i ) . '</option>';
			}

			$content .= '</select>';

			//AM/PM Field
			$content .= '<select name="bwps_authentication[end][sel]" id="bwps_authentication">';
			$content .= '<option value="am" ' . selected( date( 'a', $end ), 'am', false ) . '>' . __( 'am', 'better_wp_security' ) . '</option>';
			$content .= '<option value="pm" ' . selected( date( 'a', $end ), 'pm', false ) . '>' . __( 'pm', 'better_wp_security' ) . '</option>';
			$content .= '</select>';
			$content .= '<label for="bwps_authentication_away_mod_end_time"> ' . __( 'Set the time at which the admin dashboard should become available again.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Start date field
		 *
		 * @param  array $args field arguements
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
			$content = '<input class="start_date_field" type="text" id="bwps_authentication_away_mode_start_date" name="bwps_authentication[start][date]" value="' . date( 'm/d/y', $start ) . '"/>';
			$content .= '<label class="start_date_field" for="bwps_authentication_away_mode_start_date"> ' . __( 'Set the date at which the admin dashboard should become unavailable', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Start time field
		 *
		 * @param  array $args field arguements
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
			$content = '<select name="bwps_authentication[start][hour]" id="bwps_authentication_away_mod_start_time">';

			for ( $i = 1; $i <= 12; $i ++ ) {
				$content .= '<option value="' . sprintf( '%02d', $i ) . '" ' . selected( date( 'g', $start ), $i, false ) . '>' . $i . '</option>';
			}

			$content .= '</select>';

			//Minute Field
			$content .= '<select name="bwps_authentication[start][minute]" id="bwps_authentication_away_mod_start_time">';

			for ( $i = 0; $i <= 59; $i ++ ) {

				$content .= '<option value="' . sprintf( '%02d', $i ) . '" ' . selected( date( 'i', $start ), sprintf( '%02d', $i ), false ) . '>' . sprintf( '%02d', $i ) . '</option>';
			}

			$content .= '</select>';

			//AM/PM Field
			$content .= '<select name="bwps_authentication[start][sel]" id="bwps_authentication_away_mod_start_time">';
			$content .= '<option value="am" ' . selected( date( 'a', $start ), 'am', false ) . '>' . __( 'am', 'better_wp_security' ) . '</option>';
			$content .= '<option value="pm" ' . selected( date( 'a', $start ), 'pm', false ) . '>' . __( 'pm', 'better_wp_security' ) . '</option>';
			$content .= '</select>';
			$content .= '<label for="bwps_authentication_away_mod_start_time"> ' . __( 'Set the time at which the admin dashboard should become available again.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos type Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function away_mode_type( $args ) {

			$content = '<select name="bwps_authentication[away_mode-type]" id="bwps_authentication_away_mode_type">';
			$content .= '<option value="1" ' . selected( $this->settings['away_mode-type'], 1, false ) . '>' . __( 'Daily', 'better_wp_security' ) . '</option>';
			$content .= '<option value="2" ' . selected( $this->settings['away_mode-type'], 2, false ) . '>' . __( 'One Time', 'better_wp_security' ) . '</option>';
			$content .= '</select>';
			$content .= '<label for="bwps_authentication_away_mode_type"> ' . __( 'Check this box to enable away mode', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * Build and echo the away mode description
		 *
		 * @return void
		 */
		public function add_module_intro( $screen ) {

			$content = '<p>' . __( 'The below settings control who and how users can log in to the WordPress Dashboard. Turning on settings below can greatly increase the security of your WordPress website by preventing many of the common attacks that go after weaknesses in the standard login system.', 'better_wp_security' ) . '</p>';
			$content .= '<p>' . __( 'Please keep in mind the following settings are designed to work primarily with the standard login system. If you have any plugins or a theme that has changed anything already please test your site after turning on the below settings to verify there are no conflicts.', 'better_wp_security' ) . '</p>';

			echo $content;

		}

		/**
		 * Render the settings metabox
		 *
		 * @return void
		 */
		public function metabox_advanced_settings() {

			//set appropriate action for multisite or standard site
			if ( is_multisite() ) {
				$action = 'edit.php?action=bwps_authentication';
			} else {
				$action = 'options.php';
			}

			printf( '<form name="%s" method="post" action="%s">', get_current_screen()->id, $action );

			$this->core->do_settings_sections( 'security_page_toplevel_page_bwps-authentication', false );

			echo '<p>' . PHP_EOL;

			settings_fields( 'security_page_toplevel_page_bwps-authentication' );

			echo '<input class="button-primary" name="submit" type="submit" value="' . __( 'Save Changes', 'better_wp_security' ) . '" />' . PHP_EOL;

			echo '</p>' . PHP_EOL;

			echo '</form>';

		}

		/**
		 * Generate a pseudo-random key
		 * 
		 * @return string pseudo-random key
		 */
		private function generate_key() {

			//Generate a random key to use
			$avail = 'ABCDEFGHIJKLMNOFQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
			$key = '';
				
			//length of hey
			$key_length = rand( 20, 30 );
				
			//generate remaning characters
			for ( $i = 0; $i < $key_length; $i++ ) {
				$key  .= $avail[rand( 0, 61 )];
			}

			return esc_sql( $key );

		}

		/**
		 * Sanitize and validate input
		 *
		 * @param  Array $input array of input fields
		 *
		 * @return Array         Sanitized array
		 */
		public function sanitize_module_input( $input ) {

			//process strong passwords settings
			$input['strong_passwords-enabled'] = ( isset( $input['strong_passwords-enabled'] ) && intval( $input['strong_passwords-enabled'] == 1 ) ? true : false );
			if ( isset( $input['strong_passwords-roll'] ) && ctype_alpha( wp_strip_all_tags( $input['strong_passwords-roll'] ) ) ) {
				$input['strong_passwords-roll'] = wp_strip_all_tags( $input['strong_passwords-roll'] );
			}

			//Process hide backend settings
			$input['hide_backend-enabled'] = ( isset( $input['hide_backend-enabled'] ) && intval( $input['hide_backend-enabled'] == 1 ) ? true : false );
			$input['hide_backend-slug'] = sanitize_title( $input['hide_backend-slug'] );
			$input['hide_backend-register'] = sanitize_title( $input['hide_backend-register'] );

			$forbidden_slugs = array(
				'admin',
				'login',
				'wp-login.php',
				'dashboard',
				'wp-admin'
			);

			if ( in_array( $input[''], $forbidden_slugs ) ) {
				
				$type    = 'error';
				$message = __( 'Invalid hide login slug used. The login url slug cannot be "login," "admin," "dashboard," or "wp-login.php" as these are use by default in WordPress.', 'better_wp_security' );

			} else {

				add_rewrite_rule( $input['hide_backend-slug'] . '/?$', 'wp-login.php', 'top' );
				flush_rewrite_rules();

			}

			//process away mode settings
			$input['away_mode-enabled'] = ( isset( $input['away_mode-enabled'] ) && intval( $input['away_mode-enabled'] == 1 ) ? true : false );
			$input['away_mode-type'] = ( isset( $input['away_mode-type'] ) && intval( $input['away_mode-type'] == 1 ) ? 1 : 2 );

			//we don't need to process this again if it is a multisite installation
			if ( ! is_multisite() ) {

				$input['away_mode-start'] = strtotime( $input['start']['date'] . ' ' . $input['start']['hour'] . ':' . $input['start']['minute'] . ' ' . $input['start']['sel'] );
				$input['away_mode-end']   = strtotime( $input['end']['date'] . ' ' . $input['end']['hour'] . ':' . $input['end']['minute'] . ' ' . $input['end']['sel'] );
				unset( $input['start'] );
				unset( $input['end'] );

			}

			if ( $this->module->check_away( true, $input ) === true ) {

				$input['away_mode-enabled'] = false; //disable away mode

				$type    = 'error';
				$message = __( 'Invalid time listed. The time entered would lock you out of your site now. Please try again.', 'better_wp_security' );

			} elseif ( $input['away_mode-type'] === 2 && $input['away_mode-end'] < $input['away_mode-start'] ) {

				$input['away_mode-enabled'] = false; //disable away mode

				$type    = 'error';
				$message = __( 'Invalid time listed. The start time selected is after the end time selected.', 'better_wp_security' );

			} elseif ( $input['away_mode-type'] === 2 && $input['away_mode-end'] < current_time( 'timestamp' ) ) {

				$input['away_mode-enabled'] = false; //disable away mode

				$type    = 'error';
				$message = __( 'Invalid time listed. The period selected already ended.', 'better_wp_security' );

			} else {

				$type    = 'updated';
				$message = __( 'Settings Updated', 'better_wp_security' );

			}

			if ( $input['away_mode-enabled'] == 1 && ! file_exists( $this->away_file ) ) {

				@file_put_contents( $this->away_file, 'true' );

			} else {

				@unlink( $this->away_file );

			}

			add_settings_error(
				'bwps_admin_notices',
				esc_attr( 'settings_updated' ),
				$message,
				$type
			);

			return $input;

		}

		/**
		 * Prepare and save options in network settings
		 *
		 * @return void
		 */
		public function save_network_options() {

			$settings['strong_passwords-enabled'] = ( isset( $_POST['bwps_authentication']['strong_passwords-enabled'] ) && intval( $_POST['bwps_authentication']['strong_passwords-enabled'] == 1 ) ? true : false );
			if ( isset( $_POST['bwps_authentication']['strong_passwords-roll'] ) && ctype_alpha( wp_strip_all_tags( $_POST['bwps_authentication']['strong_passwords-roll'] ) ) ) {
				$settings['strong_passwords-roll'] = wp_strip_all_tags( $_POST['bwps_authentication']['strong_passwords-roll'] );
			}

			$settings['hide_backend-enabled'] = ( isset( $_POST['bwps_authentication']['hide_backend-enabled'] ) && intval( $_POST['bwps_authentication']['hide_backend-enabled'] == 1 ) ? true : false );
			$settings['hide_backend-slug'] = sanitize_title( $_POST['bwps_authentication']['hide_backend-slug'] );
			$settings['hide_backend-register'] = sanitize_title( $_POST['bwps_authentication']['hide_backend-register'] );

			$settings['away_mode-enabled'] = ( isset( $_POST['bwps_authentication']['away_mode-enabled'] ) && intval( $_POST['bwps_authentication']['away_mode-enabled'] == 1 ) ? true : false );
			$settings['away_mode-type'] = ( isset( $_POST['bwps_authentication']['away_mode-type'] ) && intval( $_POST['bwps_authentication']['away_mode-type'] == 1 ) ? 1 : 2 );
			$settings['away_mode-start'] = strtotime( $_POST['bwps_authentication']['start']['date'] . ' ' . $_POST['bwps_authentication']['start']['hour'] . ':' . $_POST['bwps_authentication']['start']['minute'] . ' ' . $_POST['bwps_authentication']['start']['sel'] );
			$settings['away_mode-end']   = strtotime( $_POST['bwps_authentication']['end']['date'] . ' ' . $_POST['bwps_authentication']['end']['hour'] . ':' . $_POST['bwps_authentication']['end']['minute'] . ' ' . $_POST['bwps_authentication']['end']['sel'] );

			update_site_option( 'bwps_authentication', $settings ); //we must manually save network options

			//send them back to the away mode options page
			wp_redirect( add_query_arg( array( 'page' => 'toplevel_page_bwps-authentication', 'updated' => 'true' ), network_admin_url( 'admin.php' ) ) );
			exit();

		}

		/**
		 * Start the System Tweaks Admin Module
		 *
		 * @param Ithemes_BWPS_Core $core Instance of core plugin class
		 * @param BWPS_Authentication $module Instance of the authentication module class
		 *
		 * @return BWPS_Authentication_Admin                The instance of the BWPS_Authentication_Admin class
		 */
		public static function start( $core, $module ) {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self( $core, $module );
			}

			return self::$instance;

		}

	}

}