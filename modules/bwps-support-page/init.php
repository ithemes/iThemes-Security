<?php

if ( ! class_exists( 'BWPS_Support_Page_Admin' ) ) {
	require_once( dirname( __FILE__ ) . '/class-bwps-support-page-admin.php' );
}

BWPS_Support_Page_Admin::start( $this->core );
