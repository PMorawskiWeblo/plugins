<?php

/**
 * The shortcode functionality of the plugin.
 *
 * @package Weblo_Search_Engine
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

/**
 * The shortcode functionality of the plugin.
 */
class Weblo_Search_Shortcode
{

	/**
	 * Register the shortcodes.
	 */
	public function register_shortcode()
	{
		add_shortcode('weblo_search_engine', array($this, 'render_shortcode'));
		add_shortcode('weblo_search_input', array($this, 'render_input_shortcode'));
		add_shortcode('weblo_search_results', array($this, 'render_results_shortcode'));
	}

	/**
	 * Render the shortcode output.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_shortcode($atts)
	{
		$atts = shortcode_atts(
			array(
				'default_hidden' => get_option('weblo_search_default_hidden', '1'),
				'class'          => '',
			),
			$atts,
			'weblo_search_engine'
		);

		$default_hidden = '1' === $atts['default_hidden'] ? '1' : '0';
		$hidden_class = '1' === $default_hidden ? 'weblo-search-hidden' : '';
		$custom_class = ! empty($atts['class']) ? sanitize_html_class($atts['class']) : '';

		// Generate unique ID for this instance.
		static $instance_counter = 0;
		$instance_counter++;
		$instance_id = 'weblo-search-' . $instance_counter;
		$container_id = $instance_id . '-container';

		$placeholder = get_option('weblo_search_placeholder', __('Search products...', 'weblo-search-engine'));
		$recommended_products = get_option('weblo_search_recommended_products', array());
		$recommended_categories = get_option('weblo_search_recommended_categories', array());
		$show_promotions = get_option('weblo_search_show_promotions', '0');
		$show_all_products = get_option('weblo_search_show_all_products', '0');
		$custom_links = get_option('weblo_search_custom_links', array());

		ob_start();
?>
		<div id="<?php echo esc_attr($container_id); ?>"
			class="weblo-search-engine <?php echo esc_attr($hidden_class); ?> <?php echo esc_attr($custom_class); ?>"
			data-instance-id="<?php echo esc_attr($instance_id); ?>">
			<div class="weblo-search-engine-container-content">
				<div class="wrap_input_close d-flex align-items-center justify-content-between">
					<div class="weblo-search-input-wrapper">
						<input type="text" id="<?php echo esc_attr($instance_id); ?>-input" class="weblo-search-input"
							placeholder="<?php echo esc_attr($placeholder); ?>" autocomplete="off"
							data-instance-id="<?php echo esc_attr($instance_id); ?>" />
						<button type="button" class="weblo-search-clear-input"
							id="<?php echo esc_attr($instance_id); ?>-clear-input"
							aria-label="<?php esc_attr_e('Clear input', 'weblo-search-engine'); ?>" style="display: none;"
							data-instance-id="<?php echo esc_attr($instance_id); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
								<path d="M12.5303 0.53125L0.530273 12.5312M0.530273 0.53125L12.5303 12.5312" stroke="#47170D"
									stroke-width="1.5" stroke-linejoin="round" />
							</svg>
						</button>
						<span class="weblo-search-loading"
							style="display: none;"><?php esc_html_e('Loading...', 'weblo-search-engine'); ?></span>
					</div>
					<button type="button" class="weblo-search-close-container"
						id="<?php echo esc_attr($instance_id); ?>-close-container"
						aria-label="<?php esc_attr_e('Close search', 'weblo-search-engine'); ?>"
						data-instance-id="<?php echo esc_attr($instance_id); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
							<path d="M12.5303 0.53125L0.530273 12.5312M0.530273 0.53125L12.5303 12.5312" stroke="#47170D"
								stroke-width="1.5" stroke-linejoin="round" />
						</svg>
					</button>
				</div>
				<div id="<?php echo esc_attr($instance_id); ?>-no-results" class="weblo-search-no-results"
					style="display: none;"></div>

				<div class="weblo-search-results-wrapper">
					<div class="weblo-search-column weblo-search-products no-results"
						data-instance-id="<?php echo esc_attr($instance_id); ?>">
						<h3 id="<?php echo esc_attr($instance_id); ?>-products-title" class="weblo-search-section-title"
							data-recommended-text="<?php echo esc_attr__('Recommended Products', 'weblo-search-engine'); ?>"
							data-search-text="<?php echo esc_attr__('Search Results', 'weblo-search-engine'); ?>">
							<?php esc_html_e('Recommended Products', 'weblo-search-engine'); ?></h3>
						<div id="<?php echo esc_attr($instance_id); ?>-products-container"
							class="weblo-search-products-container">
							<div id="<?php echo esc_attr($instance_id); ?>-recommended-products"
								class="weblo-search-recommended-products">
								<?php
								if (! empty($recommended_products)) {
									foreach ($recommended_products as $product_id) {
										$product_id = absint($product_id);
										if ($product_id > 0) {
											// render_product_item will check visibility.
											$this->render_product_item($product_id);
										}
									}
								}
								?>
							</div>
							<div id="<?php echo esc_attr($instance_id); ?>-results" class="weblo-search-results"
								style="display: none;"></div>

						</div>
					</div>

					<div class="weblo-search-column weblo-search-categories">
						<h3 id="<?php echo esc_attr($instance_id); ?>-categories-title" class="weblo-search-section-title"
							data-recommended-text="<?php echo esc_attr__('Recommended Categories', 'weblo-search-engine'); ?>"
							data-search-text="<?php echo esc_attr__('Categories', 'weblo-search-engine'); ?>">
							<?php esc_html_e('Recommended Categories', 'weblo-search-engine'); ?></h3>
						<div id="<?php echo esc_attr($instance_id); ?>-categories-container"
							class="weblo-search-categories-container">
							<div id="<?php echo esc_attr($instance_id); ?>-recommended-categories"
								class="weblo-search-recommended-categories">
								<?php
								if (! empty($recommended_categories)) {
									foreach ($recommended_categories as $category_id) {
										$category_id = absint($category_id);
										if ($category_id > 0) {
											$term = get_term($category_id, 'product_cat');
											if ($term && ! is_wp_error($term)) {
												$this->render_category_item($term);
											}
										}
									}
								}

								// Show promotions link.
								if ('1' === $show_promotions) {
									$this->render_promotions_link();
								}

								// Show all products link.
								if ('1' === $show_all_products) {
									$this->render_all_products_link();
								}

								// Render custom links.
								if (! empty($custom_links)) {
									foreach ($custom_links as $link) {
										$this->render_custom_link($link);
									}
								}
								?>
							</div>
							<div id="<?php echo esc_attr($instance_id); ?>-categories-results"
								class="weblo-search-categories-results" style="display: none;"></div>
						</div>
					</div>
				</div>
			</div>


		</div>
	<?php
		return ob_get_clean();
	}

	/**
	 * Render product item.
	 *
	 * @param int $product_id Product ID.
	 */
	private function render_product_item($product_id)
	{
		$product = wc_get_product($product_id);
		if (! $product) {
			return;
		}

		// Skip hidden products.
		if ('hidden' === $product->get_catalog_visibility()) {
			return;
		}

		// Use unified template.
		$template_path = WEBLO_SEARCH_ENGINE_PATH . 'templates/search-results-template.php';
		if (file_exists($template_path)) {
			include $template_path;
		}
	}

	/**
	 * Render category item.
	 *
	 * @param WP_Term $term Category term.
	 */
	private function render_category_item($term)
	{
		$category_link = get_term_link($term, 'product_cat');
	?>
		<div class="search-category">
			<a href="<?php echo esc_url($category_link); ?>">
				<?php echo esc_html($term->name); ?>
			</a>
		</div>
	<?php
	}

	/**
	 * Render promotions link.
	 */
	private function render_promotions_link()
	{
		// Get shop page URL and add on_sale filter.
		$shop_url = wc_get_page_permalink('shop');
		if (! $shop_url) {
			$shop_url = home_url('/');
		}

		// Add our custom query var for filtering sale products.
		$promotions_url = add_query_arg(array(
			'on_sale' => '1',
		), $shop_url);

	?>
		<div class="search-category">
			<a href="<?php echo esc_url($promotions_url); ?>">
				<?php esc_html_e('Promocje', 'weblo-search-engine'); ?>
			</a>
		</div>
	<?php
	}

	/**
	 * Render all products link.
	 */
	private function render_all_products_link()
	{
		$shop_url = wc_get_page_permalink('shop');
		if (! $shop_url) {
			$shop_url = home_url('/');
		}
	?>
		<div class="search-category">
			<a href="<?php echo esc_url($shop_url); ?>">
				<?php esc_html_e('All products', 'weblo-search-engine'); ?>
			</a>
		</div>
	<?php
	}

	/**
	 * Render custom link item.
	 *
	 * @param array $link Link data with 'url' and 'text' keys.
	 */
	private function render_custom_link($link)
	{
		if (empty($link['url']) || empty($link['text'])) {
			return;
		}
	?>
		<div class="search-category">
			<a href="<?php echo esc_url($link['url']); ?>">
				<?php echo esc_html($link['text']); ?>
			</a>
		</div>
	<?php
	}

	/**
	 * Render input shortcode (only input field).
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_input_shortcode($atts)
	{
		$atts = shortcode_atts(
			array(
				'instance' => 'weblo-search-1',
				'class'    => '',
			),
			$atts,
			'weblo_search_input'
		);

		$instance_id = sanitize_html_class($atts['instance']);
		$custom_class = ! empty($atts['class']) ? sanitize_html_class($atts['class']) : '';
		$placeholder = get_option('weblo_search_placeholder', __('Search products...', 'weblo-search-engine'));

		ob_start();
	?>
		<div class="weblo-search-input-wrapper <?php echo esc_attr($custom_class); ?>"
			data-instance-id="<?php echo esc_attr($instance_id); ?>">
			<input type="text" id="<?php echo esc_attr($instance_id); ?>-input" class="weblo-search-input"
				placeholder="<?php echo esc_attr($placeholder); ?>" autocomplete="off"
				data-instance-id="<?php echo esc_attr($instance_id); ?>" />
			<button type="button" class="weblo-search-clear-input" id="<?php echo esc_attr($instance_id); ?>-clear-input"
				aria-label="<?php esc_attr_e('Clear input', 'weblo-search-engine'); ?>" style="display: none;"
				data-instance-id="<?php echo esc_attr($instance_id); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
					<path d="M12.5303 0.53125L0.530273 12.5312M0.530273 0.53125L12.5303 12.5312" stroke="#47170D"
						stroke-width="1.5" stroke-linejoin="round" />
				</svg>
			</button>
			<span class="weblo-search-loading"
				style="display: none;"><?php esc_html_e('Loading...', 'weblo-search-engine'); ?></span>
		</div>
		<div id="<?php echo esc_attr($instance_id); ?>-no-results" class="weblo-search-no-results" style="display: none;"></div>
	<?php
		return ob_get_clean();
	}

	/**
	 * Render results shortcode (only products results).
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_results_shortcode($atts)
	{
		$atts = shortcode_atts(
			array(
				'instance' => 'weblo-search-1',
				'class'    => '',
			),
			$atts,
			'weblo_search_results'
		);

		$instance_id = sanitize_html_class($atts['instance']);
		$custom_class = ! empty($atts['class']) ? sanitize_html_class($atts['class']) : '';
		$recommended_products = get_option('weblo_search_recommended_products', array());

		ob_start();
	?>
		<div class="weblo-search-results-wrapper <?php echo esc_attr($custom_class); ?>"
			data-instance-id="<?php echo esc_attr($instance_id); ?>">
			<div class="weblo-search-column weblo-search-products no-results"
				data-instance-id="<?php echo esc_attr($instance_id); ?>">
				<h3 id="<?php echo esc_attr($instance_id); ?>-products-title" class="weblo-search-section-title"
					data-recommended-text="<?php echo esc_attr__('Recommended Products', 'weblo-search-engine'); ?>"
					data-search-text="<?php echo esc_attr__('Search Results', 'weblo-search-engine'); ?>">
					<?php esc_html_e('Recommended Products', 'weblo-search-engine'); ?></h3>
				<div id="<?php echo esc_attr($instance_id); ?>-products-container" class="weblo-search-products-container">
					<div id="<?php echo esc_attr($instance_id); ?>-recommended-products"
						class="weblo-search-recommended-products">
						<?php
						if (! empty($recommended_products)) {
							foreach ($recommended_products as $product_id) {
								$product_id = absint($product_id);
								if ($product_id > 0) {
									$this->render_product_item($product_id);
								}
							}
						}
						?>
					</div>
					<div id="<?php echo esc_attr($instance_id); ?>-results" class="weblo-search-results" style="display: none;">
					</div>
				</div>
			</div>
		</div>
<?php
		return ob_get_clean();
	}
}
