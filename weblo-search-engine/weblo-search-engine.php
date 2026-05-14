<?php

/**
 * Plugin Name: Weblo Search Engine
 * Plugin URI: https://weblo.pl
 * Description: Advanced product search engine for WooCommerce with AJAX search, category hierarchy, and customizable interface.
 * Version: 1.0.0
 * Author: Weblo
 * Author URI: https://weblo.pl
 * Text Domain: weblo-search-engine
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 *
 * @package Weblo_Search_Engine
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

/**
 * Current plugin version.
 */
define('WEBLO_SEARCH_ENGINE_VERSION', '1.0.0');

/**
 * Plugin directory path.
 */
define('WEBLO_SEARCH_ENGINE_PATH', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL.
 */
define('WEBLO_SEARCH_ENGINE_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_weblo_search_engine()
{
	require_once WEBLO_SEARCH_ENGINE_PATH . 'includes/class-weblo-search-engine-activator.php';
	Weblo_Search_Engine_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_weblo_search_engine()
{
	require_once WEBLO_SEARCH_ENGINE_PATH . 'includes/class-weblo-search-engine-deactivator.php';
	Weblo_Search_Engine_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_weblo_search_engine');
register_deactivation_hook(__FILE__, 'deactivate_weblo_search_engine');

/**
 * Check if WooCommerce is active.
 */
function weblo_search_engine_check_woocommerce()
{
	// Check if WooCommerce plugin is active.
	if (! in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins', array())), true)) {
		// Also check for multisite.
		if (is_multisite()) {
			$active_plugins = get_site_option('active_sitewide_plugins', array());
			if (! isset($active_plugins['woocommerce/woocommerce.php'])) {
				return false;
			}
		} else {
			return false;
		}
	}

	// Double check if WooCommerce class exists (it should be loaded by now).
	if (! class_exists('WooCommerce')) {
		return false;
	}

	return true;
}

/**
 * Display admin notice if WooCommerce is not active.
 */
function weblo_search_engine_woocommerce_notice()
{
?>
<div class="notice notice-error">
    <p><?php esc_html_e('Weblo Search Engine requires WooCommerce to be installed and active.', 'weblo-search-engine'); ?>
    </p>
</div>
<?php
}

/**
 * Begins execution of the plugin.
 */
function run_weblo_search_engine()
{
	// Check if WooCommerce is active.
	if (! weblo_search_engine_check_woocommerce()) {
		add_action('admin_notices', 'weblo_search_engine_woocommerce_notice');
		return;
	}

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require_once WEBLO_SEARCH_ENGINE_PATH . 'includes/class-weblo-search-engine.php';

	/**
	 * Begins execution of the plugin.
	 */
	$plugin = new Weblo_Search_Engine();
	$plugin->run();
}

// Initialize plugin after all plugins are loaded.
add_action('plugins_loaded', 'run_weblo_search_engine', 10);