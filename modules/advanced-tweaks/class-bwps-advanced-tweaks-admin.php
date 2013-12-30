<?php

if ( ! class_exists( 'BWPS_Advanced_Tweaks_Admin' ) ) {

	class BWPS_Advanced_Tweaks_Admin {

		private static $instance = NULL;

		private
			$settings,
			$core,
			$page;

		private function __construct( $core ) {

			$this->core     = $core;
			$this->settings = get_site_option( 'bwps_advanced_tweaks' );

			add_action( 'bwps_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( 'bwps_page_top', array( $this, 'add_module_intro' ) ); //add page intro and information
			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) ); //enqueue scripts for admin page
			//add_filter( 'bwps_wp_config_rules', array( $this, 'wp_config_rule' ) ); //build wp_config.php rules
			add_filter( 'bwps_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( 'bwps_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu
			add_filter( 'bwps_add_dashboard_status', array( $this, 'dashboard_status' ) ); //add information for plugin status

			//manually save options on multisite
			if ( is_multisite() ) {
				add_action( 'network_admin_edit_bwps_advanced_tweaks', array( $this, 'save_network_options' ) ); //save multisite options
			}

		}

		/**
		 * Register subpage for Away Mode
		 *
		 * @param array $available_pages array of BWPS settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $bwps_globals;

			$this->page = $available_pages[0] . '-advanced_tweaks';

			$available_pages[] = add_submenu_page(
				'bwps',
				__( 'Advanced Tweaks', 'better_wp_security' ),
				__( 'Advanced Tweaks', 'better_wp_security' ),
				$bwps_globals['plugin_access_lvl'],
				$available_pages[0] . '-advanced_tweaks',
				array( $this->core, 'render_page' )
			);

			return $available_pages;

		}

		public function add_admin_tab( $tabs ) {

			$tabs[$this->page] = __( 'Tweaks', 'better_wp_security' );

			return $tabs;

		}

		/**
		 * Add meta boxes to primary options pages
		 *
		 * @param array $available_pages array of available page_hooks
		 */
		public function add_admin_meta_boxes( $available_pages ) {

			add_meta_box(
				'advanced_tweaks_options',
				__( 'Configure Advanced Security Tweaks', 'better_wp_security' ),
				array( $this, 'metabox_advanced_settings' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
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

			if ( strpos( get_current_screen()->id, 'security_page_toplevel_page_bwps-advanced_tweaks' ) !== false ) {

				wp_enqueue_script( 'bwps_advanced_tweaks_js', $bwps_globals['plugin_url'] . 'modules/advanced-tweaks/js/admin-advanced-tweaks.js', 'jquery', $bwps_globals['plugin_build'] );

			}

		}

		/**
		 * Sets the status in the plugin dashboard
		 *
		 * @return void
		 */
		public function dashboard_status( $statuses ) {

			$link = 'admin.php?page=toplevel_page_bwps-advanced_tweaks';

			if ( $this->settings['enabled'] === 1 ) {

				$status_array = 'safe-low';
				$status       = array(
					'text' => __( 'You are blocking known bad hosts and agents with the ban users tool.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status       = array(
					'text' => __( 'You are not blocking any users that are known to be a problem. Consider turning on the Ban Users feature.', 'better_wp_security' ),
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

			//Enabled Advanced Tweaks
			add_settings_section(
				'advanced_tweaks_enabled',
				__( 'Enable Advanced Tweaks', 'better_wp_security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_bwps-advanced_tweaks'
			);

			//server settings section
			add_settings_section(
				'advanced_tweaks_server',
				__( 'Configure Server Tweaks', 'better_wp_security' ),
				array( $this, 'server_tweaks_intro' ),
				'security_page_toplevel_page_bwps-advanced_tweaks'
			);

			//WordPress settings section
			add_settings_section(
				'advanced_tweaks_wordpress',
				__( 'Configure WordPress Tweaks', 'better_wp_security' ),
				array( $this, 'wordpress_tweaks_intro' ),
				'security_page_toplevel_page_bwps-advanced_tweaks'
			);

			//enabled field
			add_settings_field(
				'bwps_advanced_tweaks[enabled]',
				__( 'Enable Advanced Security Tweaks', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_enabled' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_enabled'
			);

			//protect files field
			add_settings_field(
				'bwps_advanced_tweaks[protect_files]',
				__( 'Protect System Files', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_server_protect_files' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_server'
			);

			//protect files field
			add_settings_field(
				'bwps_advanced_tweaks[disable_directory_browsing]',
				__( 'Disable Directory Browsing', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_server_disable_directory_browsing' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_server'
			);

			//Register the settings field for the entire module
			register_setting(
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'bwps_advanced_tweaks',
				array( $this, 'sanitize_module_input' )
			);

		}

		/**
		 * Empty callback function
		 */
		public function empty_callback_function() {}

		public function server_tweaks_intro() {
			echo '<h2 class="settings-section-header">' . __( 'Server Tweaks', 'better-wp-security' ) . '</h2>';
		}

		public function wordpress_tweaks_intro() {
			echo '<h2 class="settings-section-header">' . __( 'WordPress Tweaks', 'better-wp-security' ) . '</h2>';
		}

		/**
		 * echos Enabled Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_enabled( $args ) {

			if ( isset( $this->settings['enabled'] ) && $this->settings['enabled'] === 1 ) {
				$enabled = 1;
			} else {
				$enabled = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_enabled" name="bwps_advanced_tweaks[enabled]" value="1" ' . checked( 1, $enabled, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_enabled"> ' . __( 'Check this box to enable advanced security tweaks. Remember, some of these tweaks might conflict with other plugins or your theme so test your site after enabling each setting.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Protect Files Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_server_protect_files( $args ) {

			if ( isset( $this->settings['protect_files'] ) && $this->settings['protect_files'] === 1 ) {
				$protect_files = 1;
			} else {
				$protect_files = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_protect_files" name="bwps_advanced_tweaks[protect_files]" value="1" ' . checked( 1, $protect_files, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_protect_files"> ' . __( 'Prevent public access to readme.html, readme.txt, wp-config.php, install.php, wp-includes, and .htaccess. These files can give away important information on your site and serve no purpose to the public once WordPress has been successfully installed.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Disable Directory Browsing Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_server_disable_directory_browsing( $args ) {

			if ( isset( $this->settings['disable_directory_browsing'] ) && $this->settings['disable_directory_browsing'] === 1 ) {
				$disable_directory_browsing = 1;
			} else {
				$disable_directory_browsing = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_disable_directory_browsing" name="bwps_advanced_tweaks[disable_directory_browsing]" value="1" ' . checked( 1, $disable_directory_browsing, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_protect_files"> ' . __( 'Prevents users from seeing a list of files in a directory when no index file is present.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * Build and echo the away mode description
		 *
		 * @return void
		 */
		public function add_module_intro( $screen ) {

			if ( $screen === 'security_page_toplevel_page_bwps-advanced_tweaks' ) { //only display on away mode page

				$content = '<p>' . __( 'These are advanced settings that may be utilized to further strengthen the security of your WordPress site. The reason we list them as advanced though is that each fix, while blocking common forms of attack against your site, can also block legitimate plugins and themes that rely on the same techniques. When turning on the settings below we recommend you enable them 1 by 1 and test your site in between to make sure everything is working as expected.', 'better_wp_security' ) . '</p>';

				echo $content;

			}

		}

		/**
		 * Render the settings metabox
		 *
		 * @return void
		 */
		public function metabox_advanced_settings() {

			//set appropriate action for multisite or standard site
			if ( is_multisite() ) {
				$action = 'edit.php?action=bwps_advanced_tweaks';
			} else {
				$action = 'options.php';
			}

			printf( '<form name="%s" method="post" action="%s">', get_current_screen()->id, $action );

			$this->core->do_settings_sections( 'security_page_toplevel_page_bwps-advanced_tweaks', false );

			echo '<p>' . PHP_EOL;

			settings_fields( 'security_page_toplevel_page_bwps-advanced_tweaks' );

			echo '<input class="button-primary" name="submit" type="submit" value="' . __( 'Save Changes', 'better_wp_security' ) . '" />' . PHP_EOL;

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
			$message = __( 'Settings Updated', 'better_wp_security' );

			$input['enabled'] = intval( $input['enabled'] == 1 ? 1 : 0 );
			$input['protect_files'] = intval( $input['protect_files'] == 1 ? 1 : 0 );
			$input['disable_directory_browsing'] = intval( $input['disable_directory_browsing'] == 1 ? 1 : 0 );

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

			if ( isset( $_POST['bwps_advanced_tweaks']['enabled'] ) ) {
				$settings['enabled'] = intval( $_POST['bwps_advanced_tweaks']['enabled'] == 1 ? 1 : 0 );
			}

			if ( isset( $_POST['bwps_advanced_tweaks']['protect_files'] ) ) {
				$settings['protect_files'] = intval( $_POST['bwps_advanced_tweaks']['protect_files'] == 1 ? 1 : 0 );
			}

			if ( isset( $_POST['bwps_advanced_tweaks']['disable_directory_browsing'] ) ) {
				$settings['disable_directory_browsing'] = intval( $_POST['bwps_advanced_tweaks']['disable_directory_browsing'] == 1 ? 1 : 0 );
			}

			update_site_option( 'bwps_advanced_tweaks', $settings ); //we must manually save network options

			//send them back to the away mode options page
			wp_redirect( add_query_arg( array( 'page' => 'toplevel_page_bwps-advanced_tweaks', 'updated' => 'true' ), network_admin_url( 'admin.php' ) ) );
			exit();

		}

		/**
		 * Start the System Tweaks Admin Module
		 *
		 * @param  Ithemes_BWPS_Core $core Instance of core plugin class
		 *
		 * @return BWPS_Advanced_Tweaks_Admin                The instance of the BWPS_Advanced_Tweaks_Admin class
		 */
		public static function start( $core ) {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}