<?php

if ( ! class_exists( 'BWPS_Away_mode' ) ) {
	require( dirname( __FILE__ ) . '/class-bwps-away-mode.php' );
}

if ( ! class_exists( 'BWPS_Away_mode_Admin' ) ) {
	require( dirname( __FILE__ ) . '/class-bwps-away-mode-admin.php' );
}

$away_mode = BWPS_Away_Mode::start( $this->core );
BWPS_Away_Mode_Admin::start( $this->core, $away_mode );
