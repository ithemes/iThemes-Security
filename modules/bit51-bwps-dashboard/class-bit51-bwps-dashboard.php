<?php
/**
 * Brand plugins with Bit51 sidebar items in the admin
 * 
 * @version 1.0
 */

if ( ! class_exists( 'Bit51_BWPS_Dashboard' ) ) {

	class Bit51_BWPS_Dashboard {

		private static $instance = null;

		private 
			$core,
			$feed,
			$paypal_code;

		private function __construct( $core ) {

			global $bwps_globals;

			$this->core = $core;

			$this->paypal_code = 'V647NGJSBC882';
			$this->feed = 'http://bit51.com/feed';

			//add sharing reminder
			add_action( 'admin_init', array( $this, 'share_reminder' ) );

			//Add admin CSS
			add_action( $bwps_globals['plugin_hook'] . 'admin_init', array( $this, 'register_admin_css' ) );

			add_action( $bwps_globals['plugin_hook'] . '_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) );

		}

		/**
		 * Add meta boxes to primary options pages
		 * 
		 * @param array $available_pages array of available page_hooks
		 */
		function add_admin_meta_boxes( $available_pages ) {

			foreach ( $available_pages as $page ) {
				
				//add metaboxes
				add_meta_box( 
					'bit51_publicize', 
					__( 'Support Better WP Security', 'better_wp_security' ),
					array( $this, 'metabox_sidebar_publicize' ),
					$page,
					'side',
					'core'
				);

				add_meta_box( 
					'bit51_contact_info', 
					__( 'Bit51 on the Web', 'better_wp_security' ),
					array( $this, 'metabox_sidebar_contact' ),
					$page,
					'side',
					'core'
				);

				add_meta_box( 
					'bit51_latest', 
					__( 'The Latest from Bit51', 'better_wp_security' ),
					array( $this, 'metabox_sidebar_latest' ),
					$page,
					'side',
					'core'
				);

			}

			add_meta_box( 
				'bwps_status', 
				__( 'Security Status', 'better_wp_security' ),
				array( $this, 'metabox_normal_status' ),
				'toplevel_page_bwps',
				'normal',
				'core'
			);

			add_meta_box( 
				'bwps_system_info', 
				__( 'System Information', 'better_wp_security' ),
				array( $this, 'metabox_normal_system' ),
				'toplevel_page_bwps',
				'normal',
				'core'
			);

		}

		/**
		 * Registers admin styles and handles other items required at admin_init
		 * 
		 * @return void
		 */
		public function register_admin_css() {

			global $bwps_globals;

			wp_register_style( 'bwps_admin_dashboard', $bwps_globals['plugin_url'] . 'modules/bit51-bwps-dashboard/css/dashboard.css' );

			add_action( $bwps_globals['plugin_url'] . 'enqueue_admin_styles', array( $this, 'enqueue_admin_css' ) );

		}

		public function enqueue_admin_css() {
			wp_enqueue_style( 'bwps_admin_dashboard' );
		}

		/**
		 * Display (and hide) donation reminder
		 *
		 * Adds reminder to donate or otherwise support on dashboard
		 *
		 * @return void
		 **/
		function share_reminder() {
		
			global $blog_id, $bwps_globals;
			
			$options = get_site_option( $bwps_globals['plugin_hook'] . '_data' );

			//Gotta make sure this is available when needed
			global $plugname;
			global $plughook;
			global $plugopts;
			$plugname = $bwps_globals['plugin_name'];
			$plughook = $bwps_globals['plugin_hook'];
			$plugopts = admin_url( 'options-general.php?page=' . $bwps_globals['plugin_hook'] );
			
			//display the notifcation if they haven't turned it off and they've been using the plugin at least 30 days
			if ( ! isset( $options['no-nag'] ) && isset( $options['activatestamp'] ) && $options['activatestamp'] < ( current_time( 'timestamp' ) - 2952000 ) ) {
			
				if ( ! function_exists( 'bwps_share_notice' ) ) {
			
					function bwps_share_notice() {
				
						global $plugname, $plughook, $plugopts;
					
					    printf( '<div class="updated"><p>%s %s %s</p> <p><input type="button" class="button " value="%s" onclick="document.location.href=\'?%s_lets_rate=yes&_wpnonce=%s\';">  <input type="button" class="button " value="%s" onclick="document.location.href=\'?%s_lets_tweet=yes&_wpnonce=%s\';">  <input type="button" class="button " value="%s" onclick="document.location.href=\'?%s_share_nag=off&_wpnonce=%s\';"></p></div>',
					    	__( 'It looks like you\'ve been enjoying', 'better_wp_security' ),
					    	$plugname,
					    	__( 'for at least 30 days. Would you please consider telling your friends about it?', 'better_wp_security' ),
					    	__( 'Rate it 5★\'s', 'better_wp_security' ),
					    	$plughook,
					    	wp_create_nonce( $plughook . '-reminder' ),
					    	__( 'Tell Your Followers', 'better_wp_security' ),
					    	$plughook,
					    	wp_create_nonce( $plughook . '-reminder' ),
					    	__( 'Don\'t Bug Me Again', 'better_wp_security' ),
					    	$plughook,
					    	wp_create_nonce( $plughook . '-reminder' )
					    );
				    
					}
				
				}
				
				add_action( 'admin_notices', 'bwps_share_notice' ); //register notification
				
			}
			
			//if they've clicked a button hide the notice
			if ( ( isset( $_GET[$bwps_globals['plugin_hook'] . '_share_nag'] ) || isset( $_GET[$bwps_globals['plugin_hook'] . '_lets_rate'] ) || isset( $_GET[$bwps_globals['plugin_hook'] . '_lets_tweet'] ) ) && wp_verify_nonce( $_REQUEST['_wpnonce'], $bwps_globals['plugin_hook'] . '-reminder' ) ) {

				$options = get_site_option( $bwps_globals['plugin_hook'] . '_data' );
				$options['no-nag'] = 1;
				update_site_option( $bwps_globals['plugin_hook'] . '_data', $options );
				remove_action( 'admin_notices', 'bwps_share_notice' );
				
				//Go to the WordPress page to let them rate it.
				if ( isset( $_GET[$bwps_globals['plugin_hook'] . '_lets_rate'] ) ) {
					wp_redirect( $bwps_globals['wordpress_page'], '302' );
				}
				
				//Compose a Tweet
				if ( isset( $_GET[$bwps_globals['plugin_hook'] . '_lets_tweet'] ) ) {
					wp_redirect( 'http://twitter.com/home?status=' . urlencode( 'I use ' . $bwps_globals['plugin_name'] . ' for WordPress by @Bit51 and you should too - ' . $bwps_globals['plugin_homepage'] ) , '302' );
				}
				
			}
			
		}

		/**
		 * Build and echo the content sidebar metabox
		 * 
		 * @return void
		 */
		public function metabox_sidebar_contact() {

			$content = '<ul>';
			$content .= '<li class="facebook"><a href="https://www.facebook.com/bit51" target="_blank">' . __( 'Like Bit51 on Facebook', 'better_wp_security' ) . '</a></li>';
			$content .= '<li class="twitter"><a href="http://twitter.com/Bit51" target="_blank">' . __( 'Follow Bit51 on Twitter', 'better_wp_security' ) . '</a></li>';
			$content .= '<li class="google"><a href="https://plus.google.com/b/111800087192533843819" target="_blank">' . __( 'Circle Bit51 on Google+', 'better_wp_security' ) . '</a></li>';
			
			$content .= '<li class="subscribe"><a href="http://bit51.com/subscribe" target="_blank">' . __( 'Subscribe to Bit51 Updates', 'better_wp_security' ) . '</a></li>';
			
			$content .= '</ul>';

			echo $content;

		}

		/**
		 * Build and echo the "share this" sidebar metabox
		 * 
		 * @return void
		 */
		public function metabox_sidebar_publicize() {

			global $bwps_globals;

			$content = __( 'Have you found this plugin useful? Please help support it\'s continued development with a donation of $20, $50, or even $100.', 'better_wp_security' );
			$content .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="' . $this->paypal_code . '"><input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"><img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1"></form>';
			$content .= '<p>' . __( 'Short on funds?', 'better_wp_security' ) . '</p>';
			$content .= '<ul>';
			$content .= '<li><a href="' . $bwps_globals['wordpress_page'] . '" target="_blank">' . sprintf( __( 'Rate %s 5★\'s on WordPress.org', 'better_wp_security' ), $bwps_globals['plugin_name'] ) . '</a></li>';
			$content .= '<li>' . sprintf( __( 'Talk about it on your site and link back to the %splugin page', 'better_wp_security' ), '<a href="' . $bwps_globals['plugin_homepage'] . '" target="_blank">' ) . '</a></li>';
			$content .= '<li><a href="http://twitter.com/home?status=' . urlencode( sprintf( __( 'I use %s for WordPress by %s and you should too - %s' ), $bwps_globals['plugin_name'], '@bit51', $bwps_globals['plugin_homepage'] ) ) . '" target="_blank">' . __( 'Tweet about it. ', 'better_wp_security' ) . '</a></li>';
			$content .= '</ul>';

			echo $content;

		}

		/**
		 * Display a list of latest posts from Bit51
		 * 
		 * @return void
		 */
		public function metabox_sidebar_latest() {

			include_once( ABSPATH . WPINC . '/feed.php' ); //load WordPress feed info
			
			$feed = fetch_feed( $this->feed ); //get the feed

			if ( ! isset( $feed->errors ) ) {

				$feeditems = $feed->get_items( 0, $feed->get_item_quantity( 5 ) ); //narrow feed to last 5 items
			
				$content = '<ul>'; //start list
			
				if ( ! $feeditems ) {
			
			    	$content .= '<li class="bit51">' . __( 'I couldn\'t find any updates. If the problem persists please contact the feed owner', 'better_wp_security' ) . '</li>';
			    
				} else {
			
					foreach ( $feeditems as $item ) {
						
						$url = preg_replace( '/#.*/', '', esc_url( $item->get_permalink(), $protocolls = null, 'display' ) );
						
						$content .= '<li class="bit51"><a class="rsswidget" href="' . $url . '" target="_blank">'. esc_html( $item->get_title() ) .'</a></li>';
						
					}
					
				}	
									
				$content .= '</ul>'; //end list

			} else {
				$content = __( 'It appears as if the feed is currently down. Please try again later', 'better_wp_security' );
			}

			echo $content;

		}

		/**
		 * Display security status
		 * 
		 * @return void
		 */
		public function metabox_normal_status() {

			global $bwps_globals;

			$statuses = array();

			$statuses = apply_filters( $bwps_globals['plugin_hook'] . '_add_dashboard_status', $statuses );

			if ( isset ( $statuses['high'] ) || isset( $statuses['safe-high'] ) ) {

				printf( '<h2>%s</h2>', __( 'High Priority', 'better_wp_security' ) );
				_e( 'These are items that should be secured immediately.', 'better_wp_security' );

				echo '<ol class="statuslist">';

				if ( isset ( $statuses['high'] ) ) {

					foreach ( $statuses['high'] as $status ) {

						printf( '<li> <strong><a  style="color: red;" href="%s">%s</a></strong></li>', $status['link'], $status['text'] );

					}

				} elseif ( isset ( $statuses['safe-high'] ) ) {

					foreach ( $statuses['safe-high'] as $status ) {

						printf( '<li> <a  style="color: green;" href="%s">%s</a></li>', $status['link'], $status['text'] );

					}

				}

				echo '</ol>';

			}

			if ( isset ( $statuses['medium'] ) || isset( $statuses['safe-medium'] ) ) {

				printf( '<h2>%s</h2>', __( 'Medium Priority', 'better_wp_security' ) );
				_e( 'These are items that should be secured if possible however they are not critical to the overall security of your site.', 'better_wp_security' );

				echo '<ol class="statuslist">';

				if ( isset ( $statuses['medium'] ) ) {

					foreach ( $statuses['medium'] as $status ) {

						printf( '<li> <strong><a  style="color: orange;" href="%s">%s</a></strong></li>', $status['link'], $status['text'] );

					}

				} elseif ( isset ( $statuses['safe-medium'] ) ) {

					foreach ( $statuses['safe-medium'] as $status ) {

						printf( '<li> <a  style="color: green;" href="%s">%s</a></li>', $status['link'], $status['text'] );

					}

				}

				echo '</ol>';

			}

			if ( isset ( $statuses['low'] ) || isset( $statuses['safe-low'] ) ) {

				printf( '<h2>%s</h2>', __( 'Low Priority', 'better_wp_security' ) );
				_e( 'These are items that should be secured if, and only if, your plugins or theme do not conflict with their use.', 'better_wp_security' );

				echo '<ol class="statuslist">';

				if ( isset ( $statuses['low'] ) ) {

					foreach ( $statuses['low'] as $status ) {

						printf( '<li> <strong><a  style="color: blue;" href="%s">%s</a></strong></li>', $status['link'], $status['text'] );

					}

				} elseif ( isset ( $statuses['safe-low'] ) ) {

					foreach ( $statuses['safe-low'] as $status ) {

						printf( '<li> <a  style="color: green;" href="%s">%s</a></li>', $status['link'], $status['text'] );

					}

				}

				echo '</ol>';

			}

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
		 * Start the BWPS Dashboard module
		 * 
		 * @param  Bit51_BWPS_Core    $core     	Instance of core plugin class
		 * @return Bit51_BWPS_Dashboard 			The instance of the Bit51_BWPS_Dashboard class
		 */
		public static function start( $core ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}