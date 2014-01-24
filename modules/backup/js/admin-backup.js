jQuery( document ).ready( function () {

	jQuery( "#itsec_backup_enabled" ).change(function () {

		if ( jQuery( "#itsec_backup_enabled" ).is( ':checked' ) ) {

			jQuery( "#backup-settings" ).show();

		} else {

			jQuery( "#backup-settings" ).hide();

		}

	} ).change();

	jQuery( '#itsec_backup_exclude' ).multiSelect();

} );
