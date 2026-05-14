<?php
/**
 * Database migration runner.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Migrations {
	/**
	 * Schema service.
	 *
	 * @var StoreGuide_AI_Schema
	 */
	private $schema;

	/**
	 * Option key for schema version.
	 */
	const OPTION_KEY = 'storeguide_ai_schema_version';

	/**
	 * Constructor.
	 *
	 * @param StoreGuide_AI_Schema $schema Schema service.
	 */
	public function __construct( $schema ) {
		$this->schema = $schema;
	}

	/**
	 * Migrate if needed.
	 *
	 * @return void
	 */
	public function maybe_migrate() {
		$current = (string) get_option( self::OPTION_KEY, '' );

		if ( version_compare( $current, StoreGuide_AI_Schema::VERSION, '>=' ) ) {
			return;
		}

		$this->schema->create_tables();
		update_option( self::OPTION_KEY, StoreGuide_AI_Schema::VERSION, false );
	}
}
