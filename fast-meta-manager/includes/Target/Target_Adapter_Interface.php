<?php
/**
 * Interface for all metadata target adapters.
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

namespace FFMM\Target;

interface Target_Adapter_Interface
{
	/**
	 * @return array<string, array<int, mixed>>
	 */
	public function get_meta(int $object_id): array;

	public function add_meta(int $object_id, string $meta_key, $meta_value): bool;

	public function update_meta(int $object_id, string $meta_key, $meta_value, $prev_value = null): bool;

	public function delete_meta(int $object_id, string $meta_key, $meta_value = null): bool;

	public function can_access(int $object_id): bool;

	public function get_object_label(int $object_id): string;
}
