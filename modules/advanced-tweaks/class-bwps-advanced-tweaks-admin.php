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

			add_filter( 'bwps_file_rules', array( $this, 'build_rewrite_rules' ) );
			add_filter( 'bwps_file_rules', array( $this, 'build_wpconfig_rules' ) );

			add_action( 'bwps_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( 'bwps_page_top', array( $this, 'add_module_intro' ) ); //add page intro and information
			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) ); //enqueue scripts for admin page
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

			if ( $this->settings['protect_files'] === 1 ) {

				$status_array = 'safe-medium';
				$status       = array(
					'text' => __( 'You are protecting common WordPress files from access.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'medium';
				$status       = array(
					'text' => __( 'You are not protecting common WordPress files from access. Click here to protect WordPress files.', 'better_wp_security' ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['directory_browsing'] === 1 ) {

				$status_array = 'safe-low';
				$status       = array(
					'text' => __( 'You have successfully disabled directory browsing on your site.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status       = array(
					'text' => __( 'You have not disabled directory browsing on your site. Click here to prevent a user from seeing every file present in your WordPress site.', 'better_wp_security' ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['request_methods'] === 1 ) {

				$status_array = 'safe-low';
				$status       = array(
					'text' => __( 'You are blocking HTTP request methods you do not need.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status       = array(
					'text' => __( 'You are not blocking HTTP request methods you do not need. Click here to block extra HTTP request methods that WordPress should not normally need.', 'better_wp_security' ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['suspicious_query_strings'] === 1 ) {

				$status_array = 'safe-medium';
				$status       = array(
					'text' => __( 'Your WordPress site is blocking suspicious looking information in the URL.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'medium';
				$status       = array(
					'text' => __( 'Your WordPress site is not blocking suspicious looking information in the URL. Click here to block users from trying to execute code that they should not be able to execute.', 'better_wp_security' ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['non_english_characters'] === 1 ) {

				$status_array = 'safe-low';
				$status       = array(
					'text' => __( 'Your WordPress site is blocking non-english characters in the URL.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status       = array(
					'text' => __( 'Your WordPress site is not blocking non-english characters in the URL. Click here to fix this.', 'better_wp_security' ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['long_url_strings'] === 1 ) {

				$status_array = 'safe-low';
				$status       = array(
					'text' => __( 'Your installation does not accept long URLs.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status       = array(
					'text' => __( 'Your installation accepts long (over 255 character) URLS. This can lead to vulnerabilities. Click here to fix this.', 'better_wp_security' ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['write_permissions'] === 1 ) {

				$status_array = 'safe-low';
				$status       = array(
					'text' => __( 'Wp-config.php and .htacess are not writeable.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status       = array(
					'text' => __( 'Wp-config.php and .htacess are writeable. This can lead to vulnerabilities. Click here to fix this.', 'better_wp_security' ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['generator_tag'] === 1 ) {

				$status_array = 'safe-low';
				$status       = array(
					'text' => __( 'Your WordPress installation is not publishing its version number to the world.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status       = array(
					'text' => __( 'Your WordPress installation is publishing its version number to the world. Click here to fix this.', 'better_wp_security' ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['wlwmanifest_header'] === 1 ) {

				$status_array = 'safe-low';
				$status       = array(
					'text' => __( 'Your WordPress installation is not publishing the Windows Live Writer header.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status       = array(
					'text' => __( 'Your WordPress installation is publishing the Windows Live Writer header. Click here to fix this.', 'better_wp_security' ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['edituri_header'] === 1 ) {

				$status_array = 'safe-low';
				$status       = array(
					'text' => __( 'Your WordPress installation is not publishing the really simple discovery header.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status       = array(
					'text' => __( 'Your WordPress installation is publishing the really simple discovery header. Click here to fix this.', 'better_wp_security' ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['theme_updates'] === 1 ) {

				$status_array = 'safe-low';
				$status       = array(
					'text' => __( 'Your WordPress installation is not telling users who cannot update themes about theme updates.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status       = array(
					'text' => __( 'Your WordPress installation is telling users who cannot update themes about theme updates. Click here to fix this.', 'better_wp_security' ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['plugin_updates'] === 1 ) {

				$status_array = 'safe-low';
				$status       = array(
					'text' => __( 'Your WordPress installation is not telling users who cannot update plugins about plugin updates.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status       = array(
					'text' => __( 'Your WordPress installation is telling users who cannot update plugins about plugin updates. Click here to fix this.', 'better_wp_security' ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['core_updates'] === 1 ) {

				$status_array = 'safe-low';
				$status       = array(
					'text' => __( 'Your WordPress installation is not telling users who cannot update WordPress core about WordPress core updates.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status       = array(
					'text' => __( 'Your WordPress installation is telling users who cannot update WordPress core about WordPress core updates. Click here to fix this.', 'better_wp_security' ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['comment_spam'] === 1 ) {

				$status_array = 'safe-low';
				$status       = array(
					'text' => __( 'Your WordPress installation is not allowing users without a user agent to post comments.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status       = array(
					'text' => __( 'Your WordPress installation is allowing users without a user agent to post comments. Fix this to reduce comment spam.', 'better_wp_security' ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['random_version'] === 1 ) {

				$status_array = 'safe-low';
				$status       = array(
					'text' => __( 'Version information is obscured to all non admin users.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status       = array(
					'text' => __( 'Users may still be able to get version information from various plugins and themes. Click here to fix this.', 'better_wp_security' ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT === true ) {

				$status_array = 'safe-low';
				$status       = array(
					'text' => __( 'Users cannot edit plugin and themes files directly from within the WordPress Dashboard.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status       = array(
					'text' => __( 'Users can edit plugin and themes files directly from within the WordPress Dashboard. Click here to fix this.', 'better_wp_security' ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['disable_xmlrpc'] === 1 ) {

				$status_array = 'safe-low';
				$status       = array(
					'text' => __( 'XML-RPC is not available on your WordPress installation.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status       = array(
					'text' => __( 'XML-RPC is available on your WordPress installation. Attackers can use this feature to attack your site. Click here to disable access to XML-RPC.', 'better_wp_security' ),
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

			//Add Settings sections
			add_settings_section(
				'advanced_tweaks_enabled',
				__( 'Enable Advanced Tweaks', 'better_wp_security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_bwps-advanced_tweaks'
			);

			add_settings_section(
				'advanced_tweaks_server',
				__( 'Configure Server Tweaks', 'better_wp_security' ),
				array( $this, 'server_tweaks_intro' ),
				'security_page_toplevel_page_bwps-advanced_tweaks'
			);

			add_settings_section(
				'advanced_tweaks_wordpress',
				__( 'Configure WordPress Tweaks', 'better_wp_security' ),
				array( $this, 'wordpress_tweaks_intro' ),
				'security_page_toplevel_page_bwps-advanced_tweaks'
			);

			//Add settings fields
			add_settings_field(
				'bwps_advanced_tweaks[enabled]',
				__( 'Enable Advanced Security Tweaks', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_enabled' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_enabled'
			);

			add_settings_field(
				'bwps_advanced_tweaks[protect_files]',
				__( 'Protect System Files', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_server_protect_files' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_server'
			);

			add_settings_field(
				'bwps_advanced_tweaks[directory_browsing]',
				__( 'Disable Directory Browsing', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_server_directory_browsing' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_server'
			);

			add_settings_field(
				'bwps_advanced_tweaks[request_methods]',
				__( 'Filter Request Methods', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_server_request_methods' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_server'
			);

			add_settings_field(
				'bwps_advanced_tweaks[suspicious_query_strings]',
				__( 'Filter Suspicious Query Strings', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_server_suspicious_query_strings' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_server'
			);

			add_settings_field(
				'bwps_advanced_tweaks[non_english_characters]',
				__( 'Filter Non-English Characters', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_server_non_english_characters' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_server'
			);

			add_settings_field(
				'bwps_advanced_tweaks[long_url_strings]',
				__( 'Filter Long URL Strings', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_server_long_url_strings' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_server'
			);

			add_settings_field(
				'bwps_advanced_tweaks[write_permissions]',
				__( 'Remove File Writing Permissions', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_server_write_permissions' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_server'
			);

			add_settings_field(
				'bwps_advanced_tweaks[generator_tag]',
				__( 'Remove WordPress Generator Meta Tag', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_wordpress_generator_tag' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_wordpress'
			);

			add_settings_field(
				'bwps_advanced_tweaks[wlwmanifest_header]',
				__( 'Remove Windows Live Writer Header', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_wordpress_wlwmanifest_header' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_wordpress'
			);

			add_settings_field(
				'bwps_advanced_tweaks[edituri_header]',
				__( 'Remove EditURI Header', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_wordpress_edituri_header' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_wordpress'
			);

			add_settings_field(
				'bwps_advanced_tweaks[theme_updates]',
				__( 'Hide Theme Update Notifications', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_wordpress_theme_updates' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_wordpress'
			);

			add_settings_field(
				'bwps_advanced_tweaks[plugin_updates]',
				__( 'Hide Plugin Update Notifications', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_wordpress_plugin_updates' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_wordpress'
			);

			add_settings_field(
				'bwps_advanced_tweaks[core_updates]',
				__( 'Hide Core Update Notifications', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_wordpress_core_updates' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_wordpress'
			);

			add_settings_field(
				'bwps_advanced_tweaks[comment_spam]',
				__( 'Reduce Comment Spam', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_wordpress_comment_spam' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_wordpress'
			);

			add_settings_field(
				'bwps_advanced_tweaks[random_version]',
				__( 'Display Random Version', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_wordpress_random_version' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_wordpress'
			);

			add_settings_field(
				'bwps_advanced_tweaks[file_editor]',
				__( 'Disable File Editor', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_wordpress_file_editor' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_wordpress'
			);

			add_settings_field(
				'bwps_advanced_tweaks[disable_xmlrpc]',
				__( 'Disable XML-RPC', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_wordpress_disable_xmlrpc' ),
				'security_page_toplevel_page_bwps-advanced_tweaks',
				'advanced_tweaks_wordpress'
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
		public function advanced_tweaks_server_directory_browsing( $args ) {

			if ( isset( $this->settings['directory_browsing'] ) && $this->settings['directory_browsing'] === 1 ) {
				$directory_browsing = 1;
			} else {
				$directory_browsing = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_directory_browsing" name="bwps_advanced_tweaks[directory_browsing]" value="1" ' . checked( 1, $directory_browsing, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_directory_browsing"> ' . __( 'Prevents users from seeing a list of files in a directory when no index file is present.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Filter Request MethodsField
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_server_request_methods( $args ) {

			if ( isset( $this->settings['request_methods'] ) && $this->settings['request_methods'] === 1 ) {
				$request_methods = 1;
			} else {
				$request_methods = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_request_methods" name="bwps_advanced_tweaks[request_methods]" value="1" ' . checked( 1, $request_methods, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_request_methods"> ' . __( 'Filter out hits with the trace, delete, or track request methods.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Filter Suspicious Query Strings Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_server_suspicious_query_strings( $args ) {

			if ( isset( $this->settings['suspicious_query_strings'] ) && $this->settings['suspicious_query_strings'] === 1 ) {
				$suspicious_query_strings = 1;
			} else {
				$suspicious_query_strings = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_suspicious_query_strings" name="bwps_advanced_tweaks[suspicious_query_strings]" value="1" ' . checked( 1, $suspicious_query_strings, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_suspicious_query_strings"> ' . __( 'Filter out suspicious query strings in the URL. These are very often signs of someone trying to gain access to your site but some plugins and themes can also be blocked.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Filter Non-English Characters Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_server_non_english_characters( $args ) {

			if ( isset( $this->settings['non_english_characters'] ) && $this->settings['non_english_characters'] === 1 ) {
				$non_english_characters = 1;
			} else {
				$non_english_characters = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_non_english_characters" name="bwps_advanced_tweaks[non_english_characters]" value="1" ' . checked( 1, $non_english_characters, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_non_english_characters"> ' . __( 'Filter out non-english characters from the query string. This should not be used on non-english sites and only works when "Filter Suspicious Query String" has been selected.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Long URL Strings Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_server_long_url_strings( $args ) {

			if ( isset( $this->settings['long_url_strings'] ) && $this->settings['long_url_strings'] === 1 ) {
				$long_url_strings = 1;
			} else {
				$long_url_strings = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_long_url_strings" name="bwps_advanced_tweaks[long_url_strings]" value="1" ' . checked( 1, $long_url_strings, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_long_url_strings"> ' . __( 'Limits the number of characters that can be sent in the URL. Hackers often take advantage of long URLs to try to inject information into your database.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Remove write permissions Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_server_write_permissions( $args ) {

			if ( isset( $this->settings['write_permissions'] ) && $this->settings['write_permissions'] === 1 ) {
				$write_permissions = 1;
			} else {
				$write_permissions = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_write_permissions" name="bwps_advanced_tweaks[write_permissions]" value="1" ' . checked( 1, $write_permissions, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_write_permissions"> ' . __( 'Prevents scripts and users from being able to write to the wp-config.php file and .htaccess file. Note that in the case of this and many plugins this can be overcome however it still does make the files more secure. Turning this on will set the UNIX file permissions to 0444 on these files and turning it off will set the permissions to 0644.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Remove WordPress Generator Meta Tag Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_wordpress_generator_tag( $args ) {

			if ( isset( $this->settings['generator_tag'] ) && $this->settings['generator_tag'] === 1 ) {
				$generator_tag = 1;
			} else {
				$generator_tag = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_generator_tag" name="bwps_advanced_tweaks[generator_tag]" value="1" ' . checked( 1, $generator_tag, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_generator_tag"> ' . __( 'Removes the <meta name="generator" content="WordPress [version]" /> meta tag from your sites header. This process hides version information from a potential attacker making it more difficult to determine vulnerabilities.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Remove Windows Live Writer Header Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_wordpress_wlwmanifest_header( $args ) {

			if ( isset( $this->settings['wlwmanifest_header'] ) && $this->settings['wlwmanifest_header'] === 1 ) {
				$wlwmanifest_header = 1;
			} else {
				$wlwmanifest_header = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_wlwmanifest_header" name="bwps_advanced_tweaks[wlwmanifest_header]" value="1" ' . checked( 1, $wlwmanifest_header, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_wlwmanifest_header"> ' . __( 'Removes the Windows Live Writer header. This is not needed if you do not use Windows Live Writer or other blogging clients that rely on this file.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Remove EditURI Header Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_wordpress_edituri_header( $args ) {

			if ( isset( $this->settings['edituri_header'] ) && $this->settings['edituri_header'] === 1 ) {
				$edituri_header = 1;
			} else {
				$edituri_header = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_edituri_header" name="bwps_advanced_tweaks[edituri_header]" value="1" ' . checked( 1, $edituri_header, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_edituri_header"> ' . __( 'Removes the RSD (Really Simple Discovery) header. If you don\'t integrate your blog with external XML-RPC services such as Flickr then the "RSD" function is pretty much useless to you.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Hide Theme Update Notifications Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_wordpress_theme_updates( $args ) {

			if ( isset( $this->settings['theme_updates'] ) && $this->settings['theme_updates'] === 1 ) {
				$theme_updates = 1;
			} else {
				$theme_updates = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_theme_updates" name="bwps_advanced_tweaks[theme_updates]" value="1" ' . checked( 1, $theme_updates, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_theme_updates"> ' . __( 'Hides theme update notifications from users who cannot update themes. Please note that this only makes a difference in multi-site installations.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Hide Plugin Update Notifications Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_wordpress_plugin_updates( $args ) {

			if ( isset( $this->settings['plugin_updates'] ) && $this->settings['plugin_updates'] === 1 ) {
				$plugin_updates = 1;
			} else {
				$plugin_updates = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_plugin_updates" name="bwps_advanced_tweaks[plugin_updates]" value="1" ' . checked( 1, $plugin_updates, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_plugin_updates"> ' . __( 'Hides plugin update notifications from users who cannot update plugins. Please note that this only makes a difference in multi-site installations.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Hide Core Update Notifications Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_wordpress_core_updates( $args ) {

			if ( isset( $this->settings['core_updates'] ) && $this->settings['core_updates'] === 1 ) {
				$core_updates = 1;
			} else {
				$core_updates = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_core_updates" name="bwps_advanced_tweaks[core_updates]" value="1" ' . checked( 1, $core_updates, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_core_updates"> ' . __( 'Hides core update notifications from users who cannot update core. Please note that this only makes a difference in multi-site installations.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Reduce Comment Spam Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_wordpress_comment_spam( $args ) {

			if ( isset( $this->settings['comment_spam'] ) && $this->settings['comment_spam'] === 1 ) {
				$comment_spam = 1;
			} else {
				$comment_spam = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_comment_spam" name="bwps_advanced_tweaks[comment_spam]" value="1" ' . checked( 1, $comment_spam, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_comment_spam"> ' . __( 'This option will cut down on comment spam by denying comments from bots with no referrer or without a user-agent identified.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Display Random Version Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_wordpress_random_version( $args ) {

			if ( isset( $this->settings['random_version'] ) && $this->settings['random_version'] === 1 ) {
				$random_version = 1;
			} else {
				$random_version = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_random_version" name="bwps_advanced_tweaks[random_version]" value="1" ' . checked( 1, $random_version, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_random_version"> ' . __( 'Displays a random version number to visitors who are not logged in at all points where version number must be used and removes the version completely from where it can.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Disable File Editor Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_wordpress_file_editor( $args ) {

			if ( isset( $this->settings['file_editor'] ) && $this->settings['file_editor'] === 1 && defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT === true ) {
				$file_editor = 1;
			} else {
				$file_editor = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_file_editor" name="bwps_advanced_tweaks[file_editor]" value="1" ' . checked( 1, $file_editor, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_file_editor"> ' . __( 'Disables the file editor for plugins and themes requiring users to have access to the file system to modify files. Once activated you will need to manually edit theme and other files using a tool other than WordPress.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Disable XML-RPC Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_wordpress_disable_xmlrpc( $args ) {

			if ( isset( $this->settings['disable_xmlrpc'] ) && $this->settings['disable_xmlrpc'] === 1 ) {
				$disable_xmlrpc = 1;
			} else {
				$disable_xmlrpc = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_disable_xmlrpc" name="bwps_advanced_tweaks[disable_xmlrpc]" value="1" ' . checked( 1, $disable_xmlrpc, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_disable_xmlrpc"> ' . __( 'Disables all XML-RPC functionality. XML-RPC is a feature WordPress uses to connect to remote services and is often taken advantage of by attackers.', 'better_wp_security' ) . '</label>';

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
		 * Build wp-config.php rules
		 * 
		 * @param  array $input  options to build rules from
		 * @return array         rules to write
		 */
		public function build_wpconfig_rules( $rules_array, $input = null ) {

			//Get the rules from the database if input wasn't sent
			if ( $input === null ) {
				$input = get_site_option( 'bwps_advanced_tweaks' );
			}

			if ( $input['file_editor'] == 1 && $input['enabled'] == 1 ) {

				$rule[] = array(
					'type'			=> 'add',
					'search_text'	=> '//The entry below were created by Better WP Security to disable the file editor',
					'rule'			=> '//The entry below were created by Better WP Security to disable the file editor',
				);

				$rule[] = array(
					'type'			=> 'add',
					'search_text'	=> 'DISALLOW_FILE_EDIT',
					'rule'			=> "define( 'DISALLOW_FILE_EDIT', true );",
				);

			} else {

				$rule[] = array(
					'type'			=> 'delete',
					'search_text'	=> '//The entry below were created by Better WP Security to disable the file editor',
					'rule'			=> false,
				);

				$rule[] = array(
					'type'			=> 'delete',
					'search_text'	=> 'DISALLOW_FILE_EDIT',
					'rule'			=> false,
				);

			}

			$rules_array[] = array(
				'type'	=> 'wpconfig',
				'name'	=> 'Advanced Tweaks',
				'rules'	=> $rule,
			);

			return $rules_array;

		}

		/**
		 * Build rewrite rules
		 * 
		 * @param  array $input  options to build rules from
		 * @return array         rules to write
		 */
		public function build_rewrite_rules( $rules_array, $input = null ) {

			global $bwps_lib;

			$server_type = $bwps_lib->get_server(); //Get the server type to build the right rules

			//Get the rules from the database if input wasn't sent
			if ( $input === null ) {
				$input = get_site_option( 'bwps_advanced_tweaks' );
			}

			$rules = ''; //initialize all rules to blank string

			//don't add any rules if the module hasn't been enabled
			if ( $input['enabled'] == 1 ) {

				//Process Protect Files Rules
				if ( $input['protect_files'] == 1 ) {

					if ( $server_type === 'nginx' ) { //NGINX rules

						$rules .= 
							"\t# " . __( 'Rules to block access to WordPress specific files and wp-includes', 'better-wp-security' ) . PHP_EOL .
							"\tlocation ~ /\.ht { deny all; }" . PHP_EOL .
							"\tlocation ~ wp-config.php { deny all; }" . PHP_EOL .
							"\tlocation ~ readme.html { deny all; }" . PHP_EOL .
							"\tlocation ~ readme.txt { deny all; }" . PHP_EOL .
							"\tlocation ~ /install.php { deny all; }" . PHP_EOL .
							"\tlocation ^wp-includes/(.*).php { deny all }" . PHP_EOL .
	   						"\tlocation ^/wp-admin/includes(.*)$ { deny all }" . PHP_EOL;

					} else { //rules for all other servers

						$rules .= 
							"# " . __( 'Rules to block access to WordPress specific files', 'better-wp-security' ) . PHP_EOL .
							"<files .htaccess>" . PHP_EOL .
								"\tOrder allow,deny" .  PHP_EOL .
								"\tDeny from all" . PHP_EOL .
							"</files>" . PHP_EOL .
							"<files readme.html>" . PHP_EOL .
								"\tOrder allow,deny" . PHP_EOL .
								"\tDeny from all" . PHP_EOL .
							"</files>" . PHP_EOL .
							"<files readme.txt>" . PHP_EOL .
								"\tOrder allow,deny" . PHP_EOL .
								"\tDeny from all" . PHP_EOL .
							"</files>" . PHP_EOL .
							"<files install.php>" . PHP_EOL .
								"\tOrder allow,deny" . PHP_EOL .
								"\tDeny from all" . PHP_EOL .
							"</files>" . PHP_EOL .
							"<files wp-config.php>" . PHP_EOL .
								"\tOrder allow,deny" . PHP_EOL .
								"\tDeny from all" . PHP_EOL .
							"</files>" . PHP_EOL;
						
					}

				}

				//Rules to disanle XMLRPC
				if ( $input['disable_xmlrpc'] == 1 ) {

					if ( strlen( $rules ) > 1 ) {
						$rules .= PHP_EOL;
					}

					$rules .= "# " . __( 'Rules to disable XML-RPC', 'better-wp-security' ) . PHP_EOL;

					if ( $server_type === 'nginx' ) { //NGINX rules

						$rules .= 
							"\t# " . __( 'Rules to block access to WordPress specific files and wp-includes', 'better-wp-security' ) . PHP_EOL .
	   						"\tlocation ^/xmlrpc.php { deny all }" . PHP_EOL;

					} else { //rules for all other servers

						$rules .= 
							"<files xmlrpc.php>" . PHP_EOL .
								"\tOrder allow,deny" . PHP_EOL .
								"\tDeny from all" . PHP_EOL .
							"</files>" . PHP_EOL;
						
					}

				}

				//Primary Rules for Directory Browsing
				if ( $input['directory_browsing'] == 1 ) {

					if ( strlen( $rules ) > 1 ) {
						$rules .= PHP_EOL;
					}

					$rules .= "# " . __( 'Rules to disable directory browsing', 'better-wp-security' ) . PHP_EOL;

					if ( $server_type === 'nginx' ) { //NGINX rules

						$rules .= "location / {" . PHP_EOL .
							"\tautoindex off;" . PHP_EOL .
	                		"\troot " . ABSPATH . ";" . PHP_EOL .
							"}" . PHP_EOL;

					} else { //rules for all other servers

						$rules .= 
							"Options -Indexes" . PHP_EOL;
						
					}

				}

				//Apache rewrite rules (and related NGINX rules)
				if ( $input['protect_files'] == 1 || $input['request_methods'] == 1 || $input['suspicious_query_strings'] == 1 || $input['non_english_characters'] == 1 || $input['comment_spam'] == 1 ) {

					if ( strlen( $rules ) > 1 ) {
						$rules .= PHP_EOL;
					}

					//Open Apache rewrite rules
					if ( $server_type !== 'nginx' ) {

						$rules .= 
							"<IfModule mod_rewrite.c>" . PHP_EOL .
							"\tRewriteEngine On" . PHP_EOL;

					}

					//Rewrite Rules for Protect Files
					if ( $input['protect_files'] == 1 && $server_type !== 'nginx' ) {

						$rules .= PHP_EOL . "\t# " . __( 'Rules to protect wp-includes', 'better-wp-security' ) . PHP_EOL;

						$rules .= 
							"\tRewriteRule ^wp-admin/includes/ - [F,L]" . PHP_EOL .
							"\tRewriteRule !^wp-includes/ - [S=3]" . PHP_EOL .
							"\tRewriteCond %{SCRIPT_FILENAME} !^(.*)wp-includes/ms-files.php" . PHP_EOL .
							"\tRewriteRule ^wp-includes/[^/]+\.php$ - [F,L]" . PHP_EOL .
							"\tRewriteRule ^wp-includes/js/tinymce/langs/.+\.php - [F,L]" . PHP_EOL .
							"\tRewriteRule ^wp-includes/theme-compat/ - [F,L]" . PHP_EOL;

					}

					//Apache rewrite rules for disable http methods
					if ( $input['request_methods'] == 1 ) {

						$rules .= PHP_EOL . "\t# " . __( 'Rules to block unneeded HTTP methods', 'better-wp-security' ) . PHP_EOL;

						if ( $server_type === 'nginx' ) { //NGINX rules
							
							$rules .= 
								"\tif (\$request_method ~* \"^(TRACE|DELETE|TRACK)\"){ return 403; }" . PHP_EOL;

						} else { //rules for all other servers
							
							$rules .= 
								"\tRewriteCond %{REQUEST_METHOD} ^(TRACE|DELETE|TRACK) [NC]" . PHP_EOL .
								"\tRewriteRule ^(.*)$ - [F,L]" . PHP_EOL;

						}
						
					}

					//Process suspicious query rules
					if ( $input['suspicious_query_strings'] == 1 ) {

						$rules .= PHP_EOL . "\t# " . __( 'Rules to block suspicious URIs', 'better-wp-security' ) . PHP_EOL;

						if ( $server_type === 'nginx' ) { //NGINX rules

							"\tset \$susquery 0;" . PHP_EOL .
							"\tif (\$args ~* \"\\.\\./\") { set \$susquery 1; }" . PHP_EOL .
							"\tif (\$args ~* \".(bash|git|hg|log|svn|swp|cvs)\") { set \$susquery 1; }" .PHP_EOL .
							"\tif (\$args ~* \"etc/passwd\") { set \$susquery 1; }" . PHP_EOL .
							"\tif (\$args ~* \"boot.ini\") { set \$susquery 1; }" . PHP_EOL .
							"\tif (\$args ~* \"ftp:\") { set \$susquery 1; }" . PHP_EOL .
							"\tif (\$args ~* \"http:\") { set \$susquery 1; }" . PHP_EOL .
							"\tif (\$args ~* \"https:\") { set \$susquery 1; }" . PHP_EOL .
							"\tif (\$args ~* \"(<|%3C).*script.*(>|%3E)\") { set \$susquery 1; }" . PHP_EOL .
							"\tif (\$args ~* \"mosConfig_[a-zA-Z_]{1,21}(=|%3D)\") { set \$susquery 1; }" . PHP_EOL .
							"\tif (\$args ~* \"base64_encode\") { set \$susquery 1; }" . PHP_EOL .
							"\tif (\$args ~* \"(%24&x)\") { set \$susquery 1; }" . PHP_EOL .
							"\tif (\$args ~* \"(\\[|\\]|\\(|\\)|<|>|ê|\\\"|;|\?|\*|=$)\"){ set \$susquery 1; }" . PHP_EOL .
							"\tif (\$args ~* \"(&#x22;|&#x27;|&#x3C;|&#x3E;|&#x5C;|&#x7B;|&#x7C;|%24&x)\"){ set \$susquery 1; }" . PHP_EOL .
							"\tif (\$args ~* \"(127.0)\") { set \$susquery 1; }" . PHP_EOL .
							"\tif (\$args ~* \"(globals|encode|localhost|loopback)\") { set \$susquery 1; }" .PHP_EOL .
							"\tif (\$args ~* \"(request|select|insert|concat|union|declare)\") { set \$susquery 1; }" . PHP_EOL;
							"\tif (\$susquery = 1) { return 403; }" . PHP_EOL;

						} else { //rules for all other servers

							$rules .= 
								"\tRewriteCond %{QUERY_STRING} \.\.\/ [NC,OR]" . PHP_EOL .
								"\tRewriteCond %{QUERY_STRING} ^.*\.(bash|git|hg|log|svn|swp|cvs) [NC,OR]" . PHP_EOL .
								"\tRewriteCond %{QUERY_STRING} etc/passwd [NC,OR]" . PHP_EOL .
								"\tRewriteCond %{QUERY_STRING} boot\.ini [NC,OR]" . PHP_EOL .
								"\tRewriteCond %{QUERY_STRING} ftp\:  [NC,OR]" . PHP_EOL .
								"\tRewriteCond %{QUERY_STRING} http\:  [NC,OR]" . PHP_EOL .
								"\tRewriteCond %{QUERY_STRING} https\:  [NC,OR]" . PHP_EOL .
								"\tRewriteCond %{QUERY_STRING} (\<|%3C).*script.*(\>|%3E) [NC,OR]" . PHP_EOL .
								"\tRewriteCond %{QUERY_STRING} mosConfig_[a-zA-Z_]{1,21}(=|%3D) [NC,OR]" . PHP_EOL . 
								"\tRewriteCond %{QUERY_STRING} base64_encode.*\(.*\) [NC,OR]" . PHP_EOL .
								"\tRewriteCond %{QUERY_STRING} ^.*(\[|\]|\(|\)|<|>|ê|\"|;|\?|\*|=$).* [NC,OR]" . PHP_EOL .
								"\tRewriteCond %{QUERY_STRING} ^.*(&#x22;|&#x27;|&#x3C;|&#x3E;|&#x5C;|&#x7B;|&#x7C;).* [NC,OR]" . PHP_EOL .
								"\tRewriteCond %{QUERY_STRING} ^.*(%24&x).* [NC,OR]" .  PHP_EOL .
								"\tRewriteCond %{QUERY_STRING} ^.*(127\.0).* [NC,OR]" . PHP_EOL .
								"\tRewriteCond %{QUERY_STRING} ^.*(globals|encode|localhost|loopback).* [NC,OR]" . PHP_EOL .
								"\tRewriteCond %{QUERY_STRING} ^.*(request|select|concat|insert|union|declare).* [NC]" . PHP_EOL .
								"\tRewriteCond %{QUERY_STRING} !^loggedout=true" . PHP_EOL .
								"\tRewriteCond %{QUERY_STRING} !^action=rp" . PHP_EOL .
								"\tRewriteCond %{HTTP_COOKIE} !^.*wordpress_logged_in_.*$" . PHP_EOL .
								"\tRewriteCond %{HTTP_REFERER} !^http://maps\.googleapis\.com(.*)$" . PHP_EOL .
								"\tRewriteRule ^(.*)$ - [F,L]" . PHP_EOL;
							
						}

					}

					//Process filtering of foreign characters
					if ( $input['non_english_characters'] == 1 ) {

						$rules .= PHP_EOL . "\t# " . __( 'Rules to block foreign characters in URLs', 'better-wp-security' ) . PHP_EOL;

						if ( $server_type === 'nginx' ) { //NGINX rules

							$rules .= 
								"\tif (\$args ~* \"(%0|%A|%B|%C|%D|%E|%F)\") { return 403; }" . PHP_EOL;

						} else { //rules for all other servers

							$rules .=
								"\tRewriteCond %{QUERY_STRING} ^.*(%0|%A|%B|%C|%D|%E|%F).* [NC]" . PHP_EOL .
								"\tRewriteRule ^(.*)$ - [F,L]" . PHP_EOL;
							
						}

					}

					//Process Comment spam rules
					if ( $input['comment_spam'] == 1 ) {

						$rules .= PHP_EOL . "\t# " . __( 'Rules to help reduce spam', 'better-wp-security' ) . PHP_EOL;

						if ( $server_type === 'nginx' ) { //NGINX rules

							$rules .=
								"\tlocation /wp-comments-post.php {" . PHP_EOL .
			  					"\t\tvalid_referers jetpack.wordpress.com/jetpack-comment/ " . $bwps_lib->get_domain( get_site_url(), false ) . ";" . PHP_EOL .
			  					"\t\tset \$rule_0 0;" . PHP_EOL .
								"\t\tif (\$request_method ~ \"POST\"){ set \$rule_0 1\$rule_0; }" . PHP_EOL .
			 					"\t\tif (\$invalid_referer) { set \$rule_0 2\$rule_0; }" . PHP_EOL .
								"\t\tif (\$http_user_agent ~ \"^$\"){ set \$rule_0 3\$rule_0; }" . PHP_EOL .
								"\t\tif (\$rule_0 = \"3210\") { return 403; }" . PHP_EOL .
								"\t}";

							} else { //rules for all other servers

							$rules .= 
								"\tRewriteCond %{REQUEST_METHOD} POST" . PHP_EOL .
								"\tRewriteCond %{REQUEST_URI} ^(.*)wp-comments-post\.php*" . PHP_EOL .
								"\tRewriteCond %{HTTP_REFERER} !^" . $bwps_lib->get_domain( get_site_url() ) . ".* " . PHP_EOL .
								"\tRewriteCond %{HTTP_REFERER} !^http://jetpack\.wordpress\.com/jetpack-comment/ [OR]" . PHP_EOL .
								"\tRewriteCond %{HTTP_USER_AGENT} ^$" . PHP_EOL . 
								"\tRewriteRule ^(.*)$ - [F,L]" . PHP_EOL;

							}

					}

					//Close Apache Rewrite rules
					if ( $server_type !== 'nginx' ) { //non NGINX rules

						$rules .= 
							"</IfModule>";

					}

				}

			}

			if ( strlen( $rules ) > 0 ) {
				$rules = explode( PHP_EOL, $rules );
			} else {
				$rules = false;
			}

			//create a proper array for writing
			$rules_array[] = array(
				'type'		=> 'htaccess',
				'priority'	=> 10,
				'name'		=> 'Advanced Tweaks',
				'rules'		=> $rules,
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

			global $bwps_lib, $bwps_files;

			$type    = 'updated';
			$message = __( 'Settings Updated', 'better_wp_security' );

			$input['enabled'] = ( isset( $input['enabled'] ) && intval( $input['enabled'] == 1 ) ? 1 : 0 );
			$input['protect_files'] = ( isset( $input['protect_files'] ) && intval( $input['protect_files'] == 1 ) ? 1 : 0 );
			$input['directory_browsing'] = ( isset( $input['directory_browsing'] ) && intval( $input['directory_browsing'] == 1 ) ? 1 : 0 );
			$input['request_methods'] = ( isset( $input['request_methods'] ) && intval( $input['request_methods'] == 1 ) ? 1 : 0 );
			$input['suspicious_query_strings'] = ( isset( $input['suspicious_query_strings'] ) && intval( $input['suspicious_query_strings'] == 1 ) ? 1 : 0 );
			$input['non_english_characters'] = ( isset( $input['non_english_characters'] ) && intval( $input['non_english_characters'] == 1 ) ? 1 : 0 );
			$input['long_url_strings'] = ( isset( $input['long_url_strings'] ) && intval( $input['long_url_strings'] == 1 ) ? 1 : 0 );
			$input['write_permissions'] = ( isset( $input['write_permissions'] ) && intval( $input['write_permissions'] == 1 ) ? 1 : 0 );
			$input['generator_tag'] = ( isset( $input['generator_tag'] ) && intval( $input['generator_tag'] == 1 ) ? 1 : 0 );
			$input['wlwmanifest_header'] = ( isset( $input['wlwmanifest_header'] ) && intval( $input['wlwmanifest_header'] == 1 ) ? 1 : 0 );
			$input['edituri_header'] = ( isset( $input['edituri_header'] ) && intval( $input['edituri_header'] == 1 ) ? 1 : 0 );
			$input['theme_updates'] = ( isset( $input['theme_updates'] ) && intval( $input['theme_updates'] == 1 ) ? 1 : 0 );
			$input['plugin_updates'] = ( isset( $input['plugin_updates'] ) && intval( $input['plugin_updates'] == 1 ) ? 1 : 0 );
			$input['core_updates'] = ( isset( $input['core_updates'] ) && intval( $input['core_updates'] == 1 ) ? 1 : 0 );
			$input['comment_spam'] = ( isset( $input['comment_spam'] ) && intval( $input['comment_spam'] == 1 ) ? 1 : 0 );
			$input['random_version'] = ( isset( $input['random_version'] ) && intval( $input['random_version'] == 1 ) ? 1 : 0 );
			$input['file_editor'] = ( isset( $input['file_editor'] ) && intval( $input['file_editor'] == 1 ) ? 1 : 0 );
			$input['disable_xmlrpc'] = ( isset( $input['disable_xmlrpc'] ) && intval( $input['disable_xmlrpc'] == 1 ) ? 1 : 0 );

			$rules = $this->build_rewrite_rules( array(), $input );
			$bwps_files->set_rewrites( $rules );

			//build and send htaccess rules
			if ( ! $bwps_files->save_rewrites() ) {
				
				$type    = 'error';
				$message = __( 'test WordPress was unable to save the your options to .htaccess. Please check with your server administrator and try again.', 'better_wp_security' );

			}

			$rules = $this->build_wpconfig_rules( array(), $input );

			$bwps_files->set_wpconfig( $rules );

			if ( ! $bwps_files->save_wpconfig() ) {

				$type    = 'error';
				$message = __( 'WordPress was unable to save your options to wp-config.php. Please check with your server administrator and try again.', 'better_wp_security' );

			}

			//Process file writing option
			$config_file = $bwps_lib->get_config();
			$rewrite_file = $bwps_lib->get_htaccess();

			if ( $input['write_permissions'] == 1 ) {

				@chmod( $config_file, 0444 );
				@chmod( $rewrite_file, 0444 );

			} else {

				@chmod( $config_file, 0644 );
				@chmod( $rewrite_file, 0644 );

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

			$settings['enabled'] = ( isset( $_POST['bwps_advanced_tweaks']['enabled'] ) && intval( $_POST['bwps_advanced_tweaks']['enabled'] == 1 ) ? 1 : 0 );
			$settings['protect_files'] = ( isset( $_POST['bwps_advanced_tweaks']['protect_files'] ) && intval( $_POST['bwps_advanced_tweaks']['protect_files'] == 1 ) ? 1 : 0 );
			$settings['directory_browsing'] = ( isset( $_POST['bwps_advanced_tweaks']['directory_browsing'] ) && intval( $_POST['bwps_advanced_tweaks']['directory_browsing'] == 1 ) ? 1 : 0 );
			$settings['request_methods'] = ( isset( $_POST['bwps_advanced_tweaks']['request_methods'] ) && intval( $_POST['bwps_advanced_tweaks']['request_methods'] == 1 ) ? 1 : 0 );
			$settings['suspicious_query_strings'] = ( isset( $_POST['bwps_advanced_tweaks']['suspicious_query_strings'] ) && intval( $_POST['bwps_advanced_tweaks']['suspicious_query_strings'] == 1 ) ? 1 : 0 );
			$settings['non_english_characters'] = ( isset( $_POST['bwps_advanced_tweaks']['non_english_characters'] ) && intval( $_POST['bwps_advanced_tweaks']['non_english_characters'] == 1 ) ? 1 : 0 );
			$settings['long_url_strings'] = ( isset( $_POST['bwps_advanced_tweaks']['long_url_strings'] ) && intval( $_POST['bwps_advanced_tweaks']['long_url_strings'] == 1 ) ? 1 : 0 );
			$settings['write_permissions'] = ( isset( $_POST['bwps_advanced_tweaks']['write_permissions'] ) && intval( $_POST['bwps_advanced_tweaks']['write_permissions'] == 1 ) ? 1 : 0 );
			$settings['generator_tag'] = ( isset( $_POST['bwps_advanced_tweaks']['generator_tag'] ) && intval( $_POST['bwps_advanced_tweaks']['generator_tag'] == 1 ) ? 1 : 0 );
			$settings['wlwmanifest_header'] = ( isset( $_POST['bwps_advanced_tweaks']['wlwmanifest_header'] ) && intval( $_POST['bwps_advanced_tweaks']['wlwmanifest_header'] == 1 ) ? 1 : 0 );
			$settings['edituri_header'] = ( isset( $_POST['bwps_advanced_tweaks']['edituri_header'] ) && intval( $_POST['bwps_advanced_tweaks']['edituri_header'] == 1 ) ? 1 : 0 );
			$settings['theme_updates'] = ( isset( $_POST['bwps_advanced_tweaks']['theme_updates'] ) && intval( $_POST['bwps_advanced_tweaks']['theme_updates'] == 1 ) ? 1 : 0 );
			$settings['plugin_updates'] = ( isset( $_POST['bwps_advanced_tweaks']['plugin_updates'] ) && intval( $_POST['bwps_advanced_tweaks']['plugin_updates'] == 1 ) ? 1 : 0 );
			$settings['core_updates'] = ( isset( $_POST['bwps_advanced_tweaks']['core_updates'] ) && intval( $_POST['bwps_advanced_tweaks']['core_updates'] == 1 ) ? 1 : 0 );
			$settings['comment_spam'] = ( isset( $_POST['bwps_advanced_tweaks']['comment_spam'] ) && intval( $_POST['bwps_advanced_tweaks']['comment_spam'] == 1 ) ? 1 : 0 );
			$settings['random_version'] = ( isset( $_POST['bwps_advanced_tweaks']['random_version'] ) && intval( $_POST['bwps_advanced_tweaks']['random_version'] == 1 ) ? 1 : 0 );
			$settings['file_editor'] = ( isset( $_POST['bwps_advanced_tweaks']['file_editor'] ) && intval( $_POST['bwps_advanced_tweaks']['file_editor'] == 1 ) ? 1 : 0 );
			$settings['disable_xmlrpc'] = ( isset( $_POST['bwps_advanced_tweaks']['disable_xmlrpc'] ) && intval( $_POST['bwps_advanced_tweaks']['disable_xmlrpc'] == 1 ) ? 1 : 0 );

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