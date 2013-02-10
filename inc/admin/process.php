<?php

if ( ! class_exists( 'bwps_admin_process' ) ) {

	class bwps_admin_process extends bwps_admin_common {
	
		function __construct() {
		
			if (isset( $_POST['bwps_page']) ) {
				add_action( 'admin_init', array( &$this, 'form_dispatcher' ) );
			}
			
		}
	
		/**
		 * Form dispacther
		 *
		 * Executes appropriate process function based on post variable
		 *
		 **/
		function form_dispatcher() {
			//verify nonce
			if ( ! wp_verify_nonce( $_POST['wp_nonce'], 'BWPS_admin_save' ) ) {
				die( 'Security error!' );
			}
			
			switch ( $_POST['bwps_page'] ) {
				case 'adminuser_1':
					$this->adminuser_process_1();
					break;
				case 'adminuser_2':
					$this->adminuser_process_2();
					break;
				case 'awaymode_1':
					$this->awaymode_process_1();
					break;
				case 'banusers_1':
					$this->banusers_process_1();
					break;
				case 'banusers_2':
					$this->banusers_process_2();
					break;
				case 'contentdirectory_1':
					$this->contentdirectory_process_1();
					break;
				case 'dashboard_1':
					$this->dashboard_process_1();
					break;
				case 'dashboard_2':
					$this->dashboard_process_2();
					break;
				case 'dashboard_3':
					$this->dashboard_process_3();
					break;
				case 'dashboard_4':
					$this->dashboard_process_4();
					break;
				case 'dashboard_5':
					$this->dashboard_process_5();
					break;
				case 'databasebackup_1':
					$this->databasebackup_process_1();
					break;
				case 'databasebackup_2':
					$this->databasebackup_process_2();
					break;
				case 'databaseprefix_1':
					$this->databaseprefix_process_1();
					break;
				case 'hidebackend_1':
					$this->hidebackend_process_1();
					break;
				case 'intrusiondetection_1':
					$this->intrusiondetection_process_1();
					break;
				case 'intrusiondetection_2':
					$this->intrusiondetection_process_2();
					break;
				case 'loginlimits_1':
					$this->loginlimits_process_1();
					break;
				case 'log_1':
					$this->log_process_1();
					break;
				case 'log_2':
					$this->log_process_2();
					break;
				case 'ssl_1':
					$this->ssl_process_1();
					break;
				case 'systemtweaks_1':
					$this->systemtweaks_process_1();
					break;
			}
		}
		
		/**
		 * Process dashboard initial site backup
		 *
		 **/
		function dashboard_process_1() {
		
			global $bwps, $wpdb, $bwps_backup, $bwpsoptions;
		
			$errorHandler = __( 'Database Backup Completed.', $this->hook );
			
			$bwpsoptions['backup_last'] = current_time( 'timestamp' );
			$bwpsoptions['initial_backup'] = 1;
				
			update_option( $this->primarysettings, $bwpsoptions );
			
			//execute backup
			$bwps_backup->execute_backup();
			
			$bwps->clearcache( true );
			$this->showmessages( $errorHandler );		
			
		}
		
		/**
		 * Process dashboard initial site backup ignore
		 *
		 **/
		function dashboard_process_2() {
		
			global $bwps, $bwpsoptions;
		
			$errorHandler = __( 'Database Backup Ignored.', $this->hook );
			
			$bwpsoptions['initial_backup'] = 1;
			
			update_option( $this->primarysettings, $bwpsoptions );
			
			$bwps->clearcache( true );
			$this->showmessages( $errorHandler );		
			
		}
		
		/**
		 * Process dashboard initial file write confirm
		 *
		 **/
		function dashboard_process_3() {
		
			global $bwps, $bwpsoptions;
		
			$errorHandler = __( 'WordPress Core File Writing confirmed.', $this->hook );
			
			$bwpsoptions['initial_filewrite'] = 1;
			$bwpsoptions['st_writefiles'] = 1;
			
			update_option( $this->primarysettings, $bwpsoptions );
			
			$this->showmessages( $errorHandler );

			$adminurl = is_multisite() ? admin_url() . 'network/' : admin_url();

			header( 'Location: ' . $adminurl . 'admin.php?page=better-wp-security' );	
			
		}
		
		/**
		 * Process dashboard initial file write deny
		 *
		 **/
		function dashboard_process_4() {
		
			global $bwps, $bwpsoptions;
		
			$errorHandler = __( 'WordPress Core File Writing ignored.', $this->hook );
			
			$bwpsoptions['initial_filewrite'] = 1;
			$bwpsoptions['st_writefiles'] = 0;
			
			update_option( $this->primarysettings, $bwpsoptions );
			
			$this->showmessages( $errorHandler );

			$adminurl = is_multisite() ? admin_url() . 'network/' : admin_url();

			header( 'Location: ' . $adminurl . 'admin.php?page=better-wp-security' );
			
		}
		
		/**
		 * Process one-click security form
		 *
		 **/
		function dashboard_process_5() {
			
			global $bwps, $bwpsoptions, $bwpsmemlimit;
		
			$errorHandler = __( 'Site Secured.', $this->hook );

			if ( $_POST['oneclick'] == 1 ) {
			
				//select options for one-click access (enable all sections that don't write to files or are otherwise known to cause conflicts).
				$bwpsoptions['ll_enabled'] = 1;
				$bwpsoptions['id_enabled'] = 1;
				$bwpsoptions['st_generator'] = 1;
				$bwpsoptions['st_manifest'] = 1;
				$bwpsoptions['st_themenot'] = 1;
				$bwpsoptions['st_pluginnot'] = 1;
				$bwpsoptions['st_corenot'] = 1;
				$bwpsoptions['st_enablepassword'] = 1;
				$bwpsoptions['st_loginerror'] = 1;
				$bwpsoptions['oneclickchosen'] = 1;
				
				update_option( $this->primarysettings, $bwpsoptions );
				
				$errorHandler = __( 'Settings Saved. Your website is now protected from most attacks.', $this->hook );
				
				$bwps->clearcache( true );

			} else {

				$bwpsoptions['oneclickchosen'] = 1;
				update_option( $this->primarysettings, $bwpsoptions );

				$errorHandler = __( 'Initial configuration complete. Use the checklist below to enable additional features.', $this->hook );

			}

			$this->showmessages( $errorHandler );		
			
		}
		
		/**
		 * Process change admin user form
		 *
		 **/
		function adminuser_process_1() {
		
			global $bwps, $wpdb;
			$errorHandler = __( 'Successfully Changed admin Username. If you are logged in as admin you will have to log in again before continuing.', $this->hook );
			
			//sanitize the username
			$newuser = sanitize_text_field( $_POST['newuser'] );
			
			if ( strlen( $newuser ) < 1 ) { //if the field was left blank set an error message
			
				$errorHandler = new WP_Error();
				$errorHandler->add( '2', $newuser . __( 'You must enter a valid username. Please try again', $this->hook ) );
				
			} else {	
			
				if ( validate_username( $newuser ) ) { //make sure username is valid
				
					if ( $this->user_exists( $newuser ) ) { //if the user already exists set an error
					
						if ( ! is_wp_error( $errorHandler ) ) {
							$errorHandler = new WP_Error();
						}
								
						$errorHandler->add( '2', $newuser . __( ' already exists. Please try again', $this->hook ) );
								
					} else {
								
						//query main user table
						$wpdb->query( "UPDATE `" . $wpdb->users . "` SET user_login = '" . $wpdb->escape( $newuser ) . "' WHERE user_login='admin';" );
						
						if ( is_multisite() ) { //process sitemeta if we're in a multi-site situation
						
							$oldAdmins = $wpdb->get_var( "SELECT meta_value FROM `" . $wpdb->sitemeta . "` WHERE meta_key = 'site_admins'" );
							$newAdmins = str_replace( '5:"admin"', strlen( $newuser ) . ':"' . $wpdb->escape( $newuser ) . '"', $oldAdmins );
							$wpdb->query( "UPDATE `" . $wpdb->sitemeta . "` SET meta_value = '" . $wpdb->escape( $newAdmins ) . "' WHERE meta_key = 'site_admins'" );
							
						}
						
					}
					
				} else {
				
					if ( ! is_wp_error( $errorHandler ) ) { //set an error for invalid username
						$errorHandler = new WP_Error();
					}
				
					$errorHandler->add( '2', $newuser . __( ' is not a valid username. Please try again', $this->hook ) );
				}
			}
			
			$bwps->clearcache( true );
			$this-> showmessages( $errorHandler ); //finally show messages //finally show messages
			
			wp_clear_auth_cookie();
			
		}

		/**
		 * Process change admin user id form
		 *
		 **/
		function adminuser_process_2() {

			global $bwps;

			$errorHandler = __( 'Successfully Changed user 1 ID.', $this->hook );

			if ( $this->changeuserid() === false ) {

				if ( ! is_wp_error( $errorHandler ) ) { //set an error for invalid username
					$errorHandler = new WP_Error();
				}
				
				$errorHandler->add( '2', __( 'User 1\'s ID was not changed. Please try again', $this->hook ) );

			}

			$bwps->clearcache( true );
			$this-> showmessages( $errorHandler ); //finally show messages //finally show messages

		}
		
		/**
		 * Process away mode options form
		 *
		 **/
		function awaymode_process_1() {
		
			global $bwps, $bwpsoptions;
		
			$errorHandler = __( 'Settings Saved', $this->hook );
			
			//validate options
			$bwpsoptions['am_enabled'] = ( isset( $_POST['am_enabled'] ) && $_POST['am_enabled'] == 1  ? 1 : 0 );
			
			$bwpsoptions['am_type'] = ( isset( $_POST['am_type'] ) && $_POST['am_type'] == 1  ? 1 : 0 );
						
			//form times
			$startDate = strtotime( $_POST['am_startmonth'] . "/" . $_POST['am_startday'] . "/" . $_POST['am_startyear'] . ' 12:01 am' );
			$endDate = strtotime( $_POST['am_endmonth'] . "/" . $_POST['am_endday'] . "/" . $_POST['am_endyear'] . ' 12:01 am' );
			
			if ( $bwpsoptions['am_type'] == 0 && $endDate <= $startDate ) { //can't have an ending date before a starting date
			
				if ( ! is_wp_error( $errorHandler ) ) {
					$errorHandler = new WP_Error();
				}
						
				$errorHandler->add( '2', __( 'The ending date must be after the current date.', $this->hook ) );
			}
			
			$startTime = strtotime( '1/1/1970 ' . $_POST['am_starthour'] . ':' . $_POST['am_startmin'] . ' ' . $_POST['am_starthalf'] );
			$endTime = strtotime( '1/1/1970 ' . $_POST['am_endhour'] . ':' . $_POST['am_endmin'] . ' ' . $_POST['am_endhalf'] );
			
			if ( $bwpsoptions['am_type'] == 1 && $startTime == $endTime ) { //can't have an ending date before a starting date
			
				if ( ! is_wp_error( $errorHandler ) ) {
					$errorHandler = new WP_Error();
				}
						
				$errorHandler->add( '2', __( 'Your current settings would lock you out 24 hours a day. Please make sure start and end times differ.', $this->hook ) );
			}
			
			$bwpsoptions['am_startdate'] = $startDate;
			$bwpsoptions['am_enddate'] = $endDate;
			$bwpsoptions['am_starttime'] = $startTime;
			$bwpsoptions['am_endtime'] = $endTime;
			
			if ( ! is_wp_error( $errorHandler ) ) {

				update_option( $this->primarysettings, $bwpsoptions );

				if ( $bwpsoptions['st_writefiles'] == 1  ) {
				
					$this->writewpconfig(); //save to wp-config.php
					
				} else {
				
					if ( ! is_wp_error( $errorHandler ) ) {
						$errorHandler = new WP_Error();
					}
							
					$errorHandler->add( '2', __( 'Settings Saved. You will have to manually add code to your wp-config.php file See the Better WP Security Dashboard for the code you will need.', $this->hook ) );
				
				}

			}
			
			$bwps->clearcache( true );
			$this-> showmessages( $errorHandler ); //finally show messages
			
		}
		
		/**
		 * Process options form for ban hosts page
		 *
		 **/
		function banusers_process_1() {
		
			global $bwps, $bwpsoptions; 
			
			@ini_set( 'auto_detect_line_endings', true );
			
			$errorHandler = __( 'Settings Saved', $this->hook );
			
			$bwpsoptions['bu_enabled'] = ( isset( $_POST['bu_enabled'] ) && $_POST['bu_enabled'] == 1  ? 1 : 0 );
			
			//validate list
			$banhosts = explode( PHP_EOL, $_POST['bu_banlist'] );
			$list = array();
			
			if( ! empty( $banhosts ) ) {
			
				foreach( $banhosts as $item ) {
				
					$item = filter_var( $item, FILTER_SANITIZE_STRING );
				
					if ( strlen( $item ) > 0 ) {

						$ipParts = explode( '.', $item );
						$isIP = 0;
						$partcount = 1;
						$goodip = true;
						$foundwild = false;
						
						foreach ( $ipParts as $part ) {
						
							if ( $goodip == true ) {
							
								if ( ( is_numeric( trim( $part ) ) && trim( $part ) <= 255 && trim( $part ) >= 0 ) || trim( $part ) == '*' ) {
									$isIP++;
								}
															
								switch ( $partcount ) {
								
									case 1:
									
										if ( trim( $part ) == '*' ) {
										
											$goodip = false;
								
											if ( ! is_wp_error( $errorHandler ) ) { //invalid ip 
												$errorHandler = new WP_Error();
											}
										
											$errorHandler->add( '1', __( filter_var( $item, FILTER_SANITIZE_STRING ) . ' is note a valid ip.', $this->hook ) );
										
										}
										
										break;
										
									case 2:
									
										if ( trim( $part ) == '*' ) {
	
											$foundwild = true;
										
										}
									
										break;
										
									default:
									
										if ( trim( $part ) != '*' ) {
									
											if ( $foundwild == true ) {
										
												$goodip = false;
											
												if ( ! is_wp_error( $errorHandler ) ) { //invalid ip 
													$errorHandler = new WP_Error();
												}
													
												$errorHandler->add( '1', __( filter_var( $item, FILTER_SANITIZE_STRING ) . ' is note a valid ip.', $this->hook ) );
											
											}
										
										} else {
									
											$foundwild = true;	
									
										}
									
										break;
									
								}
							
								$partcount++;
							
							}
									
						}
							
						if ( ip2long( trim( str_replace( '*', '0', $item ) ) ) == false ) { //invalid ip 
								
							if ( ! is_wp_error( $errorHandler ) ) {
								$errorHandler = new WP_Error();
							}
									
							$errorHandler->add( '1', __( filter_var( $item, FILTER_SANITIZE_STRING ) . ' is not a valid ip.', $this->hook ) );
									
						} elseif ( strlen( $item > 4 && ! in_array( $item, $list ) ) ) {
								
							$list[] = trim( $item );
																			
						}
						
					}
						
				}
				
			}

			if ( sizeof( $list ) > 1 ) {
				sort( $list );
				$list = array_unique( $list, SORT_STRING );
			}
			
			$bwpsoptions['bu_banlist'] = implode( PHP_EOL, $list );
			
			if ( $bwps->checklist( $bwpsoptions['bu_banlist'] ) ) {
			
				if ( ! is_wp_error( $errorHandler) ) {
					$errorHandler = new WP_Error();
				}
				
				$errorHandler->add( '1', __( 'You cannot ban yourself. Please try again.', $this->hook ) );
				
			}
			
			//now to process useragents
			$banagents = explode( PHP_EOL, $_POST['bu_banagent'] );
			$agents = array();
			
			if ( ! empty( $banagents ) ) {
			
				foreach ( $banagents as $agent ) {
					
					$text = quotemeta( sanitize_text_field( $agent ) );

					$agents[] = $text;
					
				}
			
			}

			if ( sizeof( $agents ) > 1 ) {
				sort( $agents );
				$agents = array_unique( $agents, SORT_STRING );
			}
			
			$bwpsoptions['bu_banagent'] = implode( PHP_EOL, $agents );
			
			if ( ! is_wp_error( $errorHandler ) ) {
			
				update_option( $this->primarysettings, $bwpsoptions );
				
				if ( ( strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'apache' ) || strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'litespeed' ) ) && $bwpsoptions['st_writefiles'] == 1 ) { //if they're using apache write to .htaccess
					
					$this->writehtaccess();
					
					$errorHandler = __( 'Settings Saved.', $this->hook );

					define( 'BWPS_GOOD_LIST', true );
						
				} else { //not on apache to let them know they will have to manually enter rules
				
					$errorHandler = new WP_Error();
					
					$errorHandler->add( '2', __( 'Settings Saved. You will have to manually add rewrite rules to your configuration. See the Better WP Security Dashboard for a list of the rewrite rules you will need.', $this->hook ) );
					
				
				}
				
				
			}
						
			$bwps->clearcache( true );
			$this-> showmessages( $errorHandler ); //finally show messages
		}
		
		/**
		 * Process away mode options form
		 *
		 **/
		function banusers_process_2() {
		
			global $bwps, $bwpsoptions;
		
			$errorHandler = __( 'Settings Saved', $this->hook );
			
			//validate options
			$bwpsoptions['bu_blacklist'] = ( isset( $_POST['bu_blacklist'] ) && $_POST['bu_blacklist'] == 1  ? 1 : 0 );
			
			if ( ! is_wp_error( $errorHandler ) ) {
			
				update_option( $this->primarysettings, $bwpsoptions );
				
				if ( ( strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'apache' ) || strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'litespeed' ) ) && $bwpsoptions['st_writefiles'] == 1 ) { //if they're using apache write to .htaccess
					
					$this->writehtaccess();
					
					$errorHandler = __( 'Settings Saved.', $this->hook );
						
				} else { //not on apache to let them know they will have to manually enter rules
				
					$errorHandler = new WP_Error();
					
					$errorHandler->add( '2', __( 'Settings Saved. You will have to manually add rewrite rules to your configuration. See the Better WP Security Dashboard for a list of the rewrite rules you will need.', $this->hook ) );
					
				
				}
				
				
			}
						
			$bwps->clearcache( true );
			$this-> showmessages( $errorHandler ); //finally show messages
			
		}
		
		/**
		 * Process changing of wp-content directory
		 *
		 **/
		function contentdirectory_process_1() {
		
			global $bwps, $wpdb, $bwpsoptions;
			$errorHandler = __( 'Settings Saved', $this->hook );
			
			$oldDir = WP_CONTENT_DIR;
			$newDir = trailingslashit( ABSPATH ) . sanitize_text_field( $_POST['dirname'] );
			
			$renamed = rename( $oldDir, $newDir );
			
			if ( ! $renamed ) {
			
				if ( ! is_wp_error( $errorHandler ) ) {
					$errorHandler = new WP_Error();
				}
						
				$errorHandler->add( '2', __( 'Unable to rename the wp-content folder. Operation cancelled.', $this->hook ) );
				
			}
			
			$wpconfig = $this->getConfig(); //get the path for the config file
					
			if ( ! $f = @fopen( $wpconfig, 'a+' ) ) {
						
				@chmod( $wpconfig, 0644 );
				
				if ( ! $f = @fopen( $wpconfig, 'a+' ) ) {
							
					return -1;
							
				}
						
			}
			
			@fclose( $f );
					
			$handle = @fopen( $wpconfig, 'r+' ); //open for reading
					
			if ( $handle && $renamed ) {
			
				@ini_set( 'auto_detect_line_endings', true );
			
				$scanText = "<?php";
				$newText = "<?php" . PHP_EOL . PHP_EOL . "define( 'WP_CONTENT_DIR', '" . $newDir . "' );" . PHP_EOL . "define( 'WP_CONTENT_URL', '" . trailingslashit( get_option( 'siteurl' ) ) . sanitize_text_field( $_POST['dirname'] ) . "' );" . PHP_EOL . PHP_EOL;
					
				//read each line into an array
				while ( $lines[] = fgets( $handle, 4096 ) ) {}
						
				fclose( $handle ); //close reader
						
				$handle = @fopen( $wpconfig, 'w+' ); //open writer
						
				foreach ( $lines as $line ) { //process each line
						
					if ( strstr( $line, 'WP_CONTENT_DIR' ) || strstr( $line, 'WP_CONTENT_URL' ) ) {
					
						$line = str_replace( $line, '', $line );

					}

					if (strstr( $line, $scanText ) ) {
					
						$line = str_replace( $scanText, $newText, $line );

					}
							
					fwrite( $handle, $line ); //write the line
							
				}
						
				fclose( $handle ); //close the config file
				
				if ( $bwpsoptions['st_fileperm'] == 1 ) {
					@chmod( $wpconfig, 0444 ); //make sure the config file is no longer writable
				}		
						
			}
			
			$bwps->clearcache( true );
			$bwps->clearcache( true );
			$this-> showmessages( $errorHandler ); //finally show messages //finally show messages
			
		}
		
		/**
		 * Process spot database backup
		 *
		 **/
		function databasebackup_process_1() {
		
			global $bwps, $bwps_backup, $bwpsoptions;
		
			$errorHandler = __( 'Database Backup Completed.', $this->hook );
			
			$bwpsoptions['backup_last'] = current_time( 'timestamp' );
				
			update_option( $this->primarysettings, $bwpsoptions );
			
			$bwps_backup->execute_backup();
			
			$this->showmessages( $errorHandler );		
			
		}
		
		/**
		 * Process scheduled database backups options form
		 *
		 **/
		function databasebackup_process_2() {
		
			global $bwps, $bwps_backup, $bwpsoptions;
			
			$errorHandler = __( 'Settings Saved', $this->hook );
			
			//validate options
			$bwpsoptions['backup_enabled'] = ( isset( $_POST['backup_enabled'] ) && $_POST['backup_enabled'] == 1  ? 1 : 0 );
			$bwpsoptions['backup_email'] = ( isset( $_POST['backup_email'] ) && $_POST['backup_email'] == 1  ? 1 : 0 );
			$bwpsoptions['backups_to_retain'] = absint( $_POST['backups_to_retain'] );
			$bwpsoptions['backup_time'] = ( isset( $_POST['backup_time'] ) && absint( $_POST['backup_time'] ) > 0 ? absint( $_POST['backup_time'] ) : 1 );
			$bwpsoptions['backup_interval'] = $_POST['backup_interval'];
			
			if ( is_email( $_POST['backup_emailaddress'] ) ) {
			
				$bwpsoptions['backup_emailaddress'] = $_POST['backup_emailaddress'];
			
			} else {
			
				if ( ! is_wp_error( $errorHandler ) ) {
					$errorHandler = new WP_Error();
				}
						
				$errorHandler->add( '2', __( 'The email address you entered is not a valid email address. You must enter a valid email address.', $this->hook ) );
			
			}
			
			if ( $bwpsoptions['backup_enabled'] == 1 ) {
			
				$nextbackup = $bwpsoptions['backup_next']; //get next schedule
				
				switch ( $bwpsoptions['backup_interval'] ) { //schedule backup at appropriate time
					case '0':
						$next = 60 * 60 * $bwpsoptions['backup_time'];
						break;
					case '1':
						$next = 60 * 60 * 24 * $bwpsoptions['backup_time'];
						break;
					case '2':
						$next = 60 * 60 * 24 * 7  * $bwpsoptions['backup_time'];
						break;
				}
					
				if ( $bwpsoptions['backup_last'] == '' ) { //don't run a new backup until we need it to reduce load
				
					$bwpsoptions['backup_next'] = ( current_time( 'timestamp' ) + $next );
				
				} else {
				
					$bwpsoptions['backup_next'] = ( $bwpsoptions['backup_last'] + $next );
				
				}
				
			} else { //backups aren't scheduled so clear time
				
				$bwpsoptions['backup_next'] = '';
				$bwpsoptions['backup_last'] = '';
				
			}
						
			update_option( $this->primarysettings, $bwpsoptions );
			
			if ( $bwpsoptions['backup_email'] == 1 ) { //if backups are done by email remove any files saved to the disk
			
				$files = scandir( BWPS_PP . '/backups/', 1 );
				
				foreach ( $files as $file ) {
					if ( strstr( $file, 'database-backup' ) ) {
						unlink ( BWPS_PP . '/backups/' . $file );
					}
				}
				
			}
			
			$bwps->clearcache( true );
			$this-> showmessages( $errorHandler ); //finally show messages
			
		}
		
		/**
		 * Process database prefix change
		 *
		 **/
		function databaseprefix_process_1() {
			global $bwps, $wpdb, $bwpsoptions;
			$errorHandler = __( 'Database Prefix Changed', $this->hook );	
	
			$checkPrefix = true;//Assume the first prefix we generate is unique
			
			//generate prefixes until we have one that is valid
			while ( $checkPrefix ) {
			
				$avail = 'abcdefghijklmnopqrstuvwxyz0123456789';
				
				//first character should be alpha
				$newPrefix = $avail[rand( 0, 25 )];
				
				//length of new prefix
				$prelength = rand( 4, 9 );
				
				//generate remaning characters
				for ( $i = 0; $i < $prelength; $i++ ) {
					$newPrefix .= $avail[rand( 0, 35 )];
				}
				
				//complete with underscore
				$newPrefix .= '_';
				
				$newPrefix = $wpdb->escape( $newPrefix ); //just be safe
				
				$checkPrefix = $wpdb->get_results( 'SHOW TABLES LIKE "' . $newPrefix . '%";', ARRAY_N ); //if there are no tables with that prefix in the database set checkPrefix to false
					
			}
				
			$tables = $wpdb->get_results( 'SHOW TABLES LIKE "' . $wpdb->base_prefix . '%"', ARRAY_N ); //retrieve a list of all tables in the DB
					
			//Rename each table
			foreach ( $tables as $table ) {
					
				$table = substr( $table[0], strlen( $wpdb->base_prefix ), strlen( $table[0] ) ); //Get the table name without the old prefix
		
				//rename the table and generate an error if there is a problem
				if ( $wpdb->query( 'RENAME TABLE `' . $wpdb->base_prefix . $table . '` TO `' . $newPrefix . $table . '`;' ) === false ) {
		
					if ( ! is_wp_error( $errorHandler ) ) { //set an error for invalid username
						$errorHandler = new WP_Error();
					}
		
					$errorHandler->add( '2', __( 'Error: Could not rename table ', $this->hook ) . $wpdb->base_prefix . __( '. You may have to rename the table manually.', $this->hook ) );	
						
				}
						
			}
					
			$upOpts = true; //assume we've successfully updated all options to start
					
			if ( is_multisite() ) { //multisite requires us to rename each blogs' options
						
				$blogs = $wpdb->get_col( "SELECT blog_id FROM `" . $newPrefix . "blogs` WHERE public = '1' AND archived = '0' AND mature = '0' AND spam = '0' ORDER BY blog_id DESC" ); //get list of blog id's
					
				if ( is_array( $blogs) ) { //make sure there are other blogs to update
						
					//update each blog's user_roles option
					foreach ( $blogs as $blog ) {
							
						$results = $wpdb->query( 'UPDATE `' . $newPrefix . $blog . '_options` SET option_name = "' . $newPrefix . $blog . '_user_roles" WHERE option_name = "' . $wpdb->base_prefix . $blog . '_user_roles" LIMIT 1;' );
								
						if ( $results === false ) { //if there's an error upOpts should equal false
							$upOpts = false;
						}
								
					}
							
				}
						
			}
					
			$upOpts = $wpdb->query( 'UPDATE `' . $newPrefix . 'options` SET option_name = "' . $newPrefix . 'user_roles" WHERE option_name = "' . $wpdb->base_prefix . 'user_roles" LIMIT 1;' ); //update options table and set flag to false if there's an error
										
			if ( $upOpts === false ) { //set an error
		
				if ( ! is_wp_error( $errorHandler ) ) {
					$errorHandler = new WP_Error();
				}
							
				$errorHandler->add( '2', __( 'Could not update prefix refences in options tables.', $this->hook ) );
						
			}
										
			$rows = $wpdb->get_results( 'SELECT * FROM `' . $newPrefix . 'usermeta`' ); //get all rows in usermeta
										
			//update all prefixes in usermeta
			foreach ( $rows as $row ) {
					
				if ( substr( $row->meta_key, 0, strlen( $wpdb->base_prefix ) ) == $wpdb->base_prefix ) {
						
					$pos = $newPrefix . substr( $row->meta_key, strlen( $wpdb->base_prefix ), strlen( $row->meta_key ) );
							
					$result = $wpdb->query( 'UPDATE `' . $newPrefix . 'usermeta` SET meta_key="' . $pos . '" WHERE meta_key= "' . $row->meta_key . '" LIMIT 1;' );
							
					if ( $result == false ) {
								
						if ( ! is_wp_error( $errorHandler ) ) {
							$errorHandler = new WP_Error();
						}
										
						$errorHandler->add( '2', __( 'Could not update prefix refences in usermeta table.', $this->hook ) );
								
					}
							
				}
						
			}
					
			$wpconfig = $this->getConfig(); //get the path for the config file
					
			if ( ! $f = @fopen( $wpconfig, 'a+' ) ) {
						
				@chmod( $wpconfig, 0644 );
				
				if ( ! $f = @fopen( $wpconfig, 'a+' ) ) {
							
					return -1;
							
				}
						
			}
			
			@fclose( $f );
					
			$handle = @fopen( $wpconfig, "r+" ); //open for reading
					
			if ( $handle ) {
					
				//read each line into an array
				while ( $lines[] = fgets( $handle, 4096 ) ){}
						
				fclose( $handle ); //close reader
						
				$handle = @fopen( $wpconfig, "w+" ); //open writer
						
				foreach ( $lines as $line ) { //process each line
						
					//if the prefix is in the line
					if ( strpos( $line, 'table_prefix' ) ) {
							
						$line = str_replace( $wpdb->base_prefix, $newPrefix, $line );
								
					}
							
					fwrite( $handle, $line ); //write the line
							
				}
						
				fclose( $handle ); //close the config file
						
				if ( $bwpsoptions['st_fileperm'] == 1 ) {
					@chmod( $wpconfig, 0444 ); //make sure the config file is no longer writable
				}
						
				$wpdb->base_prefix = $newPrefix; //update the prefix
						
			}
					
			$bwps->clearcache( true );
			$bwps->clearcache( true );
			$this-> showmessages( $errorHandler ); //finally show messages //finally show messages
			
			remove_action( 'admin_notices', 'site_admin_notice' );
			remove_action( 'network_admin_notices', 'site_admin_notice' );
					
		}	
		
		/**
		 * Process options for hide backend form
		 *
		 **/
		function hidebackend_process_1() {
		
			global $bwps, $bwpsoptions;
		
			$errorHandler = __( 'Settings Saved', $this->hook );
			
			//if they don't have permalinks enabled set an error
			if ( get_option( 'permalink_structure' ) == '' && ! is_multisite() ) {
			
				if ( ! is_wp_error( $errorHandler ) ) {
					$errorHandler = new WP_Error();
				}
								
				$errorHandler->add( '2', __( 'You must enable permalinks in your WordPress settings for this feature to work.', $this->hook ) );
			
			}
			
			//calidate options
			$bwpsoptions['hb_enabled'] = ( isset( $_POST['hb_enabled'] ) && $_POST['hb_enabled'] == 1  ? 1 : 0 );
			$bwpsoptions['hb_login'] = sanitize_text_field( $_POST['hb_login'] );
			$bwpsoptions['hb_admin'] = sanitize_text_field( $_POST['hb_admin'] );
			$bwpsoptions['hb_register'] = sanitize_text_field( $_POST['hb_register'] );
			
			//generate a secret key (if there isn't one already)
			if ( $bwpsoptions['hb_key'] == '' || ( isset( $_POST['hb_getnewkey'] ) && $_POST['hb_getnewkey'] == 1 ) ) {
				$bwpsoptions['hb_key'] = $this->hidebe_genKey();
			}
			
			if ( ! is_wp_error( $errorHandler ) ) {
			
				update_option( $this->primarysettings, $bwpsoptions );
				
				if ( ( strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'apache' ) || strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'litespeed' ) ) && $bwpsoptions['st_writefiles'] == 1 ) { //if they're using apache write to .htaccess
					
					$this->writehtaccess();
					
					$errorHandler = __( 'Settings Saved.', $this->hook );
						
				} else { //not on apache to let them know they will have to manually enter rules
				
					$errorHandler = new WP_Error();
					
					$errorHandler->add( '2', __( 'Settings Saved. You will have to manually add rewrite rules to your configuration. See the Better WP Security Dashboard for a list of the rewrite rules you will need.', $this->hook ) );
					
				
				}
				
				
			}
						
			$bwps->clearcache( true );
			$this-> showmessages( $errorHandler ); //finally show messages
		
		}
		
		/**
		 * Process manual file scan
		 *
		 **/
		function intrusiondetection_process_1() {
		
			global $bwps, $bwpsoptions, $bwps_filecheck;
			
			$errorHandler = __( 'File Check Complete.', $this->hook );
				
			$bwpsoptions['id_filechecktime'] = current_time( 'timestamp' );
					
			update_option( $this->primarysettings, $bwpsoptions );
				
			$bwps_filecheck->execute_filecheck( false );
				
			$this->showmessages( $errorHandler );	
		
		}
		
		/**
		 * Process options for intrusion detection form
		 *
		 **/
		function intrusiondetection_process_2() {
		
			global $bwps, $bwpsoptions;
			
			@ini_set( 'auto_detect_line_endings', true );
		
			$errorHandler = __( 'Settings Saved', $this->hook );
			
			//validate the input
			$bwpsoptions['id_blacklistipthreshold'] = isset( $_POST['id_blacklistipthreshold'] ) ? absint( $_POST['id_blacklistipthreshold'] ) : 3;
			$bwpsoptions['id_blacklistip'] = ( isset( $_POST['id_blacklistip'] ) && $_POST['id_blacklistip'] == 1  ? 1 : 0 );
			$bwpsoptions['id_enabled'] = ( isset( $_POST['id_enabled'] ) && $_POST['id_enabled'] == 1  ? 1 : 0 );
			$bwpsoptions['id_emailnotify'] = ( isset( $_POST['id_emailnotify'] ) && $_POST['id_emailnotify'] == 1  ? 1 : 0 );
			$bwpsoptions['id_checkinterval'] = absint( $_POST['id_checkinterval'] );
			$bwpsoptions['id_banperiod'] = absint( $_POST['id_banperiod'] );
			$bwpsoptions['id_threshold'] = absint( $_POST['id_threshold'] );
			$bwpsoptions['id_fileenabled'] = ( isset( $_POST['id_fileenabled'] ) && $_POST['id_fileenabled'] == 1  ? 1 : 0 );
			$bwpsoptions['id_fileemailnotify'] = ( isset( $_POST['id_fileemailnotify'] ) && $_POST['id_fileemailnotify'] == 1  ? 1 : 0 );
			$bwpsoptions['id_fileincex'] = ( isset( $_POST['id_fileincex'] ) && $_POST['id_fileincex'] == 1  ? 1 : 0 );
			$bwpsoptions['id_filedisplayerror'] = ( isset( $_POST['id_filedisplayerror'] ) && $_POST['id_filedisplayerror'] == 1  ? 1 : 0 );
			$bwpsoptions['id_filechecktime'] = '';
			
			if ( is_email( $_POST['id_fileemailaddress'] ) ) {
			
				$bwpsoptions['id_fileemailaddress'] = $_POST['id_fileemailaddress'];
			
			} else {
			
				if ( ! is_wp_error( $errorHandler ) ) {
					$errorHandler = new WP_Error();
				}
						
				$errorHandler->add( '2', __( 'The email address you entered for the file check email is not a valid email address. You must enter a valid email address.', $this->hook ) );
			
			}
			
			if ( is_email( $_POST['id_emailaddress'] ) ) {
			
				$bwpsoptions['id_emailaddress'] = $_POST['id_emailaddress'];
			
			} else {
			
				if ( ! is_wp_error( $errorHandler ) ) {
					$errorHandler = new WP_Error();
				}
						
				$errorHandler->add( '2', __( 'The email address you entered for the detect 404 email is not a valid email address. You must enter a valid email address.', $this->hook ) );
			
			}
			
			$fileWhiteItems = explode( PHP_EOL, $_POST['id_specialfile'] );
			$fileList = array();
			
			foreach ( $fileWhiteItems as $item ) {
				
				$fileList[] = sanitize_text_field( $item );
			
			}
			
			$bwpsoptions['id_specialfile'] = implode( PHP_EOL, $fileList );
			
			//if they set an invalid ban period set an error
			if ( $bwpsoptions['id_banperiod'] == 0 ) {
			
				if ( ! is_wp_error( $errorHandler ) ) {
					$errorHandler = new WP_Error();
				}
						
				$errorHandler->add( '2', __( 'Lockout time period needs to be aan integer greater than 0.', $this->hook ) );
				
			}
			
			//if they set an invalid check interval set an error
			if ( $bwpsoptions['id_checkinterval'] == 0 ) {
			
				if ( ! is_wp_error( $errorHandler ) ) {
					$errorHandler = new WP_Error();
				}
						
				$errorHandler->add( '2', __( 'Login time period needs to be aan integer greater than 0.', $this->hook ) );
				
			}
			
			//if they set an invalid 404 threshold set an error
			if ( $bwpsoptions['id_threshold'] == 0 ) {
			
				if ( ! is_wp_error( $errorHandler ) ) {
					$errorHandler = new WP_Error();
				}
						
				$errorHandler->add( '2', __('The error threshold needs to be aan integer greater than 0.', $this->hook ) );
				
			}
			
			//process the whitelist
			$whiteList = explode( PHP_EOL, $_POST['id_whitelist'] );
			$whiteitems = array();
			
			if( ! empty( $whiteList ) ) {
			
				$list = array();
					
				foreach( $whiteList as $item ) {
						
					if ( strlen( $item ) > 0 ) {
							
						$ipParts = explode( '.', $item );
						$isIP = 0;
						$partcount = 1;
						$goodip = true;
						$foundwild = false;
								
						foreach ( $ipParts as $part ) {
								
							if ( $goodip == true ) {
							
								if ( ( is_numeric( trim( $part ) ) && trim( $part ) <= 255 && trim( $part ) >= 0 ) || trim( $part ) == '*' ) {
									$isIP++;
								}
																	
								switch ( $partcount ) {
										
									case 1:
											
										if ( trim( $part ) == '*' ) {
												
											$goodip = false;
										
											if ( ! is_wp_error( $errorHandler ) ) { //invalid ip 
												$errorHandler = new WP_Error();
											}
												
											$errorHandler->add( '1', __( filter_var( $item, FILTER_SANITIZE_STRING ) . ' is note a valid ip.', $this->hook ) );
												
										}
												
										break;
												
									case 2:
											
										if ( trim( $part ) == '*' ) {
			
											$foundwild = true;
												
										}
											
										break;
												
									default:
											
										if ( trim( $part ) != '*' ) {
											
											if ( $foundwild == true ) {
												
												$goodip = false;
													
												if ( ! is_wp_error( $errorHandler ) ) { //invalid ip 
													$errorHandler = new WP_Error();
												}
															
												$errorHandler->add( '1', __( filter_var( $item, FILTER_SANITIZE_STRING ) . ' is note a valid ip.', $this->hook ) );
													
											}
												
										} else {
											
											$foundwild = true;	
											
										}
											
										break;
											
								}
									
								$partcount++;
									
							}
											
						}
						
						if ( ip2long( trim( str_replace( '*', '0', $item ) ) ) == false ) { //invalid ip 
										
							if ( ! is_wp_error( $errorHandler ) ) {
								$errorHandler = new WP_Error();
							}
											
							$errorHandler->add( '1', __( filter_var( $item, FILTER_SANITIZE_STRING ) . ' is not a valid ip.', $this->hook ) );
											
						} else {
										
							$list[] = trim( $item );
																					
						}
								
					}
								
				}
						
			}
			
			$bwpsoptions['id_whitelist'] = implode( PHP_EOL, $list );
			
			if ( ! is_wp_error( $errorHandler ) ) {
				update_option( $this->primarysettings, $bwpsoptions );

				if ( $bwpsoptions['st_writefiles'] == 1  ) {
				
					$this->writewpconfig(); //save to wp-config.php
					
				} else {
				
					if ( ! is_wp_error( $errorHandler ) ) {
						$errorHandler = new WP_Error();
					}
							
					$errorHandler->add( '2', __( 'Settings Saved. You will have to manually add code to your wp-config.php file See the Better WP Security Dashboard for the code you will need.', $this->hook ) );
				
				}

			}
						
			$bwps->clearcache( true );
			$this-> showmessages( $errorHandler ); //finally show messages
		
		}
		
		/**
		 * Process save options for login limits page
		 *
		 **/
		function loginlimits_process_1() {
		
			global $bwps, $bwpsoptions;
		
			$errorHandler = __( 'Settings Saved', $this->hook );
			
			//valitdate input
			$bwpsoptions['ll_blacklistipthreshold'] = isset( $_POST['ll_blacklistipthreshold'] ) ? absint( $_POST['ll_blacklistipthreshold'] ) : 3;
			$bwpsoptions['ll_blacklistip'] = ( isset( $_POST['ll_blacklistip'] ) && $_POST['ll_blacklistip'] == 1  ? 1 : 0 );
			$bwpsoptions['ll_enabled'] = ( isset( $_POST['ll_enabled'] ) && $_POST['ll_enabled'] == 1  ? 1 : 0 );
			$bwpsoptions['ll_emailnotify'] = ( isset( $_POST['ll_emailnotify'] ) && $_POST['ll_emailnotify'] == 1  ? 1 : 0 );
			$bwpsoptions['ll_maxattemptshost'] = absint( $_POST['ll_maxattemptshost'] );
			$bwpsoptions['ll_maxattemptsuser'] = absint( $_POST['ll_maxattemptsuser'] );
			$bwpsoptions['ll_checkinterval'] = absint( $_POST['ll_checkinterval'] );
			$bwpsoptions['ll_banperiod'] = absint( $_POST['ll_banperiod'] );
			
			if ( is_email( $_POST['ll_emailaddress'] ) ) {
			
				$bwpsoptions['ll_emailaddress'] = $_POST['ll_emailaddress'];
			
			} else {
			
				if ( ! is_wp_error( $errorHandler ) ) {
					$errorHandler = new WP_Error();
				}
						
				$errorHandler->add( '2', __( 'The email address you entered for lockout notifications is not a valid email address. You must enter a valid email address.', $this->hook ) );
			
			}
			
			//if they entered an invalid ban period set an error
			if ( $bwpsoptions['ll_banperiod'] == 0 ) {
			
				if ( ! is_wp_error( $errorHandler ) ) {
					$errorHandler = new WP_Error();
				}
						
				$errorHandler->add( '2', __( 'Lockout time period needs to be aan integer greater than 0.', $this->hook ) );
				
			}
			
			//if the intered an invalid check interval set an error
			if ( $bwpsoptions['ll_checkinterval'] == 0 ) {
			
				if ( ! is_wp_error( $errorHandler ) ) {
					$errorHandler = new WP_Error();
				}
						
				$errorHandler->add( '2', __( 'Login time period needs to be aan integer greater than 0.', $this->hook ) );
				
			}
			
			//if they entered invalid max attempts per host set and error
			if ( $bwpsoptions['ll_maxattemptshost'] == 0 ) {
			
				if ( ! is_wp_error( $errorHandler ) ) {
					$errorHandler = new WP_Error();
				}
						
				$errorHandler->add( '2', __( 'Max login attempts per host needs to be aan integer greater than 0.', $this->hook ) );
				
			}
			
			//if they entered invalid max attempts per user set an error
			if ( $bwpsoptions['ll_maxattemptsuser'] == 0 ) {
			
				if ( ! is_wp_error( $errorHandler ) ) {
					$errorHandler = new WP_Error();
				}
						
				$errorHandler->add( '2', __( 'Max login attempts per user needs to be aan integer greater than 0.', $this->hook ) );
				
			}
			
			//if there are no errors save the options to the database
			if ( ! is_wp_error( $errorHandler ) ) {
				update_option( $this->primarysettings, $bwpsoptions );
			}
						
			$bwps->clearcache( true );
			$this-> showmessages( $errorHandler ); //finally show messages
			
		}
		
		/**
		 * Process clearing old records form from view log page
		 *
		 **/
		function log_process_1() {
		
			global $bwps, $wpdb, $bwpsoptions;
			
			$errorHandler = __( 'The selected records have been cleared.', $this->hook );
			
			if ( isset( $_POST['badlogins'] ) && $_POST['badlogins'] == 1 ) { //delete old bad logins
				$wpdb->query( "DELETE FROM `" . $wpdb->base_prefix . "bwps_log` WHERE `type` = 1;" );
			}
			
			if ( isset( $_POST['404s'] ) && $_POST['404s'] == 1 ) { //delete old 404s
				$wpdb->query( "DELETE FROM `" . $wpdb->base_prefix . "bwps_log` WHERE `type` = 2;" );
			}
			
			if ( isset( $_POST['lockouts'] ) && $_POST['lockouts'] == 1 ) { //delete old or inactive lockouts
				$wpdb->query( "DELETE FROM `" . $wpdb->base_prefix . "bwps_lockouts`;" );
			}
			
			if ( isset( $_POST['changes'] ) && $_POST['changes'] == 1 ) { //delete old file records
				$wpdb->query( "DELETE FROM `" . $wpdb->base_prefix . "bwps_log` WHERE `type` = 3;" );
			}
						
			$bwps->clearcache();
			$this-> showmessages( $errorHandler ); //finally show messages
		}
		
		/**
		 * Process clearing lockouts on view log page
		 *
		 **/
		function log_process_2() {
			global $bwps, $wpdb;
			
			$errorHandler = __( 'The selected lockouts have been cleared.', $this->hook );
			
			foreach ( $_POST as $key => $value ) {
			
				if ( strstr( $key, "lo_" ) ) { //see if it's a lockout to avoid processings extra post fields
				
					$wpdb->update(
						$wpdb->base_prefix . 'bwps_lockouts',
						array(
							'active' => 0
						),
						array(
							'id' => $value
						)
					);
					
				}
				
			}
			
			$bwps->clearcache();
			$this-> showmessages( $errorHandler ); //finally show messages
			
		}
		
		/**
		 * Process rewrite tweaks from system tweaks page
		 *
		 **/
		function ssl_process_1() {
		
			global $bwps, $bwpsoptions;
			
			@ini_set( 'auto_detect_line_endings', true );
		
			$errorHandler = __( 'Settings Saved', $this->hook );
			
			//validate options
			$bwpsoptions['ssl_forcelogin'] = ( isset( $_POST['ssl_forcelogin'] ) && $_POST['ssl_forcelogin'] == 1  ? 1 : 0 );
			$bwpsoptions['ssl_forceadmin'] = ( isset( $_POST['ssl_forceadmin'] ) && $_POST['ssl_forceadmin'] == 1  ? 1 : 0 );
			$bwpsoptions['ssl_frontend'] = ( isset( $_POST['ssl_frontend'] ) ? $_POST['ssl_frontend'] : 0 );
			
						
			if ( ! is_wp_error( $errorHandler ) ) {
			
				update_option( $this->primarysettings, $bwpsoptions );
				
				if ( $bwpsoptions['st_writefiles'] == 1  ) {
				
					$this->writewpconfig(); //save to wp-config.php
					
				} else {
				
					if ( ! is_wp_error( $errorHandler ) ) {
						$errorHandler = new WP_Error();
					}
							
					$errorHandler->add( '2', __( 'Settings Saved. You will have to manually add code to your wp-config.php file See the Better WP Security Dashboard for the code you will need.', $this->hook ) );
				
				}
				
				if ( ( strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'apache' ) || strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'litespeed' ) ) && $bwpsoptions['st_writefiles'] == 1 ) { //if they're using apache write to .htaccess
				
					$this->writehtaccess();
					
				} else { //if they're not using apache let them know to manually update rules
				
					if ( is_wp_error( $errorHandler ) ) {
					
						$errorHandler = new WP_Error();
						
						$errorHandler->add( '2', __( 'Settings Saved. You will have to manually add rewrite rules and wp-config.php code to your configuration. See the Better WP Security Dashboard for a list of the rewrite rules  and wp-config.php code you will need.', $this->hook ) );
						
					} else {
						
						$errorHandler = new WP_Error();
						
						$errorHandler->add( '2', __( 'Settings Saved. You will have to manually add rewrite rules to your configuration. See the Better WP Security Dashboard for a list of the rewrite rules you will need.', $this->hook ) );
					
					}
				
				}
				
				if ( ! is_wp_error( $errorHandler ) ) {
					$errorHandler = __( 'Settings Saved.', $this->hook );
				}
				
			}
						
			$bwps->clearcache( true );
			$this-> showmessages( $errorHandler ); //finally show messages
			
		}
		
		
		/**
		 * Process rewrite tweaks from system tweaks page
		 *
		 **/
		function systemtweaks_process_1() {
		
			global $bwps, $bwpsoptions;
		
			$errorHandler = __( 'Settings Saved', $this->hook );
			
			//validate options
			$bwpsoptions['st_ht_files'] = ( isset( $_POST['st_ht_files'] ) && $_POST['st_ht_files'] == 1  ? 1 : 0 );
			$bwpsoptions['st_ht_request'] = ( isset( $_POST['st_ht_request'] ) && $_POST['st_ht_request'] == 1  ? 1 : 0 );
			$bwpsoptions['st_ht_query'] = ( isset( $_POST['st_ht_query'] ) && $_POST['st_ht_query'] == 1  ? 1 : 0 );
						
			//always set directory browsing to 1 on nginx to prevent nag
			if ( strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'apache' ) ) {
				$bwpsoptions['st_ht_browsing'] = ( isset( $_POST['st_ht_browsing'] ) && $_POST['st_ht_browsing'] == 1  ? 1 : 0 );
			} else {
				$bwpsoptions['st_ht_browsing'] = 1;
			}	
			
			$bwpsoptions['st_generator'] = ( isset( $_POST['st_generator'] ) && $_POST['st_generator'] == 1  ? 1 : 0 );
			$bwpsoptions['st_manifest'] = ( isset( $_POST['st_manifest'] ) && $_POST['st_manifest'] == 1  ? 1 : 0 );
			$bwpsoptions['st_edituri'] = ( isset( $_POST['st_edituri'] ) && $_POST['st_edituri'] == 1  ? 1 : 0 );
			$bwpsoptions['st_themenot'] = ( isset( $_POST['st_themenot'] ) && $_POST['st_themenot'] == 1  ? 1 : 0 );
			$bwpsoptions['st_pluginnot'] = ( isset( $_POST['st_pluginnot'] ) && $_POST['st_pluginnot'] == 1  ? 1 : 0 );
			$bwpsoptions['st_corenot'] = ( isset( $_POST['st_corenot'] ) && $_POST['st_corenot'] == 1  ? 1 : 0 );
			$bwpsoptions['st_enablepassword'] = ( isset( $_POST['st_enablepassword'] ) && $_POST['st_enablepassword'] == 1  ? 1 : 0 );
			if ( ctype_alpha( wp_strip_all_tags( $_POST['st_passrole'] ) ) ) {
				$bwpsoptions['st_passrole'] = wp_strip_all_tags( $_POST['st_passrole'] );
			}
			$bwpsoptions['st_loginerror'] = ( isset( $_POST['st_loginerror'] ) && $_POST['st_loginerror'] == 1  ? 1 : 0 );
			$bwpsoptions['st_fileperm'] = ( isset( $_POST['st_fileperm'] ) && $_POST['st_fileperm'] == 1  ? 1 : 0 );
			$bwpsoptions['st_randomversion'] = ( isset( $_POST['st_randomversion'] ) && $_POST['st_randomversion'] == 1  ? 1 : 0 );
			$bwpsoptions['st_longurl'] = ( isset( $_POST['st_longurl'] ) && $_POST['st_longurl'] == 1  ? 1 : 0 );
			$bwpsoptions['st_fileedit'] = ( isset( $_POST['st_fileedit'] ) && $_POST['st_fileedit'] == 1  ? 1 : 0 );
			$bwpsoptions['st_writefiles'] = ( isset( $_POST['st_writefiles'] ) && $_POST['st_writefiles'] == 1  ? 1 : 0 );
			$bwpsoptions['st_comment'] = ( isset( $_POST['st_comment'] ) && $_POST['st_comment'] == 1  ? 1 : 0 );
						
			if ( ! is_wp_error( $errorHandler ) ) {
			
				update_option( $this->primarysettings, $bwpsoptions );
				
				if ( $bwpsoptions['st_writefiles'] == 1  ) {
				
					$this->writewpconfig(); //save to wp-config.php
					
				} else {
				
					if ( ! is_wp_error( $errorHandler ) ) {
						$errorHandler = new WP_Error();
					}
							
					$errorHandler->add( '2', __( 'Settings Saved. You will have to manually add code to your wp-config.php file See the Better WP Security Dashboard for the code you will need.', $this->hook ) );
				
				}
				
				if ( ( strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'apache' ) || strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'litespeed' ) ) && $bwpsoptions['st_writefiles'] == 1 ) { //if they're using apache write to .htaccess
				
					$this->writehtaccess();
					
				} else { //if they're not using apache let them know to manually update rules
				
					if ( is_wp_error( $errorHandler ) ) {
					
						$errorHandler = new WP_Error();
						
						$errorHandler->add( '2', __( 'Settings Saved. You will have to manually add rewrite rules and wp-config.php code to your configuration. See the Better WP Security Dashboard for a list of the rewrite rules  and wp-config.php code you will need.', $this->hook ) );
						
					} else {
						
						$errorHandler = new WP_Error();
						
						$errorHandler->add( '2', __( 'Settings Saved. You will have to manually add rewrite rules to your configuration. See the Better WP Security Dashboard for a list of the rewrite rules you will need.', $this->hook ) );
					
					}
				
				}
				
				if ( ! is_wp_error( $errorHandler ) ) {
					$errorHandler = __( 'Settings Saved.', $this->hook );
				}
				
			}
						
			$bwps->clearcache( true );
			$this-> showmessages( $errorHandler ); //finally show messages
			
		}
	
	}

}
