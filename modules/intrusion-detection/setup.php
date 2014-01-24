<?php

if ( ! class_exists( 'ITSEC_Intrusion_Detection_Setup' ) ) {

	class ITSEC_Intrusion_Detection_Setup {

		function __construct() {

			global $itsec_setup_action;

			if ( isset( $itsec_setup_action ) ) {

				switch ( $itsec_setup_action ) {

					case 'activate':
						$this->execute_activate();
						break;
					case 'upgrade':
						$this->execute_upgrade();
						break;
					case 'deactivate':
						$this->execute_deactivate();
						break;
					case 'uninstall':
						$this->execute_uninstall();
						break;

				}

			} else {
				wp_die( 'error' );
			}

		}

		/**
		 * Execute module activation
		 *
		 * @return void
		 */
		function execute_activate() {

			$options = get_site_option( 'itsec_intrusion_detection' );

			if ( $options === false ) {

				$defaults = array(
					'four_oh_four-enabled'         => false,
					'four_oh_four-check_period'    => 5,
					'four_oh_four-error_threshold' => 20,
					'four_oh_four-white_list'      => array(
						'/favicon.ico',
						'/robots.txt',
						'/apple-touch-icon.png',
						'/apple-touch-icon-precomposed.png',
					),
					'file_change-enabled'          => false,
					'file_change-list'             => array();
					'file_change-method'           => true;
					'file_change-types'            => array(
						'.jpg',
						'.jpeg',
						'.png',
					),
					'file_change-email'            => true,
				);

				add_site_option( 'itsec_intrusion_detection', $defaults );

			}

			$file_list = get_site_option( 'itsec_local_file_list' );

			if ( $file_list === false ) {
				add_site_option( 'itsec_local_file_list', array() );
			}

		}

		/**
		 * Execute module deactivation
		 *
		 * @return void
		 */
		function execute_deactivate() {
		}

		/**
		 * Execute module uninstall
		 *
		 * @return void
		 */
		function execute_uninstall() {

			$this->execute_deactivate();

			delete_site_option( 'itsec_intrusion_detection' );
			delete_site_option( 'itsec_local_file_list' );

		}

		/**
		 * Execute module upgrade
		 *
		 * @return void
		 */
		function execute_upgrade() {

		}

	}

}

new ITSEC_Intrusion_Detection_Setup();