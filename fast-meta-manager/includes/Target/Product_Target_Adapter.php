<?php
/**
 * Product metadata adapter.
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

namespace FFMM\Target;

class Product_Target_Adapter extends Post_Target_Adapter
{
	public function can_access(int $object_id): bool
	{
		$post_type = get_post_type($object_id);
		return 'product' === $post_type && current_user_can('edit_post', $object_id);
	}

	public function get_object_label(int $object_id): string
	{
		$title = get_the_title($object_id);
		return sprintf(__('Product #%1$d: %2$s', 'fast-meta-manager'), $object_id, $title ? $title : __('(no title)', 'fast-meta-manager'));
	}
}
