<?php
/**
 * Plugin list action links.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Admin;

/**
 * Dodaje link „Ustawienia” na liście wtyczek.
 */
final class PluginLinks {

	/**
	 * Rejestruje filtr linków akcji.
	 */
	public function register(): void {
		add_filter( 'plugin_action_links_' . FF_PLUGIN_BASENAME, array( $this, 'add_links' ) );
	}

	/**
	 * Dodaje link prowadzący do listy formularzy.
	 *
	 * @param array<int, string> $links Istniejące linki.
	 * @return array<int, string>
	 */
	public function add_links( array $links ): array {
		$url = Menu::forms_list_url();

		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $url ),
			esc_html__( 'Settings', 'fast-forms' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}
}
