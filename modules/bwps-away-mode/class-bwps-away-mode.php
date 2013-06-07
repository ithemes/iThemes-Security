<?php

if ( ! class_exists( 'BWPS_Away_Mode' ) ) {

	class BWPS_Away_Mode {

		private static $instance = null;

		private 
			$settings,
			$core;

		private function __construct( $core ) {

			$this->core = $core;
			$this->settings = get_option( 'bwps_away_mode' );

			add_action( $this->core->plugin->globals['plugin_hook'] . '_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) );
			add_filter( $this->core->plugin->globals['plugin_hook'] . '_add_admin_sub_pages', array( $this, 'add_sub_page' ) );
			add_action( 'admin_init', array( $this, 'initialize_admin' ) );

		}

		/**
		 * Register subpage for Away Mode
		 * 
		 * @param array $available_pages array of BWPS settings pages
		 */
		function add_sub_page( $available_pages ) {

			$available_pages[] = add_submenu_page(
				$this->core->plugin->globals['plugin_hook'],
				__( 'Away Mode', 'better_wp_security' ),
				__( 'Away Mode', 'better_wp_security' ),
				$this->core->plugin->globals['plugin_access_lvl'],
				$available_pages[0] . '-away_mode',
				array( $this->core, 'render_page' )
			);

			return $available_pages;

		}

		/**
		 * Add meta boxes to primary options pages
		 * 
		 * @param array $available_pages array of available page_hooks
		 */
		function add_admin_meta_boxes( $available_pages ) {

			//add metaboxes
			add_meta_box( 
				'default_module_intro', 
				__( 'About Away Mode', 'better_wp_security' ),
				array( $this, 'metabox_normal_intro' ),
				'security_page_toplevel_page_better_wp_security-away_mode',
				'normal',
				'core'
			);

			//add metaboxes
			add_meta_box( 
				'default_module_settings', 
				__( 'Configure Away Mode', 'better_wp_security' ),
				array( $this, 'metabox_advanced_settings' ),
				'security_page_toplevel_page_better_wp_security-away_mode',
				'advanced',
				'core'
			);

		}

		/**
		 * Execute admin initializations
		 * 
		 * @return void
		 */
		function initialize_admin() {

			add_settings_section(  
				'away_mode_settings',
				__( 'Configure Away Mode', 'better_wp_security' ),
				array( $this, 'sandbox_general_options_callback' ),
				'security_page_toplevel_page_better_wp_security-away_mode'
			);

			add_settings_field(   
				'bwps_away_mode[enabled]', 
				__( 'Enable Away Mode', 'better_wp_security' ),
				array( $this, 'away_mode_enabled' ),
				'security_page_toplevel_page_better_wp_security-away_mode',
				'away_mode_settings'
			);

			add_settings_field(   
				'bwps_away_mode[type]', 
				__( 'Type of Restriction', 'better_wp_security' ),
				array( $this, 'away_mode_type' ),
				'security_page_toplevel_page_better_wp_security-away_mode',
				'away_mode_settings'
			);

			register_setting(  
				'security_page_toplevel_page_better_wp_security-away_mode',
				'bwps_away_mode'
			);

			wp_enqueue_script( 'jquery-ui-datepicker' );

		}

		/**
		 * Settings section callback
		 * 
		 * @return void
		 */
		function sandbox_general_options_callback() {}

		/**
		 * echos Enabled Field
		 * 
		 * @param  array $args field arguements
		 * @return void
		 */
		function away_mode_enabled( $args ) {

			$content = '<input type="checkbox" id="bwps_away_mode_enabled" name="bwps_away_mode[enabled]" value="1" ' . checked( 1, $this->settings['enabled'], false ) . '/>';  
			$content .= '<label for="bwps_away_mode_enabled"> '  . __( 'Check this box to enable away mode', 'better_wp_security' ) . '</label>';   

			echo $content;

		}

		/**
		 * echos type Field
		 * 
		 * @param  array $args field arguements
		 * @return void
		 */
		function away_mode_type( $args ) {

			$content = '<select name="bwps_away_mode[type]" id="bwps_away_mode_test">' . 
    		$content .= '<option value="1" ' . selected( $this->settings['type'], 1, false ) . '>Daily</option>';
    		$content .= '<option value="2" ' . selected( $this->settings['type'], 2, false ) . '>One Time</option>';
			$content .= '</select>';
			$content .= '<label for="bwps_away_mode_type"> '  . __( 'Check this box to enable away mode', 'better_wp_security' ) . '</label>';   

			echo $content;

		}

		/**
		 * Build and echo the away mode description
		 * 
		 * @return void
		 */
		public function metabox_normal_intro() {

			$content = '<p>' . __( 'As most sites are only updated at certain times of the day it is not always necessary to provide access to the WordPress dashboard 24 hours a day, 7 days a week. The options below will allow you to disable access to the WordPress Dashboard for the specified period. In addition to limiting exposure to attackers this could also be useful to disable site access based on a schedule for classroom or other reasons.', 'better_wp_security' ) . '</p>';
			
			if ( preg_match( "/^(G|H)(:| \\h)/", get_option( 'time_format' ) ) ) { 
				$currdate = date_i18n( 'l, d F Y' . ' ' . get_option( 'time_format' ) , current_time( 'timestamp' ) );
			} else {
				$currdate = date( 'g:i a \o\n l F jS, Y', current_time( 'timestamp' ) );
			}
			
			$content = '<p>' . __( 'Please note that according to your', 'better_wp_security' ) . ' <a href="options-general.php">' . __( 'WordPress timezone settings', 'better_wp_security' ) . '</a> ' . __( 'your local time is', 'better_wp_security' ) . ' <strong><em>' . $currdate . '</em></strong>. ' . __( 'If this is incorrect please correct it on the', 'better_wp_security' ) . ' <a href="options-general.php">' . __( 'WordPress general settings page', 'better_wp_security' ) . '</a> ' . __( 'by setting the appropriate time zone. Failure to set the correct timezone may result in unintended lockouts.', 'better_wp_security' ) . '</p>';


			echo $content;

		}

		/**
		 * Render the settings metabox
		 * 
		 * @return void
		 */
		public function metabox_advanced_settings() {

			printf( '<form name="%s" method="post" action="options.php">', get_current_screen()->id );

			$this->core->do_settings_sections( 'security_page_toplevel_page_better_wp_security-away_mode', false );

			echo '<p>' . PHP_EOL;

			settings_fields( 'security_page_toplevel_page_better_wp_security-away_mode' );

			echo '<input class="button-primary" name="submit" type="submit" value="' . __( 'Save Changes', 'better_wp_security' ) . '" />' . PHP_EOL;

			echo '</p>' . PHP_EOL;

			echo '</form>';

		}

		/**
		 * Start the Springbox module
		 * 
		 * @param  Bit51_BWPS_Core    $core     Instance of core plugin class
		 * @return BWPS_Away_Mode 			    The instance of the BWPS_Away_Mode class
		 */
		public static function start( $core ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}