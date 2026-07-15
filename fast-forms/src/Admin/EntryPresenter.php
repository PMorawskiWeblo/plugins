<?php
/**
 * Entry data presenter for admin.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Admin;

use Weblo\FastForms\FormBuilder\Schema;
use Weblo\FastForms\FormBuilder\SchemaMetaJson;
use Weblo\FastForms\Admin\EntryFileDownload;
use Weblo\FastForms\PostTypes\EntryPostType;
use Weblo\FastForms\Submission\SchemaFields;
use Weblo\FastForms\Support\TextEncoding;

/**
 * Formatuje dane zgłoszenia do wyświetlenia w kokpicie.
 */
final class EntryPresenter {

	/**
	 * Zwraca sformatowane wiersze zgłoszenia.
	 *
	 * @param int $entry_id ID zgłoszenia.
	 * @return array<int, array{label: string, value: string, type: string}>
	 */
	public static function get_rows( int $entry_id ): array {
		$payload_raw  = get_post_meta( $entry_id, EntryPostType::META_PAYLOAD, true );
		$payload      = SchemaMetaJson::decode( $payload_raw );
		$snapshot_raw = get_post_meta( $entry_id, EntryPostType::META_SCHEMA_SNAPSHOT, true );
		$schema       = Schema::sanitize( SchemaMetaJson::decode( $snapshot_raw ) );

		if ( ! is_array( $payload ) ) {
			$payload = array();
		}

		$rows    = array();
		$handled = array();

		foreach ( SchemaFields::flatten( $schema ) as $field ) {
			$key   = SchemaFields::field_key( $field );
			$type  = $field['type'] ?? 'text';
			$label = $field['label'] ?? $key;

			if ( '' === $key ) {
				continue;
			}

			$handled[ $key ] = true;
			$value           = $payload[ $key ] ?? '';

			$rows[] = array(
				'label' => (string) $label,
				'value' => self::format_value( $type, $value, $entry_id, $key ),
				'type'  => $type,
			);
		}

		foreach ( $payload as $key => $value ) {
			if ( isset( $handled[ $key ] ) ) {
				continue;
			}

			$rows[] = array(
				'label' => (string) $key,
				'value' => self::format_value( 'text', $value, $entry_id, (string) $key ),
				'type'  => 'text',
			);
		}

		return $rows;
	}

	/**
	 * @param string $type     Typ pola.
	 * @param mixed  $value    Wartość.
	 * @param int    $entry_id ID zgłoszenia.
	 * @param string $key      Klucz pola.
	 */
	private static function format_value( string $type, $value, int $entry_id, string $key ): string {
		if ( 'file' === $type ) {
			$files = EntryFileDownload::get_file_records( $entry_id, $key );

			if ( empty( $files ) ) {
				return '';
			}

			$is_image = static function ( array $file ): bool {
				$mime = (string) ( $file['type'] ?? $file['mime_type'] ?? '' );

				if ( '' !== $mime && str_starts_with( $mime, 'image/' ) ) {
					return true;
				}

				$name = EntryFileDownload::get_display_name( $file );
				$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );

				return in_array( $ext, array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg' ), true );
			};

			if ( 1 === count( $files ) ) {
				$name        = EntryFileDownload::get_display_name( $files[0] );
				$url         = EntryFileDownload::get_admin_url( $entry_id, $key );
				$file        = $files[0];
				$preview_url = EntryFileDownload::get_admin_url( $entry_id, $key, 0, true );

				if ( $is_image( $file ) ) {
					return sprintf(
						'<div class="ff-entry-file"><a href="%1$s">%2$s</a><br><img src="%3$s" alt="%2$s" class="ff-entry-file__preview" height="80"></div>',
						esc_url( $url ),
						esc_html( $name ),
						esc_url( $preview_url )
					);
				}

				return sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $name ) );
			}

			$links = array();

			foreach ( $files as $index => $file ) {
				$name        = EntryFileDownload::get_display_name( $file );
				$url         = EntryFileDownload::get_admin_url( $entry_id, $key, $index );
				$preview_url = EntryFileDownload::get_admin_url( $entry_id, $key, $index, true );

				if ( $is_image( $file ) ) {
					$links[] = sprintf(
						'<div class="ff-entry-file"><a href="%1$s">%2$s</a><br><img src="%3$s" alt="%2$s" class="ff-entry-file__preview" height="80"></div>',
						esc_url( $url ),
						esc_html( $name ),
						esc_url( $preview_url )
					);
				} else {
					$links[] = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $name ) );
				}
			}

			return '<ul class="ff-entry-files"><li>' . implode( '</li><li>', $links ) . '</li></ul>';
		}

		if ( is_array( $value ) ) {
			return esc_html( wp_json_encode( $value ) );
		}

		$string = TextEncoding::repair_unicode_escapes( (string) $value );

		if ( 'consent' === $type || 'checkbox' === $type ) {
			return $string ? esc_html__( 'Yes', 'fast-forms' ) : esc_html__( 'No', 'fast-forms' );
		}

		if ( 'email' === $type && is_email( $string ) ) {
			return sprintf( '<a href="mailto:%1$s">%1$s</a>', esc_attr( $string ) );
		}

		if ( 'url' === $type && filter_var( $string, FILTER_VALIDATE_URL ) ) {
			return sprintf( '<a href="%1$s" target="_blank" rel="noopener noreferrer">%1$s</a>', esc_url( $string ) );
		}

		if ( 'textarea' === $type ) {
			return nl2br( esc_html( $string ) );
		}

		return esc_html( $string );
	}

	/**
	 * Zwraca metadane zgłoszenia do panelu bocznego.
	 *
	 * @param int $entry_id ID zgłoszenia.
	 * @return array<string, string>
	 */
	public static function get_meta_summary( int $entry_id ): array {
		$form_id = (int) get_post_meta( $entry_id, EntryPostType::META_FORM_ID, true );
		$form    = $form_id ? get_post( $form_id ) : null;

		$meta = array(
			__( 'Form', 'fast-forms' )     => $form instanceof \WP_Post ? $form->post_title : '—',
			__( 'Form ID', 'fast-forms' ) => $form_id ? (string) $form_id : '—',
		);

		$name = (string) get_post_meta( $entry_id, EntryPostType::META_NAME, true );
		if ( '' !== $name ) {
			$meta[ __( 'Name', 'fast-forms' ) ] = $name;
		}

		$email = (string) get_post_meta( $entry_id, EntryPostType::META_EMAIL, true );
		if ( '' !== $email ) {
			$meta[ __( 'Email', 'fast-forms' ) ] = $email;
		}

		$phone = (string) get_post_meta( $entry_id, EntryPostType::META_PHONE, true );
		if ( '' !== $phone ) {
			$meta[ __( 'Phone', 'fast-forms' ) ] = $phone;
		}

		$meta[ __( 'Submitted at', 'fast-forms' ) ] = (string) get_post_meta( $entry_id, EntryPostType::META_SUBMITTED_AT, true );
		$meta[ __( 'Status', 'fast-forms' ) ]       = self::format_status( (string) get_post_meta( $entry_id, EntryPostType::META_STATUS, true ) );

		$recaptcha_score = self::format_recaptcha_score( $entry_id );
		if ( '—' !== $recaptcha_score ) {
			$meta[ __( 'reCAPTCHA score', 'fast-forms' ) ] = $recaptcha_score;
		}

		$meta[ __( 'Schema version', 'fast-forms' ) ] = (string) get_post_meta( $entry_id, EntryPostType::META_SCHEMA_VERSION, true );

		return $meta;
	}

	private static function format_status( string $status ): string {
		$map = array(
			'new'         => __( 'New', 'fast-forms' ),
			'read'        => __( 'Read', 'fast-forms' ),
			'archived'    => __( 'Archived', 'fast-forms' ),
		);

		return $map[ $status ] ?? $status;
	}

	private static function format_recaptcha_score( int $entry_id ): string {
		$score = get_post_meta( $entry_id, EntryPostType::META_RECAPTCHA_SCORE, true );

		return ( '' === $score || false === $score ) ? '—' : (string) $score;
	}
}