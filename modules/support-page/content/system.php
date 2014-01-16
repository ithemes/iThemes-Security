<?php
global $itsec_lib, $wpdb;
$config_file = $itsec_lib->get_config();
$htaccess = $itsec_lib->get_htaccess();
?>

<ul class="itsec-support">
<li>
	<h4><?php _e( 'User Information', 'ithemes-security' ); ?></h4>
	<ul>
		<li><?php _e( 'Public IP Address', 'ithemes-security' ); ?>: <strong><a target="_blank"
																				title="<?php _e( 'Get more information on this address', 'ithemes-security' ); ?>"
																				href="http://whois.domaintools.com/<?php echo $itsec_lib->get_ip(); ?>"><?php echo $itsec_lib->get_ip(); ?></a></strong>
		</li>
		<li><?php _e( 'User Agent', 'ithemes-security' ); ?>:
			<strong><?php echo filter_var( $_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_STRING ); ?></strong></li>
	</ul>
</li>

<li>
	<h4><?php _e( 'File System Information', 'ithemes-security' ); ?></h4>
	<ul>
		<li><?php _e( 'Website Root Folder', 'ithemes-security' ); ?>: <strong><?php echo get_site_url(); ?></strong>
		</li>
		<li><?php _e( 'Document Root Path', 'ithemes-security' ); ?>:
			<strong><?php echo filter_var( $_SERVER['DOCUMENT_ROOT'], FILTER_SANITIZE_STRING ); ?></strong></li>
		<?php
		if ( @is_writable( $htaccess ) ) {

			$copen  = '<font color="red">';
			$cclose = '</font>';
			$htaw   = __( 'Yes', 'ithemes-security' );

		} else {

			$copen  = '';
			$cclose = '';
			$htaw   = __( 'No.', 'ithemes-security' );

		}
		?>
		<li><?php _e( '.htaccess File is Writable', 'ithemes-security' ); ?>:
			<strong><?php echo $copen . $htaw . $cclose; ?></strong></li>
		<?php
		if ( @is_writable( $config_file ) ) {

			$copen  = '<font color="red">';
			$cclose = '</font>';
			$wconf  = __( 'Yes', 'ithemes-security' );

		} else {

			$copen  = '';
			$cclose = '';
			$wconf  = __( 'No.', 'ithemes-security' );

		}
		?>
		<li><?php _e( 'wp-config.php File is Writable', 'ithemes-security' ); ?>:
			<strong><?php echo $copen . $wconf . $cclose; ?></strong></li>
	</ul>
</li>

<li>
	<h4><?php _e( 'Database Information', 'ithemes-security' ); ?></h4>
	<ul>
		<li><?php _e( 'MySQL Database Version', 'ithemes-security' ); ?>
			: <?php $sqlversion = $wpdb->get_var( "SELECT VERSION() AS version" ); ?>
			<strong><?php echo $sqlversion; ?></strong></li>
		<li><?php _e( 'MySQL Client Version', 'ithemes-security' ); ?>:
			<strong><?php echo mysql_get_client_info(); ?></strong></li>
		<li><?php _e( 'Database Host', 'ithemes-security' ); ?>: <strong><?php echo DB_HOST; ?></strong></li>
		<li><?php _e( 'Database Name', 'ithemes-security' ); ?>: <strong><?php echo DB_NAME; ?></strong></li>
		<li><?php _e( 'Database User', 'ithemes-security' ); ?>: <strong><?php echo DB_USER; ?></strong></li>
		<?php $mysqlinfo = $wpdb->get_results( "SHOW VARIABLES LIKE 'sql_mode'" );
		if ( is_array( $mysqlinfo ) )
			$sql_mode = $mysqlinfo[0]->Value;
		if ( empty( $sql_mode ) )
			$sql_mode = __( 'Not Set', 'ithemes-security' );
		else $sql_mode = __( 'Off', 'ithemes-security' );
		?>
		<li><?php _e( 'SQL Mode', 'ithemes-security' ); ?>: <strong><?php echo $sql_mode; ?></strong></li>
	</ul>
</li>

<li>
	<h4><?php _e( 'Server Information', 'ithemes-security' ); ?></h4>
	<?php $server_addr = array_key_exists( 'SERVER_ADDR', $_SERVER ) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR']; ?>
	<ul>
		<li><?php _e( 'Server / Website IP Address', 'ithemes-security' ); ?>: <strong><a target="_blank"
																						  title="<?php _e( 'Get more information on this address', 'ithemes-security' ); ?>"
																						  href="http://whois.domaintools.com/<?php echo $server_addr; ?>"><?php echo $server_addr; ?></a></strong>
		</li>
		<li><?php _e( 'Server Type', 'ithemes-security' ); ?>:
			<strong><?php echo filter_var( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ), FILTER_SANITIZE_STRING ); ?></strong>
		</li>
		<li><?php _e( 'Operating System', 'ithemes-security' ); ?>: <strong><?php echo PHP_OS; ?></strong></li>
		<li><?php _e( 'Browser Compression Supported', 'ithemes-security' ); ?>:
			<strong><?php echo filter_var( $_SERVER['HTTP_ACCEPT_ENCODING'], FILTER_SANITIZE_STRING ); ?></strong></li>
	</ul>
</li>

<li>
	<h4><?php _e( 'PHP Information', 'ithemes-security' ); ?></h4>
	<ul>
		<li><?php _e( 'PHP Version', 'ithemes-security' ); ?>: <strong><?php echo PHP_VERSION; ?></strong></li>
		<li><?php _e( 'PHP Memory Usage', 'ithemes-security' ); ?>:
			<strong><?php echo round( memory_get_usage() / 1024 / 1024, 2 ) . __( ' MB', 'ithemes-security' ); ?></strong>
		</li>
		<?php
		if ( ini_get( 'memory_limit' ) ) {
			$memory_limit = filter_var( ini_get( 'memory_limit' ), FILTER_SANITIZE_STRING );
		} else {
			$memory_limit = __( 'N/A', 'ithemes-security' );
		}
		?>
		<li><?php _e( 'PHP Memory Limit', 'ithemes-security' ); ?>: <strong><?php echo $memory_limit; ?></strong></li>
		<?php
		if ( ini_get( 'upload_max_filesize' ) ) {
			$upload_max = filter_var( ini_get( 'upload_max_filesize' ), FILTER_SANITIZE_STRING );
		} else {
			$upload_max = __( 'N/A', 'ithemes-security' );
		}
		?>
		<li><?php _e( 'PHP Max Upload Size', 'ithemes-security' ); ?>: <strong><?php echo $upload_max; ?></strong></li>
		<?php
		if ( ini_get( 'post_max_size' ) ) {
			$post_max = filter_var( ini_get( 'post_max_size' ), FILTER_SANITIZE_STRING );
		} else {
			$post_max = __( 'N/A', 'ithemes-security' );
		}
		?>
		<li><?php _e( 'PHP Max Post Size', 'ithemes-security' ); ?>: <strong><?php echo $post_max; ?></strong></li>
		<?php
		if ( ini_get( 'safe_mode' ) ) {
			$safe_mode = __( 'On', 'ithemes-security' );
		} else {
			$safe_mode = __( 'Off', 'ithemes-security' );
		}
		?>
		<li><?php _e( 'PHP Safe Mode', 'ithemes-security' ); ?>: <strong><?php echo $safe_mode; ?></strong></li>
		<?php
		if ( ini_get( 'allow_url_fopen' ) ) {
			$allow_url_fopen = __( 'On', 'ithemes-security' );
		} else {
			$allow_url_fopen = __( 'Off', 'ithemes-security' );
		}
		?>
		<li><?php _e( 'PHP Allow URL fopen', 'ithemes-security' ); ?>: <strong><?php echo $allow_url_fopen; ?></strong>
		</li>
		<?php
		if ( ini_get( 'allow_url_include' ) ) {
			$allow_url_include = __( 'On', 'ithemes-security' );
		} else {
			$allow_url_include = __( 'Off', 'ithemes-security' );
		}
		?>
		<li><?php _e( 'PHP Allow URL Include' ); ?>: <strong><?php echo $allow_url_include; ?></strong></li>
		<?php
		if ( ini_get( 'display_errors' ) ) {
			$display_errors = __( 'On', 'ithemes-security' );
		} else {
			$display_errors = __( 'Off', 'ithemes-security' );
		}
		?>
		<li><?php _e( 'PHP Display Errors', 'ithemes-security' ); ?>: <strong><?php echo $display_errors; ?></strong>
		</li>
		<?php
		if ( ini_get( 'display_startup_errors' ) ) {
			$display_startup_errors = __( 'On', 'ithemes-security' );
		} else {
			$display_startup_errors = __( 'Off', 'ithemes-security' );
		}
		?>
		<li><?php _e( 'PHP Display Startup Errors', 'ithemes-security' ); ?>:
			<strong><?php echo $display_startup_errors; ?></strong></li>
		<?php
		if ( ini_get( 'expose_php' ) ) {
			$expose_php = __( 'On', 'ithemes-security' );
		} else {
			$expose_php = __( 'Off', 'ithemes-security' );
		}
		?>
		<li><?php _e( 'PHP Expose PHP', 'ithemes-security' ); ?>: <strong><?php echo $expose_php; ?></strong></li>
		<?php
		if ( ini_get( 'register_globals' ) ) {
			$register_globals = __( 'On', 'ithemes-security' );
		} else {
			$register_globals = __( 'Off', 'ithemes-security' );
		}
		?>
		<li><?php _e( 'PHP Register Globals', 'ithemes-security' ); ?>:
			<strong><?php echo $register_globals; ?></strong></li>
		<?php
		if ( ini_get( 'max_execution_time' ) ) {
			$max_execute = filter_var( ini_get( 'max_execution_time' ) );
		} else {
			$max_execute = __( 'N/A', 'ithemes-security' );
		}
		?>
		<li><?php _e( 'PHP Max Script Execution Time' ); ?>:
			<strong><?php echo $max_execute; ?> <?php _e( 'Seconds' ); ?></strong></li>
		<?php
		if ( ini_get( 'magic_quotes_gpc' ) ) {
			$magic_quotes_gpc = __( 'On', 'ithemes-security' );
		} else {
			$magic_quotes_gpc = __( 'Off', 'ithemes-security' );
		}
		?>
		<li><?php _e( 'PHP Magic Quotes GPC', 'ithemes-security' ); ?>:
			<strong><?php echo $magic_quotes_gpc; ?></strong></li>
		<?php
		if ( ini_get( 'open_basedir' ) ) {
			$open_basedir = __( 'On', 'ithemes-security' );
		} else {
			$open_basedir = __( 'Off', 'ithemes-security' );
		}
		?>
		<li><?php _e( 'PHP open_basedir', 'ithemes-security' ); ?>: <strong><?php echo $open_basedir; ?></strong></li>
		<?php
		if ( is_callable( 'xml_parser_create' ) ) {
			$xml = __( 'Yes', 'ithemes-security' );
		} else {
			$xml = __( 'No', 'ithemes-security' );
		}
		?>
		<li><?php _e( 'PHP XML Support', 'ithemes-security' ); ?>: <strong><?php echo $xml; ?></strong></li>
		<?php
		if ( is_callable( 'iptcparse' ) ) {
			$iptc = __( 'Yes', 'ithemes-security' );
		} else {
			$iptc = __( 'No', 'ithemes-security' );
		}
		?>
		<li><?php _e( 'PHP IPTC Support', 'ithemes-security' ); ?>: <strong><?php echo $iptc; ?></strong></li>
		<?php
		if ( is_callable( 'exif_read_data' ) ) {
			$exif = __( 'Yes', 'ithemes-security' ) . " ( V" . substr( phpversion( 'exif' ), 0, 4 ) . ")";
		} else {
			$exif = __( 'No', 'ithemes-security' );
		}
		?>
		<li><?php _e( 'PHP Exif Support', 'ithemes-security' ); ?>: <strong><?php echo $exif; ?></strong></li>
	</ul>
</li>

<li>
	<h4><?php _e( 'WordPress Configuration', 'ithemes-security' ); ?></h4>
	<ul>
		<?php
		if ( is_multisite() ) {
			$multSite = __( 'Multisite is enabled', 'ithemes-security' );
		} else {
			$multSite = __( 'Multisite is NOT enabled', 'ithemes-security' );
		}
		?>
		<li><?php _e( '	Multisite', 'ithemes-security' ); ?>: <strong><?php echo $multSite; ?></strong></li>
		<?php
		if ( get_option( 'permalink_structure' ) != '' ) {
			$copen               = '';
			$cclose              = '';
			$permalink_structure = __( 'Enabled', 'ithemes-security' );
		} else {
			$copen               = '<font color="red">';
			$cclose              = '</font>';
			$permalink_structure = __( 'WARNING! Permalinks are NOT Enabled. Permalinks MUST be enabled for iThemes Security to function correctly', 'ithemes-security' );
		}
		?>
		<li><?php _e( 'WP Permalink Structure', 'ithemes-security' ); ?>:
			<strong> <?php echo $copen . $permalink_structure . $cclose; ?></strong></li>
		<li><?php _e( 'Wp-config Location', 'ithemes-security' ); ?>: <strong><?php echo $config_file ?></strong></li>
	</ul>
</li>
<li>
	<h4><?php _e( 'iThemes Security variables', 'ithemes-security' ); ?></h4>
	<?php /*
		<ul>
			<li><?php _e( 'iThemes SecurityBuild Version', 'ithemes-security' );?>: <strong><?php echo $itsecdata['version']; ?></strong><br />
			<em><?php _e( 'Note: this is NOT the same as the version number on the plugin page or WordPress.org page and is instead used for support.', 'ithemes-security' ); ?></em></li>
		</ul>
		*/
	?>
</li>
</ul>