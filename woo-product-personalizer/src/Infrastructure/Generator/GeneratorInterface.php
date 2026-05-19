<?php
/**
 * Generator interface.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Infrastructure\Generator;

defined( 'ABSPATH' ) || exit;

/**
 * Interface GeneratorInterface
 */
interface GeneratorInterface {

	/**
	 * Generate production file.
	 *
	 * @param int    $order_id     Order ID.
	 * @param int    $item_id      Order line item ID.
	 * @param string $preview_data Base64, local path, or image URL.
	 * @param string $directory    Output directory.
	 * @param string $filename_tag Filename tag (e.g. projekt, warstwy).
	 * @return array{path: string, url: string}
	 */
	public function generate( $order_id, $item_id, $preview_data, $directory, $filename_tag = 'projekt' );
}
