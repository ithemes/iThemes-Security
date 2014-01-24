<?php

$_POST['dir'] = urldecode( $_POST['dir'] );

$path = $_SERVER['PHP_SELF'];

$path_info = pathinfo( $path );

if( file_exists( $root . $_POST['dir'] ) ) {

	$files = scandir( $root . $_POST['dir'] );

	natcasesort( $files );

	if( count( $files ) > 2 ) { /* The 2 accounts for . and .. */

		echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";

		// All dirs
		foreach( $files as $file ) {

			if( file_exists( $root . $_POST['dir'] . $file ) && $file != '.' && $file != '..' && is_dir( $root . $_POST['dir'] . $file ) ) {
				echo '<li class="directory collapsed"><a href="#" rel="' . htmlentities( $_POST['dir'] . $file ) . '/">' . htmlentities( $file ) . '<div class="itsec_treeselect_control"><img src="' . $path_info['dirname'] . '/images/redminus.png" style="vertical-align: -3px;" title="Add to exclusions..." class="itsec_filetree_exclude"></div></a></li>';
			}

		}

		// All files
		foreach( $files as $file ) {

			if( file_exists( $root . $_POST['dir'] . $file ) && $file != '.' && $file != '..' && !is_dir( $root . $_POST['dir'] . $file ) ) {

				$ext = preg_replace( '/^.*\./', '', $file );
				echo '<li class="file ext_' . $ext . '"><a href="#" rel="' . htmlentities( $_POST['dir'] . $file ) . '">' . htmlentities( $file ) . '<div class="itsec_treeselect_control"><img src="' . $path_info['dirname'] . '/images/redminus.png" style="vertical-align: -3px;" title="Add to exclusions..." class="itsec_filetree_exclude"></div></a></li>';

			}

		}

		echo "</ul>";	

	}

}
