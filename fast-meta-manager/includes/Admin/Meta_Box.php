<?php

/**
 * Meta box integration for post edit screens.
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

namespace FFMM\Admin;

use FFMM\Render\Meta_Table_Renderer;
use FFMM\Security\Permission_Manager;
use FFMM\Target\Post_Target_Adapter;

class Meta_Box
{
	private Meta_Table_Renderer $renderer;
	private Permission_Manager $permission_manager;
	private Post_Target_Adapter $post_adapter;

	public function __construct(Meta_Table_Renderer $renderer, Permission_Manager $permission_manager, Post_Target_Adapter $post_adapter)
	{
		$this->renderer           = $renderer;
		$this->permission_manager = $permission_manager;
		$this->post_adapter       = $post_adapter;
	}

	public function register_meta_boxes(): void
	{
		foreach ($this->get_supported_post_types() as $post_type) {
			add_meta_box(
				'ffmm_meta_box',
				__('Fast Meta Manager', 'fast-meta-manager'),
				[$this, 'render_meta_box'],
				$post_type,
				'normal',
				'default'
			);
		}
	}

	/**
	 * @return array<int, string>
	 */
	private function get_supported_post_types(): array
	{
		$post_types = get_post_types(['show_ui' => true], 'names');
		$post_types = array_values(array_filter($post_types, static function (string $post_type): bool {
			return ! in_array($post_type, ['attachment', 'revision', 'nav_menu_item'], true);
		}));

		/**
		 * Filter post types with the FFMM meta box enabled.
		 *
		 * @param array<int, string> $post_types
		 */
		$post_types = apply_filters('ffmm/meta_box_post_types', $post_types);

		if (! is_array($post_types)) {
			return ['post', 'page'];
		}

		return array_values(array_map('sanitize_key', $post_types));
	}

	/**
	 * @param \WP_Post $post
	 */
	public function render_meta_box($post): void
	{
		$post_id = (int) $post->ID;
		if ($post_id < 1 || ! $this->post_adapter->can_access($post_id)) {
			echo esc_html__('You are not allowed to view metadata for this content.', 'fast-meta-manager');
			return;
		}

		$meta_rows = $this->post_adapter->get_meta($post_id);
		$can_edit  = $this->permission_manager->can_edit_meta();
		$return_url = add_query_arg(
			[
				'post'   => $post_id,
				'action' => 'edit',
			],
			admin_url('post.php')
		);

		echo '<p>';
		echo esc_html__('Quick metadata view for current content. For advanced filtering, open Tools page.', 'fast-meta-manager');
		echo ' ';
		printf(
			'<a href="%1$s">%2$s</a>',
			esc_url(
				add_query_arg(
					[
						'page'      => 'fast-meta-manager',
						'target'    => 'post',
						'object_id' => $post_id,
					],
					admin_url('tools.php')
				)
			),
			esc_html__('Open full manager', 'fast-meta-manager')
		);
		echo '</p>';

		$this->renderer->render($meta_rows, 'post', $post_id, $can_edit, $return_url, true, false);
	}
}
