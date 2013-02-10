<?php

if ( ! class_exists( 'bwps_wp_content' ) ) {

	class bwps_wp_content extends bwps_admin_common {
	
		function __construct() {
		
			global $bwpsoptions;
			
			if ( $bwpsoptions['ssl_frontend'] == 1 ) {
		
				add_action( 'post_submitbox_misc_actions', array( &$this, 'ssl_enable' ) );
				add_action( 'save_post', array( &$this, 'save_post' ) );
			
			}
			
		}
		
		function ssl_enable() {
		
			global $post;
			
			wp_nonce_field( 'BWPS_admin_save','wp_nonce' );
			
			$enabled = false;
			
			if ( $post->ID ) {
				$enabled = get_post_meta( $post->ID, 'bwps_enable_ssl', true );
			}
			
			echo '<div id="bwps" class="misc-pub-section">' . 
			'<label for="enable_ssl">Enable SSL:</label> ' .
			'<input type="checkbox" value="1" name="enable_ssl" id="enable_ssl"' . ( $enabled == true ? ' checked="checked"' : '' ) . ' />' .
			'</div>';
		
		
		}
		
		function save_post( $id ) {
		
			if ( isset( $_POST['wp_nonce'] ) ) {
				
				if ( ! wp_verify_nonce( $_POST['wp_nonce'], 'BWPS_admin_save' ) || ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) || ( $_POST['post_type'] == 'page' && ! current_user_can( 'edit_page', $id ) ) || ( $_POST['post_type'] == 'post' && ! current_user_can( 'edit_post', $id ) ) ) {
					return $id;
				}
			
				$bwps_enable_ssl = ( ( isset( $_POST['enable_ssl'] ) &&  $_POST['enable_ssl'] == true ) ? true : false );
			
				if ( $bwps_enable_ssl ) {
					update_post_meta( $id, 'bwps_enable_ssl', true );
				} else {
					update_post_meta( $id, 'bwps_enable_ssl', false );
				}
			
				return $bwps_enable_ssl;
		
			}
		
		}
	
	}

}

new bwps_wp_content();