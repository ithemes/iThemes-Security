<?php

if ( ! class_exists( 'Bit51_BWPS_Utilities' ) ) {

	final class Bit51_BWPS_Utilities {

		private static $instance = null; //instantiated instance of this plugin

		public
			$plugin;

		private 
			$lock_file;

		/**
		 * Loads core functionality across both admin and frontend.
		 * 
		 * @param Bit51_BWPS $plugin
		 * 
		 * @return void
		 */
		private function __construct( $plugin ) {

			global $bwps_globals;

			$this->plugin = $plugin; //Allow us to access plugin defaults throughout

			$this->lock_file = $bwps_globals['upload_dir'] . '/config.lock';

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
		 * Attempt to get a lock for atomic operations
		 * 
		 * @return bool true if lock was achieved, else false
		 */
		public function get_lock() {

			global $bwps_globals;

			if ( file_exists( $this->lock_file ) ) {

				$pid = @file_get_contents( $this->lock_file );

				if ( @posix_getsid( $pid ) !== false) {

					return true; //file is locked for writing
				
				} 

			}

			@file_put_contents( $this->lock_file, getmypid() );

			return false;

		}

		/**
		 * Release the lock
		 * 
		 * @return bool true if released, false otherwise
		 */
		public function release_lock() {

			if ( @unlink( $this->lock_file ) ) {
				return true;
			}

			return false;

		}

		/**
		 * Gets location of .htaccess
		 *
		 * Finds and returns path to .htaccess
		 *
		 * @return string path to .htaccess
		 *
		 **/
		public function get_htaccess() {
		
			return ABSPATH . '.htaccess';
			
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
		 * Start the global utilities instance
		 * 
		 * @param  [plugin_class]  $plugin       Instance of main plugin class
		 * @return Bit51_BWPS_Utilities          The instance of the Bit51_BWPS_Utilities class
		 */
		public static function start( $plugin ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $plugin );
			}

			return self::$instance;

		}

	}

}