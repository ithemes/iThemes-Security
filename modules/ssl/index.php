<?php

if ( ! class_exists( 'ITSEC_SSL' ) ) {
	require( dirname( __FILE__ ) . '/class-itsec-ssl.php' );
}

if ( ! class_exists( 'ITSEC_SSL_Admin' ) ) {
	require( dirname( __FILE__ ) . '/class-itsec-ssl-admin.php' );
}

$ssl = ITSEC_SSL::start();
ITSEC_SSL_Admin::start( $this, $ssl );
