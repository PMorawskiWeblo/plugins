<?php
/**
 * Layout custom post type.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class LayoutPostType
 */
class LayoutPostType {

	const POST_TYPE = 'wpp_layout';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register CPT.
	 *
	 * @return void
	 */
	public function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Layouts', 'woo-product-personalizer' ),
					'singular_name'      => __( 'Layout', 'woo-product-personalizer' ),
					'add_new'            => __( 'Add New', 'woo-product-personalizer' ),
					'add_new_item'       => __( 'Add New Layout', 'woo-product-personalizer' ),
					'edit_item'          => __( 'Edit Layout', 'woo-product-personalizer' ),
					'new_item'           => __( 'New Layout', 'woo-product-personalizer' ),
					'view_item'          => __( 'View Layout', 'woo-product-personalizer' ),
					'search_items'       => __( 'Search Layouts', 'woo-product-personalizer' ),
					'not_found'          => __( 'No layouts found.', 'woo-product-personalizer' ),
					'not_found_in_trash' => __( 'No layouts found in Trash.', 'woo-product-personalizer' ),
					'menu_name'          => __( 'Layouts', 'woo-product-personalizer' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => AdminMenu::PARENT_SLUG,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'can_export'          => true,
			)
		);
	}
}
