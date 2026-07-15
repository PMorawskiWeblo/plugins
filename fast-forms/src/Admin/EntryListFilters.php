<?php
/**
 * Entry list filters in admin.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Admin;

use Weblo\FastForms\PostTypes\EntryPostType;
use Weblo\FastForms\PostTypes\FormPostType;
use Weblo\FastForms\Support\EntryDateFilter;

/**
 * Filtry listy zgłoszeń: formularz, status, zakres dat.
 */
final class EntryListFilters {

	/**
	 * Rejestruje hooki filtrów.
	 */
	public function register(): void {
		add_action( 'restrict_manage_posts', array( $this, 'render_filters' ) );
		add_action( 'pre_get_posts', array( $this, 'apply_filters' ) );
	}

	/**
	 * Renderuje kontrolki filtrów nad listą zgłoszeń.
	 *
	 * @param string $post_type Typ wpisu.
	 */
	public function render_filters( string $post_type ): void {
		if ( EntryPostType::POST_TYPE !== $post_type ) {
			return;
		}

		$selected_form   = isset( $_GET['ff_form'] ) ? absint( wp_unslash( $_GET['ff_form'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected_status = isset( $_GET['ff_status'] ) ? sanitize_key( wp_unslash( $_GET['ff_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$date_from       = isset( $_GET['ff_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['ff_date_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$date_to         = isset( $_GET['ff_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['ff_date_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$forms = get_posts(
			array(
				'post_type'      => FormPostType::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		echo '<label class="screen-reader-text" for="ff-filter-form">' . esc_html__( 'Form', 'fast-forms' ) . '</label>';
		echo '<select name="ff_form" id="ff-filter-form">';
		echo '<option value="">' . esc_html__( 'All forms', 'fast-forms' ) . '</option>';

		foreach ( $forms as $form ) {
			if ( ! $form instanceof \WP_Post ) {
				continue;
			}

			printf(
				'<option value="%1$d"%2$s>%3$s</option>',
				(int) $form->ID,
				selected( $selected_form, (int) $form->ID, false ),
				esc_html( $form->post_title )
			);
		}

		echo '</select>';

		$statuses = array(
			''         => __( 'All statuses', 'fast-forms' ),
			'new'      => __( 'New', 'fast-forms' ),
			'read'     => __( 'Read', 'fast-forms' ),
			'archived' => __( 'Archived', 'fast-forms' ),
		);

		echo '<label class="screen-reader-text" for="ff-filter-status">' . esc_html__( 'Status', 'fast-forms' ) . '</label>';
		echo '<select name="ff_status" id="ff-filter-status">';

		foreach ( $statuses as $value => $label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $value ),
				selected( $selected_status, $value, false ),
				esc_html( $label )
			);
		}

		echo '</select>';

		printf(
			'<label class="screen-reader-text" for="ff-filter-date-from">%s</label><input type="date" name="ff_date_from" id="ff-filter-date-from" value="%s" placeholder="%s" />',
			esc_html__( 'Date from', 'fast-forms' ),
			esc_attr( $date_from ),
			esc_attr__( 'From', 'fast-forms' )
		);

		printf(
			'<label class="screen-reader-text" for="ff-filter-date-to">%s</label><input type="date" name="ff_date_to" id="ff-filter-date-to" value="%s" placeholder="%s" />',
			esc_html__( 'Date to', 'fast-forms' ),
			esc_attr( $date_to ),
			esc_attr__( 'To', 'fast-forms' )
		);

		$search = isset( $_GET['ff_search'] ) ? sanitize_text_field( wp_unslash( $_GET['ff_search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		printf(
			'<label class="screen-reader-text" for="ff-filter-search">%s</label><input type="search" name="ff_search" id="ff-filter-search" value="%s" placeholder="%s" />',
			esc_html__( 'Search', 'fast-forms' ),
			esc_attr( $search ),
			esc_attr__( 'Search submissions…', 'fast-forms' )
		);
	}

	/**
	 * Stosuje filtry do zapytania listy zgłoszeń.
	 *
	 * @param \WP_Query $query Zapytanie.
	 */
	public function apply_filters( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( EntryPostType::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		$form_id = isset( $_GET['ff_form'] ) ? absint( wp_unslash( $_GET['ff_form'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status  = isset( $_GET['ff_status'] ) ? sanitize_key( wp_unslash( $_GET['ff_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$from    = isset( $_GET['ff_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['ff_date_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$to      = isset( $_GET['ff_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['ff_date_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search  = isset( $_GET['ff_search'] ) ? sanitize_text_field( wp_unslash( $_GET['ff_search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$meta_query = array();

		if ( $form_id > 0 ) {
			$meta_query[] = array(
				'key'   => EntryPostType::META_FORM_ID,
				'value' => $form_id,
			);
		}

		if ( '' !== $status ) {
			$meta_query[] = array(
				'key'   => EntryPostType::META_STATUS,
				'value' => $status,
			);
		}

		if ( ! empty( $meta_query ) || '' !== $from || '' !== $to ) {
			$query->set(
				'meta_query',
				EntryDateFilter::merge_meta_query( $meta_query, $from, $to )
			);
		}

		if ( '' !== $search ) {
			add_filter( 'posts_where', array( $this, 'filter_search_where' ), 10, 2 );
			$query->set( 'ff_search_term', $search );
		}
	}

	/**
	 * Rozszerza zapytanie o wyszukiwanie w meta zgłoszenia.
	 *
	 * @param string    $where Zapytanie WHERE.
	 * @param \WP_Query $query Zapytanie.
	 */
	public function filter_search_where( string $where, \WP_Query $query ): string {
		if ( EntryPostType::POST_TYPE !== $query->get( 'post_type' ) ) {
			return $where;
		}

		$term = (string) $query->get( 'ff_search_term' );

		if ( '' === $term ) {
			return $where;
		}

		remove_filter( 'posts_where', array( $this, 'filter_search_where' ), 10 );

		global $wpdb;

		$like = '%' . $wpdb->esc_like( $term ) . '%';
		$keys = array(
			EntryPostType::META_NAME,
			EntryPostType::META_EMAIL,
			EntryPostType::META_PHONE,
			EntryPostType::META_PAYLOAD,
		);

		$clauses = array();

		foreach ( $keys as $key ) {
			$clauses[] = $wpdb->prepare(
				"EXISTS (SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = {$wpdb->posts}.ID AND pm.meta_key = %s AND pm.meta_value LIKE %s)",
				$key,
				$like
			);
		}

		if ( ! empty( $clauses ) ) {
			$where .= ' AND (' . implode( ' OR ', $clauses ) . ')';
		}

		return $where;
	}
}
