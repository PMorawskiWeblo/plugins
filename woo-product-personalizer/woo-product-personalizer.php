<?php

/**
 * Plugin Name:       Woo Product Personalizer
 * Plugin URI:        https://weblo.pl/
 * Description:       Personalize WooCommerce products with image and text layouts, live preview, and order production files.
 * Version:           3.11
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Weblo
 * Text Domain:       woo-product-personalizer
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   9.0
 *
 * @package WooProductPersonalizer
 */

defined('ABSPATH') || exit;

define('WPP_VERSION', '3.11');
define('WPP_PLUGIN_FILE', __FILE__);
define('WPP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WPP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPP_TEXT_DOMAIN', 'woo-product-personalizer');
define('WPP_UPLOADS_SUBDIR', 'wc-product-personalizer');

require_once WPP_PLUGIN_PATH . 'src/Autoloader.php';

\WooProductPersonalizer\Autoloader::register();

add_action(
	'before_woocommerce_init',
	static function () {
		\WooProductPersonalizer\Integrations\WooCommerce\WooCommerceCompatibility::declare_features();
	}
);

register_activation_hook(__FILE__, array('\WooProductPersonalizer\Core\Installer', 'activate'));
register_deactivation_hook(__FILE__, array('\WooProductPersonalizer\Core\Deactivator', 'deactivate'));

add_action(
	'plugins_loaded',
	static function () {
		$plugin = new \WooProductPersonalizer\Plugin();
		$plugin->boot();
	},
	20
);