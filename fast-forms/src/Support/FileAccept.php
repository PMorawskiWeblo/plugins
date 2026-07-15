<?php
/**
 * HTML accept attribute helpers for file fields.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Support;

/**
 * Buduje atrybut accept dla input[type=file].
 */
final class FileAccept {

	/**
	 * Zwraca wartość atrybutu accept (np. .png,image/png).
	 */
	public static function build( string $allowed_types ): string {
		$allowed_types = trim( $allowed_types );

		if ( '' === $allowed_types ) {
			return '';
		}

		$extensions = preg_split( '/\s*,\s*/', strtolower( $allowed_types ) ) ?: array();
		$parts      = array();

		foreach ( $extensions as $extension ) {
			$extension = ltrim( trim( $extension ), '.' );

			if ( '' === $extension ) {
				continue;
			}

			$parts[] = '.' . $extension;

			$mime = self::mime_for_extension( $extension );

			if ( '' !== $mime ) {
				$parts[] = $mime;
			}
		}

		return implode( ',', array_unique( $parts ) );
	}

	/**
	 * Zwraca mapę rozszerzenie => MIME ograniczoną do dozwolonych typów pola.
	 *
	 * @return array<string, string>
	 */
	public static function mimes_for_allowed_types( string $allowed_types ): array {
		$extensions = self::extensions_list( $allowed_types );

		if ( empty( $extensions ) ) {
			return self::filtered_wp_mimes();
		}

		$mimes   = array();
		$all     = wp_get_mime_types();

		foreach ( $all as $ext_group => $mime ) {
			foreach ( explode( '|', (string) $ext_group ) as $extension ) {
				if ( in_array( $extension, $extensions, true ) ) {
					$mimes[ $extension ] = (string) $mime;
				}
			}
		}

		/**
		 * Filtruje dozwolone typy MIME dla uploadów formularza.
		 *
		 * @param array<string, string> $mimes         Lista MIME.
		 * @param string                $allowed_types Dozwolone rozszerzenia z pola.
		 */
		return apply_filters( 'ff_allowed_upload_mimes', $mimes, $allowed_types );
	}

	/**
	 * @return array<string, string>
	 */
	private static function filtered_wp_mimes(): array {
		$mimes = wp_get_mime_types();

		/**
		 * Filtruje dozwolone typy MIME dla uploadów formularza.
		 *
		 * @param array<string, string> $mimes Lista MIME WordPress.
		 * @param string                $allowed_types Pusty — brak ograniczenia per pole.
		 */
		return apply_filters( 'ff_allowed_upload_mimes', $mimes, '' );
	}

	/**
	 * @return array<int, string>
	 */
	public static function extensions_list( string $allowed_types ): array {
		$allowed_types = trim( $allowed_types );

		if ( '' === $allowed_types ) {
			return array();
		}

		$extensions = preg_split( '/\s*,\s*/', strtolower( $allowed_types ) ) ?: array();
		$list       = array();

		foreach ( $extensions as $extension ) {
			$extension = ltrim( trim( $extension ), '.' );

			if ( '' !== $extension ) {
				$list[] = $extension;
			}
		}

		return array_values( array_unique( $list ) );
	}

	private static function mime_for_extension( string $extension ): string {
		if ( ! function_exists( 'wp_get_mime_types' ) ) {
			return '';
		}

		foreach ( wp_get_mime_types() as $extensions => $mime ) {
			$items = explode( '|', (string) $extensions );

			if ( in_array( $extension, $items, true ) ) {
				return (string) $mime;
			}
		}

		return '';
	}
}
