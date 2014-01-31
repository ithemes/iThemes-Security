<?php

if ( ! class_exists( 'ITSEC_Advanced_Tweaks_Admin' ) ) {

	class ITSEC_Advanced_Tweaks_Admin {

		private static $instance = null;

		private $settings, $core, $page;

		private function __construct( $core ) {

			global $itsec_allow_tracking;

			$this->core     = $core;
			$this->settings = get_site_option( 'itsec_advanced_tweaks' );

			add_filter( 'itsec_file_rules', array( $this, 'build_rewrite_rules' ) );
			add_filter( 'itsec_file_rules', array( $this, 'build_wpconfig_rules' ) );

			add_action( 'itsec_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) ); //enqueue scripts for admin page
			add_filter( 'itsec_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( 'itsec_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu
			add_filter( 'itsec_add_dashboard_status', array( $this, 'dashboard_status' ) ); //add information for plugin status
			add_filter( 'itsec_add_sidebar_status', array( $this, 'sidebar_status' ) ); //add information for plugin sidebar status

			//manually save options on multisite
			if ( is_multisite() ) {
				add_action( 'network_admin_edit_itsec_advanced_tweaks', array( $this, 'save_network_options' ) ); //save multisite options
			}

			if ( $itsec_allow_tracking === true ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'tracking_script' ) );
			}

		}

		/**
		 * Add meta boxes to primary options pages
		 *
		 * @param array $available_pages array of available page_hooks
		 */
		public function add_admin_meta_boxes( $available_pages ) {

			add_meta_box( 'advanced_tweaks_description', __( 'Description', 'ithemes-security' ), array( $this, 'add_module_intro' ), 'security_page_toplevel_page_itsec-advanced_tweaks', 'normal', 'core' );

			add_meta_box( 'advanced_tweaks_options', __( 'Configure Advanced Security Tweaks', 'ithemes-security' ), array( $this, 'metabox_advanced_settings' ), 'security_page_toplevel_page_itsec-advanced_tweaks', 'advanced', 'core' );

		}

		/**
		 * Add tab to admin area
		 *
		 * @param array $tabs array of tabs
		 *
		 * @return array of tabs
		 */
		public function add_admin_tab( $tabs ) {

			$tabs[$this->page] = __( 'Tweaks', 'ithemes-security' );

			return $tabs;

		}

		/**
		 * Build and echo the away mode description
		 *
		 * @return void
		 */
		public function add_module_intro( $screen ) {

			$content = '<p>' . __( 'These are advanced settings that may be utilized to further strengthen the security of your WordPress site. The reason we list them as advanced though is that each fix, while blocking common forms of attack against your site, can also block legitimate plugins and themes that rely on the same techniques. When turning on the settings below we recommend you enable them 1 by 1 and test your site in between to make sure everything is working as expected.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * Register subpage for Away Mode
		 *
		 * @param array $available_pages array of ITSEC settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $itsec_globals;

			$this->page = $available_pages[0] . '-advanced_tweaks';

			$available_pages[] = add_submenu_page( 'itsec', __( 'Advanced Tweaks', 'ithemes-security' ), __( 'Advanced Tweaks', 'ithemes-security' ), $itsec_globals['plugin_access_lvl'], $available_pages[0] . '-advanced_tweaks', array( $this->core, 'render_page' ) );

			return $available_pages;

		}

		/**
		 * Add Away mode Javascript
		 *
		 * @return void
		 */
		public function admin_script() {

			global $itsec_globals;

			if ( strpos( get_current_screen()->id, 'security_page_toplevel_page_itsec-advanced_tweaks' ) !== false ) {

				wp_enqueue_script( 'itsec_advanced_tweaks_js', $itsec_globals['plugin_url'] . 'modules/advanced-tweaks/js/admin-advanced-tweaks.js', 'jquery', $itsec_globals['plugin_build'] );
				wp_enqueue_script( 'jquery_geturlvar', $itsec_globals['plugin_url'] . 'inc/js/jquery-geturlvar.js', array( 'jquery', 'itsec_advanced_tweaks_js' ), $itsec_globals['plugin_build'] );

			}

		}

		/**
		 * echos Enabled Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_enabled( $args ) {

			if ( isset( $this->settings['enabled'] ) && $this->settings['enabled'] === true ) {
				$enabled = 1;
			} else {
				$enabled = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_enabled" name="itsec_advanced_tweaks[enabled]" value="1" ' . checked( 1, $enabled, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_enabled">' . __( 'Enable Advanced Security Tweaks', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'Remember, some of these tweaks might conflict with other plugins or your theme so test your site after enabling each setting.', 'ithemes-security' ) . '</p>';

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

			if ( isset( $this->settings['directory_browsing'] ) && $this->settings['directory_browsing'] === true ) {
				$directory_browsing = 1;
			} else {
				$directory_browsing = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_server_directory_browsing" name="itsec_advanced_tweaks[directory_browsing]" value="1" ' . checked( 1, $directory_browsing, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_server_directory_browsing">' . __( 'Disable Directory Browsing', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'Prevents users from seeing a list of files in a directory when no index file is present.', 'ithemes-security' ) . '</p>';

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

			if ( isset( $this->settings['long_url_strings'] ) && $this->settings['long_url_strings'] === true ) {
				$long_url_strings = 1;
			} else {
				$long_url_strings = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_server_long_url_strings" name="itsec_advanced_tweaks[long_url_strings]" value="1" ' . checked( 1, $long_url_strings, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_server_long_url_strings">' . __( 'Filter Long URL Strings', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'Limits the number of characters that can be sent in the URL. Hackers often take advantage of long URLs to try to inject information into your database.', 'ithemes-security' ) . '</p>';

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

			if ( isset( $this->settings['non_english_characters'] ) && $this->settings['non_english_characters'] === true ) {
				$non_english_characters = 1;
			} else {
				$non_english_characters = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_server_non_english_characters" name="itsec_advanced_tweaks[non_english_characters]" value="1" ' . checked( 1, $non_english_characters, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_server_non_english_characters">' . __( 'Filter Non-English Characters', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'Filter out non-english characters from the query string. This should not be used on non-english sites and only works when "Filter Suspicious Query String" has been selected.', 'ithemes-security' ) . '</p>';

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

			if ( isset( $this->settings['protect_files'] ) && $this->settings['protect_files'] === true ) {
				$protect_files = 1;
			} else {
				$protect_files = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_server_protect_files" name="itsec_advanced_tweaks[protect_files]" value="1" ' . checked( 1, $protect_files, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_server_protect_files">' . __( 'Protect System Files', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description"> ' . __( 'Prevent public access to readme.html, readme.txt, wp-config.php, install.php, wp-includes, and .htaccess. These files can give away important information on your site and serve no purpose to the public once WordPress has been successfully installed.', 'ithemes-security' ) . '</p>';

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

			if ( isset( $this->settings['request_methods'] ) && $this->settings['request_methods'] === true ) {
				$request_methods = 1;
			} else {
				$request_methods = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_server_request_methods" name="itsec_advanced_tweaks[request_methods]" value="1" ' . checked( 1, $request_methods, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_server_request_methods">' . __( 'Filter Request Methods', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'Filter out hits with the trace, delete, or track request methods.', 'ithemes-security' ) . '</p>';

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

			if ( isset( $this->settings['suspicious_query_strings'] ) && $this->settings['suspicious_query_strings'] === true ) {
				$suspicious_query_strings = 1;
			} else {
				$suspicious_query_strings = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_server_suspicious_query_strings" name="itsec_advanced_tweaks[suspicious_query_strings]" value="1" ' . checked( 1, $suspicious_query_strings, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_server_suspicious_query_strings">' . __( 'Filter Suspicious Query Strings in the URL', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'These are very often signs of someone trying to gain access to your site but some plugins and themes can also be blocked.', 'ithemes-security' ) . '</label>';

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

			if ( isset( $this->settings['write_permissions'] ) && $this->settings['write_permissions'] === true ) {
				$write_permissions = 1;
			} else {
				$write_permissions = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_server_write_permissions" name="itsec_advanced_tweaks[write_permissions]" value="1" ' . checked( 1, $write_permissions, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_server_write_permissions">' . __( 'Remove File Writing Permissions', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'Prevents scripts and users from being able to write to the wp-config.php file and .htaccess file. Note that in the case of this and many plugins this can be overcome however it still does make the files more secure. Turning this on will set the UNIX file permissions to 0444 on these files and turning it off will set the permissions to 0644.', 'ithemes-security' ) . '</p>';

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

			if ( isset( $this->settings['comment_spam'] ) && $this->settings['comment_spam'] === true ) {
				$comment_spam = 1;
			} else {
				$comment_spam = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_server_comment_spam" name="itsec_advanced_tweaks[comment_spam]" value="1" ' . checked( 1, $comment_spam, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_server_comment_spam">' . __( 'Reduce Comment Spam', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'This option will cut down on comment spam by denying comments from bots with no referrer or without a user-agent identified.', 'ithemes-security' ) . '</p>';

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

			if ( isset( $this->settings['core_updates'] ) && $this->settings['core_updates'] === true ) {
				$core_updates = 1;
			} else {
				$core_updates = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_server_core_updates" name="itsec_advanced_tweaks[core_updates]" value="1" ' . checked( 1, $core_updates, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_server_core_updates">' . __( 'Hide Core Update Notifications', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'Hides core update notifications from users who cannot update core. Please note that this only makes a difference in multi-site installations.', 'ithemes-security' ) . '</p>';

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

			if ( isset( $this->settings['disable_xmlrpc'] ) && $this->settings['disable_xmlrpc'] === true ) {
				$disable_xmlrpc = 1;
			} else {
				$disable_xmlrpc = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_server_disable_xmlrpc" name="itsec_advanced_tweaks[disable_xmlrpc]" value="1" ' . checked( 1, $disable_xmlrpc, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_server_disable_xmlrpc">' . __( 'Disable XML-RPC', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'Disables all XML-RPC functionality. XML-RPC is a feature WordPress uses to connect to remote services and is often taken advantage of by attackers.', 'ithemes-security' ) . '</p>';

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

			if ( isset( $this->settings['edituri_header'] ) && $this->settings['edituri_header'] === true ) {
				$edituri_header = 1;
			} else {
				$edituri_header = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_server_edituri_header" name="itsec_advanced_tweaks[edituri_header]" value="1" ' . checked( 1, $edituri_header, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_server_edituri_header">' . __( 'Remove the RSD (Really Simple Discovery) header. ', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'Removes the RSD (Really Simple Discovery) header. If you don\'t integrate your blog with external XML-RPC services such as Flickr then the "RSD" function is pretty much useless to you.', 'ithemes-security' ) . '</p>';

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

			if ( isset( $this->settings['file_editor'] ) && $this->settings['file_editor'] === true && defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT === true ) {
				$file_editor = 1;
			} else {
				$file_editor = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_server_file_editor" name="itsec_advanced_tweaks[file_editor]" value="1" ' . checked( 1, $file_editor, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_server_file_editor">' . __( 'Disable File Editor', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'Disables the file editor for plugins and themes requiring users to have access to the file system to modify files. Once activated you will need to manually edit theme and other files using a tool other than WordPress.', 'ithemes-security' ) . '</p>';

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

			if ( isset( $this->settings['generator_tag'] ) && $this->settings['generator_tag'] === true ) {
				$generator_tag = 1;
			} else {
				$generator_tag = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_server_generator_tag" name="itsec_advanced_tweaks[generator_tag]" value="1" ' . checked( 1, $generator_tag, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_server_generator_tag">' . __( 'Remove WordPress Generator Meta Tag', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'Removes the <code>&lt;meta name="generator" content="WordPress [version]" /&gt;</code></pre> meta tag from your sites header. This process hides version information from a potential attacker making it more difficult to determine vulnerabilities.', 'ithemes-security' ) . '</p>';

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

			if ( isset( $this->settings['plugin_updates'] ) && $this->settings['plugin_updates'] === true ) {
				$plugin_updates = 1;
			} else {
				$plugin_updates = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_server_plugin_updates" name="itsec_advanced_tweaks[plugin_updates]" value="1" ' . checked( 1, $plugin_updates, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_server_plugin_updates">' . __( 'Hide Plugin Update Notifications', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'Hides plugin update notifications from users who cannot update plugins. Please note that this only makes a difference in multi-site installations.', 'ithemes-security' ) . '</p>';

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

			if ( isset( $this->settings['random_version'] ) && $this->settings['random_version'] === true ) {
				$random_version = 1;
			} else {
				$random_version = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_server_random_version" name="itsec_advanced_tweaks[random_version]" value="1" ' . checked( 1, $random_version, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_server_random_version">' . __( 'Display Random Version', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'Displays a random version number to visitors who are not logged in at all points where version number must be used and removes the version completely from where it can.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Replace jQuery Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_wordpress_safe_jquery( $args ) {

			global $itsec_lib;

			if ( isset( $this->settings['safe_jquery'] ) && $this->settings['safe_jquery'] === true ) {
				$safe_jquery = 1;
			} else {
				$safe_jquery = 0;
			}

			$raw_version = get_site_option( 'itsec_jquery_version' );

			if ( $raw_version !== false ) {
				$version = sanitize_text_field( $raw_version );
			} else {
				$version = 'undetermined';
			}

			if ( $itsec_lib->safe_jquery_version() === true ) {
				$color = 'green';
			} else {
				$color = 'red';
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_wordpress_safe_jquery" name="itsec_advanced_tweaks[safe_jquery]" value="1" ' . checked( 1, $safe_jquery, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_wordpress_safe_jquery">' . __( 'Enqueue a safe version of jQuery', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'Remove the existing jQuery version used and replace it with a safe version (the version that comes default with WordPress).', 'ithemes-security' ) . '</p>';

			$content .= '<p class="description" style="color: ' . $color . '">' . __( 'Your current jQuery version is ', 'ithemes-security' ) . $version . '.</p>';
			$content .= sprintf( '<p class="description">%s <a href="%s" target="_blank">%s</a>.</p>', __( 'Note that this only checks the homepage of your site and only for users who are logged in. This is done intentionally to save resources. If you think this is in error ', 'ithemes-security' ), site_url(), __( 'click here to check again.', 'ithemes-security' ) );

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

			if ( isset( $this->settings['theme_updates'] ) && $this->settings['theme_updates'] === true ) {
				$theme_updates = 1;
			} else {
				$theme_updates = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_server_theme_updates" name="itsec_advanced_tweaks[theme_updates]" value="1" ' . checked( 1, $theme_updates, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_server_theme_updates">' . __( 'Hide Theme Update Notifications', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'Hides theme update notifications from users who cannot update themes. Please note that this only makes a difference in multi-site installations.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * echos Disable PHP In Uploads Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function advanced_tweaks_wordpress_uploads_php( $args ) {

			if ( isset( $this->settings['uploads_php'] ) && $this->settings['uploads_php'] === true ) {
				$uploads_php = 1;
			} else {
				$uploads_php = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_server_uploads_php" name="itsec_advanced_tweaks[uploads_php]" value="1" ' . checked( 1, $uploads_php, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_server_uploads_php">' . __( 'Disable PHP in Uploads', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'Disable PHP execution in the uploads directory. This will prevent uploading of malicious scripts to uploads.', 'ithemes-security' ) . '</p>';

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

			if ( isset( $this->settings['wlwmanifest_header'] ) && $this->settings['wlwmanifest_header'] === true ) {
				$wlwmanifest_header = 1;
			} else {
				$wlwmanifest_header = 0;
			}

			$content = '<input type="checkbox" id="itsec_advanced_tweaks_server_wlwmanifest_header" name="itsec_advanced_tweaks[wlwmanifest_header]" value="1" ' . checked( 1, $wlwmanifest_header, false ) . '/>';
			$content .= '<label for="itsec_advanced_tweaks_server_wlwmanifest_header">' . __( 'Remove the Windows Live Writer header. ', 'ithemes-security' ) . '</label>';
			$content .= '<p class="description">' . __( 'This is not needed if you do not use Windows Live Writer or other blogging clients that rely on this file.', 'ithemes-security' ) . '</p>';

			echo $content;

		}

		/**
		 * Build rewrite rules
		 *
		 * @param  array $input options to build rules from
		 *
		 * @return array         rules to write
		 */
		public static function build_rewrite_rules( $rules_array, $input = null ) {

			global $itsec_lib;

			$server_type = $itsec_lib->get_server(); //Get the server type to build the right rules

			//Get the rules from the database if input wasn't sent
			if ( $input === null ) {
				$input = get_site_option( 'itsec_advanced_tweaks' );
			}

			$rules = ''; //initialize all rules to blank string

			//don't add any rules if the module hasn't been enabled
			if ( $input['enabled'] == true ) {

				//Process Protect Files Rules
				if ( $input['protect_files'] == true ) {

					if ( $server_type === 'nginx' ) { //NGINX rules

						$rules .= "\t# " . __( 'Rules to block access to WordPress specific files and wp-includes', 'ithemes-security' ) . PHP_EOL . "\tlocation ~ /\.ht { deny all; }" . PHP_EOL . "\tlocation ~ wp-config.php { deny all; }" . PHP_EOL . "\tlocation ~ readme.html { deny all; }" . PHP_EOL . "\tlocation ~ readme.txt { deny all; }" . PHP_EOL . "\tlocation ~ /install.php { deny all; }" . PHP_EOL . "\tlocation ^wp-includes/(.*).php { deny all }" . PHP_EOL . "\tlocation ^/wp-admin/includes(.*)$ { deny all }" . PHP_EOL;

					} else { //rules for all other servers

						$rules .= "# " . __( 'Rules to block access to WordPress specific files', 'ithemes-security' ) . PHP_EOL . "<files .htaccess>" . PHP_EOL . "\tOrder allow,deny" . PHP_EOL . "\tDeny from all" . PHP_EOL . "</files>" . PHP_EOL . "<files readme.html>" . PHP_EOL . "\tOrder allow,deny" . PHP_EOL . "\tDeny from all" . PHP_EOL . "</files>" . PHP_EOL . "<files readme.txt>" . PHP_EOL . "\tOrder allow,deny" . PHP_EOL . "\tDeny from all" . PHP_EOL . "</files>" . PHP_EOL . "<files install.php>" . PHP_EOL . "\tOrder allow,deny" . PHP_EOL . "\tDeny from all" . PHP_EOL . "</files>" . PHP_EOL . "<files wp-config.php>" . PHP_EOL . "\tOrder allow,deny" . PHP_EOL . "\tDeny from all" . PHP_EOL . "</files>" . PHP_EOL;

					}

				}

				//Rules to disanle XMLRPC
				if ( $input['disable_xmlrpc'] == true ) {

					if ( strlen( $rules ) > 1 ) {
						$rules .= PHP_EOL;
					}

					$rules .= "# " . __( 'Rules to disable XML-RPC', 'ithemes-security' ) . PHP_EOL;

					if ( $server_type === 'nginx' ) { //NGINX rules

						$rules .= "\t# " . __( 'Rules to block access to WordPress specific files and wp-includes', 'ithemes-security' ) . PHP_EOL . "\tlocation ^/xmlrpc.php { deny all }" . PHP_EOL;

					} else { //rules for all other servers

						$rules .= "<files xmlrpc.php>" . PHP_EOL . "\tOrder allow,deny" . PHP_EOL . "\tDeny from all" . PHP_EOL . "</files>" . PHP_EOL;

					}

				}

				//Primary Rules for Directory Browsing
				if ( $input['directory_browsing'] == true ) {

					if ( strlen( $rules ) > 1 ) {
						$rules .= PHP_EOL;
					}

					$rules .= "# " . __( 'Rules to disable directory browsing', 'ithemes-security' ) . PHP_EOL;

					if ( $server_type === 'nginx' ) { //NGINX rules

						$rules .= "location / {" . PHP_EOL . "\tautoindex off;" . PHP_EOL . "\troot " . ABSPATH . ";" . PHP_EOL . "}" . PHP_EOL;

					} else { //rules for all other servers

						$rules .= "Options -Indexes" . PHP_EOL;

					}

				}

				//Apache rewrite rules (and related NGINX rules)
				if ( $input['protect_files'] == true || $input['uploads_php'] == true || $input['request_methods'] == true || $input['suspicious_query_strings'] == true || $input['non_english_characters'] == true || $input['comment_spam'] == true ) {

					if ( strlen( $rules ) > 1 ) {
						$rules .= PHP_EOL;
					}

					//Open Apache rewrite rules
					if ( $server_type !== 'nginx' ) {

						$rules .= "<IfModule mod_rewrite.c>" . PHP_EOL . "\tRewriteEngine On" . PHP_EOL;

					}

					//Rewrite Rules for Protect Files
					if ( $input['protect_files'] == true && $server_type !== 'nginx' ) {

						$rules .= PHP_EOL . "\t# " . __( 'Rules to protect wp-includes', 'ithemes-security' ) . PHP_EOL;

						$rules .= "\tRewriteRule ^wp-admin/includes/ - [F,L]" . PHP_EOL . "\tRewriteRule !^wp-includes/ - [S=3]" . PHP_EOL . "\tRewriteCond %{SCRIPT_FILENAME} !^(.*)wp-includes/ms-files.php" . PHP_EOL . "\tRewriteRule ^wp-includes/[^/]+\.php$ - [F,L]" . PHP_EOL . "\tRewriteRule ^wp-includes/js/tinymce/langs/.+\.php - [F,L]" . PHP_EOL . "\tRewriteRule ^wp-includes/theme-compat/ - [F,L]" . PHP_EOL;

					}

					//Rewrite Rules for Disable PHP in Uploads
					if ( $input['uploads_php'] == true ) {

						$rules .= PHP_EOL . "\t# " . __( 'Rules to prevent php execution in uploads', 'ithemes-security' ) . PHP_EOL;

						if ( $server_type !== 'nginx' ) {

							$rules .= "\tRewriteRule ^(.*)/uploads/(.*).php(.?) - [F,L]" . PHP_EOL;

						} else { //rules for all other servers

							$rules .= "\tlocation ^(.*)/uploads/(.*).php(.?){ deny all }" . PHP_EOL;

						}

					}

					//Apache rewrite rules for disable http methods
					if ( $input['request_methods'] == true ) {

						$rules .= PHP_EOL . "\t# " . __( 'Rules to block unneeded HTTP methods', 'ithemes-security' ) . PHP_EOL;

						if ( $server_type === 'nginx' ) { //NGINX rules

							$rules .= "\tif (\$request_method ~* \"^(TRACE|DELETE|TRACK)\"){ return 403; }" . PHP_EOL;

						} else { //rules for all other servers

							$rules .= "\tRewriteCond %{REQUEST_METHOD} ^(TRACE|DELETE|TRACK) [NC]" . PHP_EOL . "\tRewriteRule ^(.*)$ - [F,L]" . PHP_EOL;

						}

					}

					//Process suspicious query rules
					if ( $input['suspicious_query_strings'] == true ) {

						$rules .= PHP_EOL . "\t# " . __( 'Rules to block suspicious URIs', 'ithemes-security' ) . PHP_EOL;

						if ( $server_type === 'nginx' ) { //NGINX rules

							"\tset \$susquery 0;" . PHP_EOL . "\tif (\$args ~* \"\\.\\./\") { set \$susquery 1; }" . PHP_EOL . "\tif (\$args ~* \".(bash|git|hg|log|svn|swp|cvs)\") { set \$susquery 1; }" . PHP_EOL . "\tif (\$args ~* \"etc/passwd\") { set \$susquery 1; }" . PHP_EOL . "\tif (\$args ~* \"boot.ini\") { set \$susquery 1; }" . PHP_EOL . "\tif (\$args ~* \"ftp:\") { set \$susquery 1; }" . PHP_EOL . "\tif (\$args ~* \"http:\") { set \$susquery 1; }" . PHP_EOL . "\tif (\$args ~* \"https:\") { set \$susquery 1; }" . PHP_EOL . "\tif (\$args ~* \"(<|%3C).*script.*(>|%3E)\") { set \$susquery 1; }" . PHP_EOL . "\tif (\$args ~* \"mosConfig_[a-zA-Z_]{1,21}(=|%3D)\") { set \$susquery 1; }" . PHP_EOL . "\tif (\$args ~* \"base64_encode\") { set \$susquery 1; }" . PHP_EOL . "\tif (\$args ~* \"(%24&x)\") { set \$susquery 1; }" . PHP_EOL . "\tif (\$args ~* \"(\\[|\\]|\\(|\\)|<|>|ê|\\\"|;|\?|\*|=$)\"){ set \$susquery 1; }" . PHP_EOL . "\tif (\$args ~* \"(&#x22;|&#x27;|&#x3C;|&#x3E;|&#x5C;|&#x7B;|&#x7C;|%24&x)\"){ set \$susquery 1; }" . PHP_EOL . "\tif (\$args ~* \"(127.0)\") { set \$susquery 1; }" . PHP_EOL . "\tif (\$args ~* \"(globals|encode|localhost|loopback)\") { set \$susquery 1; }" . PHP_EOL . "\tif (\$args ~* \"(request|select|insert|concat|union|declare)\") { set \$susquery 1; }" . PHP_EOL;
							"\tif (\$susquery = 1) { return 403; }" . PHP_EOL;

						} else { //rules for all other servers

							$rules .= "\tRewriteCond %{QUERY_STRING} \.\.\/ [NC,OR]" . PHP_EOL . "\tRewriteCond %{QUERY_STRING} ^.*\.(bash|git|hg|log|svn|swp|cvs) [NC,OR]" . PHP_EOL . "\tRewriteCond %{QUERY_STRING} etc/passwd [NC,OR]" . PHP_EOL . "\tRewriteCond %{QUERY_STRING} boot\.ini [NC,OR]" . PHP_EOL . "\tRewriteCond %{QUERY_STRING} ftp\:  [NC,OR]" . PHP_EOL . "\tRewriteCond %{QUERY_STRING} http\:  [NC,OR]" . PHP_EOL . "\tRewriteCond %{QUERY_STRING} https\:  [NC,OR]" . PHP_EOL . "\tRewriteCond %{QUERY_STRING} (\<|%3C).*script.*(\>|%3E) [NC,OR]" . PHP_EOL . "\tRewriteCond %{QUERY_STRING} mosConfig_[a-zA-Z_]{1,21}(=|%3D) [NC,OR]" . PHP_EOL . "\tRewriteCond %{QUERY_STRING} base64_encode.*\(.*\) [NC,OR]" . PHP_EOL . "\tRewriteCond %{QUERY_STRING} ^.*(\[|\]|\(|\)|<|>|ê|\"|;|\?|\*|=$).* [NC,OR]" . PHP_EOL . "\tRewriteCond %{QUERY_STRING} ^.*(&#x22;|&#x27;|&#x3C;|&#x3E;|&#x5C;|&#x7B;|&#x7C;).* [NC,OR]" . PHP_EOL . "\tRewriteCond %{QUERY_STRING} ^.*(%24&x).* [NC,OR]" . PHP_EOL . "\tRewriteCond %{QUERY_STRING} ^.*(127\.0).* [NC,OR]" . PHP_EOL . "\tRewriteCond %{QUERY_STRING} ^.*(globals|encode|localhost|loopback).* [NC,OR]" . PHP_EOL . "\tRewriteCond %{QUERY_STRING} ^.*(request|select|concat|insert|union|declare).* [NC]" . PHP_EOL . "\tRewriteCond %{QUERY_STRING} !^loggedout=true" . PHP_EOL . "\tRewriteCond %{QUERY_STRING} !^action=rp" . PHP_EOL . "\tRewriteCond %{HTTP_COOKIE} !^.*wordpress_logged_in_.*$" . PHP_EOL . "\tRewriteCond %{HTTP_REFERER} !^http://maps\.googleapis\.com(.*)$" . PHP_EOL . "\tRewriteRule ^(.*)$ - [F,L]" . PHP_EOL;

						}

					}

					//Process filtering of foreign characters
					if ( $input['non_english_characters'] == true ) {

						$rules .= PHP_EOL . "\t# " . __( 'Rules to block foreign characters in URLs', 'ithemes-security' ) . PHP_EOL;

						if ( $server_type === 'nginx' ) { //NGINX rules

							$rules .= "\tif (\$args ~* \"(%0|%A|%B|%C|%D|%E|%F)\") { return 403; }" . PHP_EOL;

						} else { //rules for all other servers

							$rules .= "\tRewriteCond %{QUERY_STRING} ^.*(%0|%A|%B|%C|%D|%E|%F).* [NC]" . PHP_EOL . "\tRewriteRule ^(.*)$ - [F,L]" . PHP_EOL;

						}

					}

					//Process Comment spam rules
					if ( $input['comment_spam'] == true ) {

						$rules .= PHP_EOL . "\t# " . __( 'Rules to help reduce spam', 'ithemes-security' ) . PHP_EOL;

						if ( $server_type === 'nginx' ) { //NGINX rules

							$rules .= "\tlocation /wp-comments-post.php {" . PHP_EOL . "\t\tvalid_referers jetpack.wordpress.com/jetpack-comment/ " . $itsec_lib->get_domain( get_site_url(), false ) . ";" . PHP_EOL . "\t\tset \$rule_0 0;" . PHP_EOL . "\t\tif (\$request_method ~ \"POST\"){ set \$rule_0 1\$rule_0; }" . PHP_EOL . "\t\tif (\$invalid_referer) { set \$rule_0 2\$rule_0; }" . PHP_EOL . "\t\tif (\$http_user_agent ~ \"^$\"){ set \$rule_0 3\$rule_0; }" . PHP_EOL . "\t\tif (\$rule_0 = \"3210\") { return 403; }" . PHP_EOL . "\t}";

						} else { //rules for all other servers

							$rules .= "\tRewriteCond %{REQUEST_METHOD} POST" . PHP_EOL . "\tRewriteCond %{REQUEST_URI} ^(.*)wp-comments-post\.php*" . PHP_EOL . "\tRewriteCond %{HTTP_REFERER} !^" . $itsec_lib->get_domain( get_site_url() ) . ".* " . PHP_EOL . "\tRewriteCond %{HTTP_REFERER} !^http://jetpack\.wordpress\.com/jetpack-comment/ [OR]" . PHP_EOL . "\tRewriteCond %{HTTP_USER_AGENT} ^$" . PHP_EOL . "\tRewriteRule ^(.*)$ - [F,L]" . PHP_EOL;

						}

					}

					//Close Apache Rewrite rules
					if ( $server_type !== 'nginx' ) { //non NGINX rules

						$rules .= "</IfModule>";

					}

				}

			}

			if ( strlen( $rules ) > 0 ) {
				$rules = explode( PHP_EOL, $rules );
			} else {
				$rules = false;
			}

			//create a proper array for writing
			$rules_array[] = array( 'type' => 'htaccess', 'priority' => 10, 'name' => 'Advanced Tweaks', 'rules' => $rules, );

			return $rules_array;

		}

		/**
		 * Build wp-config.php rules
		 *
		 * @param  array $input options to build rules from
		 *
		 * @return array         rules to write
		 */
		public static function build_wpconfig_rules( $rules_array, $input = null ) {

			//Return options to default on deactivation
			if ( $rules_array === false ) {

				$input       = array();
				$rules_array = array();

				$deactivating = true;

				$initials = get_site_option( 'itsec_initials' );

				if ( isset( $initials['file_editor'] ) && $initials['file_editor'] === false ) {
					$input['file_editor'] = false;
					$input['enabled']     = false;
				} else {
					$input['file_editor'] = true;
					$input['enabled']     = true;
				}

			} else {

				$deactivating = false;

				//Get the rules from the database if input wasn't sent
				if ( $input === null ) {
					$input = get_site_option( 'itsec_advanced_tweaks' );
				}

			}

			$comment_add = array( 'type' => 'add', 'search_text' => '//The entry below were created by iThemes Security to disable the file editor', 'rule' => '//The entry below were created by iThemes Security to disable the file editor', );

			$comment_remove = array( 'type' => 'delete', 'search_text' => '//The entry below were created by iThemes Security to disable the file editor', 'rule' => false, );

			$rule_add = array( 'type' => 'add', 'search_text' => 'DISALLOW_FILE_EDIT', 'rule' => "define( 'DISALLOW_FILE_EDIT', true );", );

			$rule_remove = array( 'type' => 'delete', 'search_text' => 'DISALLOW_FILE_EDIT', 'rule' => false, );

			if ( $input['file_editor'] == true && $input['enabled'] == true ) {

				if ( $deactivating === true ) {
					$rule[] = $comment_remove;
				} else {
					$rule[] = $comment_add;
				}

				$rule[] = $rule_add;

			} else {

				$rule[] = $comment_remove;

				$rule[] = $rule_remove;

			}

			$rules_array[] = array( 'type' => 'wpconfig', 'name' => 'Advanced Tweaks', 'rules' => $rule, );

			return $rules_array;

		}

		/**
		 * Sets the status in the plugin dashboard
		 *
		 * @return void
		 */
		public function dashboard_status( $statuses ) {

			global $itsec_lib;

			$link = 'admin.php?page=toplevel_page_itsec-advanced_tweaks&itsec_action=fix_error';

			if ( $this->settings['protect_files'] === true ) {

				$status_array = 'safe-medium';
				$status       = array( 'text' => __( 'You are protecting common WordPress files from access.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_protect_files', );

			} else {

				$status_array = 'medium';
				$status       = array( 'text' => __( 'You are not protecting common WordPress files from access. Click here to protect WordPress files.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_protect_files', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['directory_browsing'] === true ) {

				$status_array = 'safe-low';
				$status       = array( 'text' => __( 'You have successfully disabled directory browsing on your site.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_directory_browsing', );

			} else {

				$status_array = 'low';
				$status       = array( 'text' => __( 'You have not disabled directory browsing on your site. Click here to prevent a user from seeing every file present in your WordPress site.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_directory_browsing', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['request_methods'] === true ) {

				$status_array = 'safe-low';
				$status       = array( 'text' => __( 'You are blocking HTTP request methods you do not need.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_request_methods', );

			} else {

				$status_array = 'low';
				$status       = array( 'text' => __( 'You are not blocking HTTP request methods you do not need. Click here to block extra HTTP request methods that WordPress should not normally need.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_request_methods', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['suspicious_query_strings'] === true ) {

				$status_array = 'safe-medium';
				$status       = array( 'text' => __( 'Your WordPress site is blocking suspicious looking information in the URL.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_suspicious_query_strings', );

			} else {

				$status_array = 'medium';
				$status       = array( 'text' => __( 'Your WordPress site is not blocking suspicious looking information in the URL. Click here to block users from trying to execute code that they should not be able to execute.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_suspicious_query_strings', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['non_english_characters'] === true ) {

				$status_array = 'safe-low';
				$status       = array( 'text' => __( 'Your WordPress site is blocking non-english characters in the URL.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_non_english_characters', );

			} else {

				$status_array = 'low';
				$status       = array( 'text' => __( 'Your WordPress site is not blocking non-english characters in the URL. Click here to fix this.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_non_english_characters', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['long_url_strings'] === true ) {

				$status_array = 'safe-low';
				$status       = array( 'text' => __( 'Your installation does not accept long URLs.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_long_url_strings', );

			} else {

				$status_array = 'low';
				$status       = array( 'text' => __( 'Your installation accepts long (over 255 character) URLS. This can lead to vulnerabilities. Click here to fix this.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_long_url_strings', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['write_permissions'] === true ) {

				$status_array = 'safe-low';
				$status       = array( 'text' => __( 'Wp-config.php and .htacess are not writeable.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_write_permissions', );

			} else {

				$status_array = 'low';
				$status       = array( 'text' => __( 'Wp-config.php and .htacess are writeable. This can lead to vulnerabilities. Click here to fix this.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_write_permissions', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['generator_tag'] === true ) {

				$status_array = 'safe-medium';
				$status       = array( 'text' => __( 'Your WordPress installation is not publishing its version number to the world.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_generator_tag', );

			} else {

				$status_array = 'medium';
				$status       = array( 'text' => __( 'Your WordPress installation is publishing its version number to the world. Click here to fix this.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_generator_tag', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['wlwmanifest_header'] === true ) {

				$status_array = 'safe-low';
				$status       = array( 'text' => __( 'Your WordPress installation is not publishing the Windows Live Writer header.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_wlwmanifest_header', );

			} else {

				$status_array = 'low';
				$status       = array( 'text' => __( 'Your WordPress installation is publishing the Windows Live Writer header. Click here to fix this.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_wlwmanifest_header', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['edituri_header'] === true ) {

				$status_array = 'safe-low';
				$status       = array( 'text' => __( 'Your WordPress installation is not publishing the really simple discovery header.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_edituri_header', );

			} else {

				$status_array = 'low';
				$status       = array( 'text' => __( 'Your WordPress installation is publishing the really simple discovery header. Click here to fix this.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_edituri_header', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['theme_updates'] === true ) {

				$status_array = 'safe-medium';
				$status       = array( 'text' => __( 'Your WordPress installation is not telling users who cannot update themes about theme updates.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_theme_updates', );

			} else {

				$status_array = 'medium';
				$status       = array( 'text' => __( 'Your WordPress installation is telling users who cannot update themes about theme updates. Click here to fix this.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_theme_updates', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['plugin_updates'] === true ) {

				$status_array = 'safe-medium';
				$status       = array( 'text' => __( 'Your WordPress installation is not telling users who cannot update plugins about plugin updates.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_plugin_updates', );

			} else {

				$status_array = 'medium';
				$status       = array( 'text' => __( 'Your WordPress installation is telling users who cannot update plugins about plugin updates. Click here to fix this.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_plugin_updates', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['core_updates'] === true ) {

				$status_array = 'safe-medium';
				$status       = array( 'text' => __( 'Your WordPress installation is not telling users who cannot update WordPress core about WordPress core updates.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_core_updates', );

			} else {

				$status_array = 'medium';
				$status       = array( 'text' => __( 'Your WordPress installation is telling users who cannot update WordPress core about WordPress core updates. Click here to fix this.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_core_updates', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['comment_spam'] === true ) {

				$status_array = 'safe-medium';
				$status       = array( 'text' => __( 'Your WordPress installation is not allowing users without a user agent to post comments.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_comment_spam', );

			} else {

				$status_array = 'medium';
				$status       = array( 'text' => __( 'Your WordPress installation is allowing users without a user agent to post comments. Fix this to reduce comment spam.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_comment_spam', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['random_version'] === true ) {

				$status_array = 'safe-low';
				$status       = array( 'text' => __( 'Version information is obscured to all non admin users.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_random_version', );

			} else {

				$status_array = 'low';
				$status       = array( 'text' => __( 'Users may still be able to get version information from various plugins and themes. Click here to fix this.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_random_version', );

			}

			array_push( $statuses[$status_array], $status );

			if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT === true ) {

				$status_array = 'safe-low';
				$status       = array( 'text' => __( 'Users cannot edit plugin and themes files directly from within the WordPress Dashboard.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_file_editor', );

			} else {

				$status_array = 'low';
				$status       = array( 'text' => __( 'Users can edit plugin and themes files directly from within the WordPress Dashboard. Click here to fix this.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_file_editor', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['disable_xmlrpc'] === true ) {

				$status_array = 'safe-low';
				$status       = array( 'text' => __( 'XML-RPC is not available on your WordPress installation.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_disable_xmlrpc', );

			} else {

				$status_array = 'low';
				$status       = array( 'text' => __( 'XML-RPC is available on your WordPress installation. Attackers can use this feature to attack your site. Click here to disable access to XML-RPC.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_disable_xmlrpc', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $this->settings['uploads_php'] === true ) {

				$status_array = 'safe-medium';
				$status       = array( 'text' => __( 'Users cannot execute PHP from the uploads folder.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_uploads_php', );

			} else {

				$status_array = 'medium';
				$status       = array( 'text' => __( 'Users can execute PHP from the uploads folder. Click here to fix.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_server_uploads_php', );

			}

			array_push( $statuses[$status_array], $status );

			if ( $itsec_lib->safe_jquery_version() === true ) {

				$status_array = 'safe-high';
				$status       = array( 'text' => __( 'The front page of your site is using a safe version of jQuery.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_wordpress_safe_jquery', );

			} else {

				$status_array = 'high';
				$status       = array( 'text' => __( 'The front page of your site is not using a safe version of jQuery or the version of jQuery cannot be determined.', 'ithemes-security' ), 'link' => $link . '#itsec_advanced_tweaks_wordpress_safe_jquery', );

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
		 * Execute admin initializations
		 *
		 * @return void
		 */
		public function initialize_admin() {

			global $itsec_lib;

			//Add Settings sections
			add_settings_section(
				'advanced_tweaks_enabled',
				__( 'Enable Advanced Tweaks', 'ithemes-security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_itsec-advanced_tweaks'
			);

			add_settings_section(
				'advanced_tweaks_server',
				__( 'Configure Server Tweaks', 'ithemes-security' ),
				array( $this, 'server_tweaks_intro' ),
				'security_page_toplevel_page_itsec-advanced_tweaks'
			);

			add_settings_section(
				'advanced_tweaks_wordpress',
				__( 'Configure WordPress Tweaks', 'ithemes-security' ),
				array( $this, 'wordpress_tweaks_intro' ),
				'security_page_toplevel_page_itsec-advanced_tweaks'
			);

			if ( is_multisite() ) {
				add_settings_section(
					'advanced_tweaks_wordpress_multisite',
					__( 'Configure Multisite Tweaks', 'ithemes-security' ),
					array( $this, 'wordpress_multisite_tweaks_intro' ),
					'security_page_toplevel_page_itsec-advanced_tweaks'
				);
			}

			//Add settings fields
			add_settings_field(
				'itsec_advanced_tweaks[enabled]',
				__( 'Advanced Security Tweaks', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_enabled' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_enabled'
			);

			add_settings_field(
				'itsec_advanced_tweaks[protect_files]',
				__( 'System Files', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_server_protect_files' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_server'
			);

			add_settings_field(
				'itsec_advanced_tweaks[directory_browsing]',
				__( 'Directory Browsing', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_server_directory_browsing' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_server'
			);

			add_settings_field(
				'itsec_advanced_tweaks[request_methods]',
				__( 'Request Methods', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_server_request_methods' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_server'
			);

			add_settings_field(
				'itsec_advanced_tweaks[suspicious_query_strings]',
				__( 'Suspicious Query Strings', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_server_suspicious_query_strings' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_server'
			);

			add_settings_field(
				'itsec_advanced_tweaks[non_english_characters]',
				__( 'Non-English Characters', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_server_non_english_characters' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_server'
			);

			add_settings_field(
				'itsec_advanced_tweaks[long_url_strings]',
				__( 'Long URL Strings', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_server_long_url_strings' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_server'
			);

			add_settings_field(
				'itsec_advanced_tweaks[write_permissions]',
				__( 'File Writing Permissions', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_server_write_permissions' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_server'
			);

			add_settings_field(
				'itsec_advanced_tweaks[uploads_php]',
				__( 'Uploads', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_wordpress_uploads_php' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_server'
			);

			add_settings_field(
				'itsec_advanced_tweaks[generator_tag]',
				__( 'Generator Meta Tag', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_wordpress_generator_tag' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_wordpress'
			);

			add_settings_field(
				'itsec_advanced_tweaks[wlwmanifest_header]',
				__( 'Windows Live Writer Header', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_wordpress_wlwmanifest_header' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_wordpress'
			);

			add_settings_field(
				'itsec_advanced_tweaks[edituri_header]',
				__( 'EditURI Header', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_wordpress_edituri_header' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_wordpress'
			);

			add_settings_field(
				'itsec_advanced_tweaks[comment_spam]',
				__( 'Comment Spam', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_wordpress_comment_spam' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_wordpress'
			);

			add_settings_field(
				'itsec_advanced_tweaks[random_version]',
				__( 'Display Random Version', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_wordpress_random_version' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_wordpress'
			);

			add_settings_field(
				'itsec_advanced_tweaks[file_editor]',
				__( 'File Editor', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_wordpress_file_editor' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_wordpress'
			);

			add_settings_field(
				'itsec_advanced_tweaks[disable_xmlrpc]',
				__( 'XML-RPC', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_wordpress_disable_xmlrpc' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_wordpress'
			);

			if ( $itsec_lib->safe_jquery_version() !== true || $this->settings['safe_jquery'] === true ) {

				add_settings_field(
					'itsec_advanced_tweaks[safe_jquery]',
					__( 'Replace jQuery With a Safe Version', 'ithemes-security' ),
					array( $this, 'advanced_tweaks_wordpress_safe_jquery' ),
					'security_page_toplevel_page_itsec-advanced_tweaks',
					'advanced_tweaks_wordpress'
				);

			}

			add_settings_field(
				'itsec_advanced_tweaks[theme_updates]',
				__( 'Theme Update Notifications', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_wordpress_theme_updates' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_wordpress_multisite'
			);

			add_settings_field(
				'itsec_advanced_tweaks[plugin_updates]',
				__( 'Plugin Update Notifications', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_wordpress_plugin_updates' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_wordpress_multisite'
			);

			add_settings_field(
				'itsec_advanced_tweaks[core_updates]',
				__( 'Core Update Notifications', 'ithemes-security' ),
				array( $this, 'advanced_tweaks_wordpress_core_updates' ),
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'advanced_tweaks_wordpress_multisite'
			);

			//Register the settings field for the entire module
			register_setting(
				'security_page_toplevel_page_itsec-advanced_tweaks',
				'itsec_advanced_tweaks',
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
				$action = 'edit.php?action=itsec_advanced_tweaks';
			} else {
				$action = 'options.php';
			}

			printf( '<form name="%s" method="post" action="%s" class="itsec-form">', get_current_screen()->id, $action );

			$this->core->do_settings_sections( 'security_page_toplevel_page_itsec-advanced_tweaks', false );

			echo '<p>' . PHP_EOL;

			settings_fields( 'security_page_toplevel_page_itsec-advanced_tweaks' );

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

			global $itsec_lib, $itsec_files;

			$type    = 'updated';
			$message = __( 'Settings Updated', 'ithemes-security' );

			$input['enabled']                  = ( isset( $input['enabled'] ) && intval( $input['enabled'] == 1 ) ? true : false );
			$input['protect_files']            = ( isset( $input['protect_files'] ) && intval( $input['protect_files'] == 1 ) ? true : false );
			$input['directory_browsing']       = ( isset( $input['directory_browsing'] ) && intval( $input['directory_browsing'] == 1 ) ? true : false );
			$input['request_methods']          = ( isset( $input['request_methods'] ) && intval( $input['request_methods'] == 1 ) ? true : false );
			$input['suspicious_query_strings'] = ( isset( $input['suspicious_query_strings'] ) && intval( $input['suspicious_query_strings'] == 1 ) ? true : false );
			$input['non_english_characters']   = ( isset( $input['non_english_characters'] ) && intval( $input['non_english_characters'] == 1 ) ? true : false );
			$input['long_url_strings']         = ( isset( $input['long_url_strings'] ) && intval( $input['long_url_strings'] == 1 ) ? true : false );
			$input['write_permissions']        = ( isset( $input['write_permissions'] ) && intval( $input['write_permissions'] == 1 ) ? true : false );
			$input['generator_tag']            = ( isset( $input['generator_tag'] ) && intval( $input['generator_tag'] == 1 ) ? true : false );
			$input['wlwmanifest_header']       = ( isset( $input['wlwmanifest_header'] ) && intval( $input['wlwmanifest_header'] == 1 ) ? true : false );
			$input['edituri_header']           = ( isset( $input['edituri_header'] ) && intval( $input['edituri_header'] == 1 ) ? true : false );
			$input['theme_updates']            = ( isset( $input['theme_updates'] ) && intval( $input['theme_updates'] == 1 ) ? true : false );
			$input['plugin_updates']           = ( isset( $input['plugin_updates'] ) && intval( $input['plugin_updates'] == 1 ) ? true : false );
			$input['core_updates']             = ( isset( $input['core_updates'] ) && intval( $input['core_updates'] == 1 ) ? true : false );
			$input['comment_spam']             = ( isset( $input['comment_spam'] ) && intval( $input['comment_spam'] == 1 ) ? true : false );
			$input['random_version']           = ( isset( $input['random_version'] ) && intval( $input['random_version'] == 1 ) ? true : false );
			$input['file_editor']              = ( isset( $input['file_editor'] ) && intval( $input['file_editor'] == 1 ) ? true : false );
			$input['disable_xmlrpc']           = ( isset( $input['disable_xmlrpc'] ) && intval( $input['disable_xmlrpc'] == 1 ) ? true : false );
			$input['uploads_php']              = ( isset( $input['uploads_php'] ) && intval( $input['uploads_php'] == 1 ) ? true : false );
			$input['safe_jquery']              = ( isset( $input['safe_jquery'] ) && intval( $input['safe_jquery'] == 1 ) ? true : false );

			$rules = $this->build_rewrite_rules( array(), $input );
			$itsec_files->set_rewrites( $rules );

			//build and send htaccess rules
			if ( ! $itsec_files->save_rewrites() ) {

				$type    = 'error';
				$message = __( 'test WordPress was unable to save the your options to .htaccess. Please check with your server administrator and try again.', 'ithemes-security' );

			}

			$rules = $this->build_wpconfig_rules( array(), $input );

			$itsec_files->set_wpconfig( $rules );

			if ( ! $itsec_files->save_wpconfig() ) {

				$type    = 'error';
				$message = __( 'WordPress was unable to save your options to wp-config.php. Please check with your server administrator and try again.', 'ithemes-security' );

			}

			//Process file writing option
			$config_file  = $itsec_lib->get_config();
			$rewrite_file = $itsec_lib->get_htaccess();

			if ( $input['write_permissions'] == true ) {

				@chmod( $config_file, 0444 );
				@chmod( $rewrite_file, 0444 );

			} else {

				@chmod( $config_file, 0644 );
				@chmod( $rewrite_file, 0644 );

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

			$settings['enabled']                  = ( isset( $_POST['itsec_advanced_tweaks']['enabled'] ) && intval( $_POST['itsec_advanced_tweaks']['enabled'] == 1 ) ? true : false );
			$settings['protect_files']            = ( isset( $_POST['itsec_advanced_tweaks']['protect_files'] ) && intval( $_POST['itsec_advanced_tweaks']['protect_files'] == 1 ) ? true : false );
			$settings['directory_browsing']       = ( isset( $_POST['itsec_advanced_tweaks']['directory_browsing'] ) && intval( $_POST['itsec_advanced_tweaks']['directory_browsing'] == 1 ) ? true : false );
			$settings['request_methods']          = ( isset( $_POST['itsec_advanced_tweaks']['request_methods'] ) && intval( $_POST['itsec_advanced_tweaks']['request_methods'] == 1 ) ? true : false );
			$settings['suspicious_query_strings'] = ( isset( $_POST['itsec_advanced_tweaks']['suspicious_query_strings'] ) && intval( $_POST['itsec_advanced_tweaks']['suspicious_query_strings'] == 1 ) ? true : false );
			$settings['non_english_characters']   = ( isset( $_POST['itsec_advanced_tweaks']['non_english_characters'] ) && intval( $_POST['itsec_advanced_tweaks']['non_english_characters'] == 1 ) ? true : false );
			$settings['long_url_strings']         = ( isset( $_POST['itsec_advanced_tweaks']['long_url_strings'] ) && intval( $_POST['itsec_advanced_tweaks']['long_url_strings'] == 1 ) ? true : false );
			$settings['write_permissions']        = ( isset( $_POST['itsec_advanced_tweaks']['write_permissions'] ) && intval( $_POST['itsec_advanced_tweaks']['write_permissions'] == 1 ) ? true : false );
			$settings['generator_tag']            = ( isset( $_POST['itsec_advanced_tweaks']['generator_tag'] ) && intval( $_POST['itsec_advanced_tweaks']['generator_tag'] == 1 ) ? true : false );
			$settings['wlwmanifest_header']       = ( isset( $_POST['itsec_advanced_tweaks']['wlwmanifest_header'] ) && intval( $_POST['itsec_advanced_tweaks']['wlwmanifest_header'] == 1 ) ? true : false );
			$settings['edituri_header']           = ( isset( $_POST['itsec_advanced_tweaks']['edituri_header'] ) && intval( $_POST['itsec_advanced_tweaks']['edituri_header'] == 1 ) ? true : false );
			$settings['theme_updates']            = ( isset( $_POST['itsec_advanced_tweaks']['theme_updates'] ) && intval( $_POST['itsec_advanced_tweaks']['theme_updates'] == 1 ) ? true : false );
			$settings['plugin_updates']           = ( isset( $_POST['itsec_advanced_tweaks']['plugin_updates'] ) && intval( $_POST['itsec_advanced_tweaks']['plugin_updates'] == 1 ) ? true : false );
			$settings['core_updates']             = ( isset( $_POST['itsec_advanced_tweaks']['core_updates'] ) && intval( $_POST['itsec_advanced_tweaks']['core_updates'] == 1 ) ? true : false );
			$settings['comment_spam']             = ( isset( $_POST['itsec_advanced_tweaks']['comment_spam'] ) && intval( $_POST['itsec_advanced_tweaks']['comment_spam'] == 1 ) ? true : false );
			$settings['random_version']           = ( isset( $_POST['itsec_advanced_tweaks']['random_version'] ) && intval( $_POST['itsec_advanced_tweaks']['random_version'] == 1 ) ? true : false );
			$settings['file_editor']              = ( isset( $_POST['itsec_advanced_tweaks']['file_editor'] ) && intval( $_POST['itsec_advanced_tweaks']['file_editor'] == 1 ) ? true : false );
			$settings['disable_xmlrpc']           = ( isset( $_POST['itsec_advanced_tweaks']['disable_xmlrpc'] ) && intval( $_POST['itsec_advanced_tweaks']['disable_xmlrpc'] == 1 ) ? true : false );
			$settings['uploads_php']              = ( isset( $_POST['itsec_advanced_tweaks']['uploads_php'] ) && intval( $_POST['itsec_advanced_tweaks']['uploads_php'] == 1 ) ? true : false );
			$settings['safe_jquery']              = ( isset( $_POST['itsec_advanced_tweaks']['safe_jquery'] ) && intval( $_POST['itsec_advanced_tweaks']['safe_jquery'] == 1 ) ? true : false );

			update_site_option( 'itsec_advanced_tweaks', $settings ); //we must manually save network options

			//send them back to the away mode options page
			wp_redirect( add_query_arg( array( 'page' => 'toplevel_page_itsec-advanced_tweaks', 'updated' => 'true' ), network_admin_url( 'admin.php' ) ) );
			exit();

		}

		/**
		 * Add header for server tweaks
		 *
		 * @return void
		 */
		public function server_tweaks_intro() {

			echo '<h2 class="settings-section-header">' . __( 'Server Tweaks', 'ithemes-security' ) . '</h2>';
		}

		/**
		 * Sets the status in the plugin sidebar
		 *
		 * @return array $statuses array of sidebar statuses
		 */
		public function sidebar_status( $statuses ) {

			if ( $this->settings['generator_tag'] !== true ) {

				$statuses[] = array(
					'priority'  => 'medium',
					'bad_text'  => __( 'Your WordPress installation is publishing its version number to the world.', 'ithemes-security' ),
					'good_text' => __( 'Your WordPress installation is not publishing its version number to the world.', 'ithemes-security' ),
					'why_text'  => __( 'The more information an attacker has about you the easier it is to use that information against you. Knowing the version of your WordPress software can lead to known vulnerabilities if your WordPress version is not kept up to date.', 'ithemes-security' ),
					'option'    => 'itsec_advanced_tweaks',
					'setting'   => 'enabled:generator_tag',
					'value'     => true,
					'field_id'  => 'itsec_advanced_tweaks_enabled:itsec_advanced_tweaks_server_generator_tag',
				);

			}

			if ( $this->settings['comment_spam'] !== true ) {

				$statuses[] = array(
					'priority'  => 'medium',
					'bad_text'  => __( 'Your WordPress installation is not protecting against spam bots.', 'ithemes-security' ),
					'good_text' => __( 'Your WordPress installation is protecting against spam bots.', 'ithemes-security' ),
					'why_text'  => __( 'Many automated spam bots do not identify themselves to your WordPress site. This will prevent comments from these bots while preserving comments from any legitimate user.', 'ithemes-security' ),
					'option'    => 'itsec_advanced_tweaks',
					'setting'   => 'enabled:comment_spam',
					'value'     => true,
					'field_id'  => 'itsec_advanced_tweaks_enabled:itsec_advanced_tweaks_server_comment_spam',
				);

			}

			if ( $this->settings['random_version'] !== true ) {

				$statuses[] = array(
					'priority'  => 'medium',
					'bad_text'  => __( 'Your WordPress installation is not hiding the version number from users.', 'ithemes-security' ),
					'good_text' => __( 'Your WordPress installation is hiding the version number from users.', 'ithemes-security' ),
					'why_text'  => __( 'Hiding the version number helps prevent bots and others from knowing what you are running making it more difficult to determine if known vulnerabilities exist.', 'ithemes-security' ),
					'option'    => 'itsec_advanced_tweaks',
					'setting'   => 'enabled:random_version',
					'value'     => true,
					'field_id'  => 'itsec_advanced_tweaks_enabled:itsec_advanced_tweaks_server_random_version',
				);

			}

			if ( is_multisite() ) {

				if ( $this->settings['theme_updates'] !== true ) {

					$statuses[] = array(
						'priority'  => 'medium',
						'bad_text'  => __( 'Your WordPress installation is not hiding theme updates from users.', 'ithemes-security' ),
						'good_text' => __( 'Your WordPress installation is hiding theme updates from users.', 'ithemes-security' ),
						'why_text'  => __( 'Hiding updates from users prevents non-admin users from changing code and potentialing causing problems on your site.', 'ithemes-security' ),
						'option'    => 'itsec_advanced_tweaks',
						'setting'   => 'enabled:theme_updates',
						'value'     => true,
						'field_id'  => 'itsec_advanced_tweaks_enabled:itsec_advanced_tweaks_server_theme_updates',
					);

				}

				if ( $this->settings['plugin_updates'] !== true ) {

					$statuses[] = array(
						'priority'  => 'medium',
						'bad_text'  => __( 'Your WordPress installation is not hiding plugin updates from users.', 'ithemes-security' ),
						'good_text' => __( 'Your WordPress installation is hiding plugin updates from users.', 'ithemes-security' ),
						'why_text'  => __( 'Hiding updates from users prevents non-admin users from changing code and potentialing causing problems on your site.', 'ithemes-security' ),
						'option'    => 'itsec_advanced_tweaks',
						'setting'   => 'enabled:plugin_updates',
						'value'     => true,
						'field_id'  => 'itsec_advanced_tweaks_enabled:itsec_advanced_tweaks_server_plugin_updates',
					);

				}

				if ( $this->settings['core_updates'] !== true ) {

					$statuses[] = array(
						'priority'  => 'medium',
						'bad_text'  => __( 'Your WordPress installation is not hiding core updates from users.', 'ithemes-security' ),
						'good_text' => __( 'Your WordPress installation is hiding core updates from users.', 'ithemes-security' ),
						'why_text'  => __( 'Hiding updates from users prevents non-admin users from changing code and potentialing causing problems on your site.', 'ithemes-security' ),
						'option'    => 'itsec_advanced_tweaks',
						'setting'   => 'enabled:core_updates',
						'value'     => true,
						'field_id'  => 'itsec_advanced_tweaks_enabled:itsec_advanced_tweaks_server_core_updates',
					);

				}

			}

			return $statuses;

		}

		/**
		 * Adds fields that will be tracked for Google Analytics
		 */
		public function tracking_script() {

			if ( strpos( get_current_screen()->id, 'security_page_toplevel_page_itsec-advanced_tweaks' ) !== false ) {

				$tracking_items = array(
					'enabled',
					'protect_files',
					'directory_browsing',
					'request_methods',
					'suspicious_query_strings',
					'non_english_characters',
					'long_url_strings',
					'write_permissions',
					'uploads_php',
					'generator_tag',
					'wlwmanifest_header',
					'edituri_header',
					'comment_spam',
					'random_version',
					'file_editor',
					'disable_xmlrpc',
					'core_updates',
					'plugin_updates',
					'theme_updates',
					'safe_jquery',
				);

				$tracking_values = array(
					'enabled'                  => '0:b',
					'protect_files'            => '0:b',
					'directory_browsing'       => '0:b',
					'request_methods'          => '0:b',
					'suspicious_query_strings' => '0:b',
					'non_english_characters'   => '0:b',
					'long_url_strings'         => '0:b',
					'write_permissions'        => '0:b',
					'uploads_php'              => '0:b',
					'generator_tag'            => '0:b',
					'wlwmanifest_header'       => '0:b',
					'edituri_header'           => '0:b',
					'comment_spam'             => '0:b',
					'random_version'           => '0:b',
					'file_editor'              => '0:b',
					'disable_xmlrpc'           => '0:b',
					'core_updates'             => '0:b',
					'plugin_updates'           => '0:b',
					'theme_updates'            => '0:b',
					'safe_jquery'              => '0:b',
				);

				wp_localize_script( 'itsec_tracking', 'tracking_items', $tracking_items );
				wp_localize_script( 'itsec_tracking', 'tracking_values', $tracking_values );
				wp_localize_script( 'itsec_tracking', 'tracking_section', 'itsec_advanced_tweaks' );

			}

		}

		/**
		 * Add header for WordPress Multisite tweaks
		 *
		 * @return void
		 */
		public function wordpress_multisite_tweaks_intro() {

			echo '<h2 class="settings-section-header">' . __( 'Multisite Tweaks', 'ithemes-security' ) . '</h2>';
		}

		/**
		 * Add header for WordPress tweaks
		 *
		 * @return void
		 */
		public function wordpress_tweaks_intro() {

			echo '<h2 class="settings-section-header">' . __( 'WordPress Tweaks', 'ithemes-security' ) . '</h2>';
		}

		/**
		 * Start the System Tweaks Admin Module
		 *
		 * @param  Ithemes_ITSEC_Core $core Instance of core plugin class
		 *
		 * @return ITSEC_Advanced_Tweaks_Admin                The instance of the ITSEC_Advanced_Tweaks_Admin class
		 */
		public static function start( $core ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}