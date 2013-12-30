<?php

if ( ! class_exists( 'BWPS_Advanced_Tweaks' ) ) {
	require( dirname( __FILE__ ) . '/class-bwps-advanced-tweaks.php' );
}

if ( ! class_exists( 'BWPS_Advanced_Tweaks_Admin' ) ) {
	require( dirname( __FILE__ ) . '/class-bwps-advanced-tweaks-admin.php' );
}

BWPS_Advanced_Tweaks::start();
BWPS_Advanced_Tweaks_Admin::start( $this );
