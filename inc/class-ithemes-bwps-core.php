<?php
/**
 * Core functionality primarily for adding consistent dashboard functionality
 *
 * @version 1.0
 */

if ( ! class_exists( 'Ithemes_BWPS_Core' ) ) {

	final class Ithemes_BWPS_Core {

		private static $instance = NULL; //instantiated instance of this plugin

		public
			$admin_tabs,
			$page_hooks,
			$plugin;

		/**
		 * Loads core functionality across both admin and frontend.
		 *
		 * @param Ithemes_BWPS $plugin
		 *
		 * @return void
		 */
		private function __construct( $plugin ) {

			global $bwps_globals;

			@ini_set( 'auto_detect_line_endings', true ); //Make certain we're using proper line endings

			$this->plugin = $plugin; //Allow us to access plugin defaults throughout

			//load the text domain
			load_plugin_textdomain( 'better_wp_security', false, $bwps_globals['plugin_dir'] . 'languages' );

			//require plugin setup information
			require_once( $bwps_globals['plugin_dir'] . 'inc/class-ithemes-bwps-setup.php' );
			register_activation_hook( $bwps_globals['plugin_file'], array( 'Ithemes_BWPS_Setup', 'on_activate' ) );
			register_deactivation_hook( $bwps_globals['plugin_file'], array( 'Ithemes_BWPS_Setup', 'on_deactivate' ) );
			register_uninstall_hook( $bwps_globals['plugin_file'], array( 'Ithemes_BWPS_Setup', 'on_uninstall' ) );

			//Determine if we need to run upgrade scripts
			$plugin_data = get_option( $bwps_globals['plugin_hook'] . '_data' );

			if ( $plugin_data !== false ) { //if plugin data does exist

				//see if the saved build version is older than the current build version
				if ( isset( $plugin_data['build'] ) && $plugin_data['build'] !== $bwps_globals['plugin_build'] ) {
					Ithemes_BWPS_Setup::on_activate( $plugin_data['build'] ); //run upgrade scripts
				}

			}

			//save plugin information
			add_action( $bwps_globals['plugin_hook'] . '_set_plugin_data', array( $this, 'save_plugin_data' ) );

		}

		/**
		 * Displays plugin admin notices
		 *
		 * @return  void
		 */
		public function admin_notices() {

			settings_errors( 'bwps_admin_notices' );

		}

		/**
		 * Enque actions to build the admin pages
		 *
		 * @return void
		 */
		public function build_admin() {

			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			add_action( 'admin_init', array( $this, 'execute_admin_init' ) );

			if ( is_multisite() ) { //must be network admin in multisite
				add_action( 'network_admin_menu', array( $this, 'setup_primary_admin' ) );
			} else {
				add_action( 'admin_menu', array( $this, 'setup_primary_admin' ) );
			}

		}

		/**
		 * Registers admin styles and handles other items required at admin_init
		 *
		 * @return void
		 */
		public function execute_admin_init() {

			global $bwps_globals;

			wp_register_style( 'bwps_admin_styles', $bwps_globals['plugin_url'] . 'inc/css/ithemes.css' );
			do_action( $bwps_globals['plugin_hook'] . 'admin_init' ); //execute modules init scripts

			//Load the wp-config writer class in case we need it.
			require_once( $bwps_globals['plugin_dir'] . 'inc/class-ithemes-bwps-wpconfig.php' );

			//display any applicable error messages

		}

		public function admin_tabs( $current = NULL ) {

			global $bwps_globals;

			if ( $current == NULL ) {
				$current = $bwps_globals['plugin_hook'];
			}

			echo '<div id="icon-themes" class="icon32"><br></div>';
			echo '<h2 class="nav-tab-wrapper">';

			foreach ( $this->admin_tabs as $location => $tabname ) {

				if ( is_array( $tabname ) ) {

					$class = ( $location == $current ) ? ' nav-tab-active' : '';
					echo '<a class="nav-tab' . $class . '" href="?page=' . $tabname[1] . '&tab=' . $location . '">' . $tabname[0] . '</a>';

				} else {

					$class = ( $location == $current ) ? ' nav-tab-active' : '';
					echo '<a class="nav-tab' . $class . '" href="?page=' . $location . '">' . $tabname . '</a>';

				}

			}

			echo '</h2>';

		}

		/**
		 * Enqueues the styles for the admin area so WordPress can load them
		 *
		 * @return void
		 */
		public function enqueue_admin_styles() {

			global $bwps_globals;

			wp_enqueue_style( 'bwps_admin_styles' );
			do_action( $bwps_globals['plugin_url'] . 'enqueue_admin_styles' );

		}

		/**
		 * Handles the building of admin menus and calls required functions to render admin pages
		 *
		 * @return void
		 */
		public function setup_primary_admin() {

			global $bwps_globals;

			//If the plugin admin screen will only appear under options we'll add an options page
			if ( $this->plugin->top_level_menu === false ) {

				$this->page_hooks[] = add_options_page(
					$bwps_globals['plugin_name'],
					$this->plugin->menu_name,
					$bwps_globals['plugin_access_lvl'],
					$bwps_globals['plugin_hook'],
					array( $this, 'render_page' )
				);

			} else { //this plugin wants a top-level admin section

				$this->admin_tabs['bwps'] = __( 'Dashboard', 'better_wp_security' ); //set a tab for the dashboard

				//Set default dashboard title to "Dashboard"
				if ( ! isset( $this->plugin->dashboard_page_name ) || $this->plugin->dashboard_page_name === '' ) {
					$dashboard_page_name = __( 'Dashboard', 'better_wp_security' );
				} else {
					$dashboard_page_name = $this->plugin->dashboard_page_name;
				}

				//Set default menu title to "Security"
				if ( ! isset( $this->plugin->menu_name ) || $this->plugin->menu_name === '' ) {
					$menu_name = __( 'Security', 'better_wp_security' );
				} else {
					$menu_name = $this->plugin->menu_name;
				}

				//Set default menu icon to an empty string
				if ( ! isset( $this->plugin->menu_icon ) || $this->plugin->menu_icon === '' ) {
					$menu_icon = '';
				} else {
					$menu_icon = $this->plugin->menu_icon;
				}

				$this->page_hooks[] = add_menu_page(
					$dashboard_page_name,
					$menu_name,
					$bwps_globals['plugin_access_lvl'],
					$bwps_globals['plugin_hook'],
					array( $this, 'render_page' ),
					$menu_icon
				);

				if ( $this->plugin->settings_page === true ) {

					if ( ! isset( $this->plugin->settings_page_name ) || $this->plugin->settings_page_name === '' ) {
						$settings_page_name = __( 'Settings', 'better_wp_security' );
					} else {
						$settings_page_name = $this->plugin->settings_page_name;
					}

					if ( ! isset( $this->plugin->settings_menu_title ) || $this->plugin->settings_menu_title === '' ) {
						$settings_menu_title = __( 'Settings', 'better_wp_security' );
					} else {
						$settings_menu_title = $this->plugin->settings_menu_title;
					}

					$this->page_hooks[] = add_submenu_page(
						$bwps_globals['plugin_hook'],
						$settings_page_name,
						$settings_menu_title,
						$bwps_globals['plugin_access_lvl'],
						$this->page_hooks[0] . '-settings',
						array( $this, 'render_page' )
					);

				}

				$this->page_hooks = apply_filters( $bwps_globals['plugin_hook'] . '_add_admin_sub_pages', $this->page_hooks );

				$this->admin_tabs = apply_filters( $bwps_globals['plugin_hook'] . '_add_admin_tabs', $this->admin_tabs );

				//Make the dashboard is named correctly
				global $submenu;

				if ( isset( $this->plugin->dashboard_menu_title ) && $this->plugin->dashboard_menu_title !== '' ) {
					$dashboard_menu = $this->plugin->dashboard_menu_title;
				} else {
					$dashboard_menu = __( 'Dashboard', 'better_wp_security' );
				}

				if ( isset( $submenu[$bwps_globals['plugin_hook']] ) ) {
					$submenu[$bwps_globals['plugin_hook']][0][0] = $this->plugin->dashboard_menu_title;
				}

			}

			foreach ( $this->page_hooks as $page_hook ) {

				add_action( 'load-' . $page_hook, array( $this, 'page_actions' ) ); //Load page structure
				add_action( 'admin_footer-' . $page_hook, array( $this, 'admin_footer_scripts' ) ); //Load postbox startup script to footer
				add_action( 'admin_print_styles-' . $page_hook, array( $this, 'enqueue_admin_styles' ) ); //Load admin styles

			}

		}

		/**
		 * Enqueue JavaScripts for admin page rendering amd execute calls to add further meta_boxes
		 *
		 * @return void
		 */
		public function page_actions() {

			global $bwps_globals;

			do_action( $bwps_globals['plugin_hook'] . '_add_admin_meta_boxes', $this->page_hooks );

			//Set two columns for all plugins using this framework
			add_screen_option( 'layout_columns', array( 'max' => 2, 'default' => 2 ) );

			//Enqueue common scripts and try to keep it simple
			wp_enqueue_script( 'common' );
			wp_enqueue_script( 'wp-lists' );
			wp_enqueue_script( 'postbox' );

		}

		/**
		 * Prints the jQuery script to initiliase the metaboxes
		 * Called on admin_footer-*
		 *
		 * @return void
		 */
		function admin_footer_scripts() {

			?>

			<script type="text/javascript">postboxes.add_postbox_toggles( pagenow );</script>

		<?php

		}

		/**
		 * Render basic structure of the settings page
		 *
		 * @return void
		 */
		public function render_page() {

			global $bwps_globals;

			if ( is_multisite() ) {
				$screen = substr( get_current_screen()->id, 0, strpos( get_current_screen()->id, '-network' ) );
			} else {
				$screen = get_current_screen()->id; //the current screen id
			}

			?>

			<div class="wrap">

				<?php if ( isset( $this->plugin->top_level_menu ) && $this->plugin->top_level_menu === true ) { ?>
					<h2><?php echo $bwps_globals['plugin_name'] . ' - ' . get_admin_page_title(); ?></h2>
				<?php } else { ?>
					<h2><?php echo get_admin_page_title(); ?></h2>
				<?php } ?>

				<?php
				if ( isset ( $_GET['page'] ) ) {
					$this->admin_tabs( $_GET['page'] );
				} else {
					$this->admin_tabs();
				}
				?>

				<?php
				wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				?>

				<div id="poststuff">

					<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">

						<div id="postbox-container-1" class="postbox-container">
							<?php do_meta_boxes( $screen, 'priority_side', NULL ); ?>
							<?php do_meta_boxes( $screen, 'side', NULL ); ?>
						</div>

						<div id="postbox-container-2" class="postbox-container">
							<?php do_action( $bwps_globals['plugin_hook'] . '_page_top', $screen ); ?>
							<?php do_meta_boxes( $screen, 'normal', NULL ); ?>
							<?php do_action( $bwps_globals['plugin_hook'] . '_page_middle', $screen ); ?>
							<?php do_meta_boxes( $screen, 'advanced', NULL ); ?>
							<?php do_action( $bwps_globals['plugin_hook'] . '_page_bottom', $screen ); ?>
						</div>

					</div>
					<!-- #post-body -->

				</div>
				<!-- #poststuff -->

			</div><!-- .wrap -->

		<?php
		}

		/**
		 * Saves general plugin data to determine global items
		 *
		 * @return void
		 */
		function save_plugin_data() {

			global $bwps_globals;

			$save_data = false; //flag to avoid saving data if we don't have to

			$plugin_data = get_site_option( $bwps_globals['plugin_hook'] . '_data' );

			//Update the build number if we need to
			if ( ! isset( $plugin_data['build'] ) || ( isset( $plugin_data['build'] ) && $plugin_data['build'] !== $bwps_globals['plugin_build'] ) ) {
				$plugin_data['build'] = $bwps_globals['plugin_build'];
				$save_data            = true;
			}

			//update the activated time if we need to in order to tell when the plugin was installed
			if ( ! isset( $plugin_data['activatestamp'] ) ) {
				$plugin_data['activatestamp'] = time();
				$save_data                    = true;
			}

			//update the options table if we have to
			if ( $save_data === true ) {
				update_site_option( $bwps_globals['plugin_hook'] . '_data', $plugin_data );
			}

		}

		/**
		 * Setup and call admin messages
		 *
		 * Sets up messages and registers actions for WordPress admin messages
		 *
		 * @param object $messages WordPress error object or string of message to display
		 *
		 **/
		function show_admin_messages( $messages ) {

			global $saved_messages; //use global to transfer to add_action callback

			$saved_messages = ''; //initialize so we can get multiple error messages (if needed)

			if ( function_exists( 'apc_store' ) ) {
				apc_clear_cache(); //Let's clear APC (if it exists) when big stuff is saved.
			}

			if ( is_wp_error( $messages ) ) { //see if object is even an error

				$errors = $messages->get_error_messages(); //get all errors if it is

				foreach ( $errors as $error => $string ) {
					$saved_messages .= '<div id="message" class="error"><p>' . $string . '</p></div>';
				}

			} else { //no errors so display settings saved message

				$saved_messages .= '<div id="message" class="updated"><p><strong>' . $messages . '</strong></p></div>';

			}

			//register appropriate message actions
			add_action( 'admin_notices', array( $this, 'dispmessage' ) );

		}

		/**
		 * Echos admin messages
		 *
		 * @return void
		 *
		 **/
		function display_admin_message() {

			global $saved_messages;

			echo $saved_messages;

			unset( $saved_messages ); //delete any saved messages

		}

		/**
		 * Prints out all settings sections added to a particular settings page
		 *
		 * adapted from core function for better styling within meta_box
		 *
		 *
		 * @param string  $page       The slug name of the page whos settings sections you want to output
		 * @param boolean $show_title Whether or not the title of the section should display: default true.
		 */
		function do_settings_sections( $page, $show_title = true ) {

			global $wp_settings_sections, $wp_settings_fields;

			if ( ! isset( $wp_settings_sections ) || ! isset( $wp_settings_sections[$page] ) )
				return;

			foreach ( (array) $wp_settings_sections[$page] as $section ) {
				if ( $section['title'] && $show_title === true )
					echo "<h4>{$section['title']}</h4>\n";

				if ( $section['callback'] )
					call_user_func( $section['callback'], $section );

				if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[$page] ) || ! isset( $wp_settings_fields[$page][$section['id']] ) )
					continue;

				echo '<table class="form-table" id="' . $section['id'] . '">';
				do_settings_fields( $page, $section['id'] );
				echo '</table>';

			}

		}

		/**
		 * Start the global admin instance
		 *
		 * @param  [plugin_class]  $plugin       Instance of main plugin class
		 *
		 * @return bwps_Core                       The instance of the bwps_Core class
		 */
		public static function start( $plugin ) {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self( $plugin );
			}

			return self::$instance;

		}

	}

}
