<?php

/**
 * Plugin settings repository.
 *
 * @package WooProductPersonalizer
 */

namespace WooProductPersonalizer\Infrastructure\Repository;

defined('ABSPATH') || exit;

/**
 * Class SettingsRepository
 */
class SettingsRepository
{

	const OPTION_KEY = 'wpp_settings';

	/**
	 * Cached settings for the current request.
	 *
	 * @var array<string, mixed>|null
	 */
	private $cached;

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public function defaults()
	{
		return array(
			'max_upload_mb'          => 10,
			'allowed_mime_types'     => array('image/jpeg', 'image/png', 'image/webp'),
			'frontend_mode'          => 'modal',
			'button_position'        => 'after_add_to_cart',
			'shortcode_only'         => false,
			'default_button_label'           => __('Personalize product', 'woo-product-personalizer'),
			'default_button_label_completed' => _x('Personalized', 'completed personalize button', 'woo-product-personalizer'),
			'default_accept_text'            => __('I accept the preview shown above.', 'woo-product-personalizer'),
			'debug_enabled'          => false,
			'cleanup_enabled'        => false,
			'cleanup_interval'       => 14,
			'cleanup_only_completed' => true,
		);
	}

	/**
	 * Ensure defaults exist.
	 *
	 * @return void
	 */
	public function ensure_defaults()
	{
		if (false === get_option(self::OPTION_KEY, false)) {
			add_option(self::OPTION_KEY, $this->defaults());
		}
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function all()
	{
		if (null !== $this->cached) {
			return $this->cached;
		}

		$stored       = get_option(self::OPTION_KEY, array());
		$this->cached = wp_parse_args(is_array($stored) ? $stored : array(), $this->defaults());

		return $this->cached;
	}

	/**
	 * Get single setting.
	 *
	 * @param string $key     Key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		$all = $this->all();
		return array_key_exists($key, $all) ? $all[$key] : $default;
	}

	/**
	 * Update settings.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return bool
	 */
	public function update(array $settings)
	{
		$merged       = wp_parse_args($settings, $this->defaults());
		$this->cached = $merged;
		return update_option(self::OPTION_KEY, $merged);
	}

	/**
	 * Is debug enabled.
	 *
	 * @return bool
	 */
	public function is_debug_enabled()
	{
		return (bool) $this->get('debug_enabled', false);
	}

	/**
	 * Is cleanup enabled.
	 *
	 * @return bool
	 */
	public function is_cleanup_enabled()
	{
		return (bool) $this->get('cleanup_enabled', false);
	}

	/**
	 * Get frontend mode.
	 *
	 * @return string
	 */
	public function get_frontend_mode()
	{
		$mode = $this->get('frontend_mode', 'modal');
		return in_array($mode, array('inline', 'modal'), true) ? $mode : 'modal';
	}

	/**
	 * Get button position.
	 *
	 * @return string
	 */
	public function get_button_position()
	{
		return (string) $this->get('button_position', 'after_add_to_cart');
	}

	/**
	 * Is shortcode-only mode.
	 *
	 * @return bool
	 */
	public function is_shortcode_only()
	{
		return (bool) $this->get('shortcode_only', false);
	}
}