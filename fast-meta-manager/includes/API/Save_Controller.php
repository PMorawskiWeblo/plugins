<?php

/**
 * Handle metadata mutations from admin forms.
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

namespace FFMM\API;

if (! defined('ABSPATH')) {
	exit;
}

use FFMM\Core\Target_Resolver;
use FFMM\Security\Permission_Manager;

class Save_Controller
{
	private Target_Resolver $target_resolver;
	private Permission_Manager $permission_manager;

	public function __construct(Target_Resolver $target_resolver, Permission_Manager $permission_manager)
	{
		$this->target_resolver    = $target_resolver;
		$this->permission_manager = $permission_manager;
	}

	public function handle_add_meta(): void
	{
		$this->assert_request('ffmm_add_meta_action');
		$this->mutate('add');
	}

	public function handle_save_meta(): void
	{
		$this->assert_request('ffmm_save_meta_action');
		$this->mutate('save');
	}

	public function handle_delete_meta(): void
	{
		$this->assert_request('ffmm_delete_meta_action');
		$this->mutate('delete');
	}

	private function assert_request(string $nonce_action): void
	{
		if (! $this->permission_manager->can_edit_meta()) {
			wp_die(esc_html__('Insufficient permissions.', 'fast-meta-manager'));
		}

		check_admin_referer($nonce_action, 'ffmm_nonce');
	}

	private function mutate(string $mode): void
	{
		$target    = isset($_POST['target']) ? sanitize_key(wp_unslash((string) $_POST['target'])) : '';
		$object_id = isset($_POST['object_id']) ? (int) $_POST['object_id'] : 0;
		$meta_key  = isset($_POST['meta_key']) ? sanitize_text_field(wp_unslash((string) $_POST['meta_key'])) : '';
		$value_raw = isset($_POST['meta_value']) ? wp_unslash((string) $_POST['meta_value']) : '';
		$value     = $this->normalize_value($value_raw);
		$prev_raw  = isset($_POST['prev_value']) ? wp_unslash((string) $_POST['prev_value']) : null;
		$prev_val  = null !== $prev_raw && '' !== $prev_raw ? json_decode($prev_raw, true) : null;

		$return_url_raw = isset($_POST['return_url']) ? wp_unslash((string) $_POST['return_url']) : '';
		$return_url     = '' !== $return_url_raw ? esc_url_raw($return_url_raw) : '';

		if ($object_id < 1 || '' === $meta_key) {
			$this->redirect_back($target, $object_id, 'invalid_payload', $return_url);
		}

		if (! $this->can_edit_meta_key($meta_key)) {
			$this->redirect_back($target, $object_id, 'protected_meta_key_blocked', $return_url);
		}

		$adapter = $this->target_resolver->get_adapter($target);
		if (! $adapter || ! $adapter->can_access($object_id)) {
			$this->redirect_back($target, $object_id, 'access_denied', $return_url);
		}

		$allowed_keys = apply_filters('ffmm/allowed_meta_keys', null, $target, $object_id);
		if (is_array($allowed_keys) && ! in_array($meta_key, $allowed_keys, true)) {
			$this->redirect_back($target, $object_id, 'meta_key_not_allowed', $return_url);
		}

		$value = apply_filters('ffmm/sanitize_meta_value', $value, $meta_key, $target, $object_id);
		do_action('ffmm/before_save', $target, $object_id, $meta_key, $value, $mode);

		$ok = false;
		if ('add' === $mode) {
			$ok = $adapter->add_meta($object_id, $meta_key, $value);
		} elseif ('save' === $mode) {
			$ok = $adapter->update_meta($object_id, $meta_key, $value, $prev_val);
		} elseif ('delete' === $mode) {
			$ok = $adapter->delete_meta($object_id, $meta_key, $prev_val);
		}

		do_action('ffmm/after_save', $target, $object_id, $meta_key, $value, $mode, $ok);
		$this->redirect_back($target, $object_id, $ok ? 'success' : 'operation_failed', $return_url);
	}

	/**
	 * @return mixed
	 */
	private function normalize_value(string $value)
	{
		$trimmed = trim($value);
		if ('' !== $trimmed && ('{' === $trimmed[0] || '[' === $trimmed[0])) {
			$decoded = json_decode($trimmed, true);
			if (JSON_ERROR_NONE === json_last_error()) {
				return $decoded;
			}
		}

		if (! current_user_can('unfiltered_html')) {
			return wp_kses_post($value);
		}

		return $value;
	}

	private function redirect_back(string $target, int $object_id, string $status, string $return_url = ''): void
	{
		$default_url = add_query_arg(
			[
				'page'      => 'fast-meta-manager',
				'target'    => $target,
				'object_id' => $object_id,
				'ffmm_msg'  => $status,
			],
			admin_url('tools.php')
		);

		$url = '' !== $return_url
			? add_query_arg('ffmm_msg', $status, remove_query_arg('ffmm_msg', $return_url))
			: $default_url;

		wp_safe_redirect($url, 302, 'Fast Meta Manager');
		exit;
	}

	private function can_edit_meta_key(string $meta_key): bool
	{
		$settings = get_option('ffmm_settings', []);
		$allow    = is_array($settings) && ! empty($settings['allow_protected_meta']);

		if ($allow) {
			return true;
		}

		$is_protected = $this->is_protected_meta_key($meta_key);
		$is_protected = (bool) apply_filters('ffmm/is_protected_meta_key', $is_protected, $meta_key);

		return ! $is_protected;
	}

	private function is_protected_meta_key(string $meta_key): bool
	{
		if ('' === $meta_key) {
			return true;
		}

		$blocked_keys = [
			'_edit_lock',
			'_edit_last',
			'_wp_page_template',
		];

		if (in_array($meta_key, $blocked_keys, true)) {
			return true;
		}

		if (str_starts_with($meta_key, '_')) {
			return true;
		}

		return false;
	}
}
