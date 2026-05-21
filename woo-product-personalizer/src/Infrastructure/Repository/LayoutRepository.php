<?php
/**
 * Layout repository.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Infrastructure\Repository;

use WooProductPersonalizer\Admin\LayoutPostType;
use WooProductPersonalizer\Domain\Layout\Layout;
use WooProductPersonalizer\Helpers\LayoutAssetResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Class LayoutRepository
 */
class LayoutRepository {

	const META_CONFIG = '_wpp_layout_config';
	const META_VERSION = '_wpp_layout_version';

	/**
	 * Default empty layout config.
	 *
	 * @return array
	 */
	public function default_config() {
		return array(
			'personalization_mode' => 'layout_2',
			'crop_mask_shape'      => true,
			'canvas'      => array(
				'width'      => 2000,
				'height'     => 2400,
				'background' => '',
				'overlay'    => '',
			),
			'image_slots' => array(),
			'text_fields' => array(),
			'limits'      => array(
				'max_total_images' => 4,
				'max_upload_mb'    => 10,
			),
		);
	}

	/**
	 * Get layout by ID.
	 *
	 * @param int $layout_id Layout post ID.
	 * @return Layout|null
	 */
	public function find( $layout_id ) {
		$post = get_post( absint( $layout_id ) );

		if ( ! $post || LayoutPostType::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}

		$config = get_post_meta( $post->ID, self::META_CONFIG, true );
		if ( ! is_array( $config ) || empty( $config ) ) {
			$config = $this->default_config();
		}

		$config = LayoutAssetResolver::resolve( $config );

		return new Layout( $post->ID, $post->post_title, $config );
	}

	/**
	 * Save layout config.
	 *
	 * @param int   $layout_id Layout ID.
	 * @param array $config    Config.
	 * @return bool
	 */
	public function save_config( $layout_id, array $config ) {
		$config = wp_parse_args( $config, $this->default_config() );
		update_post_meta( $layout_id, self::META_CONFIG, $config );
		update_post_meta( $layout_id, self::META_VERSION, WPP_VERSION );
		return true;
	}

	/**
	 * Get choices for product select.
	 *
	 * @return array<int, string>
	 */
	public function get_choices() {
		$posts = get_posts(
			array(
				'post_type'      => LayoutPostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$choices = array();
		foreach ( $posts as $post ) {
			$choices[ $post->ID ] = $post->post_title;
		}
		return $choices;
	}
}
