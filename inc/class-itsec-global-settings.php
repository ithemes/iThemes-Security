<?php
/**
 * Plugin-wide settings for logs, email and more
 *
 * @package iThemes-Security
 * @since   4.0
 */
if ( ! class_exists( 'ITSEC_Global_Settings' ) ) {

	class ITSEC_Global_Settings {

		private static $instance = null;

		private
			$settings,
			$core,
			$page;

		private function __construct( $core ) {

			$this->core     = $core;
			$this->settings = get_site_option( 'itsec_global' );

			add_action( 'itsec_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_filter( 'itsec_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( 'itsec_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu

			//manually save options on multisite
			if ( is_multisite() ) {
				add_action( 'network_admin_edit_itsec_global', array( $this, 'save_network_options' ) ); //save multisite options
			}

		}

		/**
		 * Add meta boxes to primary options pages
		 *
		 * @param array $available_pages array of available page_hooks
		 */
		public function add_admin_meta_boxes( $available_pages ) {

			add_meta_box(
				'global_description',
				__( 'Description', 'ithemes-security' ),
				array( $this, 'add_module_intro' ),
				'security_page_toplevel_page_itsec-global',
				'normal',
				'core'
			);

			add_meta_box(
				'global_options',
				__( 'Configure Global Settings', 'ithemes-security' ),
				array( $this, 'metabox_advanced_settings' ),
				'security_page_toplevel_page_itsec-global',
				'advanced',
				'core'
			);

		}

		/**
		 * Adds tab to plugin administration area
		 *
		 * @param array $tabs array of tabs
		 *
		 * @return mixed array of tabs
		 */
		public function add_admin_tab( $tabs ) {

			$tabs[$this->page] = __( 'Global', 'ithemes-security' );

			return $tabs;

		}

		/**
		 * Build and echo the away mode description
		 *
		 * @return void
		 */
		public function add_module_intro( $screen ) {

			$content = '<p>' . __( 'The settings below are used throughout the iThemes Security system.', 'ithemes-security' ) . '</p>';
			echo $content;

		}

		/**
		 * Register subpage for Away Mode
		 *
		 * @param array $available_pages array of ITSEC settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $itsec_globals;

			$this->page = $available_pages[0] . '-global';

			$available_pages[] = add_submenu_page(
				'itsec',
				__( 'Global Settings', 'ithemes-security' ),
				__( 'Global Settings', 'ithemes-security' ),
				$itsec_globals['plugin_access_lvl'],

				$available_pages[0] . '-global', array( $this->core, 'render_page' )
			);

			return $available_pages;

		}

		/**
		 * echos Backup email Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function backup_email( $args ) {

			if ( isset( $this->settings['backup_email'] ) && is_array( $this->settings['backup_email'] ) ) {
				$emails = implode( PHP_EOL, $this->settings['backup_email'] );
				$emails = sanitize_text_field( $emails );
			} else {
				$emails = '';
			}

			$content = '<textarea id="itsec_global_backup_email" name="itsec_global[backup_email]">' . $emails . '</textarea><br>';
			$content .= '<label for="itsec_global_backup_email"> ' . __( 'The email address(es) all database backups will be sent to. One address per line.', 'ithemes-security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Blacklist Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function blacklist( $args ) {

			if ( isset( $this->settings['blacklist'] ) && $this->settings['blacklist'] === false ) {
				$blacklist = 0;
			} else {
				$blacklist = 1;
			}

			$content = '<input type="checkbox" id="itsec_global_blacklist" name="itsec_global[blacklist]" value="1" ' . checked( 1, $blacklist, false ) . '/>';
			$content .= '<label for="itsec_global_blacklist"> ' . __( 'Enable Blacklist Repeat Offender', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'If this box is checked the IP address of the offending computer will be added to the "Ban Users" blacklist after reaching the number of lockouts listed below.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Blacklist Threshold Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function blacklist_count( $args ) {

			if ( isset( $this->settings['blacklist_count'] ) ) {
				$blacklist_count = absint( $this->settings['blacklist_count'] );
			} else {
				$blacklist_count = 3;
			}

			$content = '<input class="small-text" name="itsec_global[blacklist_count]" id="itsec_global_blacklist_count" value="' . $blacklist_count . '" type="text">';
			$content .= '<label for="itsec_global_blacklist_count"> ' . __( 'Lockouts', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'The number of lockouts per IP before the host is banned permanently from this site.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Blacklist Lookback Period Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function blacklist_period( $args ) {

			if ( isset( $this->settings['blacklist_period'] ) ) {
				$blacklist_period = absint( $this->settings['blacklist_period'] );
			} else {
				$blacklist_period = 7;
			}

			$content = '<input class="small-text" name="itsec_global[blacklist_period]" id="itsec_global_blacklist_period" value="' . $blacklist_period . '" type="text">';
			$content .= '<label for="itsec_global_blacklist_period"> ' . __( 'Days', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'How many days should a lockout be remembered to meet the blacklist count above.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Lockout Email Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function email_notifications( $args ) {

			if ( isset( $this->settings['email_notifications'] ) && $this->settings['email_notifications'] === false ) {
				$email_notifications = 0;
			} else {
				$email_notifications = 1;
			}

			$content = '<input type="checkbox" id="itsec_global_email_notifications" name="itsec_global[email_notifications]" value="1" ' . checked( 1, $email_notifications, false ) . '/>';
			$content .= '<label for="itsec_global_email_notifications">' . __( 'Enable Email Lockout Notifications', 'ithemes-security' ) . '</label>';
			$content .= sprintf( '<p class="description">%s<a href="admin.php?page=toplevel_page_itsec-global">%s</a>%s</p>', __( 'This feature will trigger an email to be sent to the ', 'ithemes-security' ), __( 'notifications email address', 'ithemes-security' ), __( ' whenever a host or user is locked out of the system.', 'ithemes-security' ) );

			echo $content;

		}

		/**
		 * Empty callback function
		 */
		public function empty_callback_function() {
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
				'global',
				__( 'Global Settings', 'ithemes-security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_itsec-global'
			);

			//Settings Fields
			add_settings_field(
				'itsec_global[notification_email]',
				__( 'Notification Email', 'ithemes-security' ),
				array( $this, 'notification_email' ),
				'security_page_toplevel_page_itsec-global',
				'global'
			);

			add_settings_field(
				'itsec_global[backup_email]',
				__( 'Backup Email', 'ithemes-security' ),
				array( $this, 'backup_email' ),
				'security_page_toplevel_page_itsec-global',
				'global'
			);

			add_settings_field(
				'itsec_global[lockout_message]',
				__( 'Host Lockout Message', 'ithemes-security' ),
				array( $this, 'lockout_message' ),
				'security_page_toplevel_page_itsec-global',
				'global'
			);

			add_settings_field(
				'itsec_global[user_lockout_message]',
				__( 'User Lockout Message', 'ithemes-security' ),
				array( $this, 'user_lockout_message' ),
				'security_page_toplevel_page_itsec-global',
				'global'
			);

			add_settings_field(
				'itsec_global[blacklist]',
				__( 'Blacklist Repeat Offender', 'ithemes-security' ),
				array( $this, 'blacklist' ),
				'security_page_toplevel_page_itsec-global',
				'global'
			);

			add_settings_field(
				'itsec_global[blacklist_count]',
				__( 'Blacklist Threshold', 'ithemes-security' ),
				array( $this, 'blacklist_count' ),
				'security_page_toplevel_page_itsec-global',
				'global'
			);

			add_settings_field(
				'itsec_global[blacklist_period]',
				__( 'Blacklist Lookback Period', 'ithemes-security' ),
				array( $this, 'blacklist_period' ),
				'security_page_toplevel_page_itsec-global',
				'global'
			);

			add_settings_field(
				'itsec_global[lockout_period]',
				__( 'Lockout Period', 'ithemes-security' ),
				array( $this, 'lockout_period' ),
				'security_page_toplevel_page_itsec-global',
				'global'
			);

			add_settings_field(
				'itsec_global[lockout_white_list]',
				__( 'Lockout White List', 'ithemes-security' ),
				array( $this, 'lockout_white_list' ),
				'security_page_toplevel_page_itsec-global',
				'global'
			);

			add_settings_field(
				'itsec_global[email_notifications]',
				__( 'Email Lockout Notifications', 'ithemes-security' ),
				array( $this, 'email_notifications' ),
				'security_page_toplevel_page_itsec-global',
				'global'
			);

			add_settings_field(
				'itsec_global[log_type]',
				__( 'Log Type', 'ithemes-security' ),
				array( $this, 'log_type' ),
				'security_page_toplevel_page_itsec-global',
				'global'
			);

			add_settings_field(
				'itsec_global[log_rotation]',
				__( 'Days to Keep Database Logs', 'ithemes-security' ),
				array( $this, 'log_rotation' ),
				'security_page_toplevel_page_itsec-global',
				'global'
			);

			add_settings_field(
				'itsec_global[log_location]',
				__( 'Path to Log Files', 'ithemes-security' ),
				array( $this, 'log_location' ),
				'security_page_toplevel_page_itsec-global',
				'global'
			);

			//Register the settings field for the entire module
			register_setting(
				'security_page_toplevel_page_itsec-global',
				'itsec_global',
				array( $this, 'sanitize_module_input' )
			);

		}

		/**
		 * echos Admin User Username Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function lockout_message( $args ) {

			if ( isset( $this->settings['lockout_message'] ) ) {
				$lockout_message = sanitize_text_field( $this->settings['lockout_message'] );
			} else {
				$lockout_message = __( 'error', 'ithemes-security' );
			}

			$content = '<textarea class="widefat" name="itsec_global[lockout_message]" id="itsec_global_lockout_message" rows="5" >' . $lockout_message . '</textarea><br />';
			$content .= '<label for="itsec_global_lockout_message"> ' . __( 'The message to display when a computer (host) has been locked out.', 'ithemes-security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Lockout Period Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function lockout_period( $args ) {

			if ( isset( $this->settings['lockout_period'] ) ) {
				$lockout_period = absint( $this->settings['lockout_period'] );
			} else {
				$lockout_period = 15;
			}

			$content = '<input class="small-text" name="itsec_global[lockout_period]" id="itsec_global_lockout_period" value="' . $lockout_period . '" type="text">';
			$content .= '<label for="itsec_global_lockout_period"> ' . __( 'Minutes', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'The length of time a host or user will be banned from this site after hitting the limit of bad logins.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Lockout White List Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function lockout_white_list( $args ) {

			$white_list = '';

			//Convert and show the agent list
			if ( isset( $this->settings['lockout_white_list'] ) && is_array( $this->settings['lockout_white_list'] ) && sizeof( $this->settings['lockout_white_list'] ) >= 1 ) {

				$white_list = implode( PHP_EOL, $this->settings['lockout_white_list'] );

			} elseif ( isset( $this->settings['lockout_white_list'] ) && ! is_array( $this->settings['lockout_white_list'] ) && strlen( $this->settings['lockout_white_list'] ) > 1 ) {

				$white_list = $this->settings['lockout_white_list'];

			}

			$content = '<textarea id="itsec_global_lockout_white_list" name="itsec_global[lockout_white_list]" rows="10" cols="50">' . $white_list . '</textarea>';
			$content .= '<p>' . __( 'Use the guidelines below to enter hosts that will not be locked out from your site. This will keep you from locking yourself out of any features if you should trigger a lockout. Please note this does not override away mode and will only prevent a temporary ban. Should a permanent ban be triggered you will still be added to the "Ban Users" list unless the IP address is also white listed in that section.', 'ithemes-security' ) . '</p>';
			$content .= '<ul>';
			$content .= '<li>' . __( 'You may white list users by individual IP address or IP address range.', 'ithemes-security' ) . '</li>';
			$content .= '<li>' . __( 'Individual IP addesses must be in IPV4 standard format (i.e. ###.###.###.### or ###.###.###.###/##). Wildcards (*) or a netmask is allowed to specify a range of ip addresses.', 'ithemes-security' ) . '</li>';
			$content .= '<li>' . __( 'If using a wildcard (*) you must start with the right-most number in the ip field. For example ###.###.###.* and ###.###.*.* are permitted but ###.###.*.### is not.', 'ithemes-security' ) . '</li>';
			$content .= '<li><a href="http://ip-lookup.net/domain-lookup.php" target="_blank">' . __( 'Lookup IP Address.', 'ithemes-security' ) . '</a></li>';
			$content .= '<li>' . __( 'Enter only 1 IP address or 1 IP address range per line.', 'ithemes-security' ) . '</li>';
			$content .= '</ul>';

			echo $content;

		}

		/**
		 * echos Log Location Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function log_location( $args ) {

			global $itsec_globals;

			if ( isset( $this->settings['log_location'] ) ) {
				$log_location = sanitize_text_field( $this->settings['log_location'] );
			} else {
				$log_location = $itsec_globals['ithemes_log_dir'];
			}

			$content = '<input class="large-text" name="itsec_global[log_location]" id="itsec_global_log_location" value="' . $log_location . '" type="text">';
			$content .= '<label for="itsec_global_log_location"> ' . __( 'The path on your machine where log files should be stored.', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'This path must be writable by your website. For added security it is recommended you do not include it in your website root folder.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Log Rotation Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function log_rotation( $args ) {

			if ( isset( $this->settings['log_rotation'] ) ) {
				$log_rotation = absint( $this->settings['log_rotation'] );
			} else {
				$log_rotation = 30;
			}

			$content = '<input class="small-text" name="itsec_global[log_rotation]" id="itsec_global_log_rotation" value="' . $log_rotation . '" type="text">';
			$content .= '<label for="itsec_global_log_rotation"> ' . __( 'Days', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'The number of days database logs should be kept. File logs will be kept indefinitely but will be rotated once the file hits 10MB.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Log type Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function log_type( $args ) {

			if ( isset( $this->settings['log_type'] ) ) {
				$log_type = $this->settings['log_type'];
			} else {
				$log_type = 0;
			}

			echo '<select id="itsec_global_log_type" name="itsec_global[log_type]">';

			echo '<option value="0" ' . selected( $log_type, '0' ) . '>' . __( 'Database Only', 'ithemes-security' ) . '</option>';
			echo '<option value="1" ' . selected( $log_type, '1' ) . '>' . __( 'File Only', 'ithemes-security' ) . '</option>';
			echo '<option value="2" ' . selected( $log_type, '2' ) . '>' . __( 'Both', 'ithemes-security' ) . '</option>';
			echo '</select>';
			echo '<label for="itsec_global_log_type"> ' . __( 'How should event logs be kept', 'ithemes-security' ) . '</label>';
			echo '<p class="description">' . __( 'iThemes Security can log events in multiple ways. Each with its own advantages and disadvantages. Database Only puts all events in the database with your posts and other WordPress data. This makes it easy to retrieve and process in the plugin but can be slower especially if the database table gets very large. File Only is very fast but the plugin does not process the logs itself as that would take far more resources. Finally, you can log both if you so desire. For most users or smaller sites Database Only should be fine. If you have a very large site or a log processing software then File Only might be a better option. Of course you can also do both if you so desire.' ) . '</p>';

		}

		/**
		 * Render the settings metabox
		 *
		 * @return void
		 */
		public function metabox_advanced_settings() {

			//set appropriate action for multisite or standard site
			if ( is_multisite() ) {
				$action = 'edit.php?action=itsec_global';
			} else {
				$action = 'options.php';
			}

			printf( '<form name="%s" method="post" action="%s">', get_current_screen()->id, $action );

			$this->core->do_settings_sections( 'security_page_toplevel_page_itsec-global', false );

			echo '<p>' . PHP_EOL;

			settings_fields( 'security_page_toplevel_page_itsec-global' );

			echo '<input class="button-primary" name="submit" type="submit" value="' . __( 'Save Changes', 'ithemes-security' ) . '" />' . PHP_EOL;

			echo '</p>' . PHP_EOL;

			echo '</form>';

		}

		/**
		 * echos Admin User Username Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function notification_email( $args ) {

			if ( isset( $this->settings['notification_email'] ) && is_array( $this->settings['notification_email'] ) ) {
				$emails = implode( PHP_EOL, $this->settings['notification_email'] );
				$emails = sanitize_text_field( $emails );
			} else {
				$emails = '';
			}

			$content = '<textarea id="itsec_global_notification_email" name="itsec_global[notification_email]">' . $emails . '</textarea><br>';
			$content .= '<label for="itsec_global_notification_email"> ' . __( 'The email address(es) all security notifications will be sent to. One address per line.', 'ithemes-security' ) . '</label>';

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

			global $itsec_globals, $itsec_lib;

			if ( isset( $input['backup_email'] ) ) {

				$bad_emails = array();
				$emails     = explode( PHP_EOL, $input['backup_email'] );

				foreach ( $emails as $email ) {

					if ( is_email( trim( $email ) ) === false ) {
						$bad_emails[] = $email;
					}

				}

				if ( sizeof( $bad_emails ) > 0 ) {

					$bad_addresses = implode( ', ', $bad_emails );
					$type          = 'error';
					$message       = __( 'The following backup email address(es) do not appear to be valid: ', 'ithemes-security' ) . $bad_addresses;
				}

				$input['backup_email'] = $emails;
			}

			if ( isset( $input['notification_email'] ) ) {

				$bad_emails = array();
				$emails     = explode( PHP_EOL, $input['notification_email'] );

				foreach ( $emails as $email ) {

					if ( is_email( trim( $email ) ) === false ) {
						$bad_emails[] = $email;
					}

				}

				if ( sizeof( $bad_emails ) > 0 ) {

					$bad_addresses = implode( ', ', $bad_emails );
					$type          = 'error';
					$message       = __( 'The following notification email address(es) do not appear to be valid: ', 'ithemes-security' ) . $bad_addresses;
				}

				$input['notification_email'] = $emails;
			}

			$input['lockout_message']      = isset( $input['lockout_message'] ) ? sanitize_text_field( $input['lockout_message'] ) : '';
			$input['user_lockout_message'] = isset( $input['user_lockout_message'] ) ? sanitize_text_field( $input['user_lockout_message'] ) : '';
			$input['blacklist']            = ( isset( $input['blacklist'] ) && intval( $input['blacklist'] == 1 ) ? true : false );
			$input['blacklist_count']      = isset( $input['blacklist_count'] ) ? absint( $input['blacklist_count'] ) : 3;
			$input['blacklist_period']     = isset( $input['blacklist_period'] ) ? absint( $input['blacklist_period'] ) : 7;
			$input['email_notifications']  = ( isset( $input['email_notifications'] ) && intval( $input['email_notifications'] == 1 ) ? true : false );
			$input['lockout_period']       = isset( $input['lockout_period'] ) ? absint( $input['lockout_period'] ) : 15;
			$input['log_rotation']         = isset( $input['log_rotation'] ) ? absint( $input['log_rotation'] ) : 30;

			$input['log_location'] = isset( $input['log_location'] ) ? sanitize_text_field( $input['log_location'] ) : $itsec_globals['ithemes_log_dir'];

			//Process white list
			if ( isset( $input['lockout_white_list'] ) && ! is_array( $input['lockout_white_list'] ) ) {
				$white_listed_addresses = explode( PHP_EOL, $input['lockout_white_list'] );
			} else {
				$white_listed_addresses = array();
			}

			$bad_white_listed_ips = array();
			$raw_white_listed_ips = array();

			foreach ( $white_listed_addresses as $index => $address ) {

				if ( strlen( trim( $address ) ) > 0 ) {

					if ( $itsec_lib->validates_ip_address( $address ) === false ) {

						$bad_white_listed_ips[] = filter_var( $address, FILTER_SANITIZE_STRING );

					}

					$raw_white_listed_ips[] = filter_var( $address, FILTER_SANITIZE_STRING );

				} else {
					unset( $white_listed_addresses[$index] );
				}

			}

			$raw_white_listed_ips = array_unique( $raw_white_listed_ips );

			if ( sizeof( $bad_white_listed_ips ) > 0 ) {

				$type    = 'error';
				$message = '';

				$message .= sprintf( '%s<br /><br />', __( 'There is a problem with an IP address in the white list:', 'ithemes-security' ) );

				foreach ( $bad_white_listed_ips as $bad_ip ) {
					$message .= sprintf( '%s %s<br />', $bad_ip, __( 'is not a valid address in the white list users box.', 'ithemes-security' ) );
				}

			} else {

				$type    = 'updated';
				$message = __( 'Settings Updated', 'ithemes-security' );

			}

			$input['lockout_white_list'] = $raw_white_listed_ips;

			if ( $input['log_location'] != $itsec_globals['ithemes_log_dir'] ) {
				$good_path = $itsec_lib->validate_path( $input['log_location'] );
			} else {
				$good_path = true;
			}

			if ( $good_path !== true ) {

				$type              = 'error';
				$message           = __( 'The file path entered does not appear to be valid. Please ensure it exists and that WordPress can write to it. ', 'ithemes-security' );
				$input['log_type'] = 0;

			} else {
				$input['log_type'] = isset( $input['log_type'] ) ? intval( $input['log_type'] ) : 0;
			}

			add_settings_error( 'itsec_admin_notices', esc_attr( 'settings_updated' ), $message, $type );

			return $input;

		}

		/**
		 * Prepare and save options in network settings
		 *
		 * @return void
		 */
		public function save_network_options() {

			$settings['backup_email']   = isset( $_POST['itsec_global']['backup_email'] ) ? sanitize_text_field( $_POST['itsec_global']['backup_email'] ) : '';
			$settings['notification_email']   = isset( $_POST['itsec_global']['notification_email'] ) ? sanitize_text_field( $_POST['itsec_global']['notification_email'] ) : '';
			$settings['lockout_message']      = isset( $_POST['itsec_global']['lockout_message'] ) ? sanitize_text_field( $_POST['itsec_global']['lockout_message'] ) : __( 'error', 'ithemes-security' );
			$settings['user_lockout_message'] = isset( $_POST['itsec_global']['user_lockout_message'] ) ? sanitize_text_field( $_POST['itsec_global']['user_lockout_message'] ) : __( 'You have been locked out due to too many login attempts.', 'ithemes-security' );
			$settings['blacklist']            = ( isset( $_POST['itsec_global']['blacklist'] ) && intval( $_POST['itsec_global']['blacklist'] == 1 ) ? true : false );
			$settings['blacklist_count']      = isset( $_POST['itsec_global']['blacklist_count'] ) ? absint( $_POST['itsec_global']['blacklist_count'] ) : 3;
			$settings['blacklist_period']     = isset( $_POST['itsec_global']['blacklist_period'] ) ? absint( $_POST['itsec_global']['blacklist_period'] ) : 7;
			$settings['lockout_period']       = isset( $_POST['itsec_global']['lockout_period'] ) ? absint( $_POST['itsec_global']['lockout_period'] ) : 15;
			$settings['email_notifications']  = ( isset( $_POST['itsec_global']['email_notifications'] ) && intval( $_POST['itsec_global']['email_notifications'] == 1 ) ? true : false );
			$settings['log_rotation']         = isset( $_POST['itsec_global']['log_rotation'] ) ? absint( $_POST['itsec_global']['log_rotation'] ) : 30;
			$settings['log_type']             = isset( $_POST['itsec_global']['log_type'] ) ? intval( $_POST['itsec_global']['log_type'] ) : 0;
			$settings['log_location']         = sanitize_text_field( $_POST['itsec_global']['log_location'] );

			update_site_option( 'itsec_global', $settings ); //we must manually save network options

			//send them back to the away mode options page
			wp_redirect( add_query_arg( array( 'page' => 'toplevel_page_itsec-authentication', 'updated' => 'true' ), network_admin_url( 'admin.php' ) ) );
			exit();

		}

		/**
		 * echos Admin User Username Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function user_lockout_message( $args ) {

			if ( isset( $this->settings['user_lockout_message'] ) ) {
				$user_lockout_message = sanitize_text_field( $this->settings['user_lockout_message'] );
			} else {
				$user_lockout_message = __( 'You have been locked out due to too many login attempts.', 'ithemes-security' );
			}

			$content = '<textarea class="widefat" name="itsec_global[user_lockout_message]" id="itsec_global_user_lockout_message" rows="5" >' . $user_lockout_message . '</textarea><br />';
			$content .= '<label for="itsec_global_user_lockout_message"> ' . __( 'The message to display to a user when their account has been locked out.', 'ithemes-security' ) . '</label>';

			echo $content;

		}

		/**
		 * Start the System Tweaks Admin Module
		 *
		 * @param Ithemes_ITSEC_Core $core Instance of core plugin class
		 *
		 * @return ITSEC_Global_Settings                The instance of the ITSEC_Global_Settings class
		 */
		public static function start( $core ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}