<?php
/**
 * Comment metadata adapter.
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

namespace FFMM\Target;

class Comment_Target_Adapter implements Target_Adapter_Interface
{
	public function get_meta(int $object_id): array
	{
		return get_comment_meta($object_id);
	}

	public function add_meta(int $object_id, string $meta_key, $meta_value): bool
	{
		return false !== add_comment_meta($object_id, $meta_key, $meta_value);
	}

	public function update_meta(int $object_id, string $meta_key, $meta_value, $prev_value = null): bool
	{
		return false !== update_comment_meta($object_id, $meta_key, $meta_value, $prev_value);
	}

	public function delete_meta(int $object_id, string $meta_key, $meta_value = null): bool
	{
		return delete_comment_meta($object_id, $meta_key, $meta_value);
	}

	public function can_access(int $object_id): bool
	{
		return current_user_can('edit_comment', $object_id);
	}

	public function get_object_label(int $object_id): string
	{
		$comment = get_comment($object_id);
		if (! $comment) {
			return sprintf(__('Comment #%d', 'fast-meta-manager'), $object_id);
		}

		return sprintf(__('Comment #%1$d on post #%2$d', 'fast-meta-manager'), $object_id, (int) $comment->comment_post_ID);
	}
}
