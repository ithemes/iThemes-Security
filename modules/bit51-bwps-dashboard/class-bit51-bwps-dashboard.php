<?php
/**
 * Brand plugins with Bit51 sidebar items in the admin
 * 
 * @version 1.0
 */

if ( ! class_exists( 'Bit51_BWPS_Dashboard') ) {

	class Bit51_BWPS_Dashboard {

		private static $instance = null;

		private 
			$core,
			$feed,
			$paypal_code;

		private function __construct( $core ) {

			$this->core = $core;

			$this->paypal_code = 'V647NGJSBC882';
			$this->feed = 'http://bit51.com/feed';

			//add sharing reminder
			add_action( 'admin_init', array( $this, 'share_reminder' ) );

			//Add admin CSS
			add_action( $this->core->plugin->globals['plugin_hook'] . 'admin_init', array( $this, 'register_admin_css' ) );

			add_action( $this->core->plugin->globals['plugin_hook'] . '_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) );

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
					__( 'Support Better WP Security', $this->core->plugin->globals['plugin_hook'] ),
					array( $this, 'metabox_sidebar_publicize' ),
					$page,
					'side',
					'core'
				);

				add_meta_box( 
					'bit51_contact_info', 
					__( 'Bit51 on the Web', $this->core->plugin->globals['plugin_hook'] ),
					array( $this, 'metabox_sidebar_contact' ),
					$page,
					'side',
					'core'
				);

				add_meta_box( 
					'bit51_latest', 
					__( 'The Latest from Bit51', $this->core->plugin->globals['plugin_hook'] ),
					array( $this, 'metabox_sidebar_latest' ),
					$page,
					'side',
					'core'
				);

			}

		}

		/**
		 * Registers admin styles and handles other items required at admin_init
		 * 
		 * @return void
		 */
		public function register_admin_css() {
			wp_register_style( 'bwps_admin_dashboard', $this->core->plugin->globals['plugin_url'] . 'modules/bit51-bwps-dashboard/css/dashboard.css' );

			add_action( $this->core->plugin->globals['plugin_url'] . 'enqueue_admin_styles', array( $this, 'enqueue_admin_css' ) );
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
		
			global $blog_id; //get the current blog id
			
			$options = get_option( $this->core->plugin->globals['plugin_hook'] . '_data' );

			//Gotta make sure this is available when needed
			global $plugname;
			global $plughook;
			global $plugopts;
			$plugname = $this->core->plugin->globals['plugin_name'];
			$plughook = $this->core->plugin->globals['plugin_hook'];
			$plugopts = admin_url( 'options-general.php?page=' . $this->core->plugin->globals['plugin_hook'] );
			
			//display the notifcation if they haven't turned it off and they've been using the plugin at least 30 days
			if ( ! isset( $options['no-nag'] ) && isset( $options['activatestamp'] ) && $options['activatestamp'] < ( current_time( 'timestamp' ) - 2952000 ) ) {
			
				if ( ! function_exists( 'bwps_share_notice' ) ) {
			
					function bwps_share_notice() {
				
						global $plugname;
						global $plughook;
						global $plugopts;
					
					    echo '<div class="updated">' . PHP_EOL .
							'<p>' . __( 'It looks like you\'ve been enjoying', $plughook ) . ' ' . $plugname . ' ' . __( 'for at least 30 days. Would you please consider telling your friends about it?', $plughook ) . '</p> <p><input type="button" class="button " value="' . __( 'Rate it 5★\'s', $plughook ) . '" onclick="document.location.href=\'?' . $plughook . '_lets_rate=yes&_wpnonce=' .  wp_create_nonce( $plughook . '-reminder' ) . '\';">  <input type="button" class="button " value="' . __( 'Tell Your Followers', $plughook ) . '" onclick="document.location.href=\'?' . $plughook . '_lets_tweet=yes&_wpnonce=' .  wp_create_nonce( $plughook . '-reminder' ) . '\';">  <input type="button" class="button " value="' . __( 'Don\'t Bug Me Again', $plughook ) . '" onclick="document.location.href=\'?' . $plughook . '_share_nag=off&_wpnonce=' .  wp_create_nonce( $plughook . '-reminder' ) . '\';"></p>' . PHP_EOL .
					    	'</div>';
				    
					}
				
				}
				
				add_action( 'admin_notices', 'bwps_share_notice' ); //register notification
				
			}
			
			//if they've clicked a button hide the notice
			if ( ( isset( $_GET[$this->core->plugin->globals['plugin_hook'] . '_share_nag'] ) || isset( $_GET[$this->core->plugin->globals['plugin_hook'] . '_lets_rate'] ) || isset( $_GET[$this->core->plugin->globals['plugin_hook'] . '_lets_tweet'] ) ) && wp_verify_nonce( $_REQUEST['_wpnonce'], $this->core->plugin->globals['plugin_hook'] . '-reminder' ) ) {

				$options = get_option( $this->core->plugin->globals['plugin_hook'] . '_data' );
				$options['no-nag'] = 1;
				update_option( $this->core->plugin->globals['plugin_hook'] . '_data', $options );
				remove_action( 'admin_notices', 'bwps_share_notice' );
				
				//Go to the WordPress page to let them rate it.
				if ( isset( $_GET[$this->core->plugin->globals['plugin_hook'] . '_lets_rate'] ) ) {
					wp_redirect( $this->core->plugin->globals['plugin_homepage'], '302' );
				}
				
				//Compose a Tweet
				if ( isset( $_GET[$this->core->plugin->globals['plugin_hook'] . '_lets_tweet'] ) ) {
					wp_redirect( 'http://twitter.com/home?status=' . urlencode( 'I use ' . $this->core->plugin->globals['plugin_name'] . ' for WordPress by @Bit51 and you should too - ' . $this->core->plugin->globals['plugin_homepage'] ) , '302' );
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
			$content .= '<li class="facebook"><a href="https://www.facebook.com/bit51" target="_blank">' . __( 'Like Bit51 on Facebook', $this->core->plugin->globals['plugin_hook'] ) . '</a></li>';
			$content .= '<li class="twitter"><a href="http://twitter.com/Bit51" target="_blank">' . __( 'Follow Bit51 on Twitter', $this->core->plugin->globals['plugin_hook'] ) . '</a></li>';
			$content .= '<li class="google"><a href="https://plus.google.com/b/111800087192533843819" target="_blank">' . __( 'Circle Bit51 on Google+', $this->core->plugin->globals['plugin_hook'] ) . '</a></li>';
			
			$content .= '<li class="subscribe"><a href="http://bit51.com/subscribe" target="_blank">' . __( 'Subscribe with RSS or Email', $this->core->plugin->globals['plugin_hook'] ) . '</a></li>';
			
			$content .= '</ul>';

			echo $content;

		}

		/**
		 * Build and echo the "share this" sidebar metabox
		 * 
		 * @return void
		 */
		public function metabox_sidebar_publicize() {

			$content = __( 'Have you found this plugin useful? Please help support it\'s continued development with a donation of $20, $50, or even $100.', $this->core->plugin->globals['plugin_hook'] );
			$content .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="' . $this->paypal_code . '"><input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"><img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1"></form>';
			$content .= '<p>' . __( 'Short on funds?', $this->core->plugin->globals['plugin_hook'] ) . '</p>';
			$content .= '<ul>';
			$content .= '<li><a href="' . $this->core->plugin->globals['wordpress_page'] . '" target="_blank">' . __( 'Rate', $this->core->plugin->globals['plugin_hook'] ) . ' ' . $this->core->plugin->globals['plugin_name'] . __( ' 5★\'s on WordPress.org', $this->core->plugin->globals['plugin_hook'] ) . '</a></li>';
			$content .= '<li>' . __( 'Talk about it on your site and link back to the ', $this->core->plugin->globals['plugin_hook'] ) . '<a href="' . $this->homepage . '" target="_blank">' . __( 'plugin page.', $this->core->plugin->globals['plugin_hook'] ) . '</a></li>';
			$content .= '<li><a href="http://twitter.com/home?status=' . urlencode( 'I use ' . $this->core->plugin->globals['plugin_name'] . ' for WordPress by @bit51 and you should too - ' . $this->core->plugin->globals['plugin_homepage'] ) . '" target="_blank">' . __( 'Tweet about it. ', $this->core->plugin->globals['plugin_hook'] ) . '</a></li>';
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
			
			    	$content .= '<li class="bit51">' . __( 'No news items, feed might be broken...', $this->core->plugin->globals['plugin_hook'] ) . '</li>';
			    
				} else {
			
					foreach ( $feeditems as $item ) {
						
						$url = preg_replace( '/#.*/', '', esc_url( $item->get_permalink(), $protocolls = null, 'display' ) );
						
						$content .= '<li class="bit51"><a class="rsswidget" href="' . $url . '" target="_blank">'. esc_html( $item->get_title() ) .'</a></li>';
						
					}
					
				}	
									
				$content .= '</ul>'; //end list

			} else {
				$content = __( 'It appears as if the feed is currently down. Please try again later', $this->core->plugin->globals['plugin_hook'] );
			}

			echo $content;

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