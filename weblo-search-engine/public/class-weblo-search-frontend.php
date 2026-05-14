<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package Weblo_Search_Engine
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

	/**
	 * The public-facing functionality of the plugin.
	 */
	class Weblo_Search_Frontend {

		/**
		 * The version of this plugin.
		 *
		 * @var string
		 */
		private $version;

		/**
		 * Initialize the class and set its properties.
		 *
		 * @param string $version The version of this plugin.
		 */
		public function __construct( $version ) {
			$this->version = $version;
		}

		/**
		 * Add sale query var.
		 *
		 * @param array $vars Query vars.
		 * @return array Modified query vars.
		 */
		public function add_sale_query_var( $vars ) {
			$vars[] = 'on_sale';
			return $vars;
		}

		/**
		 * Filter products on sale.
		 *
		 * @param WP_Query $query WP_Query object.
		 */
		public function filter_sale_products( $query ) {
			if ( ! is_admin() && $query->is_main_query() ) {
				$on_sale = get_query_var( 'on_sale' );
				
				if ( '1' === $on_sale || 'true' === $on_sale ) {
					// Check if we're on shop page or product archive.
					if ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) {
						// Get product IDs that are on sale.
						$sale_product_ids = $this->get_products_on_sale_ids();
						
						if ( ! empty( $sale_product_ids ) ) {
							// Set post__in to only show products on sale.
							$existing_post_in = $query->get( 'post__in' );
							if ( ! empty( $existing_post_in ) ) {
								// Intersect with existing post__in if set.
								$sale_product_ids = array_intersect( $sale_product_ids, $existing_post_in );
							}
							
							if ( ! empty( $sale_product_ids ) ) {
								$query->set( 'post__in', $sale_product_ids );
							} else {
								// If no products on sale, return empty result.
								$query->set( 'post__in', array( 0 ) );
							}
						} else {
							// If no products on sale, return empty result.
							$query->set( 'post__in', array( 0 ) );
						}
					}
				}
			}
		}

	/**
	 * Get product IDs that are on sale.
	 *
	 * @return array Array of product IDs on sale.
	 */
	private function get_products_on_sale_ids() {
		global $wpdb;
		
		// Check cache first.
		$cache_key = 'weblo_products_on_sale_ids';
		$cached = get_transient( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Get all products with sale price set using prepared statements.
		$sale_product_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.post_id 
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				WHERE pm.meta_key = %s 
				AND pm.meta_value != '' 
				AND pm.meta_value IS NOT NULL
				AND p.post_type = %s
				AND p.post_status = %s",
				'_sale_price',
				'product',
				'publish'
			)
		);
		
		if ( empty( $sale_product_ids ) ) {
			set_transient( $cache_key, array(), HOUR_IN_SECONDS );
			return array();
		}
		
		// Filter products that are actually on sale (check dates and exclude hidden).
		$filtered_ids = array();
		foreach ( $sale_product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product && $product->is_on_sale() && 'hidden' !== $product->get_catalog_visibility() ) {
				$filtered_ids[] = $product_id;
			}
		}
		
		// Cache for 1 hour.
		set_transient( $cache_key, $filtered_ids, HOUR_IN_SECONDS );
		
		return $filtered_ids;
	}
	}

