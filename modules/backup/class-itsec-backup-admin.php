<?php

if ( ! class_exists( 'ITSEC_Backup_Admin' ) ) {

	class ITSEC_Backup_Admin {

		private static $instance = null;

		private
			$core,
			$module,
			$settings;

		private function __construct( $core, $module ) {

			$this->core     = $core;
			$this->module   = $module;
			$this->settings = get_site_option( 'itsec_backup' );

			add_action( 'itsec_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) ); //enqueue scripts for admin page
			add_filter( 'itsec_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( 'itsec_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu
			add_filter( 'itsec_add_dashboard_status', array( $this, 'dashboard_status' ) ); //add information for plugin status

			if ( isset( $_POST['itsec_backup'] ) && $_POST['itsec_backup'] == 'one_time_backup' ) {
				add_action( 'admin_init', array( $this, 'one_time_backup' ) );
			}

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
				'backup_one_time',
				__( 'Make a Database Backup', 'ithemes-security' ),
				array( $this, 'metabox_one_time' ),
				'security_page_toplevel_page_itsec-backup',
				'advanced',
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

				wp_enqueue_script( 'itsec_backup_js', $itsec_globals['plugin_url'] . 'modules/backup/js/admin-backup.js', 'jquery', $itsec_globals['plugin_build'] );
				wp_enqueue_script( 'jquery_multiselect', $itsec_globals['plugin_url'] . 'modules/backup/js/jquery.multi-select.js', 'jquery', $itsec_globals['plugin_build'] );

				wp_register_style( 'itsec_backup_styles', $itsec_globals['plugin_url'] . 'modules/backup/css/multi-select.css' ); //add multi-select css
				wp_enqueue_style( 'itsec_backup_styles' );

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

			if ( class_exists( 'backupbuddy_api0' ) && sizeof( backupbuddy_api0::getSchedules() ) >= 1 ) {

				$status_array = 'medium';
				$status       = array( 'text' => __( 'Your site is performing scheduled database and file backups.', 'ithemes-security' ), 'link' => $link, );


			} elseif ( $this->settings['enabled'] === true ) {

				$status_array = 'medium';
				$status       = array( 'text' => __( 'Your site is performing scheduled database backups but is not backing up files. Consider purchasing or scheduling BackupBuddy to protect your investment.', 'ithemes-security' ), 'link' => $link, );

			} else {

				$status_array = 'high';
				$status       = array( 'text' => __( 'Your site is not performing any scheduled backups.', 'ithemes-security' ), 'link' => $link, );

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
		 * echos Exclude tables Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function exclude( $args ) {

			global $itsec_globals, $wpdb;

			$tables = $wpdb->get_results( 'SHOW TABLES', ARRAY_N );

			$content = '<select multiple="multiple" name="itsec_backup[exclude][]" id="itsec_backup_exclude">';

			foreach ( $tables as $table ) {

				$short_table = substr( $table[0], strlen( $wpdb->prefix ) );

				if ( isset( $this->settings['exclude'] ) && in_array( $short_table, $this->settings['exclude'] ) ) {
					$selected = ' selected';
				} else {
					$selected = '';
				}

				$content .= '<option value="' . $short_table . '"' . $selected . '>' . $table[0] . '</option>';

			}

			$content .= '</select>';
			$content .= '<label for="itsec_backup_exclude"> ' . __( 'Tables with data that does not need to be backed up.', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'Some plugins (such as iThemes Security) can create log files in your database. While these logs might be handy for some functions they can also take up a lot of room and, in some cases, even make backing your database up almost impossible. Select log tables below to exclude their data from the backup. Note the table itself will still be backed up but the data in the table will not be.', 'ithemes-security' ) . '</p>';

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
				'backup-settings-2',
				__( 'Configure Database Backups', 'ithemes-security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_itsec-backup'
			);

			add_settings_section(
				'backup-enabled',
				__( 'Enable Database Backups', 'ithemes-security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_itsec-backup'
			);

			add_settings_section(
				'backup-settings',
				__( 'Backup Schedule Settings', 'ithemes-security' ),
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

			add_settings_field(
				'itsec_backup[interval]',
				__( 'Backup Interval', 'ithemes-security' ),
				array( $this, 'interval' ),
				'security_page_toplevel_page_itsec-backup',
				'backup-settings'
			);

			add_settings_field(
				'itsec_backup[method]',
				__( 'Backup Method', 'ithemes-security' ),
				array( $this, 'method' ),
				'security_page_toplevel_page_itsec-backup',
				'backup-settings-2'
			);

			add_settings_field(
				'itsec_backup[location]',
				__( 'Backup Location', 'ithemes-security' ),
				array( $this, 'location' ),
				'security_page_toplevel_page_itsec-backup',
				'backup-settings-2'
			);

			add_settings_field(
				'itsec_backup[zip]',
				__( 'Compress Backup Files', 'ithemes-security' ),
				array( $this, 'zip' ),
				'security_page_toplevel_page_itsec-backup',
				'backup-settings-2'
			);

			add_settings_field(
				'itsec_backup[exclude]',
				__( 'Exclude Tables', 'ithemes-security' ),
				array( $this, 'exclude' ),
				'security_page_toplevel_page_itsec-backup',
				'backup-settings-2'
			);

			//Register the settings field for the entire module
			register_setting(
				'security_page_toplevel_page_itsec-backup',
				'itsec_backup',
				array( $this, 'sanitize_module_input' )
			);

		}

		/**
		 * echos Backup Interval Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function interval( $args ) {

			if ( isset( $this->settings['interval'] ) ) {
				$interval = absint( $this->settings['interval'] );
			} else {
				$interval = 3;
			}

			$content = '<input class="small-text" name="itsec_backup[interval]" id="itsec_backup_interval" value="' . $interval . '" type="text"> ';
			$content .= '<label for="itsec_backup_interval"> ' . __( 'Days', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'The number of days between database backups.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Backup Location Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function location( $args ) {

			global $itsec_globals;

			if ( isset( $this->settings['location'] ) ) {
				$location = sanitize_text_field( $this->settings['location'] );
			} else {
				$location = $itsec_globals['ithemes_backup_dir'];
			}

			$content = '<input class="large-text" name="itsec_backup[location]" id="itsec_backup_location" value="' . $location . '" type="text">';
			$content .= '<label for="itsec_backup_location"> ' . __( 'The path on your machine where backup files should be stored.', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'This path must be writable by your website. For added security it is recommended you do not include it in your website root folder.', 'ithemes-security' ) . '</p>';

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
		 * Display the form for one-time database backups
		 * 
		 * @return void
		 */
		public function metabox_one_time() {

			$content = '<form method="post" action="">';
			$content .= wp_nonce_field( 'itsec_do_backup','wp_nonce' );
			$content .= '<input type="hidden" name="itsec_backup" value="one_time_backup" />';
			$content .= '<p>' . __( 'Press the button below to create a backup of your WordPress database. If you have "Send Backups By Email" selected in automated backups you will receive an email containing the backup file.', 'ithemes-security' ) . '</p>';
			$content .= '<p class="submit"><input type="submit" class="button-primary" value="' . __( 'Create Database Backup', 'ithemes_security' ) . '" /></p>';
			$content .= '</form>';

			echo $content;
		}

		/**
		 * echos method Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function method( $args ) {

			if ( isset( $this->settings['method'] ) ) {
				$method = $this->settings['method'];
			} else {
				$method = 0;
			}

			echo '<select id="itsec_backup_method" name="itsec_backup[method]">';

			echo '<option value="0" ' . selected( $method, '0' ) . '>' . __( 'Save Locally and Email', 'ithemes-security' ) . '</option>';
			echo '<option value="1" ' . selected( $method, '1' ) . '>' . __( 'Email Only', 'ithemes-security' ) . '</option>';
			echo '<option value="2" ' . selected( $method, '2' ) . '>' . __( 'Save Locally Only', 'ithemes-security' ) . '</option>';
			echo '</select><br />';
			echo '<label for="itsec_backup_method"> ' . __( 'Backup Save Method', 'ithemes-security' ) . '</label>';
			echo '<p class="description">' . __( 'Select what iThemes Security should do with your backup file. You can have it emailed to you, saved locally or both.' ) . '</p>';

		}

		public function one_time_backup() {

			if ( ! wp_verify_nonce( $_POST['wp_nonce'], 'itsec_do_backup' ) ) {
				die( 'Security error!' );
			}

			$this->module->do_backup( true );

		}

		/**
		 * Sanitize and validate input
		 *
		 * @param  Array $input array of input fields
		 *
		 * @return Array         Sanitized array
		 */
		public function sanitize_module_input( $input ) {

			global $itsec_globals;

			$type    = 'updated';
			$message = __( 'Settings Updated', 'ithemes-security' );

			$input['enabled']  = ( isset( $input['enabled'] ) && intval( $input['enabled'] == 1 ) ? true : false );
			$input['interval'] = isset( $input['interval'] ) ? absint( $input['interval'] ) : 3;
			$input['method']   = isset( $input['method'] ) ? intval( $input['method'] ) : 0;
			$input['location'] = isset( $input['location'] ) ? sanitize_text_field( $input['location'] ) : $itsec_globals['location'];
			$input['last_run'] = isset( $this->settings['last_run'] ) ? $this->settings['last_run'] : 0;



			if ( $input['location'] != $itsec_globals['ithemes_backup_dir'] ) {
				$good_path = $itsec_lib->validate_path( $input['location'] );
			} else {
				$good_path = true;
			}

			if ( $good_path !== true ) {

				$type            = 'error';
				$message         = __( 'The file path entered does not appear to be valid. Please ensure it exists and that WordPress can write to it. ', 'ithemes-security' );
				$input['method'] = 2;

			}

			$input['zip']  = ( isset( $input['zip'] ) && intval( $input['zip'] == 1 ) ? true : false );

			add_settings_error( 'itsec_admin_notices', esc_attr( 'settings_updated' ), $message, $type );

			return $input;

		}

		/**
		 * Prepare and save options in network settings
		 *
		 * @return void
		 */
		public function save_network_options() {

			$settings['enabled']  = ( isset( $_POST['itsec_backup']['enabled'] ) && intval( $_POST['itsec_backup']['enabled'] == 1 ) ? true : false );
			$settings['interval'] = isset( $_POST['itsec_backup']['interval'] ) ? absint( $_POST['itsec_backup']['interval'] ) : 3;
			$settings['method']   = isset( $_POST['itsec_backup']['method'] ) ? intval( $_POST['itsec_backup']['method'] ) : 0;
			$settings['location'] = sanitize_text_field( $_POST['itsec_backup']['location'] );
			$settings['last_run'] = isset( $this->settings['last_run'] ) ? $this->settings['last_run'] : 0;
			$settings['zip']  = ( isset( $_POST['itsec_backup']['zip'] ) && intval( $_POST['itsec_backup']['zip'] == 1 ) ? true : false );

		}

		/**
		 * echos Zip Backups Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function zip( $args ) {

			if ( isset( $this->settings['zip'] ) && $this->settings['zip'] === false ) {
				$zip = 0;
			} else {
				$zip = 1;
			}

			$content = '<input type="checkbox" id="itsec_backup_zip" name="itsec_backup[zip]" value="1" ' . checked( 1, $zip, false ) . '/>';
			$content .= '<label for="itsec_backup_zip"> ' . __( 'Zip Database Backups. You may need to turn this off if you are having problems with backups.', 'ithemes-security' ) . '</label>';

			echo $content;

		}

		/**
		 * Start the Intrusion Detection Admin Module
		 *
		 * @param Ithemes_ITSEC_Core $core Instance of core plugin class
		 * @param ITSEC_Backup $module Instance of backup module class
		 *
		 * @return ITSEC_Backup_Admin                The instance of the ITSEC_Backup_Admin class
		 */
		public static function start( $core, $module ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $core, $module );
			}

			return self::$instance;

		}

	}

}