<?php

if ( ! class_exists( 'BWPS_Ban_Users_Admin' ) ) {

	class BWPS_Ban_Users_Admin {

		private static $instance = NULL;

		private
			$settings,
			$core,
			$page;

		private function __construct( $core ) {

			$this->core     = $core;
			$this->settings = get_site_option( 'bwps_ban_users' );

			add_filter( 'bwps_file_rules', array( $this, 'build_rewrite_rules' ) );

			add_action( 'bwps_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( 'bwps_page_top', array( $this, 'add_module_intro' ) ); //add page intro and information
			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) ); //enqueue scripts for admin page
			add_filter( 'bwps_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( 'bwps_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu
			add_filter( 'bwps_add_dashboard_status', array( $this, 'dashboard_status' ) ); //add information for plugin status

			//manually save options on multisite
			if ( is_multisite() ) {
				add_action( 'network_admin_edit_bwps_ban_users', array( $this, 'save_network_options' ) ); //save multisite options
			}

		}

		/**
		 * Register subpage for Away Mode
		 *
		 * @param array $available_pages array of BWPS settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $bwps_globals;

			$this->page = $available_pages[0] . '-ban_users';

			$available_pages[] = add_submenu_page(
				'bwps',
				__( 'Ban Users', 'better_wp_security' ),
				__( 'Ban Users', 'better_wp_security' ),
				$bwps_globals['plugin_access_lvl'],
				$available_pages[0] . '-ban_users',
				array( $this->core, 'render_page' )
			);

			return $available_pages;

		}

		public function add_admin_tab( $tabs ) {

			$tabs[$this->page] = __( 'Ban Users', 'better_wp_security' );

			return $tabs;

		}

		/**
		 * Add meta boxes to primary options pages
		 *
		 * @param array $available_pages array of available page_hooks
		 */
		public function add_admin_meta_boxes( $available_pages ) {

			add_meta_box(
				'ban_users_options',
				__( 'Configure Banned User Settings', 'better_wp_security' ),
				array( $this, 'metabox_advanced_settings' ),
				'security_page_toplevel_page_bwps-ban_users',
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

			if ( strpos( get_current_screen()->id, 'security_page_toplevel_page_bwps-ban_users' ) !== false ) {

				wp_enqueue_script( 'bwps_ban_users_js', $bwps_globals['plugin_url'] . 'modules/ban-users/js/admin-ban_users.js', 'jquery', $bwps_globals['plugin_build'] );

			}

		}

		/**
		 * Sets the status in the plugin dashboard
		 *
		 * @return void
		 */
		public function dashboard_status( $statuses ) {

			$link = 'admin.php?page=toplevel_page_bwps-ban_users';

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

			//default blacklist section
			add_settings_section(
				'ban_users_default',
				__( 'Default Blacklist', 'better_wp_security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_bwps-ban_users'
			);

			//Enabled section
			add_settings_section(
				'ban_users_enabled',
				__( 'Configure Ban Users', 'better_wp_security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_bwps-ban_users'
			);

			//primary settings section
			add_settings_section(
				'ban_users_settings',
				__( 'Configure Ban Users', 'better_wp_security' ),
				array( $this, 'empty_callback_function' ),
				'security_page_toplevel_page_bwps-ban_users'
			);

			//default list field
			add_settings_field(
				'bwps_ban_users[default]',
				__( 'Enable Default Blacklist', 'better_wp_security' ),
				array( $this, 'ban_users_default' ),
				'security_page_toplevel_page_bwps-ban_users',
				'ban_users_default'
			);

			//enabled field
			add_settings_field(
				'bwps_ban_users[enabled]',
				__( 'Enable Ban Users', 'better_wp_security' ),
				array( $this, 'ban_users_enabled' ),
				'security_page_toplevel_page_bwps-ban_users',
				'ban_users_enabled'
			);

			//host list field
			add_settings_field(
				'bwps_ban_users[host_list]',
				__( 'Ban Hosts', 'better_wp_security' ),
				array( $this, 'ban_users_host_list' ),
				'security_page_toplevel_page_bwps-ban_users',
				'ban_users_settings'
			);

			//agent _list field
			add_settings_field(
				'bwps_ban_users[agent_list]',
				__( 'Ban User Agents', 'better_wp_security' ),
				array( $this, 'ban_users_agent_list' ),
				'security_page_toplevel_page_bwps-ban_users',
				'ban_users_settings'
			);

			//agent _list field
			add_settings_field(
				'bwps_ban_users[white_list]',
				__( 'Whitelist Users', 'better_wp_security' ),
				array( $this, 'ban_users_white_list' ),
				'security_page_toplevel_page_bwps-ban_users',
				'ban_users_settings'
			);

			//Register the settings field for the entire module
			register_setting(
				'security_page_toplevel_page_bwps-ban_users',
				'bwps_ban_users',
				array( $this, 'sanitize_module_input' )
			);

		}

		/**
		 * Empty callback function
		 */
		public function empty_callback_function() {}

		/**
		 * echos Enabled Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function ban_users_enabled( $args ) {

			if ( isset( $this->settings['enabled'] ) && $this->settings['enabled'] === 1 ) {
				$enabled = 1;
			} else {
				$enabled = 0;
			}

			$content = '<input type="checkbox" id="bwps_ban_users_enabled" name="bwps_ban_users[enabled]" value="1" ' . checked( 1, $enabled, false ) . '/>';
			$content .= '<label for="bwps_ban_users_enabled"> ' . __( 'Check this box to enable ban users', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos hackrepair list Field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function ban_users_default( $args ) {

			if ( isset( $this->settings['default'] ) && $this->settings['default'] === 1 ) {
				$default = 1;
			} else {
				$default = 0;
			}

			$content = '<p>' . __( 'As a getting-started point you can include the excellent blacklist developed by Jim Walker of <a href="http://hackrepair.com/blog/how-to-block-bots-from-seeing-your-website-bad-bots-and-drive-by-hacks-explained" target="_blank">HackRepair.com</a>.', 'better-wp-security' ) . '</p>';
			$content .= '<br />';
			$content .= '<input type="checkbox" id="bwps_ban_users_default" name="bwps_ban_users[default]" value="1" ' . checked( 1, $default, false ) . '/>';
			$content .= '<label for="bwps_ban_users_default"> ' . __( 'Check this box to enable HackRepair.com\'s blacklist feature', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Banned Hosts field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function ban_users_host_list( $args ) {

			$host_list = '';

			//Convert and show the host list
			if ( isset( $this->settings['host_list'] ) && is_array( $this->settings['host_list'] ) && sizeof( $this->settings['host_list'] ) >= 1 ) {

				$host_list = implode( PHP_EOL, $this->settings['host_list'] );

			} elseif ( isset( $this->settings['host_list'] ) && ! is_array( $this->settings['host_list'] ) && strlen( $this->settings['host_list'] ) > 1 ) {

				$host_list = $this->settings['host_list'];

			}

			$content = '<textarea id="bwps_ban_users_host_list" name="bwps_ban_users[host_list]" rows="10" cols="50">' . $host_list . '</textarea>';
			$content .= '<p>' . __( 'Use the guidelines below to enter hosts that will not be allowed access to your site. Note you cannot ban yourself.', 'better_wp_security' ) . '</p>';
			$content .= '<ul><em>';
			$content .= '<li>' . __( 'You may ban users by individual IP address or IP address range.', 'better_wp_security' ) . '</li>';
			$content .= '<li>' . __( 'Individual IP addesses must be in IPV4 standard format (i.e. ###.###.###.### or ###.###.###.###/##). Wildcards (*) or a netmask is allowed to specify a range of ip addresses.', 'better_wp_security' ) . '</li>';
			$content .= '<li>' . __( 'If using a wildcard (*) you must start with the right-most number in the ip field. For example ###.###.###.* and ###.###.*.* are permitted but ###.###.*.### is not.', 'better_wp_security' ) . '</li>';
			$content .= '<li><a href="http://ip-lookup.net/domain-lookup.php" target="_blank">' . __( 'Lookup IP Address.', 'better_wp_security' ) . '</a></li>';
			$content .= '<li>' . __( 'Enter only 1 IP address or 1 IP address range per line.', 'better_wp_security' ) . '</li>';
			$content .= '</em></ul>';

			echo $content;

		}

		/**
		 * echos Banned Agents field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function ban_users_agent_list( $args ) {

			$agent_list = '';

			//Convert and show the agent list
			if ( isset( $this->settings['agent_list'] ) && is_array( $this->settings['agent_list'] ) && sizeof( $this->settings['agent_list'] ) >= 1 ) {

				$agent_list = implode( PHP_EOL, $this->settings['agent_list'] );

			} elseif ( isset( $this->settings['agent_list'] ) && ! is_array( $this->settings['agent_list'] ) && strlen( $this->settings['agent_list'] ) > 1 ) {

				$agent_list = $this->settings['agent_list'];

			}

			$content = '<textarea id="bwps_ban_users_agent_list" name="bwps_ban_users[agent_list]" rows="10" cols="50">' . $agent_list . '</textarea>';
			$content .= '<p>' . __( 'Use the guidelines below to enter user agents that will not be allowed access to your site.', 'better_wp_security' ) . '</p>';
			$content .= '<ul><em>';
			$content .= '<li>' . __( 'Enter only 1 user agent per line.', 'better_wp_security' ) . '</li>';
			$content .= '</em></ul>';

			echo $content;

		}

		/**
		 * echos Banned white list field
		 *
		 * @param  array $args field arguements
		 *
		 * @return void
		 */
		public function ban_users_white_list( $args ) {

			$white_list = '';

			//Convert and show the agent list
			if ( isset( $this->settings['white_list'] ) && is_array( $this->settings['white_list'] ) && sizeof( $this->settings['white_list'] ) >= 1 ) {

				$white_list = implode( PHP_EOL, $this->settings['white_list'] );

			} elseif ( isset( $this->settings['white_list'] ) && ! is_array( $this->settings['white_list'] ) && strlen( $this->settings['white_list'] ) > 1 ) {

				$white_list = $this->settings['white_list'];

			}

			$content = '<textarea id="bwps_ban_users_white_list" name="bwps_ban_users[white_list]" rows="10" cols="50">' . $white_list . '</textarea>';
			$content .= '<p>' . __( 'Use the guidelines below to enter hosts that will not be banned from your site. This will keep you from locking yourself out of any features if you should trigger a lockout. Please note this does not override away mode.', 'better_wp_security' ) . '</p>';
			$content .= '<ul><em>';
			$content .= '<li>' . __( 'You may white list users by individual IP address or IP address range.', 'better_wp_security' ) . '</li>';
			$content .= '<li>' . __( 'Individual IP addesses must be in IPV4 standard format (i.e. ###.###.###.### or ###.###.###.###/##). Wildcards (*) or a netmask is allowed to specify a range of ip addresses.', 'better_wp_security' ) . '</li>';
			$content .= '<li>' . __( 'If using a wildcard (*) you must start with the right-most number in the ip field. For example ###.###.###.* and ###.###.*.* are permitted but ###.###.*.### is not.', 'better_wp_security' ) . '</li>';
			$content .= '<li><a href="http://ip-lookup.net/domain-lookup.php" target="_blank">' . __( 'Lookup IP Address.', 'better_wp_security' ) . '</a></li>';
			$content .= '<li>' . __( 'Enter only 1 IP address or 1 IP address range per line.', 'better_wp_security' ) . '</li>';
			$content .= '</em></ul>';

			echo $content;

		}

		/**
		 * Build and echo the away mode description
		 *
		 * @return void
		 */
		public function add_module_intro( $screen ) {

			if ( $screen === 'security_page_toplevel_page_bwps-ban_users' ) { //only display on away mode page

				$content = '<p>' . __( 'This feature allows you to ban hosts and user agents from your site completely using individual or groups of IP addresses as well as user agents without having to manage any configuration of your server. Any IP or user agent found in the lists below will not be allowed any access to your site.', 'better_wp_security' ) . '</p>';

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
				$action = 'edit.php?action=bwps_ban_users';
			} else {
				$action = 'options.php';
			}

			printf( '<form name="%s" method="post" action="%s">', get_current_screen()->id, $action );

			$this->core->do_settings_sections( 'security_page_toplevel_page_bwps-ban_users', false );

			echo '<p>' . PHP_EOL;

			settings_fields( 'security_page_toplevel_page_bwps-ban_users' );

			echo '<input class="button-primary" name="submit" type="submit" value="' . __( 'Save Changes', 'better_wp_security' ) . '" />' . PHP_EOL;

			echo '</p>' . PHP_EOL;

			echo '</form>';

		}

		/**
		 * Build the rewrite rules and sends them to the file writer
		 *
		 * @param array  $rules_array array of rules to modify
		 * @param array  $input array of options, ips, etc
		 *
		 * @return array array of rules to send to file writer
		 */
		public function build_rewrite_rules( $rules_array, $input = null ) {

			global $bwps_lib;

			//setup data structures to write. These are simply lists of all IPs and hosts as well as options to check
			if ( $input === null ) { //blocking ip on the fly

				$input = get_site_option( 'bwps_ban_users' );

			}

			$default        = $input['default'];
			$enabled        = $input['enabled'];
			$raw_host_list  = $input['host_list'];
			$raw_agent_list = $input['agent_list'];
			$raw_white_list = $input['white_list'];

			$server_type = $bwps_lib->get_server(); //Get the server type to build the right rules

			//initialize lists so we can check later if we've used them
			$host_list    = '';
			$agent_list   = '';
			$default_list = '';

			//load the default blacklist if needed
			if ( $default === 1 && $server_type === 'nginx' ) {
				$default_list = file_get_contents( plugin_dir_path( __FILE__ ) . 'lists/hackrepair-nginx.inc' );
			} elseif ( $default === 1 ) {
				$default_list = file_get_contents( plugin_dir_path( __FILE__ ) . 'lists/hackrepair-apache.inc' );
			}

			//Only process other lists if the feature has been enabled
			if ( $enabled === 1 ) {

				//process hosts list
				if ( is_array( $raw_host_list ) ) {

					foreach ( $raw_host_list as $host ) {

						$host_rule = ''; //initialze converted host
						$converted_host = $bwps_lib->ip_wild_to_mask( $host );

						if( ! $this->is_ip_whitelisted( $converted_host, $raw_white_list ) ) {

							if ( $server_type === 'nginx' ) { //NGINX rules
								$host_rule = "\tdeny " . $converted_host;
							} else { //rules for all other servers
								$host_rule = 'Deny from ' . $converted_host;
							}

							$host_list .= $host_rule . PHP_EOL; //build large string of all hosts

						} else {

							/**
							 * @todo warn the user the ip to be banned is whitelisted
							 */

						}

					}

				}

				//Process the agents list
				if ( is_array( $raw_agent_list ) ) {

					$count = 1; //to help us find the last one

					foreach ( $raw_agent_list as $agent ) {

						if ( strlen( trim( $agent ) ) > 1 ) { 

							//if it isn't the last rule make sure we add an or
							if ( $count < sizeof( $agent ) ) {
								$end = ' [NC,OR]' . PHP_EOL;
							} else {
								$end = '[NC]' . PHP_EOL;
							}

							if ( $server_type === 'nginx' ) { //NGINX rule
								$converted_agent = 'if ($http_user_agent ~* "^' . trim( $agent ) . '"){ return 403; }' . PHP_EOL;
							} else { //Rule for all other servers
								$converted_agent = 'RewriteCond %{HTTP_USER_AGENT} ^' . trim( $agent ) . $end;
							}

							$agent_list .= $converted_agent; //build large string of all agents

						}

						$count ++;

					}

				}

			}

			$rules = ''; //initialize rules

			//Start with default rules if we have them
			if ( strlen( $default_list ) > 1 ) {

				$rules .= $default_list;

			}

			//Add banned host lists
			if ( strlen( $host_list ) > 1 || strlen( $default_list ) ) {

				if ( strlen( $default_list ) > 1 ) {
					$rules .= PHP_EOL;
				}

				if ( $server_type === 'nginx' && strlen( $host_list ) > 1 ) { //NGINX rules

					$rules .= 'location / {' . PHP_EOL;
					$rules .= $host_list;
					$rules .= "\tallow all" . PHP_EOL;
					$rules .= '}' . PHP_EOL;

				} elseif ( strlen( $host_list ) > 1 ) {

					$rules .= 'Order allow,deny' . PHP_EOL;
					$rules .= $host_list;
					$rules .= 'Allow from all';

				}

			}

			//Add banned user agents
			if ( strlen( $agent_list ) > 1 ) {

				if ( strlen( $default_list ) > 1 || strlen( $host_list ) > 1 ) {
					$rules .= PHP_EOL;
				}

				if ( $server_type === 'nginx' ) { //NGINX rules

					$rules .= $agent_list;

				} else {

					$rules .= '<IfModule mod_rewrite.c>' . PHP_EOL;
					$rules .= 'RewriteEngine On' . PHP_EOL;
					$rules .= $agent_list;
					$rules .= 'RewriteRule ^(.*)$ - [F,L]' . PHP_EOL;
					$rules .= '</IfModule>' . PHP_EOL;

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
				'priority'	=> 1,
				'name'		=> 'Ban Users',
				'rules'		=> $rules,
			);

			return $rules_array;

		}

		private function insert_ip( $ip, $ban_list = null, $white_list = null) {

			$settings = get_site_option('bwps_ban_users');

			if ( $ban_list !== null ) {

				$ban_list = $settings['ban_list'];

			}

			if ( $white_list !== null ) {

				$white_list = $settings['white_list'];

			}
			

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

			$no_errors = false; //start out assuming they entered a bad IP somewhere

			//Sanitize checkbox features
			$input['enabled'] = ( isset( $input['enabled'] ) && intval( $input['enabled'] == 1 ) ? 1 : 0 );
			$input['default'] = ( isset( $input['default'] ) && intval( $input['default'] == 1 ) ? 1 : 0 );

			//process agent list
			if ( isset( $input['agent_list'] ) && ! is_array( $input['agent_list'] ) ) {
				$agents = explode( PHP_EOL, $input['agent_list'] );
			} else {
				$agents = array();
			}

			$good_agents = array();

			foreach ( $agents as $agent ) {
				$good_agents[] = quotemeta( sanitize_text_field( $agent ) );
			}

			$input['agent_list'] = $good_agents;

			//Process white list
			if ( isset( $input['white_list'] ) && ! is_array( $input['white_list'] ) ) {
				$white_listed_addresses = explode( PHP_EOL, $input['white_list'] );
			} else {
				$white_listed_addresses = array();
			}

			$bad_white_listed_ips  = array();
			$raw_white_listed_ips = array();

			foreach ( $white_listed_addresses as $index => $address ) {

				if ( strlen( trim( $address ) ) > 0 ) {

					if ( $bwps_lib->validates_ip_address( $address ) === false ) {

						$bad_white_listed_ips[] = filter_var( $address, FILTER_SANITIZE_STRING );

					}

					$raw_white_listed_ips[] = filter_var( $address, FILTER_SANITIZE_STRING );

				} else {
					unset( $white_listed_addresses[$index] );
				}

			}

			$raw_white_listed_ips = array_unique( $raw_white_listed_ips );

			if ( sizeof( $bad_white_listed_ips ) > 0 ) {

				$input['enabled'] = 0; //disable ban users list

				$type    = 'error';
				$message = '';

				$message .= sprintf( '%s<br /><br />', __( 'Note that the ban users feature has been disabled until the following errors are corrected:', 'better_wp_security' ) );

				foreach ( $bad_white_listed_ips as $bad_ip ) {
					$message .= sprintf( '%s %s<br />', $bad_ip, __( 'is not a valid address in the whitelist users box.', 'better-wp-security' ) );
				}

			} else {

				$type    = 'updated';
				$message = __( 'Settings Updated', 'better_wp_security' );

				$no_errors = true;

			}

			$input['white_list'] = $raw_white_listed_ips;

			//Process hosts list
			if ( isset( $input['host_list'] ) && ! is_array( $input['host_list'] ) ) {
				$addresses = explode( PHP_EOL, $input['host_list'] );
			} else {
				$addresses = array();
			}

			$bad_ips  = array();
			$raw_ips = array();

			foreach ( $addresses as $index => $address ) {

				if ( strlen( trim( $address ) ) > 0 ) {

					if ( $bwps_lib->validates_ip_address( $address ) === false ) {

						$bad_ips[] = filter_var( $address, FILTER_SANITIZE_STRING );

					}

					$raw_ips[] = filter_var( $address, FILTER_SANITIZE_STRING );

				} else {
					unset( $addresses[$index] );
				}

			}

			$raw_ips = array_unique( $raw_ips );

			if ( sizeof( $bad_ips ) > 0 ) {

				$input['enabled'] = 0; //disable ban users list

				$type    = 'error';

				if ( $no_errors === true ) {
					$message .= sprintf( '%s<br /><br />', __( 'Note that the ban users feature has been disabled until the following errors are corrected:', 'better_wp_security' ) );
				}
				
				foreach ( $bad_ips as $bad_ip ) {
					$message .= sprintf( '%s %s<br />', $bad_ip, __( 'is not a valid address in the ban users box.', 'better-wp-security' ) );
				}

			} else {

				$type    = 'updated';
				$message = __( 'Settings Updated', 'better_wp_security' );

				$no_errors = true;

			}

			$input['host_list'] = $raw_ips;

			if ( $no_errors === true ) {

				$rules = $this->build_rewrite_rules( array(), $input );

				$bwps_files->set_rewrites( $rules );

				if ( ! $bwps_files->save_rewrites() ) {

					$type    = 'error';
					$message = __( 'Better WP Security could not write the required rewrite rules. You will have to enter them manually.', 'better_wp_security' );

				} 

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

			$settings['enabled'] = ( isset( $_POST['bwps_ban_users']['enabled'] ) && intval( $_POST['bwps_ban_users']['enabled'] == 1 ) ? 1 : 0 );

			update_site_option( 'bwps_ban_users', $settings ); //we must manually save network options

			//send them back to the away mode options page
			wp_redirect( add_query_arg( array( 'page' => 'toplevel_page_bwps-away_mode', 'updated' => 'true' ), network_admin_url( 'admin.php' ) ) );
			exit();

		}

		/**
		 * Determines whether a given IP address is whitelisted
		 * 
		 * @param  string  $ip_to_check  ip to check
		 * @param  array   $white_ips    ip list to compare to if not yet saved to options
		 * @return boolean               true if whitelisted or false
		 */
		private function is_ip_whitelisted( $ip_to_check, $white_ips = null ) {

			global $bwps_lib;

			if ( $white_ips === null ) {

				$settings = get_site_option( 'bwps_ban_users' );

				$white_ips = $settings['white_list'];

			}

			$white_ips[] = $bwps_lib->get_ip(); //add current user ip to whitelist to check automatically

			foreach ( $white_ips as $white_ip ) {

				$converted_white_ip = $bwps_lib->ip_wild_to_mask( $white_ip );

				$check_range = $bwps_lib->cidr_to_range( $converted_white_ip );
				$ip_range = $bwps_lib->cidr_to_range( $ip_to_check );

				if( sizeof( $check_range ) === 2 ) { //range to check

					$check_min = ip2long( $check_range[0] );
					$check_max = ip2long( $check_range[1] );

					if ( sizeof( $ip_range ) === 2 ) {

						$ip_min = ip2long( $ip_range[0] );
						$ip_max = ip2long( $ip_range[1] );

						if ( ( $check_min < $ip_min && $ip_min < $check_max ) || ( $check_min < $ip_max && $ip_max < $check_max ) ) {
							return true;
						}

					} else { 

						$ip = ip2long( $ip_range[0] );

						if ( $check_min < $ip && $ip < $check_max ) {
							return true;
						}

					}

				} else { //single ip to check

					$check = ip2long( $check_range[0] );

					if ( sizeof( $ip_range ) === 2  ) {

						$ip_min = ip2long( $ip_range[0] );
						$ip_max = ip2long( $ip_range[1] );

						if ( $ip_min < $check && $check < $ip_max ) {
							return true;
						}

					} else { 

						$ip = ip2long( $ip_range[0] );

						if ( $check == $ip ) {
							return true;
						}

					}

				}

			}

			return false;

		}

		/**
		 * Start the Ban Users module
		 *
		 * @param  Ithemes_BWPS_Core $core Instance of core plugin class
		 *
		 * @return BWPS_Ban_Users_Admin                The instance of the BWPS_Ban_Users_Admin class
		 */
		public static function start( $core ) {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}