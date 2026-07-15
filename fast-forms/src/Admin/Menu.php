<?php
/**
 * Admin menu registration.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Admin;

use Weblo\FastForms\PostTypes\EntryPostType;
use Weblo\FastForms\PostTypes\FormPostType;
use Weblo\FastForms\Support\AssetVersion;
use Weblo\FastForms\Support\Capabilities;

/**
 * Rejestruje menu „Formularze” w kokpicie WordPress.
 */
final class Menu {

	public const PAGE_SETTINGS = 'ff-settings';
	public const PAGE_MANAGER  = 'ff-manager';

	/**
	 * Rejestruje hooki menu.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Zwraca slug rodzica menu formularzy.
	 */
	public static function forms_parent_slug(): string {
		return 'edit.php?post_type=' . FormPostType::POST_TYPE;
	}

	/**
	 * Zwraca URL listy formularzy.
	 */
	public static function forms_list_url(): string {
		return admin_url( self::forms_parent_slug() );
	}

	/**
	 * Dodaje top-level menu i podmenu.
	 */
	public function register_menu(): void {
		$parent = self::forms_parent_slug();
		$cap    = Capabilities::MANAGE_FORMS;

		add_menu_page(
			__( 'Forms', 'fast-forms' ),
			__( 'Forms', 'fast-forms' ),
			$cap,
			$parent,
			'',
			'dashicons-feedback',
			26
		);

		add_submenu_page(
			$parent,
			__( 'Add new form', 'fast-forms' ),
			__( 'Add new form', 'fast-forms' ),
			$cap,
			'post-new.php?post_type=' . FormPostType::POST_TYPE
		);

		add_submenu_page(
			$parent,
			__( 'Form submissions', 'fast-forms' ),
			__( 'Form submissions', 'fast-forms' ),
			$cap,
			'edit.php?post_type=' . EntryPostType::POST_TYPE
		);

		add_submenu_page(
			$parent,
			__( 'Global settings', 'fast-forms' ),
			__( 'Global settings', 'fast-forms' ),
			$cap,
			self::PAGE_SETTINGS,
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			$parent,
			__( 'Form manager', 'fast-forms' ),
			__( 'Form manager', 'fast-forms' ),
			$cap,
			self::PAGE_MANAGER,
			array( $this, 'render_manager_page' )
		);
	}

	/**
	 * Ładuje style admina na ekranach wtyczki.
	 *
	 * @param string $hook Bieżący hook ekranu admina.
	 */
	public function enqueue_assets( string $hook ): void {
		$screens = array(
			'toplevel_page_ff-settings',
			'formularze_page_' . self::PAGE_SETTINGS,
			'formularze_page_' . self::PAGE_MANAGER,
			'wpf_form_page_' . self::PAGE_SETTINGS,
			'wpf_form_page_' . self::PAGE_MANAGER,
		);

		if ( ! in_array( $hook, $screens, true ) ) {
			return;
		}

		wp_enqueue_style(
			'fast-forms-admin',
			FF_PLUGIN_URL . 'assets/admin/css/admin.css',
			array(),
			AssetVersion::get( 'assets/admin/css/admin.css' )
		);
	}

	/**
	 * Renderuje ekran ustawień globalnych.
	 */
	public function render_settings_page(): void {
		if ( ! Capabilities::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'fast-forms' ) );
		}

		$this->render_template(
			'settings',
			array(
				'title'    => __( 'Global settings', 'fast-forms' ),
				'settings' => SettingsAdmin::get_settings(),
			)
		);
	}

	/**
	 * Renderuje ekran managera formularzy.
	 */
	public function render_manager_page(): void {
		if ( ! Capabilities::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'fast-forms' ) );
		}

		$this->render_template(
			'manager',
			array(
				'title' => __( 'Form manager', 'fast-forms' ),
				'forms' => ManagerAdmin::get_forms(),
			)
		);
	}

	/**
	 * Ładuje plik szablonu admina.
	 *
	 * @param string               $template Nazwa szablonu bez rozszerzenia.
	 * @param array<string, mixed> $vars     Zmienne przekazywane do szablonu.
	 */
	private function render_template( string $template, array $vars = array() ): void {
		$file = FF_PLUGIN_DIR . 'templates/admin/' . $template . '.php';

		if ( ! is_readable( $file ) ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- kontrolowane zmienne szablonu.
		extract( $vars, EXTR_SKIP );

		include $file;
	}
}
