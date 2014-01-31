href = location.href;

var _gaq = _gaq || [];
_gaq.push( ['_setAccount', 'UA-47645120-1'] );

(function () {
	var ga = document.createElement( 'script' );
	ga.type = 'text/javascript';
	ga.async = true;
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName( 'script' )[0];
	s.parentNode.insertBefore( ga, s );
})();

jQuery( document ).ready( function () {

	jQuery( '.itsec-form' ).submit( function ( event ) {

		event.preventDefault();

		var section = tracking_section;
		var timestamp = new Date().getTime();
		var values = jQuery( this ).serializeArray();

		jQuery.each( values, function ( name, value ) {

			var setting = value.name.substring( value.name.indexOf( '[' ) + 1, value.name.indexOf( ']' ) );

			if ( setting.length > 0 ) {

				var index =  tracking_items.indexOf( setting );

				if ( index !== - 1 ) {

					var value_array = tracking_values[setting].split( ':' );
					var default_type = value_array[1];

					if ( default_type == 'b' && value.value == 1 ) {
						var saved_value = 'true';
					} else {
						var saved_value = value.value;
					}

					tracking_items.splice( index, 1 );

					_gaq.push( ['_trackEvent', section, setting, saved_value, timestamp, true] );

				}

			}

		} );

		jQuery.each( tracking_items, function( item, value ) {

			var value_array = tracking_values[value].split( ':' );
			var default_value = value_array[0];
			var default_type = value_array[1];

			if ( default_type == 'b' && default_value == 0 ) {
				var saved_value = 'false';
			} else if ( default_type == 'b' ) {
				var saved_value = default_value;
			}

			_gaq.push( ['_trackEvent', section, value   , saved_value, timestamp, true] );

		} );

	} );

} );
