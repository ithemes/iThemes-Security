<?php

require_once( plugin_dir_path( __FILE__ ) . 'admin/common.php' );

if ( ! class_exists( 'bwps_setup' ) ) {

	class bwps_setup extends bwps_admin_common {

		private $update;

		/**
		 * Establish setup object
		 *
		 * Establishes set object and calls appropriate execution function
		 *
		 * @param bool $case[optional] Appropriate execution module to call
		 *
		 **/
		function __construct( $case = false, $updating = false ) {
	
			if ( ! $case ) {
				die( 'error' );
			}

			switch($case) {
				case 'activate': //active plugin
					$this->activate_execute( $updating );
					break;

				case 'deactivate': //deactivate plugin
					$this->deactivate_execute( $updating );
					break;

				case 'uninstall': //uninstall plugin
					$this->uninstall_execute();
					break;
			}

		}
		
		/**
		 * Public function to activate
		 *
		 **/
		function on_activate() {
			
			define( 'BWPS_NEW_INSTALL', true );
			new bwps_setup( 'activate' );
			
		}

		/**
		 * Public function to deactivate
		 *
		 **/
		function on_deactivate() {
	
			$devel = false; //set to true to uninstall for development
		
			if ( $devel ) {
				$case = 'uninstall';
			} else {
				$case = 'deactivate';
			}

			new bwps_setup( $case );
		}

		/**
		 * Public function to uninstall
		 *
		 **/
		function on_uninstall() {
		
			new bwps_setup( 'uninstall' );
			
		}
		
		/**
		 * Execute activation
		 * 
		 * @param  boolean $updating true if the plugin is updating
		 * @return void
		 */
		function activate_execute( $updating = false ) {
			global $wpdb;
			
			$bwpsoptions = get_option( $this->primarysettings );
			$bwpsdata = get_option( $this->plugindata );
		
			//if this is multisite make sure they're network activating or die
			if ( defined( 'BWPS_NEW_INSTALL' ) && BWPS_NEW_INSTALL == true && is_multisite() && ! strpos( $_SERVER['REQUEST_URI'], 'wp-admin/network/plugins.php' ) ) {
			
				die ( __( '<strong>ERROR</strong>: You must activate this plugin from the network dashboard.', 'better-wp-security' ) );	
			
			}	
				
			$oldversion = $bwpsdata['version']; //get old version number
			$bwpsdata['version'] = $this->pluginversion; //set new version number
			
			//remove no support nag if it's been more than six months
			if ( ! isset( $bwpsdata['activatestamp'] ) || $bwpsdata['activatestamp'] < ( current_time( 'timestamp' ) - 15552000 ) ) {
			
				if ( isset( $bwpsdata['no-nag'] ) ) {
					unset( $bwpsdata['no-nag'] );
				}
				
				//set activate timestamp to today (they'll be notified again in a month)
				$bwpsdata['activatestamp'] = current_time( 'timestamp' );
			}
			
			//save plugin data
			update_option( $this->plugindata, $bwpsdata ); //save new plugin data
			
			//update if version numbers don't match
			if ( $updating === true ) {
				$this->update_execute( $oldversion );
			}
			
			$bwpsoptions = $this->default_settings(); //verify and set default options
			
			//Set up log table
			$tables = "CREATE TABLE " . $wpdb->base_prefix . "bwps_log (
				id int(11) NOT NULL AUTO_INCREMENT ,
				type int(1) NOT NULL ,
				timestamp int(10) NOT NULL ,
				host varchar(20) ,
				user bigint(20) ,
				username varchar(255) ,
				url varchar(255) ,
				mem_used varchar(255),
				referrer varchar(255) ,
				data MEDIUMTEXT ,
				PRIMARY KEY  (id)
				);";
			
			//set up lockout table	
			$tables .= "CREATE TABLE " . $wpdb->base_prefix . "bwps_lockouts (
				id int(11) NOT NULL AUTO_INCREMENT ,
				type int(1) NOT NULL ,
				active int(1) NOT NULL ,
				starttime int(10) NOT NULL ,
				exptime int(10) NOT NULL ,
				host varchar(20) ,
				user bigint(20) ,
				PRIMARY KEY  (id)
				);";
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			@dbDelta( $tables );
			
			//get contents of wp-config.php
			$lines = explode( "\n", implode( '', file( $this->getconfig() ) ) ); //parse each line of file into array
			
			//set default options for wp-config stuff
			foreach ($lines as $line) {
			
				if ( strstr( $line, 'DISALLOW_FILE_EDIT' ) && strstr( $line, 'true' ) ) {
					
					$bwpsoptions['st_fileedit'] = 1;
					
				}
				
				if ( strstr( $line, 'FORCE_SSL_LOGIN' ) && strstr( $line, 'true' ) ) {
				
					$bwpsoptions['ssl_forcelogin'] = 1;
					
				}
				
				if ( strstr( $line, 'FORCE_SSL_ADMIN' ) && strstr( $line, 'true' ) ) {
				
					$bwpsoptions['ssl_forceadmin'] = 1;
					
				}
				
			}
			
			//Get the right options
			if ( is_multisite() ) {
			
				switch_to_blog( 1 );
			
				update_option( $this->primarysettings, $bwpsoptions ); //save new options data
			
				restore_current_blog();
			
			} else {
			
				update_option( $this->primarysettings, $bwpsoptions ); //save new options data
				
			}

			if ( $updating  === false ) {

				if ( ( strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'apache' ) || strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'litespeed' ) ) && $bwpsoptions['st_writefiles'] == 1 ) { //if they're using apache write to .htaccess
					
					$this->writehtaccess();
						
				}
			
				if ( $bwpsoptions['st_writefiles'] == 1 ) {
				
					$this->writewpconfig(); //write appropriate options to wp-config.php
					
				}

			}
			
		}

		/**
		 * Update Execution
		 * 
		 * @param  string $oldversion Old version number
		 * @return void
		 */
		function update_execute( $oldversion = '' ) {
			global $wpdb, $bwpsoptions;
			
			if ( get_option( 'BWPS_options' ) != false ) {
			
				$oldoptions = maybe_unserialize( get_option( 'BWPS_options' ) );
				
				$bwpsoptions['am_enabled'] = isset( $oldoptions['away_enable'] ) ? $oldoptions['away_enable'] : '0';
				$bwpsoptions['am_type'] = isset( $oldoptions['away_mode'] ) ? $oldoptions['away_mode'] : '0';
				$bwpsoptions['am_startdate'] = isset( $oldoptions['away_start'] ) ? $oldoptions['away_start'] : '1';
				$bwpsoptions['am_starttime'] = isset( $oldoptions['away_start'] ) ? $oldoptions['away_start'] : '1';
				$bwpsoptions['am_enddate'] = isset( $oldoptions['away_end'] ) ? $oldoptions['away_end'] : '1';
				$bwpsoptions['am_endtime'] = isset( $oldoptions['away_end'] ) ? $oldoptions['away_end'] : '1';
				$bwpsoptions['st_generator'] = isset( $oldoptions['tweaks_removeGenerator'] ) ? $oldoptions['tweaks_removeGenerator'] : '0';
				$bwpsoptions['st_loginerror'] = isset( $oldoptions['tweaks_removeLoginMessages'] ) ? $oldoptions['tweaks_removeLoginMessages'] : '0';
				$bwpsoptions['st_randomversion'] = isset( $oldoptions['tweaks_randomVersion'] ) ? $oldoptions['tweaks_randomVersion'] : '0';
				$bwpsoptions['st_themenot'] = isset( $oldoptions['tweaks_themeUpdates'] ) ? $oldoptions['tweaks_themeUpdates'] : '0';
				$bwpsoptions['st_pluginnot'] = isset( $oldoptions['tweaks_pluginUpdates'] ) ? $oldoptions['tweaks_pluginUpdates'] : '0';
				$bwpsoptions['st_corenot'] = isset( $oldoptions['tweaks_coreUpdates'] ) ? $oldoptions['tweaks_coreUpdates'] : '0';
				$bwpsoptions['st_manifest'] = isset( $oldoptions['tweaks_removewlm'] ) ? $oldoptions['tweaks_removewlm'] : '0';
				$bwpsoptions['st_edituri'] = isset( $oldoptions['tweaks_removersd'] ) ? $oldoptions['tweaks_removersd'] : '0';
				$bwpsoptions['st_longurl'] = isset( $oldoptions['tweaks_longurls'] ) ? $oldoptions['tweaks_longurls'] : '0';
				$bwpsoptions['st_enablepassword'] = isset( $oldoptions['tweaks_strongpass'] ) ? $oldoptions['away_enable'] : '0';
				$bwpsoptions['st_passrole'] = isset( $oldoptions['tweaks_strongpassrole'] ) ? $oldoptions['away_enable'] : '0';
				$bwpsoptions['st_ht_files'] = isset( $oldoptions['htaccess_protectht'] ) ? $oldoptions['away_enable'] : '0';
				$bwpsoptions['st_ht_browsing'] = isset( $oldoptions['htaccess_dirbrowse'] ) ? $oldoptions['away_enable'] : '0';
				$bwpsoptions['st_ht_request'] = isset( $oldoptions['htaccess_request'] ) ? $oldoptions['away_enable'] : '0';
				$bwpsoptions['st_ht_query'] = isset( $oldoptions['htaccess_qstring'] ) ? $oldoptions['away_enable'] : '0';
				$bwpsoptions['hb_enabled'] = isset( $oldoptions['hidebe_enable'] ) ? $oldoptions['hidebe_enable'] : '0';
				$bwpsoptions['hb_login'] = isset( $oldoptions['hidebe_login_slug'] ) ? $oldoptions['hidebe_login_slug'] : 'login';
				$bwpsoptions['hb_admin'] = isset( $oldoptions['hidebe_admin_slug'] ) ? $oldoptions['hidebe_admin_slug'] : 'admin';
				$bwpsoptions['hb_register'] = isset( $oldoptions['hidebe_register_slug'] ) ? $oldoptions['hidebe_register_slug'] : 'register';
				$bwpsoptions['hb_key'] = isset( $oldoptions['hidebe_key'] ) ? $oldoptions['hidebe_key'] : '';
				$bwpsoptions['ll_enabled'] = isset( $oldoptions['ll_enable'] ) ? $oldoptions['ll_enable'] : '0';
				$bwpsoptions['ll_maxattemptshost'] = isset( $oldoptions['ll_maxattemptshost'] ) ? $oldoptions['ll_maxattemptshost'] : '5';
				$bwpsoptions['ll_maxattemptsuser'] = isset( $oldoptions['ll_maxattemptsuser'] ) ? $oldoptions['ll_maxattemptsuser'] : '10';
				$bwpsoptions['ll_checkinterval'] = isset( $oldoptions['ll_checkinterval'] ) ? $oldoptions['ll_checkinterval'] : '5';
				$bwpsoptions['ll_banperiod'] = isset( $oldoptions['ll_banperiod'] ) ? $oldoptions['ll_banperiod'] : '15';
				$bwpsoptions['ll_emailnotify'] = isset( $oldoptions['ll_emailnotify'] ) ? $oldoptions['ll_emailnotify'] : '1';
				$bwpsoptions['id_enabled'] = isset( $oldoptions['idetect_d404enable'] ) ? $oldoptions['idetect_d404enable'] : '0';
				$bwpsoptions['id_emailnotify'] = isset( $oldoptions['idetect_emailnotify'] ) ? $oldoptions['idetect_emailnotify'] : '1';
				$bwpsoptions['id_checkinterval'] = isset( $oldoptions['idetect_checkint'] ) ? ( $oldoptions['idetect_checkint'] / 60 ) : '5';
				$bwpsoptions['id_threshold'] = isset( $oldoptions['idetect_locount'] ) ? $oldoptions['idetect_locount'] : '20';
				$bwpsoptions['id_banperiod'] = isset( $oldoptions['idetect_lolength'] ) ? ( $oldoptions['idetect_lolength'] / 60 ) : '15';
				$bwpsoptions['id_whitelist'] = isset( $oldoptions['idetect_whitelist'] ) ? $oldoptions['idetect_whitelist'] : '0';
				$bwpsoptions['bu_enabled'] = isset( $oldoptions['banvisits_enable'] ) ? $oldoptions['banvisits_enable'] : '0';				
				
				if ( isset(  $oldoptions['banvisits_banlist'] ) ) {
					$list = array();
				
					$items = explode ("\n", $oldoptions['banvisits_banlist'] );
				
					foreach ( $items as $item ) {
					
						if ( strstr( $item, '*' ) ) {
					
							if ( ip2long( trim( str_replace( '*', '0', $item ) ) ) != false ) {
						
								$list[] = $item;
						
							}
					
						} elseif ( ! strstr( $item, '-' ) ) {
					
							if ( ip2long( trim( $item ) ) != false ) {
						
								$list[] = $item;
						
							}
						
						}
						
					}
				
					$bwpsoptions['bu_banlist'] = implode( "\n", $list );
				
				}
				
				//Get the right options
				if ( is_multisite() ) {
				
					switch_to_blog( 1 );
				
					update_option( $this->primarysettings, $bwpsoptions ); //save new options data
				
					restore_current_blog();
				
				} else {
				
					update_option( $this->primarysettings, $bwpsoptions ); //save new options data
					
				}
				
				delete_option( 'BWPS_Login_Slug' );
				delete_option( 'BWPS_options' );
				delete_option( 'BWPS_versions' );
				
				$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->base_prefix . "BWPS_d404`;" );
				$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->base_prefix . "BWPS_ll`;" );
				$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->base_prefix . "BWPS_lockouts`;" );
				
				$this->deletehtaccess('Better WP Security Protect htaccess');
				$this->deletehtaccess('Better WP Security Hide Backend');
				$this->deletehtaccess('Better WP Security Ban IPs');
			
			} else {
				
				if ( str_replace( '.', '', $oldversion ) < 304 ) {
				
					$ranges = explode( "\n", $bwpsoptions['bu_banrange'] );
					$ips = explode( "\n", $bwpsoptions['bu_individual'] );
					$whitelist = explode( "\n", $bwpsoptions['id_whitelist'] );
					
					if ( sizeof( $ranges ) > 0 || sizeof( $whitelist ) > 0 ) {
					
						for ( $i = 0; $i < sizeof( $ranges ); $i++ ) {
					
							if ( strstr( $ranges[$i], '-' ) ) {
							
								unset( $ranges[$i] );
							
							}
					
						}
						
						$list = array_merge( $ranges, $ips );
						
						if ( ! is_array( $list ) || ( is_array( $list ) && sizeof( $list ) < 1 ) ) {
							$bwpsoptions['bu_enabled'] = '0';
						}
						
						$bwpsoptions['bu_banlist'] = implode( "\n", $list );
					
						for ( $i = 0; $i < sizeof( $whitelist ); $i++ ) {
						
							if ( strstr( $whitelist[$i], '-' ) ) {
								
								unset( $whitelist[$i] );
								
							}
						
						}
						
						$bwpsoptions['id_whitelist'] = implode( "\n", $whitelist );						
						
						//Get the right options
						if ( is_multisite() ) {
						
							switch_to_blog( 1 );
						
							update_option( $this->primarysettings, $bwpsoptions ); //save new options data
						
							restore_current_blog();
						
						} else {
						
							update_option( $this->primarysettings, $bwpsoptions ); //save new options data
							
						}
					
					}
					
				}
				
				if ( str_replace( '.', '', $oldversion ) < 3012 ) {
					
					if ( wp_next_scheduled( 'bwps_backup' ) ) {
						wp_clear_scheduled_hook( 'bwps_backup' );
					}
					
				}
				
				if ( str_replace( '.', '', $oldversion ) < 3031 ) {
					
					$bwpsoptions['st_writefiles'] = 1;
					$bwpsoptions['initial_filewrite'] = 1;
					
					$bwpsoptions['ssl_forcelogin'] = $bwpsoptions['st_forceloginssl'];
					$bwpsoptions['ssl_forceadmin'] = $bwpsoptions['st_forceadminssl'];
					
					if ( $bwpsoptions['backup_enabled'] == 1 && $bwpsoptions['ll_enabled'] == 1 && $bwpsoptions['id_enabled'] == 1 && $bwpsoptions['st_ht_files'] == 1 && $bwpsoptions['st_ht_browsing'] == 1 && $bwpsoptions['st_generator'] == 1 && $bwpsoptions['st_manifest'] == 1 && $bwpsoptions['st_themenot'] == 1 && $bwpsoptions['st_pluginnot'] == 1 && $bwpsoptions['st_corenot'] == 1 && $bwpsoptions['st_enablepassword'] == 1 && $bwpsoptions['st_loginerror'] == 1 && $bwpsoptions['st_ht_request'] == 1 ) {
					
						$bwpsoptions['id_fileenabled'] = 1;	
						
					}	
					
					//Get the right options
					if ( is_multisite() ) {
					
						switch_to_blog( 1 );
					
						update_option( $this->primarysettings, $bwpsoptions ); //save new options data
					
						restore_current_blog();
					
					} else {
					
						update_option( $this->primarysettings, $bwpsoptions ); //save new options data
						
					}
					
				}
				
				if ( str_replace( '.', '', $oldversion ) < 3033 ) {
					
					$bwpsoptions['ssl_frontend'] = $bwpsoptions['ssl_forcesite'] == 1 ? 2 : 1;	
					
					//Get the right options
					if ( is_multisite() ) {
					
						switch_to_blog( 1 );
					
						update_option( $this->primarysettings, $bwpsoptions ); //save new options data
					
						restore_current_blog();
					
					} else {
					
						update_option( $this->primarysettings, $bwpsoptions ); //save new options data
						
					}
					
				}
				
				if ( str_replace( '.', '', $oldversion ) < 3044 ) {
				
					//turn on id confirmation for existing users.
					$idconfirm = $bwpsoptions['id_fileenabled'] == 1 ? true : false;
					
					update_option( 'bwps_filecheck', $idconfirm );
				
				}

				if ( str_replace( '.', '', $oldversion ) < 3051 ) {
				
					//turn on away mode for existing users.
					$amconfirm = $bwpsoptions['am_enabled'] == 1 ? 1 : 0;
					
					update_option( 'bwps_awaymode', $amconfirm );
				
				}

				if ( str_replace( '.', '', $oldversion ) < 3056 ) {
				
					delete_option( 'bwps_awaymode' );
					delete_option( 'bwps_filecheck' );
				
				}

				if ( str_replace( '.', '', $oldversion ) < 3059 ) {


					$this->writehtaccess();
			
					if ( $bwpsoptions['st_writefiles'] == 1 ) {
			
						$this->writewpconfig(); //write appropriate options to wp-config.php
				
					}

				}
			
			}
		
		}
		
		/**
		 * Deactivate execution
		 *
		 **/
		function deactivate_execute( $updating = false ) {
		
			if ( wp_next_scheduled( 'bwps_backup' ) ) {
				wp_clear_scheduled_hook( 'bwps_backup' );
			}
			
			//delete options from files
			$this->deletewpconfig();
			$this->deletehtaccess();
			
			if ( function_exists( 'apc_store' ) ) { 
				apc_clear_cache(); //Let's clear APC (if it exists) when big stuff is saved.
			}
			
			//Get the right options
			if ( is_multisite() ) {
			
				switch_to_blog( 1 );
			
				delete_option( 'bwps_intrusion_warning' );
				
				delete_site_transient( 'bit51_bwps_backup' );

				delete_site_transient( 'bwps_away' );
			
				restore_current_blog();
			
			} else {
			
				delete_option( 'bwps_intrusion_warning' );

				delete_transient( 'bwps_away' );
				
				delete_transient( 'bit51_bwps_backup' );
				
			}
			
		}
		
		/**
		 * Uninstall execution
		 *
		 **/
		function uninstall_execute() {
			
			$this->deactivate_execute(); //execute deactivation functions
						
			//remove all settings
			foreach( $this->settings as $settings ) {
			
				foreach ( $settings as $setting => $option ) {
					
					//Delete the right options
					if ( is_multisite() ) {
					
						switch_to_blog( 1 );
					
						delete_option( $setting );
					
						restore_current_blog();
					
					} else {
					
						delete_option( $setting );
						
					}
					
				}
				
			}
			
			delete_option( 'bwps_file_log' );
			delete_option( 'bwps_awaymode' );
			delete_metadata( 'post', null, 'bwps_enable_ssl', null, true );
			
			global $wpdb;
			
			//drop database tables
			$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->base_prefix . "bwps_lockouts`;" );
			$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->base_prefix . "bwps_log`;" );
			
			//delete plugin information (version, etc)
			//Delete the right options
			if ( is_multisite() ) {
			
				switch_to_blog( 1 );
			
				delete_option( $this->plugindata );
			
				restore_current_blog();
			
			} else {
			
				delete_option( $this->plugindata );
				
			}
			
			if ( function_exists( 'apc_store' ) ) { 
				apc_clear_cache(); //Let's clear APC (if it exists) when big stuff is saved.
			}
			
		}
		
	}
	
}
