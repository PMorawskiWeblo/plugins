<?php
/**
 * Capability checks for Fast Meta Manager.
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

namespace FFMM\Security;

if (! defined('ABSPATH')) {
	exit;
}

class Permission_Manager
{
	public function can_access_tools_page(): bool
	{
		return current_user_can($this->get_view_capability());
	}

	public function can_edit_meta(): bool
	{
		$settings = get_option('ffmm_settings', []);
		$mode     = is_array($settings) && isset($settings['mode']) ? (string) $settings['mode'] : 'edit';

		if ('edit' !== $mode) {
			return false;
		}

		return current_user_can($this->get_edit_capability());
	}

	public function can_manage_settings(): bool
	{
		return current_user_can($this->get_settings_capability());
	}

	public function get_view_capability(): string
	{
		return apply_filters('ffmm/capability_check', 'manage_options', 'view');
	}

	public function get_edit_capability(): string
	{
		return apply_filters('ffmm/capability_check', 'manage_options', 'edit');
	}

	public function get_settings_capability(): string
	{
		return apply_filters('ffmm/capability_check', 'manage_options', 'settings');
	}
}
