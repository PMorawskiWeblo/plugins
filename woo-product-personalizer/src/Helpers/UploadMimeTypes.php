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
	 * @return array<string, array{label: string, extensions: string, alternate_types?: string[]}>
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
			'image/heic' => array(
				'label'           => 'HEIC',
				'extensions'      => 'heic|heif',
				'alternate_types' => array( 'image/heif' ),
			),
		);
	}

	/**
	 * Extension → MIME fallbacks when WordPress does not know the type yet.
	 *
	 * @return array<string, string>
	 */
	private static function extension_fallback_map() {
		return array(
			'heic' => 'image/heic',
			'heif' => 'image/heif',
			'jfif' => 'image/jpeg',
		);
	}

	/**
	 * All built-in MIME type strings.
	 *
	 * @return string[]
	 */
	public static function all() {
		return array_keys( self::definitions() );
	}

	/**
	 * Keep only built-in MIME types from checkbox selection.
	 *
	 * @param string[] $types Selected types.
	 * @return string[]
	 */
	public static function filter_allowed( array $types ) {
		return array_values( array_intersect( $types, self::all() ) );
	}

	/**
	 * Include alternate MIME aliases for selected built-in types (e.g. HEIF for HEIC).
	 *
	 * @param string[] $types MIME types.
	 * @return string[]
	 */
	public static function expand_allowed( array $types ) {
		$expanded = $types;

		foreach ( self::definitions() as $mime => $definition ) {
			if ( ! in_array( $mime, $types, true ) || empty( $definition['alternate_types'] ) ) {
				continue;
			}

			foreach ( $definition['alternate_types'] as $alternate ) {
				$expanded[] = $alternate;
			}
		}

		return array_values( array_unique( $expanded ) );
	}

	/**
	 * Parse comma-separated custom formats (MIME or extension) into image MIME types.
	 *
	 * @param string $raw Raw admin input.
	 * @return string[]
	 */
	public static function parse_custom_string( $raw ) {
		$mimes = array();

		foreach ( preg_split( '/\s*,\s*/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY ) as $token ) {
			$mime = self::token_to_mime( $token );
			if ( $mime ) {
				$mimes[] = $mime;
			}
		}

		return array_values( array_unique( $mimes ) );
	}

	/**
	 * Sanitize and normalize custom formats string for storage.
	 *
	 * @param string $raw Raw admin input.
	 * @return string
	 */
	public static function sanitize_custom_string( $raw ) {
		$parts = array();

		foreach ( preg_split( '/\s*,\s*/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY ) as $token ) {
			$token = sanitize_text_field( trim( $token ) );
			if ( '' === $token ) {
				continue;
			}

			if ( self::token_to_mime( $token ) ) {
				$parts[] = $token;
			}
		}

		return implode( ', ', $parts );
	}

	/**
	 * Effective allowed MIME list: checkboxes + custom field.
	 *
	 * @param string[] $checkbox_types Built-in types from settings.
	 * @param string   $custom_raw     Comma-separated custom formats.
	 * @return string[]
	 */
	public static function merge_allowed_types( array $checkbox_types, $custom_raw ) {
		$known  = self::filter_allowed( $checkbox_types );
		$custom = self::parse_custom_string( $custom_raw );

		return self::expand_allowed(
			array_values(
				array_unique(
					array_merge( $known, $custom )
				)
			)
		);
	}

	/**
	 * Resolve a single token (MIME or extension) to an image MIME type.
	 *
	 * @param string $token Token from admin or filename extension.
	 * @return string Empty when invalid.
	 */
	public static function token_to_mime( $token ) {
		$token = strtolower( trim( (string) $token ) );

		if ( '' === $token ) {
			return '';
		}

		if ( preg_match( '#^image/[a-z0-9.+-]+$#i', $token ) ) {
			return $token;
		}

		$extension = ltrim( $token, '.' );
		if ( ! preg_match( '/^[a-z0-9]+$/', $extension ) ) {
			return '';
		}

		$checked = wp_check_filetype( 'file.' . $extension );
		if ( ! empty( $checked['type'] ) && 0 === strpos( $checked['type'], 'image/' ) ) {
			return $checked['type'];
		}

		$fallbacks = self::extension_fallback_map();

		return $fallbacks[ $extension ] ?? '';
	}

	/**
	 * Map for wp_check_filetype_and_ext() / wp_handle_upload().
	 *
	 * @param string[] $allowed_mimes Allowed MIME types.
	 * @return array<string, string>
	 */
	public static function wp_upload_map( array $allowed_mimes ) {
		$allowed_mimes = self::expand_allowed( $allowed_mimes );
		$map           = array();

		foreach ( self::definitions() as $mime => $definition ) {
			if ( in_array( $mime, $allowed_mimes, true ) ) {
				$map[ $definition['extensions'] ] = $mime;
			}
		}

		foreach ( $allowed_mimes as $mime ) {
			$already_mapped = false;

			foreach ( $map as $mapped_mime ) {
				if ( $mapped_mime === $mime ) {
					$already_mapped = true;
					break;
				}
			}

			if ( $already_mapped ) {
				continue;
			}

			$extensions = self::mime_to_extensions( $mime );
			if ( '' !== $extensions ) {
				$map[ $extensions ] = $mime;
			}
		}

		return $map;
	}

	/**
	 * File extensions (lowercase, no dot) allowed for client-side fallback checks.
	 *
	 * @param string[] $allowed_mimes Allowed MIME types.
	 * @return string[]
	 */
	public static function allowed_extensions( array $allowed_mimes ) {
		$extensions = array();

		foreach ( self::wp_upload_map( $allowed_mimes ) as $ext_group => $mime ) {
			unset( $mime );
			foreach ( explode( '|', (string) $ext_group ) as $ext ) {
				$ext = strtolower( trim( $ext ) );
				if ( '' !== $ext ) {
					$extensions[] = $ext;
				}
			}
		}

		return array_values( array_unique( $extensions ) );
	}

	/**
	 * Whether HEIC/HEIF uploads are enabled in settings.
	 *
	 * @param string[] $allowed_mimes Allowed MIME types.
	 * @return bool
	 */
	public static function allows_heic_upload( array $allowed_mimes ) {
		$expanded = self::expand_allowed( $allowed_mimes );

		return in_array( 'image/heic', $expanded, true ) || in_array( 'image/heif', $expanded, true );
	}

	/**
	 * Values for HTML file input accept attribute.
	 *
	 * @param string[] $allowed_mimes Allowed MIME types.
	 * @return string[]
	 */
	public static function accept_list( array $allowed_mimes ) {
		$accept = self::expand_allowed( $allowed_mimes );

		foreach ( self::allowed_extensions( $allowed_mimes ) as $ext ) {
			$accept[] = '.' . $ext;
		}

		return array_values( array_unique( $accept ) );
	}

	/**
	 * Guess WordPress-style extension group for a custom image MIME type.
	 *
	 * @param string $mime MIME type.
	 * @return string
	 */
	private static function mime_to_extensions( $mime ) {
		if ( 0 !== strpos( $mime, 'image/' ) ) {
			return '';
		}

		foreach ( wp_get_mime_types() as $extensions => $wp_mime ) {
			$wp_mimes = explode( '|', (string) $wp_mime );
			if ( in_array( $mime, $wp_mimes, true ) ) {
				return $extensions;
			}
		}

		$subtype = substr( $mime, 6 );
		$common  = array(
			'tiff'   => 'tif|tiff',
			'svg+xml' => 'svg',
		);

		return $common[ $subtype ] ?? $subtype;
	}
}
