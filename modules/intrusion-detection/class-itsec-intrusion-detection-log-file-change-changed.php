<?php
/**
 * Display Changed Files Details for Intrusion Detection Module
 *
 * @package    iThemes-Security
 * @subpackage Intrusion-Detection
 * @since      4.0
 */
if ( ! class_exists( 'ITSEC_Intrusion_Detection_Log_File_Change_Changed' ) ) {

	final class ITSEC_Intrusion_Detection_Log_File_Change_Changed extends ITSEC_WP_List_Table {

		function __construct() {

			parent::__construct(
				array(
					'singular' => 'itsec_file_change_log_changed_item',
					'plural'   => 'itsec_file_change_log_changed_items',
					'ajax'     => true
				)
			);

		}

		/**
		 * Create Table headers
		 *
		 * @param string $which top for above table, bottom for below
		 *
		 **/
		function extra_tablenav( $which ) {

			if ( $which == 'top' ) {
				echo '<h4>' . __( 'Files Changed', 'ithemes-security' ) . '</h4>';
			}

		}

		/**
		 * Define file column
		 *
		 * @param array $item array of row data
		 *
		 * @return string formatted output
		 *
		 **/
		function column_file( $item ) {

			return $item['file'];

		}

		/**
		 * Define modified column
		 *
		 * @param array $item array of row data
		 *
		 * @return string formatted output
		 *
		 **/
		function column_modified( $item ) {

			return date( 'Y-m-d, g:i A', $item['modified'] );

		}

		/**
		 * Define hash column
		 *
		 * @param array $item array of row data
		 *
		 * @return string formatted output
		 *
		 **/
		function column_hash( $item ) {

			return $item['hash'];

		}

		/**
		 * Define Columns
		 *
		 * @return array array of column titles
		 */
		public function get_columns() {

			return array(
				'file'     => __( 'File', 'ithemes-security' ),
				'modified' => __( 'Modified', 'ithemes-security' ),
				'hash'     => __( 'File Hash', 'ithemes-security' ),
			);

		}

		/**
		 * Prepare data for table
		 *
		 * @return void
		 */
		public function prepare_data_items( $data ) {

			$columns               = $this->get_columns();
			$hidden                = array();
			$sortable              = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );

			$items = $data['changed'];

			$table_data = array();

			$count = 0;

			//Loop through results and take data we need
			foreach ( $items as $item => $attr ) {

				$table_data[$count]['file']     = $item;
				$table_data[$count]['modified'] = sanitize_text_field( $attr['mod_date'] );
				$table_data[$count]['hash']     = sanitize_text_field( $attr['hash'] );

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
			$orderby = 'file';

			// If no order, default to desc
			$order = 'desc';

			// Determine sort order
			$result = strcmp( $a[$orderby], $b[$orderby] );

			// Send final sort direction to usort
			return ( $order === 'asc' ) ? $result : - $result;

		}

	}

}