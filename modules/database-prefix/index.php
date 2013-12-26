<?php

if ( ! class_exists( 'BWPS_Database_Prefix_Admin' ) ) {
	require( dirname( __FILE__ ) . '/class-bwps-database-prefix-admin.php' );
}

BWPS_Database_Prefix_Admin::start( $this );
