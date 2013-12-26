<?php

if ( ! class_exists( 'BWPS_SSL' ) ) {
	require( dirname( __FILE__ ) . '/class-bwps-ssl.php' );
}

if ( ! class_exists( 'BWPS_SSL_Admin' ) ) {
	require( dirname( __FILE__ ) . '/class-bwps-ssl-admin.php' );
}

$ssl = BWPS_SSL::start();
BWPS_SSL_Admin::start( $this, $ssl );
