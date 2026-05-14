<?php

/**
 * Template for displaying search result product item.
 *
 * @package Weblo_Search_Engine
 * 
 * @var int $product_id Product ID.
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

$product = wc_get_product($product_id);
if (! $product) {
	return;
}

// Get taxonomy "linia" terms.
$linia_terms = get_the_terms($product_id, 'linia');
$linia_name = '';
if ($linia_terms && ! is_wp_error($linia_terms) && ! empty($linia_terms)) {
	$linia_term = reset($linia_terms); // Get first term.
	$linia_name = $linia_term->name;
}

// Get product_slogan custom field or short description.
$product_slogan = get_post_meta($product_id, 'product_slogan', true);
$description_text = '';

if (! empty($product_slogan)) {
	$description_text = $product_slogan;
} else {
	// Use short description or excerpt.
	$short_description = $product->get_short_description();
	if (! empty($short_description)) {
		$description_text = wp_trim_words($short_description, 15);
	} else {
		$description_text = wp_trim_words(get_the_excerpt($product_id), 15);
	}
}
?>
<div class="search-product">
    <div class="product-image">
        <a href="<?php echo esc_url(get_permalink($product_id)); ?>">
            <?php echo get_the_post_thumbnail($product_id, 'thumbnail'); ?>
        </a>
    </div>
    <div class="product-info">
        <div class="product-info-line-title-description">
            <div class="product-info-line-title">
                <?php if (! empty($linia_name)) : ?>
                <span class="product-line"><?php echo esc_html($linia_name); ?></span>
                <?php endif; ?>
                <h4 class="product-title">
                    <a href="<?php echo esc_url(get_permalink($product_id)); ?>">
                        <?php echo esc_html(get_the_title($product_id)); ?>
                    </a>
                </h4>
            </div>
            <?php if (! empty($description_text)) : ?>
            <div class="description-text"><?php echo esc_html($description_text); ?></div>
            <?php endif; ?>
        </div>

        <span class="price"><?php echo wp_kses_post($product->get_price_html()); ?></span>
    </div>
</div>