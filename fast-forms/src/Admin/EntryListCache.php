<?php
/**
 * Request-level cache for entry list admin columns.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Admin;

use Weblo\FastForms\PostTypes\EntryPostType;
use Weblo\FastForms\PostTypes\FormPostType;

/**
 * Prefetch meta i tytułów formularzy na liście zgłoszeń.
 */
final class EntryListCache {

	/** @var array<int, string> */
	private static array $form_titles = array();

	/**
	 * Ładuje meta zgłoszeń i tytuły powiązanych formularzy dla bieżącej strony listy.
	 *
	 * @param array<int, \WP_Post> $posts   Wpisy z listy.
	 * @param \WP_Query            $query   Zapytanie.
	 * @return array<int, \WP_Post>
	 */
	public static function prefetch_posts( array $posts, \WP_Query $query ): array {
		if ( ! is_admin() || empty( $posts ) ) {
			return $posts;
		}

		if ( EntryPostType::POST_TYPE !== $query->get( 'post_type' ) ) {
			return $posts;
		}

		$post_ids = wp_list_pluck( $posts, 'ID' );

		if ( empty( $post_ids ) ) {
			return $posts;
		}

		update_meta_cache( 'post', $post_ids );
		self::warm_form_titles( $post_ids );

		return $posts;
	}

	/**
	 * @param array<int, int> $entry_ids ID zgłoszeń.
	 */
	public static function warm_form_titles( array $entry_ids ): void {
		$form_ids = array();

		foreach ( $entry_ids as $entry_id ) {
			$form_id = (int) get_post_meta( (int) $entry_id, EntryPostType::META_FORM_ID, true );

			if ( $form_id > 0 ) {
				$form_ids[ $form_id ] = $form_id;
			}
		}

		if ( empty( $form_ids ) ) {
			return;
		}

		$missing = array();

		foreach ( $form_ids as $form_id ) {
			if ( ! isset( self::$form_titles[ $form_id ] ) ) {
				$missing[] = $form_id;
			}
		}

		if ( empty( $missing ) ) {
			return;
		}

		$forms = get_posts(
			array(
				'post_type'              => FormPostType::POST_TYPE,
				'post__in'               => $missing,
				'posts_per_page'         => count( $missing ),
				'orderby'                => 'post__in',
				'post_status'            => 'any',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		foreach ( $forms as $form ) {
			if ( $form instanceof \WP_Post ) {
				self::$form_titles[ $form->ID ] = $form->post_title;
			}
		}

		foreach ( $missing as $form_id ) {
			if ( ! isset( self::$form_titles[ $form_id ] ) ) {
				self::$form_titles[ $form_id ] = '';
			}
		}
	}

	/**
	 * Zwraca tytuł formularza (z cache requestu).
	 */
	public static function get_form_title( int $form_id ): string {
		if ( $form_id < 1 ) {
			return '';
		}

		if ( isset( self::$form_titles[ $form_id ] ) ) {
			return self::$form_titles[ $form_id ];
		}

		$form = get_post( $form_id );
		self::$form_titles[ $form_id ] = $form instanceof \WP_Post ? $form->post_title : '';

		return self::$form_titles[ $form_id ];
	}
}
