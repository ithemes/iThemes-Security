<?php

if ( ! class_exists( 'BWPS_Ban_Users' ) ) {
	require_once( dirname( __FILE__ ) . '/class-bwps-ban-users.php' );
}

if ( ! class_exists( 'BWPS_Ban_Users_Admin' ) ) {
	require_once( dirname( __FILE__ ) . '/class-bwps-ban-users-admin.php' );
}

$ban_users = BWPS_Ban_Users::start( $this->core );
BWPS_Ban_Users_Admin::start( $this->core, $ban_users );
