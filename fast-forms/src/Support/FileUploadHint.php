<?php
/**
 * Human-readable upload rules for file fields.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Support;

/**
 * Tekst podpowiedzi o limitach uploadu plików.
 */
final class FileUploadHint {

	/**
	 * Buduje komunikat o dozwolonych typach i limitach.
	 *
	 * @param array<string, mixed> $field Definicja pola file.
	 */
	public static function build( array $field ): string {
		$parts = array();

		$allowed_raw = trim( (string) ( $field['allowedTypes'] ?? '' ) );
		$extensions  = FileAccept::extensions_list( $allowed_raw );

		if ( ! empty( $extensions ) ) {
			$parts[] = sprintf(
				/* translators: %s: comma-separated file extensions */
				__( 'Allowed file types: %s', 'fast-forms' ),
				implode( ', ', $extensions )
			);
		}

		$max_kb = absint( $field['maxFileSize'] ?? 0 );

		if ( $max_kb > 0 ) {
			$parts[] = sprintf(
				/* translators: %s: maximum file size */
				__( 'Maximum file size: %s', 'fast-forms' ),
				self::format_size_label( $max_kb )
			);
		}

		if ( ! empty( $field['allowMultiple'] ) ) {
			$max_files = $field['maxFiles'] ?? '';
			$min_files = $field['minFiles'] ?? '';

			if ( '' !== $max_files ) {
				$parts[] = sprintf(
					/* translators: %d: maximum number of files */
					__( 'Maximum number of files: %d', 'fast-forms' ),
					(int) $max_files
				);
			}

			if ( '' !== $min_files ) {
				$parts[] = sprintf(
					/* translators: %d: minimum number of files */
					__( 'Minimum number of files: %d', 'fast-forms' ),
					(int) $min_files
				);
			}
		}

		if ( empty( $parts ) ) {
			return __( 'All common file types are allowed.', 'fast-forms' );
		}

		return implode( ' · ', $parts );
	}

	/**
	 * @param int $max_kb Limit w KB.
	 */
	private static function format_size_label( int $max_kb ): string {
		if ( $max_kb >= 1024 ) {
			$mb = $max_kb / 1024;

			return ( abs( $mb - round( $mb ) ) < 0.05 ? (string) (int) round( $mb ) : number_format( $mb, 1, '.', '' ) ) . ' MB';
		}

		return $max_kb . ' KB';
	}
}
