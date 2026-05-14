<?php
/**
 * Class RulesTableSettings
 *
 * @package WPDesk\FSPro\TableRate
 */

namespace WPDesk\FSPro\TableRate;

use FSProVendor\WPDesk\PluginBuilder\Plugin\Hookable;

/**
 * Can change rules table settings.
 */
class RulesTableSettings implements Hookable {

	private $is_plugins_license_activated;

	public function __construct( bool $is_plugins_license_activated ) {
		$this->is_plugins_license_activated = $is_plugins_license_activated;
	}

	/**
	 * Hooks.
	 */
	public function hooks() {
		add_filter( 'flexible_shipping_rules_table_settings', [ $this, 'enable_multiple_conditions' ] );
		add_filter( 'flexible-shipping/rules-table/settings', [ $this, 'enable_local_ai_button' ] );
	}

	/**
	 * @param array $rules_table_settings .
	 *
	 * @return array
	 */
	public function enable_multiple_conditions( array $rules_table_settings ): array {
		$rules_table_settings['multiple_conditions_available']       = true;
		$rules_table_settings['multiple_additional_costs_available'] = true;
		$rules_table_settings['special_actions_available']           = true;

		return $rules_table_settings;
	}

	/**
	 * @param array $rules_table_settings .
	 *
	 * @return array
	 */
	public function enable_local_ai_button( array $rules_table_settings ): array {
		$rules_table_settings['ai_local_button_available'] = true;
		if ( ! $this->is_plugins_license_activated ) {
			$rules_table_settings['ai_local_message_title']      = __( 'Activate license to use AI Assistant', 'flexible-shipping-pro' );
			$rules_table_settings['ai_local_message']            = sprintf(
				__( 'AI Assistant is available for Flexible Shipping PRO with active subscription. Check the perks:
%1$s
%3$sOne-click shipping configuration import from AI Assistant%4$s
%3$sPremium 1-on-1 support%4$s
%3$sReady-made configurations%4$s
%3$sRegular updates, bug fixes and all upcoming new features%4$s
%3$s30-day money back guarantee%4$s
%2$s
Without active subscription, use %5$sexternal AI chat →%6$s.', 'flexible-shipping-pro' ),
				'<ul class="star-list">',
				'</ul>',
				'<li>',
				'</li>',
				'<a target="_blank" href="https://octol.io/fs-pro-ai-assistant">',
				'</a>'
			);
			$rules_table_settings['ai_local_message_action']     = __( 'Activate Flexible Shipping PRO Subscription', 'flexible-shipping-pro' );
			$rules_table_settings['ai_local_message_action_url'] = admin_url( 'plugins.php#flexible-shipping-pro-activation-form' );
		} else {
			$rules_table_settings['ai_local_message'] = '';
		}
		return $rules_table_settings;
	}
}

