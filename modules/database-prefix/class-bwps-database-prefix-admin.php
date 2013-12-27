<?php

if ( ! class_exists( 'BWPS_Database_Prefix_Admin' ) ) {

	class BWPS_Database_Prefix_Admin {

		private static $instance = NULL;

		private
			$settings,
			$core,
			$page;

		private function __construct( $core ) {

			global $wpdb;

			$this->core = $core;

			if ( $wpdb->base_prefix === 'wp_' ) {
				$this->settings = true;
			} else {
				$this->settings = false;
			}

			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_action( 'bwps_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( 'bwps_page_top', array( $this, 'add_module_intro' ) ); //add page intro and information
			add_filter( 'bwps_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( 'bwps_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu
			add_filter( 'bwps_add_dashboard_status', array( $this, 'dashboard_status' ) ); //add information for plugin status

		}

		/**
		 * Register subpage for Away Mode
		 *
		 * @param array $available_pages array of BWPS settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $bwps_globals;

			$this->page = $available_pages[0] . '-database_prefix';

			$available_pages[] = add_submenu_page(
				'bwps',
				__( 'Database Prefix', 'better_wp_security' ),
				__( 'Database Prefix', 'better_wp_security' ),
				$bwps_globals['plugin_access_lvl'],
				$available_pages[0] . '-database_prefix',
				array( $this->core, 'render_page' )
			);

			return $available_pages;

		}

		public function add_admin_tab( $tabs ) {

			$tabs[$this->page] = __( 'Prefix', 'better_wp_security' );

			return $tabs;

		}

		/**
		 * Add meta boxes to primary options pages
		 *
		 * @param array $available_pages array of available page_hooks
		 */
		public function add_admin_meta_boxes() {

			//add metaboxes
			add_meta_box(
				'database_prefix_options',
				__( 'Change Database Prefix', 'better_wp_security' ),
				array( $this, 'metabox_advanced_settings' ),
				'security_page_toplevel_page_bwps-database_prefix',
				'advanced',
				'core'
			);

		}

		/**
		 * Sets the status in the plugin dashboard
		 *
		 * @return void
		 */
		public function dashboard_status( $statuses ) {

			$link = 'admin.php?page=toplevel_page_bwps-database_prefix';

			if ( $this->settings !== true ) {

				$status_array = 'safe-medium';
				$status       = array(
					'text' => sprintf( '%s wp_.', __( 'Your database table prefix is not using', 'better_wp_security' ) ),
					'link' => $link,
				);

			} else {

				$status_array = 'medium';
				$status       = array(
					'text' => sprintf( '%s wp_.', __( 'Your database table prefix should not be', 'better_wp_security' ) ),
					'link' => $link,
				);

			}

			array_push( $statuses[$status_array], $status );

			return $statuses;

		}

		/**
		 * Execute admin initializations
		 *
		 * @return void
		 */
		public function initialize_admin() {

			if ( isset( $_POST['bwps_one_time_save'] ) && $_POST['bwps_one_time_save'] == 'database_prefix' ) {

				if ( ! wp_verify_nonce( $_POST['wp_nonce'], 'BWPS_admin_save' ) ) {

					die( 'Security error!' );

				} else {

					$this->process_database_prefix();

				}

			}

		}

		/**
		 * Build and echo the away mode description
		 *
		 * @return void
		 */
		public function add_module_intro( $screen ) {

			if ( $screen === 'security_page_toplevel_page_bwps-database_prefix' ) { //only display on away mode page

				$content = '<p>' . __( 'By default WordPress assigns the prefix "wp_" to all the tables in the database where your content, users, and objects live. For potential attackers this means it is easier to write scripts that can target WordPress databases as all the important table names for 95% or so of sites are already known. Changing this makes it more difficult for tools that are trying to take advantage of vulnerabilites in other places to affect the database of your site.', 'better-wp-security' ) . '</p>';
				$content .= '<p>' . __( 'Please note that the use of this tool requires quite a bit of system memory which my be more than some hosts can handle. If you back your database up you can\'t do any permanent damage but without a proper backup you risk breaking your site and having to perform a rather difficult fix.', 'better-wp-security' ) . '</p>';
				$content .= sprintf( '<p style="text-align: center; font-size: 130%%; font-weight: bold; color: blue;">%s: <a href="?page=better-wp-security-databasebackup">%s</a> %s</p>', __( 'WARNING', 'better-wp-security' ), __( 'BACKUP YOUR DATABASE', 'better-wp-security' ), __( 'BEFORE USING THIS TOOL!', 'better-wp-security' ) );
				
				echo $content;

			}

		}

		/**
		 * Render the settings metabox
		 *
		 * @return void
		 */
		public function metabox_advanced_settings() {

			global $wpdb;

			if ( $this->settings === true ) { //Show the correct info

				?>
				<p><strong><?php _e( 'Your database is using the default table prefix', 'better-wp-security' ); ?> <em>wp_</em>. <?php _e( 'You should change this.', 'better-wp-security' ); ?></strong></p>
				<?php

			} else {

				$prefix = $this->settings === false ? $wpdb->base_prefix : $this->settings;

				?>
				<p><?php _e( 'Your current database table prefix is', 'better-wp-security' ); ?> <strong><em><?php echo $prefix; ?></em></strong></p>
				<?php

			}

			?>

			<form method="post" action="">
				<?php wp_nonce_field( 'BWPS_admin_save', 'wp_nonce' ); ?>
				<input type="hidden" name="bwps_one_time_save" value="database_prefix"/>
				<p><?php _e( 'Press the button below to generate a random database prefix value and update all of your tables accordingly.', 'better-wp-security' ); ?></p>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( 'Change Database Table Prefix', 'better-wp-security' ); ?>"/>
				</p>
			</form>

			<?php

		}

		/**
		 * Sanitize and validate input
		 *
		 */
		public function process_database_prefix() {

			global $wpdb;

			$checkPrefix = true;//Assume the first prefix we generate is unique

			//generate a new table prefix that doesn't conflict with any other in use in the database
			while ( $checkPrefix ) {
			
				$avail = 'abcdefghijklmnopqrstuvwxyz0123456789';
				
				//first character should be alpha
				$newPrefix = $avail[rand( 0, 25 )];
				
				//length of new prefix
				$prelength = rand( 4, 9 );
				
				//generate remaning characters
				for ( $i = 0; $i < $prelength; $i++ ) {
					$newPrefix .= $avail[rand( 0, 34 )];
				}
				
				//complete with underscore
				$newPrefix .= '_';
				
				$newPrefix = esc_sql( $newPrefix ); //just be safe
				
				$checkPrefix = $wpdb->get_results( 'SHOW TABLES LIKE "' . $newPrefix . '%";', ARRAY_N ); //if there are no tables with that prefix in the database set checkPrefix to false
					
			}

			//assume this will work
			$type    = 'updated';
			$message = __( 'Settings Updated', 'better_wp_security' );

			$tables = $wpdb->get_results( 'SHOW TABLES LIKE "' . $wpdb->base_prefix . '%"', ARRAY_N ); //retrieve a list of all tables in the DB
					
			//Rename each table
			foreach ( $tables as $table ) {
					
				$table = substr( $table[0], strlen( $wpdb->base_prefix ), strlen( $table[0] ) ); //Get the table name without the old prefix
		
				//rename the table and generate an error if there is a problem
				if ( $wpdb->query( 'RENAME TABLE `' . $wpdb->base_prefix . $table . '` TO `' . $newPrefix . $table . '`;' ) === false ) {

					$type    = 'error';
					$message = sprintf( '%s %s%s. %s', __( 'Error: Could not rename table', 'better-wp-security' ), $wpdb->base_prefix, $table, __( 'You may have to rename the table manually.', 'better-wp-security' ) );
						
				}
						
			}
					
			$upOpts = true; //assume we've successfully updated all options to start
					
			if ( is_multisite() ) { //multisite requires us to rename each blogs' options
						
				$blogs = $wpdb->get_col( "SELECT blog_id FROM `" . $newPrefix . "blogs` WHERE public = '1' AND archived = '0' AND mature = '0' AND spam = '0' ORDER BY blog_id DESC" ); //get list of blog id's
					
				if ( is_array( $blogs) ) { //make sure there are other blogs to update
						
					//update each blog's user_roles option
					foreach ( $blogs as $blog ) {
							
						$results = $wpdb->query( 'UPDATE `' . $newPrefix . $blog . '_options` SET option_name = "' . $newPrefix . $blog . '_user_roles" WHERE option_name = "' . $wpdb->base_prefix . $blog . '_user_roles" LIMIT 1;' );
								
						if ( $results === false ) { //if there's an error upOpts should equal false
							$upOpts = false;
						}
								
					}
							
				}
						
			}
					
			$upOpts = $wpdb->query( 'UPDATE `' . $newPrefix . 'options` SET option_name = "' . $newPrefix . 'user_roles" WHERE option_name = "' . $wpdb->base_prefix . 'user_roles" LIMIT 1;' ); //update options table and set flag to false if there's an error
										
			if ( $upOpts === false ) { //set an error

				$type    = 'error';
				$message = __( 'Could not update prefix references in options table.', 'better_wp_security' );
						
			}
										
			$rows = $wpdb->get_results( 'SELECT * FROM `' . $newPrefix . 'usermeta`' ); //get all rows in usermeta
										
			//update all prefixes in usermeta
			foreach ( $rows as $row ) {
					
				if ( substr( $row->meta_key, 0, strlen( $wpdb->base_prefix ) ) == $wpdb->base_prefix ) {
						
					$pos = $newPrefix . substr( $row->meta_key, strlen( $wpdb->base_prefix ), strlen( $row->meta_key ) );
							
					$result = $wpdb->query( 'UPDATE `' . $newPrefix . 'usermeta` SET meta_key="' . $pos . '" WHERE meta_key= "' . $row->meta_key . '" LIMIT 1;' );
							
					if ( $result == false ) {

						$type    = 'error';
						$message = __( 'Could not update prefix references in usermeta table.', 'better_wp_security' );
								
					}
							
				}
						
			}

			$rules = array(
				'save'  => false,
				'rules' => array(
					'rules' => array(
						array( 
							'type' =>	'replace',
							'search_text' => 'table_prefix',
							'rule'	=> "\$table_prefix = '" . $newPrefix . "';",
						),
					),
				),
			);

			if ( ! new Ithemes_BWPS_Files( 'wpconfig', 'Database Prefix', $rules ) ) {

				$type    = 'error';
				$message = __( 'WordPress was unable to rename your rename the database table in your wp-config.php file. Please check with your server administrator and try again.', 'better_wp_security' );

			}

			$this->settings = $newPrefix; //this tells the form field that all went well.

			add_settings_error(
				'bwps_admin_notices',
				esc_attr( 'settings_updated' ),
				$message,
				$type
			);

		}

		/**
		 * Start the Content Directory module
		 *
		 * @param  Ithemes_BWPS_Core $core Instance of core plugin class
		 *
		 * @return BWPS_Database_Prefix_Admin                The instance of the BWPS_Database_Prefix_Admin class
		 */
		public static function start( $core ) {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}