<?php 

require_once( BWPS_PP . 'inc/admin/common.php' );
require_once( BWPS_PP . 'inc/admin/content.php' );
require_once( BWPS_PP . 'inc/admin/process.php' );
require_once( BWPS_PP . 'inc/admin/tables.php' );
require_once( BWPS_PP . 'inc/admin/wpcontent.php' );

if ( ! class_exists( 'bwps_admin_construct' ) ) {

	class bwps_admin_construct extends bwps_admin_common {

		/**
		 * Sets admin configuration
		 *
		 **/
		function __construct() {

			global $isIWP;
			
			//add scripts and css
			add_action( 'admin_print_scripts', array( &$this, 'config_page_scripts' ) );
			add_action( 'admin_print_styles', array( &$this, 'config_page_styles' ) );
			add_action( 'admin_print_styles', array( &$this, 'bwps_styles' ) );
	
			//add action link
			add_filter( 'plugin_action_links', array( &$this, 'add_action_link' ), 10, 2 );
	
			//add donation reminder
			add_action( 'admin_init', array( &$this, 'ask' ) );	

			//don't execute anything but SSL for InfiniteWP
			if ( $isIWP === false ) {
		
				add_action( 'admin_init', array( &$this, 'awaycheck' ) );

			}

			//Process 404 .csv file
			if ( isset( $_GET['bit51_404_csv'] ) ) {

				add_action( 'init', array( &$this, 'log404csv' ) );
				
			}
				
		}
		
		/**
		 * Register admin css styles (only for plugin admin page)
		 *
		 **/
		function bwps_styles() {
		
			//make sure we're on the appropriate page
			if ( isset( $_GET['page'] ) && strpos( $_GET['page'], $this->hook ) !== false ) {
			
				wp_enqueue_style( 'bwps-css', BWPS_PU . 'inc/admin/css/style.css' );
				
			}
			
		}
	
	}
	
}

new bwps_admin_construct();
new bwps_admin_content();
new bwps_admin_process();
