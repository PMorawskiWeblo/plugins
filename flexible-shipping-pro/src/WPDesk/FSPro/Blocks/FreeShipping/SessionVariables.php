<?php

namespace WPDesk\FSPro\Blocks\FreeShipping;

use FSProVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WPDesk\FSPro\TableRate\FreeShipping\FreeShippingQuantityNoticeGenerator;

class SessionVariables implements Hookable {

	public function hooks() {
		add_filter( 'flexible-shipping/free-shipping-block/session-variables', function( $session_variables ) {
			if ( ! is_array( $session_variables ) ) {
				$session_variables = [ $session_variables ];
			}
			$session_variables[] = FreeShippingQuantityNoticeGenerator::FS_FREE_SHIPPING_NOTICE_NAME;
			return $session_variables;
		} );
	}

}
