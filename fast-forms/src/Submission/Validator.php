<?php
/**
 * Form submission validator.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Submission;

use Weblo\FastForms\Support\UploadedFiles;

/**
 * Waliduje dane wysyłki na podstawie schemy formularza.
 */
final class Validator {

	/** @var array<string, mixed> */
	private array $schema;

	/** @var array<string, mixed> */
	private array $messages;

	/**
	 * @param array<string, mixed> $schema   Schemа formularza.
	 * @param array<string, mixed> $messages Komunikaty walidacji.
	 */
	public function __construct( array $schema, array $messages ) {
		$this->schema   = $schema;
		$this->messages = $messages;
	}

	/**
	 * Waliduje przesłane dane.
	 *
	 * @param array<string, mixed> $post  Dane $_POST.
	 * @param array<string, mixed> $files Dane $_FILES.
	 * @return array{valid: bool, errors: array<string, string>, payload: array<string, mixed>, files: array<string, array<string, mixed>>}
	 */
	public function validate( array $post, array $files ): array {
		$errors  = array();
		$payload = array();
		$uploads = array();

		foreach ( SchemaFields::flatten( $this->schema ) as $field ) {
			$key  = SchemaFields::field_key( $field );
			$type = $field['type'] ?? 'text';

			if ( '' === $key ) {
				continue;
			}

			if ( 'file' === $type ) {
				$file_items = UploadedFiles::normalize_uploads( $files, $key );
				$error      = $this->validate_files( $field, $file_items );

				if ( null !== $error ) {
					$errors[ $key ] = $error;
					continue;
				}

				if ( ! empty( $file_items ) ) {
					if ( 1 === count( $file_items ) ) {
						$uploads[ $key ] = $file_items[0];
					} else {
						$uploads[ $key ] = $file_items;
					}

					$names = array_map(
						static function ( array $item ): string {
							return (string) ( $item['name'] ?? '' );
						},
						$file_items
					);

					$payload[ $key ] = 1 === count( $names ) ? $names[0] : $names;
				} else {
					$payload[ $key ] = '';
				}

				continue;
			}

			$value = $this->get_posted_value( $post, $key, $field );
			$error = $this->validate_value( $field, $value );

			if ( null !== $error ) {
				$errors[ $key ] = $error;
				continue;
			}

			$payload[ $key ] = $this->sanitize_value( $field, $value );
		}

		return array(
			'valid'   => empty( $errors ),
			'errors'  => $errors,
			'payload' => $payload,
			'files'   => $uploads,
		);
	}

	/**
	 * @param array<string, mixed> $post  Dane POST.
	 * @param string               $key  Klucz pola.
	 * @param array<string, mixed> $field Definicja pola.
	 * @return string|array<int, string>
	 */
	private function get_posted_value( array $post, string $key, array $field ) {
		$type = $field['type'] ?? 'text';

		if ( in_array( $type, array( 'checkbox', 'consent' ), true ) ) {
			return isset( $post[ $key ] ) ? '1' : '';
		}

		if ( 'radio' === $type && ! empty( $field['allowMultiple'] ) ) {
			$raw = $post[ $key ] ?? array();

			if ( ! is_array( $raw ) ) {
				return '' !== (string) $raw ? array( (string) wp_unslash( $raw ) ) : array();
			}

			$values = array();

			foreach ( $raw as $item ) {
				$item = trim( (string) wp_unslash( $item ) );

				if ( '' !== $item ) {
					$values[] = $item;
				}
			}

			return array_values( array_unique( $values ) );
		}

		return isset( $post[ $key ] ) ? (string) wp_unslash( $post[ $key ] ) : '';
	}

	/**
	 * @param array<string, mixed> $field Definicja pola.
	 * @param string|array<int, string> $value Wartość.
	 */
	private function validate_value( array $field, $value ): ?string {
		$type = $field['type'] ?? 'text';

		if ( 'radio' === $type && ! empty( $field['allowMultiple'] ) ) {
			return $this->validate_multiple_choice( $field, is_array( $value ) ? $value : array() );
		}

		$required = ! empty( $field['required'] );
		$trimmed  = trim( (string) $value );

		if ( $required && '' === $trimmed ) {
			return $this->messages['required'] ?? __( 'This field is required.', 'fast-forms' );
		}

		if ( '' === $trimmed ) {
			return null;
		}

		$type = $field['type'] ?? 'text';

		if ( 'email' === $type && ! is_email( $trimmed ) ) {
			return $this->messages['invalidEmail'] ?? __( 'Please enter a valid email address.', 'fast-forms' );
		}

		if ( 'url' === $type && ! filter_var( $trimmed, FILTER_VALIDATE_URL ) ) {
			return $this->messages['invalidEmail'] ?? __( 'Please enter a valid URL.', 'fast-forms' );
		}

		$min_length = $field['minLength'] ?? '';
		if ( '' !== $min_length && mb_strlen( $trimmed ) < (int) $min_length ) {
			return $this->messages['tooShort'] ?? __( 'The value is too short.', 'fast-forms' );
		}

		$max_length = $field['maxLength'] ?? '';
		if ( '' !== $max_length && mb_strlen( $trimmed ) > (int) $max_length ) {
			return $this->messages['tooLong'] ?? __( 'The value is too long.', 'fast-forms' );
		}

		if ( in_array( $type, array( 'number', 'range', 'star_rating' ), true ) ) {
			if ( ! is_numeric( $trimmed ) ) {
				return $this->messages['submitError'] ?? __( 'Please enter a valid number.', 'fast-forms' );
			}

			$numeric = (float) $trimmed;
			$min     = $field['min'] ?? '';
			$max     = $field['max'] ?? '';

			if ( 'star_rating' === $type ) {
				$min = '' !== $min ? (int) $min : 1;
				$max = '' !== $max ? (int) $max : 5;

				if ( ! preg_match( '/^\d+$/', $trimmed ) ) {
					return $this->messages['submitError'] ?? __( 'Please enter a valid rating.', 'fast-forms' );
				}

				$numeric = (int) $trimmed;
			}

			if ( '' !== $min && $numeric < (float) $min ) {
				return $this->messages['tooShort'] ?? __( 'The value is too small.', 'fast-forms' );
			}

			if ( '' !== $max && $numeric > (float) $max ) {
				return $this->messages['tooLong'] ?? __( 'The value is too large.', 'fast-forms' );
			}
		}

		if ( in_array( $type, array( 'select', 'radio' ), true ) ) {
			$allowed = $this->get_choice_option_values( $field );

			if ( empty( $allowed ) ) {
				return $this->messages['submitError'] ?? __( 'An invalid option was selected.', 'fast-forms' );
			}

			if ( ! in_array( $trimmed, $allowed, true ) ) {
				return $this->messages['submitError'] ?? __( 'An invalid option was selected.', 'fast-forms' );
			}
		}

		return null;
	}

	/**
	 * Waliduje wielokrotny wybór (checkboxy).
	 *
	 * @param array<string, mixed>      $field  Definicja pola.
	 * @param array<int, string>      $values Wybrane wartości.
	 */
	private function validate_multiple_choice( array $field, array $values ): ?string {
		$required = ! empty( $field['required'] );
		$count    = count( $values );
		$min      = $field['minSelections'] ?? '';
		$max      = $field['maxSelections'] ?? '';

		if ( '' === $min && $required ) {
			$min = 1;
		}

		if ( $required && 0 === $count ) {
			return $this->messages['required'] ?? __( 'This field is required.', 'fast-forms' );
		}

		if ( '' !== $min && $count < (int) $min ) {
			return sprintf(
				/* translators: %d: minimum number of selections */
				__( 'Select at least %d option(s).', 'fast-forms' ),
				(int) $min
			);
		}

		if ( '' !== $max && $count > (int) $max ) {
			return sprintf(
				/* translators: %d: maximum number of selections */
				__( 'Select at most %d option(s).', 'fast-forms' ),
				(int) $max
			);
		}

		$allowed = $this->get_choice_option_values( $field );

		foreach ( $values as $value ) {
			if ( ! in_array( $value, $allowed, true ) ) {
				return $this->messages['submitError'] ?? __( 'An invalid option was selected.', 'fast-forms' );
			}
		}

		return null;
	}

	/**
	 * @param array<string, mixed> $field Definicja pola.
	 * @return array<int, string>
	 */
	private function get_choice_option_values( array $field ): array {
		$allowed = array();

		foreach ( $field['options'] ?? array() as $option ) {
			if ( ! is_array( $option ) ) {
				continue;
			}

			$val = trim( (string) ( $option['value'] ?? '' ) );
			$lab = trim( (string) ( $option['label'] ?? '' ) );

			if ( '' === $val && '' === $lab ) {
				continue;
			}

			if ( '' === $val ) {
				$val = sanitize_title( $lab );
			}

			$allowed[] = $val;
		}

		return array_values( array_unique( $allowed ) );
	}

	/**
	 * @param array<string, mixed>      $field Definicja pola.
	 * @param string|array<int, string> $value Wartość.
	 * @return string|array<int, string>
	 */
	private function sanitize_value( array $field, $value ) {
		$type = $field['type'] ?? 'text';

		if ( 'radio' === $type && ! empty( $field['allowMultiple'] ) && is_array( $value ) ) {
			return array_values(
				array_map(
					static function ( string $item ): string {
						return sanitize_text_field( $item );
					},
					$value
				)
			);
		}

		$string = (string) $value;

		switch ( $type ) {
			case 'email':
				return sanitize_email( $string );
			case 'url':
				return esc_url_raw( $string );
			case 'star_rating':
				return (string) absint( $string );
			case 'textarea':
			case 'consent':
				return sanitize_textarea_field( $string );
			default:
				return sanitize_text_field( $string );
		}
	}

	/**
	 * @param array<string, mixed>           $field Definicja pola.
	 * @param array<int, array<string, mixed>> $files Lista plików.
	 */
	private function validate_files( array $field, array $files ): ?string {
		$required = ! empty( $field['required'] );
		$count    = count( $files );

		if ( 0 === $count ) {
			return $required ? ( $this->messages['required'] ?? __( 'This field is required.', 'fast-forms' ) ) : null;
		}

		$allow_multiple = ! empty( $field['allowMultiple'] );

		if ( ! $allow_multiple && $count > 1 ) {
			return $this->messages['submitError'] ?? __( 'Only one file can be uploaded.', 'fast-forms' );
		}

		$max_files = $field['maxFiles'] ?? '';
		$min_files = $field['minFiles'] ?? '';

		if ( '' === $min_files && $required && $allow_multiple ) {
			$min_files = 1;
		}

		if ( $allow_multiple && '' !== $min_files && $count < (int) $min_files ) {
			return sprintf(
				/* translators: %d: minimum number of files */
				__( 'Upload at least %d file(s).', 'fast-forms' ),
				(int) $min_files
			);
		}

		if ( $allow_multiple && '' !== $max_files && $count > (int) $max_files ) {
			return sprintf(
				/* translators: %d: maximum number of files */
				__( 'You can upload at most %d file(s).', 'fast-forms' ),
				(int) $max_files
			);
		}

		foreach ( $files as $file ) {
			$error = $this->validate_file( $field, $file );

			if ( null !== $error ) {
				return $error;
			}
		}

		return null;
	}

	/**
	 * @param array<string, mixed>      $field Definicja pola.
	 * @param array<string, mixed>|null $file  Plik.
	 */
	private function validate_file( array $field, ?array $file ): ?string {
		if ( null === $file ) {
			return null;
		}

		if ( UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
			return $this->messages['submitError'] ?? __( 'An error occurred while uploading the file.', 'fast-forms' );
		}

		$max_kb = (int) ( $field['maxFileSize'] ?? 0 );
		if ( \Weblo\FastForms\Support\UploadLimits::exceeds_limit( (int) ( $file['size'] ?? 0 ), $max_kb ) ) {
			return $this->messages['fileTooLarge'] ?? __( 'The file is too large.', 'fast-forms' );
		}

		$allowed     = trim( (string) ( $field['allowedTypes'] ?? '' ) );
		$allowed_ext = array();

		if ( '' !== $allowed ) {
			$allowed_ext = array_map(
				static function ( string $item ): string {
					return ltrim( trim( $item ), '.' );
				},
				explode( ',', strtolower( $allowed ) )
			);
		}

		if ( ! empty( $allowed_ext ) ) {
			$ext = strtolower( pathinfo( $file['name'] ?? '', PATHINFO_EXTENSION ) );

			if ( ! in_array( $ext, $allowed_ext, true ) ) {
				return $this->messages['invalidFile'] ?? __( 'This file type is not allowed.', 'fast-forms' );
			}
		}

		$checked = wp_check_filetype_and_ext(
			$file['tmp_name'] ?? '',
			$file['name'] ?? ''
		);

		if ( empty( $checked['ext'] ) || empty( $checked['type'] ) ) {
			return $this->messages['invalidFile'] ?? __( 'This file type is not allowed.', 'fast-forms' );
		}

		if ( ! empty( $allowed_ext ) && ! in_array( strtolower( (string) $checked['ext'] ), $allowed_ext, true ) ) {
			return $this->messages['invalidFile'] ?? __( 'This file type is not allowed.', 'fast-forms' );
		}

		return null;
	}
}
