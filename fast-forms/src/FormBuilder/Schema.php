<?php
/**
 * Form schema helpers.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\FormBuilder;

use Weblo\FastForms\Support\ConsentHtml;
use Weblo\FastForms\Support\TextEncoding;

/**
 * Domyślna struktura schemy i sanitizacja.
 */
final class Schema {

	public const VERSION = 1;

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
			'version' => self::VERSION,
			'rows'    => array(),
		);
	}

	/**
	 * Normalizuje i sanityzuje schemę formularza.
	 *
	 * @param mixed $schema Surowa schemа.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $schema ): array {
		if ( ! is_array( $schema ) ) {
			return self::empty_schema();
		}

		$rows = array();

		if ( isset( $schema['rows'] ) && is_array( $schema['rows'] ) ) {
			foreach ( $schema['rows'] as $row ) {
				$sanitized_row = self::sanitize_row( $row );
				if ( null !== $sanitized_row ) {
					$rows[] = $sanitized_row;
				}
			}
		}

		return self::deduplicate_field_html_ids(
			array(
				'version' => self::VERSION,
				'rows'    => $rows,
			)
		);
	}

	/**
	 * Usuwa zduplikowane htmlId w schemie (pierwsze wystąpienie zostaje).
	 *
	 * @param array<string, mixed> $schema Schemа.
	 * @return array<string, mixed>
	 */
	private static function deduplicate_field_html_ids( array $schema ): array {
		if ( empty( $schema['rows'] ) || ! is_array( $schema['rows'] ) ) {
			return $schema;
		}

		$used = array();

		foreach ( $schema['rows'] as $row_index => $row ) {
			if ( empty( $row['columns'] ) || ! is_array( $row['columns'] ) ) {
				continue;
			}

			foreach ( $row['columns'] as $column_index => $column ) {
				if ( empty( $column['fields'] ) || ! is_array( $column['fields'] ) ) {
					continue;
				}

				foreach ( $column['fields'] as $field_index => $field ) {
					$html_id = (string) ( $field['htmlId'] ?? '' );

					if ( '' === $html_id ) {
						continue;
					}

					if ( isset( $used[ $html_id ] ) ) {
						$schema['rows'][ $row_index ]['columns'][ $column_index ]['fields'][ $field_index ]['htmlId'] = '';
						continue;
					}

					$used[ $html_id ] = true;
				}
			}
		}

		return $schema;
	}

	/**
	 * Sanityzuje wiersz.
	 *
	 * @param mixed $row Surowy wiersz.
	 * @return array<string, mixed>|null
	 */
	private static function sanitize_row( $row ): ?array {
		if ( ! is_array( $row ) ) {
			return null;
		}

		$id      = self::sanitize_id( $row['id'] ?? '' );
		$columns = array();

		if ( isset( $row['columns'] ) && is_array( $row['columns'] ) ) {
			foreach ( $row['columns'] as $column ) {
				$sanitized_column = self::sanitize_column( $column );
				if ( null !== $sanitized_column ) {
					$columns[] = $sanitized_column;
				}
			}
		}

		if ( empty( $columns ) ) {
			return null;
		}

		return array(
			'id'        => $id,
			'cssClass'  => sanitize_text_field( $row['cssClass'] ?? '' ),
			'htmlId'    => self::sanitize_html_id( (string) ( $row['htmlId'] ?? '' ) ),
			'columns'   => $columns,
		);
	}

	/**
	 * Sanityzuje kolumnę.
	 *
	 * @param mixed $column Surowa kolumna.
	 * @return array<string, mixed>|null
	 */
	private static function sanitize_column( $column ): ?array {
		if ( ! is_array( $column ) ) {
			return null;
		}

		$id     = self::sanitize_id( $column['id'] ?? '' );
		$width  = isset( $column['width'] ) ? absint( $column['width'] ) : 100;
		$width  = min( 100, max( 1, $width ) );
		$fields = array();

		if ( isset( $column['fields'] ) && is_array( $column['fields'] ) ) {
			foreach ( $column['fields'] as $field ) {
				$sanitized_field = self::sanitize_field( $field );
				if ( null !== $sanitized_field ) {
					$fields[] = $sanitized_field;
				}
			}
		}

		return array(
			'id'        => $id,
			'cssClass'  => sanitize_text_field( $column['cssClass'] ?? '' ),
			'htmlId'    => self::sanitize_html_id( (string) ( $column['htmlId'] ?? '' ) ),
			'width'     => $width,
			'fields'    => $fields,
		);
	}

	/**
	 * Sanityzuje pole formularza.
	 *
	 * @param mixed $field Surowe pole.
	 * @return array<string, mixed>|null
	 */
	private static function sanitize_field( $field ): ?array {
		if ( ! is_array( $field ) ) {
			return null;
		}

		$type = sanitize_key( $field['type'] ?? 'text' );

		if ( ! in_array( $type, self::FIELD_TYPES, true ) ) {
			return null;
		}

		$sanitized = array(
			'id'           => self::sanitize_id( $field['id'] ?? '' ),
			'type'         => $type,
			'name'         => self::sanitize_name( $field['name'] ?? '' ),
			'label'        => 'consent' === $type
				? ConsentHtml::sanitize( (string) ( $field['label'] ?? '' ) )
				: TextEncoding::sanitize_field_text( (string) ( $field['label'] ?? '' ) ),
			'required'     => ! empty( $field['required'] ),
			'placeholder'  => TextEncoding::sanitize_field_text( (string) ( $field['placeholder'] ?? '' ) ),
			'defaultValue' => TextEncoding::sanitize_field_text( (string) ( $field['defaultValue'] ?? '' ) ),
			'cssClass'     => sanitize_text_field( $field['cssClass'] ?? '' ),
			'htmlId'       => self::sanitize_html_id( (string) ( $field['htmlId'] ?? '' ) ),
			'hideLabel'    => 'consent' === $type ? ( ! isset( $field['hideLabel'] ) || ! empty( $field['hideLabel'] ) ) : ! empty( $field['hideLabel'] ),
			'consentText'  => self::sanitize_consent_text( (string) ( $field['consentText'] ?? ( 'consent' === $type ? ( $field['label'] ?? '' ) : '' ) ) ),
			'contentText'  => self::sanitize_consent_text( (string) ( $field['contentText'] ?? ( 'content' === $type ? ( $field['label'] ?? '' ) : '' ) ) ),
			'minLength'    => isset( $field['minLength'] ) && '' !== $field['minLength'] ? absint( $field['minLength'] ) : '',
			'maxLength'    => isset( $field['maxLength'] ) && '' !== $field['maxLength'] ? absint( $field['maxLength'] ) : '',
			'rows'         => isset( $field['rows'] ) ? absint( $field['rows'] ) : 4,
			'min'          => isset( $field['min'] ) && '' !== $field['min'] ? floatval( $field['min'] ) : '',
			'max'          => isset( $field['max'] ) && '' !== $field['max'] ? floatval( $field['max'] ) : '',
			'step'         => isset( $field['step'] ) && '' !== $field['step'] ? sanitize_text_field( (string) $field['step'] ) : '',
			'options'      => self::sanitize_options( $field['options'] ?? array() ),
			'allowedTypes' => sanitize_text_field( $field['allowedTypes'] ?? '' ),
			'maxFileSize'  => isset( $field['maxFileSize'] ) && '' !== $field['maxFileSize'] ? absint( $field['maxFileSize'] ) : '',
			'submitText'   => TextEncoding::sanitize_field_text( (string) ( $field['submitText'] ?? BuilderI18n::default_submit_text() ) ),
			'loadingText'  => TextEncoding::sanitize_field_text( (string) ( $field['loadingText'] ?? BuilderI18n::default_loading_text() ) ),
			'allowMultiple' => ! empty( $field['allowMultiple'] ),
			'minSelections' => isset( $field['minSelections'] ) && '' !== $field['minSelections'] ? absint( $field['minSelections'] ) : '',
			'maxSelections' => isset( $field['maxSelections'] ) && '' !== $field['maxSelections'] ? absint( $field['maxSelections'] ) : '',
			'maxFiles'      => isset( $field['maxFiles'] ) && '' !== $field['maxFiles'] ? absint( $field['maxFiles'] ) : '',
			'minFiles'      => isset( $field['minFiles'] ) && '' !== $field['minFiles'] ? absint( $field['minFiles'] ) : '',
			'fileButtonText' => TextEncoding::sanitize_field_text( (string) ( $field['fileButtonText'] ?? '' ) ),
			'choiceLayout'   => self::sanitize_choice_layout( (string) ( $field['choiceLayout'] ?? 'vertical' ) ),
			'showUploadHint' => ! empty( $field['showUploadHint'] ),
		);

		if ( 'star_rating' === $type ) {
			$sanitized = self::apply_star_rating_bounds( $sanitized );
		}

		if ( 'radio' === $type ) {
			$sanitized = self::apply_choice_selection_bounds( $sanitized );
		}

		if ( 'file' === $type ) {
			$sanitized = self::apply_file_field_bounds( $sanitized );
		}

		if ( 'select' === $type || 'radio' === $type ) {
			$sanitized = self::apply_option_defaults( $sanitized );
		}

		if ( 'content' === $type ) {
			$sanitized['name']     = '';
			$sanitized['required'] = false;
			if ( ! isset( $field['hideLabel'] ) ) {
				$sanitized['hideLabel'] = true;
			}
		}

		if ( 'submit' === $type ) {
			$sanitized['liveValidation'] = ! empty( $field['liveValidation'] );
		}

		return $sanitized;
	}

	/**
	 * Ustawia domyślne opcje (selected) i synchronizuje defaultValue.
	 *
	 * @param array<string, mixed> $field Pole.
	 * @return array<string, mixed>
	 */
	private static function apply_option_defaults( array $field ): array {
		$options = $field['options'] ?? array();

		if ( empty( $options ) ) {
			$field['defaultValue'] = '';

			return $field;
		}

		$default_raw = (string) ( $field['defaultValue'] ?? '' );
		$has_selected = false;

		foreach ( $options as $option ) {
			if ( ! empty( $option['selected'] ) ) {
				$has_selected = true;
				break;
			}
		}

		if ( ! $has_selected && '' !== $default_raw ) {
			$defaults = ! empty( $field['allowMultiple'] )
				? array_map( 'trim', explode( ',', $default_raw ) )
				: array( $default_raw );

			foreach ( $options as $index => $option ) {
				$value = (string) ( $option['value'] ?? '' );
				$options[ $index ]['selected'] = in_array( $value, $defaults, true );
			}
		}

		$selected_values = array();
		$single_set      = false;

		foreach ( $options as $index => $option ) {
			if ( empty( $option['selected'] ) ) {
				$options[ $index ]['selected'] = false;
				continue;
			}

			if ( empty( $field['allowMultiple'] ) ) {
				if ( $single_set ) {
					$options[ $index ]['selected'] = false;
					continue;
				}

				$single_set = true;
			}

			$value = (string) ( $option['value'] ?? '' );

			if ( '' !== $value ) {
				$selected_values[] = $value;
			}
		}

		$field['options'] = $options;

		if ( ! empty( $field['allowMultiple'] ) ) {
			$field['defaultValue'] = implode( ',', $selected_values );
		} else {
			$field['defaultValue'] = $selected_values[0] ?? '';
		}

		return $field;
	}

	/**
	 * Normalizuje limity wielokrotnego wyboru.
	 *
	 * @param array<string, mixed> $field Pole.
	 * @return array<string, mixed>
	 */
	private static function apply_choice_selection_bounds( array $field ): array {
		if ( empty( $field['allowMultiple'] ) ) {
			$field['minSelections'] = '';
			$field['maxSelections'] = '';

			return $field;
		}

		$option_count = count( $field['options'] ?? array() );
		$min          = '' !== $field['minSelections'] ? (int) $field['minSelections'] : ( ! empty( $field['required'] ) ? 1 : 0 );
		$max          = '' !== $field['maxSelections'] ? (int) $field['maxSelections'] : 0;

		if ( $option_count > 0 ) {
			$min = min( $option_count, max( 0, $min ) );

			if ( $max > 0 ) {
				$max = min( $option_count, max( $min, $max ) );
			}
		} else {
			$min = max( 0, $min );
		}

		$field['minSelections'] = $min > 0 ? $min : ( ! empty( $field['required'] ) ? 1 : '' );
		$field['maxSelections'] = $max > 0 ? $max : '';

		return $field;
	}

	/**
	 * Ustawia domyślne min/max (1–5) dla oceny gwiazdkowej.
	 *
	 * @param array<string, mixed> $field Pole.
	 * @return array<string, mixed>
	 */
	private static function apply_star_rating_bounds( array $field ): array {
		$min = isset( $field['min'] ) && '' !== $field['min'] ? absint( $field['min'] ) : 1;
		$max = isset( $field['max'] ) && '' !== $field['max'] ? absint( $field['max'] ) : 5;

		$min = max( 1, min( 20, $min ) );
		$max = max( $min, min( 20, $max ) );

		$field['min'] = $min;
		$field['max'] = $max;

		if ( '' !== ( $field['defaultValue'] ?? '' ) ) {
			$default = absint( (string) $field['defaultValue'] );
			$field['defaultValue'] = (string) max( $min, min( $max, $default ) );
		}

		return $field;
	}

	/**
	 * Normalizuje limity wielu plików.
	 *
	 * @param array<string, mixed> $field Pole.
	 * @return array<string, mixed>
	 */
	private static function apply_file_field_bounds( array $field ): array {
		if ( empty( $field['allowMultiple'] ) ) {
			$field['maxFiles'] = '';
			$field['minFiles'] = '';

			return $field;
		}

		$max = '' !== ( $field['maxFiles'] ?? '' ) ? (int) $field['maxFiles'] : 0;
		$min = '' !== ( $field['minFiles'] ?? '' ) ? (int) $field['minFiles'] : ( ! empty( $field['required'] ) ? 1 : 0 );

		if ( $max > 0 ) {
			$max = max( 2, min( 50, $max ) );
		} else {
			$max = 0;
		}

		if ( $min > 0 ) {
			$min = max( 1, min( 50, $min ) );

			if ( $max > 0 ) {
				$min = min( $max, $min );
			}
		} else {
			$min = 0;
		}

		$field['minFiles'] = $min > 0 ? $min : ( ! empty( $field['required'] ) ? 1 : '' );
		$field['maxFiles'] = $max > 0 ? $max : '';

		return $field;
	}

	/**
	 * Sanityzuje treść zgody (dozwolony prosty HTML).
	 */
	private static function sanitize_consent_text( string $text ): string {
		return ConsentHtml::sanitize( $text );
	}

	/**
	 * Sanityzuje identyfikator elementu.
	 *
	 * @param string $id Identyfikator.
	 */
	private static function sanitize_id( string $id ): string {
		$id = sanitize_key( $id );

		if ( '' === $id ) {
			$id = 'ff_' . wp_generate_password( 8, false, false );
		}

		return $id;
	}

	/**
	 * Sanityzuje atrybut HTML id pola (wrapper).
	 *
	 * @param string $id Identyfikator HTML.
	 */
	private static function sanitize_html_id( string $id ): string {
		$id = preg_replace( '/[^a-zA-Z0-9_-]/', '', trim( $id ) ) ?? '';

		if ( '' === $id ) {
			return '';
		}

		if ( preg_match( '/^[0-9]/', $id ) ) {
			$id = 'field-' . $id;
		}

		return $id;
	}

	/**
	 * Sanityzuje atrybut name pola.
	 *
	 * @param string $name Nazwa pola.
	 */
	private static function sanitize_name( string $name ): string {
		$name = sanitize_key( $name );

		return $name;
	}

	/**
	 * Sanityzuje układ opcji (pionowy / poziomy).
	 *
	 * @param string $layout Układ.
	 */
	private static function sanitize_choice_layout( string $layout ): string {
		return 'horizontal' === sanitize_key( $layout ) ? 'horizontal' : 'vertical';
	}

	/**
	 * Sanityzuje opcje selecta.
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

			$label = TextEncoding::sanitize_field_text( (string) ( $option['label'] ?? '' ) );
			$value = TextEncoding::sanitize_field_text( (string) ( $option['value'] ?? '' ) );

			if ( '' === $label && '' === $value ) {
				continue;
			}

			$sanitized[] = array(
				'label'    => $label,
				'value'    => $value,
				'selected' => ! empty( $option['selected'] ),
			);
		}

		return $sanitized;
	}
}
