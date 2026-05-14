<?php
/**
 * Class FreeShippingRequiresOptions
 *
 * @package WPDesk\FSPro\TableRate
 */

namespace WPDesk\FSPro\TableRate\FreeShipping;

use FSProVendor\WPDesk\FS\TableRate\AbstractOptions;

/**
 * Can provide free shipping requires options.
 */
class FreeShippingDisplayOnOptions extends AbstractOptions {
	const CART     = 'cart';
	const CHECKOUT = 'checkout';
	const PRODUCT = 'product';
	const SHOP     = 'shop';
	const ALL      = 'all';

	const DEFAULT  = [
		self::CART,
		self::CHECKOUT,
	];

	/**
	 * @return array
	 */
	public function get_options(): array {
		return [
			// Translators: page id.
			self::CART     => sprintf( __( 'Cart (ID: %1$d)', 'flexible-shipping-pro' ), wc_get_page_id( 'cart' ) ),
			// Translators: page id.
			self::CHECKOUT => sprintf( __( 'Checkout (ID: %1$d)', 'flexible-shipping-pro' ), wc_get_page_id( 'checkout' ) ),
			self::PRODUCT  => __( 'All product pages', 'flexible-shipping-pro' ),
			self::SHOP     => __( 'All shop pages', 'flexible-shipping-pro' ),
			self::ALL      => __( 'All pages', 'flexible-shipping-pro' ),
		];
	}

}
