<?php
/**
 * Plugin activation routines.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Core;

use WooProductPersonalizer\Infrastructure\Repository\SettingsRepository;
use WooProductPersonalizer\Infrastructure\Uploads\UploadsManager;

defined( 'ABSPATH' ) || exit;

/**
 * Class Installer
 */
class Installer {

	/**
	 * Run on plugin activation.
	 *
	 * @return void
	 */
	public static function activate() {
		$settings = new SettingsRepository();
		$settings->ensure_defaults();

		$uploads = new UploadsManager( new Logger( $settings ) );
		$uploads->ensure_directories();

		$cpt = new \WooProductPersonalizer\Admin\LayoutPostType();
		$cpt->register();

		flush_rewrite_rules();
	}
}
