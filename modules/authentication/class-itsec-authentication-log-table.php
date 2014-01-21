<?php
/**
 * Log tables for Authentication Module
 *
 * @package iThemes-Security
 * @subpackage Authentication
 * @since 4.0
 */
if ( ! class_exists( 'ITSEC_Authentication_Log_Table' ) ) {

	final class ITSEC_Authentication_Log_Table extends ITSEC_WP_List_Table {

		function __construct() {

			parent::__construct(
				array(
					'singular'	=> 'itsec_authentication_log_item',
					'plural'	=> 'itsec_authentication_log_items',
					'ajax'		=> false
				)
			);

		}

		public function display() {

		}

		public function prepare_items() {

			global $wpdb;

			$columns = $this->get_columns();
			$hidden = array();
			$sortable = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );

			$items = $wpdb->get_results( "SELECT * FROM `" . $wpdb->base_prefix . "itsec_log`;", ARRAY_A );

			$data = array();

			foreach ( $items as $item ) { //loop through and group 404s

				if ( isset( $data[$error['url']] ) ) {

					$data[$error['url']]['count'] = $data[$error['url']]['count'] + 1;
					$data[$error['url']]['time'] = $data[$error['url']]['time'] > $error['timestamp'] ? $data[$error['url']]['last'] : $error['timestamp'];
					$data[$error['url']]['referrer'] = $error['referrer'];
					if ( !in_array( $error['host'], $data[$error['url']]['host'] ) ) {
						$data[$error['url']]['host'][] = $error['host'];
					}
					$data[$error['url']]['id'] = $error['id'];
					$data[$error['url']]['url'] = $error['url'];

				} else {

					$data[$error['url']]['count'] = 1;
					$data[$error['url']]['time'] = $error['timestamp'];
					$data[$error['url']]['referrer'] = $error['referrer'];
					$data[$error['url']]['host'] = array($error['host']);
					$data[$error['url']]['id'] = $error['id'];
					$data[$error['url']]['url'] = $error['url'];
					$data[$error['url']]['last'] = $error['timestamp'];

				}

			}

			usort ( $data, array( &$this, 'sortrows' ) );

			$per_page = 50; //50 items per page
			$current_page = $this->get_pagenum();
			$total_items = count( $data );

			$data = array_slice( $data,( ( $current_page - 1 ) * $per_page ), $per_page );

			$rows = array();
			$count = 0;

			//Loop through results and take data we need
			foreach ( $data as $item => $attr ) {

				$rows[$count]['time'] = $attr['time'];
				$rows[$count]['id'] = $attr['id'];
				$rows[$count]['host'] = $attr['host'];
				$rows[$count]['uri'] = $attr['url'];
				$rows[$count]['referrer'] = $attr['referrer'];
				$rows[$count]['count'] = $attr['count'];

				$count++;

			}

			$this->items = $rows;
			$this->set_pagination_args(
				array(
					'total_items' => $total_items,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total_items/$per_page )
				)
			);

		}

	}

}