<?php
/**
 * Default form schema helpers.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\FormBuilder;

/**
 * Domyślna struktura i walidacja schemy formularza.
 */
final class SchemaDefaults {

	/**
	 * Aktualna wersja formatu schemy.
	 */
	public const SCHEMA_VERSION = 1;

	/**
	 * Dozwolone typy pól.
	 */
	public const FIELD_TYPES = array(
		'text',
		'email',
		'tel',
		'url',
		'number',
		'range',
		'star_rating',
		'date',
		'textarea',
		'select',
		'radio',
		'checkbox',
		'consent',
		'content',
		'file',
		'submit',
	);

	/**
	 * Zwraca pustą schemę formularza.
	 *
	 * @return array<string, mixed>
	 */
	public static function empty_schema(): array {
		return array(
			'version' => self::SCHEMA_VERSION,
			'rows'    => array(),
		);
	}

	/**
	 * Pobiera schemę formularza z meta lub zwraca pustą.
	 *
	 * @param int $form_id ID formularza.
	 * @return array<string, mixed>
	 */
	public static function get_schema( int $form_id ): array {
		$stored = get_post_meta( $form_id, \Weblo\FastForms\PostTypes\FormPostType::META_SCHEMA, true );

		if ( ! is_array( $stored ) || empty( $stored ) ) {
			return self::empty_schema();
		}

		return self::sanitize_schema( $stored );
	}

	/**
	 * Sanityzuje schemę formularza.
	 *
	 * @param array<string, mixed> $schema Surowa schemа.
	 * @return array<string, mixed>
	 */
	public static function sanitize_schema( array $schema ): array {
		$sanitized = array(
			'version' => isset( $schema['version'] ) ? absint( $schema['version'] ) : self::SCHEMA_VERSION,
			'rows'    => array(),
		);

		if ( empty( $schema['rows'] ) || ! is_array( $schema['rows'] ) ) {
			return $sanitized;
		}

		foreach ( $schema['rows'] as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$row_html_id = preg_replace( '/[^a-zA-Z0-9_-]/', '', trim( (string) ( $row['htmlId'] ?? '' ) ) ) ?? '';
			if ( '' !== $row_html_id && preg_match( '/^[0-9]/', $row_html_id ) ) {
				$row_html_id = 'field-' . $row_html_id;
			}

			$sanitized_row = array(
				'id'       => sanitize_key( $row['id'] ?? '' ),
				'cssClass' => sanitize_text_field( $row['cssClass'] ?? '' ),
				'htmlId'   => $row_html_id,
				'columns'  => array(),
			);

			if ( empty( $row['columns'] ) || ! is_array( $row['columns'] ) ) {
				continue;
			}

			foreach ( $row['columns'] as $column ) {
				if ( ! is_array( $column ) ) {
					continue;
				}

				$col_html_id = preg_replace( '/[^a-zA-Z0-9_-]/', '', trim( (string) ( $column['htmlId'] ?? '' ) ) ) ?? '';
				if ( '' !== $col_html_id && preg_match( '/^[0-9]/', $col_html_id ) ) {
					$col_html_id = 'field-' . $col_html_id;
				}

				$sanitized_column = array(
					'id'       => sanitize_key( $column['id'] ?? '' ),
					'cssClass' => sanitize_text_field( $column['cssClass'] ?? '' ),
					'htmlId'   => $col_html_id,
					'width'    => min( 100, max( 1, absint( $column['width'] ?? 100 ) ) ),
					'fields'   => array(),
				);

				if ( empty( $column['fields'] ) || ! is_array( $column['fields'] ) ) {
					$sanitized_row['columns'][] = $sanitized_column;
					continue;
				}

				foreach ( $column['fields'] as $field ) {
					if ( ! is_array( $field ) ) {
						continue;
					}

					$type = sanitize_key( $field['type'] ?? 'text' );
					if ( ! in_array( $type, self::FIELD_TYPES, true ) ) {
						continue;
					}

					$sanitized_field = array(
						'id'           => sanitize_key( $field['id'] ?? '' ),
						'type'         => $type,
						'name'         => sanitize_key( $field['name'] ?? '' ),
						'label'        => sanitize_text_field( $field['label'] ?? '' ),
						'required'     => ! empty( $field['required'] ),
						'placeholder'  => sanitize_text_field( $field['placeholder'] ?? '' ),
						'defaultValue' => sanitize_text_field( $field['defaultValue'] ?? '' ),
						'cssClass'     => sanitize_html_class( $field['cssClass'] ?? '' ),
						'minLength'    => isset( $field['minLength'] ) && '' !== $field['minLength'] ? absint( $field['minLength'] ) : '',
						'maxLength'    => isset( $field['maxLength'] ) && '' !== $field['maxLength'] ? absint( $field['maxLength'] ) : '',
						'rows'         => isset( $field['rows'] ) ? absint( $field['rows'] ) : 4,
						'min'          => isset( $field['min'] ) && '' !== $field['min'] ? floatval( $field['min'] ) : '',
						'max'          => isset( $field['max'] ) && '' !== $field['max'] ? floatval( $field['max'] ) : '',
						'step'         => isset( $field['step'] ) && '' !== $field['step'] ? floatval( $field['step'] ) : '',
						'options'      => self::sanitize_options( $field['options'] ?? array() ),
						'allowedTypes' => sanitize_text_field( $field['allowedTypes'] ?? '' ),
						'maxFileSize'  => isset( $field['maxFileSize'] ) && '' !== $field['maxFileSize'] ? absint( $field['maxFileSize'] ) : '',
						'submitText'   => sanitize_text_field( $field['submitText'] ?? BuilderI18n::default_submit_text() ),
						'loadingText'  => sanitize_text_field( $field['loadingText'] ?? BuilderI18n::default_loading_text() ),
						'liveValidation' => ! empty( $field['liveValidation'] ),
					);

					$sanitized_column['fields'][] = $sanitized_field;
				}

				$sanitized_row['columns'][] = $sanitized_column;
			}

			if ( ! empty( $sanitized_row['columns'] ) ) {
				$sanitized['rows'][] = $sanitized_row;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanityzuje opcje pola select.
	 *
	 * @param mixed $options Surowe opcje.
	 * @return array<int, array<string, string>>
	 */
	private static function sanitize_options( $options ): array {
		if ( ! is_array( $options ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $options as $option ) {
			if ( ! is_array( $option ) ) {
				continue;
			}

			$label = sanitize_text_field( $option['label'] ?? '' );
			$value = sanitize_text_field( $option['value'] ?? '' );

			if ( '' === $label && '' === $value ) {
				continue;
			}

			$sanitized[] = array(
				'label' => $label,
				'value' => $value,
			);
		}

		return $sanitized;
	}
}
