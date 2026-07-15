<?php
/**
 * Email merge tag parser.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Submission;

use Weblo\FastForms\Admin\EntryFileDownload;
use Weblo\FastForms\PostTypes\EntryPostType;

/**
 * Podmienia merge tagi w szablonach e-mail.
 */
final class MergeTagParser {

	/**
	 * @param string               $template Treść lub temat.
	 * @param int                  $form_id  ID formularza.
	 * @param int                  $entry_id ID zgłoszenia.
	 * @param array<string, mixed> $payload  Odpowiedzi pól.
	 * @param array<string, mixed> $schema   Schemа formularza.
	 * @param string               $context  `html` (treść) lub `subject` (temat — bez HTML i znaków nowej linii).
	 */
	public static function parse( string $template, int $form_id, int $entry_id, array $payload, array $schema, string $context = 'html' ): string {
		$is_subject = 'subject' === $context;
		$form       = get_post( $form_id );
		$tags       = array(
			'{form:title}' => $is_subject ? self::sanitize_subject( $form instanceof \WP_Post ? $form->post_title : '' ) : ( $form instanceof \WP_Post ? $form->post_title : '' ),
			'{form:id}'    => (string) $form_id,
			'{entry:id}'   => (string) $entry_id,
			'{entry:date}' => $entry_id > 0
				? (string) get_post_meta( $entry_id, EntryPostType::META_SUBMITTED_AT, true )
				: current_time( 'mysql' ),
		);

		foreach ( SchemaFields::flatten( $schema ) as $field ) {
			$key = SchemaFields::field_key( $field );

			if ( '' === $key ) {
				continue;
			}

			$tags[ '{field:' . $key . '}' ] = self::format_field_value( $field, $payload[ $key ] ?? '', $entry_id, $key, $context );
		}

		$tags['{all_fields}'] = $is_subject
			? self::render_all_fields_plain( $schema, $payload, $entry_id )
			: self::render_all_fields( $schema, $payload, $entry_id );

		$result = str_replace( array_keys( $tags ), array_values( $tags ), $template );

		return $is_subject ? self::sanitize_subject( $result ) : $result;
	}

	/**
	 * Usuwa znaki sterujące i HTML — bezpieczne dla tematu e-maila (ochrona przed header injection).
	 */
	public static function sanitize_subject( string $value ): string {
		$value = wp_strip_all_tags( $value );
		$value = preg_replace( '/[\r\n\t]+/', ' ', $value ) ?? '';

		return trim( preg_replace( '/\s{2,}/', ' ', $value ) );
	}

	/**
	 * @param array<string, mixed> $schema   Schemа.
	 * @param array<string, mixed> $payload  Odpowiedzi.
	 * @param int                  $entry_id ID zgłoszenia.
	 */
	private static function render_all_fields( array $schema, array $payload, int $entry_id ): string {
		$rows = array();

		foreach ( SchemaFields::flatten( $schema ) as $field ) {
			$key   = SchemaFields::field_key( $field );
			$label = (string) ( $field['label'] ?? $key );

			if ( '' === $key ) {
				continue;
			}

			$value   = self::format_field_value( $field, $payload[ $key ] ?? '', $entry_id, $key );
			$rows[]  = '<tr><th style="text-align:left;padding:4px 12px 4px 0;vertical-align:top;">' . esc_html( $label ) . '</th><td style="padding:4px 0;">' . $value . '</td></tr>';
		}

		if ( empty( $rows ) ) {
			return '';
		}

		return '<table cellspacing="0" cellpadding="0" border="0">' . implode( '', $rows ) . '</table>';
	}

	/**
	 * @param array<string, mixed> $schema   Schemа.
	 * @param array<string, mixed> $payload  Odpowiedzi.
	 * @param int                  $entry_id ID zgłoszenia.
	 */
	private static function render_all_fields_plain( array $schema, array $payload, int $entry_id ): string {
		$lines = array();

		foreach ( SchemaFields::flatten( $schema ) as $field ) {
			$key   = SchemaFields::field_key( $field );
			$label = (string) ( $field['label'] ?? $key );

			if ( '' === $key ) {
				continue;
			}

			$value = self::format_field_value( $field, $payload[ $key ] ?? '', $entry_id, $key, 'subject' );

			if ( '' === $value ) {
				continue;
			}

			$lines[] = $label . ': ' . $value;
		}

		return implode( '; ', $lines );
	}

	/**
	 * @param array<string, mixed> $field    Definicja pola.
	 * @param mixed                $value    Wartość.
	 * @param int                  $entry_id ID zgłoszenia.
	 * @param string               $key      Klucz pola.
	 * @param string               $context  `html` lub `subject`.
	 */
	private static function format_field_value( array $field, $value, int $entry_id, string $key, string $context = 'html' ): string {
		$is_subject = 'subject' === $context;
		$type       = $field['type'] ?? 'text';

		if ( 'file' === $type ) {
			$files = EntryFileDownload::get_file_records( $entry_id, $key );

			if ( empty( $files ) ) {
				return '';
			}

			$parts = array();

			foreach ( $files as $index => $file ) {
				$name = EntryFileDownload::get_display_name( $file );

				if ( $is_subject ) {
					$parts[] = self::sanitize_subject( $name );
					continue;
				}

				$url     = EntryFileDownload::get_admin_url( $entry_id, $key, $index );
				$parts[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
			}

			return $is_subject ? implode( ', ', $parts ) : implode( '<br />', $parts );
		}

		if ( is_array( $value ) ) {
			if ( 'radio' === $type ) {
				$text = self::format_choice_labels( $field, $value );

				return $is_subject ? self::sanitize_subject( $text ) : esc_html( $text );
			}

			$json = wp_json_encode( $value );

			return $is_subject ? self::sanitize_subject( (string) $json ) : esc_html( (string) $json );
		}

		$string = (string) $value;

		if ( in_array( $type, array( 'checkbox', 'consent' ), true ) ) {
			$text = $string ? __( 'Yes', 'fast-forms' ) : __( 'No', 'fast-forms' );

			return $is_subject ? self::sanitize_subject( $text ) : esc_html( $text );
		}

		if ( $is_subject ) {
			return self::sanitize_subject( $string );
		}

		if ( 'email' === $type && is_email( $string ) ) {
			return '<a href="mailto:' . esc_attr( $string ) . '">' . esc_html( $string ) . '</a>';
		}

		if ( 'url' === $type && filter_var( $string, FILTER_VALIDATE_URL ) ) {
			return '<a href="' . esc_url( $string ) . '">' . esc_html( $string ) . '</a>';
		}

		if ( 'textarea' === $type ) {
			return nl2br( esc_html( $string ) );
		}

		return esc_html( $string );
	}

	/**
	 * Zamienia wartości wyboru na etykiety opcji.
	 *
	 * @param array<string, mixed> $field  Definicja pola.
	 * @param array<int, string>   $values Wybrane wartości.
	 */
	private static function format_choice_labels( array $field, array $values ): string {
		$labels = array();

		foreach ( $values as $value ) {
			$label = (string) $value;

			foreach ( $field['options'] ?? array() as $option ) {
				if ( ! is_array( $option ) ) {
					continue;
				}

				$option_value = (string) ( $option['value'] ?? '' );
				$option_label = (string) ( $option['label'] ?? $option_value );

				if ( '' === $option_value ) {
					$option_value = sanitize_title( $option_label );
				}

				if ( $option_value === (string) $value ) {
					$label = $option_label;
					break;
				}
			}

			$labels[] = $label;
		}

		return implode( ', ', $labels );
	}
}
