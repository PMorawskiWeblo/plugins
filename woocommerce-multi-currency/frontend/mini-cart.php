<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WOOMULTI_CURRENCY_Frontend_Mini_Cart
 */
class WOOMULTI_CURRENCY_Frontend_Mini_Cart {
	protected $settings;

	public function __construct() {
		$this->settings = WOOMULTI_CURRENCY_Data::get_ins();
		if ( $this->settings->get_enable() ) {
			if ( is_plugin_active( 'woocommerce-memberships/woocommerce-memberships.php' ) || is_plugin_active( 'polylang/polylang.php' ) || is_plugin_active( 'woocommerce-side-cart-premium/xoo-wsc-main.php' ) || is_plugin_active( 'woocommerce-myparcel/woocommerce-myparcel.php' ) ) {
				add_action( 'woocommerce_before_mini_cart', array( $this, 'woocommerce_before_mini_cart' ), 99 );
			} else {
				add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'woocommerce_before_mini_cart' ), 99 );
			}

			add_action( 'wp_enqueue_scripts', array( $this, 'remove_session' ) );
		}
	}

	public function remove_session() {
		$selected_currencies = $this->settings->get_currencies();
		if ( isset( $_GET['wmc-currency'] ) && in_array( $_GET['wmc-currency'], $selected_currencies ) ) {
			wp_enqueue_script( 'woocommerce-multi-currency-cart', WOOMULTI_CURRENCY_JS . 'woocommerce-multi-currency-cart.js', array( 'jquery' ), WOOMULTI_CURRENCY_VERSION );
		}
	}

	/**
	 * Recalculator for mini cart
	 */
	public function woocommerce_before_mini_cart() {
		do_action( 'wmc_before_force_recalculate_totals' );
		@WC()->cart->calculate_totals();
		do_action( 'wmc_after_force_recalculate_totals' );
	}
}