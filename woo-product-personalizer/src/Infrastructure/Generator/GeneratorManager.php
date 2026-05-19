<?php
/**
 * Generator strategy manager.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Infrastructure\Generator;

use WooProductPersonalizer\Core\Logger;
use WooProductPersonalizer\Infrastructure\Uploads\UploadsManager;

defined( 'ABSPATH' ) || exit;

/**
 * Class GeneratorManager
 */
class GeneratorManager {

	/**
	 * Active generator.
	 *
	 * @var GeneratorInterface
	 */
	private $generator;

	/**
	 * Constructor.
	 *
	 * @param UploadsManager $uploads Uploads.
	 * @param Logger         $logger   Logger.
	 */
	public function __construct( UploadsManager $uploads, Logger $logger ) {
		$this->generator = new PngGenerator( $uploads, $logger );
	}

	/**
	 * Generate production file.
	 *
	 * @param int    $order_id     Order ID.
	 * @param int    $item_id      Order line item ID.
	 * @param string $preview_data Preview data.
	 * @param string $directory    Directory.
	 * @return array{path: string, url: string}
	 */
	public function generate( $order_id, $item_id, $preview_data, $directory ) {
		return $this->generator->generate( $order_id, $item_id, $preview_data, $directory );
	}
}
