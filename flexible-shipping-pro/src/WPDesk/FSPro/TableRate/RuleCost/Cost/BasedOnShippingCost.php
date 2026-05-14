<?php
/**
 * Based on Shipping Cost additional cost.
 *
 * @package WPDesk\FSPro\TableRate\RuleCost\Cost
 */

namespace WPDesk\FSPro\TableRate\RuleCost\Cost;

use WPDesk\FS\TableRate\Rule\Cost\AbstractAdditionalCost;
use WPDesk\FS\TableRate\Rule\ShippingContents\ShippingContents;

/**
 * Based on Shipping Cost additional cost.
 */
class BasedOnShippingCost extends AbstractAdditionalCost {

	public function __construct() {
		$this->based_on = 'shipping_cost';
		// Translators: currency.
		$this->name = sprintf( __( 'Shipping cost (%1$s)', 'flexible-shipping-pro' ), get_woocommerce_currency_symbol() );
	}

	/**
	 * Returns value from shipment contents to calculate cost.
	 *
	 * @param ShippingContents $shipping_contents .
	 *
	 * @return float
	 */
	protected function get_value_from_shipment_contents( $shipping_contents ): float {
		return $shipping_contents->get_calculated_shipping_cost();
	}
}
