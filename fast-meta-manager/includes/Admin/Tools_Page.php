<?php
/**
 * Admin tools page rendering.
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

namespace FFMM\Admin;

if (! defined('ABSPATH')) {
	exit;
}

use FFMM\Core\Target_Resolver;
use FFMM\Render\Meta_Table_Renderer;
use FFMM\Security\Permission_Manager;

class Tools_Page
{
	private Target_Resolver $target_resolver;
	private Meta_Table_Renderer $renderer;
	private Permission_Manager $permission_manager;

	public function __construct(Target_Resolver $target_resolver, Meta_Table_Renderer $renderer, Permission_Manager $permission_manager)
	{
		$this->target_resolver    = $target_resolver;
		$this->renderer           = $renderer;
		$this->permission_manager = $permission_manager;
	}

	public function register_menu(Settings_Manager $settings_manager): void
	{
		add_management_page(
			__('Fast Meta Manager', 'fast-meta-manager'),
			__('Fast Meta Manager', 'fast-meta-manager'),
			$this->permission_manager->get_view_capability(),
			'fast-meta-manager',
			[$this, 'render_tools_page']
		);

		add_submenu_page(
			'tools.php',
			__('Fast Meta Manager Settings', 'fast-meta-manager'),
			__('Fast Meta Manager Settings', 'fast-meta-manager'),
			$this->permission_manager->get_settings_capability(),
			'fast-meta-manager-settings',
			[$settings_manager, 'render_settings_page']
		);
	}

	public function render_tools_page(): void
	{
		if (! $this->permission_manager->can_access_tools_page()) {
			wp_die(esc_html__('You are not allowed to access this page.', 'fast-meta-manager'));
		}

		$allowed_targets = $this->target_resolver->get_allowed_targets();
		$target_labels   = $this->target_resolver->get_target_labels();
		$settings        = wp_parse_args(get_option('ffmm_settings', []), ['default_target' => 'post']);
		$target_raw      = isset($_GET['target']) ? wp_unslash((string) $_GET['target']) : (string) $settings['default_target'];
		$target          = sanitize_key($target_raw);
		$target          = in_array($target, $allowed_targets, true) ? $target : (string) reset($allowed_targets);
		$object_id       = isset($_GET['object_id']) ? (int) $_GET['object_id'] : 0;
		$meta_filter_raw = isset($_GET['meta_filter']) ? wp_unslash((string) $_GET['meta_filter']) : '';
		$meta_filter     = sanitize_text_field($meta_filter_raw);
		$adapter         = $this->target_resolver->get_adapter($target);
		$meta_rows       = [];
		$object_label    = '';
		$can_edit        = false;
		$return_url      = add_query_arg(
			[
				'page'      => 'fast-meta-manager',
				'target'    => $target,
				'object_id' => $object_id,
				'meta_filter' => $meta_filter,
			],
			admin_url('tools.php')
		);

		if ($adapter && $object_id > 0 && $adapter->can_access($object_id)) {
			$meta_rows = $adapter->get_meta($object_id);
			if ('' !== $meta_filter) {
				$meta_rows = array_filter(
					$meta_rows,
					static fn (string $meta_key): bool => false !== stripos($meta_key, $meta_filter),
					ARRAY_FILTER_USE_KEY
				);
			}
			$object_label = $adapter->get_object_label($object_id);
			$can_edit     = $this->permission_manager->can_edit_meta();
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e('Fast Meta Manager', 'fast-meta-manager'); ?></h1>
			<p>
				<a class="button" href="<?php echo esc_url(admin_url('tools.php?page=fast-meta-manager-settings')); ?>">
					<?php esc_html_e('Settings', 'fast-meta-manager'); ?>
				</a>
			</p>

			<form method="get" action="">
				<input type="hidden" name="page" value="fast-meta-manager" />
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e('Target', 'fast-meta-manager'); ?></th>
						<td>
							<select name="target">
								<?php foreach ($allowed_targets as $item) : ?>
									<option value="<?php echo esc_attr($item); ?>" <?php selected($item, $target); ?>>
										<?php echo esc_html($target_labels[$item] ?? $item); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e('Object ID', 'fast-meta-manager'); ?></th>
						<td><input type="number" min="1" name="object_id" value="<?php echo esc_attr((string) $object_id); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e('Filter meta keys', 'fast-meta-manager'); ?></th>
						<td><input type="text" class="regular-text" name="meta_filter" value="<?php echo esc_attr($meta_filter); ?>" /></td>
					</tr>
				</table>
				<?php submit_button(__('Load metadata', 'fast-meta-manager')); ?>
			</form>

			<?php if ($object_label) : ?>
				<h2><?php echo esc_html($object_label); ?></h2>
				<?php $this->renderer->render($meta_rows, $target, $object_id, $can_edit, $return_url); ?>

				<?php if ($can_edit) : ?>
					<hr />
					<h3><?php esc_html_e('Add metadata', 'fast-meta-manager'); ?></h3>
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<?php wp_nonce_field('ffmm_add_meta_action', 'ffmm_nonce'); ?>
						<input type="hidden" name="action" value="ffmm_add_meta" />
						<input type="hidden" name="target" value="<?php echo esc_attr($target); ?>" />
						<input type="hidden" name="object_id" value="<?php echo esc_attr((string) $object_id); ?>" />
						<input type="hidden" name="return_url" value="<?php echo esc_url($return_url); ?>" />
						<p>
							<label for="ffmm-meta-key"><?php esc_html_e('Meta key', 'fast-meta-manager'); ?></label><br />
							<input id="ffmm-meta-key" class="regular-text" type="text" name="meta_key" required />
						</p>
						<p>
							<label for="ffmm-meta-value"><?php esc_html_e('Meta value', 'fast-meta-manager'); ?></label><br />
							<textarea id="ffmm-meta-value" class="large-text code" name="meta_value" rows="5"></textarea>
						</p>
						<?php submit_button(__('Add metadata', 'fast-meta-manager'), 'secondary'); ?>
					</form>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}
}
