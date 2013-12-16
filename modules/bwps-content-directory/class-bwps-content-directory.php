<?php

if ( ! class_exists( 'BWPS_Content_Directory' ) ) {

	class BWPS_Content_Directory {

		private static $instance = NULL;

		private
			$settings,
			$core,
			$page;

		private function __construct( $core ) {

			global $bwps_globals;

			$this->core = $core;

			if ( ! strstr( WP_CONTENT_DIR, 'wp-content' ) || ! strstr( WP_CONTENT_URL, 'wp-content' ) ) {
				$this->settings = true;
			} else {
				$this->settings = false;
			}

			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_action( $bwps_globals['plugin_hook'] . '_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_action( $bwps_globals['plugin_hook'] . '_page_top', array( $this, 'add_content_directory_intro' ) ); //add page intro and information
			add_filter( $bwps_globals['plugin_hook'] . '_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( $bwps_globals['plugin_hook'] . '_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu
			add_filter( $bwps_globals['plugin_hook'] . '_add_dashboard_status', array( $this, 'dashboard_status' ) ); //add information for plugin status

		}

		/**
		 * Register subpage for Away Mode
		 *
		 * @param array $available_pages array of BWPS settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $bwps_globals;

			$this->page = $available_pages[0] . '-content_directory';

			$available_pages[] = add_submenu_page(
				$bwps_globals['plugin_hook'],
				__( 'Content Directory', 'better_wp_security' ),
				__( 'Content Directory', 'better_wp_security' ),
				$bwps_globals['plugin_access_lvl'],
				$available_pages[0] . '-content_directory',
				array( $this->core, 'render_page' )
			);

			return $available_pages;

		}

		public function add_admin_tab( $tabs ) {

			$tabs[$this->page] = __( 'Dir', 'better_wp_security' );

			return $tabs;

		}

		/**
		 * Add meta boxes to primary options pages
		 *
		 * @param array $available_pages array of available page_hooks
		 */
		public function add_admin_meta_boxes() {

			if ( ! $this->settings === true ) {

				//add metaboxes
				add_meta_box(
					'content_directory_options',
					__( 'Change Content Directory', 'better_wp_security' ),
					array( $this, 'metabox_advanced_settings' ),
					'security_page_toplevel_page_bwps-content_directory',
					'advanced',
					'core'
				);

			}

		}

		/**
		 * Sets the status in the plugin dashboard
		 *
		 * @return void
		 */
		public function dashboard_status( $statuses ) {

			$link = 'admin.php?page=toplevel_page_bwps-content_directory';

			if ( $this->settings === true ) {

				$status_array = 'safe-low';
				$status       = array(
					'text' => __( 'You have renamed the wp-content directory of your site.', 'better_wp_security' ),
					'link' => $link,
				);

			} else {

				$status_array = 'low';
				$status       = array(
					'text' => __( 'You should rename the wp-content directory of your site.', 'better_wp_security' ),
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

			if ( ! $this->settings === true && isset( $_POST['bwps_one_time_save'] ) ) {

				if ( ! wp_verify_nonce( $_POST['wp_nonce'], 'BWPS_admin_save' ) ) {

					die( 'Security error!' );

				} else {

					$this->process_directory();

				}

			}

		}

		/**
		 * Build and echo the away mode description
		 *
		 * @return void
		 */
		public function add_content_directory_intro( $screen ) {

			if ( $screen === 'security_page_toplevel_page_bwps-content_directory' ) { //only display on away mode page

				if ( $this->settings !== true ) {

					$content = '<p>' . __( 'By default WordPress puts all your content including images, plugins, themes, uploads, and more in a directory called "wp-content". This makes it easy to scan for vulnerable files on your WordPress installation as an attacker already knows where the vulnerable files will be at. As there are many plugins and themes with security vulnerabilities moving this folder can make it harder for an attacker to find problems with your site as scans of your site\'s file system will not produce any results.', 'better-wp-security' ) . '</p>';
					$content .= '<p>' . __( 'Please note that changing the name of your wp-content directory on a site that already has images and other content referencing it will break your site. For that reason I highly recommend you do not try this on anything but a fresh WordPress install. In addition, this tool will not allow further changes to your wp-content folder once it has already been renamed in order to avoid accidently breaking a site later on. This includes uninstalling this plugin which will not revert the changes made by this page.', 'better-wp-security' ) . '</p>';
					$content .= '<p>' . __( 'Finally, changing the name of the wp-content directory may in fact break plugins and themes that have "hard-coded" it into their design rather than call it dynamically.', 'better-wp-security' ) . '</p>';
					$content .= '<p style="text-align: center; font-size: 130%; font-weight: bold; color: #ff0000;">' . __( 'WARNING: BACKUP YOUR WORDPRESS INSTALLATION BEFORE USING THIS TOOL!', 'better-wp-security' ) . '</p>';
					$content .= '<p style="text-align: center; font-size: 130%; font-weight: bold; color: #ff0000;">' . __( 'RENAMING YOUR wp-content WILL BREAK LINKS ON A SITE WITH EXISTING CONTENT.', 'better-wp-security' ) . '</p>';

				} else {

					if ( isset( $_POST['bwps_one_time_save'] ) ) {

						$dir_name = sanitize_file_name( $_POST['name'] );

					} else {

						$dir_name = substr( WP_CONTENT_DIR, strrpos( WP_CONTENT_DIR, '/' ) + 1 );
					}

					$content = '<p>' . __( 'Congratulations! You have already renamed your "wp-content" directory.', 'better-wp-security' ) . '</p>';
					$content .= '<p>' . __( 'Your current content directory is: ', 'better-wp-security' );
					$content .= '<strong>' . $dir_name . '</strong></p>';
					$content .= '<p>' . __( 'No further actions are available on this page.', 'better-wp-security' ) . '</p>';

				}

				echo $content;

			}

		}

		/**
		 * Render the settings metabox
		 *
		 * @return void
		 */
		public function metabox_advanced_settings() {

			if ( $this->settings !== true ) { //only show form if user the content directory hasn't already been changed
				?>

				<form method="post" action="">
					<?php wp_nonce_field( 'BWPS_admin_save', 'wp_nonce' ); ?>
					<input type="hidden" name="bwps_one_time_save" value="content_directory"/>
					<table class="form-table">
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<label for "name"><?php _e( 'Directory Name', 'better-wp-security' ); ?></label>
							</th>
							<td class="settingfield">
								<?php //username field ?>
								<input id="name" name="name" type="text" value="wp-content"/>

								<p><?php _e( 'Enter a new directory name to replace "wp-content." You may need to log in again after performing this operation.', 'better-wp-security' ); ?></p>
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'better-wp-security' ); ?>"/>
					</p>
				</form>
			<?php

			}

		}

		/**
		 * Sanitize and validate input
		 *
		 */
		public function process_directory() {

			global $bwps_utilities;

			$dir_name = sanitize_file_name( $_POST['name'] );

			//assume this will work
			$type    = 'updated';
			$message = __( 'Settings Updated', 'better_wp_security' );

			if ( strlen( $dir_name ) <= 2 ) { //make sure the directory name is at least 2 characters

				$type    = 'error';
				$message = __( 'Please choose a directory name that is greater than 2 characters in length.', 'better_wp_security' );

			} elseif( $dir_name === 'wp-content' ) {

				$type    = 'error';
				$message = __( 'You have not chosen a new name for wp-content. Nothing was saved.', 'better_wp_security' );

			} else { //process the name change

				if ( $bwps_utilities->get_lock() ) {

					$config = $bwps_utilities->get_config();

					if ( ! $f = @fopen( $config, 'a+' ) ) { //make sure we can open the file for writing

						@chmod( $config, 0644 );

						if ( ! $f = @fopen( $config, 'a+' ) ) {

							$type    = 'error';
							$message = __( 'Fatal error. Couldn\'t open wp-config.php for writing. Please contact your system administrator.', 'better_wp_security' );

							add_settings_error(
								'bwps_admin_notices',
								esc_attr( 'settings_updated' ),
								$message,
								$type
							);

						}

						@fclose( $f );

						return;

					}

					$old_dir = WP_CONTENT_DIR;
					$new_dir = trailingslashit( ABSPATH ) . $dir_name;

					if ( ! rename( $old_dir, $new_dir ) ) { //make sure renaming the directory was successful

						$type    = 'error';
						$message = __( 'WordPress was unable to rename your wp-content directory. Please check with your server administrator and try again.', 'better_wp_security' );

					} else {

						$handle = @fopen( $config, 'r+' ); //open for reading

						if ( $handle ) {

							$scanText = "<?php";
							$newText  = "<?php" . PHP_EOL . "define( 'WP_CONTENT_DIR', '" . $new_dir . "' );" . PHP_EOL . "define( 'WP_CONTENT_URL', '" . trailingslashit( get_option( 'siteurl' ) ) . $dir_name . "' );" . PHP_EOL;

							//read each line into an array
							while ( $lines[] = fgets( $handle, 4096 ) ) {
							}

							fclose( $handle ); //close reader

							$handle = @fopen( $config, 'w+' ); //open writer

							foreach ( $lines as $line ) { //process each line

								if ( strstr( $line, 'WP_CONTENT_DIR' ) || strstr( $line, 'WP_CONTENT_URL' ) ) {

									$line = str_replace( $line, '', $line );

								}

								if ( strstr( $line, $scanText ) ) {

									$line = str_replace( $scanText, $newText, $line );

								}

								fwrite( $handle, $line ); //write the line

							}

							fclose( $handle ); //close the config file

						}

					}

					$this->settings = true;

					$bwps_utilities->release_lock();

				}

			}

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
		 * @return BWPS_content_directory                The instance of the BWPS_content_directory class
		 */
		public static function start( $core ) {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}