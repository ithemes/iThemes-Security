<?php
/**
 * Log 404 errors for Intrusion Detection Module
 *
 * @package    iThemes-Security
 * @subpackage Intrusion-Detection
 * @since      4.0
 */
if ( ! class_exists( 'ITSEC_Intrusion_Detection_Log_Four_Oh_Four' ) ) {

	final class ITSEC_Intrusion_Detection_Log_Four_Oh_Four extends ITSEC_WP_List_Table {

		function __construct() {

			parent::__construct(
				array(
					'singular' => 'itsec_four_oh_four_log_item',
					'plural'   => 'itsec_four_oh_four_log_items',
					'ajax'     => true
				)
			);

		}

		/**
		 * Define first time column
		 *
		 * @param array $item array of row data
		 *
		 * @return string formatted output
		 *
		 **/
		function column_first_time( $item ) {

			return $item['first_time'];

		}

		/**
		 * Define time column
		 *
		 * @param array $item array of row data
		 *
		 * @return string formatted output
		 *
		 **/
		function column_last_time( $item ) {

			return $item['last_time'];

		}

		/**
		 * Define count column
		 *
		 * @param array $item array of row data
		 *
		 * @return string formatted output
		 *
		 **/
		function column_count( $item ) {

			return $item['count'];

		}

		/**
		 * Define uri column
		 *
		 * @param array $item array of row data
		 *
		 * @return string formatted output
		 *
		 **/
		function column_uri( $item ) {

			return '<a href="' . $_SERVER['REQUEST_URI'] . '&itsec_404_details_uri=' . urldecode( $item['uri'] ) . '">' . $item['uri'] . '</a>';

		}

		/**
		 * Define Columns
		 *
		 * @return array array of column titles
		 */
		public function get_columns() {

			return array(
				'uri'        => __( 'Location', 'ithemes-security' ),
				'count'      => __( 'Count', 'ithemes-security' ),
				'first_time' => __( 'First Recorded', 'ithemes-security' ),
				'last_time'  => __( 'Last Recorded', 'ithemes-security' ),
			);

		}

		/**
		 * Define Sortable Columns
		 *
		 * @return array of column titles that can be sorted
		 */
		public function get_sortable_columns() {

			$order = ( empty( $_GET['order'] ) ) ? false : true;

			$sortable_columns = array(
				'uri'        => array( 'uri', $order ),
				'count'      => array( 'count', $order ),
				'first_time' => array( 'first_time', $order ),
				'last_time'  => array( 'last_time', $order ),
			);

			return $sortable_columns;

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

			$items = $itsec_logger->get_events( 'four_oh_four' );

			$table_data = array();

			foreach ( $items as $item ) { //loop through and group 404s

				if ( isset( $table_data[$item['log_url']] ) ) {

					$table_data[$item['log_url']]['count']      = $table_data[$item['log_url']]['count'] + 1;
					$table_data[$item['log_url']]['last_time']  = strtotime( $table_data[$item['log_url']]['last_time'] ) > strtotime( $item['log_date'] ) ? $table_data[$item['log_url']]['last_time'] : sanitize_text_field( $item['log_date'] );
					$table_data[$item['log_url']]['first_time'] = strtotime( $table_data[$item['log_url']]['first_time'] ) < strtotime( $item['log_date'] ) ? $table_data[$item['log_url']]['first_time'] : sanitize_text_field( $item['log_date'] );
					$table_data[$item['log_url']]['uri']        = sanitize_text_field( $item['log_url'] );

				} else {

					$table_data[$item['log_url']]['count']      = 1;
					$table_data[$item['log_url']]['last_time']  = sanitize_text_field( $item['log_date'] );
					$table_data[$item['log_url']]['first_time'] = sanitize_text_field( $item['log_date'] );
					$table_data[$item['log_url']]['uri']        = sanitize_text_field( $item['log_url'] );

				}

			}

			usort( $table_data, array( $this, 'sortrows' ) );

			$per_page     = 20; //20 items per page
			$current_page = $this->get_pagenum();
			$total_items  = count( $table_data );

			$table_data = array_slice( $table_data, ( ( $current_page - 1 ) * $per_page ), $per_page );

			$this->items = $table_data;

			$this->set_pagination_args(
				array(
					'total_items' => $total_items,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total_items / $per_page )
				)
			);

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
			$orderby = ( ! empty( $_GET['orderby'] ) ) ? esc_attr( $_GET['orderby'] ) : 'last_time';

			// If no order, default to desc
			$order = ( ! empty( $_GET['order'] ) ) ? esc_attr( $_GET['order'] ) : 'desc';

			// Determine sort order
			$result = strcmp( $a[$orderby], $b[$orderby] );

			// Send final sort direction to usort
			return ( $order === 'asc' ) ? $result : - $result;

		}

	}

}