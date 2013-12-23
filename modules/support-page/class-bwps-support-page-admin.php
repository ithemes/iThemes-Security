<?php

if ( ! class_exists( 'BWPS_Support_Page_Admin' ) ) {

	class BWPS_Support_Page_Admin {

		private static $instance = NULL;

		private
			$core,
			$page;

		private function __construct( $core ) {

			$this->core = $core;

			add_action( 'bwps_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( 'bwps_page_top', array( $this, 'add_support_intro' ) ); //add page intro and information
			add_filter( 'bwps_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( 'bwps_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu

		}

		/**
		 * Register subpage for Away Mode
		 *
		 * @param array $available_pages array of BWPS settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $bwps_globals;

			$this->page = $available_pages[0] . '-support';

			$available_pages[] = add_submenu_page(
				'bwps',
				__( 'Support', 'better_wp_security' ),
				__( 'Support', 'better_wp_security' ),
				$bwps_globals['plugin_access_lvl'],
				$available_pages[0] . '-support',
				array( $this->core, 'render_page' )
			);

			return $available_pages;

		}

		public function add_admin_tab( $tabs ) {

			$tabs[$this->page] = __( 'Support', 'better_wp_security' );

			return $tabs;

		}

		/**
		 * Add meta boxes to primary options pages
		 *
		 */
		public function add_admin_meta_boxes() {

			add_meta_box(
				'bwps_system_info',
				__( 'System Information', 'better_wp_security' ),
				array( $this, 'metabox_normal_system' ),
				'security_page_toplevel_page_bwps-support',
				'normal',
				'core'
			);

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

			if ( $screen === 'security_page_toplevel_page_bwps-support' ) { //only display on away mode page

				$content = 'Support Information';

				echo $content;

			}

		}

		/**
		 * Start the Support module
		 *
		 * @param  Ithemes_BWPS_Core $core Instance of core plugin class
		 *
		 * @return BWPS_Support_Page_Admin                The instance of the BWPS_Support_Page_Admin class
		 */
		public static function start( $core ) {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}