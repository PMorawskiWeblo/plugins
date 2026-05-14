<?php

// add_action('woocommerce_before_cart_table',function(){
//     if(current_user_can('administrator')) {
//         include('new-cart-gift-counter.php');
//     }
// });
if (get_field('turn_off_gifts', 'option') != 1) :
    add_action('woocommerce_after_cart_table', function () {

        include('new-cart-gift-template.php');
    }, 1);
endif;
// add_action('woocommerce_before_single_product',function(){
//     include('new-cart-gift-product-template.php');
// });

add_action('woocommerce_after_cart', function () {
    include('new-cart-crosssell-template.php');
});


function get_eligible_products_count($cart = null)
{
    if ($cart == null)
        $cart = WC()->cart;

    $count = 0;
    foreach ($cart->get_cart() as $cart_item) {
        // Skip if it's a gift product
        if (isset($cart_item['custom_price'])) {
            continue;
        }

        // Get product categories
        $product = $cart_item['data'];
        $product_cats = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'slugs'));

        // Skip if product is in accessories or samples category
        if (in_array('akcesoria', $product_cats) || in_array('probki', $product_cats)) {
            continue;
        }

        // Add quantity of eligible product
        $count += $cart_item['quantity'];
    }

    return $count;
}

function get_gift_cart_total($cart = null)
{
    if ($cart == null)
        $cart = WC()->cart;
    $total_with_tax = $cart->total;
    $shipping_total = $cart->get_shipping_total();
    $shipping_tax = array_sum($cart->get_shipping_taxes());
    $total_without_shipping = (float) ($total_with_tax - $shipping_total - $shipping_tax);
    $sumakoszyka = (float) $total_without_shipping;
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['isgift'])) {
            $gift = round($cart_item['line_total'] + $cart_item['line_tax'], 2);
            $sumakoszyka = $sumakoszyka - $gift;
        }
    }

    return $sumakoszyka;
}

function check_gift_threshold($level)
{
    $threshold_type = get_field('lvl' . $level . '-threshold-type', 'option');
    $price_prog = (float)safe_wmc_get_price(get_field('lvl' . $level . '-prog', 'option'));
    $qty_prog = (int)get_field('lvl' . $level . '-qty-prog', 'option');

    $cart_total = (float)get_gift_cart_total();
    $products_count = (int)get_eligible_products_count();

    switch ($threshold_type) {
        case 'price':
            return $cart_total >= $price_prog;
        case 'quantity':
            return $products_count >= $qty_prog;
        case 'both':
            return $cart_total >= $price_prog && $products_count >= $qty_prog;
        default:
            return false;
    }
}

function get_gift_level_in_cart()
{
    $giftlevel = '';
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['isgift'])) {
            $giftlevel = $cart_item['level'];
        }
    }
    return $giftlevel;
}

function is_gift_in_cart()
{
    $cartkeys = array();
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['isgift'])) {
            return true;
        }
    }
    return false;
}
function get_gifts_in_cart($level)
{
    $cartkeys = array();
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['isgift']) && $cart_item['level'] == $level) {
            $cartkeys[] = $cart_item_key;
        }
    }
    return $cartkeys;
}
function get_gift_levels()
{
    $giftids = array();
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['isgift'])) {
            $giftids[$cart_item['giftid']] = $cart_item_key;
        }
    }

    $levels = array();
    $levels[0]['nazwa'] = get_field('lvl1-nazwa', 'option');
    $levels[0]['prog'] = (float)safe_wmc_get_price(get_field('lvl1-prog', 'option'));
    $levels[0]['qty_prog'] = (int)get_field('lvl1-qty-prog', 'option');
    $levels[0]['threshold_type'] = get_field('lvl1-threshold-type', 'option');
    $levels[0]['required_coupon'] = false;
    $levels[0]['produkty'] = get_field('lvl1-produkty', 'option');
    if (!is_array($levels[0]['produkty'])) {
        $levels[0]['produkty'] = array();
    }

    $levels[1]['nazwa'] = get_field('lvl2-nazwa', 'option');
    $levels[1]['prog'] = (float)safe_wmc_get_price(get_field('lvl2-prog', 'option'));
    $levels[1]['qty_prog'] = (int)get_field('lvl2-qty-prog', 'option');
    $levels[1]['threshold_type'] = get_field('lvl2-threshold-type', 'option');
    $levels[1]['required_coupon'] = get_field('lvl2-required_coupon_code', 'option');
    $levels[1]['produkty'] = get_field('lvl2-produkty', 'option');
    if (!is_array($levels[1]['produkty'])) {
        $levels[1]['produkty'] = array();
    }

    $levels[2]['nazwa'] = get_field('lvl3-nazwa', 'option');
    $levels[2]['prog'] = (float)safe_wmc_get_price(get_field('lvl3-prog', 'option'));
    $levels[2]['qty_prog'] = (int)get_field('lvl3-qty-prog', 'option');
    $levels[2]['threshold_type'] = get_field('lvl3-threshold-type', 'option');
    $levels[2]['required_coupon'] = get_field('lvl3-required_coupon_code', 'option');
    $levels[2]['produkty'] = get_field('lvl3-produkty', 'option');
    if (!is_array($levels[2]['produkty'])) {
        $levels[2]['produkty'] = array();
    }

    $coupons = WC()->cart->get_applied_coupons();

    foreach ($coupons as $coupon) {
        $coupon = new WC_Coupon($coupon);
        $promo = get_field('dodaj_gifty', $coupon->get_id());
        if ($promo) {
            $levels0prod = get_field('poziom1-produkty', $coupon->get_id());
            $levels1prod = get_field('poziom2-produkty', $coupon->get_id());
            $levels2prod = get_field('poziom3-produkty', $coupon->get_id());
            if ($levels0prod && is_array($levels0prod)) {
                foreach ($levels0prod as $key => $prod) {
                    $levels0prod[$key]['influ'] = true;
                    $levels0prod[$key]['influ-code'] = $coupon->get_id();
                }
                $levels[0]['produkty'] = array_merge($levels0prod, $levels[0]['produkty']);
            }
            if ($levels1prod && is_array($levels1prod)) {
                foreach ($levels1prod as $key => $prod) {
                    $levels1prod[$key]['influ'] = true;
                    $levels1prod[$key]['influ-code'] = $coupon->get_id();
                }
                $levels[1]['produkty'] = array_merge($levels1prod, $levels[1]['produkty']);
            }
            if ($levels2prod && is_array($levels2prod)) {
                foreach ($levels2prod as $key => $prod) {
                    $levels2prod[$key]['influ'] = true;
                    $levels2prod[$key]['influ-code'] = $coupon->get_id();
                }
                $levels[2]['produkty'] = array_merge($levels2prod, $levels[2]['produkty']);
            }
        }
    }

    foreach ($levels as $key0 => $level) {
        $influcount = 0;
        $count = 0;
        if (!isset($level['produkty']) || !is_array($level['produkty'])) {
            $levels[$key0]['produkty'] = array();
            continue;
        }
        foreach ($level['produkty'] as $key => $prod) {
            if (isset($prod['influ'])) {
                $id = strtolower($level['nazwa']) . '-influ-' . $influcount;
                $levels[$key0]['produkty'][$key]['id'] = $id;
                if (array_key_exists($id, $giftids))
                    $levels[$key0]['produkty'][$key]['incart'] = $giftids[$id];
                $influcount++;
            } else {
                $id = strtolower($level['nazwa']) . '-' . $count;
                $levels[$key0]['produkty'][$key]['id'] = $id;
                if (array_key_exists($id, $giftids))
                    $levels[$key0]['produkty'][$key]['incart'] = $giftids[$id];
                $count++;
            }
        }
    }
    return $levels;
}

add_action('wp_ajax_nc_add_to_cart_gift', 'new_cart_add_to_cart_gift');
add_action('wp_ajax_nopriv_nc_add_to_cart_gift', 'new_cart_add_to_cart_gift');
function new_cart_add_to_cart_gift()
{
    $levels = get_gift_levels();
    $giftid = $_POST['giftid'];
    if ($giftid) {
        $found = false;
        foreach ($levels as $level) {
            foreach ($level['produkty'] as $prod) {
                if ($giftid == $prod['id']) {
                    $giftlevel = $level;
                    $giftprod = $prod;
                    if (isset($prod['influ']))
                        $giftcode = $prod['influ-code'];
                    $found = true;
                    break;
                }
            }
            if ($found)
                break;
        }
    }

    $name = $giftlevel['nazwa'];
    $prog = (float)$giftlevel['prog'];
    $qty_prog = (int)$giftlevel['qty_prog'];
    $threshold_type = $giftlevel['threshold_type'];

    foreach (get_gifts_in_cart($giftlevel['nazwa']) as $key)
        WC()->cart->remove_cart_item($key);

    if ($giftprod['produkt']->ID)
        $prodid = $giftprod['produkt']->ID;
    else
        $prodid = $giftprod['produkt'];
    $prodprice = $giftprod['cena'];
    $data = array(
        "giftid" => $giftid,
        "isgift" => true,
        "prog" => $prog,
        "qty_prog" => $qty_prog,
        "threshold_type" => $threshold_type,
        "level" => $name,
        "giftcode" => $giftcode,
        "custom_price" => $prodprice,
        "gift_name" => $giftprod['nazwa'],
        "gift_slogan" => $giftprod['slogan'],
        "gift_level" => $name,
        // "required_coupon" => $giftlevel['required_coupon']
    );



    $cart_total = (float)get_gift_cart_total();
    $products_count = (int)get_eligible_products_count();
    $can_add = false;

    switch ($threshold_type) {
        case 'price':
            $can_add = ($cart_total >= $prog);
            $error_message = __("Cart total is too low", "gift-in-cart-plugin");
            break;
        case 'quantity':
            $can_add = ($products_count >= $qty_prog);
            $error_message = __("Not enough eligible products in cart", "gift-in-cart-plugin");
            break;
        case 'both':
            $can_add = ($cart_total >= $prog && $products_count >= $qty_prog);
            $error_message = sprintf(
                __("Cart total (%s) or product quantity (%d) is too low. Required: %s and %d", "gift-in-cart-plugin"),
                wc_price($cart_total),
                $products_count,
                wc_price($prog),
                $qty_prog
            );
            break;
    }

    if ($can_add) {
        $info = __("We added a gift to your order", "gift-in-cart-plugin");
        WC()->cart->add_to_cart($prodid, 1, null, null, $data);
        wc_add_notice($info, 'success');
        wp_send_json_success($info);
    } else {
        wc_add_notice($error_message, 'error');
        wp_send_json_error($error_message);
    }
}

add_filter('woocommerce_coupon_get_discount_amount', 'limit_coupon_discount_for_gratis_items', 10, 5);

function limit_coupon_discount_for_gratis_items($discount, $discounting_amount, $cart_item, $single, $coupon)
{
    // Check if the cart item has the 'gratis' meta and if it is true
    if (isset($cart_item['custom_price']) && $cart_item['custom_price']) {
        // Set the discount to 0 for this item
        $discount = 0;
    }
    return $discount;
}



add_action('wp_ajax_nc_remove_from_cart_gift', 'new_cart_remove_from_cart_gift');
add_action('wp_ajax_nopriv_nc_remove_from_cart_gift', 'new_cart_remove_from_cart_gift');
function new_cart_remove_from_cart_gift()
{
    $cartkey = $_POST['cartkey'];
    if ($cartkey) {
        $info = __("Gift removed from cart", "gift-in-cart-plugin");
        WC()->cart->remove_cart_item($cartkey);
        wc_add_notice($info, 'notice');
        wp_send_json_success($info);
    } else {
        $info = __("Gift not found in cart", "gift-in-cart-plugin");
        wc_add_notice($info, 'error');
        wp_send_json_error($info);
    }
}

add_action('woocommerce_before_calculate_totals', 'new_cart_set_custom_cart_gift', 10, 1);
function new_cart_set_custom_cart_gift($cart)
{
    if (is_admin() && ! defined('DOING_AJAX'))
        return;

    // Required since Woocommerce version 3.2 for cart items properties changes
    if (did_action('woocommerce_before_calculate_totals') >= 2)
        return;

    // Loop through cart items
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['custom_price'])) {
            $cart_item['data']->set_price($cart_item['custom_price']);
        }
        if (isset($cart_item['cena_obslugi'])) {
            $cart_item['data']->set_price($cart_item['cena_obslugi']);
        }
        if (isset($cart_item['required_coupon'])) {
            //  wp_mail('kontakt@msliwka.pl','test',$cart_item['required_coupon']);
            $coupons = WC()->cart->get_applied_coupons();
            //    wp_mail('kontakt@msliwka.pl','test2',json_encode($coupons,true));
            if (!in_array(strtolower($cart_item['required_coupon']), $coupons)) {
                $cart->set_quantity($cart_item_key, 0, true);
                $cart->remove_cart_item($cart_item_key);
                //  wp_mail('kontakt@msliwka.pl','test4',$cart_item_key);
            }
        }
    }
}
add_action('woocommerce_cart_updated', 'new_cart_check_cart_condition');

function new_cart_check_cart_condition()
{
    if (!isset($_POST['check_cart_condition'])) {
        $_POST['check_cart_condition'] = true;

        $coupons = WC()->cart->get_applied_coupons();
        foreach ($coupons as $key => $value)
            $coupons[$key] = strtolower($value);

        $cart_total = get_gift_cart_total();
        $products_count = get_eligible_products_count();

        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['custom_price'])) {
                $remove_item = false;

                switch ($cart_item['threshold_type']) {
                    case 'price':
                        $remove_item = ($cart_total < $cart_item['prog']);
                        break;
                    case 'quantity':
                        $remove_item = ($products_count < $cart_item['qty_prog']);
                        break;
                    case 'both':
                        $remove_item = ($cart_total < $cart_item['prog'] || $products_count < $cart_item['qty_prog']);
                        break;
                }

                if ($remove_item) {
                    WC()->cart->remove_cart_item($cart_item_key);
                } else {
                    if (isset($cart_item['giftcode'])) {
                        if (!in_array(strtolower(get_the_title($cart_item['giftcode'])), $coupons)) {
                            WC()->cart->remove_cart_item($cart_item_key);
                        }
                    } else {
                        WC()->cart->set_quantity($cart_item_key, 1, true);
                    }
                }
            }
        }

        unset($_POST['check_cart_condition']);
    }
}

// Add this code to your theme's functions.php file or a custom plugin