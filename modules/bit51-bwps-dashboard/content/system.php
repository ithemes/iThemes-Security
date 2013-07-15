<?php 
	global $bwps_utilities, $wpdb; 
	$config_file = $bwps_utilities->get_config();
	$htaccess = ABSPATH . '.htaccess';
?>

<ul>
	<li>
		<h4><?php _e( 'User Information', 'better-wp-security' ); ?></h4>
		<ul>
			<li><?php _e( 'Public IP Address', 'better-wp-security' ); ?>: <strong><a target="_blank" title="<?php _e( 'Get more information on this address', 'better-wp-security' ); ?>" href="http://whois.domaintools.com/<?php echo $bwps_utilities->get_ip(); ?>"><?php echo $bwps_utilities->get_ip(); ?></a></strong></li>
			<li><?php _e( 'User Agent', 'better-wp-security' ); ?>: <strong><?php echo filter_var( $_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_STRING ); ?></strong></li>
		</ul>
	</li>
	
	<li>
		<h4><?php _e( 'File System Information', 'better-wp-security' ); ?></h4>
		<ul>
			<li><?php _e( 'Website Root Folder', 'better-wp-security' ); ?>: <strong><?php echo get_site_url(); ?></strong></li>
			<li><?php _e( 'Document Root Path', 'better-wp-security' ); ?>: <strong><?php echo filter_var( $_SERVER['DOCUMENT_ROOT'], FILTER_SANITIZE_STRING ); ?></strong></li>
			<?php 
				if ( @is_writable( $htaccess ) ) { 
				
					$copen = '<font color="red">';
					$cclose = '</font>';
					$htaw = __( 'Yes', 'better-wp-security' ); 
					
				} else {
				
					$copen = '';
					$cclose = '';
					$htaw = __( 'No.', 'better-wp-security' ); 
					
				}
			?>
			<li><?php _e( '.htaccess File is Writable', 'better-wp-security' ); ?>: <strong><?php echo $copen . $htaw . $cclose; ?></strong></li>
			<?php 
				if ( @is_writable( $config_file ) ) { 
				
					$copen = '<font color="red">';
					$cclose = '</font>';
					$wconf = __( 'Yes', 'better-wp-security' ); 
					
				} else {
				
					$copen = '';
					$cclose = '';
					$wconf = __( 'No.', 'better-wp-security' ); 
					
				}
			?>
			<li><?php _e( 'wp-config.php File is Writable', 'better-wp-security' ); ?>: <strong><?php echo $copen . $wconf . $cclose; ?></strong></li>
		</ul>
	</li>

	<li>
		<h4><?php _e( 'Database Information', 'better-wp-security' ); ?></h4>
		<ul>
			<li><?php _e( 'MySQL Database Version', 'better-wp-security' ); ?>: <?php $sqlversion = $wpdb->get_var( "SELECT VERSION() AS version" ); ?><strong><?php echo $sqlversion; ?></strong></li>
			<li><?php _e( 'MySQL Client Version', 'better-wp-security' ); ?>: <strong><?php echo mysql_get_client_info(); ?></strong></li>
			<li><?php _e( 'Database Host', 'better-wp-security' ); ?>: <strong><?php echo DB_HOST; ?></strong></li>
			<li><?php _e( 'Database Name', 'better-wp-security' ); ?>: <strong><?php echo DB_NAME; ?></strong></li>
			<li><?php _e( 'Database User', 'better-wp-security' ); ?>: <strong><?php echo DB_USER; ?></strong></li>
			<?php $mysqlinfo = $wpdb->get_results( "SHOW VARIABLES LIKE 'sql_mode'" );
				if ( is_array( $mysqlinfo ) ) $sql_mode = $mysqlinfo[0]->Value;
				if ( empty( $sql_mode ) ) $sql_mode = __( 'Not Set', 'better-wp-security' );
				else $sql_mode = __( 'Off', 'better-wp-security' );
			?>
			<li><?php _e( 'SQL Mode', 'better-wp-security' ); ?>: <strong><?php echo $sql_mode; ?></strong></li>
		</ul>
	</li>
	
	<li>
		<h4><?php _e( 'Server Information', 'better-wp-security' ); ?></h4>
		<?php $server_addr = array_key_exists('SERVER_ADDR',$_SERVER) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR']; ?>
		<ul>
			<li><?php _e( 'Server / Website IP Address', 'better-wp-security' ); ?>: <strong><a target="_blank" title="<?php _e( 'Get more information on this address', 'better-wp-security' ); ?>" href="http://whois.domaintools.com/<?php echo $server_addr; ?>"><?php echo $server_addr; ?></a></strong></li>
				<li><?php _e( 'Server Type', 'better-wp-security' ); ?>: <strong><?php echo filter_var( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ), FILTER_SANITIZE_STRING ); ?></strong></li>
				<li><?php _e( 'Operating System', 'better-wp-security' ); ?>: <strong><?php echo PHP_OS; ?></strong></li>
				<li><?php _e( 'Browser Compression Supported', 'better-wp-security' ); ?>: <strong><?php echo filter_var( $_SERVER['HTTP_ACCEPT_ENCODING'], FILTER_SANITIZE_STRING ); ?></strong></li>
		</ul>
	</li>
	
	<li>
		<h4><?php _e( 'PHP Information', 'better-wp-security' ); ?></h4>
		<ul>
			<li><?php _e( 'PHP Version', 'better-wp-security' ); ?>: <strong><?php echo PHP_VERSION; ?></strong></li>
			<li><?php _e( 'PHP Memory Usage', 'better-wp-security' ); ?>: <strong><?php echo round(memory_get_usage() / 1024 / 1024, 2) . __( ' MB', 'better-wp-security' ); ?></strong> </li>
			<?php 
				if ( ini_get( 'memory_limit' ) ) {
					$memory_limit = filter_var( ini_get( 'memory_limit' ), FILTER_SANITIZE_STRING ); 
				} else {
					$memory_limit = __( 'N/A', 'better-wp-security' ); 
				}
			?>
			<li><?php _e( 'PHP Memory Limit', 'better-wp-security' ); ?>: <strong><?php echo $memory_limit; ?></strong></li>
			<?php 
				if ( ini_get( 'upload_max_filesize' ) ) {
					$upload_max = filter_var( ini_get( 'upload_max_filesize' ), FILTER_SANITIZE_STRING );
				} else 	{
					$upload_max = __( 'N/A', 'better-wp-security' ); 
				}
			?>
			<li><?php _e( 'PHP Max Upload Size', 'better-wp-security' ); ?>: <strong><?php echo $upload_max; ?></strong></li>
			<?php 
				if ( ini_get( 'post_max_size' ) ) {
					$post_max = filter_var( ini_get( 'post_max_size' ), FILTER_SANITIZE_STRING );
				} else {
					$post_max = __( 'N/A', 'better-wp-security' ); 
				}
			?>
			<li><?php _e( 'PHP Max Post Size', 'better-wp-security' ); ?>: <strong><?php echo $post_max; ?></strong></li>
			<?php 
				if ( ini_get( 'safe_mode' ) ) {
					$safe_mode = __( 'On', 'better-wp-security' );
				} else {
					$safe_mode = __( 'Off', 'better-wp-security' ); 
				}
			?>
			<li><?php _e( 'PHP Safe Mode', 'better-wp-security' ); ?>: <strong><?php echo $safe_mode; ?></strong></li>
			<?php 
				if ( ini_get( 'allow_url_fopen' ) ) {
					$allow_url_fopen = __( 'On', 'better-wp-security' );
				} else {
					$allow_url_fopen = __( 'Off', 'better-wp-security' ); 
				}
			?>
			<li><?php _e( 'PHP Allow URL fopen', 'better-wp-security' ); ?>: <strong><?php echo $allow_url_fopen; ?></strong></li>
			<?php 
				if ( ini_get( 'allow_url_include' ) ) {
					$allow_url_include = __( 'On', 'better-wp-security' );
				} else {
					$allow_url_include = __( 'Off', 'better-wp-security' ); 
				}
			?>
			<li><?php _e( 'PHP Allow URL Include' ); ?>: <strong><?php echo $allow_url_include; ?></strong></li>
				<?php 
				if ( ini_get( 'display_errors' ) ) {
					$display_errors = __( 'On', 'better-wp-security' );
				} else {
					$display_errors = __( 'Off', 'better-wp-security' ); 
				}
			?>
			<li><?php _e( 'PHP Display Errors', 'better-wp-security' ); ?>: <strong><?php echo $display_errors; ?></strong></li>
			<?php 
				if ( ini_get( 'display_startup_errors' ) ) {
					$display_startup_errors = __( 'On', 'better-wp-security' );
				} else {
					$display_startup_errors = __( 'Off', 'better-wp-security' ); 
				}
			?>
			<li><?php _e( 'PHP Display Startup Errors', 'better-wp-security' ); ?>: <strong><?php echo $display_startup_errors; ?></strong></li>
			<?php 
				if ( ini_get( 'expose_php' ) ) {
					$expose_php = __( 'On', 'better-wp-security' );
				} else {
					$expose_php = __( 'Off', 'better-wp-security' ); 
				}
			?>
			<li><?php _e( 'PHP Expose PHP', 'better-wp-security' ); ?>: <strong><?php echo $expose_php; ?></strong></li>
			<?php 
				if ( ini_get( 'register_globals' ) ) {
					$register_globals = __( 'On', 'better-wp-security' );
				} else {
					$register_globals = __( 'Off', 'better-wp-security' ); 
				}
			?>
			<li><?php _e( 'PHP Register Globals', 'better-wp-security' ); ?>: <strong><?php echo $register_globals; ?></strong></li>
			<?php 
				if ( ini_get( 'max_execution_time' ) ) {
					$max_execute = ini_get( 'max_execution_time' );
				} else {
					$max_execute = __( 'N/A', 'better-wp-security' ); 
				}
			?>
			<li><?php _e( 'PHP Max Script Execution Time' ); ?>: <strong><?php echo $max_execute; ?> <?php _e( 'Seconds' ); ?></strong></li>
			<?php 
				if ( ini_get( 'magic_quotes_gpc' ) ) {
					$magic_quotes_gpc = __( 'On', 'better-wp-security' );
				} else {
					$magic_quotes_gpc = __( 'Off', 'better-wp-security' ); 
				}
			?>
			<li><?php _e( 'PHP Magic Quotes GPC', 'better-wp-security' ); ?>: <strong><?php echo $magic_quotes_gpc; ?></strong></li>
			<?php 
				if ( ini_get( 'open_basedir' ) ) {
					$open_basedir = __( 'On', 'better-wp-security' );
				} else {
					$open_basedir = __( 'Off', 'better-wp-security' ); 
				}
			?>
			<li><?php _e( 'PHP open_basedir', 'better-wp-security' ); ?>: <strong><?php echo $open_basedir; ?></strong></li>
			<?php 
				if ( is_callable( 'xml_parser_create' ) ) {
					$xml = __( 'Yes', 'better-wp-security' );
				} else {
					$xml = __( 'No', 'better-wp-security' ); 
				}
			?>
			<li><?php _e( 'PHP XML Support', 'better-wp-security' ); ?>: <strong><?php echo $xml; ?></strong></li>
			<?php 
				if ( is_callable( 'iptcparse' ) ) {
					$iptc = __( 'Yes', 'better-wp-security' );
				} else {
					$iptc = __( 'No', 'better-wp-security' ); 
				}
			?>
			<li><?php _e( 'PHP IPTC Support', 'better-wp-security' ); ?>: <strong><?php echo $iptc; ?></strong></li>
			<?php 
				if ( is_callable( 'exif_read_data' ) ) {
					$exif = __( 'Yes', 'better-wp-security' ). " ( V" . substr(phpversion( 'exif' ),0,4) . ")" ;
				} else {
					$exif = __( 'No', 'better-wp-security' ); 
				}
			?>
			<li><?php _e( 'PHP Exif Support', 'better-wp-security' ); ?>: <strong><?php echo $exif; ?></strong></li>
		</ul>
	</li>
	
	<li>
		<h4><?php _e( 'WordPress Configuration', 'better-wp-security' ); ?></h4>
		<ul>
			<?php
				if ( is_multisite() ) { 
					$multSite = __( 'Multisite is enabled', 'better-wp-security' );
				} else {
					$multSite = __( 'Multisite is NOT enabled', 'better-wp-security' );
				}
				?>
				<li><?php _e( '	Multisite', 'better-wp-security' );?>: <strong><?php echo $multSite; ?></strong></li>
			<?php
				if ( get_option( 'permalink_structure' ) != '' ) { 
					$copen = '';
					$cclose = '';
					$permalink_structure = __( 'Enabled', 'better-wp-security' ); 
				} else {
					$copen = '<font color="red">';
					$cclose = '</font>';
					$permalink_structure = __( 'WARNING! Permalinks are NOT Enabled. Permalinks MUST be enabled for Better WP Security to function correctly', 'better-wp-security' ); 
				}
			?>
			<li><?php _e( 'WP Permalink Structure', 'better-wp-security' ); ?>: <strong> <?php echo $copen . $permalink_structure . $cclose; ?></strong></li>
			<li><?php _e( 'Wp-config Location', 'better-wp-security' );?>: <strong><?php echo $config_file ?></strong></li>
		</ul>
	</li>
	<li>
		<h4><?php _e( 'Better WP Security variables', 'better-wp-security' ); ?></h4>
		<?php /*
		<ul>
			<?php 
				if ( $bwpsoptions['hb_key'] == '' ) {
					$hbkey = __( 'Not Yet Available. Enable Hide Backend mode to generate key.', 'better-wp-security' );
				} else {
					$hbkey = $bwpsoptions['hb_key'];
				}
			?>
			<li><?php _e( 'Hide Backend Key', 'better-wp-security' );?>: <strong><?php echo $hbkey; ?></strong></li>
			<li><?php _e( 'Better WP Build Version', 'better-wp-security' );?>: <strong><?php echo $bwpsdata['version']; ?></strong><br />
			<em><?php _e( 'Note: this is NOT the same as the version number on the plugins page and is instead used for support.', 'better-wp-security' ); ?></em></li>
		</ul>
		*/ ?>
	</li>
</ul>