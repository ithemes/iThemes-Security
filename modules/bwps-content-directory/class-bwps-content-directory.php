<?php

if ( ! class_exists( 'BWPS_Content_Directory' ) ) {

	class BWPS_Content_Directory {

		private static $instance = NULL;

		private
			$settings,
			$core,
			$page;

		private function __construct( $core ) {

			global $bwps_globals;

			$this->core      = $core;

			if ( ! strstr( WP_CONTENT_DIR, 'wp-content' ) || ! strstr( WP_CONTENT_URL, 'wp-content' ) ) {
				$this->settings = true;
			} else {
				$this->settings = false;
			}

			add_action( $bwps_globals['plugin_hook'] . '_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( $bwps_globals['plugin_hook'] . '_page_top', array( $this, 'add_content_directory_intro' ) ); //add page intro and information
			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_filter( $bwps_globals['plugin_hook'] . '_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( $bwps_globals['plugin_hook'] . '_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu
			add_filter( $bwps_globals['plugin_hook'] . '_add_dashboard_status', array( $this, 'dashboard_status' ) ); //add information for plugin status

		}

		/**
		 * Register subpage for Away Mode
		 *
		 * @param array $available_pages array of BWPS settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $bwps_globals;

			$this->page = $available_pages[0] . '-content_directory';

			$available_pages[] = add_submenu_page(
				$bwps_globals['plugin_hook'],
				__( 'Content Directory', 'better_wp_security' ),
				__( 'Content Directory', 'better_wp_security' ),
				$bwps_globals['plugin_access_lvl'],
				$available_pages[0] . '-content_directory',
				array( $this->core, 'render_page' )
			);

			return $available_pages;

		}

		public function add_admin_tab( $tabs ) {

			$tabs[$this->page] = __( 'Dir', 'better_wp_security' );

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
				'content_directory_options',
				__( 'Change Content Directory', 'better_wp_security' ),
				array( $this, 'metabox_advanced_settings' ),
				'security_page_toplevel_page_bwps-content_directory',
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

			$link = 'admin.php?page=toplevel_page_bwps-content_directory';

			if ( $this->settings === true ) {

				$status_array = 'safe-low';
				$status = array(
					'text' => __( 'You have renamed the wp-content directory of your site.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status = array(
					'text' => __( 'You should rename the wp-content directory of your site.', 'better_wp_security' ),
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

			//Enabled section
			add_settings_section(
				'content_directory_name',
				__( 'Rename Content Directory', 'better_wp_security' ),
				array( $this, 'sandbox_general_options_callback' ),
				'security_page_toplevel_page_bwps-content_directory'
			);

			//enabled field
			add_settings_field(
				'bwps_content_directory[name]',
				__( 'Content Directory Name', 'better_wp_security' ),
				array( $this, 'content_directory_name' ),
				'security_page_toplevel_page_bwps-content_directory',
				'content_directory_name'
			);

			//Register the settings field for the entire module
			register_setting(
				'security_page_toplevel_page_bwps-content_directory',
				'bwps_content_directory',
				array( $this, 'sanitize_module_input' )
			);

		}

		/**
		 * Settings section callback
		 *
		 * Can be used for an introductory setction or other output. Currently is used by both settings sections.
		 *
		 * @return void
		 */
		public function sandbox_general_options_callback() {
		}

		/**
		 * echos Enabled Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function content_directory_name( $args ) {

			//disable the option if away mode is in the past
			if ( isset( $this->settings['enabled'] ) && $this->settings['enabled'] === 1 && ( $this->settings['type'] == 1 || ( $this->settings['end'] > current_time( 'timestamp' ) || $this->settings['type'] === 2 ) ) ) {
				$enabled = 1;
			} else {
				$enabled = 0;
			}

			$content = '<input type="text" id="bwps_content_directory_name" name="bwps_content_directory[name]" value="wp-content"/><br />';
			$content .= '<label for="bwps_content_directory_name"> ' . __( 'Enter a new directory name to replace "wp-content." You may need to log in again after performing this operation.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * Build and echo the away mode description
		 *
		 * @return void
		 */
		public function add_content_directory_intro( $screen ) {

			if ( $screen === 'security_page_toplevel_page_bwps-content_directory' ) { //only display on away mode page

				$content = '<p>' . __( 'By default WordPress puts all your content including images, plugins, themes, uploads, and more in a directory called "wp-content". This makes it easy to scan for vulnerable files on your WordPress installation as an attacker already knows where the vulnerable files will be at. As there are many plugins and themes with security vulnerabilities moving this folder can make it harder for an attacker to find problems with your site as scans of your site\'s file system will not produce any results.', 'better-wp-security' ) . '</p>';
				$content .= '<p>' . __( 'Please note that changing the name of your wp-content directory on a site that already has images and other content referencing it will break your site. For that reason I highly recommend you do not try this on anything but a fresh WordPress install. In addition, this tool will not allow further changes to your wp-content folder once it has already been renamed in order to avoid accidently breaking a site later on. This includes uninstalling this plugin which will not revert the changes made by this page.', 'better-wp-security' ) . '</p>';
				$content .= '<p>' . __( 'Finally, changing the name of the wp-content directory may in fact break plugins and themes that have "hard-coded" it into their design rather than call it dynamically.', 'better-wp-security' ) . '</p>';
				$content .= '<p style="text-align: center; font-size: 130%; font-weight: bold; color: #ff0000;">' . __( 'WARNING: BACKUP YOUR WORDPRESS INSTALLATION BEFORE USING THIS TOOL!', 'better-wp-security' ) . '</p>';
				$content .= '<p style="text-align: center; font-size: 130%; font-weight: bold; color: #ff0000;">' . __( 'RENAMING YOUR wp-content WILL BREAK LINKS ON A SITE WITH EXISTING CONTENT.', 'better-wp-security' ) . '</p>';

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
				$action = 'edit.php?action=bwps_content_directory';
			} else {
				$action = 'options.php';
			}

			printf( '<form name="%s" method="post" action="%s">', get_current_screen()->id, $action );

			$this->core->do_settings_sections( 'security_page_toplevel_page_bwps-content_directory', false );

			echo '<p>' . PHP_EOL;

			settings_fields( 'security_page_toplevel_page_bwps-content_directory' );

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

			die( var_dump( $input ) );

			$input['enabled'] = intval( $input['enabled'] == 1 ? 1 : 0 );

			$input['type'] = intval( $input['type'] == 1 ? 1 : 2 );

			//we don't need to process this again if it is a multisite installation
			if ( ! is_multisite() ) {

				$input['start'] = strtotime( $input['start']['date'] . ' ' . $input['start']['hour'] . ':' . $input['start']['minute'] . ' ' . $input['start']['sel'] );
				$input['end']   = strtotime( $input['end']['date'] . ' ' . $input['end']['hour'] . ':' . $input['end']['minute'] . ' ' . $input['end']['sel'] );

			}

			if ( $this->check_away( true, $input ) === true ) {

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
		 * Start the Content Directory module
		 *
		 * @param  Ithemes_BWPS_Core $core Instance of core plugin class
		 *
		 * @return BWPS_content_directory                The instance of the BWPS_content_directory class
		 */
		public static function start( $core ) {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}