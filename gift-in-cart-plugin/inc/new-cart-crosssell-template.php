<?php
$cart = WC()->cart->get_cart();

$crossSellProducts = array();

foreach ($cart as $cart_item_key => $cart_item) {
	$product = $cart_item['data'];
	$cross_sell_ids = $product->get_cross_sell_ids();

	foreach ($cross_sell_ids as $cross_sell_id) {
		$found_in_cart = false;
		foreach ($cart as $cart_item_key2 => $cart_item2) {
			if ($cross_sell_id == $cart_item2['product_id']) {
				$found_in_cart = true;
				break;
			}
		}

		if (!$found_in_cart) {
			$crossSellProducts[] = $cross_sell_id;
		}
	}
}

$crossSellProducts = array_unique($crossSellProducts);

$cartCrossProd = array();
foreach ($crossSellProducts as $cross_sell_id) {
	$product = wc_get_product($cross_sell_id);
	if ($product) {
		$cartCrossProd[] = $product;
	}
}

?>

<?php if (!empty($cartCrossProd)) { ?>
<div class="wrap-new-cart-crosssell-template">
    <div class="new-cart-layout-cross">
        <h2><?= __('Complete your care', 'gift-in-cart-plugin'); ?></h2>
        <div class="row">
            <div class="col-12">
                <div class="splide" id="cross-products-splide">
                    <div class="splide__track">
                        <div class="cross-products splide__list">
                            <?php foreach ($cartCrossProd as $produkt) {
									$rating_count = $produkt->get_rating_count();
									$review_count = $produkt->get_review_count();
									$average = $produkt->get_average_rating();
									$average_procent = $average * 20;
								?>
                            <?php if ($produkt->get_type() != 'variable') { ?>
                            <div class="splide__slide cross-products-product "
                                data-giftid="<?php echo $produkt->get_id(); ?>">
                                <a href="<?php echo get_permalink($produkt->get_id()); ?>">
                                    <div class="cross-products-product-image">
                                        <?php echo (get_the_post_thumbnail($produkt->get_id(), 'full')); ?>
                                    </div>
                                    <div class="cross-products-product-wrap-content">
                                        <div class="cross-products-product-name">
                                            <?php echo ($produkt->get_name()); ?>
                                        </div>
                                        <div class="cross-products-product-slogan">
                                            <?php the_field('slogan', $produkt->get_id()); ?>
                                        </div>
                                        <div class="cross-products-product-star d-flex">
                                            <div class="rating_stars">
                                                <?php
															if ($rating_count > 0): ?>
                                                <div class="woocommerce-product-rating d-flex">
                                                    <div class="review-rating" style="top: 2px;">
                                                        <?php echo wc_get_rating_html($average, $rating_count);  ?>
                                                    </div>
                                                    <?php if (comments_open()): ?>
                                                    <?php //phpcs:disable 
																		?>
                                                    <div alt="Product reviews" class="woocommerce-review-link ms-md-2"
                                                        rel="nofollow">
                                                        (<?php printf(_n('%s', '%s', $review_count, 'woocommerce'), '<span class="count">' . esc_html($review_count) . '</span>'); ?>)
                                                    </div>
                                                    <?php // phpcs:enable 
																		?>

                                                    <?php endif ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($average) { ?>
                                            <div class="rating_average"><?= $average; ?></div>
                                            <div class="rating_count">(<?= $review_count; ?>
                                                <?= __('opinions', 'gift-in-cart-plugin'); ?>)</div>
                                            <?php }; ?>
                                        </div>
                                    </div>
                                </a>
                                <div class="cross-products-product-action">

                                    <?php
												$id = esc_attr($produkt->get_id());
												echo apply_filters(
													'woocommerce_loop_add_to_cart_link',
													sprintf(
														'<a href="%s" aria-describedby="woocommerce_loop_add_to_cart_link_describedby_%s" data-quantity="1" class="button product_type_simple add_to_cart_button ajax_add_to_cart btn w-100 gift-btn" data-product_id="%s" data-product_sku="%s" rel="nofollow">%s</a>
        <span id="woocommerce_loop_add_to_cart_link_describedby_%s" class="screen-reader-text">%s</span>',
														esc_url($produkt->add_to_cart_url()),
														$id,
														$id,
														esc_attr($produkt->get_sku()),
														sprintf(
															'%s <span class="new-price">%s</span>',
															__('Add a ', 'gift-in-cart-plugin'),
															$produkt->get_price_html()
														),
														$id,
														__('Click to add the product to your cart', 'gift-in-cart-plugin')
													),
													$produkt
												);
												?>



                                </div>
                            </div>

                            <?php }
								} ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php } ?>