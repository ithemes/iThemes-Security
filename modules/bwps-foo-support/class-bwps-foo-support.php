<?php
/**
 * Brand plugins with Bit51 sidebar items in the admin
 * 
 * @version 1.0
 */

require_once( plugin_dir_path( __FILE__ ) . 'class-foolic-validation-v1_1.php' );

if ( ! class_exists( 'BWPS_Foo_Support' ) ) {

	class BWPS_Foo_Support {

		private static $instance = null;

		private $support_email = 'support.bwps@fooplugins.com'; //current email address of Bit51.com support

		private 
			$core;

		private function __construct( $core ) {

			$this->core = $core;

			add_action( $this->core->plugin->globals['plugin_hook'] . '_add_admin_meta_boxes', array( $this, 'add_admin_meta_boxes' ) );

			add_filter( 'foolic_validation_include_css-' . $this->core->plugin->globals['plugin_hook'], array( $this, 'include_foolic_css' ) );
			add_filter( 'foolic_validation_input_type-' . $this->core->plugin->globals['plugin_hook'], array( $this, 'change_foolic_input_type' ) );
			add_filter( 'foolic_validation_input_size-' . $this->core->plugin->globals['plugin_hook'], array( $this, 'change_foolic_input_size' ) );
			new foolic_validation_v1_1( 'http://fooplugins.com/api/better-wp-security/check', $this->core->plugin->globals['plugin_hook'] );
			add_action( 'wp_ajax_' . $this->core->plugin->globals['plugin_hook'] . '_support', array( $this, 'ajax_submit_ticket' ) );

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
					'bwps_foo_support', 
					__( 'Need Help?', $this->core->plugin->globals['plugin_hook'] ),
					array( $this, 'metabox_sideboar_foo_support' ),
					$page,
					'priority_side',
					'core'
				);

			}

		}

		/**
		 * set screen for css implementation
		 * 
		 * @param  Screen $screen 	WordPress Screen object
		 * @return bool         	make sure we're on a BWPS screen
		 */
		function include_foolic_css( $screen ) {
			return $screen->id === 'toplevel_page_better_wp_security';
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
		 * Field size for support key field
		 * 
		 * @return string field size for support key
		 */
		function change_foolic_input_size() {
			return '29';
		}

		/**
		 * Build and echo the content sidebar metabox
		 * 
		 * @return void
		 */
		public function metabox_sideboar_foo_support() {

			$purchase_url = 'http://fooplugins.com/plugins/better-wp-security/';

			$data = apply_filters( 'foolic_get_validation_data-' . $this->core->plugin->globals['plugin_hook'], false );

			if ( $data === false ) {
				return;
			}

			if ( $data['valid'] === 'valid' ) {
				$content = '<form id="support_form">';
				$content .= '<input type="hidden" name="action" value="' . $this->core->plugin->globals['plugin_hook'] . '_support" />';
				$content .= '<input type="hidden" name="nonce" value="' . wp_create_nonce($this->core->plugin->globals['plugin_hook'] . '_ajax-nonce') . '" />';
				$content .= '<input type="hidden" name="ticket_key" value="' . $data['license'] . '" />';
				$content .= '<label for="support_issue">' . __( 'Describe the Issue', $this->core->plugin->globals['plugin_hook'] ). ':</label><textarea name="issue" style="height:100px; display:block; width:100%; border:solid 1px #aaa;" class="regular-text" id="support_issue"></textarea>';
				$content .= '<label for="support_reproduce">' . __( 'Steps to Reproduce', $this->core->plugin->globals['plugin_hook'] ). ':</label><textarea name="reproduce" style="height:200px; display:block; width:100%; border:solid 1px #aaa;" class="regular-text" id="support_reproduce"></textarea>';
				$content .= '<label for="support_other">' . __( 'Other Information', $this->core->plugin->globals['plugin_hook'] ). ':</label><textarea name="other" style="height:100px; display:block; width:100%; border:solid 1px #aaa;" class="regular-text" id="support_other"></textarea><br />';
				$content .= '<input id="submit_support" type="button" class="button-primary" value="' . __( 'Submit Support Ticket', $this->core->plugin->globals['plugin_hook'] ) . '" /><br />';
				$content .= '<br /></form>';
				$content .= '<div style="display:none" class="support_message foolic-loading"><p>' . __( 'sending...', $this->core->plugin->globals['plugin_hook'] ). '</p></div>';
				$content .= '<a target="_blank" href="' . $purchase_url . '">' . __( 'Purchase priority support', $this->core->plugin->globals['plugin_hook'] ) . '</a>';
				$content .= ' | <a href="#newkey" class="foolic-clear-' . $this->core->plugin->globals['plugin_hook'] . '">' . __( 'Enter License Key', $this->core->plugin->globals['plugin_hook'] ) . '</a>';
				$content .= $data['nonce'];


			} else {

				$content = '<strong>' . __( 'Need premium support or configuration?', $this->core->plugin->globals['plugin_hook'] ). '<br /><br /><a target="_blank" href="' . $purchase_url .'">' . __( 'Purchase one-time premium support or installation', $this->core->plugin->globals['plugin_hook'] ) . '</a>.</strong><br /><br />';
				$content .= $data['html'];

			}

			$content .= '<script type="text/javascript">
							jQuery( function( $ ) {
								$( document ).bind( "foolic-cleared-' . $this->core->plugin->globals['plugin_hook'] . '", function() {
									window.location.reload();
								} );

								$("#submit_support").click(function(e) {
									e.preventDefault();

									if ($("#support_issue").val().length == 0) {
										alert("' . __( 'Please describe the issue you are having', $this->core->plugin->globals['plugin_hook'] ). '");
										return;
									}
									$("#support_form").slideUp();
									var data = $("#support_form").serialize();

									$(".support_message").addClass("updated").show();

									$.ajax({ url: ajaxurl, cache: false, type: "POST", data: data,
										success: function (data) {
											$(".support_message").removeClass("foolic-loading").html("<p>' . __( 'Thank you for submitting your support ticket. We will contact you shortly.', $this->core->plugin->globals['plugin_hook'] ) . '</p>");
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
		 * Start the BWPS Dashboard module
		 * 
		 * @param  Bit51_BWPS_Core    $core     	Instance of core plugin class
		 * @return BWPS_Foo_Support 				The instance of the BWPS_Foo_Support class
		 */
		public static function start( $core ) {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self( $core );
			}

			return self::$instance;

		}

	}

}