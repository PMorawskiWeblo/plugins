<?php
/**
 * PDF production file (graphics + text composite in export area).
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Infrastructure\Generator;

use WooProductPersonalizer\Core\Logger;
use WooProductPersonalizer\Helpers\ExportAreaHelper;
use WooProductPersonalizer\Helpers\LayoutConfigLoader;
use WooProductPersonalizer\Helpers\ProductionDebug;
use WooProductPersonalizer\Infrastructure\Uploads\UploadsManager;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProjectPdfGenerator
 */
class ProjectPdfGenerator {

	/**
	 * Uploads.
	 *
	 * @var UploadsManager
	 */
	private $uploads;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param UploadsManager $uploads Uploads.
	 * @param Logger         $logger  Logger.
	 */
	public function __construct( UploadsManager $uploads, Logger $logger ) {
		$this->uploads = $uploads;
		$this->logger  = $logger;
	}

	/**
	 * Save PDF from PNG data URL, file path, or raw PNG bytes path.
	 *
	 * @param int    $order_id     Order ID.
	 * @param int    $item_id      Line item ID.
	 * @param string $image_source Data URL, path, or URL.
	 * @param string $directory    Target directory.
	 * @param int    $layout_id    Layout ID for dimensions/DPI.
	 * @param int    $dpi          Output DPI metadata.
	 * @return array{path: string, url: string}
	 */
	public function save( $order_id, $item_id, $image_source, $directory, $layout_id = 0, $dpi = 300 ) {
		$source_hint = is_string( $image_source ) ? substr( $image_source, 0, 120 ) : '';

		ProductionDebug::log(
			$this->logger,
			'pdf.start',
			array(
				'order_id'  => $order_id,
				'item_id'   => $item_id,
				'layout_id' => $layout_id,
				'source'    => $source_hint,
			)
		);

		$png_path = $this->materialize_png( $image_source, $directory, $order_id, $item_id );

		if ( ! $png_path || ! is_readable( $png_path ) ) {
			ProductionDebug::log( $this->logger, 'pdf.error.materialize_failed', array( 'source' => $source_hint ) );
			return array(
				'path' => '',
				'url'  => '',
			);
		}

		ProductionDebug::snapshot_png( $png_path, $directory, 'pdf-01-materialized' );
		ProductionDebug::log( $this->logger, 'pdf.materialized', ProductionDebug::analyze_png( $png_path ) );

		$config = LayoutConfigLoader::load( $layout_id );
		$area   = ExportAreaHelper::get_primary( $config );

		ProductionDebug::log(
			$this->logger,
			'pdf.layout',
			array(
				'layout_id'   => $layout_id,
				'project_pdf' => $config['project_pdf'] ?? array(),
				'export_area' => $area ? ( $area['frame'] ?? array() ) : null,
				'canvas'      => $config['canvas'] ?? array(),
			)
		);

		if ( $area && ExportAreaHelper::png_is_full_canvas( $png_path, $config ) ) {
			ExportAreaHelper::clip_png_to_area( $png_path, $area, true );
			ProductionDebug::snapshot_png( $png_path, $directory, 'pdf-02-clipped' );
			ProductionDebug::log( $this->logger, 'pdf.clipped_to_export_area', ProductionDebug::analyze_png( $png_path ) );
		}

		$dpi = max( 72, min( 1200, absint( $dpi ) ) );

		$pdf_cm = ExportAreaHelper::get_project_pdf_size_cm( $config );
		if ( $pdf_cm ) {
			ExportAreaHelper::resize_png_to_pdf_cm( $png_path, $pdf_cm['width_cm'], $pdf_cm['height_cm'], $dpi );
			$this->apply_png_dpi_metadata( $png_path, $dpi );
			ProductionDebug::snapshot_png( $png_path, $directory, 'pdf-03-resized-to-cm' );
			ProductionDebug::log(
				$this->logger,
				'pdf.resized_to_cm',
				array_merge(
					array(
						'target_cm' => $pdf_cm,
						'dpi'       => $dpi,
						'target_px' => ExportAreaHelper::pdf_page_pixel_size( $pdf_cm['width_cm'], $pdf_cm['height_cm'], $dpi ),
					),
					ProductionDebug::analyze_png( $png_path )
				)
			);
		} else {
			ProductionDebug::log( $this->logger, 'pdf.skip_cm_resize', array( 'reason' => 'project_pdf width/height not both set' ) );
		}

		$size = $this->png_dimensions( $png_path );
		if ( ! $size ) {
			ProductionDebug::log( $this->logger, 'pdf.error.invalid_png_dimensions', array( 'path' => $png_path ) );
			return array(
				'path' => '',
				'url'  => '',
			);
		}

		$page_pts = $this->resolve_page_size_points( $config, $size, $dpi );
		$analysis = ProductionDebug::analyze_png( $png_path );

		if ( ( $analysis['visible_content_pct'] ?? 0 ) < 1 ) {
			$this->logger->warning(
				'Project PDF source PNG appears empty (transparent or white).',
				array(
					'order_id' => $order_id,
					'item_id'  => $item_id,
					'analysis' => $analysis,
				)
			);
		}

		ProductionDebug::log(
			$this->logger,
			'pdf.page',
			array(
				'px'        => $size,
				'page_pt'   => $page_pts,
				'page_cm'   => array(
					round( $page_pts[0] / 72 * 2.54, 2 ),
					round( $page_pts[1] / 72 * 2.54, 2 ),
				),
				'analysis'  => $analysis,
			)
		);

		$filename = absint( $order_id ) . '_item_' . ( $item_id ? absint( $item_id ) : '0' ) . '_projekt-pdf.pdf';
		$pdf_path = trailingslashit( $directory ) . $filename;
		$pdf_url  = trailingslashit( $this->uploads->order_url( $order_id ) ) . $filename;

		$written = false;
		$method  = '';

		if ( $pdf_cm ) {
			$written = $this->write_pdf_embed_jpeg( $png_path, $pdf_path, $size[0], $size[1], $page_pts[0], $page_pts[1], $directory );
			$method  = 'gd_jpeg';
		}
		if ( ! $written ) {
			$written = $this->write_pdf_with_imagick( $png_path, $pdf_path, $dpi, $page_pts[0], $page_pts[1] );
			$method  = 'imagick';
		}
		if ( ! $written ) {
			$written = $this->write_pdf_embed_jpeg( $png_path, $pdf_path, $size[0], $size[1], $page_pts[0], $page_pts[1], $directory );
			$method  = 'gd_jpeg_fallback';
		}

		if ( $written ) {
			$effective_dpi_w = $page_pts[0] > 0 ? round( $size[0] * 72 / $page_pts[0], 1 ) : 0;
			$effective_dpi_h = $page_pts[1] > 0 ? round( $size[1] * 72 / $page_pts[1], 1 ) : 0;

			ProductionDebug::log(
				$this->logger,
				'pdf.done',
				array(
					'method'         => $method,
					'pdf_path'       => $pdf_path,
					'pdf_bytes'      => file_exists( $pdf_path ) ? filesize( $pdf_path ) : 0,
					'image_px'       => $size,
					'configured_dpi' => $dpi,
					'effective_dpi'  => array( $effective_dpi_w, $effective_dpi_h ),
				)
			);
			return array(
				'path' => $pdf_path,
				'url'  => $pdf_url,
			);
		}

		$this->logger->warning(
			'Project PDF could not be generated.',
			array(
				'order_id' => $order_id,
				'item_id'  => $item_id,
				'analysis' => $analysis,
			)
		);

		return array(
			'path' => '',
			'url'  => '',
		);
	}

	/**
	 * @param string $image_source Source.
	 * @param string $directory    Directory.
	 * @param int    $order_id     Order ID.
	 * @param int    $item_id      Item ID.
	 * @return string|false
	 */
	private function materialize_png( $image_source, $directory, $order_id, $item_id ) {
		$source = trim( (string) $image_source );

		if ( '' === $source ) {
			return false;
		}

		if ( is_readable( $source ) && false !== strpos( $source, '.png' ) ) {
			return $source;
		}

		if ( preg_match( '#^https?://#i', $source ) ) {
			$path = $this->url_to_local_path( $source );
			if ( $path && is_readable( $path ) ) {
				return $path;
			}
		}

		if ( preg_match( '#^data:image/(png|jpeg|jpg|webp);base64,#i', $source ) ) {
			$binary = base64_decode( preg_replace( '#^data:image/[^;]+;base64,#i', '', $source ), true );
			if ( false === $binary || '' === $binary ) {
				return false;
			}

			$temp = trailingslashit( $directory ) . sprintf( 'projekt-pdf-src-%d-%d.png', absint( $order_id ), absint( $item_id ) );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			if ( false === file_put_contents( $temp, $binary ) ) {
				return false;
			}

			return $temp;
		}

		return false;
	}

	/**
	 * @param string $png_path PNG path.
	 * @param string $pdf_path PDF path.
	 * @param int   $dpi       DPI metadata.
	 * @param float $page_w_pt Page width in PDF points.
	 * @param float $page_h_pt Page height in PDF points.
	 * @return bool
	 */
	private function write_pdf_with_imagick( $png_path, $pdf_path, $dpi, $page_w_pt, $page_h_pt ) {
		if ( ! class_exists( '\Imagick' ) ) {
			return false;
		}

		try {
			$im = new \Imagick( $png_path );
			$im->setBackgroundColor( new \ImagickPixel( 'white' ) );
			$im = $im->mergeImageLayers( \Imagick::LAYERMETHOD_FLATTEN );
			$im->setImageUnits( \Imagick::RESOLUTION_PIXELSPERINCH );
			$im->setImageResolution( $dpi, $dpi );

			$page_w_px = max( 1, (int) round( $page_w_pt / 72 * $dpi ) );
			$page_h_px = max( 1, (int) round( $page_h_pt / 72 * $dpi ) );
			$im->resizeImage( $page_w_px, $page_h_px, \Imagick::FILTER_LANCZOS, 1 );
			$im->setImagePage( $page_w_px, $page_h_px, 0, 0 );

			$im->setImageFormat( 'pdf' );
			$ok = $im->writeImage( $pdf_path );
			$im->clear();
			$im->destroy();

			return $ok && file_exists( $pdf_path );
		} catch ( \Throwable $e ) {
			$this->logger->debug( 'Imagick PDF failed: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Minimal single-page PDF with embedded JPEG (GD fallback).
	 *
	 * @param string $png_path PNG path.
	 * @param string $pdf_path PDF path.
	 * @param int   $width_px  Image width px.
	 * @param int   $height_px Image height px.
	 * @param float  $page_w_pt Page width in PDF points.
	 * @param float  $page_h_pt Page height in PDF points.
	 * @param string $debug_dir Order folder for debug snapshots (optional).
	 * @return bool
	 */
	private function write_pdf_embed_jpeg( $png_path, $pdf_path, $width_px, $height_px, $page_w_pt, $page_h_pt, $debug_dir = '' ) {
		if ( ! function_exists( 'imagecreatefrompng' ) || ! function_exists( 'imagejpeg' ) ) {
			ProductionDebug::log( $this->logger, 'pdf.error.gd_missing', array() );
			return false;
		}

		$flat = $this->flatten_png_to_opaque_rgb( $png_path, $width_px, $height_px );
		if ( ! $flat ) {
			ProductionDebug::log( $this->logger, 'pdf.error.flatten_failed', array( 'path' => $png_path ) );
			return false;
		}

		$debug_dir = is_string( $debug_dir ) ? trim( $debug_dir ) : '';
		if ( '' !== $debug_dir && is_dir( $debug_dir ) && function_exists( 'imagepng' ) ) {
			$flat_debug = trailingslashit( $debug_dir ) . 'debug-pdf-04-flattened.png';
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@imagepng( $flat, $flat_debug );
			ProductionDebug::log( $this->logger, 'pdf.flattened', ProductionDebug::analyze_png( $flat_debug ) );
		}

		$jpeg_tmp = $pdf_path . '.jpg';
		$ok       = imagejpeg( $flat, $jpeg_tmp, 95 );
		imagedestroy( $flat );

		if ( ! $ok || ! is_readable( $jpeg_tmp ) ) {
			return false;
		}

		$jpeg_data = file_get_contents( $jpeg_tmp );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		unlink( $jpeg_tmp );

		if ( ! is_string( $jpeg_data ) || '' === $jpeg_data ) {
			return false;
		}

		$w_pt = round( $page_w_pt, 2 );
		$h_pt = round( $page_h_pt, 2 );

		$objects  = array();
		$objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
		$objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
		$objects[] = sprintf(
			"3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Contents 4 0 R /Resources << /XObject << /Im1 5 0 R >> >> >>\nendobj\n",
			$w_pt,
			$h_pt
		);
		$stream    = sprintf( "q\n%.2F 0 0 %.2F 0 0 cm\n/Im1 Do\nQ\n", $w_pt, $h_pt );
		$objects[] = "4 0 obj\n<< /Length " . strlen( $stream ) . " >>\nstream\n" . $stream . "\nendstream\nendobj\n";
		$objects[] = "5 0 obj\n<< /Type /XObject /Subtype /Image /Width {$width_px} /Height {$height_px}"
			. ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen( $jpeg_data )
			. " >>\nstream\n" . $jpeg_data . "\nendstream\nendobj\n";

		$pdf     = "%PDF-1.4\n";
		$offsets = array( 0 );
		foreach ( $objects as $obj ) {
			$offsets[] = strlen( $pdf );
			$pdf      .= $obj;
		}

		$xref_pos = strlen( $pdf );
		$pdf     .= "xref\n0 " . count( $offsets ) . "\n";
		$pdf     .= "0000000000 65535 f \n";
		for ( $i = 1; $i < count( $offsets ); $i++ ) {
			$pdf .= sprintf( '%010d 00000 n %s', $offsets[ $i ], "\n" );
		}
		$pdf .= "trailer\n<< /Size " . count( $offsets ) . " /Root 1 0 R >>\n";
		$pdf .= 'startxref' . "\n" . $xref_pos . "\n%%EOF";

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		return false !== file_put_contents( $pdf_path, $pdf ) && file_exists( $pdf_path );
	}

	/**
	 * PDF MediaBox in points from layout cm or image pixels at DPI.
	 *
	 * @param array $config   Layout config.
	 * @param array $png_size [width, height] in px.
	 * @param int   $dpi      Output DPI.
	 * @return array{0: float, 1: float}
	 */
	private function resolve_page_size_points( array $config, array $png_size, $dpi ) {
		$pdf_cm = ExportAreaHelper::get_project_pdf_size_cm( $config );

		if ( $pdf_cm ) {
			return array(
				ExportAreaHelper::cm_to_pdf_points( $pdf_cm['width_cm'] ),
				ExportAreaHelper::cm_to_pdf_points( $pdf_cm['height_cm'] ),
			);
		}

		$dpi = max( 72, min( 1200, absint( $dpi ) ) );

		return array(
			round( $png_size[0] * 72 / $dpi, 2 ),
			round( $png_size[1] * 72 / $dpi, 2 ),
		);
	}

	/**
	 * @param string $path PNG path.
	 * @return array{0: int, 1: int}|false
	 */
	private function png_dimensions( $path ) {
		$info = getimagesize( $path );
		if ( ! is_array( $info ) ) {
			return false;
		}

		return array( (int) $info[0], (int) $info[1] );
	}

	/**
	 * Flatten transparent PNG onto white RGB canvas (fixes empty PDF from alpha).
	 *
	 * @param string $png_path PNG path.
	 * @param int    $width_px Target width.
	 * @param int    $height_px Target height.
	 * @return resource|\GdImage|false
	 */
	private function flatten_png_to_opaque_rgb( $png_path, $width_px, $height_px ) {
		$src = imagecreatefrompng( $png_path );
		if ( ! $src ) {
			return false;
		}

		$sw = imagesx( $src );
		$sh = imagesy( $src );

		$flat = imagecreatetruecolor( $width_px, $height_px );
		if ( ! $flat ) {
			imagedestroy( $src );
			return false;
		}

		imagealphablending( $flat, false );
		imagesavealpha( $flat, false );

		$white = imagecolorallocate( $flat, 255, 255, 255 );
		imagefilledrectangle( $flat, 0, 0, max( 0, $width_px - 1 ), max( 0, $height_px - 1 ), $white );

		imagealphablending( $flat, true );
		imagecopyresampled( $flat, $src, 0, 0, 0, 0, $width_px, $height_px, $sw, $sh );
		imagedestroy( $src );

		return $flat;
	}

	/**
	 * Write PNG pHYs chunk for target DPI (print metadata).
	 *
	 * @param string $path PNG path.
	 * @param int    $dpi  DPI.
	 * @return void
	 */
	private function apply_png_dpi_metadata( $path, $dpi ) {
		$path = is_string( $path ) ? trim( $path ) : '';
		if ( '' === $path || ! is_readable( $path ) || $dpi < 1 ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$png = file_get_contents( $path );
		if ( ! is_string( $png ) || strlen( $png ) < 33 || "\x89PNG\x0D\x0A\x1A\x0A" !== substr( $png, 0, 8 ) ) {
			return;
		}

		$ppm  = (int) round( $dpi / 0.0254 );
		$data = pack( 'NNC', $ppm, $ppm, 1 );
		$type = 'pHYs';
		$len  = pack( 'N', strlen( $data ) );
		$crc  = pack( 'N', crc32( $type . $data ) );
		$chunk = $len . $type . $data . $crc;

		$offset = 8 + 4 + 4 + 13 + 4;
		$existing_phys_pos = strpos( $png, 'pHYs', 8 );
		if ( false !== $existing_phys_pos ) {
			$chunk_start = $existing_phys_pos - 4;
			$old_len     = unpack( 'Nlen', substr( $png, $chunk_start, 4 ) );
			$remove_len  = 12 + (int) ( $old_len['len'] ?? 0 );
			$png         = substr( $png, 0, $chunk_start ) . substr( $png, $chunk_start + $remove_len );
		}

		$png = substr( $png, 0, $offset ) . $chunk . substr( $png, $offset );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $path, $png );
	}

	/**
	 * Whether PNG still uses full layout canvas dimensions (needs server clip).
	 *
	 * @param string $path   PNG path.
	 * @param array  $config Layout config.
	 * @return bool
	 */
	private function png_matches_canvas_size( $path, array $config ) {
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
	 * @param string $url URL.
	 * @return string|false
	 */
	private function url_to_local_path( $url ) {
		$upload = wp_upload_dir();
		if ( 0 === strpos( $url, $upload['baseurl'] ) ) {
			$path = str_replace( $upload['baseurl'], $upload['basedir'], $url );
			$path = strtok( $path, '?' );
			return file_exists( $path ) ? $path : false;
		}

		return false;
	}
}
