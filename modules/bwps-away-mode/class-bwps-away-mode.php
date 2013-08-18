<?php

if ( ! class_exists( 'BWPS_Away_Mode' ) ) {

	class BWPS_Away_Mode {

		private static $instance = null;

		private 
			$settings,
			$core,
			$away_file;

		private function __construct( $core ) {

			global $bwps_globals, $bwps_utilities;;

			$this->core = $core;
			$this->settings = get_site_option( 'bwps_away_mode' );
			$this->away_file = $bwps_globals['upload_dir'] . '/bwps_away.confg';

			add_action( $bwps_globals['plugin_hook'] . '_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( $bwps_globals['plugin_hook'] . '_page_top', array( $this, 'add_away_mode_intro' ) ); //add page intro and information
			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) ); //enqueue scripts for admin page
			add_filter( $bwps_globals['plugin_hook'] . '_wp_config_rules', array( $this, 'wp_config_rule' ) ); //build wp_config.php rules
			add_filter( $bwps_globals['plugin_hook'] . '_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( $bwps_globals['plugin_hook'] . '_add_dashboard_status', array( $this, 'dashboard_status' ) ); //add information for plugin status

			if ( $bwps_utilities->is_login_page() === true && $this->check_away() === true ) {
				header( 'Location:' . get_option( 'siteurl' ) );
			}

			//manually save options on multisite
			if ( is_multisite() ) {
				add_action( 'network_admin_edit_bwps_away_mode', array( $this, 'save_network_options' ) ); //save multisite options
			}

		}

		/**
		 * Register subpage for Away Mode
		 * 
		 * @param array $available_pages array of BWPS settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $bwps_globals;

			$available_pages[] = add_submenu_page(
				$bwps_globals['plugin_hook'],
				__( 'Away Mode', 'better_wp_security' ),
				__( 'Away Mode', 'better_wp_security' ),
				$bwps_globals['plugin_access_lvl'],
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

			global $bwps_globals;

			if ( strpos( get_current_screen()->id,'security_page_toplevel_page_bwps-away_mode' ) !== false ) {
				
				wp_enqueue_script( 'bwps_away_mode_js', $bwps_globals['plugin_url'] . 'modules/bwps-away-mode/js/admin-away.js', 'jquery', $bwps_globals['plugin_build'] );
				wp_enqueue_script( 'jquery-ui-datepicker' );
			}

		}

		/**
		 * Check if away mode is active
		 *
		 * @param bool $forms[false] Whether the call comes from the same options form
		 * @param array  @input[NULL] Input of options to check if calling from form
		 * @return bool true if locked out else false
		 */
		private function check_away( $form = false, $input = NULL ) {

			if ( $form === false ) {

				$test_type = $this->settings['type'];
				$test_start = $this->settings['start'];
				$test_end = $this->settings['end'];

			} else {

				$test_type = $input['type'];
				$test_start = $input['start'];
				$test_end = $input['end'];

			}

			$transaway = get_site_transient( 'bwps_away' );

			//if transient indicates away go ahead and lock them out
			if ( $form === false && $transaway === true && file_exists( $this->away_file ) ) {
			
				return true;

			} else { //check manually
			
				$current_time = current_time( 'timestamp' );
				
				if ( $test_type == 1 ) { //set up for daily

					$start = strtotime( date( 'n/j/y', $current_time ) . ' ' . date( 'g:i a', $test_start ) );
					$end = strtotime( date( 'n/j/y', $current_time ) . ' ' . date( 'g:i a', $test_end ) );
				
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
				
					$start = $test_start;
					$end = $test_end;
				
				}

				$remaining = $end - $current_time;
					
				if ( $start <= $current_time && $end >= $current_time && ( $form === true || ( $this->settings['enabled'] === 1 && file_exists( $this->away_file ) ) ) ) { //if away mode is enabled continue

					if ( $form === false ) {

						if ( get_site_transient( 'bwps_away' ) === true ) {
							delete_site_transient ( 'bwps_away' );
						}

						set_site_transient( 'bwps_away' , true, $remaining );

					}

					return true; //time restriction is current
					
				}

			}
			
			return false; //they are allowed to log in

		}

		/**
		 * Sets the status in the plugin dashboard
		 * 
		 * @return void
		 */
		public function dashboard_status( $statuses ) {

			$link = 'admin.php?page=toplevel_page_bwps-away_mode';

			if ( $this->settings['enabled'] === 1 ) {
				$statuses['safe'][] = array(
					'text' => __( 'Away Mode is enabled and your WordPress Dashboard is not available when you will not be needing it.', 'better_wp_security' ),
					'link' => $link,
				);
			} else {
				$statuses['partial'][] = array(
					'text' => __( 'Your WordPress Dashboard is available 24/7. Do you really update 24 hours a day? Consider using Away Mode.', 'better_wp_security' ),
					'link' => $link,
				);
			}

			return $statuses;

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

			//Enabled section
			add_settings_section(  
				'away_mode_enabled',
				__( 'Configure Away Mode', 'better_wp_security' ),
				array( $this, 'sandbox_general_options_callback' ),
				'security_page_toplevel_page_bwps-away_mode'
			);

			//primary settings section
			add_settings_section(  
				'away_mode_settings',
				__( 'Configure Away Mode', 'better_wp_security' ),
				array( $this, 'sandbox_general_options_callback' ),
				'security_page_toplevel_page_bwps-away_mode'
			);

			//enabled field
			add_settings_field(   
				'bwps_away_mode[enabled]', 
				__( 'Enable Away Mode', 'better_wp_security' ),
				array( $this, 'away_mode_enabled' ),
				'security_page_toplevel_page_bwps-away_mode',
				'away_mode_enabled'
			);

			//type field
			add_settings_field(   
				'bwps_away_mode[type]', 
				__( 'Type of Restriction', 'better_wp_security' ),
				array( $this, 'away_mode_type' ),
				'security_page_toplevel_page_bwps-away_mode',
				'away_mode_settings'
			);

			//start date field
			add_settings_field(   
				'bwps_away_mode[start_date]', 
				__( 'Start Date', 'better_wp_security' ),
				array( $this, 'away_mode_start_date' ),
				'security_page_toplevel_page_bwps-away_mode',
				'away_mode_settings'
			);

			//start time field
			add_settings_field(   
				'bwps_away_mode[start_time]', 
				__( 'Start Time', 'better_wp_security' ),
				array( $this, 'away_mode_start_time' ),
				'security_page_toplevel_page_bwps-away_mode',
				'away_mode_settings'
			);

			//end date field
			add_settings_field(   
				'bwps_away_mode[end_date]', 
				__( 'End Date', 'better_wp_security' ),
				array( $this, 'away_mode_end_date' ),
				'security_page_toplevel_page_bwps-away_mode',
				'away_mode_settings'
			);

			//end time field
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
		 * Can be used for an introductory setction or other output. Currently is used by both settings sections.
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

			//disable the option if away mode is in the past
			if ( isset( $this->settings['enabled'] ) && $this->settings['enabled'] === 1 && ( $this->settings['type'] == 1 || ( $this->settings['end'] > current_time( 'timestamp') || $this->settings['type'] === 2 ) ) ) {
				$enabled = 1;
			} else {
				$enabled = 0;
			}

			$content = '<input type="checkbox" id="bwps_away_mode_enabled" name="bwps_away_mode[enabled]" value="1" ' . checked( 1, $enabled, false ) . '/>';  
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

			$current = current_time( 'timestamp' ); //The current time

			//if saved date is in the past update it to something in the future
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

			$current = current_time( 'timestamp' ); //The current time

			//if saved date is in the past update it to something in the future
			if ( isset( $this->settings['end'] ) && isset( $this->settings['enabled'] ) && $current < $this->settings['end'] ) {
				$end = $this->settings['end'];
			} else {
				$end = strtotime( date( 'n/j/y 6:00 \a\m', ( current_time( 'timestamp' ) + ( 86400 * 2 ) ) ) );
			}

			//Hour Field
			$content = '<select name="bwps_away_mode[end][hour]" id="bwps_away_mod_end_time">';

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

			$current = current_time( 'timestamp' ); //The current time

			//if saved date is in the past update it to something in the future
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

			$current = current_time( 'timestamp' ); //The current time

			//if saved date is in the past update it to something in the future
			if ( isset( $this->settings['start'] ) && isset( $this->settings['enabled'] ) && $current < $this->settings['end'] ) {
				$start = $this->settings['start'];
			} else {
				$start = strtotime( date( 'n/j/y 12:00 \a\m', ( current_time( 'timestamp' ) + ( 86400 ) ) ) );
			}

			//Hour Field
			$content = '<select name="bwps_away_mode[start][hour]" id="bwps_away_mod_start_time">';

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

			if ( $screen === 'security_page_toplevel_page_bwps-away_mode' ) { //only display on away mode page

				$content = '<p>' . __( 'As most sites are only updated at certain times of the day it is not always necessary to provide access to the WordPress dashboard 24 hours a day, 7 days a week. The options below will allow you to disable access to the WordPress Dashboard for the specified period. In addition to limiting exposure to attackers this could also be useful to disable site access based on a schedule for classroom or other reasons.', 'better_wp_security' ) . '</p>';
				
				if ( preg_match( "/^(G|H)(:| \\h)/", get_option( 'time_format' ) ) ) { 
					$currdate = date_i18n( 'l, d F Y' . ' ' . get_option( 'time_format' ) , current_time( 'timestamp' ) );
				} else {
					$currdate = date( 'g:i a \o\n l F jS, Y', current_time( 'timestamp' ) );
				}
				
				$content = '<p>' . sprintf( __( 'Please note that according to your %sWordPress timezone settings%s your current time is %s. If this is incorrect please correct it on the %sWordPress general settings page%s by setting the appropriate time zone. Failure to set the correct timezone may result in unintended lockouts.', 'better_wp_security' ), '<a href="options-general.php">', '</a>', '<strong style="color: #f00; font-size: 150%;"><em>' . $currdate . '</em></strong>', '<a href="options-general.php">', '</a>' ) . '</p>';


				echo $content;

				//set information explaining away mode is enabled
				if ( isset( $this->settings['enabled'] ) && $this->settings['enabled'] === 1 && ( $this->settings['type'] === 1 || ( $this->settings['end'] > current_time( 'timestamp' ) ) ) ) {

					$content = '<hr />';

					$content .= sprintf( '<p><strong>%s</strong></p>', __( 'Away mode is currently enabled.', 'better_wp_security' ) );

					//Create the appropriate notification based on daily or one time use
					if ( $this->settings['type'] === 1 ) {

						$content .= sprintf( '<p>' . __( 'The dashboard of this website will become unavailable %s%s%s from %s%s%s until %s%s%s.', 'better_wp_security' ) . '</p>', '<strong>', __( 'every day', 'better_wp_security' ), '</strong>', '<strong>', date_i18n( get_option('time_format'), $this->settings['start'] ), '</strong>', '<strong>', date_i18n( get_option('time_format'), $this->settings['end'] ), '</strong>' );

					} else {

						$content .= sprintf( '<p>' . __( 'The dashboard of this website will become unavailable from %s%s%s on %s%s%s until %s%s%s on %s%s%s.', 'better_wp_security' ) . '</p>', '<strong>', date_i18n( get_option('time_format'), $this->settings['start'] ), '</strong>', '<strong>', date_i18n( get_option('date_format'), $this->settings['start'] ), '</strong>', '<strong>', date_i18n( get_option('time_format'), $this->settings['end'] ), '</strong>', '<strong>', date_i18n( get_option('date_format'), $this->settings['end'] ), '</strong>' );

					}

					$content.= '<p>' . __( 'You will not be able to log into this website when the site is unavailable.', 'better_wp_security' ) . '</p>';

					echo $content;
				}

			}

		}

		/**
		 * Render the settings metabox
		 * 
		 * @return void
		 */
		public function metabox_advanced_settings() {

			//set appropriate action for multisite or standard site
			if ( is_multisite() ) {
				$action = 'edit.php?action=bwps_away_mode';
			} else {
				$action = 'options.php';
			}

			printf( '<form name="%s" method="post" action="%s">', get_current_screen()->id, $action );

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

			global $bwps_globals;

			$input['enabled'] = intval( $input['enabled'] == 1 ? 1 : 0 );

			$input['type'] = intval( $input['type'] == 1 ? 1 : 2 );

			//we don't need to process this again if it is a multisite installation
			if ( ! is_multisite() ) {
			
				$input['start'] = strtotime( $input['start']['date'] . ' ' . $input['start']['hour'] . ':' . $input['start']['minute'] . ' ' . $input['start']['sel'] );
				$input['end'] = strtotime( $input['end']['date'] . ' ' . $input['end']['hour'] . ':' . $input['end']['minute'] . ' ' . $input['end']['sel'] );

			}

			if ( $this->check_away( true, $input ) === true ) {

				$input['enabled'] = 0; //disable away mode
				
				$type = 'error';
				$message = __( 'Invalid time listed. The time entered would lock you out of your site now. Please try again.', 'better_wp_security' );

			} elseif ( $input['type'] === 2 && $input['end'] < $input['start'] ) {

				$input['enabled'] = 0; //disable away mode
				
				$type = 'error';
				$message = __( 'Invalid time listed. The start time selected is after the end time selected.', 'better_wp_security' );

			} elseif ( $input['type'] === 2 && $input['end'] < current_time( 'timestamp' ) ) {

				$input['enabled'] = 0; //disable away mode
				
				$type = 'error';
				$message = __( 'Invalid time listed. The period selected already ended.', 'better_wp_security' );

			} else {

				$type = 'updated';
				$message = __( 'Settings Updated', 'better_wp_security' );

			}

			if ( $input['enabled'] == 1 && ! file_exists( $this->away_file ) ) {

				@file_put_contents( $this->away_file, 'true' );

			} else {

				@unlink( $this->away_file );

			}

			add_settings_error(
        		'bwps_admin_notices',
        		esc_attr( 'settings_updated' ),
        		$message,
        		$type
    		);

			return $input;

		}

		/**
		 * Prepare and save options in network settings
		 * 
		 * @return void
		 */
		public function save_network_options() {

			if ( isset( $_POST['bwps_away_mode']['enabled'] ) ) {
				$settings['enabled'] = intval( $_POST['bwps_away_mode']['enabled'] == 1 ? 1 : 0 );
			}

			$settings['type'] = intval( $_POST['bwps_away_mode']['type'] ) === 1 ? 1 : 2;
			$settings['start'] = strtotime( $_POST['bwps_away_mode']['start']['date'] . ' ' . $_POST['bwps_away_mode']['start']['hour'] . ':' . $_POST['bwps_away_mode']['start']['minute'] . ' ' . $_POST['bwps_away_mode']['start']['sel'] );
			$settings['end'] = strtotime( $_POST['bwps_away_mode']['end']['date'] . ' ' . $_POST['bwps_away_mode']['end']['hour'] . ':' . $_POST['bwps_away_mode']['end']['minute'] . ' ' . $_POST['bwps_away_mode']['end']['sel'] );

			update_site_option( 'bwps_away_mode', $settings ); //we must manually save network options

			//send them back to the away mode options page
			wp_redirect( add_query_arg( array( 'page' => 'toplevel_page_bwps-away_mode', 'updated' => 'true' ), network_admin_url( 'admin.php' ) ) );
			exit();

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