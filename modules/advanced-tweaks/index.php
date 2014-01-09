<?php

if ( ! class_exists( 'ITSEC_Advanced_Tweaks' ) ) {
	require( dirname( __FILE__ ) . '/class-itsec-advanced-tweaks.php' );
}

if ( ! class_exists( 'ITSEC_Advanced_Tweaks_Admin' ) ) {
	require( dirname( __FILE__ ) . '/class-itsec-advanced-tweaks-admin.php' );
}

ITSEC_Advanced_Tweaks::start();
ITSEC_Advanced_Tweaks_Admin::start( $this );
