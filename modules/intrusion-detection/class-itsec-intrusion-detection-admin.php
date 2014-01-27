<?php

if ( ! class_exists( 'ITSEC_Intrusion_Detection_Admin' ) ) {

	class ITSEC_Intrusion_Detection_Admin {

		private static $instance = null;

		private
			$default_white_list,
			$settings,
			$core,
			$module,
			$page;

		private function __construct( $core, $module ) {

			$this->core     = $core;
			$this->module   = $module;
			$this->settings = get_site_option( 'itsec_intrusion_detection' );

			$this->default_white_list = array(
				'/favicon.ico',
				'/robots.txt',
				'/apple-touch-icon.png',
				'/apple-touch-icon-precomposed.png',
			);

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

			global $itsec_logger;

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

			//Don't attempt to display logs if brute force isn't enabled
			if ( isset( $this->settings['four_oh_four-enabled'] ) && $this->settings['four_oh_four-enabled'] === true ) {

				$itsec_logger->add_meta_box(
					'intrusion_detection',
					'four_oh_four',
					__( '404 Errors Found', 'ithemes-security' ),
					array( $this, 'four_oh_four_logs_metabox' )
				);

			}

			//Don't attempt to display file change logs if brute force isn't enabled
			if ( isset( $this->settings['file_change-enabled'] ) && $this->settings['file_change-enabled'] === true ) {

				$itsec_logger->add_meta_box(
					'intrusion_detection',
					'file_change',
					__( 'File Change History', 'ithemes-security' ),
					array( $this, 'file_change_logs_metabox' )
				);

			}

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

			global $itsec_globals, $itsec_lib;

			if ( strpos( get_current_screen()->id, 'security_page_toplevel_page_itsec-intrusion_detection' ) !== false ) {

				wp_enqueue_script( 'itsec_intrusion_detection_js', $itsec_globals['plugin_url'] . 'modules/intrusion-detection/js/admin-intrusion-detection.js', 'jquery', $itsec_globals['plugin_build'] );
				wp_localize_script(
					'itsec_intrusion_detection_js',
					'itsec_intrusion_detection',
					array(
						'mem_limit' => $itsec_lib->get_memory_limit(),
						'text'      => __( 'Warning: Your server has less than 128MB of RAM dedicated to PHP. If you have many files in your installation or a lot of active plugins activating this feature may result in your site becoming disabled with a memory error. See the plugin homepage for more information.', 'ithemes-security' ),
						'plug_path' => $itsec_globals['plugin_url'],
						'ABSPATH'   => ABSPATH,
					)
				);

				wp_enqueue_script( 'itsec_jquery_filetree_script', $itsec_globals['plugin_url'] . 'modules/intrusion-detection/filetree/jqueryFileTree.js', 'jquery', '1.01' );

				wp_register_style( 'itsec_jquery_filetree_style', $itsec_globals['plugin_url'] . 'modules/intrusion-detection/filetree/jQueryFileTree.css' ); //add multi-select css
				wp_enqueue_style( 'itsec_jquery_filetree_style' );

				wp_register_style( 'itsec_intrusion_detection_css', $itsec_globals['plugin_url'] . 'modules/intrusion-detection/css/admin-intrusion-detection.css' ); //add multi-select css
				wp_enqueue_style( 'itsec_intrusion_detection_css' );

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

			$link = 'admin.php?page=toplevel_page_itsec-intrusion_detection';

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
		 * echos Email File Change Notifications Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function file_change_email( $args ) {

			if ( isset( $this->settings['file_change-email'] ) && $this->settings['file_change-email'] === false ) {
				$email = 0;
			} else {
				$email = 1;
			}

			$content = '<input type="checkbox" id="itsec_intrusion_detection_file_change_email" name="itsec_intrusion_detection[file_change-email]" value="1" ' . checked( 1, $email, false ) . '/>';
			$content .= '<label for="itsec_intrusion_detection_file_change_email"> ' . __( 'Email file change notifications', 'ithemes-security' ) . '</label>';
			$content .= '<p>' . __( 'If checked a notification will be sent to all emails set to receive notifications on the global settings page.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Enable File Change Detection Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function file_change_enabled( $args ) {

			if ( isset( $this->settings['file_change-enabled'] ) && $this->settings['file_change-enabled'] === true ) {
				$enabled = 1;
			} else {
				$enabled = 0;
			}

			$content = '<input type="checkbox" id="itsec_intrusion_detection_file_change_enabled" name="itsec_intrusion_detection[file_change-enabled]" value="1" ' . checked( 1, $enabled, false ) . '/>';
			$content .= '<label for="itsec_intrusion_detection_file_change_enabled"> ' . __( 'Enable File Change detection', 'ithemes-security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Enable File Change List Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function file_change_list( $args ) {

			if ( isset( $this->settings['file_change-list'] ) && is_array( $this->settings['file_change-list'] ) ) {
				$list = implode( PHP_EOL, $this->settings['file_change-list'] );
			} else {
				$list = '';
			}

			$content = '<div class="file_chooser"><div class="jquery_file_tree"></div></div>';
			$content .= '<div class="list_field">';
			$content .= '<textarea id="itsec_intrusion_detection_file_change_list" name="itsec_intrusion_detection[file_change-list]" wrap="off">' . $list . '</textarea>';
			$content .= '</div>';

			echo $content;

		}

		/**
		 * Render the file change log metabox
		 *
		 * @return void
		 */
		public function file_change_logs_metabox() {

			if ( isset( $_GET['itsec_file_change_details_id'] ) ) {

				global $itsec_logger;

				$event = $itsec_logger->get_events( 'file_change', array( 'log_id' => intval( $_GET['itsec_file_change_details_id'] ) ) );

				$data = maybe_unserialize( $event[0]['log_data'] );

				printf( '<p>%s <strong>%s</strong>.</p><p><a href="%s">%s</a></p>',
				        __( ' Below is the detailed error report for', 'ithemes-security' ),
				        sanitize_text_field( $event[0]['log_date'] ),
				        'admin.php?page=toplevel_page_itsec-intrusion_detection',
				        __( 'Click here to return to the file change summary', 'ithemes-security' )
				);

				printf( '<p><strong>%s:</strong> %d<br /><strong>%s:</strong> %d<br /><strong>%s:</strong> %d<br /><strong>%s:</strong> %d %s<br /></p>',
				        __( 'Files Added', 'ithemes-security' ),
				        isset( $data['added'] ) ? sizeof( $data['added'] ) : 0,
				        __( 'Files Deleted', 'ithemes-security' ),
				        isset( $data['removed'] ) ? sizeof( $data['removed'] ) : 0,
				        __( 'Files Changed', 'ithemes-security' ),
				        isset( $data['changed'] ) ? sizeof( $data['changed'] ) : 0,
				        __( 'Memory Used', 'ithemes-security' ),
				        isset( $data['memory'] ) ? sizeof( $data['memory'] ) : 0,
				        __( 'MB', 'ithemes-security' )
				);

				require( dirname( __FILE__ ) . '/class-itsec-intrusion-detection-log-file-change-added.php' );

				$added_display = new ITSEC_Intrusion_Detection_Log_File_Change_Added();

				$added_display->prepare_data_items( $data );
				$added_display->display();

				require( dirname( __FILE__ ) . '/class-itsec-intrusion-detection-log-file-change-removed.php' );

				$removed_display = new ITSEC_Intrusion_Detection_Log_File_Change_Removed();

				$removed_display->prepare_data_items( $data );
				$removed_display->display();

				require( dirname( __FILE__ ) . '/class-itsec-intrusion-detection-log-file-change-changed.php' );

				$changed_display = new ITSEC_Intrusion_Detection_Log_File_Change_Changed();

				$changed_display->prepare_data_items( $data );
				$changed_display->display();

			} else {

				require( dirname( __FILE__ ) . '/class-itsec-intrusion-detection-log-file-change.php' );

				echo __( 'Below is a summary log of all the file changes recorded for your WordPress site. To get details on a particular item click the title. To adjust logging options visit the global settings page.', 'ithemes-security' );

				$log_display = new ITSEC_Intrusion_Detection_Log_File_Change();

				$log_display->prepare_items();
				$log_display->display();

			}

		}

		/**
		 * echos method Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function file_change_method( $args ) {

			if ( isset( $this->settings['file_change-method'] ) ) {
				$method = $this->settings['file_change-method'];
			} else {
				$method = 1;
			}

			echo '<select id="itsec_intrusion_detection_file_change_method" name="itsec_intrusion_detection[file_change-method]">';

			echo '<option value="1" ' . selected( $method, '1' ) . '>' . __( 'Exclude Selected', 'ithemes-security' ) . '</option>';
			echo '<option value="0" ' . selected( $method, '0' ) . '>' . __( 'Include Selected', 'ithemes-security' ) . '</option>';
			echo '</select><br />';
			echo '<label for="itsec_intrusion_detection_file_change_method"> ' . __( 'Include/Exclude Files', 'ithemes-security' ) . '</label>';
			echo '<p class="description">' . __( 'Select what iThemes Security should exclude files and folders selected or whether the scan should only include files and folders selected.' ) . '</p>';

		}

		/**
		 * echos file change types Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function file_change_types( $args ) {

			if ( isset( $this->settings['file_change-types'] ) && is_array( $this->settings['file_change-types'] ) ) {
				$types = implode( PHP_EOL, $this->settings['file_change-types'] );
			} else {
				$types = implode( PHP_EOL, array( '.jpg', '.jpeg', '.png' ) );
			}

			$content = '<textarea id="itsec_intrusion_detection_file_change_types" name="itsec_intrusion_detection[file_change-types]" wrap="off" cols="20" rows="10">' . $types . '</textarea><br />';
			$content .= '<label for="itsec_intrusion_detection_file_change_types"> ' . __( 'File types listed here will not be checked for changes. While it is possible to change files such as images it is quite rare and nearly all known WordPress attacks exploit php, js and other text files.', 'ithemes-security' ) . '</label>';

			echo $content;

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

			$content = '<input class="small-text" name="itsec_intrusion_detection[four_oh_four-check_period]" id="itsec_intrusion_detection_four_oh_four_check_period" value="' . $check_period . '" type="text"> ';
			$content .= '<label for="itsec_intrusion_detection_four_oh_four_check_period"> ' . __( 'Minutes', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'The number of minutes in which 404 errors should be remembered and counted towards lockouts.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Enable 404 Detection Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function four_oh_four_enabled( $args ) {

			if ( ( get_option( 'permalink_structure' ) == '' || get_option( 'permalink_structure' ) == false ) && ! is_multisite() ) {

				$adminurl = is_multisite() ? admin_url() . 'network/' : admin_url();

				$content = sprintf( '<p class="noPermalinks">%s <a href="%soptions-permalink.php">%s</a> %s</p>', __( 'You must turn on', 'ithemes-security' ), $adminurl, __( 'WordPress permalinks', 'ithemes-security' ), __( 'to use this feature.', 'ithemes-security' ) );

			} else {

				if ( isset( $this->settings['four_oh_four-enabled'] ) && $this->settings['four_oh_four-enabled'] === true ) {
					$enabled = 1;
				} else {
					$enabled = 0;
				}

				$content = '<input type="checkbox" id="itsec_intrusion_detection_four_oh_four_enabled" name="itsec_intrusion_detection[four_oh_four-enabled]" value="1" ' . checked( 1, $enabled, false ) . '/>';
				$content .= '<label for="itsec_intrusion_detection_four_oh_four_enabled"> ' . __( 'Enable 404 detection', 'ithemes-security' ) . '</label>';

			}

			echo $content;

		}

		/**
		 * echos Error Threshold Field
		 *
		 * @param  array $args field arguments
		 *
		 * @return void
		 */
		public function four_oh_four_error_threshold( $args ) {

			if ( isset( $this->settings['four_oh_four-error_threshold'] ) ) {
				$error_threshold = absint( $this->settings['four_oh_four-error_threshold'] );
			} else {
				$error_threshold = 20;
			}

			$content = '<input class="small-text" name="itsec_intrusion_detection[four_oh_four-error_threshold]" id="itsec_intrusion_detection_four_oh_four_error_threshold" value="' . $error_threshold . '" type="text"> ';
			$content .= '<label for="itsec_intrusion_detection_four_oh_four_error_threshold"> ' . __( 'Errors', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'The numbers of errors (within the check period time frame) that will trigger a lockout. Set to zero (0) to record 404 errors without locking out users. This can be useful for troubleshooting content or other errors. The default is 20.', 'ithemes-security' ) . '</p>';

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
		 * Echo the File Change Detection Header
		 */
		public function file_change_header() {

			$content = '<h2 class="settings-section-header">' . __( 'File Change Detection', 'ithemes-security' ) . '</h2>';
			$content .= '<p>' . __( '', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * Render the settings metabox
		 *
		 * @return void
		 */
		public function four_oh_four_logs_metabox() {

			if ( isset( $_GET['itsec_404_details_uri'] ) ) {

				require( dirname( __FILE__ ) . '/class-itsec-intrusion-detection-log-four-oh-four-detail.php' );

				printf( '%s <strong>%s</strong>. <a href="%s">%s</a>',
				        __( ' Below is the detailed error report for', 'ithemes-security' ),
				        sanitize_text_field( $_GET['itsec_404_details_uri'] ),
				        'admin.php?page=toplevel_page_itsec-intrusion_detection',
				        __( 'Click here to return to the 404 summary', 'ithemes-security' )
				);

				$log_display = new ITSEC_Intrusion_Detection_Log_Four_Oh_Four();

			} else {

				require( dirname( __FILE__ ) . '/class-itsec-intrusion-detection-log-four-oh-four.php' );

				echo __( 'Below is a summary log of all the 404 errors on your WordPress site. To get details on a particular item click the title. To adjust logging options visit the global settings page.', 'ithemes-security' );

				$log_display = new ITSEC_Intrusion_Detection_Log_Four_Oh_Four();

			}

			$log_display->prepare_items();
			$log_display->display();

		}

		/**
		 * echos 404 white list field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function four_oh_four_white_list( $args ) {

			if ( isset( $this->settings['four_oh_four-white_list'] ) && is_array( $this->settings['four_oh_four-white_list'] ) ) {
				$white_list = implode( PHP_EOL, $this->settings['four_oh_four-white_list'] );
			} else {
				$white_list = implode( PHP_EOL, $this->default_white_list );
			}

			$content = '<textarea id="itsec_intrusion_detection_four_oh_four_white_list" name="itsec_intrusion_detection[four_oh_four-white_list]" rows="10" cols="50">' . $white_list . '</textarea>';
			$content .= '<p>' . __( 'Use the whitelist above to prevent recording common 404 errors. If you know a common file on your site is missing and you do not want it to count towards a lockout record it here. You must list the full path beginning with the "/"', 'ithemes-security' ) . '</p>';

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

			add_settings_section(
				'intrusion_detection_file_change-enabled',
				__( 'File Change Detection', 'ithemes-security' ),
				array( $this, 'file_change_header' ),
				'security_page_toplevel_page_itsec-intrusion_detection'
			);

			add_settings_section(
				'intrusion_detection_file_change-settings',
				__( 'File Change Detection Settings', 'ithemes-security' ),
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

			add_settings_field(
				'itsec_intrusion_detection[four_oh_four-check_period]',
				__( 'Minutes to Remember 404 Error (Check Period)', 'ithemes-security' ),
				array( $this, 'four_oh_four_check_period' ),
				'security_page_toplevel_page_itsec-intrusion_detection',
				'intrusion_detection_four_oh_four-settings'
			);

			add_settings_field(
				'itsec_intrusion_detection[four_oh_four-error_threshold]',
				__( 'Error Threshold', 'ithemes-security' ),
				array( $this, 'four_oh_four_error_threshold' ),
				'security_page_toplevel_page_itsec-intrusion_detection',
				'intrusion_detection_four_oh_four-settings'
			);

			add_settings_field(
				'itsec_intrusion_detection[four_oh_four-white_list]',
				__( '404 File/Folder White List', 'ithemes-security' ),
				array( $this, 'four_oh_four_white_list' ),
				'security_page_toplevel_page_itsec-intrusion_detection',
				'intrusion_detection_four_oh_four-settings'
			);

			//File Change Detection Fields
			add_settings_field(
				'itsec_intrusion_detection[file_change-enabled]',
				__( 'File Change Detection', 'ithemes-security' ),
				array( $this, 'file_change_enabled' ),
				'security_page_toplevel_page_itsec-intrusion_detection',
				'intrusion_detection_file_change-enabled'
			);

			add_settings_field(
				'itsec_intrusion_detection[file_change-method]',
				__( 'Include/Exclude Files and Folders', 'ithemes-security' ),
				array( $this, 'file_change_method' ),
				'security_page_toplevel_page_itsec-intrusion_detection',
				'intrusion_detection_file_change-settings'
			);

			add_settings_field(
				'itsec_intrusion_detection[file_change-list]',
				__( 'Files and Folders List', 'ithemes-security' ),
				array( $this, 'file_change_list' ),
				'security_page_toplevel_page_itsec-intrusion_detection',
				'intrusion_detection_file_change-settings'
			);

			add_settings_field(
				'itsec_intrusion_detection[file_change-types]',
				__( 'Ignore File Types', 'ithemes-security' ),
				array( $this, 'file_change_types' ),
				'security_page_toplevel_page_itsec-intrusion_detection',
				'intrusion_detection_file_change-settings'
			);

			add_settings_field(
				'itsec_intrusion_detection[file_change-email]',
				__( 'Email File Change Notifications', 'ithemes-security' ),
				array( $this, 'file_change_email' ),
				'security_page_toplevel_page_itsec-intrusion_detection',
				'intrusion_detection_file_change-settings'
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

			global $itsec_current_time;

			$type    = 'updated';
			$message = __( 'Settings Updated', 'ithemes-security' );

			//process brute force settings
			$input['four_oh_four-enabled']         = ( isset( $input['four_oh_four-enabled'] ) && intval( $input['four_oh_four-enabled'] == 1 ) ? true : false );
			$input['four_oh_four-check_period']    = isset( $input['four_oh_four-check_period'] ) ? absint( $input['four_oh_four-check_period'] ) : 5;
			$input['four_oh_four-error_threshold'] = isset( $input['four_oh_four-error_threshold'] ) ? absint( $input['four_oh_four-error_threshold'] ) : 20;

			if ( isset ( $input['four_oh_four-white_list'] ) ) {

				$raw_paths  = explode( PHP_EOL, $input['four_oh_four-white_list'] );
				$good_paths = array();

				foreach ( $raw_paths as $path ) {

					$path = sanitize_text_field( trim( $path ) );

					if ( $path[0] != '/' ) {
						$path = '/' . $path;
					}

					if ( strlen( $path ) > 1 ) {
						$good_paths[] = $path;
					}

				}

				$input['four_oh_four-white_list'] = $good_paths;

			} else {

				$input['four_oh_four-white_list'] = array();

			}

			//File Change Detection Fields
			$input['file_change-enabled'] = ( isset( $input['file_change-enabled'] ) && intval( $input['file_change-enabled'] == 1 ) ? true : false );
			$input['file_change-method']  = ( isset( $input['file_change-method'] ) && intval( $input['file_change-method'] == 1 ) ? true : false );
			$input['file_change-email']   = ( isset( $input['file_change-email'] ) && intval( $input['file_change-email'] == 1 ) ? true : false );

			$file_list = explode( PHP_EOL, $input['file_change-list'] );

			$good_files = array();

			foreach ( $file_list as $file ) {
				$good_files[] = sanitize_text_field( $file );
			}

			$input['file_change-list'] = $good_files;

			$file_types = explode( PHP_EOL, $input['file_change-types'] );

			$good_types = array();

			foreach ( $file_types as $type ) {

				$good_type = sanitize_text_field( '.' . str_replace( '.', '', $type ) );

				$good_types[] = sanitize_text_field( $good_type );
			}

			$input['file_change-types'] = $good_types;

			$input['file_change-last_run'] = strtotime( date( 'F jS, Y', $itsec_current_time ) );

			add_settings_error( 'itsec_admin_notices', esc_attr( 'settings_updated' ), $message, $type );

			return $input;

		}

		/**
		 * Prepare and save options in network settings
		 *
		 * @return void
		 */
		public function save_network_options() {

			$settings['four_oh_four-enabled']         = ( isset( $_POST['itsec_intrusion_detection']['four_oh_four-enabled'] ) && intval( $_POST['itsec_intrusion_detection']['four_oh_four-enabled'] == 1 ) ? true : false );
			$settings['four_oh_four-check_period']    = isset( $_POST['itsec_intrusion_detection']['four_oh_four-check_period'] ) ? absint( $_POST['itsec_intrusion_detection']['four_oh_four-check_period'] ) : 5;
			$settings['four_oh_four-error_threshold'] = isset( $_POST['itsec_intrusion_detection']['four_oh_four-error_threshold'] ) ? absint( $_POST['itsec_intrusion_detection']['four_oh_four-error_threshold'] ) : 20;
			$settings['file_change-enabled']          = ( isset( $_POST['itsec_intrusion_detection']['file_change-enabled'] ) && intval( $_POST['itsec_intrusion_detection']['file_change-enabled'] == 1 ) ? true : false );
			$settings['file_change-method']           = ( isset( $_POST['itsec_intrusion_detection']['file_change-method'] ) && intval( $_POST['itsec_intrusion_detection']['file_change-method'] == 1 ) ? true : false );
			$settings['four_oh_four-email']           = ( isset( $_POST['itsec_intrusion_detection']['four_oh_four-email'] ) && intval( $_POST['itsec_intrusion_detection']['four_oh_four-email'] == 1 ) ? true : false );

		}

		/**
		 * Start the Intrusion Detection Admin Module
		 *
		 * @param Ithemes_ITSEC_Core        $core Instance of core plugin class
		 * @param ITSEC_Intrusion_Detection $core Instance of plugin module class
		 *
		 * @return ITSEC_Intrusion_Detection_Admin                The instance of the ITSEC_Intrusion_Detection_Admin class
		 */
		public static function start( $core, $module ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $core, $module );
			}

			return self::$instance;

		}

	}

}