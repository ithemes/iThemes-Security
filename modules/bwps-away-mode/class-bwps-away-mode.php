<?php

if ( ! class_exists( 'BWPS_Away_Mode' ) ) {

	class BWPS_Away_Mode {

		private static $instance = null;

		private 
			$settings,
			$core;

		private function __construct( $core ) {

			$this->core = $core;
			$this->settings = get_site_option( 'bwps_away_mode' );

			add_action( $this->core->plugin->globals['plugin_hook'] . '_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) );
			add_action( $this->core->plugin->globals['plugin_hook'] . '_page_top', array( $this, 'add_away_mode_intro' ) );
			add_filter( $this->core->plugin->globals['plugin_hook'] . '_add_admin_sub_pages', array( $this, 'add_sub_page' ) );
			add_action( 'admin_init', array( $this, 'initialize_admin' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) );

		}

		/**
		 * Register subpage for Away Mode
		 * 
		 * @param array $available_pages array of BWPS settings pages
		 */
		public function add_sub_page( $available_pages ) {

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
		public function add_admin_meta_boxes( $available_pages ) {

			//add metaboxes
			add_meta_box( 
				'away_mode_options', 
				__( 'Configure Away Mode', 'better_wp_security' ),
				array( $this, 'metabox_advanced_settings' ),
				'security_page_toplevel_page_bwps-away_mode',
				'advanced',
				'core'
			);

		}

		/**
		 * Add Away mode Javascript
		 * 
		 * @return void
		 */
		public function admin_script() {

			if( get_current_screen()->id == 'security_page_toplevel_page_bwps-away_mode' ) {
				wp_enqueue_script( 'bwps_away_mode_js', $this->core->plugin->globals['plugin_url'] . 'modules/bwps-away-mode/js/admin-away.js', 'jquery', $this->core->plugin->globals['plugin_build'] );
				wp_enqueue_script( 'jquery-ui-datepicker' );
			}

		}

		/**
		 * Check if away mode is active
		 * 
		 * @return bool true if locked out else false
		 */
		private function check_away() {

			$transaway = get_site_transient( 'bwps_away' );

			//if transient indicates away go ahead and lock them out
			if ( $transaway === true && defined( 'BWPS_AWAY_MODE' ) && BWPS_AWAY_MODE === true ) {
			
				return true;

			} else { //check manually
			
				$current_time = current_time( 'timestamp' );
				
				if ( $this->settings['type'] == 1 && defined( 'BWPS_AWAY_MODE' ) && BWPS_AWAY_MODE === true ) { //set up for daily

					$start = strtotime( date( 'n/j/y', $current_time ) . ' ' . date( 'g:i a', $this->settings['start'] ) );
					$end = strtotime( date( 'n/j/y', $current_time ) . ' ' . date( 'g:i a', $this->settings['end'] ) );
				
					if ( $start > $end ) { //starts and ends on same calendar day

						if ( strtotime( date( 'n/j/y', $current_time ) . ' ' . date( 'g:i a', $start ) ) <= $current_time ) { 
					
							$start = strtotime( date( 'n/j/y', $current_time ) . ' ' . date( 'g:i a', $start ) );
							$end = strtotime( date( 'n/j/y', ( $current_time + 86400 ) ) . ' ' . date( 'g:i a', $end ) );
							
						} else {
						
							$start = strtotime( date( 'n/j/y', $current_time - 86400 ) . ' ' . date( 'g:i a', $start ) );
							$end = strtotime( date( 'n/j/y', ( $current_time ) ) . ' ' . date( 'g:i a', $end ) );
						
						}
						
					}

					if ( $end < $current_time ) { //make sure to advance the day appropriately

						$start = $start + 86400;
						$end = $end + 86400;

					}
					
				} else { //one time settings
				
					$start = $this->settings['start'];
					$end = $this->settings['end'];
				
				}

				$remaining = $end - $current_time;
					
				if ( $this->settings['enabled'] == 1 && defined( 'BWPS_AWAY_MODE' ) && BWPS_AWAY_MODE === true && $start <= $current_time && $end >= $current_time ) { //if away mode is enabled continue

					if ( get_site_transient( 'bwps_away' ) === true ) {
						delete_site_transient ( 'bwps_away' );
					}

					set_site_transient( 'bwps_away' , true, $remaining );

					return true; //time restriction is current
					
				}

			}
			
			return false; //they are allowed to log in

		}

		/**
		 * Execute admin initializations
		 * 
		 * @return void
		 */
		public function initialize_admin() {

			//execute lockout if applicable
			if( $this->check_away() ) {
				wp_redirect( get_option( 'siteurl' ) );
				wp_clear_auth_cookie();
			}

			add_settings_section(  
				'away_mode_enabled',
				__( 'Configure Away Mode', 'better_wp_security' ),
				array( $this, 'sandbox_general_options_callback' ),
				'security_page_toplevel_page_bwps-away_mode'
			);

			add_settings_section(  
				'away_mode_settings',
				__( 'Configure Away Mode', 'better_wp_security' ),
				array( $this, 'sandbox_general_options_callback' ),
				'security_page_toplevel_page_bwps-away_mode'
			);

			add_settings_field(   
				'bwps_away_mode[enabled]', 
				__( 'Enable Away Mode', 'better_wp_security' ),
				array( $this, 'away_mode_enabled' ),
				'security_page_toplevel_page_bwps-away_mode',
				'away_mode_enabled'
			);

			add_settings_field(   
				'bwps_away_mode[type]', 
				__( 'Type of Restriction', 'better_wp_security' ),
				array( $this, 'away_mode_type' ),
				'security_page_toplevel_page_bwps-away_mode',
				'away_mode_settings'
			);

			add_settings_field(   
				'bwps_away_mode[start_date]', 
				__( 'Start Date', 'better_wp_security' ),
				array( $this, 'away_mode_start_date' ),
				'security_page_toplevel_page_bwps-away_mode',
				'away_mode_settings'
			);

			add_settings_field(   
				'bwps_away_mode[start_time]', 
				__( 'Start Time', 'better_wp_security' ),
				array( $this, 'away_mode_start_time' ),
				'security_page_toplevel_page_bwps-away_mode',
				'away_mode_settings'
			);

			add_settings_field(   
				'bwps_away_mode[end_date]', 
				__( 'End Date', 'better_wp_security' ),
				array( $this, 'away_mode_end_date' ),
				'security_page_toplevel_page_bwps-away_mode',
				'away_mode_settings'
			);

			add_settings_field(   
				'bwps_away_mode[end_time]', 
				__( 'End Time', 'better_wp_security' ),
				array( $this, 'away_mode_end_time' ),
				'security_page_toplevel_page_bwps-away_mode',
				'away_mode_settings'
			);

			//Register the settings field for the entire module
			register_setting(  
				'security_page_toplevel_page_bwps-away_mode',
				'bwps_away_mode',
				array( $this, 'sanitize_module_input' )
			);

			//Add the date picker
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );

		}

		/**
		 * Settings section callback
		 * 
		 * @return void
		 */
		public function sandbox_general_options_callback() {}

		/**
		 * echos Enabled Field
		 * 
		 * @param  array $args field arguements
		 * @return void
		 */
		public function away_mode_enabled( $args ) {

			$content = '<input type="checkbox" id="bwps_away_mode_enabled" name="bwps_away_mode[enabled]" value="1" ' . checked( 1, $this->settings['enabled'], false ) . '/>';  
			$content .= '<label for="bwps_away_mode_enabled"> '  . __( 'Check this box to enable away mode', 'better_wp_security' ) . '</label>';   

			echo $content;

		}

		/**
		 * echos End date field
		 * 
		 * @param  array $args field arguements
		 * @return void
		 */
		public function away_mode_end_date( $args ) {

			$current = current_time( 'timestamp' );

			if ( isset( $this->settings['end'] ) && isset( $this->settings['enabled'] ) && $current < $this->settings['end'] ) {
				$end = $this->settings['end'];
			} else {
				$end = strtotime( date( 'n/j/y 12:00 \a\m', ( current_time( 'timestamp' ) + ( 86400 * 2 ) ) ) );
			}

			//Date Field
			$content = '<input class="end_date_field" type="text" id="bwps_away_mode_end_date" name="bwps_away_mode[end][date]" value="' . date( 'm/d/y', $end ) . '"/>'; 
			$content .= '<label class="end_date_field" for="bwps_away_mode_end_date"> '  . __( 'Set the date at which the admin dashboard should become available', 'better_wp_security' ) . '</label>'; 

			echo $content;

		}

		/**
		 * echos End time field
		 * 
		 * @param  array $args field arguements
		 * @return void
		 */
		public function away_mode_end_time( $args ) {

			$current = current_time( 'timestamp' );

			if ( isset( $this->settings['end'] ) && isset( $this->settings['enabled'] ) && $current < $this->settings['end'] ) {
				$end = $this->settings['end'];
			} else {
				$end = strtotime( date( 'n/j/y 12:00 \a\m', ( current_time( 'timestamp' ) + ( 86400 * 2 ) ) ) );
			}

			//Hour Field
			$content .= '<select name="bwps_away_mode[end][hour]" id="bwps_away_mod_end_time">';

			for ( $i = 1; $i <= 12; $i++ ) {
				$content .= '<option value="' . sprintf( '%02d', $i ) . '" ' . selected( date( 'g', $end ), $i, false ) . '>' . $i . '</option>';
			}

			$content .= '</select>';

			//Minute Field
			$content .= '<select name="bwps_away_mode[end][minute]" id="bwps_away_mod_end_time">';

			for ( $i = 0; $i <= 59; $i++ ) {

				$content .= '<option value="' . sprintf( '%02d', $i ) . '" ' . selected( date( 'i', $end ), sprintf( '%02d', $i ), false ) . '>' . sprintf( '%02d', $i ) . '</option>';
			}

			$content .= '</select>';

			//AM/PM Field
			$content .= '<select name="bwps_away_mode[end][sel]" id="bwps_away_mod_end_time">';
    		$content .= '<option value="am" ' . selected( date( 'a', $end ), 'am', false ) . '>' . __( 'am', 'better_wp_security' ) . '</option>';
    		$content .= '<option value="pm" ' . selected( date( 'a', $end ), 'pm', false ) . '>' . __( 'pm', 'better_wp_security' ) . '</option>';
			$content .= '</select>';
			$content .= '<label for="bwps_away_mod_end_time"> '  . __( 'Set the time at which the admin dashboard should become available again.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos Start date field
		 * 
		 * @param  array $args field arguements
		 * @return void
		 */
		public function away_mode_start_date( $args ) {

			$current = current_time( 'timestamp' );

			if ( isset( $this->settings['start'] ) && isset( $this->settings['enabled'] ) && $current < $this->settings['end'] ) {
				$start = $this->settings['start'];
			} else {
				$start = strtotime( date( 'n/j/y 12:00 \a\m', ( current_time( 'timestamp' ) + ( 86400 ) ) ) );
			}

			//Date Field
			$content = '<input class="start_date_field" type="text" id="bwps_away_mode_start_date" name="bwps_away_mode[start][date]" value="' . date( 'm/d/y', $start ) . '"/>'; 
			$content .= '<label class="start_date_field" for="bwps_away_mode_start_date"> '  . __( 'Set the date at which the admin dashboard should become unavailable', 'better_wp_security' ) . '</label>'; 

			echo $content;

		}

		/**
		 * echos Start time field
		 * 
		 * @param  array $args field arguements
		 * @return void
		 */
		public function away_mode_start_time( $args ) {

			$current = current_time( 'timestamp' );

			if ( isset( $this->settings['start'] ) && isset( $this->settings['enabled'] ) && $current < $this->settings['end'] ) {
				$start = $this->settings['start'];
			} else {
				$start = strtotime( date( 'n/j/y 12:00 \a\m', ( current_time( 'timestamp' ) + ( 86400 ) ) ) );
			}

			//Hour Field
			$content .= '<select name="bwps_away_mode[start][hour]" id="bwps_away_mod_start_time">';

			for ( $i = 1; $i <= 12; $i++ ) {
				$content .= '<option value="' . sprintf( '%02d', $i ) . '" ' . selected( date( 'g', $start ), $i, false ) . '>' . $i . '</option>';
			}

			$content .= '</select>';

			//Minute Field
			$content .= '<select name="bwps_away_mode[start][minute]" id="bwps_away_mod_start_time">';

			for ( $i = 0; $i <= 59; $i++ ) {

				$content .= '<option value="' . sprintf( '%02d', $i ) . '" ' . selected( date( 'i', $start ), sprintf( '%02d', $i ), false ) . '>' . sprintf( '%02d', $i ) . '</option>';
			}

			$content .= '</select>';

			//AM/PM Field
			$content .= '<select name="bwps_away_mode[start][sel]" id="bwps_away_mod_start_time">';
    		$content .= '<option value="am" ' . selected( date( 'a', $start ), 'am', false ) . '>' . __( 'am', 'better_wp_security' ) . '</option>';
    		$content .= '<option value="pm" ' . selected( date( 'a', $start ), 'pm', false ) . '>' . __( 'pm', 'better_wp_security' ) . '</option>';
			$content .= '</select>';
			$content .= '<label for="bwps_away_mod_start_time"> '  . __( 'Set the time at which the admin dashboard should become available again.', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * echos type Field
		 * 
		 * @param  array $args field arguements
		 * @return void
		 */
		public function away_mode_type( $args ) {

			$content = '<select name="bwps_away_mode[type]" id="bwps_away_mode_type">';
    		$content .= '<option value="1" ' . selected( $this->settings['type'], 1, false ) . '>' . __( 'Daily', 'better_wp_security' ) . '</option>';
    		$content .= '<option value="2" ' . selected( $this->settings['type'], 2, false ) . '>' . __( 'One Time', 'better_wp_security' ) . '</option>';
			$content .= '</select>';
			$content .= '<label for="bwps_away_mode_type"> '  . __( 'Check this box to enable away mode', 'better_wp_security' ) . '</label>';

			echo $content;

		}

		/**
		 * Build and echo the away mode description
		 * 
		 * @return void
		 */
		public function add_away_mode_intro( $screen ) {

			if ( $screen === 'security_page_toplevel_page_bwps-away_mode' ) {

				$content = '<p>' . __( 'As most sites are only updated at certain times of the day it is not always necessary to provide access to the WordPress dashboard 24 hours a day, 7 days a week. The options below will allow you to disable access to the WordPress Dashboard for the specified period. In addition to limiting exposure to attackers this could also be useful to disable site access based on a schedule for classroom or other reasons.', 'better_wp_security' ) . '</p>';
				
				if ( preg_match( "/^(G|H)(:| \\h)/", get_option( 'time_format' ) ) ) { 
					$currdate = date_i18n( 'l, d F Y' . ' ' . get_option( 'time_format' ) , current_time( 'timestamp' ) );
				} else {
					$currdate = date( 'g:i a \o\n l F jS, Y', current_time( 'timestamp' ) );
				}
				
				$content = '<p>' . sprintf( __( 'Please note that according to your %sWordPress timezone settings%s your current time is %s. If this is incorrect please correct it on the %sWordPress general settings page%s by setting the appropriate time zone. Failure to set the correct timezone may result in unintended lockouts.', 'better_wp_security' ), '<a href="options-general.php">', '</a>', '<strong style="color: #f00; font-size: 150%;"><em>' . $currdate . '</em></strong>', '<a href="options-general.php">', '</a>' ) . '</p>';


				echo $content;

			}

		}

		/**
		 * Render the settings metabox
		 * 
		 * @return void
		 */
		public function metabox_advanced_settings() {

			printf( '<form name="%s" method="post" action="options.php">', get_current_screen()->id );

			$this->core->do_settings_sections( 'security_page_toplevel_page_bwps-away_mode', false );

			echo '<p>' . PHP_EOL;

			settings_fields( 'security_page_toplevel_page_bwps-away_mode' );

			echo '<input class="button-primary" name="submit" type="submit" value="' . __( 'Save Changes', 'better_wp_security' ) . '" />' . PHP_EOL;

			echo '</p>' . PHP_EOL;

			echo '</form>';

		}

		/**
		 * Sanitize and validate input
		 * 
		 * @param  Array $input  array of input fields
		 * @return Array         Sanitized array
		 */
		public function sanitize_module_input( $input ) {

			$input['enabled'] = intval( $input['enabled'] );
			$input['type'] = intval( $input['type'] );
			
			$input['start'] = strtotime( $input['start']['date'] . ' ' . $input['start']['hour'] . ':' . $input['start']['minute'] . ' ' . $input['start']['sel'] );
			$input['end'] = strtotime( $input['end']['date'] . ' ' . $input['end']['hour'] . ':' . $input['end']['minute'] . ' ' . $input['end']['sel'] );

			return $input;

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