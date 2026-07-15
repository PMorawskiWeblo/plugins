<?php
/**
 * Secure admin download for entry file uploads.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Admin;

use Weblo\FastForms\PostTypes\EntryPostType;
use Weblo\FastForms\Support\Capabilities;
use Weblo\FastForms\Support\UploadProtection;
use Weblo\FastForms\Support\UploadedFiles;

/**
 * Serwuje pliki zgłoszeń tylko dla uprawnionych użytkowników w kokpicie.
 */
final class EntryFileDownload {

	/**
	 * Rejestruje handler pobierania.
	 */
	public function register(): void {
		add_action( 'admin_post_ff_download_entry_file', array( $this, 'serve' ) );
	}

	/**
	 * URL pobrania pliku w panelu admina (wymaga zalogowania + podpisanego tokenu).
	 *
	 * Token HMAC jest niezależny od zalogowanego użytkownika, dzięki czemu linki
	 * wygenerowane przy wysyłce formularza (gość) działają tak samo jak w kokpicie.
	 *
	 * @param int    $entry_id  ID zgłoszenia.
	 * @param string $field_key Klucz pola pliku.
	 * @param int    $index     Indeks pliku (dla wielu plików).
	 * @param bool   $inline    true = podgląd w przeglądarce (obrazy), false = pobranie.
	 */
	public static function get_admin_url( int $entry_id, string $field_key, int $index = 0, bool $inline = false ): string {
		$args = array(
			'action'   => 'ff_download_entry_file',
			'entry_id' => $entry_id,
			'field'    => $field_key,
			'ff_token' => self::download_token( $entry_id, $field_key, $index ),
		);

		if ( $index > 0 ) {
			$args['file_index'] = $index;
		}

		if ( $inline ) {
			$args['ff_inline'] = '1';
		}

		return add_query_arg( $args, admin_url( 'admin-post.php' ) );
	}

	/**
	 * Zwraca rekord pliku z meta zgłoszenia.
	 *
	 * @param int    $index Indeks pliku.
	 * @return array<string, mixed>|null
	 */
	public static function get_file_record( int $entry_id, string $field_key, int $index = 0 ): ?array {
		$records = self::get_file_records( $entry_id, $field_key );

		return $records[ $index ] ?? null;
	}

	/**
	 * Zwraca wszystkie rekordy plików pola.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_file_records( int $entry_id, string $field_key ): array {
		if ( $entry_id < 1 || '' === $field_key ) {
			return array();
		}

		$entry = get_post( $entry_id );

		if ( ! $entry instanceof \WP_Post || EntryPostType::POST_TYPE !== $entry->post_type ) {
			return array();
		}

		$files = get_post_meta( $entry_id, EntryPostType::META_UPLOADED_FILES, true );

		if ( ! is_array( $files ) || ! isset( $files[ $field_key ] ) || ! is_array( $files[ $field_key ] ) ) {
			return array();
		}

		$records = UploadedFiles::records_from_meta( $files[ $field_key ] );

		foreach ( $records as $index => $file ) {
			$path = (string) ( $file['file'] ?? '' );

			if ( '' === $path && ! empty( $file['url'] ) ) {
				$resolved = self::path_from_upload_url( (string) $file['url'] );

				if ( '' !== $resolved ) {
					$records[ $index ]['file'] = $resolved;
				}
			}
		}

		return $records;
	}

	/**
	 * Mapuje publiczny URL uploads na ścieżkę dyskową (stare zgłoszenia).
	 */
	private static function path_from_upload_url( string $url ): string {
		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['error'] ) ) {
			return '';
		}

		$baseurl = trailingslashit( (string) $upload_dir['baseurl'] );

		if ( ! str_starts_with( $url, $baseurl ) ) {
			return '';
		}

		$relative = substr( $url, strlen( $baseurl ) );
		$path     = wp_normalize_path( trailingslashit( (string) $upload_dir['basedir'] ) . $relative );

		return is_readable( $path ) ? $path : '';
	}

	/**
	 * Zwraca nazwę pliku do wyświetlenia.
	 *
	 * @param array<string, mixed> $file Rekord pliku.
	 */
	public static function get_display_name( array $file ): string {
		$name = (string) ( $file['name'] ?? '' );

		if ( '' !== $name ) {
			return $name;
		}

		$path = (string) ( $file['file'] ?? '' );

		return '' !== $path ? basename( $path ) : '';
	}

	/**
	 * Obsługuje żądanie pobrania pliku.
	 */
	public function serve(): void {
		if ( ! Capabilities::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission.', 'fast-forms' ), esc_html__( 'Access denied', 'fast-forms' ), array( 'response' => 403 ) );
		}

		$entry_id  = isset( $_GET['entry_id'] ) ? absint( wp_unslash( (string) $_GET['entry_id'] ) ) : 0;
		$field_key = isset( $_GET['field'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['field'] ) ) : '';
		$file_index = isset( $_GET['file_index'] ) ? absint( wp_unslash( (string) $_GET['file_index'] ) ) : 0;

		if ( $entry_id < 1 || '' === $field_key ) {
			wp_die( esc_html__( 'Invalid request.', 'fast-forms' ), esc_html__( 'Error', 'fast-forms' ), array( 'response' => 400 ) );
		}

		$token = isset( $_GET['ff_token'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['ff_token'] ) ) : '';

		if ( ! self::verify_download_token( $entry_id, $field_key, $file_index, $token ) ) {
			wp_die( esc_html__( 'Invalid request.', 'fast-forms' ), esc_html__( 'Error', 'fast-forms' ), array( 'response' => 403 ) );
		}

		$file = self::get_file_record( $entry_id, $field_key, $file_index );

		if ( null === $file ) {
			wp_die( esc_html__( 'The file does not exist.', 'fast-forms' ), esc_html__( 'Not found', 'fast-forms' ), array( 'response' => 404 ) );
		}

		$path = (string) ( $file['file'] ?? '' );

		if ( '' === $path || ! is_readable( $path ) || ! UploadProtection::is_path_in_uploads( $path ) ) {
			wp_die( esc_html__( 'The file is not available.', 'fast-forms' ), esc_html__( 'Not found', 'fast-forms' ), array( 'response' => 404 ) );
		}

		$name = self::get_display_name( $file );
		$type = (string) ( $file['type'] ?? '' );

		if ( '' === $type ) {
			$checked = wp_check_filetype( $name );
			$type    = (string) ( $checked['type'] ?? 'application/octet-stream' );
		}

		if ( '' === $type ) {
			$type = 'application/octet-stream';
		}

		$inline = isset( $_GET['ff_inline'] ) && '1' === (string) wp_unslash( $_GET['ff_inline'] );

		nocache_headers();
		header( 'Content-Type: ' . $type );
		header(
			'Content-Disposition: ' . ( $inline ? 'inline' : 'attachment' ) . '; filename="' . sanitize_file_name( $name ) . '"'
		);
		header( 'Content-Length: ' . (string) filesize( $path ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $path );
		exit;
	}

	/**
	 * Generuje deterministyczny token pobrania (HMAC, bez powiązania z sesją użytkownika).
	 */
	private static function download_token( int $entry_id, string $field_key, int $index = 0 ): string {
		return hash_hmac(
			'sha256',
			$entry_id . '|' . $field_key . '|' . $index,
			wp_salt( 'auth' )
		);
	}

	/**
	 * Weryfikuje token pobrania z URL.
	 */
	private static function verify_download_token( int $entry_id, string $field_key, int $index, string $token ): bool {
		if ( '' === $token ) {
			return false;
		}

		return hash_equals( self::download_token( $entry_id, $field_key, $index ), $token );
	}
}
