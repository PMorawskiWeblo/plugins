<?php
/**
 * Resolve layout asset paths to public URLs.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class LayoutAssetResolver
 */
class LayoutAssetResolver {

	/**
	 * Resolve all asset URLs in layout config.
	 *
	 * @param array $config Layout config.
	 * @return array
	 */
	public static function resolve( array $config ) {
		if ( ! empty( $config['canvas'] ) && is_array( $config['canvas'] ) ) {
			$config['canvas']['background'] = self::resolve_url( $config['canvas']['background'] ?? '' );
			$config['canvas']['overlay']    = self::resolve_url( $config['canvas']['overlay'] ?? '' );
		}

		if ( ! empty( $config['image_slots'] ) && is_array( $config['image_slots'] ) ) {
			foreach ( $config['image_slots'] as $i => $slot ) {
				if ( ! empty( $slot['mask'] ) ) {
					$config['image_slots'][ $i ]['mask'] = self::resolve_url( $slot['mask'] );
				}
			}
		}

		return $config;
	}

	/**
	 * Resolve single asset path relative to the plugin directory.
	 *
	 * @param string $url URL or relative path.
	 * @return string
	 */
	public static function resolve_url( $url ) {
		if ( empty( $url ) ) {
			return '';
		}

		if ( preg_match( '#^https?://#i', $url ) ) {
			return $url;
		}

		$relative = ltrim( $url, '/' );
		$path     = WPP_PLUGIN_PATH . $relative;

		if ( file_exists( $path ) ) {
			return add_query_arg( 'ver', WPP_VERSION, WPP_PLUGIN_URL . $relative );
		}

		return $url;
	}
}
