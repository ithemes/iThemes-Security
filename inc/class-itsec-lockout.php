<?php

<?php

if ( ! class_exists( 'ITSEC_Lockout' ) ) {

	class ITSEC_Lockout {

		private static $instance = NULL; //instantiated instance of this plugin

		function __construct() {

		}

		/**
		 * Start the global lockout instance
		 *
		 * @return ITSEC_Lockout         The instance of the ITSEC_Lockout class
		 */
		public static function start() {

			if ( ! isset( self::$instance ) || self::$instance === NULL ) {
				self::$instance = new self();
			}

			return self::$instance;

		}

	}

}