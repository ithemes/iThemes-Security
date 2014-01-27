<?php
/**
 * Display File Change Log for Intrusion Detection Module
 *
 * @package    iThemes-Security
 * @subpackage Intrusion-Detection
 * @since      4.0
 */
if ( ! class_exists( 'ITSEC_Intrusion_Detection_Log_File_Change' ) ) {

	final class ITSEC_Intrusion_Detection_Log_File_Change extends ITSEC_WP_List_Table {

		function __construct() {

			parent::__construct(
				array(
					'singular' => 'itsec_file_change_log_item',
					'plural'   => 'itsec_file_change_log_items',
					'ajax'     => true
				)
			);

		}

		/**
		 * Define time column
		 *
		 * @param array $item array of row data
		 *
		 * @return string formatted output
		 *
		 **/
		function column_time( $item ) {

			return $item['time'];

		}

		/**
		 * Define added column
		 *
		 * @param array $item array of row data
		 *
		 * @return string formatted output
		 *
		 **/
		function column_added( $item ) {

			return $item['added'];

		}

		/**
		 * Define removed column
		 *
		 * @param array $item array of row data
		 *
		 * @return string formatted output
		 *
		 **/
		function column_removed( $item ) {

			return $item['removed'];

		}

		/**
		 * Define changed column
		 *
		 * @param array $item array of row data
		 *
		 * @return string formatted output
		 *
		 **/
		function column_changed( $item ) {

			return $item['changed'];

		}

		/**
		 * Define memory used column
		 *
		 * @param array $item array of row data
		 *
		 * @return string formatted output
		 *
		 **/
		function column_memory( $item ) {

			return $item['memory'];

		}

		/**
		 * Define detail column
		 *
		 * @param array $item array of row data
		 *
		 * @return string formatted output
		 *
		 **/
		function column_detail( $item ) {

			return '<a href="' . $_SERVER['REQUEST_URI'] . '&itsec_file_change_details_id=' . urldecode( $item['detail'] ) . '">' . __( 'Details', 'ithemes-security' ) . '</a>';

		}

		/**
		 * Define Columns
		 *
		 * @return array array of column titles
		 */
		public function get_columns() {

			return array(
				'time'    => __( 'Check Time', 'ithemes-security' ),
				'added'   => __( 'Files Added', 'ithemes-security' ),
				'removed' => __( 'Files Deleted', 'ithemes-security' ),
				'changed' => __( 'Files Changed', 'ithemes-security' ),
				'memory'  => __( 'Memory Used', 'ithemes-security' ),
				'detail'  => __( 'Details', 'ithemes-security' ),
			);

		}

		/**
		 * Prepare data for table
		 *
		 * @return void
		 */
		public function prepare_items() {

			global $itsec_logger;

			$columns               = $this->get_columns();
			$hidden                = array();
			$sortable              = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );

			$items = $itsec_logger->get_events( 'file_change' );

			$table_data = array();

			$count = 0;

			//Loop through results and take data we need
			foreach ( $items as $item ) {

				$data = maybe_unserialize( $item['log_data'] );

				$table_data[$count]['time']    = $item['log_date'];
				$table_data[$count]['detail']  = $item['log_id'];
				$table_data[$count]['added']   = isset( $data['added'] ) ? sizeof( $data['added'] ) : 0;
				$table_data[$count]['removed'] = isset( $data['removed'] ) ? sizeof( $data['removed'] ) : 0;
				$table_data[$count]['changed'] = isset( $data['changed'] ) ? sizeof( $data['changed'] ) : 0;
				$table_data[$count]['memory']  = isset( $data['memory'] ) ? $data['memory'] : 0;

				$count ++;

			}

			usort( $table_data, array( $this, 'sortrows' ) );

			$this->items = $table_data;

		}

		/**
		 * Sorts rows by count in descending order
		 *
		 * @param array $a first array to compare
		 * @param array $b second array to compare
		 *
		 * @return int comparison result
		 */
		function sortrows( $a, $b ) {

			// If no sort, default to count
			$orderby = 'time';

			// If no order, default to desc
			$order = 'desc';

			// Determine sort order
			$result = strcmp( $a[$orderby], $b[$orderby] );

			// Send final sort direction to usort
			return ( $order === 'asc' ) ? $result : - $result;

		}

	}

}