<?php

/**
 * Admin Developer Panel View
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load logger class
if (!class_exists('Loyalty_Program_Logger')) {
    require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
}

// Get current settings
$debug_enabled = get_option('loyalty_program_debug_enabled', 'no');
$log_size = Loyalty_Program_Logger::get_formatted_log_size();
$log_file_path = Loyalty_Program_Logger::get_log_file_path();

// Handle download request
if (isset($_GET['action']) && $_GET['action'] === 'download_log' && isset($_GET['_wpnonce'])) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'download_log')) {
        Loyalty_Program_Logger::download_log();
    }
}

settings_errors('loyalty_program_developer');
?>

<div class="wrap loyalty-program-developer">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <p class="description">
        <?php _e('Developer tools and debugging options for Loyalty Program plugin.', 'loyalty-program'); ?>
    </p>

    <!-- Debug Settings -->
    <div class="loyalty-developer-section">
        <h2><?php _e('Debug Settings', 'loyalty-program'); ?></h2>

        <form method="post" action="" id="developer-settings-form">
            <?php wp_nonce_field('loyalty_program_developer', 'loyalty_program_developer_nonce'); ?>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <?php _e('Enable Debug Log', 'loyalty-program'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="debug_log_enabled" value="yes"
                                        <?php checked($debug_enabled, 'yes'); ?>>
                                    <?php _e('Enable custom debug logging for this plugin', 'loyalty-program'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('When enabled, the plugin will log debug information to a custom log file. Maximum file size: 5 MB.', 'loyalty-program'); ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <?php _e('Log File Location', 'loyalty-program'); ?>
                        </th>
                        <td>
                            <code><?php echo esc_html($log_file_path); ?></code>
                            <p class="description">
                                <?php _e('Current log file size:', 'loyalty-program'); ?>
                                <strong><?php echo esc_html($log_size); ?></strong>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h3 style="border-top: 1px solid #dcdcde; padding-top: 20px; margin-top: 20px;">
                <?php _e('Asset Versioning', 'loyalty-program'); ?>
            </h3>
            <p class="description">
                <?php _e('Control version numbers for CSS and JavaScript files to manage browser caching.', 'loyalty-program'); ?>
            </p>

            <table class="form-table">
                <tbody>
                    <?php
                    $custom_version = get_option('loyalty_program_custom_version', LOYALTY_PROGRAM_VERSION);
                    $random_version_enabled = get_option('loyalty_program_random_version', 'no');
                    ?>
                    <tr>
                        <th scope="row">
                            <label for="custom_version">
                                <?php _e('Plugin Version', 'loyalty-program'); ?>
                                <span class="required">*</span>
                            </label>
                        </th>
                        <td>
                            <input type="text" name="custom_version" id="custom_version"
                                value="<?php echo esc_attr($custom_version); ?>" class="regular-text" required
                                <?php echo $random_version_enabled === 'yes' ? 'readonly' : ''; ?>>
                            <p class="description">
                                <?php _e('Version number used for CSS and JS file URLs. Default:', 'loyalty-program'); ?>
                                <code><?php echo esc_html(LOYALTY_PROGRAM_VERSION); ?></code>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <?php _e('Random Version (Cache Busting)', 'loyalty-program'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="random_version_enabled" id="random_version_enabled"
                                        value="yes" <?php checked($random_version_enabled, 'yes'); ?>>
                                    <?php _e('Generate random version on each page load', 'loyalty-program'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Useful for development to bypass browser cache. Overrides the custom version above.', 'loyalty-program'); ?>
                                </p>
                                <?php if ($random_version_enabled === 'yes') : ?>
                                    <p class="description" style="color: #d63638; font-weight: 600;">
                                        ⚠️
                                        <?php _e('Warning: Random versioning is enabled. This may impact performance.', 'loyalty-program'); ?>
                                    </p>
                                <?php endif; ?>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <button type="submit" name="loyalty_program_developer_save" class="button button-primary">
                    <?php _e('Save Settings', 'loyalty-program'); ?>
                </button>
            </p>
        </form>
    </div>

    <!-- Log Viewer -->
    <?php if ($debug_enabled === 'yes') : ?>
        <div class="loyalty-developer-section" style="margin-top: 30px;">
            <h2><?php _e('Debug Log Viewer', 'loyalty-program'); ?></h2>

            <div class="loyalty-log-actions" style="margin-bottom: 15px;">
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=loyalty-program-developer&action=download_log'), 'download_log'); ?>"
                    class="button button-secondary">
                    <span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
                    <?php _e('Download Log', 'loyalty-program'); ?>
                </a>

                <form method="post" action="" style="display: inline-block; margin-left: 10px;">
                    <?php wp_nonce_field('loyalty_program_clear_log', 'loyalty_program_clear_log_nonce'); ?>
                    <button type="submit" name="loyalty_program_clear_log" class="button button-secondary" id="clear-log-btn">
                        <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
                        <?php _e('Clear Log', 'loyalty-program'); ?>
                    </button>
                </form>

                <button type="button" id="refresh-log" class="button button-secondary" style="margin-left: 10px;">
                    <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
                    <?php _e('Refresh', 'loyalty-program'); ?>
                </button>
            </div>

            <div class="loyalty-log-viewer">
                <pre id="log-content"
                    style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 4px; max-height: 500px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.5;"><?php
                                                                                                                                                                                                                    $log_content = Loyalty_Program_Logger::get_log_content(100);
                                                                                                                                                                                                                    echo esc_html($log_content);
                                                                                                                                                                                                                    ?></pre>
            </div>

            <p class="description" style="margin-top: 10px;">
                <?php _e('Showing last 100 log entries. Download the full log file for complete history.', 'loyalty-program'); ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Custom Integrations -->
    <div class="loyalty-developer-section" style="margin-top: 30px;">
        <h2 class="loyalty-developer-section-toggle" style="cursor: pointer; user-select: none; position: relative; padding-left: 30px;">
            <span class="dashicons dashicons-arrow-right" style="position: absolute; left: 0; top: 50%; transform: translateY(-50%); margin-top: 2px;"></span>
            <?php _e('Custom Integrations', 'loyalty-program'); ?>
        </h2>

        <div class="loyalty-developer-section-content" style="display: none;">
            <p class="description">
                <?php _e('Information about custom integrations with the Loyalty Program plugin.', 'loyalty-program'); ?>
            </p>

            <div style="margin-top: 20px;">
                <h3 style="margin-top: 0;"><?php _e('Review Points Integration', 'loyalty-program'); ?></h3>

                <p>
                    <?php _e('To award loyalty points when users submit product reviews through your custom review system, add the following code before the redirect in your review submission handler:', 'loyalty-program'); ?>
                </p>

                <div style="background: #f6f7f7; border-left: 4px solid #2271b1; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 10px 0;">
                        <strong><?php _e('File:', 'loyalty-program'); ?></strong>
                        <code>template-pages/ratings-purchase.php</code>
                    </p>
                    <p style="margin: 0 0 10px 0;">
                        <strong><?php _e('Location:', 'loyalty-program'); ?></strong>
                        <?php _e('Add before wp_redirect() call', 'loyalty-program'); ?>
                    </p>
                    <pre
                        style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code>// Award loyalty points
if (class_exists('Loyalty_Program_Points') && $order_id) {
    $order = wc_get_order($order_id);
    if ($order) {
        Loyalty_Program_Points::award_review_points($order->get_user_id(), $post_id);
    }
}</code></pre>
                </div>

                <div style="background: #f0f6fc; border-left: 4px solid #72aee6; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 5px 0;">
                        <strong><?php _e('How it works:', 'loyalty-program'); ?></strong>
                    </p>
                    <ul style="margin: 5px 0 0 20px;">
                        <li><?php _e('Checks if Loyalty Program plugin is active', 'loyalty-program'); ?></li>
                        <li><?php _e('Retrieves user ID from the order', 'loyalty-program'); ?></li>
                        <li><?php _e('Verifies user is a loyalty program member', 'loyalty-program'); ?></li>
                        <li><?php _e('Awards configured points amount (default: 50 points)', 'loyalty-program'); ?></li>
                        <li><?php _e('Logs the action in debug log if enabled', 'loyalty-program'); ?></li>
                    </ul>
                </div>

                <p style="margin-top: 15px;">
                    <span class="dashicons dashicons-admin-settings" style="color: #2271b1;"></span>
                    <strong><?php _e('Configure points amount:', 'loyalty-program'); ?></strong>
                    <?php _e('Settings → Points Configuration → Review Points', 'loyalty-program'); ?>
                </p>
            </div>

            <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #dcdcde;">
                <h3 style="margin-top: 0;"><?php _e('Check User Membership', 'loyalty-program'); ?></h3>

                <p>
                    <?php _e('To check if a user is enrolled in the loyalty program, use the following code:', 'loyalty-program'); ?>
                </p>

                <div style="background: #f6f7f7; border-left: 4px solid #2271b1; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 10px 0;">
                        <strong><?php _e('Usage:', 'loyalty-program'); ?></strong>
                        <?php _e('Check if user is a loyalty program member', 'loyalty-program'); ?>
                    </p>
                    <pre
                        style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code>// Check if current logged-in user is a member
if (class_exists('Loyalty_Program_Points')) {
    $user_id = get_current_user_id();
    
    if (Loyalty_Program_Points::is_member($user_id)) {
        // User is a member - show loyalty content
        echo 'Welcome, loyalty member!';
    } else {
        // User is not a member - show join message
        echo 'Join our loyalty program!';
    }
}

// Check if specific user is a member
$user_id = 123; // User ID to check
if (class_exists('Loyalty_Program_Points') && Loyalty_Program_Points::is_member($user_id)) {
    echo 'User #' . $user_id . ' is a member';
}</code></pre>
                </div>

                <div style="background: #f0f6fc; border-left: 4px solid #72aee6; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 5px 0;">
                        <strong><?php _e('How it works:', 'loyalty-program'); ?></strong>
                    </p>
                    <ul style="margin: 5px 0 0 20px;">
                        <li><?php _e('Checks if user has enrolled in the loyalty program', 'loyalty-program'); ?></li>
                        <li><?php _e('Returns true if user is a member, false otherwise', 'loyalty-program'); ?></li>
                        <li><?php _e('Works with any user ID', 'loyalty-program'); ?></li>
                        <li><?php _e('Can be used in templates, plugins, or theme functions', 'loyalty-program'); ?></li>
                    </ul>
                </div>

                <div style="background: #fcf9e8; border-left: 4px solid #dba617; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 5px 0;">
                        <strong><?php _e('Additional membership functions:', 'loyalty-program'); ?></strong>
                    </p>
                    <pre
                        style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code>// Get user's current points
$points = Loyalty_Program_Points::get_current_points($user_id);

// Get user's total earned points
$total_earned = Loyalty_Program_Points::get_total_earned($user_id);

// Get membership information
$membership = Loyalty_Program_Points::get_membership($user_id);
// Returns: ['is_member' => bool, 'join_date' => string, 'join_date_formatted' => string]</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // SweetAlert2 Helper
        var SwalConfig = {
            alert: function(message, title, icon) {
                return Swal.fire({
                    title: title || '',
                    text: message,
                    icon: icon || 'info',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#b02e66',
                    color: '#000000',
                    buttonsStyling: true
                });
            },
            confirm: function(message, title, icon) {
                return Swal.fire({
                    title: title || '',
                    text: message,
                    icon: icon || 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Tak',
                    cancelButtonText: 'Anuluj',
                    confirmButtonColor: '#b02e66',
                    cancelButtonColor: '#aac096',
                    color: '#000000',
                    buttonsStyling: true
                });
            },
            error: function(message, title) {
                return this.alert(message, title || 'Błąd', 'error');
            },
            warning: function(message, title) {
                return this.alert(message, title || 'Ostrzeżenie', 'warning');
            }
        };
        
        // Clear log button with confirmation
        $('#clear-log-btn').on('click', function(e) {
            e.preventDefault();
            var $form = $(this).closest('form');
            SwalConfig.confirm('<?php esc_attr_e('Are you sure you want to clear the debug log?', 'loyalty-program'); ?>').then(function(result) {
                if (result.isConfirmed) {
                    $form[0].submit();
                }
            });
            return false;
        });
        
        // Refresh log button
        $('#refresh-log').on('click', function() {
            var $button = $(this);
            var originalText = $button.find('.dashicons').next().text();

            $button.prop('disabled', true).find('.dashicons').next().text(
                '<?php _e('Loading...', 'loyalty-program'); ?>');

            $.post(ajaxurl, {
                action: 'loyalty_program_get_log',
                nonce: '<?php echo wp_create_nonce('loyalty_program_get_log'); ?>'
            }, function(response) {
                if (response.success) {
                    $('#log-content').text(response.data.content);
                }
            }).always(function() {
                $button.prop('disabled', false).find('.dashicons').next().text(originalText);
            });
        });

        // Auto-scroll log to bottom
        var logContent = document.getElementById('log-content');
        if (logContent) {
            logContent.scrollTop = logContent.scrollHeight;
        }

        // Handle random version checkbox
        $('#random_version_enabled').on('change', function() {
            var $customVersion = $('#custom_version');
            if ($(this).is(':checked')) {
                $customVersion.prop('readonly', true).css('background-color', '#f0f0f1');
            } else {
                $customVersion.prop('readonly', false).css('background-color', '');
            }
        });

        // Form validation
        $('#developer-settings-form').on('submit', function(e) {
            var customVersion = $('#custom_version').val().trim();
            var randomEnabled = $('#random_version_enabled').is(':checked');

            if (!randomEnabled && customVersion === '') {
                e.preventDefault();
                SwalConfig.warning('<?php esc_attr_e('Please enter a plugin version number.', 'loyalty-program'); ?>').then(function() {
                    $('#custom_version').focus();
                });
                return false;
            }
        });
    });
</script>

<style>
    .loyalty-developer-section {
        background: #fff;
        border: 1px solid #c3c4c7;
        padding: 20px;
        margin: 20px 0;
        border-radius: 4px;
    }

    .loyalty-developer-section h2 {
        margin-top: 0;
        border-bottom: 1px solid #dcdcde;
        padding-bottom: 10px;
    }

    .loyalty-log-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .loyalty-log-viewer pre {
        white-space: pre-wrap;
        word-wrap: break-word;
    }

    .loyalty-log-actions .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
    }

    #coupon-generator-controls {
        background: #f6f7f7;
        border: 1px solid #c3c4c7;
        border-radius: 4px;
        padding: 20px;
        margin: 15px 0;
    }

    #coupon-generator-controls label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
    }

    #coupon-generator-controls input[type="number"] {
        width: 120px;
        margin-right: 10px;
    }

    #generate-status {
        margin-top: 15px;
    }
</style>

<!-- Coupon Generator (Test Tool) -->
<div class="loyalty-developer-section">
    <h2 class="loyalty-developer-section-toggle" style="cursor: pointer; user-select: none; position: relative; padding-left: 30px;">
        <span class="dashicons dashicons-arrow-right" style="position: absolute; left: 0; top: 50%; transform: translateY(-50%); margin-top: 2px;"></span>
        <?php _e('Coupon Generator (Test Tool)', 'loyalty-program'); ?>
    </h2>

    <div class="loyalty-developer-section-content" style="display: none;">
        <p class="description">
            <?php _e('Generate personal coupons for all loyalty program members. Process in batches to avoid server timeouts.', 'loyalty-program'); ?>
        </p>

        <div id="coupon-generator-controls">
            <div style="margin-bottom: 15px;">
                <label for="batch-offset">
                    <?php _e('Offset (Starting Position):', 'loyalty-program'); ?>
                </label>
                <input type="number" id="batch-offset" value="0" min="0" step="50" />
                <span class="description"><?php _e('Start from this user index', 'loyalty-program'); ?></span>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="batch-size">
                    <?php _e('Batch Size:', 'loyalty-program'); ?>
                </label>
                <input type="number" id="batch-size" value="50" min="10" max="500" step="10" />
                <span
                    class="description"><?php _e('Number of users to process per batch (default: 50)', 'loyalty-program'); ?></span>
            </div>

            <button type="button" id="generate-coupons-btn" class="button button-primary">
                <?php _e('Generate Coupons (Single Batch)', 'loyalty-program'); ?>
            </button>

            <button type="button" id="auto-generate-coupons-btn" class="button button-secondary">
                <?php _e('Auto-Generate All (Continuous)', 'loyalty-program'); ?>
            </button>

            <div id="generate-status"></div>
        </div>

        <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 15px; margin: 15px 0;">
            <p style="margin: 0;">
                <strong>⚠️ <?php _e('Important:', 'loyalty-program'); ?></strong>
                <?php _e('Single Batch mode processes one batch at a time - you control the offset manually. Auto-Generate mode will continue processing until all members have coupons.', 'loyalty-program'); ?>
            </p>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        var isAutoGenerating = false;

        // Single batch generation
        $('#generate-coupons-btn').on('click', function() {
            var $button = $(this);
            var $status = $('#generate-status');
            var offset = parseInt($('#batch-offset').val()) || 0;
            var batchSize = parseInt($('#batch-size').val()) || 50;

            $button.prop('disabled', true).text('<?php esc_attr_e('Processing...', 'loyalty-program'); ?>');
            $status.html(
                '<p class="description"><?php esc_html_e('Generating coupons...', 'loyalty-program'); ?></p>'
            );

            $.post(ajaxurl, {
                action: 'loyalty_program_generate_coupons',
                nonce: '<?php echo wp_create_nonce('loyalty_program_generate_coupons'); ?>',
                offset: offset,
                batch_size: batchSize
            }, function(response) {
                if (response.success) {
                    var data = response.data;
                    var newOffset = data.processed;

                    // Update offset input
                    $('#batch-offset').val(newOffset);

                    $status.html(
                        '<div class="notice notice-success inline"><p>' +
                        '<strong><?php esc_html_e('Batch completed!', 'loyalty-program'); ?></strong><br>' +
                        '<?php esc_html_e('Generated:', 'loyalty-program'); ?> ' + data
                        .generated + '<br>' +
                        '<?php esc_html_e('Skipped (already have coupon):', 'loyalty-program'); ?> ' +
                        data.skipped + '<br>' +
                        '<?php esc_html_e('Progress:', 'loyalty-program'); ?> ' + data
                        .processed + ' / ' + data.total +
                        ' <?php esc_html_e('members', 'loyalty-program'); ?><br>' +
                        (data.has_more ?
                            '<strong style="color: #d63638;"><?php esc_html_e('More batches remaining! Offset updated.', 'loyalty-program'); ?></strong>' :
                            '<strong style="color: #00a32a;"><?php esc_html_e('All members processed!', 'loyalty-program'); ?></strong>'
                        ) +
                        '</p></div>'
                    );
                } else {
                    $status.html(
                        '<div class="notice notice-error inline"><p>' +
                        (response.data.message ||
                            '<?php esc_html_e('Error generating coupons.', 'loyalty-program'); ?>'
                        ) +
                        '</p></div>'
                    );
                }
            }).fail(function() {
                $status.html(
                    '<div class="notice notice-error inline"><p><?php esc_html_e('Network error. Please try again.', 'loyalty-program'); ?></p></div>'
                );
            }).always(function() {
                $button.prop('disabled', false).text(
                    '<?php esc_attr_e('Generate Coupons (Single Batch)', 'loyalty-program'); ?>'
                );
            });
        });

        // Auto-generate (continuous)
        $('#auto-generate-coupons-btn').on('click', function() {
            var $button = $(this);
            var $status = $('#generate-status');

            if (isAutoGenerating) {
                // Stop auto-generation
                isAutoGenerating = false;
                $button.removeClass('button-secondary').text(
                    '<?php esc_attr_e('Auto-Generate All (Continuous)', 'loyalty-program'); ?>');
                return;
            }

            SwalConfig.confirm('<?php esc_attr_e('This will automatically process all batches until complete. Continue?', 'loyalty-program'); ?>').then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                isAutoGenerating = true;
                $button.addClass('button-secondary').text(
                    '<?php esc_attr_e('Stop Auto-Generate', 'loyalty-program'); ?>');
                $('#generate-coupons-btn').prop('disabled', true);

                var batchSize = parseInt($('#batch-size').val()) || 50;
                var totalGenerated = 0;
                var totalSkipped = 0;

                // Progress bar
                $status.html(
                '<div style="margin: 10px 0;">' +
                '<p class="description" id="auto-progress-text"><?php esc_html_e('Starting auto-generation...', 'loyalty-program'); ?></p>' +
                '<div style="background: #ddd; border-radius: 4px; height: 20px; overflow: hidden; margin-top: 5px;">' +
                '<div id="auto-progress-bar" style="background: #00a32a; height: 100%; width: 0%; transition: width 0.3s ease;"></div>' +
                '</div>' +
                '<p class="description" id="auto-progress-stats" style="margin-top: 5px;"></p>' +
                '</div>'
                );

                function processBatch(offset) {
                if (!isAutoGenerating) {
                    $status.html(
                        '<div class="notice notice-warning inline"><p><?php esc_html_e('Auto-generation stopped by user.', 'loyalty-program'); ?></p></div>'
                    );
                    $('#generate-coupons-btn').prop('disabled', false);
                    return;
                }

                $.post(ajaxurl, {
                    action: 'loyalty_program_generate_coupons',
                    nonce: '<?php echo wp_create_nonce('loyalty_program_generate_coupons'); ?>',
                    offset: offset,
                    batch_size: batchSize
                }, function(response) {
                    if (response.success) {
                        var data = response.data;
                        var percentage = Math.round((data.processed / data.total) * 100);

                        totalGenerated += data.generated;
                        totalSkipped += data.skipped;

                        // Update progress bar
                        $('#auto-progress-bar').css('width', percentage + '%');
                        $('#auto-progress-text').text(
                            '<?php esc_html_e('Auto-generating coupons...', 'loyalty-program'); ?> ' +
                            percentage + '%');
                        $('#auto-progress-stats').text(
                            data.processed + ' / ' + data.total +
                            ' <?php esc_html_e('members processed', 'loyalty-program'); ?> | ' +
                            '<?php esc_html_e('Generated:', 'loyalty-program'); ?> ' +
                            totalGenerated + ' | ' +
                            '<?php esc_html_e('Skipped:', 'loyalty-program'); ?> ' +
                            totalSkipped
                        );

                        // Update offset input
                        $('#batch-offset').val(data.processed);

                        // If there are more, continue
                        if (data.has_more) {
                            processBatch(data.processed);
                        } else {
                            // All done!
                            isAutoGenerating = false;
                            $status.html(
                                '<div class="notice notice-success inline"><p>' +
                                '<strong><?php esc_html_e('Auto-generation completed!', 'loyalty-program'); ?></strong><br>' +
                                '<?php esc_html_e('Total generated:', 'loyalty-program'); ?> ' +
                                totalGenerated + '<br>' +
                                '<?php esc_html_e('Total skipped:', 'loyalty-program'); ?> ' +
                                totalSkipped + '<br>' +
                                '<?php esc_html_e('Total processed:', 'loyalty-program'); ?> ' +
                                data.total +
                                ' <?php esc_html_e('members', 'loyalty-program'); ?>' +
                                '</p></div>'
                            );
                            $button.removeClass('button-secondary').text(
                                '<?php esc_attr_e('Auto-Generate All (Continuous)', 'loyalty-program'); ?>'
                            );
                            $('#generate-coupons-btn').prop('disabled', false);
                        }
                    } else {
                        isAutoGenerating = false;
                        $status.html(
                            '<div class="notice notice-error inline"><p>' +
                            (response.data.message ||
                                '<?php esc_html_e('Error generating coupons.', 'loyalty-program'); ?>'
                            ) +
                            '</p></div>'
                        );
                        $button.removeClass('button-secondary').text(
                            '<?php esc_attr_e('Auto-Generate All (Continuous)', 'loyalty-program'); ?>'
                        );
                        $('#generate-coupons-btn').prop('disabled', false);
                    }
                }).fail(function() {
                    isAutoGenerating = false;
                    $status.html(
                        '<div class="notice notice-error inline"><p><?php esc_html_e('Network error. Please try again.', 'loyalty-program'); ?></p></div>'
                    );
                    $button.removeClass('button-secondary').text(
                        '<?php esc_attr_e('Auto-Generate All (Continuous)', 'loyalty-program'); ?>'
                    );
                    $('#generate-coupons-btn').prop('disabled', false);
                });
                }

                // Start from current offset
                var startOffset = parseInt($('#batch-offset').val()) || 0;
                processBatch(startOffset);
            });
        });
    });
</script>

<!-- Funkcje dla programistów -->
<div class="loyalty-developer-section">
    <h2 class="loyalty-developer-toggle" style="cursor: pointer; user-select: none;">
        <span class="dashicons dashicons-arrow-right" style="display: inline-block; transition: transform 0.3s;"></span>
        <?php _e('Funkcje dla programistów', 'loyalty-program'); ?>
    </h2>

    <div class="loyalty-developer-content" style="display: none;">
        <p class="description">
            <?php _e('Gotowe funkcje PHP do wykorzystania w swoich projektach.', 'loyalty-program'); ?>
        </p>

        <div style="margin-top: 40px;">
            <h3 style="margin-top: 0;"><?php _e('Sprawdzanie daty urodzenia użytkownika', 'loyalty-program'); ?></h3>

            <p>
                <?php _e('Aby sprawdzić czy użytkownik podał datę urodzenia w systemie lojalnościowym, użyj poniższego kodu:', 'loyalty-program'); ?>
            </p>

            <div style="background: #f6f7f7; border-left: 4px solid #2271b1; padding: 15px; margin: 15px 0;">
                <p style="margin: 0 0 10px 0;">
                    <strong><?php _e('Przykład użycia:', 'loyalty-program'); ?></strong>
                    <?php _e('Sprawdzanie czy użytkownik uzupełnił datę urodzenia', 'loyalty-program'); ?>
                </p>
                <pre
                    style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code><?php echo esc_html('// Sprawdź czy aktualnie zalogowany użytkownik podał datę urodzenia
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    
    // Pobierz datę urodzenia z meta danych użytkownika
    $birth_date = get_user_meta($user_id, \'loyalty_program_birth_date\', true);
    
    if (!empty($birth_date)) {
        // Użytkownik podał datę urodzenia
        echo \'Data urodzenia: \' . $birth_date;
        
        // Możesz sformatować datę
        $date_obj = DateTime::createFromFormat(\'Y-m-d\', $birth_date);
        if ($date_obj) {
            echo \'Sformatowana: \' . $date_obj->format(\'d/m/Y\');
        }
    } else {
        // Użytkownik NIE podał daty urodzenia
        echo \'Nie podano daty urodzenia\';
    }
}

// Sprawdź datę urodzenia dla konkretnego użytkownika
$user_id = 123; // ID użytkownika
$birth_date = get_user_meta($user_id, \'loyalty_program_birth_date\', true);

if (!empty($birth_date)) {
    echo \'Użytkownik #\' . $user_id . \' podał datę urodzenia: \' . $birth_date;
} else {
    echo \'Użytkownik #\' . $user_id . \' nie podał daty urodzenia\';
}'); ?></code></pre>
            </div>

            <div style="background: #f0f6fc; border-left: 4px solid #72aee6; padding: 15px; margin: 15px 0;">
                <p style="margin: 0 0 5px 0;">
                    <strong><?php _e('Jak to działa:', 'loyalty-program'); ?></strong>
                </p>
                <ul style="margin: 5px 0 0 20px;">
                    <li><?php _e('Funkcja get_user_meta() pobiera datę urodzenia z bazy danych', 'loyalty-program'); ?></li>
                    <li><?php _e('Klucz meta: loyalty_program_birth_date', 'loyalty-program'); ?></li>
                    <li><?php _e('Format daty: Y-m-d (np. 1990-12-25)', 'loyalty-program'); ?></li>
                    <li><?php _e('Zwraca pustą wartość jeśli użytkownik nie podał daty', 'loyalty-program'); ?></li>
                    <li><?php _e('Możesz przekonwertować datę na dowolny format za pomocą DateTime', 'loyalty-program'); ?>
                    </li>
                </ul>
            </div>

            <div style="background: #fcf9e8; border-left: 4px solid #dba617; padding: 15px; margin: 15px 0;">
                <p style="margin: 0 0 5px 0;">
                    <strong><?php _e('Dodatkowe funkcje związane z datą urodzenia:', 'loyalty-program'); ?></strong>
                </p>
                <pre
                    style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code><?php echo esc_html('// Sprawdź czy użytkownik już otrzymał punkty za podanie daty urodzenia
$points_awarded = get_user_meta($user_id, \'loyalty_program_birthday_points_awarded\', true);
if ($points_awarded === \'yes\') {
    echo \'Użytkownik już otrzymał punkty za datę urodzenia\';
}

// Pobierz ilość punktów za datę urodzenia z ustawień
$birthday_points = get_option(\'loyalty_program_points_birthday\', 25);
echo \'Punkty za datę urodzenia: \' . $birthday_points;

// Sprawdź czy funkcja urodzin jest włączona
$birth_date_enabled = get_option(\'loyalty_program_enable_birth_date\', \'no\');
if ($birth_date_enabled === \'yes\') {
    echo \'Funkcja daty urodzenia jest włączona\';
}

// Pełna funkcja sprawdzająca
function loyalty_user_has_birth_date($user_id = null) {
    if ($user_id === null) {
        if (!is_user_logged_in()) {
            return false;
        }
        $user_id = get_current_user_id();
    }
    
    $birth_date = get_user_meta($user_id, \'loyalty_program_birth_date\', true);
    return !empty($birth_date);
}

// Użycie:
if (loyalty_user_has_birth_date()) {
    echo \'Użytkownik ma datę urodzenia\';
}'); ?></code></pre>
            </div>

            <div style="background: #f0f6fc; border-left: 4px solid #00a32a; padding: 15px; margin: 15px 0;">
                <p style="margin: 0 0 5px 0;">
                    <strong><?php _e('Przykład: Warunkowe wyświetlanie treści', 'loyalty-program'); ?></strong>
                </p>
                <pre
                    style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code><?php echo esc_html('// W szablonie WordPress - pokaż komunikat jeśli brak daty urodzenia
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $birth_date = get_user_meta($user_id, \'loyalty_program_birth_date\', true);
    
    if (empty($birth_date)) {
        echo \'<div class="notice notice-warning">
            <p>Uzupełnij datę urodzenia i zdobądź dodatkowe punkty!</p>
        </div>\';
    }
}

// Hook WordPress - wykonaj akcję gdy brak daty urodzenia
add_action(\'wp_footer\', function() {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $birth_date = get_user_meta($user_id, \'loyalty_program_birth_date\', true);
        
        if (empty($birth_date)) {
            // Wyświetl modal lub notyfikację
            ?>
            <script>
                console.log(\'Użytkownik nie podał daty urodzenia\');
            </script>
            <?php
        }
    }
});'); ?></code></pre>
            </div>

            <p style="margin-top: 15px;">
                <span class="dashicons dashicons-info" style="color: #2271b1;"></span>
                <strong><?php _e('Źródło:', 'loyalty-program'); ?></strong>
                <?php _e('Implementacja znajduje się w pliku:', 'loyalty-program'); ?>
                <code>includes/class-loyalty-program-shortcodes.php</code>
                <?php _e('(metoda birth_date_shortcode, linia ~1240)', 'loyalty-program'); ?>
            </p>
        </div>

        <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #dcdcde;">
            <h3 class="loyalty-developer-shortcode-toggle" style="margin-top: 0; cursor: pointer; user-select: none; position: relative; padding-left: 30px;">
                <span class="dashicons dashicons-arrow-right" style="position: absolute; left: 0; top: 50%; transform: translateY(-50%); margin-top: 2px;"></span>
                <?php _e('Shortcode: Sprawdzanie statusu daty urodzenia', 'loyalty-program'); ?>
            </h3>

            <div class="loyalty-developer-shortcode-content" style="display: none;">
                <p>
                    <?php _e('Nowy shortcode do sprawdzania czy użytkownik podał datę urodzenia. Zwraca "true" lub "false" jako tekst.', 'loyalty-program'); ?>
                </p>

                <div style="background: #f6f7f7; border-left: 4px solid #2271b1; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 10px 0;">
                        <strong><?php _e('Shortcode:', 'loyalty-program'); ?></strong>
                        <code>[loyalty_check_birth_date]</code>
                    </p>
                    <p style="margin: 0 0 10px 0;">
                        <strong><?php _e('Zwraca:', 'loyalty-program'); ?></strong>
                        <?php _e('Tekst "true" jeśli użytkownik podał datę urodzenia, "false" jeśli nie', 'loyalty-program'); ?>
                    </p>
                    <pre
                        style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code><?php echo esc_html('// Podstawowe użycie - sprawdzenie aktualnie zalogowanego użytkownika
[loyalty_check_birth_date]
// Zwraca: "true" lub "false"

// Sprawdzenie konkretnego użytkownika po ID
[loyalty_check_birth_date user_id="123"]
// Zwraca: "true" lub "false"'); ?></code></pre>
                </div>

                <div style="background: #f0f6fc; border-left: 4px solid #72aee6; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 5px 0;">
                        <strong><?php _e('Zastosowania:', 'loyalty-program'); ?></strong>
                    </p>
                    <ul style="margin: 5px 0 0 20px;">
                        <li><?php _e('Warunkowe wyświetlanie treści (z pluginem do warunkowych shortcodów)', 'loyalty-program'); ?>
                        </li>
                        <li><?php _e('Integracje z page builderami (Elementor, Divi, itp.)', 'loyalty-program'); ?></li>
                        <li><?php _e('JavaScript - sprawdzanie statusu na froncie', 'loyalty-program'); ?></li>
                        <li><?php _e('Dynamiczne ukrywanie/pokazywanie sekcji', 'loyalty-program'); ?></li>
                    </ul>
                </div>

                <div style="background: #fcf9e8; border-left: 4px solid #dba617; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 5px 0;">
                        <strong><?php _e('Przykłady użycia z innymi pluginami:', 'loyalty-program'); ?></strong>
                    </p>
                    <pre
                        style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code><?php echo esc_html('// 1. Z pluginem "Conditional Blocks" - pokaż treść tylko gdy NIE MA daty urodzenia
[if check="[loyalty_check_birth_date]" value="false"]
    <div class="alert">
        <p>📅 Uzupełnij datę urodzenia i zdobądź dodatkowe punkty!</p>
        [loyalty_birth_date]
    </div>
[/if]

// 2. Z pluginem "Shortcodes Ultimate" - warunkowe wyświetlanie
[su_note note_color="#fff3cd"]
Status daty urodzenia: [loyalty_check_birth_date]
[/su_note]

// 3. JavaScript - pobieranie wartości na froncie
<div id="birth-date-status" data-has-birth-date="[loyalty_check_birth_date]"></div>

<script>
jQuery(document).ready(function($) {
    var hasBirthDate = $(\'#birth-date-status\').data(\'has-birth-date\');
    
    if (hasBirthDate === \'true\') {
        console.log(\'Użytkownik ma datę urodzenia\');
        // Pokaż sekcję z nagrodami urodzinowymi
        $(\'.birthday-rewards\').show();
    } else {
        console.log(\'Użytkownik NIE ma daty urodzenia\');
        // Pokaż prompt do uzupełnienia
        $(\'.birthday-prompt\').show();
    }
});
</script>

// 4. PHP w szablonie (do_shortcode)
<?php
$has_birth_date = do_shortcode(\'[loyalty_check_birth_date]\');
if ($has_birth_date === \'true\') {
    echo \'<p>✓ Data urodzenia uzupełniona</p>\';
} else {
    echo \'<p>⚠ Proszę uzupełnić datę urodzenia</p>\';
}
?>'); ?></code></pre>
                </div>

                <div style="background: #f0f6fc; border-left: 4px solid #00a32a; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 5px 0;">
                        <strong><?php _e('Przykład: Dynamiczna sekcja nagród urodzinowych', 'loyalty-program'); ?></strong>
                    </p>
                    <pre
                        style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code><?php echo esc_html('<!-- HTML/Shortcode w treści strony -->
<div class="loyalty-birthday-section">
    <h3>🎂 Nagrody urodzinowe</h3>
    
    <div class="birth-date-status-wrapper" data-status="[loyalty_check_birth_date]">
        <noscript>
            <!-- Fallback dla użytkowników bez JavaScript -->
            [loyalty_check_birth_date]
        </noscript>
    </div>
    
    <div class="has-birth-date" style="display:none;">
        <p>✓ Data urodzenia uzupełniona!</p>
        <p>W dniu Twoich urodzin otrzymasz specjalne punkty i rabaty.</p>
    </div>
    
    <div class="no-birth-date" style="display:none;">
        <p>📅 Uzupełnij datę urodzenia aby odblokowac nagrody!</p>
        [loyalty_birth_date]
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var status = $(\'.birth-date-status-wrapper\').data(\'status\');
    
    if (status === \'true\') {
        $(\'.has-birth-date\').show();
    } else {
        $(\'.no-birth-date\').show();
    }
});
</script>'); ?></code></pre>
                </div>

                <p style="margin-top: 15px;">
                    <span class="dashicons dashicons-info" style="color: #2271b1;"></span>
                    <strong><?php _e('Dokumentacja:', 'loyalty-program'); ?></strong>
                    <?php _e('Pełna dokumentacja shortcode\'a dostępna w:', 'loyalty-program'); ?>
                    <a href="<?php echo admin_url('admin.php?page=loyalty-program-shortcodes'); ?>">
                        <?php _e('Loyalty Program → Shortcodes', 'loyalty-program'); ?>
                    </a>
                </p>

                <p style="margin-top: 10px;">
                    <span class="dashicons dashicons-editor-code" style="color: #2271b1;"></span>
                    <strong><?php _e('Implementacja:', 'loyalty-program'); ?></strong>
                    <code>includes/class-loyalty-program-shortcodes.php</code>
                    <?php _e('(metoda check_birth_date_shortcode)', 'loyalty-program'); ?>
                </p>
            </div>
        </div>

        <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #dcdcde;">
            <h3 class="loyalty-developer-shortcode-toggle" style="margin-top: 0; cursor: pointer; user-select: none; position: relative; padding-left: 30px;">
                <span class="dashicons dashicons-arrow-right" style="position: absolute; left: 0; top: 50%; transform: translateY(-50%); margin-top: 2px;"></span>
                <?php _e('Shortcode: Sprawdzanie kompletności profilu', 'loyalty-program'); ?>
            </h3>

            <div class="loyalty-developer-shortcode-content" style="display: none;">
                <p>
                    <?php _e('Nowy shortcode do sprawdzania czy użytkownik ma kompletny profil. Zwraca "true" lub "false" jako tekst.', 'loyalty-program'); ?>
                </p>

                <div style="background: #f6f7f7; border-left: 4px solid #2271b1; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 10px 0;">
                        <strong><?php _e('Shortcode:', 'loyalty-program'); ?></strong>
                        <code>[loyalty_check_profile_complete]</code>
                    </p>
                    <p style="margin: 0 0 10px 0;">
                        <strong><?php _e('Zwraca:', 'loyalty-program'); ?></strong>
                        <?php _e('Tekst "true" jeśli profil jest kompletny, "false" jeśli nie', 'loyalty-program'); ?>
                    </p>
                    <p style="margin: 0 0 10px 0;">
                        <strong><?php _e('Profil jest kompletny gdy:', 'loyalty-program'); ?></strong>
                    </p>
                    <ul style="margin: 5px 0 0 20px;">
                        <li>✅ <?php _e('Data urodzenia wypełniona', 'loyalty-program'); ?></li>
                        <li>✅ <?php _e('Zgoda SMS zaakceptowana', 'loyalty-program'); ?></li>
                        <li>✅ <?php _e('Zgoda Newsletter zaakceptowana', 'loyalty-program'); ?></li>
                        <li>✅ <?php _e('Numer telefonu wypełniony', 'loyalty-program'); ?></li>
                    </ul>
                    <pre
                        style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code><?php echo esc_html('// Podstawowe użycie - sprawdzenie aktualnie zalogowanego użytkownika
[loyalty_check_profile_complete]
// Zwraca: "true" lub "false"

// Sprawdzenie konkretnego użytkownika po ID
[loyalty_check_profile_complete user_id="123"]
// Zwraca: "true" lub "false"'); ?></code></pre>
                </div>

                <div style="background: #f0f6fc; border-left: 4px solid #72aee6; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 5px 0;">
                        <strong><?php _e('Funkcja PHP do sprawdzania kompletności profilu:', 'loyalty-program'); ?></strong>
                    </p>
                    <pre
                        style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code><?php echo esc_html('// Sprawdź czy aktualnie zalogowany użytkownik ma kompletny profil
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    
    // Pobierz wszystkie wymagane dane
    $birth_date = get_user_meta($user_id, \'loyalty_program_birth_date\', true);
    $sms_consent = get_user_meta($user_id, \'loyalty_program_sms_consent\', true);
    $newsletter_consent = get_user_meta($user_id, \'loyalty_program_newsletter_consent\', true);
    $billing_phone = get_user_meta($user_id, \'billing_phone\', true);
    
    // Sprawdź czy profil jest kompletny (dokładnie ta sama logika co w shortcode)
    $profile_complete = !empty($birth_date)
        && $sms_consent === \'yes\'
        && $newsletter_consent === \'yes\'
        && !empty($billing_phone);
    
    if ($profile_complete) {
        echo \'✓ Profil kompletny\';
    } else {
        echo \'⚠ Profil niekompletny - uzupełnij brakujące dane\';
        
        // Pokaż co brakuje
        if (empty($birth_date)) echo \'<br>- Brak daty urodzenia\';
        if ($sms_consent !== \'yes\') echo \'<br>- Brak zgody SMS\';
        if ($newsletter_consent !== \'yes\') echo \'<br>- Brak zgody Newsletter\';
        if (empty($billing_phone)) echo \'<br>- Brak numeru telefonu\';
    }
}

// Funkcja pomocnicza
function loyalty_is_profile_complete($user_id = null) {
    if ($user_id === null) {
        if (!is_user_logged_in()) {
            return false;
        }
        $user_id = get_current_user_id();
    }
    
    $birth_date = get_user_meta($user_id, \'loyalty_program_birth_date\', true);
    $sms_consent = get_user_meta($user_id, \'loyalty_program_sms_consent\', true);
    $newsletter_consent = get_user_meta($user_id, \'loyalty_program_newsletter_consent\', true);
    $billing_phone = get_user_meta($user_id, \'billing_phone\', true);
    
    return !empty($birth_date)
        && $sms_consent === \'yes\'
        && $newsletter_consent === \'yes\'
        && !empty($billing_phone);
}

// Użycie:
if (loyalty_is_profile_complete()) {
    echo \'Profil użytkownika jest kompletny\';
}'); ?></code></pre>
                </div>

                <div style="background: #fcf9e8; border-left: 4px solid #dba617; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 5px 0;">
                        <strong><?php _e('Przykłady użycia shortcode:', 'loyalty-program'); ?></strong>
                    </p>
                    <pre
                        style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code><?php echo esc_html('// 1. Warunkowe wyświetlanie z pluginem "Conditional Blocks"
[if check="[loyalty_check_profile_complete]" value="false"]
    <div class="alert alert-warning">
        <h4>⚠ Uzupełnij swój profil!</h4>
        <p>Aby otrzymać dodatkowe punkty, uzupełnij:</p>
        <ul>
            <li>Datę urodzenia</li>
            <li>Zgody marketingowe</li>
            <li>Numer telefonu</li>
        </ul>
    </div>
[/if]

[if check="[loyalty_check_profile_complete]" value="true"]
    <div class="alert alert-success">
        ✓ Twój profil jest kompletny! Dziękujemy.
    </div>
[/if]

// 2. JavaScript - dynamiczne zmiany na podstawie statusu
<div id="profile-status" data-complete="[loyalty_check_profile_complete]"></div>

<script>
jQuery(document).ready(function($) {
    var isComplete = $(\'#profile-status\').data(\'complete\');
    
    if (isComplete === \'true\') {
        // Profil kompletny - pokaż bonusy
        $(\'.profile-complete-rewards\').show();
        $(\'.complete-profile-prompt\').hide();
    } else {
        // Profil niekompletny - pokaż prompt
        $(\'.profile-complete-rewards\').hide();
        $(\'.complete-profile-prompt\').show();
    }
});
</script>

// 3. PHP w szablonie
<?php
$is_complete = do_shortcode(\'[loyalty_check_profile_complete]\');
if ($is_complete === \'true\') {
    echo \'<div class="badge badge-success">Profil Kompletny</div>\';
} else {
    echo \'<div class="badge badge-warning">Uzupełnij Profil</div>\';
}
?>'); ?></code></pre>
                </div>

                <div style="background: #f0f6fc; border-left: 4px solid #00a32a; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 5px 0;">
                        <strong><?php _e('Przykład: Panel postępu uzupełniania profilu', 'loyalty-program'); ?></strong>
                    </p>
                    <pre
                        style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code><?php echo esc_html('<!-- HTML/Shortcode w treści strony -->
<div class="profile-completion-panel">
    <h3>📋 Uzupełnij swój profil</h3>
    
    <div class="profile-status" data-complete="[loyalty_check_profile_complete]">
        <!-- Status będzie dynamicznie aktualizowany przez JS -->
    </div>
    
    <div class="profile-incomplete" style="display:none;">
        <div class="progress-bar">
            <div class="progress-fill" style="width: 0%;"></div>
        </div>
        
        <h4>Brakujące elementy:</h4>
        <ul class="checklist">
            <li data-field="birth_date">
                <span class="icon">❌</span> Data urodzenia
                [loyalty_birth_date]
            </li>
            <li data-field="consents">
                <span class="icon">❌</span> Zgody marketingowe
                [loyalty_consents]
            </li>
            <li data-field="phone">
                <span class="icon">❌</span> Numer telefonu
                <a href="/moje-konto/edit-account/">Uzupełnij w koncie</a>
            </li>
        </ul>
        
        <p class="reward-info">
            🎁 Po uzupełnieniu profilu otrzymasz dodatkowe punkty!
        </p>
    </div>
    
    <div class="profile-complete" style="display:none;">
        <div class="success-message">
            <span class="icon">✅</span>
            <h4>Gratulacje! Twój profil jest kompletny!</h4>
            <p>Masz dostęp do wszystkich funkcji programu lojalnościowego.</p>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var isComplete = $(\'.profile-status\').data(\'complete\');
    
    if (isComplete === \'true\') {
        $(\'.profile-complete\').show();
        $(\'.profile-incomplete\').hide();
    } else {
        $(\'.profile-incomplete\').show();
        $(\'.profile-complete\').hide();
        
        // Opcjonalnie: sprawdź co konkretnie brakuje i zaktualizuj UI
        checkMissingFields();
    }
    
    function checkMissingFields() {
        // Tu możesz dodać logikę sprawdzającą konkretne pola
        // i aktualizującą procent wypełnienia
    }
});
</script>'); ?></code></pre>
                </div>

                <p style="margin-top: 15px;">
                    <span class="dashicons dashicons-info" style="color: #2271b1;"></span>
                    <strong><?php _e('Dokumentacja:', 'loyalty-program'); ?></strong>
                    <?php _e('Pełna dokumentacja shortcode\'a dostępna w:', 'loyalty-program'); ?>
                    <a href="<?php echo admin_url('admin.php?page=loyalty-program-shortcodes'); ?>">
                        <?php _e('Loyalty Program → Shortcodes', 'loyalty-program'); ?>
                    </a>
                </p>

                <p style="margin-top: 10px;">
                    <span class="dashicons dashicons-editor-code" style="color: #2271b1;"></span>
                    <strong><?php _e('Źródło:', 'loyalty-program'); ?></strong>
                    <?php _e('Logika sprawdzania znajduje się w:', 'loyalty-program'); ?>
                    <code>includes/class-loyalty-program-woocommerce.php</code>
                    <?php _e('(metoda check_and_award_profile_completion_points)', 'loyalty-program'); ?>
                </p>

                <p style="margin-top: 10px;">
                    <span class="dashicons dashicons-editor-code" style="color: #2271b1;"></span>
                    <strong><?php _e('Implementacja shortcode:', 'loyalty-program'); ?></strong>
                    <code>includes/class-loyalty-program-shortcodes.php</code>
                    <?php _e('(metoda check_profile_complete_shortcode)', 'loyalty-program'); ?>
                </p>
            </div>
        </div>

        <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #dcdcde;">
            <h3 class="loyalty-developer-shortcode-toggle" style="margin-top: 0; cursor: pointer; user-select: none; position: relative; padding-left: 30px;">
                <span class="dashicons dashicons-arrow-right" style="position: absolute; left: 0; top: 50%; transform: translateY(-50%); margin-top: 2px;"></span>
                <?php _e('Shortcode: Sprawdzanie wypełnienia ankiety/quizu', 'loyalty-program'); ?>
            </h3>

            <div class="loyalty-developer-shortcode-content" style="display: none;">
                <p>
                    <?php _e('Nowy shortcode do sprawdzania czy użytkownik wypełnił konkretną ankietę lub quiz. Wymaga podania ID ankiety. Zwraca "true" lub "false" jako tekst.', 'loyalty-program'); ?>
                </p>

                <div style="background: #f6f7f7; border-left: 4px solid #2271b1; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 10px 0;">
                        <strong><?php _e('Shortcode:', 'loyalty-program'); ?></strong>
                        <code>[loyalty_check_survey_completed id="survey_id"]</code>
                    </p>
                    <p style="margin: 0 0 10px 0;">
                        <strong><?php _e('Parametry:', 'loyalty-program'); ?></strong>
                    </p>
                    <ul style="margin: 5px 0 0 20px;">
                        <li><code>id</code> - ID ankiety/quizu (WYMAGANE)</li>
                        <li><code>user_id</code> - ID użytkownika (opcjonalne, domyślnie: aktualnie zalogowany)</li>
                    </ul>
                    <p style="margin: 10px 0 10px 0;">
                        <strong><?php _e('Zwraca:', 'loyalty-program'); ?></strong>
                        <?php _e('Tekst "true" jeśli użytkownik wypełnił ankietę, "false" jeśli nie', 'loyalty-program'); ?>
                    </p>
                    <pre
                        style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code><?php echo esc_html('// Podstawowe użycie - sprawdzenie dla aktualnego użytkownika
[loyalty_check_survey_completed id="survey_123"]
// Zwraca: "true" lub "false"

// Sprawdzenie konkretnego użytkownika
[loyalty_check_survey_completed id="quiz_456" user_id="789"]
// Zwraca: "true" lub "false"

// Sprawdzenie różnych ankiet
[loyalty_check_survey_completed id="welcome_quiz"]
[loyalty_check_survey_completed id="product_survey"]
[loyalty_check_survey_completed id="satisfaction_poll"]'); ?></code></pre>
                </div>

                <div style="background: #f0f6fc; border-left: 4px solid #72aee6; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 5px 0;">
                        <strong><?php _e('Funkcja PHP do sprawdzania wypełnienia ankiety:', 'loyalty-program'); ?></strong>
                    </p>
                    <pre
                        style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code><?php echo esc_html('// Sprawdź czy użytkownik wypełnił konkretną ankietę
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $survey_id = \'survey_123\'; // ID ankiety
    
    // Pobierz listę wypełnionych ankiet
    $completed_surveys = get_user_meta($user_id, \'loyalty_program_completed_surveys\', true);
    if (!is_array($completed_surveys)) {
        $completed_surveys = array();
    }
    
    // Sprawdź czy ankieta jest na liście wypełnionych
    $is_completed = in_array($survey_id, $completed_surveys);
    
    if ($is_completed) {
        echo \'✓ Użytkownik wypełnił ankietę\';
    } else {
        echo \'⚠ Ankieta nie została wypełniona\';
    }
}

// Funkcja pomocnicza
function loyalty_check_survey_completed($survey_id, $user_id = null) {
    if ($user_id === null) {
        if (!is_user_logged_in()) {
            return false;
        }
        $user_id = get_current_user_id();
    }
    
    $completed_surveys = get_user_meta($user_id, \'loyalty_program_completed_surveys\', true);
    if (!is_array($completed_surveys)) {
        $completed_surveys = array();
    }
    
    return in_array($survey_id, $completed_surveys);
}

// Użycie:
if (loyalty_check_survey_completed(\'welcome_quiz\')) {
    echo \'Użytkownik ukończył quiz powitalny\';
}

// Sprawdź wiele ankiet naraz
$surveys_to_check = [\'survey_1\', \'survey_2\', \'quiz_1\'];
$completed_count = 0;

foreach ($surveys_to_check as $survey_id) {
    if (loyalty_check_survey_completed($survey_id)) {
        $completed_count++;
    }
}

echo "Wypełniono $completed_count z " . count($surveys_to_check) . " ankiet";'); ?></code></pre>
                </div>

                <div style="background: #fcf9e8; border-left: 4px solid #dba617; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 5px 0;">
                        <strong><?php _e('Przykłady użycia shortcode:', 'loyalty-program'); ?></strong>
                    </p>
                    <pre
                        style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code><?php echo esc_html('// 1. Warunkowe wyświetlanie z pluginem "Conditional Blocks"
[if check="[loyalty_check_survey_completed id=\'welcome_quiz\']" value="false"]
    <div class="alert">
        <h4>🎁 Bonus za wypełnienie!</h4>
        <p>Wypełnij quiz powitalny i zdobądź dodatkowe punkty:</p>
        [loyalty_survey id="welcome_quiz"]
    </div>
[/if]

[if check="[loyalty_check_survey_completed id=\'welcome_quiz\']" value="true"]
    <div class="success">
        ✓ Quiz powitalny ukończony! Dziękujemy.
    </div>
[/if]

// 2. Gamifikacja - pokaż progress wypełniania ankiet
<div class="surveys-progress">
    <h3>Postęp ankiet (3/5)</h3>
    <ul>
        <li data-completed="[loyalty_check_survey_completed id=\'survey_1\']">
            Ankieta 1: <span class="status"></span>
        </li>
        <li data-completed="[loyalty_check_survey_completed id=\'survey_2\']">
            Ankieta 2: <span class="status"></span>
        </li>
        <li data-completed="[loyalty_check_survey_completed id=\'survey_3\']">
            Ankieta 3: <span class="status"></span>
        </li>
    </ul>
</div>

<script>
jQuery(document).ready(function($) {
    $(\'.surveys-progress li\').each(function() {
        var isCompleted = $(this).data(\'completed\');
        var $status = $(this).find(\'.status\');
        
        if (isCompleted === \'true\') {
            $status.html(\'✅ Ukończona\').css(\'color\', \'green\');
            $(this).addClass(\'completed\');
        } else {
            $status.html(\'⏳ Do wypełnienia\').css(\'color\', \'orange\');
        }
    });
});
</script>

// 3. Odblokowywanie treści po wypełnieniu ankiety
<div id="exclusive-content" 
     data-survey-completed="[loyalty_check_survey_completed id=\'product_survey\']">
    <!-- Treść będzie pokazana dynamicznie -->
</div>

<script>
jQuery(document).ready(function($) {
    var surveyCompleted = $(\'#exclusive-content\').data(\'survey-completed\');
    
    if (surveyCompleted === \'true\') {
        // Pokaż ekskluzywną treść
        $(\'#exclusive-content\').html(\'
            <div class="exclusive">
                <h3>🎉 Ekskluzywna treść odblokowan!</h3>
                <p>Dziękujemy za wypełnienie ankiety.</p>
                <!-- treść premium -->
            </div>
        \');
    } else {
        // Pokaż prompt do wypełnienia
        $(\'#exclusive-content\').html(\'
            <div class="locked">
                <h3>🔒 Treść zablokowana</h3>
                <p>Wypełnij ankietę aby odblokować ekskluzywne treści.</p>
                [loyalty_survey id="product_survey"]
            </div>
        \');
    }
});
</script>

// 4. PHP w szablonie
<?php
$survey_completed = do_shortcode(\'[loyalty_check_survey_completed id="feedback_survey"]\');
if ($survey_completed === \'true\') {
    echo \'<div class="badge success">Ankieta wypełniona ✓</div>\';
} else {
    echo \'<div class="badge warning">Wypełnij ankietę</div>\';
    echo do_shortcode(\'[loyalty_survey id="feedback_survey"]\');
}
?>'); ?></code></pre>
                </div>

                <div style="background: #f0f6fc; border-left: 4px solid #00a32a; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 5px 0;">
                        <strong><?php _e('Przykład: System osiągnięć oparty na ankietach', 'loyalty-program'); ?></strong>
                    </p>
                    <pre
                        style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code><?php echo esc_html('<!-- System osiągnięć -->
<div class="achievements-panel">
    <h2>🏆 Twoje osiągnięcia</h2>
    
    <div class="achievement" data-survey="welcome_quiz" 
         data-completed="[loyalty_check_survey_completed id=\'welcome_quiz\']">
        <div class="achievement-icon">🎓</div>
        <div class="achievement-info">
            <h3>Nowicjusz</h3>
            <p>Ukończ quiz powitalny</p>
        </div>
        <div class="achievement-status"></div>
    </div>
    
    <div class="achievement" data-survey="product_expert" 
         data-completed="[loyalty_check_survey_completed id=\'product_expert\']">
        <div class="achievement-icon">⭐</div>
        <div class="achievement-info">
            <h3>Ekspert produktowy</h3>
            <p>Wypełnij ankietę o produktach</p>
        </div>
        <div class="achievement-status"></div>
    </div>
    
    <div class="achievement" data-survey="master_survey" 
         data-completed="[loyalty_check_survey_completed id=\'master_survey\']">
        <div class="achievement-icon">👑</div>
        <div class="achievement-info">
            <h3>Mistrz ankiet</h3>
            <p>Ukończ zaawansowaną ankietę</p>
        </div>
        <div class="achievement-status"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var completedCount = 0;
    var totalCount = $(\'.achievement\').length;
    
    $(\'.achievement\').each(function() {
        var isCompleted = $(this).data(\'completed\');
        var $status = $(this).find(\'.achievement-status\');
        
        if (isCompleted === \'true\') {
            $(this).addClass(\'unlocked\');
            $status.html(\'<span class="badge success">✓ Odblokowane</span>\');
            completedCount++;
        } else {
            $(this).addClass(\'locked\');
            $status.html(\'<span class="badge locked">🔒 Zablokowane</span>\');
        }
    });
    
    // Pokaż progress
    var progressPercent = Math.round((completedCount / totalCount) * 100);
    $(\'<div class="progress-summary">\')
        .html(\'Postęp: \' + completedCount + \'/\' + totalCount + \' (\' + progressPercent + \'%)\')
        .prependTo(\'.achievements-panel\');
});
</script>

<style>
.achievement {
    display: flex;
    align-items: center;
    padding: 15px;
    margin: 10px 0;
    border: 2px solid #ddd;
    border-radius: 8px;
    transition: all 0.3s;
}

.achievement.unlocked {
    border-color: #00a32a;
    background: #f0f9f4;
}

.achievement.locked {
    opacity: 0.6;
    filter: grayscale(50%);
}

.achievement-icon {
    font-size: 48px;
    margin-right: 20px;
}
</style>'); ?></code></pre>
                </div>

                <p style="margin-top: 15px;">
                    <span class="dashicons dashicons-info" style="color: #2271b1;"></span>
                    <strong><?php _e('Dokumentacja:', 'loyalty-program'); ?></strong>
                    <?php _e('Pełna dokumentacja shortcode\'a dostępna w:', 'loyalty-program'); ?>
                    <a href="<?php echo admin_url('admin.php?page=loyalty-program-shortcodes'); ?>">
                        <?php _e('Loyalty Program → Shortcodes', 'loyalty-program'); ?>
                    </a>
                </p>

                <p style="margin-top: 10px;">
                    <span class="dashicons dashicons-editor-code" style="color: #2271b1;"></span>
                    <strong><?php _e('Źródło danych:', 'loyalty-program'); ?></strong>
                    <?php _e('Wypełnione ankiety są zapisywane w:', 'loyalty-program'); ?>
                    <code>loyalty_program_completed_surveys</code>
                    <?php _e('(user meta - tablica ID ankiet)', 'loyalty-program'); ?>
                </p>

                <p style="margin-top: 10px;">
                    <span class="dashicons dashicons-editor-code" style="color: #2271b1;"></span>
                    <strong><?php _e('Implementacja shortcode:', 'loyalty-program'); ?></strong>
                    <code>includes/class-loyalty-program-shortcodes.php</code>
                    <?php _e('(metoda check_survey_completed_shortcode)', 'loyalty-program'); ?>
                </p>

                <p style="margin-top: 10px;">
                    <span class="dashicons dashicons-editor-code" style="color: #2271b1;"></span>
                    <strong><?php _e('Logika ankiet:', 'loyalty-program'); ?></strong>
                    <code>includes/class-loyalty-program-shortcodes.php</code>
                    <?php _e('(metoda survey_shortcode - linia ~1745)', 'loyalty-program'); ?>
                </p>
            </div>
        </div>

        <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #dcdcde;">
            <h3 class="loyalty-developer-shortcode-toggle" style="margin-top: 0; cursor: pointer; user-select: none; position: relative; padding-left: 30px;">
                <span class="dashicons dashicons-arrow-right" style="position: absolute; left: 0; top: 50%; transform: translateY(-50%); margin-top: 2px;"></span>
                <?php _e('Shortcode: Sprawdzanie uczestnictwa w Live with Expert', 'loyalty-program'); ?>
            </h3>

            <div class="loyalty-developer-shortcode-content" style="display: none;">
                <p>
                    <?php _e('Nowy shortcode do sprawdzania czy użytkownik uczestniczył w konkretnym live session. Wymaga podania CSV ID z importu. Zwraca "true" lub "false" jako tekst.', 'loyalty-program'); ?>
                </p>

                <div style="background: #f6f7f7; border-left: 4px solid #2271b1; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 10px 0;">
                        <strong><?php _e('Shortcode:', 'loyalty-program'); ?></strong>
                        <code>[loyalty_check_live_participated id="live_123456789_1234"]</code>
                    </p>
                    <p style="margin: 0 0 10px 0;">
                        <strong><?php _e('Parametry:', 'loyalty-program'); ?></strong>
                    </p>
                    <ul style="margin: 5px 0 0 20px;">
                        <li><code>id</code> - CSV ID z historii importów Live Expert (WYMAGANE)</li>
                        <li><code>user_id</code> - ID użytkownika (opcjonalne, domyślnie: aktualnie zalogowany)</li>
                    </ul>
                    <p style="margin: 10px 0 10px 0;">
                        <strong><?php _e('Zwraca:', 'loyalty-program'); ?></strong>
                        <?php _e('Tekst "true" jeśli użytkownik był w tym CSV, "false" jeśli nie', 'loyalty-program'); ?>
                    </p>
                    <p style="margin: 10px 0 10px 0;">
                        <strong><?php _e('Gdzie znaleźć CSV ID?', 'loyalty-program'); ?></strong><br>
                        <?php _e('CSV ID znajdziesz w:', 'loyalty-program'); ?>
                        <a href="<?php echo admin_url('admin.php?page=loyalty-program-live-expert'); ?>">
                            <?php _e('Loyalty Program → Live with Expert → Import History', 'loyalty-program'); ?>
                        </a>
                    </p>
                    <pre
                        style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code><?php echo esc_html('// Podstawowe użycie - sprawdzenie dla aktualnego użytkownika
[loyalty_check_live_participated id="live_1699876543_1234"]
// Zwraca: "true" lub "false"

// Sprawdzenie konkretnego użytkownika
[loyalty_check_live_participated id="live_1699876543_1234" user_id="789"]
// Zwraca: "true" lub "false"

// Sprawdzenie różnych live sessions
[loyalty_check_live_participated id="live_1699876543_1234"]
[loyalty_check_live_participated id="live_1699876544_5678"]
[loyalty_check_live_participated id="live_1699876545_9012"]'); ?></code></pre>
                </div>

                <div style="background: #f0f6fc; border-left: 4px solid #72aee6; padding: 15px; margin: 15px 0;">
                    <p style="margin: 0 0 5px 0;">
                        <strong><?php _e('Funkcja PHP do sprawdzania uczestnictwa:', 'loyalty-program'); ?></strong>
                    </p>
                    <pre
                        style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px; margin: 10px 0 0 0;"><code>// Sprawdź czy użytkownik uczestniczył w konkretnym live - PROSTA METODA
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $csv_id = 'live_1763040662_8544'; // CSV ID z historii importów
    
    // Pobierz listę live sessions użytkownika (prosta tablica CSV ID)
    $user_live_sessions = get_user_meta($user_id, 'loyalty_program_live_sessions', true);
    
    if (!is_array($user_live_sessions)) {
        $user_live_sessions = array();
    }
    
    // Sprawdź czy CSV ID jest na liście
    if (in_array($csv_id, $user_live_sessions)) {
        echo 'Użytkownik uczestniczył w tym live ✓';
    } else {
        echo 'Użytkownik nie był w tym live';
    }
}

// Funkcja pomocnicza (bardzo prosta!)
function loyalty_check_live_participated($csv_id, $user_id = null) {
    if ($user_id === null) {
        if (!is_user_logged_in()) {
            return false;
        }
        $user_id = get_current_user_id();
    }
    
    // Pobierz prostą tablicę CSV ID
    $user_live_sessions = get_user_meta($user_id, 'loyalty_program_live_sessions', true);
    
    if (!is_array($user_live_sessions)) {
        return false;
    }
    
    // Sprawdź czy CSV ID jest na liście
    return in_array($csv_id, $user_live_sessions);
}

// Użycie:
if (loyalty_check_live_participated('live_1763040662_8544')) {
    echo 'Użytkownik był na live session';
}

// Pobierz wszystkie live sessions użytkownika
$user_id = get_current_user_id();
$all_sessions = get_user_meta($user_id, 'loyalty_program_live_sessions', true);
// Zwraca prostą tablicę:
// array(
//     'live_1763040662_8544',
//     'live_1763040663_1234',
//     'live_1763040664_5678',
// )

if (!empty($all_sessions)) {
    echo 'Użytkownik uczestniczył w ' . count($all_sessions) . ' live sessions';
    
    // Wyświetl listę wszystkich live sessions
    foreach ($all_sessions as $session_id) {
        echo '- ' . $session_id . '<br>';
    }
}</code></pre>
                </div>

                <p style="margin-top: 15px;">
                    <span class="dashicons dashicons-info" style="color: #2271b1;"></span>
                    <strong><?php _e('Dokumentacja:', 'loyalty-program'); ?></strong>
                    <?php _e('Pełna dokumentacja shortcode\'a dostępna w:', 'loyalty-program'); ?>
                    <a href="<?php echo admin_url('admin.php?page=loyalty-program-shortcodes'); ?>">
                        <?php _e('Loyalty Program → Shortcodes', 'loyalty-program'); ?>
                    </a>
                </p>

                <p style="margin-top: 10px;">
                    <span class="dashicons dashicons-info" style="color: #2271b1;"></span>
                    <strong><?php _e('Gdzie znaleźć CSV ID:', 'loyalty-program'); ?></strong>
                    <a href="<?php echo admin_url('admin.php?page=loyalty-program-live-expert'); ?>">
                        <?php _e('Loyalty Program → Live with Expert', 'loyalty-program'); ?>
                    </a>
                    <?php _e('(tabela Import History)', 'loyalty-program'); ?>
                </p>

                <p style="margin-top: 10px;">
                    <span class="dashicons dashicons-editor-code" style="color: #2271b1;"></span>
                    <strong><?php _e('Źródło danych:', 'loyalty-program'); ?></strong>
                    <?php _e('CSV ID jest zapisywane w dedykowanej meta użytkownika:', 'loyalty-program'); ?>
                    <code>loyalty_program_live_sessions</code>
                    <?php _e('(user meta - prosta tablica CSV ID)', 'loyalty-program'); ?>
                </p>

                <p style="margin-top: 10px;">
                    <span class="dashicons dashicons-editor-code" style="color: #2271b1;"></span>
                    <strong><?php _e('Implementacja shortcode:', 'loyalty-program'); ?></strong>
                    <code>includes/class-loyalty-program-shortcodes.php</code>
                    <?php _e('(metoda check_live_participated_shortcode)', 'loyalty-program'); ?>
                </p>

                <p style="margin-top: 10px;">
                    <span class="dashicons dashicons-editor-code" style="color: #2271b1;"></span>
                    <strong><?php _e('Proces importu CSV:', 'loyalty-program'); ?></strong>
                    <code>includes/admin/class-loyalty-program-admin-menu.php</code>
                    <?php _e('(metoda process_live_csv)', 'loyalty-program'); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Toggle dla głównej sekcji "Funkcje dla programistów"
        $('.loyalty-developer-toggle').on('click', function() {
            var $content = $(this).closest('.loyalty-developer-section').find('.loyalty-developer-content');
            var $icon = $(this).find('.dashicons');

            $content.slideToggle(300);
            $icon.toggleClass('dashicons-arrow-right dashicons-arrow-down');
        });

        // Toggle dla sekcji shortcode'ów
        $('.loyalty-developer-shortcode-toggle').on('click', function() {
            var $content = $(this).next('.loyalty-developer-shortcode-content');
            var $icon = $(this).find('.dashicons');

            $content.slideToggle(300);
            $icon.toggleClass('dashicons-arrow-right dashicons-arrow-down');
        });

        // Toggle dla sekcji "Custom Integrations" i "Coupon Generator"
        $('.loyalty-developer-section-toggle').on('click', function() {
            var $content = $(this).next('.loyalty-developer-section-content');
            var $icon = $(this).find('.dashicons');

            $content.slideToggle(300);
            $icon.toggleClass('dashicons-arrow-right dashicons-arrow-down');
        });
    });
</script>

<style>
    .loyalty-developer-toggle {
        position: relative;
        padding-left: 30px;
    }

    .loyalty-developer-toggle .dashicons {
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        margin-top: 2px;
    }

    .loyalty-developer-toggle:hover {
        color: #2271b1;
    }

    .loyalty-developer-shortcode-toggle:hover {
        color: #2271b1;
    }

    .loyalty-developer-section-toggle:hover {
        color: #2271b1;
    }
</style>