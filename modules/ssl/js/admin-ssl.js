jQuery( document ).ready( function () {

	jQuery( '#itsec_ssl_admin, #itsec_ssl_login' ).change( function () {

		if ( this.checked ) {

			alert( ssl_warning_text.text );

		}

	} );

} );