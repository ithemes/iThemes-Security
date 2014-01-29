jQuery( document ).ready( function () {

	jQuery( '.itsec_ajax_form' ).submit( function () {

		var item = this;

		event.preventDefault();

		var data = {
			action: 'itsec_sidebar',
			option: jQuery( this ).find( '[name=itsec_option]' ).val(),
			setting: jQuery( this ).find( '[name=itsec_setting]' ).val(),
			value: jQuery( this ).find( '[name=itsec_value]' ).val(),
			nonce: jQuery( this ).find( '[name=itsec_sidebar_nonce]' ).val()
		};

		jQuery.post( ajax_object.ajax_url, data, function ( response ) {

			if ( response == true ) {

				jQuery( item ).parents( '.itsec-status-feed-item' ).removeClass( 'incomplete' ).addClass( 'complete' );

			} else {
				//how to handle failure
			}

		} );

		return false;

	} );

} );
