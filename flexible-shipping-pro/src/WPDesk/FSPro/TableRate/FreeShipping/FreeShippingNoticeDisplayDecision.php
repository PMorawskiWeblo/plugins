<?php

namespace WPDesk\FSPro\TableRate\FreeShipping;

use FSProVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WPDesk\FS\TableRate\FreeShipping\FreeShippingNoticeData;

/**
 * Can make decision if notice should be displayed.
 */
class FreeShippingNoticeDisplayDecision implements Hookable {

	/**
	 * @return void
	 */
	public function hooks() {
		add_filter( 'flexible-shipping/free-shipping/show-notice', [ $this, 'should_display_notice' ], 10, 2 );
	}

	/**
	 * @param bool                   $should_display
	 * @param FreeShippingNoticeData $free_shipping_notice_data
	 *
	 * @return bool
	 */
	public function should_display_notice( $should_display, $free_shipping_notice_data ) {
		if ( ! $free_shipping_notice_data instanceof FreeShippingNoticeData ) {
			return is_bool( $should_display ) ? $should_display : false;
		}

		$display_on_pages = $this->get_display_on_page_settings_from_meta( $free_shipping_notice_data );

		return (
			( in_array( FreeShippingDisplayOnOptions::ALL, $display_on_pages, true ) && ! is_checkout() )
			|| ( in_array( FreeShippingDisplayOnOptions::SHOP, $display_on_pages, true ) && is_shop() )
			|| ( in_array( FreeShippingDisplayOnOptions::CART, $display_on_pages, true ) && ( is_cart() ) )
			|| ( in_array( FreeShippingDisplayOnOptions::CHECKOUT, $display_on_pages, true ) && ( is_checkout() ) )
			|| ( in_array( FreeShippingDisplayOnOptions::PRODUCT, $display_on_pages, true ) && is_product() )
		);
	}

	private function get_display_on_page_settings_from_meta( FreeShippingNoticeData $free_shipping_notice_data ): array {
		$meta_data                = $free_shipping_notice_data->get_meta_data();
		$shipping_method_settings = $meta_data['method_settings'] ?? [];
		$display_on_pages         = $shipping_method_settings['method_free_shipping_display_on'] ?? FreeShippingDisplayOnOptions::DEFAULT;

		if ( ! is_array( $display_on_pages ) ) {
			return [];
		}

		return $display_on_pages;
	}

}
