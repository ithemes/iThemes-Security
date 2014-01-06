<?php

if ( ! class_exists( 'Ithemes_BWPS_Lib' ) ) {

	final class Ithemes_BWPS_Lib {

		private static $instance = NULL; //instantiated instance of this plugin

		/**
		 * Loads core functionality across both admin and frontend.
		 *
		 * @param Ithemes_BWPS $plugin
		 */
		private function __construct() {

			$this->lock_file = trailingslashit( ABSPATH ) . 'config.lock';

		}

		/**
		 * Gets location of wp-config.php
		 *
		 * Finds and returns path to wp-config.php
		 *
		 * @return string path to wp-config.php
		 *
		 **/
		public function get_config() {

			if ( file_exists( trailingslashit( ABSPATH ) . 'wp-config.php' ) ) {

				return trailingslashit( ABSPATH ) . 'wp-config.php';

			} else {

				return trailingslashit( dirname( ABSPATH ) ) . 'wp-config.php';

			}

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
		function get_domain( $address, $apache = true ) {
		
			preg_match( "/^(http:\/\/)?([^\/]+)/i", $address, $matches );

			$host = $matches[2];

			preg_match( "/[^\.\/]+\.[^\.\/]+$/", $host, $matches );

			if ( $apache == true ) {
				$wc = '(.*)';
			} else {
				$wc = '*.';
			}

			if ( ! is_array( $matches ) ) {
				return false;
			}

			// multisite domain mapping compatibility. when hide login is enabled, 
			// rewrite rules redirect valid POST requests from MAPPED_DOMAIN/wp-login.php?SECRET_KEY
			// because they aren't coming from the "top-level" domain. blog_id 1, the parent site,
			// is a completely different, unrelated domain in this configuration.
			if ( is_multisite() && function_exists( 'domain_mapping_warning' ) ) {
				return $wc;
			} elseif( isset( $matches[0] ) ) {
				return $wc . $matches[0] ;
			} else {
				return false;
			}
		}

		/**
		 * Determine whether we're on the login page or not
		 *
		 * @return bool true if is login page else false
		 */
		public function is_login_page() {

			return in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) );

		}

		/**
		 * Gets location of .htaccess
		 *
		 * Finds and returns path to .htaccess or nginx.conf if appropriate
		 *
		 * @return string path to .htaccess
		 *
		 **/
		public function get_htaccess() {

			if ( $this->get_server() === 'nginx' ) {

				return ABSPATH . 'nginx.conf';

			} else {

				return ABSPATH . '.htaccess';

			}

		}

		/**
		 * Returns the actual IP address of the user
		 *
		 * @return  String The IP address of the user
		 *
		 * */
		public function get_ip() {

			//Just get the headers if we can or else use the SERVER global
			if ( function_exists( 'apache_request_headers' ) ) {

				$headers = apache_request_headers();

			} else {

				$headers = $_SERVER;

			}

			//Get the forwarded IP if it exists
			if ( array_key_exists( 'X-Forwarded-For', $headers ) && ( filter_var( $headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) || filter_var( $headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) ) {

				$the_ip = $headers['X-Forwarded-For'];

			} else {

				$the_ip = $_SERVER['REMOTE_ADDR'];

			}

			return $the_ip;

		}

		/**
		 * Returns the server type of the plugin user.
		 *
		 * @return string|bool server type the user is using of false if undetectable.
		 */
		public function get_server() {

			$server_raw = strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) );

			//figure out what server they're using
			if ( strpos( $server_raw, 'apache' ) !== false ) {

				return 'apache';

			} elseif ( strpos( $server_raw, 'nginx' ) !== false ) {

				return 'nginx';

			} elseif ( strpos( $server_raw, 'litespeed' ) !== false ) {

				return 'litespeed';

			} else { //unsupported server

				return false;

			}

		}

		/**
		 * Determine whether the server supports SSL (shared cert not supported
		 * 
		 * @return bool true if ssl is supported or false
		 */
		public function get_ssl() {

			$timeout = 5; //timeout for the request
			$url = str_replace( 'http://', 'https://', get_bloginfo( 'url' ) );
			$site_title = trim( get_bloginfo() );

			$request = curl_init();

			curl_setopt( $request, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $request, CURLOPT_VERBOSE, 1 );
			curl_setopt( $request, CURLOPT_HEADER, 1 );
			curl_setopt( $request, CURLOPT_URL, $url );
			curl_setopt( $request, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $request, CURLOPT_CONNECTTIMEOUT, $timeout );

			$data = curl_exec( $request );	
	
			$header_size = curl_getinfo( $request, CURLINFO_HEADER_SIZE );
			$http_code = intval( curl_getinfo( $request, CURLINFO_HTTP_CODE ) );
			$body = substr( $data, $header_size );

			preg_match( '/<title>(.+)<\/title>/', $body, $matches );

			if( $http_code === 200 && isset( $matches[1] ) && trim( $matches[1] ) == $site_title ) {
				return true;
			} else {
				return false;
			}

		}

		/**
		 * Validates a list of ip addresses
		 *
		 * @param string $ip string of hosts to check
		 *
		 * @return array array of good hosts or false
		 */
		public function validates_ip_address( $ip ) {

			//validate list
			$ip = trim( filter_var( $ip, FILTER_SANITIZE_STRING ) );
			$ip_parts      = explode( '.', $ip );
			$error_handler = NULL;
			$is_ip          = 0;
			$part_count     = 1;
			$good_ip        = true;
			$found_wildcard     = false;

			foreach ( $ip_parts as $part ) {

				if ( $good_ip == true ) {

					if ( ( is_numeric( $part ) && $part <= 255 && $part >= 0 ) || $part === '*' || ( $part_count === 3 && strpos( $part, '/' ) !== false ) ) {
						$is_ip ++;
					}

					switch ( $part_count ) {

						case 1: //1st octet

							if ( $part === '*' || strpos( $part, '/' ) !== false ) {

								return false;

							}

							break;

						case 2: //2nd octet

							if ( $part === '*' ) {

								$found_wildcard = true;

							} elseif ( strpos( $part, '/' ) !== false ) {

								return false;

							}

							break;

						case 3: //3rd octet

							if ( $part !== '*' ) {

								if ( $found_wildcard === true ) {

									return false;

								}

							} elseif ( strpos( $part, '/' ) !== false ) {

								return false;

							} else {

								$found_wildcard = true;

							}

							break;

						default: //4th octet and netmask

							if ( $part !== '*' ) {

								if ( $found_wildcard == true ) {

									return false;

								} elseif ( strpos( $part, '/' ) !== false ) {

									$netmask = intval( substr( $part, ( strpos( $part, '/' ) + 1 ) ) );

									if ( ! is_numeric( $netmask ) && 1 > $netmask && 31 < $netmask ) {

										return false;

									}

								}

							}

							break;

					}

					$part_count ++;

				}

			}

			if ( ( strpos( $ip, '/' ) !== false && ip2long( trim( substr( $ip, 0, strpos( $ip, '/' ) ) ) ) === false )  || ( strpos( $ip, '/' ) === false && ip2long( trim( str_replace( '*', '0', $ip ) ) ) === false ) ) { //invalid ip

				return false;

			}

			return true; //ip is valid

		}

		/**
		 * Converts IP with * wildcards to one with a netmask instead
		 * 
		 * @param  string $ip ip to convert
		 * 
		 * @return string     the converted ip
		 */
		public function ip_wild_to_mask( $ip ) {

			$host_parts = array_reverse( explode( '.', trim( $ip ) ) );

			if ( strpos( $ip, '*' ) ) {

				$mask           = 0; //used to calculate netmask with wildcards
				$converted_host = str_replace( '*', '0', $ip );

				//convert hosts with wildcards to host with netmask and create rule lines
				foreach ( $host_parts as $part ) {

					if ( $part === '*' ) {
						$mask = $mask + 8;
					}

					//Apply a mask if we had to convert
					if ( $mask > 0 ) {
						$converted_host .= '/' . $mask;
					}

				}

				return $converted_host;

			} 

			return $ip;

		}

		/**
		 * Converts CIDR to ip range.
		 *
		 * Modified from function at http://stackoverflow.com/questions/4931721/getting-list-ips-from-cidr-notation-in-php
		 * as it was far more elegant than my own solution
		 * 
		 * @param  string $cidr cidr notation to convert
		 * @return array        range of ips returned
		 */
		public function cidr_to_range( $cidr ) {
			
			$range = array();

			if ( strpos( $cidr, '/' ) ) {

	  			$cidr = explode( '/', $cidr );

	  			$range[] = long2ip( ( ip2long( $cidr[0] ) ) & ( (-1 << ( 32 - (int) $cidr[1] ) ) ) );
	  			$range[] = long2ip( ( ip2long( $cidr[0] ) ) + pow( 2, ( 32 - (int) $cidr[1] ) ) - 1 );

	  		} else { //if not a range just return the original ip

	  			$range[] = $cidr;

	  		}

	  		return $range;

	  	}

		/**
		 * Start the global library instance
		 *
		 * @return Ithemes_BWPS_Lib          The instance of the Ithemes_BWPS_Lib class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}