<?php

/**
 * Settings page view.
 *
 * @package Weblo_Search_Engine
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

// Handle form submission.
if (isset($_POST['submit']) && check_admin_referer('weblo_search_settings')) {
	update_option('weblo_search_placeholder', sanitize_text_field($_POST['weblo_search_placeholder']));
	update_option('weblo_search_trigger_class', sanitize_text_field($_POST['weblo_search_trigger_class']));
	update_option('weblo_search_limit', absint($_POST['weblo_search_limit']));
	update_option('weblo_search_default_hidden', sanitize_text_field($_POST['weblo_search_default_hidden']));

	// Handle recommended products.
	$recommended_products = isset($_POST['weblo_search_recommended_products'])
		? array_map('absint', $_POST['weblo_search_recommended_products'])
		: array();
	update_option('weblo_search_recommended_products', $recommended_products);

	// Handle recommended categories.
	$recommended_categories = isset($_POST['weblo_search_recommended_categories'])
		? array_map('absint', $_POST['weblo_search_recommended_categories'])
		: array();
	update_option('weblo_search_recommended_categories', $recommended_categories);

	// Handle show promotions.
	$show_promotions = isset($_POST['weblo_search_show_promotions']) ? '1' : '0';
	update_option('weblo_search_show_promotions', $show_promotions);

	// Handle show all products.
	$show_all_products = isset($_POST['weblo_search_show_all_products']) ? '1' : '0';
	update_option('weblo_search_show_all_products', $show_all_products);

	// Handle custom links.
	$custom_links = array();
	if (isset($_POST['weblo_custom_link_url']) && isset($_POST['weblo_custom_link_text']) && is_array($_POST['weblo_custom_link_url']) && is_array($_POST['weblo_custom_link_text'])) {
		$urls = array_map('sanitize_text_field', wp_unslash($_POST['weblo_custom_link_url']));
		$texts = array_map('sanitize_text_field', wp_unslash($_POST['weblo_custom_link_text']));
		foreach ($urls as $index => $url) {
			$url = esc_url_raw($url);
			$text = isset($texts[$index]) ? sanitize_text_field($texts[$index]) : '';
			if (! empty($url) && ! empty($text) && filter_var($url, FILTER_VALIDATE_URL) !== false) {
				$custom_links[] = array(
					'url'  => $url,
					'text' => $text,
				);
			}
		}
	}
	update_option('weblo_search_custom_links', $custom_links);

	echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully.', 'weblo-search-engine') . '</p></div>';
}

$placeholder = get_option('weblo_search_placeholder', __('Search products...', 'weblo-search-engine'));
$trigger_class = get_option('weblo_search_trigger_class', 'search_engine_icon');
$limit = get_option('weblo_search_limit', 10);
$default_hidden = get_option('weblo_search_default_hidden', '1');
$recommended_products = get_option('weblo_search_recommended_products', array());
$recommended_categories = get_option('weblo_search_recommended_categories', array());
$show_promotions = get_option('weblo_search_show_promotions', '0');
$show_all_products = get_option('weblo_search_show_all_products', '0');
$custom_links = get_option('weblo_search_custom_links', array());

// Get all products for multi-select - only published and visible.
// Note: We don't cache product objects as they may become stale.
// Instead, we use a two-step approach: get IDs first, then filter.
$all_product_ids = wc_get_products(array(
	'limit'  => -1,
	'status' => 'publish',
	'return' => 'ids',
));

// Filter out hidden products.
$visible_product_ids = array();
if (! empty($all_product_ids)) {
	foreach ($all_product_ids as $product_id) {
		$product = wc_get_product($product_id);
		if ($product && 'hidden' !== $product->get_catalog_visibility()) {
			$visible_product_ids[] = $product_id;
		}
	}
}

// Get full product objects only for visible products (limited to first 500 for performance).
$all_products = array();
if (! empty($visible_product_ids)) {
	$limited_ids = array_slice($visible_product_ids, 0, 500);
	$all_products = wc_get_products(array(
		'include' => $limited_ids,
		'limit'   => 500,
		'orderby' => 'title',
		'order'   => 'ASC',
	));
}

// Get all categories for multi-select.
$all_categories = get_terms(array(
	'taxonomy'   => 'product_cat',
	'hide_empty' => false,
));
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('weblo_search_settings'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label
                        for="weblo_search_placeholder"><?php esc_html_e('Search placeholder', 'weblo-search-engine'); ?></label>
                </th>
                <td>
                    <input type="text" id="weblo_search_placeholder" name="weblo_search_placeholder"
                        value="<?php echo esc_attr($placeholder); ?>" class="regular-text" />
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label
                        for="weblo_search_trigger_class"><?php esc_html_e('Trigger CSS class', 'weblo-search-engine'); ?></label>
                </th>
                <td>
                    <input type="text" id="weblo_search_trigger_class" name="weblo_search_trigger_class"
                        value="<?php echo esc_attr($trigger_class); ?>" class="regular-text" />
                    <p class="description">
                        <?php esc_html_e('CSS class that will trigger the search engine toggle when clicked.', 'weblo-search-engine'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label
                        for="weblo_search_limit"><?php esc_html_e('Search results limit', 'weblo-search-engine'); ?></label>
                </th>
                <td>
                    <input type="number" id="weblo_search_limit" name="weblo_search_limit"
                        value="<?php echo esc_attr($limit); ?>" class="small-text" min="1" max="100" />
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label
                        for="weblo_search_default_hidden"><?php esc_html_e('Search form hidden by default', 'weblo-search-engine'); ?></label>
                </th>
                <td>
                    <select id="weblo_search_default_hidden" name="weblo_search_default_hidden">
                        <option value="1" <?php selected($default_hidden, '1'); ?>>
                            <?php esc_html_e('Yes', 'weblo-search-engine'); ?></option>
                        <option value="0" <?php selected($default_hidden, '0'); ?>>
                            <?php esc_html_e('No', 'weblo-search-engine'); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label
                        for="weblo_search_recommended_products"><?php esc_html_e('Recommended Products', 'weblo-search-engine'); ?></label>
                </th>
                <td>
                    <select id="weblo_search_recommended_products" name="weblo_search_recommended_products[]" multiple
                        class="weblo-select2" style="width: 100%;">
                        <?php foreach ($all_products as $product) : ?>
                        <option value="<?php echo esc_attr($product->get_id()); ?>"
                            <?php selected(in_array($product->get_id(), $recommended_products, true)); ?>>
                            <?php echo esc_html($product->get_name()); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Search and select multiple products.', 'weblo-search-engine'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label
                        for="weblo_search_recommended_categories"><?php esc_html_e('Recommended Categories', 'weblo-search-engine'); ?></label>
                </th>
                <td>
                    <select id="weblo_search_recommended_categories" name="weblo_search_recommended_categories[]"
                        multiple class="weblo-select2" style="width: 100%;">
                        <?php foreach ($all_categories as $category) : ?>
                        <option value="<?php echo esc_attr($category->term_id); ?>"
                            <?php selected(in_array($category->term_id, $recommended_categories, true)); ?>>
                            <?php echo esc_html($category->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Search and select multiple categories.', 'weblo-search-engine'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label
                        for="weblo_search_show_promotions"><?php esc_html_e('Show promotions (in categories)', 'weblo-search-engine'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="weblo_search_show_promotions" name="weblo_search_show_promotions"
                            value="1" <?php checked($show_promotions, '1'); ?> />
                        <?php esc_html_e('Yes', 'weblo-search-engine'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Show a link to promotions page in the categories section.', 'weblo-search-engine'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label
                        for="weblo_search_show_all_products"><?php esc_html_e('Show all products', 'weblo-search-engine'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="weblo_search_show_all_products" name="weblo_search_show_all_products"
                            value="1" <?php checked($show_all_products, '1'); ?> />
                        <?php esc_html_e('Yes', 'weblo-search-engine'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Show a link to all products (shop page) in the categories section.', 'weblo-search-engine'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Custom Links', 'weblo-search-engine'); ?></label>
                </th>
                <td>
                    <div id="weblo-custom-links-container">
                        <?php if (! empty($custom_links)) : ?>
                        <?php foreach ($custom_links as $index => $link) : ?>
                        <div class="weblo-custom-link-row" style="margin-bottom: 10px;">
                            <input type="url" name="weblo_custom_link_url[]"
                                value="<?php echo esc_attr($link['url']); ?>"
                                placeholder="<?php esc_attr_e('URL', 'weblo-search-engine'); ?>" class="regular-text"
                                style="width: 45%; margin-right: 10px;" />
                            <input type="text" name="weblo_custom_link_text[]"
                                value="<?php echo esc_attr($link['text']); ?>"
                                placeholder="<?php esc_attr_e('Link Text', 'weblo-search-engine'); ?>"
                                class="regular-text" style="width: 35%; margin-right: 10px;" />
                            <button type="button" class="button weblo-remove-link"
                                style="width: 15%;"><?php esc_html_e('Remove', 'weblo-search-engine'); ?></button>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="weblo-add-custom-link"
                        class="button"><?php esc_html_e('Add Custom Link', 'weblo-search-engine'); ?></button>
                    <p class="description">
                        <?php esc_html_e('Add custom links that will appear in Recommended Categories section.', 'weblo-search-engine'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>