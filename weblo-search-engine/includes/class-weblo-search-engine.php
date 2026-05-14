<?php
/**
 * The core plugin class.
 *
 * @package Weblo_Search_Engine
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The core plugin class.
 */
class Weblo_Search_Engine {

	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 *
	 * @var Weblo_Search_Engine_Loader
	 */
	protected $loader;

	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_ajax_hooks();
		$this->define_shortcode_hooks();
		$this->define_assets_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function load_dependencies() {
		require_once WEBLO_SEARCH_ENGINE_PATH . 'includes/class-weblo-search-engine-loader.php';
		require_once WEBLO_SEARCH_ENGINE_PATH . 'includes/class-weblo-search-engine-i18n.php';
		require_once WEBLO_SEARCH_ENGINE_PATH . 'includes/class-weblo-search-assets.php';
		require_once WEBLO_SEARCH_ENGINE_PATH . 'admin/class-weblo-search-admin.php';
		require_once WEBLO_SEARCH_ENGINE_PATH . 'public/class-weblo-search-frontend.php';
		require_once WEBLO_SEARCH_ENGINE_PATH . 'includes/class-weblo-search-ajax.php';
		require_once WEBLO_SEARCH_ENGINE_PATH . 'includes/class-weblo-search-shortcode.php';

		$this->loader = new Weblo_Search_Engine_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 */
	private function set_locale() {
		$plugin_i18n = new Weblo_Search_Engine_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Weblo_Search_Admin( $this->get_version() );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
		$this->loader->add_action( 'save_post_product', $plugin_admin, 'invalidate_cache', 10, 1 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 */
	private function define_public_hooks() {
		$plugin_public = new Weblo_Search_Frontend( $this->get_version() );
		$this->loader->add_action( 'pre_get_posts', $plugin_public, 'filter_sale_products', 10, 1 );
		$this->loader->add_filter( 'query_vars', $plugin_public, 'add_sale_query_var' );
	}

	/**
	 * Register all of the hooks related to AJAX functionality.
	 */
	private function define_ajax_hooks() {
		$plugin_ajax = new Weblo_Search_Ajax();
		$this->loader->add_action( 'wp_ajax_weblo_search', $plugin_ajax, 'search_products_ajax' );
		$this->loader->add_action( 'wp_ajax_nopriv_weblo_search', $plugin_ajax, 'search_products_ajax' );
		$this->loader->add_action( 'wp_ajax_weblo_get_products_html', $plugin_ajax, 'get_products_html_ajax' );
		$this->loader->add_action( 'wp_ajax_nopriv_weblo_get_products_html', $plugin_ajax, 'get_products_html_ajax' );
	}

	/**
	 * Register all of the hooks related to shortcode functionality.
	 */
	private function define_shortcode_hooks() {
		$plugin_shortcode = new Weblo_Search_Shortcode();
		$this->loader->add_action( 'init', $plugin_shortcode, 'register_shortcode' );
	}

	/**
	 * Register all of the hooks related to assets functionality.
	 */
	private function define_assets_hooks() {
		$plugin_assets = new Weblo_Search_Assets( $this->get_version() );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_assets, 'enqueue_frontend_assets' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_assets, 'enqueue_admin_assets' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return Weblo_Search_Engine_Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return string The version number of the plugin.
	 */
	public function get_version() {
		return WEBLO_SEARCH_ENGINE_VERSION;
	}
}

