<?php
/**
 * Brand plugins with iThemes sidebar items in the admin
 *
 * @version 1.0
 */

require_once( plugin_dir_path( __FILE__ ) . 'class-foolic-validation-v1_1.php' );

if ( ! class_exists( 'ITSEC_Foo_Support_Admin' ) ) {

	class ITSEC_Foo_Support_Admin {

		private static $instance = null;

		private $support_email;

		private function __construct() {

			$this->support_email = 'support.itsec@fooplugins.com';

			add_action( 'itsec_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) );

			add_filter( 'foolic_validation_include_css-itsec', array( $this, 'include_foolic_css' ) );
			add_filter( 'foolic_validation_input_type-itsec', array( $this, 'change_foolic_input_type' ) );
			new foolic_validation_v1_1( 'http://fooplugins.com/api/ithemes-security/check', 'itsec' );
			add_action( 'wp_ajax_' . 'itsec_support', array( $this, 'ajax_submit_ticket' ) );

		}

		/**
		 * Add meta boxes to primary options pages
		 *
		 * @param array $available_pages array of available page_hooks
		 */
		function add_admin_meta_boxes( $available_pages ) {

			foreach ( $available_pages as $page ) {

				//add metaboxes
				add_meta_box( 'itsec_foo_support', __( 'Need Help?', 'ithemes-security' ), array( $this, 'metabox_sideboar_foo_support' ), $page, 'priority_side', 'core' );

			}

		}

		/**
		 * set screen for css implementation
		 *
		 * @param  Screen $screen WordPress Screen object
		 *
		 * @return bool            make sure we're on a ITSEC screen
		 */
		function include_foolic_css( $screen ) {

			return $screen->id === 'toplevel_page_ithemes-security';
		}

		/**
		 * Set input type
		 *
		 * @return string type of input box for support key
		 */
		function change_foolic_input_type() {

			return 'text';
		}

		/**
		 * Build and echo the content sidebar metabox
		 *
		 * @return void
		 */
		public function metabox_sideboar_foo_support() {

			$purchase_url = 'http://fooplugins.com/plugins/ithemes-security/';

			$data = apply_filters( 'foolic_get_validation_data-itsec', false );

			if ( $data === false ) {
				return;
			}

			if ( $data['valid'] === 'valid' ) {

				$content = '<form id="support_form">';
				$content .= '<input type="hidden" name="action" value="' . $this->hook . '_support" />';
				$content .= '<input type="hidden" name="nonce" value="' . wp_create_nonce( $this->hook . '_ajax-nonce' ) . '" />';
				$content .= '<input type="hidden" name="ticket_key" value="' . $data['license'] . '" />';
				$content .= '<label for="support_email">' . __( 'Your Email Address', $this->hook ) . ':</label><input type="text" name="email" value="' . $current_user->user_email . '" id="support_email">';
				$content .= '<label for="support_name">' . __( 'Your Name', $this->hook ) . ':</label><input type="text" name="name" value="' . $current_user->display_name . '" id="support_name">';
				$content .= '<label for="support_issue">' . __( 'Describe the Issue', $this->hook ) . ':</label><textarea name="issue" style="height:100px; display:block; width:100%; border:solid 1px #aaa;" class="regular-text" id="support_issue"></textarea>';
				$content .= '<label for="support_reproduce">' . __( 'Steps to Reproduce', $this->hook ) . ':</label><textarea name="reproduce" style="height:200px; display:block; width:100%; border:solid 1px #aaa;" class="regular-text" id="support_reproduce"></textarea>';
				$content .= '<label for="support_other">' . __( 'Other Information', $this->hook ) . ':</label><textarea name="other" style="height:100px; display:block; width:100%; border:solid 1px #aaa;" class="regular-text" id="support_other"></textarea><br />';
				$content .= '<input id="submit_support" type="button" class="button-primary" value="' . __( 'Submit Support Ticket', $this->hook ) . '" /><br />';
				$content .= '<br /></form>';
				$content .= '<div style="display:none" class="support_message foolic-loading"><p>' . __( 'sending...', $this->hook ) . '</p></div>';
				$content .= '<a target="_blank" href="' . $purchase_url . '">' . __( 'Purchase priority support', $this->hook ) . '</a>';
				$content .= ' | <a href="#newkey" class="foolic-clear-' . $this->hook . '">' . __( 'Enter License Key', $this->hook ) . '</a>';
				$content .= $data['nonce'];

			} else {

				$content = '<strong>' . __( 'Need premium support or configuration?', 'ithemes-security' ) . '<br /><br /><a target="_blank" href="' . $purchase_url . '">' . __( 'Purchase one-time premium support or installation', 'ithemes-security' ) . '</a>.</strong><br /><br />';
				$content .= $data['html'];

			}

			$content .= '<script type="text/javascript">
							jQuery( function( $ ) {
								$( document ).bind( "foolic-cleared-' . 'itsec", function() {
									window.location.reload();
								} );

								$("#submit_support").click(function(e) {
									e.preventDefault();

									if ($("#support_issue").val().length == 0) {
										alert("' . __( 'Please describe the issue you are having', 'ithemes-security' ) . '");
										return;
									}
									$("#support_form").slideUp();
									var data = $("#support_form").serialize();

									$(".support_message").addClass("updated").show();

									$.ajax({ url: ajaxurl, cache: false, type: "POST", data: data,
										success: function (data) {
											$(".support_message").removeClass("foolic-loading").html("<p>' . __( 'Thank you for submitting your support ticket. We will contact you shortly.', 'ithemes-security' ) . '</p>");
										},
										error: function(a,b,c) {
											alert(a);
										}
									});
								} );
							} );
						</script>';

			echo $content;

		}

		/**
		 * Start the ITSEC Dashboard module
		 *
		 * @return ITSEC_Foo_Support                The instance of the ITSEC_Foo_Support class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}