<?php
/**
 * Dekodowanie schemy z POST (JSON lub base64/base64url).
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\FormBuilder;

/**
 * Odczyt payloadu schemy z żądania WordPress (Aktualizuj).
 */
final class SchemaPayload {

	public const ENCODED_FIELD = 'ff_schema_encoded';

	/**
	 * Dekoduje schemę z pól POST.
	 *
	 * @param string $raw_json JSON z textarea (ff_schema).
	 * @param string $encoded  Base64/base64url z ukrytego pola.
	 * @return array{decoded: array<string, mixed>|null, source: string, meta: array<string, mixed>}
	 */
	public static function decode_from_post( string $raw_json, string $encoded ): array {
		$meta = array(
			'raw_len'     => strlen( $raw_json ),
			'encoded_len' => strlen( $encoded ),
		);

		if ( '' !== $encoded ) {
			$json   = self::decode_base64_payload( $encoded );
			$meta['b64_json_len'] = is_string( $json ) ? strlen( $json ) : 0;
			$meta['b64_decode_ok'] = is_string( $json ) && '' !== $json;

			if ( is_string( $json ) && '' !== $json ) {
				$decoded = json_decode( $json, true );

				if ( is_array( $decoded ) ) {
					return array(
						'decoded' => $decoded,
						'source'  => 'base64',
						'meta'    => $meta,
					);
				}

				$meta['b64_json_error'] = json_last_error_msg();
			}
		}

		if ( '' !== $raw_json ) {
			$decoded = json_decode( $raw_json, true );

			if ( is_array( $decoded ) ) {
				return array(
					'decoded' => $decoded,
					'source'  => 'raw_json',
					'meta'    => $meta,
				);
			}

			$meta['raw_json_error'] = json_last_error_msg();
		}

		return array(
			'decoded' => null,
			'source'  => 'none',
			'meta'    => $meta,
		);
	}

	/**
	 * Dekoduje standardowe base64 lub base64url (bez znaków + i / w URL).
	 *
	 * @param string $encoded Payload z POST.
	 */
	private static function decode_base64_payload( string $encoded ): ?string {
		$encoded = trim( $encoded );

		if ( '' === $encoded ) {
			return null;
		}

		if ( str_contains( $encoded, '-' ) || str_contains( $encoded, '_' ) ) {
			$normalized = strtr( $encoded, '-_', '+/' );
			$pad        = strlen( $normalized ) % 4;

			if ( $pad > 0 ) {
				$normalized .= str_repeat( '=', 4 - $pad );
			}

			$json = base64_decode( $normalized, true );

			if ( is_string( $json ) && '' !== $json ) {
				return $json;
			}
		}

		// W application/x-www-form-urlencoded znak + w base64 staje się spacją.
		$legacy = str_replace( ' ', '+', $encoded );
		$json   = base64_decode( $legacy, true );

		if ( is_string( $json ) && '' !== $json ) {
			return $json;
		}

		return null;
	}
}
