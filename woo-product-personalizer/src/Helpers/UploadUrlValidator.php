<?php
/**
 * Validate customer image URLs used in personalization state.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class UploadUrlValidator
 */
class UploadUrlValidator {

	/**
	 * Allowed URL prefixes for slot images (without temp token check).
	 *
	 * @return string[]
	 */
	public static function static_asset_prefixes() {
		$upload   = wp_upload_dir();
		$prefixes = array(
			trailingslashit( $upload['baseurl'] ) . WPP_UPLOADS_SUBDIR . '/layouts/',
		);

		if ( defined( 'WPP_PLUGIN_URL' ) ) {
			$prefixes[] = WPP_PLUGIN_URL;
		}

		return $prefixes;
	}

	/**
	 * Whether URL is allowed for a customer-uploaded slot image.
	 *
	 * @param string      $url          Image URL.
	 * @param string|null $upload_token Session upload token (required for temp files).
	 * @return bool
	 */
	public static function is_allowed_customer_image_url( $url, $upload_token = null ) {
		$url = esc_url_raw( (string) $url );
		if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
			return false;
		}

		foreach ( self::static_asset_prefixes() as $prefix ) {
			if ( 0 === strpos( $url, $prefix ) ) {
				return true;
			}
		}

		return self::is_temp_upload_url( $url, $upload_token );
	}

	/**
	 * Whether URL points to a file in the plugin temp uploads directory.
	 *
	 * When no session token is provided (e.g. during checkout), the token folder
	 * is validated from the URL path instead of the live session.
	 *
	 * @param string      $url          Image URL.
	 * @param string|null $upload_token Session upload token.
	 * @return bool
	 */
	public static function is_temp_upload_url( $url, $upload_token = null ) {
		$url = esc_url_raw( (string) $url );
		if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
			return false;
		}

		$upload    = wp_upload_dir();
		$temp_root = trailingslashit( $upload['baseurl'] ) . WPP_UPLOADS_SUBDIR . '/temp/';

		if ( 0 !== strpos( $url, $temp_root ) ) {
			return false;
		}

		$upload_token = is_string( $upload_token ) ? $upload_token : '';

		if ( '' !== $upload_token ) {
			$token = sanitize_file_name( $upload_token );
			if ( '' === $token ) {
				return false;
			}

			return 0 === strpos( $url, $temp_root . $token . '/' );
		}

		return self::temp_url_has_valid_token_segment( $url, $temp_root );
	}

	/**
	 * Validate temp URL shape: temp/{token}/{filename} with a sane token segment.
	 *
	 * @param string $url       Image URL.
	 * @param string $temp_root Temp base URL including trailing slash.
	 * @return bool
	 */
	private static function temp_url_has_valid_token_segment( $url, $temp_root ) {
		$relative = ltrim( substr( $url, strlen( $temp_root ) ), '/' );

		if ( '' === $relative ) {
			return false;
		}

		$parts = explode( '/', $relative, 2 );

		if ( count( $parts ) < 2 || '' === $parts[0] || '' === $parts[1] ) {
			return false;
		}

		$token = sanitize_file_name( $parts[0] );

		return '' !== $token && $token === $parts[0] && strlen( $token ) >= 16;
	}

	/**
	 * Sanitize image_fields in project state (strip paths, drop invalid sources).
	 *
	 * @param array       $state        Project state.
	 * @param string|null $upload_token Upload session token.
	 * @return array
	 */
	public static function sanitize_image_fields( array $state, $upload_token = null ) {
		if ( empty( $state['image_fields'] ) || ! is_array( $state['image_fields'] ) ) {
			return $state;
		}

		foreach ( $state['image_fields'] as $id => $field ) {
			if ( ! is_array( $field ) ) {
				unset( $state['image_fields'][ $id ] );
				continue;
			}

			unset( $state['image_fields'][ $id ]['path'] );

			$source = isset( $field['source'] ) ? (string) $field['source'] : '';
			if ( '' === $source ) {
				$state['image_fields'][ $id ]['source'] = '';
				continue;
			}

			if ( ! self::is_allowed_customer_image_url( $source, $upload_token ) ) {
				$state['image_fields'][ $id ]['source'] = '';
			}
		}

		return $state;
	}
}
