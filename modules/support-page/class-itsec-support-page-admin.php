<?php

if ( ! class_exists( 'ITSEC_Support_Page_Admin' ) ) {

	class ITSEC_Support_Page_Admin {

		private static $instance = null;

		private $core, $page;

		private function __construct( $core ) {

			$this->core = $core;

			add_action( 'itsec_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_filter( 'itsec_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( 'itsec_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu

		}

		/**
		 * Register subpage for Away Mode
		 *
		 * @param array $available_pages array of ITSEC settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $itsec_globals;

			$this->page = $available_pages[0] . '-support';

			$available_pages[] = add_submenu_page( 'itsec', __( 'Support', 'ithemes-security' ), __( 'Support', 'ithemes-security' ), $itsec_globals['plugin_access_lvl'], $available_pages[0] . '-support', array( $this->core, 'render_page' ) );

			return $available_pages;

		}

		public function add_admin_tab( $tabs ) {

			$tabs[$this->page] = __( 'Support', 'ithemes-security' );

			return $tabs;

		}

		/**
		 * Add meta boxes to primary options pages
		 *
		 */
		public function add_admin_meta_boxes() {

			add_meta_box( 'itsec_system_info_description', __( 'Description', 'ithemes-security' ), array( $this, 'add_support_intro' ), 'security_page_toplevel_page_itsec-support', 'normal', 'core' );

			add_meta_box( 'itsec_system_info', __( 'System Information', 'ithemes-security' ), array( $this, 'metabox_normal_system' ), 'security_page_toplevel_page_itsec-support', 'normal', 'core' );

		}

		/**
		 * Displays system information
		 *
		 * @return void
		 */
		public function metabox_normal_system() {

			require_once( 'content/system.php' );

		}

		/**
		 * Build and echo the away mode description
		 *
		 * @return void
		 */
		public function add_support_intro( $screen ) {

			$content = 'Support Information';

			echo $content;

		}

		/**
		 * Start the Support module
		 *
		 * @param  Ithemes_ITSEC_Core $core Instance of core plugin class
		 *
		 * @return ITSEC_Support_Page_Admin                The instance of the ITSEC_Support_Page_Admin class
		 */
		public static function start( $core ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}