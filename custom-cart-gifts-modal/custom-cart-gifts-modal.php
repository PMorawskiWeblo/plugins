<?php


/**
 * Plugin Name: Custom Cart + Crosssell Page
 * Plugin URI: 
 * Description: Wtyczka wyświetlająca modal z prezentami w koszyku + strona z crosssellami
 * Version: 2.2
 * Author: weblo.pl
 * Author URI: https://weblo.pl
 * License: GPL2
 * Text Domain: custom-cart-gifts-modal
 */

// Zabezpieczenie przed bezpośrednim dostępem do pliku
if (!defined('ABSPATH')) {
    exit;
}

class CustomCartGiftsModal
{

    public function __construct()
    {
        if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            add_action('admin_notices', function () {
                echo '<div class="error"><p>' .
                    __('Custom Cart Gifts Modal wymaga zainstalowanego i aktywnego WooCommerce.', 'custom-cart-gifts-modal') .
                    '</p></div>';
            });
            return;
        } else {
            add_action('init', array($this, 'init'));
        }

        remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display', 0);

    }

    public function init()
    {
        if (is_admin()) {
            require_once plugin_dir_path(__FILE__) . 'admin/class-admin.php';
            new CartAdmin();
        }

        if (get_option('cgm_enable_cart') === 'on') {
            require_once plugin_dir_path(__FILE__) . 'includes/class-cart-crosssells.php';
            new CartCrossSells();


            require_once plugin_dir_path(__FILE__) . 'includes/class-custom-cart.php';
            new CustomCart();

            require_once plugin_dir_path(__FILE__) . 'includes/class-crossell-page.php';
            new CrossSellPage();

            // Wyłącz AJAX add to cart na stronach archiwum produktów
//             update_option('woocommerce_enable_ajax_add_to_cart', 'no');
        }else{
//             update_option('woocommerce_enable_ajax_add_to_cart', 'yes');
        }

    }

}


if (class_exists('CustomCartGiftsModal')) {
    $custom_cart_gifts_modal = new CustomCartGiftsModal();
}

if (get_option('cgm_enable_cart') === 'on') {
    add_filter('woocommerce_locate_template', 'override_woocommerce_templates', 10, 3);
}

function override_woocommerce_templates($template, $template_name, $template_path)
{

    $plugin_path = plugin_dir_path(__FILE__) . 'templates/woocommerce/';

    if (file_exists($plugin_path . $template_name)) {
        return $plugin_path . $template_name;
    }

    return $template;
}

