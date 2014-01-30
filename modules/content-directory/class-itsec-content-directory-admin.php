<?php

if ( ! class_exists( 'ITSEC_Content_Directory_Admin' ) ) {

	class ITSEC_Content_Directory_Admin {

		private static $instance = null;

		private $settings, $core, $page;

		private function __construct( $core ) {

			$this->core = $core;

			if ( strpos( WP_CONTENT_DIR, 'wp-content' ) === false || strpos( WP_CONTENT_URL, 'wp-content' ) === false ) {
				$this->settings = true;
			} else {
				$this->settings = false;
			}

			add_action( 'admin_init', array( $this, 'initialize_admin' ) ); //initialize admin area
			add_action( 'itsec_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) ); //add meta boxes to admin page
			add_filter( 'itsec_add_admin_sub_pages', array( $this, 'add_sub_page' ) ); //add to admin menu
			add_filter( 'itsec_add_admin_tabs', array( $this, 'add_admin_tab' ) ); //add tab to menu
			add_filter( 'itsec_add_dashboard_status', array( $this, 'dashboard_status' ) ); //add information for plugin status

		}

		/**
		 * Register subpage for Away Mode
		 *
		 * @param array $available_pages array of ITSEC settings pages
		 */
		public function add_sub_page( $available_pages ) {

			global $itsec_globals;

			$this->page = $available_pages[0] . '-content_directory';

			$available_pages[] = add_submenu_page( 'itsec', __( 'Content Directory', 'ithemes-security' ), __( 'Content Directory', 'ithemes-security' ), $itsec_globals['plugin_access_lvl'], $available_pages[0] . '-content_directory', array( $this->core, 'render_page' ) );

			return $available_pages;

		}

		public function add_admin_tab( $tabs ) {

			$tabs[$this->page] = __( 'Dir', 'ithemes-security' );

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
				add_meta_box( 'content_directory_description', __( 'Description', 'ithemes-security' ), array( $this, 'add_module_intro' ), 'security_page_toplevel_page_itsec-content_directory', 'normal', 'core' );

				add_meta_box( 'content_directory_options', __( 'Change Content Directory', 'ithemes-security' ), array( $this, 'metabox_advanced_settings' ), 'security_page_toplevel_page_itsec-content_directory', 'advanced', 'core' );

			}

		}

		/**
		 * Sets the status in the plugin dashboard
		 *
		 * @return void
		 */
		public function dashboard_status( $statuses ) {

			$link = 'admin.php?page=toplevel_page_itsec-content_directory';

			if ( $this->settings === true ) {

				$status_array = 'safe-low';
				$status       = array( 'text' => __( 'You have renamed the wp-content directory of your site.', 'ithemes-security' ), 'link' => $link, );

			} else {

				$status_array = 'low';
				$status       = array( 'text' => __( 'You should rename the wp-content directory of your site.', 'ithemes-security' ), 'link' => $link, );

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

			if ( ! $this->settings === true && isset( $_POST['itsec_one_time_save'] ) && $_POST['itsec_one_time_save'] == 'content_directory' ) {

				if ( ! wp_verify_nonce( $_POST['wp_nonce'], 'ITSEC_admin_save' ) ) {

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
		public function add_module_intro( $screen ) {

			if ( $this->settings !== true ) {

				$content = '<p>' . __( 'By default WordPress puts all your content including images, plugins, themes, uploads, and more in a directory called "wp-content". This makes it easy to scan for vulnerable files on your WordPress installation as an attacker already knows where the vulnerable files will be at. As there are many plugins and themes with security vulnerabilities moving this folder can make it harder for an attacker to find problems with your site as scans of your site\'s file system will not produce any results.', 'ithemes-security' ) . '</p>';
				$content .= '<p>' . __( 'Please note that changing the name of your wp-content directory on a site that already has images and other content referencing it will break your site. For that reason I highly recommend you do not try this on anything but a fresh WordPress install. In addition, this tool will not allow further changes to your wp-content folder once it has already been renamed in order to avoid accidently breaking a site later on. This includes uninstalling this plugin which will not revert the changes made by this page.', 'ithemes-security' ) . '</p>';
				$content .= '<p>' . __( 'Finally, changing the name of the wp-content directory may in fact break plugins and themes that have "hard-coded" it into their design rather than call it dynamically.', 'ithemes-security' ) . '</p>';
				$content .= sprintf( '<div class="itsec-warning-message"><span>%s: </span>%s</div>', __( 'WARNING', 'ithemes-security' ), __( 'Backup your WordPress installation before using this tool. Renaming your <code>wp-content</code> directory will break links on a site with existing content.', 'ithemes-security' ) );

			} else {

				if ( isset( $_POST['itsec_one_time_save'] ) ) {

					$dir_name = sanitize_file_name( $_POST['name'] );

				} else {

					$dir_name = substr( WP_CONTENT_DIR, strrpos( WP_CONTENT_DIR, '/' ) + 1 );
				}

				$content = '<p>' . __( 'Congratulations! You have already renamed your "wp-content" directory.', 'ithemes-security' ) . '</p>';
				$content .= '<p>' . __( 'Your current content directory is: ', 'ithemes-security' );
				$content .= '<strong>' . $dir_name . '</strong></p>';
				$content .= '<p>' . __( 'No further actions are available on this page.', 'ithemes-security' ) . '</p>';

			}

			echo $content;

		}

		/**
		 * Render the settings metabox
		 *
		 * @return void
		 */
		public function metabox_advanced_settings() {

			if ( $this->settings !== true ) { //only show form if user the content directory hasn't already been changed
				?>

				<form method="post" action="" class="itsec-form">
					<?php wp_nonce_field( 'ITSEC_admin_save', 'wp_nonce' ); ?>
					<input type="hidden" name="itsec_one_time_save" value="content_directory"/>
					<table class="form-table">
						<tr valign="top">
							<th scope="row" class="settinglabel">
								<label for "name"><?php _e( 'Directory Name', 'ithemes-security' ); ?></label>
							</th>
							<td class="settingfield">
								<?php //username field ?>
								<input id="name" name="name" type="text" value="wp-content"/>

								<p class="description"><?php _e( 'Enter a new directory name to replace "wp-content." You may need to log in again after performing this operation.', 'ithemes-security' ); ?></p>
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" class="button-primary"
							   value="<?php _e( 'Save Changes', 'ithemes-security' ); ?>"/>
					</p>
				</form>
			<?php

			}

		}

		/**
		 * Build wp-config.php rules
		 *
		 * @param  array $input options to build rules from
		 *
		 * @return array         rules to write
		 */
		public function build_wpconfig_rules( $rules_array, $input = null ) {

			//Get the rules from the database if input wasn't sent
			if ( $input === null ) {
				return $rules_array;
			}

			$new_dir = trailingslashit( ABSPATH ) . $input;

			$rules[] = array( 'type' => 'add', 'search_text' => '//Do not delete these. Doing so WILL break your site.', 'rule' => "//Do not delete these. Doing so WILL break your site.", );

			$rules[] = array( 'type' => 'add', 'search_text' => 'WP_CONTENT_URL', 'rule' => "define( 'WP_CONTENT_URL', '" . trailingslashit( get_option( 'siteurl' ) ) . $input . "' );", );

			$rules[] = array( 'type' => 'add', 'search_text' => 'WP_CONTENT_DIR', 'rule' => "define( 'WP_CONTENT_DIR', '" . $new_dir . "' );", );

			$rules_array[] = array( 'type' => 'wpconfig', 'name' => 'Content Directory', 'rules' => $rules, );

			return $rules_array;

		}

		/**
		 * Sanitize and validate input
		 *
		 */
		public function process_directory() {

			global $itsec_files;

			$dir_name = sanitize_file_name( $_POST['name'] );

			//assume this will work
			$type    = 'updated';
			$message = __( 'Settings Updated', 'ithemes-security' );

			if ( strlen( $dir_name ) <= 2 ) { //make sure the directory name is at least 2 characters

				$type    = 'error';
				$message = __( 'Please choose a directory name that is greater than 2 characters in length.', 'ithemes-security' );

			} elseif ( $dir_name === 'wp-content' ) {

				$type    = 'error';
				$message = __( 'You have not chosen a new name for wp-content. Nothing was saved.', 'ithemes-security' );

			} else { //process the name change

				$old_dir = WP_CONTENT_DIR;
				$new_dir = trailingslashit( ABSPATH ) . $dir_name;

				$rules = $this->build_wpconfig_rules( array(), $dir_name );

				$itsec_files->set_wpconfig( $rules );

				if ( $itsec_files->save_wpconfig() ) {

					if ( ! rename( $old_dir, $new_dir ) ) { //make sure renaming the directory was successful

						$type    = 'error';
						$message = __( 'WordPress was unable to rename your wp-content directory. Please check with your server administrator and try again.', 'ithemes-security' );

					}

				} else {

					$type    = 'error';
					$message = __( 'WordPress was unable to rename your wp-content directory. Please check with your server administrator and try again.', 'ithemes-security' );

				}

			}

			$this->settings = true; //this tells the form field that all went well.

			add_settings_error( 'itsec_admin_notices', esc_attr( 'settings_updated' ), $message, $type );

		}

		/**
		 * Start the Content Directory module
		 *
		 * @param  Ithemes_ITSEC_Core $core Instance of core plugin class
		 *
		 * @return ITSEC_content_directory                The instance of the ITSEC_Content_Directory_Admin class
		 */
		public static function start( $core ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}