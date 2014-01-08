jQuery( document ).ready( function () {

	jQuery( "#bwps_authentication_away_mode_end_date, #bwps_authentication_away_mode_start_date" ).datepicker();

	jQuery( "#bwps_authentication_away_mode_enabled" ).change( function() {

		if ( jQuery( "#bwps_authentication_away_mode_enabled" ).is( ':checked' ) ) {

			jQuery( "#authentication_away_mode-settings" ).show();

		} else {

			jQuery( "#authentication_away_mode-settings" ).hide();

		}

	} ).change();

	jQuery( "#bwps_authentication_strong_passwords_enabled" ).change( function() {

		if ( jQuery( "#bwps_authentication_strong_passwords_enabled" ).is( ':checked' ) ) {

			jQuery( "#authentication_strong_passwords-settings" ).show();

		} else {

			jQuery( "#authentication_strong_passwords-settings" ).hide();

		}

	} ).change();

	jQuery( "#bwps_authentication_hide_backend_enabled" ).change( function() {

		if ( jQuery( "#bwps_authentication_hide_backend_enabled" ).is( ':checked' ) ) {

			jQuery( "#authentication_hide_backend-settings" ).show();

		} else {

			jQuery( "#authentication_hide_backend-settings" ).hide();

		}

	} ).change();

	if ( jQuery( 'p.noPermalinks' ).length ) {
		jQuery( "#authentication_hide_backend-settings" ).hide();
	}

	jQuery( "#bwps_authentication_away_mode_type" ).change( function() {

		if ( jQuery( "#bwps_authentication_away_mode_type" ).val() == "2" ) {

			jQuery( ".end_date_field, .start_date_field" ).closest( "tr" ).show();

		} else {

			jQuery( ".end_date_field, .start_date_field" ).closest( "tr" ).hide();

		}

	} ).change();

} );
