<?php

if ( ! class_exists( 'BWPS_Content_Directory_Admin' ) ) {
	require_once( dirname( __FILE__ ) . '/class-bwps-content-directory-admin.php' );
}

BWPS_Content_Directory_Admin::start( $this->core );
