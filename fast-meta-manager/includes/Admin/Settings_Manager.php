<?php
/**
 * Register and render plugin settings.
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

namespace FFMM\Admin;

if (! defined('ABSPATH')) {
	exit;
}

use FFMM\Core\Target_Resolver;
use FFMM\Security\Permission_Manager;

class Settings_Manager
{
	private Target_Resolver $target_resolver;
	private Permission_Manager $permission_manager;

	public function __construct(Target_Resolver $target_resolver, Permission_Manager $permission_manager)
	{
		$this->target_resolver    = $target_resolver;
		$this->permission_manager = $permission_manager;
	}

	public function register_settings(): void
	{
		register_setting(
			'ffmm_settings_group',
			'ffmm_settings',
			[
				'type'              => 'array',
				'sanitize_callback' => [$this, 'sanitize_settings'],
				'default'           => $this->get_default_settings(),
			]
		);
	}

	/**
	 * @param array<string, mixed> $settings
	 * @return array<string, mixed>
	 */
	public function sanitize_settings(array $settings): array
	{
		$default = $this->get_default_settings();
		$allowed = $this->target_resolver->get_all_targets();

		$settings['enabled_targets'] = isset($settings['enabled_targets']) && is_array($settings['enabled_targets'])
			? array_values(array_intersect($allowed, array_map('sanitize_key', $settings['enabled_targets'])))
			: $default['enabled_targets'];

		$settings['mode'] = isset($settings['mode']) && in_array($settings['mode'], ['view', 'edit'], true)
			? $settings['mode']
			: $default['mode'];

		$settings['default_target'] = isset($settings['default_target']) && in_array($settings['default_target'], $allowed, true)
			? $settings['default_target']
			: $default['default_target'];
		$settings['allow_protected_meta'] = isset($settings['allow_protected_meta']) ? 1 : 0;

		return $settings;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_default_settings(): array
	{
		$all_targets = $this->target_resolver->get_all_targets();

		return [
			'enabled_targets'          => $all_targets,
			'mode'                     => 'edit',
			'default_target'           => in_array('post', $all_targets, true) ? 'post' : (string) reset($all_targets),
			'allow_protected_meta'     => 0,
		];
	}

	public function render_settings_page(): void
	{
		if (! $this->permission_manager->can_manage_settings()) {
			wp_die(esc_html__('You are not allowed to manage these settings.', 'fast-meta-manager'));
		}

		$settings       = wp_parse_args(get_option('ffmm_settings', []), $this->get_default_settings());
		$all_targets    = $this->target_resolver->get_all_targets();
		$target_labels  = $this->target_resolver->get_target_labels();
		$settings_tabs  = apply_filters('ffmm/settings_tabs', []);
		?>
		<div class="wrap">
			<h1><?php esc_html_e('Fast Meta Manager Settings', 'fast-meta-manager'); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields('ffmm_settings_group'); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e('Enabled targets', 'fast-meta-manager'); ?></th>
						<td>
							<?php foreach ($all_targets as $target) : ?>
								<label class="ffmm-target-label">
									<input type="checkbox" name="ffmm_settings[enabled_targets][]" value="<?php echo esc_attr($target); ?>" <?php checked(in_array($target, $settings['enabled_targets'], true)); ?> />
									<?php echo esc_html($target_labels[$target] ?? $target); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e('Mode', 'fast-meta-manager'); ?></th>
						<td>
							<select name="ffmm_settings[mode]">
								<option value="view" <?php selected('view', $settings['mode']); ?>><?php esc_html_e('View', 'fast-meta-manager'); ?></option>
								<option value="edit" <?php selected('edit', $settings['mode']); ?>><?php esc_html_e('Edit', 'fast-meta-manager'); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e('Default target', 'fast-meta-manager'); ?></th>
						<td>
							<select name="ffmm_settings[default_target]">
								<?php foreach ($all_targets as $target) : ?>
									<option value="<?php echo esc_attr($target); ?>" <?php selected($target, $settings['default_target']); ?>><?php echo esc_html($target_labels[$target] ?? $target); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e('Protected/system meta keys', 'fast-meta-manager'); ?></th>
						<td>
							<label>
								<input type="checkbox" name="ffmm_settings[allow_protected_meta]" value="1" <?php checked(! empty($settings['allow_protected_meta'])); ?> />
								<?php esc_html_e('Allow editing protected/system keys (advanced)', 'fast-meta-manager'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('When disabled, keys like _wp_* and _edit_lock are blocked for safety.', 'fast-meta-manager'); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(__('Save settings', 'fast-meta-manager')); ?>
			</form>

			<?php if (is_array($settings_tabs) && ! empty($settings_tabs)) : ?>
				<hr />
				<h2><?php esc_html_e('Additional settings tabs', 'fast-meta-manager'); ?></h2>
				<pre><?php echo esc_html(wp_json_encode($settings_tabs, JSON_PRETTY_PRINT)); ?></pre>
			<?php endif; ?>
		</div>
		<?php
	}
}
