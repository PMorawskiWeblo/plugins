<?php

namespace WPDesk\FSPro\ShippingMethod;

use FSProVendor\WPDesk\PluginBuilder\Plugin\Hookable;
use WPDesk\FS\TableRate\ShippingMethodSingle;

class MethodTimestamps implements Hookable {

	public const CREATION_TIME_WITH_PRO            = 'creation_time_with_pro';
	public const METHOD_RULES_UPDATE_TIME_WITH_PRO = 'method_rules_update_time_with_pro';

	public function hooks(): void {
		add_action( 'woocommerce_shipping_zone_method_added', [ $this, 'save_creation_time_on_shipping_method' ], 10, 3 );
		add_filter( 'woocommerce_shipping_flexible_shipping_single_instance_settings_values', [ $this, 'save_rules_table_update_time_on_shipping_method' ], 10, 2 );
	}

	/**
	 * @param int $instance_id
	 * @param string $type
	 * @param int $zone_id
	 *
	 * @return void
	 */
	public function save_creation_time_on_shipping_method( $instance_id, $type, $zone_id ): void {
		if ( $type !== 'flexible_shipping_single' ) {
			return;
		}
		$shipping_method = new ShippingMethodSingle( $instance_id );
		$shipping_method->update_instance_option( self::CREATION_TIME_WITH_PRO, current_time( 'timestamp' ) ); // phpcs:ignore
	}

	/**
	 * @param array $settings
	 * @param \WC_Shipping_Method $shipping_method
	 *
	 * @return array
	 */
	public function save_rules_table_update_time_on_shipping_method( $settings, $shipping_method ) {
		$current_settings = get_option( $shipping_method->get_instance_option_key(), [] );
		if ( is_array( $settings ) && is_array( $current_settings ) && ( $settings['method_rules'] ?? '' ) !== ( $current_settings['method_rules'] ?? '' ) ) {
			$settings[ self::METHOD_RULES_UPDATE_TIME_WITH_PRO ] = current_time( 'timestamp' ); // phpcs:ignore
		}

		return $settings;
	}
}
