<?php
/**
 * Main plugin bootstrap class.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Core;

use Weblo\FastForms\Admin\EntryListFilters;
use Weblo\FastForms\Admin\ManagerAdmin;
use Weblo\FastForms\Admin\Menu;
use Weblo\FastForms\Admin\PluginLinks;
use Weblo\FastForms\Admin\SettingsAdmin;
use Weblo\FastForms\Admin\EntryAdmin;
use Weblo\FastForms\Admin\EntryFileDownload;
use Weblo\FastForms\FormBuilder\Assets as BuilderAssets;
use Weblo\FastForms\FormBuilder\BuilderScreen;
use Weblo\FastForms\FormBuilder\FormSaver;
use Weblo\FastForms\FormBuilder\RestApi;
use Weblo\FastForms\Frontend\Assets as FrontendAssets;
use Weblo\FastForms\Frontend\Shortcode;
use Weblo\FastForms\PostTypes\EntryPostType;
use Weblo\FastForms\PostTypes\FormPostType;
use Weblo\FastForms\Submission\SubmitRestApi;
use Weblo\FastForms\Support\Capabilities;

/**
 * Spina moduły wtyczki i rejestruje hooki.
 */
final class Plugin {

	private const CAPS_OPTION = 'ff_capabilities_version';

	private static ?self $instance = null;

	private bool $booted = false;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Uruchamia wtyczkę.
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		$this->maybe_upgrade_capabilities();

		ff_debug_log( 'Plugin boot', array( 'version' => FF_VERSION ) );

		( new FormPostType() )->register();
		( new EntryPostType() )->register();
		( new RestApi() )->register();
		( new SubmitRestApi() )->register();
		( new FormSaver() )->register();
		( new FrontendAssets() )->register();
		( new Shortcode() )->register();
		( new EntryFileDownload() )->register();

		if ( is_admin() ) {
			( new Menu() )->register();
			( new PluginLinks() )->register();
			( new BuilderScreen() )->register();
			( new BuilderAssets() )->register();
			( new EntryAdmin() )->register();
			( new EntryListFilters() )->register();
			( new SettingsAdmin() )->register();
			( new ManagerAdmin() )->register();
		}
	}

	/**
	 * Zapewnia capability po aktualizacji wtyczki bez ponownej aktywacji.
	 */
	private function maybe_upgrade_capabilities(): void {
		$stored = get_option( self::CAPS_OPTION, '' );

		if ( $stored === FF_VERSION ) {
			return;
		}

		Capabilities::add_to_roles();
		update_option( self::CAPS_OPTION, FF_VERSION );
	}
}
