<?php
/**
 * Plugin Name:       Quick Returns
 * Plugin URI:        https://weblo.pl/
 * Description:       Lightweight WooCommerce return request wizard with HPOS support.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Weblo
 * Author URI:        https://weblo.pl/
 * Text Domain:       quick-returns
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   9.0
 *
 * @package Weblo\QuickReturns
 */

defined( 'ABSPATH' ) || exit;

define( 'QUICK_RETURNS_VERSION', '1.0.0' );
define( 'QUICK_RETURNS_FILE', __FILE__ );
define( 'QUICK_RETURNS_PATH', plugin_dir_path( __FILE__ ) );
define( 'QUICK_RETURNS_URL', plugin_dir_url( __FILE__ ) );

$autoloader = QUICK_RETURNS_PATH . 'vendor/autoload.php';
if ( file_exists( $autoloader ) ) {
	require $autoloader;
} else {
	spl_autoload_register(
		static function ( string $class ): void {
			$prefix = 'Weblo\\QuickReturns\\';
			if ( 0 !== strpos( $class, $prefix ) ) {
				return;
			}
			$relative = substr( $class, strlen( $prefix ) );
			$file     = QUICK_RETURNS_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
			if ( file_exists( $file ) ) {
				require $file;
			}
		}
	);
}

add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				QUICK_RETURNS_FILE,
				true
			);
		}
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function (): void {
					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html__( 'Quick Returns requires WooCommerce to be active.', 'quick-returns' )
					);
				}
			);
			return;
		}

		\Weblo\QuickReturns\Core\Plugin::instance()->boot();
	}
);

register_activation_hook(
	__FILE__,
	static function (): void {
		\Weblo\QuickReturns\Infrastructure\PostType\ReturnRequestPostType::register();
		flush_rewrite_rules();
		\Weblo\QuickReturns\Infrastructure\Repository\SettingsRepository::set_defaults();
	}
);

register_deactivation_hook(
	__FILE__,
	static function (): void {
		flush_rewrite_rules();
	}
);
