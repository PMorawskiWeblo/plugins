<?php
/**
 * User metadata adapter.
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

namespace FFMM\Target;

class User_Target_Adapter implements Target_Adapter_Interface
{
	public function get_meta(int $object_id): array
	{
		return get_user_meta($object_id);
	}

	public function add_meta(int $object_id, string $meta_key, $meta_value): bool
	{
		return false !== add_user_meta($object_id, $meta_key, $meta_value);
	}

	public function update_meta(int $object_id, string $meta_key, $meta_value, $prev_value = null): bool
	{
		return false !== update_user_meta($object_id, $meta_key, $meta_value, $prev_value);
	}

	public function delete_meta(int $object_id, string $meta_key, $meta_value = null): bool
	{
		return delete_user_meta($object_id, $meta_key, $meta_value);
	}

	public function can_access(int $object_id): bool
	{
		return current_user_can('edit_user', $object_id);
	}

	public function get_object_label(int $object_id): string
	{
		$user = get_user_by('id', $object_id);
		if (! $user) {
			return sprintf(__('User #%d', 'fast-meta-manager'), $object_id);
		}

		return sprintf(__('User #%1$d: %2$s', 'fast-meta-manager'), $object_id, $user->user_login);
	}
}
