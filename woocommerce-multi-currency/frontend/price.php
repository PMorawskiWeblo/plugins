<?php

use Automattic\Jetpack\Constants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WOOMULTI_CURRENCY_Frontend_Price
 */
class WOOMULTI_CURRENCY_Frontend_Price {
	protected static $settings;
	protected static $language;
	protected $price;
	protected $change;
	protected static $decimal_data = '';
	public $prepare_param;
	public $show_approximate_price;

	public function __construct() {
		self::$settings = WOOMULTI_CURRENCY_Data::get_ins();
		$this->change   = false;

		//Fix for FB WC feed data
		if ( isset( $_GET['wc-api'] ) && $_GET['wc-api'] === 'wc_facebook_get_feed_data' ) {
			return;
		}

		if ( self::$settings->get_enable() ) {

			/*Pay with Multi Currencies*/
			add_action( 'init', array( $this, 'init' ), 99 );
			add_action( 'init', array( $this, 'add_change_price_hooks' ), 100 );

			/*Approximately*/
			add_action( 'init', array( $this, 'prepare_params' ), 999 );

			/*not work for variable product whose variations have the same price*/
			add_filter( 'woocommerce_get_price_html', array( $this, 'add_approximately_price' ), 20, 2 );

			add_filter( 'woocommerce_cart_product_price', array( $this, 'cart_product_approximately_price' ), 20, 2 );
			add_filter( 'woocommerce_cart_totals_order_total_html', array( $this, 'cart_total_approximately_price' ), 30 );
			add_filter( 'woocommerce_cart_subtotal', array( $this, 'cart_subtotal_approximately_price' ), 20, 3 );
			add_filter( 'woocommerce_cart_product_subtotal', array( $this, 'cart_product_subtotal_approximately_price' ), 20, 4 );
			add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'shipping_approximately_price' ), 20, 2 );
			add_filter( 'woocommerce_get_formatted_order_total', array( $this, 'woocommerce_get_formatted_order_total' ), 20, 2 );
			add_filter( 'woocommerce_cart_totals_taxes_total_html', array( $this, 'cart_totals_taxes_total_html' ), 20, 2 );

			if ( self::$settings->get_price_switcher() ) {
				add_action( 'woocommerce_single_product_summary', array( $this, 'add_price_switcher' ), 20 );
			}

			add_filter( 'wmc_get_price', array( $this, 'format_beauty_price' ), 99, 2 );
//			add_filter( 'wmc_is_get_price_shipping', array( $this, 'apply_beauty_price_to_shipping' ) );

			add_filter( 'wmc_set_decimals', array( $this, 'override_decimal' ), 999 );

			if ( is_plugin_active( 'woocommerce-bookings/woocommerce-bookings.php' ) ) {
				/*Fix wrong sale price added when changing currency for WooCommerce Bookings products*/
				add_filter( 'woocommerce_get_price_html', array( $this, 'woocommerce_get_price_html' ), 20, 2 );
			}

			/*Yith dynamic pricing and discount*/
			add_action( 'ywdpd_before_calculate_discounts', array( $this, 'remove_filters' ), 1 );
			add_action( 'ywdpd_after_calculate_discounts', array( $this, 'add_filters' ), 1 );
		}
	}

	public function add_change_price_hooks() {
		$current_currency = self::$settings->get_current_currency();
		$default_currency = self::$settings->get_default_currency();

		if ( $default_currency !== $current_currency ) {
			/*Simple product*/
			add_filter( 'woocommerce_product_get_regular_price', array( $this, 'woocommerce_product_get_regular_price' ), 99, 2 );
			add_filter( 'woocommerce_product_get_sale_price', array( $this, 'woocommerce_product_get_sale_price' ), 99, 2 );
			add_filter( 'woocommerce_product_get_price', array( $this, 'woocommerce_product_get_price' ), 99, 2 );

			/*Variable price*/
			add_filter( 'woocommerce_product_variation_get_price', array( $this, 'woocommerce_product_variation_get_price' ), 99, 2 );
			add_filter( 'woocommerce_product_variation_get_regular_price', array( $this, 'woocommerce_product_variation_get_regular_price' ), 99, 2 );
			add_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'woocommerce_product_variation_get_sale_price' ), 99, 2 );

			/*Variable Parent min max price*/
			add_filter( 'woocommerce_variation_prices', array( $this, 'get_woocommerce_variation_prices' ), 99, 3 );
		}
	}

	public function remove_filters() {
		remove_filter( 'woocommerce_product_get_price', array( $this, 'woocommerce_product_get_price' ), 99 );
	}

	public function add_filters() {
		add_filter( 'woocommerce_product_get_price', array( $this, 'woocommerce_product_get_price' ), 99, 2 );
	}

	/**
	 * @param $price_html
	 * @param $product WC_Product_Booking
	 *
	 * @return string
	 */
	public function woocommerce_get_price_html( $price_html, $product ) {
		if ( $product && is_a( $product, 'WC_Product_Booking' ) && self::$settings->get_current_currency() !== self::$settings->get_default_currency() ) {
			$base_price = WC_Bookings_Cost_Calculation::calculated_base_cost( $product );
			if ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
				if ( function_exists( 'wc_get_price_excluding_tax' ) ) {
					$display_price = wc_get_price_including_tax( $product, array(
						'qty'   => 1,
						'price' => $base_price,
					) );
				} else {
					$display_price = $product->get_price_including_tax( 1, $base_price );
				}
			} else {
				if ( function_exists( 'wc_get_price_excluding_tax' ) ) {
					$display_price = wc_get_price_excluding_tax( $product, array(
						'qty'   => 1,
						'price' => $base_price,
					) );
				} else {
					$display_price = $product->get_price_excluding_tax( 1, $base_price );
				}
			}
			remove_filter( 'woocommerce_product_get_price', array( $this, 'woocommerce_product_get_price' ), 99 );
			$display_price_suffix_comp  = wc_price( apply_filters( 'woocommerce_product_get_price', $display_price, $product ) ) . $product->get_price_suffix();
			$original_price_suffix_comp = wc_price( $display_price ) . $product->get_price_suffix();
			add_filter( 'woocommerce_product_get_price', array( $this, 'woocommerce_product_get_price' ), 99, 2 );
			$original_price_suffix = wc_price( $display_price ) . $product->get_price_suffix();
			$display_price         = apply_filters( 'woocommerce_product_get_price', $display_price, $product );
			$display_price_suffix  = wc_price( $display_price ) . $product->get_price_suffix();

			if ( $original_price_suffix_comp !== $display_price_suffix_comp ) {
				$price_html = "<del>{$original_price_suffix}</del><ins>{$display_price_suffix}</ins>";
			} elseif ( $display_price ) {
				if ( $product->has_additional_costs() || $product->get_display_cost() ) {
					/* translators: 1: display price */
					$price_html = sprintf( __( 'From: %s', 'woocommerce-bookings' ), wc_price( $display_price ) ) . $product->get_price_suffix();
				} else {
					$price_html = wc_price( $display_price ) . $product->get_price_suffix();
				}
			} elseif ( ! $product->has_additional_costs() ) {
				$price_html = __( 'Free', 'woocommerce-bookings' );
			} else {
				$price_html = '';
			}
		}

		return $price_html;
	}

	/**
	 *
	 */
	public function add_price_switcher() {
		if ( is_product() ) {
			echo do_shortcode( '[woo_multi_currency_product_price_switcher]' );
		}
	}

	/**
	 * @param $product WC_Product_Variable|WC_Product
	 * @param bool $currency_code
	 * @param bool $raw
	 *
	 * @return float|int|mixed|string
	 */
	public static function get_variation_max_price( $product, $currency_code = false, $raw = false ) {
		$variation_ids     = $product->get_visible_children();
		$price_max         = 0;
		$check_fixed_price = self::$settings->check_fixed_price();
		foreach ( $variation_ids as $variation_id ) {
			$variation = wc_get_product( $variation_id );

			if ( $variation ) {
				$price = 0;
				if ( ! $currency_code ) {
					$current_currency = self::$settings->get_current_currency();
				} elseif ( ! $raw ) {
					$current_currency = $currency_code;
				}

				if ( $check_fixed_price && ! $raw ) {
					$fixed_price = self::get_fixed_price( $variation, $current_currency );
					if ( $fixed_price !== false || self::$settings->get_params( 'ignore_exchange_rate' ) ) {
						$price = $fixed_price;
					}
				}

				if ( ! $price && ! self::$settings->get_params( 'ignore_exchange_rate' ) ) {
					$price = $variation->get_price( 'edit' );
					if ( ! $raw ) {
						$price = wmc_get_price( $price, $currency_code );
					}
				}

				if ( $price > $price_max ) {
					$price_max = $price;
				}
			}
		}

		return apply_filters( 'wmc_get_variation_max_price', $price_max, $product, $currency_code, $raw );
	}

	/**
	 * @param $product WC_Product|WC_Product_Variable
	 * @param bool $currency_code
	 * @param bool $raw
	 *
	 * @return bool|float|int|mixed|string
	 */
	public static function get_variation_min_price( $product, $currency_code = false, $raw = false ) {
		$variation_ids     = $product->get_visible_children();
		$price_min         = false;
		$check_fixed_price = self::$settings->check_fixed_price();
		foreach ( $variation_ids as $variation_id ) {
			$variation = wc_get_product( $variation_id );

			if ( $variation ) {
				$price = 0;

				if ( ! $currency_code ) {
					$current_currency = self::$settings->get_current_currency();
				} elseif ( ! $raw ) {
					$current_currency = $currency_code;
				}

				if ( $check_fixed_price && ! $raw ) {
					$fixed_price = self::get_fixed_price( $variation, $current_currency );
					if ( $fixed_price !== false || self::$settings->get_params( 'ignore_exchange_rate' ) ) {
						$price = $fixed_price;
					}
				}

				if ( ! $price && ! self::$settings->get_params( 'ignore_exchange_rate' ) ) {
					$price = $variation->get_price( 'edit' );
					if ( ! $raw ) {
						$price = wmc_get_price( $price, $currency_code );
					}
				}

				if ( $price_min === false ) {
					$price_min = $price;
				}

				if ( $price < $price_min ) {
					$price_min = $price;
				}
			}
		}

		return $price_min;
	}

	/**
	 *
	 */
	public function prepare_params() {
		if ( self::$settings->get_auto_detect() != 2 ) {
			return;
		}

		if ( ! self::$settings->getcookie( 'wmc_currency_rate' ) || ! self::$settings->getcookie( 'wmc_currency_symbol' ) || ! self::$settings->getcookie( 'wmc_ip_info' ) ) {
			return;
		}

		$this->show_approximate_price = true;
		$geoplugin_arg                = json_decode( base64_decode( self::$settings->getcookie( 'wmc_ip_info' ) ), true );
		$detect_currency_code         = isset( $geoplugin_arg['currency_code'] ) ? $geoplugin_arg['currency_code'] : '';
		$country_code                 = isset( $geoplugin_arg['country'] ) ? $geoplugin_arg['country'] : '';
		$currencies                   = self::$settings->get_currencies();

		if ( apply_filters( 'wmc_approximate_price_currency_by_country', self::$settings->get_enable_currency_by_country() ) ) {
			foreach ( $currencies as $currency ) {
				$data = self::$settings->get_currency_by_countries( $currency );
				if ( in_array( $country_code, $data ) ) {
					$detect_currency_code = $currency;
					break;
				}
			}
		}

		if ( $detect_currency_code == self::$settings->get_current_currency() ) {
			return;
		}

		$list_currencies  = self::$settings->get_list_currencies();
		$default_currency = self::$settings->get_default_currency();
		if ( $detect_currency_code && isset( $list_currencies[ $detect_currency_code ] ) ) {
			$decimals    = (int) $list_currencies[ $detect_currency_code ]['decimals'];
			$current_pos = $list_currencies[ $detect_currency_code ]['pos'];
		} else {
			$decimals    = (int) $list_currencies[ $default_currency ]['decimals'];
			$current_pos = $list_currencies[ $default_currency ]['pos'];
		}
		$format = '';
		switch ( $current_pos ) {
			case 'left' :
				$format = '%1$s%2$s';
				break;
			case 'right' :
				$format = '%2$s%1$s';
				break;
			case 'left_space' :
				$format = '%1$s&nbsp;%2$s';
				break;
			case 'right_space' :
				$format = '%2$s&nbsp;%1$s';
				break;
		}

		$this->prepare_param = array(
			'rate'               => self::$settings->getcookie( 'wmc_currency_rate' ),
			'symbol'             => self::$settings->getcookie( 'wmc_currency_symbol' ),
			'format'             => $format,
			'decimals'           => $decimals,
			'decimal_separator'  => wc_get_price_decimal_separator(),
			'thousand_separator' => wc_get_price_thousand_separator(),
			'priority'           => self::$settings->get_param( 'approximately_priority' ),
			'currency_code'      => $detect_currency_code
		);
	}

	/**
	 * @param $html_price
	 * @param $product WC_Product
	 *
	 * @return string
	 */
	public function add_approximately_price( $html_price, $product ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $html_price;
		}
		if ( self::$settings->get_auto_detect() == 2 ) {

			if ( '' === $product->get_price() || empty( $this->prepare_param ) ) { // || ! $product->is_in_stock()
				return $html_price;
			}

			$params          = $this->prepare_param;
			$raw_price       = wc_get_price_to_display( $product, array(
				'qty'   => 1,
				'price' => $product->get_price( 'edit' )
			) );
			$price           = number_format( apply_filters( 'wmc_get_price', $raw_price * $params['rate'], $params['currency_code'] ), (int) $params['decimals'], $params['decimal_separator'], $params['thousand_separator'] );
			$pos             = strpos( $params['symbol'], '#PRICE#' );
			$formatted_price = $pos ? str_replace( '#PRICE#', $price, $params['symbol'] ) : sprintf( $params['format'], $params['symbol'], $price );

			$max_price = '';
			if ( $product->get_type() == 'variable' ) {
				$price_max = self::get_variation_max_price( $product, false, true );
				if ( $price_max != $product->get_price( 'edit' ) ) {
					$raw_price = wc_get_price_to_display( $product, array( 'qty' => 1, 'price' => $price_max ) );
					$price_max = number_format( apply_filters( 'wmc_get_price', $raw_price * $params['rate'], $params['currency_code'] ), (int) $params['decimals'], $params['decimal_separator'], $params['thousand_separator'] );
					$max_price = $pos ? str_replace( '#PRICE#', $price_max, $params['symbol'] ) : sprintf( $params['format'], $params['symbol'], $price_max );
					$max_price = ' - ' . $max_price;
				}
			}

			$appro_label = self::$settings->get_param( 'approximately_label' );
			$pos         = strpos( $appro_label, '{price}' );
			$appro_price = $pos ? str_replace( '{price}', $formatted_price . $max_price, $appro_label ) : $appro_label . $formatted_price . $max_price;

			$html_price = $params['priority'] ? '<span class="wmc-approximately">' . $appro_price . '</span>' . $html_price : $html_price . '<span class="wmc-approximately">' . $appro_price . '</span>';

		}

		return $html_price;
	}

	/**
	 * @param $html
	 *
	 * @return string
	 */
	public function cart_total_approximately_price( $html ) {
		if ( ! $this->show_approximate_price || ! in_array( 'total', (array) self::$settings->get_param( 'approximate_position' ) ) ) {
			return $html;
		}

		$total_price = wc()->cart->get_total( 'edit' );
		$appro_price = $this->get_approximately_price( floatval( $total_price ) );

		return self::$settings->get_param( 'approximately_priority' ) ? $appro_price . $html : $html . $appro_price;
	}

	/**
	 * @param $cart_subtotal
	 * @param $compound
	 * @param $cart WC_Cart
	 *
	 * @return string
	 */
	public function cart_subtotal_approximately_price( $cart_subtotal, $compound, $cart ) {
		if ( ! $this->show_approximate_price || ! in_array( 'subtotal', (array) self::$settings->get_param( 'approximate_position' ) ) ) {
			return $cart_subtotal;
		}

		if ( $compound ) {
			$subtotal_price = $cart->get_cart_contents_total() + $cart->get_shipping_total() + $cart->get_taxes_total( false, false );
		} elseif ( $cart->display_prices_including_tax() ) {
			$subtotal_price = $cart->get_subtotal() + $cart->get_subtotal_tax();
		} else {
			$subtotal_price = $cart->get_subtotal();
		}

		$appro_price = $this->get_approximately_price( $subtotal_price );

		return $this->prepare_param['priority'] ? $appro_price . $cart_subtotal : $cart_subtotal . $appro_price;
	}

	public function cart_product_approximately_price( $product_price, $product ) {
		if ( ! $this->show_approximate_price || ! in_array( 'product', (array) self::$settings->get_param( 'approximate_position' ) ) ) {
			return $product_price;
		}
		if ( wc()->cart->display_prices_including_tax() ) {
			$item_price = wc_get_price_including_tax( $product );
		} else {
			$item_price = wc_get_price_excluding_tax( $product );
		}
		$appro_price = $this->get_approximately_price( $item_price );

		return $this->prepare_param['priority'] ? $appro_price . $product_price : $product_price . $appro_price;
	}

	/**
	 * @param $product_subtotal
	 * @param $product WC_Product
	 * @param $quantity
	 * @param $cart WC_Cart
	 *
	 * @return string
	 */
	public function cart_product_subtotal_approximately_price( $product_subtotal, $product, $quantity, $cart ) {
		if ( ! $this->show_approximate_price || ! in_array( 'product_subtotal', (array) self::$settings->get_param( 'approximate_position' ) ) ) {
			return $product_subtotal;
		}
		$price = $product->get_price();

		if ( $product->is_taxable() ) {
			if ( $cart->display_prices_including_tax() ) {
				$row_price = wc_get_price_including_tax( $product, array( 'qty' => $quantity ) );
			} else {
				$row_price = wc_get_price_excluding_tax( $product, array( 'qty' => $quantity ) );
			}
		} else {
			$row_price = $price * $quantity;
		}

		$appro_price = $this->get_approximately_price( $row_price );

		return $this->prepare_param['priority'] ? $appro_price . $product_subtotal : $product_subtotal . $appro_price;
	}

	/**
	 * @param $label
	 * @param $method WC_Shipping_Rate
	 *
	 * @return string
	 */
	public function shipping_approximately_price( $label, $method ) {
		if ( ! $this->show_approximate_price || ! in_array( 'shipping', (array) self::$settings->get_param( 'approximate_position' ) ) ) {
			return $label;
		}
		$has_cost  = 0 < $method->cost;
		$hide_cost = ! $has_cost && in_array( $method->get_method_id(), array( 'free_shipping', 'local_pickup' ), true );
		$raw_price = '';
		if ( $has_cost && ! $hide_cost ) {
			if ( WC()->cart->display_prices_including_tax() ) {
				$raw_price = $method->cost + $method->get_shipping_tax();
			} else {
				$raw_price = $method->cost;
			}
		}
		$appro_price = $this->get_approximately_price( $raw_price );

		return $this->prepare_param['priority'] ? $appro_price . $label : $label . $appro_price;
	}

	/**
	 * @param $formatted_total
	 * @param $order WC_Order
	 *
	 * @return string
	 */
	public function woocommerce_get_formatted_order_total( $formatted_total, $order ) {
		if ( ! $order ) {
			return $formatted_total;
		}
		if ( $this->show_approximate_price && in_array( 'total_thankyou', (array) self::$settings->get_param( 'approximate_position' ) ) ) {
			$appro_price = $this->get_approximately_price( $order->get_total() );

			$formatted_total = self::$settings->get_param( 'approximately_priority' ) ? $appro_price . $formatted_total : $formatted_total . $appro_price;
		} elseif ( is_order_received_page() && self::$settings->get_params( 'equivalent_currency' ) ) {
//			$current_currency = self::$settings->get_current_currency();
//			$order_currency   = $order->get_currency();
//			if ( self::$settings->get_current_currency() !== $order_currency ) {
//				$total = $order->get_total();
//				$total = wmc_revert_price( $total, $order_currency );
//				if ( self::$settings->get_default_currency() !== $current_currency ) {
//					$total = wmc_get_price( $total );
//				}
//				$formatted_total = $formatted_total . '(' . wc_price( $total, array( 'currency' => $current_currency ) ) . ')';
//			}
		}

		return $formatted_total;
	}

	public function cart_totals_taxes_total_html( $html ) {
		if ( ! $this->show_approximate_price || ! in_array( 'tax', (array) self::$settings->get_param( 'approximate_position' ) ) ) {
			return $html;
		}

		$taxes       = WC()->cart->get_taxes_total();
		$appro_taxes = wp_kses_post( $this->get_approximately_price( $taxes ) );

		return $this->prepare_param['priority'] ? $appro_taxes . $html : $html . $appro_taxes;
	}

	public function get_approximately_price( $raw_price ) {
		if ( empty( $this->prepare_param ) || ! $raw_price ) {
			return '';
		}
		$raw_price = wmc_revert_price( $raw_price );
		$params    = $this->prepare_param;
		$price     = number_format( $raw_price * $params['rate'], (int) $params['decimals'], $params['decimal_separator'], $params['thousand_separator'] );

		$pos             = strpos( $params['symbol'], '#PRICE#' );
		$formatted_price = $pos ? str_replace( '#PRICE#', $price, $params['symbol'] ) : sprintf( $params['format'], $params['symbol'], $price );

		$appro_label = self::$settings->get_param( 'approximately_label' );
		$pos         = strpos( $appro_label, '{price}' );
		$appro_price = $pos ? str_replace( '{price}', $formatted_price, $appro_label ) : $appro_label . $formatted_price;

		$html_price = '<div class="wmc-approximately">' . $appro_price . '</div>';

		return $html_price;
	}

	/**
	 * Check on checkout page
	 */

	public function init() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		/*Fix UX Builder of Flatsome*/
		if ( isset( $_GET['uxb_iframe'] ) ) {
			return;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return;
		}

		$is_cart     = $this->is_cart();
		$is_checkout = $this->is_checkout();
		if ( ! ( wp_doing_ajax() && isset( $_POST['action'] ) && $_POST['action'] == 'wmc_get_products_price' || ! wp_doing_ajax() ) ) {
			return;
		}

		$allow_multi = self::$settings->get_enable_multi_payment();

		$current_currency       = self::$settings->get_current_currency();
		$checkout_currency      = self::$settings->get_checkout_currency();
		$checkout_currency_args = self::$settings->get_checkout_currency_args();
		$old_currency           = self::$settings->getcookie( 'wmc_current_currency_old' );

		if ( in_array( $current_currency, $checkout_currency_args ) ) {
			$checkout_currency = $current_currency;
		}

		/*Checkout && Cartpage*/
		if ( ! $allow_multi ) {
			if ( $is_checkout ) {
				self::$settings->set_current_currency( self::$settings->get_default_currency(), false );
			} elseif ( self::$settings->enable_cart_page() && $is_cart ) {
				self::$settings->set_current_currency( $checkout_currency, false );
			} elseif ( $old_currency && $old_currency != $current_currency ) {
				self::$settings->set_current_currency( $old_currency, false );
			}
		} else {
			if ( ! isset( $_REQUEST['custom-page'] ) ) { //check ! isset( $_REQUEST['custom-page'] ) to fix looping checkout error with theme Handmade
				if ( $allow_multi && $is_checkout ) { // is checkout page
					self::$settings->set_current_currency( $checkout_currency, false );
				} elseif ( self::$settings->enable_cart_page() && $is_cart ) {
					self::$settings->set_current_currency( $checkout_currency, false );
				} elseif ( $old_currency && $old_currency != $current_currency ) {
					self::$settings->set_current_currency( $old_currency, false );
				}
			}
		}

		/*need testing more*/
		if ( ! WOOMULTI_CURRENCY_Data::is_request_to_rest_api() ) {
			$list_currencies  = self::$settings->get_list_currencies();
			$current_currency = self::$settings->get_current_currency();
			$need_update      = false;
			if ( ! empty( $list_currencies[ $current_currency ] ) ) {
				if ( $list_currencies[ $current_currency ]['hide'] === '1' ) {
					$need_update = true;
				}
			} else {
				$need_update = true;
			}
			if ( $need_update ) {
				self::$settings->set_fallback_currency();
			}
		}

		do_action( 'wmc_after_init_currency', self::$settings, $is_cart, $is_checkout, $this );
	}

	public function get_current_url() {
		global $wp;
		$current_url = site_url( add_query_arg( array(), $wp->request ) );

		$redirect_url = isset( $_SERVER['REDIRECT_URI'] ) ? $_SERVER['REDIRECT_URI'] : '';
		$redirect_url = ! empty( $_SERVER['REDIRECT_URL'] ) ? $_SERVER['REDIRECT_URL'] : $redirect_url;
		$redirect_url = ! empty( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : $redirect_url;

		return $current_url . $redirect_url;
	}

	public function get_post_from_url() {
		$current_url = $this->get_current_url();
		if ( class_exists( 'SitePress' ) ) {
			global $sitepress;
			remove_filter( 'url_to_postid', array( $sitepress, 'url_to_postid' ) );
			$id = url_to_postid( $current_url );
			add_filter( 'url_to_postid', array( $sitepress, 'url_to_postid' ) );
		} else {
			$id = url_to_postid( $current_url );
		}

		$post = get_post( $id );

		return $post;
	}

	public function has_shortcode( $post, $tag ) {
		return is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $tag );
	}

	public function is_cart() {
		if ( class_exists( 'Polylang' ) && function_exists( 'is_cart' ) ) {
			return is_cart();
		} else {
			return $this->has_shortcode( $this->get_post_from_url(), 'woocommerce_cart' ) || strpos( $this->get_current_url(), wc_get_cart_url() ) !== false || Constants::is_defined( 'WOOCOMMERCE_CART' );
		}
	}

	public function is_checkout() {
		if ( class_exists( 'Polylang' ) && function_exists( 'is_checkout' ) ) {
			return is_checkout();
		} else {
			return $this->has_shortcode( $this->get_post_from_url(), 'woocommerce_checkout' ) || strpos( $this->get_current_url(), wc_get_checkout_url() ) !== false || Constants::is_defined( 'WOOCOMMERCE_CHECKOUT' ) || apply_filters( 'woocommerce_is_checkout', false );
		}
	}

	/**
	 * Variable Parent min max price
	 *
	 * @param $price_arr
	 *
	 * @return array
	 */
	public function get_woocommerce_variation_prices( $price_arr, $product, $for_display ) {
		$current_currency = self::$settings->get_current_currency();

		$temp_arr = $price_arr;
		if ( is_array( $price_arr ) && ! empty( $price_arr ) ) {
			$check_fixed_price = self::$settings->check_fixed_price();
			$ignore_exchange   = self::$settings->get_params( 'ignore_exchange_rate' );
			foreach ( $price_arr as $price_type => $values ) {
				foreach ( $values as $key => $value ) {
					if ( $check_fixed_price ) {

						if ( $temp_arr['regular_price'][ $key ] != $temp_arr['price'][ $key ] ) {
							if ( $price_type == 'regular_price' ) {
								$fixed_price = self::get_fixed_price( wc_get_product( $key ), $current_currency, 'regular_price' );

								if ( $fixed_price !== false || $ignore_exchange ) {
									$price_arr[ $price_type ][ $key ] = $for_display ? $this->tax_handle( $fixed_price, $product ) : $fixed_price;
								} else {
									$price_arr[ $price_type ][ $key ] = wmc_get_price( $value );
								}
							}

							if ( $price_type == 'price' || $price_type == 'sale_price' ) {
								$fixed_price = self::get_fixed_price( wc_get_product( $key ), $current_currency, 'sale_price' );

								if ( $fixed_price !== false || $ignore_exchange ) {
									$price_arr[ $price_type ][ $key ] = $for_display ? $this->tax_handle( $fixed_price, $product ) : $fixed_price;
								} else {
									$price_arr[ $price_type ][ $key ] = wmc_get_price( $value );
								}
							}
						} else {
							$fixed_price = self::get_fixed_price( wc_get_product( $key ), $current_currency, 'regular_price' );
							if ( $fixed_price !== false || $ignore_exchange ) {
								$price_arr[ $price_type ][ $key ] = $for_display ? $this->tax_handle( $fixed_price, $product ) : $fixed_price;
							} else {
								$price_arr[ $price_type ][ $key ] = wmc_get_price( $value );
							}
						}

					} else {
						$price_arr[ $price_type ][ $key ] = wmc_get_price( $value );
					}
				}
			}
		}

		return $price_arr;
	}

	public function tax_handle( $price, $product ) {
		if ( ! $price ) {
			return $price;
		}

		$data = array( 'qty' => 1, 'price' => $price, );

		return 'incl' === get_option( 'woocommerce_tax_display_shop' ) ? wc_get_price_including_tax( $product, $data ) : wc_get_price_excluding_tax( $product, $data );
	}

	/**
	 * @param $price
	 * @param $product WC_Product
	 *
	 * @return mixed
	 */
	public function woocommerce_product_variation_get_sale_price( $price, $product ) {
		if ( ! $price ) {
			return $price;
		}

		$product_id = $product->get_id();
		if ( ! wp_doing_ajax() && isset( $this->price[ $product_id ][ $price ] ) ) {
			return $this->price[ $product_id ][ $price ];
		}
		$changes = $product->get_changes();

		if ( self::$settings->check_fixed_price() && ( is_array( $changes ) ) && count( $changes ) < 1 ) {

			$current_currency = self::$settings->get_current_currency();
			$fixed_price      = self::get_fixed_price( $product, $current_currency, 'sale_price' );
			if ( $fixed_price !== false || self::$settings->get_params( 'ignore_exchange_rate' ) ) {
				return $this->set_cache( $fixed_price, $product_id, $price );
			}
		}

		return $this->set_cache( wmc_get_price( $price ), $product_id, $price );
		//Do nothing to remove prices hash to always get live price.
	}

	/**
	 * Set price to global. It will help more speedy.
	 *
	 * @param $price
	 * @param $id
	 * @param $key
	 *
	 * @return float
	 */
	protected function set_cache( $price, $id, $key ) {
		$ajax = isset( $_GET['wc-ajax'] ) ? sanitize_text_field( $_GET['wc-ajax'] ) : '';
		if ( $ajax === 'ppc-create-order' ) {
			/**
			 * Fix bug with WooCommerce PayPal Payments
			 *
			 * 'HUF', 'JPY' and 'TWD' do not support decimals, the rest must have 2 decimals
			 */
			$current_currency = self::$settings->get_current_currency();
			$args             = array( 'decimals' => 2 );
			if ( in_array( $current_currency, array( 'HUF', 'JPY', 'TWD' ) ) ) {
				$args['decimals'] = 0;
			}
			$price = WOOMULTI_CURRENCY_Data::convert_price_to_float( $price, $args );
		} else {
//			$current_currency = self::$settings->get_current_currency();
//			$list_currencies  = self::$settings->get_list_currencies();
//			if ( isset( $list_currencies[ $current_currency ]['decimals'] ) ) {
//				$price = WOOMULTI_CURRENCY_Data::convert_price_to_float( $price, array( 'decimals' => absint( $list_currencies[ $current_currency ]['decimals'] ) ) );
//			}
		}

		if ( $price && $id && $key ) {
			/*Default decimal is "."*/
			$this->price[ $id ][ $key ] = str_replace( ',', '.', $price );

			return $this->price[ $id ][ $key ];
		} else {
			return $price === false ? '' : $price;
		}
	}

	/**
	 * @param $price
	 * @param $product WC_Product
	 *
	 * @return mixed
	 */
	public function woocommerce_product_variation_get_regular_price( $price, $product ) {
		if ( ! $price ) {
			return $price;
		}
		$product_id = $product->get_id();
		if ( ! wp_doing_ajax() && isset( $this->price[ $product_id ][ $price ] ) ) {
			return $this->price[ $product_id ][ $price ];
		}
		$changes = $product->get_changes();

		if ( self::$settings->check_fixed_price() && ( is_array( $changes ) ) && count( $changes ) < 1 ) {

			$current_currency = self::$settings->get_current_currency();
			$fixed_price      = self::get_fixed_price( $product, $current_currency, 'regular_price' );
			if ( $fixed_price !== false || self::$settings->get_params( 'ignore_exchange_rate' ) ) {
				return $this->set_cache( $fixed_price, $product_id, $price );
			}
		}
		do_action( 'wmc_before_convert_regular_price', $product, $price );
		$price = $this->set_cache( wmc_get_price( $price ), $product_id, $price );
		do_action( 'wmc_after_convert_regular_price', $product, $price );

		return $price;
	}

	/**
	 * @param $price
	 * @param $product WC_Product
	 *
	 * @return mixed
	 */
	public function woocommerce_product_variation_get_price( $price, $product ) {
		if ( ! $price ) {
			return $price;
		}
		$product_id = $product->get_id();

		if ( ! wp_doing_ajax() && isset( $this->price[ $product_id ][ $price ] ) ) {
			return $this->price[ $product_id ][ $price ];
		}

		$changes = $product->get_changes();

		if ( self::$settings->check_fixed_price() && ( is_array( $changes ) ) && count( $changes ) < 1 ) {

			$current_currency = self::$settings->get_current_currency();
			$fixed_price      = self::get_fixed_price( $product, $current_currency );

			if ( $fixed_price !== false || self::$settings->get_params( 'ignore_exchange_rate' ) ) {
				return $this->set_cache( $fixed_price, $product_id, $price );
			}
		}


		return $this->set_cache( wmc_get_price( $price ), $product_id, $price );
	}

	/**
	 * @param $price
	 * @param $product WC_Product
	 *
	 * @return mixed
	 */
	public function woocommerce_product_get_price( $price, $product ) {
		$condition = apply_filters( 'wmc_product_get_price_condition', true, $price, $product );
		if ( ! $price || ! $condition ) {
			return $price;
		}

		$product_id = $product->get_id();

		if ( ! wp_doing_ajax() && isset( $this->price[ $product_id ][ $price ] ) ) {
			return $this->price[ $product_id ][ $price ];
		}

		$changes = $product->get_changes();
		if ( self::$settings->check_fixed_price() && ( is_array( $changes ) ) && count( $changes ) < 1 ) {
			$current_currency = self::$settings->get_current_currency();
			if ( $product->get_type() === 'variable' ) {

				$children = $product->get_visible_children();
				$prices   = [];
				if ( ! empty( $children ) ) {
					foreach ( $children as $child_id ) {
						$variation = wc_get_product( $child_id );
						$f_price   = self::get_fixed_price( $variation, $current_currency );
						$prices[]  = $f_price;
					}
				}
				$fixed_price = min( $prices );

			} else {
				$fixed_price = self::get_fixed_price( $product, $current_currency );
			}

			if ( $fixed_price !== false || self::$settings->get_params( 'ignore_exchange_rate' ) ) {
				return $this->set_cache( $fixed_price, $product_id, $price );
			}
		}

		$new_price = apply_filters( 'wmc_wc_product_get_price', wmc_get_price( $price ) );

		return $this->set_cache( $new_price, $product_id, $price );
	}

	/**
	 * @param $price
	 * @param $product WC_Product
	 *
	 * @return mixed
	 */
	public function woocommerce_product_get_sale_price( $price, $product ) {
		if ( ! $price ) {
			return $price;
		}
		$product_id = $product->get_id();
		if ( ! wp_doing_ajax() && isset( $this->price[ $product_id ][ $price ] ) ) {
			return $this->price[ $product_id ][ $price ];
		}
		$changes = $product->get_changes();

		if ( self::$settings->check_fixed_price() && ( is_array( $changes ) ) && count( $changes ) < 1 ) {
			$current_currency = self::$settings->get_current_currency();
			$fixed_price      = self::get_fixed_price( $product, $current_currency, 'sale_price' );
			if ( $fixed_price !== false || self::$settings->get_params( 'ignore_exchange_rate' ) ) {
				return $this->set_cache( $fixed_price, $product_id, $price );
			}
		}

		return $this->set_cache( wmc_get_price( $price ), $product_id, $price );
	}

	/**
	 * @param $price
	 * @param $product WC_Product
	 *
	 * @return mixed
	 */
	public function woocommerce_product_get_regular_price( $price, $product ) {
		if ( ! $price ) {
			return $price;
		}
		$product_id = $product->get_id();
		if ( ! wp_doing_ajax() && isset( $this->price[ $product_id ][ $price ] ) ) {
			return $this->price[ $product_id ][ $price ];
		}
		$changes = $product->get_changes();

		if ( self::$settings->check_fixed_price() && ( is_array( $changes ) ) && count( $changes ) < 1 ) {
			$current_currency = self::$settings->get_current_currency();
			$fixed_price      = self::get_fixed_price( $product, $current_currency, 'regular_price' );
			if ( $fixed_price !== false || self::$settings->get_params( 'ignore_exchange_rate' ) ) {
				return $this->set_cache( $fixed_price, $product_id, $price );
			}
		}
		do_action( 'wmc_before_convert_regular_price', $product, $price );
		$price = $this->set_cache( wmc_get_price( $price ), $product_id, $price );
		do_action( 'wmc_after_convert_regular_price', $product, $price );

		return $price;
	}


	public function apply_beauty_price_to_shipping( $is_shipping_price ) {
		return ! boolval( self::$settings->get_params( 'beauty_price_shipping' ) );
	}

	public static function format_beauty_price( $price, $current_currency ) {
		$enable                = self::$settings->get_param( 'beauty_price_enable' );
		$input_price           = wc_prices_include_tax();
		$shop_display_incl_tax = 'incl' === get_option( 'woocommerce_tax_display_shop' ) ? true : false;
		$cart_display_incl_tax = 'incl' === get_option( 'woocommerce_tax_display_cart' ) ? true : false;

		$cond_1 = $input_price && $shop_display_incl_tax && $cart_display_incl_tax;
		$cond_2 = ! $input_price && ! $shop_display_incl_tax && ! $cart_display_incl_tax;

		if ( $enable && ( $cond_1 || $cond_2 || ! wc_tax_enabled() ) ) {
			/*Make price like the final price before applying beauty price rules*/
			$processed_price = WOOMULTI_CURRENCY_Data::convert_price_to_float( $price );
			$int             = $new_int = (int) $processed_price;
			$fraction        = $new_fraction = $processed_price - $int;
//			$int      = $new_int = (int) $price;
//			$fraction = $new_fraction = $price - $int;

			$lower_bound = self::$settings->get_param( 'price_lower_bound' );
			$from        = self::$settings->get_param( 'beauty_price_from' );
			$to          = self::$settings->get_param( 'beauty_price_to' );
			$value       = self::$settings->get_param( 'beauty_price_value' );
			$currencies  = self::$settings->get_param( 'beauty_price_currencies' );
			$int_frac    = self::$settings->get_param( 'beauty_price_part' );
			$round_up    = self::$settings->get_param( 'beauty_price_round_up' );

			$count_from       = is_array( $from ) ? count( $from ) : '';
			$count_to         = is_array( $to ) ? count( $to ) : '';
			$count_value      = is_array( $value ) ? count( $value ) : '';
			$count_currencies = is_array( $currencies ) ? count( $currencies ) : '';
			$count_int_frac   = is_array( $int_frac ) ? count( $int_frac ) : '';

			if ( $count_from && $count_to && $count_value && $count_currencies && $count_int_frac ) {
				$count = min( $count_from, $count_to, $count_value, $count_currencies, $count_int_frac );
				if ( ! is_array( $round_up ) || count( $round_up ) !== $count ) {
					$round_up = array_fill( 0, $count, 0 );
				}
				for ( $i = 0; $i < $count; $i ++ ) {
					if ( empty( $currencies[ $i ] ) || ! in_array( $current_currency, $currencies[ $i ] ) ) {
						continue;
					}
					if ( $int_frac[ $i ] === 'integer' ) {
						$offset = strlen( $value[ $i ] );
						if ( $offset == 0 ) {
							continue;
						}
						$price_length = strlen( $int );
						if ( $price_length >= $offset ) {
							$first_part_price  = substr( $int, 0, - $offset );
							$second_part_price = substr( $int, - $offset );

							if ( $lower_bound ) {
								if ( (int) $second_part_price >= (int) $from[ $i ] && (int) $second_part_price <= (int) $to[ $i ] ) {
									$new_int = $first_part_price . $value[ $i ];
									$new_int = (int) $new_int;
									if ( $round_up[ $i ] ) {
										$new_int += pow( 10, strlen( $from[ $i ] ) );
									}
									$price = $new_int + (float) $new_fraction;
									break;
								}
							} else {
								if ( (int) $second_part_price > (int) $from[ $i ] && (int) $second_part_price <= (int) $to[ $i ] ) {
									$new_int = $first_part_price . $value[ $i ];
									$new_int = (int) $new_int;
									if ( $round_up[ $i ] ) {
										$new_int += pow( 10, strlen( $from[ $i ] ) );
									}
									$price = $new_int + (float) $new_fraction;
									break;
								}
							}
						}
					}
				}
				for ( $i = 0; $i < $count; $i ++ ) {
					if ( empty( $currencies[ $i ] ) || ! in_array( $current_currency, $currencies[ $i ] ) ) {
						continue;
					}
					if ( $int_frac[ $i ] === 'fraction' ) {
						$f_from  = (float) $from[ $i ];
						$f_to    = (float) $to[ $i ];
						$f_value = $value[ $i ];
						$offset  = 0;

						if ( $f_from > 1 || $f_to > 1 ) {
							continue;
						}

						switch ( true ) {
							case $f_value == '':
								$offset = 0;
								break;
							case $f_value >= 1:
								$offset = strlen( $value[ $i ] );
								break;
							case $f_value < 1:
								$string = str_replace( ',', '.', $f_value );
								$string = substr( $string, strpos( $string, '.' ) + 1 );
								$offset = strlen( $string );
								break;
						}
						if ( $offset > intval( self::$decimal_data ) ) {
							self::$decimal_data = $offset;
						}

						$f_value = (float) $f_value;

						if ( $lower_bound ) {
							if ( (float) $fraction >= $f_from && (float) $fraction < $f_to ) {
								$new_fraction = $f_value;
								$price        = $new_int + (float) $new_fraction;
								if ( $round_up[ $i ] ) {
									$price += 1;
								}
								break;
							}
						} else {
							if ( (float) $fraction > $f_from && (float) $fraction < $f_to ) {
								$new_fraction = $f_value;
								$price        = $new_int + (float) $new_fraction;
								if ( $round_up[ $i ] ) {
									$price += 1;
								}
								break;
							}
						}
					}
				}
			}
		}

		return (float) $price;
	}

	public function override_decimal( $decimal ) {

		if ( self::$decimal_data !== '' ) {
			return self::$decimal_data;
		}

		return $decimal;
	}

	/**Fix error 500 with Subscriptions for WooCommerce plugin from WebToffee if using $product->is_on_sale('edit') for variable_subscription product
	 *
	 * @param $product WC_Product
	 *
	 * @return bool
	 */
	public static function is_on_sale( $product ) {
		$context = 'edit';
		if ( '' !== (string) $product->get_sale_price( $context ) && $product->get_regular_price( $context ) > $product->get_sale_price( $context ) ) {
			$on_sale = true;

			if ( $product->get_date_on_sale_from( $context ) && $product->get_date_on_sale_from( $context )->getTimestamp() > time() ) {
				$on_sale = false;
			}

			if ( $product->get_date_on_sale_to( $context ) && $product->get_date_on_sale_to( $context )->getTimestamp() < time() ) {
				$on_sale = false;
			}
		} else {
			$on_sale = false;
		}

		return $on_sale;
	}

	/**
	 * @param $product WC_Product
	 * @param $current_currency
	 * @param string $type
	 *
	 * @return bool|float
	 */
	public static function get_fixed_price( $product, $current_currency, $type = '' ) {
		$price = self::get_product_fixed_price( $product, $current_currency, $type );

		if ( $price === false ) {
			if ( class_exists( 'SitePress' ) ) {
				if ( self::$language === null ) {
					self::$language   = '';
					$default_lang     = apply_filters( 'wpml_default_language', null );
					$current_language = apply_filters( 'wpml_current_language', null );
					if ( $current_language && $current_language !== $default_lang ) {
						self::$language = $current_language;
					}
				}
			} else {
				self::$language = '';
			}
			if ( self::$language ) {
				global $sitepress;
				$product_id     = $product->get_id();
				$wpml_object_id = apply_filters(
					'wpml_object_id', $product_id, 'product', false, $sitepress->get_default_language()
				);
				if ( $wpml_object_id != $product_id ) {
					$wpml_product = wc_get_product( $wpml_object_id );
					if ( $wpml_product ) {
						$price = self::get_product_fixed_price( $wpml_product, $current_currency, $type );
					}
				}
			}
		}

		return $price;
	}

	/**
	 * @param $product WC_Product
	 * @param $current_currency
	 * @param $type
	 *
	 * @return bool|float
	 */
	private static function get_product_fixed_price( $product, $current_currency, $type ) {
		$price = false;

		if ( self::$settings->get_current_currency() !== self::$settings->get_default_currency() ) {
			$product_id = $product->get_id();


			switch ( $type ) {
				case 'regular_price':
					$product_price = wmc_adjust_fixed_price( json_decode( get_post_meta( $product_id, '_regular_price_wmcp', true ), true ) );
					if ( isset( $product_price[ $current_currency ] ) && $product_price[ $current_currency ] != '' ) {
						$price = $product_price[ $current_currency ];
					}
					break;

				case 'sale_price':
					$sale_price = wmc_adjust_fixed_price( json_decode( get_post_meta( $product_id, '_sale_price_wmcp', true ), true ) );
					if ( isset( $sale_price[ $current_currency ] ) && $sale_price[ $current_currency ] != '' ) {
						$price = $sale_price[ $current_currency ];
					}
					break;

				default:
					$product_price = wmc_adjust_fixed_price( json_decode( get_post_meta( $product_id, '_regular_price_wmcp', true ), true ) );

					if ( isset( $product_price[ $current_currency ] ) && ! $product->is_on_sale( 'edit' ) && $product_price[ $current_currency ] != '' ) {
						$price = $product_price[ $current_currency ];
					} else {
						$sale_price = wmc_adjust_fixed_price( json_decode( get_post_meta( $product_id, '_sale_price_wmcp', true ), true ) );
						if ( isset( $sale_price[ $current_currency ] ) && $sale_price[ $current_currency ] != '' ) {
							$price = $sale_price[ $current_currency ];
						}
					}

//					$price         = isset( $product_price[ $current_currency ] ) && $product_price[ $current_currency ] > 0 ? $product_price[ $current_currency ] : $price;
//					if ( $product->is_on_sale( 'edit' ) ) {
//						$sale_price = wmc_adjust_fixed_price( json_decode( get_post_meta( $product_id, '_sale_price_wmcp', true ), true ) );
//						if ( isset( $sale_price[ $current_currency ] ) && $sale_price[ $current_currency ] != '' ) {
//							$price = $sale_price[ $current_currency ];
//						}
//					}
			}

		}

		return $price;
	}
}