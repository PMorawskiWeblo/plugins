<?php

/**
 * Admin Settings View
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
$is_woocommerce_active = class_exists('WooCommerce');

// Get current settings
$loyalty_enabled = get_option('loyalty_program_enabled', 'yes');
$points_per_currency = get_option('loyalty_program_points_per_currency', '1');
$points_only_products = get_option('loyalty_program_points_only_products', 'no');

// Points for actions
$points_signup = get_option('loyalty_program_points_signup', '100');
$points_review = get_option('loyalty_program_points_review', '50');
$points_coupon_use = get_option('loyalty_program_points_coupon_use', '10');
$points_birthday = get_option('loyalty_program_points_birthday', '25');
$points_profile_complete = get_option('loyalty_program_points_profile_complete', '75');
$points_notifications = get_option('loyalty_program_points_notifications', '20');
$points_attendance_master = get_option('loyalty_program_points_attendance_master', '50');
$points_flash_hunter = get_option('loyalty_program_points_flash_hunter', '20');

// WooCommerce settings
$coupon_value = get_option('loyalty_program_coupon_value', '10');
$coupon_min_amount = get_option('loyalty_program_coupon_min_amount', '150');
$disable_personal_coupons = get_option('loyalty_program_disable_personal_coupons', 'no');

settings_errors('loyalty_program_settings');
?>

<div class="wrap loyalty-program-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('loyalty_program_settings', 'loyalty_program_settings_nonce'); ?>

        <h2 class="title"><?php _e('General Settings', 'loyalty-program'); ?></h2>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <?php _e('Enable Loyalty Program', 'loyalty-program'); ?>
                    </th>
                    <td>
                        <?php if (!$is_woocommerce_active) : ?>
                        <div
                            style="background: #fcf8e3; border-left: 4px solid #f0ad4e; padding: 12px; margin-bottom: 10px;">
                            <strong style="color: #8a6d3b;">⚠️
                                <?php _e('WooCommerce Required', 'loyalty-program'); ?></strong>
                            <p style="margin: 5px 0 0 0; color: #8a6d3b;">
                                <?php _e('The WooCommerce plugin must be installed and activated for the loyalty program to work correctly.', 'loyalty-program'); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="loyalty_program_enabled" value="yes"
                                    <?php checked($loyalty_enabled, 'yes'); ?>
                                    <?php disabled(!$is_woocommerce_active); ?>>
                                <?php _e('Enable the loyalty program for your store', 'loyalty-program'); ?>
                            </label>
                            <?php if (!$is_woocommerce_active) : ?>
                            <p class="description" style="color: #d63638;">
                                <?php _e('This option is disabled because WooCommerce is not active.', 'loyalty-program'); ?>
                            </p>
                            <?php endif; ?>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Auto-enroll on Registration', 'loyalty-program'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="loyalty_program_auto_enroll" value="yes"
                                    <?php checked(get_option('loyalty_program_auto_enroll', 'no'), 'yes'); ?>>
                                <?php _e('Automatically add new users to the loyalty program when they create an account', 'loyalty-program'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, all new users will be automatically enrolled in the loyalty program. If disabled, users need to manually join the program.', 'loyalty-program'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="points_per_currency">
                            <?php _e('Points per Currency Unit', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="points_per_currency" id="points_per_currency"
                            value="<?php echo esc_attr($points_per_currency); ?>" class="small-text" min="0"
                            step="0.01">
                        <p class="description">
                            <?php _e('How many loyalty points customers earn per currency unit spent (e.g., 1 point per 1 PLN).', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="points_only_products">
                            <?php _e('Points Only for Products', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="points_only_products" id="points_only_products" value="yes"
                                <?php checked($points_only_products, 'yes'); ?>>
                            <?php _e('Award points only for product value (excluding shipping costs)', 'loyalty-program'); ?>
                        </label>
                        <p class="description">
                            <?php _e('When enabled, points will be calculated based only on the value of products in the cart (after coupon discounts), excluding shipping costs. For example: products worth 100 PLN + shipping 20 PLN + coupon -50 PLN = points will be awarded only for 50 PLN (products value after discount).', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="loyalty_program_points_award_status">
                            <?php _e('Award Points When Status', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <?php
                        $points_award_status = get_option('loyalty_program_points_award_status', 'completed');
                        ?>
                        <select name="loyalty_program_points_award_status" id="loyalty_program_points_award_status">
                            <option value="processing" <?php selected($points_award_status, 'processing'); ?>>
                                <?php _e('Processing (In Progress)', 'loyalty-program'); ?>
                            </option>
                            <option value="completed" <?php selected($points_award_status, 'completed'); ?>>
                                <?php _e('Completed', 'loyalty-program'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php _e('Choose when to award points for orders and coupon usage. Points will be awarded when the order reaches the selected status.', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2 class="title" style="margin-top: 30px; border-top: 1px solid #dcdcde; padding-top: 20px;">
            <?php _e('Points for Customer Actions', 'loyalty-program'); ?>
        </h2>
        <p class="description">
            <?php _e('Configure how many points customers receive for specific actions in your loyalty program.', 'loyalty-program'); ?>
        </p>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="points_signup">
                            <?php _e('Joining the Loyalty Program', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="points_signup" id="points_signup"
                            value="<?php echo esc_attr($points_signup); ?>" class="small-text" min="0" step="1">
                        <span class="description"><?php _e('points', 'loyalty-program'); ?></span>
                        <p class="description">
                            <?php _e('Points awarded when a customer joins the loyalty program.', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="points_review">
                            <?php _e('Writing a Product Review', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="points_review" id="points_review"
                            value="<?php echo esc_attr($points_review); ?>" class="small-text" min="0" step="1">
                        <span class="description"><?php _e('points', 'loyalty-program'); ?></span>
                        <p class="description">
                            <?php _e('Points awarded for each approved product review.', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="points_coupon_use">
                            <?php _e('Using a Coupon Code', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="points_coupon_use" id="points_coupon_use"
                            value="<?php echo esc_attr($points_coupon_use); ?>" class="small-text" min="0" step="1">
                        <span class="description"><?php _e('points', 'loyalty-program'); ?></span>
                        <p class="description">
                            <?php _e('Points awarded when a customer uses a coupon code during checkout.', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="points_birthday">
                            <?php _e('Adding Birthday Date', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="points_birthday" id="points_birthday"
                            value="<?php echo esc_attr($points_birthday); ?>" class="small-text" min="0" step="1">
                        <span class="description"><?php _e('points', 'loyalty-program'); ?></span>
                        <p class="description">
                            <?php _e('Points awarded when a customer adds their birthday to their profile.', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="points_profile_complete">
                            <?php _e('Completing Profile', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="points_profile_complete" id="points_profile_complete"
                            value="<?php echo esc_attr($points_profile_complete); ?>" class="small-text" min="0"
                            step="1">
                        <span class="description"><?php _e('points', 'loyalty-program'); ?></span>
                        <p class="description">
                            <?php _e('Points awarded when a customer completes their profile information.', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="points_notifications">
                            <?php _e('Sign up for notifications', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="points_notifications" id="points_notifications"
                            value="<?php echo esc_attr($points_notifications); ?>" class="small-text" min="0" step="1">
                        <span class="description"><?php _e('points', 'loyalty-program'); ?></span>
                        <p class="description">
                            <?php _e('Points awarded for consenting to notifications (default: 20 points).', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="points_return_purchase">
                            <?php _e('Return for More', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="points_return_purchase" id="points_return_purchase"
                            value="<?php echo esc_attr(get_option('loyalty_program_points_return_purchase', '50')); ?>"
                            class="small-text" min="0" step="1">
                        <span class="description"><?php _e('points', 'loyalty-program'); ?></span>
                        <p class="description">
                            <?php _e('Points awarded when a customer purchases the same product again within 30 days (default: 50 points).', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="points_live_expert">
                            <?php _e('Live with Expert', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="points_live_expert" id="points_live_expert"
                            value="<?php echo esc_attr(get_option('loyalty_program_points_live_expert', '30')); ?>"
                            class="small-text" min="0" step="1">
                        <span class="description"><?php _e('points', 'loyalty-program'); ?></span>
                        <p class="description">
                            <?php _e('Points awarded to users who watched a live session with an expert (default: 30 points).', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="points_attendance_master">
                            <?php _e('Attendance Master', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="points_attendance_master" id="points_attendance_master"
                            value="<?php echo esc_attr($points_attendance_master); ?>" class="small-text" min="0"
                            step="1">
                        <span class="description"><?php _e('points', 'loyalty-program'); ?></span>
                        <p class="description">
                            <?php _e('Points awarded for completing attendance-based challenges (default: 50 points).', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="points_flash_hunter">
                            <?php _e('Flash Hunter', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="points_flash_hunter" id="points_flash_hunter"
                            value="<?php echo esc_attr($points_flash_hunter); ?>" class="small-text" min="0" step="1">
                        <span class="description"><?php _e('points', 'loyalty-program'); ?></span>
                        <p class="description">
                            <?php _e('Points awarded for using a Flash Hunter coupon within its validity period (default: 20 points).', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="points_supplementation_discipline">
                            <?php _e('Supplementation Discipline', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="points_supplementation_discipline"
                            id="points_supplementation_discipline"
                            value="<?php echo esc_attr(get_option('loyalty_program_points_supplementation_discipline', '50')); ?>"
                            class="small-text" min="0" step="1">
                        <span class="description"><?php _e('points', 'loyalty-program'); ?></span>
                        <p class="description">
                            <?php _e('Points awarded for purchasing the same product 3 times within 3 months (default: 50 points).', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="discipline_not_purchased_text">
                            <?php _e('Text for not purchased products', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" name="discipline_not_purchased_text" id="discipline_not_purchased_text"
                            value="<?php echo esc_attr(get_option('loyalty_program_discipline_not_purchased_text', 'Kup i zrealizuj misję MyBestLife Club')); ?>"
                            class="regular-text">
                        <p class="description">
                            <?php _e('Text displayed when product/variant is in the discipline program but user hasn\'t purchased it yet.', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>

            </tbody>
        </table>

        <?php if (class_exists('WooCommerce')) : ?>
        <h2 class="title" style="margin-top: 30px; border-top: 1px solid #dcdcde; padding-top: 20px;">
            <?php _e('WooCommerce Integration', 'loyalty-program'); ?>
        </h2>
        <p class="description">
            <?php _e('Configure WooCommerce-specific loyalty program features.', 'loyalty-program'); ?>
        </p>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="coupon_value">
                            <?php _e('Personal Coupon Value', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="coupon_value" id="coupon_value"
                            value="<?php echo esc_attr($coupon_value); ?>" class="small-text" min="0" step="0.01">
                        <span class="description"><?php echo get_woocommerce_currency_symbol(); ?></span>
                        <p class="description">
                            <?php _e('Value of the personal discount coupon generated for each user when they join the loyalty program.', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="coupon_min_amount">
                            <?php _e('Coupon Minimum Order Amount', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="coupon_min_amount" id="coupon_min_amount"
                            value="<?php echo esc_attr($coupon_min_amount); ?>" class="small-text" min="0" step="0.01">
                        <span class="description"><?php echo get_woocommerce_currency_symbol(); ?></span>
                        <p class="description">
                            <?php _e('Minimum order amount required to use the personal coupon (default: 150 PLN).', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Disable Personal Coupons', 'loyalty-program'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="disable_personal_coupons" value="yes"
                                    <?php checked($disable_personal_coupons, 'yes'); ?>>
                                <?php _e('Disable personal coupons for all users', 'loyalty-program'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, users will not be able to use their personal loyalty coupons in checkout.', 'loyalty-program'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Update Existing Coupons', 'loyalty-program'); ?>
                    </th>
                    <td>
                        <button type="button" id="update-existing-coupons" class="button button-secondary">
                            <?php _e('Update All Personal Coupons', 'loyalty-program'); ?>
                        </button>
                        <p class="description">
                            <?php _e('Update all existing personal coupons with new settings (value and minimum amount). Use this after changing coupon settings.', 'loyalty-program'); ?>
                        </p>
                        <div id="update-coupons-status"></div>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Show Loyalty Info in Cart', 'loyalty-program'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="loyalty_program_show_cart_info" value="yes"
                                    <?php checked(get_option('loyalty_program_show_cart_info', 'yes'), 'yes'); ?>>
                                <?php _e('Display "Loyalty Reward" label for loyalty program items in cart', 'loyalty-program'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, items from loyalty program rewards will show a "Gift from Loyalty Program" label in the cart.', 'loyalty-program'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php endif; ?>

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
                }
            };
            
            $('#update-existing-coupons').on('click', function() {
            console.log('update-existing-coupons');
                var $button = $(this);
                var $status = $('#update-coupons-status');

                SwalConfig.confirm('<?php esc_attr_e('Are you sure you want to update all existing personal coupons with current settings?', 'loyalty-program'); ?>').then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                $button.prop('disabled', true).text(
                    '<?php esc_attr_e('Updating...', 'loyalty-program'); ?>');

                // Show progress bar
                $status.html(
                    '<div style="margin: 10px 0;">' +
                    '<p class="description" id="coupon-progress-text"><?php esc_html_e('Initializing...', 'loyalty-program'); ?></p>' +
                    '<div style="background: #ddd; border-radius: 4px; height: 20px; overflow: hidden; margin-top: 5px;">' +
                    '<div id="coupon-progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s ease;"></div>' +
                    '</div>' +
                    '<p class="description" id="coupon-progress-stats" style="margin-top: 5px;"></p>' +
                    '</div>'
                );

                // Start batch processing
                var totalProcessed = 0;
                var batchSize = 100;

                function processBatch(offset) {
                    $.post(ajaxurl, {
                        action: 'loyalty_program_update_coupons',
                        nonce: '<?php echo wp_create_nonce('loyalty_program_update_coupons'); ?>',
                        offset: offset,
                        batch_size: batchSize
                    }, function(response) {
                        if (response.success) {
                            var data = response.data;
                            var percentage = Math.round((data.processed / data.total) * 100);

                            // Update progress bar
                            $('#coupon-progress-bar').css('width', percentage + '%');
                            $('#coupon-progress-text').text(
                                '<?php esc_html_e('Processing coupons...', 'loyalty-program'); ?> ' +
                                percentage + '%'
                            );
                            $('#coupon-progress-stats').text(
                                data.processed + ' / ' + data.total +
                                ' <?php esc_html_e('coupons processed', 'loyalty-program'); ?>'
                            );

                            // If there are more coupons, continue
                            if (data.has_more) {
                                processBatch(data.processed);
                            } else {
                                // All done!
                                $status.html(
                                    '<div class="notice notice-success inline"><p>' +
                                    '<?php esc_html_e('All personal coupons updated successfully!', 'loyalty-program'); ?> ' +
                                    '(' + data.total +
                                    ' <?php esc_html_e('coupons', 'loyalty-program'); ?>)' +
                                    '</p></div>'
                                );
                                $button.prop('disabled', false).text(
                                    '<?php esc_attr_e('Update All Personal Coupons', 'loyalty-program'); ?>'
                                );
                            }
                        } else {
                            // Error
                            $status.html(
                                '<div class="notice notice-error inline"><p>' +
                                (response.data.message ||
                                    '<?php esc_html_e('Error updating coupons.', 'loyalty-program'); ?>'
                                    ) +
                                '</p></div>'
                            );
                            $button.prop('disabled', false).text(
                                '<?php esc_attr_e('Update All Personal Coupons', 'loyalty-program'); ?>'
                            );
                        }
                    }).fail(function() {
                        // AJAX error
                        $status.html(
                            '<div class="notice notice-error inline"><p>' +
                            '<?php esc_html_e('Network error. Please try again.', 'loyalty-program'); ?>' +
                            '</p></div>'
                        );
                        $button.prop('disabled', false).text(
                            '<?php esc_attr_e('Update All Personal Coupons', 'loyalty-program'); ?>'
                        );
                    });
                }

                // Start from offset 0
                processBatch(0);
                });
            });
        });
        </script>

        <h2 class="title" style="margin-top: 30px; border-top: 1px solid #dcdcde; padding-top: 20px;">
            <?php _e('Wheel of Fortune Configuration', 'loyalty-program'); ?>
        </h2>
        <p class="description">
            <?php _e('Configure prize options for the wheel of fortune. Users will be able to spin the wheel to win points.', 'loyalty-program'); ?>
        </p>

        <?php
        $wheel_prizes = get_option('loyalty_program_wheel_prizes', array());
        $wheel_days_between_spins = get_option('loyalty_program_wheel_days_between_spins', 7);
        ?>

        <table class="form-table" style="margin-bottom: 20px;">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="wheel_days_between_spins">
                            <?php _e('Days Between Spins', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="wheel_days_between_spins" id="wheel_days_between_spins"
                            value="<?php echo esc_attr($wheel_days_between_spins); ?>" class="small-text" min="1"
                            step="1" required>
                        <span class="description"><?php _e('days', 'loyalty-program'); ?></span>
                        <p class="description">
                            <?php _e('Number of days a user must wait between wheel spins (default: 7 days).', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="loyalty-wheel-prizes-section">
            <div class="loyalty-rewards-header" style="margin-bottom: 15px;">
                <h3 style="margin: 0;"><?php _e('Wheel Prizes', 'loyalty-program'); ?></h3>
                <button type="button" id="add-wheel-prize-row" class="button button-secondary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Add Prize', 'loyalty-program'); ?>
                </button>
            </div>

            <div class="loyalty-rewards-table-wrapper">
                <table class="wp-list-table widefat fixed striped loyalty-wheel-prizes-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><?php _e('#', 'loyalty-program'); ?></th>
                            <th style="width: 40px;"></th>
                            <th><?php _e('Prize Name', 'loyalty-program'); ?></th>
                            <th style="width: 120px;"><?php _e('Points', 'loyalty-program'); ?></th>
                            <th style="width: 120px;"><?php _e('Probability (%)', 'loyalty-program'); ?></th>
                            <th style="width: 100px;"><?php _e('Color', 'loyalty-program'); ?></th>
                            <th style="width: 150px;"><?php _e('Actions', 'loyalty-program'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="wheel-prizes-tbody">
                        <?php if (!empty($wheel_prizes)) : ?>
                        <?php foreach ($wheel_prizes as $index => $prize) : ?>
                        <?php echo render_wheel_prize_row($index, $prize); ?>
                        <?php endforeach; ?>
                        <?php else : ?>
                        <tr class="no-prizes-row">
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <span class="dashicons dashicons-games" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php _e('No wheel prizes configured yet. Click "Add Prize" to create your first prize.', 'loyalty-program'); ?>
                                </p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <h2 class="title" style="margin-top: 30px; border-top: 1px solid #dcdcde; padding-top: 20px;">
            <?php _e('User Account Fields', 'loyalty-program'); ?>
        </h2>
        <p class="description">
            <?php _e('Configure which fields should be available via shortcode [loyalty_account_fields]. Use the shortcode to display these fields on any page.', 'loyalty-program'); ?>
        </p>

        <?php
        $enable_birth_date = get_option('loyalty_program_enable_birth_date', 'no');
        $enable_sms_consent = get_option('loyalty_program_enable_sms_consent', 'no');
        $enable_newsletter_consent = get_option('loyalty_program_enable_newsletter_consent', 'no');
        $enable_billing_phone = get_option('loyalty_program_enable_billing_phone', 'yes');
        $enable_user_coupon = get_option('loyalty_program_enable_user_coupon', 'no');
        $account_custom_page = get_option('loyalty_program_account_custom_page', '');
        ?>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <?php _e('Birth Date Field', 'loyalty-program'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="loyalty_program_enable_birth_date" value="yes"
                                    <?php checked($enable_birth_date, 'yes'); ?>>
                                <?php _e('Display birth date field in user account', 'loyalty-program'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Adds a date picker field for users to enter their birth date.', 'loyalty-program'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('SMS Consent Field', 'loyalty-program'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="loyalty_program_enable_sms_consent" value="yes"
                                    <?php checked($enable_sms_consent, 'yes'); ?>>
                                <?php _e('Display SMS consent checkbox in user account', 'loyalty-program'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Adds a checkbox for users to consent to receiving SMS notifications.', 'loyalty-program'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Newsletter Consent Field', 'loyalty-program'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="loyalty_program_enable_newsletter_consent" value="yes"
                                    <?php checked($enable_newsletter_consent, 'yes'); ?>>
                                <?php _e('Display newsletter consent checkbox in user account', 'loyalty-program'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Adds a checkbox for users to consent to receiving newsletter emails.', 'loyalty-program'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Phone Number Field', 'loyalty-program'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="loyalty_program_enable_billing_phone" value="yes"
                                    <?php checked($enable_billing_phone, 'yes'); ?>>
                                <?php _e('Display phone number field in user account', 'loyalty-program'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Adds a phone number field (billing_phone) for users. This field is required for profile completion.', 'loyalty-program'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e('User Coupon', 'loyalty-program'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="loyalty_program_enable_user_coupon" value="yes"
                                    <?php checked($enable_user_coupon, 'yes'); ?>>
                                <?php _e('Display user coupon in account fields', 'loyalty-program'); ?>
                            </label>
                            <p class="description">
                                <?php _e('If enabled, the [loyalty_user_coupon] shortcode will be included in [loyalty_account_fields].', 'loyalty-program'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="loyalty_program_account_custom_page">
                            <?php _e('Custom Account Page', 'loyalty-program'); ?>
                        </label>
                    </th>
                    <td>
                        <?php
                        wp_dropdown_pages(array(
                            'name' => 'loyalty_program_account_custom_page',
                            'id' => 'loyalty_program_account_custom_page',
                            'selected' => $account_custom_page,
                            'show_option_none' => __('-- Select Page --', 'loyalty-program'),
                            'option_none_value' => '',
                        ));
                        ?>
                        <p class="description">
                            <?php _e('If a page is selected, it will be automatically added to the "My Account" menu as the second-to-last item (before "Logout").', 'loyalty-program'); ?>
                        </p>
                    </td>
                </tr>

            </tbody>
        </table>

        <p class="submit">
            <button type="submit" name="loyalty_program_settings_save" class="button button-primary">
                <?php _e('Save Settings', 'loyalty-program'); ?>
            </button>
        </p>
    </form>
</div>

<!-- Wheel Prize Row Template -->
<script type="text/template" id="wheel-prize-row-template">
    <?php echo render_wheel_prize_row('{{INDEX}}', array(
        'name' => '',
        'points' => 10,
        'probability' => 10,
        'color' => '#3b82f6',
        'enabled' => 'yes',
    )); ?>
</script>

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
        }
    };
    
    var prizeIndex = <?php echo count($wheel_prizes); ?>;

    // Make table sortable (drag & drop)
    $('#wheel-prizes-tbody').sortable({
        handle: '.drag-handle',
        placeholder: 'ui-state-highlight',
        update: function(event, ui) {
            updatePrizeRowNumbers();
        }
    });

    // Add new prize row
    $('#add-wheel-prize-row').on('click', function() {
        $('.no-prizes-row').remove();

        var template = $('#wheel-prize-row-template').html();
        var newRow = template.replace(/\{\{INDEX\}\}/g, prizeIndex);

        $('#wheel-prizes-tbody').append(newRow);
        prizeIndex++;
        updatePrizeRowNumbers();
    });

    // Delete prize row
    $(document).on('click', '.delete-wheel-prize-btn', function() {
        var $btn = $(this);
        SwalConfig.confirm('<?php esc_attr_e('Are you sure you want to delete this prize?', 'loyalty-program'); ?>').then(function(result) {
            if (result.isConfirmed) {
                $btn.closest('tr').remove();
                updatePrizeRowNumbers();

                if ($('#wheel-prizes-tbody tr').length === 0) {
                    $('#wheel-prizes-tbody').html(
                        '<tr class="no-prizes-row"><td colspan="7" style="text-align: center; padding: 40px;"><span class="dashicons dashicons-games" style="font-size: 48px; opacity: 0.3;"></span><p><?php _e('No wheel prizes configured yet. Click "Add Prize" to create your first prize.', 'loyalty-program'); ?></p></td></tr>'
                    );
                }
            }
        });
    });

    // Toggle enabled/disabled styling
    $(document).on('change', '.wheel-prize-enabled-toggle', function() {
        var $row = $(this).closest('tr');
        if ($(this).is(':checked')) {
            $row.removeClass('disabled');
        } else {
            $row.addClass('disabled');
        }
    });

    // Update color preview when color changes
    $(document).on('input change', '.wheel-prize-color', function() {
        var color = $(this).val();
        $(this).siblings('.wheel-prize-color-preview').css('background-color', color);
    });

    // Update row numbers
    function updatePrizeRowNumbers() {
        $('#wheel-prizes-tbody tr:not(.no-prizes-row)').each(function(index) {
            $(this).find('.wheel-prize-number').text(index + 1);
        });
    }

    // Calculate total probability
    function calculateTotalProbability() {
        var total = 0;
        $('#wheel-prizes-tbody tr:not(.no-prizes-row)').each(function() {
            var prob = parseFloat($(this).find('.wheel-prize-probability').val()) || 0;
            total += prob;
        });
        return total;
    }

    // Update probability on change
    $(document).on('input', '.wheel-prize-probability', function() {
        var total = calculateTotalProbability();
        var $info = $('#probability-info');

        if ($info.length === 0) {
            $('.loyalty-wheel-prizes-section').append(
                '<div id="probability-info" style="margin-top: 10px; padding: 10px; border-radius: 4px;"></div>'
            );
            $info = $('#probability-info');
        }

        if (Math.abs(total - 100) < 0.1) {
            $info.html(
                '<span style="color: #00a32a;">✓ <?php esc_html_e('Total probability:', 'loyalty-program'); ?> ' +
                total.toFixed(1) + '%</span>').css('background', '#d5f4e6');
        } else if (total > 100) {
            $info.html(
                '<span style="color: #d63638;">⚠ <?php esc_html_e('Total probability:', 'loyalty-program'); ?> ' +
                total.toFixed(1) +
                '% (<?php esc_html_e('exceeds 100%', 'loyalty-program'); ?>)</span>').css(
                'background', '#fcf0f1');
        } else {
            $info.html(
                '<span style="color: #d63638;">⚠ <?php esc_html_e('Total probability:', 'loyalty-program'); ?> ' +
                total.toFixed(1) +
                '% (<?php esc_html_e('should be 100%', 'loyalty-program'); ?>)</span>').css(
                'background', '#fcf0f1');
        }
    });

    // Initialize row numbers and probability on load
    updatePrizeRowNumbers();
    if ($('#wheel-prizes-tbody tr:not(.no-prizes-row)').length > 0) {
        $('.wheel-prize-probability').first().trigger('input');
    }
});
</script>

<style>
.loyalty-wheel-prizes-section {
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.loyalty-wheel-prizes-table tbody tr {
    cursor: move;
}

.loyalty-wheel-prizes-table tbody tr.ui-sortable-helper {
    background: #f0f6fc;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.loyalty-wheel-prizes-table tbody tr.disabled {
    opacity: 0.5;
    background: #f6f7f7;
}

.loyalty-wheel-prizes-table .drag-handle {
    cursor: move;
    color: #646970;
    text-align: center;
}

.loyalty-wheel-prizes-table .drag-handle:hover {
    color: #2271b1;
}

.loyalty-wheel-prizes-table input[type="text"],
.loyalty-wheel-prizes-table input[type="number"] {
    width: 100%;
}

.wheel-prize-color-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
}

.loyalty-wheel-prizes-table input[type="color"] {
    width: 50px;
    height: 35px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    cursor: pointer;
    padding: 2px;
}

.loyalty-wheel-prizes-table input[type="color"]:hover {
    border-color: #2271b1;
}

.wheel-prize-color-preview {
    display: inline-block;
    width: 30px;
    height: 30px;
    border-radius: 4px;
    border: 1px solid #8c8f94;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
}

.loyalty-wheel-prizes-table .wheel-prize-number {
    text-align: center;
    font-weight: 600;
    color: #646970;
}
</style>

<?php
/**
 * Render a single wheel prize row
 * 
 * @param int|string $index Row index
 * @param array $prize Prize data
 * @return string
 */
function render_wheel_prize_row($index, $prize)
{
    $enabled = isset($prize['enabled']) ? $prize['enabled'] : 'yes';
    $disabled_class = $enabled === 'no' ? 'disabled' : '';

    ob_start();
?>
<tr class="wheel-prize-row <?php echo esc_attr($disabled_class); ?>">
    <td class="wheel-prize-number" style="text-align: center;"></td>
    <td class="drag-handle">
        <span class="dashicons dashicons-move"></span>
    </td>
    <td>
        <input type="text" name="wheel_prizes[<?php echo esc_attr($index); ?>][name]" class="wheel-prize-name"
            value="<?php echo esc_attr($prize['name'] ?? ''); ?>"
            placeholder="<?php esc_attr_e('e.g., 50 Points', 'loyalty-program'); ?>" required>
    </td>
    <td>
        <input type="number" name="wheel_prizes[<?php echo esc_attr($index); ?>][points]" class="wheel-prize-points"
            value="<?php echo esc_attr($prize['points'] ?? 10); ?>" min="0" step="1" required>
    </td>
    <td>
        <input type="number" name="wheel_prizes[<?php echo esc_attr($index); ?>][probability]"
            class="wheel-prize-probability" value="<?php echo esc_attr($prize['probability'] ?? 10); ?>" min="0"
            max="100" step="0.1" required>
        <span class="description">%</span>
    </td>
    <td>
        <div class="wheel-prize-color-wrapper">
            <input type="color" name="wheel_prizes[<?php echo esc_attr($index); ?>][color]" class="wheel-prize-color"
                value="<?php echo esc_attr($prize['color'] ?? '#3b82f6'); ?>"
                title="<?php esc_attr_e('Choose a color for your reward', 'loyalty-program'); ?>">
            <span class="wheel-prize-color-preview"
                style="background-color: <?php echo esc_attr($prize['color'] ?? '#3b82f6'); ?>"></span>
        </div>
    </td>
    <td>
        <label class="toggle-switch">
            <input type="checkbox" name="wheel_prizes[<?php echo esc_attr($index); ?>][enabled]"
                class="wheel-prize-enabled-toggle" value="yes" <?php checked($enabled, 'yes'); ?>>
            <span class="toggle-slider"></span>
        </label>
        <button type="button" class="button delete-wheel-prize-btn"
            title="<?php esc_attr_e('Delete', 'loyalty-program'); ?>">
            <span class="dashicons dashicons-trash"></span>
        </button>
    </td>
</tr>
<?php
    return ob_get_clean();
}
?>