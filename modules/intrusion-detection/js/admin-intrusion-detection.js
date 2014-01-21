jQuery( document ).ready( function () {

	jQuery( "#itsec_intrusion_detection_four_oh_four_enabled" ).change(function () {

		if ( jQuery( "#itsec_intrusion_detection_four_oh_four_enabled" ).is( ':checked' ) ) {

			jQuery( "#intrusion_detection_four_oh_four-settings" ).show();

		} else {

			jQuery( "#intrusion_detection_four_oh_four-settings" ).hide();

		}

	} ).change();

} );
