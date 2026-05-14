<?php

class CartCrossSells
{

    public function __construct()
    {


        add_action('wp_enqueue_scripts', array($this, 'add_scripts'));

        add_action('wp_ajax_ajax_add_to_cart_with_crosssell', array($this, 'ajax_add_to_cart_with_crosssell'));
        add_action('wp_ajax_nopriv_ajax_add_to_cart_with_crosssell', array($this, 'ajax_add_to_cart_with_crosssell'));

        add_action('wp_ajax_remove_cross_sell_product_from_cart', array($this, 'remove_cross_sell_product_from_cart'));
        add_action('wp_ajax_nopriv_remove_cross_sell_product_from_cart', array($this, 'remove_cross_sell_product_from_cart'));

        add_action('wp_footer', array($this, 'render_modal_template'));

        if (isset($_POST['submit_modal_settings'])) {
            update_option('cgm_crosssell_products_count', $_POST['crosssell_count']);
        }
    }

    public function render_modal_template()
    {
        include(plugin_dir_path(__FILE__) . '../templates/modal-template.php');
    }


    public function ajax_add_to_cart_with_crosssell()
    {

        if (!wp_doing_ajax()) {
            return;
        }

        if (!check_ajax_referer('ajax_crosssell_nonce', 'nonce', false)) {
            wp_send_json_error('Nieprawidłowy token bezpieczeństwa');
            return;
        }

        $product_id = isset($_POST['product_id']) && !empty($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) && !empty($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        $variation_id = isset($_POST['variation_id']) && !empty($_POST['variation_id']) ? (int)$_POST['variation_id'] : 0;
        $variations = isset($_POST['variations']) && !empty($_POST['variations']) ? $_POST['variations'] : array();
        $is_cross_sell = isset($_POST['crossSellProductId']) && !empty($_POST['crossSellProductId']) ? $_POST['crossSellProductId'] : false;
        $level_index = isset($_POST['levelIndex']) && $_POST['levelIndex'] != '' ? $_POST['levelIndex'] : null;

        $product_num = get_option('cgm_crosssell_products_count', 3);

        if (!$product_id) {
            wp_send_json_error('Nieprawidłowy produkt');
            return;
        }

        $cart_item_data = array();

        if ($is_cross_sell !== false) {
            $cart_item_data['is_cross_sell'] = $is_cross_sell;
        }

        if ($level_index !== null) {
            $cart_item_data['level_index'] = $level_index;
        }

        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variations, $cart_item_data);

        if (!$cart_item_key) {
            ob_start();
            wc_print_notices();
            $notices_html = ob_get_clean();
            wp_send_json_error(array('notices_html' => $notices_html));
        } else {
            wc_add_notice(__('Product has been added to cart', 'custom-cart-gifts-modal'), 'success');
        }

        if (!$is_cross_sell) {

            $cart = WC()->cart->get_cart();
            $cross_sell_ids = array();

            $product = wc_get_product(isset($variation_id) && $variation_id > 0 ? $variation_id : $product_id);
            $product_id = $product->get_id();
            $product_cross_sells = $product->get_cross_sell_ids();
            $product_upsells = $product->get_upsell_ids();

            if (!empty($product_cross_sells)) {
                $cross_sell_ids = array_merge($cross_sell_ids, $product_cross_sells);
            }
            if (!empty($product_upsells)) {
                $cross_sell_ids = array_merge($cross_sell_ids, $product_upsells);
            }

            if (($key = array_search($product_id, $cross_sell_ids)) !== false) {
                unset($cross_sell_ids[$key]);
            }
            if (isset($variation_id) && $variation_id > 0 && ($key = array_search($variation_id, $cross_sell_ids)) !== false) {
                unset($cross_sell_ids[$key]);
            }

            $cross_sell_ids = array_unique($cross_sell_ids);

            $filtered_cross_sell_ids = array();
            foreach ($cross_sell_ids as $cross_sell_id) {
                $cross_sell_product = wc_get_product($cross_sell_id);
                if ($cross_sell_product && $cross_sell_product->is_purchasable() && $cross_sell_product->is_in_stock()) {
                    $filtered_cross_sell_ids[] = $cross_sell_id;
                }
            }
            $cross_sell_ids = $filtered_cross_sell_ids;

            if (empty($cross_sell_ids) || count($cross_sell_ids) < $product_num) {
                $product = wc_get_product($product_id);
                if ($product) {

                    $related_ids = wc_get_related_products($product_id, 10);
                    if (empty($related_ids)) {
                        $args = array(
                            'status' => 'publish',
                            'limit' => 10,
                            'orderby' => 'rand',
                            'stock_status' => 'instock',
                            'exclude' => array($product_id)
                        );

                        $random_products = wc_get_products($args);
                        $related_ids = array();

                        foreach ($random_products as $random_product) {
                            if ($random_product->is_purchasable()) {
                                $related_ids[] = $random_product->get_id();
                            }
                        }
                    }

                    $cross_sell_ids = array_merge($cross_sell_ids, $related_ids);
                }
            }
            $cross_sell_ids = array_unique($cross_sell_ids);
            $cross_sell_ids = array_slice($cross_sell_ids, 0, $product_num);

            $html = '';

            foreach ($cross_sell_ids as $product_id) {
                $product_theme = get_template_directory() . '/components/content-product-modal.php';
                ob_start();
?>
<li class="splide__slide">
    <?php
                    setup_postdata($product_id);
                    include($product_theme);
                    wp_reset_postdata();
                    ?>
</li>
<?php
                $html .= ob_get_clean();
            }

            $added_product = wc_get_product($product_id);
            $added_product_name = '';
            if ($added_product) {
                $added_product_name = __('Product has been added to cart', 'custom-cart-gifts-modal');
            }

            $response = array(
                'html' => $html,
                'products' => $cross_sell_ids,
                'added_product_name' => $added_product_name
            );

            wp_send_json_success($response);
        } else {
            $cross_sell_page = new CrossSellPage();
            $html = $cross_sell_page->get_html_cross_sell_products();
            ob_start();
            wc_print_notices();
            $notices_html = ob_get_clean();
            wp_send_json_success(array('html' => $html, 'is_cross_sell_page' => true, 'notices_html' => $notices_html));
        }
    }


    public function remove_cross_sell_product_from_cart()
    {

        $level_index = isset($_POST['levelIndex']) && !empty($_POST['levelIndex']) ? (int)$_POST['levelIndex'] : null;
        $cross_sell_product_id = isset($_POST['crossSellProductId']) && !empty($_POST['crossSellProductId']) ? (int)$_POST['crossSellProductId'] : 0;

        if (!check_ajax_referer('ajax_crosssell_nonce', 'nonce', false)) {
            wp_send_json_error('Nieprawidłowy token bezpieczeństwa');
            return;
        }

        $cart = WC()->cart->get_cart();

        foreach ($cart as $cart_item_key => $cart_item) {

            if (!isset($cart_item['is_cross_sell']) || !isset($cart_item['level_index'])) {
                continue;
            }

            $is_cross_sell = (int)$cart_item['is_cross_sell'];
            $level_index = (int)$cart_item['level_index'];

            if ($is_cross_sell == $cross_sell_product_id) {
                if ($level_index == $level_index) {
                    WC()->cart->remove_cart_item($cart_item_key);

                    $cross_sell_page = new CrossSellPage();
                    $html = $cross_sell_page->get_html_cross_sell_products();

                    wc_add_notice(__('Product has been removed from cart', 'custom-cart-gifts-modal'), 'success');

                    ob_start();
                    wc_print_notices();
                    $notices_html = ob_get_clean();

                    wp_send_json_success(array('html' => $html, 'notices_html' => $notices_html));
                }
            }
        }

        wp_send_json_error('Nie udało się usunąć produktu z koszyka');
    }
    public function add_scripts()
    {

        $version = 2.993;
        $version = rand(1, 1000000);


        wp_enqueue_style('cgm-custom-cart-gifts-modal-css', plugin_dir_url(__FILE__) . '../assets/css/modal.min.css', array(), $version, 'all');
        wp_enqueue_script('cgm-custom-cart-gifts-modal-js', plugin_dir_url(__FILE__) . '../assets/js/modal.js', array('jquery'), $version, true);
        wp_localize_script('cgm-custom-cart-gifts-modal-js', 'ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ajax_crosssell_nonce'),
            'added' => __('Added to cart', 'custom-cart-gifts-modal'),
            'products_per_page' => get_option('cgm_crosssell_products_count', 3),
            'counter_class' => get_option('cgm_counter_class', '')
        ));

        if (!wp_script_is('cgm-splide-js', 'registered') && !wp_script_is('cgm-splide', 'registered')) {
            wp_enqueue_script('cgm-splide-js', plugin_dir_url(__FILE__) . '../assets/js/splide.min.js', array(), $version, true);
            wp_enqueue_style('cgm-splide-css', plugin_dir_url(__FILE__) . '../assets/css/splide.min.css', array(), $version, 'all');
        }
    }
}