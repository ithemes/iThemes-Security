<?php
/**
 * Core functionality primarily for adding consistent dashboard functionality
 *
 * @version 1.0
 */

if ( ! class_exists( 'ITSEC_Core' ) ) {

	final class ITSEC_Core {

		private static $instance = NULL; //instantiated instance of this plugin

		public
			$admin_tabs,
			$page_hooks,
			$plugin;

		/**
		 * Loads core functionality across both admin and frontend.
		 *
		 * @param Ithemes_ITSEC $plugin
		 *
		 * @return void
		 */
		private function __construct() {

			global $itsec_globals, $itsec_lib, $itsec_files, $itsec_logger, $itsec_lockout;

			@ini_set( 'auto_detect_line_endings', true ); //Make certain we're using proper line endings

			//load utility functions
			require_once( $itsec_globals['plugin_dir'] . 'inc/class-itsec-lib.php' );
			$itsec_lib = ITSEC_Lib::start();

			//load file utility functions
			require_once( $itsec_globals['plugin_dir'] . 'inc/class-itsec-files.php' );
			$itsec_files = ITSEC_Files::start();

			//load logging functions
			require_once( $itsec_globals['plugin_dir'] . 'inc/class-itsec-logger.php' );
			$itsec_logger = ITSEC_Logger::start();

			//load lockout functions
			require_once( $itsec_globals['plugin_dir'] . 'inc/class-itsec-lockout.php' );
			$itsec_lockout = ITSEC_Lockout::start();

			//load logging functions
			require_once( $itsec_globals['plugin_dir'] . 'inc/class-itsec-global-settings.php' );
			ITSEC_Global_Settings::start( $this );

			//load the text domain
			load_plugin_textdomain( 'ithemes-security', false, $itsec_globals['plugin_dir'] . 'languages' );

			$this->load_modules(); //load all modules

			//builds admin menus after modules are loaded
			if ( is_admin() ) {
				$this->build_admin();
			}

			//require plugin setup information
			require_once( $itsec_globals['plugin_dir'] . 'inc/class-itsec-setup.php' );
			register_activation_hook( $itsec_globals['plugin_file'], array( 'ITSEC_Setup', 'on_activate' ) );
			register_deactivation_hook( $itsec_globals['plugin_file'], array( 'ITSEC_Setup', 'on_deactivate' ) );
			register_uninstall_hook( $itsec_globals['plugin_file'], array( 'ITSEC_Setup', 'on_uninstall' ) );

			//Determine if we need to run upgrade scripts
			$plugin_data = get_option( 'itsec_data' );

			if ( $plugin_data !== false ) { //if plugin data does exist

				//see if the saved build version is older than the current build version
				if ( isset( $plugin_data['build'] ) && $plugin_data['build'] !== $itsec_globals['plugin_build'] ) {
					Ithemes_ITSEC_Setup::on_activate( $plugin_data['build'] ); //run upgrade scripts
				}

			}

			//save plugin information
			add_action( 'itsec_set_plugin_data', array( $this, 'save_plugin_data' ) );

		}

		/**
		 * Displays plugin admin notices
		 *
		 * @return  void
		 */
		public function admin_notices() {

			settings_errors( 'itsec_admin_notices' );

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

			global $itsec_globals;

			wp_register_style( 'itsec_admin_styles', $itsec_globals['plugin_url'] . 'inc/css/ithemes.css' );
			do_action( 'itsec_admin_init' ); //execute modules init scripts

		}

		public function admin_tabs( $current = NULL ) {

			global $itsec_globals;

			if ( $current == NULL ) {
				$current = 'itsec';
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

			global $itsec_globals;

			wp_enqueue_style( 'itsec_admin_styles' );
			do_action( $itsec_globals['plugin_url'] . 'enqueue_admin_styles' );

		}

		/**
		 * Loads required plugin modules
		 *
		 * Note: Do not modify this area other than to specify modules to load.
		 * Build all functionality into the appropriate module.
		 *
		 * @return void
		 */
		public function load_modules() {

			global $itsec_globals;

			$modules_folder = $itsec_globals['plugin_dir'] . 'modules';

			$modules = scandir( $modules_folder );

			foreach ( $modules as $module ) {

				$module_folder = $modules_folder . '/' . $module;

				if ( $module !== '.' && $module !== '..' && is_dir( $module_folder ) && file_exists( $module_folder . '/index.php' ) ) {

					require_once( $module_folder . '/index.php' );

				}

			}

		}

		/**
		 * Handles the building of admin menus and calls required functions to render admin pages
		 *
		 * @return void
		 */
		public function setup_primary_admin() {

			global $itsec_globals;

			$this->admin_tabs['itsec'] = __( 'Dashboard', 'ithemes-security' ); //set a tab for the dashboard

			$this->page_hooks[] = add_menu_page(
				__( 'Dashboard', 'ithemes-security' ),
				__( 'Security', 'ithemes-security' ),
				$itsec_globals['plugin_access_lvl'],
				'itsec',
				array( $this, 'render_page' ),
				plugin_dir_url( $itsec_globals['plugin_file'] ) . 'img/shield-small.png'
			);

			$this->page_hooks = apply_filters( 'itsec_add_admin_sub_pages', $this->page_hooks );

			$this->admin_tabs = apply_filters( 'itsec_add_admin_tabs', $this->admin_tabs );

			//Make the dashboard is named correctly
			global $submenu;

			if ( isset( $submenu['itsec'] ) ) {
				$submenu['itsec'][0][0] = __( 'Dashboard', 'ithemes-security' );
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

			global $itsec_globals;

			do_action( 'itsec_add_admin_meta_boxes', $this->page_hooks );

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

			global $itsec_globals;

			if ( is_multisite() ) {
				$screen = substr( get_current_screen()->id, 0, strpos( get_current_screen()->id, '-network' ) );
			} else {
				$screen = get_current_screen()->id; //the current screen id
			}

			?>

			<div class="wrap">
	
				<h2><?php echo $itsec_globals['plugin_name'] . ' - ' . get_admin_page_title(); ?></h2>

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
							<?php
							if ( isset ( $_GET['page'] ) ) {
								$this->admin_tabs( $_GET['page'] );
							} else {
								$this->admin_tabs();
							}
							?>
							<?php do_action( 'itsec_page_top', $screen ); ?>
							<?php do_meta_boxes( $screen, 'normal', NULL ); ?>
							<?php do_action( 'itsec_page_middle', $screen ); ?>
							<?php do_meta_boxes( $screen, 'advanced', NULL ); ?>
							<?php do_action( 'itsec_page_bottom', $screen ); ?>
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

			global $itsec_globals, $itsec_lib;

			$save_data = false; //flag to avoid saving data if we don't have to

			$plugin_data = get_site_option( 'itsec_data' );

			//Update the build number if we need to
			if ( ! isset( $plugin_data['build'] ) || ( isset( $plugin_data['build'] ) && $plugin_data['build'] !== $itsec_globals['plugin_build'] ) ) {
				$plugin_data['build'] = $itsec_globals['plugin_build'];
				$save_data            = true;
			}

			//update the activated time if we need to in order to tell when the plugin was installed
			if ( ! isset( $plugin_data['activatestamp'] ) ) {
				$plugin_data['activatestamp'] = time();
				$save_data                    = true;
			}

			//update the options table if we have to
			if ( $save_data === true ) {
				update_site_option( 'itsec_data', $plugin_data );
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
		 * @return itsec_Core                       The instance of the itsec_Core class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}
