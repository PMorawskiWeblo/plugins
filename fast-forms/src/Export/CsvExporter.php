<?php
/**
 * CSV export for form entries.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Export;

use Weblo\FastForms\Admin\EntryFileDownload;
use Weblo\FastForms\FormBuilder\FormSchemaStorage;
use Weblo\FastForms\PostTypes\EntryPostType;
use Weblo\FastForms\PostTypes\FormPostType;
use Weblo\FastForms\Submission\SchemaFields;
use Weblo\FastForms\Support\EntryDateFilter;

/**
 * Generuje eksport CSV zgłoszeń dla wybranego formularza.
 */
final class CsvExporter {

	private const BATCH_SIZE = 200;

	/**
	 * Wysyła plik CSV do przeglądarki.
	 *
	 * @param int    $form_id   ID formularza.
	 * @param string $date_from Data od (Y-m-d).
	 * @param string $date_to   Data do (Y-m-d).
	 */
	public static function stream( int $form_id, string $date_from = '', string $date_to = '' ): void {
		$form = get_post( $form_id );

		if ( ! $form instanceof \WP_Post || FormPostType::POST_TYPE !== $form->post_type ) {
			wp_die( esc_html__( 'Invalid form.', 'fast-forms' ) );
		}

		$schema  = FormSchemaStorage::get( $form_id );
		$columns = self::build_columns( $schema, $form_id, $date_from, $date_to );

		$filename = sanitize_file_name( 'fast-forms-' . $form_id . '-' . gmdate( 'Y-m-d-His' ) . '.csv' );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$output = fopen( 'php://output', 'w' );

		if ( false === $output ) {
			wp_die( esc_html__( 'Could not generate the CSV file.', 'fast-forms' ) );
		}

		// BOM dla poprawnego UTF-8 w Excelu.
		fwrite( $output, "\xEF\xBB\xBF" );

		fputcsv( $output, array_values( $columns ), ';' );

		foreach ( self::entry_batches( $form_id, $date_from, $date_to ) as $entries ) {
			$post_ids = wp_list_pluck( $entries, 'ID' );

			if ( ! empty( $post_ids ) ) {
				update_meta_cache( 'post', $post_ids );
			}

			foreach ( $entries as $entry ) {
				if ( ! $entry instanceof \WP_Post ) {
					continue;
				}

				fputcsv( $output, self::build_row_line( $entry, $columns ), ';' );
			}
		}

		fclose( $output );
		exit;
	}

	/**
	 * @param array<string, mixed> $schema    Schemа formularza.
	 * @param int                  $form_id   ID formularza.
	 * @param string               $date_from Data od.
	 * @param string               $date_to   Data do.
	 * @return array<string, string> Mapa klucz => nagłówek.
	 */
	private static function build_columns( array $schema, int $form_id, string $date_from, string $date_to ): array {
		$columns = self::base_columns( $schema );

		foreach ( self::collect_extra_field_keys( $form_id, $date_from, $date_to, $schema ) as $key ) {
			$column_key = 'field:' . $key;

			if ( ! isset( $columns[ $column_key ] ) ) {
				$columns[ $column_key ] = $key;
			}
		}

		return $columns;
	}

	/**
	 * @param array<string, mixed> $schema Schemа formularza.
	 * @return array<string, string>
	 */
	private static function base_columns( array $schema ): array {
		$columns = array(
			'entry_id'        => __( 'Entry ID', 'fast-forms' ),
			'submitted_at'    => __( 'Submitted at', 'fast-forms' ),
			'status'          => __( 'Status', 'fast-forms' ),
			'recaptcha_score' => __( 'reCAPTCHA score', 'fast-forms' ),
		);

		foreach ( SchemaFields::flatten( $schema ) as $field ) {
			$key = SchemaFields::field_key( $field );

			if ( '' === $key ) {
				continue;
			}

			$columns[ 'field:' . $key ] = (string) ( $field['label'] ?? $key );
		}

		return $columns;
	}

	/**
	 * @param array<string, mixed> $schema Schemа formularza.
	 * @return array<int, string>
	 */
	private static function collect_extra_field_keys( int $form_id, string $date_from, string $date_to, array $schema ): array {
		$schema_keys = array();

		foreach ( SchemaFields::flatten( $schema ) as $field ) {
			$key = SchemaFields::field_key( $field );

			if ( '' !== $key ) {
				$schema_keys[ $key ] = true;
			}
		}

		$extra = array();

		foreach ( self::entry_batches( $form_id, $date_from, $date_to ) as $entries ) {
			$post_ids = wp_list_pluck( $entries, 'ID' );

			if ( ! empty( $post_ids ) ) {
				update_meta_cache( 'post', $post_ids );
			}

			foreach ( $entries as $entry ) {
				if ( ! $entry instanceof \WP_Post ) {
					continue;
				}

				foreach ( array_keys( self::decode_payload( $entry->ID ) ) as $key ) {
					if ( ! isset( $schema_keys[ $key ] ) ) {
						$extra[ $key ] = true;
					}
				}
			}
		}

		return array_keys( $extra );
	}

	/**
	 * @return \Generator<int, array<int, \WP_Post>>
	 */
	private static function entry_batches( int $form_id, string $date_from, string $date_to ): \Generator {
		$page = 1;

		do {
			$query = new \WP_Query( self::query_args( $form_id, $date_from, $date_to, $page ) );
			$posts = array_filter(
				$query->posts,
				static function ( $post ): bool {
					return $post instanceof \WP_Post;
				}
			);

			if ( empty( $posts ) ) {
				break;
			}

			yield array_values( $posts );

			++$page;
		} while ( $page <= (int) $query->max_num_pages );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function query_args( int $form_id, string $date_from, string $date_to, int $page ): array {
		list( $date_from, $date_to ) = EntryDateFilter::normalize_range( $date_from, $date_to );

		return array(
			'post_type'              => EntryPostType::POST_TYPE,
			'post_status'            => EntryPostType::QUERY_STATUSES,
			'posts_per_page'         => self::BATCH_SIZE,
			'paged'                  => $page,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'fields'                 => 'all',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => EntryDateFilter::merge_meta_query(
				array(
					array(
						'key'   => EntryPostType::META_FORM_ID,
						'value' => $form_id,
					),
				),
				$date_from,
				$date_to
			),
		);
	}

	/**
	 * @param array<string, string> $columns Kolumny.
	 * @return array<int, string>
	 */
	private static function build_row_line( \WP_Post $entry, array $columns ): array {
		$payload = self::decode_payload( $entry->ID );
		$files   = get_post_meta( $entry->ID, EntryPostType::META_UPLOADED_FILES, true );
		$files   = is_array( $files ) ? $files : array();

		$row = array(
			'entry_id'        => (string) $entry->ID,
			'submitted_at'    => (string) get_post_meta( $entry->ID, EntryPostType::META_SUBMITTED_AT, true ),
			'status'          => (string) get_post_meta( $entry->ID, EntryPostType::META_STATUS, true ),
			'recaptcha_score' => (string) get_post_meta( $entry->ID, EntryPostType::META_RECAPTCHA_SCORE, true ),
		);

		foreach ( $payload as $key => $value ) {
			$row[ 'field:' . $key ] = self::stringify_value( $value, $files, (string) $key, $entry->ID );
		}

		$line = array();

		foreach ( array_keys( $columns ) as $column_key ) {
			$line[] = $row[ $column_key ] ?? '';
		}

		return $line;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function decode_payload( int $entry_id ): array {
		$raw  = get_post_meta( $entry_id, EntryPostType::META_PAYLOAD, true );
		$data = is_string( $raw ) ? json_decode( $raw, true ) : $raw;

		return is_array( $data ) ? $data : array();
	}

	/**
	 * @param mixed                     $value Wartość pola.
	 * @param array<string, mixed>      $files Meta plików zgłoszenia.
	 */
	private static function stringify_value( $value, array $files, string $key, int $entry_id ): string {
		if ( is_array( $value ) ) {
			return wp_json_encode( $value ) ?: '';
		}

		if ( isset( $files[ $key ] ) && is_array( $files[ $key ] ) ) {
			$records = \Weblo\FastForms\Support\UploadedFiles::records_from_meta( $files[ $key ] );

			if ( ! empty( $records ) ) {
				$parts = array();

				foreach ( $records as $index => $file ) {
					$name    = EntryFileDownload::get_display_name( $file );
					$url     = EntryFileDownload::get_admin_url( $entry_id, $key, $index );
					$parts[] = $name . ' (' . $url . ')';
				}

				return implode( '; ', $parts );
			}
		}

		if ( is_bool( $value ) ) {
			return $value ? '1' : '0';
		}

		return (string) $value;
	}
}
