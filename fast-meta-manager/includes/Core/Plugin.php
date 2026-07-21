<?php

/**
 * Main plugin orchestrator.
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

namespace FFMM\Core;

if (! defined('ABSPATH')) {
	exit;
}

use FFMM\Admin\Settings_Manager;
use FFMM\Admin\Tools_Page;
use FFMM\Admin\Meta_Box;
use FFMM\API\Save_Controller;
use FFMM\Render\Meta_Table_Renderer;
use FFMM\Security\Permission_Manager;
use FFMM\Target\Post_Target_Adapter;

class Plugin
{
	private Loader $loader;
	private Target_Resolver $target_resolver;
	private Permission_Manager $permission_manager;
	private Settings_Manager $settings_manager;
	private Meta_Table_Renderer $renderer;
	private Tools_Page $tools_page;
	private Meta_Box $meta_box;
	private Save_Controller $save_controller;

	public function __construct()
	{
		$this->loader             = new Loader();
		$this->target_resolver    = new Target_Resolver();
		$this->permission_manager = new Permission_Manager();
		$this->settings_manager   = new Settings_Manager($this->target_resolver, $this->permission_manager);
		$this->renderer           = new Meta_Table_Renderer();
		$this->tools_page         = new Tools_Page($this->target_resolver, $this->renderer, $this->permission_manager);
		$this->meta_box           = new Meta_Box($this->renderer, $this->permission_manager, new Post_Target_Adapter());
		$this->save_controller    = new Save_Controller($this->target_resolver, $this->permission_manager);

		$this->register_admin();
		$this->register_api();
		$this->register_plugin_links();
	}

	public function run(): void
	{
		$this->loader->run();
	}

	private function register_admin(): void
	{
		$this->loader->add_action('admin_init', $this->settings_manager, 'register_settings');
		$this->loader->add_action('admin_menu', $this, 'register_admin_menu');
		$this->loader->add_action('add_meta_boxes', $this->meta_box, 'register_meta_boxes');
		$this->loader->add_action('admin_notices', $this, 'render_notices');
		$this->loader->add_action('admin_enqueue_scripts', $this->renderer, 'enqueue_assets');
	}

	private function register_api(): void
	{
		$this->loader->add_action('admin_post_ffmm_add_meta', $this->save_controller, 'handle_add_meta');
		$this->loader->add_action('admin_post_ffmm_save_meta', $this->save_controller, 'handle_save_meta');
		$this->loader->add_action('admin_post_ffmm_delete_meta', $this->save_controller, 'handle_delete_meta');
	}

	private function register_plugin_links(): void
	{
		$this->loader->add_action('plugin_action_links_' . plugin_basename(FFMM_PLUGIN_FILE), $this, 'add_action_links', 10, 1);
	}

	public function register_admin_menu(): void
	{
		$this->tools_page->register_menu($this->settings_manager);
	}

	/**
	 * @param array<int, string> $links
	 * @return array<int, string>
	 */
	public function add_action_links(array $links): array
	{
		array_unshift(
			$links,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url(admin_url('tools.php?page=fast-meta-manager-settings')),
				esc_html__('Settings', 'fast-meta-manager')
			)
		);

		return $links;
	}

	public function render_notices(): void
	{
		if (! isset($_GET['ffmm_msg'])) {
			return;
		}

		$msg     = sanitize_text_field(wp_unslash((string) $_GET['ffmm_msg']));
		$is_ok   = 'success' === $msg;
		$class   = $is_ok ? 'notice notice-success' : 'notice notice-error';
		$message = $is_ok
			? __('Metadata updated.', 'fast-meta-manager')
			: __('Operation failed. Please verify target, permissions and input value.', 'fast-meta-manager');
		if ('protected_meta_key_blocked' === $msg) {
			$message = __('Editing protected/system meta keys is disabled in settings.', 'fast-meta-manager');
		}

		printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
	}
}
