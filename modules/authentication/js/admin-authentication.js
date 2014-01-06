jQuery( document ).ready( function () {

	jQuery( "#bwps_authentication_away_mode_end_date, #bwps_authentication_away_mode_start_date" ).datepicker();

	jQuery( "#bwps_authentication_away_mode_enabled" ).change( function() {

		if ( jQuery( "#bwps_authentication_away_mode_enabled" ).is( ':checked' ) ) {

			jQuery( "#authentication_away_mode-settings" ).show();

		} else {

			jQuery( "#authentication_away_mode-settings" ).hide();

		}

	} ).change();

	jQuery( "#bwps_authentication_away_mode_type" ).change( function() {

		if ( jQuery( "#bwps_authentication_away_mode_type" ).val() == "2" ) {

			jQuery( ".end_date_field, .start_date_field" ).closest( "tr" ).show();

		} else {

			jQuery( ".end_date_field, .start_date_field" ).closest( "tr" ).hide();

		}

	} ).change();

} );
