jQuery( document ).ready( function () {

    jQuery( '#bwps_ssl_admin, #bwps_ssl_login' ).change( function () {

        if ( this.checked ) {

            alert( ssl_warning_text.text );

        }

    } );

} );