<?php

namespace WPDesk\FSPro\TableRate\FreeShipping;

use FSProVendor\WPDesk\Notice\Notice;

/**
 * Can display compatibility notice.
 */
class FreeShippingCompatibilityNotice implements \FSProVendor\WPDesk\PluginBuilder\Plugin\Hookable {

	public function hooks(): void {
		add_action( 'admin_notices', [ $this, 'display_compatibility_notice_when_needed' ] );
	}

	public function display_compatibility_notice_when_needed(): void {
		if ( defined( 'FLEXIBLE_SHIPPING_VERSION' ) && version_compare( FLEXIBLE_SHIPPING_VERSION, '4.20.0', '<' ) ) {
			new Notice(
				sprintf(
					// Translators: strong and new line.
					__( '%1$sFlexible Shipping plugin update needed!%2$s%3$sFree shipping notice for rules based on quantity won’t be displayed for outdated version.', 'flexible-shipping-pro' ),
					'<strong>',
					'</strong>',
					'<br/><br/>'
				),
				Notice::NOTICE_TYPE_WARNING
			);
		}
	}

}
