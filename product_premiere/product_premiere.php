<?php

/**
 * Plugin Name: Product Premiere for WooCommerce
 * Description: Adds product premiere functionality to WooCommerce products.
 * Version: 1.0.0
 * Author: Weblo
 * Text Domain: product-premiere
 */

if (! defined('ABSPATH')) {
    exit;
}

// Autoloader for plugin classes.
spl_autoload_register(function ($class) {
    if (strpos($class, 'PPWC_') !== 0) {
        return;
    }
    $file = plugin_dir_path(__FILE__) . 'includes/' . strtolower(str_replace('PPWC_', '', $class)) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

if (! class_exists('PPWC_Product_Premiere')) {
    class PPWC_Product_Premiere
    {

        private static $instance = null;

        public static function get_instance()
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct()
        {
            $this->define_constants();
            $this->init_classes();
        }

        private function define_constants()
        {
            define('PPWC_PLUGIN_PATH', plugin_dir_path(__FILE__));
            define('PPWC_PLUGIN_URL', plugin_dir_url(__FILE__));
            define('PPWC_PLUGIN_VERSION', '1.0.0');
        }

        private function init_classes()
        {
            // Initialize Public class for both admin and front-end
            if (class_exists('PPWC_Public')) {
                PPWC_Public::get_instance();
            }

            if (is_admin()) {
                if (class_exists('PPWC_Admin')) {
                    PPWC_Admin::get_instance();
                }
                if (class_exists('PPWC_Settings')) {
                    PPWC_Settings::get_instance();
                }
            }
        }
    }
}

add_action('plugins_loaded', array('PPWC_Product_Premiere', 'get_instance'));

// Dodaj hook dla crona
add_action('ppwc_cleanup_expired_premieres', function () {
    $public = PPWC_Public::get_instance();
    $public->cleanup_all_expired_premieres();
});

// Zaplanuj zadanie crona jeśli nie jest zaplanowane
register_activation_hook(__FILE__, function () {
    if (!wp_next_scheduled('ppwc_cleanup_expired_premieres')) {
        wp_schedule_event(time(), 'daily', 'ppwc_cleanup_expired_premieres');
    }
});

// Usuń zadanie crona przy deaktywacji
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('ppwc_cleanup_expired_premieres');
});