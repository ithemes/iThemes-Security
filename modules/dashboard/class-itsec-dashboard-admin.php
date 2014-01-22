<?php
/**
 * Brand plugins with iThemes sidebar items in the admin
 *
 * @version 1.0
 */

if ( ! class_exists( 'ITSEC_Dashboard_Admin' ) ) {

	class ITSEC_Dashboard_Admin {

		private static $instance = null;

		private
			$feed,
			$paypal_id;

		private function __construct() {

			$this->paypal_id = 'V647NGJSBC882'; //Donation ID for paypal
			$this->feed      = 'http://ithemes.com/blog/feed/'; //Feed location for sidebar

			//add sharing reminder
			add_action( 'admin_init', array( $this, 'share_reminder' ) );

			//Add admin CSS
			add_action( 'itsec_admin_init', array( $this, 'register_admin_css' ) );

			add_action( 'itsec_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) );

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
					'itsec_status_feed',
					__( 'Security Status Feed', 'ithemes-security' ),
					array( $this, 'metabox_status_feed' ),
					$page,
					'priority_side',
					'core'
				);

			}

			add_meta_box(
				'itsec_status',
				__( 'Security Status', 'ithemes-security' ),
				array( $this, 'metabox_normal_status' ),
				'toplevel_page_itsec',
				'normal',
				'core'
			);

		}

		/**
		 * Enqueue CSS for iThemes Security dashboard
		 *
		 * @return void
		 */
		public function enqueue_admin_css() {

			wp_enqueue_style( 'itsec_admin_dashboard' );

		}

		/**
		 * Display security status
		 *
		 * @return void
		 */
		public function metabox_normal_status() {

			$statuses = array(
				'safe-high'   => array(),
				'high'        => array(),
				'safe-medium' => array(),
				'medium'      => array(),
				'safe-low'    => array(),
				'low'         => array(),
			);

			$statuses = apply_filters( 'itsec_add_dashboard_status', $statuses );

			if ( isset ( $statuses['high'][0] ) ) {

				printf( '<h2>%s</h2>', __( 'High Priority', 'ithemes-security' ) );
				_e( 'These are items that should be secured immediately.', 'ithemes-security' );

				echo '<ul class="statuslist high-priority">';

				if ( isset ( $statuses['high'] ) ) {

					foreach ( $statuses['high'] as $status ) {

						printf( '<li><p>%s</p><div class="itsec_status_action"><a class="button-primary" href="%s">Fix it</a></div></li>', $status['text'], $status['link'] );

					}

				}

				echo '</ul>';

			}

			if ( isset ( $statuses['medium'][0] ) ) {

				printf( '<h2>%s</h2>', __( 'Medium Priority', 'ithemes-security' ) );
				_e( 'These are items that should be secured if possible however they are not critical to the overall security of your site.', 'ithemes-security' );

				echo '<ul class="statuslist medium-priority">';

				if ( isset ( $statuses['medium'] ) ) {

					foreach ( $statuses['medium'] as $status ) {

						printf( '<li><p>%s</p><div class="itsec_status_action"><a class="button-primary" href="%s">Fix it</a></div></li>', $status['text'], $status['link'] );

					}

				}

				echo '</ul>';

			}

			if ( isset ( $statuses['low'][0] ) ) {

				printf( '<h2>%s</h2>', __( 'Low Priority', 'ithemes-security' ) );
				_e( 'These are items that should be secured if, and only if, your plugins or theme do not conflict with their use.', 'ithemes-security' );

				echo '<ul class="statuslist low-priority">';

				if ( isset ( $statuses['low'] ) ) {

					foreach ( $statuses['low'] as $status ) {

						printf( '<li><p>%s</p><div class="itsec_status_action"><a class="button-secondary" href="%s">Fix it</a></div></li>', $status['text'], $status['link'] );

					}

				}

				echo '</ul>';

			}

			if ( isset ( $statuses['safe-high'] ) || isset ( $statuses['safe-medium'] ) || isset ( $statuses['safe-low'] ) ) {

				printf( '<h2>%s</h2>', __( 'Completed', 'ithemes-security' ) );
				_e( 'These are items that you have successfuly secured.', 'ithemes-security' );

				echo '<ul class="statuslist completed">';

				if ( isset ( $statuses['safe-high'] ) ) {

					foreach ( $statuses['safe-high'] as $status ) {

						printf( '<li><p>%s</p></li>', $status['text'] );

					}

				}

				if ( isset ( $statuses['safe-medium'] ) ) {

					foreach ( $statuses['safe-medium'] as $status ) {

						printf( '<li><p>%s</p></li>', $status['text'] );

					}

				}

				if ( isset ( $statuses['safe-low'] ) ) {

					foreach ( $statuses['safe-low'] as $status ) {

						printf( '<li><p>%s</p></li>', $status['text'] );

					}

				}

				echo '</ul>';

			}

		}

		/**
		 * Build and echo the content sidebar metabox
		 *
		 * @return void
		 */
		public function metabox_sidebar_contact() {

			$content = '<ul>';
			$content .= '<li class="facebook"><a href="https://www.facebook.com/ithemes" target="_blank">' . __( 'Like iThemes on Facebook', 'ithemes-security' ) . '</a></li>';
			$content .= '<li class="twitter"><a href="http://twitter.com/ithemes" target="_blank">' . __( 'Follow iThemes on Twitter', 'ithemes-security' ) . '</a></li>';
			$content .= '<li class="google"><a href="https://plus.google.com/b/100771929727041515430" target="_blank">' . __( 'Circle iThemes on Google+', 'ithemes-security' ) . '</a></li>';

			$content .= '<li class="subscribe"><a href="http://ithemes.com/subscribe" target="_blank">' . __( 'Subscribe to iThemes Updates', 'ithemes-security' ) . '</a></li>';

			$content .= '</ul>';

			echo $content;

		}

		/**
		 * Display the Security Status feed
		 *
		 * @return void
		 */
		public function metabox_status_feed() {

			// Intro Text
			$content = '<div class="itsec-status-feed-item intro">';
			$content .= '<p>';
			$content .= __( 'We\'ve analyzed your site and determined that these simple changes will have the biggest impact in keeping your site safe and secure. Do these things first, then use the tabs to explore more advanced features.', 'ithemes-security' );
			$content .= '</p>';
			$content .= '</div>';

			// Begin Feed Items
			$content .= '<div class="itsec-status-feed-item completed">';
			$content .= '<div class="itsec-status-feed-completed-message">';
			$content .= '<p>User ID 1 changed!</p>';
			$content .= '</div>';
			$content .= '<div class="itsec-status-feed-actions">';
			$content .= '<p><input class="button-secondary" name="submit" type="submit" value="Undo"></p>';
			$content .= '</div>';
			$content .= '</div>';

			$content .= '<div class="itsec-status-feed-item completed">';
			$content .= '<div class="itsec-status-feed-completed-message">';
			$content .= '<p>Admin Username Changed!</p>';
			$content .= '</div>';
			$content .= '<div class="itsec-status-feed-actions">';
			$content .= '<p><input class="button-secondary" name="submit" type="submit" value="Undo"></p>';
			$content .= '</div>';
			$content .= '</div>';

			$content .= '<div class="itsec-status-feed-item medium-priority">';
			$content .= '<p>You should hide the WordPress admin area.</p>';
			$content .= '<div class="itsec-status-feed-actions">';
			$content .= '<p class="itsec-why"><a href="#">Why Change This?</a></p>';
			$content .= '<p><input class="button-primary" name="submit" type="submit" value="Fix This"></p>';
			$content .= '</div>';
			$content .= '</div>';

			$content .= '<div class="itsec-status-feed-item medium-priority">';
			$content .= '<p>You shouldn\'t let non-administrators see all available updates.</p>';
			$content .= '<div class="itsec-status-feed-actions">';
			$content .= '<p class="itsec-why"><a href="#">Why Change This?</a></p>';
			$content .= '<p><input class="button-primary" name="submit" type="submit" value="Fix This"></p>';
			$content .= '</div>';
			$content .= '</div>';

			$content .= '<div class="itsec-status-feed-item notice">';
			$content .= '<p>Your site had 116 instances of 404 errors last week. This could be evidence of an attempted attack. Check your logs.</p>';
			$content .= '<div class="itsec-status-feed-actions">';
			$content .= '<p class="itsec-why"><a href="#">Why Change This?</a></p>';
			$content .= '<p><input class="button-primary" name="submit" type="submit" value="Fix This"></p>';
			$content .= '</div>';
			$content .= '</div>';

			$content .= '<div class="itsec-status-feed-item high-priority">';
			$content .= '<p>Change username "admin" to something else.</p>';
			$content .= '<div class="itsec-status-feed-actions">';
			$content .= '<p class="itsec-why"><a href="#">Why Change This?</a></p>';
			$content .= '<p><input class="button-primary" name="submit" type="submit" value="Fix This"></p>';
			$content .= '</div>';
			$content .= '</div>';

			$content .= '<div class="itsec-status-feed-item ithemes-message">';
			$content .= '<p>A friendly message from iThemes directing you to a blog post or new feature or something of the like.</p>';
			$content .= '<div class="itsec-status-feed-actions">';
			$content .= '<p><a class="button-primary">Check it out!</a></p>';
			$content .= '</div>';
			$content .= '</div>';

			// Bottom Interchangeable Ad
			$content .= '<div class="itsec-status-feed-item closing">';
			$content .= '<p>';
			//$content .= 		;
			$content .= '</p>';
			$content .= '</div>';

			echo $content;
		}

		/**
		 * Display a list of latest posts from iThemes
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

					$content .= '<li class="ithemes">' . __( 'I couldn\'t find any updates. If the problem persists please contact the feed owner', 'ithemes-security' ) . '</li>';

				} else {

					foreach ( $feeditems as $item ) {

						$url = preg_replace( '/#.*/', '', esc_url( $item->get_permalink(), $protocolls = null, 'display' ) );

						$content .= '<li class="ithemes"><a class="rsswidget" href="' . $url . '" target="_blank">' . esc_html( $item->get_title() ) . '</a></li>';

					}

				}

				$content .= '</ul>'; //end list

			} else {
				$content = __( 'It appears as if the feed is currently down. Please try again later', 'ithemes-security' );
			}

			echo $content;

		}

		/**
		 * Build and echo the "share this" sidebar metabox
		 *
		 * @return void
		 */
		public function metabox_sidebar_publicize() {

			global $itsec_globals;

			$content = __( 'Have you found this plugin useful? Please help support it\'s continued development with a donation of $20, $50, or even $100.', 'ithemes-security' );
			$content .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="' . $this->paypal_id . '"><input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"><img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1"></form>';
			$content .= '<p>' . __( 'Short on funds?', 'ithemes-security' ) . '</p>';
			$content .= '<ul>';
			$content .= '<li><a href="http://wordpress.org/extend/plugins/ithemes-security/" target="_blank">' . sprintf( __( 'Rate %s 5★\'s on WordPress.org', 'ithemes-security' ), $itsec_globals['plugin_name'] ) . '</a></li>';
			$content .= '<li>' . sprintf( __( 'Talk about it on your site and link back to the %splugin page', 'ithemes-security' ), '<a href="http://ithemes.com" target="_blank">' ) . '</a></li>';
			$content .= '<li><a href="http://twitter.com/home?status=' . urlencode( sprintf( __( 'I use %s for WordPress by %s and you should too - %s' ), $itsec_globals['plugin_name'], '@ithemes', 'http://ithemes.com' ) ) . '" target="_blank">' . __( 'Tweet about it. ', 'ithemes-security' ) . '</a></li>';
			$content .= '<li><a href="http://twitter.com/home?status=' . urlencode( sprintf( __( 'I use %s for WordPress by %s and you should too - %s' ), $itsec_globals['plugin_name'], '@ithemes', 'http://ithemes.com' ) ) . '" target="_blank">' . __( 'Tweet about it. ', 'ithemes-security' ) . '</a></li>';
			$content .= '</ul>';

			echo $content;

		}

		/**
		 * Registers admin styles and handles other items required at admin_init
		 *
		 * @return void
		 */
		public function register_admin_css() {

			global $itsec_globals;

			wp_register_style( 'itsec_admin_dashboard', $itsec_globals['plugin_url'] . 'modules/dashboard/css/dashboard.css' );

			add_action( $itsec_globals['plugin_url'] . 'enqueue_admin_styles', array( $this, 'enqueue_admin_css' ) );

		}

		/**
		 * Display (and hide) donation reminder
		 *
		 * Adds reminder to donate or otherwise support on dashboard
		 *
		 * @return void
		 **/
		public function share_reminder() {

			global $blog_id, $itsec_globals;

			$options = get_site_option( 'itsec_data' );

			//Gotta make sure this is available when needed
			global $plugname;
			global $plughook;
			global $plugopts;
			$plugname = $itsec_globals['plugin_name'];
			$plughook = 'itsec';
			$plugopts = admin_url( 'options-general.php?page=itsec' );

			//display the notifcation if they haven't turned it off and they've been using the plugin at least 30 days
			if ( ! isset( $options['no-nag'] ) && isset( $options['activatestamp'] ) && $options['activatestamp'] < ( current_time( 'timestamp' ) - 2952000 ) ) {

				if ( ! function_exists( 'itsec_share_notice' ) ) {

					function itsec_share_notice() {

						global $plugname, $plughook, $plugopts;

						printf( '<div class="updated"><p>%s %s %s</p> <p><input type="button" class="button " value="%s" onclick="document.location.href=\'?%s_lets_rate=yes&_wpnonce=%s\';">  <input type="button" class="button " value="%s" onclick="document.location.href=\'?%s_lets_tweet=yes&_wpnonce=%s\';">  <input type="button" class="button " value="%s" onclick="document.location.href=\'?%s_share_nag=off&_wpnonce=%s\';"></p></div>', __( 'It looks like you\'ve been enjoying', 'ithemes-security' ), $plugname, __( 'for at least 30 days. Would you please consider telling your friends about it?', 'ithemes-security' ), __( 'Rate it 5★\'s', 'ithemes-security' ), $plughook, wp_create_nonce( $plughook . '-reminder' ), __( 'Tell Your Followers', 'ithemes-security' ), $plughook, wp_create_nonce( $plughook . '-reminder' ), __( 'Don\'t Bug Me Again', 'ithemes-security' ), $plughook, wp_create_nonce( $plughook . '-reminder' ) );

					}

				}

				add_action( 'admin_notices', 'itsec_share_notice' ); //register notification

			}

			//if they've clicked a button hide the notice
			if ( ( isset( $_GET['itsec_share_nag'] ) || isset( $_GET['itsec_lets_rate'] ) || isset( $_GET['itsec_lets_tweet'] ) ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'itsec-reminder' ) ) {

				$options           = get_site_option( 'itsec_data' );
				$options['no-nag'] = 1;
				update_site_option( 'itsec_data', $options );
				remove_action( 'admin_notices', 'itsec_share_notice' );

				//Go to the WordPress page to let them rate it.
				if ( isset( $_GET['itsec_lets_rate'] ) ) {
					wp_redirect( 'http://wordpress.org/extend/plugins/ithemes-security/', '302' );
				}

				//Compose a Tweet
				if ( isset( $_GET['itsec_lets_tweet'] ) ) {
					wp_redirect( 'http://twitter.com/home?status=' . urlencode( 'I use ' . $itsec_globals['plugin_name'] . ' for WordPress by @iThemes and you should too - ' . 'http://ithemes.com' ), '302' );
				}

			}

		}

		/**
		 * Start the ITSEC Dashboard module
		 *
		 * @return ITSEC_Dashboard_Admin            The instance of the ITSEC_Dashboard_Admin class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}