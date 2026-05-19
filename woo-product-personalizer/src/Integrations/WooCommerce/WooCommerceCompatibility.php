<?php
/**
 * WooCommerce feature compatibility and admin notice adjustments.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Integrations\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Declares compatibility with WooCommerce features and suppresses selected WC admin notices.
 */
class WooCommerceCompatibility {

	/**
	 * Declare compatibility with enabled WooCommerce features (must run on before_woocommerce_init).
	 *
	 * @return void
	 */
	public static function declare_features() {
		if ( ! class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			return;
		}

		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WPP_PLUGIN_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', WPP_PLUGIN_FILE, true );
	}

	/**
	 * Register hooks after WooCommerce is loaded.
	 *
	 * @return void
	 */
	public static function register() {
		add_filter( 'woocommerce_show_admin_notice', array( __CLASS__, 'filter_admin_notices' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'dismiss_secure_connection_notice' ), 1 );
	}

	/**
	 * Hide the HTTPS recommendation notice (common on local/dev without SSL).
	 *
	 * @param bool   $show        Whether to show the notice.
	 * @param string $notice_name Notice identifier.
	 * @return bool
	 */
	public static function filter_admin_notices( $show, $notice_name ) {
		if ( 'no_secure_connection' === $notice_name ) {
			return false;
		}

		return $show;
	}

	/**
	 * Remove persisted secure-connection notice from WooCommerce notice queue.
	 *
	 * @return void
	 */
	public static function dismiss_secure_connection_notice() {
		if ( ! class_exists( 'WC_Admin_Notices' ) || ( function_exists( 'is_ssl' ) && is_ssl() ) ) {
			return;
		}

		\WC_Admin_Notices::remove_notice( 'no_secure_connection', true );
	}
}
