<?php
/**
 * Saves form schema on standard WordPress post save.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\FormBuilder;

use Weblo\FastForms\PostTypes\FormPostType;
use Weblo\FastForms\Support\Capabilities;
use Weblo\FastForms\Support\DebugLog;

/**
 * Zapisuje schemę przy kliknięciu „Aktualizuj” w WordPressie.
 */
final class FormSaver {

	public const NONCE_ACTION = 'ff_save_form_schema';
	public const NONCE_FIELD  = 'ff_schema_nonce';
	public const SCHEMA_FIELD   = 'ff_schema';
	public const SETTINGS_FIELD = 'ff_form_settings';

	/**
	 * Rejestruje hook zapisu.
	 */
	public function register(): void {
		add_action( 'save_post_' . FormPostType::POST_TYPE, array( $this, 'save_schema' ), 10, 2 );
	}

	/**
	 * Zapisuje schemę z ukrytego pola formularza edycji.
	 *
	 * @param int      $post_id ID wpisu.
	 * @param \WP_Post $post    Obiekt wpisu.
	 */
	public function save_schema( int $post_id, \WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- weryfikacja nonce.
		if ( ! wp_verify_nonce( wp_unslash( $_POST[ self::NONCE_FIELD ] ), self::NONCE_ACTION ) ) {
			DebugLog::schema( 'Schema save rejected — invalid nonce', array( 'form_id' => $post_id ), 'WARNING' );
			return;
		}

		if ( ! Capabilities::can_edit_form( $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::SCHEMA_FIELD ] ) ) {
			DebugLog::schema( 'Schema save skipped — missing ff_schema field', array( 'form_id' => $post_id ), 'WARNING' );
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- dekodowanie JSON + Schema::sanitize.
		$raw_json = wp_unslash( (string) $_POST[ self::SCHEMA_FIELD ] );
		$encoded  = isset( $_POST[ SchemaPayload::ENCODED_FIELD ] )
			? wp_unslash( (string) $_POST[ SchemaPayload::ENCODED_FIELD ] )
			: '';

		$payload = SchemaPayload::decode_from_post( $raw_json, $encoded );
		$decoded = $payload['decoded'];

		DebugLog::schema(
			'Schema save — POST payload decoded',
			array(
				'form_id'         => $post_id,
				'decode_source'   => $payload['source'],
				'payload_meta'    => $payload['meta'],
				'incoming_fields' => is_array( $decoded ) ? DebugLog::count_schema_fields( $decoded ) : 0,
				'incoming_rows'   => is_array( $decoded ) ? count( $decoded['rows'] ?? array() ) : 0,
			)
		);

		if ( ! is_array( $decoded ) ) {
			DebugLog::schema(
				'Schema save failed — invalid JSON',
				array(
					'form_id'       => $post_id,
					'decode_source' => $payload['source'],
					'payload_meta'  => $payload['meta'],
				),
				'ERROR'
			);
			return;
		}

		$before_count   = DebugLog::count_schema_fields( FormSchemaStorage::get( $post_id ) );
		$incoming_count = DebugLog::count_schema_fields( $decoded );

		if ( $before_count > 0 && 0 === $incoming_count ) {
			DebugLog::schema(
				'Schema save blocked — refusing to overwrite with empty schema',
				array(
					'form_id'       => $post_id,
					'fields_before' => $before_count,
					'decode_source' => $payload['source'],
				),
				'WARNING'
			);
			return;
		}

		$result = FormSchemaStorage::save( $post_id, $decoded );

		if ( false === $result ) {
			DebugLog::schema(
				'Schema save failed — storage rejected payload',
				array(
					'form_id'          => $post_id,
					'fields_before'    => $before_count,
					'incoming_fields'  => $incoming_count,
					'decode_source'    => $payload['source'],
				),
				'ERROR'
			);
			return;
		}

		$after_count = DebugLog::count_schema_fields( $result['schema'] );

		DebugLog::schema(
			'Post save schema stored',
			array(
				'form_id'        => $post_id,
				'fields_before'  => $before_count,
				'fields_after'   => $after_count,
				'schema_version' => $result['schemaVersion'],
				'save_source'    => 'wordpress_update',
				'decode_source'  => $payload['source'],
			)
		);

		if ( isset( $_POST[ self::SETTINGS_FIELD ] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$settings_json = wp_unslash( $_POST[ self::SETTINGS_FIELD ] );
			$settings      = json_decode( $settings_json, true );

			if ( is_array( $settings ) ) {
				FormSettingsStorage::save_all( $post_id, $settings );
				DebugLog::schema( 'Post save settings stored', array( 'form_id' => $post_id ) );
			}
		}
	}
}
