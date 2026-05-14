<?php
/**
 * Internationalization loader.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_I18n {
	/**
	 * Load plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'storeguide-ai', false, dirname( STOREGUIDE_AI_PLUGIN_BASENAME ) . '/languages/' );
	}
}
