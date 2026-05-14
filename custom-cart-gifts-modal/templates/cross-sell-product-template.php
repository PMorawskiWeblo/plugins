<?php

$product = wc_get_product($product_id);

if ($product) {
    $product_price = $product->get_price();

    if ($product_discount_type === 'percent') {
        $discounted_price = $product_price * (1 - ($product_discount / 100));
    } else {
        $discounted_price = $product_price - $product_discount;
    }
    $dane_produktu = array(
        'id' => $product_id,
        'nazwa' => $product->get_name(),
        'thumbnail' => get_the_post_thumbnail_url($product_id, 'full'),
        'cena_regularna' => $product_price,
        'cena_promocyjna' => $discounted_price,
        'link_produktu' => get_permalink($product_id),
        'link_dodaj_do_koszyka' => $product->add_to_cart_url(),
        'product_type' => $product->get_type(),
        'product_url' => $product->get_permalink(),
        'level_index' =>  $level_index
    );
}

$is_variable = $product->is_type('variable');

$product_data = $dane_produktu;

if ($product_data) {
?>
    <div class="cross-sell-product <?php echo $product_in_cart ? 'in-cart' : ''; ?>"
        data-product-id="<?php echo esc_attr($product_data['id']); ?>">
        <?php if (!isset($product_data['blad'])): ?>
            <div class="cross-sell-product-image">
                <a href="<?php echo esc_url($product_data['link_produktu']); ?>">
                    <img src="<?php echo esc_url($product_data['thumbnail']); ?>"
                        alt="<?php echo esc_attr($product_data['nazwa']); ?>">
                </a>
            </div>

            <div class="cross-sell-product-info">
                <h3 class="cross-sell-product-title">
                    <a href="<?php echo esc_url($product_data['link_produktu']); ?>">
                        <?php echo esc_html($product_data['nazwa']); ?>
                    </a>
                </h3>
                <div class="price-wrap">

                    <div class="cross-sell-product-price">
                        <div class="price">
                            <?php
                            if (!empty($product_data['cena_promocyjna']) || $product_data['cena_promocyjna'] === 0): ?>
                                <del><?php echo wc_price($product_data['cena_regularna']); ?></del>
                                <ins><?php echo wc_price($product_data['cena_promocyjna']); ?></ins>
                            <?php else: ?>
                                <span><?php echo wc_price($product_data['cena_regularna']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php
                        if ($product_discount_type && $product_discount) {
                            if ($product_discount_type == 'percent') {
                        ?>
                                <div class="discount">
                                    -<?= $product_discount ?>%
                                </div>
                            <?php
                            } else {
                            ?>
                                <div class="discount">
                                    -<?= wc_price($product_discount); ?>
                                </div>
                            <?php
                            }
                            ?>
                        <?php
                        }

                        ?>
                    </div>
                    <?php
                    if ($is_variable) {
                    ?>
                        <div class="cross-sell-product-button">
                            <a data-no-modal="true" href="<?php echo esc_url($product_data['product_url']); ?>" class="button"
                                data-level-index="<?php echo esc_attr($product_data['level_index']); ?>"
                                data-cross-sell-product-id="<?php echo esc_attr($product_data['id']); ?>"
                                data-product_id="<?php echo esc_attr($product_data['id']); ?>">
                                <?php esc_html_e('Select variant', 'custom-cart-gifts-modal'); ?>
                            </a>
                        </div>
                    <?php
                    } else {
                    ?>
                        <div class="cross-sell-product-button">
                            <?php
                            if ($product_in_cart) {
                            ?>
                                <a data-no-modal="true" class="button remove_cross_sell_product_from_cart"
                                    data-level-index="<?php echo esc_attr($product_data['level_index']); ?>"
                                    data-cross-sell-product-id="<?php echo esc_attr($product_data['id']); ?>"
                                    data-product_id="<?php echo esc_attr($product_data['id']); ?>">
                                    <?php esc_html_e('Remove from cart', 'custom-cart-gifts-modal'); ?>
                                </a>
                            <?php
                            } else {
                            ?>
                                <a data-no-modal="true" href="<?php echo esc_url($product_data['link_dodaj_do_koszyka']); ?>"
                                    class="button add_to_cart_button ajax_add_to_cart btn btn_dark"
                                    data-level-index="<?php echo esc_attr($product_data['level_index']); ?>"
                                    data-cross-sell-product-id="<?php echo esc_attr($product_data['id']); ?>"
                                    data-product_id="<?php echo esc_attr($product_data['id']); ?>">
                                    <?php esc_html_e('Add to cart', 'custom-cart-gifts-modal'); ?>
                                </a>
                            <?php
                            }
                            ?>
                        </div>
                    <?php
                    }
                    ?>

                </div>
            </div>
        <?php else: ?>

        <?php endif; ?>
    </div>

<?php
}
?>