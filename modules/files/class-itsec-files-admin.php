<?php

if ( ! class_exists( 'ITSEC_Files_Admin' ) ) {

	class ITSEC_Files_Admin {

		private static $instance = null;

		private
			$settings,
			$core;

		private function __construct( $core ) {

			$this->core      = $core;
			$this->settings  = get_site_option( 'itsec_files' );

			add_action( 'itsec_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) ); //enqueue scripts for admin page
			add_filter( 'itsec_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( 'itsec_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu
			add_filter( 'itsec_add_dashboard_status', array( $this, 'dashboard_status' ) ); //add information for plugin status

			//manually save options on multisite
			if ( is_multisite() ) {
				add_action( 'network_admin_edit_itsec_authentication', array( $this, 'save_network_options' ) ); //save multisite options
			}

		}

		/**
		 * Add meta boxes to primary options pages
		 *
		 * @return void
		 */
		public function add_admin_meta_boxes() {

		}

		/**
		 * Adds tab to plugin administration area
		 *
		 * @param array $tabs array of tabs
		 *
		 * @return mixed array of tabs
		 */
		public function add_admin_tab( $tabs ) {

		}

		/**
		 * Register subpage for Away Mode
		 *
		 * @param array $available_pages array of ITSEC settings pages
		 */
		public function add_sub_page( $available_pages ) {
		}

		/**
		 * Add Files Admin Javascript
		 *
		 * @return void
		 */
		public function admin_script() {

		}

		/**
		 * Sets the status in the plugin dashboard
		 *
		 * @return void
		 */
		public function dashboard_status( $statuses ) {
		}

		/**
		 * Execute admin initializations
		 *
		 * @return void
		 */
		public function initialize_admin() {
		}

		/**
		 * Sanitize and validate input
		 *
		 * @param  Array $input array of input fields
		 *
		 * @return Array         Sanitized array
		 */
		public function sanitize_module_input( $input ) {
		}

		/**
		 * Prepare and save options in network settings
		 *
		 * @return void
		 */
		public function save_network_options() {
		}

		/**
		 * Start the Files Admin Module
		 *
		 * @param Ithemes_ITSEC_Core   $core   Instance of core plugin class
		 *
		 * @return ITSEC_Files_Admin                The instance of the ITSEC_Files_Admin class
		 */
		public static function start( $core ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}