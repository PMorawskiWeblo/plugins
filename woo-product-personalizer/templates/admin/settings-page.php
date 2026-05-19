<?php

/**
 * Settings page template.
 *
 * @package WooProductPersonalizer
 * @var array  $options   Settings.
 * @var array  $wpp_paths Plugin paths for help text.
 */

defined('ABSPATH') || exit;

use WooProductPersonalizer\Helpers\UploadMimeTypes;
use WooProductPersonalizer\Infrastructure\Cleanup\CleanupService;

$manual_cleanup_days = isset($options['cleanup_interval']) ? (int) $options['cleanup_interval'] : 14;
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="options.php">
        <?php settings_fields('wpp_settings_group'); ?>

        <h2><?php esc_html_e('General', 'woo-product-personalizer'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label
                        for="wpp_max_upload_mb"><?php esc_html_e('Max upload size (MB)', 'woo-product-personalizer'); ?></label>
                </th>
                <td><input type="number" id="wpp_max_upload_mb" name="wpp_settings[max_upload_mb]"
                        value="<?php echo esc_attr($options['max_upload_mb']); ?>" min="1" max="100"
                        class="small-text" /></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Allowed MIME types', 'woo-product-personalizer'); ?></th>
                <td>
                    <?php foreach (UploadMimeTypes::definitions() as $mime => $definition) : ?>
                    <?php $checked = in_array($mime, (array) $options['allowed_mime_types'], true); ?>
                    <label style="display:block;margin-bottom:4px;">
                        <input type="checkbox" name="wpp_settings[allowed_mime_types][]"
                            value="<?php echo esc_attr($mime); ?>" <?php checked($checked); ?> />
                        <?php
							echo esc_html(
								sprintf(
									'%s (%s)',
									$definition['label'],
									$mime
								)
							);
							?>
                    </label>
                    <?php endforeach; ?>
                    <p class="description">
                        <?php esc_html_e('Formats the storefront personalizer can load on the canvas (Konva). GIF/AVIF support depends on the customer’s browser; BMP is widely supported.', 'woo-product-personalizer'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label
                        for="wpp_default_button_label"><?php esc_html_e('Default button label', 'woo-product-personalizer'); ?></label>
                </th>
                <td><input type="text" id="wpp_default_button_label" name="wpp_settings[default_button_label]"
                        value="<?php echo esc_attr($options['default_button_label']); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label
                        for="wpp_default_button_label_completed"><?php esc_html_e('Default completed button label', 'woo-product-personalizer'); ?></label>
                </th>
                <td>
                    <input type="text" id="wpp_default_button_label_completed"
                        name="wpp_settings[default_button_label_completed]"
                        value="<?php echo esc_attr($options['default_button_label_completed'] ?? ''); ?>"
                        class="regular-text" />
                    <p class="description">
                        <?php esc_html_e('Shown on the product page after all required personalization fields are valid (when add to cart is blocked until personalization is complete).', 'woo-product-personalizer'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label
                        for="wpp_default_accept_text"><?php esc_html_e('Default acceptance text', 'woo-product-personalizer'); ?></label>
                </th>
                <td><textarea id="wpp_default_accept_text" name="wpp_settings[default_accept_text]" rows="3"
                        class="large-text"><?php echo esc_textarea($options['default_accept_text']); ?></textarea></td>
            </tr>
        </table>

        <h2><?php esc_html_e('Frontend', 'woo-product-personalizer'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Display mode', 'woo-product-personalizer'); ?></th>
                <td>
                    <label><input type="radio" name="wpp_settings[frontend_mode]" value="modal"
                            <?php checked($options['frontend_mode'], 'modal'); ?> />
                        <?php esc_html_e('Modal', 'woo-product-personalizer'); ?></label><br />
                    <label><input type="radio" name="wpp_settings[frontend_mode]" value="inline"
                            <?php checked($options['frontend_mode'], 'inline'); ?> />
                        <?php esc_html_e('Inline', 'woo-product-personalizer'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label
                        for="wpp_button_position"><?php esc_html_e('Button position', 'woo-product-personalizer'); ?></label>
                </th>
                <td>
                    <select id="wpp_button_position" name="wpp_settings[button_position]">
                        <?php
						$positions = array(
							'after_price'        => __('After price', 'woo-product-personalizer'),
							'before_add_to_cart' => __('Before add to cart', 'woo-product-personalizer'),
							'after_add_to_cart'  => __('After add to cart', 'woo-product-personalizer'),
							'shortcode_only'     => __('Shortcode only', 'woo-product-personalizer'),
						);
						foreach ($positions as $value => $label) :
						?>
                        <option value="<?php echo esc_attr($value); ?>"
                            <?php selected($options['button_position'], $value); ?>><?php echo esc_html($label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Shortcode: [woo_product_personalizer]', 'woo-product-personalizer'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Shortcode only', 'woo-product-personalizer'); ?></th>
                <td><label><input type="checkbox" name="wpp_settings[shortcode_only]" value="1"
                            <?php checked($options['shortcode_only']); ?> />
                        <?php esc_html_e('Hide automatic button placement', 'woo-product-personalizer'); ?></label></td>
            </tr>
        </table>

        <h2><?php esc_html_e('Cleanup', 'woo-product-personalizer'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Auto cleanup', 'woo-product-personalizer'); ?></th>
                <td>
                    <label><input type="checkbox" name="wpp_settings[cleanup_enabled]" value="1"
                            <?php checked($options['cleanup_enabled']); ?> />
                        <?php esc_html_e('Enable scheduled cleanup', 'woo-product-personalizer'); ?></label>
                    <p class="description">
                        <?php esc_html_e('Runs a background job on the interval selected below. It removes old personalization project folders from disk (PNG exports, project.json, uploaded images) using the same rules as manual cleanup.', 'woo-product-personalizer'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label
                        for="wpp_cleanup_interval"><?php esc_html_e('Cleanup interval', 'woo-product-personalizer'); ?></label>
                </th>
                <td>
                    <select id="wpp_cleanup_interval" name="wpp_settings[cleanup_interval]">
                        <option value="7" <?php selected($options['cleanup_interval'], 7); ?>>
                            <?php esc_html_e('Every 7 days', 'woo-product-personalizer'); ?></option>
                        <option value="14" <?php selected($options['cleanup_interval'], 14); ?>>
                            <?php esc_html_e('Every 14 days', 'woo-product-personalizer'); ?></option>
                        <option value="30" <?php selected($options['cleanup_interval'], 30); ?>>
                            <?php esc_html_e('Every 30 days', 'woo-product-personalizer'); ?></option>
                    </select>
                    <p class="description">
                        <?php esc_html_e('How often the scheduled job runs, and the minimum age of an order folder before it can be deleted (based on the folder’s last modified time). Cancelled orders are always removed immediately and are not subject to this age limit. Example: “Every 14 days” runs the job every two weeks and targets other eligible folders older than 14 days.', 'woo-product-personalizer'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Completed orders only', 'woo-product-personalizer'); ?></th>
                <td>
                    <label><input type="checkbox" name="wpp_settings[cleanup_only_completed]" value="1"
                            <?php checked($options['cleanup_only_completed']); ?> />
                        <?php esc_html_e('Only delete folders for completed/cancelled/refunded orders', 'woo-product-personalizer'); ?></label>
                    <p class="description">
                        <?php esc_html_e('When enabled, folders for orders that are still processing, on-hold, or pending payment are never removed. Cancelled, completed, and refunded orders can be cleaned up; cancelled folders are deleted right away (also when the order status changes to cancelled).', 'woo-product-personalizer'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Debug', 'woo-product-personalizer'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e('Debug mode', 'woo-product-personalizer'); ?></th>
                <td>
                    <label><input type="checkbox" name="wpp_settings[debug_enabled]" value="1"
                            <?php checked($options['debug_enabled']); ?> />
                        <?php esc_html_e('Enable debug mode', 'woo-product-personalizer'); ?></label>
                    <p class="description">
                        <?php esc_html_e('Turns on developer tooling for the storefront personalizer:', 'woo-product-personalizer'); ?>
                    </p>
                    <ul class="description" style="list-style:disc;margin-left:18px;">
                        <li><?php esc_html_e('A debug panel on the product page (state, layout, slots, recent events).', 'woo-product-personalizer'); ?>
                        </li>
                        <li><?php esc_html_e('Prefixed [WPP] messages in the browser console.', 'woo-product-personalizer'); ?>
                        </li>
                        <li><?php esc_html_e('Selected client-side events sent to the server log (AJAX).', 'woo-product-personalizer'); ?>
                        </li>
                        <li><?php esc_html_e('PHP-side debug/info/warning/error entries from the plugin when logging is active.', 'woo-product-personalizer'); ?>
                        </li>
                    </ul>
                    <p class="description">
                        <?php esc_html_e('The on-screen panel and console output also appear when WP_DEBUG is true in wp-config.php, but the log file and server-side AJAX logging require this setting to be enabled.', 'woo-product-personalizer'); ?>
                    </p>
                    <p class="description">
                        <strong><?php esc_html_e('Log file:', 'woo-product-personalizer'); ?></strong>
                        <code
                            style="display:block;margin-top:4px;word-break:break-all;"><?php echo esc_html($wpp_paths['debug_log']); ?></code>
                    </p>
                    <p class="description">
                        <?php esc_html_e('New lines are appended with a timestamp, level, and optional JSON context. The file is created automatically on first write.', 'woo-product-personalizer'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>

    <hr />
    <h2><?php esc_html_e('Manual cleanup', 'woo-product-personalizer'); ?></h2>
    <p class="description">
        <?php esc_html_e('Scans order project folders on disk and removes those matching the rules above and the age threshold you choose below. Cancelled orders are always removed, regardless of age.', 'woo-product-personalizer'); ?>
    </p>
    <p class="description">
        <strong><?php esc_html_e('Target directory:', 'woo-product-personalizer'); ?></strong>
        <code
            style="display:block;margin-top:4px;word-break:break-all;"><?php echo esc_html($wpp_paths['orders_dir']); ?>*</code>
        <?php esc_html_e('Each subfolder is named after the WooCommerce order ID and may contain project.json, preview PNG, ZIP, and customer uploads.', 'woo-product-personalizer'); ?>
    </p>
    <p class="description">
        <?php esc_html_e('After the run finishes, an admin notice shows how many folders were deleted (or would be deleted in dry run). Folders that are too new (except cancelled orders), belong to open orders, or cannot be removed are left unchanged.', 'woo-product-personalizer'); ?>
    </p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('wpp_manual_cleanup'); ?>
        <input type="hidden" name="action" value="wpp_manual_cleanup" />
        <p>
            <label
                for="wpp_manual_cleanup_days"><strong><?php esc_html_e('Delete folders', 'woo-product-personalizer'); ?></strong></label><br />
            <select id="wpp_manual_cleanup_days" name="wpp_manual_cleanup_days">
                <?php foreach (CleanupService::manual_interval_choices() as $days => $label) : ?>
                <option value="<?php echo esc_attr((string) $days); ?>" <?php selected($manual_cleanup_days, $days); ?>>
                    <?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p class="description">
            <?php esc_html_e('Based on the folder’s last modified time on disk. Independent from the scheduled cleanup interval above.', 'woo-product-personalizer'); ?>
        </p>
        <p>
            <label><input type="checkbox" name="wpp_dry_run" value="1" />
                <?php esc_html_e('Dry run (report only, do not delete)', 'woo-product-personalizer'); ?></label>
        </p>
        <p class="description">
            <?php esc_html_e('Simulates cleanup without deleting anything: eligible folders are counted and listed in the result as “would be deleted”, but no files or directories are removed. Use this to verify the rules before running a real cleanup.', 'woo-product-personalizer'); ?>
        </p>
        <?php submit_button(__('Run cleanup now', 'woo-product-personalizer'), 'secondary'); ?>
    </form>
</div>