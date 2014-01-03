<?php

if ( ! class_exists( 'BWPS_SSL_Admin' ) ) {

	class BWPS_SSL_Admin {

		private static $instance = NULL;

		private
			$settings,
			$core,
			$module,
			$page,
			$ssl_support,
			$has_ssl;

		private function __construct( $core, $module ) {

			global $bwps_lib;

			$this->core			= $core;
			$this->module		= $module;
			$this->settings		= get_site_option( 'bwps_ssl' );
			$this->has_ssl		= $bwps_lib->get_ssl();

			add_filter( 'bwps_file_rules', array( $this, 'build_wpconfig_rules' ) );

			add_action( 'bwps_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( 'bwps_page_top', array( $this, 'add_module_intro' ) ); //add page intro and information
			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_filter( 'bwps_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( 'bwps_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu
			add_filter( 'bwps_add_dashboard_status', array( $this, 'dashboard_status' ) ); //add information for plugin status
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) ); //enqueue scripts for admin page

			//manually save options on multisite
			if ( is_multisite() ) {
				add_action( 'network_admin_edit_bwps_ssl', array( $this, 'save_network_options' ) ); //save multisite options
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
			
			wp_nonce_field( 'BWPS_Admin_Save','wp_nonce' );
			
			$enabled = false;
			
			if ( $post->ID ) {
				$enabled = get_post_meta( $post->ID, 'bwps_enable_ssl', true );
			}
			
			$content = '<div id="bwps" class="misc-pub-section">';
			$content .= '<label for="enable_ssl">Enable SSL:</label> ';
			$content .= '<input type="checkbox" value="1" name="enable_ssl" id="enable_ssl"' . checked( 1, $enabled, false ) . ' />';
			$content .= '</div>';

			echo $content;
		
		}

		/**
		 * Save post meta for SSL selection
		 * 
		 * @param  int $id	post id
		 * @return bool		value of bwps_enable_ssl
		 */
		function save_post( $id ) {
		
			if ( isset( $_POST['wp_nonce'] ) ) {
				
				if ( ! wp_verify_nonce( $_POST['wp_nonce'], 'BWPS_Admin_Save' ) || ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) || ( $_POST['post_type'] == 'page' && ! current_user_can( 'edit_page', $id ) ) || ( $_POST['post_type'] == 'post' && ! current_user_can( 'edit_post', $id ) ) ) {
					return $id;
				}
			
				$bwps_enable_ssl = ( ( isset( $_POST['enable_ssl'] ) &&  $_POST['enable_ssl'] == true ) ? true : false );
			
				if ( $bwps_enable_ssl ) {
					update_post_meta( $id, 'bwps_enable_ssl', true );
				} else {
					update_post_meta( $id, 'bwps_enable_ssl', false );
				}
			
				return $bwps_enable_ssl;
		
			}
		
		}

		/**
		 * Register subpage for Away Mode
		 *
		 * @param array $available_pages array of BWPS settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $bwps_globals;

			$this->page = $available_pages[0] . '-ssl';

			$available_pages[] = add_submenu_page(
				'bwps',
				__( 'SSL', 'better_wp_security' ),
				__( 'SSL', 'better_wp_security' ),
				$bwps_globals['plugin_access_lvl'],
				$available_pages[0] . '-ssl',
				array( $this->core, 'render_page' )
			);

			return $available_pages;

		}

		public function add_admin_tab( $tabs ) {

			$tabs[$this->page] = __( 'SSL', 'better_wp_security' );

			return $tabs;

		}

		/**
		 * Add meta boxes to primary options pages
		 *
		 * @param array $available_pages array of available page_hooks
		 */
		public function add_admin_meta_boxes( $available_pages ) {

			//add metaboxes
			add_meta_box(
				'ssl_options',
				__( 'Configure SSL', 'better_wp_security' ),
				array( $this, 'metabox_advanced_settings' ),
				'security_page_toplevel_page_bwps-ssl',
				'advanced',
				'core'
			);

		}

		/**
		 * Add SSL Javascript
		 *
		 * @return void
		 */
		public function admin_script() {

			global $bwps_globals;

			if ( strpos( get_current_screen()->id, 'security_page_toplevel_page_bwps-ssl' ) !== false ) {

				wp_enqueue_script( 'bwps_ssl_js', $bwps_globals['plugin_url'] . 'modules/ssl/js/admin-ssl.js', 'jquery', $bwps_globals['plugin_build'] );

    			//make sure the text of the warning is translatable
   				wp_localize_script( 'bwps_ssl_js', 'ssl_warning_text', array( 'text' => __( 'Are you sure you want to enable SSL? If your server does not support SSL you will be locked out of your WordPress Dashboard.', 'better-wp-security' ) ) );
				
			}

		}

		/**
		 * Sets the status in the plugin dashboard
		 *
		 * @return void
		 */
		public function dashboard_status( $statuses ) {

			$link = 'admin.php?page=toplevel_page_bwps-ssl';

			if ( FORCE_SSL_LOGIN === true && FORCE_SSL_ADMIN === true ) { 

				$status_array = 'safe-low';
				$status = array(
					'text' => __( 'You are requiring a secure connection for logins and the admin area.', 'better_wp_security' ),
					'link' => $link,
				);

			} elseif ( FORCE_SSL_LOGIN === true || FORCE_SSL_ADMIN === true ) {

			$status_array = 'low';
				$status = array(
					'text' => __( 'You are requiring a secure connection for logins or the admin area but not both.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status = array(
					'text' => __( 'You are not requiring a secure connection for logins or for the admin area.', 'better_wp_security' ),
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

			//primary settings section
			add_settings_section(
				'ssl_settings',
				__( 'Configure SSL', 'better_wp_security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_bwps-ssl'
			);

			//enabled field
			add_settings_field(
				'bwps_ssl[frontend]',
				__( 'Front End SSL Mode', 'better_wp_security' ),
				array( $this, 'ssl_frontend' ),
				'security_page_toplevel_page_bwps-ssl',
				'ssl_settings'
			);

			//enabled field
			add_settings_field(
				'bwps_ssl[login]',
				__( 'Force SSL for Login', 'better_wp_security' ),
				array( $this, 'ssl_login' ),
				'security_page_toplevel_page_bwps-ssl',
				'ssl_settings'
			);

			//enabled field
			add_settings_field(
				'bwps_ssl[admin]',
				__( 'Force SSL for Dashboard', 'better_wp_security' ),
				array( $this, 'ssl_admin' ),
				'security_page_toplevel_page_bwps-ssl',
				'ssl_settings'
			);

			//Register the settings field for the entire module
			register_setting(
				'security_page_toplevel_page_bwps-ssl',
				'bwps_ssl',
				array( $this, 'sanitize_module_input' )
			);

		}

		/**
		 * Empty callback function
		 */
		public function empty_callback_function() {}

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

			echo '<select id="bwps_ssl_frontend" name="bwps_ssl[frontend]">';
			
			echo '<option value="0" ' . selected( $frontend, '0' ) . '>' . __( 'Off', 'better-wp-security' ) . '</option>';
			echo '<option value="1" ' . selected( $frontend, '1' ) . '>' . __( 'Per Content', 'better-wp-security' ) . '</option>';
			echo '<option value="2" ' . selected( $frontend, '2' ) . '>' . __( 'Whole Site', 'better-wp-security' ) . '</option>';
			echo '</select><br />';
			echo '<label for="bwps_ssl_frontend"> ' . __( 'Enables secure SSL connection for the front-end (public parts of your site). Turning this off will disable front-end SSL control, turning this on "Per Content" will place a checkbox on the edit page for all posts and pages (near the publish settings) allowing you to turn on SSL for selected pages or posts, and selecting "Whole Site" will force the whole site to use SSL (not recommended unless you have a really good reason to use it).', 'better_wp_security' ) . '</label>';

		}

		/**
		 * echos login Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function ssl_login( $args ) {

			if ( isset( $this->settings['login'] ) && $this->settings['login'] === 1 && defined( 'FORCE_SSL_LOGIN' ) && FORCE_SSL_LOGIN === true ) {
				$login = 1;
			} else {
				$login = 0;
			}

			$content = '<input onchange="forcessl()" type="checkbox" id="bwps_ssl_login" name="bwps_ssl[login]" value="1" ' . checked( 1, $login, false ) . '/>';
			$content .= '<label for="bwps_ssl_login"> ' . __( 'Forces all logins to be served only over a secure SSL connection.', 'better_wp_security' ) . '</label>';

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

			if ( isset( $this->settings['admin'] ) && $this->settings['admin'] === 1 && defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN === true ) {
				$admin = 1;
			} else {
				$admin = 0;
			}

			$content = '<input onchange="forcessl()" type="checkbox" id="bwps_ssl_admin" name="bwps_ssl[admin]" value="1" ' . checked( 1, $admin, false ) . '/>';
			$content .= '<label for="bwps_ssl_admin"> ' . __( 'Forces all of the WordPress dashboard to be served only over a secure SSL connection.', 'better_wp_security' ) . '</label>';

			echo $content;

		}



		/**
		 * Build and echo the away mode description
		 *
		 * @return void
		 */
		public function add_module_intro( $screen ) {

			global $bwps_lib;

			if ( $screen === 'security_page_toplevel_page_bwps-ssl' ) { //only display on away mode page

				$content = '<p>' . __( 'Secure Socket Layers (aka SSL) is a technology that is used to encrypt the data sent between your server or host and the visitor to your web page. When activated it makes it almost impossible for an attacker to intercept data in transit therefore making the transmission of form, password, or other encrypted data much safer.', 'better-wp-security' ) . '</p>';
				$content .= '<p>' . __( 'Better WP Security gives you the option of turning on SSL (if your server or host support it) for all or part of your site. The options below allow you to automatically use SSL for major parts of your site, the login page, the admin dashboard, or the site as a whole. You can also turn on SSL for any post or page by editing the content you want to use SSL in and selecting "Enable SSL" in the publishing options of the content in question.', 'better-wp-security' ) . '</p>';
				$content .= '<p>' . __( 'While this plugin does give you the option of encrypting everything please note this might not be for you. SSL does add overhead to your site which will increase download times slightly. Therefore we recommend you enable SSL at a minimum on the login page, then on the whole admin section, finally on individual pages or posts with forms that require sensitive information.', 'better-wp-security' ) . '</p>';

				if ( $bwps_lib->get_ssl() === false ) {

					$content .= sprintf( '<h4 style="color: red; text-align: center; border-bottom: none; font-size: 130%%;">%s</h4>', __( 'WARNING: Your server does not appear to support SSL. Your server MUST support SSL to use these features. Using these features without SSL support on your server or host will cause some or all of your site to become unavailable.', 'better-wp-security' ) );		

				} else {

					$content .= sprintf( '<h4 style="color: blue; text-align: center; border-bottom: none; font-size: 130%%;">%s</h4>', __( 'WARNING: Your server does appear to support SSL. Using these features without SSL support on your server or host will cause some or all of your site to become unavailable.', 'better-wp-security' ) );	

				}		

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
				$action = 'edit.php?action=bwps_ssl';
			} else {
				$action = 'options.php';
			}

			printf( '<form name="%s" method="post" action="%s">', get_current_screen()->id, $action );

			$this->core->do_settings_sections( 'security_page_toplevel_page_bwps-ssl', false );

			echo '<p>' . PHP_EOL;

			settings_fields( 'security_page_toplevel_page_bwps-ssl' );

			echo '<input class="button-primary" name="submit" type="submit" value="' . __( 'Save Changes', 'better_wp_security' ) . '" />' . PHP_EOL;

			echo '</p>' . PHP_EOL;

			echo '</form>';

		}

		/**
		 * Build wp-config.php rules
		 * 
		 * @param  array $input  options to build rules from
		 * @return array         rules to write
		 */
		public function build_wpconfig_rules( $rules_array, $input = null ) {

			//Get the rules from the database if input wasn't sent
			if ( $input === null ) {
				$input = get_site_option( 'bwps_ssl' );
			}

			if ( $input['login'] == 1 ) {

				$rules[] = array(
					'type'			=> 'add',
					'search_text'	=> 'FORCE_SSL_LOGIN',
					'rule'			=> "define( 'FORCE_SSL_LOGIN', true );",
				);

				$has_login = true;

			} else {

				$rules[] = array(
					'type'			=> 'delete',
					'search_text'	=> 'FORCE_SSL_LOGIN',
					'rule'			=> false,
				);

				$has_login = false;

			}

			if ( $input['admin'] == 1 ) {

				$rules[] = array(
					'type'			=> 'add',
					'search_text'	=> 'FORCE_SSL_ADMIN',
					'rule'			=> "define( 'FORCE_SSL_ADMIN', true );",
				);

				$has_admin = true;

			} else {

				$rules[] = array(
					'type'			=> 'delete',
					'search_text'	=> 'FORCE_SSL_ADMIN',
					'rule'			=> false,
				);

				$has_admin = false;

			}

			if ( $has_login === false && $has_admin == false ) {

				$comment = array(
					'type'			=> 'delete',
					'search_text'	=> '//The entries below were created by Better WP Security to enforce SSL',
					'rule'			=> false,
				);

			} else {

				$comment = array(
					'type'			=> 'add',
					'search_text'	=> '//The entries below were created by Better WP Security to enforce SSL',
					'rule'			=> '//The entries below were created by Better WP Security to enforce SSL',
				);

			}

			array_unshift( $rules, $comment );

			$rules_array[] = array(
				'type'	=> 'wpconfig',
				'name'	=> 'SSL',
				'rules'	=> $rules,
			);

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

			global $bwps_files;

			//Assume this is going to work by default
			$type    = 'updated';
			$message = __( 'Settings Updated', 'better_wp_security' );

			$input['frontend'] = isset( $input['frontend'] ) ? intval( $input['frontend'] ) : 0;
			$input['login'] = isset( $input['login'] ) ? intval( $input['login'] ) : 0;
			$input['admin'] = isset( $input['admin'] ) ? intval( $input['admin'] ) : 0;

			$rules = $this->build_wpconfig_rules( array(), $input );

			$bwps_files->set_wpconfig( $rules );

			if ( ! $bwps_files->save_wpconfig() ) {

				$type    = 'error';
				$message = __( 'WordPress was unable to save the SSL options to wp-config.php. Please check with your server administrator and try again.', 'better_wp_security' );

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

			$settings['login'] = ( isset( $_POST['bwps_ssl']['login'] ) && intval( $_POST['bwps_ssl']['login'] == 1 ) ? 1 : 0 );
			$settings['admin'] = ( isset( $_POST['bwps_ssl']['admin'] ) && intval( $_POST['bwps_ssl']['admin'] == 1 ) ? 1 : 0 );
			$settings['frontend'] = isset( $_POST['bwps_ssl']['frontend'] ) ? intval( $_POST['bwps_ssl']['frontend'] ) : 0;

			update_site_option( 'bwps_ssl', $settings ); //we must manually save network options

			//send them back to the away mode options page
			wp_redirect( add_query_arg( array( 'page' => 'toplevel_page_bwps-ssl', 'updated' => 'true' ), network_admin_url( 'admin.php' ) ) );
			exit();

		}

		/**
		 * Start the Away Mode module
		 *
		 * @param  Ithemes_BWPS_Core $core Instance of core plugin class
		 * @param BWPS_SSL $module Instance of the ssl module class
		 *
		 * @return BWPS_SSL_Admin              The instance of the BWPS_SSL_Admin class
		 */
		public static function start( $core, $module ) {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self( $core, $module );
			}

			return self::$instance;

		}

	}

}