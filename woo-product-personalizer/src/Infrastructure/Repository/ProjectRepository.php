<?php
/**
 * Order project persistence.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Infrastructure\Repository;

use WooProductPersonalizer\Core\Logger;
use WooProductPersonalizer\Helpers\ExportAreaHelper;
use WooProductPersonalizer\Helpers\LayoutConfigLoader;
use WooProductPersonalizer\Helpers\ProductionDebug;
use WooProductPersonalizer\Infrastructure\Generator\GeneratorManager;
use WooProductPersonalizer\Infrastructure\Generator\ProjectPdfGenerator;
use WooProductPersonalizer\Infrastructure\Generator\TextSvgGenerator;
use WooProductPersonalizer\Helpers\PersonalizationSummaryHelper;
use WooProductPersonalizer\Infrastructure\Repository\LayoutRepository;
use WooProductPersonalizer\Helpers\UploadUrlValidator;
use WooProductPersonalizer\Infrastructure\Uploads\UploadsManager;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProjectRepository
 */
class ProjectRepository {

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
	 * Generator.
	 *
	 * @var GeneratorManager
	 */
	private $generator;

	/**
	 * Settings.
	 *
	 * @var SettingsRepository
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param UploadsManager $uploads Uploads.
	 * @param Logger         $logger   Logger.
	 */
	public function __construct( UploadsManager $uploads, Logger $logger ) {
		$this->uploads   = $uploads;
		$this->logger    = $logger;
		$this->generator = new GeneratorManager( $uploads, $logger );
		$this->settings  = new SettingsRepository();
	}

	/**
	 * Save order project package.
	 *
	 * @param int    $order_id     Order ID.
	 * @param int    $item_id      Item ID.
	 * @param array  $state        Project state.
	 * @param string $preview_data        Full preview from browser.
	 * @param int    $product_id          Product ID.
	 * @param int    $layout_id           Layout ID.
	 * @param string $layers_preview_data Layers-only preview (optional).
	 * @param string $text_svg_source     Text-only SVG from browser or path (optional).
	 * @param string $project_pdf_source  Composite PNG for PDF (optional).
	 * @return array{json: string, production: string, production_url: string, layers_production?: string, layers_production_url?: string, text_svg?: string, text_svg_url?: string, project_pdf?: string, project_pdf_url?: string}|false
	 */
	public function save_order_project( $order_id, $item_id, array $state, $preview_data, $product_id, $layout_id, $layers_preview_data = '', $text_svg_source = '', $project_pdf_source = '' ) {
		$dir = $this->uploads->create_order_directory( $order_id );
		if ( ! $dir ) {
			return false;
		}

		$attachments_dir = $dir . '/attachments';
		$this->copy_attachments( $state, $attachments_dir );

		$project = array(
			'plugin_version' => WPP_VERSION,
			'created_at'     => gmdate( 'c' ),
			'order_id'       => $order_id,
			'item_id'        => $item_id,
			'product_id'     => $product_id,
			'layout_id'      => $layout_id,
			'personalized'   => true,
			'acceptance'     => $state['acceptance'] ?? array(),
			'text_fields'    => $this->normalize_text_fields( $state, $layout_id ),
			'image_fields'   => $this->normalize_image_fields( $state, $attachments_dir ),
		);

		$json_path = $dir . '/project.json';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $json_path, wp_json_encode( $project, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );

		$production = $this->generator->generate( $order_id, $item_id, $preview_data, $dir, 'projekt' );

		$result = array(
			'json'             => $json_path,
			'production'       => $production['path'] ?? '',
			'production_url'   => $production['url'] ?? '',
		);

		$layout_config = LayoutConfigLoader::load( $layout_id );
		$export_area   = ExportAreaHelper::get_primary( $layout_config );

		if ( '' !== trim( (string) $layers_preview_data ) ) {
			$layers = $this->generator->generate( $order_id, $item_id, $layers_preview_data, $dir, 'warstwy' );
			$result['layers_production']     = $layers['path'] ?? '';
			$result['layers_production_url'] = $layers['url'] ?? '';

			if ( $export_area && ! empty( $result['layers_production'] ) && ExportAreaHelper::png_is_full_canvas( $result['layers_production'], $layout_config ) ) {
				ExportAreaHelper::clip_png_to_area( $result['layers_production'], $export_area, true );
			}

			$this->apply_output_dpi_to_png( $result['layers_production'] ?? '', $this->get_output_dpi() );
		}

		$text_svg = $this->save_text_svg( $order_id, $item_id, $state, $layout_id, $dir, $text_svg_source );
		if ( ! empty( $text_svg['path'] ) ) {
			$result['text_svg']     = $text_svg['path'];
			$result['text_svg_url'] = $text_svg['url'];
		}

		if ( '' !== trim( (string) $project_pdf_source ) ) {
			ProductionDebug::log(
				$this->logger,
				'project.save_pdf_requested',
				array(
					'order_id'  => $order_id,
					'item_id'   => $item_id,
					'layout_id' => $layout_id,
					'source'    => is_string( $project_pdf_source ) ? substr( $project_pdf_source, 0, 120 ) : '',
				)
			);

			$pdf_gen = new ProjectPdfGenerator( $this->uploads, $this->logger );
			$pdf     = $pdf_gen->save(
				$order_id,
				$item_id,
				$project_pdf_source,
				$dir,
				$layout_id,
				$this->get_output_dpi()
			);

			if ( ! empty( $pdf['path'] ) ) {
				$result['project_pdf']     = $pdf['path'];
				$result['project_pdf_url'] = $pdf['url'];
			}
		}

		return $result;
	}

	/**
	 * Whether PNG dimensions match layout canvas (full-stage export).
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
	 * Persist text layers as SVG when the layout has text fields.
	 *
	 * @param int    $order_id        Order ID.
	 * @param int    $item_id         Item ID.
	 * @param array  $state           Project state.
	 * @param int    $layout_id       Layout ID.
	 * @param string $dir             Order directory.
	 * @param string $text_svg_source Browser SVG, file path, or empty for server build.
	 * @return array{path: string, url: string}
	 */
	private function save_text_svg( $order_id, $item_id, array $state, $layout_id, $dir, $text_svg_source ) {
		if ( empty( $state['text_fields'] ) || ! is_array( $state['text_fields'] ) ) {
			return array(
				'path' => '',
				'url'  => '',
			);
		}

		$has_content = false;
		foreach ( $state['text_fields'] as $raw ) {
			$value = is_array( $raw ) ? (string) ( $raw['value'] ?? '' ) : (string) $raw;
			if ( '' !== trim( $value ) ) {
				$has_content = true;
				break;
			}
		}

		if ( ! $has_content ) {
			return array(
				'path' => '',
				'url'  => '',
			);
		}

		$generator = new TextSvgGenerator( $this->uploads, $this->logger );
		$source    = trim( (string) $text_svg_source );
		$dpi       = $this->get_output_dpi();

		if ( '' !== $source ) {
			return $generator->save( $order_id, $item_id, $source, $dir, 'tekst', $layout_id, $state, $dpi );
		}

		return $generator->generate_from_state( $order_id, $item_id, $state, $layout_id, $dir, $dpi );
	}

	/**
	 * Output DPI for production exports.
	 *
	 * @return int
	 */
	private function get_output_dpi() {
		return min( 1200, max( 72, absint( $this->settings->get( 'production_export_dpi', 300 ) ) ) );
	}

	/**
	 * Write PNG pHYs chunk to store target DPI metadata.
	 *
	 * @param string $path PNG file path.
	 * @param int    $dpi  DPI.
	 * @return void
	 */
	private function apply_output_dpi_to_png( $path, $dpi ) {
		$path = is_string( $path ) ? trim( $path ) : '';
		if ( '' === $path || ! is_readable( $path ) || $dpi < 1 ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$png = file_get_contents( $path );
		if ( ! is_string( $png ) || strlen( $png ) < 33 || "\x89PNG\x0D\x0A\x1A\x0A" !== substr( $png, 0, 8 ) ) {
			return;
		}

		$ppm = (int) round( $dpi / 0.0254 ); // pixels per meter.
		$data = pack( 'NNC', $ppm, $ppm, 1 ); // unit=meter.
		$type = 'pHYs';
		$len  = pack( 'N', strlen( $data ) );
		$crc  = pack( 'N', crc32( $type . $data ) );
		$chunk = $len . $type . $data . $crc;

		$offset = 8 + 4 + 4 + 13 + 4; // signature + IHDR chunk.
		$existing_phys_pos = strpos( $png, 'pHYs', 8 );
		if ( false !== $existing_phys_pos ) {
			$chunk_start = $existing_phys_pos - 4;
			$old_len = unpack( 'Nlen', substr( $png, $chunk_start, 4 ) );
			$remove_len = 12 + (int) ( $old_len['len'] ?? 0 );
			$png = substr( $png, 0, $chunk_start ) . substr( $png, $chunk_start + $remove_len );
		}

		$png = substr( $png, 0, $offset ) . $chunk . substr( $png, $offset );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $path, $png );
	}

	/**
	 * Copy uploaded sources into attachments folder.
	 *
	 * @param array  $state State.
	 * @param string $dir   Target dir.
	 * @return void
	 */
	private function copy_attachments( array $state, $dir ) {
		if ( empty( $state['image_fields'] ) || ! is_array( $state['image_fields'] ) ) {
			return;
		}

		$index = 1;
		foreach ( $state['image_fields'] as $slot_id => $field ) {
			$source = $field['source'] ?? '';
			if ( empty( $source ) ) {
				continue;
			}

			$path = $this->url_to_path( $source );
			if ( $path && file_exists( $path ) ) {
				$ext  = pathinfo( $path, PATHINFO_EXTENSION );
				$dest = $dir . '/original_' . $index . '.' . $ext;
				copy( $path, $dest );
				++$index;
			}
		}
	}

	/**
	 * Normalize text fields for JSON export.
	 *
	 * @param array $state     State.
	 * @param int   $layout_id Layout post ID.
	 * @return array
	 */
	private function normalize_text_fields( array $state, $layout_id = 0 ) {
		$out = array();
		if ( empty( $state['text_fields'] ) || ! is_array( $state['text_fields'] ) ) {
			return $out;
		}

		$field_map = $this->text_fields_map_from_layout( $layout_id );

		foreach ( $state['text_fields'] as $id => $value ) {
			$field  = $field_map[ $id ] ?? array( 'style' => array() );
			$parsed = PersonalizationSummaryHelper::parse_text_field( $value, $field );

			$entry = array(
				'id'         => $id,
				'value'      => $parsed['value'],
				'fontSize'   => $parsed['font_size'],
				'fontFamily' => $parsed['font_family'],
			);

			if ( is_array( $value ) ) {
				if ( isset( $value['offsetX'] ) ) {
					$entry['offsetX'] = (float) $value['offsetX'];
				}
				if ( isset( $value['offsetY'] ) ) {
					$entry['offsetY'] = (float) $value['offsetY'];
				}
			}

			$out[] = $entry;
		}

		return $out;
	}

	/**
	 * Map text field id => config from layout post.
	 *
	 * @param int $layout_id Layout ID.
	 * @return array<string, array>
	 */
	private function text_fields_map_from_layout( $layout_id ) {
		$map = array();
		if ( ! $layout_id ) {
			return $map;
		}

		$raw = get_post_meta( (int) $layout_id, LayoutRepository::META_CONFIG, true );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return $map;
		}

		$config = json_decode( $raw, true );
		if ( ! is_array( $config ) || empty( $config['text_fields'] ) ) {
			return $map;
		}

		foreach ( $config['text_fields'] as $field ) {
			if ( ! empty( $field['id'] ) ) {
				$map[ $field['id'] ] = $field;
			}
		}

		return $map;
	}

	/**
	 * Normalize image fields for JSON export.
	 *
	 * @param array  $state State.
	 * @param string $dir   Attachments dir.
	 * @return array
	 */
	private function normalize_image_fields( array $state, $dir ) {
		$out = array();
		if ( empty( $state['image_fields'] ) ) {
			return $out;
		}
		foreach ( $state['image_fields'] as $id => $field ) {
			$out[] = array(
				'id'        => $id,
				'source'    => $field['source'] ?? '',
				'transform' => $field['transform'] ?? array(),
			);
		}
		return $out;
	}

	/**
	 * Convert upload URL to path.
	 *
	 * @param string $url URL.
	 * @return string|false
	 */
	private function url_to_path( $url ) {
		$allowed = UploadUrlValidator::is_allowed_customer_image_url( $url, null )
			|| $this->is_plugin_order_file_url( $url )
			|| $this->is_wpp_temp_url( $url );

		if ( ! $allowed ) {
			return false;
		}

		$upload = wp_upload_dir();
		if ( 0 === strpos( $url, $upload['baseurl'] ) ) {
			$path = str_replace( $upload['baseurl'], $upload['basedir'], $url );
			$path = strtok( $path, '?' );
			return file_exists( $path ) ? $path : false;
		}

		if ( defined( 'WPP_PLUGIN_URL' ) && 0 === strpos( $url, WPP_PLUGIN_URL ) ) {
			$rel  = ltrim( substr( $url, strlen( WPP_PLUGIN_URL ) ), '/' );
			$path = WPP_PLUGIN_PATH . $rel;
			$path = strtok( $path, '?' );
			return file_exists( $path ) ? $path : false;
		}

		return false;
	}

	/**
	 * Whether URL points to a generated file inside an order folder.
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	private function is_plugin_order_file_url( $url ) {
		$upload = wp_upload_dir();
		$prefix = trailingslashit( $upload['baseurl'] ) . WPP_UPLOADS_SUBDIR . '/orders/';

		return 0 === strpos( $url, $prefix );
	}

	/**
	 * Whether URL is under the plugin temp uploads directory.
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	private function is_wpp_temp_url( $url ) {
		$upload = wp_upload_dir();
		$prefix = trailingslashit( $upload['baseurl'] ) . WPP_UPLOADS_SUBDIR . '/temp/';

		return 0 === strpos( $url, $prefix );
	}
}
