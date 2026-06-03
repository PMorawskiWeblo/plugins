<?php
/**
 * Load layout configuration from post meta (array or legacy JSON string).
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Helpers;

use WooProductPersonalizer\Infrastructure\Repository\LayoutRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class LayoutConfigLoader
 */
class LayoutConfigLoader {

	/**
	 * Read layout config for a layout post ID.
	 *
	 * @param int $layout_id Layout post ID.
	 * @return array
	 */
	public static function load( $layout_id ) {
		$layout_id = absint( $layout_id );

		if ( ! $layout_id ) {
			return ( new LayoutRepository() )->default_config();
		}

		$raw = get_post_meta( $layout_id, LayoutRepository::META_CONFIG, true );

		if ( is_array( $raw ) && ! empty( $raw ) ) {
			return $raw;
		}

		if ( is_string( $raw ) && '' !== trim( $raw ) ) {
			$config = json_decode( $raw, true );
			if ( is_array( $config ) ) {
				return $config;
			}
		}

		return ( new LayoutRepository() )->default_config();
	}
}
