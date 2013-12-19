<?php

if ( ! class_exists( 'BWPS_Away_Mode_Setup' ) ) {

	class BWPS_Away_Mode_Setup {

		function __construct() {

			global $bwps_setup_action;

			if ( isset( $bwps_setup_action ) ) {

				switch ( $bwps_setup_action ) {

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

			$options = get_site_option( 'bwps_away_mode' );

			$upload_dir = wp_upload_dir(); //get the full upload directory array so we can grab the base directory.

			$away_file = $upload_dir['basedir'] . '/bwps_away.confg'; //override file

			if ( isset( $options['enabled'] ) && $options['enabled'] == 1 && ! file_exists( $away_file ) ) {
				@file_put_contents( $away_file, 'true' );
			}

		}

		/**
		 * Execute module deactivation
		 *
		 * @return void
		 */
		function execute_deactivate() {

			delete_site_transient( 'bwps_away' );

			$upload_dir = wp_upload_dir(); //get the full upload directory array so we can grab the base directory.

			$away_file = $upload_dir['basedir'] . '/bwps_away.confg'; //override file

			@unlink( $away_file );

		}

		/**
		 * Execute module uninstall
		 *
		 * @return void
		 */
		function execute_uninstall() {

			$this->execute_deactivate();

			delete_site_option( 'bwps_away_mode' );

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

new BWPS_Away_Mode_Setup();