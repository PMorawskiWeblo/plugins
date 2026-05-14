<?php

namespace WPDesk\FSPro\TableRate\FreeShipping;

use WPDesk\FS\TableRate\FreeShipping\FreeShippingNoticeData;
use WPDesk\FS\TableRate\FreeShipping\FreeShippingNoticeGenerator;

/**
 * Can generate free shipping notice based on cart items quantity.
 */
class FreeShippingQuantityNoticeGenerator extends FreeShippingNoticeGenerator {

	const FS_FREE_SHIPPING_NOTICE_NAME = 'fs_free_shipping_notice_quantity';

	/**
	 * @param array $fs_method .
	 *
	 * @return bool
	 */
	protected function has_shipping_method_free_shipping_notice_enabled( array $fs_method ): bool {
		return ! empty( $fs_method[ self::SETTING_METHOD_FREE_SHIPPING ] )
				&& 'yes' === ( $fs_method[ \WPDesk_Flexible_Shipping::SETTING_METHOD_FREE_SHIPPING_NOTICE ] ?? 'no' )
				&& FreeShippingRequiresOptions::ITEM_QUANTITY === ( $fs_method['method_free_shipping_requires'] ?? '' );
	}

	/**
	 * Returns current cart value.
	 *
	 * @return float
	 */
	protected function get_cart_value(): float {
		$this->cart = $this->get_cart();
		return array_sum( $this->cart->get_cart_item_quantities() );
	}

	protected function get_cart() {
		return WC()->cart;
	}

	/**
	 * @param bool   $show_progress_bar
	 * @param float  $percentage
	 * @param float  $lowest_free_shipping_limit
	 * @param float  $amount
	 * @param string $free_shipping_notice_text
	 * @param string $button_url
	 * @param string $button_label
	 * @param array  $meta_data
	 *
	 * @return FreeShippingNoticeData
	 */
	protected function prepare_free_shipping_notice_data(
		bool $show_progress_bar,
		float $percentage,
		float $lowest_free_shipping_limit,
		float $amount,
		string $free_shipping_notice_text,
		string $button_url,
		string $button_label,
		array $meta_data
	): FreeShippingNoticeData {
		return new FreeShippingNoticeData(
			$show_progress_bar,
			$percentage,
			$lowest_free_shipping_limit,
			$lowest_free_shipping_limit,
			0,
			$amount,
			$this->get_notice_text_message( $amount, $free_shipping_notice_text ),
			$button_url,
			$button_label,
			$meta_data
		);
	}

}
