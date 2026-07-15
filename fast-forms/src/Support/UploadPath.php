<?php
/**
 * Upload path resolution for form file fields.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Support;

use Weblo\FastForms\FormBuilder\FormSettingsStorage;
use Weblo\FastForms\PostTypes\FormPostType;

/**
 * Buduje bezpieczną ścieżkę względem katalogu uploads WordPress.
 */
final class UploadPath {

	public const DEFAULT_PATTERN = 'fast-forms/{form_slug}';

	/**
	 * Domyślny wzorzec ścieżki (globalny).
	 */
	public static function get_default_pattern(): string {
		return self::DEFAULT_PATTERN;
	}

	/**
	 * Sanityzuje wzorzec ścieżki (może zawierać tagi {form_slug} itd.).
	 */
	public static function sanitize_pattern( string $path ): string {
		$path = trim( str_replace( '\\', '/', $path ) );
		$path = ltrim( $path, '/' );

		if ( '' === $path || str_contains( $path, '..' ) ) {
			return self::DEFAULT_PATTERN;
		}

		$path = preg_replace( '#[^a-zA-Z0-9_/\-\{\}]#', '', $path ) ?? '';
		$path = preg_replace( '#/+#', '/', $path ) ?? '';
		$path = trim( $path, '/' );

		return '' !== $path ? $path : self::DEFAULT_PATTERN;
	}

	/**
	 * Zwraca wzorzec ścieżki dla formularza (przed rozwinięciem tagów).
	 */
	public static function get_pattern_for_form( int $form_id ): string {
		$form_settings = FormSettingsStorage::get_form( $form_id );
		$custom        = trim( (string) ( $form_settings['uploadPath'] ?? '' ) );

		if ( '' !== $custom ) {
			return self::sanitize_pattern( $custom );
		}

		$global = (string) ( GlobalSettings::get()['uploadPath'] ?? self::DEFAULT_PATTERN );

		return self::sanitize_pattern( $global );
	}

	/**
	 * Rozwija tagi i zwraca katalog bazowy formularza w uploads.
	 */
	public static function resolve_for_form( int $form_id ): string {
		return self::expand_placeholders( self::get_pattern_for_form( $form_id ), $form_id );
	}

	/**
	 * Pełna ścieżka względna dla zgłoszenia (bez leading slash).
	 */
	public static function get_entry_subdir( int $form_id, int $entry_id ): string {
		$base = self::resolve_for_form( $form_id );

		return $base . '/' . gmdate( 'Y/m' ) . '/entry-' . max( 0, $entry_id );
	}

	/**
	 * Publiczny URL katalogu bazowego formularza.
	 */
	public static function get_form_base_url( int $form_id ): string {
		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['error'] ) ) {
			return '';
		}

		return trailingslashit( $upload_dir['baseurl'] ) . self::resolve_for_form( $form_id );
	}

	/**
	 * @return array<string, string>
	 */
	public static function get_merge_tags(): array {
		return array(
			'{form_slug}'  => __( 'form slug (post name)', 'fast-forms' ),
			'{form_id}'    => __( 'numeric form ID', 'fast-forms' ),
			'{form_title}' => __( 'sanitized form title', 'fast-forms' ),
		);
	}

	/**
	 * HTML z listą dostępnych tagów ścieżki.
	 */
	public static function merge_tags_list_html(): string {
		$parts = array();

		foreach ( self::get_merge_tags() as $tag => $label ) {
			$parts[] = '<code>' . esc_html( $tag ) . '</code> — ' . esc_html( $label );
		}

		return implode( '<br>', $parts );
	}

	/**
	 * @param string $pattern Wzorzec z tagami.
	 */
	private static function expand_placeholders( string $pattern, int $form_id ): string {
		$post = get_post( $form_id );

		if ( ! $post instanceof \WP_Post || FormPostType::POST_TYPE !== $post->post_type ) {
			return 'fast-forms/form-' . $form_id;
		}

		$slug = sanitize_title( $post->post_name );

		if ( '' === $slug ) {
			$slug = sanitize_title( $post->post_title );
		}

		if ( '' === $slug ) {
			$slug = 'form-' . $form_id;
		}

		$title_slug = sanitize_file_name( $post->post_title );

		if ( '' === $title_slug ) {
			$title_slug = 'form-' . $form_id;
		}

		$path = str_replace(
			array( '{form_slug}', '{form_id}', '{form_title}' ),
			array( $slug, (string) $form_id, $title_slug ),
			$pattern
		);

		$segments = array();
		$parts    = explode( '/', $path );

		foreach ( $parts as $part ) {
			if ( '' === $part ) {
				continue;
			}

			$segment = sanitize_file_name( $part );

			if ( '' !== $segment ) {
				$segments[] = $segment;
			}
		}

		if ( empty( $segments ) ) {
			return 'fast-forms/form-' . $form_id;
		}

		return implode( '/', $segments );
	}
}
