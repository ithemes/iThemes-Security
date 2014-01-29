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
			$sidebar_items;

		private function __construct() {

			add_action( 'itsec_admin_init', array( $this, 'register_admin_css' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) ); //enqueue scripts for admin page
			add_action( 'itsec_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) );
			add_action( 'wp_ajax_itsec_sidebar', array( $this, 'save_ajax_options' ) );

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
		 * Add Dashboard Javascript
		 *
		 * @return void
		 */
		public function admin_script() {

			global $itsec_globals;

			if ( strpos( get_current_screen()->id, 'itsec' ) !== false ) {

				wp_enqueue_script( 'itsec_dashboard_js', $itsec_globals['plugin_url'] . 'modules/dashboard/js/admin-dashboard.js', 'jquery', $itsec_globals['plugin_build'] );
				wp_localize_script( 'itsec_dashboard_js', 'ajax_object',
				                    array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'we_value' => 1234 ) );

			}

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
		 * Display the Security Status feed
		 *
		 * @return void
		 */
		public function metabox_status_feed() {

			$this->sidebar_items = array();

			$this->sidebar_items = apply_filters( 'itsec_add_sidebar_status', $this->sidebar_items );

			// Intro Text
			$content = '<div class="itsec-status-feed-item intro">';
			$content .= '<p>';
			$content .= __( 'We\'ve analyzed your site and determined that these simple changes will have the biggest impact in keeping your site safe and secure. Do these things first, then use the tabs to explore more advanced features.', 'ithemes-security' );
			$content .= '</p>';
			$content .= '</div>';

			// Begin Feed Items
			if ( isset( $this->sidebar_items[0] ) ) {

				$status_count = 0;

				foreach ( $this->sidebar_items as $item ) {

					$status_count ++;

					if ( $status_count <= 5 ) {

						$content .= '<div class="itsec-status-feed-item high-priority">';
						$content .= '<p class="bad_text">' . $item['bad_text'] . '</p>';
						$content .= '<p class="good_text">' . $item['good_text'] . '</p>';
						$content .= '<div class="itsec-status-feed-actions">';
						$content .= '<p class="itsec-why"><a href="#">' . __( 'Why Change This?', 'ithemes-security' ) . '</a></p>';
						$content .= '<form class="itsec_ajax_form" method="post" action="">';
						$content .= wp_nonce_field( 'itsec_sidebar_ajax', 'itsec_sidebar_nonce', true, false );
						$content .= '<input type="hidden" name="itsec_option" id="itsec_option" value="' . $item['option'] . '">';
						$content .= '<input type="hidden" name="itsec_setting" id="itsec_setting" value="' . $item['setting'] . '">';
						$content .= '<input type="hidden" name="itsec_value" id="itsec_value" value="' . $item['value'] . '">';
						$content .= '<p><input class="button-primary" name="submit" type="submit" value="' . __( 'Fix This', 'ithemes-security' ) . '"></p>';
						$content .= '</form>';
						$content .= '</div>';
						$content .= '</div>';

					}

				}

			}

			// Commented section here to show markup for item after completion
			/* 
			$content .= '<div class="itsec-status-feed-item completed">';
			$content .= '<div class="itsec-status-feed-completed-message">';
			$content .= '<p>User ID 1 changed!</p>';
			$content .= '</div>';
			$content .= '<div class="itsec-status-feed-actions">';
			$content .= '<p><input class="button-secondary" name="submit" type="submit" value="Undo"></p>';
			$content .= '</div>';
			$content .= '</div>';
			*/

			// These two sections left here to show markup for notices and ithemes-messages
			$content .= '<div class="itsec-status-feed-item notice">';
			$content .= '<p>Your site had 116 instances of 404 errors last week. This could be evidence of an attempted attack. Check your logs.</p>';
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

			// Bottom Interchangeable Ad - left here as reminder to figure out a way to do this ad
			$content .= '<div class="itsec-status-feed-item closing">';
			$content .= '<p>';
			//$content .= 		;
			$content .= '</p>';
			$content .= '</div>';

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

		public function save_ajax_options() {

			$data = array();

			foreach ( $_POST as $item => $value ) {
				$data[sanitize_text_field( $item )] = sanitize_text_field( $value );
			}

			if ( ( isset( $data['action'] ) === false || isset( $data['setting'] ) === false || isset( $data['option'] ) === false || isset( $data['value'] ) === false || isset( $data['nonce'] ) === false ) && wp_verify_nonce( $data['nonce'], 'itsec_sidebar_ajax' ) === false ) {
				die( false );
			}

			$whatever = intval( $_POST['whatever'] );
			$whatever += 10;
			echo $whatever;
			die();

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