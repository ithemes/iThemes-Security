jQuery( document ).ready( function () {

	jQuery( "#bwps_away_mode_end_date, #bwps_away_mode_start_date" ).datepicker();

	jQuery( "#bwps_away_mode_enabled" ).change( function() {

		if ( jQuery( "#bwps_away_mode_enabled" ).is( ':checked' ) ) {

			jQuery( "#away_mode_settings" ).show();

		} else {

			jQuery( "#away_mode_settings" ).hide();

		}

	} ).change();

	jQuery( "#bwps_away_mode_type" ).change( function() {

		if ( jQuery( "#bwps_away_mode_type" ).val() == "2" ) {

			jQuery( ".end_date_field, .start_date_field" ).closest( "tr" ).show();

		} else {

			jQuery( ".end_date_field, .start_date_field" ).closest( "tr" ).hide();

		}

	} ).change();

} );