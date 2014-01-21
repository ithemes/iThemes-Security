<?php

if ( ! class_exists( 'ITSEC_Intrusion_Detection_Admin' ) ) {

	class ITSEC_Intrusion_Detection_Admin {

		private static $instance = null;

		private
			$settings,
			$core,
			$page;

		private function __construct( $core ) {

			$this->core      = $core;
			$this->settings  = get_site_option( 'itsec_intrusion_detection' );

			add_action( 'itsec_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) ); //enqueue scripts for admin page
			add_filter( 'itsec_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( 'itsec_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu
			add_filter( 'itsec_add_dashboard_status', array( $this, 'dashboard_status' ) ); //add information for plugin status

			//manually save options on multisite
			if ( is_multisite() ) {
				add_action( 'network_admin_edit_itsec_intrusion_detection', array( $this, 'save_network_options' ) ); //save multisite options
			}

		}

		/**
		 * Add meta boxes to primary options pages
		 *
		 * @return void
		 */
		public function add_admin_meta_boxes() {

			add_meta_box(
				'intrusion_detection_description',
				__( 'Description', 'ithemes-security' ),
				array( $this, 'add_module_intro' ),
				'security_page_toplevel_page_itsec-intrusion_detection',
				'normal',
				'core'
			);

			add_meta_box(
				'intrusion_detection_options',
				__( 'Configure Intrusion Detection', 'ithemes-security' ),
				array( $this, 'metabox_advanced_settings' ),
				'security_page_toplevel_page_itsec-intrusion_detection',
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

			$tabs[$this->page] = __( 'Detect', 'ithemes-security' );

			return $tabs;

		}

		/**
		 * Build and echo the away mode description
		 *
		 * @return void
		 */
		public function add_module_intro( $screen ) {

			$content = '<p>' . __( 'The following settings help protect your site by detecting changes and other attempts to compromise the files in your WordPress system.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * Add Files Admin Javascript
		 *
		 * @return void
		 */
		public function admin_script() {

			global $itsec_globals;

			if ( strpos( get_current_screen()->id, 'security_page_toplevel_page_itsec-intrusion_detection' ) !== false ) {

				wp_enqueue_script( 'itsec_intrusion_detection_js', $itsec_globals['plugin_url'] . 'modules/intrusion-detection/js/admin-intrusion-detection.js', 'jquery', $itsec_globals['plugin_build'] );

			}

		}

		/**
		 * Register subpage for Away Mode
		 *
		 * @param array $available_pages array of ITSEC settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $itsec_globals;

			$this->page = $available_pages[0] . '-intrusion_detection';

			$available_pages[] = add_submenu_page(
				'itsec',
				__( 'Intrusion Detection', 'ithemes-security' ),
				__( 'Intrusion Detection', 'ithemes-security' ),
				$itsec_globals['plugin_access_lvl'],
				$available_pages[0] . '-intrusion_detection',
				array( $this->core, 'render_page' )
			);

			return $available_pages;

		}

		/**
		 * Sets the status in the plugin dashboard
		 *
		 * @return void
		 */
		public function dashboard_status( $statuses ) {
		}

		/**
		 * Empty callback function
		 */
		public function empty_callback_function() {
		}

		/**
		 * echos Enable 404 Detection Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function four_oh_four_enabled( $args ) {

			if ( isset( $this->settings['four_oh_four-enabled'] ) && $this->settings['four_oh_four-enabled'] === true ) {
				$enabled = 1;
			} else {
				$enabled = 0;
			}

			$content = '<input type="checkbox" id="itsec_intrusion_detection_four_oh_four_enabled" name="itsec_intrusion_detection[four_oh_four-enabled]" value="1" ' . checked( 1, $enabled, false ) . '/>';
			$content .= '<label for="itsec_intrusion_detection_four_oh_four_enabled"> ' . __( 'Enable 404 detection.', 'ithemes-security' ) . '</label>';

			echo $content;

		}

		/**
		 * Echo the 404 Detection Header
		 */
		public function four_oh_four_header() {

			$content = '<h2 class="settings-section-header">' . __( '404 Detection', 'ithemes-security' ) . '</h2>';
			$content .= '<p>' . __( '404 detection looks at a user who is hitting a large number of non-existent pages, that is they are getting a large number of 404 errors. It assumes that a user who hits a lot of 404 errors in a short period of time is scanning for something (presumably a vulnerability) and locks them out accordingly (you can set the thresholds for this below). This also gives the added benefit of helping you find hidden problems causing 404 errors on unseen parts of your site as all errors will be logged in the "View Logs" page. You can set threshholds for this feature below.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * Execute admin initializations
		 *
		 * @return void
		 */
		public function initialize_admin() {

			//Add Settings sections
			add_settings_section(
				'intrusion_detection_four_oh_four-enabled',
				__( 'Enable 404 Detection', 'ithemes-security' ),
				array( $this, 'four_oh_four_header' ),
				'security_page_toplevel_page_itsec-intrusion_detection'
			);

			add_settings_section(
				'intrusion_detection_four_oh_four-settings',
				__( '404 Detection Settings', 'ithemes-security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_itsec-intrusion_detection'
			);

			//404 Detection Fields
			add_settings_field(
				'itsec_intrusion_detection[four_oh_four-enabled]',
				__( '404 Detection', 'ithemes-security' ),
				array( $this, 'four_oh_four_enabled' ),
				'security_page_toplevel_page_itsec-intrusion_detection',
				'intrusion_detection_four_oh_four-enabled'
			);

			//Register the settings field for the entire module
			register_setting(
				'security_page_toplevel_page_itsec-intrusion_detection',
				'itsec_intrusion_detection',
				array( $this, 'sanitize_module_input' )
			);

		}

		/**
		 * Render the settings metabox
		 *
		 * @return void
		 */
		public function metabox_advanced_settings() {

			//set appropriate action for multisite or standard site
			if ( is_multisite() ) {
				$action = 'edit.php?action=itsec_intrusion_detection';
			} else {
				$action = 'options.php';
			}

			printf( '<form name="%s" method="post" action="%s">', get_current_screen()->id, $action );

			$this->core->do_settings_sections( 'security_page_toplevel_page_itsec-intrusion_detection', false );

			echo '<p>' . PHP_EOL;

			settings_fields( 'security_page_toplevel_page_itsec-intrusion_detection' );

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

			//process brute force settings
			$input['four_oh_four-enabled']           = ( isset( $input['four_oh_four-enabled'] ) && intval( $input['four_oh_four-enabled'] == 1 ) ? true : false );

			add_settings_error( 'itsec_admin_notices', esc_attr( 'settings_updated' ), $message, $type );

			return $input;

		}

		/**
		 * Prepare and save options in network settings
		 *
		 * @return void
		 */
		public function save_network_options() {

			$settings['four_oh_four-enabled']           = ( isset( $_POST['itsec_intrusion_detection']['four_oh_four-enabled'] ) && intval( $_POST['itsec_intrusion_detection']['four_oh_four-enabled'] == 1 ) ? true : false );

		}

		/**
		 * Start the Intrusion Detection Admin Module
		 *
		 * @param Ithemes_ITSEC_Core   $core   Instance of core plugin class
		 *
		 * @return ITSEC_Intrusion_Detection_Admin                The instance of the ITSEC_Intrusion_Detection_Admin class
		 */
		public static function start( $core ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}