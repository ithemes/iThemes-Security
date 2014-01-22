<?php
/*	pb_backupbuddy_mysqlbuddy class
 *	
 *	@since 3.0.0
 *
 *	Helps backup and restore database tables.
 *	Dumps a mysql database (all tables, tables with a certain prefix, or none) with additional inclusions/exclusions of tables possible.
 *	Automatically determines available dump methods (unless method is forced). Runs methods in order of preference. Falls back automatically
 *	to any `lesser` methods if any fail.
 *
 *	Requirements:
 *
 *		Expects mysql to already be set up and connected.
 *
 *	General process order:
 *
 *		Construction: __construct() -> [available_zip_methods()]
 *		Dump: dump() -> _calculate_tables() -> [_mysql and/or _php]
 *
 *		Method process:
 *			_mysql method (FAST):
 *				Builds the command line -> runs command via exec() -> checks exit code -> verifies .sql file was created; falls to next method if exit code is bad or .sql file is missing.
 *			_php method (SLOW; compatibility mode; only mode pre-3.0):
 *				Iterates through all tables issuing SQL commands to server to get create statements and all database content. Very brute force method.
 *
 *	@author Dustin Bolton < http://dustinbolton.com >
 */
class pb_backupbuddy_mysqlbuddy {
	
	
	const COMMAND_LINE_LENGTH_CHECK_THRESHOLD = 250;													// If command line is longer than this then we will try to determine max command line length.
	
	
	/********** Properties **********/
	
	
	private $_version = '0.0.1';																		// Internal version number for this library.
	
	private $_database_host = '';																		// Database host/server. @see __construct().
	private $_database_socket = '';																		// If using sockets, points to socket file. Left blank if sockets not in use. @see __construct().
	private $_database_name = '';																		// Database name. @see __construct().
	private $_database_user = '';																		// Database username. @see __construct().
	private $_database_pass = '';																		// Database password. @see __construct().
	private $_database_prefix = '';																		// Database table prefix to backup when in prefix mode. @see __construct().
	
	private $_base_dump_mode = '';																		// Determines base tables to include in backup. Ex: none, all, or by prefix. @see __construct().
	private $_additional_includes = array();															// Additional tables to backup in addition to those determined by base mode.
	private $_additional_excludes = array();															// Tables to exclude from ( $_additional_includes + those determined by base mode ).
	private $_methods = array();																		// Available mechanisms for dumping in order of preference.
	private $_mysql_directories = array();																// Populated by _calculate_mysql_directory().
	private $_default_mysql_directories = array( '/usr/bin/', '/usr/bin/mysql/', '/usr/local/bin/' );	// If mysql tells us where its installed we prepend to this. Beginning and trailing slashes.
	private $_mysql_directory = '';																		// Tested working mysql directory to use for actual dump.
	private $_commandbuddy;
	
	/********** Methods **********/
	
	
	/*	__construct()
	 *	
	 *	Default constructor.
	 *	
	 *	@param		string		$database_host			Host / server of database to pull from. May be in the format: `localhost` for normal; `localhost:/path/to/mysql.sock` for sockets. If sockets then parased and internal class variables set appropriately.
	 *	@param		string		$database_name			Name of database to pull from.
	 *	@param		string		$database_user			User of database to pull from.
	 *	@param		string		$database_pass			Pass of database to pull from.
	 *	@param		string		$database_prefix		Prefix of tables in database to pull from / insert into (only used if base mode is `prefix`).
	 *	@param		array		$force_methods			Optional parameter to override automatic method detection. Skips test and runs first method first.  Falls back to other methods if any failure. Possible methods:  commandline, php
	 *	@return		
	 */
	public function __construct( $database_host, $database_name, $database_user, $database_pass, $database_prefix, $force_methods = array() ) {
		
		// Handles command line execution.
		require_once( pb_backupbuddy::plugin_path() . '/lib/commandbuddy/commandbuddy.php' );
		$this->_commandbuddy = new pb_backupbuddy_commandbuddy();
		
		// Check for use of sockets in host. Handle if using sockets.
		//$database_host = 'localhost:/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
		if ( strpos( $database_host, ':' ) === false ) { // Normal host.
			pb_backupbuddy::status( 'details', 'Database host for dumping: `' . $database_host . '`' );
			$this->_database_host = $database_host;
		} else { // Non-normal host specification.
			$host_split = explode( ':', $database_host );
			if ( !is_numeric( $host_split[1] ) ) { // String so assume a socket.
				pb_backupbuddy::status( 'details', 'Database host (socket) for dumping. Host: `' . $host_split[0] . '`; Socket: `' . $host_split[1] . '`.' );
				$this->_database_host = $host_split[0];
				$this->_database_socket = $host_split[1];
			} else { // Numeric, treat as port and leave as one piece.
				$this->_database_host = $database_host;
			}
		}
		unset( $host_split );
		
		pb_backupbuddy::status( 'details', 'Loading mysqldump library.' );
		pb_backupbuddy::status( 'details', 'Mysql server default directories: `' . implode( ',', $this->_default_mysql_directories ) . '`' );
		
		$this->_database_name = $database_name;
		$this->_database_user = $database_user;
		$this->_database_pass = $database_pass;
		$this->_database_prefix = $database_prefix;
		
		if ( is_array( $force_methods ) ) {
			pb_backupbuddy::status( 'details', 'mysqlbuddy: Force method of `' . count( $force_methods ) . '` passed.' );
		} else {
			pb_backupbuddy::status( 'details', 'mysqlbuddy: Force method not an array.' );
		}
		
		// Set mechanism for dumping / restoring.
		if ( count( $force_methods ) > 0 ) { // Mechanism forced. Overriding automatic check.
			pb_backupbuddy::status( 'message', 'mysqlbuddy: Settings overriding automatic detection of available database dump methods. Using forced override methods: `' . implode( ',', $force_methods ) . '`.' );
			$this->_methods = $force_methods;
		} else { // No method defined; auto-detect the best.
			pb_backupbuddy::status( 'message', 'mysqlbuddy: Method not forced. About to detect directory and available methods.' );
			// Try to determine mysql location / possible locations.
			$this->_mysql_directories = $this->_calculate_mysql_dir(); // Don't need to check this in forced mode.
			
			$this->_methods = $this->available_dump_methods(); // Run after _calculate_mysql_dir().
		}
		pb_backupbuddy::status( 'message', 'mysqlbuddy: Detected database dump methods: `' . implode( ',', $this->_methods ) . '`.' );
		
	} // End __construct().
	
	
	
	/*	available_dump_methods()
	 *	
	 *	function description
	 *	
	 *	@param		
	 *	@return		string				Possible returns:  mysqldump, php
	 */
	public function available_dump_methods() {
		
		pb_backupbuddy::status( 'details', 'mysqldump test: Testing available mysql database dump methods.' );
		if ( function_exists( 'exec' ) ) { // Exec is available so test mysqldump from here.
			pb_backupbuddy::status( 'details', 'mysqldump test: exec() function exists. Testing running mysqldump via exec().' );
			
			
			/********** Begin preparing command **********/
			// Handle Windows wanting .exe.
			if ( stristr( PHP_OS, 'WIN' ) && !stristr( PHP_OS, 'DARWIN' ) ) { // Running Windows. (not darwin)
				$command = 'msqldump.exe';
			} else {
				$command = 'mysqldump';
			}
			
			$command .= " --version";
			
			// Redirect STDERR to STDOUT.
			$command .= '  2>&1';
			
			/********** End preparing command **********/
			
			// Loop through all possible directories to run command through.
			foreach( $this->_mysql_directories as $mysql_directory ) { // Try each possible directory. mysql directory included trailing slash.
				
				// Run command.
				pb_backupbuddy::status( 'details', 'mysqldump test running next.' );
				list( $exec_output, $exec_exit_code ) = $this->_commandbuddy->execute( $mysql_directory . $command );
				
				if ( stristr( implode( ' ', $exec_output ), 'Ver' ) !== false ) { // String Ver appeared in response (expected response to mysqldump --version
					pb_backupbuddy::status( 'details', 'mysqldump test: Command appears to be accessible and returns expected response.' );
					$this->_mysql_directory = $mysql_directory; // Set to use this directory for the real dump.
					return array( 'commandline', 'php' ); // mysqldump best, php next.
				}
			}
			
			
		} else { // No exec() so must fall back to PHP method only.
			pb_backupbuddy::status( 'details', 'mysqldump test: exec() unavailable so skipping command line mysqldump check.' );
			pb_backupbuddy::status( 'message', 'mysqldump test: Falling back to database compatibility mode (PHPdump emulation). This is slower.' );
			return array( 'php' );
		}
		
		return array( 'php' );
		
	} // End available_dump_method().
	
	
	
	/*	_calculate_mysql_dir()
	 *	
	 *	Tries to determine the path to where mysql is installed.  Needed for running by command line.  Prepends found location to list of possible default mysql directories.
	 *	
	 *	@return		array			Array of directories in preferred order.
	 */
	private function _calculate_mysql_dir() {
		
		pb_backupbuddy::status( 'details', 'mysqlbuddy: Attempting to calculate exact mysql directory.' );
		$failed = true;
		$mysql_directories = $this->_default_mysql_directories;
		
		global $wpdb;
		$basedir = $wpdb->get_results( "SHOW VARIABLES LIKE 'basedir'", ARRAY_N );
		if ( $basedir !== false ) {
			$basedirtrim = rtrim( $basedir[0][1], '/\\' ); // Trim trailing slashes.
			$mysqldir = $basedirtrim . '/bin/';
			array_unshift( $mysql_directories, $mysqldir ); // Prepends the just found directory to the beginning of the list.
			pb_backupbuddy::status( 'details', 'mysqlbuddy: Mysql reported its directory. Reported: `' . $basedir[0][1] . '`; Adding binary location to beginning of mysql directory list: `' . $mysqldir . '`' );
			$failed = false;
		}
		
		if ( $failed === true ) {
			pb_backupbuddy::status( 'details', 'mysqlbuddy: Mysql would not report its directory.' );
		}
		
		return $mysql_directories;
		
	} // End _calculate_mysql_dir().
	
	
	
	/*	dump()
	 *	
	 *	function description
	 *
	 *	@see _mysqldump().
	 *	@see _phpdump().
	 *	
	 *	@param		string		$output_directory		Directory to output to. May also be used as a temporary file location. Trailing slash auto-added if missing.
	 *	@param		array		$tables					Array of tables to dump.
	 *	REMOVED: @param		string		$base_dump_mode			Determines which database tables to dump by default. $additional_[includes/excludes] modified. Modes: all, none, or prefix.
	 *	REMOVED: @param		array		$additional_includes	Array of additional table(s) to INCLUDE in dump. Added in addition to those found by the $base_dump_mode
	 *	REMOVED: @param		array		$additional_excludes	Array of additional table(s) to EXCLUDE from dump. Removed from those found by the $base_dump_mode + $additional_includes.
	 *	@return
	 */
	public function dump( $output_directory, $tables = array() ) { //, $base_dump_mode, $additional_includes = array(), $additional_excludes = array() ) {
		
		$output_directory = rtrim( $output_directory, '/' ) . '/'; // Make sure we have trailing slash.
		pb_backupbuddy::status( 'action', 'start_database' );
		pb_backupbuddy::status( 'message', 'Starting database dump procedure.' );
		pb_backupbuddy::status( 'details', "mysqlbuddy: Output directory: `{$output_directory}`." );
		if ( count( $tables ) == 1 ) {
			pb_backupbuddy::status( 'details', 'Dumping single table `' . $tables[0] . '`.' );
		} else {
			pb_backupbuddy::status( 'details', 'Table dump count: `' . count( $tables ) . '`.' );
		}
		if ( file_exists( $output_directory . 'db_1.sql' ) ) {
			pb_backupbuddy::status( 'details', 'Database SQL file exists. It will be appended to.' );
		}
		
		// Attempt each method in order.
		pb_backupbuddy::status( 'details', 'Preparing to dump using available method(s) by priority. Methods: `' . implode( ',', $this->_methods ) . '`' );
		foreach( $this->_methods as $method ) {
			if ( method_exists( $this, "_dump_{$method}" ) ) {
				pb_backupbuddy::status( 'details', 'mysqlbuddy: Attempting dump method `' . $method . '`.' );
				$result = call_user_func( array( $this, "_dump_{$method}" ), $output_directory, $tables );
				
				if ( $result === true ) { // Dump completed succesfully with this method.
					pb_backupbuddy::status( 'details', 'mysqlbuddy: Dump method `' . $method . '` completed successfully.' );
					$return = true;
					break;
				} elseif ( $result === false ) { // Dump failed this method. Will try compatibility fallback to next mode if able.
					// Do nothing. Will try next mode next loop.
					pb_backupbuddy::status( 'details', 'mysqlbuddy: Dump method `' . $method . '` failed. Trying another compatibility mode next if able.' );
				} else { // Something else returned; need to resume? TODO: this is for future use for resuming dump.
					pb_backupbuddy::status( 'details', 'mysqlbuddy: Unexepected response: `' . implode( ',', $result ) . '`' );
				}
			}
		}
		
		//pb_backupbuddy::status( 'status', 'database_end' );
		pb_backupbuddy::status( 'action', 'finish_database' );
		
		if ( $return === true ) { // Success.
			pb_backupbuddy::status( 'message', 'Database dump procedure succeeded.' );
			return true;
		} else { // Overall failure.
			pb_backupbuddy::status( 'error', 'Database dump procedure failed.' );
			return false;
		}
		
	} // End dump().
	
	
	
	/*	_dump_commandline()
	 *	
	 *	function description
	 *	
	 *	@param		string		$output_directory		Directory to output to. May also be used as a temporary file location. Trailing slash required. dump() auto-adds trailing slash before passing.
	 *	@param		array		$tables					Array of tables to dump.
	 *	REMOVED: @param		string		$base_dump_mode			Base dump mode. Used to tell whether or not to dump entire database or piecemeal tables. Try to keep command line short.
	 *	REMOVED: @param		array		$additional_excludes	Additional excludes. Used to tell whether or not to dump entire database or piecemeal tables. Try to keep command line short.
	 *	@return		
	 */
	private function _dump_commandline( $output_directory, $tables ) { //, $base_dump_mode, $additional_excludes ) {
		
		$output_file = $output_directory . 'db_1.sql';
		pb_backupbuddy::status( 'details', 'mysqlbuddy: Preparing to run command line mysqldump via exec().' );
		$exclude_command = '';
		
		/*
		if ( ( $base_dump_mode == 'all' ) && ( count( $additional_excludes ) == 0 ) ) { // Dumping ALL tables in the database so do not specify tables in command line.
			// Do nothing. Just dump full database.
			pb_backupbuddy::status( 'details', 'mysqlbuddy: Dumping entire database with no exclusions.' );
		} elseif ( ( $base_dump_mode == 'all' ) && ( count( $additional_excludes ) > 0 ) ) { // Dumping all tables by default BUT also excluding certain ones.
			pb_backupbuddy::status( 'details', 'mysqlbuddy: Dumping entire database with additional exclusions.' );
			// Handle additional exclusions.
			$additional_excludes = array_filter( $additional_excludes ); // ignore any phantom excludes.
			foreach( $additional_excludes as $additional_exclude ) {
				$exclude_command .= " --ignore-table={$this->_database_name}.{$additional_exclude}";
			}
		} else { // Only dumping certain 
			pb_backupbuddy::status( 'details', 'mysqlbuddy: Dumping specific tables.' );
			$tables_string = implode( ' ', $tables ); // Specific tables listed to dump.
		}
		*/
		
		$tables_string = implode( ' ', $tables ); // Specific tables listed to dump.
		
		
		/********** Begin preparing command **********/
		// Handle Windows wanting .exe.
		if ( stristr( PHP_OS, 'WIN' ) && !stristr( PHP_OS, 'DARWIN' ) ) { // Running Windows. (not darwin)
			$command = $this->_mysql_directory . 'msqldump.exe';
		} else {
			$command = $this->_mysql_directory . 'mysqldump';
		}
		
		// Handle possible sockets.
		if ( $this->_database_socket != '' ) {
			$command .= " --socket={$this->_database_socket}";
			pb_backupbuddy::status( 'details', 'mysqlbuddy: Using sockets in command.' );
		}
		
		// TODO WINDOWS NOTE: information in the MySQL documentation about mysqldump needing to use --result-file= on Windows to avoid some issue with line endings.
		/*
		Notes:
			--skip-opt				Skips some default options. MUST add back in create-options else autoincrement will be lost! See http://dev.mysql.com/doc/refman/5.5/en/mysqldump.html#option_mysqldump_opt
			--quick
			--skip-comments			Dont bother with extra comments.
			--create-options		Required to add in auto increment option.
			--disable-keys			Prevent re-indexing the database after each and every insert until the entire table is inserted. Prevents timeouts when a db table has fulltext keys especially.
		*/
		$command .= " --host=" . escapeshellarg( $this->_database_host ) . " --user=" . escapeshellarg( $this->_database_user ) . " --password=" . escapeshellarg( $this->_database_pass ) . " --skip-opt --quick --disable-keys --skip-comments --create-options {$exclude_command} " . escapeshellarg( $this->_database_name ) . " {$tables_string} 2>&1 >> {$output_file}"; // 2>&1 redirect STDERR to STDOUT.
		/********** End preparing command **********/
		
		
		/********** Begin command line length check **********/
		// Simple check command line length. If it appears long then do advanced check via command line to see what actual limit is. Falls back if too long to execute our process in one go.
		// TODO: In the future handle fallback better by possibly breaking the command up if possible rather than strict fallback to PHP dumping.
		$command_length = strlen( $command );
		pb_backupbuddy::status( 'details', 'mysqlbuddy: Command length: `' . $command_length . '`.' );
		if ( '1' == pb_backupbuddy::$options['ignore_command_length_check'] ) {
			pb_backupbuddy::status( 'details', 'mysqlbuddy: Advanced option to ignore command line length check results in enabled. Any negative results will be ignored (if applicable).' );
		}
		if ( $command_length > self::COMMAND_LINE_LENGTH_CHECK_THRESHOLD ) { // Arbitrary length. Seems standard max lengths are > 200000 on Linux. ~8600 on Windows?
			pb_backupbuddy::status( 'details', 'mysqlbuddy: Command line length of `' . $command_length . '` (bytes) is large enough ( >' . self::COMMAND_LINE_LENGTH_CHECK_THRESHOLD . ' ) to verify compatibility. Checking maximum allowed command line length for this sytem.' );
			list( $exec_output, $exec_exit_code ) = $this->_commandbuddy->execute( 'echo $(( $(getconf ARG_MAX) - $(env | wc -c) ))' ); // Value will be a number. This is the maximum byte size of the command line.
			pb_backupbuddy::status( 'details', 'mysqlbuddy: Command output: `' . implode( ',', $exec_output ) . '`; Exit code: `' . $exec_exit_code . '`; Exit code description: `' . pb_backupbuddy::$filesystem->exit_code_lookup( $exec_exit_code ) . '`' );
			if ( is_array( $exec_output ) && is_numeric( $exec_output[0] ) ) {
				pb_backupbuddy::status( 'details', 'mysqlbuddy: Detected maximum command line length for this system: `' . $exec_output[0] . '`.' );
				if ( $command_length > ( $exec_output[0] - 100 ) ) { // Check if we exceed maximum length. Subtract 100 to make room for path definition.
					if ( '1' == pb_backupbuddy::$options['ignore_command_length_check'] ) {
						pb_backupbuddy::status( 'details', 'mysqlbuddy: This system\'s maximum command line length of `' . $exec_output[0] . '` is shorter than command length of `' . $command_length . '`. However, the option to ignore command line length check is enabled based on settings so continuing anyways.' );
					} else {
						if ( $exec_output[0] < 0 ) {
							pb_backupbuddy::status( 'warning', 'mysqlbuddy: This system reported a negative number for the maximum command line length. This means that BackupBuddy cannot safely detect this system\'s limit.  This check can be overriden by enabling the "Skip command line length check" advanced option on the Settings page in BackupBuddy as a workaround. get_conf ARG_MAX likely failed.' );
						}
						pb_backupbuddy::status( 'details', 'mysqlbuddy: This system\'s maximum command line length of `' . $exec_output[0] . '` is shorter than command length of `' . $command_length . '`. Falling back into compatibility mode to insure database dump integrity.' );
						return false;
					}
				} else {
					pb_backupbuddy::status( 'details', 'mysqlbuddy: This system\'s maximum command line length of `' . $exec_output[0] . '` is longer than command length of `' . $command_length . '`. Continuing.' );
				}
			} else {
				pb_backupbuddy::status( 'details', 'mysqlbuddy: Unable to determine maximum command line length. Falling back into compatibility mode to insure database dump integrity.' );
				return false; // Fall back to compatibilty mode just in case.
			}
		} else {
			pb_backupbuddy::status( 'details', 'mysqlbuddy: Command line length length check skipped since it is less than check threshold `' . self::COMMAND_LINE_LENGTH_CHECK_THRESHOLD . '`.' );
		}
		/********** End command line length check **********/
		
		
		// Run command.
		pb_backupbuddy::status( 'details', 'mysqlbuddy: Running dump via commandline next.' );
		list( $exec_output, $exec_exit_code ) = $this->_commandbuddy->execute( $command );
		
		// Check for unexpected mysql command line response text...
		if ( is_array( $exec_output ) ) {
			foreach( $exec_output as $exec_output_line ) {
				if ( false !== stristr( $exec_output_line, 'unrecognized option' ) ) {
					pb_backupbuddy::status( 'error', 'mysqldump did not recognize one or more options. Verify mysqldump version is up to date. Falling back to compatibility mode. Error: `' . $exec_output_line . '`.' );
					return false;
				}
			}
		}
		
		// If mysql went away while we were busy with command line try to re-establish.
		global $wpdb;
		if ( @mysql_ping( $wpdb->dbh ) !== true ) { // No longer connected to database.
			pb_backupbuddy::status( 'error', 'mysqlbuddy: Error #43849374. Database went away from PHP while running from command line. The PHP <--> mysql connection likely timed out while we were doing other things.' );
			
			// Clean up connection.
			@mysql_close( $wpdb->dbh );
			$wpdb->ready = false;
			
			// Attempt to reconnect.
			$wpdb->db_connect();
			
			// Check if reconnect worked.
			if ( ( NULL == $wpdb->dbh ) || ( !mysql_ping( $wpdb->dbh ) ) ) { // Reconnect failed if we have a null resource or ping fails
				pb_backupbuddy::status( 'error', __('Database Server reconnection failed.', 'it-l10n-backupbuddy' ) );
			} else {
				pb_backupbuddy::status( 'details', __( 'Database Server reconnection successful.', 'it-l10n-backupbuddy' ) );
				$result = true;
			}
		}
		
		
		// Check the result of the 
		if ( $exec_exit_code == '0' ) {
			pb_backupbuddy::status( 'details', 'mysqlbuddy: Command appears to succeeded and returned proper response.' );
			if ( file_exists( $output_file ) ) { // SQL file found. SUCCESS!
				pb_backupbuddy::status( 'message', 'mysqlbuddy: Database dump SQL file exists.' );
				
				// Display final SQL file size.
				$sql_filesize = pb_backupbuddy::$format->file_size( filesize( $output_file ) );
				pb_backupbuddy::status( 'details', 'Final SQL database dump file size of `' . basename( $output_file ) . '`: ' . $sql_filesize . '.' );
				
				return true;
			} else { // SQL file MISSING. FAILURE!
				pb_backupbuddy::status( 'error', 'mysqlbuddy: Though command reported success database dump SQL file is missing: `' . $output_file . '`.' );
				return false;
			}
		} else {
			pb_backupbuddy::status( 'details', 'mysqlbuddy: Warning #9030. Command did not exit normally. This is normal is your server does not support command line mysql functionality. Falling back to database dump compatibility modes.' );
			return false;
		}
		
		
		// Should never get to here.
		pb_backupbuddy::status( 'error', 'mysqlbuddy: Uncaught exception #453890.' );
		return false;
		
	} // End _dump_commandline().
	
	
	
	/*	_phpdump()
	 *	
	 *	PHP-based dumping of SQL data. Compatibility mode. Was only mode pre-3.0.
	 *	
	 *	@param		string		$output_directory		Directory to output to. May also be used as a temporary file location. Trailing slash required. dump() auto-adds trailing slash before passing.
	 *	@param		array		$tables					Array of tables to dump.
	 *	REMOVED: @param		string		$base_dump_mode			Base dump mode. NOT USED. Consistent with other dump mode.
	 *	REMOVED: @param		array		$additional_excludes	Additional excludes. NOT USED. Consistent with other dump mode.
	 *	@return		
	 */
	private function _dump_php( $output_directory, $tables ) { //, $base_dump_mode, $additional_excludes ) {
		
		$max_rows_per_select = 3000;
		
		$output_file = $output_directory . 'db_1.sql';
		pb_backupbuddy::status( 'details', 'mysqlbuddy: Preparing to run PHP mysqldump compatibility mode.' );
		
		if ( false === ( $file_handle = fopen( $output_file, 'a' ) ) ) {
			pb_backupbuddy::status( 'error', 'Error #9018: Database file is not creatable/writable. Check your permissions for file `' . $output_file . '` in directory `' . $output_directory . '`.' );
			return false;
		}
		
		
		global $wpdb;
		if ( !is_object( $wpdb ) ) {
			pb_backupbuddy::status( 'error', 'WordPress database object $wpdb did not exist. This should not happen.' );
			error_log( 'WordPress database object $wpdb did not exist. This should not happen. BackupBuddy Error #8945587973.' );
			return false;
		}
		
		// Connect if not connected for importbuddy.
		if ( !mysql_ping( $wpdb->dbh ) ) {
			$wpdb->dbh = mysql_connect( $this->_database_host, $this->_database_user, $this->_database_pass );
			mysql_select_db( $this->_database_name, $wpdb->dbh );
		}
		
		$_count = 0;
		$insert_sql = '';
		
		global $wpdb; // Used later for checking that we are still connected to DB.
		
		// Iterate through all the tables to backup.
		// TODO: Future ability to break up DB exporting to multiple page loads if needed. Really still need this now that we have command line dump?
		foreach( $tables as $table_key => $table ) {
			pb_backupbuddy::status( 'details', 'Starting to dump database table `' . $table . '`. Max rows per select: ' . $max_rows_per_select . '.' );
			$create_table = $wpdb->get_results( "SHOW CREATE TABLE `{$table}`", ARRAY_N );
			
			if ( $create_table === false ) {
				pb_backupbuddy::status( 'error', 'Unable to access and dump database table `' . $table . '`. Table may not exist. Skipping backup of this table.' );
				//backupbuddy_core::mail_error( 'Error #4537384: Unable to access and dump database table `' . $table . '`. Table may not exist. Skipping backup of this table.' );
				continue; // Skip this iteration as accessing this table failed.
			}
			
			// Table creation text.
			$create_table_array = $create_table[0];
			unset( $create_table );
			$insert_sql .= str_replace( "\n", '', $create_table_array[1] ) . ";\n"; // Remove internal linebreaks; only put one at end.
			unset( $create_table_array );
			
			// Disable keys for this table.
			$insert_sql .= "/*!40000 ALTER TABLE `{$table}` DISABLE KEYS */;\n";
			
			$rows_remain = true;
			$rows_start = 0;
			while ( true === $rows_remain ) {
				// Row creation text for all rows within this table.
				$query = "SELECT * FROM `$table` LIMIT " . $rows_start . ',' . $max_rows_per_select;
				//pb_backupbuddy::status( 'details', 'Query: `' . $query . '`' );
				$table_query = $wpdb->get_results( $query, ARRAY_N );
				$rows_start += $max_rows_per_select; // Next loop we will begin at this offset.
				if ( $table_query === false ) {
					pb_backupbuddy::status( 'error', 'ERROR #85449745. Unable to retrieve data from table `' . $table . '`. This table may be corrupt (try repairing the database) or too large to hold in memory (increase mysql and/or PHP memory). Check your PHP error log for further errors which may provide further information. Not continuing database dump to insure backup integrity.' );
					return false;
				}
				if ( 0 == count( $table_query ) ) {
					$rows_remain = false;
					break;
				}
				
				$columns = $wpdb->get_col_info();
				$num_fields = count( $columns );
				foreach( $table_query as $fetch_row ) {
					$insert_sql .= "INSERT INTO `$table` VALUES(";
					for ( $n=1; $n<=$num_fields; $n++ ) {
						$m = $n - 1;
										
						if ( $fetch_row[$m] === NULL ) {
							$insert_sql .= "NULL, ";
						} else {
							$insert_sql .= "'" . mysql_real_escape_string( $fetch_row[$m] ) . "', ";
						}
					}
					$insert_sql = substr( $insert_sql, 0, -2 );
					$insert_sql .= ");\n";
					
					fwrite( $file_handle, $insert_sql );
					$insert_sql = '';
					
					// Help keep HTTP alive.
					$_count++;
					if ($_count >= 800) {
						echo ' ';
						pb_backupbuddy::status( 'details', 'About to flush...' );
						pb_backupbuddy::flush();
						$_count = 0;
					}
				} // end while $rows_remain is true.
				
				// testing: mysql_close( $wpdb->dbh );
				// Verify database is still connected and working properly. Sometimes mysql runs out of memory and dies in the above foreach.
				// No point in reconnecting as we can NOT trust that our dump was succesful anymore (it most likely was not).
				if ( @mysql_ping( $wpdb->dbh ) ) { // Still connected to database.
					
				} else { // Database not connected.
					pb_backupbuddy::status( 'error', __( 'ERROR #9026: The mySQL server went away unexpectedly during database dump of table `' . $table . '`. This is almost always caused by mySQL running out of memory. The backup integrity can no longer be guaranteed so the backup has been halted.' ) . ' ' . __( 'Last table dumped before database server went away: ' ) . '`' . $table . '`.' );
					return false;
				}
				
			} // End foreach $tables.
			
			// Re-enable keys for this table.
			$insert_sql .= "/*!40000 ALTER TABLE `{$table}` ENABLE KEYS */;\n";
			
			// Help keep HTTP alive.
			echo ' ';
			pb_backupbuddy::status( 'details', 'Finished dumping database table `' . $table . '`.' );
			pb_backupbuddy::status( 'details', 'About to flush...' );
			pb_backupbuddy::flush();
			
			//unset( $tables[$table_key] );
		} // end foreach table.
		
		@fclose( $file_handle );
		unset( $file_handle );
		
		
		pb_backupbuddy::status( 'details', __('Finished PHP based SQL dump method.', 'it-l10n-backupbuddy' ) );
		
		return true;
		
	} // End _phpdump().
	
	
	
	/*	get_methods()
	 *	
	 *	Get an array of methods. Note: If force overriding methods then detected methods will not be able to display.
	 *	
	 *	@return		array				Array of methods.
	 */
	public function get_methods() {
		return $this->_methods;
	}
	
	
	
	/*	import()
	 *	
	 *	Import SQL contents of a .sql file into the database. Only modification is to table prefix if needed. Prefixes (new and old) provided in constructor.
	 *	Automatically handles fallback based on best available methods.
	 *	
	 *	@param		string		$sql_file				Full absolute path to .sql file to import from.
 	 *	@param		string		$old_prefix				Old database prefix. New prefix provided in constructor.
 	 *	@param		int			$query_start			Query number (aka line number) to resume import at.
 	 *	@param		boolean		$ignore_existing		Whether or not to allow import if tables exist already. Default: false.
	 *	@return		mixed								true on success (boolean)
	 *													false on failure (boolean)
	 *													integer (int) on needing a resumse (integer is resume number for next page loads $query_start)
	 */
	public function import( $sql_file, $old_prefix, $query_start = 0, $ignore_existing = false ) {
		$return = false;
		
		// Require a new table prefix.
		if ( $this->_database_prefix == '' ) {
			pb_backupbuddy::status( 'error', 'ERROR 9008: A database prefix is required for importing.' );
		}
		
		if ( $query_start > 0 ) {
			pb_backupbuddy::status( 'message', 'Continuing to restore database dump starting at query ' . $query_start . '.' );
		} else {
			pb_backupbuddy::status( 'message', 'Restoring database dump. This may take a moment...' );
		}
		
		global $wpdb;
		
		// Check whether or not tables already exist that might collide.
		if ( $ignore_existing === false ) {
			if ( $query_start == 0 ) { // Check number of tables already existing with this prefix. Skips this check on substeps of DB import.
				$rows = $wpdb->get_results( "SELECT table_name FROM information_schema.tables WHERE table_name LIKE '" . mysql_real_escape_string( str_replace( '_', '\_', $this->_database_prefix ) ) . "%' AND table_schema = DATABASE()", ARRAY_A );
				if ( count( $rows ) > 0 ) {
					pb_backupbuddy::status( 'error', 'Error #9014: Database import halted to prevent overwriting existing WordPress data.', 'The database already contains a WordPress installation with this prefix (' . count( $rows ) . ' tables). Restore has been stopped to prevent overwriting existing data. *** Please go back and enter a new database name and/or prefix OR select the option to wipe the database prior to import from the advanced settings on the first import step. ***' );
					return false;
				}
				unset( $rows );
			}
		}
		
		
		pb_backupbuddy::status( 'message', 'Starting database import procedure.' );
		pb_backupbuddy::status( 'details', 'mysqlbuddy: Maximum execution time for this run: ' . pb_backupbuddy::$options['max_execution_time'] . ' seconds.' );
		pb_backupbuddy::status( 'details', 'mysqlbuddy: Old prefix: `' . $old_prefix . '`; New prefix: `' . $this->_database_prefix . '`.' );
		pb_backupbuddy::status( 'details', "mysqlbuddy: Importing SQL file: `{$sql_file}`. Old prefix: `{$old_prefix}`. Query start: `{$query_start}`." );
		pb_backupbuddy::status( 'details', 'About to flush...' );
		pb_backupbuddy::flush();
		
		// Attempt each method in order.
		pb_backupbuddy::status( 'details', 'Preparing to import using available method(s) by priority. Basing import methods off dump methods: `' . implode( ',', $this->_methods ) . '`' );
		foreach( $this->_methods as $method ) {
			if ( method_exists( $this, "_import_{$method}" ) ) {
				pb_backupbuddy::status( 'details', 'mysqlbuddy: Attempting import method `' . $method . '`.' );
				$result = call_user_func( array( $this, "_import_{$method}" ), $sql_file, $old_prefix, $query_start, $ignore_existing );
				
				if ( $result === true ) { // Dump completed succesfully with this method.
					pb_backupbuddy::status( 'details', 'mysqlbuddy: Import method `' . $method . '` completed successfully.' );
					$return = true;
					break;
				} elseif ( $result === false ) { // Dump failed this method. Will try compatibility fallback to next mode if able.
					// Do nothing. Will try next mode next loop.
					pb_backupbuddy::status( 'details', 'mysqlbuddy: Import method `' . $method . '` failed. Trying another compatibility mode next if able.' );
				} else { // Something else returned; used for resuming (integer) or a message (string).
					pb_backupbuddy::status( 'details', 'mysqlbuddy: Non-boolean response (usually means resume is needed): `' . $result . '`' );
					return $result; // Dont fallback if this happens. Usually means resume is needed to finish.
				}
			}
		}
				
		if ( $return === true ) { // Success.
			pb_backupbuddy::status( 'message', 'Database import procedure succeeded.' );
			return true;
		} else { // Overall failure.
			pb_backupbuddy::status( 'error', 'Database import procedure did not complete or failed.' );
			return false;
		}
		
	} // End import().
	
	
	
	public function _import_commandline( $sql_file, $old_prefix, $query_start = 0, $ignore_existing = false ) {
		pb_backupbuddy::status( 'details', 'mysqlbuddy: Preparing to run command line mysql import via exec().' );
		
		
		// If prefix has changed then need to update the file.
		if ( $old_prefix != $this->_database_prefix ) {
			if ( !isset( pb_backupbuddy::$classes['textreplacebuddy'] ) ) {
				require_once( pb_backupbuddy::plugin_path() . '/lib/textreplacebuddy/textreplacebuddy.php' );
				pb_backupbuddy::$classes['textreplacebuddy'] = new pb_backupbuddy_textreplacebuddy();
			};
			pb_backupbuddy::$classes['textreplacebuddy']->set_methods( array( 'commandline' ) ); // dont fallback into text version here.
			
			$regex_condition = "(INSERT INTO|CREATE TABLE|REFERENCES|CONSTRAINT|ALTER TABLE) (`?){$old_prefix}(`?)";
			pb_backupbuddy::$classes['textreplacebuddy']->string_replace( $sql_file, $old_prefix, $this->_database_prefix, $regex_condition );
			
			$sql_file = $sql_file . '.tmp'; // New SQL file created by textreplacebuddy.
		}
		
		
		/********** Begin preparing command **********/
		// Handle Windows wanting .exe. Note: executable directory path is prepended on exec() line of code.
		if ( stristr( PHP_OS, 'WIN' ) && !stristr( PHP_OS, 'DARWIN' ) ) { // Running Windows. (not darwin)
			$command = 'msql.exe';
		} else {
			$command = 'mysql';
		}
		
		// Handle possible sockets.
		if ( $this->_database_socket != '' ) {
			$command .= " --socket={$this->_database_socket}";
			pb_backupbuddy::status( 'details', 'mysqlbuddy: Using sockets in command.' );
		}
		
		//$command .= " --host={$this->_database_host} --user={$this->_database_user} --password={$this->_database_pass} --default_character_set utf8 {$this->_database_name} < {$sql_file}";
		$command .= " --host=" . escapeshellarg($this->_database_host) . " --user=" . escapeshellarg($this->_database_user) . " --password=" . escapeshellarg($this->_database_pass) . " --default_character_set utf8 " . escapeshellarg($this->_database_name) . " 2>&1 < {$sql_file}"; // 2>&1 redirect STDERR to STDOUT.
		/********** End preparing command **********/
		
		// Run command.
		pb_backupbuddy::status( 'details', 'mysqlbuddy: Running import via command line next.' );
		list( $exec_output, $exec_exit_code ) = $this->_commandbuddy->execute( $this->_mysql_directory . $command );
		
		
		// TODO: Removed mysql pinging here. Do we need (or even want) that here?
		
		
		// Check the result of the execution.
		if ( $exec_exit_code == '0' ) {
			pb_backupbuddy::status( 'details', 'mysqlbuddy: Command appears to succeeded and returned proper response.' );
			return true;
		} else {
			pb_backupbuddy::status( 'error', 'mysqlbuddy: Command did not exit normally. Falling back to database dump compatibility modes.' );
			return false;
		}
		
		
		// Should never get to here.
		pb_backupbuddy::status( 'error', 'mysqlbuddy: Uncaught exception #433053890.' );
		return false;
	} // End _import_commandline().
	
	
	
	function string_begins_with( $string, $search ) {
		return ( strncmp( $string, $search, strlen($search) ) == 0 );
	}
	
	
	
	/*	_import_php()
	 *	
	 *	Import from .SQL file into database via PHP by reading in file line by line.
	 *	Using codebase from BackupBuddy / importbuddy 2.x.
	 *	@see import().
	 *	@since 2.x.
	 *	
	 *	@param		SEE import() PARAMETERS!!
	 *	@return		mixed			True on success (and completion), integer on incomplete (resume needed via $query_start), false on failure.
	 */
	public function _import_php( $sql_file, $old_prefix, $query_start = 0, $ignore_existing = false ) {
		
		$this->time_start = time();
		
		pb_backupbuddy::status( 'message', 'Starting import of SQL data... This may take a moment...' );
		pb_backupbuddy::status ('details', 'Loading SQL from file `' . $sql_file . '`.' );
		$file_stream = @fopen( $sql_file, 'r' );
		
		if ( false === $file_stream ) {
			pb_backupbuddy::status( 'error', 'Error #9009: Unable to find any database backup data in the selected backup or could not open file (possibly due to permissions). Tried opening file `' . $sql_file . '`. Error #9009.' );
			return false;
		}
		
		// Iterate through each full row action and import it one at a time.
			
		$query_count = 0;
		$file_data = '';
		/* $in_create_table_block = false; */
		
		while ( ! feof( $file_stream ) ) {
		
			while ( false === strpos( $file_data, ";\n" ) ) {
				$file_data .= fread( $file_stream, 4096 );
			}
			
			$queries = explode( ";\n", $file_data );
			
			if ( preg_match( "/;\n$/", $file_data ) ) {
				$file_data = '';
			} else {
				$file_data = array_pop( $queries );
			}
			
			// TODO: DEBUGGING:
			//pb_backupbuddy::$options['max_execution_time'] = 0.41;
			
			// Loops through each full query.
			foreach ( (array) $queries as $query ) {
				if ( $query_count < ( $query_start - 1 ) ) { // Handle skipping any queries up to the point we are at.
					$query_count++;
					continue; // Continue to next foreach iteration.
				} else {
					$query_count++;
				}
				
				$query = trim( $query );
				
				if ( empty( $query ) || ( $this->string_begins_with( $query, '/*!40103' ) ) ) { // If blank line or starts with /*!40103 (mysqldump file has this junk in it).
					continue;
				}
				
				/*
				if ( $in_create_table_block === true ) {
				} else { // Watch for beginning of CREATE TABLE block if not in one.
					// Handle broken up CREATE TABLE blocks caused by mysqldump.
					if ( $this->string_begins_with( $query, 'CREATE TABLE' ) ) {
						$in_create_table_block = true;
					}
				}
				*/
				
				$result = $this->_import_sql_dump_line( $query, $old_prefix, $ignore_existing );
				
				if ( false === $result ) { // Skipped query
					continue;
				}
				
				if ( 0 === ( $query_count % 2000 ) ) { // Display Working every 1500 queries imported.
					pb_backupbuddy::status( 'message', 'Working... Imported ' . $query_count . ' queries so far.' );
				}
				/*
				if ( 0 === ( $query_count % 6000 ) ) {
					echo "<br>\n";
				}
				*/
				
				// If we are within 1 second of reaching maximum PHP runtime then stop here so that it can be picked up in another PHP process...
				if ( ( ( microtime( true ) - $this->time_start ) + 1 ) >= pb_backupbuddy::$options['max_execution_time'] ) {
					// TODO: Debugging:
					//if ( ( ( microtime( true ) - $this->time_start ) ) >= pb_backupbuddy::$options['max_execution_time'] ) {
					pb_backupbuddy::status( 'message', 'Exhausted available PHP time to import for this page load. Last query: ' . $query_count . '.' );
					
					fclose( $file_stream );
					
					return ( $query_count + 1 );
					//break 2;
				} // End if.
				
			} // End foreach().
			
		} // End while().
		
		fclose( $file_stream );
		
		pb_backupbuddy::status( 'message', 'Import of SQL data in compatibility mode (PHP) complete.' );			
		pb_backupbuddy::status( 'message', 'Took ' . round( microtime( true ) - $this->time_start, 3 ) . ' seconds on ' . $query_count . ' queries. ' );
		
		return true;
		
	} // End _import_php().
	
	
	
	/**
	 *	_import_sql_dump_line()
	 *
	 *	Imports a line/query into the database.
	 *	Handles using the specified table prefix.
	 *	@since 2.x.
	 *
	 *	@param		string		$query			Query string to run for importing.
	 *	@param		string		$old_prefix		Old prefix. (new prefix was passed in constructor).
	 *	@return		boolean						True=success, False=failed.
	 *
	 */
	function _import_sql_dump_line( $query, $old_prefix, $ignore_existing ) {
		$new_prefix = $this->_database_prefix;
		
		$query_operators = 'INSERT INTO|CREATE TABLE|REFERENCES|CONSTRAINT|ALTER TABLE|\/\*!\d+\s+ALTER TABLE';
		
		// Replace database prefix in query.
		if ( $old_prefix !== $new_prefix ) {
			$query = preg_replace( "/^($query_operators)(\s+`?)$old_prefix/i", "\${1}\${2}$new_prefix", $query ); // 4-29-11
		}
		
		// Output which table we are able to create to help get an idea where we are in the import.
		if ( 1 == preg_match( "/CREATE TABLE `?(\w+)`?/i", $query, $matches ) ) {
			pb_backupbuddy::status( 'details', 'Creating table `' . $matches[1] . '`.' );
		}
		
		global $wpdb;
		
		// Run the query
		$results = $wpdb->query( $query );
		
		if ( false === $results ) {
			if ( $ignore_existing !== true ) {
				$mysql_error = mysql_error( $wpdb->dbh );
				pb_backupbuddy::status( 'error', 'Error #9010: Unable to import SQL query: ' . $mysql_error );
				if ( false !== stristr( $mysql_error, 'server has gone away' ) ) { // if string denotes that mysql server has gone away bail since it will likely just flood user with errors...
					pb_backupbuddy::status( 'error', 'Error #9010b: Halting backup process as mysql server has gone away and database data could not be imported. Typically the restore cannot continue so the process has been halted. This is usually caused by a problematic mysql server at your hosting provider, low mysql timeouts, etc. Contact your hosting company for support.' );
					pb_backupbuddy::status( 'details', 'Last query attempted: `' . $query . '`.' );
					die( 'Error #948343: FATAL ERROR - IMPORT HALTED' );
				}
			}
			return false;
		} else {
			return true;
		}
		
	} // End _import_sql_dump_line().
	
	
	
} // End pb_backupbuddy_mysqlbuddy class.
?>