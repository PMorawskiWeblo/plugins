<?php
/**
 * Database schema creator.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Schema {
	/**
	 * Current schema version.
	 */
	const VERSION = '0.1.0';

	/**
	 * Create or update tables.
	 *
	 * @return void
	 */
	public function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$documents       = $wpdb->prefix . 'storeguide_ai_documents';
		$document_meta   = $wpdb->prefix . 'storeguide_ai_document_meta';
		$conversations   = $wpdb->prefix . 'storeguide_ai_conversations';
		$messages        = $wpdb->prefix . 'storeguide_ai_messages';
		$logs            = $wpdb->prefix . 'storeguide_ai_logs';
		$quotas          = $wpdb->prefix . 'storeguide_ai_quotas';

		$sql_documents = "CREATE TABLE {$documents} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			document_type VARCHAR(50) NOT NULL,
			object_id BIGINT UNSIGNED NOT NULL,
			title TEXT NULL,
			summary LONGTEXT NULL,
			content_text LONGTEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			indexed_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY object_type_unique (document_type(20), object_id),
			KEY document_type (document_type)
		) {$charset_collate};";

		$sql_document_meta = "CREATE TABLE {$document_meta} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			document_id BIGINT UNSIGNED NOT NULL,
			product_id BIGINT UNSIGNED NULL,
			price DECIMAL(18,6) NULL,
			stock_status VARCHAR(20) NULL,
			category_ids_json LONGTEXT NULL,
			attribute_map_json LONGTEXT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY document_id_unique (document_id),
			KEY product_id (product_id),
			KEY price (price),
			KEY stock_status (stock_status)
		) {$charset_collate};";

		$sql_conversations = "CREATE TABLE {$conversations} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_key VARCHAR(100) NOT NULL,
			user_id BIGINT UNSIGNED NULL,
			customer_ip_hash VARCHAR(64) NULL,
			source_page TEXT NULL,
			started_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			PRIMARY KEY (id),
			KEY session_key (session_key)
		) {$charset_collate};";

		$sql_messages = "CREATE TABLE {$messages} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			conversation_id BIGINT UNSIGNED NOT NULL,
			role VARCHAR(20) NOT NULL,
			message_text LONGTEXT NOT NULL,
			provider VARCHAR(60) NULL,
			model VARCHAR(120) NULL,
			prompt_tokens INT UNSIGNED NULL,
			completion_tokens INT UNSIGNED NULL,
			estimated_cost DECIMAL(18,6) NULL,
			latency_ms INT UNSIGNED NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY conversation_id (conversation_id),
			KEY role (role)
		) {$charset_collate};";

		$sql_logs = "CREATE TABLE {$logs} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			log_type VARCHAR(50) NOT NULL,
			level VARCHAR(20) NOT NULL,
			context_key VARCHAR(191) NULL,
			message TEXT NOT NULL,
			details_json LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY log_type (log_type),
			KEY level (level)
		) {$charset_collate};";

		$sql_quotas = "CREATE TABLE {$quotas} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			scope_type VARCHAR(20) NOT NULL,
			scope_key VARCHAR(191) NOT NULL,
			period_type VARCHAR(20) NOT NULL,
			period_key VARCHAR(20) NOT NULL,
			requests_count INT UNSIGNED NOT NULL DEFAULT 0,
			input_tokens INT UNSIGNED NOT NULL DEFAULT 0,
			output_tokens INT UNSIGNED NOT NULL DEFAULT 0,
			estimated_cost DECIMAL(18,6) NOT NULL DEFAULT 0.000000,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY unique_scope_period (scope_type, scope_key(120), period_type, period_key)
		) {$charset_collate};";

		dbDelta( $sql_documents );
		dbDelta( $sql_document_meta );
		dbDelta( $sql_conversations );
		dbDelta( $sql_messages );
		dbDelta( $sql_logs );
		dbDelta( $sql_quotas );
	}
}
