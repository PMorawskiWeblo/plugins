<?php

/**
 * Main plugin composition root.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer;

use WooProductPersonalizer\Admin\AdminMenu;
use WooProductPersonalizer\Admin\LayoutImportExport;
use WooProductPersonalizer\Admin\LayoutMetaBox;
use WooProductPersonalizer\Admin\LayoutPostType;
use WooProductPersonalizer\Admin\OrderItemMetaDisplay;
use WooProductPersonalizer\Admin\OrderMetaBox;
use WooProductPersonalizer\Admin\OrderZipDownload;
use WooProductPersonalizer\Admin\PluginActionLinks;
use WooProductPersonalizer\Admin\ProductMetaBox;
use WooProductPersonalizer\Admin\SettingsPage;
use WooProductPersonalizer\Admin\Ajax\CleanupAjax;
use WooProductPersonalizer\Core\Assets;
use WooProductPersonalizer\Core\Container;
use WooProductPersonalizer\Core\Cron;
use WooProductPersonalizer\Core\DependencyChecker;
use WooProductPersonalizer\Core\Logger;
use WooProductPersonalizer\Frontend\PersonalizerController;
use WooProductPersonalizer\Frontend\Shortcode;
use WooProductPersonalizer\Infrastructure\Cleanup\CleanupService;
use WooProductPersonalizer\Infrastructure\Cleanup\OrderCleanupHooks;
use WooProductPersonalizer\Infrastructure\Repository\SettingsRepository;
use WooProductPersonalizer\Infrastructure\Uploads\UploadsManager;
use WooProductPersonalizer\Integrations\WooCommerce\CartHooks;
use WooProductPersonalizer\Integrations\WooCommerce\OrderDisplayHooks;
use WooProductPersonalizer\Integrations\WooCommerce\OrderHooks;
use WooProductPersonalizer\Integrations\WooCommerce\ProductHooks;
use WooProductPersonalizer\Integrations\WooCommerce\WooCommerceCompatibility;

defined('ABSPATH') || exit;

/**
 * Class Plugin
 */
class Plugin
{

	/**
	 * Service container.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Boot the plugin.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->container = new Container();
		$this->register_services();

		$checker = $this->container->get('dependency_checker');
		$checker->register_notice();

		if (! $checker->is_woocommerce_active()) {
			return;
		}

		load_plugin_textdomain(WPP_TEXT_DOMAIN, false, dirname(plugin_basename(WPP_PLUGIN_FILE)) . '/languages');

		WooCommerceCompatibility::register();

		$this->register_hooks();
	}

	/**
	 * Register core services.
	 *
	 * @return void
	 */
	private function register_services()
	{
		$this->container->set(
			'settings',
			static function () {
				return new SettingsRepository();
			}
		);

		$this->container->set(
			'logger',
			static function (Container $c) {
				return new Logger($c->get('settings'));
			}
		);

		$this->container->set(
			'dependency_checker',
			static function () {
				return new DependencyChecker();
			}
		);

		$this->container->set(
			'uploads',
			static function (Container $c) {
				return new UploadsManager($c->get('logger'));
			}
		);

		$this->container->set(
			'cleanup',
			static function (Container $c) {
				return new CleanupService(
					$c->get('settings'),
					$c->get('uploads'),
					$c->get('logger')
				);
			}
		);
	}

	/**
	 * Register hooks after WooCommerce is available.
	 *
	 * @return void
	 */
	private function register_hooks()
	{
		$settings = $this->container->get('settings');
		$logger   = $this->container->get('logger');
		$uploads  = $this->container->get('uploads');
		$cleanup  = $this->container->get('cleanup');

		new PluginActionLinks();
		new Assets($settings);
		new Cron($settings, $cleanup, $logger);
		new OrderCleanupHooks($cleanup);

		if (is_admin()) {
			new AdminMenu();
			new SettingsPage($settings, $cleanup, $logger);
			new LayoutPostType();
			new LayoutMetaBox();
			new LayoutImportExport();
			new ProductMetaBox();
			new OrderMetaBox($uploads);
			new OrderItemMetaDisplay( $uploads );
			new OrderZipDownload($uploads);
			new CleanupAjax($cleanup, $logger);
		}

		$personalizer = new PersonalizerController($settings, $logger, $uploads);
		new Shortcode($personalizer, $settings);
		new ProductHooks($personalizer, $settings);
		new CartHooks($settings, $logger, $uploads);
		new OrderHooks($settings, $logger, $uploads);
		new OrderDisplayHooks();
	}

	/**
	 * Get service container.
	 *
	 * @return Container
	 */
	public function container()
	{
		return $this->container;
	}
}
