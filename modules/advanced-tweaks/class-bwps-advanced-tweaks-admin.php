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

			add_action( 'bwps_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( 'bwps_page_top', array( $this, 'add_module_intro' ) ); //add page intro and information
			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) ); //enqueue scripts for admin page
			//add_filter( 'bwps_wp_config_rules', array( $this, 'wp_config_rule' ) ); //build wp_config.php rules
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

			if ( $this->settings['enabled'] === 1 ) {

				$status_array = 'safe-low';
				$status       = array(
					'text' => __( 'You are blocking known bad hosts and agents with the ban users tool.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status       = array(
					'text' => __( 'You are not blocking any users that are known to be a problem. Consider turning on the Ban Users feature.', 'better_wp_security' ),
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
				'bwps_advanced_tweaks[random_version]',
				__( 'Display Random Version', 'better_wp_security' ),
				array( $this, 'advanced_tweaks_wordpress_random_version' ),
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

			if ( isset( $this->settings['file_editor'] ) && $this->settings['file_editor'] === 1 ) {
				$file_editor = 1;
			} else {
				$file_editor = 0;
			}

			$content = '<input type="checkbox" id="bwps_advanced_tweaks_server_file_editor" name="bwps_advanced_tweaks[file_editor]" value="1" ' . checked( 1, $file_editor, false ) . '/>';
			$content .= '<label for="bwps_advanced_tweaks_server_file_editor"> ' . __( 'Disables the file editor for plugins and themes requiring users to have access to the file system to modify files. Once activated you will need to manually edit theme and other files using a tool other than WordPress.', 'better_wp_security' ) . '</label>';

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
		 * Sanitize and validate input
		 *
		 * @param  Array $input array of input fields
		 *
		 * @return Array         Sanitized array
		 */
		public function sanitize_module_input( $input ) {

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
			$input['random_version'] = ( isset( $input['random_version'] ) && intval( $input['random_version'] == 1 ) ? 1 : 0 );

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
			$settings['random_version'] = ( isset( $_POST['bwps_advanced_tweaks']['random_version'] ) && intval( $_POST['bwps_advanced_tweaks']['random_version'] == 1 ) ? 1 : 0 );

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