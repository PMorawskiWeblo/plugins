<?php
/**
 * Form custom post type.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\PostTypes;

/**
 * Rejestruje CPT definicji formularzy.
 */
final class FormPostType {

	public const POST_TYPE = 'wpf_form';

	/**
	 * Meta keys używane przez formularze.
	 */
	public const META_SCHEMA               = '_wpf_schema';
	public const META_SCHEMA_VERSION       = '_wpf_schema_version';
	public const META_EMAIL_SETTINGS       = '_wpf_email_settings';
	public const META_VALIDATION_SETTINGS  = '_wpf_validation_settings';
	public const META_NOTIFICATION_SETTINGS = '_wpf_notification_settings';
	public const META_FORM_SETTINGS        = '_wpf_form_settings';

	/**
	 * Rejestruje hooki CPT.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_filter( 'enter_title_here', array( $this, 'filter_title_placeholder' ), 10, 2 );
	}

	/**
	 * Rejestruje typ wpisu formularza.
	 */
	public function register_post_type(): void {
		$labels = array(
			'name'               => __( 'Forms', 'fast-forms' ),
			'singular_name'      => __( 'Form', 'fast-forms' ),
			'menu_name'          => __( 'Forms', 'fast-forms' ),
			'add_new'            => __( 'Add new', 'fast-forms' ),
			'add_new_item'       => __( 'Add new form', 'fast-forms' ),
			'edit_item'          => __( 'Edit form', 'fast-forms' ),
			'new_item'           => __( 'New form', 'fast-forms' ),
			'view_item'          => __( 'View form', 'fast-forms' ),
			'search_items'       => __( 'Search forms', 'fast-forms' ),
			'not_found'          => __( 'No forms found.', 'fast-forms' ),
			'not_found_in_trash' => __( 'No forms found in trash.', 'fast-forms' ),
			'all_items'          => __( 'All forms', 'fast-forms' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_rest'        => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'can_export'          => true,
				'delete_with_user'    => false,
			)
		);
	}

	/**
	 * Zmienia placeholder tytułu na ekranie edycji formularza.
	 *
	 * @param string   $placeholder Domyślny placeholder.
	 * @param \WP_Post $post        Edytowany wpis.
	 * @return string
	 */
	public function filter_title_placeholder( string $placeholder, \WP_Post $post ): string {
		if ( self::POST_TYPE === $post->post_type ) {
			return __( 'Form name', 'fast-forms' );
		}

		return $placeholder;
	}
}
