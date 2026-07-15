<?php
/**
 * HTTP protection for uploaded form files.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Support;

/**
 * Blokuje bezpośredni dostęp HTTP do katalogów z plikami formularzy (Apache).
 */
final class UploadProtection {

	/**
	 * Zabezpiecza katalog przed listowaniem i bezpośrednim pobieraniem plików.
	 */
	public static function protect_directory( string $absolute_path ): void {
		$absolute_path = wp_normalize_path( $absolute_path );

		if ( '' === $absolute_path || ! is_dir( $absolute_path ) ) {
			return;
		}

		self::write_protection_files( $absolute_path );

		$upload_dir = wp_upload_dir();

		if ( empty( $upload_dir['error'] ) ) {
			$base = wp_normalize_path( (string) $upload_dir['basedir'] );

			if ( '' !== $base && str_starts_with( $absolute_path, $base ) ) {
				self::protect_ancestor_directories( $base, $absolute_path );
			}
		}
	}

	/**
	 * Sprawdza, czy ścieżka pliku leży w katalogu uploads WordPress.
	 */
	public static function is_path_in_uploads( string $absolute_path ): bool {
		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['error'] ) ) {
			return false;
		}

		$basedir = wp_normalize_path( (string) $upload_dir['basedir'] );
		$real    = wp_normalize_path( realpath( $absolute_path ) ?: $absolute_path );

		if ( '' === $basedir || '' === $real ) {
			return false;
		}

		return str_starts_with( $real, trailingslashit( $basedir ) );
	}

	/**
	 * @param string $base    Katalog uploads.
	 * @param string $target  Katalog docelowy pliku.
	 */
	private static function protect_ancestor_directories( string $base, string $target ): void {
		$relative = ltrim( substr( $target, strlen( trailingslashit( $base ) ) ), '/' );

		if ( '' === $relative ) {
			return;
		}

		$current = $base;

		foreach ( explode( '/', $relative ) as $segment ) {
			if ( '' === $segment ) {
				continue;
			}

			$current = trailingslashit( $current ) . $segment;

			if ( is_dir( $current ) ) {
				self::write_protection_files( $current );
			}
		}
	}

	/**
	 * @param string $dir Ścieżka katalogu.
	 */
	private static function write_protection_files( string $dir ): void {
		$htaccess = trailingslashit( $dir ) . '.htaccess';

		if ( ! file_exists( $htaccess ) ) {
			$content = "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n";

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $htaccess, $content );
		}

		$index = trailingslashit( $dir ) . 'index.php';

		if ( ! file_exists( $index ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}
	}
}
