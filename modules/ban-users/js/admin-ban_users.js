jQuery( document ).ready( function () {

	jQuery( "#itsec_ban_users_enabled" ).change(function () {

		if ( jQuery( "#itsec_ban_users_enabled" ).is( ':checked' ) || ( jQuery( "#setting-error-settings_updated" ).length > 0 && jQuery( "#setting-error-settings_updated" ).hasClass( "error" ) ) ) {
			jQuery( "#ban_users_settings" ).show();

		} else {
			jQuery( "#ban_users_settings" ).hide();

		}

	} ).change();

} );