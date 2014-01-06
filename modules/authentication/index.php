<?php

if ( ! class_exists( 'BWPS_Authentication' ) ) {
	require( dirname( __FILE__ ) . '/class-bwps-authentication.php' );
}

if ( ! class_exists( 'BWPS_Authentication_admin' ) ) {
	require( dirname( __FILE__ ) . '/class-bwps-authentication-admin.php' );
}

$authentication = BWPS_Authentication::start();
BWPS_Authentication_Admin::start( $this, $authentication );
