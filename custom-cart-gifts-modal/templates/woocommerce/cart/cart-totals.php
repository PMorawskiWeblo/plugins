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
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 2.3.6
 */

defined('ABSPATH') || exit;

?>
<div class="cart_totals <?php echo (WC()->customer->has_calculated_shipping()) ? 'calculated_shipping' : ''; ?>">


    <?php do_action('woocommerce_before_cart_totals'); ?>
    <?php if (wc_coupons_enabled()) { ?>
    <div class="wrap_coupons">
        <div class="coupons_head">
            <h3 class="cart_totals_title"><?php esc_html_e('Coupons', 'custom-cart-gifts-modal'); ?></h3>
            <svg class="coupons_head_icon not_active" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                viewBox="0 0 16 16" fill="none">
                <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M2.19526 5.52861C2.45561 5.26826 2.87772 5.26826 3.13807 5.52861L8 10.3905L12.8619 5.52861C13.1223 5.26826 13.5444 5.26826 13.8047 5.52861C14.0651 5.78896 14.0651 6.21107 13.8047 6.47141L8.4714 11.8047C8.21106 12.0651 7.78895 12.0651 7.5286 11.8047L2.19526 6.47141C1.93491 6.21107 1.93491 5.78896 2.19526 5.52861Z"
                    fill="#FE6645" />
            </svg>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.coupons_head_icon').click(function() {
                $(this).toggleClass('not_active');
                $('.cart_totals .actions').toggleClass('hidden');
            });
        });
        </script>
        <div class="actions">
            <form action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
                <div class="coupon table-row">
                    <label for="coupon_code"
                        class="screen-reader-text"><?php esc_html_e('Coupon:', 'custom-cart-gifts-modal'); ?></label>
                    <input type="text" name="coupon_code" class="input-text" id="coupon_code" value=""
                        placeholder="<?php esc_attr_e('Coupon code', 'custom-cart-gifts-modal'); ?>" /> <button
                        type="submit"
                        class="button<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_eent_class_name('button') : ''); ?>"
                        name="apply_coupon"
                        value="<?php esc_attr_e('Apply coupon', 'custom-cart-gifts-modal'); ?>"><?php esc_html_e('Apply coupon', 'custom-cart-gifts-modal'); ?></button>
                    <?php do_action('woocommerce_cart_coupon'); ?>
                </div>
            </form>
        </div>
        <?php if (WC()->cart->get_coupons()) : ?>
        <div class="coupons-wrap">
            <div class="coupons">
                <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
                <div class="cart-discount coupon-<?php echo esc_attr(sanitize_title($code)); ?> table-row">
                    <div class="coupon-chip">
                        <div class="coupon_left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 26 26"
                                fill="none">
                                <path d="M17.333 13L8.66634 13" stroke="#FE6645" stroke-width="1.25"
                                    stroke-linecap="round" stroke-linejoin="round" />
                                <path
                                    d="M5.41667 5.41669H20.5833C21.158 5.41669 21.7091 5.64496 22.1154 6.05129C22.5217 6.45762 22.75 7.00872 22.75 7.58335V10.8334C22.1754 10.8334 21.6243 11.0616 21.2179 11.468C20.8116 11.8743 20.5833 12.4254 20.5833 13C20.5833 13.5747 20.8116 14.1258 21.2179 14.5321C21.6243 14.9384 22.1754 15.1667 22.75 15.1667V18.4167C22.75 18.9913 22.5217 19.5424 22.1154 19.9488C21.7091 20.3551 21.158 20.5834 20.5833 20.5834H5.41667C4.84203 20.5834 4.29093 20.3551 3.8846 19.9488C3.47827 19.5424 3.25 18.9913 3.25 18.4167V15.1667C3.82464 15.1667 4.37574 14.9384 4.78206 14.5321C5.18839 14.1258 5.41667 13.5747 5.41667 13C5.41667 12.4254 5.18839 11.8743 4.78206 11.468C4.37574 11.0616 3.82464 10.8334 3.25 10.8334V7.58335C3.25 7.00872 3.47827 6.45762 3.8846 6.05129C4.29093 5.64496 4.84203 5.41669 5.41667 5.41669"
                                    stroke="#FE6645" stroke-width="1.25" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            <span class="coupon-name"><?php echo esc_html($coupon->get_code()); ?></span>
                        </div>
                        <div class="coupon_right">
                            <span class="coupon-discount"><?php echo wc_price($coupon->get_amount()); ?></span>
                            <a href="<?php echo esc_url(add_query_arg('remove_coupon', urlencode($code), wc_get_cart_url())); ?>"
                                class="remove-coupon"
                                aria-label="<?php esc_attr_e('Remove coupon', 'custom-cart-gifts-modal'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="18" viewBox="0 0 16 18"
                                    fill="none">
                                    <path
                                        d="M3 18C2.45 18 1.97933 17.8043 1.588 17.413C1.19667 17.0217 1.00067 16.5507 1 16V3H0V1H5V0H11V1H16V3H15V16C15 16.55 14.8043 17.021 14.413 17.413C14.0217 17.805 13.5507 18.0007 13 18H3ZM13 3H3V16H13V3ZM5 14H7V5H5V14ZM9 14H11V5H9V14Z"
                                        fill="#4C4C4C" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php } ?>

    <div class="wrap_cart_totals">


        <div class="cart_totals_head">
            <h3 class="cart_totals_title"><?php esc_html_e('Cart totals', 'custom-cart-gifts-modal'); ?></h3>
        </div>

        <div class="shop_table shop_table_responsive">

            <div class="cart-subtotal table-row">
                <div class="label th"><?php esc_html_e('Subtotal', domain: 'custom-cart-gifts-modal'); ?></div>
                <div class="value td" data-title="<?php esc_attr_e('Subtotal', 'custom-cart-gifts-modal'); ?>">
                    <?php wc_cart_totals_subtotal_html(); ?></div>
            </div>

            <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
            <?php

				$show_free_shipping = get_option('cgm_show_free_shipping', false) == 'on';
				$free_shipping_threshold = get_option('cgm_free_shipping_threshold', 0);
				$cart_total = WC()->cart->get_subtotal();

				$packages = WC()->shipping()->get_packages();

				?>
            <div class="shipping table-row">
                <div class="label th"><?php esc_html_e('Shipping', 'custom-cart-gifts-modal'); ?></div>
                <div class="value td" data-title="<?php esc_attr_e('Shipping', 'custom-cart-gifts-modal'); ?>">
                    <?php
						foreach ($packages as $i => $package) {
							$chosen_method = isset(WC()->session->chosen_shipping_methods[$i]) ? WC()->session->chosen_shipping_methods[$i] : '';
							$available_methods = $package['rates'];
							if (count($available_methods) > 0) {
								foreach ($available_methods as $method) {
									if ($method->id === $chosen_method) {
										if ($method->cost == 0) {
											esc_html_e('Free', 'custom-cart-gifts-modal');
										} else {
											echo wc_price($method->cost);
										}
										break;
									}
								}
							}
						}
						?>
                </div>
            </div>
            <?php endif; ?>

            <?php foreach (WC()->cart->get_fees() as $fee) : ?>
            <div class="fee table-row">
                <div class="label th"><?php echo esc_html($fee->name); ?></div>
                <div class="value td" data-title="<?php echo esc_attr($fee->name); ?>">
                    <?php wc_cart_totals_fee_html($fee); ?></div>
            </div>
            <?php endforeach; ?>
            <?php
			if (wc_tax_enabled() && ! WC()->cart->display_prices_including_tax()) {

				$taxable_address = WC()->customer->get_taxable_address();
				$estimated_text  = '';

				if (WC()->customer->is_customer_outside_base() && ! WC()->customer->has_calculated_shipping()) {
					/* translators: %s location. */
					$estimated_text = sprintf(' <small>' . esc_html__('(estimated for %s)', 'custom-cart-gifts-modal') . '</small>', WC()->countries->estimated_for_prefix($taxable_address[0]) . WC()->countries->countries[$taxable_address[0]]);
				}

				if ('itemized' === get_option('woocommerce_tax_total_display')) {
					foreach (WC()->cart->get_tax_totals() as $code => $tax) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			?>
            <div class="tax-rate tax-rate-<?php echo esc_attr(sanitize_title($code)); ?> table-row">
                <div class="label th"><?php echo esc_html($tax->label) . $estimated_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
													?></div>
                <div class="value td" data-title="<?php echo esc_attr($tax->label); ?>">
                    <?php echo wp_kses_post($tax->formatted_amount); ?></div>
            </div>
            <?php
					}
				} else {
					?>
            <div class="tax-total table-row">
                <div class="label th"><?php echo esc_html(WC()->countries->tax_or_vat()) . $estimated_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
												?></div>
                <div class="value td" data-title="<?php echo esc_attr(WC()->countries->tax_or_vat()); ?>">
                    <?php wc_cart_totals_taxes_total_html(); ?></div>
            </div>
            <?php
				}
			}
			?>


            <?php if (WC()->cart->get_coupons()) : ?>
            <div class="cart-discount table-row">
                <div class="label th"><?php esc_html_e('Discount', domain: 'custom-cart-gifts-modal'); ?></div>
                <div class="value td">
                    <?php
						$total_discount = 0;
						foreach (WC()->cart->get_coupons() as $code => $coupon) {
							$total_discount += WC()->cart->get_coupon_discount_amount($code, WC()->cart->display_cart_ex_tax);
						}
						echo '-' . wp_kses_post($total_discount) . ' ' . get_woocommerce_currency_symbol();
						?>
                </div>
            </div>

            <?php endif; ?>

            <?php do_action('woocommerce_cart_totals_before_order_total'); ?>


            <div class="order-total table-row">
                <div class="label th"><?php esc_html_e('Total', 'custom-cart-gifts-modal'); ?></div>
                <div class="value td" data-title="<?php esc_attr_e('Total', 'custom-cart-gifts-modal'); ?>">
                    <?php wc_cart_totals_order_total_html(); ?></div>
            </div>

            <?php do_action('woocommerce_cart_totals_after_order_total'); ?>

        </div>
        <div class="wrap_buttons">
            <div class="wc-proceed-to-checkout-custom">
                <?php
				// Usuwamy domyślną akcję
				remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);

				$show_cross_sell_page = get_option('cgm_show_cross_sell_page', false) == 'on' ? true : false;
				$crossel_page_id = get_option('cgm_cross_sell_page_url', '');
				$crossel_page_url = get_permalink($crossel_page_id);

				$cross_sell_products = array();
				if (class_exists('CrossSellPage')) {
					$instance = new CrossSellPage();
					if (method_exists($instance, 'get_cross_sell_products_from_active_levels')) {
						$cross_sell_products = $instance->get_cross_sell_products_from_active_levels();
					}
				}
				if ($show_cross_sell_page && !empty($crossel_page_url) && !empty($cross_sell_products)) {
				?>
                <a href="<?= $crossel_page_url; ?>"
                    class="checkout-button btn btn_orange alt wc-forward<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>">
                    <?php esc_html_e('Proceed to shipping and payment', 'custom-cart-gifts-modal'); ?>
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M7.29289 20.7071C6.90237 20.3166 6.90237 19.6834 7.29289 19.2929L14.5858 12L7.29289 4.70711C6.90237 4.31658 6.90237 3.68342 7.29289 3.29289C7.68342 2.90237 8.31658 2.90237 8.70711 3.29289L16.7071 11.2929C17.0976 11.6834 17.0976 12.3166 16.7071 12.7071L8.70711 20.7071C8.31658 21.0976 7.68342 21.0976 7.29289 20.7071Z"
                                fill="white" />
                        </svg>
                    </span>
                </a>
                <?php
				} else {
				?>
                <a href="<?php echo esc_url(wc_get_checkout_url()); ?>"
                    class="checkout-button btn btn_orange alt wc-forward<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>">
                    <?php esc_html_e('Proceed to shipping and payment', 'custom-cart-gifts-modal'); ?>
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M7.29289 20.7071C6.90237 20.3166 6.90237 19.6834 7.29289 19.2929L14.5858 12L7.29289 4.70711C6.90237 4.31658 6.90237 3.68342 7.29289 3.29289C7.68342 2.90237 8.31658 2.90237 8.70711 3.29289L16.7071 11.2929C17.0976 11.6834 17.0976 12.3166 16.7071 12.7071L8.70711 20.7071C8.31658 21.0976 7.68342 21.0976 7.29289 20.7071Z"
                                fill="white" />
                        </svg>
                    </span>
                </a>
                <?php
				}
				?>
            </div>
            <a class="btn btn_dark_outline href=" <?php echo esc_url(wc_get_page_permalink('shop')); ?>" "><?= __('Continue shopping', 'custom-cart-gifts-modal'); ?></a>
		</div>
	</div>

	<?php if (have_rows('cart_infos', 'option')) : ?>
		<div class=" cart_infos">
                <?php while (have_rows('cart_infos', 'option')) : the_row();
					$cart_info_icon = get_sub_field('cart_info_icon');
					$cart_info_text = get_sub_field('cart_info_text');
				?>
                <div class="cart_info">
                    <?php if ($cart_info_icon) : ?>
                    <div class="wrap_cart_info_icon">
                        <img class="cart_info_icon" src="<?php echo esc_url($cart_info_icon['url']); ?>"
                            alt="<?php echo esc_attr($cart_info_icon['alt']); ?>" />
                    </div>
                    <?php endif; ?>
                    <?php if ($cart_info_text) : ?>
                    <div class="cart_info_text">
                        <?php echo $cart_info_text; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
        </div>
        <?php endif; ?>




        <?php if (get_option('cgm_show_delivery_time', false) == 'on') { ?>
        <div class=" delivery-time">
            <span class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1"
                    width="256" height="256" viewBox="0 0 256 256" xml:space="preserve">
                    <defs>
                    </defs>
                    <g style="stroke: none; stroke-width: 0; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: none; fill-rule: nonzero; opacity: 1;"
                        transform="translate(1.4065934065934016 1.4065934065934016) scale(2.81 2.81)">
                        <path
                            d="M 87.364 62.295 h -1.948 v -2 h 1.948 c 0.351 0 0.636 -0.285 0.636 -0.636 V 49.441 c 0 -0.155 -0.058 -0.307 -0.161 -0.424 L 77.65 37.558 c -0.121 -0.136 -0.294 -0.214 -0.476 -0.214 h -9.356 v 22.951 h 8.401 v 2 H 65.818 V 35.344 h 11.356 c 0.752 0 1.47 0.322 1.97 0.885 L 89.334 47.69 C 89.764 48.175 90 48.797 90 49.441 v 10.218 C 90 61.112 88.817 62.295 87.364 62.295 z"
                            style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;"
                            transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                        <path
                            d="M 67.818 62.295 H 47.291 v -2 h 18.527 V 28.877 c 0 -1.95 -1.202 -3.537 -2.681 -3.537 H 31.866 c -1.479 0 -2.681 1.587 -2.681 3.537 v 30.23 c 0 0.7 0.408 1.188 0.774 1.188 h 8.143 v 2 h -8.143 c -1.53 0 -2.774 -1.43 -2.774 -3.188 v -30.23 c 0 -3.053 2.1 -5.537 4.681 -5.537 h 31.272 c 2.581 0 4.681 2.484 4.681 5.537 V 62.295 z"
                            style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;"
                            transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                        <path
                            d="M 80.817 66.66 c -3.087 0 -5.598 -2.511 -5.598 -5.598 s 2.511 -5.599 5.598 -5.599 s 5.599 2.512 5.599 5.599 S 83.904 66.66 80.817 66.66 z M 80.817 57.464 c -1.983 0 -3.598 1.614 -3.598 3.599 c 0 1.983 1.614 3.598 3.598 3.598 c 1.984 0 3.599 -1.614 3.599 -3.598 C 84.416 59.078 82.802 57.464 80.817 57.464 z"
                            style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;"
                            transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                        <path
                            d="M 42.693 66.66 c -3.087 0 -5.598 -2.511 -5.598 -5.598 s 2.511 -5.599 5.598 -5.599 s 5.598 2.512 5.598 5.599 S 45.78 66.66 42.693 66.66 z M 42.693 57.464 c -1.984 0 -3.598 1.614 -3.598 3.599 c 0 1.983 1.614 3.598 3.598 3.598 s 3.598 -1.614 3.598 -3.598 C 46.291 59.078 44.677 57.464 42.693 57.464 z"
                            style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;"
                            transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                        <path
                            d="M 89 49.819 H 74.619 c -1.358 0 -2.463 -1.104 -2.463 -2.462 v -5.44 c 0 -1.358 1.104 -2.463 2.463 -2.463 h 6.944 v 2 h -6.944 c -0.255 0 -0.463 0.208 -0.463 0.463 v 5.44 c 0 0.255 0.208 0.462 0.463 0.462 H 89 V 49.819 z"
                            style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;"
                            transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                        <path
                            d="M 21.207 52.942 H 8.615 c -0.552 0 -1 -0.447 -1 -1 s 0.448 -1 1 -1 h 12.592 c 0.552 0 1 0.447 1 1 S 21.759 52.942 21.207 52.942 z"
                            style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;"
                            transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                        <path
                            d="M 21.207 46.031 H 4.617 c -0.552 0 -1 -0.448 -1 -1 s 0.448 -1 1 -1 h 16.59 c 0.552 0 1 0.448 1 1 S 21.759 46.031 21.207 46.031 z"
                            style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;"
                            transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                        <path
                            d="M 21.207 39.121 H 1 c -0.552 0 -1 -0.448 -1 -1 s 0.448 -1 1 -1 h 20.207 c 0.552 0 1 0.448 1 1 S 21.759 39.121 21.207 39.121 z"
                            style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;"
                            transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                        <path
                            d="M 47.502 51.655 c -5.695 0 -10.328 -4.633 -10.328 -10.328 S 41.807 31 47.502 31 c 5.694 0 10.327 4.633 10.327 10.328 S 53.196 51.655 47.502 51.655 z M 47.502 33 c -4.592 0 -8.328 3.736 -8.328 8.328 s 3.736 8.328 8.328 8.328 c 4.592 0 8.327 -3.736 8.327 -8.328 S 52.094 33 47.502 33 z"
                            style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;"
                            transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                        <path
                            d="M 46.299 45.637 c -0.272 0 -0.533 -0.111 -0.722 -0.308 l -2.728 -2.845 c -0.382 -0.398 -0.369 -1.031 0.03 -1.414 c 0.398 -0.383 1.031 -0.37 1.414 0.03 l 1.96 2.045 l 4.417 -5.208 c 0.357 -0.42 0.988 -0.473 1.409 -0.116 c 0.422 0.357 0.474 0.988 0.116 1.41 l -5.134 6.053 c -0.184 0.216 -0.449 0.344 -0.732 0.353 C 46.319 45.637 46.309 45.637 46.299 45.637 z"
                            style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;"
                            transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                    </g>
                </svg>
            </span>
            <?php echo __('Expected delivery time', 'custom-cart-gifts-modal'); ?>
        </div>





        <?php } ?>

        <?php do_action('woocommerce_after_cart_totals'); ?>

    </div>