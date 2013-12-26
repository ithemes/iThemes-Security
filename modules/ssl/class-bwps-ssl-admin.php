<?php

if ( ! class_exists( 'BWPS_SSL_Admin' ) ) {

	class BWPS_SSL_Admin {

		private static $instance = NULL;

		private
			$settings,
			$core,
			$module,
			$page,
			$ssl_support;

		private function __construct( $core, $module ) {

			global $bwps_lib;

			$this->core      = $core;
			$this->module	 = $module;
			$this->settings  = get_site_option( 'bwps_ssl' );

			$bwps_lib->get_ssl();

			add_action( 'bwps_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( 'bwps_page_top', array( $this, 'add_module_intro' ) ); //add page intro and information
			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_filter( 'bwps_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( 'bwps_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu
			add_filter( 'bwps_add_dashboard_status', array( $this, 'dashboard_status' ) ); //add information for plugin status

			//manually save options on multisite
			if ( is_multisite() ) {
				add_action( 'network_admin_edit_bwps_ssl', array( $this, 'save_network_options' ) ); //save multisite options
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

			$content = '<select id="bwps_ssl_frontend" name="bwps_ssl[frontend]">';
			
			$content .= '<option value="0" ' . selected( $frontent, '0' ) . '>' . __( 'Off', 'better-wp-security' ) . '</option>';
			$content .= '<option value="1" ' . selected( $frontent, '1' ) . '>' . __( 'Per Content', 'better-wp-security' ) . '</option>';
			$content .= '<option value="2" ' . selected( $frontent, '2' ) . '>' . __( 'Whole Site', 'better-wp-security' ) . '</option>';
			$content .= '</select><br />';
			$content .= '<label for="bwps_ssl_frontend"> ' . __( 'Enables secure SSL connection for the front-end (public parts of your site). Turning this off will disable front-end SSL control, turning this on "Per Content" will place a checkbox on the edit page for all posts and pages (near the publish settings) allowing you to turn on SSL for selected pages or posts, and selecting "Whole Site" will force the whole site to use SSL (not recommended unless you have a really good reason to use it).', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos login Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function ssl_login( $args ) {

			if ( isset( $this->settings['login'] ) && $this->settings['login'] === 1 ) {
				$login = 1;
			} else {
				$login = 0;
			}

			$content = '<input type="checkbox" id="bwps_ssl_login" name="bwps_ssl[login]" value="1" ' . checked( 1, $login, false ) . '/>';
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

			if ( isset( $this->settings['admin'] ) && $this->settings['admin'] === 1 ) {
				$admin = 1;
			} else {
				$admin = 0;
			}

			$content = '<input type="checkbox" id="bwps_ssl_admin" name="bwps_ssl[admin]" value="1" ' . checked( 1, $admin, false ) . '/>';
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
		 * Sanitize and validate input
		 *
		 * @param  Array $input array of input fields
		 *
		 * @return Array         Sanitized array
		 */
		public function sanitize_module_input( $input ) {

			$input['enabled'] = intval( $input['enabled'] == 1 ? 1 : 0 );

			$input['type'] = intval( $input['type'] == 1 ? 1 : 2 );

			//we don't need to process this again if it is a multisite installation
			if ( ! is_multisite() ) {

				$input['start'] = strtotime( $input['start']['date'] . ' ' . $input['start']['hour'] . ':' . $input['start']['minute'] . ' ' . $input['start']['sel'] );
				$input['end']   = strtotime( $input['end']['date'] . ' ' . $input['end']['hour'] . ':' . $input['end']['minute'] . ' ' . $input['end']['sel'] );

			}

			if ( $this->module->check_away( true, $input ) === true ) {

				$input['enabled'] = 0; //disable away mode

				$type    = 'error';
				$message = __( 'Invalid time listed. The time entered would lock you out of your site now. Please try again.', 'better_wp_security' );

			} elseif ( $input['type'] === 2 && $input['end'] < $input['start'] ) {

				$input['enabled'] = 0; //disable away mode

				$type    = 'error';
				$message = __( 'Invalid time listed. The start time selected is after the end time selected.', 'better_wp_security' );

			} elseif ( $input['type'] === 2 && $input['end'] < current_time( 'timestamp' ) ) {

				$input['enabled'] = 0; //disable away mode

				$type    = 'error';
				$message = __( 'Invalid time listed. The period selected already ended.', 'better_wp_security' );

			} else {

				$type    = 'updated';
				$message = __( 'Settings Updated', 'better_wp_security' );

			}

			if ( $input['enabled'] == 1 && ! file_exists( $this->away_file ) ) {

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

			if ( isset( $_POST['bwps_away_mode']['enabled'] ) ) {
				$settings['enabled'] = intval( $_POST['bwps_away_mode']['enabled'] == 1 ? 1 : 0 );
			}

			$settings['type']  = intval( $_POST['bwps_away_mode']['type'] ) === 1 ? 1 : 2;
			$settings['start'] = strtotime( $_POST['bwps_away_mode']['start']['date'] . ' ' . $_POST['bwps_away_mode']['start']['hour'] . ':' . $_POST['bwps_away_mode']['start']['minute'] . ' ' . $_POST['bwps_away_mode']['start']['sel'] );
			$settings['end']   = strtotime( $_POST['bwps_away_mode']['end']['date'] . ' ' . $_POST['bwps_away_mode']['end']['hour'] . ':' . $_POST['bwps_away_mode']['end']['minute'] . ' ' . $_POST['bwps_away_mode']['end']['sel'] );

			update_site_option( 'bwps_away_mode', $settings ); //we must manually save network options

			//send them back to the away mode options page
			wp_redirect( add_query_arg( array( 'page' => 'toplevel_page_bwps-away_mode', 'updated' => 'true' ), network_admin_url( 'admin.php' ) ) );
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