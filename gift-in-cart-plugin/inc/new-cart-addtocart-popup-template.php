<?php
$product_id = get_query_var('productid');
$quantity = get_query_var('quantity') ?: 1;
$product = wc_get_product($product_id);
$product_title = get_the_title($product_id);
$product_permalink = $product->get_permalink();
$product_cross = $product->get_cross_sell_ids();
$product_upsell = $product->get_upsell_ids();
$product_slogan = get_field('slogan', $product_id);
if (empty($product_slogan)) {
    $product_slogan = $product->get_short_description();
}
?>
<div class="gift-in-cart-popup-content d-flex flex-column">
    <div class="gift-in-cart-popup-top">
        <div class="gift-in-cart-popup-top-head">
            <h2 class="gift-in-cart-popup-title">
                <?= sprintf(__('Added to cart (%d)', 'gift-in-cart-plugin'), $quantity); ?>
            </h2>
            <div id="gift-in-cart-popup-close" class="gift-in-cart-popup-close">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#47170D" stroke-width="1.5" stroke-linejoin="round" />
                </svg>
            </div>
        </div>
        <div class="gift-in-cart-message">
            <div class="woocommerce-cart-form__cart-item d-flex gift-in-cart-popup-product-item">
                <div class="cart-item-left">
                    <div class="product-thumbnail">
                        <?php
                        $thumbnail = $product->get_image();
                        if ($product_permalink) {
                            printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail);
                        } else {
                            echo $thumbnail;
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
                            echo '<div class="cart-item-linia">' . implode(', ', $linie_names) . '</div>';
                        }
                        ?>
                        <div class="product-name">
                            <?php
                            if ($product_permalink) {
                                echo wp_kses_post(sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $product_title));
                            } else {
                                echo wp_kses_post($product_title);
                            }
                            ?>
                        </div>
                        <?php if ($product_slogan) { ?>
                            <div class="product-slogan">
                                <?php echo $product_slogan; ?>
                            </div>
                        <?php } ?>
                        <?php
                        // Sprawdź czy produkt ma warianty i przedział cenowy
                        $price_range_class = '';
                        if ($product->is_type('variable')) {
                            $min_price = $product->get_variation_price('min');
                            $max_price = $product->get_variation_price('max');
                            if ($min_price != $max_price) {
                                $price_range_class = 'price_range';
                            }
                        }
                        ?>
                        <div class="wrap_product_price_att <?php echo esc_attr($price_range_class); ?>">
                            <div class="product-price d-flex">
                                <?php echo $product->get_price_html(); ?>
                            </div>
                            <?php
                            $capacity = $product->get_attribute('pa_pojemnosc');
                            if (!empty($capacity)) {
                                echo '<span class="capacity-separator">•</span> <span class="product-capacity">' . esc_html($capacity) . '</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="gift-in-cart-buttons d-flex">

            <a id="gift-in-cart-continue-shopping" class="btn_outline btn-big"
                href=""><?php echo __('Continue shopping', 'gift-in-cart-plugin'); ?></a>
            <a id="gift-in-cart-go-to-cart" class="btn btn-big"
                href="<?php echo wc_get_cart_url(); ?>"><?php echo __('Go to cart and checkout', 'gift-in-cart-plugin'); ?></a>
        </div>

        <?php echo do_shortcode('[cart_shipping_counter_progress_bar]'); ?>

    </div>
    <div class="gift-in-cart-popup-bottom d-flex flex-column">
        <div class="gift-in-cart-products-heading">
            <h3><?= __('Bestsellers', 'gift-in-cart-plugin'); ?></h3>
        </div>
        <?php
        // Pobierz produkty z kategorii "bestsellery"
        $bestsellery_products = array();
        $bestsellery_term = get_term_by('slug', 'bestsellery', 'product_cat');
        if ($bestsellery_term) {
            $args = array(
                'post_type'      => 'product',
                'posts_per_page' => 4,
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $bestsellery_term->term_id,
                    ),
                ),
            );
            $bestsellery_posts = get_posts($args);
            $bestsellery_products = wp_list_pluck($bestsellery_posts, 'ID');
        }

        // Połącz produkty powiązane (cross-sell i upsell) bez powtórzeń
        $related_products = array_unique(array_merge($product_cross, $product_upsell));

        // Pobierz ID produktów z koszyka, aby je wykluczyć
        $cart_product_ids = array();
        if (WC()->cart && !WC()->cart->is_empty()) {
            foreach (WC()->cart->get_cart() as $cart_item) {
                $cart_product_ids[] = $cart_item['product_id'];
                if (isset($cart_item['variation_id']) && $cart_item['variation_id'] > 0) {
                    $cart_product_ids[] = $cart_item['variation_id'];
                }
            }
        }

        // Połącz wszystkie produkty: najpierw bestsellery, potem powiązane
        $all_products = array_merge($bestsellery_products, $related_products);
        $all_products = array_unique($all_products);

        // Jeśli jest mniej niż 4 produkty, dodaj losowe (wykluczając te z koszyka i już dodane)
        if (count($all_products) < 4) {
            $needed = 4 - count($all_products);
            $exclude_ids = array_merge($all_products, $cart_product_ids);
            $exclude_ids[] = $product_id; // Wyklucz również aktualnie dodany produkt

            $args = array(
                'post_type'      => 'product',
                'posts_per_page' => $needed + 10, // Pobierz więcej, żeby mieć zapas
                'post__not_in'   => $exclude_ids,
                'orderby'        => 'rand'
            );
            $random_products = get_posts($args);
            $random_product_ids = wp_list_pluck($random_products, 'ID');

            $all_products = array_merge($all_products, array_slice($random_product_ids, 0, $needed));
            $all_products = array_unique($all_products);
        }

        // Ogranicz do 4 produktów
        $final_products = array_slice($all_products, 0, 4);
        ?>

        <div class="splide" id="gift-in-cart-products-splide">
            <div class="splide__track">
                <div class="splide__list" id="gift-in-cart-products-list-bestsellers">
                    <?php foreach ($final_products as $product_id) {
                        $product = wc_get_product($product_id);
                        if (!$product || !$product->is_purchasable()) {
                            continue;
                        }
                    ?>
                        <div class="splide__slide">
                            <div class="woocommerce-cart-form__cart-item d-flex gift-in-cart-popup-product-item">
                                <div class="cart-item-left">
                                    <div class="product-thumbnail">
                                        <?php
                                        $thumbnail = $product->get_image();
                                        $product_permalink = $product->get_permalink();
                                        if ($product_permalink) {
                                            printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail);
                                        } else {
                                            echo $thumbnail;
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="cart-item-middle col d-flex flex-column justify-content-between h-100">
                                    <div class="cart-item-middle-contetn">
                                        <?php
                                        $linie_terms = get_the_terms($product_id, 'linia');
                                        if (!is_wp_error($linie_terms) && !empty($linie_terms)) {
                                            $linie_names = array();
                                            foreach ($linie_terms as $term) {
                                                $linie_names[] = esc_html($term->name);
                                            }
                                            echo '<div class="cart-item-linia">' . implode(', ', $linie_names) . '</div>';
                                        }
                                        ?>
                                        <div class="product-name">
                                            <?php
                                            echo wp_kses_post(get_the_title($product_id));
                                            ?>
                                        </div>
                                        <div class="product-slogan">
                                            <?php
                                            $product_slogan = get_field('slogan', $product_id);
                                            if (empty($product_slogan)) {
                                                $product_slogan = $product->get_short_description();
                                            }
                                            echo esc_html($product_slogan);
                                            ?>
                                        </div>
                                        <?php
                                        $capacity = $product->get_attribute('pa_pojemnosc');
                                        if (!empty($capacity)) {
                                            echo '<div class="product-capacity">' . esc_html($capacity) . '</div>';
                                        }
                                        // Sprawdź czy produkt ma warianty i przedział cenowy
                                        $price_range_class = '';
                                        if ($product->is_type('variable')) {
                                            $min_price = $product->get_variation_price('min');
                                            $max_price = $product->get_variation_price('max');
                                            if ($min_price != $max_price) {
                                                $price_range_class = 'price_range';
                                            }
                                        }
                                        ?>
                                        <div class="wrap_product_price_att <?php echo esc_attr($price_range_class); ?>">
                                            <div class="product-price d-flex">
                                                <?php echo $product->get_price_html(); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="cart-item-btn">
                                        <div class="add-to-cart-wrapper">
                                            <?php
                                            echo apply_filters(
                                                'woocommerce_loop_add_to_cart_link',
                                                sprintf(
                                                    '<a href="%s" aria-describedby="woocommerce_loop_add_to_cart_link_describedby_%s" data-quantity="1" class="btn_outline w-100 ajax_add_to_cart d-flex justify-content-center align-items-center" data-product_id="%s" data-product_sku="%s" rel="nofollow">%s</a>',
                                                    esc_url($product->add_to_cart_url()),
                                                    esc_attr($product_id),
                                                    esc_attr($product_id),
                                                    esc_attr($product->get_sku()),
                                                    sprintf(

                                                        __('Add', 'gift-in-cart-plugin'),
                                                        $product->get_price_html()
                                                    )
                                                ),
                                                $product
                                            );
                                            ?>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>