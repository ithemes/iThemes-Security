<?php

if ( ! class_exists( 'Bit51_BWPS_WPConfig' ) ) {

	class Bit51_BWPS_WPConfig {

		private static $instance = null; //instantiated instance of this plugin

		private 
			$plugin,
			$rules;

		private function __construct( $plugin ) {

			$this->plugin = $plugin;
			$this->rules = '';

			if ( defined( 'BWPS_WRITE_CONFIG' ) ) {

				die(var_dump($this->rules));

			}

		}

		/**
		 * Gets location of wp-config.php
		 *
		 * Finds and returns path to wp-config.php
		 *
		 * @return string path to wp-config.php
		 *
		 **/
		private function get_config() {
		
			if ( file_exists( trailingslashit( ABSPATH ) . 'wp-config.php' ) ) {
			
				return trailingslashit( ABSPATH ) . 'wp-config.php';
				
			} else {
			
				return trailingslashit( dirname( ABSPATH ) ) . 'wp-config.php';
				
			}
			
		}

		/**
		 * Generate rules for wp-config.php
		 * 
		 * @return void
		 */
		public function get_rules() {

			$this->rules = apply_filters( $this->plugin->globals['plugin_hook'] . '_add_wp_config_rule', $this->rules );

		}

		public function write_config() {



		}

		/**
		 * Start the global instance
		 * 
		 * @param  [plugin_class]  $plugin       Instance of main plugin class
		 * @return bwps_Core                     The instance of the bwps_Core class
		 */
		public static function start( $plugin ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $plugin );
			}

			return self::$instance;

		}

	}

}