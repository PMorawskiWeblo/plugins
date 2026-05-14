<?php

/**
 * The assets functionality of the plugin.
 *
 * @package Weblo_Search_Engine
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

/**
 * The assets functionality of the plugin.
 */
class Weblo_Search_Assets
{

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
	public function __construct($version)
	{
		$this->version = $version;
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_frontend_assets()
	{
		$dev_mode = get_option('weblo_search_dev_mode', '0');
		$assets_version = get_option('weblo_search_assets_version', $this->version);
		
		// If dev mode is enabled, use timestamp. Otherwise use custom version or plugin version.
		$version = '1' === $dev_mode ? time() : $assets_version;

		// Enqueue CSS.
		wp_enqueue_style(
			'weblo-search-engine-frontend',
			WEBLO_SEARCH_ENGINE_URL . 'assets/css/frontend.min.css',
			array(),
			$version
		);

		// Enqueue JS.
		wp_enqueue_script(
			'weblo-search-engine-frontend',
			WEBLO_SEARCH_ENGINE_URL . 'assets/js/frontend.js',
			array('jquery'),
			$version,
			true
		);

		// Localize script.
		$trigger_class = get_option('weblo_search_trigger_class', 'search_engine_icon');
		$shop_url = wc_get_page_permalink('shop');
		if (! $shop_url) {
			$shop_url = home_url('/');
		}

		wp_localize_script(
			'weblo-search-engine-frontend',
			'webloSearchEngine',
			array(
				'ajaxurl'      => admin_url('admin-ajax.php'),
				'nonce'        => wp_create_nonce('weblo_search_nonce'),
				'triggerClass' => $trigger_class,
				'minLength'    => 3,
				'debounceTime' => 300,
				'noResultsText' => __('Unfortunately, nothing matches "%s". See suggestions below.', 'weblo-search-engine'),
				'shopUrl'      => $shop_url,
			)
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets($hook)
	{
		if ('toplevel_page_weblo-search-engine' !== $hook && 'search-engine_page_weblo-search-engine-developer' !== $hook) {
			return;
		}

		$dev_mode = get_option('weblo_search_dev_mode', '0');
		$assets_version = get_option('weblo_search_assets_version', $this->version);
		
		// If dev mode is enabled, use timestamp. Otherwise use custom version or plugin version.
		$version = '1' === $dev_mode ? time() : $assets_version;

		// Enqueue Select2 - try to use WooCommerce's Select2 first, fallback to CDN.
		if (class_exists('WooCommerce') && wp_script_is('select2', 'registered')) {
			// WooCommerce has Select2 registered, use it.
			wp_enqueue_style('select2');
			wp_enqueue_script('select2');
		} else {
			// Use CDN version.
			wp_enqueue_style(
				'select2',
				'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
				array(),
				'4.1.0'
			);

			wp_enqueue_script(
				'select2',
				'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
				array('jquery'),
				'4.1.0',
				true
			);
		}

		// Enqueue CSS.
		wp_enqueue_style(
			'weblo-search-engine-admin',
			WEBLO_SEARCH_ENGINE_URL . 'assets/css/admin.css',
			array('select2'),
			$version
		);

		// Enqueue jQuery UI Sortable for drag & drop sorting.
		wp_enqueue_script('jquery-ui-sortable');

		// Enqueue admin JS.
		wp_enqueue_script(
			'weblo-search-engine-admin',
			WEBLO_SEARCH_ENGINE_URL . 'assets/js/admin.js',
			array('jquery', 'select2', 'jquery-ui-sortable'),
			$version,
			true
		);

		// Localize script.
		wp_localize_script(
			'weblo-search-engine-admin',
			'webloSearchAdmin',
			array(
				'productsPlaceholder'  => __('Search products...', 'weblo-search-engine'),
				'categoriesPlaceholder' => __('Search categories...', 'weblo-search-engine'),
				'urlPlaceholder'       => __('URL', 'weblo-search-engine'),
				'textPlaceholder'      => __('Link Text', 'weblo-search-engine'),
				'removeText'          => __('Remove', 'weblo-search-engine'),
			)
		);
	}
}
