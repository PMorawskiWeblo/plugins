<?php
/**
 * Persist cart personalization previews as files instead of base64 in session.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Infrastructure\Uploads;

use WooProductPersonalizer\Core\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Class CartPreviewStorage
 */
class CartPreviewStorage {

	const SUBDIR           = 'cart-previews';
	const THUMB_MAX_WIDTH  = 480;
	const THUMB_QUALITY    = 82;
	const FULL_FILENAME    = 'full.png';
	const LAYERS_FILENAME  = 'layers.png';
	const TEXT_FILENAME    = 'text.svg';
	const THUMB_FILENAME   = 'preview.jpg';

	/**
	 * Uploads manager.
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
	 * Store preview from a canvas data URL.
	 *
	 * @param string $data_url Data URL (png/jpeg/webp).
	 * @return array{id: string, thumb_url: string, full_url: string, full_path: string}|false
	 */
	public function store_from_data_url( $data_url ) {
		$binary = $this->decode_data_url( $data_url );

		if ( false === $binary ) {
			return false;
		}

		$id  = wp_generate_password( 32, false, false );
		$dir = $this->preview_dir( $id );

		if ( ! wp_mkdir_p( $dir ) ) {
			$this->logger->error( 'Failed to create cart preview directory.', array( 'id' => $id ) );
			return false;
		}

		$full_path = trailingslashit( $dir ) . self::FULL_FILENAME;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === file_put_contents( $full_path, $binary ) ) {
			$this->logger->error( 'Failed to write cart preview file.', array( 'id' => $id ) );
			$this->delete( $id );
			return false;
		}

		$thumb_path = trailingslashit( $dir ) . self::THUMB_FILENAME;
		$thumb_ok   = $this->create_thumbnail( $full_path, $thumb_path );

		$full_url  = $this->preview_file_url( $id, self::FULL_FILENAME );
		$thumb_url = $thumb_ok ? $this->preview_file_url( $id, self::THUMB_FILENAME ) : $full_url;

		return array(
			'id'        => $id,
			'thumb_url' => $thumb_url,
			'full_url'  => $full_url,
			'full_path' => $full_path,
		);
	}

	/**
	 * Store layers-only preview (photos + text, no background) in an existing preview folder.
	 *
	 * @param string $id       Preview ID from store_from_data_url().
	 * @param string $data_url Canvas data URL.
	 * @return array{path: string, url: string}|false
	 */
	public function store_layers_from_data_url( $id, $data_url ) {
		$binary = $this->decode_data_url( $data_url );

		if ( false === $binary ) {
			return false;
		}

		$dir = $this->find_preview_dir( $id );

		if ( ! $dir ) {
			return false;
		}

		$path = trailingslashit( $dir ) . self::LAYERS_FILENAME;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === file_put_contents( $path, $binary ) ) {
			$this->logger->error( 'Failed to write cart layers preview file.', array( 'id' => $id ) );
			return false;
		}

		return array(
			'path' => $path,
			'url'  => $this->preview_file_url( $id, self::LAYERS_FILENAME ),
		);
	}

	/**
	 * Store text-only SVG in an existing preview folder.
	 *
	 * @param string $id  Preview ID from store_from_data_url().
	 * @param string $svg Raw SVG document.
	 * @return array{path: string, url: string}|false
	 */
	public function store_text_svg( $id, $svg ) {
		$svg = is_string( $svg ) ? trim( $svg ) : '';

		if ( '' === $svg || ! preg_match( '#^\s*<\?xml#i', $svg ) && ! preg_match( '#^\s*<svg#i', $svg ) ) {
			return false;
		}

		$dir = $this->find_preview_dir( $id );

		if ( ! $dir ) {
			return false;
		}

		$path = trailingslashit( $dir ) . self::TEXT_FILENAME;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === file_put_contents( $path, $svg ) ) {
			$this->logger->error( 'Failed to write cart text SVG file.', array( 'id' => $id ) );
			return false;
		}

		return array(
			'path' => $path,
			'url'  => $this->preview_file_url( $id, self::TEXT_FILENAME ),
		);
	}

	/**
	 * Absolute path to the text SVG preview file.
	 *
	 * @param string $id Preview ID.
	 * @return string|false
	 */
	public function get_text_path( $id ) {
		$dir = $this->find_preview_dir( $id );

		if ( ! $dir ) {
			return false;
		}

		$path = trailingslashit( $dir ) . self::TEXT_FILENAME;

		return is_readable( $path ) ? $path : false;
	}

	/**
	 * Resolve text SVG file for order production.
	 *
	 * @param string $preview_id  Stored preview ID.
	 * @param string $preview_url Text SVG URL from order meta.
	 * @return string|false
	 */
	public function resolve_text_production_path( $preview_id, $preview_url = '' ) {
		$path = $this->get_text_path( $preview_id );

		if ( false !== $path ) {
			return $path;
		}

		return $this->text_path_from_preview_url( $preview_url );
	}

	/**
	 * Map a text SVG preview URL to text.svg in the cart preview folder.
	 *
	 * @param string $url Preview URL.
	 * @return string|false
	 */
	public function text_path_from_preview_url( $url ) {
		$local = $this->uploads->url_to_local_path( $url );

		if ( false === $local || is_dir( $local ) ) {
			return false;
		}

		if ( self::TEXT_FILENAME === basename( $local ) ) {
			return $local;
		}

		$text = trailingslashit( dirname( $local ) ) . self::TEXT_FILENAME;

		return is_readable( $text ) ? $text : false;
	}

	/**
	 * Absolute path to the layers-only preview file.
	 *
	 * @param string $id Preview ID.
	 * @return string|false
	 */
	public function get_layers_path( $id ) {
		$dir = $this->find_preview_dir( $id );

		if ( ! $dir ) {
			return false;
		}

		$path = trailingslashit( $dir ) . self::LAYERS_FILENAME;

		return is_readable( $path ) ? $path : false;
	}

	/**
	 * Resolve layers preview file for production PNG generation.
	 *
	 * @param string $preview_id  Stored preview ID.
	 * @param string $preview_url Layers preview URL from order meta.
	 * @return string|false
	 */
	public function resolve_layers_production_path( $preview_id, $preview_url = '' ) {
		$path = $this->get_layers_path( $preview_id );

		if ( false !== $path ) {
			return $path;
		}

		return $this->layers_path_from_preview_url( $preview_url );
	}

	/**
	 * Map a layers preview URL to layers.png in the cart preview folder.
	 *
	 * @param string $url Preview URL.
	 * @return string|false
	 */
	public function layers_path_from_preview_url( $url ) {
		$local = $this->uploads->url_to_local_path( $url );

		if ( false === $local || is_dir( $local ) ) {
			return false;
		}

		if ( self::LAYERS_FILENAME === basename( $local ) ) {
			return $local;
		}

		$layers = trailingslashit( dirname( $local ) ) . self::LAYERS_FILENAME;

		return is_readable( $layers ) ? $layers : false;
	}

	/**
	 * Absolute path to the full-resolution preview file.
	 *
	 * @param string $id Preview ID.
	 * @return string|false
	 */
	public function get_full_path( $id ) {
		$dir = $this->find_preview_dir( $id );

		if ( ! $dir ) {
			return false;
		}

		$path = trailingslashit( $dir ) . self::FULL_FILENAME;

		return is_readable( $path ) ? $path : false;
	}

	/**
	 * Resolve the full-resolution preview file for production PNG generation.
	 *
	 * @param string $preview_id  Stored preview ID.
	 * @param string $preview_url Thumbnail or full preview URL from order meta.
	 * @return string|false
	 */
	public function resolve_production_path( $preview_id, $preview_url = '' ) {
		$path = $this->get_full_path( $preview_id );

		if ( false !== $path ) {
			return $path;
		}

		return $this->full_path_from_preview_url( $preview_url );
	}

	/**
	 * Map a cart preview URL to the full.png file (handles thumbnail URLs).
	 *
	 * @param string $url Preview URL.
	 * @return string|false
	 */
	public function full_path_from_preview_url( $url ) {
		$local = $this->uploads->url_to_local_path( $url );

		if ( false === $local || is_dir( $local ) ) {
			return false;
		}

		if ( self::FULL_FILENAME === basename( $local ) ) {
			return $local;
		}

		if ( self::THUMB_FILENAME === basename( $local ) ) {
			$full = trailingslashit( dirname( $local ) ) . self::FULL_FILENAME;

			return is_readable( $full ) ? $full : false;
		}

		return false;
	}

	/**
	 * Remove a stored cart preview directory.
	 *
	 * @param string $id Preview ID.
	 * @return void
	 */
	public function delete( $id ) {
		$dir = $this->find_preview_dir( $id );

		if ( ! $dir ) {
			return;
		}

		$this->delete_directory( $dir );
	}

	/**
	 * Decode a supported image data URL to binary.
	 *
	 * @param string $data_url Data URL.
	 * @return string|false
	 */
	private function decode_data_url( $data_url ) {
		$data_url = is_string( $data_url ) ? trim( $data_url ) : '';

		if ( '' === $data_url || ! preg_match( '#^data:image/(png|jpe?g|webp);base64,#i', $data_url, $matches ) ) {
			return false;
		}

		$payload = substr( $data_url, strpos( $data_url, ',' ) + 1 );
		$payload = preg_replace( '/\s+/', '', $payload );
		$binary  = base64_decode( $payload, true );

		if ( false === $binary || '' === $binary ) {
			return false;
		}

		return $binary;
	}

	/**
	 * Create a JPEG thumbnail from the full preview.
	 *
	 * @param string $source_path Source file.
	 * @param string $dest_path   Destination file.
	 * @return bool
	 */
	private function create_thumbnail( $source_path, $dest_path ) {
		$editor = wp_get_image_editor( $source_path );

		if ( is_wp_error( $editor ) ) {
			$this->logger->warning( 'Cart preview thumbnail skipped.', array( 'error' => $editor->get_error_message() ) );
			return false;
		}

		$size = $editor->get_size();

		if ( is_wp_error( $size ) || empty( $size['width'] ) ) {
			return false;
		}

		if ( $size['width'] > self::THUMB_MAX_WIDTH ) {
			$resized = $editor->resize( self::THUMB_MAX_WIDTH, null, false );

			if ( is_wp_error( $resized ) ) {
				return false;
			}
		}

		$saved = $editor->save( $dest_path, 'image/jpeg' );

		if ( is_wp_error( $saved ) ) {
			$this->logger->warning( 'Cart preview thumbnail save failed.', array( 'error' => $saved->get_error_message() ) );
			return false;
		}

		if ( isset( $saved['path'] ) && $saved['path'] !== $dest_path && is_readable( $saved['path'] ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
			@rename( $saved['path'], $dest_path );
		}

		return is_readable( $dest_path );
	}

	/**
	 * Preview directory for an ID.
	 *
	 * @param string $id Preview ID.
	 * @return string
	 */
	private function preview_dir( $id ) {
		return trailingslashit( $this->uploads->base_path() ) . self::SUBDIR . '/' . $id;
	}

	/**
	 * Public URL for a preview file.
	 *
	 * @param string $id       Preview ID.
	 * @param string $filename File name.
	 * @return string
	 */
	private function preview_file_url( $id, $filename ) {
		return trailingslashit( $this->uploads->base_url() ) . self::SUBDIR . '/' . $id . '/' . $filename;
	}

	/**
	 * Locate preview directory on disk (case-insensitive fallback).
	 *
	 * @param string $id Preview ID.
	 * @return string|false
	 */
	private function find_preview_dir( $id ) {
		$id = $this->normalize_preview_id( $id );

		if ( strlen( $id ) < 16 ) {
			return false;
		}

		$exact = $this->preview_dir( $id );

		if ( is_dir( $exact ) ) {
			return $exact;
		}

		$root = trailingslashit( $this->uploads->base_path() ) . self::SUBDIR;

		if ( ! is_dir( $root ) ) {
			return false;
		}

		$entries = scandir( $root );

		if ( ! is_array( $entries ) ) {
			return false;
		}

		foreach ( $entries as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}

			if ( 0 === strcasecmp( $entry, $id ) && is_dir( trailingslashit( $root ) . $entry ) ) {
				return trailingslashit( $root ) . $entry;
			}
		}

		return false;
	}

	/**
	 * Normalize preview ID for filesystem lookup.
	 *
	 * @param string $id Raw ID.
	 * @return string
	 */
	private function normalize_preview_id( $id ) {
		return is_string( $id ) ? preg_replace( '/[^a-zA-Z0-9]/', '', $id ) : '';
	}

	/**
	 * Recursively delete a directory.
	 *
	 * @param string $dir Directory path.
	 * @return void
	 */
	private function delete_directory( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$entries = scandir( $dir );

		if ( ! is_array( $entries ) ) {
			return;
		}

		foreach ( $entries as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}

			$path = trailingslashit( $dir ) . $entry;

			if ( is_dir( $path ) ) {
				$this->delete_directory( $path );
			} else {
				wp_delete_file( $path );
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		@rmdir( $dir );
	}
}
