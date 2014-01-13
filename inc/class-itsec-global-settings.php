<?php

if ( ! class_exists( 'ITSEC_Global_Settings' ) ) {

	class ITSEC_Global_Settings {

		private static $instance = NULL;

		private
			$settings,
			$core,
			$page;

		private function __construct( $core ) {

			global $itsec_globals;

			$this->core      = $core;
			$this->settings  = get_site_option( 'itsec_global' );

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

			$available_pages[] = add_submenu_page(
				'itsec',
				__( 'Global Settings', 'ithemes-security' ),
				__( 'Global Settings', 'ithemes-security' ),
				$itsec_globals['plugin_access_lvl'],
				$available_pages[0] . '-global',
				array( $this->core, 'render_page' )
			);

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
		 * Execute admin initializations
		 *
		 * @return void
		 */
		public function initialize_admin() {

			global $itsec_lib;

			//Add Settings sections
			add_settings_section(
				'global',
				__( 'Brute Force Protection', 'ithemes-security' ),
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

			//Register the settings field for the entire module
			register_setting(
				'security_page_toplevel_page_itsec-global',
				'itsec_global',
				array( $this, 'sanitize_module_input' )
			);

		}

		/**
		 * Empty callback function
		 */
		public function empty_callback_function() {}

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
				$emails = explode( PHP_EOL, $input['notification_email'] );

				foreach ( $emails as $email ) {

					if ( is_email( trim( $email ) ) === false ) {
						$bad_emails[] = $email;
					}

				}

				if ( sizeof( $bad_emails ) > 0 ) {

					$bad_addresses = implode( ', ', $bad_emails );
					$type = 'error';
					$message = __( 'The following email address(es) do not appear to be valid: ', 'ithemes-security' ) . $bad_addresses;
				}

				$input['notification_email'] = $emails;
			}

			add_settings_error(
				'itsec_admin_notices',
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

			$settings['strong_passwords-enabled'] = ( isset( $_POST['itsec_authentication']['strong_passwords-enabled'] ) && intval( $_POST['itsec_authentication']['strong_passwords-enabled'] == 1 ) ? true : false );

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

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}