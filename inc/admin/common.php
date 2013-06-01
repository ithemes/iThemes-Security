<?php

if ( ! class_exists( 'bwps_admin_common' ) ) {

	class bwps_admin_common extends bit51_bwps {
		
		/**
		 * Redirects to homepage if awaymode is active
		 *
		 **/
		function awaycheck() {
		
			global $bwps;
			
			if( $bwps->checkaway() ) {
				wp_redirect( get_option( 'siteurl' ) );
				wp_clear_auth_cookie();
			}
			
		}

		/**
		 * Changes user id of given user
		 *
		 * Changes the WordPress user id of the user given
		 *
		 * @param $username sting the username to change
		 * @return bool success or failure
		 *
		 **/
		function changeuserid() {
			global $wpdb;

			$user = get_user_by( 'id', '1' );
			if ( $user === false ) {
				return false;
			}

			$wpdb->query( "DELETE FROM `" . $wpdb->users . "` WHERE ID = 1;" );

			$wpdb->insert(
				$wpdb->users,
				array(
					'user_login'			=> $user->user_login,
					'user_pass'				=> $user->user_pass,
					'user_nicename'			=> $user->user_nicename,
					'user_email'			=> $user->user_email,
					'user_url'				=> $user->user_url,
					'user_registered'		=> $user->user_registered,
					'user_activation_key'	=> $user->user_activation_key,
					'user_status'			=> $user->user_status,
					'display_name'			=> $user->display_name
				)
			);
			
			$newUser = $wpdb->insert_id;
			$wpdb->query( "UPDATE `" . $wpdb->posts . "` SET post_author = '" . $newUser . "' WHERE post_author = 1;" );
			$wpdb->query( "UPDATE `" . $wpdb->usermeta . "` SET user_id = '" . $newUser . "' WHERE user_id = 1;" );
			$wpdb->query( "UPDATE `" . $wpdb->comments . "` SET user_id = '" . $newUser . "' WHERE user_id = 1;" );
			$wpdb->query( "UPDATE `" . $wpdb->links . "` SET link_owner = '" . $newUser . "' WHERE link_owner = 1;" );

			return true;

		}

		/**
		 * Determine if one-click protection has been activated
		 *
		 * @return bool success or failure
		 *
		 **/
		function checkoneclick() {

			global $bwpsoptions, $bwpsmemlimit;
			
			if ( $bwpsoptions['id_fileenabled'] == 0 &&  $bwpsmemlimit >= 128 ) {
				$idfilecheck = 0;
			} else {
				$idfilecheck = 1;
			}
			
			if ( $bwpsoptions['ll_enabled'] == 1 && $bwpsoptions['id_enabled'] == 1 && $bwpsoptions['st_generator'] == 1 && $bwpsoptions['st_manifest'] == 1 && $bwpsoptions['st_themenot'] == 1 && $bwpsoptions['st_pluginnot'] == 1 && $bwpsoptions['st_corenot'] == 1 && $bwpsoptions['st_enablepassword'] == 1 && $bwpsoptions['st_loginerror'] == 1 && $idfilecheck == 1 ) {
				return true;
			} else {
				return false;
			}

		}
		
		/**
		 * Deletes BWPS options from .htaccess
		 *
		 * Deletes all possible BWPS options from .htaccess and cleans for rewrite
		 *
		 * @return int -1 for failure, 1 for success
		 *
		 **/
		function deletehtaccess( $section = 'Better WP Security' ) {
		
			global $bwpsoptions;
				
			$htaccess = ABSPATH . '.htaccess';
			
			@ini_set( 'auto_detect_line_endings', true );
			
			if ( ! file_exists( $htaccess ) ) {
				$ht = @fopen( $htaccess, 'a+' );
				@fclose( $ht );
			}
						
			$markerdata = explode( PHP_EOL, implode( '', file( $htaccess ) ) ); //parse each line of file into array
		
			if ( $markerdata ) { //as long as there are lines in the file
					
				$state = true;
						
				if ( ! $f = @fopen( $htaccess, 'w+' ) ) {
							
					@chmod( $htaccess, 0644 );
					
					if ( ! $f = @fopen( $htaccess, 'w+' ) ) {
								
						return -1;
								
					}
							
				}
						
				foreach ( $markerdata as $n => $markerline ) { //for each line in the file
						
					if ( strpos( $markerline, '# BEGIN ' . $section ) !== false ) { //if we're at the beginning of the section
						$state = false;
					}
							
					if ( $state == true ) { //as long as we're not in the section keep writing

						fwrite( $f, trim( $markerline ) . PHP_EOL );
						
					}
							
					if ( strpos( $markerline, '# END ' . $section ) !== false ) { //see if we're at the end of the section
						$state = true;
					}
							
				}
						
				@fclose( $f );
				
				if ( $bwpsoptions['st_fileperm'] == 1 ) {
					@chmod( $htaccess, 0444 );
				}
						
				return 1;
						
			}
				
			return 1; //nothing to write
					
		}
		
		/**
		 * Deletes BWPS options from wp-config
		 *
		 * Deletes all possible BWPS options from wp-config and cleans for rewrite
		 *
		 * @return int -1 for failure, 1 for success
		 *
		 **/
		function deletewpconfig() {
		
			global $bwpsoptions;
		
			$configfile = $this->getConfig();
			
			@ini_set( 'auto_detect_line_endings', true );
						
			$lines = explode( PHP_EOL, implode( '', file( $configfile ) ) );
			
			if ( isset( $lines ) ) { //as long as there are lines in the file
						
				$state = true;
								
				if ( ! $f = @fopen( $configfile, 'w+' ) ) {
							
					@chmod( $configfile, 0644 );
					
					if ( ! $f = @fopen( $configfile, 'w+' ) ) {
								
						return -1;
								
					}
							
				}
							
				foreach ( $lines as $line ) { //for each line in the file
											
					if ( ! strstr( $line, 'BWPS_FILECHECK' ) && ! strstr( $line, 'BWPS_AWAY_MODE' ) && ! strstr( $line, 'DISALLOW_FILE_EDIT' ) && ! strstr( $line, 'FORCE_SSL_LOGIN' ) && ! strstr( $line, 'FORCE_SSL_ADMIN' ) ) {
						
						fwrite( $f, trim( $line ) . PHP_EOL );
						
					}
														
				}
							
				@fclose( $f );
				
				if ( $bwpsoptions['st_fileperm'] == 1 ) {
					@chmod( $configfile, 0444 );
				}
							
				return 1;
							
			}
					
			return 1; //nothing to write
				
		}
				
		/**
		 * Gets location of wp-config.php
		 *
		 * Finds and returns path to wp-config.php
		 *
		 * @return string path to wp-config.php
		 *
		 **/
		function getConfig() {
		
			if ( file_exists( trailingslashit( ABSPATH ) . 'wp-config.php' ) ) {
			
				return trailingslashit( ABSPATH ) . 'wp-config.php';
				
			} else {
			
				return trailingslashit( dirname( ABSPATH ) ) . 'wp-config.php';
				
			}
			
		}
		
		/**
		 * Generates rewrite rules
		 *
		 * Generates rewrite rules for use in Apache or NGINX
		 *
		 * @return string|boolean Rewrite rules or false if unsupported server
		 *
		 **/
		function getrules() {
		
			global $bwpsoptions;
		
			@ini_set( 'auto_detect_line_endings', true );
		
			//figure out what server they're using
			if ( strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'apache' ) ) {
			
				$bwpsserver = 'apache';
				
			} else if ( strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'nginx' ) ) {
			
				$bwpsserver = 'nginx';
				
			} else if ( strstr( strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) ), 'litespeed' ) ) {
			
				$bwpsserver = 'litespeed';
				
			} else { //unsupported server
			
				return false;
			
			}
			
			$rules = '';
			
			//remove directory indexing
			if ( $bwpsoptions['st_ht_browsing'] == 1 ) {
			
				if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {
				
					$rules .= "Options -Indexes" . PHP_EOL . PHP_EOL;
				
				}
				
			}
			
			//ban hosts
			
			if ( $bwpsoptions['bu_blacklist'] == 1 ) {
			
				if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {
				
					$blacklist = file_get_contents( plugin_dir_path( __FILE__ ) . 'hackrepair-apache.inc' );
					
				} else {
					
					$blacklist = file_get_contents( plugin_dir_path( __FILE__ ) . 'hackrepair-nginx.inc' );
					
				}
				
				$rules .= $blacklist . PHP_EOL;
				
				
				
			}
			
			
			if ( $bwpsoptions['bu_enabled'] == 1 ) {
			
				$hosts = explode( PHP_EOL, $bwpsoptions['bu_banlist'] );
				
				if ( ! empty( $hosts ) && ! ( sizeof( $hosts ) == 1 && trim( $hosts[0] ) == ''  ) ) {
				
					if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {
					
						$rules .= 	"Order Allow,Deny" . PHP_EOL .
									"Deny from env=DenyAccess" . PHP_EOL .
									"Allow from all" . PHP_EOL;
						
						
					}
					
					$phosts = array();

					foreach ( $hosts as $host ) {

						$host = trim( $host );

						if ( ! in_array( $host, $phosts ) ) {

							if ( strstr( $host, '*' ) ) {
							
								$parts = array_reverse ( explode( '.', $host ) );

								$netmask = 32;

								foreach ( $parts as $part ) {

									if ( strstr( trim( $part ), '*' ) ) {

									$netmask = $netmask - 8;

									}

								}

								$dhost = trim( str_replace('*', '0', implode( '.', array_reverse( $parts ) ) ) . '/' . $netmask );

								
								if ( strlen( $dhost ) > 4 ) { // what's this check for? If strstr( $host, '*' ) is true on line 331 then you will never have "...." in this var. Especially not with /$netmask attached. - Maybe this is obsolete/needs to be updated!

									if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {

										$dhost = trim( str_replace('*', '[0-9]+', implode( '\\.', array_reverse( $parts ) ) ) ); //re-define $dhost to match required output for SetEnvIf-RegEX
									
										
										$trule = "SetEnvIF REMOTE_ADDR \"^" . $dhost . "$\" DenyAccess" . PHP_EOL; //Ban IP
										
										if ( trim( $trule ) != 'SetEnvIF REMOTE_ADDR \"^$\" DenyAccess' ) { //whatever this test was used for
								
											$rules .= $trule;
											$rules .= "SetEnvIF X-FORWARDED-FOR \"^" . $dhost . "$\" DenyAccess" . PHP_EOL; //Ban IP from Proxy-User
											$rules .= "SetEnvIF X-CLUSTER-CLIENT-IP \"^" . $dhost . "$\" DenyAccess" . PHP_EOL; //Ban IP for Cluster/Cloud-hosted WP-Installs
											
										}
									
									} else {

										$rules .= "\tdeny " . $dhost . ';' . PHP_EOL;
								
									}
									
								}
							
							} else {
							
								$dhost = trim( $host );

								if ( strlen( $dhost ) > 4 ) {
								
									if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {

										$dhost = str_replace('.', '\\.', $dhost); //re-define $dhost to match required output for SetEnvIf-RegEX
										$rules .= "SetEnvIF REMOTE_ADDR \"^" . $dhost . "$\" DenyAccess" . PHP_EOL; //Ban IP
										$rules .= "SetEnvIF X-FORWARDED-FOR \"^" . $dhost . "$\" DenyAccess" . PHP_EOL; //Ban IP from Proxy-User
										$rules .= "SetEnvIF X-CLUSTER-CLIENT-IP \"^" . $dhost . "$\" DenyAccess" . PHP_EOL; //Ban IP for Cluster/Cloud-hosted WP-Installs
									
									} else {
								
										$rules .= "\tdeny " . $dhost. ";" . PHP_EOL;
								
									}
									
								}
							
							}	

						}

						$phosts[] = $host;
					
					}
				
				}
			
			}
			
			//lockdown files
			if ( $bwpsoptions['st_ht_files'] == 1 ) {
			
				if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {
				
					$rules .= 
						"<files .htaccess>" . PHP_EOL .
							"Order allow,deny" .  PHP_EOL .
							"Deny from all" . PHP_EOL .
						"</files>" . PHP_EOL . PHP_EOL .
						"<files readme.html>" . PHP_EOL .
							"Order allow,deny" . PHP_EOL .
							"Deny from all" . PHP_EOL .
						"</files>" . PHP_EOL . PHP_EOL .
						"<files readme.txt>" . PHP_EOL .
							"Order allow,deny" . PHP_EOL .
							"Deny from all" . PHP_EOL .
						"</files>" . PHP_EOL . PHP_EOL .
						"<files install.php>" . PHP_EOL .
							"Order allow,deny" . PHP_EOL .
							"Deny from all" . PHP_EOL .
						"</files>" . PHP_EOL . PHP_EOL .
						"<files wp-config.php>" . PHP_EOL .
							"Order allow,deny" . PHP_EOL .
							"Deny from all" . PHP_EOL .
						"</files>" . PHP_EOL . PHP_EOL;
					
				} else {
				
					$rules .= 
						"\tlocation ~ /\.ht { deny all; }" . PHP_EOL .
						"\tlocation ~ wp-config.php { deny all; }" . PHP_EOL .
						"\tlocation ~ readme.html { deny all; }" . PHP_EOL .
						"\tlocation ~ readme.txt { deny all; }" . PHP_EOL .
						"\tlocation ~ /install.php { deny all; }" . PHP_EOL;
				}
				
			}
			
			//start mod_rewrite rules
			if ( $bwpsoptions['st_ht_request'] == 1 || $bwpsoptions['st_comment'] == 1 || $bwpsoptions['st_ht_query'] == 1 || $bwpsoptions['hb_enabled'] == 1 || ( $bwpsoptions['bu_enabled'] == 1 && strlen(  $bwpsoptions['bu_banagent'] ) > 0 ) ) {
			
				if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {
				
					$rules .= "<IfModule mod_rewrite.c>" . PHP_EOL .
						"RewriteEngine On" . PHP_EOL . PHP_EOL;
				
				} else {
				
					$rules .= 
						"\tset \$susquery 0;" . PHP_EOL .
						"\tset \$rule_2 0;" . PHP_EOL .
						"\tset \$rule_3 0;" . PHP_EOL;
				
				}
			
			}
			
			//ban hosts and agents
			if ( $bwpsoptions['bu_enabled'] == 1 && strlen( $bwpsoptions['bu_banagent'] ) > 0 ) {
				
				$agents = explode( PHP_EOL, $bwpsoptions['bu_banagent'] );
				
				if ( ! empty( $agents ) && ! ( sizeof( $agents ) == 1 && trim( $agents[0] ) == ''  ) ) {
				
					if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {
					
						$count = 1;
				
						foreach ( $agents as $agent ) {
							
							$rules .= "RewriteCond %{HTTP_USER_AGENT} ^" . trim( $agent );
							
							if ( $count < sizeof( $agents ) ) {
							
								$rules .= " [NC,OR]" . PHP_EOL;
								$count++;
							
							} else {
							
								$rules .= " [NC]" . PHP_EOL;
							
							}
							
						}
					
						$rules .= "RewriteRule ^(.*)$ - [F,L]" . PHP_EOL . PHP_EOL;
						
					} else {
					
						$count = 1;
						$alist = '';
						
						foreach ( $agents as $agent ) {
									
							$alist .= trim( $agent );
									
							if ( $count < sizeof( $agents ) ) {
									
								$alist .= '|';
								$count++;
									
							}
									
						}
							
						$rules .= 
							"\tif (\$http_user_agent ~* " . $alist . ") { return 403; }" . PHP_EOL;
					}
				
				}
			
			}
			
			if ( $bwpsoptions['st_ht_files'] == 1 ) {
			
				if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {
				
					$rules .= "RewriteRule ^wp-admin/includes/ - [F,L]" . PHP_EOL .
						"RewriteRule !^wp-includes/ - [S=3]" . PHP_EOL .
						"RewriteCond %{SCRIPT_FILENAME} !^(.*)wp-includes/ms-files.php" . PHP_EOL .
						"RewriteRule ^wp-includes/[^/]+\.php$ - [F,L]" . PHP_EOL .
						"RewriteRule ^wp-includes/js/tinymce/langs/.+\.php - [F,L]" . PHP_EOL .
						"RewriteRule ^wp-includes/theme-compat/ - [F,L]" . PHP_EOL . PHP_EOL;
					
				} else {
				
					$rules .= 
						"\trewrite ^wp-includes/(.*).php /not_found last;" . PHP_EOL .
						"\trewrite ^/wp-admin/includes(.*)$ /not_found last;" . PHP_EOL;
				
				}
				
			}
			
			if ( $bwpsoptions['st_ht_request'] == 1 ) {
			
				if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {
				
					$rules .= "RewriteCond %{REQUEST_METHOD} ^(TRACE|DELETE|TRACK) [NC]" . PHP_EOL .
						"RewriteRule ^(.*)$ - [F,L]" . PHP_EOL . PHP_EOL;
				
				} else {
				
					$rules .= 
					"\tif (\$request_method ~* \"^(TRACE|DELETE|TRACK)\"){ return 403; }" . PHP_EOL;
				
				}
				
			}
			
			if ( $bwpsoptions['st_comment'] == 1 ) {
			
				if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {
				
					$rules .= "RewriteCond %{REQUEST_METHOD} POST" . PHP_EOL .
						"RewriteCond %{REQUEST_URI} ^(.*)wp-comments-post\.php*" . PHP_EOL .
						"RewriteCond %{HTTP_REFERER} !^" . $this->topdomain( get_option( 'siteurl' ) ) . ".* " . PHP_EOL .
						"RewriteCond %{HTTP_REFERER} !^http://jetpack\.wordpress\.com/jetpack-comment/ [OR]" . PHP_EOL .
						"RewriteCond %{HTTP_USER_AGENT} ^$" . PHP_EOL . 
						"RewriteRule ^(.*)$ - [F,L]" . PHP_EOL . PHP_EOL;
				
				} else {

					$rules .=
					"\tlocation /wp-comments-post.php {" . PHP_EOL .
  					"\t\tvalid_referers jetpack.wordpress.com/jetpack-comment/ " . $this->topdomain( get_option( 'siteurl' ), false ) . ";" . PHP_EOL .
  					"\t\tset \$rule_0 0;" . PHP_EOL .
					"\t\tif (\$request_method ~ \"POST\"){ set \$rule_0 1\$rule_0; }" . PHP_EOL .
 					"\t\tif (\$invalid_referer) { set \$rule_0 2\$rule_0; }" . PHP_EOL .
					"\t\tif (\$http_user_agent ~ \"^$\"){ set \$rule_0 3\$rule_0; }" . PHP_EOL .
					"\t\tif (\$rule_0 = \"3210\") { return 403; }" . PHP_EOL .
					"\t}";
				
				}
				
			}
			
			//filter suspicious queries
			if ( $bwpsoptions['st_ht_query'] == 1 ) {
			
				if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {
				
					$rules .= "RewriteCond %{QUERY_STRING} \.\.\/ [NC,OR]" . PHP_EOL .
						"RewriteCond %{QUERY_STRING} ^.*\.(bash|git|hg|log|svn|swp|cvs) [NC,OR]" . PHP_EOL .
						"RewriteCond %{QUERY_STRING} etc/passwd [NC,OR]" . PHP_EOL .
						"RewriteCond %{QUERY_STRING} boot\.ini [NC,OR]" . PHP_EOL .
						"RewriteCond %{QUERY_STRING} ftp\:  [NC,OR]" . PHP_EOL .
						"RewriteCond %{QUERY_STRING} http\:  [NC,OR]" . PHP_EOL .
						"RewriteCond %{QUERY_STRING} https\:  [NC,OR]" . PHP_EOL .
						"RewriteCond %{QUERY_STRING} (\<|%3C).*script.*(\>|%3E) [NC,OR]" . PHP_EOL .
						"RewriteCond %{QUERY_STRING} mosConfig_[a-zA-Z_]{1,21}(=|%3D) [NC,OR]" . PHP_EOL . 
						"RewriteCond %{QUERY_STRING} base64_encode.*\(.*\) [NC,OR]" . PHP_EOL .
						"RewriteCond %{QUERY_STRING} ^.*(\[|\]|\(|\)|<|>|ê|\"|;|\?|\*|=$).* [NC,OR]" . PHP_EOL .
						"RewriteCond %{QUERY_STRING} ^.*(&#x22;|&#x27;|&#x3C;|&#x3E;|&#x5C;|&#x7B;|&#x7C;).* [NC,OR]" . PHP_EOL .
						"RewriteCond %{QUERY_STRING} ^.*(%24&x).* [NC,OR]" .  PHP_EOL .
						"RewriteCond %{QUERY_STRING} ^.*(%0|%A|%B|%C|%D|%E|%F|127\.0).* [NC,OR]" . PHP_EOL .
						"RewriteCond %{QUERY_STRING} ^.*(globals|encode|localhost|loopback).* [NC,OR]" . PHP_EOL .
						"RewriteCond %{QUERY_STRING} ^.*(request|select|concat|insert|union|declare).* [NC]" . PHP_EOL .
						"RewriteCond %{QUERY_STRING} !^loggedout=true" . PHP_EOL .
						"RewriteCond %{QUERY_STRING} !^action=rp" . PHP_EOL .
						"RewriteCond %{HTTP_COOKIE} !^.*wordpress_logged_in_.*$" . PHP_EOL .
						"RewriteCond %{HTTP_REFERER} !^http://maps\.googleapis\.com(.*)$" . PHP_EOL .
						"RewriteRule ^(.*)$ - [F,L]" . PHP_EOL . PHP_EOL;
				
				} else {
				
					$rules .= 
					
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
						"\tif (\$args ~* \"(%0|%A|%B|%C|%D|%E|%F|127.0)\") { set \$susquery 1; }" . PHP_EOL .
						"\tif (\$args ~* \"(globals|encode|localhost|loopback)\") { set \$susquery 1; }" .PHP_EOL .
						"\tif (\$args ~* \"(request|select|insert|concat|union|declare)\") { set \$susquery 1; }" . PHP_EOL;
				
				}
				
			}
			
			if ( $bwpsserver == 'nginx' ) {
			
				$rules .= 
					"\tif (\$http_cookie !~* \"wordpress_logged_in_\" ) {" . PHP_EOL .
					"\t\tset \$susquery 2\$susquery;" . PHP_EOL .
					"\t\tset \$rule_2 1;" . PHP_EOL .
					"\t\tset \$rule_3 1;" . PHP_EOL .
					"\t}" . PHP_EOL . 
					"\tif (\$args !~ \"^loggedout=true\") { set \$susquery 3\$susquery; }" . PHP_EOL;
					"\tif (\$args !~ \"^action=rp\") { set \$susquery 4\$susquery; }" . PHP_EOL;
			
			}
			
			if ( $bwpsoptions['st_ht_query'] == 1 ) {
			
				if ( $bwpsserver == 'nginx' ) {
			
					$rules .= 
						"\tif (\$susquery = 4321) { return 403; }" . PHP_EOL;
						
				}
				
			}
			
			//hide backend rules	
			if ( $bwpsoptions['hb_enabled'] == 1 ) {
					
				//get the slugs
				$login = $bwpsoptions['hb_login'];
				$admin = $bwpsoptions['hb_admin'];
				$register = $bwpsoptions['hb_register'];
							
				//generate the key
				$key = $bwpsoptions['hb_key'];
					
				//get the domain without subdomain
				$reDomain = $this->topdomain( get_option( 'siteurl' ) );
				
				$siteurl = explode( '/', get_option( 'siteurl' ) );

				if ( isset ( $siteurl[3] ) ) {

					$dir = '/' . $siteurl[3] . '/';
       
				} else {

					$dir = '/';

				}
			
				//hide wordpress backend
				if ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) {
					
					$rules .= "RewriteRule ^" . $login . "/?$ " . $dir . "wp-login.php?" . $key . " [R,L]" . PHP_EOL . PHP_EOL .
						"RewriteCond %{HTTP_COOKIE} !^.*wordpress_logged_in_.*$" . PHP_EOL .
						"RewriteRule ^" . $admin . "/?$ " . $dir . "wp-login.php?" . $key . "&redirect_to=" . $dir . "wp-admin/ [R,L]" . PHP_EOL . PHP_EOL .
						"RewriteRule ^" . $admin . "/?$ " . $dir . "wp-admin/?" . $key . " [R,L]" . PHP_EOL . PHP_EOL .
						"RewriteRule ^" . $register . "/?$ " . $dir . "wp-login.php?" . $key . "&action=register [R,L]" . PHP_EOL . PHP_EOL .
						"RewriteCond %{SCRIPT_FILENAME} !^(.*)admin-ajax\.php" . PHP_EOL . 
						"RewriteCond %{HTTP_REFERER} !^" . $reDomain . $dir . "wp-admin" . PHP_EOL .
						"RewriteCond %{HTTP_REFERER} !^" . $reDomain . $dir . "wp-login\.php" . PHP_EOL .
						"RewriteCond %{HTTP_REFERER} !^" . $reDomain . $dir . $login . PHP_EOL .
						"RewriteCond %{HTTP_REFERER} !^" . $reDomain . $dir . $admin . PHP_EOL .
						"RewriteCond %{HTTP_REFERER} !^" . $reDomain . $dir . $register . PHP_EOL .
						"RewriteCond %{QUERY_STRING} !^" . $key . PHP_EOL .
						"RewriteCond %{QUERY_STRING} !^action=logout" . PHP_EOL .
						"RewriteCond %{QUERY_STRING} !^action=rp" . PHP_EOL .
						"RewriteCond %{QUERY_STRING} !^action=register" . PHP_EOL .
						"RewriteCond %{QUERY_STRING} !^action=postpass" . PHP_EOL .
						"RewriteCond %{HTTP_COOKIE} !^.*wordpress_logged_in_.*$" . PHP_EOL .
						"RewriteRule ^.*wp-admin/?|^.*wp-login\.php " . $dir . "not_found [R,L]" . PHP_EOL . PHP_EOL .
						"RewriteCond %{QUERY_STRING} ^loggedout=true" . PHP_EOL .
						"RewriteRule ^.*$ " . $dir . "wp-login.php?" . $key . " [R,L]" . PHP_EOL;
							
				} else {
					
					$rules .= 
						"\trewrite ^" . $dir . $login . "/?$ " . $dir . "wp-login.php?" . $key . " redirect;" . PHP_EOL .
						"\tif (\$rule_2 = 1) { rewrite ^" . $dir . $admin . "/?$ " . $dir . "wp-login.php?" . $key . "&redirect_to=/wp-admin/ redirect; }" . PHP_EOL .
						"\tif (\$rule_2 = 0) { rewrite ^" . $dir . $admin . "/?$ " . $dir . "wp-admin/?" . $key . " redirect; }" . PHP_EOL .
						"\trewrite ^" . $dir . $register . "/?$ " . $dir . "wp-login.php?" . $key . "&action=register redirect;" . PHP_EOL .
						"\tif (\$uri !~ \"^(.*)admin-ajax.php\") { set \$rule_3 2\$rule_3; }" . PHP_EOL .
						"\tif (\$http_referer !~* wp-admin ) { set \$rule_3 3\$rule_3; }" . PHP_EOL .
						"\tif (\$http_referer !~* wp-login.php ) { set \$rule_3 4\$rule_3; }" . PHP_EOL .
						"\tif (\$http_referer !~* " . $login . " ) { set \$rule_3 5\$rule_3; }" . PHP_EOL .
						"\tif (\$http_referer !~* " . $admin . " ) { set \$rule_3 6\$rule_3; }" . PHP_EOL .
						"\tif (\$http_referer !~* " . $register . " ) { set \$rule_3 7\$rule_3; }" . PHP_EOL .
						"\tif (\$args !~ \"^action=logout\") { set \$rule_3 8\$rule_3; }" . PHP_EOL .
						"\tif (\$args !~ \"^" . $key . "\") { set \$rule_3 9\$rule_3; }" . PHP_EOL .
						"\tif (\$args !~ \"^action=rp\") { set \$rule_3 0\$rule_3; }" . PHP_EOL .
						"\tif (\$args !~ \"^action=register\") { set \$rule_3 a\$rule_3; }" . PHP_EOL .
						"\tif (\$args !~ \"^action=postpass\") { set \$rule_3 b\$rule_3; }" . PHP_EOL .
						"\tif (\$rule_3 = ba0987654321) {" . PHP_EOL .
						"\t\trewrite ^(.*/)?wp-login.php " . $dir . "not_found redirect;" . PHP_EOL .
						"\t\trewrite ^" . $dir . "wp-admin(.*)$ " . $dir . "not_found redirect;" . PHP_EOL .
						"\t}" . PHP_EOL;
				
				}
	
			}
			
			//close mod_rewrite
			if ( $bwpsoptions['st_ht_request'] == 1 || $bwpsoptions['st_comment'] == 1 || $bwpsoptions['st_ht_query'] == 1 || $bwpsoptions['hb_enabled'] == 1 || ( $bwpsoptions['bu_enabled'] == 1 && strlen(  $bwpsoptions['bu_banagent'] ) > 0 ) ) {
			
				if ( ( $bwpsserver == 'apache' || $bwpsserver == 'litespeed' ) ) {
				
					$rules .= "</IfModule>" . PHP_EOL;
				
				}
			
			}
			
			//add markers if we have rules
			if ( $rules != '' ) {
				$rules = "# BEGIN Better WP Security" . PHP_EOL . $rules . "# END Better WP Security" . PHP_EOL;
			}
			
			return implode( PHP_EOL, array_diff( explode( PHP_EOL, $rules ), array( 'Deny from ', 'Deny from' ) ) );
		
		}
		
		/**
		 * Generates wp-confing rules
		 *
		 * Generates wp-confing rules
		 *
		 * @return string wp-confing rules
		 *
		 **/
		function getwpcontent() {
		
			global $bwpsoptions;
			
			@ini_set( 'auto_detect_line_endings', true );
			
			if ( $bwpsoptions['id_fileenabled'] == 1 || $bwpsoptions['am_enabled'] == 1 || $bwpsoptions['st_fileedit'] == 1 || $bwpsoptions['ssl_forcelogin'] == 1 || $bwpsoptions['ssl_forceadmin'] == 1 ) {
			
				$rules = "//BEGIN Better WP Security" . PHP_EOL;
				
				if ( $bwpsoptions['st_fileedit'] == 1 ) {
				
					$rules .= "define( 'DISALLOW_FILE_EDIT', true );" . PHP_EOL;
				
				}
				
				if ( $bwpsoptions['ssl_forcelogin'] == 1 ) {
				
					$rules .= "define( 'FORCE_SSL_LOGIN', true );" . PHP_EOL;
				
				}
				
				if ( $bwpsoptions['ssl_forceadmin'] == 1 ) {
				
					$rules .= "define( 'FORCE_SSL_ADMIN', true );" . PHP_EOL;
				
				}

				if ( $bwpsoptions['am_enabled'] == 1 ) {
				
					$rules .= "define( 'BWPS_AWAY_MODE', true );" . PHP_EOL;
				
				}

				if ( $bwpsoptions['id_fileenabled'] == 1 ) {
				
					$rules .= "define( 'BWPS_FILECHECK', true );" . PHP_EOL;
				
				}
				
				$rules .= "//END Better WP Security" . PHP_EOL;
			
			} else {
			
				$rules = '';
				
			}
			
			return $rules;
		
		}
		
		/**
		 * Generates secret key
		 *
		 * Generates secret key for hide backend function
		 *
		 * @return string key
		 *
		 **/
		function hidebe_genKey() {
		
			$size = 20; //length of key
			$chars = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"; //available characters
			srand( ( double ) microtime() * 1000000 ); //random seed
			$pass = '' ;
				
			for ( $i = 0; $i <= $size; $i++ ) {
			
				$num = rand() % 33;
				$tmp = substr( $chars, $num, 1 );
				$pass = $pass . $tmp;
				
			}
			
			return $pass;	
			
		}

		/**
		 * Download the 404 log in .csv format
		 * 
		 **/
		function log404csv() {

			global $wpdb;

			@header( 'Content-type: text/x-csv' );
			@header( 'Content-Transfer-Encoding: binary' );
			@header( 'Content-Disposition: attachment; filename=404errors.csv' );
			@header( 'Cache-Control: no-cache, must-revalidate' ); 
			@header( 'Expires: Thu, 22 Jun 1978 00:28:00 GMT' );

			@ini_set( 'auto_detect_line_endings', true );

			$headers = array(
				'url',
				'time',
				'host',
				'referrer'
			);

			$errors = $wpdb->get_results( "SELECT url, timestamp, host, referrer, url FROM `" . $wpdb->base_prefix . "bwps_log` WHERE `type` = 2;", ARRAY_A );

			array_unshift( $errors, $headers );

			foreach ( $errors as $error ) {

				foreach ( $error as $attr ) {

					echo $attr . ',';

				}

				echo PHP_EOL;

			}
			
			exit;

		}
				
		/**
		 * Return primary domain from given url
		 *
		 * Returns primary domsin name (without subdomains) of given URL
		 *
		 * @param string $address address to filter
		 * @param boolean $apache[true] does this require an apache style wildcard
		 * @return string domain name
		 *
		 **/		
		function topdomain( $address, $apache = true ) {
		
			preg_match( "/^(http:\/\/)?([^\/]+)/i", $address, $matches );
			$host = $matches[2];
			preg_match( "/[^\.\/]+\.[^\.\/]+$/", $host, $matches );
			if ( $apache == true ) {
				$wc = '(.*)';
			} else {
				$wc = '*.';
			}
			
			return $wc . $matches[0] ;;
			
		}
		
		/**
		 * Checks if user exists
		 *
		 * Checks to see if WordPress user with given username or user id exists
		 *
		 * @param string $username login username or user id of user to check
		 * @return bool true if user exists otherwise false
		 *
		 **/
		function user_exists( $username ) {
		
			global $wpdb;
			
			//return false if username is null
			if ( $username == '' ) {
				return false;
			}
			
			//queary the user table to see if the user is there
			$user = $wpdb->get_var( $wpdb->prepare( "SELECT user_login FROM `" . $wpdb->users . "` WHERE user_login = '%s';", sanitize_text_field( $username ) ) );
			$userid = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM `" . $wpdb->users . "` WHERE ID='%s';", sanitize_text_field( $username ) ) );
			
			if ( $user == $username || $userid == $username ) {
				return true;
			} else {
				return false;
			}
			
		}	
		
		/**
		 * Writes .htaccess options
		 *
		 * Writes various Better WP Security options to the .htaccess file
		 *
		 * @return int Write results -1 for error, 1 for success
		 *
		 **/
		function writehtaccess() {
		
			global $bwpsoptions;
			
			//clean up old rules first
			if ( $this->deletehtaccess() == -1 ) {
			
				return -1; //we can't write to the file
			
			}
			
			$htaccess = ABSPATH . '.htaccess';
			
			//get the subdirectory if it is installed in one
			$siteurl = explode( '/', get_option( 'siteurl' ) );
			
			if ( isset ( $siteurl[3] ) ) {
			
				$dir = '/' . $siteurl[3] . '/';
				
			} else {
			
				$dir = '/';
			
			}		
						
			if ( ! $f = @fopen( $htaccess, 'a+' ) ) {
						
				@chmod( $htaccess, 0644 );
				
				if ( ! $f = @fopen( $htaccess, 'a+' ) ) {
							
					return -1;
							
				}
						
			}
			
			@ini_set( 'auto_detect_line_endings', true );
			
			$ht = explode( PHP_EOL, implode( '', file( $htaccess ) ) ); //parse each line of file into array
			
			$rules = $this->getrules();	
			
			$rulesarray = explode( PHP_EOL, $rules );
			
			$contents = array_merge( $rulesarray, $ht );
			 
			if ( ! $f = @fopen( $htaccess, 'w+' ) ) {
				
				return -1; //we can't write to the file
				
			}
			
			$blank = false;
			
			//write each line to file
			foreach ( $contents as $insertline ) {
			
				if ( trim( $insertline ) == '' ) {
					if ( $blank == false ) {
					
						fwrite( $f, PHP_EOL . trim( $insertline ) );
						
					}
					
					$blank = true;
				
				} else {
					
					$blank = false;
					
					fwrite( $f, PHP_EOL . trim( $insertline ) );
					
				}
				
			}
				
			@fclose( $f );
			
			if ( $bwpsoptions['st_fileperm'] == 1 ) {
				@chmod( $htaccess, 0444 );
			}
			
			return 1; //success
		
		}
		
		/**
		 * Writes wp-config.php options
		 *
		 * Writes various Better WP Security options to the wp-config.php file
		 *
		 * @return int Write results -1 for error, 1 for success
		 *
		 **/
		function writewpconfig() {
		
			global $bwpsoptions;
		
			//clear the old rules first
			if ( $this->deletewpconfig() == -1 ) {
			
				return -1; //we can't write to the file
			
			}
			
			$lines = '';
			
			$configfile = $this->getconfig();
			
			@ini_set( 'auto_detect_line_endings', true );
			
			$config = explode( PHP_EOL, implode( '', file( $configfile ) ) );
			
			if ( $bwpsoptions['st_fileedit'] == 1 ) {
			
				$lines .= "define( 'DISALLOW_FILE_EDIT', true );" . PHP_EOL . PHP_EOL;
			
			}
			
			if ( $bwpsoptions['ssl_forcelogin'] == 1 ) {
			
				$lines .= "define( 'FORCE_SSL_LOGIN', true );" . PHP_EOL;
			
			}
			
			if ( $bwpsoptions['ssl_forceadmin'] == 1 ) {
			
				$lines .= "define( 'FORCE_SSL_ADMIN', true );" . PHP_EOL . PHP_EOL;
			
			}

			if ( $bwpsoptions['am_enabled'] == 1 ) {
			
				$lines .= "define( 'BWPS_AWAY_MODE', true );" . PHP_EOL;
			
			}

			if ( $bwpsoptions['id_fileenabled'] == 1 ) {
				
				$lines .= "define( 'BWPS_FILECHECK', true );" . PHP_EOL;
				
			}
			
			if ( ! $f = @fopen( $configfile, 'w+' ) ) {
						
				@chmod( $configfile, 0644 );
				
				if ( ! $f = @fopen( $configfile, 'w+' ) ) {
							
					return -1;
							
				}
						
			}
			
			$blank = false;
			
			//rewrite each appropriate line
			foreach ($config as $line) {
			
				if ( strstr( $line, "<?php" ) ) {
				
					$line = $line . PHP_EOL . PHP_EOL . $lines; //paste ending 
				
				}
				
				if ( trim( $line ) == '' ) {
					if ( $blank == false ) {
					
						fwrite( $f, PHP_EOL . trim( $line ) );
						
					}
					
					$blank = true;
				
				} else {
					
					$blank = false;
									
					if ( strstr( $line, '<?php' ) ) {
						fwrite( $f, trim( $line ) );
					} else {	
						fwrite( $f, PHP_EOL . trim( $line ) );
					}
					
				}
				
			}
			
			@fclose( $f );
			
			if ( $bwpsoptions['st_fileperm'] == 1 ) {
				@chmod( $configfile, 0444 );
			}
			
			return 1; //success
		
		}
			
	}	
	
}
