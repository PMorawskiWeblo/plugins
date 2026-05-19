<?php
/**
 * Layout builder metabox.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Admin;

use WooProductPersonalizer\Admin\LayoutPostType;
use WooProductPersonalizer\Helpers\LayoutConfigSanitizer;
use WooProductPersonalizer\Infrastructure\Repository\LayoutRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class LayoutMetaBox
 */
class LayoutMetaBox {

	/**
	 * Repository.
	 *
	 * @var LayoutRepository
	 */
	private $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new LayoutRepository();

		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post_' . LayoutPostType::POST_TYPE, array( $this, 'save' ), 10, 2 );
	}

	/**
	 * Register metabox.
	 *
	 * @return void
	 */
	public function register() {
		add_meta_box(
			'wpp_layout_builder',
			__( 'Layout configuration', 'woo-product-personalizer' ),
			array( $this, 'render' ),
			LayoutPostType::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render metabox.
	 *
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( 'wpp_save_layout', 'wpp_layout_nonce' );

		$config = get_post_meta( $post->ID, LayoutRepository::META_CONFIG, true );
		if ( ! is_array( $config ) ) {
			$config = $this->repository->default_config();
		}

		$config_json = wp_json_encode( $config );
		include WPP_PLUGIN_PATH . 'templates/admin/layout-metabox.php';
	}

	/**
	 * Save layout config.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST['wpp_layout_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpp_layout_nonce'] ) ), 'wpp_save_layout' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw = isset( $_POST['wpp_layout_config'] ) ? wp_unslash( $_POST['wpp_layout_config'] ) : '';
		$config = json_decode( $raw, true );

		if ( ! is_array( $config ) ) {
			return;
		}

		$config = LayoutConfigSanitizer::sanitize( $config );
		$this->repository->save_config( $post_id, $config );
	}
}
