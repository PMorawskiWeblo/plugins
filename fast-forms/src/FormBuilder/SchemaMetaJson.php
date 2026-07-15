<?php
/**
 * JSON schemy w post meta — kodowanie/dekodowanie z obsługą WP slashes.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\FormBuilder;

use Weblo\FastForms\Support\DebugLog;

/**
 * WordPress usuwa backslashe z JSON w post meta (href=\" staje się href=").
 * Zapis wymaga wp_slash(); odczyt — naprawy starych, uszkodzonych wpisów.
 */
final class SchemaMetaJson {

	private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

	/**
	 * Koduje schemę do zapisu w post meta.
	 *
	 * @param array<string, mixed> $schema Schemа.
	 */
	public static function encode( array $schema ): ?string {
		$encoded = wp_json_encode( $schema, self::JSON_FLAGS );

		if ( false === $encoded ) {
			return null;
		}

		return wp_slash( $encoded );
	}

	/**
	 * Dekoduje JSON schemy z post meta.
	 *
	 * @param mixed $raw Wartość z get_post_meta().
	 * @return array<string, mixed>|null
	 */
	public static function decode( $raw ): ?array {
		if ( is_array( $raw ) ) {
			return $raw;
		}

		if ( ! is_string( $raw ) || '' === $raw ) {
			return null;
		}

		$decoded = json_decode( $raw, true );

		if ( is_array( $decoded ) ) {
			return $decoded;
		}

		$decode_error = json_last_error_msg();
		$repaired     = self::repair_stripped_escapes( $raw );
		$decoded      = json_decode( $repaired, true );

		if ( is_array( $decoded ) ) {
			DebugLog::schema(
				'Schema meta JSON repaired after WP slash stripping',
				array(
					'json_error_before' => $decode_error,
					'raw_len'           => strlen( $raw ),
				),
				'WARNING'
			);

			return $decoded;
		}

		DebugLog::schema(
			'Schema meta JSON decode failed',
			array(
				'json_error' => json_last_error_msg(),
				'raw_len'    => strlen( $raw ),
			),
			'ERROR'
		);

		return null;
	}

	/**
	 * Naprawia JSON, w którym WP usunął backslashe z atrybutów HTML (np. href=" w consentText).
	 */
	private static function repair_stripped_escapes( string $json ): string {
		$repaired = preg_replace_callback(
			'/"consentText"\s*:\s*"(.*)"\s*,\s*"minLength"\s*:/s',
			static function ( array $matches ): string {
				$text = str_replace( '"', '\\"', $matches[1] );

				return '"consentText":"' . $text . '","minLength":';
			},
			$json
		);

		if ( ! is_string( $repaired ) ) {
			return $json;
		}

		if ( json_decode( $repaired, true ) !== null ) {
			return $repaired;
		}

		$repaired = preg_replace_callback(
			'/"label"\s*:\s*"(.*)"\s*,\s*"required"\s*:/s',
			static function ( array $matches ): string {
				$text = str_replace( '"', '\\"', $matches[1] );

				return '"label":"' . $text . '","required":';
			},
			$json
		);

		return is_string( $repaired ) ? $repaired : $json;
	}
}
