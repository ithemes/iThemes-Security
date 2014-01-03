<?php
/**
 * Brand plugins with iThemes sidebar items in the admin
 *
 * @version 1.0
 */

if ( ! class_exists( 'Ithemes_BWPS_Dashboard_Admin' ) ) {

	class Ithemes_BWPS_Dashboard_Admin {

		private static $instance = NULL;

		private
			$feed,
			$paypal_id;

		private function __construct() {

			$this->paypal_id = 'V647NGJSBC882'; //Donation ID for paypal
			$this->feed        = 'http://ithemes.com/blog/feed/'; //Feed location for sidebar

			//add sharing reminder
			add_action( 'admin_init', array( $this, 'share_reminder' ) );

			//Add admin CSS
			add_action( 'bwps_admin_init', array( $this, 'register_admin_css' ) );

			add_action( 'bwps_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) );

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
					'bwps_status_feed',
					__( 'Security Status Feed', 'better_wp_security' ),
					array( $this, 'metabox_status_feed' ),
					$page,
					'priority_side',
					'core'
				);

				add_meta_box(
					'ithemes_publicize',
					__( 'Support Better WP Security', 'better_wp_security' ),
					array( $this, 'metabox_sidebar_publicize' ),
					$page,
					'side',
					'core'
				);

				add_meta_box(
					'ithemes_contact_info',
					__( 'iThemes on the Web', 'better_wp_security' ),
					array( $this, 'metabox_sidebar_contact' ),
					$page,
					'side',
					'core'
				);

				add_meta_box(
					'ithemes_latest',
					__( 'The Latest from iThemes', 'better_wp_security' ),
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

		}

		/**
		 * Registers admin styles and handles other items required at admin_init
		 *
		 * @return void
		 */
		public function register_admin_css() {

			global $bwps_globals;

			wp_register_style( 'bwps_admin_dashboard', $bwps_globals['plugin_url'] . 'modules/ithemes-bwps-dashboard/css/dashboard.css' );

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

			$options = get_site_option( 'bwps_data' );

			//Gotta make sure this is available when needed
			global $plugname;
			global $plughook;
			global $plugopts;
			$plugname = $bwps_globals['plugin_name'];
			$plughook = 'bwps';
			$plugopts = admin_url( 'options-general.php?page=bwps' );

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
			if ( ( isset( $_GET['bwps_share_nag'] ) || isset( $_GET['bwps_lets_rate'] ) || isset( $_GET['bwps_lets_tweet'] ) ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'bwps-reminder' ) ) {

				$options           = get_site_option( 'bwps_data' );
				$options['no-nag'] = 1;
				update_site_option( 'bwps_data', $options );
				remove_action( 'admin_notices', 'bwps_share_notice' );

				//Go to the WordPress page to let them rate it.
				if ( isset( $_GET['bwps_lets_rate'] ) ) {
					wp_redirect( 'http://wordpress.org/extend/plugins/better-wp-security/', '302' );
				}

				//Compose a Tweet
				if ( isset( $_GET['bwps_lets_tweet'] ) ) {
					wp_redirect( 'http://twitter.com/home?status=' . urlencode( 'I use ' . $bwps_globals['plugin_name'] . ' for WordPress by @iThemes and you should too - ' . 'http://ithemes.com' ), '302' );
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
			$content .= '<li class="facebook"><a href="https://www.facebook.com/ithemes" target="_blank">' . __( 'Like iThemes on Facebook', 'better_wp_security' ) . '</a></li>';
			$content .= '<li class="twitter"><a href="http://twitter.com/ithemes" target="_blank">' . __( 'Follow iThemes on Twitter', 'better_wp_security' ) . '</a></li>';
			$content .= '<li class="google"><a href="https://plus.google.com/b/100771929727041515430" target="_blank">' . __( 'Circle iThemes on Google+', 'better_wp_security' ) . '</a></li>';

			$content .= '<li class="subscribe"><a href="http://ithemes.com/subscribe" target="_blank">' . __( 'Subscribe to iThemes Updates', 'better_wp_security' ) . '</a></li>';

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
			$content .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="' . $this->paypal_id . '"><input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"><img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1"></form>';
			$content .= '<p>' . __( 'Short on funds?', 'better_wp_security' ) . '</p>';
			$content .= '<ul>';
			$content .= '<li><a href="http://wordpress.org/extend/plugins/better-wp-security/" target="_blank">' . sprintf( __( 'Rate %s 5★\'s on WordPress.org', 'better_wp_security' ), $bwps_globals['plugin_name'] ) . '</a></li>';
			$content .= '<li>' . sprintf( __( 'Talk about it on your site and link back to the %splugin page', 'better_wp_security' ), '<a href="http://ithemes.com" target="_blank">' ) . '</a></li>';
			$content .= '<li><a href="http://twitter.com/home?status=' . urlencode( sprintf( __( 'I use %s for WordPress by %s and you should too - %s' ), $bwps_globals['plugin_name'], '@ithemes', 'http://ithemes.com' ) ) . '" target="_blank">' . __( 'Tweet about it. ', 'better_wp_security' ) . '</a></li>';
			$content .= '<li><a href="http://twitter.com/home?status=' . urlencode( sprintf( __( 'I use %s for WordPress by %s and you should too - %s' ), $bwps_globals['plugin_name'], '@ithemes', 'http://ithemes.com' ) ) . '" target="_blank">' . __( 'Tweet about it. ', 'better_wp_security' ) . '</a></li>';
			$content .= '</ul>';

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

					$content .= '<li class="ithemes">' . __( 'I couldn\'t find any updates. If the problem persists please contact the feed owner', 'better_wp_security' ) . '</li>';

				} else {

					foreach ( $feeditems as $item ) {

						$url = preg_replace( '/#.*/', '', esc_url( $item->get_permalink(), $protocolls = NULL, 'display' ) );

						$content .= '<li class="ithemes"><a class="rsswidget" href="' . $url . '" target="_blank">' . esc_html( $item->get_title() ) . '</a></li>';

					}

				}

				$content .= '</ul>'; //end list

			} else {
				$content = __( 'It appears as if the feed is currently down. Please try again later', 'better_wp_security' );
			}

			echo $content;

		}
		
		/**
		 * Display the Security Status feed
		 *
		 * @return void
		 */
		public function metabox_status_feed() {

			$content  =		'<div class="bwps-status-feed-item">';
			$content .=			'<p>This is here so you can see the markup/styling for the feed items</p>';
			$content .=		'</div>';
			
			$content .= 	'<div class="bwps-status-feed-item urgent">';
			$content .=			'<p>Change username "admin" to something else.</p>';
			$content .=			'<div class="bwps-status-feed-actions">';
			$content .=				'<p class="bwps-why"><a href="#">Why Change This?</a></p>';
			$content .=				'<p><input class="button-primary" name="submit" type="submit" value="Fix This"></p>';
			$content .=			'</div>';
			$content .=		'</div>';
			
			$content .= 	'<div class="bwps-status-feed-item completed">';
			$content .=			'<div class="bwps-status-feed-completed-message">';
			$content .=				'<p>User ID 1 changed!</p>';
			$content .=			'</div>';
			$content .=			'<div class="bwps-status-feed-actions">';
			$content .=				'<p><input class="button-secondary" name="submit" type="submit" value="Undo"></p>';
			$content .=			'</div>';
			$content .=		'</div>';
				
				
			$content .=		'<div class="bwps-status-feed-item recommended">';
			$content .=			'<p>You should hide the WordPress admin area.</p>';
			$content .=			'<div class="bwps-status-feed-actions">';
			$content .=				'<p class="bwps-why"><a href="#">Why Change This?</a></p>';
			$content .=				'<p><input class="button-primary" name="submit" type="submit" value="Fix This"></p>';
			$content .=			'</div>';
			$content .=		'</div>';
				
			echo $content;
		}

		/**
		 * Display security status
		 *
		 * @return void
		 */
		public function metabox_normal_status() {

			global $bwps_globals;

			$statuses = array(
				'safe-high' => array(),
				'high' => array(),
				'safe-medium' => array(),
				'medium' => array(),
				'safe-low' => array(),
				'low' => array(),
			);

			$statuses = apply_filters( 'bwps_add_dashboard_status', $statuses );
			
			if ( isset ( $statuses['safe-high'] ) || isset ( $statuses['safe-medium'] ) || isset ( $statuses['safe-low'] ) ) {

				printf( '<h2>%s</h2>', __( 'Completed', 'better_wp_security' ) );
				_e( 'These are items that you have successfuly secured.', 'better_wp_security' );

				echo '<ul class="statuslist completed">';

				if ( isset ( $statuses['safe-high'] ) ) {

					foreach ( $statuses['safe-high'] as $status ) {

						printf( '<li>%s</li>', $status['text'] );

					}

				}

				if ( isset ( $statuses['safe-medium'] ) ) {

					foreach ( $statuses['safe-medium'] as $status ) {

						printf( '<li>%s</li>', $status['text'] );

					}

				}

				if ( isset ( $statuses['safe-low'] ) ) {

					foreach ( $statuses['safe-low'] as $status ) {

						printf( '<li><p>%s</p></li>', $status['text'] );

					}

				}

				echo '</ul>';

			}

			if ( isset ( $statuses['high'] ) ) {

				printf( '<h2>%s</h2>', __( 'High Priority', 'better_wp_security' ) );
				_e( 'These are items that should be secured immediately.', 'better_wp_security' );

				echo '<ul class="statuslist recommended">';

				if ( isset ( $statuses['high'] ) ) {

					foreach ( $statuses['high'] as $status ) {

						printf( '<li><p>%s</p><div class="bwps_status_action"><a class="button-primary" href="%s">Fix it</a></div></li>', $status['text'], $status['link'] );

					}

				}

				echo '</ul>';

			}

			if ( isset ( $statuses['medium'] ) ) {

				printf( '<h2>%s</h2>', __( 'Medium Priority', 'better_wp_security' ) );
				_e( 'These are items that should be secured if possible however they are not critical to the overall security of your site.', 'better_wp_security' );

				echo '<ul class="statuslist recommended">';

				if ( isset ( $statuses['medium'] ) ) {

					foreach ( $statuses['medium'] as $status ) {

						printf( '<li><p>%s</p><div class="bwps_status_action"><a class="button-primary" href="%s">Fix it</a></div></li>', $status['text'], $status['link'] );

					}

				}

				echo '</ul>';

			}

			if ( isset ( $statuses['low'] ) ) {

				printf( '<h2>%s</h2>', __( 'Low Priority', 'better_wp_security' ) );
				_e( 'These are items that should be secured if, and only if, your plugins or theme do not conflict with their use.', 'better_wp_security' );

				echo '<ul class="statuslist additional">';

				if ( isset ( $statuses['low'] ) ) {

					foreach ( $statuses['low'] as $status ) {

						printf( '<li><p>%s</p><div class="bwps_status_action"><a class="button-secondary" href="%s">Fix it</a></div></li>', $status['text'], $status['link'] );

					}

				}

				echo '</ul>';

			}

		}

		/**
		 * Start the BWPS Dashboard module
		 *
		 * @return Ithemes_BWPS_Dashboard_Admin            The instance of the Ithemes_BWPS_Dashboard_Admin class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}