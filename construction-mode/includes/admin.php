<?php

/**
 * Admin functionality for Construction Mode
 *
 * @package ConstructionMode
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
	exit;
}

/**
 * Add admin menu page
 */
function cm_add_admin_menu()
{
	$strings = cm_strings();
	add_options_page(
		$strings['page_title'],
		$strings['menu_title'],
		'manage_options',
		'construction-mode',
		'cm_render_settings_page'
	);
}
add_action('admin_menu', 'cm_add_admin_menu');

/**
 * Register settings
 */
function cm_register_settings()
{
	register_setting(
		'cm_options_group',
		'cm_options',
		[
			'sanitize_callback' => 'cm_sanitize_options',
			'default'           => [],
		]
	);

	$strings = cm_strings();

	add_settings_section(
		'cm_main_section',
		'',
		'__return_empty_string',
		'construction-mode'
	);

	// Enabled checkbox
	add_settings_field(
		'cm_enabled',
		$strings['enabled_label'],
		'cm_render_enabled_field',
		'construction-mode',
		'cm_main_section'
	);

	// Logo field
	add_settings_field(
		'cm_logo',
		$strings['logo_label'],
		'cm_render_logo_field',
		'construction-mode',
		'cm_main_section'
	);

	// Background color
	add_settings_field(
		'cm_bg_color',
		$strings['bg_label'],
		'cm_render_bg_color_field',
		'construction-mode',
		'cm_main_section'
	);

	// Title
	add_settings_field(
		'cm_title',
		$strings['title_label'],
		'cm_render_title_field',
		'construction-mode',
		'cm_main_section'
	);

	// Description
	add_settings_field(
		'cm_description',
		$strings['desc_label'],
		'cm_render_description_field',
		'construction-mode',
		'cm_main_section'
	);

	// Noindex checkbox
	add_settings_field(
		'cm_noindex',
		$strings['noindex_label'],
		'cm_render_noindex_field',
		'construction-mode',
		'cm_main_section'
	);

	// Disable login checkbox
	add_settings_field(
		'cm_disable_login',
		$strings['disable_login_label'],
		'cm_render_disable_login_field',
		'construction-mode',
		'cm_main_section'
	);

	// Secret parameter name
	add_settings_field(
		'cm_secret_param',
		$strings['secret_param_label'],
		'cm_render_secret_param_field',
		'construction-mode',
		'cm_main_section'
	);

	// Secret parameter value
	add_settings_field(
		'cm_secret_value',
		$strings['secret_value_label'],
		'cm_render_secret_value_field',
		'construction-mode',
		'cm_main_section'
	);
}
add_action('admin_init', 'cm_register_settings');

/**
 * Sanitize options
 *
 * @param array $input Raw input data
 * @return array Sanitized options
 */
function cm_sanitize_options($input)
{
	$sanitized = [];

	// Enabled: cast to 0/1
	$sanitized['enabled'] = ! empty($input['enabled']) ? 1 : 0;

	// Logo ID: integer only
	$sanitized['logo_id'] = ! empty($input['logo_id']) ? absint($input['logo_id']) : 0;

	// Background color: validate hex color
	if (! empty($input['bg_color']) && preg_match('/^#[0-9a-fA-F]{6}$/', $input['bg_color'])) {
		$sanitized['bg_color'] = $input['bg_color'];
	} else {
		$sanitized['bg_color'] = '#ffffff';
	}

	// Title: sanitize_text_field
	$sanitized['title'] = ! empty($input['title']) ? sanitize_text_field($input['title']) : '';

	// Description: sanitize_textarea_field
	$sanitized['description'] = ! empty($input['description']) ? sanitize_textarea_field($input['description']) : '';

	// Noindex: cast to 0/1
	$sanitized['noindex'] = ! empty($input['noindex']) ? 1 : 0;

	// Disable login: cast to 0/1
	$sanitized['disable_login'] = ! empty($input['disable_login']) ? 1 : 0;

	// Secret param: sanitize_key
	$sanitized['secret_param'] = ! empty($input['secret_param']) ? sanitize_key($input['secret_param']) : 'cm_preview';

	// Secret value: sanitize_text_field, enforce min length 8
	$secret_value = ! empty($input['secret_value']) ? sanitize_text_field($input['secret_value']) : '';
	if (strlen($secret_value) < 8) {
		// If too short, generate a new one
		$secret_value = cm_generate_secret_value();
	}
	$sanitized['secret_value'] = $secret_value;

	return $sanitized;
}

/**
 * Generate random secret value
 *
 * @param int $length Length of secret (default 20)
 * @return string
 */
function cm_generate_secret_value($length = 20)
{
	$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$secret = '';
	$max = strlen($characters) - 1;
	for ($i = 0; $i < $length; $i++) {
		$secret .= $characters[wp_rand(0, $max)];
	}
	return $secret;
}

/**
 * Render enabled field
 */
function cm_render_enabled_field()
{
	$options = cm_get_options();
	$strings = cm_strings();
	$checked = ! empty($options['enabled']) ? 'checked="checked"' : '';
?>
	<label>
		<input type="checkbox" name="cm_options[enabled]" value="1" <?php echo $checked; ?>>
		<?php echo esc_html($strings['enabled_label']); ?>
	</label>
<?php
}

/**
 * Render logo field
 */
function cm_render_logo_field()
{
	$options = cm_get_options();
	$logo_id = ! empty($options['logo_id']) ? absint($options['logo_id']) : 0;
	$logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'thumbnail') : '';
	$strings = cm_strings();
?>
	<div class="cm-logo-field">
		<input type="hidden" name="cm_options[logo_id]" id="cm_logo_id" value="<?php echo esc_attr($logo_id); ?>">
		<div id="cm_logo_preview" style="margin-bottom: 10px;">
			<?php if ($logo_url) : ?>
				<img src="<?php echo esc_url($logo_url); ?>" style="max-width: 150px; height: auto; display: block;">
			<?php endif; ?>
		</div>
		<button type="button" class="button" id="cm_upload_logo"><?php echo esc_html($strings['upload_logo']); ?></button>
		<?php if ($logo_id) : ?>
			<button type="button" class="button" id="cm_remove_logo"
				style="margin-left: 10px;"><?php echo esc_html($strings['remove_logo']); ?></button>
		<?php endif; ?>
	</div>
<?php
}

/**
 * Render background color field
 */
function cm_render_bg_color_field()
{
	$options = cm_get_options();
	$bg_color = ! empty($options['bg_color']) ? esc_attr($options['bg_color']) : '#ffffff';
?>
	<input type="text" name="cm_options[bg_color]" id="cm_bg_color" value="<?php echo esc_attr($bg_color); ?>"
		class="cm-color-picker" data-default-color="#ffffff">
<?php
}

/**
 * Render title field
 */
function cm_render_title_field()
{
	$options = cm_get_options();
	$title = ! empty($options['title']) ? esc_attr($options['title']) : '';
?>
	<input type="text" name="cm_options[title]" id="cm_title" value="<?php echo esc_attr($title); ?>" class="regular-text">
<?php
}

/**
 * Render description field
 */
function cm_render_description_field()
{
	$options = cm_get_options();
	$description = ! empty($options['description']) ? esc_textarea($options['description']) : '';
?>
	<textarea name="cm_options[description]" id="cm_description" rows="5"
		class="large-text"><?php echo esc_textarea($description); ?></textarea>
<?php
}

/**
 * Render noindex field
 */
function cm_render_noindex_field()
{
	$options = cm_get_options();
	$strings = cm_strings();
	$checked = ! empty($options['noindex']) ? 'checked="checked"' : '';
?>
	<label>
		<input type="checkbox" name="cm_options[noindex]" value="1" <?php echo $checked; ?>>
		<?php echo esc_html($strings['noindex_label']); ?>
	</label>
<?php
}

/**
 * Render disable login field
 */
function cm_render_disable_login_field()
{
	$options = cm_get_options();
	$strings = cm_strings();
	$checked = ! empty($options['disable_login']) ? 'checked="checked"' : '';
?>
	<label>
		<input type="checkbox" name="cm_options[disable_login]" value="1" <?php echo $checked; ?>>
		<?php echo esc_html($strings['disable_login_label']); ?>
	</label>
	<p class="description"><?php echo esc_html($strings['disable_login_desc']); ?></p>
<?php
}

/**
 * Render secret parameter field
 */
function cm_render_secret_param_field()
{
	$options = cm_get_options();
	$strings = cm_strings();
	$secret_param = ! empty($options['secret_param']) ? esc_attr($options['secret_param']) : 'cm_preview';
?>
	<input type="text" name="cm_options[secret_param]" id="cm_secret_param" value="<?php echo esc_attr($secret_param); ?>"
		class="regular-text">
	<p class="description"><?php echo esc_html($strings['secret_param_desc']); ?></p>
<?php
}

/**
 * Render secret value field
 */
function cm_render_secret_value_field()
{
	$options = cm_get_options();
	$secret_value = ! empty($options['secret_value']) ? esc_attr($options['secret_value']) : '';
	$secret_param = ! empty($options['secret_param']) ? esc_attr($options['secret_param']) : 'cm_preview';
	$strings = cm_strings();

	// Generate example URL
	$example_url = '';
	$show_example = ! empty($secret_value) && strlen($secret_value) >= 8 && ! empty($secret_param);
	if ($show_example) {
		$example_url = add_query_arg($secret_param, $secret_value, untrailingslashit(home_url()));
	}
?>
	<div class="cm-secret-value-field">
		<input type="text" name="cm_options[secret_value]" id="cm_secret_value"
			value="<?php echo esc_attr($secret_value); ?>" class="regular-text" minlength="8">
		<button type="button" class="button"
			id="cm_regenerate_secret"><?php echo esc_html($strings['regenerate']); ?></button>
		<p class="description"><?php echo esc_html($strings['secret_value_desc']); ?></p>
		<p class="description cm-example-url"
			style="margin-top: 10px; padding: 10px; background: #f0f0f1; border-left: 4px solid #2271b1; <?php echo $show_example ? '' : 'display: none;'; ?>">
			<strong><?php echo esc_html($strings['example_url_label']); ?></strong><br>
			<code style="word-break: break-all;"><?php echo $show_example ? esc_url($example_url) : ''; ?></code>
		</p>
	</div>
<?php
}

/**
 * Render settings page
 */
function cm_render_settings_page()
{
	if (! current_user_can('manage_options')) {
		return;
	}

	$strings = cm_strings();
?>
	<div class="wrap">
		<h1><?php echo esc_html($strings['page_title']); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields('cm_options_group');
			do_settings_sections('construction-mode');
			submit_button($strings['save_changes']);
			?>
		</form>
	</div>
<?php
}

/**
 * Show admin notice when enabled
 */
function cm_admin_notice_enabled()
{
	if (! cm_is_enabled()) {
		return;
	}

	$strings = cm_strings();
?>
	<div class="notice notice-info is-dismissible">
		<p><?php echo esc_html($strings['notice_enabled']); ?></p>
	</div>
<?php
}
add_action('admin_notices', 'cm_admin_notice_enabled');

/**
 * Enqueue admin assets
 */
function cm_enqueue_admin_assets($hook)
{
	if ('settings_page_construction-mode' !== $hook) {
		return;
	}

	// Enqueue WordPress color picker
	wp_enqueue_style('wp-color-picker');
	wp_enqueue_script('wp-color-picker');

	// Enqueue media uploader
	wp_enqueue_media();

	// Enqueue custom admin assets
	wp_enqueue_style(
		'cm-admin',
		CM_PLUGIN_URL . 'assets/admin.css',
		[],
		CM_VERSION
	);

	wp_enqueue_script(
		'cm-admin',
		CM_PLUGIN_URL . 'assets/admin.js',
		['jquery', 'wp-color-picker'],
		CM_VERSION,
		true
	);

	// Localize script
	$strings = cm_strings();
	wp_localize_script(
		'cm-admin',
		'cmAdmin',
		[
			'strings' => $strings,
			'nonce'   => wp_create_nonce('cm_admin_nonce'),
			'homeUrl' => untrailingslashit(home_url()),
		]
	);
}
add_action('admin_enqueue_scripts', 'cm_enqueue_admin_assets');
