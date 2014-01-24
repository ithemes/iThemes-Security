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

	if ( itsec_intrusion_detection.mem_limit <= 128 ) {

		jQuery( "#itsec_intrusion_detection_file_change_enabled" ).change( function () {

			if ( this.checked ) {
				alert( itsec_intrusion_detection.text );

			}

		} );

	}

	jQuery( '.jquery_file_tree' ).fileTree( 
		{ 
			root: itsec_intrusion_detection.ABSPATH,
			script: itsec_intrusion_detection.plug_path + 'modules/intrusion-detection/connector.php',
			expandSpeed: -1,
			collapseSpeed: -1,
			multiFolder: false

		},	function( file ) {
				
			jQuery( '#itsec_intrusion_detection_file_change_list' ).val( file.substring( itsec_intrusion_detection.ABSPATH.length ) + "\n" + jQuery( '#itsec_intrusion_detection_file_change_list' ).val() );
				
		}, function( directory ) {
				
			jQuery( '#itsec_intrusion_detection_file_change_list' ).val( directory.substring( itsec_intrusion_detection.ABSPATH.length ) + "\n" + jQuery( '#itsec_intrusion_detection_file_change_list' ).val() );
				
		} 
	);

} );

jQuery( window ).load( function() {

	jQuery( document ).on( 'mouseover mouseout', '.jqueryFileTree > li a', function( event ) {
		if ( event.type == 'mouseover' ) {
			jQuery( this ).children( '.itsec_treeselect_control' ).css( 'visibility', 'visible' );
		} else {
			jQuery( this ).children( '.itsec_treeselect_control' ).css( 'visibility', 'hidden' );
		}
	} );

} );
