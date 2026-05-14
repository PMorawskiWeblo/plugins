<?php

/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<!-- <section class="section_breadcums section_breadcums_default">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="breadcrumbs_wrapper">
                    <p id="breadcrumbs">
                        <span>
                            <span>
                                <a href="<?php echo esc_url(wc_get_cart_url()); ?>">
                                    <?php esc_html_e('Cart', 'weblo-custom-checkout'); ?>
                                </a>
                            </span>
                            <span class="breadcrumb-separator" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="14" viewBox="0 0 8 14"
                                    fill="none">
                                    <path d="M0.530273 12.5303L6.53027 6.53027L0.530273 0.530273" stroke="#6D6059"
                                        stroke-width="1.5" stroke-linejoin="round"></path>
                                </svg>
                            </span>
                            <span class="go_to_buyer_details_step step_active">
                                <?php esc_html_e('Buyer details', 'weblo-custom-checkout'); ?>
                            </span>
                            <span class="breadcrumb-separator" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="14" viewBox="0 0 8 14"
                                    fill="none">
                                    <path d="M0.530273 12.5303L6.53027 6.53027L0.530273 0.530273" stroke="#6D6059"
                                        stroke-width="1.5" stroke-linejoin="round"></path>
                                </svg>
                            </span>
                            <span class="go_to_delivery_and_payment_step">
                                <?php esc_html_e('Delivery and payment', 'weblo-custom-checkout'); ?>
                            </span>
                            <span class="breadcrumb-separator" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="14" viewBox="0 0 8 14"
                                    fill="none">
                                    <path d="M0.530273 12.5303L6.53027 6.53027L0.530273 0.530273" stroke="#6D6059"
                                        stroke-width="1.5" stroke-linejoin="round"></path>
                                </svg>
                            </span>
                            <span class="breadcrumb_last_not_active" aria-current="page">
                                <?php esc_html_e('Summary', 'weblo-custom-checkout'); ?>
                            </span>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section> -->

<div class="wrap_checout_content">


    <?php
    do_action('woocommerce_before_checkout_form', $checkout);

    // If checkout registration is disabled and not logged in, the user cannot checkout.
    if (! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in()) {
        echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'weblo-custom-checkout')));
        return;
    }

    $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
    ?>

    <form name="checkout" method="post" class="checkout woocommerce-checkout Weblo_Custom_Checkout_Form"
        action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data"
        aria-label="<?php echo esc_attr__('Checkout', 'weblo-custom-checkout'); ?>">

        <div class="Weblo_Custom_Checkout_Form_Content">
            <div class="Weblo_Custom_Checkout_Form_Content_Left">
                <?php if ($checkout->get_checkout_fields()) : ?>

                    <?php do_action('woocommerce_checkout_before_customer_details'); ?>

                    <div class="customer_details" id="customer_details">
                        <div class="customer_details_billing">
                            <?php do_action('woocommerce_checkout_billing'); ?>
                        </div>

                        <div class="customer_details_shipping ">
                            <?php do_action('woocommerce_checkout_shipping'); ?>
                        </div>
                    </div>

                    <?php do_action('woocommerce_checkout_after_customer_details'); ?>

                <?php endif; ?>

                <div class="shippings_payments_wrapper shippings_payments_wrapper_inactive ">
                    <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
                        <div class="shipping_methods_wrapper checkout_section">
                            <div class="shipping_methods_wrapper_top">
                                <div class="checkout_section_head">
                                    <h4 class="checkout_section_title">
                                        <?php esc_html_e('Shipping details', 'weblo-custom-checkout'); ?>
                                    </h4>
                                    <div class="checkout_section_shipping_details">
                                        <div class="checkout_section_shipping_details_content">
                                            <?php
                                            // Sprawdź domyślną wartość checkboxa "ship to different address"
                                            $ship_to_different_default = apply_filters('woocommerce_ship_to_different_address_checked', 'shipping' === get_option('woocommerce_ship_to_destination') ? 1 : 0);

                                            // Pobierz dane - sprawdź najpierw czy są dane shipping, jeśli nie to użyj billing
                                            $shipping_address_1 = $checkout->get_value('shipping_address_1');
                                            $shipping_postcode = $checkout->get_value('shipping_postcode');
                                            $shipping_city = $checkout->get_value('shipping_city');

                                            $billing_address_1 = $checkout->get_value('billing_address_1');
                                            $billing_postcode = $checkout->get_value('billing_postcode');
                                            $billing_city = $checkout->get_value('billing_city');

                                            // Jeśli checkbox jest domyślnie zaznaczony lub mamy dane shipping, użyj shipping
                                            // W przeciwnym razie użyj billing
                                            if ($ship_to_different_default || (!empty($shipping_address_1) || !empty($shipping_postcode) || !empty($shipping_city))) {
                                                $address_1 = $shipping_address_1;
                                                $postcode = $shipping_postcode;
                                                $city = $shipping_city;
                                            } else {
                                                $address_1 = $billing_address_1;
                                                $postcode = $billing_postcode;
                                                $city = $billing_city;
                                            }

                                            // Formatuj adres tylko jeśli mamy dane
                                            if (!empty($address_1) || !empty($postcode) || !empty($city)) {
                                                $formatted_address = '';
                                                if (!empty($address_1)) {
                                                    $street_label = __('st.', 'weblo-custom-checkout');
                                                    $formatted_address .= esc_html($street_label . ' ' . $address_1);
                                                }
                                                if (!empty($postcode) || !empty($city)) {
                                                    if (!empty($formatted_address)) {
                                                        $formatted_address .= '<br>';
                                                    }
                                                    $city_line = '';
                                                    if (!empty($postcode)) {
                                                        $city_line .= esc_html($postcode);
                                                    }
                                                    if (!empty($city)) {
                                                        if (!empty($city_line)) {
                                                            $city_line .= ' ';
                                                        }
                                                        $city_line .= esc_html($city);
                                                    }
                                                    $formatted_address .= $city_line;
                                                }
                                                echo $formatted_address;
                                            }
                                            ?>
                                        </div>
                                        <div class="btn_change_shipping_details go_to_buyer_details_step">
                                            <?= __('Change', 'weblo-custom-checkout'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="shipping_methods_wrapper_bottom">
                                <div class="checkout_section_head">
                                    <h4 class="checkout_section_title">
                                        <?php esc_html_e('Shipping methods', 'weblo-custom-checkout'); ?>
                                    </h4>
                                    <p class="checkout_section_description">
                                        <?php esc_html_e('Select a delivery method.', 'weblo-custom-checkout'); ?></p>
                                </div>
                                <?php do_action('woocommerce_review_order_before_shipping'); ?>
                                <?php wc_cart_totals_shipping_html(); ?>
                                <?php do_action('woocommerce_review_order_after_shipping'); ?>
                            </div>

                        </div>
                    <?php endif; ?>

                    <div class="payment_methods_wrapper checkout_section">
                        <div class="checkout_section_head">
                            <h4 class="checkout_section_title">
                                <?php esc_html_e('Payment methods', 'weblo-custom-checkout'); ?>
                            </h4>
                            <p class="checkout_section_description">
                                <?php esc_html_e('Select payment method.', 'weblo-custom-checkout'); ?></p>
                        </div>
                        <?php do_action('woocommerce_checkout_before_payment'); ?>
                        <div id="payment" class="woocommerce-checkout-payment">
                            <?php if (WC()->cart->needs_payment()) : ?>
                                <ul class="wc_payment_methods payment_methods methods">
                                    <?php
                                    if (!empty($available_gateways)) {
                                        foreach ($available_gateways as $gateway) {
                                            wc_get_template('checkout/payment-method.php', array('gateway' => $gateway));
                                        }
                                    } else {
                                        echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">' . apply_filters('woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? esc_html__('Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'weblo-custom-checkout') : esc_html__('Please fill in your details above to see available payment methods.', 'weblo-custom-checkout')) . '</li>'; // @codingStandardsIgnoreLine
                                    }
                                    ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <?php do_action('woocommerce_checkout_after_payment'); ?>
                    </div>
                </div>


            </div>
            <div class="Weblo_Custom_Checkout_Form_Content_Right">
                <div class="Weblo_Custom_Checkout_Form_Content_Right_Content">
                    <?php do_action('woocommerce_checkout_before_order_review_heading'); ?>

                    <h4 id="order_review_heading"><?php esc_html_e('Order summary', 'weblo-custom-checkout'); ?></h4>

                    <?php do_action('woocommerce_checkout_before_order_review'); ?>

                    <div id="order_review" class="woocommerce-checkout-review-order">
                        <?php do_action('woocommerce_checkout_order_review'); ?>
                    </div>

                    <?php do_action('woocommerce_checkout_after_order_review'); ?>

                    <div class="btn go_to_delivery_and_payment btn_big w-100">
                        <?= __('Go to delivery and payment', 'weblo-custom-checkout'); ?>
                    </div>



                    <div class="order_review_wrapper order_review_wrapper_hidden w-100">
                        <div class="form-row place-order">
                            <noscript>
                                <?php
                                $order_button_text = __('Pay for your order', 'weblo-custom-checkout');
                                /* translators: $1 and $2 opening and closing emphasis tags respectively */
                                printf(esc_html__('Since your browser does not support JavaScript, or it is disabled, please ensure you click the %1$sUpdate Totals%2$s button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'weblo-custom-checkout'), '<em>', '</em>');
                                ?>
                                <br /><button type="submit"
                                    class="button alt<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"
                                    name="woocommerce_checkout_update_totals"
                                    value="<?php esc_attr_e('Update totals', 'weblo-custom-checkout'); ?>"><?php esc_html_e('Update totals', 'weblo-custom-checkout'); ?></button>
                            </noscript>

                            <?php wc_get_template('checkout/terms.php'); ?>

                            <?php do_action('woocommerce_review_order_before_submit'); ?>

                            <?php echo apply_filters('woocommerce_order_button_html', '<button type="submit" class="btn btn_big w-100' . esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : '') . '" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr($order_button_text) . '" data-value="' . esc_attr($order_button_text) . '">' . esc_html($order_button_text) . '</button>'); // @codingStandardsIgnoreLine 
                            ?>

                            <?php do_action('woocommerce_review_order_after_submit'); ?>

                            <?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>
                        </div>

                    </div>

                    <?php
                    $estimated_delivery_from = get_field('estimated_delivery_from', 'option');
                    $estimated_delivery_to = get_field('estimated_delivery_to', 'option');

                    if ($estimated_delivery_from && $estimated_delivery_to) {
                        function add_business_days_checkout($date, $days)
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

                        $today = new DateTime();
                        $delivery_from = add_business_days_checkout(clone $today, $estimated_delivery_from);
                        $delivery_to = add_business_days_checkout(clone $today, $estimated_delivery_to);

                        $delivery_info = sprintf('%s - %s', $delivery_from->format('d'), $delivery_to->format('d.m.Y'));

                        $delivery_info_days = sprintf(
                            __('%d-%d BUSINESS DAYS', 'weblo-custom-checkout'),
                            $estimated_delivery_from,
                            $estimated_delivery_to
                        );

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
                    <?php endif;
                    }
                    ?>
                </div>
            </div>
        </div>



    </form>

    <?php do_action('woocommerce_after_checkout_form', $checkout); ?>
</div>