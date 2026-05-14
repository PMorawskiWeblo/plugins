<?php
/**
 * Product indexing builder.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Index_Builder {
	/**
	 * Allowed content post types for knowledge indexing.
	 *
	 * @var array<int, string>
	 */
	private $content_post_types = array( 'post', 'page' );
	/**
	 * Cache for automatically detected product meta keys.
	 *
	 * @var array<int, string>|null
	 */
	private $auto_detect_product_meta_keys_cache = null;
	/**
	 * Cache for automatically detected content meta keys.
	 *
	 * @var array<int, string>|null
	 */
	private $auto_detect_content_meta_keys_cache = null;
	/**
	 * Index single product.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public function index_product( $product_id ) {
		if ( 'product' !== get_post_type( $product_id ) ) {
			return;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}

		$document_id = $this->upsert_document( $product );
		if ( $document_id > 0 ) {
			$this->upsert_document_meta( $document_id, $product );
		}
	}

	/**
	 * Remove indexed product.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public function delete_product( $product_id ) {
		$this->delete_document_by_type_and_object( 'product', $product_id );
	}

	/**
	 * Index single website content post (blog/page).
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function index_content_post( $post_id ) {
		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, $this->content_post_types, true ) ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_status, array( 'publish', 'private' ), true ) ) {
			return;
		}

		$title   = get_the_title( $post_id );
		$summary = has_excerpt( $post_id ) ? (string) get_the_excerpt( $post_id ) : wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 40 );
		$content = $this->build_post_searchable_content( $post );

		$this->upsert_document_row(
			(string) $post_type,
			(int) $post_id,
			(string) $title,
			(string) $summary,
			(string) $content
		);
	}

	/**
	 * Remove indexed page/blog post.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function delete_content_post( $post_id ) {
		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, $this->content_post_types, true ) ) {
			return;
		}

		$this->delete_document_by_type_and_object( (string) $post_type, $post_id );
	}

	/**
	 * Build index for website content.
	 *
	 * @param int $limit Maximum posts in one run.
	 * @return int
	 */
	public function index_content_batch( $limit = 100, $post_types = array() ) {
		$total   = 0;
		$allowed = $this->resolve_allowed_content_post_types( $post_types );
		foreach ( $allowed as $post_type ) {
			$chunk = $this->index_content_chunk( $post_type, $limit, 0 );
			$total += (int) $chunk['processed'];
		}
		return $total;
	}

	/**
	 * Index single chunk of products using cursor (ID > last ID).
	 *
	 * @param int $limit Batch size.
	 * @param int $after_id Last processed product ID.
	 * @return array<string, mixed>
	 */
	public function index_products_chunk( $limit = 100, $after_id = 0 ) {
		return $this->index_posts_chunk_by_type( 'product', $limit, $after_id, true );
	}

	/**
	 * Index single chunk of content posts using cursor.
	 *
	 * @param string $post_type Post type.
	 * @param int    $limit Batch size.
	 * @param int    $after_id Last processed ID.
	 * @return array<string, mixed>
	 */
	public function index_content_chunk( $post_type, $limit = 100, $after_id = 0 ) {
		return $this->index_posts_chunk_by_type( $post_type, $limit, $after_id, false );
	}

	/**
	 * Resolve allowed content post types for indexing.
	 *
	 * @param array<int, string> $post_types Post types.
	 * @return array<int, string>
	 */
	private function resolve_allowed_content_post_types( $post_types ) {
		if ( ! is_array( $post_types ) || empty( $post_types ) ) {
			return $this->content_post_types;
		}

		$allowed = array_values( array_intersect( array_map( 'sanitize_key', $post_types ), $this->content_post_types ) );
		return empty( $allowed ) ? $this->content_post_types : $allowed;
	}

	/**
	 * Delete document by type and object id.
	 *
	 * @param string $document_type Document type.
	 * @param int    $object_id Object ID.
	 * @return void
	 */
	private function delete_document_by_type_and_object( $document_type, $object_id ) {
		global $wpdb;
		$documents     = $wpdb->prefix . 'storeguide_ai_documents';
		$document_meta = $wpdb->prefix . 'storeguide_ai_document_meta';

		$document_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$documents} WHERE document_type = %s AND object_id = %d LIMIT 1",
				$document_type,
				$object_id
			)
		);

		if ( $document_id <= 0 ) {
			return;
		}

		$wpdb->delete( $document_meta, array( 'document_id' => $document_id ), array( '%d' ) );
		$wpdb->delete( $documents, array( 'id' => $document_id ), array( '%d' ) );
		do_action( 'storeguide_ai_document_deleted', (string) $document_type, (int) $object_id );
	}

	/**
	 * Build index for products.
	 *
	 * @param int $limit Maximum products in one run.
	 * @return int
	 */
	public function index_products_batch( $limit = 100 ) {
		$chunk = $this->index_products_chunk( $limit, 0 );
		return (int) $chunk['processed'];
	}

	/**
	 * Shared chunk indexer by post type.
	 *
	 * @param string $post_type Post type.
	 * @param int    $limit Batch size.
	 * @param int    $after_id Last processed ID.
	 * @param bool   $is_product Whether this is product indexing.
	 * @return array<string, mixed>
	 */
	private function index_posts_chunk_by_type( $post_type, $limit, $after_id, $is_product ) {
		global $wpdb;
		$posts_table = $wpdb->posts;
		$limit       = max( 1, min( 2000, absint( $limit ) ) );
		$after_id    = max( 0, absint( $after_id ) );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID
				FROM {$posts_table}
				WHERE post_type = %s
				  AND post_status IN ('publish','private')
				  AND ID > %d
				ORDER BY ID ASC
				LIMIT %d",
				$post_type,
				$after_id,
				$limit
			)
		);

		if ( empty( $ids ) ) {
			return array(
				'processed' => 0,
				'last_id'   => $after_id,
				'has_more'  => false,
			);
		}

		foreach ( $ids as $post_id ) {
			if ( $is_product ) {
				$this->index_product( (int) $post_id );
			} else {
				$this->index_content_post( (int) $post_id );
			}
		}

		$last_id = (int) end( $ids );
		return array(
			'processed' => count( $ids ),
			'last_id'   => $last_id,
			'has_more'  => count( $ids ) >= $limit,
		);
	}

	/**
	 * Insert/update main document.
	 *
	 * @param WC_Product $product Product.
	 * @return int
	 */
	private function upsert_document( $product ) {
		return $this->upsert_document_row(
			'product',
			(int) $product->get_id(),
			(string) $product->get_name(),
			wp_strip_all_tags( (string) $product->get_short_description() ),
			$this->build_searchable_content( $product )
		);
	}

	/**
	 * Upsert document row for any supported object type.
	 *
	 * @param string $document_type Document type.
	 * @param int    $object_id Object id.
	 * @param string $title Title.
	 * @param string $summary Summary.
	 * @param string $content_text Searchable content.
	 * @return int
	 */
	private function upsert_document_row( $document_type, $object_id, $title, $summary, $content_text ) {
		global $wpdb;
		$documents = $wpdb->prefix . 'storeguide_ai_documents';
		$now       = current_time( 'mysql', true );

		$data = array(
			'document_type' => $document_type,
			'object_id'     => $object_id,
			'title'         => $title,
			'summary'       => $summary,
			'content_text'  => $content_text,
			'status'        => 'active',
			'indexed_at'    => $now,
			'updated_at'    => $now,
		);

		$existing_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$documents} WHERE document_type = %s AND object_id = %d LIMIT 1",
				$document_type,
				$object_id
			)
		);

		if ( $existing_id > 0 ) {
			$wpdb->update(
				$documents,
				$data,
				array( 'id' => $existing_id ),
				array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
			do_action(
				'storeguide_ai_document_indexed',
				(string) $document_type,
				array(
					'object_id'    => (int) $object_id,
					'title'        => (string) $title,
					'summary'      => (string) $summary,
					'content_text' => (string) $content_text,
				)
			);
			return $existing_id;
		}

		$wpdb->insert(
			$documents,
			$data,
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		$insert_id = (int) $wpdb->insert_id;
		do_action(
			'storeguide_ai_document_indexed',
			(string) $document_type,
			array(
				'object_id'    => (int) $object_id,
				'title'        => (string) $title,
				'summary'      => (string) $summary,
				'content_text' => (string) $content_text,
			)
		);
		return $insert_id;
	}

	/**
	 * Build rich searchable content for retrieval.
	 *
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	private function build_searchable_content( $product ) {
		$chunks = array();
		$chunks[] = (string) $product->get_name();
		$chunks[] = (string) $product->get_short_description();
		$chunks[] = (string) $product->get_description();
		$chunks[] = (string) $product->get_sku();

		$category_names = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
		$tag_names      = wp_get_post_terms( $product->get_id(), 'product_tag', array( 'fields' => 'names' ) );
		if ( is_array( $category_names ) ) {
			$chunks[] = implode( ' ', $category_names );
		}
		if ( is_array( $tag_names ) ) {
			$chunks[] = implode( ' ', $tag_names );
		}

		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! is_object( $attribute ) || ! method_exists( $attribute, 'get_name' ) ) {
				continue;
			}

			$attr_name = (string) $attribute->get_name();
			$attr_values = array();

			if ( method_exists( $attribute, 'is_taxonomy' ) && $attribute->is_taxonomy() ) {
				$terms = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
				if ( is_array( $terms ) ) {
					$attr_values = $terms;
				}
			} elseif ( method_exists( $attribute, 'get_options' ) ) {
				$options = $attribute->get_options();
				if ( is_array( $options ) ) {
					$attr_values = array_map( 'strval', $options );
				}
			}

			$chunks[] = $attr_name . ' ' . implode( ' ', $attr_values );
		}

		// Include variation SKU/attributes for large variable catalogs.
		if ( method_exists( $product, 'is_type' ) && $product->is_type( 'variable' ) && method_exists( $product, 'get_children' ) ) {
			$variation_ids = array_slice( array_map( 'intval', (array) $product->get_children() ), 0, 120 );
			foreach ( $variation_ids as $variation_id ) {
				$variation = wc_get_product( $variation_id );
				if ( ! $variation ) {
					continue;
				}
				$chunks[] = (string) $variation->get_sku();
				$chunks[] = implode( ' ', array_map( 'strval', (array) $variation->get_attributes() ) );
			}
		}

		// Optional ACF/custom meta indexing (whitelisted keys from settings).
		$acf_values = $this->get_configured_product_meta_values( (int) $product->get_id() );
		if ( ! empty( $acf_values ) ) {
			$chunks = array_merge( $chunks, $acf_values );
		}

		$plain = implode( "\n", array_filter( array_map( 'wp_strip_all_tags', $chunks ) ) );
		return trim( preg_replace( '/\s+/', ' ', $plain ) );
	}

	/**
	 * Fetch flattened meta values from configured keys.
	 *
	 * @param int $product_id Product ID.
	 * @return array<int, string>
	 */
	private function get_configured_product_meta_values( $product_id ) {
		$options = get_option( 'storeguide_ai_index_options', array() );
		if ( empty( $options['acf_enabled'] ) ) {
			return array();
		}
		$auto_detect = ! empty( $options['acf_auto_detect'] );
		$keys_detected = $auto_detect ? $this->get_auto_detected_product_meta_keys( 40 ) : array();
		$keys_raw = isset( $options['acf_keys'] ) ? (string) $options['acf_keys'] : '';

		$keys_manual = array();
		if ( '' !== trim( $keys_raw ) ) {
			$keys_manual = array_values( array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', $keys_raw ) ) ) ) );
		}

		if ( ! $auto_detect ) {
			if ( empty( $keys_manual ) ) {
				return array();
			}
			$keys = $keys_manual;
		} else {
			$keys = array_values( array_unique( array_merge( $keys_detected, $keys_manual ) ) );
			if ( empty( $keys ) ) {
				return array();
			}
		}

		$values = array();
		foreach ( $keys as $key ) {
			$raw = get_post_meta( $product_id, $key, true );
			$values = array_merge( $values, $this->flatten_meta_value( $raw ) );
		}
		return array_values( array_filter( array_unique( array_map( 'strval', $values ) ) ) );
	}

	/**
	 * Flatten mixed meta value to text array.
	 *
	 * @param mixed $value Meta value.
	 * @return array<int, string>
	 */
	private function flatten_meta_value( $value ) {
		if ( is_scalar( $value ) ) {
			$text = trim( (string) $value );
			return '' === $text ? array() : array( $text );
		}
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $item ) {
				$out = array_merge( $out, $this->flatten_meta_value( $item ) );
			}
			return $out;
		}
		if ( is_object( $value ) ) {
			return $this->flatten_meta_value( get_object_vars( $value ) );
		}
		return array();
	}

	/**
	 * Build searchable text for standard WP posts/pages.
	 *
	 * @param WP_Post $post Post.
	 * @return string
	 */
	private function build_post_searchable_content( $post ) {
		$chunks   = array();
		$chunks[] = (string) $post->post_title;
		$chunks[] = (string) $post->post_excerpt;
		$chunks[] = (string) $post->post_content;
		$chunks   = array_merge( $chunks, $this->get_configured_content_meta_values( (int) $post->ID ) );

		$plain = implode( "\n", array_filter( array_map( 'wp_strip_all_tags', $chunks ) ) );
		return trim( preg_replace( '/\s+/', ' ', $plain ) );
	}

	/**
	 * Fetch flattened meta values for pages/posts from configured keys.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, string>
	 */
	private function get_configured_content_meta_values( $post_id ) {
		$options = get_option( 'storeguide_ai_index_options', array() );
		if ( empty( $options['content_meta_enabled'] ) ) {
			return array();
		}
		$auto_detect = ! empty( $options['content_meta_auto_detect'] );
		$keys_detected = $auto_detect ? $this->get_auto_detected_content_meta_keys( 40 ) : array();
		$keys_raw      = isset( $options['content_meta_keys'] ) ? (string) $options['content_meta_keys'] : '';

		$keys_manual = array();
		if ( '' !== trim( $keys_raw ) ) {
			$keys_manual = array_values( array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', $keys_raw ) ) ) ) );
		}

		if ( ! $auto_detect ) {
			if ( empty( $keys_manual ) ) {
				return array();
			}
			$keys = $keys_manual;
		} else {
			$keys = array_values( array_unique( array_merge( $keys_detected, $keys_manual ) ) );
			if ( empty( $keys ) ) {
				return array();
			}
		}
		$values = array();
		foreach ( $keys as $key ) {
			$raw = get_post_meta( $post_id, $key, true );
			$values = array_merge( $values, $this->flatten_meta_value( $raw ) );
		}
		return array_values( array_filter( array_unique( array_map( 'strval', $values ) ) ) );
	}

	/**
	 * Detect candidate custom/meta keys for products automatically (limited candidate keys).
	 *
	 * @param int $limit Max keys to return.
	 * @return array<int, string>
	 */
	private function get_auto_detected_product_meta_keys( $limit = 40 ) {
		if ( is_array( $this->auto_detect_product_meta_keys_cache ) ) {
			return $this->auto_detect_product_meta_keys_cache;
		}

		global $wpdb;
		$postmeta = $wpdb->postmeta;
		$posts    = $wpdb->posts;

		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_key
				FROM {$postmeta} pm
				INNER JOIN {$posts} p ON p.ID = pm.post_id
				WHERE p.post_type = %s
				  AND p.post_status IN ('publish','private')
				  AND pm.meta_key NOT LIKE %s
				  AND pm.meta_key NOT LIKE %s
				  AND pm.meta_key NOT IN ('_price','_regular_price','_sale_price','_sku','_stock_status','_stock','_manage_stock','_visibility','_tax_status','_tax_class')
				ORDER BY pm.meta_key ASC
				LIMIT %d",
				'product',
				'\\_%',
				'attribute\\_%',
				max( 5, min( 200, absint( $limit ) ) )
			)
		);

		$keys = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $key ) {
				$key = sanitize_key( (string) $key );
				if ( '' === $key ) {
					continue;
				}
				$keys[] = $key;
			}
		}

		$keys = array_values( array_unique( $keys ) );
		$this->auto_detect_product_meta_keys_cache = array_slice( $keys, 0, max( 10, min( 60, absint( $limit ) ) ) );
		return $this->auto_detect_product_meta_keys_cache;
	}

	/**
	 * Detect candidate meta keys for pages/posts automatically (limited candidate keys).
	 *
	 * @param int $limit Max keys.
	 * @return array<int, string>
	 */
	private function get_auto_detected_content_meta_keys( $limit = 40 ) {
		if ( is_array( $this->auto_detect_content_meta_keys_cache ) ) {
			return $this->auto_detect_content_meta_keys_cache;
		}

		global $wpdb;
		$postmeta = $wpdb->postmeta;
		$posts    = $wpdb->posts;

		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_key
				FROM {$postmeta} pm
				INNER JOIN {$posts} p ON p.ID = pm.post_id
				WHERE p.post_type IN ('post','page')
				  AND p.post_status IN ('publish','private')
				  AND pm.meta_key NOT LIKE %s
				ORDER BY pm.meta_key ASC
				LIMIT %d",
				'\\_%',
				max( 5, min( 200, absint( $limit ) ) )
			)
		);

		$keys = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $key ) {
				$key = sanitize_key( (string) $key );
				if ( '' === $key ) {
					continue;
				}
				$keys[] = $key;
			}
		}

		$keys = array_values( array_unique( $keys ) );
		$this->auto_detect_content_meta_keys_cache = array_slice( $keys, 0, max( 10, min( 60, absint( $limit ) ) ) );
		return $this->auto_detect_content_meta_keys_cache;
	}

	/**
	 * Insert/update document meta.
	 *
	 * @param int        $document_id Document id.
	 * @param WC_Product $product Product.
	 * @return void
	 */
	private function upsert_document_meta( $document_id, $product ) {
		global $wpdb;
		$document_meta = $wpdb->prefix . 'storeguide_ai_document_meta';

		$category_ids = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) );
		$attributes   = array();

		foreach ( $product->get_attributes() as $key => $attribute ) {
			if ( is_object( $attribute ) && method_exists( $attribute, 'get_options' ) ) {
				$attributes[ $key ] = $attribute->get_options();
			}
		}

		$data = array(
			'document_id'         => $document_id,
			'product_id'          => $product->get_id(),
			'price'               => (float) wc_get_price_to_display( $product ),
			'stock_status'        => $product->get_stock_status(),
			'category_ids_json'   => wp_json_encode( array_map( 'intval', $category_ids ) ),
			'attribute_map_json'  => wp_json_encode( $attributes ),
		);

		$existing_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$document_meta} WHERE document_id = %d LIMIT 1",
				$document_id
			)
		);

		if ( $existing_id > 0 ) {
			$wpdb->update(
				$document_meta,
				$data,
				array( 'id' => $existing_id ),
				array( '%d', '%d', '%f', '%s', '%s', '%s' ),
				array( '%d' )
			);
			return;
		}

		$wpdb->insert(
			$document_meta,
			$data,
			array( '%d', '%d', '%f', '%s', '%s', '%s' )
		);
	}
}
