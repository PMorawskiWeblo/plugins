<?php
/**
 * Text encoding helpers.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Support;

/**
 * Naprawia uszkodzone sekwencje Unicode (np. u015b zamiast ś po utracie backslasha).
 */
final class TextEncoding {

	/**
	 * Naprawia literalne sekwencje uXXXX w tekście.
	 */
	public static function repair_unicode_escapes( string $text ): string {
		if ( '' === $text || ! preg_match( '/(?<!\\\\)(?<![a-fA-F0-9])u[0-9a-fA-F]{4}/', $text ) ) {
			return $text;
		}

		$repaired = preg_replace_callback(
			'/(?<!\\\\)(?<![a-fA-F0-9])u([0-9a-fA-F]{4})/',
			static function ( array $matches ): string {
				$codepoint = hexdec( $matches[1] );

				if ( $codepoint <= 0 ) {
					return $matches[0];
				}

				if ( function_exists( 'mb_chr' ) ) {
					$char = mb_chr( $codepoint, 'UTF-8' );

					return false !== $char ? $char : $matches[0];
				}

				return html_entity_decode( '&#x' . $matches[1] . ';', ENT_NOQUOTES, 'UTF-8' );
			},
			$text
		);

		return is_string( $repaired ) ? $repaired : $text;
	}

	/**
	 * Sanityzuje tekst pola formularza (UTF-8 + naprawa sekwencji uXXXX).
	 */
	public static function sanitize_field_text( string $text ): string {
		$text = self::repair_unicode_escapes( $text );

		return sanitize_text_field( $text );
	}
}
