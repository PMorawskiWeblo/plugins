<?php

/**
 * Admin Users View (Optimized for large databases)
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Export is now handled in admin_init hook in class-loyalty-program-admin-menu.php
// to prevent HTML output before CSV headers

// Load users table
require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/admin/class-loyalty-program-users-table.php';
$users_table = new Loyalty_Program_Users_Table();
$users_table->prepare_items();

// OPTIMIZED: Statistics removed - they are available on Dashboard with caching
// This page now only loads the users table (pagination query)

?>

<div class="wrap loyalty-program-users-optimized">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>

    <hr class="wp-header-end">

    <p class="description">
        <?php _e('Search and manage loyalty program members. For overall statistics, visit the Dashboard.', 'loyalty-program'); ?>
    </p>

    <!-- Users Table -->
    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
        <?php
        $users_table->search_box(__('Search users', 'loyalty-program'), 'user');
        $users_table->display();
        ?>
    </form>

    <!-- User Details Modal -->
    <div id="user-details-modal" style="display: none;">
        <div class="user-details-modal-content">
            <span class="close-user-details">&times;</span>
            <div id="user-details-content">
                <div style="text-align: center; padding: 40px;">
                    <span class="spinner is-active" style="float: none;"></span>
                    <p><?php _e('Loading...', 'loyalty-program'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Manage Points Modal -->
    <div id="manage-points-modal" style="display: none;">
        <div class="manage-points-modal-content">
            <span class="close-manage-points">&times;</span>
            <h2 id="manage-points-title"><?php _e('Manage Points', 'loyalty-program'); ?></h2>

            <div class="manage-points-body">
                <form id="manage-points-form">
                    <input type="hidden" id="manage-user-id" name="user_id" value="">

                    <div class="form-field">
                        <label for="points-amount"><?php _e('Points Amount', 'loyalty-program'); ?> *</label>
                        <input type="number" id="points-amount" name="points" min="1" required>
                    </div>

                    <div class="form-field">
                        <label for="points-action-desc"><?php _e('Description', 'loyalty-program'); ?> *</label>
                        <input type="text" id="points-action-desc" name="action_desc"
                            placeholder="<?php esc_attr_e('e.g., Manual adjustment', 'loyalty-program'); ?>" required>
                    </div>

                    <div class="form-field">
                        <label><?php _e('Action Type', 'loyalty-program'); ?></label>
                        <div style="display: flex; gap: 20px; margin-top: 10px;">
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="radio" name="type" value="add" checked>
                                <span style="color: #00a32a; font-weight: 600;">
                                    <span class="dashicons dashicons-plus-alt"></span>
                                    <?php _e('Add Points', 'loyalty-program'); ?>
                                </span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="radio" name="type" value="remove">
                                <span style="color: #d63638; font-weight: 600;">
                                    <span class="dashicons dashicons-minus"></span>
                                    <?php _e('Subtract Points', 'loyalty-program'); ?>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-yes"></span>
                            <?php _e('Save Changes', 'loyalty-program'); ?>
                        </button>
                    </div>
                </form>
                <div id="manage-points-result"></div>
            </div>
        </div>
    </div>
</div>

<style>
    .loyalty-program-users-optimized {
        max-width: 1400px;
    }

    /* Statistics removed - available on Dashboard with caching */

    .loyalty-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .loyalty-badge.member {
        background: #d5f4e6;
        color: #00a32a;
    }

    .loyalty-badge.non-member {
        background: #f0f0f1;
        color: #646970;
    }

    #user-details-modal {
        position: fixed;
        z-index: 100000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .user-details-modal-content {
        background-color: #fff;
        padding: 30px;
        border-radius: 8px;
        max-width: 900px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    }

    .close-user-details {
        position: absolute;
        right: 20px;
        top: 20px;
        font-size: 30px;
        font-weight: 700;
        line-height: 1;
        color: #646970;
        cursor: pointer;
        transition: color 0.2s;
    }

    .close-user-details:hover {
        color: #d63638;
    }

    /* Manage Points Modal */
    #manage-points-modal {
        position: fixed;
        z-index: 100000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .manage-points-modal-content {
        background-color: #fff;
        padding: 30px;
        border-radius: 8px;
        max-width: 500px;
        width: 100%;
        position: relative;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    }

    .close-manage-points {
        position: absolute;
        right: 20px;
        top: 20px;
        font-size: 30px;
        font-weight: 700;
        line-height: 1;
        color: #646970;
        cursor: pointer;
        transition: color 0.2s;
    }

    .close-manage-points:hover {
        color: #d63638;
    }

    #manage-points-title {
        margin: 0 0 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f1;
    }

    .manage-points-body .form-field {
        margin-bottom: 20px;
    }

    .manage-points-body label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .manage-points-body input[type="number"],
    .manage-points-body input[type="text"] {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #dcdcde;
        border-radius: 4px;
    }

    .form-actions {
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #f0f0f1;
        text-align: right;
    }

    #manage-points-result {
        margin-top: 15px;
    }

    @media screen and (max-width: 768px) {
        .loyalty-users-stats {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    jQuery(document).ready(function($) {
        // View user details
        $('.view-user-details').on('click', function() {
            const userId = $(this).data('user-id');

            $('#user-details-modal').fadeIn(300);
            $('#user-details-content').html(
                '<div style="text-align: center; padding: 40px;"><span class="spinner is-active" style="float: none;"></span><p><?php _e('Loading...', 'loyalty-program'); ?></p></div>'
            );

            // Load user details via AJAX (same as before)
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'loyalty_program_get_user_details',
                    user_id: userId,
                    nonce: '<?php echo wp_create_nonce('loyalty_program_get_user_details'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#user-details-content').html(response.data.html);
                    } else {
                        $('#user-details-content').html('<p style="color: #d63638;">' + response
                            .data.message + '</p>');
                    }
                },
                error: function() {
                    $('#user-details-content').html(
                        '<p style="color: #d63638;"><?php _e('Error loading user details.', 'loyalty-program'); ?></p>'
                    );
                }
            });
        });

        // Close modal
        $('.close-user-details, #user-details-modal').on('click', function(e) {
            if (e.target === this) {
                $('#user-details-modal').fadeOut(300);
            }
        });

        // Manage Points
        $('.manage-points-btn').on('click', function() {
            const userId = $(this).data('user-id');
            const userName = $(this).data('user-name');

            $('#manage-points-title').text('<?php _e('Manage Points', 'loyalty-program'); ?> - ' +
                userName);
            $('#manage-user-id').val(userId);
            $('#manage-points-form')[0].reset();
            $('#manage-points-result').html('');
            $('#manage-points-modal').fadeIn(300);
        });

        // Close manage points modal
        $('.close-manage-points, #manage-points-modal').on('click', function(e) {
            if (e.target === this) {
                $('#manage-points-modal').fadeOut(300);
            }
        });

        // Submit manage points form
        $('#manage-points-form').on('submit', function(e) {
            e.preventDefault();

            const $form = $(this);
            const $result = $('#manage-points-result');
            const $button = $form.find('button[type="submit"]');

            const formData = {
                action: 'loyalty_program_modify_points',
                user_id: $('#manage-user-id').val(),
                points: $('#points-amount').val(),
                action_desc: $('#points-action-desc').val(),
                type: $('input[name="type"]:checked').val(),
                nonce: '<?php echo wp_create_nonce('loyalty_program_modify_points'); ?>'
            };

            $button.prop('disabled', true).text('<?php _e('Saving...', 'loyalty-program'); ?>');
            $result.html('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $result.html('<div class="notice notice-success inline"><p>' + response
                            .data.message + '</p></div>');

                        // Reset form
                        $form[0].reset();

                        // Reload page after 1 second
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        $result.html('<div class="notice notice-error inline"><p>' + response
                            .data.message + '</p></div>');
                        $button.prop('disabled', false).html(
                            '<span class="dashicons dashicons-yes"></span> <?php _e('Save Changes', 'loyalty-program'); ?>'
                        );
                    }
                },
                error: function() {
                    $result.html(
                        '<div class="notice notice-error inline"><p><?php _e('Error occurred. Please try again.', 'loyalty-program'); ?></p></div>'
                    );
                    $button.prop('disabled', false).html(
                        '<span class="dashicons dashicons-yes"></span> <?php _e('Save Changes', 'loyalty-program'); ?>'
                    );
                }
            });
        });
    });
</script>