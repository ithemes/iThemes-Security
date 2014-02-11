<?php

if ( ! class_exists( 'Clef' ) ) {
	require( dirname( __FILE__ ) . '/inc/wpclef/clef-require.php' );
}

if ( ! class_exists( 'ITSEC_Clef_Admin' ) ) {
	require( dirname( __FILE__ ) . '/class-itsec-clef-admin.php' );
}

$options = get_site_option( 'itsec_clef' );
if (!isset($options['enabled'])) {
    $options['enabled'] = true;
    update_site_option( 'itsec_clef', $options);
}

$clef = null;
if ($options['enabled'] == true) {
    $clef = Clef::start();
}
ITSEC_Clef_Admin::start( $this, $clef );

?>
