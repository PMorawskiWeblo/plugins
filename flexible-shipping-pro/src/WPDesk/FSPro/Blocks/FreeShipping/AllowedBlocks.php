<?php

namespace WPDesk\FSPro\Blocks\FreeShipping;

use FSProVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WPDesk\FSPro\TableRate\FreeShipping\FreeShippingDisplayOnOptions;

class AllowedBlocks implements Hookable {

	public function hooks() {
		add_filter( 'flexible-shipping/free-shipping-block/allowed-blocks', function ( $allowed_blocks, $free_shipping_notice_data ) {
			if ( $free_shipping_notice_data instanceof \WPDesk\FS\TableRate\FreeShipping\FreeShippingNoticeData ) {
				$display_on_pages = $free_shipping_notice_data->get_meta_data()['method_settings']['method_free_shipping_display_on'] ?? ['cart', 'checkout'];
				$allowed_blocks = [];
				if ( in_array( FreeShippingDisplayOnOptions::CHECKOUT, $display_on_pages, true ) || in_array( FreeShippingDisplayOnOptions::ALL, $display_on_pages, true ) ) {
					$allowed_blocks[] = 'checkout';
				}
				if ( in_array( FreeShippingDisplayOnOptions::CART, $display_on_pages, true ) || in_array( FreeShippingDisplayOnOptions::ALL, $display_on_pages, true ) ) {
					$allowed_blocks[] = 'cart';
				}
			}

			return $allowed_blocks;
		}, 10, 2 );
	}

}
