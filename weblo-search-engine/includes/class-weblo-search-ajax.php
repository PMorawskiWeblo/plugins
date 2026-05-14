<?php
/**
 * The AJAX functionality of the plugin.
 *
 * @package Weblo_Search_Engine
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The AJAX functionality of the plugin.
 */
class Weblo_Search_Ajax {

	/**
	 * Search products via AJAX.
	 */
	public function search_products_ajax() {
		check_ajax_referer( 'weblo_search_nonce', 'nonce' );

		$search_term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
		
		if ( empty( $search_term ) || strlen( $search_term ) < 3 ) {
			wp_send_json_error( array( 'message' => __( 'Search term must be at least 3 characters.', 'weblo-search-engine' ) ) );
			return;
		}

		$limit = absint( get_option( 'weblo_search_limit', 10 ) );

		// Check for cached results.
		$cache_key = 'weblo_search_' . md5( $search_term . $limit );
		$cached = get_transient( $cache_key );
		
		if ( false !== $cached ) {
			wp_send_json_success( $cached );
			return;
		}

		// Perform search query.
		$meta_query = WC()->query->get_meta_query();
		
		// Exclude hidden products from search using catalog visibility.
		// WooCommerce 3.0+ uses _catalog_visibility meta key.
		$meta_query[] = array(
			'relation' => 'OR',
			array(
				'key'     => '_catalog_visibility',
				'value'   => 'hidden',
				'compare' => '!=',
			),
			array(
				'key'     => '_catalog_visibility',
				'compare' => 'NOT EXISTS',
			),
		);
		
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			's'              => $search_term,
			'meta_query'     => $meta_query,
			'tax_query'      => WC()->query->get_tax_query(),
		);

		$products = new WP_Query( $args );
		
		// Get product IDs directly - filtering is done in meta_query.
		$product_ids = wp_list_pluck( $products->posts, 'ID' );
		$categories = $this->get_categories_hierarchy( $product_ids );

		$result = array(
			'products'  => $product_ids,
			'categories' => $categories,
			'term'      => $search_term,
		);

		// Cache for 1 hour.
		set_transient( $cache_key, $result, HOUR_IN_SECONDS );

		wp_send_json_success( $result );
	}

	/**
	 * Get categories hierarchy from product IDs.
	 *
	 * @param array $product_ids Array of product IDs.
	 * @return array Categories hierarchy.
	 */
	private function get_categories_hierarchy( $product_ids ) {
		if ( empty( $product_ids ) ) {
			return array();
		}

		$all_categories = array();

		// Get all categories from products.
		foreach ( $product_ids as $product_id ) {
			$terms = get_the_terms( $product_id, 'product_cat' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( ! isset( $all_categories[ $term->term_id ] ) ) {
						$all_categories[ $term->term_id ] = array(
							'id'       => $term->term_id,
							'name'     => $term->name,
							'slug'     => $term->slug,
							'parent'   => $term->parent,
							'children' => array(),
						);
					}
				}
			}
		}

		// Add URLs to categories.
		foreach ( $all_categories as $category_id => $category ) {
			$term_link = get_term_link( $category_id, 'product_cat' );
			if ( ! is_wp_error( $term_link ) ) {
				$all_categories[ $category_id ]['url'] = $term_link;
			} else {
				$all_categories[ $category_id ]['url'] = '';
			}
		}

		// Build hierarchy.
		$hierarchy = array();
		foreach ( $all_categories as $category_id => $category ) {
			if ( 0 === $category['parent'] ) {
				// Top-level category.
				$hierarchy[ $category_id ] = $category;
			} else {
				// Child category.
				if ( isset( $all_categories[ $category['parent'] ] ) ) {
					if ( ! isset( $hierarchy[ $category['parent'] ] ) ) {
						// Parent is not in hierarchy yet, find it.
						$parent = $all_categories[ $category['parent'] ];
						while ( $parent['parent'] > 0 && isset( $all_categories[ $parent['parent'] ] ) ) {
							$parent = $all_categories[ $parent['parent'] ];
						}
						if ( ! isset( $hierarchy[ $parent['id'] ] ) ) {
							$hierarchy[ $parent['id'] ] = $all_categories[ $parent['id'] ];
						}
					}
					$hierarchy[ $category['parent'] ]['children'][ $category_id ] = $category;
				}
			}
		}

		return $hierarchy;
	}

	/**
	 * Get products HTML via AJAX.
	 */
	public function get_products_html_ajax() {
		check_ajax_referer( 'weblo_search_nonce', 'nonce' );

		$product_ids = isset( $_POST['product_ids'] ) ? array_map( 'absint', $_POST['product_ids'] ) : array();

		if ( empty( $product_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No product IDs provided.', 'weblo-search-engine' ) ) );
			return;
		}

		ob_start();
		foreach ( $product_ids as $product_id ) {
			$this->render_product_item( $product_id );
		}
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Render product item.
	 *
	 * @param int $product_id Product ID.
	 */
	private function render_product_item( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}

		// Skip hidden products.
		if ( 'hidden' === $product->get_catalog_visibility() ) {
			return;
		}

		$template_path = WEBLO_SEARCH_ENGINE_PATH . 'templates/search-results-template.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}

