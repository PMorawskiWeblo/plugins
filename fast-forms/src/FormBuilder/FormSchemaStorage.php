<?php
/**
 * Form schema persistence.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\FormBuilder;

use Weblo\FastForms\PostTypes\FormPostType;
use Weblo\FastForms\Support\DebugLog;

/**
 * Zapis i odczyt schemy formularza w post meta.
 */
final class FormSchemaStorage {

	/** @var array<int, array<string, mixed>> */
	private static array $cache = array();

	/**
	 * Pobiera schemę formularza z meta.
	 *
	 * @param int $form_id ID formularza.
	 * @return array<string, mixed>
	 */
	public static function get( int $form_id ): array {
		if ( isset( self::$cache[ $form_id ] ) ) {
			return self::$cache[ $form_id ];
		}

		$schema_raw = get_post_meta( $form_id, FormPostType::META_SCHEMA, true );
		$sanitized  = Schema::sanitize( SchemaMetaJson::decode( $schema_raw ) );

		if ( is_string( $schema_raw ) && '' !== $schema_raw && null === json_decode( $schema_raw, true ) && DebugLog::count_schema_fields( $sanitized ) > 0 ) {
			$encoded = SchemaMetaJson::encode( $sanitized );

			if ( null !== $encoded ) {
				update_post_meta( $form_id, FormPostType::META_SCHEMA, $encoded );
				DebugLog::schema( 'Schema meta auto-repaired in database', array( 'form_id' => $form_id ) );
			}
		}

		self::$cache[ $form_id ] = $sanitized;

		return self::$cache[ $form_id ];
	}

	/**
	 * Zapisuje schemę formularza.
	 *
	 * @param int                  $form_id ID formularza.
	 * @param array<string, mixed> $schema  Schemа do zapisu.
	 * @return array{schema: array<string, mixed>, schemaVersion: int}|false
	 */
	public static function save( int $form_id, array $schema ) {
		$existing         = self::get( $form_id );
		$existing_count   = DebugLog::count_schema_fields( $existing );
		$incoming_count   = DebugLog::count_schema_fields( $schema );
		$sanitized        = Schema::sanitize( $schema );
		$sanitized_count  = DebugLog::count_schema_fields( $sanitized );

		DebugLog::schema(
			'Schema storage — sanitize result',
			array(
				'form_id'          => $form_id,
				'fields_existing'  => $existing_count,
				'fields_incoming'  => $incoming_count,
				'fields_sanitized' => $sanitized_count,
				'rows_incoming'      => count( $schema['rows'] ?? array() ),
				'rows_sanitized'     => count( $sanitized['rows'] ?? array() ),
			)
		);

		if ( $existing_count > 0 && 0 === $sanitized_count ) {
			DebugLog::schema(
				'Schema storage blocked — sanitize removed all fields',
				array(
					'form_id'         => $form_id,
					'fields_existing' => $existing_count,
					'fields_incoming' => $incoming_count,
				),
				'WARNING'
			);

			return false;
		}

		$new_version = (int) get_post_meta( $form_id, FormPostType::META_SCHEMA_VERSION, true );

		if ( $new_version < 1 ) {
			$new_version = Schema::VERSION;
		} else {
			++$new_version;
		}

		$encoded = SchemaMetaJson::encode( $sanitized );

		if ( null === $encoded ) {
			DebugLog::schema(
				'Schema storage failed — wp_json_encode error',
				array(
					'form_id'          => $form_id,
					'fields_sanitized' => $sanitized_count,
					'json_error'       => json_last_error_msg(),
				),
				'ERROR'
			);

			return false;
		}

		update_post_meta( $form_id, FormPostType::META_SCHEMA, $encoded );
		update_post_meta( $form_id, FormPostType::META_SCHEMA_VERSION, $new_version );

		self::$cache[ $form_id ] = $sanitized;

		return array(
			'schema'        => $sanitized,
			'schemaVersion' => $new_version,
		);
	}
}
