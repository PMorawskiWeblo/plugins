<?php

/**
 * Review order table
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/review-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 5.2.0
 */

defined('ABSPATH') || exit;
?>

<div class="shop_table woocommerce-checkout-review-order-table">

    <div class="review-order-body">
        <?php
        do_action('woocommerce_review_order_before_cart_contents');

        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);

            if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key)) {
        ?>
                <div
                    class="review-order-item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item woocommerce-cart-form__cart-item d-flex', $cart_item, $cart_item_key)); ?>">
                    <div class="cart-item-left">
                        <div class="product-thumbnail">
                            <a href="<?php echo esc_url($_product->get_permalink()); ?>">
                                <?php echo $_product->get_image(); ?>
                            </a>
                        </div>
                    </div>
                    <div class="cart-item-middle col">
                        <?php
                        $product_id = $cart_item['product_id'];
                        $linie_terms = get_the_terms($product_id, 'linia');
                        if (!is_wp_error($linie_terms) && !empty($linie_terms)) {
                            $linie_names = array();
                            foreach ($linie_terms as $term) {
                                $linie_names[] = esc_html($term->name);
                            }
                            echo '<div class="cart-item-linia">' . implode(', ', $linie_names) . '</div>';
                        }
                        ?>
                        <div class="review-order-item-name product-name">
                            <?php echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key)) . '&nbsp;'; ?>
                            <?php
                            if ($cart_item['quantity'] > 1) {
                                echo apply_filters(
                                    'woocommerce_checkout_cart_item_quantity',
                                    ' <strong class="product-quantity">' . sprintf('&times;&nbsp;%s', $cart_item['quantity']) . '</strong>',
                                    $cart_item,
                                    $cart_item_key
                                ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            }
                            ?>
                            <?php echo wc_get_formatted_cart_item_data($cart_item); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                            ?>
                        </div>
                        <div class="wrap_product_price_att">
                            <div class="review-order-item-total product-total">
                                <?php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                ?>
                            </div>
                            <?php
                            $capacity = $_product->get_attribute('pa_pojemnosc');
                            if (!empty($capacity)) {
                                echo  '<div class="wrap_capacity">';
                                echo '<span class="capacity-separator">•</span> <span class="product-capacity">' . esc_html($capacity) . '</span>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>

                </div>
        <?php
            }
        }

        do_action('woocommerce_review_order_after_cart_contents');
        ?>
    </div>
    <div class="review-order-footer">

        <div class="review-order-footer-item cart-subtotal">
            <div class="review-order-footer-label"><?php esc_html_e('Products', 'weblo-custom-checkout'); ?></div>
            <div class="review-order-footer-value"><?php wc_cart_totals_subtotal_html(); ?></div>
        </div>

        <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
            <?php do_action('woocommerce_review_order_before_shipping'); ?>
            <?php
            // Pobierz wybraną metodę dostawy
            $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
            $packages = WC()->shipping()->get_packages();
            $shipping_displayed = false;

            foreach ($packages as $i => $package) {
                $chosen_method = isset($chosen_shipping_methods[$i]) ? $chosen_shipping_methods[$i] : '';
                $available_methods = $package['rates'];

                if ($chosen_method && isset($available_methods[$chosen_method])) {
                    $method = $available_methods[$chosen_method];
                    $shipping_displayed = true;
            ?>
                    <div class="review-order-footer-item shipping">
                        <div class="review-order-footer-label"><?php esc_html_e('Delivery', 'weblo-custom-checkout'); ?></div>
                        <div class="review-order-footer-value"><?php echo wc_price($method->get_cost()); ?></div>
                    </div>
                <?php
                    break; // Wyświetl tylko pierwszą wybraną metodę
                }
            }

            // Jeśli nie ma wybranej metody, użyj standardowej funkcji WooCommerce
            if (!$shipping_displayed) {
                ?>
                <div class="review-order-footer-item shipping">
                    <div class="review-order-footer-label"><?php esc_html_e('Delivery', 'weblo-custom-checkout'); ?></div>
                    <div class="review-order-footer-value"><?php wc_cart_totals_shipping_html(); ?></div>
                </div>
            <?php
            }
            ?>
            <?php do_action('woocommerce_review_order_after_shipping'); ?>
        <?php endif; ?>

        <?php
        $discount_total = 0;
        foreach (WC()->cart->get_coupons() as $coupon) {
            $discount_total += $coupon->get_amount();
        }
        if ($discount_total > 0) : ?>
            <div class="review-order-footer-item cart-discount">
                <div class="review-order-footer-label">
                    <?php esc_html_e('Discount', 'weblo-custom-checkout'); ?>
                </div>
                <div class="review-order-footer-value gap-1">
                    - <?php echo wc_price(WC()->cart->get_discount_total()); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
        // Shipping section removed - it's rendered in custom shippings_payments_wrapper block.
        // if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : 
        ?>
        <?php // do_action('woocommerce_review_order_before_shipping'); 
        ?>
        <?php // wc_cart_totals_shipping_html(); 
        ?>
        <?php // do_action('woocommerce_review_order_after_shipping'); 
        ?>
        <?php // endif; 
        ?>

        <?php foreach (WC()->cart->get_fees() as $fee) : ?>
            <div class="review-order-footer-item fee">
                <div class="review-order-footer-label"><?php echo esc_html($fee->name); ?></div>
                <div class="review-order-footer-value"><?php wc_cart_totals_fee_html($fee); ?></div>
            </div>
        <?php endforeach; ?>

        <?php if (wc_tax_enabled() && ! WC()->cart->display_prices_including_tax()) : ?>
            <?php if ('itemized' === get_option('woocommerce_tax_total_display')) : ?>
                <?php foreach (WC()->cart->get_tax_totals() as $code => $tax) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited 
                ?>
                    <div class="review-order-footer-item tax-rate tax-rate-<?php echo esc_attr(sanitize_title($code)); ?>">
                        <div class="review-order-footer-label"><?php echo esc_html($tax->label); ?></div>
                        <div class="review-order-footer-value"><?php echo wp_kses_post($tax->formatted_amount); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="review-order-footer-item tax-total">
                    <div class="review-order-footer-label"><?php echo esc_html(WC()->countries->tax_or_vat()); ?></div>
                    <div class="review-order-footer-value"><?php wc_cart_totals_taxes_total_html(); ?></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php do_action('woocommerce_review_order_before_order_total'); ?>

        <div class="review-order-footer-item order-total">
            <div class="review-order-footer-label order-total-label">
                <div class="review-order-footer-label_top"><?php esc_html_e('Total sum', 'weblo-custom-checkout'); ?>
                </div>
                <div class="review-order-footer-label_bottom">
                    <?php
                    // Obliczanie podatku od zamówienia
                    $tax_total = 0;
                    if (wc_tax_enabled()) {
                        if (method_exists(WC()->cart, 'get_taxes_total')) {
                            // WooCommerce 5.0+
                            $tax_total = WC()->cart->get_taxes_total();
                        } else {
                            // Fallback dla starszych wersji WooCommerce
                            $cart_taxes     = WC()->cart->get_cart_contents_taxes();
                            $shipping_taxes = WC()->cart->get_shipping_taxes();
                            foreach ($cart_taxes as $amount) {
                                $tax_total += floatval($amount);
                            }
                            foreach ($shipping_taxes as $amount) {
                                $tax_total += floatval($amount);
                            }
                        }
                    }
                    ?>
                    <?php if ($tax_total > 0) : ?>
                        <span>
                            <?php
                            printf(
                                esc_html__('(Total with tax: %s)', 'weblo-custom-checkout'),
                                wc_price($tax_total)
                            );
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="review-order-footer-value"><?php wc_cart_totals_order_total_html(); ?></div>
        </div>

        <?php do_action('woocommerce_review_order_after_order_total'); ?>

    </div>
</div>