<?php
/**
 * Global settings admin screen.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Admin;

use Weblo\FastForms\Support\Capabilities;
use Weblo\FastForms\Support\GlobalSettings;

/**
 * Ekran ustawień globalnych wtyczki.
 */
final class SettingsAdmin {

	/**
	 * Rejestruje hooki.
	 */
	public function register(): void {
		add_action( 'admin_post_ff_save_global_settings', array( $this, 'handle_save' ) );
	}

	/**
	 * Zwraca ustawienia do szablonu.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		return GlobalSettings::get();
	}

	/**
	 * Zapisuje ustawienia globalne.
	 */
	public function handle_save(): void {
		if ( ! Capabilities::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission.', 'fast-forms' ) );
		}

		check_admin_referer( 'ff_save_global_settings' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw = isset( $_POST['ff_global_settings'] ) ? wp_unslash( $_POST['ff_global_settings'] ) : array();

		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		GlobalSettings::save( $raw );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => Menu::PAGE_SETTINGS,
					'updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
