<?php
/**
 * Entry admin screens.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Admin;

use Weblo\FastForms\PostTypes\EntryPostType;
use Weblo\FastForms\Support\AssetVersion;
use Weblo\FastForms\Support\Capabilities;

/**
 * Lista i podgląd zgłoszeń w kokpicie.
 */
final class EntryAdmin {

	/**
	 * Rejestruje hooki admina zgłoszeń.
	 */
	public function register(): void {
		add_filter( 'manage_' . EntryPostType::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . EntryPostType::POST_TYPE . '_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_filter( 'posts_results', array( EntryListCache::class, 'prefetch_posts' ), 10, 2 );
		add_action( 'edit_form_after_title', array( $this, 'render_entry_detail' ) );
		add_action( 'admin_head', array( $this, 'hide_entry_editor' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'save_post_' . EntryPostType::POST_TYPE, array( $this, 'save_entry_status' ), 10, 2 );
	}

	/**
	 * Kolumny listy zgłoszeń.
	 *
	 * @param array<string, string> $columns Kolumny.
	 * @return array<string, string>
	 */
	public function columns( array $columns ): array {
		$new = array();

		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['title'] = __( 'Submission', 'fast-forms' );
				continue;
			}

			if ( 'date' === $key ) {
				$new['ff_form']      = __( 'Form', 'fast-forms' );
				$new['ff_submitted'] = __( 'Submitted at', 'fast-forms' );
				$new['ff_status']    = __( 'Status', 'fast-forms' );
			}

			$new[ $key ] = $label;
		}

		return $new;
	}

	/**
	 * Zawartość kolumn listy.
	 *
	 * @param string $column  Nazwa kolumny.
	 * @param int    $post_id ID wpisu.
	 */
	public function column_content( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'ff_form':
				$form_id = (int) get_post_meta( $post_id, EntryPostType::META_FORM_ID, true );
				$title   = EntryListCache::get_form_title( $form_id );
				echo '' !== $title ? esc_html( $title ) : '—';
				break;

			case 'ff_submitted':
				echo esc_html( (string) get_post_meta( $post_id, EntryPostType::META_SUBMITTED_AT, true ) );
				break;

			case 'ff_status':
				echo esc_html( self::status_label( (string) get_post_meta( $post_id, EntryPostType::META_STATUS, true ) ) );
				break;
		}
	}

	/**
	 * Renderuje szczegóły zgłoszenia.
	 *
	 * @param \WP_Post $post Wpis.
	 */
	public function render_entry_detail( \WP_Post $post ): void {
		if ( EntryPostType::POST_TYPE !== $post->post_type ) {
			return;
		}

		$rows           = EntryPresenter::get_rows( $post->ID );
		$meta           = EntryPresenter::get_meta_summary( $post->ID );
		$current_status = (string) get_post_meta( $post->ID, EntryPostType::META_STATUS, true );
		$file           = FF_PLUGIN_DIR . 'templates/admin/entry-detail.php';

		if ( is_readable( $file ) ) {
			include $file;
		}
	}

	/**
	 * Ukrywa edytor treści zgłoszenia.
	 */
	public function hide_entry_editor(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || EntryPostType::POST_TYPE !== $screen->post_type ) {
			return;
		}

		echo '<style>#postdivrich,#wp-content-wrap{display:none!important;}</style>';
	}

	/**
	 * Ładuje style podglądu zgłoszenia.
	 *
	 * @param string $hook Hook admina.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'edit.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || EntryPostType::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'fast-forms-entry-admin',
			FF_PLUGIN_URL . 'assets/admin/css/entry-admin.css',
			array(),
			AssetVersion::get( 'assets/admin/css/entry-admin.css' )
		);
	}

	/**
	 * Zapisuje status zgłoszenia z ekranu podglądu.
	 *
	 * @param int      $post_id ID wpisu.
	 * @param \WP_Post $post    Wpis.
	 */
	public function save_entry_status( int $post_id, \WP_Post $post ): void {
		if ( EntryPostType::POST_TYPE !== $post->post_type ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! Capabilities::can_manage() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_POST['ff_entry_status_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['ff_entry_status_nonce'] ), 'ff_save_entry_status_' . $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['ff_entry_status'] ) ) {
			return;
		}

		$status = sanitize_key( wp_unslash( (string) $_POST['ff_entry_status'] ) );
		$allowed = array( 'new', 'read', 'archived' );

		if ( ! in_array( $status, $allowed, true ) ) {
			return;
		}

		update_post_meta( $post_id, EntryPostType::META_STATUS, $status );
	}

	/**
	 * Etykieta statusu zgłoszenia.
	 *
	 * @param string $status Kod statusu.
	 */
	private static function status_label( string $status ): string {
		$map = array(
			'new'      => __( 'New', 'fast-forms' ),
			'read'     => __( 'Read', 'fast-forms' ),
			'archived' => __( 'Archived', 'fast-forms' ),
		);

		return $map[ $status ] ?? $status;
	}

	/**
	 * Dostępne statusy zgłoszenia.
	 *
	 * @return array<string, string>
	 */
	public static function get_status_options(): array {
		return array(
			'new'      => __( 'New', 'fast-forms' ),
			'read'     => __( 'Read', 'fast-forms' ),
			'archived' => __( 'Archived', 'fast-forms' ),
		);
	}
}
