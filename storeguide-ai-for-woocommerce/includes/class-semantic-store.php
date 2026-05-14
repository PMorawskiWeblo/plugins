<?php
/**
 * Optional semantic retrieval integration (Pinecone + embeddings).
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Semantic_Store {
	/**
	 * Register hooks.
	 *
	 * @param StoreGuide_AI_Loader $loader Loader.
	 * @return void
	 */
	public function register( $loader ) {
		$loader->add_filter( 'storeguide_ai_semantic_results', $this, 'filter_semantic_results', 10, 4 );
		$loader->add_action( 'storeguide_ai_document_indexed', $this, 'on_document_indexed', 10, 2 );
		$loader->add_action( 'storeguide_ai_document_deleted', $this, 'on_document_deleted', 10, 2 );
	}

	/**
	 * Return semantic results in retriever format.
	 *
	 * @param array<int, array<string, mixed>> $rows Existing rows.
	 * @param string                            $type product|knowledge.
	 * @param string                            $query Query.
	 * @param int                               $limit Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public function filter_semantic_results( $rows, $type, $query, $limit ) {
		$config = $this->get_config();
		if ( empty( $config['enabled'] ) ) {
			return $rows;
		}

		$embedding = $this->create_embedding( $query, $config );
		if ( empty( $embedding ) ) {
			return $rows;
		}

		$matches = $this->query_pinecone( $embedding, $config, max( 1, absint( $limit ) ) );
		if ( empty( $matches ) ) {
			return $rows;
		}

		$doc_type = ( 'product' === $type ) ? 'product' : 'knowledge';
		$object_ids = array();
		foreach ( $matches as $match ) {
			if ( ! is_array( $match ) || empty( $match['metadata'] ) || ! is_array( $match['metadata'] ) ) {
				continue;
			}
			$meta = $match['metadata'];
			$meta_type = isset( $meta['doc_type'] ) ? sanitize_key( (string) $meta['doc_type'] ) : '';
			$object_id = isset( $meta['object_id'] ) ? absint( $meta['object_id'] ) : 0;
			if ( $object_id <= 0 ) {
				continue;
			}
			if ( 'product' === $doc_type && 'product' !== $meta_type ) {
				continue;
			}
			if ( 'knowledge' === $doc_type && ! in_array( $meta_type, array( 'page', 'post' ), true ) ) {
				continue;
			}
			$object_ids[] = $object_id;
		}

		if ( empty( $object_ids ) ) {
			return $rows;
		}

		if ( 'product' === $doc_type ) {
			return $this->fetch_product_rows_by_ids( $object_ids );
		}

		return $this->fetch_knowledge_rows_by_ids( $object_ids );
	}

	/**
	 * Upsert semantic vector when document is indexed.
	 *
	 * @param string               $document_type Document type.
	 * @param array<string, mixed> $payload Document payload.
	 * @return void
	 */
	public function on_document_indexed( $document_type, $payload ) {
		$config = $this->get_config();
		if ( empty( $config['enabled'] ) ) {
			return;
		}
		if ( ! is_array( $payload ) ) {
			return;
		}

		$document_type = sanitize_key( (string) $document_type );
		if ( ! in_array( $document_type, array( 'product', 'page', 'post' ), true ) ) {
			return;
		}

		$object_id = isset( $payload['object_id'] ) ? absint( $payload['object_id'] ) : 0;
		if ( $object_id <= 0 ) {
			return;
		}

		$title   = isset( $payload['title'] ) ? (string) $payload['title'] : '';
		$summary = isset( $payload['summary'] ) ? (string) $payload['summary'] : '';
		$content = isset( $payload['content_text'] ) ? (string) $payload['content_text'] : '';
		$text    = trim( wp_strip_all_tags( $title . "\n" . $summary . "\n" . $content ) );
		if ( '' === $text ) {
			return;
		}
		if ( strlen( $text ) > 8000 ) {
			$text = substr( $text, 0, 8000 );
		}

		$embedding = $this->create_embedding( $text, $config );
		if ( empty( $embedding ) ) {
			return;
		}

		$vector_id = $document_type . ':' . $object_id;
		$this->upsert_vector( $vector_id, $embedding, $document_type, $object_id, $config );
	}

	/**
	 * Delete semantic vector when document is deleted.
	 *
	 * @param string $document_type Document type.
	 * @param int    $object_id Object ID.
	 * @return void
	 */
	public function on_document_deleted( $document_type, $object_id ) {
		$config = $this->get_config();
		if ( empty( $config['enabled'] ) ) {
			return;
		}
		$document_type = sanitize_key( (string) $document_type );
		$object_id     = absint( $object_id );
		if ( $object_id <= 0 ) {
			return;
		}
		$this->delete_vector( $document_type . ':' . $object_id, $config );
	}

	/**
	 * Read semantic config.
	 *
	 * @return array<string, mixed>
	 */
	private function get_config() {
		$index_options    = get_option( 'storeguide_ai_index_options', array() );
		$provider_options = get_option( 'storeguide_ai_provider_options', array() );
		$provider_key     = isset( $provider_options['api_key'] ) ? (string) $provider_options['api_key'] : '';
		$provider_name    = isset( $provider_options['provider'] ) ? sanitize_key( (string) $provider_options['provider'] ) : '';

		$embedding_api_key = isset( $index_options['embedding_api_key'] ) ? (string) $index_options['embedding_api_key'] : '';
		if ( '' === trim( $embedding_api_key ) && 'openai' === $provider_name ) {
			$embedding_api_key = $provider_key;
		}

		$host = isset( $index_options['pinecone_host'] ) ? trim( (string) $index_options['pinecone_host'] ) : '';
		$host = preg_replace( '#^https?://#', '', $host );
		$host = rtrim( (string) $host, '/' );

		$enabled = ! empty( $index_options['semantic_retrieval_enabled'] )
			&& '' !== $host
			&& ! empty( $index_options['pinecone_api_key'] )
			&& '' !== trim( $embedding_api_key );

		return array(
			'enabled'           => $enabled ? 1 : 0,
			'pinecone_api_key'  => isset( $index_options['pinecone_api_key'] ) ? (string) $index_options['pinecone_api_key'] : '',
			'pinecone_host'     => $host,
			'pinecone_namespace'=> isset( $index_options['pinecone_namespace'] ) ? sanitize_key( (string) $index_options['pinecone_namespace'] ) : '',
			'embedding_api_key' => $embedding_api_key,
			'embedding_model'   => isset( $index_options['embedding_model'] ) && '' !== trim( (string) $index_options['embedding_model'] )
				? sanitize_text_field( (string) $index_options['embedding_model'] )
				: 'text-embedding-3-small',
		);
	}

	/**
	 * Create OpenAI embedding vector.
	 *
	 * @param string               $text Input text.
	 * @param array<string, mixed> $config Config.
	 * @return array<int, float>
	 */
	private function create_embedding( $text, $config ) {
		$api_key = isset( $config['embedding_api_key'] ) ? (string) $config['embedding_api_key'] : '';
		$model   = isset( $config['embedding_model'] ) ? (string) $config['embedding_model'] : 'text-embedding-3-small';
		if ( '' === trim( $api_key ) ) {
			return array();
		}

		$response = wp_remote_post(
			'https://api.openai.com/v1/embeddings',
			array(
				'timeout' => 45,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body' => wp_json_encode(
					array(
						'model' => $model,
						'input' => (string) $text,
					)
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return array();
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 || ! is_array( $body ) ) {
			return array();
		}
		if ( empty( $body['data'][0]['embedding'] ) || ! is_array( $body['data'][0]['embedding'] ) ) {
			return array();
		}

		return array_map( 'floatval', $body['data'][0]['embedding'] );
	}

	/**
	 * Query Pinecone for nearest vectors.
	 *
	 * @param array<int, float>    $embedding Embedding vector.
	 * @param array<string, mixed> $config Config.
	 * @param int                  $top_k Top K.
	 * @return array<int, array<string, mixed>>
	 */
	private function query_pinecone( $embedding, $config, $top_k ) {
		$host = isset( $config['pinecone_host'] ) ? (string) $config['pinecone_host'] : '';
		$key  = isset( $config['pinecone_api_key'] ) ? (string) $config['pinecone_api_key'] : '';
		if ( '' === $host || '' === trim( $key ) || empty( $embedding ) ) {
			return array();
		}

		$body = array(
			'vector'          => $embedding,
			'topK'            => max( 1, min( 20, absint( $top_k ) ) ),
			'includeMetadata' => true,
		);
		if ( ! empty( $config['pinecone_namespace'] ) ) {
			$body['namespace'] = (string) $config['pinecone_namespace'];
		}

		$response = wp_remote_post(
			'https://' . $host . '/query',
			array(
				'timeout' => 45,
				'headers' => array(
					'Api-Key'      => $key,
					'Content-Type' => 'application/json',
				),
				'body' => wp_json_encode( $body ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return array();
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 || ! is_array( $data ) || empty( $data['matches'] ) || ! is_array( $data['matches'] ) ) {
			return array();
		}

		return $data['matches'];
	}

	/**
	 * Upsert vector to Pinecone.
	 *
	 * @param string               $vector_id Vector ID.
	 * @param array<int, float>    $embedding Vector.
	 * @param string               $document_type Document type.
	 * @param int                  $object_id Object ID.
	 * @param array<string, mixed> $config Config.
	 * @return void
	 */
	private function upsert_vector( $vector_id, $embedding, $document_type, $object_id, $config ) {
		$host = isset( $config['pinecone_host'] ) ? (string) $config['pinecone_host'] : '';
		$key  = isset( $config['pinecone_api_key'] ) ? (string) $config['pinecone_api_key'] : '';
		if ( '' === $host || '' === trim( $key ) || empty( $embedding ) ) {
			return;
		}

		$vector = array(
			'id'       => $vector_id,
			'values'   => $embedding,
			'metadata' => array(
				'doc_type'  => sanitize_key( $document_type ),
				'object_id' => absint( $object_id ),
			),
		);

		$body = array( 'vectors' => array( $vector ) );
		if ( ! empty( $config['pinecone_namespace'] ) ) {
			$body['namespace'] = (string) $config['pinecone_namespace'];
		}

		wp_remote_post(
			'https://' . $host . '/vectors/upsert',
			array(
				'timeout' => 45,
				'headers' => array(
					'Api-Key'      => $key,
					'Content-Type' => 'application/json',
				),
				'body' => wp_json_encode( $body ),
			)
		);
	}

	/**
	 * Delete vector from Pinecone.
	 *
	 * @param string               $vector_id Vector ID.
	 * @param array<string, mixed> $config Config.
	 * @return void
	 */
	private function delete_vector( $vector_id, $config ) {
		$host = isset( $config['pinecone_host'] ) ? (string) $config['pinecone_host'] : '';
		$key  = isset( $config['pinecone_api_key'] ) ? (string) $config['pinecone_api_key'] : '';
		if ( '' === $host || '' === trim( $key ) ) {
			return;
		}

		$body = array(
			'ids' => array( (string) $vector_id ),
		);
		if ( ! empty( $config['pinecone_namespace'] ) ) {
			$body['namespace'] = (string) $config['pinecone_namespace'];
		}

		wp_remote_post(
			'https://' . $host . '/vectors/delete',
			array(
				'timeout' => 45,
				'headers' => array(
					'Api-Key'      => $key,
					'Content-Type' => 'application/json',
				),
				'body' => wp_json_encode( $body ),
			)
		);
	}

	/**
	 * Fetch product rows from index DB by IDs.
	 *
	 * @param array<int, int> $object_ids Product IDs.
	 * @return array<int, array<string, mixed>>
	 */
	private function fetch_product_rows_by_ids( $object_ids ) {
		global $wpdb;
		$object_ids = array_values( array_filter( array_map( 'absint', $object_ids ) ) );
		if ( empty( $object_ids ) ) {
			return array();
		}

		$documents = $wpdb->prefix . 'storeguide_ai_documents';
		$meta      = $wpdb->prefix . 'storeguide_ai_document_meta';
		$ph        = implode( ',', array_fill( 0, count( $object_ids ), '%d' ) );
		$params    = array_merge( array( 'product', 'active' ), $object_ids );
		$sql       = "SELECT d.object_id AS product_id, d.title, d.summary, m.price, m.stock_status, m.category_ids_json
			FROM {$documents} d
			LEFT JOIN {$meta} m ON m.document_id = d.id
			WHERE d.document_type = %s
			  AND d.status = %s
			  AND d.object_id IN ({$ph})";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Fetch knowledge rows from index DB by IDs.
	 *
	 * @param array<int, int> $object_ids Object IDs.
	 * @return array<int, array<string, mixed>>
	 */
	private function fetch_knowledge_rows_by_ids( $object_ids ) {
		global $wpdb;
		$object_ids = array_values( array_filter( array_map( 'absint', $object_ids ) ) );
		if ( empty( $object_ids ) ) {
			return array();
		}

		$documents = $wpdb->prefix . 'storeguide_ai_documents';
		$ph        = implode( ',', array_fill( 0, count( $object_ids ), '%d' ) );
		$params    = array_merge( array( 'active' ), $object_ids );
		$sql       = "SELECT d.object_id, d.document_type, d.title, d.summary, d.content_text
			FROM {$documents} d
			WHERE d.status = %s
			  AND d.object_id IN ({$ph})
			  AND d.document_type IN ('page','post')";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return array();
		}
		foreach ( $rows as &$row ) {
			$row['permalink'] = get_permalink( (int) $row['object_id'] );
		}
		return $rows;
	}
}

