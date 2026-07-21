<?php
/**
 * Term metadata adapter.
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

namespace FFMM\Target;

class Term_Target_Adapter implements Target_Adapter_Interface
{
	public function get_meta(int $object_id): array
	{
		return get_term_meta($object_id);
	}

	public function add_meta(int $object_id, string $meta_key, $meta_value): bool
	{
		return false !== add_term_meta($object_id, $meta_key, $meta_value);
	}

	public function update_meta(int $object_id, string $meta_key, $meta_value, $prev_value = null): bool
	{
		return false !== update_term_meta($object_id, $meta_key, $meta_value, $prev_value);
	}

	public function delete_meta(int $object_id, string $meta_key, $meta_value = null): bool
	{
		return delete_term_meta($object_id, $meta_key, $meta_value);
	}

	public function can_access(int $object_id): bool
	{
		return current_user_can('manage_categories') && null !== get_term($object_id);
	}

	public function get_object_label(int $object_id): string
	{
		$term = get_term($object_id);
		if (! $term || is_wp_error($term)) {
			return sprintf(__('Term #%d', 'fast-meta-manager'), $object_id);
		}

		return sprintf(__('Term #%1$d: %2$s', 'fast-meta-manager'), $object_id, $term->name);
	}
}
