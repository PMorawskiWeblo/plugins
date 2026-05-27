<?php

/**
 * Admin Live with Expert View
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current points setting
$points_live_expert = get_option('loyalty_program_points_live_expert', 30);

// Get import results if available
$import_results = get_transient('loyalty_program_live_import_results');
if ($import_results) {
    delete_transient('loyalty_program_live_import_results');
}

// Get CSV history
$csv_history = get_option('loyalty_program_live_csv_history', array());
// Reverse to show newest first
$csv_history = array_reverse($csv_history);

settings_errors('loyalty_program_live');
?>

<div class="wrap loyalty-program-live-expert">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <p class="description">
        <?php _e('Award points to users who participated in live sessions with experts by uploading a CSV file with their email addresses.', 'loyalty-program'); ?>
    </p>

    <!-- Current Settings Info -->
    <div class="loyalty-info-banner">
        <span class="dashicons dashicons-info"></span>
        <div>
            <strong><?php _e('Current Setting:', 'loyalty-program'); ?></strong>
            <?php echo sprintf(__('Each user will receive %d points per live session.', 'loyalty-program'), $points_live_expert); ?>
            <a href="<?php echo admin_url('admin.php?page=loyalty-program-settings'); ?>" class="button button-small">
                <?php _e('Change Points', 'loyalty-program'); ?>
            </a>
        </div>
    </div>

    <!-- Upload Form -->
    <div class="loyalty-upload-section">
        <h2><?php _e('Upload CSV File', 'loyalty-program'); ?></h2>

        <form method="post" enctype="multipart/form-data" id="live-csv-upload-form">
            <?php wp_nonce_field('loyalty_program_live_csv', 'loyalty_program_live_csv_nonce'); ?>
            <input type="hidden" name="loyalty_program_live_csv_action" value="process">

            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="live_title">
                                <?php _e('Live Session Title', 'loyalty-program'); ?>
                                <span class="required">*</span>
                            </label>
                        </th>
                        <td>
                            <input
                                type="text"
                                name="live_title"
                                id="live_title"
                                class="regular-text"
                                required
                                placeholder="<?php esc_attr_e('e.g., Product Training Session - October 2025', 'loyalty-program'); ?>">
                            <p class="description">
                                <?php _e('This title will appear in the user\'s points history.', 'loyalty-program'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="live_csv_file">
                                <?php _e('CSV File', 'loyalty-program'); ?>
                                <span class="required">*</span>
                            </label>
                        </th>
                        <td>
                            <input
                                type="file"
                                name="live_csv_file"
                                id="live_csv_file"
                                accept=".csv"
                                required>
                            <p class="description">
                                <?php _e('Upload a CSV file containing user email addresses (one per line).', 'loyalty-program'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="loyalty_program_live_csv_submit" value="<?php esc_attr_e('Upload and Award Points', 'loyalty-program'); ?>" class="button button-primary">
            </p>
        </form>
    </div>

    <!-- CSV Format Instructions -->
    <div class="loyalty-csv-instructions">
        <h2><?php _e('CSV File Format', 'loyalty-program'); ?></h2>

        <div class="instruction-box">
            <h3><?php _e('Required Format:', 'loyalty-program'); ?></h3>
            <p><?php _e('The CSV file should contain one email address per line. The first row can optionally be a header.', 'loyalty-program'); ?></p>

            <h4><?php _e('Example CSV:', 'loyalty-program'); ?></h4>
            <div class="csv-example">
                <code>
                    email<br>
                    user1@example.com<br>
                    user2@example.com<br>
                    user3@example.com
                </code>
            </div>

            <p style="margin-top: 15px;">
                <a href="<?php echo LOYALTY_PROGRAM_PLUGIN_URL . 'sample-live-participants.csv'; ?>" class="button button-secondary" download>
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Download Sample CSV', 'loyalty-program'); ?>
                </a>
            </p>

            <h4><?php _e('Or without header:', 'loyalty-program'); ?></h4>
            <div class="csv-example">
                <code>
                    user1@example.com<br>
                    user2@example.com<br>
                    user3@example.com
                </code>
            </div>
        </div>

        <div class="instruction-box important">
            <h3><?php _e('Important Notes:', 'loyalty-program'); ?></h3>
            <ul>
                <li><?php _e('Each email must match a registered WordPress user', 'loyalty-program'); ?></li>
                <li><?php _e('Users must be enrolled in the loyalty program to receive points', 'loyalty-program'); ?></li>
                <li><?php _e('Invalid emails or non-existent users will be skipped', 'loyalty-program'); ?></li>
                <li><?php _e('A detailed report will be shown after processing', 'loyalty-program'); ?></li>
                <li><?php _e('All operations are logged in the debug log', 'loyalty-program'); ?></li>
            </ul>
        </div>
    </div>

    <!-- CSV Import History -->
    <?php if (!empty($csv_history)) : ?>
        <div class="loyalty-csv-history">
            <h2><?php _e('Import History', 'loyalty-program'); ?></h2>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 180px;"><?php _e('Date', 'loyalty-program'); ?></th>
                        <th><?php _e('Title', 'loyalty-program'); ?></th>
                        <th style="width: 150px;"><?php _e('CSV ID', 'loyalty-program'); ?></th>
                        <th style="width: 100px; text-align: center;"><?php _e('Success', 'loyalty-program'); ?></th>
                        <th style="width: 100px; text-align: center;"><?php _e('Failed', 'loyalty-program'); ?></th>
                        <th style="width: 100px; text-align: center;"><?php _e('Total', 'loyalty-program'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($csv_history as $csv) : ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $csv['timestamp'])); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo esc_html($csv['title']); ?></strong>
                                <div class="row-actions">
                                    <span class="filename" style="color: #646970; font-size: 13px;">
                                        <?php echo esc_html($csv['filename']); ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <code style="background: #f0f0f1; padding: 3px 6px; border-radius: 3px; font-size: 11px;"><?php echo esc_html($csv['id']); ?></code>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge-success" style="background: #00a32a; color: #fff; padding: 3px 8px; border-radius: 3px; font-weight: 600;">
                                    <?php echo number_format_i18n($csv['success']); ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($csv['failed'] > 0) : ?>
                                    <span class="badge-failed" style="background: #d63638; color: #fff; padding: 3px 8px; border-radius: 3px; font-weight: 600;">
                                        <?php echo number_format_i18n($csv['failed']); ?>
                                    </span>
                                <?php else : ?>
                                    <span style="color: #646970;">0</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <strong><?php echo number_format_i18n($csv['total']); ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="description" style="margin-top: 10px;">
                <?php _e('Showing last 50 imports. Each import has a unique ID for tracking purposes.', 'loyalty-program'); ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Import Results -->
    <?php if ($import_results) : ?>
        <div class="loyalty-import-results">
            <h2><?php _e('Import Results', 'loyalty-program'); ?></h2>

            <div class="loyalty-stats-grid">
                <div class="stat-box success">
                    <div class="stat-value"><?php echo number_format_i18n($import_results['success']); ?></div>
                    <div class="stat-label"><?php _e('Success', 'loyalty-program'); ?></div>
                </div>

                <div class="stat-box failed">
                    <div class="stat-value"><?php echo number_format_i18n($import_results['failed']); ?></div>
                    <div class="stat-label"><?php _e('Failed', 'loyalty-program'); ?></div>
                </div>

                <div class="stat-box total">
                    <div class="stat-value"><?php echo number_format_i18n($import_results['processed']); ?></div>
                    <div class="stat-label"><?php _e('Total Processed', 'loyalty-program'); ?></div>
                </div>
            </div>

            <?php if (!empty($import_results['errors'])) : ?>
                <div class="loyalty-errors-section">
                    <h3><?php _e('Errors:', 'loyalty-program'); ?></h3>
                    <div class="errors-list">
                        <?php foreach ($import_results['errors'] as $error) : ?>
                            <div class="error-item">
                                <span class="dashicons dashicons-warning"></span>
                                <?php echo esc_html($error); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .loyalty-program-live-expert {
        max-width: 1200px;
    }

    .loyalty-info-banner {
        background: #f0f6fc;
        border-left: 4px solid #2271b1;
        padding: 15px 20px;
        margin: 20px 0;
        display: flex;
        align-items: center;
        gap: 15px;
        border-radius: 4px;
    }

    .loyalty-info-banner .dashicons {
        color: #2271b1;
        font-size: 24px;
        width: 24px;
        height: 24px;
        flex-shrink: 0;
    }

    .loyalty-info-banner .button-small {
        margin-left: 10px;
    }

    .loyalty-upload-section {
        background: #fff;
        border: 1px solid #c3c4c7;
        padding: 25px;
        margin: 20px 0;
        border-radius: 4px;
    }

    .loyalty-upload-section h2 {
        margin-top: 0;
        border-bottom: 1px solid #dcdcde;
        padding-bottom: 10px;
    }

    .loyalty-csv-history {
        background: #fff;
        border: 1px solid #c3c4c7;
        padding: 25px;
        margin: 20px 0;
        border-radius: 4px;
    }

    .loyalty-csv-history h2 {
        margin-top: 0;
        border-bottom: 1px solid #dcdcde;
        padding-bottom: 10px;
    }

    .loyalty-csv-history table {
        margin-top: 15px;
    }

    .loyalty-csv-history .row-actions {
        margin-top: 5px;
    }

    .loyalty-csv-instructions {
        background: #fff;
        border: 1px solid #c3c4c7;
        padding: 25px;
        margin: 20px 0;
        border-radius: 4px;
    }

    .instruction-box {
        background: #f6f7f7;
        padding: 20px;
        margin: 15px 0;
        border-radius: 4px;
    }

    .instruction-box.important {
        background: #fff9e6;
        border-left: 4px solid #f0b849;
    }

    .csv-example {
        background: #fff;
        border: 1px solid #c3c4c7;
        padding: 15px;
        margin: 10px 0;
        border-radius: 3px;
        font-family: monospace;
    }

    .csv-example code {
        display: block;
        line-height: 1.8;
    }

    .loyalty-import-results {
        background: #fff;
        border: 1px solid #c3c4c7;
        padding: 25px;
        margin: 20px 0;
        border-radius: 4px;
    }

    .loyalty-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }

    .stat-box {
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .stat-box.success {
        background: linear-gradient(135deg, #00a32a 0%, #008a20 100%);
        color: #fff;
    }

    .stat-box.failed {
        background: linear-gradient(135deg, #d63638 0%, #b32d2e 100%);
        color: #fff;
    }

    .stat-box.total {
        background: linear-gradient(135deg, #2271b1 0%, #135e96 100%);
        color: #fff;
    }

    .stat-value {
        font-size: 48px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 16px;
        opacity: 0.9;
    }

    .loyalty-errors-section {
        background: #fcf0f1;
        border: 1px solid #d63638;
        padding: 20px;
        margin: 20px 0;
        border-radius: 4px;
    }

    .loyalty-errors-section h3 {
        color: #d63638;
        margin-top: 0;
    }

    .errors-list {
        max-height: 400px;
        overflow-y: auto;
    }

    .error-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 10px;
        background: #fff;
        border: 1px solid #f0b0b0;
        border-radius: 3px;
        margin-bottom: 8px;
    }

    .error-item .dashicons {
        color: #d63638;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .required {
        color: #d63638;
    }

    #live-csv-upload-form .button-primary .dashicons {
        margin-top: 3px;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        // Form validation
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
        
        $('#live-csv-upload-form').on('submit', function(e) {
            var title = $('#live_title').val().trim();
            var file = $('#live_csv_file').val();

            if (!title) {
                e.preventDefault();
                SwalConfig.warning('<?php esc_html_e('Please enter a live session title.', 'loyalty-program'); ?>');
                return false;
            }

            if (!file) {
                e.preventDefault();
                SwalConfig.warning('<?php esc_html_e('Please select a CSV file.', 'loyalty-program'); ?>');
                return false;
            }

            // Confirm before processing
            e.preventDefault();
            var $form = $(this);
            var $button = $form.find('input[type="submit"]');
            
            SwalConfig.confirm('<?php esc_html_e('Are you sure you want to award points to all users in the CSV file?', 'loyalty-program'); ?>').then(function(result) {
                if (!result.isConfirmed) {
                    return false;
                }
                
                // Show processing message
                $button.prop('disabled', true).val('<?php esc_attr_e('Processing...', 'loyalty-program'); ?>');
                
                // Submit form
                $form[0].submit();
            });
        });
    });
</script>

<style>
    @keyframes rotating {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .rotating {
        animation: rotating 1s linear infinite;
    }
</style>