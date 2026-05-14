<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package Weblo_Search_Engine
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The admin-specific functionality of the plugin.
 */
class Weblo_Search_Admin {

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $version The version of this plugin.
	 */
	public function __construct( $version ) {
		$this->version = $version;
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Weblo Search Engine', 'weblo-search-engine' ),
			__( 'Search Engine', 'weblo-search-engine' ),
			'manage_options',
			'weblo-search-engine',
			array( $this, 'render_settings_page' ),
			'dashicons-search',
			99
		);

		add_submenu_page(
			'weblo-search-engine',
			__( 'Settings', 'weblo-search-engine' ),
			__( 'Settings', 'weblo-search-engine' ),
			'manage_options',
			'weblo-search-engine',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'weblo-search-engine',
			__( 'Developer', 'weblo-search-engine' ),
			__( 'Developer', 'weblo-search-engine' ),
			'manage_options',
			'weblo-search-engine-developer',
			array( $this, 'render_developer_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		// Search placeholder.
		register_setting( 'weblo_search_settings', 'weblo_search_placeholder' );
		
		// Trigger CSS class.
		register_setting( 'weblo_search_settings', 'weblo_search_trigger_class' );
		
		// Search results limit.
		register_setting( 'weblo_search_settings', 'weblo_search_limit' );
		
		// Default hidden.
		register_setting( 'weblo_search_settings', 'weblo_search_default_hidden' );
		
		// Recommended products.
		register_setting( 'weblo_search_settings', 'weblo_search_recommended_products' );
		
		// Recommended categories.
		register_setting( 'weblo_search_settings', 'weblo_search_recommended_categories' );
		
		// Show promotions.
		register_setting( 'weblo_search_settings', 'weblo_search_show_promotions' );
		
		// Show all products.
		register_setting( 'weblo_search_settings', 'weblo_search_show_all_products' );
		
		// Custom links.
		register_setting( 'weblo_search_settings', 'weblo_search_custom_links' );
		
		// Dev mode.
		register_setting( 'weblo_search_settings', 'weblo_search_dev_mode' );
		
		// Assets version.
		register_setting( 'weblo_search_settings', 'weblo_search_assets_version' );
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include WEBLO_SEARCH_ENGINE_PATH . 'admin/views/settings-page.php';
	}

	/**
	 * Render developer page.
	 */
	public function render_developer_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include WEBLO_SEARCH_ENGINE_PATH . 'admin/views/developer-page.php';
	}

	/**
	 * Invalidate cache when product is saved.
	 *
	 * @param int $post_id Post ID.
	 */
	public function invalidate_cache( $post_id ) {
		if ( 'product' !== get_post_type( $post_id ) ) {
			return;
		}

		// Clear all search transients.
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_weblo_search_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_weblo_search_' ) . '%'
		) );
		
		// Clear sale products cache.
		delete_transient( 'weblo_products_on_sale_ids' );
	}
}

