<?php
/**
 * Custom post type metadata adapter.
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

namespace FFMM\Target;

class Custom_Post_Type_Target_Adapter extends Post_Target_Adapter
{
	private string $post_type;

	public function __construct(string $post_type)
	{
		$this->post_type = sanitize_key($post_type);
	}

	public function can_access(int $object_id): bool
	{
		return $this->post_type === get_post_type($object_id) && current_user_can('edit_post', $object_id);
	}

	public function get_object_label(int $object_id): string
	{
		$title = get_the_title($object_id);
		return sprintf(
			/* translators: 1: post type slug, 2: post ID, 3: post title */
			__('%1$s #%2$d: %3$s', 'fast-meta-manager'),
			$this->post_type,
			$object_id,
			$title ? $title : __('(no title)', 'fast-meta-manager')
		);
	}
}
