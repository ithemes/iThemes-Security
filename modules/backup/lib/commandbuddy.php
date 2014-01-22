<?php
// TODO: Eventually pull out all command line execution to run through this library.
//		Currently code is ducplicated between the libraries that run command line commands.

class pb_backupbuddy_commandbuddy {
	
	public function __construct() {
	}
	
	/*	execute()
	 *	
	 *	Execute a command via the command line.
	 *	Example usage:
	 *		list( $exec_output, $exec_exit_code ) = $this->execute( 'COMMANDHHERE' );
	 *	
	 *	@param		string		$command		Command line to run.
	 *	@return		array						Associative array of the response output and exist code. Use with list to assign variables.
	 *											Format: array( EXEC_OUTPUT, EXIT_CODE );
	 */
	public function execute( $command ) {
		if ( stristr( PHP_OS, 'WIN' ) && !stristr( PHP_OS, 'DARWIN' ) ) {
			// Windows; do nothing.
		} else { // Linux/Mac
			if ( ( ini_get( 'exec_dir' ) !== false ) && ( ini_get( 'exec_dir' ) != '' ) ) { // exec_dir PHP patch in place: http://www.kyberdigi.cz/projects/execdir/
				pb_backupbuddy::status( 'details', 'exec_dir detected. Skipping path assignment.' );
			} else {
				pb_backupbuddy::status( 'details', 'exec_dir not detected. Proceeding normally.' );
				$command = 'PATH=$PATH:/usr/bin/:/usr/local/bin/:usr/local/bin:/usr/local/sbin/:/usr/sbin/:/sbin/:/usr/:/bin/' . '; ' . $command;
			}
		}
		
		// Output command (strips mysqldump passwords).
		if ( strstr( $command, '--password=' ) ) {
			$password_portion_begin = strpos( $command, '--password=' );
			$password_portion_end = strpos( $command, ' ', $password_portion_begin );
			//pb_backupbuddy::status( 'details', 'pass start: `' . $password_portion_begin . '`. pass end: `' . $password_portion_end . '`' );
			$password_portion = substr( $command, $password_portion_begin, ( $password_portion_end - $password_portion_begin ) );
			//pb_backupbuddy::status( 'details', 'pass portion: `' . $password_portion . '`.' );
			$unpassworded_command = str_replace( $password_portion, '--password=*HIDDEN*', $command );
			pb_backupbuddy::status( 'details', 'exec() command (password hidden) `' . $unpassworded_command . '` (with path definition).' );
			unset( $unpassworded_command );
		} else {
			pb_backupbuddy::status( 'details', 'exec() command `' . $command . '` (with path definition).' );
		}
		
		$exec_output = array();
		@exec( $command, $exec_output, $exec_exit_code);
		pb_backupbuddy::status( 'details', 'exec() command output: `' . implode( ',', $exec_output ) . '`; Exit code: `' . $exec_exit_code . '`; Exit code description: `' . pb_backupbuddy::$filesystem->exit_code_lookup( $exec_exit_code ) . '`' );
		
		return array( $exec_output, $exec_exit_code );
	} // End execute().
}