<?php
/**
 * Production export diagnostics (when debug mode is on).
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Helpers;

use WooProductPersonalizer\Core\Logger;
use WooProductPersonalizer\Infrastructure\Repository\SettingsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductionDebug
 */
class ProductionDebug {

	/**
	 * @param Logger $logger Logger.
	 * @param string $step   Step name.
	 * @param array  $data   Context.
	 * @return void
	 */
	public static function log( Logger $logger, $step, array $data = array() ) {
		$settings = new SettingsRepository();
		if ( ! $settings->is_debug_enabled() ) {
			return;
		}

		$logger->debug( '[production] ' . $step, $data );
	}

	/**
	 * Copy PNG snapshot into order folder for inspection.
	 *
	 * @param string $source_path Source PNG.
	 * @param string $directory   Order directory.
	 * @param string $label       Snapshot label.
	 * @return string Saved path or empty.
	 */
	public static function snapshot_png( $source_path, $directory, $label ) {
		$settings = new SettingsRepository();
		if ( ! $settings->is_debug_enabled() ) {
			return '';
		}

		$source_path = is_string( $source_path ) ? trim( $source_path ) : '';
		$directory   = is_string( $directory ) ? trim( $directory ) : '';

		if ( '' === $source_path || ! is_readable( $source_path ) || '' === $directory || ! is_dir( $directory ) ) {
			return '';
		}

		$label    = sanitize_file_name( (string) $label );
		$dest     = trailingslashit( $directory ) . 'debug-' . $label . '.png';
		$copied   = copy( $source_path, $dest );

		return $copied ? $dest : '';
	}

	/**
	 * Analyze PNG file for production troubleshooting.
	 *
	 * @param string $path File path.
	 * @return array
	 */
	public static function analyze_png( $path ) {
		$path = is_string( $path ) ? trim( $path ) : '';

		if ( '' === $path || ! is_readable( $path ) ) {
			return array(
				'readable' => false,
				'path'     => $path,
			);
		}

		$info = getimagesize( $path );
		if ( ! is_array( $info ) ) {
			return array(
				'readable' => true,
				'valid'    => false,
				'path'     => $path,
				'bytes'    => filesize( $path ),
			);
		}

		$w = (int) $info[0];
		$h = (int) $info[1];

		$analysis = array(
			'readable' => true,
			'valid'    => true,
			'path'     => $path,
			'bytes'    => filesize( $path ),
			'width_px' => $w,
			'height_px' => $h,
			'mime'     => $info['mime'] ?? '',
		);

		if ( ! function_exists( 'imagecreatefrompng' ) || $w < 1 || $h < 1 ) {
			return $analysis;
		}

		$img = imagecreatefrompng( $path );
		if ( ! $img ) {
			$analysis['gd_load'] = false;
			return $analysis;
		}

		$sample_step = max( 1, (int) floor( max( $w, $h ) / 80 ) );
		$sampled     = 0;
		$opaque      = 0;
		$non_white   = 0;

		for ( $y = 0; $y < $h; $y += $sample_step ) {
			for ( $x = 0; $x < $w; $x += $sample_step ) {
				$rgba = imagecolorat( $img, $x, $y );
				$a    = ( $rgba >> 24 ) & 0x7F;
				$r    = ( $rgba >> 16 ) & 0xFF;
				$g    = ( $rgba >> 8 ) & 0xFF;
				$b    = $rgba & 0xFF;
				++$sampled;

				if ( $a < 120 ) {
					continue;
				}

				++$opaque;
				if ( $r < 250 || $g < 250 || $b < 250 ) {
					++$non_white;
				}
			}
		}

		imagedestroy( $img );

		$analysis['sampled_pixels']      = $sampled;
		$analysis['opaque_pct']        = $sampled > 0 ? round( 100 * $opaque / $sampled, 2 ) : 0;
		$analysis['visible_content_pct'] = $sampled > 0 ? round( 100 * $non_white / $sampled, 2 ) : 0;

		return $analysis;
	}
}
