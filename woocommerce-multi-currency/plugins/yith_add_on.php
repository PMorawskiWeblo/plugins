<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 21/06/2019
 * Time: 9:11 CH
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOOMULTI_CURRENCY_Plugin_Yith_Add_On {
	public function __construct() {
		if ( is_plugin_active( 'yith-woocommerce-product-add-ons/init.php' ) || is_plugin_active( 'yith-woocommerce-advanced-product-options-premium/init.php' ) ) {
			add_filter( 'wapo_print_option_price', array( $this, 'compatible_yith_add_on' ) );
			add_action( 'woocommerce_before_calculate_totals', array( $this, 'woocommerce_before_calculate_totals' ) );
		}
	}

	public function compatible_yith_add_on( $price ) {
		return wmc_get_price( $price );
	}

	public function woocommerce_before_calculate_totals( $data ) {

		$cart_contents = WC()->cart->get_cart_contents();
		foreach ( $cart_contents as $key => $content ) {
			if ( isset( $content['yith_wapo_options'] ) && is_array( $content['yith_wapo_options'] ) && count( $content['yith_wapo_options'] ) ) {
				foreach ( $content['yith_wapo_options'] as $sub_key => $option ) {
					if ( isset( $option['price_original'] ) ) {
						$cart_contents[ $key ]['yith_wapo_options'][ $sub_key ]['price'] = wmc_get_price( $option['price_original'] );
					}
				}
			}
		}

		WC()->cart->set_cart_contents( $cart_contents );
	}
}