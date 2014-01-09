<?php

if ( ! class_exists( 'ITSEC_Database_Prefix_Admin' ) ) {
	require( dirname( __FILE__ ) . '/class-itsec-database-prefix-admin.php' );
}

ITSEC_Database_Prefix_Admin::start( $this );
