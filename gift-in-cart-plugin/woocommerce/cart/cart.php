<?php

/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woo.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.9.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart');

// Jeśli koszyk jest pusty, załaduj szablon cart-empty.php
if (WC()->cart->is_empty()) {
    $cart_empty_template = dirname(__FILE__) . '/cart-empty.php';
    if (file_exists($cart_empty_template)) {
        include $cart_empty_template;
    } else {
        // Fallback do standardowego szablonu WooCommerce
        wc_get_template('cart/cart-empty.php');
    }
    return;
}
?>
<section class="section_breadcums section_breadcums_default">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <?php get_template_part('components/breadcums'); ?>
            </div>
        </div>
    </div>
</section>
<?php
do_action('woocommerce_before_cart');
?>
<div class="custom-woocommerce-notices-wrapper">
    <div class="woocommerce-notices-wrapper">
        <?php wc_print_notices(); ?>
    </div>
</div>
<div class="new-cart-layout-wrapper d-flex flex-column">
    <div class="new-cart-layout">
        <div class="left-col-cart">
            <form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">

                <?php do_action('woocommerce_before_cart_table'); ?>

                <div class="woocommerce-cart-form-wrapper">
                    <h4 class="cart-title"><?php _e('Your cart', 'gift-in-cart-plugin'); ?> (<span
                            id="cart-counter"><?php echo WC()->cart->get_cart_contents_count(); ?></span>)</h4>

                    <div class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
                        <?php do_action('woocommerce_before_cart_contents'); ?>

                        <?php
                        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                            $_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                            $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
                            /**
                             * Filter the product name.
                             *
                             * @since 2.1.0
                             * @param string $product_name Name of the product in the cart.
                             * @param array $cart_item The product in the cart.
                             * @param string $cart_item_key Key for the product in the cart.
                             */
                            $product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);
                            $extra_class = '';
                            $is_gift = isset($cart_item['isgift']);
                            $is_sample = isset($cart_item['is_sample']) && $cart_item['is_sample'];
                            if ($is_gift)
                                $extra_class = 'cart-item-gift ';
                            if ($is_sample)
                                $extra_class .= 'cart_item_sample ';

                            if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
                                $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                        ?>
                                <div
                                    class="woocommerce-cart-form__cart-item d-flex <?php echo $extra_class;
                                                                                    echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">
                                    <div class="cart-item-left">
                                        <div class="product-thumbnail">
                                            <?php
                                            $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);

                                            if (!$product_permalink) {
                                                echo $thumbnail; // PHPCS: XSS ok.
                                            } else {
                                                printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail); // PHPCS: XSS ok.
                                            }
                                            ?>
                                        </div>
                                    </div>

                                    <div class="cart-item-middle col">
                                        <div class="cart-item-middle-contetn">
                                            <?php
                                            $linie_terms = get_the_terms($product_id, 'linia');
                                            if (!is_wp_error($linie_terms) && !empty($linie_terms)) {
                                                $linie_names = array();
                                                foreach ($linie_terms as $term) {
                                                    $linie_names[] = esc_html($term->name);
                                                }
                                                echo '<div class="cart-item-linia">'  . implode(', ', $linie_names) . '</div>';
                                            }
                                            ?>

                                            <div class="product-name"
                                                data-title="<?php esc_attr_e('Product', 'woocommerce'); ?>">
                                                <?php
                                                if (!$product_permalink) {
                                                    if (isset($cart_item['isgift']) && !empty($cart_item['gift_name'])) {
                                                        echo wp_kses_post($cart_item['gift_name'] . '&nbsp;');
                                                    } elseif (isset($cart_item['is_sample']) && !empty($cart_item['sample_name'])) {
                                                        echo wp_kses_post($cart_item['sample_name'] . '&nbsp;');
                                                    } else {
                                                        echo wp_kses_post($product_name . '&nbsp;');
                                                    }
                                                } else {
                                                    /**
                                                     * This filter is documented above.
                                                     *
                                                     * @since 2.1.0
                                                     */

                                                    $display_name = $_product->get_name();
                                                    if (isset($cart_item['isgift']) && !empty($cart_item['gift_name'])) {
                                                        $display_name = $cart_item['gift_name'];
                                                    } elseif (isset($cart_item['is_sample']) && !empty($cart_item['sample_name'])) {
                                                        $display_name = $cart_item['sample_name'];
                                                    }
                                                    echo wp_kses_post(apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $display_name), $cart_item, $cart_item_key));
                                                }

                                                do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);

                                                // Meta data.
                                                echo wc_get_formatted_cart_item_data($cart_item); // PHPCS: XSS ok.

                                                // Backorder notification.
                                                if ($_product->backorders_require_notification() && $_product->is_on_backorder($cart_item['quantity'])) {
                                                    echo wp_kses_post(apply_filters('woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__('Available on backorder', 'woocommerce') . '</p>', $product_id));
                                                }
                                                ?>

                                            </div>


                                            <?php
                                            $slogan = '';

                                            if (isset($cart_item['isgift']) && !empty($cart_item['gift_slogan'])) {
                                                $slogan = $cart_item['gift_slogan'];
                                            } else {
                                                // get_field may return false if there's no value
                                                $acf_slogan = function_exists('get_field') ? get_field('slogan', $product_id) : '';
                                                if (!empty($acf_slogan)) {
                                                    $slogan = $acf_slogan;
                                                }
                                            }

                                            // Jesli nie ma sloganu, użyj krótkiego opisu
                                            if (empty($slogan)) {
                                                if (isset($_product) && method_exists($_product, 'get_short_description')) {
                                                    $short_description = $_product->get_short_description();
                                                    if (!empty($short_description)) {
                                                        $slogan = $short_description;
                                                    }
                                                }
                                            }

                                            if (!empty($slogan)) : ?>
                                                <div class="product-slogan">
                                                    <?php echo esc_html($slogan); ?>
                                                </div>
                                            <?php endif; ?>


                                            <?php
                                            $hide_price = get_field('hide_price', 'option') == 1;
                                            $is_gift = isset($cart_item['isgift']);

                                            if (!($is_gift && $hide_price)) :
                                                // Sprawdź czy produkt ma warianty i przedział cenowy
                                                $price_range_class = '';
                                                $parent_product = $_product;
                                                // Jeśli to wariant, pobierz produkt główny
                                                if ($_product->is_type('variation')) {
                                                    $parent_product = wc_get_product($_product->get_parent_id());
                                                }
                                                // Sprawdź czy produkt główny ma warianty i przedział cenowy
                                                if ($parent_product && $parent_product->is_type('variable')) {
                                                    $min_price = $parent_product->get_variation_price('min');
                                                    $max_price = $parent_product->get_variation_price('max');
                                                    if ($min_price != $max_price) {
                                                        $price_range_class = 'price_range';
                                                    }
                                                }
                                            ?>
                                                <div class="wrap_product_price_att <?php echo esc_attr($price_range_class); ?>">
                                                    <div class="product-price d-flex"
                                                        data-title="<?php esc_attr_e('Cena', 'woocommerce'); ?>">
                                                        <?php
                                                        echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key); // PHPCS: XSS ok.


                                                        ?>
                                                    </div>
                                                    <?php
                                                    $capacity = $_product->get_attribute('pa_pojemnosc');
                                                    if (!empty($capacity)) {
                                                        echo '<span class="capacity-separator">•</span> <span class="product-capacity">' . esc_html($capacity) . '</span>';
                                                    } ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (isset($cart_item['isgift'])) { ?>
                                                <div class="product-gift-level">
                                                    <p class="mb-0"><?= __('Selected gift from level', 'gift-in-cart-plugin'); ?>
                                                        <span class="ms-0"><?= $cart_item['level']; ?></span>
                                                    </p>
                                                </div>
                                            <?php }; ?>
                                        </div>
                                        <?php if (!$is_gift) { ?>
                                            <div class="product-quantity"
                                                data-title="<?php esc_attr_e('Quantity', 'woocommerce'); ?>">
                                                <?php
                                                if ($_product->is_sold_individually()) {
                                                    $min_quantity = 1;
                                                    $max_quantity = 1;
                                                } else {
                                                    $min_quantity = 0;
                                                    $max_quantity = $_product->get_max_purchase_quantity();
                                                }

                                                if ($max_quantity == -1)
                                                    $max_quantity = 100000;


                                                ?>

                                                <div class="quantity-input d-flex align-items-center">

                                                    <span class="quantity-minus"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                            height="24" viewBox="0 0 24 24" fill="none">
                                                            <path d="M5 12H19" stroke="#47170D" stroke-width="1.5"
                                                                stroke-linejoin="round" />
                                                        </svg></span>
                                                    <input type="number" class="quantity"
                                                        name="<?php echo esc_attr($cart_item_key); ?>"
                                                        value="<?php echo esc_attr($cart_item['quantity']); ?>"
                                                        min="<?php echo esc_attr($min_quantity); ?>"
                                                        max="<?php echo esc_attr($max_quantity); ?>" step="1" />
                                                    <span class="quantity-plus"><svg xmlns="http://www.w3.org/2000/svg" width="14"
                                                            height="14" viewBox="0 0 14 14" fill="none">
                                                            <path d="M0 7H14M7 0V14" stroke="#47170D" stroke-width="1.5"
                                                                stroke-linejoin="round" />
                                                        </svg></span>

                                                </div>

                                            </div>
                                        <?php
                                        }
                                        ?>
                                    </div>

                                    <div class="cart-item-right">
                                        <div class="product-remove">
                                            <a href="<?php echo wc_get_cart_remove_url($cart_item_key); ?>"
                                                title="<?php esc_attr_e('Remove', 'woocommerce'); ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none">
                                                    <path
                                                        d="M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6M3 6H21M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6"
                                                        stroke="#47170D" stroke-width="1.5" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                            </a>
                                        </div>

                                    </div>
                                </div>
                        <?php
                            }
                        }
                        ?>

                        <?php do_action('woocommerce_cart_contents'); ?>


                        <button style="display:none;" type="submit"
                            class="button<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>"
                            name="update_cart"
                            value="<?php esc_attr_e('Update cart', 'woocommerce'); ?>"><?php esc_html_e('Update cart', 'woocommerce'); ?></button>

                        <?php do_action('woocommerce_cart_actions'); ?>

                        <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>





                        <?php do_action('woocommerce_after_cart_contents'); ?>

                    </div>
                </div>
                <?php do_action('woocommerce_after_cart_table'); ?>
                <?php include_once WP_PLUGIN_DIR . '/gift-in-cart-plugin/inc/samples.php'; ?>
            </form>

            <?php do_action('woocommerce_before_cart_collaterals'); ?>



        </div>

        <div class="right-col-cart">
            <?php if (wc_coupons_enabled()) { ?>


            <?php } ?>
            <div class="cart-collaterals">

                <?php
                /**
                 * Cart collaterals hook.
                 *
                 * @hooked woocommerce_cross_sell_display
                 * @hooked woocommerce_cart_totals - 10
                 */
                remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');
                do_action('woocommerce_cart_collaterals');
                ?>
            </div>
        </div>

    </div>
</div>
<?php
get_template_part('woocommerce/single-product/related');
?>
</div>

<?php do_action('woocommerce_after_cart'); ?>