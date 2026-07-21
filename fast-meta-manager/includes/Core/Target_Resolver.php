<?php
/**
 * Resolve and validate supported metadata targets.
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

namespace FFMM\Core;

if (! defined('ABSPATH')) {
	exit;
}

use FFMM\Target\Comment_Target_Adapter;
use FFMM\Target\Custom_Post_Type_Target_Adapter;
use FFMM\Target\Order_Target_Adapter;
use FFMM\Target\Post_Target_Adapter;
use FFMM\Target\Product_Target_Adapter;
use FFMM\Target\Target_Adapter_Interface;
use FFMM\Target\Term_Target_Adapter;
use FFMM\Target\User_Target_Adapter;

class Target_Resolver
{
	private const CPT_TARGET_PREFIX = 'post_type__';

	/** @var array<string, Target_Adapter_Interface> */
	private array $adapters;

	public function __construct()
	{
		$this->adapters = [
			'post'    => new Post_Target_Adapter(),
			'product' => new Product_Target_Adapter(),
			'order'   => new Order_Target_Adapter(),
			'term'    => new Term_Target_Adapter(),
			'user'    => new User_Target_Adapter(),
			'comment' => new Comment_Target_Adapter(),
		];

		$this->register_custom_post_type_adapters();
	}

	/**
	 * @return array<string>
	 */
	public function get_all_targets(): array
	{
		$this->register_custom_post_type_adapters();

		$all_targets = array_keys($this->adapters);

		/** @var array<string> $all_targets */
		return apply_filters('ffmm/allowed_targets', $all_targets);
	}

	/**
	 * @return array<string>
	 */
	public function get_allowed_targets(): array
	{
		$all_targets = $this->get_all_targets();

		$settings = get_option('ffmm_settings', []);
		$enabled  = is_array($settings) && isset($settings['enabled_targets']) && is_array($settings['enabled_targets'])
			? array_map('strval', $settings['enabled_targets'])
			: $all_targets;

		$allowed = array_values(array_intersect($all_targets, $enabled));

		// Auto-enable newly discovered custom post types for existing installs.
		foreach ($all_targets as $target) {
			if (! str_starts_with($target, self::CPT_TARGET_PREFIX)) {
				continue;
			}
			if (! in_array($target, $allowed, true)) {
				$allowed[] = $target;
			}
		}

		return $allowed;
	}

	/**
	 * @return array<string, string>
	 */
	public function get_target_labels(): array
	{
		$labels = [];

		foreach ($this->get_all_targets() as $target) {
			$labels[$target] = $this->get_target_label($target);
		}

		return $labels;
	}

	public function get_target_label(string $target): string
	{
		if ('post' === $target) {
			return __('Post', 'fast-meta-manager');
		}
		if ('product' === $target) {
			return __('Product', 'fast-meta-manager');
		}
		if ('order' === $target) {
			return __('Order', 'fast-meta-manager');
		}
		if ('term' === $target) {
			return __('Term', 'fast-meta-manager');
		}
		if ('user' === $target) {
			return __('User', 'fast-meta-manager');
		}
		if ('comment' === $target) {
			return __('Comment', 'fast-meta-manager');
		}

		if (str_starts_with($target, self::CPT_TARGET_PREFIX)) {
			$post_type = str_replace(self::CPT_TARGET_PREFIX, '', $target);
			$object    = get_post_type_object($post_type);
			if ($object && isset($object->labels->singular_name)) {
				return sprintf(
					/* translators: %s: custom post type label */
					__('post type: %s', 'fast-meta-manager'),
					(string) $object->labels->singular_name
				);
			}

			return sprintf(
				/* translators: %s: custom post type slug */
				__('post type: %s', 'fast-meta-manager'),
				$post_type
			);
		}

		return $target;
	}

	public function get_adapter(string $target): ?Target_Adapter_Interface
	{
		$this->register_custom_post_type_adapters();

		if (! in_array($target, $this->get_allowed_targets(), true)) {
			return null;
		}

		$adapter = $this->adapters[$target] ?? null;
		if (! $adapter) {
			return null;
		}

		/** @var Target_Adapter_Interface $adapter */
		return apply_filters('ffmm/target_adapter', $adapter, $target);
	}

	private function register_custom_post_type_adapters(): void
	{
		$post_types = get_post_types(['show_ui' => true], 'names');

		foreach ($post_types as $post_type) {
			$post_type = sanitize_key((string) $post_type);
			if (in_array($post_type, ['post', 'page', 'product', 'attachment', 'revision', 'nav_menu_item', 'shop_order'], true)) {
				continue;
			}

			$target_key                  = self::CPT_TARGET_PREFIX . $post_type;
			$this->adapters[$target_key] = new Custom_Post_Type_Target_Adapter($post_type);
		}
	}
}
