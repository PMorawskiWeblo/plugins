<?php
/**
 * Schema field helpers for submission.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Submission;

/**
 * Narzędzia do iteracji pól ze schemy formularza.
 */
final class SchemaFields {

	/**
	 * Zwraca płaską listę pól (bez submit).
	 *
	 * @param array<string, mixed> $schema Schemа formularza.
	 * @return array<int, array<string, mixed>>
	 */
	public static function flatten( array $schema ): array {
		$fields = array();

		if ( empty( $schema['rows'] ) || ! is_array( $schema['rows'] ) ) {
			return $fields;
		}

		foreach ( $schema['rows'] as $row ) {
			if ( empty( $row['columns'] ) || ! is_array( $row['columns'] ) ) {
				continue;
			}

			foreach ( $row['columns'] as $column ) {
				if ( empty( $column['fields'] ) || ! is_array( $column['fields'] ) ) {
					continue;
				}

				foreach ( $column['fields'] as $field ) {
					if ( ! is_array( $field ) ) {
						continue;
					}

					if ( in_array( ( $field['type'] ?? '' ), array( 'submit', 'content' ), true ) ) {
						continue;
					}

					$fields[] = $field;
				}
			}
		}

		return $fields;
	}

	/**
	 * Zwraca klucz pola używany w payloadzie (name lub id).
	 *
	 * @param array<string, mixed> $field Definicja pola.
	 */
	public static function field_key( array $field ): string {
		$name = sanitize_key( $field['name'] ?? '' );

		if ( '' !== $name ) {
			return $name;
		}

		return sanitize_key( $field['id'] ?? '' );
	}
}
