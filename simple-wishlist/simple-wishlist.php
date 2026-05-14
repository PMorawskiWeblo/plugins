<?php
/*
Plugin Name: Simple Wishlist
Description: Prosta wtyczka do obsługi listy życzeń
Version: 1.0
Author: Weblo
*/

if (!defined('ABSPATH')) {
    exit;
}

// Definicje stałych
define('WISHLIST_PATH', plugin_dir_path(__FILE__));
define('WISHLIST_URL', plugin_dir_url(__FILE__));

// Includowanie plików
require_once WISHLIST_PATH . 'includes/class-wishlist.php';
require_once WISHLIST_PATH . 'includes/class-wishlist-shortcodes.php';
require_once WISHLIST_PATH . 'includes/class-wishlist-settings.php';

// Inicjalizacja wtyczki
class Simple_Wishlist
{
    private static $instance = null;
    public $wishlist;
    public $shortcodes;
    public $settings;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->wishlist = new Wishlist();
        $this->shortcodes = new Wishlist_Shortcodes($this->wishlist);
        $this->settings = new Wishlist_Settings();

        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_toggle_wishlist', [$this, 'ajax_toggle_wishlist']);
        add_action('wp_ajax_remove_from_wishlist', [$this, 'ajax_remove_from_wishlist']);
    }

    public function enqueue_scripts()
    {
        // Ładuj skrypt tylko dla zalogowanych użytkowników (wishlist jest tylko dla zalogowanych)
        if (!is_user_logged_in()) {
            wp_enqueue_style('simple-wishlist', WISHLIST_URL . 'assets/css/wishlist.css');
            return;
        }

        wp_enqueue_script(
            'simple-wishlist',
            WISHLIST_URL . 'assets/js/wishlist.js',
            ['jquery'],
            '2.33',
            true
        );

        wp_localize_script('simple-wishlist', 'wishlistData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wishlist_nonce'),
            'isLoggedIn' => true,
            'emptyText' => __('Your wish list is empty', 'simple-wishlist'),
            'addToCartText' => __('Add to cart', 'simple-wishlist'),
            'addedText' => __('Added!', 'simple-wishlist'),
            'allProductsAddedText' => __('All products added!', 'simple-wishlist')
        ]);

        wp_enqueue_style('simple-wishlist', WISHLIST_URL . 'assets/css/wishlist.css');
    }

    public function ajax_toggle_wishlist()
    {
        // Sprawdź czy użytkownik jest zalogowany
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in to use wishlist', 'simple-wishlist')]);
            return;
        }

        check_ajax_referer('wishlist_nonce', 'nonce');

        $product_id = intval($_POST['product_id']);

        if ($this->wishlist->is_product_in_wishlist($product_id)) {
            $this->wishlist->remove_from_wishlist($product_id);
            $action = 'removed';
        } else {
            $this->wishlist->add_to_wishlist($product_id);
            $action = 'added';
        }

        wp_send_json_success([
            'action' => $action,
            'count' => count($this->wishlist->get_wishlist_items())
        ]);
    }

    public function ajax_remove_from_wishlist()
    {
        // Sprawdź czy użytkownik jest zalogowany
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in to use wishlist', 'simple-wishlist')]);
            return;
        }

        check_ajax_referer('wishlist_nonce', 'nonce');

        $product_id = intval($_POST['product_id']);

        if ($this->wishlist->remove_from_wishlist($product_id)) {
            wp_send_json_success([
                'count' => count($this->wishlist->get_wishlist_items())
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to remove product from wishlist', 'simple-wishlist')]);
        }
    }
}

// Inicjalizacja
function simple_wishlist()
{
    return Simple_Wishlist::get_instance();
}

simple_wishlist();