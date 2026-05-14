<?php
/*
Plugin Name: Gift in cart
Author: Weblo
Description: Gifty w koszyku
Text Domain: gift-in-cart-plugin
*/

add_action('wp_enqueue_scripts', function () {
    $developer_mode = true;
    if ($developer_mode) {
        $scripts_version = time();
    } else {
        $scripts_version = '11.1';
    }

    wp_enqueue_style('gift-in-cart-popup', plugin_dir_url(__FILE__) . 'css/gift-in-cart-popup.min.css', '', $scripts_version);
    wp_enqueue_style('new-cart-splide', plugin_dir_url(__FILE__) . 'css/splide.min.css');
    wp_enqueue_script('new-cart-splide-script', plugin_dir_url(__FILE__) . 'js/splide.min.js', array('jquery'));
    wp_enqueue_script('gift-in-cart-popup', plugin_dir_url(__FILE__) . 'js/gift-in-cart-popup.js', array('jquery', 'new-cart-splide-script'), $scripts_version);
    // wp_enqueue_style('new_cart_change', plugin_dir_url(__FILE__) . 'css/new_cart_change.css', '', '12.91111');

    if (is_product()) {
        wp_enqueue_style('new-cart-style-product', plugin_dir_url(__FILE__) . 'css/new-cart-product.min.css');
        wp_enqueue_script('new-cart-product-script', plugin_dir_url(__FILE__) . 'js/new-cart-gift-product.js', array('jquery'));
    }
    if (is_cart()) {
        wp_enqueue_style('new-cart-splide', plugin_dir_url(__FILE__) . 'css/splide.min.css');
        wp_enqueue_script('new-cart-splide-script', plugin_dir_url(__FILE__) . 'js/splide.min.js', array('jquery'));
        wp_enqueue_script('new-cart-script', plugin_dir_url(__FILE__) . 'js/new-cart.js', array('jquery'), $scripts_version);
        wp_enqueue_script('new-cart-gratis-script', plugin_dir_url(__FILE__) . 'js/new-cart-gratis.js', array('jquery'), $scripts_version);

        $cart_refresh_params = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cart_refresh_nonce')
        );
        wp_localize_script('new-cart-script', 'cart_refresh_params', $cart_refresh_params);
        wp_localize_script('new-cart-gratis-script', 'cart_refresh_params', $cart_refresh_params);
    }

    $cart_refresh_params = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'is_cart' => is_cart(),
    );
    wp_localize_script('new-cart-script', 'wc_add_to_cart_params', $cart_refresh_params);
    wp_localize_script('gift-in-cart-popup', 'ajax_object', $cart_refresh_params);

    // Dodaj zmienne językowe dla komunikatów kuponów
    $coupon_messages = array(
        'codeValid' => __('The entered code is correct.', 'gift-in-cart-plugin'),
        'codeInvalid' => __('The entered code is incorrect.', 'gift-in-cart-plugin'),
        'cartUpdated' => __('CART UPDATED.', 'gift-in-cart-plugin'),
        'giftAdded' => __('WE ADDED A GIFT TO YOUR ORDER', 'gift-in-cart-plugin'),
    );
    wp_localize_script('new-cart-script', 'couponMessages', $coupon_messages);
    wp_localize_script('new-cart-gratis-script', 'couponMessages', $coupon_messages);
});

/**
 * Bezpieczna funkcja do pobierania ceny z konwersją waluty
 * Sprawdza czy funkcja wmc_get_price z wtyczki woocommerce-multi-currency jest dostępna
 * Jeśli nie, zwraca wartość bezpośrednio
 * 
 * @param mixed $price Cena do konwersji
 * @return mixed Cena po konwersji lub oryginalna wartość
 */
function safe_wmc_get_price($price)
{
    if (function_exists('wmc_get_price')) {
        return wmc_get_price($price);
    }
    return $price;
}

add_action('wp_ajax_nc_update_cart_quantity', 'new_cart_update_cart_quantity');
add_action('wp_ajax_nopriv_nc_update_cart_quantity', 'new_cart_update_cart_quantity');

function new_cart_update_cart_quantity()
{
    $product_key = isset($_POST['product_key']) ? wc_clean(wp_unslash($_POST['product_key'])) : '';
    $quantity = isset($_POST['quantity']) ? wc_clean(wp_unslash($_POST['quantity'])) : '';

    if (empty($product_key) || empty($quantity)) {
        wp_send_json_error(__('Invalid request.', 'gift-in-cart-plugin'));
    }
    WC()->cart->set_quantity($product_key, $quantity);
    WC()->cart->calculate_totals();
    $cart_contents_count = WC()->cart->get_cart_contents_count();

    $response_data = array(
        'success' => true,
        'cart_contents_count' => $cart_contents_count,
    );
    wp_send_json_success($response_data);
}

add_action('wp_ajax_nc_add_to_cart_sample', 'new_cart_add_to_cart_sample');
add_action('wp_ajax_nopriv_nc_add_to_cart_sample', 'new_cart_add_to_cart_sample');

function new_cart_add_to_cart_sample()
{
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $sample_name = isset($_POST['sample_name']) ? sanitize_text_field($_POST['sample_name']) : '';
    $sample_price = isset($_POST['sample_price']) ? floatval($_POST['sample_price']) : 0;
    $sample_slogan = isset($_POST['sample_slogan']) ? sanitize_text_field($_POST['sample_slogan']) : '';

    if ($product_id <= 0) {
        wp_send_json_error(array('message' => __('Invalid product ID', 'gift-in-cart-plugin') . ' (ID: ' . $product_id . ')'));
        wp_die();
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        // Spróbuj pobrać produkt jako post, jeśli to nie zadziała
        $post = get_post($product_id);
        if (!$post || $post->post_type !== 'product') {
            wp_send_json_error(array('message' => __('Product not found', 'gift-in-cart-plugin') . ' (ID: ' . $product_id . ')'));
            wp_die();
        }
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(array('message' => __('Product not found', 'gift-in-cart-plugin') . ' (ID: ' . $product_id . ')'));
            wp_die();
        }
    }

    if (!$product->is_purchasable() || !$product->is_in_stock()) {
        wp_send_json_error(array('message' => __('Product is not available', 'gift-in-cart-plugin')));
        wp_die();
    }

    // Przygotuj customowe dane dla próbki
    // Zaokrąglij cenę do 2 miejsc po przecinku
    $sample_price = $sample_price > 0 ? round(floatval($sample_price), 2) : round(floatval($product->get_price()), 2);

    $cart_item_data = array(
        'is_sample' => true,
        'sample_name' => $sample_name ? $sample_name : $product->get_name(),
        'sample_price' => $sample_price,
        'sample_slogan' => $sample_slogan,
    );

    $added_to_cart = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);

    if ($added_to_cart) {
        $response = array(
            'counter' => WC()->cart->get_cart_contents_count(),
            'is_cart' => is_cart(),
        );
        $fragments = apply_filters('woocommerce_add_to_cart_fragments', array());
        $response['fragments'] = $fragments;
        wp_send_json_success($response);
    } else {
        $error_message = __('Failed to add sample to cart', 'gift-in-cart-plugin');
        $notices = wc_get_notices('error');
        if (!empty($notices)) {
            $error_message = wp_strip_all_tags($notices[0]['notice']);
            wc_clear_notices();
        }
        wp_send_json_error(array('message' => $error_message));
    }
    wp_die();
}

add_action('wp_ajax_nc_apply_coupon_code', 'new_cart_apply_coupon_code');
add_action('wp_ajax_nopriv_nc_apply_coupon_code', 'new_cart_apply_coupon_code');

function new_cart_apply_coupon_code()
{
    $couponCode = isset($_POST['coupon_code']) ? sanitize_text_field($_POST['coupon_code']) : '';

    if (empty($couponCode)) {
        wp_send_json_error(array('message' => __('Invalid coupon code.', 'gift-in-cart-plugin')));
    }

    $result = WC()->cart->apply_coupon($couponCode);

    if ($result === true) {
        WC()->cart->calculate_totals();
        wp_send_json_success();
    } elseif (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    } else {
        wp_send_json_error(array('message' => __('Unknown error occurred.', 'gift-in-cart-plugin')));
    }
}


add_action('wp_ajax_nc_delete_coupon_code', 'new_cart_delete_coupon_code');
add_action('wp_ajax_nopriv_nc_delete_coupon_code', 'new_cart_delete_coupon_code');

function new_cart_delete_coupon_code()
{

    $couponCode = isset($_POST['coupon_code']) ? sanitize_text_field($_POST['coupon_code']) : '';

    if (empty($couponCode)) {
        wp_send_json_error(array('message' => __('Invalid coupon code.', 'gift-in-cart-plugin')));
    }

    $result = WC()->cart->remove_coupon($couponCode);

    if ($result === true) {
        WC()->cart->calculate_totals();
        wp_send_json_success();
    } else {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }
}


function cart_shipping_counter()
{
    ob_start();

    if (get_field('free_shipping_counter', 'option')) {
        $shipping_min = get_field('free_shipping_min', 'option');
        if (get_woocommerce_currency() == 'EUR')
            $shipping_min = safe_wmc_get_price($shipping_min);

        $total_with_tax = WC()->cart->total;
        $shipping_total = WC()->cart->get_shipping_total();
        $shipping_tax = array_sum(WC()->cart->get_shipping_taxes());
        $total_without_shipping = (float) ($total_with_tax - $shipping_total - $shipping_tax);
        $current_total = (float) $total_without_shipping;
        $progress_percentage = min(($current_total / $shipping_min) * 100, 100);

        if ($shipping_min > $current_total) {
            $shipping_icon = '            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
  <path d="M14 18V6C14 5.46957 13.7893 4.96086 13.4142 4.58579C13.0391 4.21071 12.5304 4 12 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V17C2 17.2652 2.10536 17.5196 2.29289 17.7071C2.48043 17.8946 2.73478 18 3 18H5M5 18C5 19.1046 5.89543 20 7 20C8.10457 20 9 19.1046 9 18M5 18C5 16.8954 5.89543 16 7 16C8.10457 16 9 16.8954 9 18M15 18H9M15 18C15 19.1046 15.8954 20 17 20C18.1046 20 19 19.1046 19 18M15 18C15 16.8954 15.8954 16 17 16C18.1046 16 19 16.8954 19 18M19 18H21C21.2652 18 21.5196 17.8946 21.7071 17.7071C21.8946 17.5196 22 17.2652 22 17V13.35C21.9996 13.1231 21.922 12.903 21.78 12.726L18.3 8.376C18.2065 8.25888 18.0878 8.16428 17.9528 8.0992C17.8178 8.03412 17.6699 8.00021 17.52 8H14" stroke="#6D6059" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>';
            $to_free_shipping = $shipping_min - $current_total;
            echo '<div class="shipping-counter d-flex">';
            echo $shipping_icon;
            echo '<p>' . sprintf(__('For free shipping: %s. ', 'gift-in-cart-plugin'), '<b>' . wc_price($to_free_shipping) . '</b>');
            echo '</div>';
        } else {
            echo '<div class="shipping-counter">';
            echo '<p>' . __('Free shipping added', 'gift-in-cart-plugin') . '</p>';
            echo '</div>';
        }
    }

    return ob_get_clean();
}

add_shortcode('cart_shipping_counter', 'cart_shipping_counter');

function cart_shipping_counter_progress_bar()
{
    ob_start();

    if (get_field('free_shipping_counter', 'option')) {
        $shipping_min = get_field('free_shipping_min', 'option');
        if (get_woocommerce_currency() == 'EUR')
            $shipping_min = safe_wmc_get_price($shipping_min);

        $total_with_tax = WC()->cart->total;
        $shipping_total = WC()->cart->get_shipping_total();
        $shipping_tax = array_sum(WC()->cart->get_shipping_taxes());
        $total_without_shipping = (float) ($total_with_tax - $shipping_total - $shipping_tax);
        $current_total = (float) $total_without_shipping;
        $progress_percentage = min(($current_total / $shipping_min) * 100, 100);
        $shipping_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36" fill="none">
  <path d="M21 27V9C21 8.20435 20.6839 7.44129 20.1213 6.87868C19.5587 6.31607 18.7956 6 18 6H6C5.20435 6 4.44129 6.31607 3.87868 6.87868C3.31607 7.44129 3 8.20435 3 9V25.5C3 25.8978 3.15804 26.2794 3.43934 26.5607C3.72064 26.842 4.10218 27 4.5 27H7.5M7.5 27C7.5 28.6569 8.84315 30 10.5 30C12.1569 30 13.5 28.6569 13.5 27M7.5 27C7.5 25.3431 8.84315 24 10.5 24C12.1569 24 13.5 25.3431 13.5 27M22.5 27H13.5M22.5 27C22.5 28.6569 23.8431 30 25.5 30C27.1569 30 28.5 28.6569 28.5 27M22.5 27C22.5 25.3431 23.8431 24 25.5 24C27.1569 24 28.5 25.3431 28.5 27M28.5 27H31.5C31.8978 27 32.2794 26.842 32.5607 26.5607C32.842 26.2794 33 25.8978 33 25.5V20.025C32.9994 19.6846 32.883 19.3545 32.67 19.089L27.45 12.564C27.3097 12.3883 27.1317 12.2464 26.9292 12.1488C26.7267 12.0512 26.5048 12.0003 26.28 12H21" stroke="#6D6059" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>';
?>
        <div class="wrap_all_shipping_info">
            <?php echo $shipping_icon; ?>
            <?php if ($shipping_min > $current_total) :
                $to_free_shipping = $shipping_min - $current_total;
            ?>
                <div class="shipping-counter-progress-wrapper">
                    <div class="shipping-counter-progress-wrapper_top">
                        <div class="shipping-counter-message">
                            <?php printf(__('MISSING FOR FREE SHIPPING: %s', 'gift-in-cart-plugin'), '<span class="shipping-counter-message-amount">' . wc_price($to_free_shipping) . '</span>'); ?>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-bar-filled" style="width: <?php echo $progress_percentage; ?>%;"></div>
                        </div>
                    </div>
                    <div class="shipping-counter-progress-wrapper_bottom d-flex">

                        <div class="shipping-counter-amount">
                            <?php echo wc_price($shipping_min); ?>
                        </div>
                    </div>


                </div>
            <?php else : ?>
                <div class="shipping-counter">
                    <p><?php echo __('Free shipping added', 'gift-in-cart-plugin'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php
    }

    return ob_get_clean();
}

add_shortcode('cart_shipping_counter_progress_bar', 'cart_shipping_counter_progress_bar');

// Dodaj kontener z notyfikacjami do fragmentów WooCommerce
add_filter('woocommerce_add_to_cart_fragments', 'new_cart_add_notices_to_fragments', 10, 1);
function new_cart_add_notices_to_fragments($fragments)
{
    if (is_cart()) {
        ob_start();
    ?>
        <div class="custom-woocommerce-notices-wrapper">
            <div class="woocommerce-notices-wrapper">
                <?php wc_print_notices(); ?>
            </div>
        </div>
    <?php
        $fragments['.custom-woocommerce-notices-wrapper'] = ob_get_clean();
    }
    return $fragments;
}

// Dodaj formularz kuponu do fragmentów WooCommerce, aby był automatycznie aktualizowany
add_filter('woocommerce_add_to_cart_fragments', 'new_cart_add_coupon_form_to_fragments', 10, 1);
function new_cart_add_coupon_form_to_fragments($fragments)
{
    if (is_cart() && wc_coupons_enabled()) {
        ob_start();
    ?>

        <div class="coupon-form custom_cart_coupon_form">
            <div class="custom_cart_coupon_form_wrapper d-flex justify-content-between align-items-center">
                <div class="custom_cart_coupon_form_wrapper_title uppercase">
                    <?= __('Discount code', 'gift-in-cart-plugin'); ?>
                </div>
                <div class="custom_cart_coupon_form_wrapper_button d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
                        <path d="M0 7H14M7 0V14" stroke="#47170D" stroke-width="1.5" stroke-linejoin="round" />
                    </svg>
                    <span><?= __('Add', 'gift-in-cart-plugin'); ?></span>

                </div>
            </div>

            <div class="coupon-inputs coupon-inputs-hidden"
                data-content="<?php _e('Enter discount code', 'gift-in-cart-plugin'); ?>">

                <input type="text" name="coupon_code" class="input-text col" id="coupon_code" value=""
                    placeholder="<?php esc_attr_e('Coupon code', 'woocommerce'); ?>"
                    aria-label="<?php esc_attr_e('Coupon code', 'woocommerce'); ?>" />
                <button type="submit"
                    class="button<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"
                    name="apply_coupon"
                    value="<?php esc_attr_e('Apply', 'gift-in-cart-plugin'); ?>"><?php esc_html_e('Apply', 'gift-in-cart-plugin'); ?></button>
            </div>
            <?php do_action('woocommerce_cart_coupon'); ?>

            <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
                <div class="cart-discount d-flex justify-content-between coupon-<?php echo esc_attr(sanitize_title($code)); ?>">
                    <label><?php _e('Discount applied', 'gift-in-cart-plugin'); ?>: <strong>
                            <?php
                            echo strtoupper($coupon->get_code());
                            ?>
                        </strong>
                    </label>
                </div>
            <?php endforeach; ?>
            <div class="coupon-code-message" style="display: none;"></div>
        </div>
    <?php
        $fragments['.custom_cart_coupon_form'] = ob_get_clean();
    }
    return $fragments;
}

include('inc/new-cart-gift.php');

add_filter('woocommerce_locate_template', 'new_cart_intercept_wc_template', 10, 3);
function new_cart_intercept_wc_template($template, $template_name, $template_path)
{

    if ('cart.php' === basename($template)) {
        $template = trailingslashit(plugin_dir_path(__FILE__)) . 'woocommerce/cart/cart.php';
    }

    if ('cart-totals.php' === basename($template)) {
        $template = trailingslashit(plugin_dir_path(__FILE__)) . 'woocommerce/cart/cart-totals.php';
    }
    if ('cart-empty.php' === basename($template)) {
        $template = trailingslashit(plugin_dir_path(__FILE__)) . 'woocommerce/cart/cart-empty.php';
    }

    return $template;
}

add_action('wp_ajax_new_ajax_add_to_cart', 'new_ajax_add_to_cart');
add_action('wp_ajax_nopriv_new_ajax_add_to_cart', 'new_ajax_add_to_cart');

function new_ajax_add_to_cart()
{
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;
    $variation = isset($_POST['variation']) ? $_POST['variation'] : array();
    $response = array();

    if ($product_id > 0 && $quantity > 0) {
        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error(array('message' => 'Product not found'));
            wp_die();
        }

        // Sprawdź czy produkt ma warianty
        if ($product->is_type('variable')) {
            // Dla produktów z wariantami, variation_id jest wymagany
            if ($variation_id <= 0) {
                wp_send_json_error(array('message' => 'Please select product options'));
                wp_die();
            }

            $variation_product = wc_get_product($variation_id);
            if (!$variation_product) {
                wp_send_json_error(array('message' => 'Variation not found'));
                wp_die();
            }

            // Sprawdź czy wariant jest dostępny
            if (!$variation_product->is_purchasable() || !$variation_product->is_in_stock()) {
                wp_send_json_error(array('message' => 'This variation is not available'));
                wp_die();
            }

            $added_to_cart = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation);
        } elseif ($variation_id > 0) {
            // Jeśli przekazano variation_id dla produktu bez wariantów, użyj go jako product_id
            $added_to_cart = WC()->cart->add_to_cart($variation_id, $quantity);
            $product_id = $variation_id;
            $variation_id = 0;
        } else {
            // Dla zwykłych produktów bez wariantów
            if (!$product->is_purchasable() || !$product->is_in_stock()) {
                wp_send_json_error(array('message' => 'Product is not available'));
                wp_die();
            }

            $added_to_cart = WC()->cart->add_to_cart($product_id, $quantity);
        }

        if ($added_to_cart) {
            // Użyj variation_id jeśli istnieje, w przeciwnym razie product_id
            $display_product_id = $variation_id > 0 ? $variation_id : $product_id;
            set_query_var('productid', $display_product_id);
            set_query_var('quantity', $quantity);

            // Sprawdź czy popup jest włączony
            $popup_active = get_field('popup_active', 'option') == 1;

            if ($popup_active) {
                ob_start();
                include('inc/new-cart-addtocart-popup-template.php');
                $response['html'] = ob_get_clean();
                $response['show'] = true;
            } else {
                $response['show'] = false;
            }

            $response['counter'] = WC()->cart->get_cart_contents_count();
            $response['is_cart'] = is_cart();
            $fragments = apply_filters('woocommerce_add_to_cart_fragments', array());
            $response['fragments'] = $fragments;
            wp_send_json_success($response);
        } else {
            $error_message = 'Failed to add product to cart';
            $notices = wc_get_notices('error');
            if (!empty($notices)) {
                $error_message = wp_strip_all_tags($notices[0]['notice']);
                wc_clear_notices();
            }
            wp_send_json_error(array('message' => $error_message));
        }
    } else {
        wp_send_json_error(array('message' => 'Invalid product ID or quantity'));
    }
    wp_die();
}

// Filtry dla próbek - customowa nazwa i cena
add_filter('woocommerce_cart_item_name', 'new_cart_sample_item_name', 10, 3);
function new_cart_sample_item_name($product_name, $cart_item, $cart_item_key)
{
    if (isset($cart_item['is_sample']) && $cart_item['is_sample'] && !empty($cart_item['sample_name'])) {
        return $cart_item['sample_name'];
    }
    return $product_name;
}

// Customowa cena dla próbek
add_action('woocommerce_before_calculate_totals', 'new_cart_set_sample_price', 10, 1);
function new_cart_set_sample_price($cart)
{
    if (is_admin() && !defined('DOING_AJAX'))
        return;

    if (did_action('woocommerce_before_calculate_totals') >= 2)
        return;

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['is_sample']) && $cart_item['is_sample'] && isset($cart_item['sample_price'])) {
            // sample_price to cena brutto (z podatkiem) - ustawiamy dokładnie tę cenę
            $sample_price = floatval($cart_item['sample_price']);
            $sample_price = round($sample_price, 2);
            
            // Ustaw cenę - dla próbek ustawiamy cenę brutto bezpośrednio
            $cart_item['data']->set_price($sample_price);
            
            // Wyłącz podatek dla próbek, aby cena była dokładnie taka jak podana (brutto)
            // Podana cena już zawiera podatek, więc nie trzeba go dodawać ponownie
            $cart_item['data']->set_tax_status('none');
        }
    }
}

// Wyświetlanie customowych meta danych dla próbek
add_filter('woocommerce_get_item_data', 'new_cart_display_sample_meta', 10, 2);
function new_cart_display_sample_meta($item_data, $cart_item)
{
    if (isset($cart_item['is_sample']) && $cart_item['is_sample']) {
        if (!empty($cart_item['sample_slogan'])) {
            $item_data[] = array(
                'key' => __('Sample slogan', 'gift-in-cart-plugin'),
                'value' => $cart_item['sample_slogan'],
            );
        }
    }
    return $item_data;
}

add_action(
    'wp_footer',
    function () {
        if (get_field('popup_active', 'option') == 1) {
    ?>
        <div class="gift-in-cart-wrapper" style="display: none;">
            <div id="gift-in-cart-popup"></div>
        </div>
<?php
        }
    }
);

function get_level_settings($level)
{
    return array(
        'enabled' => get_field("lvl{$level}-on", 'option'),
        'required_coupon' => false //disable 
    );
}

// Funkcja sprawdzająca czy poziom jest aktywny i czy ma wymagany kod rabatowy
function check_level_requirements($level)
{
    $settings = get_level_settings($level);

    // Jeśli poziom nie jest włączony, zwracamy false
    if (!$settings['enabled']) {
        return false;
    }

    // Jeśli jest wymagany kod rabatowy, sprawdzamy czy jest aplikowany
    if (!empty($settings['required_coupon'])) {
        return WC()->cart->has_discount($settings['required_coupon']);
    }

    // Jeśli poziom jest włączony i nie ma wymaganego kodu, zwracamy true
    return true;
}

function get_active_levels()
{
    $active_levels = array();

    for ($i = 1; $i <= 3; $i++) {
        $settings = get_level_settings($i);
        if ($settings['enabled']) {
            $active_levels[] = $i;
        }
    }

    return $active_levels;
}

// Modyfikujemy funkcję sprawdzającą wymagania poziomów
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    // Sprawdź każdy przedmiot w koszyku
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        // Jeśli to jest gift
        if (isset($cart_item['gift_level'])) {
            $level = intval(str_replace('Level ', '', $cart_item['gift_level']));
            $level_settings = get_level_settings($level);
            $level_settings['required_coupon'] = false; //disable 

            // Jeśli poziom wymaga kuponu
            if (!empty($level_settings['required_coupon'])) {
                // Sprawdź czy wymagany kupon jest w koszyku
                if (!WC()->cart->has_discount($level_settings['required_coupon'])) {
                    // Jeśli nie ma wymaganego kuponu, usuń gift
                    $cart->remove_cart_item($cart_item_key);
                }
            }
        }
    }
}, 10, 1);

// Dodaj nowy hook na usunięcie kuponu
add_action('woocommerce_removed_coupon', 'handle_coupon_removal', 10, 1);

function handle_coupon_removal($coupon_code)
{
    // Sprawdź koszyk pod kątem prezentów
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        // Sprawdź czy przedmiot jest prezentem
        if (isset($cart_item['gift_item'])) {
            // Sprawdź poziom prezentu
            $gift_level = isset($cart_item['gift_level']) ? $cart_item['gift_level'] : '';

            if ($gift_level) {
                // Pobierz ustawienia poziomu
                $level_settings = get_level_settings($gift_level);
                $level_settings['required_coupon'] = false; //disable 


                // Jeśli ten poziom wymagał usuniętego kuponu, usuń prezent
                if (!empty($level_settings['required_coupon']) && $level_settings['required_coupon'] === $coupon_code) {
                    WC()->cart->remove_cart_item($cart_item_key);
                }
            }
        }
    }
}
