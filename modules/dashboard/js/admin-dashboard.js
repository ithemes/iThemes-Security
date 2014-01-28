jQuery( document ).ready( function () {

	jQuery( '.itsec_ajax_form' ).submit( function () {

		event.preventDefault();

		var data = {
			action: 'itsec_sidebar',
			whatever: ajax_object.we_value      // We pass php values differently!
		};

		console.log( ajax_object.we_value );

		jQuery.post( ajax_object.ajax_url, data, function(response) {
			alert('Got this from the server: ' + response);
		});

		return false;

	} );

} );
