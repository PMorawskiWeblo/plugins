<?php
/**
 * Admin module orchestrator.
 *
 * @package StoreGuideAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StoreGuide_AI_Admin {
	/**
	 * Menu handler.
	 *
	 * @var StoreGuide_AI_Admin_Menu
	 */
	private $menu;

	/**
	 * Settings handler.
	 *
	 * @var StoreGuide_AI_Settings
	 */
	private $settings;

	/**
	 * Developer settings handler.
	 *
	 * @var StoreGuide_AI_Developer_Settings
	 */
	private $developer_settings;

	/**
	 * Constructor.
	 *
	 * @param StoreGuide_AI_Admin_Menu          $menu Menu.
	 * @param StoreGuide_AI_Settings            $settings Settings.
	 * @param StoreGuide_AI_Developer_Settings  $developer_settings Developer settings.
	 */
	public function __construct( $menu, $settings, $developer_settings ) {
		$this->menu               = $menu;
		$this->settings           = $settings;
		$this->developer_settings = $developer_settings;
	}

	/**
	 * Register admin hooks.
	 *
	 * @param StoreGuide_AI_Loader $loader Hook loader.
	 * @return void
	 */
	public function register( $loader ) {
		$loader->add_action( 'admin_menu', $this->menu, 'register' );
		$loader->add_action( 'admin_init', $this->menu, 'handle_actions' );
		$loader->add_action( 'admin_enqueue_scripts', $this->menu, 'enqueue_assets' );
		$loader->add_action( 'wp_ajax_storeguide_ai_search_coupons', $this->menu, 'ajax_search_coupons' );
		$loader->add_action( 'admin_init', $this->settings, 'register' );
		$loader->add_action( 'admin_init', $this->developer_settings, 'register' );
	}
}
