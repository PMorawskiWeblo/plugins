<?php

class WOOMULTI_CURRENCY_Plugin_Product_Add_On_Ultimate {
	public function __construct() {
		add_filter( 'pewc_filter_field_price', [ $this, 'field_price_convert' ] );
		add_filter( 'pewc_filter_option_price', [ $this, 'field_price_convert' ] );
		add_filter( 'woocommerce_get_cart_item_from_session', [ $this, 'set_price_with_current_currency' ], 10, 2 );
		add_filter( 'pewc_after_add_cart_item_data', [ $this, 'add_currency_after_add_cart_item_data' ] );

	}

	public function field_price_convert( $amount ) {
		return wmc_get_price( $amount );
	}

	public function set_price_with_current_currency( $cart_item, $values ) {
		if ( empty( $values['product_extras'] ) ) {
			return $cart_item;
		}
		$pid = $values['variation_id'] ? $values['variation_id'] : $values['product_id'];

		$product                   = wc_get_product( $pid );
		$product_price             = floatval( $product->get_price() );
		$current_price_with_extras = $values['product_extras']['price_with_extras'] ?? 0;
		$current_original_price    = $values['product_extras']['original_price'] ?? 0;
		$option_price              = $current_price_with_extras - $current_original_price;
		$option_price              = wmc_get_price( $option_price );

		$cart_item['product_extras']['price_with_extras'] = $product_price + $option_price;
		$cart_item['product_extras']['original_price']    = $product_price;

		return $cart_item;
	}

	public function add_currency_after_add_cart_item_data( $cart_item_data ) {
		// Save the selected currency
		$currency = WOOMULTI_CURRENCY_Data::get_ins()->get_current_currency();

		$cart_item_data['product_extras']['wmc_currency'] = $currency;

		return $cart_item_data;
	}
}

