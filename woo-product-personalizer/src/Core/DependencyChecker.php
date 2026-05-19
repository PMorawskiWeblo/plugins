<?php
/**
 * WooCommerce dependency checker.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class DependencyChecker
 */
class DependencyChecker {

	/**
	 * Whether WooCommerce is active.
	 *
	 * @return bool
	 */
	public function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Register admin notice when WooCommerce is missing.
	 *
	 * @return void
	 */
	public function register_notice() {
		if ( $this->is_woocommerce_active() ) {
			return;
		}

		add_action(
			'admin_notices',
			static function () {
				if ( ! current_user_can( 'activate_plugins' ) ) {
					return;
				}
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html__(
						'Woo Product Personalizer requires WooCommerce to be installed and active.',
						'woo-product-personalizer'
					)
				);
			}
		);
	}
}
