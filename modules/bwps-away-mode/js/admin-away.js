jQuery( document ).ready( function () {

	jQuery( "#bwps_away_mode_end_date" ).datepicker();

	jQuery( "#bwps_away_mode_enabled" ).change( function() {

		if ( jQuery( "#bwps_away_mode_enabled" ).is( ':checked' ) ) {

			jQuery( "#away_mode_settings" ).css( "display", "block" );

		} else {

			jQuery( "#away_mode_settings" ).css( "display", "none" );

		}

	} ).change();

	jQuery( "#bwps_away_mode_type" ).change( function() {

		if ( jQuery( "#bwps_away_mode_type" ).val() == '2' ) {

			jQuery( "#bwps_away_mode_end_date" ).css( "display", "block" );

		} else {

			jQuery( "#bwps_away_mode_end_date" ).css( "display", "none" );

		}

	} ).change();

} );