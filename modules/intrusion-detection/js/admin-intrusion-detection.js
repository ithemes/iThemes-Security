jQuery( document ).ready( function () {

	jQuery( "#itsec_intrusion_detection_four_oh_four_enabled" ).change( function () {

		if ( jQuery( "#itsec_intrusion_detection_four_oh_four_enabled" ).is( ':checked' ) ) {

			jQuery( "#intrusion_detection_four_oh_four-settings" ).show();

		} else {

			jQuery( "#intrusion_detection_four_oh_four-settings" ).hide();

		}

	} ).change();

	if ( jQuery( 'p.noPermalinks' ).length ) {
		jQuery( "#intrusion_detection_four_oh_four-settings" ).hide();
	}

	jQuery( "#itsec_intrusion_detection_file_change_enabled" ).change( function () {

		if ( jQuery( "#itsec_intrusion_detection_file_change_enabled" ).is( ':checked' ) ) {

			jQuery( "#intrusion_detection_file_change-settings" ).show();

		} else {

			jQuery( "#intrusion_detection_file_change-settings" ).hide();

		}

	} ).change();

	if ( itsec_mem_limit.mem_limit <= 128 ) {

		jQuery( "#itsec_intrusion_detection_file_change_enabled" ).change( function () {

			if ( this.checked ) {
				alert( itsec_mem_limit.text );

			}

		} );

	}

	jQuery( '.filetree' ).fileTree({root: '../', script: 'filetree/jqueryFileTree.php'}, function(file) {
		console.log(file);
	});

} );
