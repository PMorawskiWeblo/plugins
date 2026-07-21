<?php
/**
 * Post metadata adapter.
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

namespace FFMM\Target;

class Post_Target_Adapter implements Target_Adapter_Interface
{
	public function get_meta(int $object_id): array
	{
		return get_post_meta($object_id);
	}

	public function add_meta(int $object_id, string $meta_key, $meta_value): bool
	{
		return false !== add_post_meta($object_id, $meta_key, $meta_value);
	}

	public function update_meta(int $object_id, string $meta_key, $meta_value, $prev_value = null): bool
	{
		return false !== update_post_meta($object_id, $meta_key, $meta_value, $prev_value);
	}

	public function delete_meta(int $object_id, string $meta_key, $meta_value = null): bool
	{
		return delete_post_meta($object_id, $meta_key, $meta_value);
	}

	public function can_access(int $object_id): bool
	{
		return current_user_can('edit_post', $object_id);
	}

	public function get_object_label(int $object_id): string
	{
		$title = get_the_title($object_id);
		return sprintf(__('Post #%1$d: %2$s', 'fast-meta-manager'), $object_id, $title ? $title : __('(no title)', 'fast-meta-manager'));
	}
}
