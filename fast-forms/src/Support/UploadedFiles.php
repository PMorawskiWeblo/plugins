<?php
/**
 * Helpers for entry file upload records.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Support;

/**
 * Normalizuje strukturę plików z $_FILES i meta zgłoszenia.
 */
final class UploadedFiles {

	/**
	 * Zwraca listę pojedynczych wpisów pliku z $_FILES dla klucza pola.
	 *
	 * @param array<string, mixed> $files $_FILES.
	 * @param string               $key   Klucz pola (bez []).
	 * @return array<int, array<string, mixed>>
	 */
	public static function normalize_uploads( array $files, string $key ): array {
		if ( ! isset( $files[ $key ] ) || ! is_array( $files[ $key ] ) ) {
			return array();
		}

		$file = $files[ $key ];

		if ( ! is_array( $file['name'] ?? null ) ) {
			if ( UPLOAD_ERR_NO_FILE === (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
				return array();
			}

			return array( $file );
		}

		$normalized = array();
		$count      = count( $file['name'] );

		for ( $index = 0; $index < $count; $index++ ) {
			if ( UPLOAD_ERR_NO_FILE === (int) ( $file['error'][ $index ] ?? UPLOAD_ERR_NO_FILE ) ) {
				continue;
			}

			$normalized[] = array(
				'name'     => $file['name'][ $index ] ?? '',
				'type'     => $file['type'][ $index ] ?? '',
				'tmp_name' => $file['tmp_name'][ $index ] ?? '',
				'error'    => $file['error'][ $index ] ?? UPLOAD_ERR_NO_FILE,
				'size'     => $file['size'][ $index ] ?? 0,
			);
		}

		return $normalized;
	}

	/**
	 * Zwraca listę plików z tablicy Validatora lub $_FILES.
	 *
	 * @param array<string, mixed> $files Tablica plików.
	 * @param string               $key   Klucz pola.
	 * @return array<int, array<string, mixed>>
	 */
	public static function items_for_field( array $files, string $key ): array {
		if ( ! isset( $files[ $key ] ) || ! is_array( $files[ $key ] ) ) {
			return array();
		}

		$item = $files[ $key ];

		if ( isset( $item['tmp_name'] ) && is_string( $item['tmp_name'] ) ) {
			if ( UPLOAD_ERR_NO_FILE === (int) ( $item['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
				return array();
			}

			return array( $item );
		}

		if ( array_is_list( $item ) ) {
			$normalized = array();

			foreach ( $item as $file ) {
				if ( ! is_array( $file ) ) {
					continue;
				}

				if ( UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
					continue;
				}

				$normalized[] = $file;
			}

			return $normalized;
		}

		return self::normalize_uploads( $files, $key );
	}

	/**
	 * Czy rekord meta reprezentuje wiele plików.
	 *
	 * @param array<string, mixed> $record Rekord z meta.
	 */
	public static function is_multiple_record( array $record ): bool {
		return ! empty( $record['multiple'] ) && isset( $record['items'] ) && is_array( $record['items'] );
	}

	/**
	 * Zwraca listę rekordów plików z meta zgłoszenia.
	 *
	 * @param mixed $record Surowy rekord meta.
	 * @return array<int, array<string, mixed>>
	 */
	public static function records_from_meta( $record ): array {
		if ( ! is_array( $record ) || empty( $record ) ) {
			return array();
		}

		if ( self::is_multiple_record( $record ) ) {
			$items = array();

			foreach ( $record['items'] as $item ) {
				if ( is_array( $item ) && '' !== (string) ( $item['file'] ?? '' ) ) {
					$items[] = $item;
				}
			}

			return $items;
		}

		if ( '' !== (string) ( $record['file'] ?? '' ) ) {
			return array( $record );
		}

		return array();
	}

	/**
	 * Buduje rekord meta do zapisu.
	 *
	 * @param array<int, array<string, mixed>> $items Lista plików.
	 */
	public static function build_meta_record( array $items ): array {
		if ( empty( $items ) ) {
			return array();
		}

		if ( 1 === count( $items ) ) {
			return $items[0];
		}

		return array(
			'multiple' => true,
			'items'    => array_values( $items ),
		);
	}
}
