<?php

if ( ! class_exists( 'bwps_admin_content' ) ) {

	class bwps_admin_content extends bwps_admin_common {
	
		function __construct() {

			global $bwpsoptions, $bwpstabs;

			if ( $bwpsoptions['st_writefiles'] == 0 ) { 

				$bwpstabs = array(
					'better-wp-security'					=> 'Dashboard',
					'better-wp-security-adminuser'			=> 'User',
					'better-wp-security-awaymode'			=> 'Away',
					'better-wp-security-banusers'			=> 'Ban',
					'better-wp-security-databasebackup'		=> 'Backup',
					'better-wp-security-hidebackend'		=> 'Hide',
					'better-wp-security-intrusiondetection'	=> 'Detect',
					'better-wp-security-loginlimits'		=> 'Login',
					'better-wp-security-ssl'				=> 'SSL',
					'better-wp-security-systemtweaks'		=> 'Tweaks',
					'better-wp-security-logs'				=> 'Logs'
				);			

			} else {

				$bwpstabs = array(
					'better-wp-security'					=> 'Dashboard',
					'better-wp-security-adminuser'			=> 'User',
					'better-wp-security-awaymode'			=> 'Away',
					'better-wp-security-banusers'			=> 'Ban',
					'better-wp-security-contentdirectory'	=> 'Dir',
					'better-wp-security-databasebackup'		=> 'Backup',
					'better-wp-security-databaseprefix'		=> 'Prefix',
					'better-wp-security-hidebackend'		=> 'Hide',
					'better-wp-security-intrusiondetection'	=> 'Detect',
					'better-wp-security-loginlimits'		=> 'Login',
					'better-wp-security-ssl'				=> 'SSL',
					'better-wp-security-systemtweaks'		=> 'Tweaks',
					'better-wp-security-logs'				=> 'Logs'
				);

			}
		
			if ( is_multisite() ) { 
				add_action( 'network_admin_menu', array( &$this, 'register_settings_page' ) ); 
			} else {
				add_action( 'admin_menu',  array( &$this, 'register_settings_page' ) );
			}

			//add settings
			add_action( 'admin_init', array( &$this, 'register_settings' ) );
		
		}
	
		/**
		 * Registers all WordPress admin menu items
		 *
		 **/
		function register_settings_page() {
		
			global $bwpsoptions, $bwpstabs;
		
			add_menu_page(
				__( $this->pluginname, $this->hook ) . ' - ' . __( 'Dashboard', $this->hook ),
				__( 'Security', $this->hook ),
				$this->accesslvl,
				$this->hook,
				array( &$this, 'admin_dashboard' ),
				BWPS_PU . 'images/shield-small.png'
			);
			
			if ( $bwpsoptions['initial_backup'] == 1 && $bwpsoptions['initial_filewrite'] == 1 ) { //they've backed up their database or ignored the warning
			
				add_submenu_page(
					$this->hook, 
					__( $this->pluginname, $this->hook ) . ' - ' . __( 'Change Admin User', $this->hook ),
					__( 'Admin User', $this->hook ),
					$this->accesslvl,
					$this->hook . '-adminuser',
					array( &$this, 'admin_adminuser' )
				);
			
				add_submenu_page(
					$this->hook, 
					__( $this->pluginname, $this->hook ) . ' - ' . __( 'Away Mode', $this->hook ),
					__( 'Away Mode', $this->hook ),
					$this->accesslvl,
					$this->hook . '-awaymode',
					array( &$this, 'admin_awaymode' )
				);
			
				add_submenu_page(
					$this->hook, 
					__( $this->pluginname, $this->hook ) . ' - ' . __( 'Ban Users', $this->hook ),
					__( 'Ban Users', $this->hook ),
					$this->accesslvl,
					$this->hook . '-banusers',
					array( &$this, 'admin_banusers' )
				);
			
				if ( $bwpsoptions['st_writefiles'] == 1 ) { 
				
					add_submenu_page(
						$this->hook, 
						__( $this->pluginname, $this->hook ) . ' - ' . __( 'Change Content Directory', $this->hook ),
						__( 'Content Directory', $this->hook ),
						$this->accesslvl,
						$this->hook . '-contentdirectory',
						array( &$this, 'admin_contentdirectory' )
					);
					
				}
			
				add_submenu_page(
					$this->hook, 
					__( $this->pluginname, $this->hook ) . ' - ' . __( 'Backup WordPress Database', $this->hook ),
					__( 'Database Backup', $this->hook ),
					$this->accesslvl,
					$this->hook . '-databasebackup',
					array( &$this, 'admin_databasebackup' )
				);
			
				if ( $bwpsoptions['st_writefiles'] == 1 ) { 
				
					add_submenu_page(
						$this->hook, 
						__( $this->pluginname, $this->hook ) . ' - ' . __( 'Change Database Prefix', $this->hook ),
						__( 'Database Prefix', $this->hook ),
						$this->accesslvl,
						$this->hook . '-databaseprefix',
						array( &$this, 'admin_databaseprefix' )
					);
					
				}
			
				add_submenu_page(
					$this->hook, 
					__( $this->pluginname, $this->hook ) . ' - ' . __( 'Hide Backend', $this->hook ),
					__( 'Hide Backend', $this->hook ),
					$this->accesslvl,
					$this->hook . '-hidebackend',
					array( &$this, 'admin_hidebackend' )
				);
			
				add_submenu_page(
					$this->hook, 
					__( $this->pluginname, $this->hook ) . ' - ' . __( 'Intrusion Detection', $this->hook ),
					__( 'Intrusion Detection', $this->hook ),
					$this->accesslvl,
					$this->hook . '-intrusiondetection',
					array( &$this, 'admin_intrusiondetection' )
				);
			
				add_submenu_page(
					$this->hook, 
					__( $this->pluginname, $this->hook ) . ' - ' . __( 'Limit Login Attempts', $this->hook ),
					__( 'Login Limits', $this->hook ),
					$this->accesslvl,
					$this->hook . '-loginlimits',
					array( &$this, 'admin_loginlimits' )
				);
			
				add_submenu_page(
					$this->hook, 
					__( $this->pluginname, $this->hook ) . ' - ' . __( 'Secure Communications With SSL', $this->hook ),
					__( 'SSL', $this->hook ),
					$this->accesslvl,
					$this->hook . '-ssl',
					array( &$this, 'admin_ssl' )
				);
				
				add_submenu_page(
					$this->hook, 
					__( $this->pluginname, $this->hook ) . ' - ' . __( 'WordPress System Tweaks', $this->hook ),
					__( 'System Tweaks', $this->hook ),
					$this->accesslvl,
					$this->hook . '-systemtweaks',
					array( &$this, 'admin_systemtweaks' )
				);
			
				add_submenu_page(
					$this->hook, 
					__( $this->pluginname, $this->hook ) . ' - ' . __( 'View Logs', $this->hook ),
					__( 'View Logs', $this->hook ),
					$this->accesslvl,
					$this->hook . '-logs',
					array( &$this, 'admin_logs' )
				);
			
				//Make the dashboard the first submenu item and the item to appear when clicking the parent.
				global $submenu;
				if ( isset( $submenu[$this->hook] ) ) {
			
					$submenu[$this->hook][0][0] = __( 'Dashboard', $this->hook );
				
				}
				
			}
			
		}	
		
		/**
		 * Registers content blocks for dashboard page
		 *
		 **/
		function admin_dashboard() {
			
			global $bwpsoptions, $bwpstabs;
			
			if ( $bwpsoptions['oneclickchosen'] == 1 && $bwpsoptions['initial_backup'] == 1 && $bwpsoptions['initial_filewrite'] == 1 ) { //they've backed up their database or ignored the warning
			
				$this->admin_page( 
					$this->pluginname . ' - ' . __( 'System Status', $this->hook ),
					array(						
						array( __( 'System Status', $this->hook ), 'dashboard_content_4' ), //Better WP Security System Status
						array( __( 'System Information', $this->hook ), 'dashboard_content_7' ), //Generic System Information
						array( __( 'Rewrite Rules', $this->hook ), 'dashboard_content_5' ), //Better WP Security Rewrite Rules
						array( __( 'Wp-config.php Code', $this->hook ), 'dashboard_content_6' ) //Better WP Security Rewrite Rules
					),
					BWPS_PU . 'images/shield-large.png',
					$bwpstabs
				);
				
			} elseif ( $bwpsoptions['oneclickchosen'] == 0 && $bwpsoptions['initial_backup'] == 1 && $bwpsoptions['initial_filewrite'] == 1 ) { //they've backed up their database or ignored the warning
			
				$this->admin_page( 
					$this->pluginname . ' - ' . __( 'System Status', $this->hook ),
					array(
						array( __( 'One-Click Protection', $this->hook ), 'dashboard_content_3' ) //One-click protection
					),
					BWPS_PU . 'images/shield-large.png',
					$bwpstabs
				);
				
			} elseif ( $bwpsoptions['oneclickchosen'] == 0 && $bwpsoptions['initial_backup'] == 1 && $bwpsoptions['initial_filewrite'] == 0 ) { 
			
				$this->admin_page( 
					$this->pluginname . ' - ' . __( 'System Status', $this->hook ),
					array(
						array( __( 'Important', $this->hook ), 'dashboard_content_2' ), //Ask the user if they want BWPS to automatically write to system files					
					),
					BWPS_PU . 'images/shield-large.png',
					array()
				);
				
			} else { //if they haven't backed up their database or ignored the warning
			
				$this->admin_page( 
					$this->pluginname . ' - ' . __( 'System Status', $this->hook ),
					array(
						array( __( 'Welcome!', $this->hook ), 'dashboard_content_1' ), //Try to force the user to back up their site before doing anything else
					),
					BWPS_PU . 'images/shield-large.png',
					array()
				);
			
			}
			
		}
		
		/**
		 * Registers content blocks for change admin user page
		 *
		 **/
		function admin_adminuser() {

			global $bwpstabs;

			if ( ! is_multisite() ) {
				$this->admin_page( 
					$this->pluginname . ' - ' . __( 'Change Admin User', $this->hook ),
					array(
						array( __( 'Before You Begin', $this->hook ), 'adminuser_content_1' ), //information to prevent the user from getting in trouble
						array( __( 'Change The Admin User Name', $this->hook ), 'adminuser_content_2' ), //adminuser options
						array( __( 'Change The Admin User ID', $this->hook ), 'adminuser_content_3' ) //adminuser options
					),
					BWPS_PU . 'images/shield-large.png',
					$bwpstabs
				);
			} else {
				$this->admin_page( 
					$this->pluginname . ' - ' . __( 'Change Admin User', $this->hook ),
					array(
						array( __( 'Before You Begin', $this->hook ), 'adminuser_content_1' ), //information to prevent the user from getting in trouble
						array( __( 'Change The Admin User Name', $this->hook ), 'adminuser_content_2' )
					),
					BWPS_PU . 'images/shield-large.png',
					$bwpstabs,
					$this->hook . '-adminuser'
				);
			}

		}
		
		/**
		 * Registers content blocks for away mode page
		 *
		 **/
		function admin_awaymode() {

			global $bwpstabs;

			$this->admin_page( 
				$this->pluginname . ' - ' . __( 'Administor Away Mode', $this->hook ),
				array(
					array( __( 'Before You Begin', $this->hook ), 'awaymode_content_1' ), //information to prevent the user from getting in trouble
					array( __( 'Away Mode Options', $this->hook ), 'awaymode_content_2' ), //awaymode options
					array( __( 'Away Mode Rules', $this->hook ), 'awaymode_content_3' )
				),
				BWPS_PU . 'images/shield-large.png',
				$bwpstabs
			);
		}
		
		/**
		 * Registers content blocks for ban hosts page
		 *
		 **/
		function admin_banusers() {

			global $bwpstabs;

			$this->admin_page( 
				$this->pluginname . ' - ' . __( 'Ban Users', $this->hook ),
				array(
					array( __( 'Before You Begin', $this->hook ), 'banusers_content_1' ), //information to prevent the user from getting in trouble
					array( __( 'User and Bot Blacklist', $this->hook ), 'banusers_content_2' ), //banusers options
					array( __( 'Banned Users Configuration', $this->hook ), 'banusers_content_3' ) //banusers options
				),
				BWPS_PU . 'images/shield-large.png'
				,
				$bwpstabs
			);
		}
		
		/**
		 * Registers content blocks for content directory page
		 *
		 **/
		function admin_contentdirectory() {

			global $bwpstabs;

			$this->admin_page( 
				$this->pluginname . ' - ' . __( 'Change wp-content Directory', $this->hook ),
				array(
					array( __( 'Before You Begin', $this->hook ), 'contentdirectory_content_1' ), //information to prevent the user from getting in trouble
					array( __( 'Change The wp-content Directory', $this->hook ), 'contentdirectory_content_2' ) //contentdirectory options
				),
				BWPS_PU . 'images/shield-large.png',
				$bwpstabs
			);
		}
		
		/**
		 * Registers content blocks for database backup page
		 *
		 **/
		function admin_databasebackup() {

			global $bwpstabs;

			$this->admin_page( 
				$this->pluginname . ' - ' . __( 'Backup WordPress Database', $this->hook ),
				array(
					array( __( 'Before You Begin', $this->hook ), 'databasebackup_content_1' ), //information to prevent the user from getting in trouble
					array( __( 'Backup Your WordPress Database', $this->hook ), 'databasebackup_content_2' ), //backup switch
					array( __( 'Schedule Automated Backups', $this->hook ), 'databasebackup_content_3' ), //scheduled backup options
					array( __( 'Backup Information', $this->hook ), 'databasebackup_content_4' ) //where to find downloads
				),
				BWPS_PU . 'images/shield-large.png',
				$bwpstabs
			);
		}
		
		/**
		 * Registers content blocks for database prefix page
		 *
		 **/
		function admin_databaseprefix() {

			global $bwpstabs;

			$this->admin_page( 
				$this->pluginname . ' - ' . __( 'Change Database Prefix', $this->hook ),
				array(
					array( __( 'Before You Begin', $this->hook ), 'databaseprefix_content_1' ), //information to prevent the user from getting in trouble
					array( __( 'Change The Database Prefix', $this->hook ), 'databaseprefix_content_2' ) //databaseprefix options
				),
				BWPS_PU . 'images/shield-large.png',
				$bwpstabs
			);
		}
		
		/**
		 * Registers content blocks for hide backend page
		 *
		 **/
		function admin_hidebackend() {

			global $bwpstabs;

			$this->admin_page( 
				$this->pluginname . ' - ' . __( 'Hide WordPress Backend', $this->hook ),
				array(
					array( __( 'Before You Begin', $this->hook ), 'hidebackend_content_1' ), //information to prevent the user from getting in trouble
					array( __( 'Hide Backend Options', $this->hook ), 'hidebackend_content_2' ), //hidebackend options
					array( __( 'Secret Key', $this->hook ), 'hidebackend_content_3' ) //hidebackend secret key information 
				),
				BWPS_PU . 'images/shield-large.png',
				$bwpstabs
			);
		}
		
		/**
		 * Registers content blocks for intrusion detection page
		 *
		 **/
		function admin_intrusiondetection() {
		
			global $bwpsoptions, $bwpstabs;
		
			if ( $bwpsoptions['id_fileenabled'] == 1 && defined( 'BWPS_FILECHECK' ) && BWPS_FILECHECK === true ) {
			
				$this->admin_page( 
					$this->pluginname . ' - ' . __( 'Intrusion Detection', $this->hook ),
					array(
						array( __( 'Before You Begin', $this->hook ), 'intrusiondetection_content_1' ), //information to prevent the user from getting in trouble
						array( __( 'Check For File Changes', $this->hook ), 'intrusiondetection_content_2' ), //Manually check for file changes						
						array( __( 'Intrusion Detection', $this->hook ), 'intrusiondetection_content_3' ) //intrusiondetection options
					),
					BWPS_PU . 'images/shield-large.png',
					$bwpstabs
				);
				
			} else {
			
				$this->admin_page( 
					$this->pluginname . ' - ' . __( 'Intrusion Detection', $this->hook ),
					array(
						array( __( 'Before You Begin', $this->hook ), 'intrusiondetection_content_1' ), //information to prevent the user from getting in trouble
						array( __( 'Intrusion Detection', $this->hook ), 'intrusiondetection_content_3' ) //intrusiondetection options
					),
					BWPS_PU . 'images/shield-large.png',
					$bwpstabs
				);
			
			}
			
		}
		
		/**
		 * Registers content blocks for login limits page
		 *
		 **/
		function admin_loginlimits() {

			global $bwpstabs;

			$this->admin_page( 
				$this->pluginname . ' - ' . __( 'Limit Login Attempts', $this->hook ),
				array(
					array( __( 'Before You Begin', $this->hook ), 'loginlimits_content_1' ), //information to prevent the user from getting in trouble
					array( __( 'Limit Login Attempts', $this->hook ), 'loginlimits_content_2' ) //loginlimit options
				),
				BWPS_PU . 'images/shield-large.png',
				$bwpstabs
			);
		}
		
		/**
		 * Registers content blocks for SSL page
		 *
		 **/
		function admin_ssl() {

			global $bwpstabs;

			$this->admin_page( 
				$this->pluginname . ' - ' . __( 'SSL', $this->hook ),
				array(
					array( __( 'Before You Begin', $this->hook ), 'ssl_content_1' ), //information to prevent the user from getting in trouble
					array( __( 'SSL Options', $this->hook ), 'ssl_content_2' ) //ssl options
					
				),
				BWPS_PU . 'images/shield-large.png',
				$bwpstabs
			);
		}
		
		/**
		 * Registers content blocks for system tweaks page
		 *
		 **/
		function admin_systemtweaks() {
			
			global $bwpstabs;

			$this->admin_page( 
				$this->pluginname . ' - ' . __( 'Various Security Tweaks', $this->hook ),
				array(
					array( __( 'Before You Begin', $this->hook ), 'systemtweaks_content_1' ), //information to prevent the user from getting in trouble
					array( __( 'System Tweaks', $this->hook ), 'systemtweaks_content_2' ) //systemtweaks htaccess (or other rewrite) options
					
				),
				BWPS_PU . 'images/shield-large.png',
				$bwpstabs
			);
		}
		
		/**
		 * Registers content blocks for view logs page
		 *
		 **/
		function admin_logs() {

			global $bwpstabs;
					
			$this->admin_page( 
				$this->pluginname . ' - ' . __( 'Better WP Security Logs', $this->hook ),
				array(
					array( __( 'Before You Begin', $this->hook ), 'logs_content_1' ), //information to prevent the user from getting in trouble
					array( __( 'Clean Database', $this->hook ), 'logs_content_2' ), //Clean Database
					array( __( 'Current Lockouts', $this->hook ), 'logs_content_3' ), //Current Lockouts log
					array( __( '404 Errors', $this->hook ), 'logs_content_4' ), //404 Errors
					array( __( 'Bad Login Attempts', $this->hook ), 'logs_content_7' ), //404 Errors
					array( __( 'All Lockouts', $this->hook ), 'logs_content_5' ), //All Lockouts
					array( __( 'Changed Files', $this->hook ), 'logs_content_6' ) //Changed Files
				
				),
				BWPS_PU . 'images/shield-large.png',
				$bwpstabs
			);
		}
		
		/**
		 * Dashboard intro prior to first backup
		 *
		 **/
		function dashboard_content_1() {
			?>
			<p><?php _e( 'Welcome to Better WP Security!', $this->hook ); ?></p>
			<p><?php echo __( 'Before we begin it is extremely important that you make a backup of your database. This will make sure you can get your site back to the way it is right now should something go wrong. Click the button below to make a backup which will be emailed to the website administrator at ', $this->hook ) . '<strong>' . get_option( 'admin_email' ) . '</strong>'; ?></p>
			<form method="post" action="">
				<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
				<input type="hidden" name="bwps_page" value="dashboard_1" />
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Create Database Backup', $this->hook ); ?>" /></p>			
			</form>
			<form method="post" action="">
				<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
				<input type="hidden" name="bwps_page" value="dashboard_2" />
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'No, thanks. I already have a backup', $this->hook ); ?>" /></p>			
			</form>
			<?php
		}
		
		/**
		 * Ask the user if they want the plugin to automatically write to system files
		 *
		 **/
		function dashboard_content_2() {
			?>
			<p><?php _e( 'Just one more question:', $this->hook ); ?></p>
			<p><?php _e( 'Better WP Security can automatically write to WordPress core files for you (wp-config.php and .htaccess). This saves time and prevents you from having to edit code yourself. While this is safe to do in nearly all systems it can, on some server configurations, cause problems. For this reason, before continuing, you have the option to allow this plugin to write to wp-config.php and .htaccess or not.', $this->hook ); ?></p>
			<p><?php _e( 'Note, that this option can be changed later in the "System Tweaks" menu of this plugin. In addition, disabling file writes here will prevent this plugin from activation features such as changing the wp-content directory and changing the database prefix.', $this->hook ); ?></p>
			<p><?php _e( 'Finally, please remember that in nearly all cases there is no issue with allowing this plugin to edit your files. However if you know your have a unique server setup or simply would rather edit these files yourself I would recommend selecting "Do not allow this plugin to change WordPress core files."', $this->hook ); ?></p>
			<form method="post" action="">
				<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
				<input type="hidden" name="bwps_page" value="dashboard_3" />
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Allow this plugin to change WordPress core files', $this->hook ); ?>" /></p>			
			</form>
			<form method="post" action="">
				<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
				<input type="hidden" name="bwps_page" value="dashboard_4" />
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Do not allow this plugin to change WordPress core files.', $this->hook ); ?>" /></p>			
			</form>
			<?php
		}
		
		/**
		 * One-click mode
		 *
		 * Information and form to turn on basic security with 1-click
		 *
		 **/
		function dashboard_content_3() {
			?>
			<form method="post" action="">
				<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
				<input type="hidden" name="bwps_page" value="dashboard_5" />
				<input type="hidden" name="oneclick" value="1" />
				<p><?php _e( 'The button below will turn on all the basic features of Better WP Security which will help automatically protect your site from potential attacks. Please note that it will NOT automatically activate any features which may interfere with other plugins, themes, or content on your site. As such, not all the items in the status will turn green by using the "Secure My Site From Basic Attacks" button. The idea is to activate basic features in one-click so you don\'t have to worry about it.', $this->hook ); ?></p>
				<p><?php _e( 'Please note this will not make any changes to any files on your site including .htaccess and wp-config.php.', $this->hook ); ?></p>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Secure My Site From Basic Attacks', $this->hook ); ?>" /></p>
			</form>	
			<form method="post" action = "">
				<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
				<input type="hidden" name="bwps_page" value="dashboard_5" />
				<input type="hidden" name="oneclick" value="0" />
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'No thanks, I prefer to do configure everything myself.', $this->hook ); ?>" /></p>			
			</form>
			<?php
		}
		
		/**
		 * Better WP Security System Status
		 *
		 **/
		function dashboard_content_4() {
			global $wpdb, $bwpsoptions, $bwpsmemlimit;
			?>
			<ol>
				<li class="securecheck">
					<?php 
						$isOn = $bwpsoptions['st_enablepassword'];
						$role = $bwpsoptions['st_passrole']; 
					?>
					<?php if ( $isOn == 1 && $role == 'subscriber' ) { ?>
						<span style="color: green;"><?php _e( 'You are enforcing strong passwords for all users.', $this-> hook ); ?></span>
					<?php } elseif ( $isOn == 1 ) { ?>
						<span style="color: orange;"><?php _e( 'You are enforcing strong passwords, but not for all users.', $this-> hook ); ?> <a href="admin.php?page=better-wp-security-systemtweaks#st_passrole"><?php _e( 'Click here to fix.', $this-> hook ); ?></a></span>					
					<?php } else { ?>
						<span style="color: red;"><?php _e( 'You are not enforcing strong passwords.', $this-> hook ); ?> <a href="admin.php?page=better-wp-security-systemtweaks#st_enablepassword"><?php _e( 'Click here to fix.', $this-> hook ); ?></a></span>
					<?php } ?>
				</li>
				<li class="securecheck">
					<?php $hcount = intval( $bwpsoptions['st_manifest'] ) + intval( $bwpsoptions['st_generator'] ) + intval( $bwpsoptions['st_edituri'] ); ?>
					<?php if ( $hcount == 3 ) { ?>
						<span style="color: green;"><?php _e( 'Your WordPress header is revealing as little information as possible.', $this-> hook ); ?></span>
					<?php } elseif ( $hcount > 0 ) { ?>
						<span style="color: blue;"><?php _e( 'Your WordPress header is still revealing some information to users.', $this-> hook ); ?> <a href="admin.php?page=better-wp-security-systemtweaks#st_generator"><?php _e( 'Click here to fix.', $this-> hook ); ?></a></span>					
					<?php } else { ?>
						<span style="color: red;"><?php _e( 'Your WordPress header is showing too much information to users.', $this-> hook ); ?> <a href="admin.php?page=better-wp-security-systemtweaks#st_generator"><?php _e( 'Click here to fix.', $this-> hook ); ?></a></span>
					<?php } ?>
				</li>
				<li class="securecheck">
					<?php $hcount = intval( $bwpsoptions['st_themenot'] ) + intval( $bwpsoptions['st_pluginnot'] ) + intval( $bwpsoptions['st_corenot'] ); ?>
					<?php if ( $hcount == 3 ) { ?>
						<span style="color: green;"><?php _e( 'Non-administrators cannot see available updates.', $this-> hook ); ?></span>
					<?php } elseif ( $hcount > 0 ) { ?>
						<span style="color: orange;"><?php _e( 'Non-administrators can see some updates.', $this-> hook ); ?> <a href="admin.php?page=better-wp-security-systemtweaks#st_themenot"><?php _e( 'Click here to fix.', $this-> hook ); ?></a></span>					
					<?php } else { ?>
						<span style="color: red;"><?php _e( 'Non-administrators can see all updates.', $this-> hook ); ?> <a href="admin.php?page=better-wp-security-systemtweaks#st_themenot"><?php _e( 'Click here to fix.', $this-> hook ); ?></a></span>
					<?php } ?>
				</li>
				<li class="securecheck">
					<?php if ( $this->user_exists( 'admin' ) ) { ?>
						<span style="color: red;"><?php _e( 'The <em>admin</em> user still exists.', $this-> hook ); ?> <a href="admin.php?page=better-wp-security-adminuser"><?php _e( 'Click here to rename admin.', $this-> hook ); ?></a></span>
					<?php } else { ?>
						<span style="color: green;"><?php _e( 'The <em>admin</em> user has been removed.', $this-> hook ); ?></span>
					<?php } ?>
				</li>
				<li class="securecheck">
					<?php if ( $this->user_exists( '1' ) ) { ?>
						<span style="color: red;"><?php _e( 'A user with id 1 still exists.', $this-> hook ); ?> <a href="admin.php?page=better-wp-security-adminuser"><?php _e( 'Click here to change user 1\'s ID.', $this-> hook ); ?></a></span>
					<?php } else { ?>
						<span style="color: green;"><?php _e( 'The user with id 1 has been removed.', $this-> hook ); ?></span>
					<?php } ?>
				</li>
				<?php if ( $bwpsoptions['st_writefiles'] == 1 ) { ?>
					<li class="securecheck">
						<?php if ( $wpdb->base_prefix == 'wp_' ) { ?>
							<span style="color: red;"><?php _e( 'Your table prefix should not be ', $this->hook ); ?><em>wp_</em>. <a href="admin.php?page=better-wp-security-databaseprefix"><?php _e( 'Click here to rename it.', $this->hook ); ?></a></span>
						<?php } else { ?>
							<span style="color: green;"><?php echo __( 'Your table prefix is', $this->hook ) . ' ' . $wpdb->base_prefix; ?></span>
						<?php } ?>
					</li>
				<?php } ?>
				<li class="securecheck">
					<?php if ( $bwpsoptions['backup_enabled'] == 1 ) { ?>
						<span style="color: green;"><?php _e( 'You have scheduled regular backups of your WordPress database.', $this->hook ); ?></span>
					<?php } else { ?>
						<span style="color: blue;"><?php _e( 'You are not scheduling regular backups of your WordPress database.', $this->hook ); ?> <a href="admin.php?page=better-wp-security-databasebackup"><?php _e( 'Click here to fix.', $this->hook ); ?></a></span>
					<?php } ?>
				</li>
				<li class="securecheck">
					<?php if ( $bwpsoptions['am_enabled'] == 1 ) { ?>
						<span style="color: green;"><?php _e( 'Your WordPress admin area is not available when you will not be needing it.', $this->hook ); ?>. </span>
					<?php } else { ?>
						<span style="color: orange;"><?php _e( 'Your WordPress admin area is available 24/7. Do you really update 24 hours a day?', $this->hook ); ?> <a href="admin.php?page=better-wp-security-awaymode"><?php _e( 'Click here to fix.', $this->hook ); ?></a></span>
					<?php } ?>
				</li>
				<li class="securecheck">
					<?php if ( $bwpsoptions['bu_blacklist'] == 1 ) { ?>
						<span style="color: green;"><?php _e( 'You are blocking known bad hosts and agents with HackRepair.com\'s blacklist.', $this->hook ); ?>. </span>
					<?php } else { ?>
						<span style="color: orange;"><?php _e( 'You are not blocking known bad hosts and agents with HackRepair.com\'s blacklist?', $this->hook ); ?> <a href="admin.php?page=better-wp-security-banusers"><?php _e( 'Click here to fix.', $this->hook ); ?></a></span>
					<?php } ?>
				</li>
				<li class="securecheck">
					<?php if ( $bwpsoptions['ll_enabled'] == 1 ) { ?>
						<span style="color: green;"><?php _e( 'Your login area is protected from brute force attacks.', $this->hook ); ?></span>
					<?php } else { ?>
						<span style="color: red;"><?php _e( 'Your login area is not protected from brute force attacks.', $this->hook ); ?> <a href="admin.php?page=better-wp-security-loginlimits"><?php _e( 'Click here to fix.', $this->hook ); ?></a></span>
					<?php } ?>
				</li>
				<li class="securecheck">
					<?php if ( $bwpsoptions['hb_enabled'] == 1 ) { ?>
						<span style="color: green;"><?php _e( 'Your WordPress admin area is hidden.', $this->hook ); ?></span>
					<?php } else { ?>
						<span style="color: blue;"><?php _e( 'Your WordPress admin area is not hidden.', $this->hook ); ?> <a href="admin.php?page=better-wp-security-hidebackend"><?php _e( 'Click here to fix.', $this->hook ); ?></a></span>
					<?php } ?>
				</li>
				<li class="securecheck">
					<?php $hcount = intval( $bwpsoptions['st_ht_files'] ) + intval( $bwpsoptions['st_ht_browsing'] ) + intval( $bwpsoptions['st_ht_request'] ) + intval( $bwpsoptions['st_ht_query'] ); ?>
					<?php if ( $hcount == 4 ) { ?>
						<span style="color: green;"><?php _e( 'Your .htaccess file is fully secured.', $this-> hook ); ?></span>
					<?php } elseif ( $hcount > 0 ) { ?>
						<span style="color: blue;"><?php _e( 'Your .htaccess file is partially secured.', $this-> hook ); ?> <a href="admin.php?page=better-wp-security-systemtweaks#st_ht_files"><?php _e( 'Click here to fix.', $this-> hook ); ?></a></span>					
					<?php } else { ?>
						<span style="color: blue;"><?php _e( 'Your .htaccess file is NOT secured.', $this-> hook ); ?> <a href="admin.php?page=better-wp-security-systemtweaks#st_ht_files"><?php _e( 'Click here to fix.', $this-> hook ); ?></a></span>
					<?php } ?>
				</li>
				<li class="securecheck">
					<?php if ( $bwpsoptions['id_enabled'] == 1 ) { ?>
						<span style="color: green;"><?php _e( 'Your installation is actively blocking attackers trying to scan your site for vulnerabilities.', $this->hook ); ?></span>
					<?php } else { ?>
						<span style="color: red;"><?php _e( 'Your installation is not actively blocking attackers trying to scan your site for vulnerabilities.', $this->hook ); ?> <a href="admin.php?page=better-wp-security-intrusiondetection"><?php _e( 'Click here to fix.', $this->hook ); ?></a></span>
					<?php } ?>
				</li>
				<li class="securecheck">
					<?php if ( $bwpsoptions['id_fileenabled'] == 1 ) { ?>
						<span style="color: green;"><?php _e( 'Your installation is actively looking for changed files.', $this->hook ); ?></span>
					<?php } else { ?>
						<?php
							if ( $bwpsmemlimit >= 128 ) {
								$idfilecolor = 'red';
							} else {
								$idfilecolor = 'blue';
							}
						?>
						<span style="color: <?php echo $idfilecolor; ?>;"><?php _e( 'Your installation is not actively looking for changed files.', $this->hook ); ?> <a href="admin.php?page=better-wp-security-intrusiondetection#id_fileenabled"><?php _e( 'Click here to fix.', $this->hook ); ?></a></span>
					<?php } ?>
				</li>
				<li class="securecheck">
					<?php if ( $bwpsoptions['st_longurl'] == 1 ) { ?>
						<span style="color: green;"><?php _e( 'Your installation does not accept long URLs.', $this->hook ); ?></span>
					<?php } else { ?>
						<span style="color: blue;"><?php _e( 'Your installation accepts long (over 255 character) URLS. This can lead to vulnerabilities.', $this->hook ); ?> <a href="admin.php?page=better-wp-security-systemtweaks#st_longurl"><?php _e( 'Click here to fix.', $this->hook ); ?></a></span>
					<?php } ?>
				</li>
				<li class="securecheck">
					<?php if ( $bwpsoptions['st_fileedit'] == 1 ) { ?>
						<span style="color: green;"><?php _e( 'You are not allowing users to edit theme and plugin files from the WordPress backend.', $this->hook ); ?></span>
					<?php } else { ?>
						<span style="color: blue;"><?php _e( 'You are allowing users to edit theme and plugin files from the WordPress backend.', $this->hook ); ?> <a href="admin.php?page=better-wp-security-systemtweaks#st_fileedit"><?php _e( 'Click here to fix.', $this->hook ); ?></a></span>
					<?php } ?>
				</li>
				<li class="securecheck">
					<?php if ( $bwpsoptions['st_writefiles'] == 1 ) { ?>
						<span style="color: green;"><?php _e( 'Better WP Security is allowed to write to wp-config.php and .htaccess.', $this->hook ); ?></span>
					<?php } else { ?>
						<span style="color: blue;"><?php _e( 'Better WP Security is not allowed to write to wp-config.php and .htaccess.', $this->hook ); ?> <a href="admin.php?page=better-wp-security-systemtweaks#st_writefiles"><?php _e( 'Click here to fix.', $this->hook ); ?></a></span>
					<?php } ?>
				</li>
				<li class="securecheck">
					<?php if ( $bwpsoptions['st_fileperm'] == 1 ) { ?>
						<span style="color: green;"><?php _e( 'wp-config.php and .htacess are not writeable.', $this->hook ); ?></span>
					<?php } else { ?>
						<span style="color: blue;"><?php _e( 'wp-config.php and .htacess are writeable.', $this->hook ); ?> <a href="admin.php?page=better-wp-security-systemtweaks#st_fileperm"><?php _e( 'Click here to fix.', $this->hook ); ?></a></span>
					<?php } ?>
				</li>
				<li class="securecheck">
					<?php if ( $bwpsoptions['st_randomversion'] == 1 ) { ?>
						<span style="color: green;"><?php _e( 'Version information is obscured to all non admin users.', $this->hook ); ?></span>
					<?php } else { ?>
						<span style="color: blue;"><?php _e( 'Users may still be able to get version information from various plugins and themes.', $this->hook ); ?> <a href="admin.php?page=better-wp-security-systemtweaks#st_randomversion"><?php _e( 'Click here to fix.', $this->hook ); ?></a></span>
					<?php } ?>
				</li>
				<?php if ( $bwpsoptions['st_writefiles'] == 1 ) { ?>
					<li class="securecheck">
						<?php if ( ! strstr( WP_CONTENT_DIR, 'wp-content' ) || ! strstr( WP_CONTENT_URL, 'wp-content' ) ) { ?>
							<span style="color: green;"><?php _e( 'You have renamed the wp-content directory of your site.', $this->hook ); ?></span>
						<?php } else { ?>
							<span style="color: blue;"><?php _e( 'You should rename the wp-content directory of your site.', $this->hook ); ?> <a href="admin.php?page=better-wp-security-contentdirectory"><?php _e( 'Click here to do so.', $this->hook ); ?></a></span>
						<?php } ?>
					</li>
				<?php } ?>
				<li class="securecheck">
					<?php if ( FORCE_SSL_LOGIN === true && FORCE_SSL_ADMIN === true ) { ?>
						<span style="color: green;"><?php _e( 'You are requiring a secure connection for logins and the admin area.', $this-> hook ); ?></span>
					<?php } elseif ( FORCE_SSL_LOGIN === true || FORCE_SSL_ADMIN === true ) { ?>
						<span style="color: blue;"><?php _e( 'You are requiring a secure connection for logins or the admin area but not both.', $this-> hook ); ?> <a href="admin.php?page=better-wp-security-ssl#ssl_frontend"><?php _e( 'Click here to fix.', $this-> hook ); ?></a></span>	
					<?php } else { ?>
						<span style="color: blue;"><?php _e( 'You are not requiring a secure connection for logins or for the admin area.', $this-> hook ); ?> <a href="admin.php?page=better-wp-security-ssl#ssl_frontend"><?php _e( 'Click here to fix.', $this-> hook ); ?></a></span>
					<?php } ?>
				</li>
				<?php if ( $bwpsoptions['st_writefiles'] == 0 ) { ?>
					<li class="securecheck">
						<span style="color: orange;"><?php _e( 'Notice: Some items are hidden as you are not allowing this plugin to write to core files.', $this->hook ); ?></span> <a href="admin.php?page=better-wp-security-systemtweaks#st_writefiles"><?php _e( 'Click here to fix.', $this->hook ); ?></a></span>
					</li>
				<?php } ?>
			</ol>
			<hr />
			<ul>
				<li><span style="color: green;"><?php _e( 'Items in green are fully secured. Good Job!', $this->hook ); ?></span></li>
				<li><span style="color: orange;"><?php _e( 'Items in orange are partially secured. Turn on more options to fully secure these areas.', $this->hook ); ?></span></li>
				<li><span style="color: red;"><?php _e( 'Items in red are not secured. You should secure these items immediately', $this->hook ); ?></span></li>
				<li><span style="color: blue;"><?php _e( 'Items in blue are not fully secured but may conflict with other themes, plugins, or the other operation of your site. Secure them if you can but if you cannot do not worry about them.', $this->hook ); ?></span></li>
			</ul>
			<?php
		}
		
		/**
		 * Rewrite rules
		 *
		 * Rewrite rules generated by better wp security
		 *
		 **/
		function dashboard_content_5() {
			
			$rules = $this->getrules();
			
			if ( $rules == '') {
				?>
				<p><?php _e( 'No rules have been generated. Turn on more features to see rewrite rules.', $this->hook ); ?></p>
				<?php
			} else {
				?>
				<style type="text/css"> 
					code { 
						overflow-x: auto; /* Use horizontal scroller if needed; for Firefox 2, not needed in Firefox 3 */ 
						overflow-y: hidden; 
						background-color: transparent; 
						white-space: pre-wrap; /* css-3 */ 
						white-space: -moz-pre-wrap !important; /* Mozilla, since 1999 */ 
						white-space: -pre-wrap; /* Opera 4-6 */ 
						white-space: -o-pre-wrap; /* Opera 7 */ 
						/* width: 99%; */ 
						word-wrap: break-word; /* Internet Explorer 5.5+ */ 
					}
				</style> 
				<?php echo highlight_string( $rules, true ); ?> 
				<?php
			}
			
		}
		
		/**
		 * wp-content.php Rules
		 *
		 * wp-content.php generated by better wp security
		 *
		 **/
		function dashboard_content_6() {
			
			$rules = $this->getwpcontent();
			
			if ( $rules == '') {
				?>
				<p><?php _e( 'No rules have been generated. Turn on more features to see wp-content rules.', $this->hook ); ?></p>
				<?php
			} else {
				?>
				<textarea style="width: 100%; height: 300px;"><?php echo $rules; ?></textarea>
				
				<?php
			}
			
		}
		
		
		/**
		 * General System Information
		 *
		 **/
		function dashboard_content_7() {
			global $bwps, $wpdb, $bwpsoptions, $bwpsdata;
			?>
			<ul>
				<li>
					<h4><?php _e( 'User Information', $this->hook ); ?></h4>
					<ul>
						<li><?php _e( 'Public IP Address', $this->hook ); ?>: <strong><a target="_blank" title="<?php _e( 'Get more information on this address', $this->hook ); ?>" href="http://whois.domaintools.com/<?php echo $bwps->getIp(); ?>"><?php echo $bwps->getIp(); ?></a></strong></li>
						<li><?php _e( 'User Agent', $this->hook ); ?>: <strong><?php echo filter_var( $_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_STRING ); ?></strong></li>
					</ul>
				</li>
				
				<li>
					<h4><?php _e( 'File System Information', $this->hook ); ?></h4>
					<ul>
						<li><?php _e( 'Website Root Folder', $this->hook ); ?>: <strong><?php echo get_site_url(); ?></strong></li>
						<li><?php _e( 'Document Root Path', $this->hook ); ?>: <strong><?php echo filter_var( $_SERVER['DOCUMENT_ROOT'], FILTER_SANITIZE_STRING ); ?></strong></li>
						<?php 
							$htaccess = ABSPATH . '.htaccess';
							
							if ( $f = @fopen( $htaccess, 'a' ) ) { 
							
								@fclose( $f );
								$copen = '<font color="red">';
								$cclose = '</font>';
								$htaw = __( 'Yes', $this->hook ); 
								
							} else {
							
								$copen = '';
								$cclose = '';
								$htaw = __( 'No.', $this->hook ); 
								
							}
							
							if ( $bwpsoptions['st_fileperm'] == 1 ) {
								@chmod( $htaccess, 0444 ); //make sure the config file is no longer writable
							}
						?>
						<li><?php _e( '.htaccess File is Writable', $this->hook ); ?>: <strong><?php echo $copen . $htaw . $cclose; ?></strong></li>
						<?php 
							$conffile = $this->getConfig();
							
							if ( $f = @fopen( $conffile, 'a' ) ) { 
							
								@fclose( $f );
								$copen = '<font color="red">';
								$cclose = '</font>';
								$wconf = __( 'Yes', $this->hook ); 
								
							} else {
							
								$copen = '';
								$cclose = '';
								$wconf = __( 'No.', $this->hook ); 
								
							}
							
							if ( $bwpsoptions['st_fileperm'] == 1 ) {
								@chmod( $conffile, 0444 ); //make sure the config file is no longer writable
							}
						?>
						<li><?php _e( 'wp-config.php File is Writable', $this->hook ); ?>: <strong><?php echo $copen . $wconf . $cclose; ?></strong></li>
					</ul>
				</li>
			
				<li>
					<h4><?php _e( 'Database Information', $this->hook ); ?></h4>
					<ul>
						<li><?php _e( 'MySQL Database Version', $this->hook ); ?>: <?php $sqlversion = $wpdb->get_var( "SELECT VERSION() AS version" ); ?><strong><?php echo $sqlversion; ?></strong></li>
						<li><?php _e( 'MySQL Client Version', $this->hook ); ?>: <strong><?php echo mysql_get_client_info(); ?></strong></li>
						<li><?php _e( 'Database Host', $this->hook ); ?>: <strong><?php echo DB_HOST; ?></strong></li>
						<li><?php _e( 'Database Name', $this->hook ); ?>: <strong><?php echo DB_NAME; ?></strong></li>
						<li><?php _e( 'Database User', $this->hook ); ?>: <strong><?php echo DB_USER; ?></strong></li>
						<?php $mysqlinfo = $wpdb->get_results( "SHOW VARIABLES LIKE 'sql_mode'" );
							if ( is_array( $mysqlinfo ) ) $sql_mode = $mysqlinfo[0]->Value;
							if ( empty( $sql_mode ) ) $sql_mode = __( 'Not Set', $this->hook );
							else $sql_mode = __( 'Off', $this->hook );
						?>
						<li><?php _e( 'SQL Mode', $this->hook ); ?>: <strong><?php echo $sql_mode; ?></strong></li>
					</ul>
				</li>
				
				<li>
					<h4><?php _e( 'Server Information', $this->hook ); ?></h4>
					<?php $server_addr = array_key_exists('SERVER_ADDR',$_SERVER) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR']; ?>
					<ul>
						<li><?php _e( 'Server / Website IP Address', $this->hook ); ?>: <strong><a target="_blank" title="<?php _e( 'Get more information on this address', $this->hook ); ?>" href="http://whois.domaintools.com/<?php echo $server_addr; ?>"><?php echo $server_addr; ?></a></strong></li>
							<li><?php _e( 'Server Type', $this->hook ); ?>: <strong><?php echo filter_var( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ), FILTER_SANITIZE_STRING ); ?></strong></li>
							<li><?php _e( 'Operating System', $this->hook ); ?>: <strong><?php echo PHP_OS; ?></strong></li>
							<li><?php _e( 'Browser Compression Supported', $this->hook ); ?>: <strong><?php echo filter_var( $_SERVER['HTTP_ACCEPT_ENCODING'], FILTER_SANITIZE_STRING ); ?></strong></li>
					</ul>
				</li>
				
				<li>
					<h4><?php _e( 'PHP Information', $this->hook ); ?></h4>
					<ul>
						<li><?php _e( 'PHP Version', $this->hook ); ?>: <strong><?php echo PHP_VERSION; ?></strong></li>
						<li><?php _e( 'PHP Memory Usage', $this->hook ); ?>: <strong><?php echo round(memory_get_usage() / 1024 / 1024, 2) . __( ' MB', $this->hook ); ?></strong> </li>
						<?php 
							if ( ini_get( 'memory_limit' ) ) {
								$memory_limit = filter_var( ini_get( 'memory_limit' ), FILTER_SANITIZE_STRING ); 
							} else {
								$memory_limit = __( 'N/A', $this->hook ); 
							}
						?>
						<li><?php _e( 'PHP Memory Limit', $this->hook ); ?>: <strong><?php echo $memory_limit; ?></strong></li>
						<?php 
							if ( ini_get( 'upload_max_filesize' ) ) {
								$upload_max = filter_var( ini_get( 'upload_max_filesize' ), FILTER_SANITIZE_STRING );
							} else 	{
								$upload_max = __( 'N/A', $this->hook ); 
							}
						?>
						<li><?php _e( 'PHP Max Upload Size', $this->hook ); ?>: <strong><?php echo $upload_max; ?></strong></li>
						<?php 
							if ( ini_get( 'post_max_size' ) ) {
								$post_max = filter_var( ini_get( 'post_max_size' ), FILTER_SANITIZE_STRING );
							} else {
								$post_max = __( 'N/A', $this->hook ); 
							}
						?>
						<li><?php _e( 'PHP Max Post Size', $this->hook ); ?>: <strong><?php echo $post_max; ?></strong></li>
						<?php 
							if ( ini_get( 'safe_mode' ) ) {
								$safe_mode = __( 'On', $this->hook );
							} else {
								$safe_mode = __( 'Off', $this->hook ); 
							}
						?>
						<li><?php _e( 'PHP Safe Mode', $this->hook ); ?>: <strong><?php echo $safe_mode; ?></strong></li>
						<?php 
							if ( ini_get( 'allow_url_fopen' ) ) {
								$allow_url_fopen = __( 'On', $this->hook );
							} else {
								$allow_url_fopen = __( 'Off', $this->hook ); 
							}
						?>
						<li><?php _e( 'PHP Allow URL fopen', $this->hook ); ?>: <strong><?php echo $allow_url_fopen; ?></strong></li>
						<?php 
							if ( ini_get( 'allow_url_include' ) ) {
								$allow_url_include = __( 'On', $this->hook );
							} else {
								$allow_url_include = __( 'Off', $this->hook ); 
							}
						?>
						<li><?php _e( 'PHP Allow URL Include' ); ?>: <strong><?php echo $allow_url_include; ?></strong></li>
							<?php 
							if ( ini_get( 'display_errors' ) ) {
								$display_errors = __( 'On', $this->hook );
							} else {
								$display_errors = __( 'Off', $this->hook ); 
							}
						?>
						<li><?php _e( 'PHP Display Errors', $this->hook ); ?>: <strong><?php echo $display_errors; ?></strong></li>
						<?php 
							if ( ini_get( 'display_startup_errors' ) ) {
								$display_startup_errors = __( 'On', $this->hook );
							} else {
								$display_startup_errors = __( 'Off', $this->hook ); 
							}
						?>
						<li><?php _e( 'PHP Display Startup Errors', $this->hook ); ?>: <strong><?php echo $display_startup_errors; ?></strong></li>
						<?php 
							if ( ini_get( 'expose_php' ) ) {
								$expose_php = __( 'On', $this->hook );
							} else {
								$expose_php = __( 'Off', $this->hook ); 
							}
						?>
						<li><?php _e( 'PHP Expose PHP', $this->hook ); ?>: <strong><?php echo $expose_php; ?></strong></li>
						<?php 
							if ( ini_get( 'register_globals' ) ) {
								$register_globals = __( 'On', $this->hook );
							} else {
								$register_globals = __( 'Off', $this->hook ); 
							}
						?>
						<li><?php _e( 'PHP Register Globals', $this->hook ); ?>: <strong><?php echo $register_globals; ?></strong></li>
						<?php 
							if ( ini_get( 'max_execution_time' ) ) {
								$max_execute = ini_get( 'max_execution_time' );
							} else {
								$max_execute = __( 'N/A', $this->hook ); 
							}
						?>
						<li><?php _e( 'PHP Max Script Execution Time' ); ?>: <strong><?php echo $max_execute; ?> <?php _e( 'Seconds' ); ?></strong></li>
						<?php 
							if ( ini_get( 'magic_quotes_gpc' ) ) {
								$magic_quotes_gpc = __( 'On', $this->hook );
							} else {
								$magic_quotes_gpc = __( 'Off', $this->hook ); 
							}
						?>
						<li><?php _e( 'PHP Magic Quotes GPC', $this->hook ); ?>: <strong><?php echo $magic_quotes_gpc; ?></strong></li>
						<?php 
							if ( ini_get( 'open_basedir' ) ) {
								$open_basedir = __( 'On', $this->hook );
							} else {
								$open_basedir = __( 'Off', $this->hook ); 
							}
						?>
						<li><?php _e( 'PHP open_basedir', $this->hook ); ?>: <strong><?php echo $open_basedir; ?></strong></li>
						<?php 
							if ( is_callable( 'xml_parser_create' ) ) {
								$xml = __( 'Yes', $this->hook );
							} else {
								$xml = __( 'No', $this->hook ); 
							}
						?>
						<li><?php _e( 'PHP XML Support', $this->hook ); ?>: <strong><?php echo $xml; ?></strong></li>
						<?php 
							if ( is_callable( 'iptcparse' ) ) {
								$iptc = __( 'Yes', $this->hook );
							} else {
								$iptc = __( 'No', $this->hook ); 
							}
						?>
						<li><?php _e( 'PHP IPTC Support', $this->hook ); ?>: <strong><?php echo $iptc; ?></strong></li>
						<?php 
							if ( is_callable( 'exif_read_data' ) ) {
								$exif = __( 'Yes', $this->hook ). " ( V" . substr(phpversion( 'exif' ),0,4) . ")" ;
							} else {
								$exif = __( 'No', $this->hook ); 
							}
						?>
						<li><?php _e( 'PHP Exif Support', $this->hook ); ?>: <strong><?php echo $exif; ?></strong></li>
					</ul>
				</li>
				
				<li>
					<h4><?php _e( 'WordPress Configuration', $this->hook ); ?></h4>
					<ul>
						<?php
							if ( is_multisite() ) { 
								$multSite = __( 'Multisite is enabled', $this->hook );
							} else {
								$multSite = __( 'Multisite is NOT enabled', $this->hook );
							}
							?>
							<li><?php _e( '	Multisite', $this->hook );?>: <strong><?php echo $multSite; ?></strong></li>
						<?php
							if ( get_option( 'permalink_structure' ) != '' ) { 
								$copen = '';
								$cclose = '';
								$permalink_structure = __( 'Enabled', $this->hook ); 
							} else {
								$copen = '<font color="red">';
								$cclose = '</font>';
								$permalink_structure = __( 'WARNING! Permalinks are NOT Enabled. Permalinks MUST be enabled for Better WP Security to function correctly', $this->hook ); 
							}
						?>
						<li><?php _e( 'WP Permalink Structure', $this->hook ); ?>: <strong> <?php echo $copen . $permalink_structure . $cclose; ?></strong></li>
						<li><?php _e( 'Wp-config Location', $this->hook );?>: <strong><?php echo $this->getConfig(); ?></strong></li>
					</ul>
				</li>
				<li>
					<h4><?php _e( 'Better WP Security variables', $this->hook ); ?></h4>
					<ul>
						<?php 
							if ( $bwpsoptions['hb_key'] == '' ) {
								$hbkey = __( 'Not Yet Available. Enable Hide Backend mode to generate key.', $this->hook );
							} else {
								$hbkey = $bwpsoptions['hb_key'];
							}
						?>
						<li><?php _e( 'Hide Backend Key', $this->hook );?>: <strong><?php echo $hbkey; ?></strong></li>
						<li><?php _e( 'Better WP Build Version', $this->hook );?>: <strong><?php echo $bwpsdata['version']; ?></strong><br />
						<em><?php _e( 'Note: this is NOT the same as the version number on the plugins page and is instead used for support.', $this->hook ); ?></em></li>
					</ul>
				</li>
			</ul>
			<?php
		}
	
		/**
		 * Intro content for change admin user page
		 *
		 **/
		function adminuser_content_1() {
			?>
			<p><?php _e( 'By default WordPress initially creates a username with the username of "admin." This is insecure as this user has full rights to your WordPress system and a potential hacker already knows that it is there. All an attacker would need to do at that point is guess the password. Changing this username will force a potential attacker to have to guess both your username and your password which makes some attacks significantly more difficult.', $this->hook ); ?></p>
			<p><?php _e( 'Note that this function will only work if you chose a username other than "admin" when installing WordPress.', $this->hook ); ?></p>
			<?php
		}
		
		/**
		 * Options form for change andmin user page
		 *
		 **/
		function adminuser_content_2() {
			if ( $this->user_exists( 'admin' ) ) { //only show form if user 'admin' exists
				?>
				<form method="post" action="">
					<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
					<input type="hidden" name="bwps_page" value="adminuser_1" />
					<table class="form-table">
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<label for "newuser"><?php _e( 'Enter Username', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<?php //username field ?>
								<input id="newuser" name="newuser" type="text" />
								<p><?php _e( 'Enter a new username to replace "admin." Please note that if you are logged in as admin you will have to log in again.', $this->hook ); ?></p>
							</td>
						</tr>
					</table>
					<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Chage Admin Username', $this->hook ); ?>" /></p>
				</form>
				<?php
			} else { //if their is no admin user display a note 
				?>
					<p><?php _e( 'Congratulations! You do not have a user named "admin" in your WordPress installation. No further action is available on this page.', $this->hook ); ?></p>
				<?php
			}
		}

		/**
		 * Options form for change andmin user id
		 *
		 **/
		function adminuser_content_3() {
			if ( $this->user_exists( '1' ) ) { //only show form if user 'admin' exists
				?>
				<p><?php _e( 'If your admin user has and ID of "1" it is vulnerable to some attacks. You should change the ID of your admin user.', $this->hook ); ?>
				<form method="post" action="">
					<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
					<input type="hidden" name="bwps_page" value="adminuser_2" />
					<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Change User 1 ID', $this->hook ); ?>" /></p>
				</form>
				<?php
			} else { //if their is no admin user display a note 
				?>
					<p><?php _e( 'Congratulations! You do not have a user with ID 1 in your WordPress installation. No further action is available on this page.', $this->hook ); ?></p>
				<?php
			}
		}
		
		/**
		 * Intro content for away mode page
		 *
		 **/
		function awaymode_content_1() {
			?>
			<p><?php _e( 'As many of us update our sites on a general schedule it is not always necessary to permit site access all of the time. The options below will disable the backend of the site for the specified period. This could also be useful to disable site access based on a schedule for classroom or other reasons.', $this->hook ); ?></p>
			<?php
				if ( preg_match( "/^(G|H)(:| \\h)/", get_option( 'time_format' ) ) ) { 
					$currdate = date_i18n( 'l, d F Y' . ' ' . get_option( 'time_format' ) , current_time( 'timestamp' ) );
				} else {
					$currdate = date( 'l, F jS, Y \a\\t g:i a', current_time( 'timestamp' ) );
				}
			?>
			<p><?php _e( 'Please note that according to your', $this->hook ); ?> <a href="options-general.php"><?php _e( 'WordPress timezone settings', $this->hook ); ?></a> <?php _e( 'your local time is', $this->hook ); ?> <strong><em><?php echo $currdate ?></em></strong>. <?php _e( 'If this is incorrect please correct it on the', $this->hook ); ?> <a href="options-general.php"><?php _e( 'WordPress general settings page', $this->hook ); ?></a> <?php _e( 'by setting the appropriate time zone. Failure to do so may result in unintended lockouts.', $this->hook ); ?></p>

			<?php
		}
		
		/**
		 * Options form for away mode page
		 *
		 **/
		function awaymode_content_2() {
			global $bwpsoptions;
			?>
			<form method="post" action="">
				<?php
					echo '<script language="javascript">';
					echo 'function amenable() {';
					echo 'alert( "' . __( 'Are you sure you want to enable away mode? Please check the local time (located at the top of this page) and verify the times set are correct to avoid locking yourself out of this site.', $this->hook ) . '" );';
					echo '}';
					echo '</script>';
				?>
			<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
			<input type="hidden" name="bwps_page" value="awaymode_1" />
			<?php 
				//get saved options
				$cDate = strtotime( date( 'n/j/y 12:00 \a\m', current_time( 'timestamp' ) ) );
				$sTime = $bwpsoptions['am_starttime'];
				$eTime = $bwpsoptions['am_endtime'];
				$sDate = $bwpsoptions['am_startdate'];
				$eDate = $bwpsoptions['am_enddate'];
				$shdisplay = date( 'g', $sTime );
				$shdisplay24 = date( 'G', $sTime );	// 24Hours
				$sidisplay = date( 'i', $sTime );
				$ssdisplay = date( 'a', $sTime );
				$ehdisplay = date( 'g', $eTime );
				$ehdisplay24 = date( 'G', $eTime );	// 24Hours
				$eidisplay = date( 'i', $eTime );
				$esdisplay = date( 'a', $eTime );
				
				if ( $bwpsoptions['am_enabled'] == 1 && $eDate > $cDate ) {	
				
					$smdisplay = date( 'n', $sDate );
					$sddisplay = date( 'j', $sDate );
					$sydisplay = date( 'Y', $sDate );
					
					$emdisplay = date( 'n', $eDate );
					$eddisplay = date( 'j', $eDate );
					$eydisplay = date( 'Y', $eDate );
					
				} else {
				
					$sDate = current_time( 'timestamp' ) + 86400;
					$eDate = current_time( 'timestamp' ) + ( 86400 * 2 );
					$smdisplay = date( 'n', $sDate );
					$sddisplay = date( 'j', $sDate );
					$sydisplay = date( 'Y', $sDate );
					
					$emdisplay = date( 'n', $eDate );
					$eddisplay = date( 'j', $eDate );
					$eydisplay = date( 'Y', $eDate );
					
				}
			?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "am_enabled"><?php _e( 'Enable Away Mode', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input onChange="amenable()" id="am_enabled" name="am_enabled" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['am_enabled'] ); ?> />
							<p><?php _e( 'Check this box to enable away mode.', $this->hook ); ?></p>
						</td>
					</tr>	
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for="am_type"><?php _e( 'Type of Restriction', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<label><input name="am_type" id="am_type" value="1" <?php checked( '1', $bwpsoptions['am_type'] ); ?> type="radio" /> <?php _e( 'Daily', $this->hook ); ?></label>
							<label><input name="am_type" value="0" <?php checked( '0', $bwpsoptions['am_type'] ); ?> type="radio" /> <?php _e( 'One Time', $this->hook ); ?></label>
							<p><?php _e( 'Selecting <em>"One Time"</em> will lock out the backend of your site from the start date and time to the end date and time. Selecting <em>"Daily"</em> will ignore the start and and dates and will disable your site backend from the start time to the end time.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for="am_startdate"><?php _e( 'Start Date', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<?php if ( preg_match( "/^(G|H)(:| \\h)/", get_option( 'time_format' ) ) ) { ?>
								<select name="am_startday">
									<?php
										for ( $i = 1; $i <= 31; $i++ ) { //determine default
											if ( $sddisplay == $i ) {
												$selected = ' selected';
											} else {
												$selected = '';
											}
											echo '<option value="' . $i . '"' . $selected . '>' . date( 'j', strtotime( '1/' . $i . '/' . date( 'Y', current_time( 'timestamp' ) ) ) ) . '</option>';
										}
									?>
								</select>							
							<?php } ?>
							<select name="am_startmonth" id="am_startdate">
								<?php
									for ( $i = 1; $i <= 12; $i++ ) { //determine default
										if ( $smdisplay == $i ) {
											$selected = ' selected';
										} else {
											$selected = '';
										}
										echo '<option value="' . $i . '"' . $selected . '>' . date_i18n( 'F', strtotime( $i . '/1/' . date( 'Y', current_time( 'timestamp' ) ) ) ) . '</option>';
									}
								?>
							</select> 
							<?php if ( ! preg_match( "/^(G|H)(:| \\h)/", get_option( 'time_format' ) ) ) { ?>
								<select name="am_startday">
									<?php
										for ( $i = 1; $i <= 31; $i++ ) { //determine default
											if ( $sddisplay == $i ) {
												$selected = ' selected';
											} else {
												$selected = '';
											}
											echo '<option value="' . $i . '"' . $selected . '>' . date_i18n( 'j', strtotime( '1/' . $i . '/' . date( 'Y', current_time( 'timestamp' ) ) ) ) . '</option>';
										}
									?>
								</select>, 
							<?php 

							} else {
								echo ' ';
							}

							?>
							<select name="am_startyear">
								<?php
									for ( $i = date( 'Y', current_time( 'timestamp' ) ); $i < ( date( 'Y', current_time( 'timestamp' ) ) + 2 ); $i++ ) { //determine default
										if ( $sydisplay == $i ) {
											$selected = ' selected';
										} else {
											$selected = '';
										}
										echo '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
									}
								?>
							</select>
							<p><?php _e( 'Select the date at which access to the backend of this site will be disabled. Note that if <em>"Daily"</em> mode is selected this field will be ignored and access will be banned every day at the specified time.', $this->hook ); ?>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for="am_enddate"><?php _e( 'End Date', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<?php if ( preg_match( "/^(G|H)(:| \\h)/", get_option( 'time_format' ) ) ) { ?>					
								<select name="am_endday">
									<?php
										for ( $i = 1; $i <= 31; $i++ ) { //determine default
											if ( $eddisplay == $i ) {
												$selected = ' selected';
											} else {
												$selected = '';
											}
											echo '<option value="' . $i . '"' . $selected . '>' . date( 'j', strtotime( '1/' . $i . '/' . date( 'Y', current_time( 'timestamp' ) ) ) ) . '</option>';
										}
									?>
								</select> 
							<?php } ?>
							<select name="am_endmonth" id="am_enddate">
								<?php
									for ( $i = 1; $i <= 12; $i++ ) { //determine default
										if ( $emdisplay == $i ) {
											$selected = ' selected';
										} else {
											$selected = '';
										}
										echo '<option value="' . $i . '"' . $selected . '>' . date_i18n( 'F', strtotime( $i . '/1/' . date( 'Y', current_time( 'timestamp' ) ) ) ) . '</option>';
									}
								?>
							</select> 
							<?php if ( ! preg_match( "/^(G|H)(:| \\h)/", get_option( 'time_format' ) ) ) { ?>
								<select name="am_endday">
									<?php
										for ( $i = 1; $i <= 31; $i++ ) { //determine default
											if ( $eddisplay == $i ) {
												$selected = ' selected';
											} else {
												$selected = '';
											}
											echo '<option value="' . $i . '"' . $selected . '>' . date( 'j', strtotime( '1/' . $i . '/' . date( 'Y', current_time( 'timestamp' ) ) ) ) . '</option>';
										}
									?>
								</select>, 
							<?php } ?>
							<select name="am_endyear">
								<?php
									for ( $i = date( 'Y', current_time( 'timestamp' ) ); $i < ( date( 'Y', current_time( 'timestamp' ) ) + 2 ); $i++ ) { //determine default
										if ( $eydisplay == $i ) {
											$selected = ' selected';
										} else {
											$selected = '';
										}
										echo '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
									}
								?>
							</select>
							<p><?php _e( 'Select the date at which access to the backend of this site will be re-enabled. Note that if <em>"Daily"</em> mode is selected this field will be ignored and access will be banned every day at the specified time.', $this->hook ); ?>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for="am_starttime"><?php _e( 'Start Time', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<select name="am_starthour" id="am_starttime">
								<?php
									if ( preg_match( "/^(G|H)(:| \\h)/", get_option( 'time_format' ) ) ) {
										for ( $i = 0; $i <= 23; $i++ ) { //determine default
											if ( $shdisplay24 == $i ) {
												$selected = ' selected';
											} else {
												$selected = '';
											}
											if ( $i < 10 ) {
												$val = "0" . $i;
											} else {
												$val = $i;
											}
											echo '<option value="' . $val  . '"' . $selected . '>' . $val . '</option>';	
										}
									} else {
										for ( $i = 1; $i <= 12; $i++ ) { //determine default
											if ( $shdisplay == $i ) {
												$selected = ' selected';
											} else {
												$selected = '';
											}
											echo '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
										}
									}
								?>
							</select> : 
							<select name="am_startmin">
								<?php
									for ( $i = 0; $i < 60; $i++ ) { //determine default
										if ( $sidisplay == $i ) {
											$selected = ' selected';
										} else {
											$selected = '';
										}
										if ( $i < 10 ) {
											$val = "0" . $i;
										} else {
											$val = $i;
										}
										echo '<option value="' . $val . '"' . $selected . '>' . $val . '</option>';
									}
								?>
							</select> 
							
							<?php if ( ! preg_match( "/^(G|H)(:| \\h)/", get_option( 'time_format' ) ) ) { ?>
								<select name="am_starthalf">											
									<option value="am"<?php if ( $ssdisplay == 'am' ) echo ' selected'; ?>>am</option>
									<option value="pm"<?php if ( $ssdisplay == 'pm' ) echo ' selected'; ?>>pm</option>
								</select>
							<?php } ?>

							<p><?php _e( 'Select the time at which access to the backend of this site will be disabled. Note that if <em>"Daily"</em> mode is selected access will be banned every day at the specified time.', $this->hook ); ?></p>

						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for="am_endtime"><?php _e( 'End Time', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<select name="am_endhour" id="am_endtime">
								<?php 
									if ( preg_match("/^(G|H)(:| \\h)/", get_option('time_format') ) ) {
										for ( $i = 0; $i <= 24; $i++ ) {//determine default
											if ( $ehdisplay24 == $i ) {
												$selected = ' selected';
											} else {
												$selected = '';
											}
											echo '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
										}
									} else {
										for ( $i = 1; $i <= 12; $i++ ) {//determine default
											if ( $ehdisplay == $i ) {
												$selected = ' selected';
											} else {
												$selected = '';
											}
											echo '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
										}								
									}
								?>
							</select> : 
							<select name="am_endmin">
								<?php
									for ( $i = 0; $i < 60; $i++ ) { //determine default
										if ( $eidisplay == $i ) {
											$selected = ' selected';
										} else {
											$selected = '';
										}
										if ( $i < 10 ) {
											$val = "0" . $i;
										} else {
											$val = $i;
										}
										echo '<option value="' . $val . '"' . $selected . '>' . $val . '</option>';
									}
								?>
							</select> 
							
							<?php if ( ! preg_match( "/^(G|H)(:| \\h)/", get_option( 'time_format' ) ) ) { ?>
								<select name="am_endhalf">											
									<option value="am"<?php if ( $esdisplay == 'am' ) echo ' selected'; ?>>am</option>
									<option value="pm"<?php if ( $esdisplay == 'pm' ) echo ' selected'; ?>>pm</option>
								</select>
							<?php }?>
							<p><?php _e( 'Select the time at which access to the backend of this site will be re-enabled. Note that if <em>"Daily"</em> mode is selected access will be banned every day at the specified time.', $this->hook ); ?></p>

						</td>
					</tr>
				</table>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', $this->hook ); ?>" /></p>
			</form>
			<?php
		}
		
		/**
		 * Selection summary block for away mode page
		 *
		 **/
		function awaymode_content_3() {

			global $bwpsoptions;
			
			//format times for display
			if ( $bwpsoptions['am_type'] == 1 ) {
			
				$freq = ' <strong><em>' . __( 'every day' ) . '</em></strong>';
				//$stime = '<strong><em>' . date( 'g:i a', $bwpsoptions['am_starttime'] ) . '</em></strong>';
				//$etime = '<strong><em>' . date( 'g:i a', $bwpsoptions['am_endtime'] ) . '</em></strong>';
				$stime = '<strong><em>' . date_i18n( get_option('time_format', 'g:i a'), $bwpsoptions['am_starttime'] ) . '</em></strong>';
				$etime = '<strong><em>' . date_i18n( get_option('time_format', 'g:i a'), $bwpsoptions['am_endtime'] ) . '</em></strong>';				

			} else {
			
				$freq = '';
				//$stime = '<strong><em>' . date( 'l, F jS, Y', $bwpsoptions['am_startdate'] ) . __( ' at ', $this->hook ) . date( 'g:i a', $bwpsoptions['am_starttime'] ) . '</em></strong>';
				//$etime = '<strong><em>' . date( 'l, F jS, Y', $bwpsoptions['am_enddate'] ) . __( ' at ', $this->hook ) . date( 'g:i a', $bwpsoptions['am_endtime'] ) . '</em></strong>';
		
				if ( ! preg_match( "/^(G|H)(:| \\h)/", get_option( 'time_format' ) ) ) {
					// 12Hours Format
					$stime = '<strong><em>' . date( 'l, F jS, Y', $bwpsoptions['am_startdate'] ) . __( ' at ', $this->hook ) . date( 'g:i a', $bwpsoptions['am_starttime'] ) . '</em></strong>';
					$etime = '<strong><em>' . date( 'l, F jS, Y', $bwpsoptions['am_enddate'] )   . __( ' at ', $this->hook ) . date( 'g:i a', $bwpsoptions['am_endtime'] ) . '</em></strong>';

				} else {
					// 24Hours Format
					$stime = '<strong><em>' . date_i18n( 'l, d F Y', $bwpsoptions['am_startdate'] ) . __( ' at ', $this->hook ) . date_i18n( get_option( 'time_format', 'g:i a' ) , $bwpsoptions['am_starttime'] ) . '</em></strong>';
					$etime = '<strong><em>' . date_i18n( 'l, d F Y', $bwpsoptions['am_enddate'] )   . __( ' at ', $this->hook ) . date_i18n( get_option( 'time_format', 'g:i a' ) , $bwpsoptions['am_endtime'] ). '</em></strong>';				
				}
			}
			
			if ( $bwpsoptions['am_enabled'] == 1 ) {
				?>
				<p style="font-size: 150%; text-align: center;"><?php _e( 'The backend (administrative section) of this site will be unavailable', $this->hook ); ?><?php echo $freq; ?> <?php _e( 'from', $this->hook ); ?> <?php echo $stime; ?> <?php _e( 'until', $this->hook ); ?> <?php echo $etime; ?>.</p>
				<?php } else { ?>
					<p><?php _e( 'Away mode is currently diabled', $this->hook ); ?></p>
				<?php
			}	
		}
		
		/**
		 * Intro block for ban hosts page
		 *
		 **/
		function banusers_content_1() {
			?>
			<p><?php _e( 'This feature allows you to ban hosts and user agents from your site completely using individual or groups of IP addresses as well as user agents without having to manage any configuration of your server. Any IP or user agent found in the lists below will not be allowed any access to your site.', $this->hook ); ?></p>
			<?php
		}
		
		/**
		 * Spot backup form for database backup page
		 *
		 **/
		function banusers_content_2() {
			global $bwpsoptions;		
			?>
			<form method="post" action="">
				<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
				<input type="hidden" name="bwps_page" value="banusers_2" />
				<p><?php _e( 'As a getting-started point you can include the excellent blacklist developed by Jim Walker of <a href="http://hackrepair.com/blog/how-to-block-bots-from-seeing-your-website-bad-bots-and-drive-by-hacks-explained" target="_blank">HackRepair.com</a>.', $this->hook ); ?></p>
				<table class="form-table">
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "bu_blacklist"><?php _e( 'Enable Default Banned List', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="bu_blacklist" name="bu_blacklist" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['bu_blacklist'] ); ?> />
							<p><?php _e( "Check this box to enable HackRepair.com's blacklist feature.", $this->hook ); ?></p>
						</td>
					</tr>
				</table>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Add Host and Agent Blacklist', $this->hook ); ?>" /></p>			
			</form>
			<?php
		}	
		
		/**
		 * Options form for ban hosts page
		 *
		 **/
		function banusers_content_3() {
			global $bwpsoptions;
			?>
			<form method="post" action="">
			<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
			<input type="hidden" name="bwps_page" value="banusers_1" />
				<table class="form-table">
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "bu_enabled"><?php _e( 'Enable Banned Users', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="bu_enabled" name="bu_enabled" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['bu_enabled'] ); ?> />
							<p><?php _e( 'Check this box to enable the banned users feature.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "bu_banlist"><?php _e( 'Ban Hosts', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<textarea id="bu_banlist" rows="10" cols="50" name="bu_banlist"><?php echo isset( $_POST['bu_banlist'] ) && BWPS_GOOD_LIST !== true ? filter_var( $_POST['bu_banlist'], FILTER_SANITIZE_STRING ) : $bwpsoptions['bu_banlist']; ?></textarea>
							<p><?php _e( 'Use the guidelines below to enter hosts that will not be allowed access to your site. Note you cannot ban yourself.', $this->hook ); ?></p>
							<ul><em>
								<li><?php _e( 'You may ban users by individual IP address or IP address range.', $this->hook ); ?></li>
								<li><?php _e( 'Individual IP addesses must be in IPV4 standard format (i.e. ###.###.###.###). Wildcards (*) are allowed to specify a range of ip addresses.', $this->hook ); ?></li>
								<li><?php _e( 'If using a wildcard (*) you must start with the right-most number in the ip field. For example ###.###.###.* and ###.###.*.* are permitted but ###.###.*.### is not.', $this->hook ); ?></li>
								<li><a href="http://ip-lookup.net/domain-lookup.php" target="_blank"><?php _e( 'Lookup IP Address.', $this->hook ); ?></a></li>
								<li><?php _e( 'Enter only 1 IP address or 1 IP address range per line.', $this->hook ); ?></li>
							</em></ul>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "bu_banrange"><?php _e( 'Ban User Agents', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<textarea id="bu_banrange" rows="10" cols="50" name="bu_banagent"><?php echo isset( $_POST['bu_banrange'] ) && BWPS_GOOD_LIST !== true ? filter_var( $_POST['bu_banagent'], FILTER_SANITIZE_STRING ) : $bwpsoptions['bu_banagent']; ?></textarea>
							<p><?php _e( 'Use the guidelines below to enter user agents that will not be allowed access to your site.', $this->hook ); ?></p>
							<ul><em>
								<li><?php _e( 'Enter only 1 user agent per line.', $this->hook ); ?></li>
							</em></ul>
						</td>
					</tr>
				</table>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', $this->hook ); ?>" /></p>
			</form>
			<?php
		}
		
		/**
		 * Intro block for change content directory page 
		 *
		 **/
		function contentdirectory_content_1() {
			?>
			<p><?php _e( 'By default WordPress puts all your content including images, plugins, themes, uploads, and more in a directory called "wp-content". This makes it easy to scan for vulnerable files on your WordPress installation as an attacker already knows where the vulnerable files will be at. As there are many plugins and themes with security vulnerabilities moving this folder can make it harder for an attacker to find problems with your site as scans of your site\'s file system will not produce any results.', $this->hook ); ?></p>
			<p><?php _e( 'Please note that changing the name of your wp-content directory on a site that already has images and other content referencing it will break your site. For that reason I highly recommend you do not try this on anything but a fresh WordPress install. In addition, this tool will not allow further changes to your wp-content folder once it has already been renamed in order to avoid accidently breaking a site later on. This includes uninstalling this plugin which will not revert the changes made by this page.', $this->hook ); ?></p>
			<p><?php _e( 'Finally, changing the name of the wp-content directory may in fact break plugins and themes that have "hard-coded" it into their design rather than call it dynamically.', $this->hook ); ?></p>
			<p style="text-align: center; font-size: 130%; font-weight: bold; color: #ff0000;"><?php _e( 'WARNING: BACKUP YOUR WORDPRESS INSTALLATION BEFORE USING THIS TOOL!', $this->hook ); ?></p>
			<p style="text-align: center; font-size: 130%; font-weight: bold; color: #ff0000;"><?php _e( 'RENAMING YOUR wp-content WILL BREAK LINKS ON A SITE WITH EXISTING CONTENT.', $this->hook ); ?></p>
			<?php
		}
		
		/**
		 * Options form for change content directory page
		 *
		 **/
		function contentdirectory_content_2() {
			if ( ! isset( $_POST['bwps_page'] ) && strpos( WP_CONTENT_DIR, 'wp-content' ) ) { //only show form if user the content directory hasn't already been changed
				?>
				<form method="post" action="">
					<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
					<input type="hidden" name="bwps_page" value="contentdirectory_1" />
					<table class="form-table">
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<label for "dirname"><?php _e( 'Directory Name', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<?php //username field ?>
								<input id="dirname" name="dirname" type="text" value="wp-content" />
								<p><?php _e( 'Enter a new directory name to replace "wp-content." You may need to log in again after performing this operation.', $this->hook ); ?></p>
							</td>
						</tr>
					</table>
					<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', $this->hook ); ?>" /></p>
				</form>
				<?php
			} else { //if their is no admin user display a note 
				if ( isset( $_POST['bwps_page'] ) ) {
					$dirname = filter_var( $_POST['dirname'], FILTER_SANITIZE_STRING );
				} else {
					$dirname = substr( WP_CONTENT_DIR, strrpos( WP_CONTENT_DIR, '/' ) + 1 );
				}
				?>
					<p><?php _e( 'Congratulations! You have already renamed your "wp-content" directory.', $this->hook ); ?></p>
					<p><?php _e( 'Your current content directory is: ', $this->hook ); ?><strong><?php echo $dirname ?></strong></p>
					<p><?php _e( 'No further actions are available on this page.', $this->hook ); ?></p>
				<?php
			}
		}
		
		/**
		 * Intro block for database backup page
		 *
		 **/
		function databasebackup_content_1() {
			?>
			<p><?php _e( 'While this plugin goes a long way to helping secure your website nothing can give you a 100% guarantee that your site won\'t be the victim of an attack. When something goes wrong one of the easiest ways of getting your site back is to restore the database from a backup and replace the files with fresh ones. Use the button below to create a full backup of your database for this purpose. You can also schedule automated backups and download or delete previous backups.', $this->hook ); ?></p>
			<?php		
		}
		
		/**
		 * Spot backup form for database backup page
		 *
		 **/
		function databasebackup_content_2() {
			?>
			<form method="post" action="">
				<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
				<input type="hidden" name="bwps_page" value="databasebackup_1" />
				<p><?php _e( 'Press the button below to create a backup of your WordPress database. If you have "Send Backups By Email" selected in automated backups you will receive an email containing the backup file.', $this->hook ); ?></p>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Create Database Backup', $this->hook ); ?>" /></p>			
			</form>
			<?php
		}	
		
		/**
		 * Options form for database backup page
		 *
		 **/
		function databasebackup_content_3() {
			global $bwpsoptions;
			?>
			<form method="post" action="">
			<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
			<input type="hidden" name="bwps_page" value="databasebackup_2" />
				<table class="form-table">
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "backup_enabled"><?php _e( 'Enable Scheduled Backups', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="backup_enabled" name="backup_enabled" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['backup_enabled'] ); ?> />
							<p><?php _e( 'Check this box to enable scheduled backups which will be emailed to the address below.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "backup_interval"><?php _e( 'Backup Interval', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="backup_time" name="backup_time" type="text" value="<?php echo $bwpsoptions['backup_time']; ?>" />
							<select id="backup_interval" name="backup_interval">
								<option value="0" <?php selected( $bwpsoptions['backup_interval'], '0' ); ?>><?php _e( 'Hours', $this->hook ); ?></option>
								<option value="1" <?php selected( $bwpsoptions['backup_interval'], '1' ); ?>><?php _e( 'Days', $this->hook ); ?></option>
								<option value="2" <?php selected( $bwpsoptions['backup_interval'], '2' ); ?>><?php _e( 'Weeks', $this->hook ); ?></option>
							</select>
							<p><?php _e( 'Select the frequency of automated backups.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "backup_email"><?php _e( 'Send Backups by Email', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="backup_email" name="backup_email" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['backup_email'] ); ?> />
							<p><?php _e( 'Email backups to the current site admin.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "backup_emailaddress"><?php _e( 'Email Address', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="backup_emailaddress" name="backup_emailaddress" type="text" value="<?php echo ( isset( $_POST['backup_emailaddress'] ) ? filter_var( $_POST['backup_emailaddress'], FILTER_SANITIZE_STRING ) : ( $bwpsoptions['backup_emailaddress'] == '' ? get_option( 'admin_email' ) : $bwpsoptions['backup_emailaddress'] ) ); ?>" />
							<p><?php _e( 'The email address backups will be sent to.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "backups_to_retain"><?php _e( 'Backups to Keep', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="backups_to_retain" name="backups_to_retain" type="text" value="<?php echo $bwpsoptions['backups_to_retain']; ?>" />
							<p><?php _e( 'Number of backup files to retain. Enter 0 to keep all files. Please note that this setting only applies if "Send Backups by Email" is not selected.', $this->hook ); ?></p>
						</td>
					</tr>
				</table>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', $this->hook ); ?>" /></p>
			</form>
			<?php
		}
		
		/**
		 * Backup location information for database backup page
		 *
		 **/
		function databasebackup_content_4() {
			global $bwpsoptions;
			if ( $bwpsoptions['backup_email'] == 1 ) { //emailing so let them know
				?>
				<p><?php echo __( 'Database backups are NOT saved to the server and instead will be emailed to', $this->hook ) . ' <strong>' . ( isset( $_POST['backup_emailaddress'] ) ? filter_var( $_POST['backup_emailaddress'], FILTER_SANITIZE_STRING ) : ( $bwpsoptions['backup_emailaddress'] == '' ? get_option( 'admin_email' ) : $bwpsoptions['backup_emailaddress'] ) ) . '</strong>. ' . __( 'To change this unset "Send Backups by Email" in the "Scheduled Automated Backups" section above.', $this->hook ); ?></p>
				<?php
			} else { //saving to disk so let them know where
				?>
				<p><?php _e( 'Please note that for security backups are not available for direct download. You will need to go to ', $this->hook ); ?></p>
				<p><strong><em><?php echo BWPS_PP . 'backups'; ?></em></strong></p>
				<p><?php _e( ' via FTP or SSH to download the files. This is because there is too much sensative information in the backup files and you do not want anyone just stumbling upon them.', $this->hook ); ?></p>
				<?php
			}
			if ( $bwpsoptions['backup_enabled'] == 1 ) { //get backup times
				if ( $bwpsoptions['backup_last'] == '' ) {
					$lastbackup = __('Never');
				} else {
					if ( preg_match("/^(G|H)(:| \\h)/", get_option('time_format') ) )	{
						$lastbackup = date_i18n( 'l, d F Y ' . get_option( 'time_format' ), $bwpsoptions['backup_last'] );	// 24Hours Format
					} else {
						$lastbackup = date( 'l, F jS, Y \a\t g:i a', $bwpsoptions['backup_last'] );		// 12Hours Format
					}
				}
				?>
				<p><strong><?php _e( 'Last Scheduled Backup:', $this->hook ); ?></strong> <?php echo $lastbackup; ?></p>
				<p><strong><?php _e( 'Next Scheduled Backup:', $this->hook ); ?></strong> 
					<?php
						if ( preg_match( "/^(G|H)(:| \\h)/", get_option( 'time_format' ) ) ) {
							echo date_i18n( 'l, d F Y ' . get_option( 'time_format' ), $bwpsoptions['backup_next'] );
						} else {
							echo date( 'l, F jS, Y \a\t g:i a', $bwpsoptions['backup_next'] );
						}
					?>
				</p>
				<?php if ( file_exists( BWPS_PP . '/backups/lock' ) ) { ?>
					<p style="color: #ff0000;"><?php _e( 'It looks like a scheduled backup is in progress please reload this page for more accurate times.', $this->hook ); ?></p>
				<?php } ?>
				<?php
			}
		}
		
		/**
		 * Intro box for change database prefix page
		 *
		 **/
		function databaseprefix_content_1() {
			?>
			<p><?php _e( 'By default WordPress assigns the prefix "wp_" to all the tables in the database where your content, users, and objects live. For potential attackers this means it is easier to write scripts that can target WordPress databases as all the important table names for 95% or so of sites are already known. Changing this makes it more difficult for tools that are trying to take advantage of vulnerabilites in other places to affect the database of your site.', $this->hook ); ?></p>
			<p><?php _e( 'Please note that the use of this tool requires quite a bit of system memory which my be more than some hosts can handle. If you back your database up you can\'t do any permanent damage but without a proper backup you risk breaking your site and having to perform a rather difficult fix.', $this->hook ); ?></p>
			<p style="text-align: center; font-size: 130%; font-weight: bold; color: blue;"><?php _e( 'WARNING: <a href="?page=better-wp-security-databasebackup">BACKUP YOUR DATABASE</a> BEFORE USING THIS TOOL!', $this->hook ); ?></p>
			<?php
		}
		
		/**
		 * Options form for change database prefix page
		 *
		 **/
		function databaseprefix_content_2() {
			global $wpdb;
			?>
			<?php if ( $wpdb->base_prefix == 'wp_' ) { //using default table prefix ?>
				<p><strong><?php _e( 'Your database is using the default table prefix', $this->hook ); ?> <em>wp_</em>. <?php _e( 'You should change this.', $this->hook ); ?></strong></p>
			<?php } else { ?>
				<p><?php _e( 'Your current database table prefix is', $this->hook ); ?> <strong><em><?php echo $wpdb->base_prefix; ?></em></strong></p>
			<?php } ?>
			<form method="post" action="">
				<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
				<input type="hidden" name="bwps_page" value="databaseprefix_1" />
				<p><?php _e( 'Press the button below to generate a random database prefix value and update all of your tables accordingly.', $this->hook ); ?></p>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Change Database Table Prefix', $this->hook ); ?>" /></p>			
			</form>
			<?php
		}
		
		/**
		 * Intro block for hide backend page
		 *
		 **/
		function hidebackend_content_1() {
			?>
			<p><?php _e( 'The "hide backend" feature changes the URL from which you can access your WordPress backend thereby further obscuring your site to potential attackers.', $this->hook); ?></p>
			<p><?php _e( 'This feature will need to modify your site\'s .htaccess file if you use the Apache webserver or, if you use NGINX you will need to add the rules manually to your virtualhost configuration. In both cases it requires permalinks to be turned on in your settings to function.', $this->hook); ?></p>
			<?php
		}
		
		/**
		 * Options form for hide backend page
		 *
		 **/
		function hidebackend_content_2() {
			global $bwpsoptions;
			$adminurl = is_multisite() ? admin_url() . 'network/' : admin_url();
			?>
			<?php if ( get_option( 'permalink_structure' ) == '' && ! is_multisite() ) { //don't display form if permalinks are off ?>
				<p><?php echo __( 'You must turn on', $this->hook ) . ' <a href="' . $adminurl . 'options-permalink.php">' . __( 'WordPress permalinks', $this->hook ) . '</a> ' . __( 'to use this feature.', $this->hook ); ?></p>
			<?php } else { ?>
				<form method="post" action="">
				<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
				<input type="hidden" name="bwps_page" value="hidebackend_1" />
					<table class="form-table">
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<label for "hb_enabled"><?php _e( 'Enable Hide Backend', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="hb_enabled" name="hb_enabled" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['hb_enabled'] ); ?> />
								<p><?php _e( 'Check this box to enable the hide backend.', $this->hook ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<label for="hb_login"><?php _e( 'Login Slug', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input name="hb_login" id="hb_login" value="<?php echo $bwpsoptions['hb_login']; ?>" type="text"><br />
								<em><span style="color: #666666;"><strong><?php _e( 'Login URL:', $this->hook ); ?></strong> <?php echo trailingslashit( get_option( 'siteurl' ) ); ?></span><span style="color: #4AA02C"><?php echo $bwpsoptions['hb_login']; ?></span></em>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<label for="hb_register"><?php _e( 'Register Slug', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input name="hb_register" id="hb_register" value="<?php echo $bwpsoptions['hb_register']; ?>" type="text"><br />
								<em><span style="color: #666666;"><strong><?php _e( 'Register URL:', $this->hook ); ?></strong> <?php echo trailingslashit( get_option( 'siteurl' ) ); ?></span><span style="color: #4AA02C"><?php echo $bwpsoptions['hb_register']; ?></span></em>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<label for="hb_admin"><?php _e( 'Admin Slug', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input name="hb_admin" id="hb_admin" value="<?php echo $bwpsoptions['hb_admin']; ?>" type="text"><br />
								<em><span style="color: #666666;"><strong><?php _e( 'Admin URL:', $this->hook ); ?></strong> <?php echo trailingslashit( get_option( 'siteurl' ) ); ?></span><span style="color: #4AA02C"><?php echo $bwpsoptions['hb_admin']; ?></span></em>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<label for "hb_getnewkey"><?php _e( 'Generate new secret key', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="hb_getnewkey" name="hb_getnewkey" type="checkbox" value="1" />
								<p><?php _e( 'Check this box to generate a new secret key.', $this->hook ); ?></p>
							</td>
						</tr>
					</table>
					<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', $this->hook ); ?>" /></p>
				</form>
			<?php } ?>
			<?php
		}
		
		/**
		 * Key information for hide backend page
		 *
		 **/
		function hidebackend_content_3() {
			global $bwpsoptions;
			?>
			<p><?php _e( 'Keep this key in a safe place. You can use it to manually fix plugins that link to wp-login.php. Once turning on this feature and plugins linking to wp-login.php will fail without adding ?[the key]& after wp-login.php. 99% of users will not need this key. The only place you would ever use it is to fix a bad login link in the code of a plugin or theme.', $this->hook ); ?></p>
			<p style="font-weight: bold; text-align: center;"><?php echo $bwpsoptions['hb_key']; ?></p>
			<?php
		}
		
		/**
		 * Intro form for intrusion detection page
		 *
		 **/
		function intrusiondetection_content_1() {
			?>
			<p><?php _e( '404 detection looks at a user who is hitting a large number of non-existent pages, that is they are getting a large number of 404 errors. It assumes that a user who hits a lot of 404 errors in a short period of time is scanning for something (presumably a vulnerability) and locks them out accordingly (you can set the thresholds for this below). This also gives the added benefit of helping you find hidden problems causing 404 errors on unseen parts of your site as all errors will be logged in the "View Logs" page. You can set threshholds for this feature below.', $this->hook ); ?></p>
			<p><?php _e( 'File change detection looks at the files in your WordPress installation and reports changes to those files. This can help you determine if an attacker has compromised your system by changing files within WordPress. Note that it will only automatically check once per day to reduce server load and other insanity.', $this->hook ); ?></p>
			<?php
		}
		
		/**
		 * Spot backup form for database backup page
		 *
		 **/
		function intrusiondetection_content_2() {
			?>
			<form method="post" action="">
				<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
				<input type="hidden" name="bwps_page" value="intrusiondetection_1" />
				<p><?php _e( 'Press the button below to manually check for changed files and folders on your site.', $this->hook ); ?></p>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Check for file/folder changes', $this->hook ); ?>" /></p>			
			</form>
			<?php
		}	
		
		/**
		 * Options form for intrusion detection page
		 *
		 **/
		function intrusiondetection_content_3() {
			global $bwpsoptions, $bwpsmemlimit;
			if ( $bwpsmemlimit < 128 ) {
				echo '<script language="javascript">';
				echo 'function warnmem() {';
				echo 'alert( "' . __( 'Warning: Your server has less than 128MB of RAM dedicated to PHP. If you have many files in your installation or a lot of active plugins activating this feature may result in your site becoming disabled with a memory error. See the plugin homepage for more information.', $this->hook ) . '" );';
				echo '}';
				echo '</script>';
			}
			?>
			<form method="post" action="">
			<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
			<input type="hidden" name="bwps_page" value="intrusiondetection_2" />
				<table class="form-table">
					<tr>
						<td scope="row" colspan="2" class="settingsection">
							<a name="id_enabled"></a><h4><?php _e( '404 Detection', $this->hook ); ?></h4>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "id_enabled"><?php _e( 'Enable 404 Detection', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="id_enabled" name="id_enabled" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['id_enabled'] ); ?> />
							<p><?php _e( 'Check this box to enable 404 intrusion detection.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "id_emailnotify"><?php _e( 'Email 404 Notifications', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="id_emailnotify" name="id_emailnotify" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['id_emailnotify'] ); ?> />
							<p><?php _e( 'Enabling this feature will trigger an email to be sent to the specified email address whenever a host is locked out of the system.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "id_emailaddress"><?php _e( 'Email Address', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="id_emailaddress" name="id_emailaddress" type="text" value="<?php echo ( isset( $_POST['id_emailaddress'] ) ? filter_var( $_POST['id_emailaddress'], FILTER_SANITIZE_STRING ) : ( $bwpsoptions['id_emailaddress'] == '' ? get_option( 'admin_email' ) : $bwpsoptions['id_emailaddress'] ) ); ?>" />
							<p><?php _e( 'The email address lockout notifications will be sent to.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "id_checkinterval"><?php _e( 'Check Period', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="id_checkinterval" name="id_checkinterval" type="text" value="<?php echo $bwpsoptions['id_checkinterval']; ?>" />
							<p><?php _e( 'The number of minutes in which 404 errors should be remembered. Setting this too long can cause legitimate users to be banned.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "id_threshold"><?php _e( 'Error Threshold', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="id_threshold" name="id_threshold" type="text" value="<?php echo $bwpsoptions['id_threshold']; ?>" />
							<p><?php _e( 'The numbers of errors (within the check period timeframe) that will trigger a lockout.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "id_banperiod"><?php _e( 'Lockout Period', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="id_banperiod" name="id_banperiod" type="text" value="<?php echo $bwpsoptions['id_banperiod']; ?>" />
							<p><?php _e( 'The number of minutes a host will be banned from the site after triggering a lockout.', $this->hook ); ?></p>
						</td>
					</tr>
					<?php if ( $bwpsoptions['st_writefiles'] == 1 ) { ?>
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<label for "id_blacklistip"><?php _e( 'Blacklist Repeat Offender', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="id_blacklistip" name="id_blacklistip" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['id_blacklistip'] ); ?> />
								<p><?php _e( 'If this box is checked the IP address of the offending computer will be added to the "Ban Users" blacklist after reaching the number of lockouts listed below.', $this->hook ); ?></p>
								<p><strong style="color: #ff0000;"><?php _e( 'Warning! If your site has a lot of missing files causing 404 errors using this feature can ban your own computer from your site. I would highly advice whitelisting your IP address below if this is the case.', $this->hook ); ?></strong></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<label for "id_blacklistipthreshold"><?php _e( 'Blacklist Threshold', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="id_blacklistipthreshold" name="id_blacklistipthreshold" type="text" value="<?php echo $bwpsoptions['id_blacklistipthreshold']; ?>" />
								<p><?php _e( 'The number of lockouts per IP before the user is banned permanently from this site', $this->hook ); ?></p>
							</td>
						</tr>
					<?php } ?>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "id_whitelist"><?php _e( '404 White List', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<textarea id="id_whitelist" rows="10" cols="50" name="id_whitelist"><?php echo isset( $_POST['id_whitelist'] ) ? filter_var( $_POST['id_whitelist'], FILTER_SANITIZE_STRING ) : $bwpsoptions['id_whitelist']; ?></textarea>
							<p><?php _e( 'Use the guidelines below to enter hosts that will never be locked out due to too many 404 errors. This could be useful for Google, etc.', $this->hook ); ?></p>
							<ul><em>
								<li><?php _e( 'You may whitelist users by individual IP address or IP address range.', $this->hook ); ?></li>
								<li><?php _e( 'Individual IP addesses must be in IPV4 standard format (i.e. ###.###.###.###). Wildcards (*) are allowed to specify a range of ip addresses.', $this->hook ); ?></li>
								<li><?php _e( 'If using a wildcard (*) you must start with the right-most number in the ip field. For example ###.###.###.* and ###.###.*.* are permitted but ###.###.*.### is not.', $this->hook ); ?></li>
								<li><a href="http://ip-lookup.net/domain-lookup.php" target="_blank"><?php _e( 'Lookup IP Address.', $this->hook ); ?></a></li>
								<li><?php _e( 'Enter only 1 IP address or 1 IP address range per line.', $this->hook ); ?></li>
								<li><?php _e( '404 errors will still be logged for users on the whitelist. Only the lockout will be prevented', $this->hook ); ?></li>
							</em></ul>
						</td>
					</tr>
					<tr>
						<td scope="row" colspan="2" class="settingsection">
							<a name="id_fileenabled"></a><h4><?php _e( 'File Change Detection', $this->hook ); ?></h4>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "id_fileenabled"><?php _e( 'Enable File Change Detection', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="id_fileenabled" name="id_fileenabled" <?php if ( $bwpsmemlimit < 128 ) echo 'onchange="warnmem()"'; ?>type="checkbox" value="1" <?php checked( '1', $bwpsoptions['id_fileenabled'] ); ?> />
							<p><?php _e( 'Check this box to enable file change detection.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "id_filedisplayerror"><?php _e( 'Display file change admin warning', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="id_filedisplayerror" name="id_filedisplayerror" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['id_filedisplayerror'] ); ?> />
							<p><?php _e( 'Disabling this feature will prevent the file change warning from displaying to the site administrator in the WordPress Dashboard. Not that disabling both the error message and the email address will result in no notifications of file changes. The only way you will be able to tell is by manually checking the log files.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "id_fileemailnotify"><?php _e( 'Email File Change Notifications', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="id_fileemailnotify" name="id_fileemailnotify" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['id_fileemailnotify'] ); ?> />
							<p><?php _e( 'Enabling this feature will trigger an email to be sent to the specified email address whenever a file change is detected.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "id_fileemailaddress"><?php _e( 'Email Address', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="id_fileemailaddress" name="id_fileemailaddress" type="text" value="<?php echo ( isset( $_POST['id_fileemailaddress'] ) ? filter_var( $_POST['id_fileemailaddress'], FILTER_SANITIZE_STRING ) : ( $bwpsoptions['id_fileemailaddress'] == '' ? get_option( 'admin_email' ) : $bwpsoptions['id_fileemailaddress'] ) ); ?>" />
							<p><?php _e( 'The email address filechange notifications will be sent to.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "id_fileincex"><?php _e( 'Include/Exclude List', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<select id="id_fileincex" name="id_fileincex">
								<option value="0" <?php selected( $bwpsoptions['id_fileincex'], '0' ); ?>><?php _e( 'Include', $this->hook ); ?></option>
								<option value="1" <?php selected( $bwpsoptions['id_fileincex'], '1' ); ?>><?php _e( 'Exclude', $this->hook ); ?></option>
							</select>
							<p><?php _e( 'If "Include" is selected only the contents of the list below will be checked. If exclude is selected all files and folders except those listed below will be checked.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "id_specialfile"><?php _e( 'File/Directory Check List', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<textarea id="id_specialfile" rows="10" cols="50" name="id_specialfile"><?php echo isset( $_POST['id_specialfile'] ) ? $_POST['id_specialfile'] : $bwpsoptions['id_specialfile']; ?></textarea>
							<p><?php _e( 'Enter directories or files you do not want to include in the check (i.e. cache folders, etc). Only 1 file or directory per line. You can specify all files of a given type by just entering the extension preceeded by a dot (.) for exampe, .jpg', $this->hook ); ?></p>
							<p><?php _e( 'Directories should be entered in the from the root of the WordPress folder. For example, if you wish to enter the uploads directory you would enter it as "wp-content/uploads" (assuming you have not renamed wp-content). For files just enter the filename without directory information.', $this->hook ); ?></p>
						</td>
					</tr>
				</table>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Options', $this->hook ); ?>" /></p>
			</form>
			<?php
		}
		
		
		/**
		 * Intro block for login limits page
		 *
		 **/
		function loginlimits_content_1() {
			?>
			<p><?php _e( 'If one had unlimited time and wanted to try an unlimited number of password combimations to get into your site they eventually would, right? This method of attach, known as a brute force attack, is something that WordPress is acutely susceptible by default as the system doesn\t care how many attempts a user makes to login. It will always let you try agin. Enabling login limits will ban the host user from attempting to login again after the specified bad login threshhold has been reached.', $this->hook ); ?></p>
			<?php	
		}
		
		/**
		 * Options form for login limits page
		 *
		 **/
		function loginlimits_content_2() {
			global $bwpsoptions;
			?>
			<form method="post" action="">
			<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
			<input type="hidden" name="bwps_page" value="loginlimits_1" />
				<table class="form-table">
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "ll_enabled"><?php _e( 'Enable Login Limits', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="ll_enabled" name="ll_enabled" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['ll_enabled'] ); ?> />
							<p><?php _e( 'Check this box to enable login limits on this site.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "ll_maxattemptshost"><?php _e( 'Max Login Attempts Per Host', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="ll_maxattemptshost" name="ll_maxattemptshost" type="text" value="<?php echo $bwpsoptions['ll_maxattemptshost']; ?>" />
							<p><?php _e( 'The number of login attempts a user has before their host or computer is locked out of the system.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "ll_maxattemptsuser"><?php _e( 'Max Login Attempts Per User', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="ll_maxattemptsuser" name="ll_maxattemptsuser" type="text" value="<?php echo $bwpsoptions['ll_maxattemptsuser']; ?>" />
							<p><?php _e( 'The number of login attempts a user has before their username is locked out of the system. Note that this is different from hosts in case an attacker is using multiple computers. In addition, if they are using your login name you could be locked out yourself.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "ll_checkinterval"><?php _e( 'Login Time Period (minutes)', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="ll_checkinterval" name="ll_checkinterval" type="text" value="<?php echo $bwpsoptions['ll_checkinterval']; ?>" />
							<p><?php _e( 'The number of minutes in which bad logins should be remembered.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "ll_banperiod"><?php _e( 'Lockout Time Period (minutes)', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="ll_banperiod" name="ll_banperiod" type="text" value="<?php echo $bwpsoptions['ll_banperiod']; ?>" />
							<p><?php _e( 'The length of time a host or computer will be banned from this site after hitting the limit of bad logins.', $this->hook ); ?></p>
						</td>
					</tr>
					<?php if ( $bwpsoptions['st_writefiles'] == 1 ) { ?>
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<label for "ll_blacklistip"><?php _e( 'Blacklist Repeat Offender', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="ll_blacklistip" name="ll_blacklistip" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['ll_blacklistip'] ); ?> />
								<p><?php _e( 'If this box is checked the IP address of the offending computer will be added to the "Ban Users" blacklist after reaching the number of lockouts listed below.', $this->hook ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<label for "ll_blacklistipthreshold"><?php _e( 'Blacklist Threshold', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="ll_blacklistipthreshold" name="ll_blacklistipthreshold" type="text" value="<?php echo $bwpsoptions['ll_blacklistipthreshold']; ?>" />
								<p><?php _e( 'The number of lockouts per IP before the user is banned permanently from this site', $this->hook ); ?></p>
							</td>
						</tr>
					<?php } ?>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "ll_emailnotify"><?php _e( 'Email Notifications', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="ll_emailnotify" name="ll_emailnotify" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['ll_emailnotify'] ); ?> />
							<p><?php _e( 'Enabling this feature will trigger an email to be sent to the specified email address whenever a host or user is locked out of the system.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<label for "ll_emailaddress"><?php _e( 'Email Address', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input id="ll_emailaddress" name="ll_emailaddress" type="text" value="<?php echo ( isset( $_POST['ll_emailaddress'] ) ? filter_var( $_POST['ll_emailaddress'], FILTER_SANITIZE_STRING ) : ( $bwpsoptions['ll_emailaddress'] == '' ? get_option( 'admin_email' ) : $bwpsoptions['ll_emailaddress'] ) ); ?>" />
							<p><?php _e( 'The email address lockout notifications will be sent to.', $this->hook ); ?></p>
						</td>
					</tr>
				</table>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', $this->hook ); ?>" /></p>
			</form>
			<?php
		}
		
		/**
		 * Intro block for view logs page
		 *
		 **/
		function logs_content_1() {
			?>
			<p><?php _e( 'This page contains the logs generated by Better WP Security, current lockouts (which can be cleared here) and a way to cleanup the logs to save space on the server and reduce CPU load. Please note, you must manually clear these logs, they will not do so automatically. I highly recommend you do so regularly to improve performance which can otherwise be slowed if the system has to search through large log-files on a regular basis.', $this->hook ); ?></p>
			<?php
		}
		
		/**
		 * Clear logs form for view logs page
		 *
		 **/
		function logs_content_2() {
			global $wpdb, $bwpsoptions;
			?>
			<form method="post" action="">
			<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
			<input type="hidden" name="bwps_page" value="log_1" />
			<?php //get database record counts
				$countlogin = $wpdb->get_var( "SELECT COUNT(*) FROM `" . $wpdb->base_prefix . "bwps_log` WHERE`type` = 1;" );
				$count404 = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->base_prefix . "bwps_log` WHERE  `type` = 2;" );
				$countlockout = $wpdb->get_var( "SELECT COUNT(*) FROM `" . $wpdb->base_prefix . "bwps_lockouts` WHERE `active` = 0;" );
				$countchange = $wpdb->get_var( "SELECT COUNT(*) FROM `" . $wpdb->base_prefix . "bwps_log` WHERE `type` = 3;" );
			 ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<?php _e( 'Old Data', $this->hook ); ?>
						</th>
						<td class="settingfield">
							<p><?php _e( 'Below is old security data still in your WordPress database. Data is considered old when the lockout has expired, or been manually cancelled, or when the log entry will no longer be used to generate a lockout.', $this->hook ); ?></p>
							<p><?php _e( 'This data is not automatically deleted so that it may be used for analysis. You may delete this data with the form below. To see the actual data you will need to access your database directly.', $this->hook ); ?></p>
							<p><?php _e( 'Check the box next to the data you would like to clear and then press the "Remove Old Data" button. (note this will not erase entries that may still be used for lockouts).', $this->hook ); ?></p>
							<ul>
								<li style="list-style: none;"> <input type="checkbox" name="badlogins" id="badlogins" value="1" /> <label for="badlogins"><?php _e( 'Your database contains', $this->hook ); ?> <strong><?php echo $countlogin; ?></strong> <?php _e( 'bad login entries.', $this->hook ); ?></label></li>
								<li style="list-style: none;"> <input type="checkbox" name="404s" id="404s" value="1" /> <label for="404s"><?php _e( 'Your database contains', $this->hook ); ?> <strong><?php echo $count404; ?></strong> <?php _e( '404 errors.', $this->hook ); ?><br />
								<em><?php _e( 'This will clear the 404 log below.', $this->hook ); ?></em></label></li>
								<li style="list-style: none;"> <input type="checkbox" name="lockouts" id="lockouts" value="1" /> <label for="lockouts"><?php _e( 'Your database contains', $this->hook ); ?> <strong><?php echo $countlockout; ?></strong> <?php _e( 'old lockouts.', $this->hook ); ?></label></li>
								<li style="list-style: none;"> <input type="checkbox" name="changes" id="changes" value="1" /> <label for="changes"><?php _e( 'Your database contains', $this->hook ); ?> <strong><?php echo $countchange; ?></strong> <?php _e( 'changed file records.', $this->hook ); ?></label></li>
							</ul>
						</td>
					</tr>
				</table>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Remove Data', $this->hook ); ?>" /></p>
			</form>
			<?php
		}
		
		/**
		 * Active lockouts table and form for view logs page
		 *
		 **/
		function logs_content_3() {
			global $wpdb;
			?>
			<form method="post" action="">
			<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
			<input type="hidden" name="bwps_page" value="log_2" />
			<?php //get locked out hosts and users from database
				$hostLocks = $wpdb->get_results( "SELECT * FROM `" . $wpdb->base_prefix . "bwps_lockouts` WHERE `active` = 1 AND `exptime` > " . current_time( 'timestamp' ) . " AND `host` != 0;", ARRAY_A );
				$userLocks = $wpdb->get_results( "SELECT * FROM `" . $wpdb->base_prefix . "bwps_lockouts` WHERE `active` = 1 AND `exptime` > " . current_time( 'timestamp' ) . " AND `user` != 0;", ARRAY_A );
			 ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<?php _e( 'Locked out hosts', $this->hook ); ?>
						</th>
						<td class="settingfield">
							<?php if ( sizeof( $hostLocks ) > 0 ) { ?>
							<ul>
								<?php foreach ( $hostLocks as $host) { ?>
									<li style="list-style: none;"><input type="checkbox" name="lo_<?php echo $host['id']; ?>" id="lo_<?php echo $host['id']; ?>" value="<?php echo $host['id']; ?>" /> <label for="lo_<?php echo $host['id']; ?>"><strong><?php echo $host['host']; ?></strong> - Expires <em><?php echo date( 'Y-m-d H:i:s', $host['exptime'] ); ?></em></label></li>
								<?php } ?>
							</ul>
							<?php } else { //no host is locked out ?>
								<p><?php _e( 'Currently no hosts are locked out of this website.', $this->hook ); ?></p>
							<?php } ?>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="settinglabel">
							<?php _e( 'Locked out users', $this->hook ); ?>
						</th>
						<td class="settingfield">
							<?php if (sizeof( $userLocks ) > 0 ) { ?>
							<ul>
								<?php foreach ( $userLocks as $user ) { ?>
									<?php $userdata = get_userdata( $user['user'] ); ?>
									<li style="list-style: none;"><input type="checkbox" name="lo_<?php echo $user['id']; ?>" id="lo_<?php echo $user['id']; ?>" value="<?php echo $user['id']; ?>" /> <label for="lo_<?php echo $user['id']; ?>"><strong><?php echo $userdata->user_login; ?></strong> - Expires <em><?php echo date( 'Y-m-d H:i:s', $user['exptime'] ); ?></em></label></li>
								<?php } ?>
							</ul>
							<?php } else { //no user is locked out ?>
								<p><?php _e( 'Currently no users are locked out of this website.', $this->hook ); ?></p>
							<?php } ?>
						</td>
					</tr>
				</table>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Release Lockout', $this->hook ); ?>" /></p>
			</form>
			<?php
		}
		
		/**
		 * 404 table for view logs page
		 *
		 **/
		function logs_content_4() {
			global $wpdb, $bwps;
			
			$log_content_4_table = new log_content_4_table();
			$log_content_4_table->prepare_items();
			$log_content_4_table->display();

			?>

				<p><a href="<?php echo admin_url(); ?><?php echo is_multisite() ? 'network/' : ''; ?>admin.php?page=better-wp-security-logs&bit51_404_csv" target="_blank" ><?php _e( 'Download 404 Log in .csv format', $this->hook ); ?></a></p>

			<?php
			
		}
		
		/**
		 * table to show all bad logins
		 *
		 **/
		function logs_content_7() {
			global $wpdb;
			
			$log_content_4_table = new log_content_7_table();
			$log_content_4_table->prepare_items();
			$log_content_4_table->display();
			
		}
		
		/**
		 * Lockout table log
		 *
		 **/
		function logs_content_5() {
			
			$log_content_5_table = new log_content_5_table();
			$log_content_5_table->prepare_items();
			$log_content_5_table->display();

		}
		
		function logs_content_6() {
			global $bwps_filecheck;
			?>
				<a name="file-change"></a>
			<?php
			
			if ( isset( $_GET['bwps_change_details_id'] ) ) {
			
				$logout = $bwps_filecheck->getdetails( absint( $_GET['bwps_change_details_id'] ) );
				
			} else {
			
				$logout = false;
				
			}
				
			if ( $logout !== false ) {
				echo $logout;
				unset( $logout );		
				?>
				
				<p><a href="<?php echo admin_url(); ?><?php echo is_multisite() ? 'network/' : ''; ?>admin.php?page=better-wp-security-logs#file-change" ><?php _e( 'Return to Log', $this->hook ); ?></a></p>
				
			<?php			
			} else {
			
				$log_content_6_table = new log_content_6_table();
				$log_content_6_table->prepare_items();
				$log_content_6_table->display();
			}
			
		}
		
		/**
		 * Intro block for system tweaks page
		 *
		 **/
		function ssl_content_1() {
			?>
			<p><?php _e( 'Secure Socket Layers (aka SSL) is a technology that is used to encrypt the data sent between your server or host and the visitor to your web page. When activated it makes it almost impossible for an attacker to intercept data in transit therefore making the transmission of form, password, or other encrypted data much safer.', $this->hook ); ?></p>
			<p><?php _e( 'Better WP Security gives you the option of turning on SSL (if your server or host support it) for all or part of your site. The options below allow you to automatically use SSL for major parts of your site, the login page, the admin dashboard, or the site as a whole. You can also turn on SSL for any post or page by editing the content you want to use SSL in and selecting "Enable SSL" in the publishing options of the content in question.', $this->hook ); ?></p>
			<p><?php _e( 'While this plugin does give you the option of encrypting everything please note this might not be for you. SSL does add overhead to your site which will increase download times slightly. Therefore we recommend you enable SSL at a minimum on the login page, then on the whole admin section, finally on individual pages or posts with forms that require sensitive information.', $this->hook ); ?></p>
			<h4 style="color: red; text-align: center; border-bottom: none;"><?php _e( 'WARNING: Your server MUST support SSL to use these features. Using these features without SSL support on your server or host will cause some or all of your site to become unavailable.', $this->hook ); ?></h4>
			<?php
		}
		
		/**
		 * Intro block for ssl options page
		 *
		 **/
		function ssl_content_2() {
			global $bwpsoptions;
			?>
			
			<form method="post" action="">
				<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
				<input type="hidden" name="bwps_page" value="ssl_1" />
				<table class="form-table">
					<?php
						echo '<script language="javascript">';
						echo 'function forcessl() {';
						echo 'alert( "' . __( 'Are you sure you want to enable SSL? If your server does not support SSL you will be locked out of your WordPress admin backend.', $this->hook ) . '" );';
						echo '}';
						echo '</script>';
					?>
					<tr valign="top" class="strongwarning">
						<th scope="row" class="settinglabel">
							<a name="ssl_frontend"></a><label for "ssl_frontend"><?php _e( 'Enforce Front end SSL', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<select id="ssl_frontend" name="ssl_frontend">
								<option value="0" <?php selected( $bwpsoptions['ssl_frontend'], '0' ); ?>><?php _e( 'Off', $this->hook ); ?></option>
								<option value="1" <?php selected( $bwpsoptions['ssl_frontend'], '1' ); ?>><?php _e( 'Per Content', $this->hook ); ?></option>
								<option value="2" <?php selected( $bwpsoptions['ssl_frontend'], '2' ); ?>><?php _e( 'Whole Site', $this->hook ); ?></option>
							</select>
							<p><?php _e( 'Enables secure SSL connection for the front-end (public parts of your site). Turning this off will disable front-end SSL control, turning this on "Per Content" will place a checkbox on the edit page for all posts and pages (near the publish settings) allowing you turn to on SSL for selected pages or posts, and selecting "Whole Site" will force the whole site to use SSL (not recommended unless you have a really good reason to use it).', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top" class="strongwarning">
						<th scope="row" class="settinglabel">
							<a name="ssl_forcelogin"></a><label for "ssl_forcelogin"><?php _e( 'Enforce Login SSL', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input onchange="forcessl()" id="ssl_forcelogin" name="ssl_forcelogin" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['ssl_forcelogin'] ); ?> />
							<p><?php _e( 'Forces all logins to be served only over a secure SSL connection.', $this->hook ); ?></p>
						</td>
					</tr>
					<tr valign="top" class="strongwarning">
						<th scope="row" class="settinglabel">
							<label for "ssl_forceadmin"><?php _e( 'Enforce Admin SSL', $this->hook ); ?></label>
						</th>
						<td class="settingfield">
							<input onchange="forcessl()" id="ssl_forceadmin" name="ssl_forceadmin" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['ssl_forceadmin'] ); ?> />
							<p><?php _e( 'Forces all of the WordPress backend to be served only over a secure SSL connection.', $this->hook ); ?></p>
						</td>
					</tr>
				</table>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', $this->hook ); ?>" /></p>
			</form>
			<?php
		}
				
		/**
		 * Intro block for system tweaks page
		 *
		 **/
		function systemtweaks_content_1() {
			?>
			<p><?php _e( 'This page contains a number of tweaks that can significantly improve the security of your system.', $this->hook ); ?></p>
			<p><?php _e( 'Server tweaks make use of rewrite rules and, in the case of Apache or LiteSpeed, will write them to your .htaccess file. If you are however using NGINX you will need to manually copy the rules on the Better WP Security Dashboard and put them in your server configuration.', $this->hook ); ?></p>
			<p><?php _e( 'The other tweaks, in some cases, make use of editing your wp-config.php file. Those that do can be manually turned off by reverting the changes that file.', $this->hook ); ?></p>
			<p><?php _e( 'Be advsied, some of these tweaks may in fact break other plugins and themes that make use of techniques that are often seen in practice as suspicious. That said, I highly recommend turning these on one-by-one and don\'t worry if you cannot use them all.', $this->hook ); ?></p>
			<?php
		}
		
		/**
		 * Rewrite options for system tweaks page
		 *
		 **/
		function systemtweaks_content_2() {
			global $bwpsoptions;
			?>
			<?php if ( ! strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'apache' ) &&  ! strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'litespeed' ) && ! strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'nginx' ) ) { //don't diplay options for unsupported server ?> 
				<p><?php _e( 'Your webserver is unsupported. You must use Apache, LiteSpeed or NGINX to make use of these rules.', $this->hook ); ?></p>
			<?php } else { ?>
				<form method="post" action="">
				<?php wp_nonce_field( 'BWPS_admin_save','wp_nonce' ); ?>
				<input type="hidden" name="bwps_page" value="systemtweaks_1" />
					<table class="form-table">
						<tr valign="top">
							<td scope="row" colspan="2" class="settingsection">
								<a name="st_ht_files"></a><h4><?php _e( 'Server Tweaks', $this->hook ); ?></h4>
							</td>
						</tr>
						<tr valign="top" class="warning">	
							<th scope="row" class="settinglabel">
								<label for "st_ht_files"><?php _e( 'Protect Files', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="st_ht_files" name="st_ht_files" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['st_ht_files'] ); ?> />
								<p><?php _e( 'Prevent public access to readme.html, readme.txt, wp-config.php, install.php, wp-includes, and .htaccess. These files can give away important information on your site and serve no purpose to the public once WordPress has been successfully installed.', $this->hook ); ?></p>
								<p class="warningtext"><?php _e( 'Warning: This feature is known to cause conflicts with some plugins and themes.', $this->hook ); ?></p>
							</td>
						</tr>
						<?php if ( strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'apache' ) || strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'litespeed' ) ) { ?>
							<tr valign="top" class="warning">
								<th scope="row" class="settinglabel">
									<label for "st_ht_browsing"><?php _e( 'Disable Directory Browsing', $this->hook ); ?></label>
								</th>
								<td class="settingfield">
									<input id="st_ht_browsing" name="st_ht_browsing" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['st_ht_browsing'] ); ?> />
									<p><?php _e( 'Prevents users from seeing a list of files in a directory when no index file is present.', $this->hook ); ?></p>
									<p class="warningtext"><?php _e( 'Warning: This feature is known to cause conflicts with some server configurations in which this feature has already been enabled in Apache.', $this->hook ); ?></p>
								</td>
							</tr>
						<?php } ?>
						<tr valign="top" class="warning">
							<th scope="row" class="settinglabel">
								<label for "st_ht_request"><?php _e( 'Filter Request Methods', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="st_ht_request" name="st_ht_request" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['st_ht_request'] ); ?> />
								<p><?php _e( 'Filter out hits with the trace, delete, or track request methods.', $this->hook ); ?></p>
								<p class="warningtext"><?php _e( 'Warning: This feature is known to cause conflicts with some plugins and themes.', $this->hook ); ?></p>
							</td>
						</tr>
						<tr valign="top" class="warning">
							<th scope="row" class="settinglabel">
								<label for "st_ht_query"><?php _e( 'Filter Suspicious Query Strings', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="st_ht_query" name="st_ht_query" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['st_ht_query'] ); ?> />
								<p><?php _e( 'Filter out suspicious query strings in the URL. These are very often signs of someone trying to gain access to your site but some plugins and themes can also be blocked.', $this->hook ); ?></p>
								<p class="warningtext"><?php _e( 'Warning: This feature is known to cause conflicts with some plugins and themes.', $this->hook ); ?></p>
							</td>
						</tr>
						<tr>
							<td scope="row" colspan="2" class="settingsubmit">
								<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', $this->hook ); ?>" /></p>
							</td>
						</tr>
						<tr>
							<td scope="row" colspan="2" class="settingsection">
								<a name="st_generator"></a><h4><?php _e( 'Header Tweaks', $this->hook ); ?></h4>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<label for "st_generator"><?php _e( 'Remove WordPress Generator Meta Tag', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="st_generator" name="st_generator" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['st_generator'] ); ?> />
								<p><?php _e( 'Removes the <meta name="generator" content="WordPress [version]" /> meta tag from your sites header. This process hides version information from a potential attacker making it more difficult to determine vulnerabilities.', $this->hook ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<label for "st_manifest"><?php _e( 'Remove wlwmanifest header', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="st_manifest" name="st_manifest" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['st_manifest'] ); ?> />
								<p><?php _e( 'Removes the Windows Live Writer header. This is not needed if you do not use Windows Live Writer or other blogging clients that rely on this file.', $this->hook ); ?></p>
							</td>
						</tr>
						<tr valign="top" class="warning">
							<th scope="row" class="settinglabel">
								<label for "st_edituri"><?php _e( 'Remove EditURI header', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="st_edituri" name="st_edituri" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['st_edituri'] ); ?> />
								<p><?php _e( 'Removes the RSD (Really Simple Discovery) header. If you don\'t integrate your blog with external XML-RPC services such as Flickr then the "RSD" function is pretty much useless to you.', $this->hook ); ?></p>
								<p class="warningtext"><?php _e( 'Warning: This feature is known to cause conflicts with some 3rd party application and services that may want to interact with WordPress.', $this->hook ); ?></p>
							</td>
						</tr>
						<tr>
							<td scope="row" colspan="2" class="settingsubmit">
								<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', $this->hook ); ?>" /></p>
							</td>
						</tr>
						<tr>
							<td scope="row" colspan="2" class="settingsection">
								<a name="st_themenot"></a><h4><?php _e( 'Dashboard Tweaks', $this->hook ); ?></h4>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<label for "st_themenot"><?php _e( 'Hide Theme Update Notifications', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="st_themenot" name="st_themenot" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['st_themenot'] ); ?> />
								<p><?php _e( 'Hides theme update notifications from users who cannot update themes. Please note that this only makes a difference in multi-site installations.', $this->hook ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<label for "st_pluginnot"><?php _e( 'Hide Plugin Update Notifications', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="st_pluginnot" name="st_pluginnot" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['st_pluginnot'] ); ?> />
								<p><?php _e( 'Hides plugin update notifications from users who cannot update themes. Please note that this only makes a difference in multi-site installations.', $this->hook ); ?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<label for "st_corenot"><?php _e( 'Hide Core Update Notifications', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="st_corenot" name="st_corenot" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['st_corenot'] ); ?> />
								<p><?php _e( 'Hides core update notifications from users who cannot update themes. Please note that this only makes a difference in multi-site installations.', $this->hook ); ?></p>
							</td>
						</tr>
						<tr>
							<td scope="row" colspan="2" class="settingsubmit">
								<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', $this->hook ); ?>" /></p>
							</td>
						</tr>
						<tr>
							<td scope="row" colspan="2" class="settingsection">
								<h4><?php _e( 'Strong Password Tweaks', $this->hook ); ?></h4>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<a name="st_enablepassword"></a><label for "st_enablepassword"><?php _e( 'Enable strong password enforcement', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="st_enablepassword" name="st_enablepassword" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['st_enablepassword'] ); ?> />
								<p><?php _e( 'Enforce strong passwords for all users with at least the role specified below.', $this->hook ); ?></p>
							</td>
						</tr>
						<tr valign="top" class="warning">
							<th scope="row" class="settinglabel">
								<label for "st_passrole"><?php _e( 'Strong Password Role', $this->hook ); ?></label>
							</th>				
							<td class="settingfield">
								<select name="st_passrole" id="st_passrole">
									<option value="administrator" <?php if ( $bwpsoptions['st_passrole'] == "administrator" ) echo "selected"; ?>><?php echo translate_user_role( 'Administrator' ); ?></option>
									<option value="editor" <?php if ( $bwpsoptions['st_passrole'] == "editor" ) echo "selected"; ?>><?php echo translate_user_role( 'Editor' ); ?></option>
									<option value="author" <?php if ( $bwpsoptions['st_passrole'] == "author" ) echo "selected"; ?>><?php echo translate_user_role( 'Author' ); ?></option>
									<option value="contributor" <?php if ( $bwpsoptions['st_passrole'] == "contributor" ) echo "selected"; ?>><?php echo translate_user_role( 'Contributor' ); ?></option>
									<option value="subscriber" <?php if ( $bwpsoptions['st_passrole'] == "subscriber" ) echo "selected"; ?>><?php echo translate_user_role( 'Subscriber' ); ?></option>
								</select>
								<p><?php _e( 'Minimum role at which a user must choose a strong password. For more information on WordPress roles and capabilities please see', $this->hook ); ?> <a href="http://codex.wordpress.org/Roles_and_Capabilities" target="_blank">http://codex.wordpress.org/Roles_and_Capabilities</a>.</p>
								<p class="warningtext"><?php _e( 'Warning: If your site invites public registrations setting the role too low may annoy your members.', $this->hook ); ?></p>
							</td>
						</tr>
						<tr>
							<td scope="row" colspan="2" class="settingsubmit">
								<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', $this->hook ); ?>" /></p>
							</td>
						</tr>
						<tr>
							<td scope="row" colspan="2" class="settingsection">
								<h4><?php _e( 'Other Tweaks', $this->hook ); ?></h4>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<a name="st_loginerror"></a><label for "st_loginerror"><?php _e( 'Remove WordPress Login Error Messages', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="st_loginerror" name="st_loginerror" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['st_loginerror'] ); ?> />
								<p><?php _e( 'Prevents error messages from being displayed to a user upon a failed login attempt.', $this->hook ); ?></p>
							</td>
						</tr>
						<tr valign="top" class="warning">
							<th scope="row" class="settinglabel">
								<a name="st_writefiles"></a><label for "st_writefiles"><?php _e( 'Write to WordPress core files', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="st_writefiles" name="st_writefiles" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['st_writefiles'] ); ?> />
								<p><?php _e( 'Allow Better WP Security to write to .htaccess and wp-config.php. With this turned on this plugin will automatically write to your .htaccess and wp-config.php files. With it turned off you will need to manually make changes to these files and both the renaming of wp-content and the changing of the database table prefix will not be available.', $this->hook ); ?></p>
								<p><?php _e( 'This option is safe in nearly all instances however, if you know you have a server configuration that may conflict or simply want to make the changes yourself then uncheck this feature.', $this->hook ); ?></p>
								<p class="warningtext"><?php _e( 'Warning: This feature is known to cause conflicts with some server configurations.', $this->hook ); ?></p>
							</td>
						</tr>
						<tr valign="top" class="warning">
							<th scope="row" class="settinglabel">
								<a name="st_comment"></a><label for "st_comment"><?php _e( 'Reduce comment spam', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="st_comment" name="st_comment" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['st_comment'] ); ?> />
								<p><?php _e( 'This option will cut down on comment spam by denying comments from bots with no referrer or without a user-agent identified.', $this->hook ); ?></p>
								<p><?php _e( 'Note this feature only applies if "Write to WordPress core files" is enabled.', $this->hook ); ?></p>
								<p class="warningtext"><?php _e( 'Warning: This feature is known to cause conflicts with some plugins and themes.', $this->hook ); ?></p>
							</td>
						</tr>
						<tr valign="top" class="warning">
							<th scope="row" class="settinglabel">
								<a name="st_fileperm"></a><label for "st_fileperm"><?php _e( 'Remove write permissions from .htaccess and wp-config.php', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="st_fileperm" name="st_fileperm" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['st_fileperm'] ); ?> />
								<p><?php _e( 'Prevents scripts and users from being able to write to the wp-config.php file and .htaccess file. Note that in the case of this and many plugins this can be overcome however it still does make the files more secure. Turning this on will set the unix file permissions to 0444 on these files and turning it off will set the permissions to 0644.', $this->hook ); ?></p>
								<p><?php _e( 'Note this feature only applies if "Write to WordPress core files" is enabled.', $this->hook ); ?></p>
								<p class="warningtext"><?php _e( 'Warning: This feature is known to cause conflicts with some plugins and themes.', $this->hook ); ?></p>
							</td>
						</tr>
						<tr valign="top" class="warning">
							<th scope="row" class="settinglabel">
								<a name="st_randomversion"></a><label for "st_randomversion"><?php _e( 'Display random version number to all non-administrative users', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="st_randomversion" name="st_randomversion" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['st_randomversion'] ); ?> />
								<p><?php _e( 'Displays a random version number to visitors who are not logged in at all points where version number must be used and removes the version completely from where it can.', $this->hook ); ?></p>
								<p class="warningtext"><?php _e( 'Warning: This feature is known to cause conflicts with some plugins and themes.', $this->hook ); ?></p>
							</td>
						</tr>
						<tr valign="top" class="warning">
							<th scope="row" class="settinglabel">
								<a name="st_longurl"></a><label for "st_longurl"><?php _e( 'Prevent long URL strings', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="st_longurl" name="st_longurl" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['st_longurl'] ); ?> />
								<p><?php _e( 'Limits the number of characters that can be sent in the URL. Hackers often take advantage of long URLs to try to inject information into your database.', $this->hook ); ?></p>
								<p class="warningtext"><?php _e( 'Warning: This feature is known to cause conflicts with some plugins and themes.', $this->hook ); ?></p>
							</td>
						</tr>
						<tr valign="top" class="warning">
							<th scope="row" class="settinglabel">
								<a name="st_fileedit"></a><label for "st_fileedit"><?php _e( 'Turn off file editor in WordPress Back-end', $this->hook ); ?></label>
							</th>
							<td class="settingfield">
								<input id="st_fileedit" name="st_fileedit" type="checkbox" value="1" <?php checked( '1', $bwpsoptions['st_fileedit'] ); ?> />
								<p><?php _e( 'Disables the file editor for plugins and themes requiring users to have access to the file system to modify files. Once activated you will need to manually edit theme and other files using a tool other than WordPress.', $this->hook ); ?></p>
								<p class="warningtext"><?php _e( 'Warning: This feature is known to cause conflicts with some plugins and themes.', $this->hook ); ?></p>
							</td>
						</tr>
					</table>
					<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', $this->hook ); ?>" /></p>
				</form>
			<?php } ?>
			<?php
		}
	
	}

}
