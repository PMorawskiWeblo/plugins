<?php

/**
 * Admin Shortcodes View (New Compact Design)
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define shortcodes data
$shortcodes = array(
    array(
        'id' => 'join_button',
        'icon' => 'admin-users',
        'name' => __('Join Loyalty Program Button', 'loyalty-program'),
        'code' => '[loyalty_join_button]',
        'description' => __('Button for users to join the loyalty program.', 'loyalty-program'),
        'parameters' => array(
            array('name' => 'text', 'desc' => __('Custom button text', 'loyalty-program'), 'default' => 'Join Loyalty Program'),
            array('name' => 'class', 'desc' => __('Additional CSS class', 'loyalty-program'), 'default' => ''),
            array('name' => 'show_message', 'desc' => __('Show message when already enrolled', 'loyalty-program'), 'default' => 'yes'),
        ),
        'examples' => array(
            '[loyalty_join_button]',
            '[loyalty_join_button text="Dołącz teraz!"]',
            '[loyalty_join_button show_message="no"]',
        ),
        'requirements' => __('User must be logged in.', 'loyalty-program'),
    ),
    array(
        'id' => 'join_button_reload',
        'icon' => 'admin-users',
        'name' => __('Join Button with Reload', 'loyalty-program'),
        'code' => '[loyalty_join_button_reload]',
        'description' => __('Button to join the loyalty program with "Join" text only. Page reloads immediately after joining. If user is not logged in, redirects to registration page.', 'loyalty-program'),
        'parameters' => array(
            array('name' => 'class', 'desc' => __('Additional CSS class', 'loyalty-program'), 'default' => ''),
        ),
        'examples' => array(
            '[loyalty_join_button_reload]',
            '[loyalty_join_button_reload class="my-custom-class"]',
        ),
        'requirements' => __('Button is always shown. For logged out users: redirects to registration. For logged in non-members: joins program. Hidden if already a member.', 'loyalty-program'),
        'output' => __('Simple button with "Join" text. Logged in users: reloads page immediately after successful join. Logged out users: redirects to registration page.', 'loyalty-program'),
    ),
    array(
        'id' => 'user_coupon',
        'icon' => 'tag',
        'name' => __('User Coupon Code', 'loyalty-program'),
        'code' => '[loyalty_user_coupon]',
        'description' => __('Displays the user\'s personal discount coupon.', 'loyalty-program'),
        'parameters' => array(),
        'examples' => array('[loyalty_user_coupon]'),
        'requirements' => __('User must be logged in and a loyalty program member.', 'loyalty-program'),
        'output' => __('Coupon box with code and copy button.', 'loyalty-program'),
    ),
    array(
        'id' => 'current_points',
        'icon' => 'star-filled',
        'name' => __('Current Points', 'loyalty-program'),
        'code' => '[loyalty_current_points]',
        'description' => __('Displays current available points balance.', 'loyalty-program'),
        'parameters' => array(),
        'examples' => array('[loyalty_current_points]'),
        'requirements' => __('User must be logged in.', 'loyalty-program'),
        'output' => __('Number display with label.', 'loyalty-program'),
    ),
    array(
        'id' => 'total_points',
        'icon' => 'awards',
        'name' => __('Total Points Earned', 'loyalty-program'),
        'code' => '[loyalty_total_points]',
        'description' => __('Displays total lifetime points earned.', 'loyalty-program'),
        'parameters' => array(),
        'examples' => array('[loyalty_total_points]'),
        'requirements' => __('User must be logged in.', 'loyalty-program'),
        'output' => __('Number display with label.', 'loyalty-program'),
    ),
    array(
        'id' => 'membership_status',
        'icon' => 'id',
        'name' => __('Membership Status', 'loyalty-program'),
        'code' => '[loyalty_membership_status]',
        'description' => __('Shows if user is enrolled with join button.', 'loyalty-program'),
        'parameters' => array(),
        'examples' => array('[loyalty_membership_status]'),
        'requirements' => __('User must be logged in.', 'loyalty-program'),
        'output' => __('Membership box with status and join button if needed.', 'loyalty-program'),
    ),
    array(
        'id' => 'points_history',
        'icon' => 'list-view',
        'name' => __('Points History', 'loyalty-program'),
        'code' => '[loyalty_points_history]',
        'description' => __('Shows user\'s points transaction history.', 'loyalty-program'),
        'parameters' => array(
            array('name' => 'limit', 'desc' => __('Number of transactions to show', 'loyalty-program'), 'default' => '10'),
        ),
        'examples' => array(
            '[loyalty_points_history]',
            '[loyalty_points_history limit="20"]',
        ),
        'requirements' => __('User must be logged in and a member.', 'loyalty-program'),
    ),
    array(
        'id' => 'wheel_of_fortune',
        'icon' => 'image-rotate',
        'name' => __('Wheel of Fortune', 'loyalty-program'),
        'code' => '[loyalty_wheel_of_fortune]',
        'description' => __('Interactive wheel of fortune game.', 'loyalty-program'),
        'parameters' => array(),
        'examples' => array('[loyalty_wheel_of_fortune]'),
        'requirements' => __('User must be logged in and a member. Configured in Settings.', 'loyalty-program'),
        'output' => __('Animated wheel with prizes and spin button.', 'loyalty-program'),
    ),
    array(
        'id' => 'wheel_of_fortune_modal',
        'icon' => 'image-rotate',
        'name' => __('Wheel of Fortune - Modal', 'loyalty-program'),
        'code' => '[loyalty_wheel_of_fortune_modal]',
        'description' => __('Wheel of fortune game that opens in a modal popup. Shows a button "Spin the Wheel of Fortune" which opens a modal with the wheel. If user cannot spin, displays a countdown timer showing hours, minutes, and seconds until next spin. After spinning, the wheel disappears and shows: Congratulations! [Prize Name] + [Points] pkt, with a Close button.', 'loyalty-program'),
        'parameters' => array(),
        'examples' => array('[loyalty_wheel_of_fortune_modal]'),
        'requirements' => __('User must be logged in and a member. Configured in Settings. Uses same wheel configuration as standard wheel.', 'loyalty-program'),
        'output' => __('Button that opens modal. Modal shows: 1) Title "Spin and see what fortune brings you today!" with X close button. 2) If can spin: animated wheel with spin button. 3) If cannot spin: countdown timer in format HH hours, MM minutes, SS seconds. 4) After spin: result screen with prize name and points, plus close button.', 'loyalty-program'),
    ),
    array(
        'id' => 'rewards_list',
        'icon' => 'awards',
        'name' => __('Rewards List', 'loyalty-program'),
        'code' => '[loyalty_rewards_list]',
        'description' => __('Displays available rewards catalog.', 'loyalty-program'),
        'parameters' => array(),
        'examples' => array('[loyalty_rewards_list]'),
        'requirements' => __('User must be logged in and a member.', 'loyalty-program'),
        'output' => __('Grid of rewards with redeem buttons.', 'loyalty-program'),
    ),
    array(
        'id' => 'my_rewards',
        'icon' => 'cart',
        'name' => __('My Rewards', 'loyalty-program'),
        'code' => '[loyalty_my_rewards]',
        'description' => __('Shows user\'s redeemed rewards.', 'loyalty-program'),
        'parameters' => array(),
        'examples' => array('[loyalty_my_rewards]'),
        'requirements' => __('User must be logged in and a member.', 'loyalty-program'),
        'output' => __('List of redeemed rewards with codes.', 'loyalty-program'),
    ),
    array(
        'id' => 'consents',
        'icon' => 'yes-alt',
        'name' => __('Consents Form', 'loyalty-program'),
        'code' => '[loyalty_consents]',
        'description' => __('Form for SMS and email newsletter consents.', 'loyalty-program'),
        'parameters' => array(),
        'examples' => array('[loyalty_consents]'),
        'requirements' => __('User must be logged in and a member.', 'loyalty-program'),
        'output' => __('Form with checkboxes for consents.', 'loyalty-program'),
    ),
    array(
        'id' => 'check_consents',
        'icon' => 'search',
        'name' => __('Check Consents in SalesManago', 'loyalty-program'),
        'code' => '[loyalty_check_consents]',
        'description' => __('Button to check user\'s consent status in SalesManago. Verifies if SMS and newsletter consents are properly synchronized with the marketing automation platform.', 'loyalty-program'),
        'parameters' => array(),
        'examples' => array('[loyalty_check_consents]'),
        'requirements' => __('User must be logged in and a member. SalesManago integration must be enabled and configured.', 'loyalty-program'),
        'output' => __('Button that displays consent status from SalesManago (SMS and Newsletter).', 'loyalty-program'),
    ),
    array(
        'id' => 'birth_date',
        'icon' => 'calendar-alt',
        'name' => __('Birth Date Form', 'loyalty-program'),
        'code' => '[loyalty_birth_date]',
        'description' => __('Form for collecting user birth date.', 'loyalty-program'),
        'parameters' => array(),
        'examples' => array('[loyalty_birth_date]'),
        'requirements' => __('User must be logged in and a member.', 'loyalty-program'),
        'output' => __('Date input form with save button.', 'loyalty-program'),
    ),
    array(
        'id' => 'check_birth_date',
        'icon' => 'yes-alt',
        'name' => __('Check Birth Date Status', 'loyalty-program'),
        'code' => '[loyalty_check_birth_date]',
        'description' => __('Returns true or false based on whether user has provided their birth date. Useful for conditional content display and developer integrations.', 'loyalty-program'),
        'parameters' => array(
            array('name' => 'user_id', 'desc' => __('User ID to check (optional)', 'loyalty-program'), 'default' => 'Current logged in user'),
        ),
        'examples' => array(
            '[loyalty_check_birth_date]',
            '[loyalty_check_birth_date user_id="123"]',
        ),
        'requirements' => __('User must be logged in and a member.', 'loyalty-program'),
        'output' => __('Returns string "true" if user has birth date, "false" if not. Can be used in conditional shortcodes.', 'loyalty-program'),
    ),
    array(
        'id' => 'account_fields',
        'icon' => 'admin-users',
        'name' => __('Account Fields Form', 'loyalty-program'),
        'code' => '[loyalty_account_fields]',
        'description' => __('Displays a form with all enabled account fields (birth date, SMS consent, newsletter consent, phone number, user coupon). Fields are configured in Settings → User Account Fields.', 'loyalty-program'),
        'parameters' => array(),
        'examples' => array(
            '[loyalty_account_fields]',
        ),
        'requirements' => __('User must be logged in and a member. At least one field must be enabled in Settings → User Account Fields.', 'loyalty-program'),
        'output' => __('Form with enabled fields and "Save changes" button. Form submits to WooCommerce My Account edit page.', 'loyalty-program'),
    ),
    array(
        'id' => 'check_profile_complete',
        'icon' => 'admin-users',
        'name' => __('Check Profile Complete Status', 'loyalty-program'),
        'code' => '[loyalty_check_profile_complete]',
        'description' => __('Returns true or false based on whether user has completed their profile. Profile is complete when: birth date, SMS consent, newsletter consent, and billing phone are all filled.', 'loyalty-program'),
        'parameters' => array(
            array('name' => 'user_id', 'desc' => __('User ID to check (optional)', 'loyalty-program'), 'default' => 'Current logged in user'),
        ),
        'examples' => array(
            '[loyalty_check_profile_complete]',
            '[loyalty_check_profile_complete user_id="123"]',
        ),
        'requirements' => __('User must be logged in and a member. Checks: birth date, SMS consent, newsletter consent, billing phone.', 'loyalty-program'),
        'output' => __('Returns string "true" if profile is complete, "false" if not. Can be used in conditional shortcodes and integrations.', 'loyalty-program'),
    ),
    array(
        'id' => 'check_survey_completed',
        'icon' => 'yes',
        'name' => __('Check Survey/Quiz Completed', 'loyalty-program'),
        'code' => '[loyalty_check_survey_completed id="survey_id"]',
        'description' => __('Returns true or false based on whether user has completed specific survey or quiz. Requires survey ID as parameter.', 'loyalty-program'),
        'parameters' => array(
            array('name' => 'id', 'desc' => __('Survey/Quiz ID (required)', 'loyalty-program'), 'default' => 'None - must be provided'),
            array('name' => 'user_id', 'desc' => __('User ID to check (optional)', 'loyalty-program'), 'default' => 'Current logged in user'),
        ),
        'examples' => array(
            '[loyalty_check_survey_completed id="survey_123"]',
            '[loyalty_check_survey_completed id="quiz_abc" user_id="456"]',
        ),
        'requirements' => __('User must be logged in and a member. Survey/Quiz ID must be provided.', 'loyalty-program'),
        'output' => __('Returns string "true" if user completed the survey/quiz, "false" if not. Perfect for conditional content and gamification.', 'loyalty-program'),
    ),
    array(
        'id' => 'check_live_participated',
        'icon' => 'video-alt3',
        'name' => __('Check Live Expert Participation', 'loyalty-program'),
        'code' => '[loyalty_check_live_participated id="live_1234567890_1234"]',
        'description' => __('Returns true or false based on whether user participated in specific Live with Expert session. Requires CSV ID from import history.', 'loyalty-program'),
        'parameters' => array(
            array('name' => 'id', 'desc' => __('CSV ID from Live Expert import (required)', 'loyalty-program'), 'default' => 'None - must be provided'),
            array('name' => 'user_id', 'desc' => __('User ID to check (optional)', 'loyalty-program'), 'default' => 'Current logged in user'),
        ),
        'examples' => array(
            '[loyalty_check_live_participated id="live_1699876543_1234"]',
            '[loyalty_check_live_participated id="live_1699876544_5678" user_id="123"]',
        ),
        'requirements' => __('User must be logged in and a member. CSV ID from Live Expert import page is required.', 'loyalty-program'),
        'output' => __('Returns string "true" if user was in the CSV import, "false" if not. Use for exclusive content for live participants.', 'loyalty-program'),
    ),
    array(
        'id' => 'check_attendance_master',
        'icon' => 'yes-alt',
        'name' => __('Check Attendance Master Action', 'loyalty-program'),
        'code' => '[loyalty_check_attendance_master id="action_123"]',
        'description' => __('Returns true or false based on whether user has completed specific attendance action from Attendance Master.', 'loyalty-program'),
        'parameters' => array(
            array('name' => 'id', 'desc' => __('Action ID from Attendance Master (required)', 'loyalty-program'), 'default' => 'None - must be provided'),
            array('name' => 'user_id', 'desc' => __('User ID to check (optional)', 'loyalty-program'), 'default' => 'Current logged in user'),
        ),
        'examples' => array(
            '[loyalty_check_attendance_master id="action_123"]',
            '[loyalty_check_attendance_master id="action_456" user_id="789"]',
        ),
        'requirements' => __('User must be logged in and a member. Action ID from Attendance Master is required.', 'loyalty-program'),
        'output' => __('Returns string "true" if user completed the action, "false" if not. Use for conditional content based on action completion.', 'loyalty-program'),
    ),
    array(
        'id' => 'discipline_progress',
        'icon' => 'chart-line',
        'name' => __('Discipline Progress (Single Product)', 'loyalty-program'),
        'code' => '[loyalty_discipline_progress]',
        'description' => __('Shows user\'s purchase discipline progress for a specific product. Only displays if the product has "Supplementation Discipline" checkbox enabled in product settings. Best used on single product pages.', 'loyalty-program'),
        'parameters' => array(
            array('name' => 'product_id', 'desc' => __('Product ID (optional, auto-detected on product pages)', 'loyalty-program'), 'default' => 'Current product ID'),
        ),
        'examples' => array(
            '[loyalty_discipline_progress]',
            '[loyalty_discipline_progress product_id="123"]',
        ),
        'requirements' => __('User must be logged in and a member. Product must have "Supplementation Discipline" enabled. Purchase history tracked.', 'loyalty-program'),
        'output' => __('Progress bar showing purchase frequency and discipline status for the product. Appears only if product has discipline enabled.', 'loyalty-program'),
    ),
    array(
        'id' => 'discipline_products_list',
        'icon' => 'list-view',
        'name' => __('Discipline Products List', 'loyalty-program'),
        'code' => '[loyalty_discipline_products_list]',
        'description' => __('Displays a comprehensive list of ALL products with Supplementation Discipline enabled, showing user\'s purchase progress for each one. Perfect for a dedicated discipline progress page.', 'loyalty-program'),
        'parameters' => array(),
        'examples' => array(
            '[loyalty_discipline_products_list]',
        ),
        'requirements' => __('User must be logged in and a member. Shows all products with discipline checkbox enabled in WooCommerce product settings.', 'loyalty-program'),
        'output' => __('Grid/list showing each discipline product with: product image, name, ID, purchase count, progress bar, days remaining, and "View Product" button. Status colors: gray (not started), yellow (in progress), green (completed).', 'loyalty-program'),
    ),
    array(
        'id' => 'attendance_action',
        'icon' => 'awards',
        'name' => __('Attendance Action (Mistrz obecności)', 'loyalty-program'),
        'code' => '[loyalty_attendance_action id="action_id"]',
        'description' => __('Displays clickable element (button or text) that awards points when clicked. Element is only active within specified time range. Each user can click only once. Perfect for daily bonuses, timed promotions, or attendance tracking.', 'loyalty-program'),
        'parameters' => array(
            array('name' => 'id', 'desc' => __('Action ID from Attendance Master page (required)', 'loyalty-program'), 'default' => 'None - must be provided'),
        ),
        'examples' => array(
            '[loyalty_attendance_action id="action_1699876543_1234"]',
        ),
        'requirements' => __('User must be logged in and a member. Action must be enabled and within active time range. User can click only once per action.', 'loyalty-program'),
        'output' => __('Clickable button or text element with points indicator. Shows different states: active (clickable), completed (already clicked), expired (time passed), not yet available (before start time). Element visibility after expiry can be configured.', 'loyalty-program'),
    ),
    array(
        'id' => 'survey',
        'icon' => 'clipboard',
        'name' => __('Survey/Quiz', 'loyalty-program'),
        'code' => '[loyalty_survey id="SURVEY_ID"]',
        'description' => __('Displays a specific survey or quiz.', 'loyalty-program'),
        'parameters' => array(
            array('name' => 'id', 'desc' => __('Survey/Quiz ID (required)', 'loyalty-program'), 'default' => ''),
        ),
        'examples' => array('[loyalty_survey id="survey_123456"]'),
        'requirements' => __('User must be logged in and a member. Survey must be enabled.', 'loyalty-program'),
        'output' => __('Survey/quiz form with questions and submit button.', 'loyalty-program'),
    ),
);

?>

<div class="wrap loyalty-program-shortcodes-new">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <p class="description">
        <?php _e('Use these shortcodes to display loyalty program information on your pages and posts. Click "More Info" for detailed documentation.', 'loyalty-program'); ?>
    </p>

    <!-- Shortcode Pages Settings -->
    <div class="shortcode-pages-settings">
        <h2><?php _e('Loyalty Program Pages', 'loyalty-program'); ?></h2>
        <p class="description">
            <?php _e('Select WordPress pages where your loyalty program content will be displayed. These settings help organize your loyalty program structure.', 'loyalty-program'); ?>
        </p>

        <?php
        settings_errors('loyalty_program_shortcodes');

        // Get saved page IDs
        $loyalty_program_page = get_option('loyalty_program_page_id', 0);
        $points_history_page = get_option('loyalty_program_points_history_page_id', 0);
        $rewards_catalog_page = get_option('loyalty_program_rewards_catalog_page_id', 0);

        // Get page titles if pages are selected
        $loyalty_program_page_title = $loyalty_program_page ? get_the_title($loyalty_program_page) : '';
        $points_history_page_title = $points_history_page ? get_the_title($points_history_page) : '';
        $rewards_catalog_page_title = $rewards_catalog_page ? get_the_title($rewards_catalog_page) : '';
        ?>

        <form method="post" action="" id="shortcode-pages-form">
            <?php wp_nonce_field('loyalty_program_shortcode_pages', 'loyalty_program_shortcode_pages_nonce'); ?>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="loyalty_program_page">
                                <?php _e('Loyalty Program Page', 'loyalty-program'); ?>
                            </label>
                        </th>
                        <td>
                            <select name="loyalty_program_page_id" id="loyalty_program_page" class="page-select2"
                                style="width: 400px;">
                                <?php if ($loyalty_program_page && $loyalty_program_page_title) : ?>
                                <option value="<?php echo esc_attr($loyalty_program_page); ?>" selected>
                                    <?php echo esc_html($loyalty_program_page_title); ?>
                                </option>
                                <?php else : ?>
                                <option value=""><?php _e('-- Select Page --', 'loyalty-program'); ?></option>
                                <?php endif; ?>
                            </select>
                            <p class="description">
                                <?php _e('Main page displaying the loyalty program overview and information.', 'loyalty-program'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="points_history_page">
                                <?php _e('Points History Page', 'loyalty-program'); ?>
                            </label>
                        </th>
                        <td>
                            <select name="loyalty_program_points_history_page_id" id="points_history_page"
                                class="page-select2" style="width: 400px;">
                                <?php if ($points_history_page && $points_history_page_title) : ?>
                                <option value="<?php echo esc_attr($points_history_page); ?>" selected>
                                    <?php echo esc_html($points_history_page_title); ?>
                                </option>
                                <?php else : ?>
                                <option value=""><?php _e('-- Select Page --', 'loyalty-program'); ?></option>
                                <?php endif; ?>
                            </select>
                            <p class="description">
                                <?php _e('Page where users can view their points transaction history.', 'loyalty-program'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="rewards_catalog_page">
                                <?php _e('Rewards Catalog Page', 'loyalty-program'); ?>
                            </label>
                        </th>
                        <td>
                            <select name="loyalty_program_rewards_catalog_page_id" id="rewards_catalog_page"
                                class="page-select2" style="width: 400px;">
                                <?php if ($rewards_catalog_page && $rewards_catalog_page_title) : ?>
                                <option value="<?php echo esc_attr($rewards_catalog_page); ?>" selected>
                                    <?php echo esc_html($rewards_catalog_page_title); ?>
                                </option>
                                <?php else : ?>
                                <option value=""><?php _e('-- Select Page --', 'loyalty-program'); ?></option>
                                <?php endif; ?>
                            </select>
                            <p class="description">
                                <?php _e('Page displaying available rewards that users can redeem with their points.', 'loyalty-program'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <button type="submit" name="loyalty_program_shortcode_pages_save" class="button button-primary">
                    <?php _e('Save Pages', 'loyalty-program'); ?>
                </button>
            </p>
        </form>
    </div>

    <!-- Compact Shortcode List -->
    <div class="shortcodes-compact-list">
        <?php foreach ($shortcodes as $shortcode) : ?>
        <div class="shortcode-compact-card">
            <div class="shortcode-compact-header">
                <div class="shortcode-icon-name">
                    <span class="dashicons dashicons-<?php echo esc_attr($shortcode['icon']); ?>"></span>
                    <h3><?php echo esc_html($shortcode['name']); ?></h3>
                </div>
                <button type="button" class="button button-small more-info-btn"
                    data-shortcode-id="<?php echo esc_attr($shortcode['id']); ?>">
                    <span class="dashicons dashicons-info"></span>
                    <?php _e('More Info', 'loyalty-program'); ?>
                </button>
            </div>

            <div class="shortcode-compact-code">
                <code><?php echo esc_html($shortcode['code']); ?></code>
                <button type="button" class="copy-shortcode-btn"
                    data-shortcode="<?php echo esc_attr($shortcode['code']); ?>">
                    <span class="dashicons dashicons-admin-page"></span>
                    <?php _e('Copy', 'loyalty-program'); ?>
                </button>
            </div>

            <p class="shortcode-compact-desc"><?php echo esc_html($shortcode['description']); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Shortcode Details Modal -->
<div id="shortcode-details-modal" style="display: none;">
    <div class="shortcode-details-content">
        <span class="close-shortcode-details">&times;</span>
        <div id="shortcode-details-body">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<style>
.loyalty-program-shortcodes-new {
    max-width: 1200px;
}

.shortcodes-compact-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.shortcode-compact-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.3s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.shortcode-compact-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.shortcode-compact-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f1;
}

.shortcode-icon-name {
    display: flex;
    align-items: center;
    gap: 10px;
}

.shortcode-icon-name .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #2271b1;
}

.shortcode-icon-name h3 {
    margin: 0;
    font-size: 16px;
    color: #1d2327;
}

.more-info-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.more-info-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.shortcode-compact-code {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
    padding: 12px;
    background: #f0f6fc;
    border: 2px solid #2271b1;
    border-radius: 6px;
}

.shortcode-compact-code code {
    flex: 1;
    background: transparent;
    color: #2271b1;
    font-size: 14px;
    font-weight: 600;
    padding: 0;
}

.copy-shortcode-btn {
    background: #2271b1;
    color: #fff;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
}

.copy-shortcode-btn:hover {
    background: #135e96;
}

.copy-shortcode-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.copy-shortcode-btn.copied {
    background: #00a32a;
}

.shortcode-compact-desc {
    margin: 0;
    color: #646970;
    font-size: 14px;
    line-height: 1.5;
}

/* Shortcode Pages Settings */
.shortcode-pages-settings {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.shortcode-pages-settings h2 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 20px;
    color: #1d2327;
}

.shortcode-pages-settings .form-table th {
    width: 220px;
    padding: 20px 10px 20px 0;
}

.shortcode-pages-settings .form-table td {
    padding: 20px 0;
}

/* Select2 Styles for Pages */
.page-select2+.select2-container {
    display: block;
}

.page-select2+.select2-container .select2-selection--single {
    height: 38px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    padding: 4px 8px;
}

.page-select2+.select2-container .select2-selection--single .select2-selection__rendered {
    line-height: 28px;
    padding-left: 8px;
    color: #2c3338;
}

.page-select2+.select2-container .select2-selection--single .select2-selection__arrow {
    height: 36px;
    right: 4px;
}

.page-select2+.select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #646970;
}

.page-select2+.select2-dropdown {
    border: 1px solid #8c8f94;
    border-radius: 4px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.page-select2+.select2-results__option {
    padding: 8px 12px;
}

.page-select2+.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #2271b1;
    color: #fff;
}

.page-select2+.select2-container--focus .select2-selection--single {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
}

/* Modal */
#shortcode-details-modal {
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

.shortcode-details-content {
    background-color: #fff;
    padding: 30px;
    border-radius: 8px;
    max-width: 700px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.close-shortcode-details {
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

.close-shortcode-details:hover {
    color: #d63638;
}

.shortcode-modal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #2271b1;
}

.shortcode-modal-header .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    color: #2271b1;
}

.shortcode-modal-header h2 {
    margin: 0;
    font-size: 22px;
}

.modal-section {
    margin-bottom: 25px;
}

.modal-section h4 {
    margin: 0 0 10px;
    font-size: 16px;
    color: #1d2327;
    display: flex;
    align-items: center;
    gap: 6px;
}

.modal-section h4 .dashicons {
    color: #2271b1;
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.modal-shortcode-display {
    background: #f0f6fc;
    border: 2px solid #2271b1;
    padding: 15px;
    border-radius: 6px;
    margin: 10px 0;
}

.modal-shortcode-display code {
    font-size: 15px;
    color: #2271b1;
    font-weight: 600;
}

.params-table {
    width: 100%;
    border-collapse: collapse;
    margin: 10px 0;
}

.params-table th,
.params-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #dcdcde;
}

.params-table th {
    background: #f0f0f1;
    font-weight: 600;
    font-size: 13px;
}

.params-table td {
    font-size: 13px;
}

.params-table code {
    background: #f0f0f1;
    padding: 2px 6px;
    border-radius: 3px;
    color: #2271b1;
}

.examples-list {
    background: #f0f0f1;
    padding: 15px;
    border-radius: 6px;
    margin: 10px 0;
}

.examples-list code {
    display: block;
    background: #fff;
    padding: 8px 12px;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    margin: 5px 0;
    color: #2271b1;
    font-weight: 600;
}

.info-box {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 12px 15px;
    border-radius: 4px;
    margin: 10px 0;
}

.info-box p {
    margin: 0;
    color: #92400e;
    font-size: 13px;
}

@media screen and (max-width: 768px) {
    .shortcodes-compact-list {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    const shortcodesData = <?php echo json_encode($shortcodes); ?>;
    const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

    // Copy shortcode
    $('.copy-shortcode-btn').on('click', function() {
        const $btn = $(this);
        const shortcode = $btn.data('shortcode');

        // Create temp input
        const $temp = $('<input>');
        $('body').append($temp);
        $temp.val(shortcode).select();
        document.execCommand('copy');
        $temp.remove();

        // Visual feedback
        const originalText = $btn.html();
        $btn.addClass('copied').html(
            '<span class="dashicons dashicons-yes"></span> <?php _e('Copied!', 'loyalty-program'); ?>'
        );

        setTimeout(function() {
            $btn.removeClass('copied').html(originalText);
        }, 2000);
    });

    // More info button
    $('.more-info-btn').on('click', function() {
        const shortcodeId = $(this).data('shortcode-id');
        const shortcode = shortcodesData.find(s => s.id === shortcodeId);

        if (!shortcode) return;

        let html = '<div class="shortcode-modal-header">';
        html += '<span class="dashicons dashicons-' + shortcode.icon + '"></span>';
        html += '<h2>' + shortcode.name + '</h2>';
        html += '</div>';

        // Shortcode
        html += '<div class="modal-section">';
        html += '<div class="modal-shortcode-display">';
        html += '<code>' + shortcode.code + '</code>';
        html += '</div>';
        html += '</div>';

        // Description
        html += '<div class="modal-section">';
        html +=
            '<h4><span class="dashicons dashicons-editor-alignleft"></span> <?php _e('Description', 'loyalty-program'); ?></h4>';
        html += '<p>' + shortcode.description + '</p>';
        html += '</div>';

        // Parameters
        if (shortcode.parameters && shortcode.parameters.length > 0) {
            html += '<div class="modal-section">';
            html +=
                '<h4><span class="dashicons dashicons-admin-settings"></span> <?php _e('Parameters', 'loyalty-program'); ?></h4>';
            html += '<table class="params-table">';
            html +=
                '<thead><tr><th><?php _e('Parameter', 'loyalty-program'); ?></th><th><?php _e('Description', 'loyalty-program'); ?></th><th><?php _e('Default', 'loyalty-program'); ?></th></tr></thead>';
            html += '<tbody>';
            shortcode.parameters.forEach(param => {
                html += '<tr>';
                html += '<td><code>' + param.name + '</code></td>';
                html += '<td>' + param.desc + '</td>';
                html += '<td>' + (param.default || '—') + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            html += '</div>';
        }

        // Examples
        if (shortcode.examples && shortcode.examples.length > 0) {
            html += '<div class="modal-section">';
            html +=
                '<h4><span class="dashicons dashicons-editor-code"></span> <?php _e('Examples', 'loyalty-program'); ?></h4>';
            html += '<div class="examples-list">';
            shortcode.examples.forEach(example => {
                html += '<code>' + example + '</code>';
            });
            html += '</div>';
            html += '</div>';
        }

        // Requirements
        if (shortcode.requirements) {
            html += '<div class="modal-section">';
            html +=
                '<h4><span class="dashicons dashicons-warning"></span> <?php _e('Requirements', 'loyalty-program'); ?></h4>';
            html += '<div class="info-box"><p>' + shortcode.requirements + '</p></div>';
            html += '</div>';
        }

        // Output
        if (shortcode.output) {
            html += '<div class="modal-section">';
            html +=
                '<h4><span class="dashicons dashicons-visibility"></span> <?php _e('Output', 'loyalty-program'); ?></h4>';
            html += '<p>' + shortcode.output + '</p>';
            html += '</div>';
        }

        $('#shortcode-details-body').html(html);
        $('#shortcode-details-modal').fadeIn(300);
    });

    // Close modal
    $('.close-shortcode-details, #shortcode-details-modal').on('click', function(e) {
        if (e.target === this) {
            $('#shortcode-details-modal').fadeOut(300);
        }
    });

    // Initialize Select2 for page selection
    function initPageSelect2($element) {
        $element.select2({
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 300,
                type: 'GET',
                data: function(params) {
                    return {
                        action: 'loyalty_program_search_pages',
                        _ajax_nonce: '<?php echo wp_create_nonce('loyalty_program_search_pages'); ?>',
                        search: params.term || ''
                    };
                },
                processResults: function(data) {
                    if (data.success) {
                        return {
                            results: data.data.results
                        };
                    }
                    return {
                        results: []
                    };
                },
                cache: true
            },
            minimumInputLength: 0,
            placeholder: '<?php esc_attr_e('-- Select Page --', 'loyalty-program'); ?>',
            allowClear: true,
            width: '400px',
            language: {
                inputTooShort: function() {
                    return '<?php esc_html_e('Start typing to search pages...', 'loyalty-program'); ?>';
                },
                searching: function() {
                    return '<?php esc_html_e('Searching...', 'loyalty-program'); ?>';
                },
                noResults: function() {
                    return '<?php esc_html_e('No pages found', 'loyalty-program'); ?>';
                },
                errorLoading: function() {
                    return '<?php esc_html_e('Error loading results. Please try again.', 'loyalty-program'); ?>';
                }
            }
        });
    }

    // Initialize page select2 elements
    $('.page-select2').each(function() {
        initPageSelect2($(this));
    });
});
</script>

<?php
// Enqueue Select2 from CDN
wp_enqueue_script(
    'select2-cdn',
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
    array('jquery'),
    '4.1.0',
    true
);

wp_enqueue_style(
    'select2-cdn',
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
    array(),
    '4.1.0'
);
?>