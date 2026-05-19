<?php
/**
 * Plugin deactivation routines.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class Deactivator
 */
class Deactivator {

	/**
	 * Run on plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( Cron::EVENT_HOOK );
		flush_rewrite_rules();
	}
}
