jQuery( document ).ready( function () {

	jQuery( '.itsec_ajax_form' ).submit( function () {

		event.preventDefault();

		var data = {
			action: 'itsec_sidebar',
			option: jQuery( this ).find( '[name=itsec_option]' ).val(),
			setting: jQuery( this ).find( '[name=itsec_setting]' ).val(),
			value: jQuery( this ).find( '[name=itsec_value]' ).val()
		};

		console.log( data );

		jQuery.post( ajax_object.ajax_url, data, function ( response ) {
			console.log( 'Success' );
		} );

		return false;

	} );

} );
