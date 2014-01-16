<?php

if ( ! class_exists( 'ITSEC_SSL_Admin' ) ) {

	class ITSEC_SSL_Admin {

		private static $instance = null;

		private $settings, $core, $module, $page, $ssl_support, $has_ssl;

		private function __construct( $core, $module ) {

			global $itsec_lib;

			$this->core     = $core;
			$this->module   = $module;
			$this->settings = get_site_option( 'itsec_ssl' );
			$this->has_ssl  = $itsec_lib->get_ssl();

			add_filter( 'itsec_file_rules', array( $this, 'build_wpconfig_rules' ) );

			add_action( 'itsec_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_filter( 'itsec_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( 'itsec_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu
			add_filter( 'itsec_add_dashboard_status', array( $this, 'dashboard_status' ) ); //add information for plugin status
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) ); //enqueue scripts for admin page

			//manually save options on multisite
			if ( is_multisite() ) {
				add_action( 'network_admin_edit_itsec_ssl', array( $this, 'save_network_options' ) ); //save multisite options
			}

			if ( $this->settings['frontend'] == 1 ) {

				add_action( 'post_submitbox_misc_actions', array( $this, 'ssl_enable_per_content' ) );
				add_action( 'save_post', array( $this, 'save_post' ) );

			}

		}

		/**
		 * Add checkbox to post meta for SSL
		 *
		 * @return void
		 */
		function ssl_enable_per_content() {

			global $post;

			wp_nonce_field( 'ITSEC_Admin_Save', 'wp_nonce' );

			$enabled = false;

			if ( $post->ID ) {
				$enabled = get_post_meta( $post->ID, 'itsec_enable_ssl', true );
			}

			$content = '<div id="itsec" class="misc-pub-section">';
			$content .= '<label for="enable_ssl">Enable SSL:</label> ';
			$content .= '<input type="checkbox" value="1" name="enable_ssl" id="enable_ssl"' . checked( 1, $enabled, false ) . ' />';
			$content .= '</div>';

			echo $content;

		}

		/**
		 * Save post meta for SSL selection
		 *
		 * @param  int $id post id
		 *
		 * @return bool        value of itsec_enable_ssl
		 */
		function save_post( $id ) {

			if ( isset( $_POST['wp_nonce'] ) ) {

				if ( ! wp_verify_nonce( $_POST['wp_nonce'], 'ITSEC_Admin_Save' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( $_POST['post_type'] == 'page' && ! current_user_can( 'edit_page', $id ) ) || ( $_POST['post_type'] == 'post' && ! current_user_can( 'edit_post', $id ) ) ) {
					return $id;
				}

				$itsec_enable_ssl = ( ( isset( $_POST['enable_ssl'] ) && $_POST['enable_ssl'] == true ) ? true : false );

				if ( $itsec_enable_ssl ) {
					update_post_meta( $id, 'itsec_enable_ssl', true );
				} else {
					update_post_meta( $id, 'itsec_enable_ssl', false );
				}

				return $itsec_enable_ssl;

			}

		}

		/**
		 * Register subpage for Away Mode
		 *
		 * @param array $available_pages array of ITSEC settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $itsec_globals;

			$this->page = $available_pages[0] . '-ssl';

			$available_pages[] = add_submenu_page( 'itsec', __( 'SSL', 'ithemes-security' ), __( 'SSL', 'ithemes-security' ), $itsec_globals['plugin_access_lvl'], $available_pages[0] . '-ssl', array( $this->core, 'render_page' ) );

			return $available_pages;

		}

		public function add_admin_tab( $tabs ) {

			$tabs[$this->page] = __( 'SSL', 'ithemes-security' );

			return $tabs;

		}

		/**
		 * Add meta boxes to primary options pages
		 *
		 * @param array $available_pages array of available page_hooks
		 */
		public function add_admin_meta_boxes( $available_pages ) {

			//add metaboxes
			add_meta_box( 'ssl_description', __( 'Description', 'ithemes-security' ), array( $this, 'add_module_intro' ), 'security_page_toplevel_page_itsec-ssl', 'normal', 'core' );

			add_meta_box( 'ssl_options', __( 'Configure SSL', 'ithemes-security' ), array( $this, 'metabox_advanced_settings' ), 'security_page_toplevel_page_itsec-ssl', 'advanced', 'core' );

		}

		/**
		 * Add SSL Javascript
		 *
		 * @return void
		 */
		public function admin_script() {

			global $itsec_globals;

			if ( strpos( get_current_screen()->id, 'security_page_toplevel_page_itsec-ssl' ) !== false ) {

				wp_enqueue_script( 'itsec_ssl_js', $itsec_globals['plugin_url'] . 'modules/ssl/js/admin-ssl.js', 'jquery', $itsec_globals['plugin_build'] );

				//make sure the text of the warning is translatable
				wp_localize_script( 'itsec_ssl_js', 'ssl_warning_text', array( 'text' => __( 'Are you sure you want to enable SSL? If your server does not support SSL you will be locked out of your WordPress Dashboard.', 'ithemes-security' ) ) );

			}

		}

		/**
		 * Sets the status in the plugin dashboard
		 *
		 * @return void
		 */
		public function dashboard_status( $statuses ) {

			$link = 'admin.php?page=toplevel_page_itsec-ssl';

			if ( FORCE_SSL_LOGIN === true && FORCE_SSL_ADMIN === true ) {

				$status_array = 'safe-low';
				$status       = array( 'text' => __( 'You are requiring a secure connection for logins and the admin area.', 'ithemes-security' ), 'link' => $link, );

			} elseif ( FORCE_SSL_LOGIN === true || FORCE_SSL_ADMIN === true ) {

				$status_array = 'low';
				$status       = array( 'text' => __( 'You are requiring a secure connection for logins or the admin area but not both.', 'ithemes-security' ), 'link' => $link, );

			} else {

				$status_array = 'low';
				$status       = array( 'text' => __( 'You are not requiring a secure connection for logins or for the admin area.', 'ithemes-security' ), 'link' => $link, );

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

			//primary settings section
			add_settings_section( 'ssl_settings', __( 'Configure SSL', 'ithemes-security' ), array( $this, 'empty_callback_function' ), 'security_page_toplevel_page_itsec-ssl' );

			//enabled field
			add_settings_field( 'itsec_ssl[frontend]', __( 'Front End SSL Mode', 'ithemes-security' ), array( $this, 'ssl_frontend' ), 'security_page_toplevel_page_itsec-ssl', 'ssl_settings' );

			//enabled field
			add_settings_field( 'itsec_ssl[login]', __( 'SSL for Login', 'ithemes-security' ), array( $this, 'ssl_login' ), 'security_page_toplevel_page_itsec-ssl', 'ssl_settings' );

			//enabled field
			add_settings_field( 'itsec_ssl[admin]', __( 'SSL for Dashboard', 'ithemes-security' ), array( $this, 'ssl_admin' ), 'security_page_toplevel_page_itsec-ssl', 'ssl_settings' );

			//Register the settings field for the entire module
			register_setting( 'security_page_toplevel_page_itsec-ssl', 'itsec_ssl', array( $this, 'sanitize_module_input' ) );

		}

		/**
		 * Empty callback function
		 */
		public function empty_callback_function() {
		}

		/**
		 * echos front end Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function ssl_frontend( $args ) {

			if ( isset( $this->settings['frontend'] ) ) {
				$frontend = $this->settings['frontend'];
			} else {
				$frontend = 0;
			}

			echo '<select id="itsec_ssl_frontend" name="itsec_ssl[frontend]">';

			echo '<option value="0" ' . selected( $frontend, '0' ) . '>' . __( 'Off', 'ithemes-security' ) . '</option>';
			echo '<option value="1" ' . selected( $frontend, '1' ) . '>' . __( 'Per Content', 'ithemes-security' ) . '</option>';
			echo '<option value="2" ' . selected( $frontend, '2' ) . '>' . __( 'Whole Site', 'ithemes-security' ) . '</option>';
			echo '</select><br />';
			echo '<label for="itsec_ssl_frontend"> ' . __( 'Front End SSL Mode', 'ithemes-security' ) . '</label>';
			echo '<p class="description">' . __( 'Enables secure SSL connection for the front-end (public parts of your site). Turning this off will disable front-end SSL control, turning this on "Per Content" will place a checkbox on the edit page for all posts and pages (near the publish settings) allowing you to turn on SSL for selected pages or posts, and selecting "Whole Site" will force the whole site to use SSL (not recommended unless you have a really good reason to use it' ) . '</p>';

		}

		/**
		 * echos login Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function ssl_login( $args ) {

			if ( isset( $this->settings['login'] ) && $this->settings['login'] === true ) {
				$login = 1;
			} else {
				$login = 0;
			}

			$content = '<input onchange="forcessl()" type="checkbox" id="itsec_ssl_login" name="itsec_ssl[login]" value="1" ' . checked( 1, $login, false ) . '/>';
			$content .= '<label for="itsec_ssl_login">' . __( 'Force SSL for Login', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'Forces all logins to be served only over a secure SSL connection.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos admin Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function ssl_admin( $args ) {

			if ( isset( $this->settings['admin'] ) && $this->settings['admin'] === true ) {
				$admin = 1;
			} else {
				$admin = 0;
			}

			$content = '<input onchange="forcessl()" type="checkbox" id="itsec_ssl_admin" name="itsec_ssl[admin]" value="1" ' . checked( 1, $admin, false ) . '/>';
			$content .= '<label for="itsec_ssl_admin">' . __( 'Force SSL for Dashboard', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'Forces all logins to be served only over a secure SSL connection.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * Build and echo the away mode description
		 *
		 * @return void
		 */
		public function add_module_intro( $screen ) {

			global $itsec_lib;

			$content = '<p>' . __( 'Secure Socket Layers (aka SSL) is a technology that is used to encrypt the data sent between your server or host and the visitor to your web page. When activated it makes it almost impossible for an attacker to intercept data in transit therefore making the transmission of form, password, or other encrypted data much safer.', 'ithemes-security' ) . '</p>';
			$content .= '<p>' . __( 'iThemes Security gives you the option of turning on SSL (if your server or host support it) for all or part of your site. The options below allow you to automatically use SSL for major parts of your site, the login page, the admin dashboard, or the site as a whole. You can also turn on SSL for any post or page by editing the content you want to use SSL in and selecting "Enable SSL" in the publishing options of the content in question.', 'ithemes-security' ) . '</p>';
			$content .= '<p>' . __( 'While this plugin does give you the option of encrypting everything please note this might not be for you. SSL does add overhead to your site which will increase download times slightly. Therefore we recommend you enable SSL at a minimum on the login page, then on the whole admin section, finally on individual pages or posts with forms that require sensitive information.', 'ithemes-security' ) . '</p>';

			if ( $itsec_lib->get_ssl() === false ) {

				$content .= sprintf( '<div class="itsec-warning-message"><span>%s: </span>%s</div>', __( 'WARNING', 'ithemes-security' ), __( 'Your server does not appear to support SSL. Your server MUST support SSL to use these features. Using these features without SSL support on your server or host will cause some or all of your site to become unavailable.', 'ithemes-security' ) );

			} else {

				$content .= sprintf( '<div class="itsec-warning-message"><span>%s: </span>%s</div>', __( 'WARNING', 'ithemes-security' ), __( 'Your server does appear to support SSL. Using these features without SSL support on your server or host will cause some or all of your site to become unavailable.', 'ithemes-security' ) );

			}

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
				$action = 'edit.php?action=itsec_ssl';
			} else {
				$action = 'options.php';
			}

			printf( '<form name="%s" method="post" action="%s">', get_current_screen()->id, $action );

			$this->core->do_settings_sections( 'security_page_toplevel_page_itsec-ssl', false );

			echo '<p>' . PHP_EOL;

			settings_fields( 'security_page_toplevel_page_itsec-ssl' );

			echo '<input class="button-primary" name="submit" type="submit" value="' . __( 'Save Changes', 'ithemes-security' ) . '" />' . PHP_EOL;

			echo '</p>' . PHP_EOL;

			echo '</form>';

		}

		/**
		 * Build wp-config.php rules
		 *
		 * @param  array $input options to build rules from
		 *
		 * @return array         rules to write
		 */
		public function build_wpconfig_rules( $rules_array, $input = null ) {

			//Return options to default on deactivation
			if ( $rules_array === false ) {

				$input       = array();
				$rules_array = array();

				$deactivating = true;

				$initials = get_site_option( 'itsec_initials' );

				if ( isset( $initials['login'] ) && $initials['login'] === false ) {
					$input['login'] = false;
				} else {
					$input['login'] = true;
				}

				if ( isset( $initials['admin'] ) && $initials['admin'] === false ) {
					$input['admin'] = false;
				} else {
					$input['admin'] = true;
				}

			} else {

				$deactivating = false;

				//Get the rules from the database if input wasn't sent
				if ( $input === null ) {
					$input = get_site_option( 'itsec_ssl' );
				}

			}

			if ( $input['login'] == true ) {

				$rules[] = array( 'type' => 'add', 'search_text' => 'FORCE_SSL_LOGIN', 'rule' => "define( 'FORCE_SSL_LOGIN', true );", );

				$has_login = true;

			} else {

				$rules[] = array( 'type' => 'delete', 'search_text' => 'FORCE_SSL_LOGIN', 'rule' => false, );

				$has_login = false;

			}

			if ( $input['admin'] == true ) {

				$rules[] = array( 'type' => 'add', 'search_text' => 'FORCE_SSL_ADMIN', 'rule' => "define( 'FORCE_SSL_ADMIN', true );", );

				$has_admin = true;

			} else {

				$rules[] = array( 'type' => 'delete', 'search_text' => 'FORCE_SSL_ADMIN', 'rule' => false, );

				$has_admin = false;

			}

			if ( ( $has_login === false && $has_admin == false ) || $deactivating === true ) {

				$comment = array( 'type' => 'delete', 'search_text' => '//The entries below were created by iThemes Security to enforce SSL', 'rule' => false, );

			} else {

				$comment = array( 'type' => 'add', 'search_text' => '//The entries below were created by iThemes Security to enforce SSL', 'rule' => '//The entries below were created by iThemes Security to enforce SSL', );

			}

			array_unshift( $rules, $comment );

			$rules_array[] = array( 'type' => 'wpconfig', 'name' => 'SSL', 'rules' => $rules, );

			return $rules_array;

		}

		/**
		 * Sanitize and validate input
		 *
		 * @param  Array $input array of input fields
		 *
		 * @return Array         Sanitized array
		 */
		public function sanitize_module_input( $input ) {

			global $itsec_files;

			//Assume this is going to work by default
			$type    = 'updated';
			$message = __( 'Settings Updated', 'ithemes-security' );

			$input['frontend'] = isset( $input['frontend'] ) ? intval( $input['frontend'] ) : 0;
			$input['login']    = ( isset( $input['login'] ) && intval( $input['login'] == 1 ) ? true : false );
			$input['admin']    = ( isset( $input['admin'] ) && intval( $input['admin'] == 1 ) ? true : false );

			$rules = $this->build_wpconfig_rules( array(), $input );

			$itsec_files->set_wpconfig( $rules );

			if ( ! $itsec_files->save_wpconfig() ) {

				$type    = 'error';
				$message = __( 'WordPress was unable to save the SSL options to wp-config.php. Please check with your server administrator and try again.', 'ithemes-security' );

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

			$settings['login']    = ( isset( $_POST['itsec_ssl']['login'] ) && intval( $_POST['itsec_ssl']['login'] == 1 ) ? true : false );
			$settings['admin']    = ( isset( $_POST['itsec_ssl']['admin'] ) && intval( $_POST['itsec_ssl']['admin'] == 1 ) ? true : false );
			$settings['frontend'] = isset( $_POST['itsec_ssl']['frontend'] ) ? intval( $_POST['itsec_ssl']['frontend'] ) : 0;

			update_site_option( 'itsec_ssl', $settings ); //we must manually save network options

			//send them back to the away mode options page
			wp_redirect( add_query_arg( array( 'page' => 'toplevel_page_itsec-ssl', 'updated' => 'true' ), network_admin_url( 'admin.php' ) ) );
			exit();

		}

		/**
		 * Start the Away Mode module
		 *
		 * @param  Ithemes_ITSEC_Core $core   Instance of core plugin class
		 * @param ITSEC_SSL           $module Instance of the ssl module class
		 *
		 * @return ITSEC_SSL_Admin              The instance of the ITSEC_SSL_Admin class
		 */
		public static function start( $core, $module ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $core, $module );
			}

			return self::$instance;

		}

	}

}