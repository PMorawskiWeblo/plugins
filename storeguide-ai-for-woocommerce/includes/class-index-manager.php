<?php
/**
 * Index synchronization manager.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Index_Manager {
	/**
	 * Index builder.
	 *
	 * @var StoreGuide_AI_Index_Builder
	 */
	private $builder;

	/**
	 * Constructor.
	 *
	 * @param StoreGuide_AI_Index_Builder $builder Builder.
	 */
	public function __construct( $builder ) {
		$this->builder = $builder;
	}

	/**
	 * Register indexing hooks.
	 *
	 * @param StoreGuide_AI_Loader $loader Loader.
	 * @return void
	 */
	public function register( $loader ) {
		$loader->add_action( 'save_post_product', $this, 'on_product_save', 10, 3 );
		$loader->add_action( 'save_post_post', $this, 'on_content_save', 10, 3 );
		$loader->add_action( 'save_post_page', $this, 'on_content_save', 10, 3 );
		$loader->add_action( 'before_delete_post', $this, 'on_before_delete_post', 10, 1 );
	}

	/**
	 * Index updated product.
	 *
	 * @param int      $post_id Product ID.
	 * @param WP_Post  $post Post object.
	 * @param bool     $update Update flag.
	 * @return void
	 */
	public function on_product_save( $post_id, $post, $update ) {
		$index_options = get_option( 'storeguide_ai_index_options', array() );
		if ( isset( $index_options['autosync'] ) && ! $index_options['autosync'] ) {
			return;
		}
		$sources = $this->get_index_sources( $index_options );
		if ( ! in_array( 'products', $sources, true ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! $update && 'publish' !== $post->post_status ) {
			return;
		}

		$this->builder->index_product( (int) $post_id );
	}

	/**
	 * Remove deleted product from index.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function on_before_delete_post( $post_id ) {
		$post_type = get_post_type( $post_id );
		if ( 'product' === $post_type ) {
			$this->builder->delete_product( (int) $post_id );
			return;
		}

		if ( in_array( $post_type, array( 'post', 'page' ), true ) ) {
			$this->builder->delete_content_post( (int) $post_id );
		}
	}

	/**
	 * Index updated blog/page content.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool    $update Update flag.
	 * @return void
	 */
	public function on_content_save( $post_id, $post, $update ) {
		$index_options = get_option( 'storeguide_ai_index_options', array() );
		if ( isset( $index_options['autosync'] ) && ! $index_options['autosync'] ) {
			return;
		}
		$sources = $this->get_index_sources( $index_options );
		$post_type = get_post_type( $post_id );
		if ( ( 'page' === $post_type && ! in_array( 'pages', $sources, true ) ) || ( 'post' === $post_type && ! in_array( 'posts', $sources, true ) ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! $update && 'publish' !== $post->post_status ) {
			return;
		}

		$this->builder->index_content_post( (int) $post_id );
	}

	/**
	 * Parse configured sources from index options.
	 *
	 * @param array<string, mixed> $options Index options.
	 * @return array<int, string>
	 */
	private function get_index_sources( $options ) {
		$raw = isset( $options['sources'] ) ? (string) $options['sources'] : 'products';
		$parts = array_map( 'sanitize_key', array_map( 'trim', explode( ',', $raw ) ) );
		$allowed = array( 'products', 'pages', 'posts' );
		$sources = array_values( array_intersect( $parts, $allowed ) );
		return empty( $sources ) ? array( 'products' ) : $sources;
	}
}
