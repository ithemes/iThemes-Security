<?php

if ( ! class_exists( 'Ithemes_BWPS_Dashboard_Admin' ) ) {
	require( dirname( __FILE__ ) . '/class-ithemes-bwps-dashboard-admin.php' );
}

Ithemes_BWPS_Dashboard_Admin::start( $this->core );
