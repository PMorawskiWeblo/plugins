<?php

/**
 * Plugin Name: Weblo Pick-up Point
 * Plugin URI: https://weblo.pl
 * Description: Adds a "Pick-up point" shipping method with configurable pickup locations and prices.
 * Version: 1.0.0
 * Author: Weblo
 * Text Domain: weblo-pickup-point
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('WEBLO_PICKUP_POINT_VERSION', '1.0.0');
define('WEBLO_PICKUP_POINT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WEBLO_PICKUP_POINT_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Check if WooCommerce is active
 */
function weblo_pickup_point_check_woocommerce()
{
    if (! class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
?>
<div class="error">
    <p><?php esc_html_e('Weblo Pick-up Point requires WooCommerce to be installed and active.', 'weblo-pickup-point'); ?>
    </p>
</div>
<?php
        });
        return false;
    }
    return true;
}

/**
 * Initialize the plugin
 */
function weblo_pickup_point_init()
{
    if (! weblo_pickup_point_check_woocommerce()) {
        return;
    }

    // Load plugin classes
    require_once WEBLO_PICKUP_POINT_PLUGIN_DIR . 'includes/class-weblo-pickup-point-method.php';
    require_once WEBLO_PICKUP_POINT_PLUGIN_DIR . 'includes/class-weblo-pickup-point-frontend.php';

    // Initialize frontend handler
    new Weblo_Pickup_Point_Frontend();
}
add_action('plugins_loaded', 'weblo_pickup_point_init');

/**
 * Register the shipping method
 */
function weblo_pickup_point_register_shipping_method($methods)
{
    $methods['weblo_pickup_point'] = 'Weblo_Pickup_Point_Shipping_Method';
    return $methods;
}
add_filter('woocommerce_shipping_methods', 'weblo_pickup_point_register_shipping_method');