<?php
/**
 * Fired during plugin activation.
 *
 * @package Weblo_Search_Engine
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Fired during plugin activation.
 */
class Weblo_Search_Engine_Activator {

	/**
	 * Set default options on activation.
	 */
	public static function activate() {
		// Set default options.
		if ( ! get_option( 'weblo_search_placeholder' ) ) {
			add_option( 'weblo_search_placeholder', __( 'Search products...', 'weblo-search-engine' ) );
		}

		if ( ! get_option( 'weblo_search_trigger_class' ) ) {
			add_option( 'weblo_search_trigger_class', 'search_engine_icon' );
		}

		if ( ! get_option( 'weblo_search_limit' ) ) {
			add_option( 'weblo_search_limit', 10 );
		}

		if ( ! get_option( 'weblo_search_default_hidden' ) ) {
			add_option( 'weblo_search_default_hidden', '1' );
		}

		if ( ! get_option( 'weblo_search_recommended_products' ) ) {
			add_option( 'weblo_search_recommended_products', array() );
		}

		if ( ! get_option( 'weblo_search_recommended_categories' ) ) {
			add_option( 'weblo_search_recommended_categories', array() );
		}

		if ( ! get_option( 'weblo_search_custom_links' ) ) {
			add_option( 'weblo_search_custom_links', array() );
		}

		if ( ! get_option( 'weblo_search_show_promotions' ) ) {
			add_option( 'weblo_search_show_promotions', '0' );
		}

		if ( ! get_option( 'weblo_search_show_all_products' ) ) {
			add_option( 'weblo_search_show_all_products', '0' );
		}

		if ( ! get_option( 'weblo_search_dev_mode' ) ) {
			add_option( 'weblo_search_dev_mode', '0' );
		}

		if ( ! get_option( 'weblo_search_assets_version' ) ) {
			add_option( 'weblo_search_assets_version', WEBLO_SEARCH_ENGINE_VERSION );
		}
	}
}

