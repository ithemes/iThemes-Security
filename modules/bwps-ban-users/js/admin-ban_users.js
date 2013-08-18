jQuery( document ).ready( function () {

	jQuery( "#bwps_ban_users_enabled" ).change( function() {

		if ( jQuery( "#bwps_ban_users_enabled" ).is( ':checked' ) ) {

			jQuery( "#ban_users_settings" ).show();

		} else {

			jQuery( "#ban_users_settings" ).hide();

		}

	} ).change();

} );