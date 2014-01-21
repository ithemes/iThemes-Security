<?php

if ( ! class_exists( 'ITSEC_Authentication' ) ) {
	require( dirname( __FILE__ ) . '/class-itsec-authentication.php' );
}

if ( ! class_exists( 'ITSEC_Authentication_Admin' ) ) {
	require( dirname( __FILE__ ) . '/class-itsec-authentication-admin.php' );
}

$authentication = ITSEC_Authentication::start();
ITSEC_Authentication_Admin::start( $this, $authentication );
