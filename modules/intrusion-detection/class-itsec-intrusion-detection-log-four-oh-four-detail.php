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
		 * Define host column
		 *
		 * @param array $item array of row data
		 * @return string formatted output
		 *
		 **/
		function column_host( $item ) {

			$r = array();
			if (!is_array($item['host'])) {
				$item['host'] = array($item['host']);
			}
			foreach ($item['host'] as $host) {
				$r[] = '<a href="http://ip-adress.com/ip_tracer/' . filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) . '" target="_blank">' . filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) . '</a>';
			}
			$return = implode('<br />', $r);
			return $return;

		}

		/**
		 * Define referrer column
		 *
		 * @param array $item array of row data
		 *
		 * @return string formatted output
		 *
		 **/
		function column_referrer( $item ) {

			return $item['referrer'];

		}

		/**
		 * Define query column
		 *
		 * @param array $item array of row data
		 *
		 * @return string formatted output
		 *
		 **/
		function column_query( $item ) {

			return $item['query'];

		}

		/**
		 * Define Columns
		 *
		 * @return array array of column titles
		 */
		public function get_columns() {

			return array(
				'time'     => __( 'Time of Error', 'ithemes-security' ),
				'host'     => __( 'Host', 'ithemes-security' ),
				'referrer' => __( 'Referrer', 'ithemes-security' ),
				'query'    => __( 'GET Parameters', 'ithemes-security' ),
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
				'time'     => array( 'time', $order ),
				'host'     => array( 'host', $order ),
				'referrer' => array( 'referrer', $order ),
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

			$items = $itsec_logger->get_events( 'four_oh_four', array( 'log_url' => esc_sql( $_GET['itsec_404_details_uri'] ) ) );

			$table_data = array();

			$count = 0;

			foreach ( $items as $item ) { //loop through and group 404s

				$log_data = unserialize( $item['log_data'] );

				if ( isset( $log_data['query_string'] ) ) {
					$query =  $log_data['query_string'];
				} else {
					$query = '';
				}

				$table_data[$count]['time']     = sanitize_text_field( $item['log_date'] );
				$table_data[$count]['host']     = sanitize_text_field( $item['log_host'] );
				$table_data[$count]['referrer'] = isset( $item['log_referrer'] ) ? sanitize_text_field( $item['log_referrer'] ) : '';
				$table_data[$count]['query']    = sanitize_text_field( $query );

				$count ++;

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
			$orderby = ( ! empty( $_GET['orderby'] ) ) ? esc_attr( $_GET['orderby'] ) : 'time';

			// If no order, default to desc
			$order = ( ! empty( $_GET['order'] ) ) ? esc_attr( $_GET['order'] ) : 'desc';

			// Determine sort order
			$result = strcmp( $a[$orderby], $b[$orderby] );

			// Send final sort direction to usort
			return ( $order === 'asc' ) ? $result : - $result;

		}

	}

}