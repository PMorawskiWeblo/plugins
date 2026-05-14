<?php
/**
 * Plugin deactivation.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Deactivator {
	/**
	 * Deactivation handler.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'storeguide_ai_run_index_batch' );
	}
}
