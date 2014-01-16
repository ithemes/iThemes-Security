<?php

if ( ! class_exists( 'ITSEC_SSL' ) ) {

	class ITSEC_SSL {

		private static $instance = null;

		private
			$settings;

		private function __construct() {

			$this->settings = get_site_option( 'itsec_ssl' );

			//Don't redirect any SSL if SSL is turned off.
			if ( isset( $this->settings['frontend'] ) && $this->settings['frontend'] >= 1 ) {
				add_action( 'template_redirect', array(
					$this,
					'ssl_redirect'
				) );
			}

		}

		/**
		 * Check if current url is using SSL
		 *
		 * @return bool true if ssl false if not
		 *
		 */
		function is_ssl() {

			//modified logic courtesy of "Good Samaritan"
			if ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 ) {
				return true;
			} else {
				return false;
			}

		}

		/**
		 * Redirects to or from SSL where appropriate
		 *
		 * @return void
		 */
		function ssl_redirect() {

			global $post;

			if ( is_singular() && $this->settings['frontend'] == 1 ) {

				$requiressl = get_post_meta( $post->ID, 'itsec_enable_ssl', true );

				if ( ( $requiressl == true && ! $this->is_ssl() ) || ( $requiressl != true && $this->is_ssl() ) ) {

					$href = ( $_SERVER['SERVER_PORT'] == '443' ? 'http' : 'https' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

					wp_redirect( $href, 301 );

				}

			} else {

				if ( ( $this->settings['frontend'] == 2 && ! $this->is_ssl() ) || ( ( $this->settings['frontend'] == 0 || $this->settings['frontend'] == 1 ) && $this->is_ssl() ) ) {

					$href = ( $_SERVER['SERVER_PORT'] == '443' ? 'http' : 'https' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

					wp_redirect( $href, 301 );

				}

			}

		}

		/**
		 * Start the Away Mode module
		 *
		 * @return ITSEC_SSL                The instance of the ITSEC_SSL class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}