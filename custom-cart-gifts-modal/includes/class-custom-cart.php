<?php

class CustomCart
{

    public function __construct()
    {

        add_action('wp_ajax_update_cart_quantity', array($this, 'ajax_update_cart_quantity'));
        add_action('wp_ajax_nopriv_update_cart_quantity', array($this, 'ajax_update_cart_quantity'));
        add_action('wp_ajax_get_product_cart_count', array($this, 'get_product_cart_count'));
        add_action('wp_ajax_nopriv_get_product_cart_count', array($this, 'get_product_cart_count'));

        add_action('wp_ajax_add_gift_to_cart', array($this, 'add_gift_to_cart'));
        add_action('wp_ajax_nopriv_add_gift_to_cart', array($this, 'add_gift_to_cart'));

        add_action('wp_ajax_remove_gift_from_cart', array($this, 'remove_gift_from_cart'));
        add_action('wp_ajax_nopriv_remove_gift_from_cart', array($this, 'remove_gift_from_cart'));

        add_action('woocommerce_before_calculate_totals', array($this, 'update_gifts_prices'), 10, 1);
        add_action('woocommerce_calculate_totals', array($this, 'gifts_update_cart_items'), 10, 1);

        add_action('woocommerce_before_calculate_totals', array($this, 'update_cross_sells_prices'), 11, 1);
        add_action('woocommerce_calculate_totals', array($this, 'update_cross_sells_items'), 11, 1);



        add_filter('body_class', array($this, 'add_custom_wc_class'));

        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        add_action('wp_ajax_update_cart_variation', array($this, 'ajax_update_cart_variation'));
        add_action('wp_ajax_nopriv_update_cart_variation', array($this, 'ajax_update_cart_variation'));
    }

    public function add_custom_wc_class($classes)
    {
        $classes[] = 'notification-' . get_option('cgm_notification_position', 'standard');
        return $classes;
    }


    public function ajax_update_cart_quantity()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'custom_cart_nonce')) {
            wp_send_json_error(array('message' => 'Błąd weryfikacji bezpieczeństwa'));
            return;
        }
        $cart_item_key = $_POST['cart_item_key'];
        $quantity = $_POST['quantity'];
        $this->update_cart_item_quantity($cart_item_key, $quantity);
        wp_send_json_success();
    }

    public function update_cart_item_quantity($cart_item_key, $quantity)
    {

        if ($quantity == 0) {
            WC()->cart->remove_cart_item($cart_item_key);
            return;
        }
        WC()->cart->set_quantity($cart_item_key, $quantity);
        WC()->cart->calculate_totals();
    }

    public function get_product_cart_count()
    {
        $count = WC()->cart->get_cart_contents_count();
        $cart_total = WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax();


        wp_send_json_success(array(
            'count' => $count,
            'total' => wc_price($cart_total)
        ));
    }

    public function enqueue_styles()
    {

        $version = 2.5;
        $version = rand(1, 1000000);

        if (is_cart()) {
            // wp_enqueue_style('cgm-custom-cart-css', plugin_dir_url(__FILE__) . '../assets/css/custom-cart.min.css', array(), $version, 'all');
            // wp_enqueue_style('cgm-custom-cart-item-css', plugin_dir_url(__FILE__) . '../assets/css/custom-cart-item.min.css', array(), $version, 'all');
            wp_enqueue_script('cgm-custom-cart-js', plugin_dir_url(__FILE__) . '../assets/js/custom-cart.js', array('jquery'), $version, true); // Dodano zależność od jQuery
            wp_enqueue_style('cgm-gifts-progress-bar-css', plugin_dir_url(__FILE__) . '../assets/css/gifts-progress-bar.min.css', array(), $version, 'all');
            wp_localize_script('cgm-custom-cart-js', 'custom_cart_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('custom_cart_nonce'),
                'counter_class' => get_option('cgm_counter_class', '')
            ));
            wp_enqueue_script('cgm-splide-js', plugin_dir_url(__FILE__) . '../assets/js/splide.min.js', array('jquery'), $version, true); // Dodano zależność od jQuery
            wp_enqueue_style('cgm-splide-css', plugin_dir_url(__FILE__) . '../assets/css/splide.min.css', array(), $version, 'all');
            wp_enqueue_style('cgm-gifts-templates-css', plugin_dir_url(__FILE__) . '../assets/css/gifts-templates.min.css', array(), $version, 'all');
        }
        wp_enqueue_style('cgm-notifications-css', plugin_dir_url(__FILE__) . '../assets/css/notifications.min.css', array(), $version, 'all');

        wp_enqueue_script('cgm-product-counter-js', plugin_dir_url(__FILE__) . '../assets/js/cart-counter.js', array('jquery'), $version, true); // Dodano zależność od jQuery
        wp_localize_script('cgm-product-counter-js', 'product_counter_params', array(
            'counter_class' => get_option('cgm_counter_class', '')
        ));
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script('cart-variations', plugin_dir_url(__FILE__) . '../assets/js/cart-variations.js', array('jquery'), '1.0.0', true);
    }

    public function add_gift_to_cart()
    {

        $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : '';
        $level_index = isset($_POST['level_index']) ? $_POST['level_index'] : '';

        if (!$product_id || $level_index == '') {
            wp_send_json_error(array('message' => 'Nie ustawiono wymaganych parametrów'));
            return;
        }


        // pobieranie ceny giftu z opcji
        $gift_price = $this->get_gift_price($product_id, $level_index);
        $gift_name = $this->get_gift_name($product_id, $level_index);


        // Sprawdzanie typu produktu po jego ID
        $product = wc_get_product($product_id);

        $product_id_test = $product->get_id();



        if (!$product) {
            wp_send_json_error(array('message' => 'Nie znaleziono produktu'));
            return;
        }
        
        $product_type = $product->get_type();
        // Jeśli produkt jest wariantem, pobierz ID rodzica
        if ($product_type === 'variation') {
            $parent_id = $product->get_parent_id();
            $variation_id = $product_id;
            $variation_data = $product->get_variation_attributes();
        } else {
            $parent_id = $product_id;
            $variation_id = 0;
            $variation_data = array();
        }
        
  

        if ($gift_price == null) {
            wp_send_json_error(array('message' => 'Nie ustawiono wymaganych parametrów'));
            return;
        }

        $cart_total_value = $this->get_cart_total_value(true);

        // sprawdzanie czy gift jest w aktywnych poziomach
        $active_levels = $this->get_active_cart_gifts_levels($cart_total_value);

        //sprawdzanie czy dozwolona ilosc giftow z poziomu
        if (!$this->check_allowed_gifts_count_from_level($level_index)) {


            $this->change_gift_in_cart($product_id, $level_index);
            wc_add_notice(__('Gift has been added to cart', 'custom-cart-gifts-modal'), 'success');
            wp_send_json_success();
            return;
        }

        if (!in_array($level_index, $active_levels)) {
            wc_add_notice(__('You cannot add this product to cart', 'custom-cart-gifts-modal'), 'error');
            wp_send_json_error(array('message' => 'Nie udało się dodać do koszyka zła cena gifta'));
            return;
        }

        
        
        if ($product_type === 'variation') {
            $added_to_cart = WC()->cart->add_to_cart($parent_id, 1, $variation_id, $variation_data, array('is_gift' => true, 'level_index' => $level_index, 'gift_price' => $gift_price, 'gift_name' => $gift_name));
        } else {
            $added_to_cart = WC()->cart->add_to_cart($product_id, 1, 0, array(), array('is_gift' => true, 'level_index' => $level_index, 'gift_price' => $gift_price, 'gift_name' => $gift_name));
        }


        WC()->cart->calculate_totals();
        wc_add_notice(__('Gift has been added to cart', 'custom-cart-gifts-modal'), 'success');
        wp_send_json_success();
    }

    public function remove_gift_from_cart()
    {
        $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : '';
        $level_index = isset($_POST['level_index']) ? $_POST['level_index'] : '';

        if (!$product_id || $level_index == '') {
            wp_send_json_error(array('message' => 'Nie ustawiono wymaganych parametrów'));
            return;
        }

        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (
                isset($cart_item['is_gift']) && $cart_item['is_gift'] == true
                && $cart_item['product_id'] == $product_id
                && $cart_item['level_index'] == $level_index
            ) {

                WC()->cart->remove_cart_item($cart_item_key);
                WC()->cart->calculate_totals();
                wc_add_notice(__('Gift has been removed from cart', 'custom-cart-gifts-modal'), 'success');
                wp_send_json_success();
            }
        }

        wp_send_json_error(array('message' => 'Nie znaleziono produktu w koszyku'));
    }

    public function update_gifts_prices()
    {
        $cart = WC()->cart;

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['is_gift']) && $cart_item['is_gift'] == true) {
                if($cart_item['variation_id'] != 0){
                    $new_price = $this->get_gift_price($cart_item['variation_id'], $cart_item['level_index']);
                    $cart_item['data']->set_price($new_price);
                }else{
                    $new_price = $this->get_gift_price($cart_item['product_id'], $cart_item['level_index']);
                    $cart_item['data']->set_price($new_price);
                }
            }
        }
    }

    public function update_cross_sells_prices()
    {

        if (did_action('woocommerce_calculate_totals') > 4) {
            return;
        }
        $cart = WC()->cart;

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {

            if (isset($cart_item['is_cross_sell']) && !empty($cart_item['is_cross_sell']) && isset($cart_item['level_index'])) {

                $product_id = $cart_item['product_id'];
                $level_index = $cart_item['level_index'];

                $product = wc_get_product($cart_item['product_id']);

                if ($product && $product->is_type('variable')) {
                    if (isset($cart_item['variation_id']) && !empty($cart_item['variation_id'])) {
                        $product_id = $cart_item['variation_id'];
                    }
                }

                $cart_previous_price = wc_get_product($product_id)->get_price();
                $cross_sell_price = $this->get_cross_sell_price($product_id, $level_index);

                WC()->cart->cart_contents[$cart_item_key]['prev_price'] = $cart_previous_price;
                $cart_item['data']->set_price($cross_sell_price);
            }
        }
    }

    public function gifts_update_cart_items($cart)
    {

        if (did_action('woocommerce_calculate_totals') > 2) {
            return;
        }

        
        $active_levels = $this->get_active_cart_gifts_levels();
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            
            $is_gift = isset($cart_item['is_gift']) ? $cart_item['is_gift'] : false;
            
            if ($is_gift) {
                
              
          
                $level_index = $cart_item['level_index'];
                $product_id = $cart_item['product_id'];
                $product_type = $cart_item['product_type'];
                $variation_id = $cart_item['variation_id'];

                if (!empty($variation_id)) {
                    $product = wc_get_product($variation_id);
                } else {
                    $product = wc_get_product($product_id);
                }
                $is_in_stock = $product->is_in_stock();

                if (!in_array($level_index, $active_levels) || !$is_in_stock) {
                    WC()->cart->remove_cart_item($cart_item_key);
                    if (!$is_in_stock) {
                        wc_add_notice(__('Gift has been removed from cart because it is out of stock.', 'custom-cart-gifts-modal'), 'notice');
                    } else {
                        wc_add_notice(__('Gift has been removed from cart because you no longer meet the requirements.', 'custom-cart-gifts-modal'), 'notice');
                    }
                } else {
                    $cart->set_quantity($cart_item_key, 1);
                }
            }
        }

        WC()->cart->calculate_totals();
    }
    public function update_cross_sells_items($cart)
    {

        if (did_action('woocommerce_calculate_totals') > 4) {
            return;
        }

        $active_levels = $this->get_active_cart_gifts_levels();

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {

            $is_cross_sell = isset($cart_item['is_cross_sell']) ? $cart_item['is_cross_sell'] : false;

            if ($is_cross_sell) {

                $level_index = $cart_item['level_index'];
                $product_id = $cart_item['product_id'];

                if (!in_array($level_index, $active_levels)) {
                    WC()->cart->remove_cart_item($cart_item_key);
                    wc_add_notice(__('Product has been removed from cart because you no longer meet the requirements.', 'custom-cart-gifts-modal'), 'notice');
                } else {
                    $cart->set_quantity($cart_item_key, 1);
                }
            }
        }

        WC()->cart->calculate_totals();
    }

    public function get_cross_sell_price($product_id, $level_index)
    {

        $cart_gifts_levels = get_option('cgm_cart_gifts_levels', array());
        $price = null;

        if (isset($cart_gifts_levels[$level_index]['crossSellProducts'])) {


            foreach ($cart_gifts_levels[$level_index]['crossSellProducts'] as $product) {

                if ($product['id'] == $product_id) {
                    $discount_value = $product['discount_value'];
                    $discount_type = $product['discount_type'];

                    $product = wc_get_product($product_id);
                    if ($product) {
                        $regular_price = $product->get_price();


                        if ($discount_type === 'percent') {
                            $price = $regular_price * (1 - ($discount_value / 100));
                        } else {
                            $price = $regular_price - $discount_value;
                        }

                        if ($price <= 0) {
                            $price = 0;
                        }
                    }

                    return $price;
                }
            }
        }
    }

    public function get_gift_price($product_id, $level_index)
    {
        
 

        $cart_gifts_levels = get_option('cgm_cart_gifts_levels', array());
        $price = null;
   

        if (isset($cart_gifts_levels[$level_index]['products'])) {
           
            foreach ($cart_gifts_levels[$level_index]['products'] as $product) {

                if ($product['id'] == $product_id) {
                    $price = $product['cena'];
                   
                    break;
                }
            }
        }

        return $price;
    }

    public function get_gift_name($product_id, $level_index)
    {
        $cart_gifts_levels = get_option('cgm_cart_gifts_levels', array());
        $name = '';
   
        if (isset($cart_gifts_levels[$level_index]['products'])) {
            foreach ($cart_gifts_levels[$level_index]['products'] as $product) {
                if ($product['id'] == $product_id) {
                    $name = $product['nazwa'];
                    break;
                }
            }
        }
        return $name;
    }

    public function get_active_cart_gifts_levels()
    {

        $cart_total_value = $this->get_cart_total_value(true);

        $cart_gifts_levels = get_option('cgm_cart_gifts_levels', array());

        $active_levels = array();

        foreach ($cart_gifts_levels as $level_index => $level) {
            if ($cart_total_value >= $level['prog']) {
                $active_levels[] = $level_index;
            }
        }

        return $active_levels;
    }

    public function get_cart_total_value($without_gifs_and_cross_sells = false)
    {

        $cart = WC()->cart;
        $subtotal_with_tax = $cart->get_subtotal() + $cart->get_subtotal_tax();
        $cart_total_value = $subtotal_with_tax;

        if ($without_gifs_and_cross_sells) {
            foreach ($cart->get_cart() as $cart_item) {
                if (isset($cart_item['is_gift']) && $cart_item['is_gift'] == true) {
                    $cart_total_value -= ($cart_item['line_total'] + $cart_item['line_tax']);
                }
                if (isset($cart_item['is_cross_sell']) && $cart_item['is_cross_sell'] != '') {
                    $cart_total_value -= ($cart_item['line_total'] + $cart_item['line_tax']);
                }
            }
        }

        return $cart_total_value;
    }

    public function check_allowed_gifts_count_from_level($level_index)
    {
        $cart_gifts_levels = get_option('cgm_cart_gifts_levels', array());
        $allowed_gifts = isset($cart_gifts_levels[$level_index]['ilosc']) ? $cart_gifts_levels[$level_index]['ilosc'] : 0;

        $gifts_count = 0;
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['is_gift']) && $cart_item['is_gift'] == true && isset($cart_item['level_index']) && $cart_item['level_index'] == $level_index) {
                $gifts_count++;
            }
        }

        return $gifts_count < $allowed_gifts;
    }

    public function change_gift_in_cart($product_id, $level_index)
    {
        $cart = WC()->cart;
        $first_gift_key = '';
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (
                isset($cart_item['is_gift']) &&
                $cart_item['is_gift'] == true &&
                isset($cart_item['level_index']) &&
                $cart_item['level_index'] == $level_index
            ) {
                $first_gift_key = $cart_item_key;
                break;
            }
        }

        if (!empty($first_gift_key)) {
            $cart->remove_cart_item($first_gift_key);
        }

    
        $gift_price = $this->get_gift_price($product_id, $level_index);
        $gift_name = $this->get_gift_name($product_id, $level_index);

        if ($gift_price == null) {
            $cart->remove_cart_item($first_gift_key);
        }

        // Dodaj nowy gift
        $cart_item_data = array(
            'is_gift' => true,
            'level_index' => $level_index,
            'gift_price' => $gift_price,
            'gift_name' => $gift_name
        );

        $added_to_cart = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);

        return $added_to_cart;
    }

    public function ajax_update_cart_variation()
    {
        check_ajax_referer('ajax_object_nonce', 'nonce');

        $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field($_POST['cart_item_key']) : '';
        $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;

        if (!$cart_item_key || !$variation_id) {
            wp_send_json_error(array('message' => 'Nieprawidłowe dane'));
            return;
        }

        $cart = WC()->cart;
        $cart_item = $cart->get_cart_item($cart_item_key);

        if (!$cart_item) {
            wp_send_json_error(array('message' => 'Nie znaleziono produktu w koszyku'));
            return;
        }

        // Pobierz ilość z aktualnego produktu
        $quantity = $cart_item['quantity'];

        // Usuń stary produkt
        $cart->remove_cart_item($cart_item_key);

        // Dodaj nowy wariant
        $new_cart_item_key = $cart->add_to_cart(
            $variation_id,
            $quantity,
            0,
            array(),
            array(
                'variation' => array(),
                'variation_id' => $variation_id
            )
        );

        if ($new_cart_item_key) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => 'Nie udało się zaktualizować wariantu'));
        }
    }


}