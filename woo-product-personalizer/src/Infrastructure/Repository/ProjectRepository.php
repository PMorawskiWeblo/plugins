<?php
/**
 * Order project persistence.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Infrastructure\Repository;

use WooProductPersonalizer\Core\Logger;
use WooProductPersonalizer\Infrastructure\Generator\GeneratorManager;
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
	 * Constructor.
	 *
	 * @param UploadsManager $uploads Uploads.
	 * @param Logger         $logger   Logger.
	 */
	public function __construct( UploadsManager $uploads, Logger $logger ) {
		$this->uploads   = $uploads;
		$this->logger    = $logger;
		$this->generator = new GeneratorManager( $uploads, $logger );
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
	 * @return array{json: string, production: string, production_url: string, layers_production?: string, layers_production_url?: string}|false
	 */
	public function save_order_project( $order_id, $item_id, array $state, $preview_data, $product_id, $layout_id, $layers_preview_data = '' ) {
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

		if ( '' !== trim( (string) $layers_preview_data ) ) {
			$layers = $this->generator->generate( $order_id, $item_id, $layers_preview_data, $dir, 'warstwy' );
			$result['layers_production']      = $layers['path'] ?? '';
			$result['layers_production_url']  = $layers['url'] ?? '';
		}

		return $result;
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
