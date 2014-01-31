href = location.href;

var _gaq = _gaq || [];
_gaq.push( ['_setAccount', 'UA-47645120-1'] );
_gaq.push( ['_setCampNameKey', 'm'] );                 // campaign name
_gaq.push( ['_setCampMediumKey', 'med'] );             // campaign medium
_gaq.push( ['_setCampSourceKey', 'pl'] );              // campaign source
_gaq.push( ['_setCampTermKey', 'ver'] );               // campaign term/keyword
_gaq.push( ['_setCampContentKey', 'cr'] );             // content

_gaq.push( ['_trackPageview'] );

(function () {
	var ga = document.createElement( 'script' );
	ga.type = 'text/javascript';
	ga.async = true;
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName( 'script' )[0];
	s.parentNode.insertBefore( ga, s );
})();

jQuery( document ).ready( function () {

	var b = 'test';

	jQuery( '.itsec-form' ).find( 'input' ).attr( "onClick", "_gaq.push(['_trackEvent', 'Outbound Link', 'Outbound Link', '" + b + "' ]);" );

} );
