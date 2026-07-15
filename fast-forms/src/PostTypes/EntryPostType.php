<?php
/**
 * Entry custom post type.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\PostTypes;

/**
 * Rejestruje CPT przesłanych zgłoszeń.
 */
final class EntryPostType {

	public const POST_TYPE = 'wpf_entry';

	/**
	 * Status wpisu zgłoszenia — prywatny, niewidoczny publicznie i poza SEO.
	 */
	public const POST_STATUS = 'private';

	/**
	 * Statusy uwzględniane w panelu (także starsze wpisy zapisane jako publish).
	 */
	public const QUERY_STATUSES = array( 'private', 'publish' );

	/**
	 * Meta keys używane przez zgłoszenia.
	 */
	public const META_FORM_ID          = '_wpf_form_id';
	public const META_SCHEMA_VERSION   = '_wpf_schema_version';
	public const META_SCHEMA_SNAPSHOT  = '_wpf_schema_snapshot';
	public const META_PAYLOAD          = '_wpf_payload';
	public const META_NAME             = '_wpf_name';
	public const META_EMAIL            = '_wpf_email';
	public const META_PHONE            = '_wpf_phone';
	public const META_SUBMITTED_AT     = '_wpf_submitted_at';
	public const META_RECAPTCHA_SCORE  = '_wpf_recaptcha_score';
	public const META_UPLOADED_FILES   = '_wpf_uploaded_files';
	public const META_STATUS           = '_wpf_status';

	/**
	 * Rejestruje hooki CPT.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'template_redirect', array( $this, 'block_public_access' ) );
	}

	/**
	 * Rejestruje typ wpisu zgłoszenia.
	 */
	public function register_post_type(): void {
		$labels = array(
			'name'               => __( 'Form submissions', 'fast-forms' ),
			'singular_name'      => __( 'Submission', 'fast-forms' ),
			'menu_name'          => __( 'Form submissions', 'fast-forms' ),
			'add_new'            => __( 'Add new', 'fast-forms' ),
			'add_new_item'       => __( 'Add new submission', 'fast-forms' ),
			'edit_item'          => __( 'View submission', 'fast-forms' ),
			'new_item'           => __( 'New submission', 'fast-forms' ),
			'view_item'          => __( 'View submission', 'fast-forms' ),
			'search_items'       => __( 'Search submissions', 'fast-forms' ),
			'not_found'          => __( 'No submissions found.', 'fast-forms' ),
			'not_found_in_trash' => __( 'No submissions found in trash.', 'fast-forms' ),
			'all_items'          => __( 'All submissions', 'fast-forms' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_in_nav_menus'   => false,
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
	 * Blokuje publiczny podgląd pojedynczego zgłoszenia (404).
	 */
	public function block_public_access(): void {
		if ( is_admin() || ! is_singular( self::POST_TYPE ) ) {
			return;
		}

		global $wp_query;

		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->set_404();
		}

		status_header( 404 );
		nocache_headers();
	}
}
