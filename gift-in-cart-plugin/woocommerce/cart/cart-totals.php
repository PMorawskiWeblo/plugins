<?php

/**
 * Cart totals
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-totals.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woo.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 2.3.6
 */

defined('ABSPATH') || exit;

?>
<?php if (wc_coupons_enabled()) { ?>



<div class="coupon-form custom_cart_coupon_form">
    <div class="custom_cart_coupon_form_wrapper d-flex justify-content-between align-items-center">
        <div class="custom_cart_coupon_form_wrapper_title uppercase">
            <?= __('Discount code', 'gift-in-cart-plugin'); ?>
        </div>
        <!-- <div class="custom_cart_coupon_form_wrapper_button d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path d="M0 7H14M7 0V14" stroke="#47170D" stroke-width="1.5" stroke-linejoin="round" />
            </svg>
            <span><?= __('Add', 'gift-in-cart-plugin'); ?></span>
        </div> -->
    </div>


    <div class="coupon-inputs coupon-inputs-shown d-flex"
        data-content="<?php _e('Enter discount code', 'gift-in-cart-plugin'); ?>">
        <div class="form_input col">
            <label for="coupon_code"
                class="code_label uppercase"><?php _e('Coupon code', 'gift-in-cart-plugin'); ?></label>
            <input type="text" name="coupon_code" class="input-text col" id="coupon_code" value=""
                placeholder="<?php esc_attr_e('Coupon code', 'gift-in-cart-plugin'); ?>"
                aria-label="<?php esc_attr_e('Coupon code', 'gift-in-cart-plugin'); ?>" />
        </div>
        <button type="submit"
            class="d-flex justify-content-center align-items-center btn btn-outline btn_outline btn-outline <?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"
            name="apply_coupon"
            value="<?php esc_attr_e('Apply', 'gift-in-cart-plugin'); ?>"><?php esc_html_e('Apply', 'gift-in-cart-plugin'); ?></button>
    </div>
    <?php do_action('woocommerce_cart_coupon'); ?>

    <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
    <div class="cart-discount d-flex justify-content-between coupon-<?php echo esc_attr(sanitize_title($code)); ?>">
        <label><?php _e('Discount applied', 'gift-in-cart-plugin'); ?>: <strong>
                <?php
                        echo strtoupper($coupon->get_code());
                        // if ($coupon->get_discount_type() === 'percent') {
                        // 	echo '-' . esc_html($coupon->get_amount()) . '%';
                        // } elseif ($coupon->get_discount_type() === 'fixed_cart' || $coupon->get_discount_type() === 'fixed_product') {
                        // 	echo '-' . wc_price($coupon->get_amount());
                        // } else {
                        // 	echo esc_html($coupon->get_amount());
                        // }
                        ?>
            </strong>
        </label>

    </div>
    <?php endforeach; ?>
    <div class="coupon-code-message" style="display: none;"></div>
</div>
<?php } ?>

<div class="cart_totals <?php echo (WC()->customer->has_calculated_shipping()) ? 'calculated_shipping' : ''; ?>">



    <?php do_action('woocommerce_before_cart_totals'); ?>

    <h4 class="cart_totals_title text-uppercase"><?php esc_html_e('Order summary', 'gift-in-cart-plugin'); ?></h4>

    <div cellspacing="0" class="shop_table shop_table_responsive">

        <div class="cart-products d-flex justify-content-between">
            <label class="cart-products-single-title">
                <?php esc_html_e('Products', 'gift-in-cart-plugin'); ?>
            </label>
            <div class="cart-products-single-price cart_value">
                <?php echo wc_price(WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax()); ?>
            </div>
        </div>



        <?php if (WC()->cart->needs_shipping()) { ?>
        <?php
            $shipping = WC()->cart->get_shipping_total();
            foreach (WC()->cart->get_shipping_taxes() as $tax => $value) {
                $shipping  += $value;
            }
            ?>

        <div class="shipping d-flex justify-content-between">
            <label><?php esc_html_e('Delivery', 'gift-in-cart-plugin'); ?></label>
            <div class="shop_table_value cart_value"><?php echo wc_price($shipping); ?></div>
        </div>

        <?php } ?>

        <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
        <div class="cart-discount d-flex justify-content-between coupon-<?php echo esc_attr(sanitize_title($code)); ?>">
            <label><?php _e('Discount', 'gift-in-cart-plugin'); ?> <span>
                    <?php
                        echo strtoupper($coupon->get_code());
                        // if ($coupon->get_discount_type() === 'percent') {
                        // 	echo esc_html($coupon->get_amount()) . '%';
                        // } elseif ($coupon->get_discount_type() === 'fixed_cart' || $coupon->get_discount_type() === 'fixed_product') {
                        // 	echo '-' . wc_price($coupon->get_amount());
                        // } else {
                        // 	echo esc_html($coupon->get_amount());
                        // }
                        ?>
                </span>
            </label>
            <div class="shop_table_value wc_cart_totals_coupon cart_value"
                data-title="<?php echo esc_attr(wc_cart_totals_coupon_label($coupon, false)); ?>">
                <?php wc_cart_totals_coupon_html($coupon); ?></div>
        </div>
        <?php endforeach; ?>



        <?php foreach (WC()->cart->get_fees() as $fee) : ?>
        <div class="fee d-flex justify-content-between">
            <label><?php echo esc_html($fee->name); ?></label>
            <div class="shop_table_value cart_value" data-title="<?php echo esc_attr($fee->name); ?>">
                <?php wc_cart_totals_fee_html($fee); ?></div>
        </div>
        <?php endforeach; ?>
        <?php echo do_shortcode('[cart_shipping_counter]'); ?>
        <?php
        if (wc_tax_enabled() && ! WC()->cart->display_prices_including_tax()) {
            $taxable_address = WC()->customer->get_taxable_address();
            $estimated_text  = '';

            if (WC()->customer->is_customer_outside_base() && ! WC()->customer->has_calculated_shipping()) {
                /* translators: %s location. */
                $estimated_text = sprintf(' <small>' . esc_html__('(estimated for %s)', 'woocommerce') . '</small>', WC()->countries->estimated_for_prefix($taxable_address[0]) . WC()->countries->countries[$taxable_address[0]]);
            }

            if ('itemized' === get_option('woocommerce_tax_total_display')) {
                foreach (WC()->cart->get_tax_totals() as $code => $tax) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        ?>
        <div class="tax-rate tax-rate-<?php echo esc_attr(sanitize_title($code)); ?>">
            <key><?php echo esc_html($tax->label) . $estimated_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                ?></label>
                <div class="shop_table_value" data-title="<?php echo esc_attr($tax->label); ?>">
                    <?php echo wp_kses_post($tax->formatted_amount); ?></div>
        </div>
        <?php
                }
            } else {
                ?>
        <div class="tax-total">
            <label><?php echo esc_html(WC()->countries->tax_or_vat()) . $estimated_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                            ?></label>
            <div class="shop_table_value" data-title="<?php echo esc_attr(WC()->countries->tax_or_vat()); ?>">
                <?php wc_cart_totals_taxes_total_html(); ?></div>
        </div>

        <?php
            }
        }
        ?>


        <?php do_action('woocommerce_cart_totals_before_order_total'); ?>

        <div class="order-total d-flex justify-content-between">
            <label><?php esc_html_e('Total', 'gift-in-cart-plugin'); ?></label>
            <div class="shop_table_value" data-title="<?php esc_attr_e('Total', 'gift-in-cart-plugin'); ?>">
                <?php wc_cart_totals_order_total_html(); ?></div>
        </div>

        <?php do_action('woocommerce_cart_totals_after_order_total'); ?>

        <?php
        $has_gift = false;
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['isgift']) && $cart_item['isgift']) {
                $has_gift = true;
                break;
            }
        }

        ?>

        <div class="wc-proceed-to-checkout">
            <?php do_action('woocommerce_proceed_to_checkout'); ?>
        </div>
        <?php
        $estimated_delivery_from = get_field('estimated_delivery_from', 'option');
        $estimated_delivery_to = get_field('estimated_delivery_to', 'option');

        function add_business_days($date, $days)
        {
            $weekdays = [1, 2, 3, 4, 5];
            $day = 0;
            while ($day < $days) {
                $date->modify('+1 day');
                if (in_array($date->format('N'), $weekdays)) {
                    $day++;
                }
            }
            return $date;
        }
        function format_polish_date($date)
        {
            $days = [
                'Mon' => 'pon.',
                'Tue' => 'wt.',
                'Wed' => 'śr.',
                'Thu' => 'czw.',
                'Fri' => 'pt.',
                'Sat' => 'sob.',
                'Sun' => 'niedz.'
            ];
            $day_name = $days[$date->format('D')];
            return $day_name . ' ' . $date->format('d.m');
        }

        $today = new DateTime();
        $delivery_from = add_business_days(clone $today, $estimated_delivery_from);
        $delivery_to = add_business_days(clone $today, $estimated_delivery_to);

        $delivery_from_formatted = format_polish_date($delivery_from);
        $delivery_to_formatted = format_polish_date($delivery_to);

        $delivery_info = sprintf('%s - %s', $delivery_from->format('d'), $delivery_to->format('d.m.Y'));
        $delivery_info_days = sprintf(
            __('%d-%d BUSINESS DAYS', 'gift-in-cart-plugin'),
            $estimated_delivery_from,
            $estimated_delivery_to
        );
        ?>

        <?php
        $cart = WC()->cart->get_cart();
        $has_preorder = false;

        foreach ($cart as $cart_item) {
            $product_id = $cart_item['product_id'];
            $preorder = get_post_meta($product_id, 'wb_preorder_show', true);
            if ($preorder === '1') {
                $has_preorder = true;
                break;
            }
        }

        if (!$has_preorder) : ?>
        <div class="delivery_info d-flex">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path
                    d="M12 21.9999V11.9999M12 11.9999L3.29 6.9999M12 11.9999L20.71 6.9999M7.5 4.2699L16.5 9.4199M11 21.7299C11.304 21.9054 11.6489 21.9979 12 21.9979C12.3511 21.9979 12.696 21.9054 13 21.7299L20 17.7299C20.3037 17.5545 20.556 17.3024 20.7315 16.9987C20.9071 16.6951 20.9996 16.3506 21 15.9999V7.9999C20.9996 7.64918 20.9071 7.30471 20.7315 7.00106C20.556 6.69742 20.3037 6.44526 20 6.2699L13 2.2699C12.696 2.09437 12.3511 2.00195 12 2.00195C11.6489 2.00195 11.304 2.09437 11 2.2699L4 6.2699C3.69626 6.44526 3.44398 6.69742 3.26846 7.00106C3.09294 7.30471 3.00036 7.64918 3 7.9999V15.9999C3.00036 16.3506 3.09294 16.6951 3.26846 16.9987C3.44398 17.3024 3.69626 17.5545 4 17.7299L11 21.7299Z"
                    stroke="#6D6059" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <div class="delivery_info_text"><?= __('Estimated delivery:', 'gift-in-cart-plugin'); ?></div>
            <div class="wrap_delivery_info ms-1"><?= $delivery_info_days ?></div>
        </div>
        <?php endif; ?>

        <?php do_action('woocommerce_after_cart_totals'); ?>

    </div>