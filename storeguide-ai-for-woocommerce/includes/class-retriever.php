<?php
/**
 * Retrieval service from indexed documents.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Retriever {
	/**
	 * Search indexed products.
	 *
	 * @param string $query User query.
	 * @param int    $limit Result limit.
	 * @return array<int, array<string, mixed>>
	 */
	public function search_products( $query, $limit = 3 ) {
		global $wpdb;

		$documents   = $wpdb->prefix . 'storeguide_ai_documents';
		$meta        = $wpdb->prefix . 'storeguide_ai_document_meta';
		$query       = trim( sanitize_text_field( (string) $query ) );
		if ( '' === $query ) {
			return array();
		}
		$limit       = max( 1, min( 20, absint( $limit ) ) );
		$candidate_limit = max( 10, $limit * 10 );
		$like        = '%' . $wpdb->esc_like( $query ) . '%';
		$filters     = $this->extract_query_filters( $query );
		$rules       = get_option( 'storeguide_ai_rules_options', array() );
		$semantic_options = $this->get_semantic_options();
		$semantic_rows = $this->get_semantic_results( 'product', $query, min( $limit, (int) $semantic_options['top_k'] ), $semantic_options );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT d.object_id AS product_id, d.title, d.summary, m.price, m.stock_status, m.category_ids_json
				FROM {$documents} d
				LEFT JOIN {$meta} m ON m.document_id = d.id
				WHERE d.document_type = %s
				  AND d.status = %s
				  AND (d.title LIKE %s OR d.summary LIKE %s OR d.content_text LIKE %s)
				ORDER BY
				  CASE WHEN m.stock_status = 'instock' THEN 0 ELSE 1 END,
				  m.price ASC
				LIMIT %d",
				'product',
				'active',
				$like,
				$like,
				$like,
				$candidate_limit
			),
			ARRAY_A
		);

		if ( empty( $results ) ) {
			$results = $this->search_by_query_tokens( $query, $candidate_limit, $documents, $meta );
		}

		$rows = $this->merge_product_rows( $results, $semantic_rows, $candidate_limit );
		if ( empty( $rows ) ) {
			return array();
		}

		$normalized = array_map( array( $this, 'normalize_row' ), $rows );
		$filtered   = $this->apply_filters_and_rules( $normalized, $filters, $rules );

		$promoted_ids = $this->parse_integer_list( isset( $rules['promoted_products'] ) ? (string) $rules['promoted_products'] : '' );
		if ( ! empty( $promoted_ids ) ) {
			usort(
				$filtered,
				static function( $a, $b ) use ( $promoted_ids ) {
					$a_promoted = in_array( (int) $a['product_id'], $promoted_ids, true ) ? 0 : 1;
					$b_promoted = in_array( (int) $b['product_id'], $promoted_ids, true ) ? 0 : 1;
					if ( $a_promoted !== $b_promoted ) {
						return $a_promoted - $b_promoted;
					}
					$a_price = null === $a['price'] ? PHP_FLOAT_MAX : (float) $a['price'];
					$b_price = null === $b['price'] ? PHP_FLOAT_MAX : (float) $b['price'];
					return $a_price <=> $b_price;
				}
			);
		}

		return array_slice( $filtered, 0, $limit );
	}

	/**
	 * Search products by max price only (fallback for price-intent queries).
	 *
	 * @param float $max_price Max price.
	 * @param int   $limit Result limit.
	 * @return array<int, array<string, mixed>>
	 */
	public function search_products_by_max_price( $max_price, $limit = 3 ) {
		global $wpdb;
		$documents = $wpdb->prefix . 'storeguide_ai_documents';
		$meta      = $wpdb->prefix . 'storeguide_ai_document_meta';
		$limit     = max( 1, min( 20, absint( $limit ) ) );
		$max_price = (float) $max_price;
		if ( $max_price <= 0 ) {
			return array();
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT d.object_id AS product_id, d.title, d.summary, m.price, m.stock_status, m.category_ids_json
				FROM {$documents} d
				LEFT JOIN {$meta} m ON m.document_id = d.id
				WHERE d.document_type = %s
				  AND d.status = %s
				  AND m.price IS NOT NULL
				  AND m.price <= %f
				ORDER BY
				  CASE WHEN m.stock_status = 'instock' THEN 0 ELSE 1 END,
				  m.price ASC
				LIMIT %d",
				'product',
				'active',
				$max_price,
				$limit
			),
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return array();
		}

		return array_map( array( $this, 'normalize_row' ), $rows );
	}

	/**
	 * Retrieve currently on-sale products directly from WooCommerce.
	 *
	 * @param int $limit Result limit.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_on_sale_products( $limit = 3 ) {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}

		$limit    = max( 1, min( 20, absint( $limit ) ) );
		$products = wc_get_products(
			array(
				'status'   => array( 'publish', 'private' ),
				'limit'    => $limit,
				'on_sale'  => true,
				'orderby'  => 'price',
				'order'    => 'ASC',
				'return'   => 'objects',
			)
		);

		if ( empty( $products ) || ! is_array( $products ) ) {
			return array();
		}

		$output = array();
		foreach ( $products as $product ) {
			if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
				continue;
			}

			$price_value = $product->get_price();
			$price_float = '' !== (string) $price_value ? (float) $price_value : null;
			$output[] = array(
				'product_id'    => (int) $product->get_id(),
				'title'         => (string) $product->get_name(),
				'summary'       => wp_strip_all_tags( (string) $product->get_short_description() ),
				'price'         => $price_float,
				'price_html'    => $this->format_product_price_text( $product ),
				'stock_status'  => (string) $product->get_stock_status(),
				'product_url'   => get_permalink( (int) $product->get_id() ),
				'thumbnail_url' => get_the_post_thumbnail_url( (int) $product->get_id(), 'thumbnail' ),
				'categories'    => array_map( 'intval', wp_get_post_terms( (int) $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) ) ),
			);
		}

		return $output;
	}

	/**
	 * Retrieve cheapest/most expensive products from index.
	 *
	 * @param string $direction asc|desc
	 * @param int    $limit Result limit.
	 * @return array<int, array<string, mixed>>
	 */
	public function search_products_by_price_order( $direction = 'asc', $limit = 3 ) {
		global $wpdb;
		$documents = $wpdb->prefix . 'storeguide_ai_documents';
		$meta      = $wpdb->prefix . 'storeguide_ai_document_meta';
		$limit     = max( 1, min( 20, absint( $limit ) ) );
		$order_dir = 'desc' === strtolower( $direction ) ? 'DESC' : 'ASC';

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT d.object_id AS product_id, d.title, d.summary, m.price, m.stock_status, m.category_ids_json
				FROM {$documents} d
				LEFT JOIN {$meta} m ON m.document_id = d.id
				WHERE d.document_type = %s
				  AND d.status = %s
				  AND m.price IS NOT NULL
				ORDER BY m.price {$order_dir}
				LIMIT %d",
				'product',
				'active',
				$limit
			),
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return array();
		}

		return array_map( array( $this, 'normalize_row' ), $rows );
	}

	/**
	 * Retrieve most popular products based on WooCommerce sales count.
	 *
	 * @param int $limit Result limit.
	 * @return array<int, array<string, mixed>>
	 */
	public function search_popular_products( $limit = 3 ) {
		global $wpdb;
		$documents = $wpdb->prefix . 'storeguide_ai_documents';
		$meta      = $wpdb->prefix . 'storeguide_ai_document_meta';
		$postmeta  = $wpdb->postmeta;
		$limit     = max( 1, min( 20, absint( $limit ) ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT d.object_id AS product_id, d.title, d.summary, m.price, m.stock_status, m.category_ids_json
				FROM {$documents} d
				LEFT JOIN {$meta} m ON m.document_id = d.id
				LEFT JOIN {$postmeta} pm ON pm.post_id = d.object_id AND pm.meta_key = %s
				WHERE d.document_type = %s
				  AND d.status = %s
				ORDER BY CAST(COALESCE(pm.meta_value, '0') AS UNSIGNED) DESC, m.price ASC
				LIMIT %d",
				'total_sales',
				'product',
				'active',
				$limit
			),
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return array();
		}

		return array_map( array( $this, 'normalize_row' ), $rows );
	}

	/**
	 * Search indexed knowledge pages/posts for general store context.
	 *
	 * @param string $query User query.
	 * @param int    $limit Result limit.
	 * @return array<int, array<string, mixed>>
	 */
	public function search_knowledge( $query, $limit = 3 ) {
		global $wpdb;
		$query = trim( sanitize_text_field( (string) $query ) );
		if ( '' === $query ) {
			return array();
		}

		$index_options = get_option( 'storeguide_ai_index_options', array() );
		$sources       = isset( $index_options['sources'] ) ? array_map( 'trim', explode( ',', (string) $index_options['sources'] ) ) : array();
		$allowed_types = array();
		if ( in_array( 'pages', $sources, true ) ) {
			$allowed_types[] = 'page';
		}
		if ( in_array( 'posts', $sources, true ) ) {
			$allowed_types[] = 'post';
		}
		if ( empty( $allowed_types ) ) {
			return array();
		}

		$documents = $wpdb->prefix . 'storeguide_ai_documents';
		$limit     = max( 1, min( 8, absint( $limit ) ) );
		$like      = '%' . $wpdb->esc_like( $query ) . '%';
		$semantic_options = $this->get_semantic_options();
		$semantic_rows = $this->get_semantic_results( 'knowledge', $query, min( $limit, (int) $semantic_options['top_k'] ), $semantic_options );

		$placeholders = implode( ', ', array_fill( 0, count( $allowed_types ), '%s' ) );
		$params       = array_merge(
			array(
				'active',
				$like,
				$like,
				$like,
			),
			$allowed_types,
			array( $limit )
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT d.object_id, d.document_type, d.title, d.summary, d.content_text
				FROM {$documents} d
				WHERE d.status = %s
				  AND (d.title LIKE %s OR d.summary LIKE %s OR d.content_text LIKE %s)
				  AND d.document_type IN ({$placeholders})
				ORDER BY d.updated_at DESC
				LIMIT %d",
				...$params
			),
			ARRAY_A
		);

		$rows = $this->merge_knowledge_rows( is_array( $rows ) ? $rows : array(), $semantic_rows, $limit );
		if ( empty( $rows ) ) {
			return array();
		}

		$output = array();
		foreach ( $rows as $row ) {
			$output[] = array(
				'object_id'      => (int) $row['object_id'],
				'document_type'  => (string) $row['document_type'],
				'title'          => (string) $row['title'],
				'summary'        => (string) $row['summary'],
				'content_text'   => (string) $row['content_text'],
				'permalink'      => get_permalink( (int) $row['object_id'] ),
			);
		}

		return $output;
	}

	/**
	 * Token-based fallback search for longer natural questions.
	 *
	 * @param string $query Query.
	 * @param int    $limit Candidate limit.
	 * @param string $documents_table Documents table.
	 * @param string $meta_table Meta table.
	 * @return array<int, array<string, mixed>>
	 */
	private function search_by_query_tokens( $query, $limit, $documents_table, $meta_table ) {
		global $wpdb;

		$tokens = preg_split( '/\s+/u', strtolower( $query ) );
		if ( ! is_array( $tokens ) ) {
			return array();
		}

		$stop_words = array( 'jak', 'ile', 'jest', 'oraz', 'i', 'or', 'and', 'the', 'for', 'with', 'from', 'produkt', 'produkty' );
		$tokens = array_values(
			array_filter(
				$tokens,
				static function( $token ) use ( $stop_words ) {
					return mb_strlen( $token ) >= 3 && ! in_array( $token, $stop_words, true );
				}
			)
		);

		if ( empty( $tokens ) ) {
			return array();
		}

		$tokens = array_slice( $tokens, 0, 6 );
		$token_variants = $this->expand_search_token_variants( $tokens );
		if ( empty( $token_variants ) ) {
			return array();
		}
		$conditions = array();
		$params = array( 'product', 'active' );
		foreach ( $token_variants as $token ) {
			$conditions[] = '(d.title LIKE %s OR d.summary LIKE %s OR d.content_text LIKE %s)';
			$token_like = '%' . $wpdb->esc_like( $token ) . '%';
			$params[] = $token_like;
			$params[] = $token_like;
			$params[] = $token_like;
		}

		$params[] = absint( $limit );
		$sql = "SELECT d.object_id AS product_id, d.title, d.summary, m.price, m.stock_status, m.category_ids_json
			FROM {$documents_table} d
			LEFT JOIN {$meta_table} m ON m.document_id = d.id
			WHERE d.document_type = %s
			  AND d.status = %s
			  AND (" . implode( ' OR ', $conditions ) . ")
			ORDER BY
			  CASE WHEN m.stock_status = 'instock' THEN 0 ELSE 1 END,
			  m.price ASC
			LIMIT %d";

		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );
	}

	/**
	 * Expand query tokens to include simple Polish/English base forms.
	 *
	 * @param array<int, string> $tokens Tokens.
	 * @return array<int, string>
	 */
	private function expand_search_token_variants( $tokens ) {
		$variants = array();
		$suffixes = array( 'ami', 'ach', 'owie', 'owego', 'owej', 'owej', 'ych', 'ego', 'ami', 'ach', 'owi', 'owa', 'owe', 'ami', 'ach', 'ie', 'om', 'em', 'ą', 'ę', 'a', 'u', 'y', 'i', 'e', 's' );

		foreach ( $tokens as $token ) {
			$token = trim( (string) $token );
			if ( mb_strlen( $token ) < 3 ) {
				continue;
			}

			$variants[] = $token;

			// Naive stemming to handle inflected forms, e.g. "kremu" -> "krem".
			foreach ( $suffixes as $suffix ) {
				if ( mb_strlen( $token ) <= mb_strlen( $suffix ) + 2 ) {
					continue;
				}
				if ( mb_substr( $token, -mb_strlen( $suffix ) ) === $suffix ) {
					$base = mb_substr( $token, 0, mb_strlen( $token ) - mb_strlen( $suffix ) );
					if ( mb_strlen( $base ) >= 3 ) {
						$variants[] = $base;
					}
				}
			}
		}

		$variants = array_values(
			array_filter(
				array_unique( array_map( 'strval', $variants ) ),
				static function ( $token ) {
					return mb_strlen( $token ) >= 3;
				}
			)
		);

		return array_slice( $variants, 0, 18 );
	}

	/**
	 * Normalize DB row.
	 *
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>
	 */
	private function normalize_row( $row ) {
		$product_id = isset( $row['product_id'] ) ? (int) $row['product_id'] : 0;
		if ( $product_id <= 0 ) {
			return array(
				'product_id'    => 0,
				'title'         => '',
				'summary'       => '',
				'price'         => null,
				'price_html'    => null,
				'stock_status'  => '',
				'product_url'   => '',
				'thumbnail_url' => '',
				'categories'    => array(),
			);
		}

		$categories = array();
		if ( ! empty( $row['category_ids_json'] ) ) {
			$decoded = json_decode( (string) $row['category_ids_json'], true );
			if ( is_array( $decoded ) ) {
				$categories = array_map( 'intval', $decoded );
			}
		}

		return array(
			'product_id'    => $product_id,
			'title'         => isset( $row['title'] ) ? (string) $row['title'] : '',
			'summary'       => isset( $row['summary'] ) ? (string) $row['summary'] : '',
			'price'         => isset( $row['price'] ) && null !== $row['price'] ? (float) $row['price'] : null,
			'price_html'    => isset( $row['price'] ) && null !== $row['price'] && function_exists( 'wc_price' ) ? trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( wc_price( (float) $row['price'] ) ), ENT_QUOTES, 'UTF-8' ) ) ) : null,
			'stock_status'  => isset( $row['stock_status'] ) ? (string) $row['stock_status'] : '',
			'product_url'   => get_permalink( $product_id ),
			'thumbnail_url' => get_the_post_thumbnail_url( $product_id, 'thumbnail' ),
			'categories'    => $categories,
		);
	}

	/**
	 * Build clean price text for any product type.
	 *
	 * @param WC_Product $product Product.
	 * @return string|null
	 */
	private function format_product_price_text( $product ) {
		if ( ! is_object( $product ) ) {
			return null;
		}
		if ( method_exists( $product, 'is_type' ) && $product->is_type( 'variable' ) ) {
			$min = (float) $product->get_variation_price( 'min', true );
			$max = (float) $product->get_variation_price( 'max', true );
			if ( $min > 0 && $max > 0 ) {
				$min_text = html_entity_decode( wp_strip_all_tags( wc_price( $min ) ), ENT_QUOTES, 'UTF-8' );
				$max_text = html_entity_decode( wp_strip_all_tags( wc_price( $max ) ), ENT_QUOTES, 'UTF-8' );
				return trim( preg_replace( '/\s+/u', ' ', $min_text ) ) . ' - ' . trim( preg_replace( '/\s+/u', ' ', $max_text ) );
			}
		}
		$price = $product->get_price();
		if ( '' === (string) $price ) {
			return null;
		}
		$price_text = html_entity_decode( wp_strip_all_tags( wc_price( (float) $price ) ), ENT_QUOTES, 'UTF-8' );
		return trim( preg_replace( '/\s+/u', ' ', $price_text ) );
	}

	/**
	 * Extract simple filters from natural language query.
	 *
	 * @param string $query Query.
	 * @return array<string, mixed>
	 */
	private function extract_query_filters( $query ) {
		$filters = array(
			'max_price' => null,
			'in_stock'  => false,
		);

		$normalized = strtolower( $query );
		if ( preg_match( '/(?:under|below|max|do|ponizej)\s*(\d+(?:[.,]\d+)?)/u', $normalized, $matches ) ) {
			$filters['max_price'] = (float) str_replace( ',', '.', $matches[1] );
		}

		$in_stock_markers = array( 'in stock', 'na stanie', 'dostepne', 'available now' );
		foreach ( $in_stock_markers as $marker ) {
			if ( false !== strpos( $normalized, $marker ) ) {
				$filters['in_stock'] = true;
				break;
			}
		}

		return $filters;
	}

	/**
	 * Apply extracted filters and configured business rules.
	 *
	 * @param array<int, array<string, mixed>> $products Products.
	 * @param array<string, mixed>              $filters Parsed filters.
	 * @param array<string, mixed>              $rules Rules options.
	 * @return array<int, array<string, mixed>>
	 */
	private function apply_filters_and_rules( $products, $filters, $rules ) {
		$excluded_categories = $this->parse_integer_list( isset( $rules['excluded_categories'] ) ? (string) $rules['excluded_categories'] : '' );
		$in_stock_only       = ! empty( $rules['in_stock_only'] ) || ! empty( $filters['in_stock'] );
		$max_price           = isset( $filters['max_price'] ) ? $filters['max_price'] : null;

		$output = array();
		foreach ( $products as $product ) {
			if ( $in_stock_only && 'instock' !== $product['stock_status'] ) {
				continue;
			}

			if ( null !== $max_price && null !== $product['price'] && (float) $product['price'] > (float) $max_price ) {
				continue;
			}

			if ( ! empty( $excluded_categories ) && ! empty( $product['categories'] ) ) {
				$overlap = array_intersect( $excluded_categories, $product['categories'] );
				if ( ! empty( $overlap ) ) {
					continue;
				}
			}

			$output[] = $product;
		}

		return $output;
	}

	/**
	 * Parse comma-separated integer list.
	 *
	 * @param string $raw Raw string.
	 * @return array<int, int>
	 */
	private function parse_integer_list( $raw ) {
		if ( '' === trim( $raw ) ) {
			return array();
		}

		$items = array_map( 'trim', explode( ',', $raw ) );
		$items = array_filter( $items, static function( $item ) {
			return '' !== $item && is_numeric( $item );
		} );

		return array_map( 'intval', $items );
	}

	/**
	 * Get semantic retrieval options.
	 *
	 * @return array<string, int>
	 */
	private function get_semantic_options() {
		$index_options = get_option( 'storeguide_ai_index_options', array() );
		return array(
			'enabled' => ! empty( $index_options['semantic_retrieval_enabled'] ) ? 1 : 0,
			'top_k'   => isset( $index_options['semantic_top_k'] ) ? max( 1, min( 20, absint( $index_options['semantic_top_k'] ) ) ) : 5,
		);
	}

	/**
	 * Read optional semantic results from external integration.
	 *
	 * @param string            $type Result type: product|knowledge.
	 * @param string            $query Query text.
	 * @param int               $limit Result limit.
	 * @param array<string,int> $options Semantic options.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_semantic_results( $type, $query, $limit, $options ) {
		if ( empty( $options['enabled'] ) ) {
			return array();
		}
		$rows = apply_filters( 'storeguide_ai_semantic_results', array(), sanitize_key( $type ), (string) $query, max( 1, absint( $limit ) ) );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Merge SQL and semantic product rows.
	 *
	 * @param array<int, array<string, mixed>> $sql_rows SQL rows.
	 * @param array<int, array<string, mixed>> $semantic_rows Semantic rows.
	 * @param int                              $limit Max rows.
	 * @return array<int, array<string, mixed>>
	 */
	private function merge_product_rows( $sql_rows, $semantic_rows, $limit ) {
		$merged = array();
		$seen   = array();

		foreach ( array_merge( is_array( $semantic_rows ) ? $semantic_rows : array(), is_array( $sql_rows ) ? $sql_rows : array() ) as $row ) {
			if ( ! is_array( $row ) || empty( $row['product_id'] ) ) {
				continue;
			}
			$product_id = (int) $row['product_id'];
			if ( $product_id <= 0 || isset( $seen[ $product_id ] ) ) {
				continue;
			}
			$seen[ $product_id ] = 1;
			$merged[] = $row;
			if ( count( $merged ) >= absint( $limit ) ) {
				break;
			}
		}

		return $merged;
	}

	/**
	 * Merge SQL and semantic knowledge rows.
	 *
	 * @param array<int, array<string, mixed>> $sql_rows SQL rows.
	 * @param array<int, array<string, mixed>> $semantic_rows Semantic rows.
	 * @param int                              $limit Max rows.
	 * @return array<int, array<string, mixed>>
	 */
	private function merge_knowledge_rows( $sql_rows, $semantic_rows, $limit ) {
		$merged = array();
		$seen   = array();

		foreach ( array_merge( is_array( $semantic_rows ) ? $semantic_rows : array(), is_array( $sql_rows ) ? $sql_rows : array() ) as $row ) {
			if ( ! is_array( $row ) || empty( $row['object_id'] ) ) {
				continue;
			}
			$object_id = (int) $row['object_id'];
			$type      = isset( $row['document_type'] ) ? (string) $row['document_type'] : 'content';
			$key       = $type . ':' . $object_id;
			if ( $object_id <= 0 || isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = 1;
			$merged[] = $row;
			if ( count( $merged ) >= absint( $limit ) ) {
				break;
			}
		}

		return $merged;
	}
}
