<?php
/**
 * PNG production file generator.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Infrastructure\Generator;

use WooProductPersonalizer\Core\Logger;
use WooProductPersonalizer\Infrastructure\Uploads\UploadsManager;

defined( 'ABSPATH' ) || exit;

/**
 * Class PngGenerator
 */
class PngGenerator implements GeneratorInterface {

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
	 * @param Logger         $logger   Logger.
	 */
	public function __construct( UploadsManager $uploads, Logger $logger ) {
		$this->uploads = $uploads;
		$this->logger  = $logger;
	}

	/**
	 * Save flattened PNG from browser data URL.
	 *
	 * @param int    $order_id     Order ID.
	 * @param int    $item_id      Order line item ID.
	 * @param string $preview_data Data URL, local path, or image URL.
	 * @param string $directory    Directory.
	 * @return array{path: string, url: string}
	 */
	public function generate( $order_id, $item_id, $preview_data, $directory ) {
		$item_id  = absint( $item_id );
		$filename = absint( $order_id ) . '_item_' . ( $item_id ? $item_id : '0' ) . '_projekt.png';
		$path     = trailingslashit( $directory ) . $filename;
		$url      = trailingslashit( $this->uploads->order_url( $order_id ) ) . $filename;

		if ( empty( $preview_data ) ) {
			$this->logger->warning( 'No preview data for PNG generation.', array( 'order_id' => $order_id, 'item_id' => $item_id ) );
			return array( 'path' => '', 'url' => '' );
		}

		$binary = $this->read_preview_binary( $preview_data );

		if ( false === $binary || '' === $binary ) {
			$this->logger->error(
				'Failed to decode preview image.',
				array(
					'order_id' => $order_id,
					'item_id'  => $item_id,
					'source'   => is_string( $preview_data ) ? substr( $preview_data, 0, 120 ) : '',
				)
			);
			return array( 'path' => '', 'url' => '' );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === file_put_contents( $path, $binary ) ) {
			$this->logger->error( 'Failed to write production PNG.', array( 'order_id' => $order_id, 'item_id' => $item_id ) );
			return array( 'path' => '', 'url' => '' );
		}

		$this->logger->info(
			'Production PNG generated.',
			array(
				'order_id' => $order_id,
				'item_id'  => $item_id,
				'path'     => $path,
			)
		);

		return array(
			'path' => $path,
			'url'  => $url,
		);
	}

	/**
	 * Load preview bytes from a data URL, path, or uploads URL.
	 *
	 * @param string $preview_data Preview source.
	 * @return string|false
	 */
	private function read_preview_binary( $preview_data ) {
		$preview_data = is_string( $preview_data ) ? trim( $preview_data ) : '';

		if ( '' === $preview_data ) {
			return false;
		}

		if ( preg_match( '#^data:image/#i', $preview_data ) ) {
			return $this->decode_data_url( $preview_data );
		}

		if ( is_readable( $preview_data ) && ! is_dir( $preview_data ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$binary = file_get_contents( $preview_data );

			return false !== $binary ? $binary : false;
		}

		$local = $this->uploads->url_to_local_path( $preview_data );

		if ( false !== $local && is_readable( $local ) && ! is_dir( $local ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$binary = file_get_contents( $local );

			return false !== $binary ? $binary : false;
		}

		if ( preg_match( '#^https?://#i', $preview_data ) ) {
			$response = wp_remote_get(
				$preview_data,
				array(
					'timeout' => 20,
				)
			);

			if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
				$binary = wp_remote_retrieve_body( $response );

				return '' !== $binary ? $binary : false;
			}
		}

		if ( preg_match( '/^data:image\/\w+;base64,/', $preview_data ) ) {
			$payload = preg_replace( '/^data:image\/\w+;base64,/', '', $preview_data );

			return base64_decode( $payload, true );
		}

		return base64_decode( $preview_data, true );
	}

	/**
	 * Decode a data URL to binary image data.
	 *
	 * @param string $data_url Data URL.
	 * @return string|false
	 */
	private function decode_data_url( $data_url ) {
		if ( ! preg_match( '#^data:image/\w+;base64,#', $data_url ) ) {
			return false;
		}

		$payload = preg_replace( '#^data:image/\w+;base64,#', '', $data_url );
		$payload = preg_replace( '/\s+/', '', $payload );

		return base64_decode( $payload, true );
	}
}
