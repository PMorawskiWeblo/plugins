<?php
/**
 * Resolves post-submit redirect targets from form notification settings.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Support;

/**
 * Custom redirect URL takes priority over a selected WordPress page.
 */
final class RedirectResolver {

	/**
	 * Builds redirect payload for submit responses when enabled.
	 *
	 * @param array<string, mixed> $notifications Notification settings.
	 * @return array{url: string, delay: int}|null
	 */
	public static function payload_from_notifications( array $notifications ): ?array {
		if ( empty( $notifications['enableRedirect'] ) ) {
			return null;
		}

		$url = self::resolve_url( $notifications );

		if ( '' === $url ) {
			return null;
		}

		return array(
			'url'   => $url,
			'delay' => (int) ( $notifications['redirectDelay'] ?? 0 ),
		);
	}

	/**
	 * Resolves the redirect URL from notification settings.
	 *
	 * @param array<string, mixed> $notifications Notification settings.
	 */
	public static function resolve_url( array $notifications ): string {
		$custom_url = trim( (string) ( $notifications['redirectUrl'] ?? '' ) );

		if ( '' !== $custom_url ) {
			$validated = esc_url_raw( $custom_url );

			if ( '' !== $validated && filter_var( $validated, FILTER_VALIDATE_URL ) ) {
				return $validated;
			}
		}

		$page_id = absint( $notifications['redirectPageId'] ?? 0 );

		if ( $page_id < 1 ) {
			return '';
		}

		$page = get_post( $page_id );

		if ( ! $page instanceof \WP_Post || 'page' !== $page->post_type || 'publish' !== $page->post_status ) {
			return '';
		}

		$permalink = get_permalink( $page_id );

		return is_string( $permalink ) && '' !== $permalink ? $permalink : '';
	}
}
