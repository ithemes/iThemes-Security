jQuery( document ).ready( function () {

	jQuery( "#itsec_advanced_tweaks_enabled" ).change(function () {

		if ( jQuery( "#itsec_advanced_tweaks_enabled" ).is( ':checked' ) ) {
			jQuery( "#advanced_tweaks_server, #advanced_tweaks_wordpress, h2.settings-section-header" ).show();

		} else {
			jQuery( "#advanced_tweaks_server, #advanced_tweaks_wordpress, h2.settings-section-header" ).hide();

		}

	} ).change();

	if ( jQuery.getUrlVars()['itsec_action'] !== 'undefinied' && jQuery.getUrlVars()['itsec_action'] == 'fix_error' ) {
		jQuery( "#itsec_advanced_tweaks_enabled" ).attr( 'checked', true );
		jQuery( "#advanced_tweaks_server, #advanced_tweaks_wordpress, h2.settings-section-header" ).show();
	}

} );