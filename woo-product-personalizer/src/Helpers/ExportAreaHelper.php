<?php
/**
 * Export area definitions (mask + frame) for production clipping.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class ExportAreaHelper
 */
class ExportAreaHelper {

	/** Centimeters per inch (for PDF point conversion). */
	const CM_PER_INCH = 2.54;

	/** PDF points per inch. */
	const PDF_POINTS_PER_INCH = 72;

	/**
	 * Project PDF page size from layout (cm). Empty when not configured.
	 *
	 * @param array $config Layout config.
	 * @return array{width_cm: float, height_cm: float}|null
	 */
	public static function get_project_pdf_size_cm( array $config ) {
		$pdf = $config['project_pdf'] ?? array();
		$w   = isset( $pdf['width_cm'] ) ? (float) $pdf['width_cm'] : 0;
		$h   = isset( $pdf['height_cm'] ) ? (float) $pdf['height_cm'] : 0;

		if ( $w > 0 && $h > 0 ) {
			return array(
				'width_cm'  => $w,
				'height_cm' => $h,
			);
		}

		return null;
	}

	/**
	 * Convert centimeters to PDF points (1/72 inch).
	 *
	 * @param float $cm Length in cm.
	 * @return float
	 */
	public static function cm_to_pdf_points( $cm ) {
		return (float) $cm * self::PDF_POINTS_PER_INCH / self::CM_PER_INCH;
	}

	/**
	 * Target pixel size for a PDF page at given DPI.
	 *
	 * @param float $width_cm  Width in cm.
	 * @param float $height_cm Height in cm.
	 * @param int   $dpi       DPI.
	 * @return array{0: int, 1: int}
	 */
	public static function pdf_page_pixel_size( $width_cm, $height_cm, $dpi ) {
		$dpi = max( 72, min( 1200, absint( $dpi ) ) );

		return array(
			max( 1, (int) round( (float) $width_cm / self::CM_PER_INCH * $dpi ) ),
			max( 1, (int) round( (float) $height_cm / self::CM_PER_INCH * $dpi ) ),
		);
	}

	/**
	 * Resize PNG to exact print dimensions (cm at DPI); white background, cover fit.
	 *
	 * @param string $png_path   PNG file path.
	 * @param float  $width_cm   Page width cm.
	 * @param float  $height_cm  Page height cm.
	 * @param int    $dpi        DPI.
	 * @return bool
	 */
	public static function resize_png_to_pdf_cm( $png_path, $width_cm, $height_cm, $dpi ) {
		$png_path = is_string( $png_path ) ? trim( $png_path ) : '';

		if ( '' === $png_path || ! is_readable( $png_path ) || ! function_exists( 'imagecreatefrompng' ) ) {
			return false;
		}

		$size = self::pdf_page_pixel_size( $width_cm, $height_cm, $dpi );
		$tw   = $size[0];
		$th   = $size[1];

		$src = imagecreatefrompng( $png_path );
		if ( ! $src ) {
			return false;
		}

		$sw = imagesx( $src );
		$sh = imagesy( $src );

		$dest = imagecreatetruecolor( $tw, $th );
		if ( ! $dest ) {
			imagedestroy( $src );
			return false;
		}

		$white = imagecolorallocate( $dest, 255, 255, 255 );
		imagefill( $dest, 0, 0, $white );
		imagecopyresampled( $dest, $src, 0, 0, 0, 0, $tw, $th, $sw, $sh );
		imagedestroy( $src );

		imagealphablending( $dest, false );
		imagesavealpha( $dest, true );
		$ok = imagepng( $dest, $png_path );
		imagedestroy( $dest );

		return (bool) $ok;
	}

	/**
	 * First export area with a valid frame (mask optional — rectangle crop only).
	 *
	 * @param array $config Layout config.
	 * @return array|null
	 */
	public static function get_primary( array $config ) {
		if ( empty( $config['export_areas'] ) || ! is_array( $config['export_areas'] ) ) {
			return null;
		}

		foreach ( $config['export_areas'] as $area ) {
			if ( ! is_array( $area ) ) {
				continue;
			}

			$frame = $area['frame'] ?? array();
			$w     = absint( $frame['width'] ?? 0 );
			$h     = absint( $frame['height'] ?? 0 );

			if ( $w > 0 && $h > 0 ) {
				return $area;
			}
		}

		return null;
	}

	/**
	 * Whether PNG dimensions match layout canvas (not yet cropped to export frame).
	 *
	 * @param string $path   PNG path.
	 * @param array  $config Layout config.
	 * @return bool
	 */
	public static function png_is_full_canvas( $path, array $config ) {
		$info = getimagesize( $path );
		if ( ! is_array( $info ) ) {
			return false;
		}

		$canvas = $config['canvas'] ?? array();
		$cw     = absint( $canvas['width'] ?? 0 );
		$ch     = absint( $canvas['height'] ?? 0 );

		return $cw > 0 && $ch > 0 && (int) $info[0] === $cw && (int) $info[1] === $ch;
	}

	/**
	 * Whether a text field bounding box intersects an export area frame.
	 *
	 * @param array $field Text field config.
	 * @param array $area  Export area.
	 * @return bool
	 */
	public static function text_field_in_area( array $field, array $area ) {
		$style = $field['style'] ?? array();
		$frame = $area['frame'] ?? array();

		$tx = absint( $style['x'] ?? 0 );
		$ty = absint( $style['y'] ?? 0 );
		$tw = max( 1, absint( $style['width'] ?? 400 ) );
		$th = max( 1, absint( $style['height'] ?? 80 ) );

		$fx = absint( $frame['x'] ?? 0 );
		$fy = absint( $frame['y'] ?? 0 );
		$fw = absint( $frame['width'] ?? 0 );
		$fh = absint( $frame['height'] ?? 0 );

		if ( $fw < 1 || $fh < 1 ) {
			return true;
		}

		return ! ( $tx + $tw < $fx || $tx > $fx + $fw || $ty + $th < $fy || $ty > $fy + $fh );
	}

	/**
	 * Crop PNG to export frame; optional mask PNG further clips alpha.
	 *
	 * @param string $png_path Absolute path to PNG.
	 * @param array  $area     Export area config.
	 * @param bool   $from_canvas_origin When true, source is full canvas and frame x/y apply.
	 * @return bool
	 */
	public static function clip_png_to_area( $png_path, array $area, $from_canvas_origin = true ) {
		$png_path = is_string( $png_path ) ? trim( $png_path ) : '';

		if ( '' === $png_path || ! is_readable( $png_path ) || ! function_exists( 'imagecreatefrompng' ) ) {
			return false;
		}

		$frame = $area['frame'] ?? array();
		$fx    = $from_canvas_origin ? absint( $frame['x'] ?? 0 ) : 0;
		$fy    = $from_canvas_origin ? absint( $frame['y'] ?? 0 ) : 0;
		$fw    = absint( $frame['width'] ?? 0 );
		$fh    = absint( $frame['height'] ?? 0 );

		if ( $fw < 1 || $fh < 1 ) {
			return false;
		}

		$source = imagecreatefrompng( $png_path );
		if ( ! $source ) {
			return false;
		}

		imagealphablending( $source, false );
		imagesavealpha( $source, true );

		$sw = imagesx( $source );
		$sh = imagesy( $source );

		$crop = imagecreatetruecolor( $fw, $fh );
		if ( ! $crop ) {
			imagedestroy( $source );
			return false;
		}

		imagealphablending( $crop, false );
		imagesavealpha( $crop, true );
		$transparent = imagecolorallocatealpha( $crop, 0, 0, 0, 127 );
		imagefill( $crop, 0, 0, $transparent );

		if ( $from_canvas_origin ) {
			$copy_w = min( $fw, max( 0, $sw - $fx ) );
			$copy_h = min( $fh, max( 0, $sh - $fy ) );

			imagecopy( $crop, $source, 0, 0, $fx, $fy, $copy_w, $copy_h );
		} else {
			imagecopyresampled( $crop, $source, 0, 0, 0, 0, $fw, $fh, $sw, $sh );
		}

		imagedestroy( $source );

		$mask_path = self::mask_path_from_url( $area['mask'] ?? '' );
		if ( $mask_path ) {
			$mask = self::load_mask_image( $mask_path, $fw, $fh );
			if ( $mask ) {
				for ( $y = 0; $y < $fh; $y++ ) {
					for ( $x = 0; $x < $fw; $x++ ) {
						$ma = ( imagecolorat( $mask, $x, $y ) >> 24 ) & 0x7F;
						if ( $ma >= 127 ) {
							imagesetpixel( $crop, $x, $y, $transparent );
							continue;
						}

						$ca = ( imagecolorat( $crop, $x, $y ) >> 24 ) & 0x7F;
						$na = (int) round( ( $ca / 127 ) * ( ( 127 - $ma ) / 127 ) * 127 );
						$na = max( 0, min( 127, $na ) );
						$rgb = imagecolorat( $crop, $x, $y ) & 0xFFFFFF;
						imagesetpixel( $crop, $x, $y, imagecolorallocatealpha( $crop, ( $rgb >> 16 ) & 0xFF, ( $rgb >> 8 ) & 0xFF, $rgb & 0xFF, $na ) );
					}
				}
				imagedestroy( $mask );
			}
		}

		$ok = imagepng( $crop, $png_path );
		imagedestroy( $crop );

		return (bool) $ok;
	}

	/**
	 * Resolve mask URL to local filesystem path.
	 *
	 * @param string $url Mask URL.
	 * @return string|false
	 */
	private static function mask_path_from_url( $url ) {
		$url = is_string( $url ) ? trim( $url ) : '';
		if ( '' === $url ) {
			return false;
		}

		if ( defined( 'WPP_PLUGIN_URL' ) && 0 === strpos( $url, WPP_PLUGIN_URL ) ) {
			$rel  = ltrim( substr( $url, strlen( WPP_PLUGIN_URL ) ), '/' );
			$path = WPP_PLUGIN_PATH . $rel;
			$path = strtok( $path, '?' );
			return file_exists( $path ) ? $path : false;
		}

		$upload = wp_upload_dir();
		if ( 0 === strpos( $url, $upload['baseurl'] ) ) {
			$path = str_replace( $upload['baseurl'], $upload['basedir'], $url );
			$path = strtok( $path, '?' );
			return file_exists( $path ) ? $path : false;
		}

		return false;
	}

	/**
	 * Load mask image scaled to frame size.
	 *
	 * @param string $path Mask path.
	 * @param int    $w    Target width.
	 * @param int    $h    Target height.
	 * @return resource|\GdImage|false
	 */
	private static function load_mask_image( $path, $w, $h ) {
		$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );

		if ( 'png' === $ext ) {
			$img = imagecreatefrompng( $path );
		} elseif ( in_array( $ext, array( 'jpg', 'jpeg' ), true ) ) {
			$img = imagecreatefromjpeg( $path );
		} elseif ( 'webp' === $ext && function_exists( 'imagecreatefromwebp' ) ) {
			$img = imagecreatefromwebp( $path );
		} else {
			return false;
		}

		if ( ! $img ) {
			return false;
		}

		$scaled = imagecreatetruecolor( $w, $h );
		if ( ! $scaled ) {
			imagedestroy( $img );
			return false;
		}

		imagealphablending( $scaled, false );
		imagesavealpha( $scaled, true );
		$transparent = imagecolorallocatealpha( $scaled, 0, 0, 0, 127 );
		imagefill( $scaled, 0, 0, $transparent );

		imagecopyresampled( $scaled, $img, 0, 0, 0, 0, $w, $h, imagesx( $img ), imagesy( $img ) );
		imagedestroy( $img );

		return $scaled;
	}
}
