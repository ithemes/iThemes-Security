jQuery( document ).ready( function () {

	jQuery( '.itsec_ajax_form' ).submit( function () {

		var item = this;

		event.preventDefault();

		var data = {
			action: 'itsec_sidebar',
			option: jQuery( this ).find( '[name=itsec_option]' ).val(),
			setting: jQuery( this ).find( '[name=itsec_setting]' ).val(),
			value: jQuery( this ).find( '[name=itsec_value]' ).val(),
			nonce: jQuery( this ).find( '[name=itsec_sidebar_nonce]' ).val(),
			field_id: jQuery( this ).find( '[name=itsec_field_id]' ).val()
		};

		jQuery.post( ajax_object.ajax_url, data, function ( response ) {

			if ( response != false ) {

				var form_element = "#" + response;

				jQuery( item ).parents( '.itsec-status-feed-item' ).removeClass( 'incomplete' ).addClass( 'complete' );
				jQuery( form_element ).prop( 'checked', true );
				jQuery( item ).remove();

			} else {
				//how to handle failure
			}

		} );

		return false;

	} );
	
	jQuery( '.itsec-why a' ).on( 'click', function() {
		event.preventDefault();
		target = jQuery(this).parent().siblings( '.why-text' );
		
		jQuery(target).slideToggle();
	});

} );
