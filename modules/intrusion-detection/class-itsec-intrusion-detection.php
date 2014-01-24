<?php

if ( ! class_exists( 'ITSEC_Intrusion_Detection' ) ) {

	class ITSEC_Intrusion_Detection {

		private static $instance = null;

		private
			$settings;

		private function __construct() {

			$this->settings  = get_site_option( 'itsec_intrusion_detection' );

			add_filter( 'itsec_lockout_modules', array( $this, 'register_lockout' ) );
			add_filter( 'itsec_logger_modules', array( $this, 'register_logger' ) );

			add_action( 'wp_head', array( $this,'check_404' ) );

		}

		public function check_404() {

			global $itsec_lib, $itsec_logger, $itsec_lockout;

			if ( $this->settings['four_oh_four-enabled'] === true && is_404() ) {

				$uri = explode( '?', $_SERVER['REQUEST_URI'] );

				if ( in_array( $uri[0], $this->settings['four_oh_four-white_list'] ) === false ) {

					$itsec_logger->log_event(
						'four_oh_four',
						3,
						array(
							'query_string' => isset( $uri[1] ) ? esc_sql( $uri[1] ) : '',
						),
						$itsec_lib->get_ip(),
						'',
						'',
						esc_sql( $uri[0] ),
						isset( $_SERVER['HTTP_REFERER'] ) ? esq_sql( $_SERVER['HTTP_REFERER'] ) : ''
					);

					$itsec_lockout->do_lockout( 'four_oh_four' );

				}

			}

		}

		/**
		 * Check file list
		 *
		 * Checks if given file should be included in file check based on exclude/include options
		 *
		 * @param string $file path of file to check from site root
		 * @return bool true if file should be checked false if not
		 *
		 **/
		private function check_file ( $file ) {
			
			//get file list from last check
			$file_list = get_site_option( 'itsec_local_file_list' );
			
			//assume not a directory and not checked
			$flag = false;
			
			//if list is empty return true
			if ( trim( $file_list ) != '' ) {
			
				$file_list = explode( PHP_EOL, $file_list );
				
			} else {
			
				//if empty include list we include nothing. If empty exclude list we include everything
				if ( $this->settings['file_change-method'] === true ) {
			
					return true;
					
				} else {
				
					return false;
					
				}
				
			}
			
			//compare file to list
			
			foreach ( $file_list as $item ) {

				$item = trim ( $item );
			
				//$file is a directory
				if ( is_dir( ABSPATH . $file ) ) {
					
					if ( strcmp( $file, $item ) === 0 ) {
						$flag = true;		
					}
				
				} else { //$file is a file
				
					if ( strpos( $item , '.' ) === 0 ) { //list item is a file extension
					
						if ( strcmp( '.' . end ( explode( '.' , $file ) ), $item ) == 0 ) {
							$flag = true;
						 }
				
					} else { //list item is a single file

						if ( strcmp( $item, $file ) == 0 ) {
							$flag = true;
						}
				
					}
					
				}
				
			}
			
			if ( $this->settings['file_change-method'] === true ) {
			
				if ( $flag == true ) { //if exclude reverse
					return false;
				} else {
					return true;
				}
			
			} else { //return flag 
			
				if ( is_dir( ABSPATH . $file ) ) {
					
					if ( $flag == true ) { //if exclude reverse
						return false;
					} else {
						return true;
					}
					
				} else {
				
					return $flag;
					
				}
				
			}
		
		}

		/**
		 * Register 404 detection for lockout
		 *
		 * @param  array $lockout_modules array of lockout modules
		 *
		 * @return array                   array of lockout modules
		 */
		public function register_lockout( $lockout_modules ) {

			$lockout_modules['four_oh_four'] = array(
				'type'   => 'four_oh_four',
				'reason' => __( 'too many attempts to access a file that doesn not exist', 'ithemes-security' ),
				'host'   => $this->settings['four_oh_four-error_threshold'],
				'period' => $this->settings['four_oh_four-check_period']
			);

			return $lockout_modules;

		}

		/**
		 * Register 404 and file change detection for logger
		 *
		 * @param  array $logger_modules array of logger modules
		 *
		 * @return array                   array of logger modules
		 */
		public function register_logger( $logger_modules ) {

			$logger_modules['four_oh_four'] = array(
				'type'      => 'four_oh_four',
				'function' => __( '404 Error', 'ithemes-security' ),
			);

			return $logger_modules;

		}

		/**
		 * Start the Intrusion Detection module
		 *
		 * @return 'ITSEC_Intrusion_Detection'                The instance of the 'ITSEC_Intrusion_Detection' class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}