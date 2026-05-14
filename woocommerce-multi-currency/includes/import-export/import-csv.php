<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @subpackage Plugin
 */
class WOOMULTI_CURRENCY_Exim_Import_CSV {
	protected static $settings;
	protected static $instance = null;

	public static function instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function __construct() {
		self::$settings = WOOMULTI_CURRENCY_Data::get_ins();
		add_action( 'wp_ajax_wmc_bulk_fixed_price', array( $this, 'import_csv' ) );
	}

	public function import_csv() {
		check_ajax_referer( 'wmc-bulk-fixed-price-nonce', 'security' );
		if ( ! current_user_can( 'edit_products' ) ) {
			wp_die( 'Sorry you are not allowed to do this.' );
		}
		$ext = explode( '.', $_FILES['csv_file']['name'] );
		$pos = sanitize_text_field( $_POST['pos'] );
		$row = sanitize_text_field( $_POST['row'] );
		if ( in_array( $_FILES['csv_file']['type'], array(
				'text/csv',
				'application/vnd.ms-excel'
			) ) && end( $ext ) == 'csv' ) {
			if ( ( $file_data = fopen( $_FILES['csv_file']['tmp_name'], "r" ) ) !== false ) {
				$size   = ( $_FILES['csv_file']['size'] );
				$header = fgetcsv( $file_data );
				if ( $pos == 0 ) {
					$pos = ftell( $file_data );
				}
				fseek( $file_data, $pos );
				$currencies = $this->get_active_currencies();
				for ( $i = 0; $i < 30; $i ++ ) {
					$data = fgetcsv( $file_data );
					if ( count( $currencies ) && ! empty( $data ) ) {
						$_regular_price_wmcp = $_sale_price_wmcp = array();
						$id                  = $data[0];
						$src                 = array_combine( $header, $data );
						foreach ( $currencies as $currency ) {
							$regular_price = isset( $src[ $currency ] ) ? $src[ $currency ] : '';
							$sale_price    = isset( $src[ $currency . '-sale' ] ) ? $src[ $currency . '-sale' ] : '';
							if ( floatval( $sale_price ) <= floatval( $regular_price ) ) {
								$_regular_price_wmcp[ $currency ] = $regular_price;
								$_sale_price_wmcp[ $currency ]    = $sale_price;
							} else {
								$_regular_price_wmcp[ $currency ] = '';
								$_sale_price_wmcp[ $currency ]    = '';
							}
						}
						update_post_meta( $id, '_regular_price_wmcp', json_encode( $_regular_price_wmcp ) );
						update_post_meta( $id, '_sale_price_wmcp', json_encode( $_sale_price_wmcp ) );
						$row ++;
					}
				}
				$current_pos = ftell( $file_data );
				$percentage  = round( $current_pos / $size * 100 );
				$data        = array(
					'pos'        => $current_pos,
					'percentage' => $percentage,
					'row'        => $row,
					'finish'     => false
				);
				fseek( $file_data, 0, SEEK_END );
				$last_pos = ftell( $file_data );
				if ( $current_pos >= $last_pos ) {
					$data['finish'] = true;
				}
				wp_send_json_success( $data );
			} else {
				wp_send_json_error( array( 'message' => esc_html__( 'Unable to read file', 'woocommerce-multi-currency' ) ) );
			}
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'File not supported', 'woocommerce-multi-currency' ) ) );
		}
	}

	public function get_active_currencies() {
		return array_values( array_diff( self::$settings->get_currencies(), array( self::$settings->get_default_currency() ) ) );
	}
}
