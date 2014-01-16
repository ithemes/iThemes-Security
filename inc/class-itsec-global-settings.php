<?php

if ( ! class_exists( 'ITSEC_Global_Settings' ) ) {

	class ITSEC_Global_Settings {

		private static $instance = null;

		private $settings, $core, $page;

		private function __construct( $core ) {

			global $itsec_globals;

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
		 * Register subpage for Away Mode
		 *
		 * @param array $available_pages array of ITSEC settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $itsec_globals;

			$this->page = $available_pages[0] . '-global';

			$available_pages[] = add_submenu_page( 'itsec', __( 'Global Settings', 'ithemes-security' ), __( 'Global Settings', 'ithemes-security' ), $itsec_globals['plugin_access_lvl'], $available_pages[0] . '-global', array( $this->core, 'render_page' ) );

			return $available_pages;

		}

		public function add_admin_tab( $tabs ) {

			$tabs[$this->page] = __( 'Global', 'ithemes-security' );

			return $tabs;

		}

		/**
		 * Add meta boxes to primary options pages
		 *
		 * @param array $available_pages array of available page_hooks
		 */
		public function add_admin_meta_boxes( $available_pages ) {

			add_meta_box( 'global_description', __( 'Description', 'ithemes-security' ), array( $this, 'add_module_intro' ), 'security_page_toplevel_page_itsec-global', 'normal', 'core' );

			add_meta_box( 'global_options', __( 'Configure Global Settings', 'ithemes-security' ), array( $this, 'metabox_advanced_settings' ), 'security_page_toplevel_page_itsec-global', 'advanced', 'core' );

		}

		/**
		 * Execute admin initializations
		 *
		 * @return void
		 */
		public function initialize_admin() {

			global $itsec_lib;

			//Add Settings sections
			add_settings_section( 'global', __( 'Global Settings', 'ithemes-security' ), array( $this, 'empty_callback_function' ), 'security_page_toplevel_page_itsec-global' );

			//Settings Fields
			add_settings_field( 'itsec_global[notification_email]', __( 'Notification Email', 'ithemes-security' ), array( $this, 'notification_email' ), 'security_page_toplevel_page_itsec-global', 'global' );

			add_settings_field( 'itsec_global[lockout_message]', __( 'Lockout Message', 'ithemes-security' ), array( $this, 'lockout_message' ), 'security_page_toplevel_page_itsec-global', 'global' );

			add_settings_field( 'itsec_authentication[blacklist]', __( 'Blacklist Repeat Offender', 'ithemes-security' ), array( $this, 'blacklist' ), 'security_page_toplevel_page_itsec-global', 'global' );

			add_settings_field( 'itsec_authentication[blacklist_count]', __( 'Blacklist Threshold', 'ithemes-security' ), array( $this, 'blacklist_count' ), 'security_page_toplevel_page_itsec-global', 'global' );

			add_settings_field( 'itsec_authentication[blacklist_period]', __( 'Blacklist Lookback Period', 'ithemes-security' ), array( $this, 'blacklist_period' ), 'security_page_toplevel_page_itsec-global', 'global' );

			add_settings_field( 'itsec_authentication[lockout_period]', __( 'Lockout Period', 'ithemes-security' ), array( $this, 'lockout_period' ), 'security_page_toplevel_page_itsec-global', 'global' );

			add_settings_field( 'itsec_authentication[email_notifications]', __( 'Email Lockout Notifications', 'ithemes-security' ), array( $this, 'email_notifications' ), 'security_page_toplevel_page_itsec-global', 'global' );

			//Register the settings field for the entire module
			register_setting( 'security_page_toplevel_page_itsec-global', 'itsec_global', array( $this, 'sanitize_module_input' ) );

		}

		/**
		 * Empty callback function
		 */
		public function empty_callback_function() {
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

			$content = '<textarea name="itsec_global[notification_email]" id="itsec_global_notification_email" rows="5" >' . $emails . '</textarea><br />';
			$content .= '<label for="itsec_global_notification_email"> ' . __( 'The email address all security notifications will be sent to.', 'ithemes-security' ) . '</label>';

			echo $content;

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

			$content = '<textarea name="itsec_global[lockout_message]" id="itsec_global_lockout_message" rows="5" >' . $lockout_message . '</textarea><br />';
			$content .= '<label for="itsec_global_lockout_message"> ' . __( 'The message to display to a user when they have been locked out.', 'ithemes-security' ) . '</label>';

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

			$content = '<input type="checkbox" id="itsec_authentication_blacklist" name="itsec_authentication[blacklist]" value="1" ' . checked( 1, $blacklist, false ) . '/>';
			$content .= '<label for="itsec_authentication_blacklist"> ' . __( 'If this box is checked the IP address of the offending computer will be added to the "Ban Users" blacklist after reaching the number of lockouts listed below.', 'ithemes-security' ) . '</label>';

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

			$content = '<input name="itsec_authentication[blacklist_count]" id="itsec_authentication_blacklist_count" value="' . $blacklist_count . '" type="text"> ' . __( 'lockouts', 'ithemes-security' ) . '<br />';
			$content .= '<label for="itsec_authentication_blacklist_count"> ' . __( 'The number of lockouts per IP before the host is banned permanently from this site.', 'ithemes-security' ) . '</label>';

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

			$content = '<input name="itsec_authentication[blacklist_period]" id="itsec_authentication_blacklist_period" value="' . $blacklist_period . '" type="text"> ' . __( 'days', 'ithemes-security' ) . '<br />';
			$content .= '<label for="itsec_authentication_blacklist_period"> ' . __( 'How many days should a lockout be remembered to meet the blacklist count above.', 'ithemes-security' ) . '</label>';

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

			$content = '<input name="itsec_authentication[lockout_period]" id="itsec_authentication_lockout_period" value="' . $lockout_period . '" type="text"> ' . __( 'minutes', 'ithemes-security' ) . '<br />';
			$content .= '<label for="itsec_authentication_lockout_period"> ' . __( 'The length of time a host or user will be banned from this site after hitting the limit of bad logins.', 'ithemes-security' ) . '</label>';

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

			$content = '<input type="checkbox" id="itsec_authentication_email_notifications" name="itsec_authentication[email_notifications]" value="1" ' . checked( 1, $email_notifications, false ) . '/>';
			$content .= sprintf( '<label for="itsec_authentication_email_notifications">%s<a href="admin.php?page=toplevel_page_itsec-global">%s</a>%s</label>', __( 'Enabling this feature will trigger an email to be sent to the ', 'ithemes-security' ), __( 'notifications email address', 'ithemes-security' ), __( ' whenever a host or user is locked out of the system.', 'ithemes-security' ) );

			echo $content;

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
		 * Sanitize and validate input
		 *
		 * @param  Array $input array of input fields
		 *
		 * @return Array         Sanitized array
		 */
		public function sanitize_module_input( $input ) {

			$type    = 'updated';
			$message = __( 'Settings Updated', 'ithemes-security' );

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
					$message       = __( 'The following email address(es) do not appear to be valid: ', 'ithemes-security' ) . $bad_addresses;
				}

				$input['notification_email'] = $emails;
			}

			$input['lockout_message']     = isset( $input['lockout_message'] ) ? sanitize_text_field( $input['lockout_message'] ) : '';
			$input['blacklist']           = ( isset( $input['blacklist'] ) && intval( $input['blacklist'] == 1 ) ? true : false );
			$input['blacklist_count']     = isset( $input['blacklist_count'] ) ? absint( $input['blacklist_count'] ) : 3;
			$input['blacklist_period']    = isset( $input['blacklist_period'] ) ? absint( $input['blacklist_period'] ) : 7;
			$input['email_notifications'] = ( isset( $input['email_notifications'] ) && intval( $input['email_notifications'] == 1 ) ? true : false );
			$input['lockout_period']      = isset( $input['lockout_period'] ) ? absint( $input['lockout_period'] ) : 15;

			add_settings_error( 'itsec_admin_notices', esc_attr( 'settings_updated' ), $message, $type );

			return $input;

		}

		/**
		 * Prepare and save options in network settings
		 *
		 * @return void
		 */
		public function save_network_options() {

			$settings['notification_email']  = isset( $_POST['itsec_authentication']['notification_email'] ) ? sanitize_text_field( $_POST['itsec_authentication']['notification_email'] ) : '';
			$settings['lockout_message']     = isset( $_POST['itsec_authentication']['lockout_message'] ) ? sanitize_text_field( $_POST['itsec_authentication']['lockout_message'] ) : '';
			$settings['blacklist']           = ( isset( $_POST['itsec_authentication']['blacklist'] ) && intval( $_POST['itsec_authentication']['blacklist'] == 1 ) ? true : false );
			$settings['blacklist_count']     = isset( $_POST['itsec_authentication']['blacklist_count'] ) ? absint( $_POST['itsec_authentication']['blacklist_count'] ) : 3;
			$settings['blacklist_period']    = isset( $_POST['itsec_authentication']['blacklist_period'] ) ? absint( $_POST['itsec_authentication']['blacklist_period'] ) : 7;
			$settings['lockout_period']      = isset( $_POST['itsec_authentication']['lockout_period'] ) ? absint( $_POST['itsec_authentication']['lockout_period'] ) : 15;
			$settings['email_notifications'] = ( isset( $_POST['itsec_authentication']['email_notifications'] ) && intval( $_POST['itsec_authentication']['email_notifications'] == 1 ) ? true : false );

			update_site_option( 'itsec_authentication', $settings ); //we must manually save network options

			//send them back to the away mode options page
			wp_redirect( add_query_arg( array( 'page' => 'toplevel_page_itsec-authentication', 'updated' => 'true' ), network_admin_url( 'admin.php' ) ) );
			exit();

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