<?php
/**
 * Supported customer upload MIME types.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class UploadMimeTypes
 */
class UploadMimeTypes {

	/**
	 * MIME types the personalizer can load on canvas (Konva / browser).
	 *
	 * @return array<string, array{label: string, extensions: string}>
	 */
	public static function definitions() {
		return array(
			'image/jpeg' => array(
				'label'      => 'JPEG',
				'extensions' => 'jpg|jpeg|jpe',
			),
			'image/png'  => array(
				'label'      => 'PNG',
				'extensions' => 'png',
			),
			'image/webp' => array(
				'label'      => 'WebP',
				'extensions' => 'webp',
			),
			'image/gif'  => array(
				'label'      => 'GIF',
				'extensions' => 'gif',
			),
			'image/avif' => array(
				'label'      => 'AVIF',
				'extensions' => 'avif',
			),
			'image/bmp'  => array(
				'label'      => 'BMP',
				'extensions' => 'bmp',
			),
		);
	}

	/**
	 * All supported MIME type strings.
	 *
	 * @return string[]
	 */
	public static function all() {
		return array_keys( self::definitions() );
	}

	/**
	 * Keep only supported MIME types.
	 *
	 * @param string[] $types Selected types.
	 * @return string[]
	 */
	public static function filter_allowed( array $types ) {
		return array_values( array_intersect( $types, self::all() ) );
	}

	/**
	 * Map for wp_handle_upload() from allowed MIME list.
	 *
	 * @param string[] $allowed_mimes Allowed MIME types.
	 * @return array<string, string>
	 */
	public static function wp_upload_map( array $allowed_mimes ) {
		$map = array();

		foreach ( self::definitions() as $mime => $definition ) {
			if ( in_array( $mime, $allowed_mimes, true ) ) {
				$map[ $definition['extensions'] ] = $mime;
			}
		}

		return $map;
	}
}
