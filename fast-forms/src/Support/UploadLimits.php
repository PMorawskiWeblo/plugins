<?php
/**
 * Global upload size limits.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Support;

/**
 * Globalne limity rozmiaru plików dla pól upload.
 */
final class UploadLimits {

	private const DEFAULT_MAX_KB = 5120;

	/**
	 * Zwraca efektywny limit rozmiaru pliku w KB (minimum z limitu pola i globalnego).
	 *
	 * @param int $field_max_kb Limit z definicji pola (0 = brak limitu pola).
	 */
	public static function effective_max_kb( int $field_max_kb ): int {
		$global = self::global_max_kb();

		if ( $field_max_kb > 0 && $global > 0 ) {
			return min( $field_max_kb, $global );
		}

		if ( $field_max_kb > 0 ) {
			return $field_max_kb;
		}

		return $global;
	}

	/**
	 * Globalny limit rozmiaru pliku w KB (0 = wyłączony).
	 */
	public static function global_max_kb(): int {
		/**
		 * Globalny maksymalny rozmiar pliku w KB dla wszystkich pól upload.
		 * Wartość 0 wyłącza globalny limit (obowiązuje tylko limit pola lub PHP).
		 *
		 * @param int $max_kb Domyślnie 5120 (5 MB).
		 */
		$max = (int) apply_filters( 'ff_max_upload_kb', self::DEFAULT_MAX_KB );

		return max( 0, $max );
	}

	/**
	 * Sprawdza, czy rozmiar pliku w bajtach przekracza efektywny limit.
	 *
	 * @param int $size_bytes     Rozmiar pliku w bajtach.
	 * @param int $field_max_kb   Limit pola w KB.
	 */
	public static function exceeds_limit( int $size_bytes, int $field_max_kb ): bool {
		$max_kb = self::effective_max_kb( $field_max_kb );

		if ( $max_kb <= 0 ) {
			return false;
		}

		return $size_bytes > ( $max_kb * 1024 );
	}
}
