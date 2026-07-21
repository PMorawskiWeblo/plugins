<?php

/**
 * Render metadata table in admin.
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

namespace FFMM\Render;

if (! defined('ABSPATH')) {
	exit;
}

class Meta_Table_Renderer
{
	public function enqueue_assets(string $hook_suffix = ''): void
	{
		unset($hook_suffix);

		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		if (! $screen) {
			return;
		}

		$is_tools_page = in_array($screen->id, ['tools_page_fast-meta-manager', 'tools_page_fast-meta-manager-settings'], true);
		$is_post_editor = 'post' === $screen->base;
		if (! $is_tools_page && ! $is_post_editor) {
			return;
		}

		wp_enqueue_style(
			'ffmm-admin',
			FFMM_PLUGIN_URL . 'assets/css/admin.css',
			[],
			FFMM_VERSION
		);

		wp_enqueue_script(
			'ffmm-admin',
			FFMM_PLUGIN_URL . 'assets/js/admin.js',
			[],
			FFMM_VERSION,
			true
		);

		wp_localize_script(
			'ffmm-admin',
			'FFMMAdmin',
			[
				'adminPostUrl' => admin_url('admin-post.php'),
				'confirmDelete' => __('Delete this metadata key?', 'fast-meta-manager'),
			]
		);
	}

	/**
	 * @param array<string, array<int, mixed>> $meta_rows
	 */
	public function render(array $meta_rows, string $target, int $object_id, bool $can_edit, string $return_url = '', bool $allow_inline_forms = true, bool $use_form_tags = true): void
	{
?>
		<table class="widefat striped ffmm-meta-table">
			<thead>
				<tr>
					<th><?php esc_html_e('Meta key', 'fast-meta-manager'); ?></th>
					<th><?php esc_html_e('Value', 'fast-meta-manager'); ?></th>
					<th><?php esc_html_e('Type', 'fast-meta-manager'); ?></th>
					<th><?php esc_html_e('Actions', 'fast-meta-manager'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($meta_rows)) : ?>
					<tr>
						<td colspan="4"><?php esc_html_e('No metadata found for this object.', 'fast-meta-manager'); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ($meta_rows as $meta_key => $values) : ?>
						<?php foreach ($values as $value) : ?>
							<?php
							$row_hash      = md5($target . '|' . (string) $object_id . '|' . (string) $meta_key . '|' . wp_json_encode($value));
							$save_form_id  = 'ffmm-save-form-' . $row_hash;
							$display_value = is_scalar($value) || null === $value
								? (string) $value
								: wp_json_encode($value, JSON_PRETTY_PRINT);
							$type            = gettype($value);
							$prev_value_json = wp_json_encode($value);
							?>
							<tr>
								<td><code class="ffmm-meta-key"><?php echo esc_html((string) $meta_key); ?></code></td>
								<td>
									<pre class="ffmm-meta-value-preview"><?php echo esc_html((string) $display_value); ?></pre>
								</td>
								<td><?php echo esc_html($type); ?></td>
								<td class="ffmm-actions-cell">
									<?php if ($can_edit && $allow_inline_forms) : ?>
							<?php if ($use_form_tags) : ?>
								<form id="<?php echo esc_attr($save_form_id); ?>" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ffmm-edit-form">
									<?php wp_nonce_field('ffmm_save_meta_action', 'ffmm_nonce'); ?>
									<input type="hidden" name="action" value="ffmm_save_meta" />
									<input type="hidden" name="target" value="<?php echo esc_attr($target); ?>" />
									<input type="hidden" name="object_id" value="<?php echo esc_attr((string) $object_id); ?>" />
									<input type="hidden" name="meta_key" value="<?php echo esc_attr((string) $meta_key); ?>" />
									<input type="hidden" name="prev_value" value="<?php echo esc_attr((string) $prev_value_json); ?>" />
									<?php if ('' !== $return_url) : ?>
										<input type="hidden" name="return_url" value="<?php echo esc_url($return_url); ?>" />
									<?php endif; ?>
									<div class="ffmm-action-row">
										<button type="button" class="button button-small ffmm-toggle-edit" data-show-label="<?php echo esc_attr__('Edit', 'fast-meta-manager'); ?>" data-hide-label="<?php echo esc_attr__('Cancel', 'fast-meta-manager'); ?>"><?php esc_html_e('Edit', 'fast-meta-manager'); ?></button>
									</div>
									<div class="ffmm-edit-panel" hidden>
										<textarea name="meta_value" rows="3" class="ffmm-meta-input"><?php echo esc_textarea((string) $display_value); ?></textarea>
									</div>
								</form>
								<div class="ffmm-action-row ffmm-save-delete-row" hidden>
									<button type="submit" form="<?php echo esc_attr($save_form_id); ?>" class="button button-small button-primary"><?php esc_html_e('Save', 'fast-meta-manager'); ?></button>
									<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ffmm-delete-form">
										<?php wp_nonce_field('ffmm_delete_meta_action', 'ffmm_nonce'); ?>
										<input type="hidden" name="action" value="ffmm_delete_meta" />
										<input type="hidden" name="target" value="<?php echo esc_attr($target); ?>" />
										<input type="hidden" name="object_id" value="<?php echo esc_attr((string) $object_id); ?>" />
										<input type="hidden" name="meta_key" value="<?php echo esc_attr((string) $meta_key); ?>" />
										<input type="hidden" name="prev_value" value="<?php echo esc_attr((string) $prev_value_json); ?>" />
										<?php if ('' !== $return_url) : ?>
											<input type="hidden" name="return_url" value="<?php echo esc_url($return_url); ?>" />
										<?php endif; ?>
										<button type="submit" class="button button-small ffmm-delete-button" onclick="return confirm('<?php echo esc_js(__('Delete this metadata key?', 'fast-meta-manager')); ?>');"><?php esc_html_e('Delete', 'fast-meta-manager'); ?></button>
									</form>
								</div>
							<?php else : ?>
								<div class="ffmm-edit-form" data-ffmm-save-nonce="<?php echo esc_attr(wp_create_nonce('ffmm_save_meta_action')); ?>" data-ffmm-delete-nonce="<?php echo esc_attr(wp_create_nonce('ffmm_delete_meta_action')); ?>" data-ffmm-target="<?php echo esc_attr($target); ?>" data-ffmm-object-id="<?php echo esc_attr((string) $object_id); ?>" data-ffmm-meta-key="<?php echo esc_attr((string) $meta_key); ?>" data-ffmm-prev-value="<?php echo esc_attr($prev_value_json); ?>" data-ffmm-return-url="<?php echo esc_attr($return_url); ?>">
								<div class="ffmm-action-row">
									<button type="button" class="button button-small ffmm-toggle-edit" data-show-label="<?php echo esc_attr__('Edit', 'fast-meta-manager'); ?>" data-hide-label="<?php echo esc_attr__('Cancel', 'fast-meta-manager'); ?>"><?php esc_html_e('Edit', 'fast-meta-manager'); ?></button>
								</div>
								<div class="ffmm-edit-panel" hidden>
									<textarea rows="3" class="ffmm-meta-input"><?php echo esc_textarea((string) $display_value); ?></textarea>
								</div>
								<div class="ffmm-action-row ffmm-save-delete-row" hidden>
									<button type="button" class="button button-small button-primary ffmm-js-submit-button" data-mode="save"><?php esc_html_e('Save', 'fast-meta-manager'); ?></button>
									<button type="button" class="button button-small ffmm-delete-button ffmm-js-submit-button" data-mode="delete"><?php esc_html_e('Delete', 'fast-meta-manager'); ?></button>
								</div>
							</div>
						<?php endif; ?>
<?php elseif ($can_edit) : ?>
										<p class="description">
											<?php esc_html_e('Inline editing is disabled in the editor to avoid form conflicts. Use the full manager to edit metadata.', 'fast-meta-manager'); ?>
										</p>
									<?php else : ?>
										<?php esc_html_e('Read only', 'fast-meta-manager'); ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	<?php
	}
}
