<?php
/**
 * Form manager and CSV export.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Admin;

use Weblo\FastForms\Export\CsvExporter;
use Weblo\FastForms\PostTypes\FormPostType;
use Weblo\FastForms\Support\Capabilities;
use Weblo\FastForms\Support\EntryDateFilter;

/**
 * Ekran managera formularzy z eksportem CSV.
 */
final class ManagerAdmin {

	/**
	 * Rejestruje hooki.
	 */
	public function register(): void {
		add_action( 'admin_post_ff_export_entries_csv', array( $this, 'handle_export' ) );
	}

	/**
	 * @return array<int, \WP_Post>
	 */
	public static function get_forms(): array {
		$posts = get_posts(
			array(
				'post_type'      => FormPostType::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		return array_filter(
			$posts,
			static function ( $post ): bool {
				return $post instanceof \WP_Post;
			}
		);
	}

	/**
	 * Obsługuje eksport CSV.
	 */
	public function handle_export(): void {
		if ( ! Capabilities::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission.', 'fast-forms' ) );
		}

		check_admin_referer( 'ff_export_entries_csv' );

		$form_id   = isset( $_POST['ff_export_form_id'] ) ? absint( wp_unslash( $_POST['ff_export_form_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$date_from = isset( $_POST['ff_export_date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['ff_export_date_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$date_to   = isset( $_POST['ff_export_date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['ff_export_date_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		list( $date_from, $date_to ) = EntryDateFilter::normalize_range( $date_from, $date_to );

		if ( $form_id < 1 ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'  => Menu::PAGE_MANAGER,
						'error' => 'missing_form',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		CsvExporter::stream( $form_id, $date_from, $date_to );
	}
}
