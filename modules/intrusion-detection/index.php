<?php

if ( ! class_exists( 'ITSEC_Intrusion_Detection' ) ) {
	require( dirname( __FILE__ ) . '/class-itsec-intrusion-detection.php' );
}

if ( ! class_exists( 'ITSEC_Intrusion_Detection_Admin' ) ) {
	require( dirname( __FILE__ ) . '/class-itsec-intrusion-detection-admin.php' );
}

ITSEC_Intrusion_Detection::start();
ITSEC_Intrusion_Detection_Admin::start( $this );
