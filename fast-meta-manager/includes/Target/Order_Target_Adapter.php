<?php
/**
 * WooCommerce order metadata adapter with HPOS support.
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

namespace FFMM\Target;

class Order_Target_Adapter implements Target_Adapter_Interface
{
	public function get_meta(int $object_id): array
	{
		$order = $this->get_order($object_id);
		if (! $order) {
			return [];
		}

		$rows = [];
		foreach ($order->get_meta_data() as $meta_item) {
			$key = (string) $meta_item->key;
			if (! isset($rows[$key])) {
				$rows[$key] = [];
			}
			$rows[$key][] = $meta_item->value;
		}

		return $rows;
	}

	public function add_meta(int $object_id, string $meta_key, $meta_value): bool
	{
		$order = $this->get_order($object_id);
		if (! $order) {
			return false;
		}

		$order->add_meta_data($meta_key, $meta_value, false);
		$order->save();
		return true;
	}

	public function update_meta(int $object_id, string $meta_key, $meta_value, $prev_value = null): bool
	{
		$order = $this->get_order($object_id);
		if (! $order) {
			return false;
		}

		$order->update_meta_data($meta_key, $meta_value);
		$order->save();
		return true;
	}

	public function delete_meta(int $object_id, string $meta_key, $meta_value = null): bool
	{
		$order = $this->get_order($object_id);
		if (! $order) {
			return false;
		}

		$order->delete_meta_data($meta_key);
		$order->save();
		return true;
	}

	public function can_access(int $object_id): bool
	{
		return current_user_can('edit_shop_order', $object_id) || current_user_can('manage_woocommerce');
	}

	public function get_object_label(int $object_id): string
	{
		$order = $this->get_order($object_id);
		if (! $order) {
			return sprintf(__('Order #%d', 'fast-meta-manager'), $object_id);
		}

		return sprintf(__('Order #%1$d (%2$s)', 'fast-meta-manager'), $order->get_id(), $this->is_hpos_enabled() ? 'HPOS' : 'Classic');
	}

	private function get_order(int $object_id)
	{
		if (! function_exists('wc_get_order')) {
			return null;
		}

		return wc_get_order($object_id);
	}

	private function is_hpos_enabled(): bool
	{
		if (! class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
			return false;
		}

		return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}
}
