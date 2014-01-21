<?php

if ( ! class_exists( 'ITSEC_Files' ) ) {
	require( dirname( __FILE__ ) . '/class-itsec-files.php' );
}

if ( ! class_exists( 'ITSEC_Files_Admin' ) ) {
	require( dirname( __FILE__ ) . '/class-itsec-files-admin.php' );
}

ITSEC_Files::start();
ITSEC_Files_Admin::start( $this );
