<?php

/**
 * Class WOOMULTI_CURRENCY_Plugin_Woofunnels_Order_Bump
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOOMULTI_CURRENCY_Plugin_Woofunnels_Order_Bump {
	protected $settings;

	public function __construct() {
		$this->settings = WOOMULTI_CURRENCY_Data::get_ins();
		if ( $this->settings->get_enable() ) {
			add_filter( 'wfob_show_product_price', array( $this, 'wfob_show_product_price' ), 10, 4 );
			add_filter( 'wfob_show_product_price_placeholder', array( $this, 'wfob_show_product_price_placeholder' ), 20, 4 );
		}
	}

	/**
	 * @param $status
	 * @param $product WC_Product
	 * @param $cart_item_key
	 * @param $price_data
	 *
	 * @return bool
	 */
	public function wfob_show_product_price( $status, $product, $cart_item_key, $price_data ) {
		if (  empty( $cart_item_key ) ) {
			return $status;
		}

		if ( ! in_array( $product->get_type(), WFOB_Common::get_subscription_product_type() ) ) {
			$status = false;
		}

		return $status;
	}

	/**
	 * @param $price_html
	 * @param $product WC_Product
	 * @param $cart_item_key
	 * @param $price_data
	 *
	 * @return bool
	 */
	public function wfob_show_product_price_placeholder( $price_html, $product, $cart_item_key, $price_data ) {
//		if ( $cart_item_key ) {
//			return $price_html;
//		}
		if ( ! in_array( $product->get_type(), WFOB_Common::get_subscription_product_type() ) ) {
			if ( $price_data['price'] > 0 && ( absint( $price_data['price'] ) !== absint( $price_data['regular_org'] ) ) ) {
				$price_html = wc_format_sale_price( wmc_get_price( $price_data['regular_org'] ), wmc_get_price( $price_data['price'] ) );
			} else {
				$price_html = wc_price( wmc_get_price( $price_data['price'] ) );
			}
		}

		return $price_html;
	}
}