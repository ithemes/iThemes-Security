<?php

if ( ! class_exists( 'ITSEC_Backup_Admin' ) ) {

	class ITSEC_Backup_Admin {

		private static $instance = null;

		private
			$core,
			$settings;

		private function __construct( $core ) {

			$this->core     = $core;
			$this->settings = get_site_option( 'itsec_backup' );

			add_action( 'itsec_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) ); //enqueue scripts for admin page
			add_filter( 'itsec_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( 'itsec_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu
			add_filter( 'itsec_add_dashboard_status', array( $this, 'dashboard_status' ) ); //add information for plugin status

			//manually save options on multisite
			if ( is_multisite() ) {
				add_action( 'network_admin_edit_itsec_backup', array( $this, 'save_network_options' ) ); //save multisite options
			}

		}

		/**
		 * Add meta boxes to primary options pages
		 *
		 * @return void
		 */
		public function add_admin_meta_boxes() {

			add_meta_box(
				'backup_description',
				__( 'Description', 'ithemes-security' ),
				array( $this, 'add_module_intro' ),
				'security_page_toplevel_page_itsec-backup',
				'normal',
				'core'
			);

			add_meta_box(
				'backup_options',
				__( 'Configure Database Backups', 'ithemes-security' ),
				array( $this, 'metabox_advanced_settings' ),
				'security_page_toplevel_page_itsec-backup',
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

			$tabs[$this->page] = __( 'Backups', 'ithemes-security' );

			return $tabs;

		}

		/**
		 * Build and echo the away mode description
		 *
		 * @return void
		 */
		public function add_module_intro( $screen ) {

			$content = '<p>' . __( 'While this plugin goes a long way to helping secure your website nothing can give you a 100% guarantee that your site will not be the victim of an attack. When something goes wrong one of the easiest ways of getting your site back is to restore the database from a backup and replace the files with fresh ones. Use the button below to create a full backup of your database for this purpose. You can also schedule automated backups and download or delete previous backups.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * Add Files Admin Javascript
		 *
		 * @return void
		 */
		public function admin_script() {

			global $itsec_globals;

			if ( strpos( get_current_screen()->id, 'security_page_toplevel_page_itsec-backup' ) !== false ) {

				wp_enqueue_script( 'itsec_backup_js', $itsec_globals['plugin_url'] . 'modules/intrusion-detection/js/admin-intrusion-detection.js', 'jquery', $itsec_globals['plugin_build'] );
				wp_enqueue_script( 'itsec_backup_jquery_filetree', $itsec_globals['plugin_url'] . 'modules/intrusion-detection/filetree/jqueryFileTree.js', 'jquery', $itsec_globals['plugin_build'] );

			}

		}

		/**
		 * Register subpage for Away Mode
		 *
		 * @param array $available_pages array of ITSEC settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $itsec_globals;

			$this->page = $available_pages[0] . '-backup';

			$available_pages[] = add_submenu_page(
				'itsec',
				__( 'Backup', 'ithemes-security' ),
				__( 'Backup', 'ithemes-security' ),
				$itsec_globals['plugin_access_lvl'],
				$available_pages[0] . '-backup',
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

			$link = 'admin.php?page=toplevel_page_itsec-backup';

			if ( $this->settings['four_oh_four-enabled'] === true ) {

				$status_array = 'safe-medium';
				$status       = array( 'text' => __( 'Your site is protecting against bots looking for known vulnerabilities.', 'ithemes-security' ), 'link' => $link, );

			} else {

				$status_array = 'medium';
				$status       = array( 'text' => __( 'Your website is not protecting against bots looking for known vulnerabilities. Consider turning on 404 protection.', 'ithemes-security' ), 'link' => $link, );

			}

			array_push( $statuses[$status_array], $status );

			return $statuses;

		}

		/**
		 * Empty callback function
		 */
		public function empty_callback_function() {
		}

		/**
		 * echos Check Period Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function four_oh_four_check_period( $args ) {

			if ( isset( $this->settings['four_oh_four-check_period'] ) ) {
				$check_period = absint( $this->settings['four_oh_four-check_period'] );
			} else {
				$check_period = 5;
			}

			$content = '<input class="small-text" name="itsec_backup[four_oh_four-check_period]" id="itsec_backup_four_oh_four_check_period" value="' . $check_period . '" type="text"> ';
			$content .= '<label for="itsec_backup_four_oh_four_check_period"> ' . __( 'Minutes', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'The number of minutes in which 404 errors should be remembered and counted towards lockouts.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Enable Backups Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function enabled( $args ) {

			if ( isset( $this->settings['enabled'] ) && $this->settings['enabled'] === true ) {
				$enabled = 1;
			} else {
				$enabled = 0;
			}

			$content = '<input type="checkbox" id="itsec_backup_enabled" name="itsec_backup[enabled]" value="1" ' . checked( 1, $enabled, false ) . '/>';
			$content .= '<label for="itsec_backup_enabled"> ' . __( 'Enable Scheduled Database Backups.', 'ithemes-security' ) . '</label>';

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
				'backup-enabled',
				__( 'Enable Database Backups', 'ithemes-security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_itsec-backup'
			);

			add_settings_section(
				'backup-settings',
				__( '404 Detection Settings', 'ithemes-security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_itsec-backup'
			);

			//404 Detection Fields
			add_settings_field(
				'itsec_backup[enabled]',
				__( 'Enable Scheduled Database Backups', 'ithemes-security' ),
				array( $this, 'enabled' ),
				'security_page_toplevel_page_itsec-backup',
				'backup-enabled'
			);

			//Register the settings field for the entire module
			register_setting(
				'security_page_toplevel_page_itsec-backup',
				'itsec_backup',
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
				$action = 'edit.php?action=itsec_backup';
			} else {
				$action = 'options.php';
			}

			printf( '<form name="%s" method="post" action="%s">', get_current_screen()->id, $action );

			$this->core->do_settings_sections( 'security_page_toplevel_page_itsec-backup', false );

			echo '<p>' . PHP_EOL;

			settings_fields( 'security_page_toplevel_page_itsec-backup' );

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
			$input['enabled']         = ( isset( $input['enabled'] ) && intval( $input['enabled'] == 1 ) ? true : false );

			add_settings_error( 'itsec_admin_notices', esc_attr( 'settings_updated' ), $message, $type );

			return $input;

		}

		/**
		 * Prepare and save options in network settings
		 *
		 * @return void
		 */
		public function save_network_options() {

			$settings['enabled']         = ( isset( $_POST['itsec_backup']['enabled'] ) && intval( $_POST['itsec_backup']['enabled'] == 1 ) ? true : false );

		}

		/**
		 * Start the Intrusion Detection Admin Module
		 *
		 * @param Ithemes_ITSEC_Core $core Instance of core plugin class
		 *
		 * @return ITSEC_Backup_Admin                The instance of the ITSEC_Backup_Admin class
		 */
		public static function start( $core ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}