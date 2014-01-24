<?php

if ( ! class_exists( 'ITSEC_Backup' ) ) {
	require( dirname( __FILE__ ) . '/class-itsec-backup.php' );
}

if ( ! class_exists( 'ITSEC_Backup_Admin' ) ) {
	require( dirname( __FILE__ ) . '/class-itsec-backup-admin.php' );
}

$itsec_backup = ITSEC_Backup::start();
ITSEC_Backup_Admin::start( $this, $itsec_backup );
