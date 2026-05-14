<?php
class Wishlist
{
    public function __construct()
    {
        // ... existing code ...

        // Dodaj akcje AJAX (tylko dla zalogowanych użytkowników)
        add_action('wp_ajax_add_to_cart_from_wishlist', [$this, 'ajax_add_to_cart_from_wishlist']);
        add_action('wp_ajax_add_all_to_cart_from_wishlist', [$this, 'ajax_add_all_to_cart_from_wishlist']);
        // add_action('wp_ajax_get_cart_count', [$this, 'ajax_get_cart_count']);
    }

    public function add_to_wishlist($product_id)
    {
        $user_id = get_current_user_id();
        if (!$user_id) return false;

        // Sprawdź czy produkt jest prawidłowy przed dodaniem
        if (!$this->is_valid_product($product_id)) {
            return false;
        }

        $wishlist = $this->get_wishlist_items();
        if (!in_array($product_id, $wishlist)) {
            $wishlist[] = $product_id;
            update_user_meta($user_id, 'wishlist', array_values($wishlist));
            return true;
        }
        return false;
    }

    public function remove_from_wishlist($product_id)
    {
        $user_id = get_current_user_id();
        if (!$user_id) return false;

        $wishlist = $this->get_wishlist_items();
        if (($key = array_search($product_id, $wishlist)) !== false) {
            unset($wishlist[$key]);
            update_user_meta($user_id, 'wishlist', array_values($wishlist));
            return true;
        }
        return false;
    }

    /**
     * Sprawdza czy produkt jest prawidłowy (istnieje i jest opublikowany)
     */
    private function is_valid_product($product_id)
    {
        if (!$product_id || $product_id <= 0) {
            return false;
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            return false;
        }

        // Sprawdź czy produkt jest opublikowany
        $post_status = get_post_status($product_id);
        if ($post_status !== 'publish') {
            return false;
        }

        return true;
    }

    public function get_wishlist_items()
    {
        $user_id = get_current_user_id();
        if (!$user_id) return [];

        $wishlist = array_filter((array) get_user_meta($user_id, 'wishlist', true));

        // Filtruj tylko prawidłowe produkty
        $valid_products = [];
        $has_invalid = false;

        foreach ($wishlist as $product_id) {
            if ($this->is_valid_product($product_id)) {
                $valid_products[] = $product_id;
            } else {
                $has_invalid = true;
            }
        }

        // Jeśli znaleziono nieprawidłowe produkty, zaktualizuj wishlistę
        if ($has_invalid) {
            update_user_meta($user_id, 'wishlist', array_values($valid_products));
        }

        return $valid_products;
    }

    public function is_product_in_wishlist($product_id)
    {
        return in_array($product_id, $this->get_wishlist_items());
    }

    public function ajax_get_cart_count()
    {
        // Sprawdź czy użytkownik jest zalogowany
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in', 'simple-wishlist')]);
            return;
        }

        check_ajax_referer('wishlist_nonce', 'nonce');

        if (!function_exists('WC') || !WC()->cart) {
            wp_send_json_error(['message' => __('WooCommerce is not available', 'simple-wishlist')]);
            return;
        }

        wp_send_json_success([
            'count' => WC()->cart->get_cart_contents_count()
        ]);
    }

    public function ajax_add_to_cart_from_wishlist()
    {
        // Sprawdź czy użytkownik jest zalogowany
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in', 'simple-wishlist')]);
            return;
        }

        check_ajax_referer('wishlist_nonce', 'nonce');

        if (!function_exists('WC') || !WC()->cart) {
            wp_send_json_error(['message' => __('WooCommerce is not available', 'simple-wishlist')]);
            return;
        }

        $product_id = intval($_POST['product_id']);

        // Sprawdź czy produkt jest prawidłowy
        if (!$this->is_valid_product($product_id)) {
            wp_send_json_error(['message' => __('Product is not available', 'simple-wishlist')]);
            return;
        }

        $added = WC()->cart->add_to_cart($product_id);

        wp_send_json_success([
            'success' => $added ? true : false,
            'cart_count' => WC()->cart->get_cart_contents_count()
        ]);
    }

    public function ajax_add_all_to_cart_from_wishlist()
    {
        // Sprawdź czy użytkownik jest zalogowany
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in', 'simple-wishlist')]);
            return;
        }

        check_ajax_referer('wishlist_nonce', 'nonce');

        if (!function_exists('WC') || !WC()->cart) {
            wp_send_json_error(['message' => __('WooCommerce is not available', 'simple-wishlist')]);
            return;
        }

        $product_ids = isset($_POST['product_ids']) ? array_map('intval', $_POST['product_ids']) : [];
        $success = true;

        foreach ($product_ids as $product_id) {
            // Sprawdź czy produkt jest prawidłowy przed dodaniem
            if ($this->is_valid_product($product_id)) {
                $added = WC()->cart->add_to_cart($product_id);
                if (!$added) {
                    $success = false;
                }
            }
        }

        wp_send_json_success([
            'success' => $success,
            'cart_count' => WC()->cart->get_cart_contents_count()
        ]);
    }
}