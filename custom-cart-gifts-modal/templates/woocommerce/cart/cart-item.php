<?php
$_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
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
$product_sku = $_product->get_sku();
if ($is_gift)
    $extra_class = 'cart-item-gift ';

if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
    $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
?>
    <div
        class="custom-cart-item woocommerce-cart-form__cart-item <?php echo $extra_class;
                                                                    echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">


        <div class="product_thumbnail">
            <?php
            $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);
            if (!$product_permalink) {
                echo $thumbnail; // PHPCS: XSS ok.
            } else {
                printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail); // PHPCS: XSS ok.
            }
            ?>
        </div>
        <div class="product_content">
            <div class="product_content_top">
                <div class="product_content_top_left">
                    <?php if (!empty($product_sku)) : ?>
                        <div class="product-sku">
                            <?= __('Product code:', 'custom-cart-gifts-modal'); ?> <?php echo $_product->get_sku(); ?>
                        </div>
                    <?php endif; ?>
                    <div class="product-name" data-title="<?php esc_attr_e('Product', 'custom-cart-gifts-modal'); ?>">
                        <?php
                        if (!$product_permalink) {
                            if (isset($cart_item['is_gift']) && !empty($cart_item['gift_name'])) {
                                echo wp_kses_post($cart_item['gift_name'] . '&nbsp;');
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
                            if (isset($cart_item['is_gift']) && !empty($cart_item['gift_name'])) {
                                $display_name = $cart_item['gift_name'];
                            }

                            echo wp_kses_post(apply_filters('woocommerce_cart_item_name', sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $display_name), $cart_item, $cart_item_key));
                        }

                        do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);
                        ?>



                    </div>
                    <div class="product-atributes">
                        <?php

                        $formatted_attributes = array();
                        $size_attribute = null;
                        $is_gift = isset($cart_item['is_gift']);
                        $is_cross_sell = isset($cart_item['is_cross_sell']);

                        $show_attributes = get_option('cgm_show_attributes_in_cart', array());
                        $show_attribute_name = get_option('cgm_show_attribute_name_in_cart', false) == 'on';

                        if ($_product->is_type('variation')) {
                            foreach ($cart_item['variation'] as $key => $value) {

                                $taxonomy = str_replace('attribute_', '', $key);
                                $term = get_term_by('slug', $value, $taxonomy);

                                if (!in_array($taxonomy, $show_attributes)) {
                                    continue;
                                }

                                $attr_name = wc_attribute_label($taxonomy);
                                $attr_value = $term ? $term->name : $value;
                                $formatted_attributes[] = $show_attribute_name ?
                                    '<span class="product-attr-label">' . $attr_name . ': </span><span class="product-attr-value">' . $attr_value . '</span>' :
                                    '<span class="product-attr-value">' . $attr_value . '</span>';
                            }
                        } else {
                            foreach ($show_attributes as $attribute) {
                                $value = $_product->get_attribute($attribute);
                                if (!empty($value)) {
                                    $attr_name = wc_attribute_label($attribute);
                                    $formatted_attributes[] = $show_attribute_name ?
                                        '<span class="product-attr-label">' . $attr_name . ': </span> <span class="product-attr-value">' . $value . '</span>' :
                                        '<span class="product-attr-value">' . $value . '</span>';
                                }
                            }
                        }

                        // Dodaj rozmiar na początku tablicy
                        if ($size_attribute) {
                            array_unshift($formatted_attributes, $size_attribute);
                        }

                        foreach ($formatted_attributes as $attribute) {
                            echo '<span class="product-attr">' . $attribute . '</span>';
                        }

                        if ($is_gift) {
                            echo '<span class="product-attr gift-attr">' . __('GIFT! ', 'custom-cart-gifts-modal') . '</span>';
                        }

                        if ($is_cross_sell) {
                            $cross_sell_prev_price = $cart_item['prev_price'];
                            $cross_sell_price = $cart_item['data']->get_price();
                            echo '<span class="product-attr cross-sell-attr"><del>' . wc_price($cross_sell_prev_price) . '</del> <span class="cross-sell-price">' . wc_price($cross_sell_price) . '</span>';
                        }

                        // Backorder notification.
                        if ($_product->backorders_require_notification() && $_product->is_on_backorder($cart_item['quantity'])) {
                            echo wp_kses_post(apply_filters('woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__('Available on backorder', 'custom-cart-gifts-modal') . '</p>', $product_id));
                        }
                        ?>
                    </div>
                </div>
                <div class="wrap_product_prcice">
                    <?php
                    if ($is_gift) {
                    ?>
                        <div class="product-price  product-price-promo-price" data-title="<?php esc_attr_e('Price', 'custom-cart-gifts-modal'); ?>">
                            <?php


                            $product_price = WC()->cart->get_product_price($_product);
                            $regular_price = $_product->get_regular_price();
                            $sale_price = $_product->get_sale_price();
                            if ($product_price != $regular_price || $product_price != $sale_price) {
                                echo '<del>' . wc_price($regular_price) . '</del> ';
                            }
                            echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key); // PHPCS: XSS ok.
                       
                            ?>
                        </div>
                    <?php
                    }
                    ?>
                    <?php if (!$is_gift): ?>
                        <div class="product-price <?php echo ($_product->is_on_sale() ? 'product-price-promo-price' : ''); ?>"
                            data-title="<?php esc_attr_e('Price', 'custom-cart-gifts-modal'); ?>">
                            <?php
                            $product_price = WC()->cart->get_product_price($_product);
                            $regular_price = $_product->get_regular_price();
                            $sale_price = $_product->get_sale_price();

                            if ($_product->is_on_sale()) {
                                echo '<del>' . wc_price($regular_price) . '</del> ';
                            }
                            echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key); // PHPCS: XSS ok.
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="cart_item_bottom">
                <div class="product-quantity" data-title="<?php esc_attr_e('Quantity', 'custom-cart-gifts-modal'); ?>">
                    <?php
                    if ($_product->is_sold_individually()) {
                        $min_quantity = 0;
                        $max_quantity = 1;
                    } else {
                        $min_quantity = 0;
                        $max_quantity = $_product->get_max_purchase_quantity();
                    }
                    if ($max_quantity == -1)
                        $max_quantity = 100000;
                    ?>
                    <div class="quantity-input">
                        <button <?= $min_quantity ?> <?= $cart_item['quantity'] == $min_quantity ? 'disabled' : '' ?>
                            type="button" class="minus minus-btn quantity-minus"
                            data-cart-item="<?php echo $cart_item_key; ?>">
                            <svg class="icon-minus" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none">
                                <path d="M19 12.998H5V10.998H19V12.998Z" fill="#E4E4E4" />
                            </svg>
                        </button>
                        <input type="text" class="quantity" value="<?php echo $cart_item['quantity']; ?>" min="0"
                            max="<?php echo $max_quantity; ?>" readonly data-cart-item="<?php echo $cart_item_key; ?>"
                            name="<?php echo $cart_item_key; ?>">
                        <button
                            <?= $cart_item['quantity'] == $max_quantity || $is_gift || $is_cross_sell ? 'disabled' : '' ?>
                            type="button" class="plus plus-btn quantity-plus"
                            data-cart-item="<?php echo $cart_item_key; ?>">
                            <svg class="icon-plus" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                viewBox="0 0 24 24" fill="none">
                                <path d="M19 12.998H13V18.998H11V12.998H5V10.998H11V4.99799H13V10.998H19V12.998Z"
                                    fill="#FE6645" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="cart-item-right">
                    <div class="product-remove">
                        <a href="<?php echo wc_get_cart_remove_url($cart_item_key); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M7 21C6.45 21 5.97933 20.8043 5.588 20.413C5.19667 20.0217 5.00067 19.5507 5 19V6H4V4H9V3H15V4H20V6H19V19C19 19.55 18.8043 20.021 18.413 20.413C18.0217 20.805 17.5507 21.0007 17 21H7ZM17 6H7V19H17V6ZM9 17H11V8H9V17ZM13 17H15V8H13V17Z"
                                    fill="#838383" />
                            </svg>
                        </a>
                    </div>

                </div>
            </div>
        </div>





    </div>

<?php
}
