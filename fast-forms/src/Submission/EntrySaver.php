<?php
/**
 * Entry persistence.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Submission;

use Weblo\FastForms\FormBuilder\Schema;
use Weblo\FastForms\FormBuilder\SchemaMetaJson;
use Weblo\FastForms\PostTypes\EntryPostType;
use Weblo\FastForms\PostTypes\FormPostType;
use Weblo\FastForms\Support\FileAccept;
use Weblo\FastForms\Support\UploadLimits;
use Weblo\FastForms\Support\UploadPath;
use Weblo\FastForms\Support\UploadProtection;
use Weblo\FastForms\Support\UploadedFiles;

/**
 * Zapisuje zgłoszenie jako CPT wpf_entry.
 */
final class EntrySaver {

	/**
	 * Tworzy wpis zgłoszenia.
	 *
	 * @param int                       $form_id ID formularza.
	 * @param array<string, mixed>      $payload Odpowiedzi pól.
	 * @param array<string, array<string, mixed>> $raw_files Pliki z $_FILES.
	 * @param array<string, mixed>      $schema  Schemа w momencie wysyłki.
	 * @param float|null                $recaptcha_score Wynik reCAPTCHA.
	 * @return int|\WP_Error ID zgłoszenia lub błąd.
	 */
	public static function create( int $form_id, array $payload, array $raw_files, array $schema, ?float $recaptcha_score = null ) {
		$form = get_post( $form_id );

		if ( ! $form instanceof \WP_Post ) {
			return new \WP_Error( 'ff_invalid_form', __( 'The form does not exist.', 'fast-forms' ) );
		}

		$entry_id = wp_insert_post(
			array(
				'post_type'   => EntryPostType::POST_TYPE,
				'post_status' => EntryPostType::POST_STATUS,
				'post_title'  => sprintf(
					/* translators: %s: form title */
					__( 'Submission — %s', 'fast-forms' ),
					$form->post_title
				),
			),
			true
		);

		if ( is_wp_error( $entry_id ) ) {
			return $entry_id;
		}

		$uploaded_files = self::handle_uploads( $raw_files, $form_id, $entry_id, $schema );

		if ( is_wp_error( $uploaded_files ) ) {
			wp_delete_post( $entry_id, true );

			return $uploaded_files;
		}

		$key_fields     = self::extract_key_fields( $payload, $schema );
		$schema_version = (int) get_post_meta( $form_id, FormPostType::META_SCHEMA_VERSION, true );

		update_post_meta( $entry_id, EntryPostType::META_FORM_ID, $form_id );
		update_post_meta( $entry_id, EntryPostType::META_SCHEMA_VERSION, $schema_version );
		update_post_meta( $entry_id, EntryPostType::META_SCHEMA_SNAPSHOT, SchemaMetaJson::encode( Schema::sanitize( $schema ) ) ?? '' );
		update_post_meta( $entry_id, EntryPostType::META_PAYLOAD, SchemaMetaJson::encode( $payload ) ?? '' );
		update_post_meta( $entry_id, EntryPostType::META_NAME, $key_fields['name'] );
		update_post_meta( $entry_id, EntryPostType::META_EMAIL, $key_fields['email'] );
		update_post_meta( $entry_id, EntryPostType::META_PHONE, $key_fields['phone'] );
		update_post_meta( $entry_id, EntryPostType::META_SUBMITTED_AT, current_time( 'mysql' ) );
		update_post_meta( $entry_id, EntryPostType::META_STATUS, 'new' );
		update_post_meta( $entry_id, EntryPostType::META_UPLOADED_FILES, $uploaded_files );

		if ( null !== $recaptcha_score ) {
			update_post_meta( $entry_id, EntryPostType::META_RECAPTCHA_SCORE, $recaptcha_score );
		}

		wp_update_post(
			array(
				'ID'         => $entry_id,
				'post_title' => sprintf(
					/* translators: 1: entry ID, 2: form title */
					__( 'Submission #%1$d — %2$s', 'fast-forms' ),
					$entry_id,
					$form->post_title
				),
			)
		);

		return $entry_id;
	}

	/**
	 * @param array<string, array<string, mixed>> $raw_files Pliki.
	 * @param int                                 $form_id   ID formularza.
	 * @param int                                 $entry_id  ID zgłoszenia.
	 * @param array<string, mixed>                $schema    Schemа formularza.
	 * @return array<string, mixed>|\WP_Error
	 */
	private static function handle_uploads( array $raw_files, int $form_id, int $entry_id, array $schema ) {
		if ( empty( $raw_files ) ) {
			return array();
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$stored    = array();
		$file_map  = self::file_fields_map( $schema );
		$entry_subdir = UploadPath::get_entry_subdir( $form_id, $entry_id );

		foreach ( $raw_files as $field_key => $file_entry ) {
			$field = $file_map[ $field_key ] ?? null;
			$items = UploadedFiles::items_for_field( $raw_files, $field_key );

			if ( empty( $items ) ) {
				continue;
			}

			$uploaded_items = array();

			foreach ( $items as $item ) {
				if ( UPLOAD_ERR_OK !== (int) ( $item['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
					continue;
				}

				$field_max_kb  = is_array( $field ) ? (int) ( $field['maxFileSize'] ?? 0 ) : 0;
				$allowed_types = is_array( $field ) ? (string) ( $field['allowedTypes'] ?? '' ) : '';

				if ( UploadLimits::exceeds_limit( (int) ( $item['size'] ?? 0 ), $field_max_kb ) ) {
					return new \WP_Error(
						'ff_upload_too_large',
						__( 'The file is too large.', 'fast-forms' )
					);
				}

				$subdir_filter = static function ( array $dirs ) use ( $entry_subdir ): array {
					$subdir = '/' . $entry_subdir;

					$dirs['subdir'] = $subdir;
					$dirs['path']   = $dirs['basedir'] . $subdir;
					$dirs['url']    = $dirs['baseurl'] . $subdir;

					return $dirs;
				};

				add_filter( 'upload_dir', $subdir_filter );

				$upload = wp_handle_upload(
					$item,
					array(
						'test_form' => false,
						'mimes'     => FileAccept::mimes_for_allowed_types( $allowed_types ),
					)
				);

				remove_filter( 'upload_dir', $subdir_filter );

				if ( isset( $upload['error'] ) ) {
					return new \WP_Error(
						'ff_upload_failed',
						sprintf(
							/* translators: %s: error message */
							__( 'Could not save file: %s', 'fast-forms' ),
							$upload['error']
						)
					);
				}

				$file_path = (string) ( $upload['file'] ?? '' );

				if ( '' !== $file_path ) {
					UploadProtection::protect_directory( dirname( $file_path ) );
				}

				$uploaded_items[] = array(
					'file' => $file_path,
					'type' => $upload['type'] ?? '',
					'name' => $item['name'] ?? '',
				);
			}

			if ( ! empty( $uploaded_items ) ) {
				$stored[ $field_key ] = UploadedFiles::build_meta_record( $uploaded_items );
			}
		}

		return $stored;
	}

	/**
	 * @param array<string, mixed> $schema Schemа formularza.
	 * @return array<string, array<string, mixed>>
	 */
	private static function file_fields_map( array $schema ): array {
		$map = array();

		foreach ( SchemaFields::flatten( $schema ) as $field ) {
			if ( 'file' !== ( $field['type'] ?? '' ) ) {
				continue;
			}

			$key = SchemaFields::field_key( $field );

			if ( '' !== $key ) {
				$map[ $key ] = $field;
			}
		}

		return $map;
	}

	/**
	 * Wyciąga imię, e-mail i telefon z payloadu.
	 *
	 * @param array<string, mixed> $payload Odpowiedzi.
	 * @param array<string, mixed> $schema  Schemа.
	 * @return array{name: string, email: string, phone: string}
	 */
	private static function extract_key_fields( array $payload, array $schema ): array {
		$result = array(
			'name'  => '',
			'email' => '',
			'phone' => '',
		);

		foreach ( SchemaFields::flatten( $schema ) as $field ) {
			$key   = SchemaFields::field_key( $field );
			$type  = $field['type'] ?? 'text';
			$value = isset( $payload[ $key ] ) ? (string) $payload[ $key ] : '';

			if ( '' === $value ) {
				continue;
			}

			if ( 'email' === $type && '' === $result['email'] ) {
				$result['email'] = $value;
			}

			if ( 'tel' === $type && '' === $result['phone'] ) {
				$result['phone'] = $value;
			}

			if ( '' === $result['name'] && self::is_name_field( $field ) ) {
				$result['name'] = $value;
			}
		}

		return $result;
	}

	/**
	 * @param array<string, mixed> $field Definicja pola.
	 */
	private static function is_name_field( array $field ): bool {
		$name_keys = array( 'name', 'imie', 'imię', 'first_name', 'firstname', 'nazwa', 'full_name' );
		$key       = strtolower( SchemaFields::field_key( $field ) );

		if ( in_array( $key, $name_keys, true ) ) {
			return true;
		}

		$label = strtolower( (string) ( $field['label'] ?? '' ) );

		return str_contains( $label, 'imię' ) || str_contains( $label, 'imie' ) || str_contains( $label, 'nazwa' );
	}
}
