<?php

$product = wc_get_product($product_id);

if ($product) {
    $dane_produktu = array(
        'id' => $product_id,
        'nazwa' => $product->get_name(),
        'thumbnail' => get_the_post_thumbnail_url($product_id, 'full'),
        'cena_regularna' => $product->get_regular_price(),
        'cena_promocyjna' => $product->get_sale_price(),
        'link_produktu' => get_permalink($product_id),
        'link_dodaj_do_koszyka' => $product->add_to_cart_url(),
        'product_type' => $product->get_type(),
        'product_url' => $product->get_permalink()
    );
}

if (empty($dane_produktu['thumbnail']) && $product->is_type('variable')) {
    $parent_id = $product->get_parent_id();
    if ($parent_id) {
        $dane_produktu['thumbnail'] = get_the_post_thumbnail_url($parent_id, 'full');
    }
}

$is_variable = $product->is_type('variable');
$product_data = $dane_produktu;

?>

<div class="cross-sell-product" data-product-id="<?php echo esc_attr($product_data['id']); ?>">

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
        <div class="wrap">
            <?php
                if (!$is_variable) {
                ?>
            <div class="cross-sell-product-price">
                <?php if (!empty($product_data['cena_promocyjna'])): ?>
                <del><?php echo wc_price($product_data['cena_regularna']); ?></del>
                <ins><?php echo wc_price($product_data['cena_promocyjna']); ?></ins>
                <?php else: ?>
                <span><?php echo wc_price($product_data['cena_regularna']); ?></span>
                <?php endif; ?>
            </div>
            <?php
                } else {
                ?>
            <div class="cross-sell-product-price">
                <?php

                        $variation_prices = $product->get_variation_prices();
                        $min_price = current($variation_prices['price']);
                        $max_price = end($variation_prices['price']);

                        if ($min_price !== $max_price) {
                            echo wc_price($min_price) . ' - ' . wc_price($max_price);
                        } else {
                            echo wc_price($min_price);
                        }

                        ?>
            </div>
            <?php
                }
                ?>

            <?php
                if ($is_variable) {
                ?>
            <div class="cross-sell-product-button">
                <a data-no-modal="true" href="<?php echo esc_url($product_data['product_url']); ?>" class="button"
                    data-product_id="<?php echo esc_attr($product_data['id']); ?>">
                    <?php esc_html_e('Select variant', 'custom-cart-gifts-modal'); ?>
                </a>
            </div>
            <?php
                } else {
                ?>
            <div class="cross-sell-product-button">
                <a data-no-modal="true" href="<?php echo esc_url($product_data['link_dodaj_do_koszyka']); ?>"
                    class="button add_to_cart_button ajax_add_to_cart btn btn_dark"
                    data-product_id="<?php echo esc_attr($product_data['id']); ?>">
                    <?php esc_html_e('Add to cart', 'custom-cart-gifts-modal'); ?>
                </a>
            </div>
            <?php
                }
                ?>
        </div>
    </div>
    <?php else: ?>

    <?php endif; ?>
</div>