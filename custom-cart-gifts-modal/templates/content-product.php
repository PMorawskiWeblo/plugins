<?php

/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

defined('ABSPATH') || exit;

global $product;

// Check if the product is a valid WooCommerce product and ensure its visibility before proceeding.
if (! is_a($product, WC_Product::class) || ! $product->is_visible()) {
    return;
}
?>
<div <?php wc_product_class('custom_single_product_view_block', $product); ?>>
    <div class="wrap_product_card grid_product_card">
        <?php echo do_shortcode('[wishlist_button]'); ?>
        <div class="product_card_image">
            <?php woocommerce_template_loop_product_link_open(); ?>
            <?php woocommerce_template_loop_product_thumbnail_custom(); ?>
            <?php woocommerce_template_loop_product_link_close(); ?>
        </div>
        <div class="product_content_body">
            <div class="product_card_content">
                <?php woocommerce_template_loop_product_link_open(); ?>
                <div class="wrap_woocommerce_template_single_rating">
                    <?php woocommerce_template_single_rating(); ?>
                </div>
                <?php woocommerce_template_loop_product_link_close(); ?>
                <?php include get_template_directory() . '/components/badge_collection_bar.php'; ?>
                <?php woocommerce_template_loop_product_link_open(); ?>
                <?php woocommerce_template_loop_product_title(); ?>
                <?php the_excerpt(); ?>
                <?php woocommerce_template_loop_product_link_close(); ?>
            </div>
            <div class="product_cart_footer">
                <?php woocommerce_template_loop_price(); ?>
                <?php woocommerce_template_loop_add_to_cart(); ?>
                <?php include get_template_directory() . '/components/parts/order_counter.php'; ?>
            </div>
        </div>
    </div>
</div>