<?php

/**
 * Plugin Name: Construction Mode
 * Plugin URI: www.weblo.pl
 * Description: Display a custom "site under construction" page for all visitors except admins and those with a secret bypass.
 * Version: 1.0.0
 * Author: Weblo
 * Author URI: https://www.weblo.pl
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: construction-mode
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
	exit;
}

// Define plugin constants
define('CM_VERSION', '1.0.0');
define('CM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CM_PLUGIN_FILE', __FILE__);

/**
 * Get plugin strings (centralized for translation)
 *
 * @return array
 */
function cm_strings()
{
	return [
		'menu_title'        => __('Construction Mode', 'construction-mode'),
		'page_title'        => __('Construction Mode Settings', 'construction-mode'),
		'enabled_label'     => __('Enable construction mode', 'construction-mode'),
		'logo_label'        => __('Logo', 'construction-mode'),
		'bg_label'          => __('Background color', 'construction-mode'),
		'title_label'       => __('Title', 'construction-mode'),
		'desc_label'        => __('Description', 'construction-mode'),
		'noindex_label'     => __('Disable indexing (noindex)', 'construction-mode'),
		'disable_login_label' => __('Disable login page', 'construction-mode'),
		'disable_login_desc' => __('When enabled, the login page (wp-login.php) will also show the construction page, except for administrators.', 'construction-mode'),
		'secret_param_label' => __('Secret parameter name', 'construction-mode'),
		'secret_param_desc' => __('URL parameter name for bypass (e.g., cm_preview)', 'construction-mode'),
		'secret_value_label' => __('Secret parameter value', 'construction-mode'),
		'secret_value_desc' => __('Minimum 8 characters. Recommended: 20+ random characters.', 'construction-mode'),
		'example_url_label' => __('Example bypass URL:', 'construction-mode'),
		'regenerate'        => __('Regenerate', 'construction-mode'),
		'notice_enabled'    => __('Construction Mode is enabled.', 'construction-mode'),
		'save_changes'      => __('Save Changes', 'construction-mode'),
		'upload_logo'       => __('Select logo', 'construction-mode'),
		'remove_logo'       => __('Remove logo', 'construction-mode'),
	];
}

/**
 * Get plugin options
 *
 * @return array
 */
function cm_get_options()
{
	$defaults = [
		'enabled'      => 0,
		'logo_id'      => 0,
		'bg_color'     => '#ffffff',
		'title'        => '',
		'description'  => '',
		'noindex'      => 0,
		'disable_login' => 0,
		'secret_param' => 'cm_preview',
		'secret_value' => '',
	];

	$options = get_option('cm_options', []);
	return wp_parse_args($options, $defaults);
}

/**
 * Check if construction mode is enabled
 *
 * @return bool
 */
function cm_is_enabled()
{
	$options = cm_get_options();
	return ! empty($options['enabled']);
}

/**
 * Check if current request is bypassed
 *
 * @return bool
 */
function cm_is_bypassed()
{
	// Admin users can always bypass
	if (current_user_can('manage_options')) {
		return true;
	}

	// Check bypass cookie
	if (isset($_COOKIE['cm_bypass']) && $_COOKIE['cm_bypass'] === '1') {
		return true;
	}

	return false;
}

/**
 * Maybe set bypass cookie from query parameter
 *
 * @return void
 */
function cm_maybe_set_bypass_cookie_from_query()
{
	if (! cm_is_enabled()) {
		return;
	}

	$options = cm_get_options();
	$secret_param = ! empty($options['secret_param']) ? $options['secret_param'] : 'cm_preview';
	$secret_value = ! empty($options['secret_value']) ? $options['secret_value'] : '';

	if (empty($secret_value)) {
		return;
	}

	// Check if secret parameter matches
	if (isset($_GET[$secret_param]) && $_GET[$secret_param] === $secret_value) {
		// Set cookie for 1 hour
		$cookie_path = defined('COOKIEPATH') ? COOKIEPATH : '/';
		$cookie_domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';
		$secure = is_ssl();
		$httponly = true;
		$samesite = 'Lax';

		// PHP 7.3+ supports SameSite attribute via array parameter
		if (PHP_VERSION_ID >= 70300) {
			setcookie(
				'cm_bypass',
				'1',
				[
					'expires'  => time() + 3600,
					'path'     => $cookie_path,
					'domain'   => $cookie_domain,
					'secure'   => $secure,
					'httponly' => $httponly,
					'samesite' => $samesite,
				]
			);
		} else {
			// For older PHP versions, set cookie without SameSite
			setcookie(
				'cm_bypass',
				'1',
				time() + 3600,
				$cookie_path,
				$cookie_domain,
				$secure,
				$httponly
			);
		}

		// Redirect to same URL without secret parameter
		$redirect_url = remove_query_arg($secret_param);
		wp_safe_redirect($redirect_url);
		exit;
	}
}

/**
 * Send robots headers
 *
 * @return void
 */
function cm_send_robots_headers()
{
	if (! cm_is_enabled()) {
		return;
	}

	$options = cm_get_options();
	if (! empty($options['noindex'])) {
		header('X-Robots-Tag: noindex, nofollow');
	}
}

/**
 * Render construction page and exit
 *
 * @return void
 */
function cm_render_page_and_exit()
{
	$options = cm_get_options();
	$bg_color = ! empty($options['bg_color']) ? esc_attr($options['bg_color']) : '#ffffff';
	$title = ! empty($options['title']) ? esc_html($options['title']) : '';
	$description = ! empty($options['description']) ? esc_html($options['description']) : '';
	$logo_id = ! empty($options['logo_id']) ? absint($options['logo_id']) : 0;
	$logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';

	// Send 503 status
	status_header(503);
	header('Retry-After: 3600');

	// Send robots headers (defense in depth)
	$options = cm_get_options();
	if (! empty($options['noindex'])) {
		header('X-Robots-Tag: noindex, nofollow');
	}

?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>

	<head>
		<meta charset="<?php bloginfo('charset'); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php wp_head(); ?>
	</head>

	<body
		style="margin: 0; padding: 0; background-color: <?php echo esc_attr($bg_color); ?>; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh;">
		<div style="text-align: center; padding: 40px 20px; max-width: 600px;">
			<?php if ($logo_url) : ?>
				<img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($title); ?>"
					style="max-width: 300px; height: auto; margin-bottom: 30px;">
			<?php endif; ?>
			<?php if ($title) : ?>
				<h1 style="font-size: 2.5em; margin: 0 0 20px 0; color: #333;"><?php echo esc_html($title); ?></h1>
			<?php endif; ?>
			<?php if ($description) : ?>
				<p style="font-size: 1.2em; line-height: 1.6; color: #666; margin: 0;"><?php echo esc_html($description); ?></p>
			<?php endif; ?>
		</div>
		<?php wp_footer(); ?>
	</body>

	</html>
<?php
	exit;
}

/**
 * Add settings link to plugin actions
 *
 * @param array $links Existing plugin action links
 * @return array Modified plugin action links
 */
function cm_add_settings_link($links)
{
	$settings_link = '<a href="' . admin_url('options-general.php?page=construction-mode') . '">' . esc_html__('Settings', 'construction-mode') . '</a>';
	array_unshift($links, $settings_link);
	return $links;
}
add_filter('plugin_action_links_' . plugin_basename(CM_PLUGIN_FILE), 'cm_add_settings_link');

// Include required files
require_once CM_PLUGIN_DIR . 'includes/admin.php';
require_once CM_PLUGIN_DIR . 'includes/frontend.php';
