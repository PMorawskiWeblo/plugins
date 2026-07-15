<?php
/**
 * Convert uploads to browser-friendly formats for crop/canvas preview.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class ImagePreviewConverter
 */
class ImagePreviewConverter {

	/**
	 * MIME types and extensions browsers usually cannot render in img/canvas.
	 *
	 * @param string $mime      Detected MIME type.
	 * @param string $extension File extension without dot.
	 * @return bool
	 */
	public static function needs_conversion( $mime, $extension = '' ) {
		$mime      = strtolower( (string) $mime );
		$extension = strtolower( ltrim( (string) $extension, '.' ) );

		if ( in_array( $mime, array( 'image/heic', 'image/heif' ), true ) ) {
			return true;
		}

		return in_array( $extension, array( 'heic', 'heif' ), true );
	}

	/**
	 * Convert source file to JPEG when required for storefront preview.
	 *
	 * @param string $source_path Absolute path to uploaded file.
	 * @param string $mime        Detected MIME type.
	 * @param string $target_dir  Directory for converted file.
	 * @return array{path: string, type: string}|\WP_Error|null Null when no conversion is needed.
	 */
	public static function convert_for_browser_preview( $source_path, $mime, $target_dir ) {
		$extension = strtolower( (string) pathinfo( $source_path, PATHINFO_EXTENSION ) );

		if ( ! self::needs_conversion( $mime, $extension ) ) {
			return null;
		}

		if ( ! is_readable( $source_path ) || ! is_dir( $target_dir ) ) {
			return new \WP_Error(
				'wpp_preview_convert',
				__( 'Could not prepare this image for preview.', 'woo-product-personalizer' )
			);
		}

		$base_name = sanitize_file_name( (string) pathinfo( $source_path, PATHINFO_FILENAME ) );
		if ( '' === $base_name ) {
			$base_name = 'upload-' . time();
		}

		$jpeg_name = wp_unique_filename( $target_dir, $base_name . '.jpg' );
		$jpeg_path = trailingslashit( $target_dir ) . $jpeg_name;

		$result = self::convert_to_jpeg( $source_path, $jpeg_path );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( $jpeg_path !== $source_path && is_readable( $source_path ) ) {
			wp_delete_file( $source_path );
		}

		return array(
			'path' => $jpeg_path,
			'type' => 'image/jpeg',
		);
	}

	/**
	 * @param string $source Source path.
	 * @param string $dest   Destination JPEG path.
	 * @return true|\WP_Error
	 */
	private static function convert_to_jpeg( $source, $dest ) {
		if ( function_exists( 'wp_get_image_editor' ) ) {
			$editor = wp_get_image_editor( $source );

			if ( ! is_wp_error( $editor ) ) {
				$saved = $editor->save( $dest, 'image/jpeg' );

				if ( ! is_wp_error( $saved ) && ! empty( $saved['path'] ) && is_readable( $saved['path'] ) ) {
					return true;
				}
			}
		}

		return self::convert_with_imagick( $source, $dest );
	}

	/**
	 * @param string $source Source path.
	 * @param string $dest   Destination JPEG path.
	 * @return true|\WP_Error
	 */
	private static function convert_with_imagick( $source, $dest ) {
		if ( ! class_exists( '\Imagick' ) ) {
			return new \WP_Error(
				'wpp_preview_convert',
				__( 'This image format cannot be previewed on the server. The browser should convert HEIC automatically; if this persists, upload JPEG or PNG instead.', 'woo-product-personalizer' )
			);
		}

		try {
			$image = new \Imagick( $source );

			if ( method_exists( $image, 'autoOrientImage' ) ) {
				$image->autoOrientImage();
			}

			$image->setImageFormat( 'jpeg' );
			$image->setImageCompressionQuality( 92 );
			$image->writeImage( $dest );
			$image->clear();

			if ( method_exists( $image, 'destroy' ) ) {
				$image->destroy();
			}

			if ( ! is_readable( $dest ) ) {
				return new \WP_Error(
					'wpp_preview_convert',
					__( 'Could not convert this image for preview. Please upload JPEG or PNG instead.', 'woo-product-personalizer' )
				);
			}

			return true;
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'wpp_preview_convert',
				__( 'Could not convert this image for preview. Please upload JPEG or PNG instead.', 'woo-product-personalizer' )
			);
		}
	}
}
