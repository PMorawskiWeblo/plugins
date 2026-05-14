<?php

/**
 * Class WOOMULTI_CURRENCY_Frontend_Update
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOOMULTI_CURRENCY_Frontend_Cache {
	protected static $settings;
	protected $price_args;
	protected $mini_cart;

	public function __construct() {
		self::$settings = WOOMULTI_CURRENCY_Data::get_ins();
		if ( self::$settings->get_enable() ) {
//			add_action( 'init', array( $this, 'clear_browser_cache' ) );
			add_action( 'wp_ajax_wmc_get_products_price', array( $this, 'get_products_price' ) );
			add_action( 'wp_ajax_nopriv_wmc_get_products_price', array( $this, 'get_products_price' ) );

			$cache_compatible = self::$settings->get_param( 'cache_compatible' );
			if ( $cache_compatible || ( self::$settings->enable_switch_currency_by_js() && self::$settings->get_param( 'do_not_reload_page' ) ) ) {

				if ( $cache_compatible == 1 ) {
					add_filter( 'woocommerce_get_price_html', array( $this, 'compatible_cache_plugin' ), PHP_INT_MAX, 2 );
				} elseif ( $cache_compatible == 2 ) {
					add_filter( 'wc_price', array( $this, 'compatible_cache_plugin_by_json' ), 1000, 5 );
					add_action( 'woocommerce_before_mini_cart_contents', [ $this, 'start_mini_cart' ] );
					add_action( 'woocommerce_after_mini_cart', [ $this, 'end_mini_cart' ] );
				}
			}
		}
	}

	/**
	 * @param $price
	 * @param $product WC_Product
	 *
	 * @return string
	 */
	public function compatible_cache_plugin( $price, $product ) {
		$wrap = 'span';
		if ( strpos( $price, '<div' ) !== false || strpos( $price, '<p' ) !== false ) {
			$wrap = 'div';
		}

		return "<{$wrap} class='wmc-cache-pid' data-wmc_product_id='{$product->get_id()}'>" . $price . "</{$wrap}>";
	}

	public function start_mini_cart() {
		$this->mini_cart = true;
	}

	public function end_mini_cart() {
		$this->mini_cart = false;
	}

	public function compatible_cache_plugin_by_json( $return, $price, $args, $unformatted_price, $original_price ) {
		if ( is_cart() || is_checkout() || $this->mini_cart) {
			return $return;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return $return;
		}

		if ( ! empty( $args['wmc_cache_price'] ) ) {
			return $return;
		}

		$currency         = self::$settings->get_current_currency();
		$list_currencies  = self::$settings->get_list_currencies();
		$default_currency = self::$settings->get_default_currency();

		$cache = [];

		if ( $currency !== $default_currency ) {
			$original_price = wmc_revert_price( $original_price, $currency );
		}

		foreach ( $list_currencies as $currency_code => $currency_data ) {
			$wmc_price    = wmc_get_price( $original_price, $currency_code );
			$price_format = \WOOMULTI_CURRENCY_Data::get_price_format( $currency_data['pos'] ?? 'left' );

			$cache[ $currency_code ] = wc_price( $wmc_price, [
				'currency'        => $currency_code,
				'wmc_cache_price' => 1,
				'price_format'    => $price_format,
				'decimals'        => (int) $currency_data['decimals'] ?? 0
			] );
		}

		if ( $cache ) {
			$cache = wp_json_encode( $cache );
			$cache = _wp_specialchars( $cache, ENT_QUOTES, 'UTF-8', true );

			$wrap = 'span';
			if ( strpos( $price, '<div' ) !== false || strpos( $price, '<p' ) !== false ) {
				$wrap = 'div';
			}

			return "<{$wrap} class='wmc-wc-price' >" . $return . "<span data-wmc_price_cache='{$cache}' style='display: none;' class='wmc-price-cache-list'></span></{$wrap}>";
		}

		return $return;
	}

	/**
	 * Clear cache browser
	 */
	public function clear_browser_cache() {
		if ( isset( $_GET['wmc-currency'] ) ) {
			header( "Cache-Control: no-cache, must-revalidate" );
			header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
			header( "Content-Type: application/xml; charset=utf-8" );
		}
	}

	/**
	 *
	 */
	public function get_products_price() {
		do_action( 'wmc_get_products_price_ajax_handle_before' );
		$pids             = ! empty( $_POST['pids'] ) ? wc_clean( $_POST['pids'] ) : [];
		$shortcodes       = ! empty( $_POST['shortcodes'] ) ? wc_clean( $_POST['shortcodes'] ) : array();
		$current_currency = self::$settings->get_current_currency();
		$list_currencies  = self::$settings->get_list_currencies();
		$result           = [ 'shortcodes' => [] ];

		$data   = $list_currencies[ $current_currency ];
		$format = WOOMULTI_CURRENCY_Data::get_price_format( $data['pos'] );
		$args   = array( 'currency' => $current_currency, 'price_format' => $format );

		if ( isset( $data['decimals'] ) ) {
			$args['decimals'] = absint( $data['decimals'] );
		}

		if ( ! empty( $pids ) ) {
			$this->price_args = $args;
			add_filter( 'wc_price_args', array( $this, 'change_price_format_by_specific_currency' ), PHP_INT_MAX );
			foreach ( $pids as $pid ) {
				$product = wc_get_product( $pid );
				if ( $product ) {
					$result['prices'][ $pid ] = $product->get_price_html();
				}
			}
			remove_filter( 'wc_price_args', array( $this, 'change_price_format_by_specific_currency' ), PHP_INT_MAX );
			$this->price_args = array();
		}

		$result['current_currency'] = $current_currency;
		$result['current_country']  = strtolower( self::$settings->get_country_data( $current_currency )['code'] );
		$shortcodes_list            = self::$settings->get_list_shortcodes();

		if ( count( $shortcodes ) ) {
			foreach ( $shortcodes as $shortcode ) {
				if ( isset( $shortcodes_list[ $shortcode['layout'] ] ) ) {
					$flag_size              = isset( $shortcode['flag_size'] ) ? $shortcode['flag_size'] : '';
					$dropdown_icon          = isset( $shortcode['dropdown_icon'] ) ? $shortcode['dropdown_icon'] : '';
					$custom_format          = isset( $shortcode['custom_format'] ) ? $shortcode['custom_format'] : '';
					$result['shortcodes'][] = do_shortcode( "[woo_multi_currency_{$shortcode['layout']} flag_size='{$flag_size}' dropdown_icon='{$dropdown_icon}' custom_format='{$custom_format}']" );
				} else {
					$result['shortcodes'][] = do_shortcode( "[woo_multi_currency]" );
				}
			}
		}

		if ( ! empty( $_POST['exchange'] ) ) {
			$exchange_sc  = [];
			$exchange_arr = wc_clean( $_POST['exchange'] );
			foreach ( $exchange_arr as $ex ) {
				$exchange_sc[] = array_merge( $ex, [ 'shortcode' => do_shortcode( "[woo_multi_currency_exchange product_id='{$ex['product_id']}' keep_format='{$ex['keep_format']}' price='{$ex['price']}' original_price='{$ex['original_price']}' currency='{$ex['currency']}']" ) ] );
			}
			$result['exchange'] = $exchange_sc;
		}

		do_action( 'wmc_get_products_price_ajax_handle_after' );
		wp_send_json_success( apply_filters( 'wmc_get_products_price_ajax_handle_response', $result ) );
	}

	public function change_price_format_by_specific_currency( $args ) {
		if ( count( $this->price_args ) ) {
			$args = wp_parse_args( $this->price_args, $args );
		}

		return $args;
	}
}