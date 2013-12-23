<?php

if ( ! class_exists( 'BWPS_Ban_Users_Admin' ) ) {
	require( dirname( __FILE__ ) . '/class-bwps-ban-users-admin.php' );
}

BWPS_Ban_Users_Admin::start( $this );
