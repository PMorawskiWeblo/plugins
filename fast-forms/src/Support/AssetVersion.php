<?php
/**
 * Asset cache-busting based on file modification time.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Support;

/**
 * Zwraca wersję pliku CSS/JS na podstawie daty modyfikacji.
 */
final class AssetVersion {

	/**
	 * Wersja do wp_enqueue_* — filemtime lub FF_VERSION jako fallback.
	 *
	 * @param string $relative_path Ścieżka względem katalogu wtyczki, np. assets/admin/js/form-builder.js.
	 */
	public static function get( string $relative_path ): string {
		$relative_path = ltrim( str_replace( '\\', '/', $relative_path ), '/' );
		$path          = FF_PLUGIN_DIR . $relative_path;

		if ( is_file( $path ) ) {
			$mtime = filemtime( $path );

			if ( false !== $mtime ) {
				return (string) $mtime;
			}
		}

		return FF_VERSION;
	}
}
