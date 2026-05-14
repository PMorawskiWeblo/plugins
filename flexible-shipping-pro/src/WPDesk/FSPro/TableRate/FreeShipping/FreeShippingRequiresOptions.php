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
class FreeShippingRequiresOptions extends AbstractOptions {

	const ORDER_AMOUNT            = 'order_amount';
	const ITEM_QUANTITY           = 'item_quantity';
	const COUPON                  = 'coupon';
	const ORDER_AMOUNT_OR_COUPON  = 'order_amount_or_coupon';
	const ORDER_AMOUNT_AND_COUPON = 'order_amount_and_coupon';

	/**
	 * @return array
	 */
	public function get_options(): array {
		return [
			self::ORDER_AMOUNT            => __( 'Minimum order value', 'flexible-shipping-pro' ),
			self::ITEM_QUANTITY           => __( 'Minimum item quantity', 'flexible-shipping-pro' ),
			self::COUPON                  => __( 'Free shipping coupon', 'flexible-shipping-pro' ),
			self::ORDER_AMOUNT_OR_COUPON  => __( 'Free shipping coupon or minimum order amount', 'flexible-shipping-pro' ),
			self::ORDER_AMOUNT_AND_COUPON => __( 'Free shipping coupon and minimum order amount', 'flexible-shipping-pro' ),
		];
	}

}
