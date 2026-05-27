<?php

/**
 * AJAX functionality
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Loyalty Program AJAX Class
 */
class Loyalty_Program_Ajax
{

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Hook into actions and filters
     * 
     * @return void
     */
    private function init_hooks()
    {
        // Get debug log
        add_action('wp_ajax_loyalty_program_get_log', array($this, 'get_debug_log'));

        // Search users
        add_action('wp_ajax_loyalty_program_search_users', array($this, 'search_users'));

        // Get user details
        add_action('wp_ajax_loyalty_program_get_user_details', array($this, 'get_user_details'));

        // Modify user points
        add_action('wp_ajax_loyalty_program_modify_points', array($this, 'modify_user_points'));

        // Toggle membership
        add_action('wp_ajax_loyalty_program_toggle_membership', array($this, 'toggle_membership'));

        // Update coupons
        add_action('wp_ajax_loyalty_program_update_coupons', array($this, 'update_all_coupons'));
        add_action('wp_ajax_loyalty_program_generate_coupons', array($this, 'generate_coupons_batch'));

        // Product search for rewards
        add_action('wp_ajax_loyalty_program_search_products', array($this, 'search_products'));

        // Category search for coupon settings
        add_action('wp_ajax_loyalty_program_search_categories', array($this, 'search_categories'));

        // Page search for shortcodes
        add_action('wp_ajax_loyalty_program_search_pages', array($this, 'search_pages'));

        // Clear dashboard cache
        add_action('wp_ajax_loyalty_program_clear_dashboard_cache', array($this, 'clear_dashboard_cache'));

        // Frontend: Join program
        add_action('wp_ajax_loyalty_program_join_frontend', array($this, 'join_program_frontend'));

        // Wheel of fortune: Spin
        add_action('wp_ajax_loyalty_program_spin_wheel', array($this, 'spin_wheel'));

        // Wheel of fortune: Reset for user
        add_action('wp_ajax_loyalty_program_reset_wheel', array($this, 'reset_user_wheel'));

        // Redeem reward
        add_action('wp_ajax_loyalty_program_redeem_reward', array($this, 'redeem_reward'));

        // Add reward to cart
        add_action('wp_ajax_loyalty_program_add_reward_to_cart', array($this, 'add_reward_to_cart'));

        // Save user consents
        add_action('wp_ajax_loyalty_program_save_consents', array($this, 'save_consents'));

        // Save birth date
        add_action('wp_ajax_loyalty_program_save_birth_date', array($this, 'save_birth_date'));

        // Save account data (birth date and phone)
        add_action('wp_ajax_loyalty_program_save_account_data', array($this, 'save_account_data'));

        // Save account data for non-members
        add_action('wp_ajax_loyalty_program_save_account_data_non_member', array($this, 'save_account_data_non_member'));

        // Submit survey/quiz
        add_action('wp_ajax_loyalty_program_submit_survey', array($this, 'submit_survey'));

        // Export Survey Results
        add_action('wp_ajax_export_survey_results', array($this, 'export_survey_results'));

        // Test SalesManago connection
        add_action('wp_ajax_loyalty_program_test_salesmanago', array($this, 'test_salesmanago_connection'));

        // Verify email in SalesManago
        add_action('wp_ajax_loyalty_program_verify_email_salesmanago', array($this, 'verify_email_salesmanago'));

        // Sync consents with SalesManago
        add_action('wp_ajax_loyalty_program_sync_consents_salesmanago', array($this, 'sync_consents_salesmanago'));

        // Check user consents in SalesManago
        add_action('wp_ajax_loyalty_program_check_salesmanago_consents', array($this, 'check_salesmanago_consents'));
        add_action('wp_ajax_nopriv_loyalty_program_check_salesmanago_consents', array($this, 'check_salesmanago_consents'));

        // Attendance Action click
        add_action('wp_ajax_loyalty_attendance_click', array($this, 'handle_attendance_click'));

        // Join program (logged in users)
        add_action('wp_ajax_loyalty_join_program', array($this, 'handle_join_program'));

        // Register and join (not logged in users)
        add_action('wp_ajax_nopriv_loyalty_register_and_join', array($this, 'handle_register_and_join'));
        add_action('wp_ajax_loyalty_register_and_join', array($this, 'handle_register_and_join'));
    }

    /**
     * Get debug log content
     * 
     * @return void
     */
    public function get_debug_log()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'loyalty_program_get_log')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'loyalty-program')
            ));
        }

        // Check permissions
        if (!current_user_can('manage_loyalty_program')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'loyalty-program')
            ));
        }

        // Load logger class
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        $content = Loyalty_Program_Logger::get_log_content(100);

        wp_send_json_success(array(
            'content' => $content
        ));
    }

    /**
     * Search users
     * 
     * @return void
     */
    public function search_users()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'loyalty_program_search_users')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'loyalty-program')
            ));
        }

        // Check permissions
        if (!current_user_can('manage_loyalty_program')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'loyalty-program')
            ));
        }

        // Get search term
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        if (empty($search) || strlen($search) < 3) {
            wp_send_json_error(array(
                'message' => __('Search term must be at least 3 characters long.', 'loyalty-program')
            ));
        }

        // Search users by username or email - ONLY LOYALTY PROGRAM MEMBERS
        $args = array(
            'search' => '*' . $search . '*',
            'search_columns' => array('user_login', 'user_email'),
            'number' => 50, // Limit to 50 results
            'orderby' => 'display_name',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => 'loyalty_program_member',
                    'value' => 'yes',
                    'compare' => '='
                )
            )
        );

        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();

        if (empty($users)) {
            wp_send_json_success(array(
                'users' => array(),
                'count' => 0
            ));
        }

        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        $users_data = array();

        foreach ($users as $user) {
            // Get membership info
            $membership = Loyalty_Program_Points::get_membership_info($user->ID);

            // Get personal coupon if exists
            $personal_coupon = '';
            if (class_exists('WooCommerce')) {
                if (!class_exists('Loyalty_Program_WooCommerce')) {
                    require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-woocommerce.php';
                }
                $personal_coupon = Loyalty_Program_WooCommerce::get_user_coupon($user->ID);
            }

            $users_data[] = array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'first_name' => get_user_meta($user->ID, 'first_name', true),
                'last_name' => get_user_meta($user->ID, 'last_name', true),
                'avatar' => get_avatar_url($user->ID, array('size' => 64)),
                'registered' => date_i18n(get_option('date_format'), strtotime($user->user_registered)),
                'is_member' => $membership['is_member'],
                'join_date' => $membership['join_date_formatted'],
                'current_points' => Loyalty_Program_Points::get_current_points($user->ID),
                'personal_coupon' => $personal_coupon,
                'has_notifications_consent' => Loyalty_Program_Points::has_notifications_consent($user->ID),
            );
        }

        wp_send_json_success(array(
            'users' => $users_data,
            'count' => count($users_data)
        ));
    }

    /**
     * Get user details with points information
     * 
     * @return void
     */
    public function get_user_details()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'loyalty_program_get_user_details')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'loyalty-program')
            ));
        }

        // Check permissions
        if (!current_user_can('manage_loyalty_program')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'loyalty-program')
            ));
        }

        // Get user ID
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;

        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('Invalid user ID.', 'loyalty-program')
            ));
        }

        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(array(
                'message' => __('User not found.', 'loyalty-program')
            ));
        }

        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Get user data
        $user_data = array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => get_user_meta($user->ID, 'first_name', true),
            'last_name' => get_user_meta($user->ID, 'last_name', true),
        );

        // Get membership info
        $membership = Loyalty_Program_Points::get_membership_info($user_id);
        $user_data = array_merge($user_data, $membership);

        // Get additional user info
        $birth_date = get_user_meta($user_id, 'loyalty_program_birth_date', true);
        $phone = get_user_meta($user_id, 'billing_phone', true);
        $sms_consent = get_user_meta($user_id, 'loyalty_program_sms_consent', true) === 'yes';
        $newsletter_consent = get_user_meta($user_id, 'loyalty_program_newsletter_consent', true) === 'yes';

        $user_data['birth_date'] = $birth_date;
        $user_data['phone'] = $phone;
        $user_data['sms_consent'] = $sms_consent;
        $user_data['newsletter_consent'] = $newsletter_consent;

        // Get points stats
        $stats = Loyalty_Program_Points::get_user_stats($user_id);

        // Get points history (last 50 transactions)
        $history = Loyalty_Program_Points::get_points_history($user_id, 50);

        // Format dates in history
        foreach ($history as &$transaction) {
            $transaction['date'] = date_i18n(
                get_option('date_format') . ' ' . get_option('time_format'),
                $transaction['timestamp']
            );
        }

        // Get wheel info
        $last_wheel_spin = get_user_meta($user_id, 'loyalty_program_last_wheel_spin', true);
        $days_between_spins = get_option('loyalty_program_wheel_days_between_spins', 7);
        $can_spin = false;
        $next_spin_date = '';

        if (empty($last_wheel_spin)) {
            $can_spin = true;
            $next_spin_date = __('Never spun', 'loyalty-program');
        } else {
            // Convert MySQL date to timestamp in WordPress timezone
            $last_spin_timestamp = mysql2date('U', $last_wheel_spin);
            $next_spin_timestamp = $last_spin_timestamp + ($days_between_spins * DAY_IN_SECONDS);
            $current_timestamp = current_time('timestamp');

            if ($current_timestamp >= $next_spin_timestamp) {
                $can_spin = true;
            }

            $next_spin_date = date_i18n(
                get_option('date_format') . ' ' . get_option('time_format'),
                $next_spin_timestamp
            );
        }

        $wheel_data = array(
            'last_spin' => $last_wheel_spin ? date_i18n(
                get_option('date_format') . ' ' . get_option('time_format'),
                mysql2date('U', $last_wheel_spin)
            ) : __('Never', 'loyalty-program'),
            'can_spin' => $can_spin,
            'next_spin_date' => $next_spin_date,
        );

        // Get completed surveys
        $completed_surveys = get_user_meta($user_id, 'loyalty_program_completed_surveys', true);
        $survey_responses = get_user_meta($user_id, 'loyalty_program_survey_responses', true);

        $surveys_data = array();
        if (is_array($completed_surveys) && !empty($completed_surveys)) {
            $all_surveys = get_option('loyalty_program_surveys', array());

            foreach ($completed_surveys as $survey_id) {
                // Find survey details
                $survey_name = $survey_id;
                $survey_type = 'survey';

                foreach ($all_surveys as $survey) {
                    if ($survey['id'] === $survey_id) {
                        $survey_name = $survey['name'];
                        $survey_type = $survey['type'];
                        break;
                    }
                }

                $survey_info = array(
                    'id' => $survey_id,
                    'name' => $survey_name,
                    'type' => $survey_type,
                    'completed_at' => '',
                    'points_earned' => 0,
                    'score' => null,
                );

                if (is_array($survey_responses) && isset($survey_responses[$survey_id])) {
                    $response = $survey_responses[$survey_id];
                    $survey_info['completed_at'] = isset($response['completed_at']) ? $response['completed_at'] : '';
                    $survey_info['points_earned'] = isset($response['points_earned']) ? $response['points_earned'] : 0;
                    $survey_info['score'] = isset($response['score_percentage']) ? $response['score_percentage'] : null;
                }

                $surveys_data[] = $survey_info;
            }
        }

        // Generate HTML
        $html = $this->render_user_details_html($user_data, $stats, $history, $wheel_data, $surveys_data);

        wp_send_json_success(array(
            'html' => $html
        ));
    }

    /**
     * Render user details HTML
     * 
     * @param array $user_data User data
     * @param array $stats Stats data
     * @param array $history Points history
     * @param array $wheel_data Wheel data
     * @param array $surveys_data Surveys data
     * @return string HTML output
     */
    private function render_user_details_html($user_data, $stats, $history, $wheel_data, $surveys_data)
    {
        ob_start();
?>
        <div class="loyalty-user-details">
            <div class="user-details-header">
                <div class="user-avatar">
                    <?php echo get_avatar($user_data['id'], 80); ?>
                </div>
                <div class="user-info">
                    <h3><?php echo esc_html($user_data['display_name']); ?></h3>
                    <div class="user-meta-grid">
                        <div><strong><?php _e('Email:', 'loyalty-program'); ?></strong>
                            <?php echo esc_html($user_data['email']); ?></div>
                        <div><strong><?php _e('ID:', 'loyalty-program'); ?></strong> <?php echo esc_html($user_data['id']); ?>
                        </div>
                        <div><strong><?php _e('Username:', 'loyalty-program'); ?></strong>
                            <?php echo esc_html($user_data['username']); ?></div>
                        <div><strong><?php _e('Join Date:', 'loyalty-program'); ?></strong>
                            <?php echo $user_data['join_date_formatted'] ? esc_html($user_data['join_date_formatted']) : '<em style="color: #646970;">' . __('Not available', 'loyalty-program') . '</em>'; ?>
                        </div>
                        <div><strong><?php _e('Birth Date:', 'loyalty-program'); ?></strong>
                            <?php echo $user_data['birth_date'] ? esc_html($user_data['birth_date']) : '<em style="color: #646970;">' . __('Not provided', 'loyalty-program') . '</em>'; ?>
                        </div>
                        <div><strong><?php _e('Phone:', 'loyalty-program'); ?></strong>
                            <?php echo $user_data['phone'] ? esc_html($user_data['phone']) : '<em style="color: #646970;">' . __('Not provided', 'loyalty-program') . '</em>'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid with 3 columns -->
            <div class="user-details-grid">
                <!-- Column 1: Points -->
                <div class="details-card">
                    <h4><span class="dashicons dashicons-star-filled"></span> <?php _e('Points', 'loyalty-program'); ?></h4>
                    <div class="points-stats">
                        <div class="point-item">
                            <span class="point-label"><?php _e('Current Points', 'loyalty-program'); ?></span>
                            <span class="point-value current"><?php echo number_format_i18n($stats['current_points']); ?></span>
                        </div>
                        <div class="point-item">
                            <span class="point-label"><?php _e('Total Earned', 'loyalty-program'); ?></span>
                            <span class="point-value earned"><?php echo number_format_i18n($stats['total_earned']); ?></span>
                        </div>
                        <div class="point-item">
                            <span class="point-label"><?php _e('Total Spent', 'loyalty-program'); ?></span>
                            <span class="point-value spent"><?php echo number_format_i18n($stats['total_spent']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Consents -->
                <div class="details-card">
                    <h4><span class="dashicons dashicons-admin-settings"></span> <?php _e('Consents', 'loyalty-program'); ?>
                    </h4>
                    <div class="consents-list">
                        <div class="consent-item">
                            <span class="consent-label"><?php _e('SMS Notifications', 'loyalty-program'); ?></span>
                            <span class="consent-status <?php echo $user_data['sms_consent'] ? 'active' : 'inactive'; ?>">
                                <?php echo $user_data['sms_consent'] ? '<span style="color: #00a32a; font-size: 20px;">✓</span>' : '<span style="color: #d63638; font-size: 20px;">✗</span>'; ?>
                            </span>
                        </div>
                        <div class="consent-item">
                            <span class="consent-label"><?php _e('Email Newsletter', 'loyalty-program'); ?></span>
                            <span
                                class="consent-status <?php echo $user_data['newsletter_consent'] ? 'active' : 'inactive'; ?>">
                                <?php echo $user_data['newsletter_consent'] ? '<span style="color: #00a32a; font-size: 20px;">✓</span>' : '<span style="color: #d63638; font-size: 20px;">✗</span>'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Column 3: Wheel of Fortune -->
                <div class="details-card">
                    <h4><span class="dashicons dashicons-image-rotate"></span>
                        <?php _e('Wheel of Fortune', 'loyalty-program'); ?></h4>
                    <div class="wheel-stats">
                        <div class="wheel-item">
                            <span class="wheel-label"><?php _e('Last Spin', 'loyalty-program'); ?></span>
                            <span class="wheel-value"><?php echo esc_html($wheel_data['last_spin']); ?></span>
                        </div>
                        <div class="wheel-item">
                            <span class="wheel-label"><?php _e('Can Spin', 'loyalty-program'); ?></span>
                            <span class="wheel-value">
                                <?php if ($wheel_data['can_spin']) : ?>
                                    <span style="color: #00a32a; font-weight: 700; font-size: 16px;">✓
                                        <?php _e('Yes', 'loyalty-program'); ?></span>
                                <?php else : ?>
                                    <span style="color: #d63638; font-weight: 700; font-size: 16px;">✗
                                        <?php _e('No', 'loyalty-program'); ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Full Width Sections -->
            <div class="full-width-sections">
                <!-- Points History -->
                <div class="details-card">
                    <h4><span class="dashicons dashicons-list-view"></span>
                        <?php _e('Points History (Last 50)', 'loyalty-program'); ?></h4>
                    <?php if (!empty($history)) : ?>
                        <div style="max-height: 200px; overflow-y: auto; border: 1px solid #dcdcde; border-radius: 4px;">
                            <table class="widefat striped" style="margin: 0;">
                                <thead>
                                    <tr>
                                        <th><?php _e('Date', 'loyalty-program'); ?></th>
                                        <th><?php _e('Action', 'loyalty-program'); ?></th>
                                        <th style="text-align: right;"><?php _e('Points', 'loyalty-program'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $transaction) : ?>
                                        <tr>
                                            <td><?php echo esc_html($transaction['date']); ?></td>
                                            <td><?php echo esc_html($transaction['action']); ?></td>
                                            <td style="text-align: right;">
                                                <strong
                                                    style="color: <?php echo $transaction['type'] === 'increase' ? '#00a32a' : '#d63638'; ?>;">
                                                    <?php echo $transaction['type'] === 'increase' ? '+' : '-'; ?><?php echo number_format_i18n($transaction['points']); ?>
                                                </strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p style="color: #646970; font-style: italic; margin: 15px 0;">
                            <?php _e('No points history yet.', 'loyalty-program'); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Surveys Participation -->
                <?php if (!empty($surveys_data)) : ?>
                    <div class="details-card">
                        <h4><span class="dashicons dashicons-clipboard"></span>
                            <?php _e('Surveys & Quizzes Participation', 'loyalty-program'); ?></h4>
                        <div style="max-height: 100px; overflow-y: auto; border: 1px solid #dcdcde; border-radius: 4px;">
                            <table class="widefat striped" style="margin: 0;">
                                <thead>
                                    <tr>
                                        <th><?php _e('Name', 'loyalty-program'); ?></th>
                                        <th><?php _e('Type', 'loyalty-program'); ?></th>
                                        <th><?php _e('Completed', 'loyalty-program'); ?></th>
                                        <th style="text-align: center;"><?php _e('Score', 'loyalty-program'); ?></th>
                                        <th style="text-align: center;"><?php _e('Points', 'loyalty-program'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($surveys_data as $survey) : ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($survey['name']); ?></strong></td>
                                            <td>
                                                <span class="survey-type-badge <?php echo esc_attr($survey['type']); ?>"
                                                    style="padding: 2px 8px; border-radius: 3px; font-size: 11px;">
                                                    <?php echo $survey['type'] === 'quiz' ? __('Quiz', 'loyalty-program') : __('Survey', 'loyalty-program'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $survey['completed_at'] ? esc_html(date_i18n(get_option('date_format'), strtotime($survey['completed_at']))) : '—'; ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <?php if ($survey['score'] !== null) : ?>
                                                    <strong style="color: #2271b1;"><?php echo esc_html($survey['score']); ?>%</strong>
                                                <?php else : ?>
                                                    <span style="color: #646970;">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <strong
                                                    style="color: #00a32a;"><?php echo esc_html($survey['points_earned']); ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="details-card">
                        <h4><span class="dashicons dashicons-clipboard"></span>
                            <?php _e('Surveys & Quizzes Participation', 'loyalty-program'); ?></h4>
                        <p style="color: #646970; font-style: italic; margin: 15px 0;">
                            <?php _e('No surveys or quizzes completed yet.', 'loyalty-program'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <style>
            .loyalty-user-details {
                padding: 10px 0;
            }

            .user-details-header {
                display: flex;
                align-items: flex-start;
                gap: 20px;
                padding: 20px;
                background: linear-gradient(135deg, #f0f6fc 0%, #e6f2ff 100%);
                border: 2px solid #2271b1;
                border-radius: 8px;
                margin-bottom: 20px;
            }

            .user-avatar img {
                border-radius: 50%;
                border: 3px solid #fff;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            .user-info {
                flex: 1;
            }

            .user-info h3 {
                margin: 0 0 12px;
                font-size: 24px;
                color: #1d2327;
            }

            .user-meta-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
                font-size: 13px;
                color: #646970;
            }

            .user-meta-grid strong {
                color: #1d2327;
            }

            .user-details-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
                margin-bottom: 20px;
            }

            .full-width-sections {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }

            .details-card {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            }

            .details-card h4 {
                margin: 0 0 15px;
                display: flex;
                align-items: center;
                gap: 8px;
                color: #1d2327;
                font-size: 16px;
                padding-bottom: 10px;
                border-bottom: 2px solid #f0f0f1;
            }

            .details-card .dashicons {
                color: #2271b1;
                font-size: 20px;
                width: 20px;
                height: 20px;
            }

            /* Points Stats */
            .points-stats {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .point-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px;
                background: #f9fafb;
                border-radius: 4px;
            }

            .point-label {
                font-size: 13px;
                color: #646970;
                font-weight: 600;
            }

            .point-value {
                font-size: 20px;
                font-weight: 700;
            }

            .point-value.current {
                color: #2271b1;
            }

            .point-value.earned {
                color: #00a32a;
            }

            .point-value.spent {
                color: #d63638;
            }

            /* Consents */
            .consents-list {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }

            .consent-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px;
                background: #f9fafb;
                border-radius: 4px;
            }

            .consent-label {
                font-size: 14px;
                color: #1d2327;
                font-weight: 600;
            }

            /* Wheel Stats */
            .wheel-stats {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .wheel-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px;
                background: #f9fafb;
                border-radius: 4px;
            }

            .wheel-label {
                font-size: 13px;
                color: #646970;
                font-weight: 600;
            }

            .wheel-value {
                font-size: 13px;
                color: #1d2327;
            }

            /* Survey badges */
            .survey-type-badge.quiz {
                background: #f0f6fc;
                color: #2271b1;
                font-weight: 600;
            }

            .survey-type-badge.survey {
                background: #f0f0f1;
                color: #646970;
                font-weight: 600;
            }

            @media screen and (max-width: 1024px) {
                .user-details-grid {
                    grid-template-columns: 1fr;
                }

                .user-meta-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media screen and (max-width: 600px) {
                .user-meta-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
<?php
        return ob_get_clean();
    }

    /**
     * Modify user points
     * 
     * @return void
     */
    public function modify_user_points()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'loyalty_program_modify_points')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'loyalty-program')
            ));
        }

        // Check permissions
        if (!current_user_can('manage_loyalty_program')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'loyalty-program')
            ));
        }

        // Get parameters
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $points = isset($_POST['points']) ? absint($_POST['points']) : 0;
        $action_desc = isset($_POST['action_desc']) ? sanitize_text_field($_POST['action_desc']) : '';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';

        // Validate
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('Invalid user ID.', 'loyalty-program')
            ));
        }

        if ($points <= 0) {
            wp_send_json_error(array(
                'message' => __('Points amount must be greater than 0.', 'loyalty-program')
            ));
        }

        if (empty($action_desc)) {
            wp_send_json_error(array(
                'message' => __('Action description is required.', 'loyalty-program')
            ));
        }

        if (!in_array($type, array('add', 'remove'))) {
            wp_send_json_error(array(
                'message' => __('Invalid transaction type.', 'loyalty-program')
            ));
        }

        // Check user exists
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(array(
                'message' => __('User not found.', 'loyalty-program')
            ));
        }

        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Get current points before modification
        $points_before = Loyalty_Program_Points::get_current_points($user_id);

        Loyalty_Program_Logger::debug('Modify points request', array(
            'user_id' => $user_id,
            'type' => $type,
            'points' => $points,
            'action_desc' => $action_desc,
            'points_before' => $points_before,
        ));

        // Modify points
        $extra_data = array(
            'admin_id' => get_current_user_id(),
            'admin_name' => wp_get_current_user()->display_name,
        );

        if ($type === 'add') {
            $result = Loyalty_Program_Points::add_points($user_id, $points, $action_desc, $extra_data);
            $message = sprintf(
                __('%d points added successfully to %s.', 'loyalty-program'),
                $points,
                $user->display_name
            );
        } else {
            $result = Loyalty_Program_Points::remove_points($user_id, $points, $action_desc, $extra_data);
            $message = sprintf(
                __('%d points removed successfully from %s.', 'loyalty-program'),
                $points,
                $user->display_name
            );
        }

        // Log result
        $points_after = Loyalty_Program_Points::get_current_points($user_id);
        Loyalty_Program_Logger::debug('Modify points result', array(
            'user_id' => $user_id,
            'type' => $type,
            'points' => $points,
            'points_before' => $points_before,
            'points_after' => $points_after,
            'result' => $result,
        ));

        if ($result) {
            wp_send_json_success(array(
                'message' => $message
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to modify points.', 'loyalty-program')
            ));
        }
    }

    /**
     * Toggle user membership in loyalty program
     * 
     * @return void
     */
    public function toggle_membership()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'loyalty_program_toggle_membership')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'loyalty-program')
            ));
        }

        // Check permissions
        if (!current_user_can('manage_loyalty_program')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'loyalty-program')
            ));
        }

        // Get user ID
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;

        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('Invalid user ID.', 'loyalty-program')
            ));
        }

        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(array(
                'message' => __('User not found.', 'loyalty-program')
            ));
        }

        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check current status and toggle
        $is_member = Loyalty_Program_Points::is_member($user_id);

        if ($is_member) {
            // Unenroll
            Loyalty_Program_Points::unenroll_user($user_id);
            $message = sprintf(
                __('%s has been removed from the loyalty program.', 'loyalty-program'),
                $user->display_name
            );
        } else {
            // Enroll
            Loyalty_Program_Points::enroll_user($user_id);
            $message = sprintf(
                __('%s has been enrolled in the loyalty program.', 'loyalty-program'),
                $user->display_name
            );
        }

        wp_send_json_success(array(
            'message' => $message,
            'is_member' => !$is_member
        ));
    }

    /**
     * Generate personal coupons for all loyalty program members (batch processing)
     * 
     * @return void
     */
    public function generate_coupons_batch()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'loyalty_program_generate_coupons')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'loyalty-program')
            ));
        }

        // Check permissions
        if (!current_user_can('manage_loyalty_program')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'loyalty-program')
            ));
        }

        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            wp_send_json_error(array(
                'message' => __('WooCommerce is not active.', 'loyalty-program')
            ));
        }

        // Load WooCommerce class
        if (!class_exists('Loyalty_Program_WooCommerce')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-woocommerce.php';
        }

        // Get batch parameters
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 50;

        // Generate coupons (batch)
        $result = Loyalty_Program_WooCommerce::generate_coupons_batch($offset, $batch_size);

        // Check for errors
        if (isset($result['error']) && $result['error']) {
            wp_send_json_error(array(
                'message' => $result['message']
            ));
        }

        // Return batch result
        wp_send_json_success(array(
            'generated' => $result['generated'],
            'skipped' => $result['skipped'],
            'total' => $result['total'],
            'has_more' => $result['has_more'],
            'processed' => $result['processed'],
            'message' => sprintf(
                __('Processed %d of %d members (generated: %d, skipped: %d)...', 'loyalty-program'),
                $result['processed'],
                $result['total'],
                $result['generated'],
                $result['skipped']
            )
        ));
    }

    /**
     * Update all existing personal coupons with current settings (batch processing)
     * 
     * @return void
     */
    public function update_all_coupons()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'loyalty_program_update_coupons')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'loyalty-program')
            ));
        }

        // Check permissions
        if (!current_user_can('manage_loyalty_program')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'loyalty-program')
            ));
        }

        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            wp_send_json_error(array(
                'message' => __('WooCommerce is not active.', 'loyalty-program')
            ));
        }

        // Load WooCommerce class
        if (!class_exists('Loyalty_Program_WooCommerce')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-woocommerce.php';
        }

        // Get batch parameters
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 100;

        // Update coupons (batch)
        $result = Loyalty_Program_WooCommerce::update_all_personal_coupons($offset, $batch_size);

        // Check for errors
        if (isset($result['error']) && $result['error']) {
            wp_send_json_error(array(
                'message' => $result['message']
            ));
        }

        // Return batch result
        wp_send_json_success(array(
            'updated' => $result['updated'],
            'total' => $result['total'],
            'has_more' => $result['has_more'],
            'processed' => $result['processed'],
            'message' => sprintf(
                __('Processed %d of %d coupons...', 'loyalty-program'),
                $result['processed'],
                $result['total']
            )
        ));
    }

    /**
     * Join loyalty program from frontend
     * 
     * @return void
     */
    public function join_program_frontend()
    {
        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            wp_send_json_error(array(
                'message' => __('Loyalty program is currently disabled.', 'loyalty-program')
            ));
        }

        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'loyalty_program_frontend')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'loyalty-program')
            ));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to join the program.', 'loyalty-program')
            ));
        }

        $user_id = get_current_user_id();

        // Check if already a member
        if (Loyalty_Program_Points::is_member($user_id)) {
            wp_send_json_error(array(
                'message' => __('You are already enrolled in the loyalty program.', 'loyalty-program')
            ));
        }

        // Enroll user
        $result = Loyalty_Program_Points::enroll_user($user_id);

        if ($result) {
            $signup_points = get_option('loyalty_program_points_signup', 100);

            wp_send_json_success(array(
                'message' => sprintf(
                    __('Welcome to the loyalty program! You have been awarded %d points!', 'loyalty-program'),
                    $signup_points
                )
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to enroll in the program. Please try again.', 'loyalty-program')
            ));
        }
    }

    /**
     * Spin the wheel of fortune
     * 
     * @return void
     */
    public function spin_wheel()
    {
        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            wp_send_json_error(array(
                'message' => __('Loyalty program is currently disabled.', 'loyalty-program')
            ));
        }

        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'loyalty_program_frontend')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'loyalty-program')
            ));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to spin the wheel.', 'loyalty-program')
            ));
        }

        $user_id = get_current_user_id();

        // Check if user is a member
        if (!Loyalty_Program_Points::is_member($user_id)) {
            wp_send_json_error(array(
                'message' => __('You must be a member to spin the wheel.', 'loyalty-program')
            ));
        }

        // Get wheel configuration
        $prizes = get_option('loyalty_program_wheel_prizes', array());
        $days_between_spins = get_option('loyalty_program_wheel_days_between_spins', 7);

        // Filter enabled prizes
        $enabled_prizes = array_filter($prizes, function ($prize) {
            return isset($prize['enabled']) && $prize['enabled'] === 'yes';
        });

        if (empty($enabled_prizes)) {
            wp_send_json_error(array(
                'message' => __('No prizes available.', 'loyalty-program')
            ));
        }

        // Check if user can spin
        $last_spin = get_user_meta($user_id, 'loyalty_program_last_wheel_spin', true);

        if (!empty($last_spin)) {
            // Convert MySQL date to timestamp in WordPress timezone
            $last_spin_timestamp = mysql2date('U', $last_spin);
            $next_spin_timestamp = $last_spin_timestamp + ($days_between_spins * DAY_IN_SECONDS);
            $current_timestamp = current_time('timestamp');

            if ($current_timestamp < $next_spin_timestamp) {
                $next_spin_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_spin_timestamp);
                wp_send_json_error(array(
                    'message' => sprintf(__('You can spin again on: %s', 'loyalty-program'), $next_spin_date)
                ));
            }
        }

        // Select a prize based on probability
        $total_probability = 0;
        $enabled_prizes_indexed = array_values($enabled_prizes);

        foreach ($enabled_prizes_indexed as $prize) {
            $total_probability += isset($prize['probability']) ? floatval($prize['probability']) : 0;
        }

        // If no probabilities set, distribute evenly
        if ($total_probability <= 0) {
            $prize_count = count($enabled_prizes_indexed);
            foreach ($enabled_prizes_indexed as &$prize) {
                $prize['probability'] = 100 / $prize_count;
            }
            $total_probability = 100;
        }

        // Generate random number and select prize
        $random = mt_rand(0, $total_probability * 100) / 100;
        $cumulative = 0;
        $selected_prize = null;
        $selected_index = 0;

        foreach ($enabled_prizes_indexed as $index => $prize) {
            $cumulative += floatval($prize['probability']);
            if ($random <= $cumulative) {
                $selected_prize = $prize;
                $selected_index = $index;
                break;
            }
        }

        // Fallback to first prize if nothing selected
        if (!$selected_prize) {
            $selected_prize = $enabled_prizes_indexed[0];
            $selected_index = 0;
        }

        // Award points
        $points = absint($selected_prize['points']);
        $prize_name = sanitize_text_field($selected_prize['name']);

        Loyalty_Program_Points::add_points(
            $user_id,
            $points,
            sprintf(__('Won %d points from Wheel of Fortune: %s', 'loyalty-program'), $points, $prize_name)
        );

        // Update last spin date
        update_user_meta($user_id, 'loyalty_program_last_wheel_spin', current_time('mysql'));

        // Debug logging
        if (get_option('loyalty_program_debug_enabled', 'no') === 'yes') {
            if (!class_exists('Loyalty_Program_Logger')) {
                require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
            }

            // Log detailed wheel spin info
            $debug_info = sprintf(
                'WHEEL SPIN - User #%d | Random: %.2f | Selected Index: %d | Prize: %s | Points: %d | Total Prizes: %d',
                $user_id,
                $random,
                $selected_index,
                $prize_name,
                $points,
                count($enabled_prizes_indexed)
            );

            // Log all prizes with their probabilities
            $prizes_debug = 'PRIZES: ';
            foreach ($enabled_prizes_indexed as $idx => $prize) {
                $prizes_debug .= sprintf(
                    '[%d: %s (%d pts, %.1f%%)] ',
                    $idx,
                    $prize['name'],
                    $prize['points'],
                    isset($prize['probability']) ? $prize['probability'] : 0
                );
            }

            Loyalty_Program_Logger::info($debug_info);
            Loyalty_Program_Logger::info($prizes_debug);
        }

        wp_send_json_success(array(
            'prize_index' => $selected_index,
            'prize_name' => $prize_name,
            'points' => $points,
            'total_prizes' => count($enabled_prizes_indexed),
            'message' => sprintf(
                __('Congratulations! You won %d points: %s', 'loyalty-program'),
                $points,
                $prize_name
            )
        ));
    }

    /**
     * Reset wheel for a user (admin only)
     * 
     * @return void
     */
    public function reset_user_wheel()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'loyalty_program_ajax')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'loyalty-program')
            ));
        }

        // Check permissions
        if (!current_user_can('manage_loyalty_program')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'loyalty-program')
            ));
        }

        // Get user ID
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;

        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('Invalid user ID.', 'loyalty-program')
            ));
        }

        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(array(
                'message' => __('User not found.', 'loyalty-program')
            ));
        }

        // Reset wheel
        delete_user_meta($user_id, 'loyalty_program_last_wheel_spin');

        // Log action
        $current_user = wp_get_current_user();
        if (get_option('loyalty_program_debug_enabled', 'no') === 'yes') {
            if (!class_exists('Loyalty_Program_Logger')) {
                require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
            }
            Loyalty_Program_Logger::info(sprintf(
                'Admin %s (#%d) reset wheel for user %s (#%d)',
                $current_user->user_login,
                get_current_user_id(),
                $user->user_login,
                $user_id
            ));
        }

        wp_send_json_success(array(
            'message' => sprintf(
                __('Wheel reset successfully for %s. They can now spin again.', 'loyalty-program'),
                $user->display_name
            )
        ));
    }

    /**
     * Redeem reward - AJAX handler
     * 
     * @return void
     */
    public function redeem_reward()
    {
        // Load required classes
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            wp_send_json_error(array('message' => __('Loyalty program is currently disabled.', 'loyalty-program')));
        }

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'loyalty_program_frontend')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'loyalty-program')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to redeem rewards.', 'loyalty-program')));
        }

        $user_id = get_current_user_id();
        $reward_index = isset($_POST['reward_index']) ? intval($_POST['reward_index']) : -1;
        $reward_type = isset($_POST['reward_type']) ? sanitize_text_field($_POST['reward_type']) : 'product'; // 'product' or 'coupon'

        // Check if user is a member
        if (!Loyalty_Program_Points::is_member($user_id)) {
            wp_send_json_error(array('message' => __('You must be a member of the loyalty program.', 'loyalty-program')));
        }

        // Get rewards configuration based on type
        if ($reward_type === 'coupon') {
            $rewards = get_option('loyalty_program_coupon_rewards', array());
        } else {
            $rewards = get_option('loyalty_program_product_rewards', array());
        }

        if (empty($rewards) || !isset($rewards[$reward_index])) {
            wp_send_json_error(array('message' => __('Invalid reward.', 'loyalty-program')));
        }

        $reward = $rewards[$reward_index];

        // Verify reward is enabled
        if (!isset($reward['enabled']) || $reward['enabled'] !== 'yes') {
            wp_send_json_error(array('message' => __('This reward is not available.', 'loyalty-program')));
        }

        // Get user's current points
        $current_points = Loyalty_Program_Points::get_current_points($user_id);

        // SECURITY: Verify user has enough points (server-side validation)
        if ($current_points < $reward['points']) {
            wp_send_json_error(array('message' => __('You do not have enough points to redeem this reward.', 'loyalty-program')));
        }

        // Load logger for debugging
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Handle product or coupon reward
        if ($reward_type === 'coupon') {
            // Generate single-use coupon FIRST (before deducting points)
            if (!class_exists('Loyalty_Program_WooCommerce')) {
                require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-woocommerce.php';
            }

            Loyalty_Program_Logger::info('Attempting to generate reward coupon', array(
                'user_id' => $user_id,
                'reward_name' => $reward['name'],
                'reward_type' => $reward['type'] ?? 'unknown',
                'discount_value' => $reward['discount_value'] ?? 0,
                'min_order_amount' => $reward['min_order_amount'] ?? 0,
                'reward_data' => $reward,
            ));

            $coupon_code = Loyalty_Program_WooCommerce::generate_reward_coupon($user_id, $reward);

            if (!$coupon_code) {
                Loyalty_Program_Logger::error('Failed to generate reward coupon', array(
                    'user_id' => $user_id,
                    'reward_name' => $reward['name'],
                    'reward_data' => $reward,
                ));
                wp_send_json_error(array('message' => __('Failed to generate coupon. Please try again or contact support.', 'loyalty-program')));
            }

            Loyalty_Program_Logger::info('Reward coupon generated successfully', array(
                'user_id' => $user_id,
                'reward_name' => $reward['name'],
                'coupon_code' => $coupon_code,
            ));

            // NOW deduct points (only after successful coupon generation)
            Loyalty_Program_Points::remove_points(
                $user_id,
                $reward['points'],
                sprintf(__('Redeemed reward: %s', 'loyalty-program'), $reward['name'])
            );

            // Save redeemed coupon reward to user meta
            $redeemed_rewards = get_user_meta($user_id, 'loyalty_program_redeemed_rewards', true);
            if (!is_array($redeemed_rewards)) {
                $redeemed_rewards = array();
            }

            $unique_reward_id = $user_id . '_' . current_time('timestamp') . '_' . uniqid();

            $redeemed_rewards[] = array(
                'type' => 'coupon', // Changed from 'reward_type' to 'type' for consistency
                'reward_type' => 'coupon', // Keep for backward compatibility
                'reward_name' => $reward['name'],
                'coupon_code' => $coupon_code,
                'coupon_type' => $reward['type'] ?? 'fixed_cart',
                'discount_value' => $reward['discount_value'] ?? 0,
                'min_order_amount' => $reward['min_order_amount'] ?? 0,
                'points' => $reward['points'],
                'date' => current_time('mysql'),
                'unique_reward_id' => $unique_reward_id,
            );

            Loyalty_Program_Logger::debug('Saving redeemed coupon reward to user meta', array(
                'user_id' => $user_id,
                'redeemed_reward_data' => $redeemed_rewards[count($redeemed_rewards) - 1],
            ));

            $meta_updated = update_user_meta($user_id, 'loyalty_program_redeemed_rewards', $redeemed_rewards);

            Loyalty_Program_Logger::info('Coupon reward redeemed successfully', array(
                'user_id' => $user_id,
                'reward_name' => $reward['name'],
                'coupon_code' => $coupon_code,
                'coupon_type' => $reward['type'] ?? 'unknown',
                'discount_value' => $reward['discount_value'] ?? 0,
                'points_spent' => $reward['points'],
                'remaining_points' => Loyalty_Program_Points::get_current_points($user_id),
                'meta_updated' => $meta_updated,
                'redeemed_rewards_count' => count($redeemed_rewards),
            ));

            wp_send_json_success(array(
                'message' => sprintf(__('Coupon generated successfully! Code: %s. %s points deducted.', 'loyalty-program'), $coupon_code, html_entity_decode(number_format_i18n($reward['points']), ENT_QUOTES, 'UTF-8')),
                'remaining_points' => Loyalty_Program_Points::get_current_points($user_id),
                'coupon_code' => $coupon_code,
            ));
        } else {
            // Handle product reward
            // Get product details if WooCommerce is active
            $product_name = '';
            if (class_exists('WooCommerce') && !empty($reward['product_id'])) {
                $product = wc_get_product($reward['product_id']);
                if ($product) {
                    $product_name = $product->get_name();
                }
            }

            // Save redeemed reward to user meta FIRST
            $redeemed_rewards = get_user_meta($user_id, 'loyalty_program_redeemed_rewards', true);
            if (!is_array($redeemed_rewards)) {
                $redeemed_rewards = array();
            }

            // Create unique ID for this specific reward (user_id + timestamp)
            $unique_reward_id = $user_id . '_' . current_time('timestamp') . '_' . uniqid();

            $redeemed_rewards[] = array(
                'reward_type' => 'product',
                'reward_name' => $reward['name'],
                'product_id' => $reward['product_id'] ?? 0,
                'product_name' => $product_name,
                'points' => $reward['points'],
                'price' => $reward['price'] ?? 0,
                'date' => current_time('mysql'),
                'unique_reward_id' => $unique_reward_id,  // Unique identifier for this specific reward
            );

            update_user_meta($user_id, 'loyalty_program_redeemed_rewards', $redeemed_rewards);

            // NOW deduct points (only after successful reward save)
            Loyalty_Program_Points::remove_points(
                $user_id,
                $reward['points'],
                sprintf(__('Redeemed reward: %s', 'loyalty-program'), $reward['name'])
            );

            // Log the redemption
            if (!class_exists('Loyalty_Program_Logger')) {
                require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
            }

            Loyalty_Program_Logger::info('Product reward redeemed', array(
                'user_id' => $user_id,
                'reward_name' => $reward['name'],
                'product_id' => $reward['product_id'] ?? 0,
                'points_spent' => $reward['points'],
                'remaining_points' => Loyalty_Program_Points::get_current_points($user_id),
            ));

            wp_send_json_success(array(
                'message' => sprintf(__('Reward redeemed successfully! %s points deducted.', 'loyalty-program'), html_entity_decode(number_format_i18n($reward['points']), ENT_QUOTES, 'UTF-8')),
                'remaining_points' => Loyalty_Program_Points::get_current_points($user_id),
            ));
        }
    }

    /**
     * Add reward to cart - AJAX handler
     * 
     * @return void
     */
    public function add_reward_to_cart()
    {
        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            wp_send_json_error(array('message' => __('Loyalty program is currently disabled.', 'loyalty-program')));
        }

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'loyalty_program_frontend')) {
            Loyalty_Program_Logger::error('Nonce verification failed for add_reward_to_cart');
            wp_send_json_error(array('message' => __('Security check failed.', 'loyalty-program')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'loyalty-program')));
        }

        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            wp_send_json_error(array('message' => __('WooCommerce is not active.', 'loyalty-program')));
        }

        $user_id = get_current_user_id();
        $unique_reward_id = isset($_POST['unique_reward_id']) ? sanitize_text_field($_POST['unique_reward_id']) : '';

        Loyalty_Program_Logger::debug('Add reward to cart request', array(
            'user_id' => $user_id,
            'unique_reward_id' => $unique_reward_id,
        ));

        if (empty($unique_reward_id)) {
            Loyalty_Program_Logger::error('Empty unique_reward_id', array('user_id' => $user_id));
            wp_send_json_error(array('message' => __('Invalid reward ID.', 'loyalty-program')));
        }

        // SECURITY: Get reward data from user's redeemed rewards (server-side)
        // DO NOT trust product_id or price from client!
        $redeemed_rewards = get_user_meta($user_id, 'loyalty_program_redeemed_rewards', true);

        if (!is_array($redeemed_rewards)) {
            Loyalty_Program_Logger::error('No redeemed rewards found', array('user_id' => $user_id));
            wp_send_json_error(array('message' => __('No rewards found.', 'loyalty-program')));
        }

        // Find reward by unique_reward_id
        $reward = null;
        $reward_index = null;
        foreach ($redeemed_rewards as $index => $item) {
            if (isset($item['unique_reward_id']) && $item['unique_reward_id'] === $unique_reward_id) {
                $reward = $item;
                $reward_index = $index;
                break;
            }
        }

        if (!$reward) {
            Loyalty_Program_Logger::error('Reward not found by unique_reward_id', array(
                'user_id' => $user_id,
                'unique_reward_id' => $unique_reward_id,
                'total_rewards' => count($redeemed_rewards),
            ));
            wp_send_json_error(array('message' => __('Invalid reward. This reward does not exist.', 'loyalty-program')));
        }

        Loyalty_Program_Logger::debug('Reward found', array(
            'reward_index' => $reward_index,
            'reward_name' => $reward['reward_name'] ?? 'N/A',
            'product_id' => $reward['product_id'] ?? 0,
        ));

        // Check if reward is already used
        if (isset($reward['used']) && $reward['used'] === 'yes') {
            Loyalty_Program_Logger::warning('Attempt to add used reward', array(
                'user_id' => $user_id,
                'unique_reward_id' => $unique_reward_id,
            ));
            wp_send_json_error(array('message' => __('This reward has already been used.', 'loyalty-program')));
        }

        // Get product_id and price from DATABASE (not from client!)
        $product_id = isset($reward['product_id']) ? intval($reward['product_id']) : 0;
        $price = isset($reward['price']) ? floatval($reward['price']) : 0;

        Loyalty_Program_Logger::debug('Reward details', array(
            'product_id' => $product_id,
            'price' => $price,
            'reward_name' => $reward['reward_name'] ?? 'N/A',
        ));

        if (!$product_id) {
            Loyalty_Program_Logger::error('Invalid product_id', array(
                'user_id' => $user_id,
                'unique_reward_id' => $unique_reward_id,
            ));
            wp_send_json_error(array('message' => __('Invalid product.', 'loyalty-program')));
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            Loyalty_Program_Logger::error('Product not found in WooCommerce', array(
                'user_id' => $user_id,
                'product_id' => $product_id,
                'unique_reward_id' => $unique_reward_id,
            ));
            wp_send_json_error(array('message' => __('Product not found.', 'loyalty-program')));
        }

        Loyalty_Program_Logger::debug('Product found', array(
            'product_name' => $product->get_name(),
            'is_in_stock' => $product->is_in_stock(),
        ));

        // Check if this specific reward (by unique_reward_id) is already in cart
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (
                isset($cart_item['gift_from_loyalty_program']) &&
                $cart_item['gift_from_loyalty_program'] === 'yes' &&
                isset($cart_item['unique_reward_id']) &&
                $cart_item['unique_reward_id'] === $unique_reward_id
            ) {
                Loyalty_Program_Logger::warning('Reward already in cart', array(
                    'user_id' => $user_id,
                    'unique_reward_id' => $unique_reward_id,
                ));
                wp_send_json_error(array('message' => __('This reward is already in your cart.', 'loyalty-program')));
            }
        }

        // Add to cart with VALIDATED data from database
        $cart_item_data = array(
            'loyalty_reward_price' => $price,  // From database
            'gift_from_loyalty_program' => 'yes',
            'unique_reward_id' => $unique_reward_id,  // Unique ID for this specific reward
            'loyalty_reward_index' => $reward_index,
            'loyalty_user_id' => $user_id,
        );

        Loyalty_Program_Logger::debug('Adding to cart', array(
            'product_id' => $product_id,
            'cart_item_data' => $cart_item_data,
        ));

        $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);

        if (!$cart_item_key) {
            Loyalty_Program_Logger::error('Failed to add product to cart', array(
                'user_id' => $user_id,
                'product_id' => $product_id,
                'unique_reward_id' => $unique_reward_id,
            ));
            wp_send_json_error(array('message' => __('Could not add product to cart.', 'loyalty-program')));
        }

        Loyalty_Program_Logger::info('Reward successfully added to cart', array(
            'user_id' => $user_id,
            'product_id' => $product_id,
            'product_name' => $product->get_name(),
            'price' => $price,
            'reward_index' => $reward_index,
            'unique_reward_id' => $unique_reward_id,
            'reward_name' => $reward['reward_name'] ?? '',
            'cart_item_key' => $cart_item_key,
        ));

        wp_send_json_success(array(
            'message' => sprintf(__('%s added to cart!', 'loyalty-program'), $product->get_name()),
            'cart_url' => wc_get_cart_url(),
        ));
    }

    /**
     * Save user consents (SMS and Newsletter)
     * 
     * @return void
     */
    public function save_consents()
    {
        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            wp_send_json_error(array('message' => __('Loyalty program is currently disabled.', 'loyalty-program')));
        }

        // Check nonce
        if (!check_ajax_referer('loyalty_save_consents', 'nonce', false)) {
            Loyalty_Program_Logger::error('Nonce verification failed for save consents');
            wp_send_json_error(array(
                'message' => __('Security check failed. Please refresh the page and try again.', 'loyalty-program')
            ));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to update consent preferences.', 'loyalty-program')
            ));
        }

        $user_id = get_current_user_id();

        // Check if user is a member
        if (!Loyalty_Program_Points::is_member($user_id)) {
            wp_send_json_error(array(
                'message' => __('You need to join the loyalty program first.', 'loyalty-program')
            ));
        }

        // Get consent values from POST
        $sms_consent = isset($_POST['sms_consent']) && $_POST['sms_consent'] === 'yes' ? 'yes' : 'no';
        $newsletter_consent = isset($_POST['newsletter_consent']) && $_POST['newsletter_consent'] === 'yes' ? 'yes' : 'no';

        // Get previous values
        $previous_sms = get_user_meta($user_id, 'loyalty_program_sms_consent', true);
        $previous_newsletter = get_user_meta($user_id, 'loyalty_program_newsletter_consent', true);

        // Update consents
        update_user_meta($user_id, 'loyalty_program_sms_consent', $sms_consent);
        update_user_meta($user_id, 'loyalty_program_newsletter_consent', $newsletter_consent);

        Loyalty_Program_Logger::info('User consents updated via shortcode', array(
            'user_id' => $user_id,
            'sms_consent' => $sms_consent,
            'newsletter_consent' => $newsletter_consent,
            'previous_sms' => $previous_sms,
            'previous_newsletter' => $previous_newsletter,
        ));

        // Check if profile is complete and award points if applicable
        if (class_exists('Loyalty_Program_WooCommerce')) {
            $woocommerce = new Loyalty_Program_WooCommerce();
            // Use reflection to call private method
            $reflection = new ReflectionClass($woocommerce);
            $method = $reflection->getMethod('check_and_award_profile_completion_points');
            $method->setAccessible(true);
            $method->invoke($woocommerce, $user_id);
        }

        // Sync consents with SalesManago
        if (!class_exists('Loyalty_Program_SalesManago')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/integrations/class-loyalty-program-salesmanago.php';
        }

        if (Loyalty_Program_SalesManago::is_enabled()) {
            $user = get_userdata($user_id);
            if ($user) {
                $email = $user->user_email;
                $first_name = get_user_meta($user_id, 'first_name', true);
                $last_name = get_user_meta($user_id, 'last_name', true);
                $phone = get_user_meta($user_id, 'billing_phone', true);
                $birth_date = get_user_meta($user_id, 'loyalty_program_birth_date', true);

                // Prepare contact data
                $contact_data = array(
                    'name' => $first_name,
                    'lastName' => $last_name,
                    'phone' => $phone,
                    'birthday' => $birth_date ? date('Y-m-d', strtotime($birth_date)) : null,
                    'streetAddress' => get_user_meta($user_id, 'billing_address_1', true),
                    'zipCode' => get_user_meta($user_id, 'billing_postcode', true),
                    'city' => get_user_meta($user_id, 'billing_city', true),
                    'country' => get_user_meta($user_id, 'billing_country', true),
                    'province' => get_user_meta($user_id, 'billing_state', true),
                );

                // Prepare consents
                $consents = array(
                    'sms' => $sms_consent === 'yes',
                    'newsletter' => $newsletter_consent === 'yes',
                );

                Loyalty_Program_Logger::info('🔄 Aktualizacja zgód użytkownika - Shortcode [loyalty_consents]', array(
                    'user_id' => $user_id,
                    'email' => $email,
                    'sms_consent' => $sms_consent,
                    'newsletter_consent' => $newsletter_consent,
                    'trigger' => 'Shortcode consent form',
                ));

                // Sync with SalesManago
                $result = Loyalty_Program_SalesManago::upsert_contact(
                    $email,
                    $contact_data,
                    array('Program lojalnościowy'),
                    $consents
                );

                if ($result['success']) {
                    Loyalty_Program_Logger::info('✅ Zgody zsynchronizowane z SalesManago', array(
                        'user_id' => $user_id,
                        'email' => $email,
                        'contact_id' => isset($result['contactId']) ? $result['contactId'] : null,
                        'source' => 'Shortcode [loyalty_consents]',
                    ));
                } else {
                    Loyalty_Program_Logger::error('❌ Błąd synchronizacji zgód z SalesManago', array(
                        'user_id' => $user_id,
                        'email' => $email,
                        'error' => $result['message'],
                        'source' => 'Shortcode [loyalty_consents]',
                    ));
                }
            }
        }

        wp_send_json_success(array(
            'message' => __('Your consent preferences have been saved successfully!', 'loyalty-program'),
            'sms_consent' => $sms_consent,
            'newsletter_consent' => $newsletter_consent,
        ));
    }

    /**
     * Save user birth date and award points (only once)
     * 
     * @return void
     */
    public function save_birth_date()
    {
        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            wp_send_json_error(array('message' => __('Loyalty program is currently disabled.', 'loyalty-program')));
        }

        // Check nonce
        if (!check_ajax_referer('loyalty_save_birth_date', 'nonce', false)) {
            Loyalty_Program_Logger::error('Nonce verification failed for save birth date');
            wp_send_json_error(array(
                'message' => __('Security check failed. Please refresh the page and try again.', 'loyalty-program')
            ));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to update your birth date.', 'loyalty-program')
            ));
        }

        $user_id = get_current_user_id();

        // Check if user is a member
        if (!Loyalty_Program_Points::is_member($user_id)) {
            wp_send_json_error(array(
                'message' => __('You need to join the loyalty program first.', 'loyalty-program')
            ));
        }

        // Check if birth date already exists
        $existing_birth_date = get_user_meta($user_id, 'loyalty_program_birth_date', true);
        if (!empty($existing_birth_date)) {
            wp_send_json_error(array(
                'message' => __('You have already set your birth date.', 'loyalty-program')
            ));
        }

        // Get birth date from POST
        $birth_date = isset($_POST['birth_date']) ? sanitize_text_field($_POST['birth_date']) : '';

        // Validate date format
        if (empty($birth_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
            wp_send_json_error(array(
                'message' => __('Please provide a valid birth date.', 'loyalty-program')
            ));
        }

        // Validate date is not in future
        $date_obj = DateTime::createFromFormat('Y-m-d', $birth_date);
        $today = new DateTime();
        if (!$date_obj || $date_obj > $today) {
            wp_send_json_error(array(
                'message' => __('Birth date cannot be in the future.', 'loyalty-program')
            ));
        }

        // Save birth date
        update_user_meta($user_id, 'loyalty_program_birth_date', $birth_date);

        Loyalty_Program_Logger::info('User birth date saved via shortcode', array(
            'user_id' => $user_id,
            'birth_date' => $birth_date,
        ));

        // Check if points already awarded
        $points_awarded = get_user_meta($user_id, 'loyalty_program_birthday_points_awarded', true);

        $points_given = 0;
        if ($points_awarded !== 'yes') {
            // Get birthday points value
            $birthday_points = get_option('loyalty_program_points_birthday', 25);

            if ($birthday_points > 0) {
                // Award points
                Loyalty_Program_Points::add_points(
                    $user_id,
                    $birthday_points,
                    __('Birth date completed', 'loyalty-program'),
                    array(
                        'type' => 'birthday',
                        'birth_date' => $birth_date,
                    )
                );

                // Mark as awarded
                update_user_meta($user_id, 'loyalty_program_birthday_points_awarded', 'yes');
                $points_given = $birthday_points;

                Loyalty_Program_Logger::info('Birthday points awarded', array(
                    'user_id' => $user_id,
                    'points' => $birthday_points,
                    'birth_date' => $birth_date,
                ));
            }
        }

        // Check if profile is complete and award profile completion points
        if (class_exists('Loyalty_Program_WooCommerce')) {
            $woocommerce = new Loyalty_Program_WooCommerce();
            // Use reflection to call private method
            $reflection = new ReflectionClass($woocommerce);
            $method = $reflection->getMethod('check_and_award_profile_completion_points');
            $method->setAccessible(true);
            $method->invoke($woocommerce, $user_id);
        }

        // Sync birth date with SalesManago
        if (!class_exists('Loyalty_Program_SalesManago')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/integrations/class-loyalty-program-salesmanago.php';
        }

        if (Loyalty_Program_SalesManago::is_enabled()) {
            $user = get_userdata($user_id);
            if ($user) {
                $email = $user->user_email;
                $first_name = get_user_meta($user_id, 'first_name', true);
                $last_name = get_user_meta($user_id, 'last_name', true);
                $phone = get_user_meta($user_id, 'billing_phone', true);
                $sms_consent = get_user_meta($user_id, 'loyalty_program_sms_consent', true) === 'yes';
                $newsletter_consent = get_user_meta($user_id, 'loyalty_program_newsletter_consent', true) === 'yes';

                // Prepare contact data
                $contact_data = array(
                    'name' => $first_name,
                    'lastName' => $last_name,
                    'phone' => $phone,
                    'birthday' => date('Y-m-d', strtotime($birth_date)),
                    'streetAddress' => get_user_meta($user_id, 'billing_address_1', true),
                    'zipCode' => get_user_meta($user_id, 'billing_postcode', true),
                    'city' => get_user_meta($user_id, 'billing_city', true),
                    'country' => get_user_meta($user_id, 'billing_country', true),
                    'province' => get_user_meta($user_id, 'billing_state', true),
                );

                // Prepare consents
                $consents = array(
                    'sms' => $sms_consent,
                    'newsletter' => $newsletter_consent,
                );

                Loyalty_Program_Logger::info('🔄 Aktualizacja daty urodzenia - Shortcode [loyalty_birth_date]', array(
                    'user_id' => $user_id,
                    'email' => $email,
                    'birth_date' => $birth_date,
                    'trigger' => 'Shortcode birth date form',
                ));

                // Sync with SalesManago
                $result = Loyalty_Program_SalesManago::upsert_contact(
                    $email,
                    $contact_data,
                    array('Program lojalnościowy'),
                    $consents
                );

                if ($result['success']) {
                    Loyalty_Program_Logger::info('✅ Data urodzenia zsynchronizowana z SalesManago', array(
                        'user_id' => $user_id,
                        'email' => $email,
                        'contact_id' => isset($result['contactId']) ? $result['contactId'] : null,
                        'birth_date' => $birth_date,
                        'source' => 'Shortcode [loyalty_birth_date]',
                    ));
                } else {
                    Loyalty_Program_Logger::error('❌ Błąd synchronizacji daty urodzenia z SalesManago', array(
                        'user_id' => $user_id,
                        'email' => $email,
                        'birth_date' => $birth_date,
                        'error' => $result['message'],
                        'source' => 'Shortcode [loyalty_birth_date]',
                    ));
                }
            }
        }

        $message = $points_given > 0
            ? sprintf(__('Your birth date has been saved successfully! You earned %d loyalty points!', 'loyalty-program'), $points_given)
            : __('Your birth date has been saved successfully!', 'loyalty-program');

        wp_send_json_success(array(
            'message' => $message,
            'birth_date' => $birth_date,
            'points_awarded' => $points_given,
            'reload' => true, // Signal to reload the page to show completion view
        ));
    }

    /**
     * Save account data (birth date and phone) via AJAX
     * 
     * @return void
     */
    public function save_account_data()
    {
        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            wp_send_json_error(array('message' => __('Loyalty program is currently disabled.', 'loyalty-program')));
        }

        // Check nonce
        if (!check_ajax_referer('loyalty_program_frontend', 'nonce', false)) {
            Loyalty_Program_Logger::error('Nonce verification failed for save account data');
            wp_send_json_error(array(
                'message' => __('Security check failed. Please refresh the page and try again.', 'loyalty-program')
            ));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to update your account data.', 'loyalty-program')
            ));
        }

        $user_id = get_current_user_id();

        // Check if user is a member
        if (!Loyalty_Program_Points::is_member($user_id)) {
            wp_send_json_error(array(
                'message' => __('You need to join the loyalty program first.', 'loyalty-program')
            ));
        }

        $updated_fields = array();
        $points_given = 0;

        // Save birth date if provided
        if (isset($_POST['loyalty_program_birth_date']) && !empty($_POST['loyalty_program_birth_date'])) {
            $birth_date = sanitize_text_field($_POST['loyalty_program_birth_date']);

            // Validate date format
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
                // Validate date is not in future
                $date_obj = DateTime::createFromFormat('Y-m-d', $birth_date);
                $today = new DateTime();
                if ($date_obj && $date_obj <= $today) {
                    $existing_birth_date = get_user_meta($user_id, 'loyalty_program_birth_date', true);
                    
                    // Save birth date
                    update_user_meta($user_id, 'loyalty_program_birth_date', $birth_date);
                    update_user_meta($user_id, 'birth_date', $birth_date); // Also save in standard field
                    
                    $updated_fields[] = 'birth_date';

                    // Award birthday points if not already awarded
                    if (empty($existing_birth_date)) {
                        $points_awarded = get_user_meta($user_id, 'loyalty_program_birthday_points_awarded', true);
                        if ($points_awarded !== 'yes') {
                            $birthday_points = get_option('loyalty_program_points_birthday', 25);
                            if ($birthday_points > 0) {
                                Loyalty_Program_Points::add_points(
                                    $user_id,
                                    $birthday_points,
                                    __('Birth date completed', 'loyalty-program'),
                                    array(
                                        'type' => 'birthday',
                                        'birth_date' => $birth_date,
                                    )
                                );
                                update_user_meta($user_id, 'loyalty_program_birthday_points_awarded', 'yes');
                                $points_given = $birthday_points;
                            }
                        }
                    }

                    Loyalty_Program_Logger::info('User birth date saved via account data form', array(
                        'user_id' => $user_id,
                        'birth_date' => $birth_date,
                    ));
                }
            }
        }

        // Save phone number if provided
        if (isset($_POST['billing_phone'])) {
            $billing_phone = sanitize_text_field($_POST['billing_phone']);
            update_user_meta($user_id, 'billing_phone', $billing_phone);
            $updated_fields[] = 'phone';

            Loyalty_Program_Logger::info('User phone number saved via account data form', array(
                'user_id' => $user_id,
                'phone' => $billing_phone,
            ));
        }

        // Check if profile is complete and award profile completion points
        if (class_exists('Loyalty_Program_WooCommerce')) {
            $woocommerce = new Loyalty_Program_WooCommerce();
            // Use reflection to call private method
            $reflection = new ReflectionClass($woocommerce);
            $method = $reflection->getMethod('check_and_award_profile_completion_points');
            $method->setAccessible(true);
            $method->invoke($woocommerce, $user_id);
        }

        // Sync with SalesManago if enabled
        if (!class_exists('Loyalty_Program_SalesManago')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/integrations/class-loyalty-program-salesmanago.php';
        }

        if (Loyalty_Program_SalesManago::is_enabled() && !empty($updated_fields)) {
            $user = get_userdata($user_id);
            if ($user) {
                $email = $user->user_email;
                $first_name = get_user_meta($user_id, 'first_name', true);
                $last_name = get_user_meta($user_id, 'last_name', true);
                $phone = get_user_meta($user_id, 'billing_phone', true);
                $sms_consent = get_user_meta($user_id, 'loyalty_program_sms_consent', true) === 'yes';
                $newsletter_consent = get_user_meta($user_id, 'loyalty_program_newsletter_consent', true) === 'yes';
                $birth_date = get_user_meta($user_id, 'loyalty_program_birth_date', true);

                $consents = array(
                    'sms' => $sms_consent,
                    'newsletter' => $newsletter_consent,
                );

                $contact_data = array(
                    'name' => $first_name,
                    'lastName' => $last_name,
                    'phone' => $phone,
                );

                if (!empty($birth_date)) {
                    $contact_data['birthday'] = date('Y-m-d', strtotime($birth_date));
                }

                Loyalty_Program_SalesManago::upsert_contact(
                    $email,
                    $contact_data,
                    array('Program lojalnościowy'),
                    $consents
                );
            }
        }

        $message = __('Your account data has been saved successfully!', 'loyalty-program');
        if ($points_given > 0) {
            $message = sprintf(__('Your account data has been saved successfully! You earned %d loyalty points!', 'loyalty-program'), $points_given);
        }

        wp_send_json_success(array(
            'message' => $message,
            'points_awarded' => $points_given,
            'updated_fields' => $updated_fields,
        ));
    }

    /**
     * Save account data (birth date, phone, consents) for non-members via AJAX
     * 
     * @return void
     */
    public function save_account_data_non_member()
    {
        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            wp_send_json_error(array('message' => __('Loyalty program is currently disabled.', 'loyalty-program')));
        }

        // Check nonce
        if (!check_ajax_referer('loyalty_program_frontend', 'nonce', false)) {
            Loyalty_Program_Logger::error('Nonce verification failed for save account data non-member');
            wp_send_json_error(array(
                'message' => __('Security check failed. Please refresh the page and try again.', 'loyalty-program')
            ));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to update your account data.', 'loyalty-program')
            ));
        }

        $user_id = get_current_user_id();
        $user_joined = false;
        $updated_fields = array();
        $points_given = 0;

        // Validate required join consent
        $join_consent = isset($_POST['join_consent']) && $_POST['join_consent'] === 'yes' ? 'yes' : 'no';
        if ($join_consent !== 'yes') {
            wp_send_json_error(array(
                'message' => __('You must consent to joining the loyalty program.', 'loyalty-program')
            ));
        }

        // If user is not a member and gave consent, enroll them
        if (!Loyalty_Program_Points::is_member($user_id)) {
            Loyalty_Program_Points::enroll_user($user_id);
            $user_joined = true;
            
            // Award signup points
            $signup_points = get_option('loyalty_program_points_signup', 100);
            if ($signup_points > 0) {
                Loyalty_Program_Points::add_points(
                    $user_id,
                    $signup_points,
                    __('Joined the loyalty program', 'loyalty-program'),
                    array(
                        'type' => 'signup',
                    )
                );
                $points_given += $signup_points;
            }
        }

        // Save birth date if provided
        if (isset($_POST['loyalty_program_birth_date']) && !empty($_POST['loyalty_program_birth_date'])) {
            $birth_date = sanitize_text_field($_POST['loyalty_program_birth_date']);

            // Validate date format
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
                // Validate date is not in future
                $date_obj = DateTime::createFromFormat('Y-m-d', $birth_date);
                $today = new DateTime();
                if ($date_obj && $date_obj <= $today) {
                    $existing_birth_date = get_user_meta($user_id, 'loyalty_program_birth_date', true);
                    
                    // Save birth date
                    update_user_meta($user_id, 'loyalty_program_birth_date', $birth_date);
                    update_user_meta($user_id, 'birth_date', $birth_date);
                    
                    $updated_fields[] = 'birth_date';

                    // Award birthday points if not already awarded
                    if (empty($existing_birth_date)) {
                        $points_awarded = get_user_meta($user_id, 'loyalty_program_birthday_points_awarded', true);
                        if ($points_awarded !== 'yes') {
                            $birthday_points = get_option('loyalty_program_points_birthday', 25);
                            if ($birthday_points > 0) {
                                Loyalty_Program_Points::add_points(
                                    $user_id,
                                    $birthday_points,
                                    __('Birth date completed', 'loyalty-program'),
                                    array(
                                        'type' => 'birthday',
                                        'birth_date' => $birth_date,
                                    )
                                );
                                update_user_meta($user_id, 'loyalty_program_birthday_points_awarded', 'yes');
                                $points_given += $birthday_points;
                            }
                        }
                    }

                    Loyalty_Program_Logger::info('User birth date saved via account data form (non-member)', array(
                        'user_id' => $user_id,
                        'birth_date' => $birth_date,
                    ));
                }
            }
        }

        // Save phone number if provided
        if (isset($_POST['billing_phone'])) {
            $billing_phone = sanitize_text_field($_POST['billing_phone']);
            if (!empty($billing_phone)) {
                update_user_meta($user_id, 'billing_phone', $billing_phone);
                $updated_fields[] = 'phone';

                Loyalty_Program_Logger::info('User phone number saved via account data form (non-member)', array(
                    'user_id' => $user_id,
                    'phone' => $billing_phone,
                ));
            }
        }

        // Get consent values from POST
        $sms_consent = isset($_POST['sms_consent']) && $_POST['sms_consent'] === 'yes' ? 'yes' : 'no';
        $newsletter_consent = isset($_POST['newsletter_consent']) && $_POST['newsletter_consent'] === 'yes' ? 'yes' : 'no';

        // Get previous values
        $previous_sms = get_user_meta($user_id, 'loyalty_program_sms_consent', true);
        $previous_newsletter = get_user_meta($user_id, 'loyalty_program_newsletter_consent', true);

        // Update consents
        update_user_meta($user_id, 'loyalty_program_sms_consent', $sms_consent);
        update_user_meta($user_id, 'loyalty_program_newsletter_consent', $newsletter_consent);

        // Award points for consents if changed from 'no' to 'yes'
        if ($newsletter_consent === 'yes' && $previous_newsletter !== 'yes') {
            $newsletter_points = get_option('loyalty_program_points_notifications', 20);
            if ($newsletter_points > 0) {
                $points_awarded_meta = get_user_meta($user_id, 'loyalty_program_newsletter_points_awarded', true);
                if ($points_awarded_meta !== 'yes') {
                    Loyalty_Program_Points::add_points(
                        $user_id,
                        $newsletter_points,
                        __('Newsletter consent given', 'loyalty-program'),
                        array(
                            'type' => 'newsletter_consent',
                        )
                    );
                    update_user_meta($user_id, 'loyalty_program_newsletter_points_awarded', 'yes');
                    $points_given += $newsletter_points;
                }
            }
        }

        if ($sms_consent === 'yes' && $previous_sms !== 'yes') {
            $sms_points = get_option('loyalty_program_points_notifications', 20);
            if ($sms_points > 0) {
                $points_awarded_meta = get_user_meta($user_id, 'loyalty_program_sms_points_awarded', true);
                if ($points_awarded_meta !== 'yes') {
                    Loyalty_Program_Points::add_points(
                        $user_id,
                        $sms_points,
                        __('SMS consent given', 'loyalty-program'),
                        array(
                            'type' => 'sms_consent',
                        )
                    );
                    update_user_meta($user_id, 'loyalty_program_sms_points_awarded', 'yes');
                    $points_given += $sms_points;
                }
            }
        }

        Loyalty_Program_Logger::info('User consents updated via account data form (non-member)', array(
            'user_id' => $user_id,
            'sms_consent' => $sms_consent,
            'newsletter_consent' => $newsletter_consent,
            'previous_sms' => $previous_sms,
            'previous_newsletter' => $previous_newsletter,
        ));

        // Check if profile is complete and award profile completion points
        if (class_exists('Loyalty_Program_WooCommerce')) {
            $woocommerce = new Loyalty_Program_WooCommerce();
            // Use reflection to call private method
            $reflection = new ReflectionClass($woocommerce);
            $method = $reflection->getMethod('check_and_award_profile_completion_points');
            $method->setAccessible(true);
            $method->invoke($woocommerce, $user_id);
        }

        // Sync with SalesManago if enabled
        if (!class_exists('Loyalty_Program_SalesManago')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/integrations/class-loyalty-program-salesmanago.php';
        }

        if (Loyalty_Program_SalesManago::is_enabled()) {
            $user = get_userdata($user_id);
            if ($user) {
                $email = $user->user_email;
                $first_name = get_user_meta($user_id, 'first_name', true);
                $last_name = get_user_meta($user_id, 'last_name', true);
                $phone = get_user_meta($user_id, 'billing_phone', true);
                $birth_date = get_user_meta($user_id, 'loyalty_program_birth_date', true);

                $consents = array(
                    'sms' => $sms_consent === 'yes',
                    'newsletter' => $newsletter_consent === 'yes',
                );

                $contact_data = array(
                    'name' => $first_name,
                    'lastName' => $last_name,
                    'phone' => $phone,
                );

                if (!empty($birth_date)) {
                    $contact_data['birthday'] = date('Y-m-d', strtotime($birth_date));
                }

                Loyalty_Program_SalesManago::upsert_contact(
                    $email,
                    $contact_data,
                    array('Program lojalnościowy'),
                    $consents
                );
            }
        }

        $message = __('Your account data has been saved successfully!', 'loyalty-program');
        if ($points_given > 0) {
            $message = sprintf(__('Your account data has been saved successfully! You earned %d loyalty points!', 'loyalty-program'), $points_given);
        }

        wp_send_json_success(array(
            'message' => $message,
            'points_awarded' => $points_given,
            'updated_fields' => $updated_fields,
            'user_joined' => $user_joined,
        ));
    }

    /**
     * Submit survey/quiz and award points
     * 
     * @return void
     */
    public function submit_survey()
    {
        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            wp_send_json_error(array('message' => __('Loyalty program is currently disabled.', 'loyalty-program')));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to submit this survey.', 'loyalty-program')
            ));
        }

        $user_id = get_current_user_id();

        // Check if user is a member
        if (!Loyalty_Program_Points::is_member($user_id)) {
            wp_send_json_error(array(
                'message' => __('You need to join the loyalty program first.', 'loyalty-program')
            ));
        }

        $survey_id = isset($_POST['survey_id']) ? sanitize_text_field($_POST['survey_id']) : '';

        if (empty($survey_id)) {
            wp_send_json_error(array(
                'message' => __('Survey ID is missing.', 'loyalty-program')
            ));
        }

        // Verify nonce
        if (!check_ajax_referer('loyalty_submit_survey_' . $survey_id, 'nonce', false)) {
            Loyalty_Program_Logger::error('Nonce verification failed for submit survey');
            wp_send_json_error(array(
                'message' => __('Security check failed. Please refresh the page and try again.', 'loyalty-program')
            ));
        }

        // Get survey data
        $surveys = get_option('loyalty_program_surveys', array());
        $survey = null;

        foreach ($surveys as $s) {
            if ($s['id'] === $survey_id) {
                $survey = $s;
                break;
            }
        }

        if (!$survey) {
            wp_send_json_error(array(
                'message' => __('Survey not found.', 'loyalty-program')
            ));
        }

        // Check if already completed
        $completed_surveys = get_user_meta($user_id, 'loyalty_program_completed_surveys', true);
        if (!is_array($completed_surveys)) {
            $completed_surveys = array();
        }

        if (in_array($survey_id, $completed_surveys)) {
            wp_send_json_error(array(
                'message' => __('You have already completed this survey.', 'loyalty-program')
            ));
        }

        // Parse answers
        parse_str($_POST['answers'], $answers_data);
        $answers = isset($answers_data['answers']) ? $answers_data['answers'] : array();

        // Initialize variables
        $quiz_points = 0;
        $correct_answers_count = 0;
        $total_questions_count = 0;
        $score_percentage = 0;

        // Calculate quiz score if applicable
        $is_quiz = $survey['type'] === 'quiz';

        if ($is_quiz) {
            // Count questions with radio/checkbox answers (scorable questions)
            foreach ($survey['questions'] as $question) {
                if (in_array($question['answerType'], array('radio', 'checkbox'))) {
                    $total_questions_count++;
                }
            }

            // Calculate correct answers
            foreach ($survey['questions'] as $q_index => $question) {
                // Skip non-scorable question types
                if (!in_array($question['answerType'], array('radio', 'checkbox'))) {
                    continue;
                }

                if (!isset($answers[$q_index])) {
                    continue;
                }

                $user_answer = $answers[$q_index];
                $question_correct = false;

                // Handle multiple choice (checkbox)
                if (is_array($user_answer)) {
                    $question_correct = true; // Assume correct until proven wrong

                    // Check if all selected answers are correct
                    foreach ($user_answer as $answer_index) {
                        if (isset($question['answers'][$answer_index])) {
                            $answer = $question['answers'][$answer_index];
                            if (!isset($answer['correct']) || !$answer['correct']) {
                                $question_correct = false;
                                break;
                            }
                            if (isset($answer['points'])) {
                                $quiz_points += intval($answer['points']);
                            }
                        }
                    }

                    // Also check if any correct answers were not selected
                    if ($question_correct) {
                        foreach ($question['answers'] as $a_index => $answer) {
                            if (isset($answer['correct']) && $answer['correct'] && !in_array($a_index, $user_answer)) {
                                $question_correct = false;
                                break;
                            }
                        }
                    }
                } else {
                    // Single choice (radio)
                    $answer_index = intval($user_answer);
                    if (isset($question['answers'][$answer_index])) {
                        $answer = $question['answers'][$answer_index];
                        if (isset($answer['correct']) && $answer['correct']) {
                            $question_correct = true;
                            if (isset($answer['points'])) {
                                $quiz_points += intval($answer['points']);
                            }
                        }
                    }
                }

                if ($question_correct) {
                    $correct_answers_count++;
                }
            }

            // Calculate percentage
            if ($total_questions_count > 0) {
                $score_percentage = round(($correct_answers_count / $total_questions_count) * 100);
            }
        }

        // Check minimum score requirement
        $min_percentage = isset($survey['settings']['minScorePercentage']) ? intval($survey['settings']['minScorePercentage']) : 0;
        $minimum_reached = !$is_quiz || ($score_percentage >= $min_percentage);

        // Calculate total points to award
        $total_points_awarded = 0;
        $completion_points = isset($survey['settings']['completionPoints']) ? intval($survey['settings']['completionPoints']) : 0;

        // Always award completion points
        if ($completion_points > 0) {
            $total_points_awarded += $completion_points;
        }

        // Award quiz points only if minimum reached
        if ($is_quiz && $minimum_reached && $quiz_points > 0) {
            $total_points_awarded += $quiz_points;
        }

        // Save answers to user meta
        $user_surveys = get_user_meta($user_id, 'loyalty_program_survey_responses', true);
        if (!is_array($user_surveys)) {
            $user_surveys = array();
        }

        $user_surveys[$survey_id] = array(
            'completed_at' => current_time('mysql'),
            'answers' => $answers,
            'points_earned' => $total_points_awarded,
            'score_percentage' => $is_quiz ? $score_percentage : null,
            'correct_answers' => $is_quiz ? $correct_answers_count : null,
            'total_questions' => $is_quiz ? $total_questions_count : null,
        );

        update_user_meta($user_id, 'loyalty_program_survey_responses', $user_surveys);

        // Mark as completed
        $completed_surveys[] = $survey_id;
        update_user_meta($user_id, 'loyalty_program_completed_surveys', $completed_surveys);

        // Award points
        if ($total_points_awarded > 0) {
            $reason_parts = array();

            if ($completion_points > 0) {
                $reason_parts[] = sprintf(__('Completion: %d pts', 'loyalty-program'), $completion_points);
            }

            if ($is_quiz && $minimum_reached && $quiz_points > 0) {
                $reason_parts[] = sprintf(__('Quiz score: %d pts', 'loyalty-program'), $quiz_points);
            }

            $reason = sprintf(__('%s - %s', 'loyalty-program'), $survey['name'], implode(', ', $reason_parts));

            Loyalty_Program_Points::add_points(
                $user_id,
                $total_points_awarded,
                $reason,
                array(
                    'type' => $is_quiz ? 'quiz' : 'survey',
                    'survey_id' => $survey_id,
                    'survey_name' => $survey['name'],
                    'completion_points' => $completion_points,
                    'quiz_points' => $is_quiz ? $quiz_points : 0,
                    'score_percentage' => $is_quiz ? $score_percentage : null,
                )
            );

            Loyalty_Program_Logger::info('Survey/Quiz points awarded', array(
                'user_id' => $user_id,
                'survey_id' => $survey_id,
                'survey_name' => $survey['name'],
                'total_points' => $total_points_awarded,
                'completion_points' => $completion_points,
                'quiz_points' => $is_quiz ? $quiz_points : 0,
                'score_percentage' => $is_quiz ? $score_percentage : null,
            ));
        }

        Loyalty_Program_Logger::info('Survey/Quiz completed', array(
            'user_id' => $user_id,
            'survey_id' => $survey_id,
            'survey_name' => $survey['name'],
            'survey_type' => $survey['type'],
            'points_earned' => $total_points_awarded,
            'score_percentage' => $is_quiz ? $score_percentage : null,
        ));

        // Prepare response
        $message = $is_quiz
            ? __('Quiz completed!', 'loyalty-program')
            : __('Thank you for completing the survey!', 'loyalty-program');

        $response_data = array(
            'message' => $message,
            'points_earned' => $total_points_awarded,
        );

        // Add quiz result if enabled
        if ($is_quiz && isset($survey['settings']['showResult']) && $survey['settings']['showResult']) {
            $response_data['score_percentage'] = $score_percentage;
            $response_data['correct_answers'] = $correct_answers_count;
            $response_data['total_questions'] = $total_questions_count;
        }

        // Add information about minimum not reached
        if ($is_quiz && !$minimum_reached) {
            $response_data['minimum_not_reached'] = true;
            $response_data['min_percentage'] = $min_percentage;
        }

        // Add redirect URL if configured
        if (!empty($survey['settings']['redirectUrl'])) {
            $response_data['redirect_url'] = $survey['settings']['redirectUrl'];
        }

        wp_send_json_success($response_data);
    }

    /**
     * Export survey results to CSV
     * 
     * @return void
     */
    public function export_survey_results()
    {
        $survey_id = isset($_GET['survey_id']) ? sanitize_text_field($_GET['survey_id']) : '';
        $nonce = isset($_GET['nonce']) ? $_GET['nonce'] : '';

        // Verify nonce
        if (!wp_verify_nonce($nonce, 'export_survey_' . $survey_id)) {
            wp_die(__('Security check failed.', 'loyalty-program'));
        }

        // Check admin capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to export survey results.', 'loyalty-program'));
        }

        if (empty($survey_id)) {
            wp_die(__('Survey ID is required.', 'loyalty-program'));
        }

        // Get survey data
        $surveys = get_option('loyalty_program_surveys', array());
        $survey = null;

        foreach ($surveys as $s) {
            if ($s['id'] === $survey_id) {
                $survey = $s;
                break;
            }
        }

        if (!$survey) {
            wp_die(__('Survey not found.', 'loyalty-program'));
        }

        // Get all results
        global $wpdb;

        $user_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = 'loyalty_program_completed_surveys' 
            AND meta_value LIKE %s",
            '%' . $wpdb->esc_like($survey_id) . '%'
        ));

        // Prepare CSV
        $filename = sanitize_file_name($survey['name']) . '_results_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Headers
        $headers = array('User ID', 'Username', 'Email', 'Display Name', 'Completed At');

        if ($survey['type'] === 'quiz') {
            $headers[] = 'Score (%)';
            $headers[] = 'Correct Answers';
            $headers[] = 'Total Questions';
        }

        $headers[] = 'Points Earned';

        // Add question headers
        foreach ($survey['questions'] as $q_index => $question) {
            $headers[] = 'Q' . ($q_index + 1) . ': ' . $question['text'];
        }

        fputcsv($output, $headers);

        // Data
        foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);
            if (!$user) continue;

            $responses = get_user_meta($user_id, 'loyalty_program_survey_responses', true);

            if (isset($responses[$survey_id])) {
                $response = $responses[$survey_id];

                $row = array(
                    $user_id,
                    $user->user_login,
                    $user->user_email,
                    $user->display_name,
                    $response['completed_at'],
                );

                if ($survey['type'] === 'quiz') {
                    $row[] = isset($response['score_percentage']) ? $response['score_percentage'] : '';
                    $row[] = isset($response['correct_answers']) ? $response['correct_answers'] : '';
                    $row[] = isset($response['total_questions']) ? $response['total_questions'] : '';
                }

                $row[] = isset($response['points_earned']) ? $response['points_earned'] : 0;

                // Add answers
                foreach ($survey['questions'] as $q_index => $question) {
                    $answer = '';

                    if (isset($response['answers'][$q_index])) {
                        $user_answer = $response['answers'][$q_index];

                        if (is_array($user_answer)) {
                            // Multiple answers (checkbox)
                            $answer_texts = array();
                            foreach ($user_answer as $ans_index) {
                                if (isset($question['answers'][$ans_index])) {
                                    $answer_texts[] = $question['answers'][$ans_index]['text'];
                                }
                            }
                            $answer = implode('; ', $answer_texts);
                        } elseif (in_array($question['answerType'], array('radio', 'checkbox'))) {
                            // Single choice
                            if (isset($question['answers'][$user_answer])) {
                                $answer = $question['answers'][$user_answer]['text'];
                            }
                        } else {
                            // Text/number/textarea/rating
                            $answer = $user_answer;
                        }
                    }

                    $row[] = $answer;
                }

                fputcsv($output, $row);
            }
        }

        fclose($output);
        exit;
    }

    /**
     * Test SalesManago connection
     * 
     * @return void
     */
    public function test_salesmanago_connection()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'loyalty_program_test_salesmanago')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'loyalty-program')));
        }

        // Check permissions
        if (!current_user_can('manage_loyalty_program')) {
            wp_send_json_error(array('message' => __('You do not have permission to test integrations.', 'loyalty-program')));
        }

        // Get credentials from POST
        $client_id = isset($_POST['client_id']) ? sanitize_text_field($_POST['client_id']) : '';
        $sha = isset($_POST['sha']) ? sanitize_text_field($_POST['sha']) : '';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $owner = isset($_POST['owner']) ? sanitize_email($_POST['owner']) : '';

        // Validate required fields
        if (empty($client_id) || empty($sha) || empty($api_key) || empty($owner)) {
            wp_send_json_error(array('message' => __('Please fill in all required fields.', 'loyalty-program')));
        }

        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        Loyalty_Program_Logger::info('Testing SalesManago connection', array(
            'client_id' => $client_id,
            'owner' => $owner,
        ));

        // Test connection using contact/upsert endpoint
        $test_url = 'https://www.salesmanago.pl/api/contact/upsert';

        // Prepare request data (same format as your existing function)
        $request_data = array(
            'clientId' => $client_id,
            'requestTime' => time() * 1000,
            'sha' => $sha,
            'apiKey' => $api_key,
            'owner' => $owner,
            'contact' => array(
                'email' => 'loyalty-test@example.com', // Valid format test email
                'name' => 'Loyalty Program Test',
            ),
            'forceOptIn' => false, // Don't opt-in during test
            'forcePhoneOptIn' => false,
        );

        $response = wp_remote_post($test_url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($request_data),
        ));

        if (is_wp_error($response)) {
            Loyalty_Program_Logger::error('SalesManago connection test failed', array(
                'error' => $response->get_error_message(),
            ));
            wp_send_json_error(array(
                'message' => __('Connection failed: ', 'loyalty-program') . $response->get_error_message()
            ));
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        Loyalty_Program_Logger::debug('SalesManago API response', array(
            'code' => $response_code,
            'body' => $response_body,
            'success' => isset($response_data['success']) ? $response_data['success'] : null,
        ));

        if ($response_code === 200 && isset($response_data['success']) && $response_data['success'] === true) {
            wp_send_json_success(array(
                'message' => __('Connection successful! SalesManago API credentials are valid.', 'loyalty-program')
            ));
        } else {
            // Handle error message (can be string or array)
            $error_message = '';
            $error_details = array();

            if (isset($response_data['message'])) {
                if (is_array($response_data['message'])) {
                    $error_message = implode(', ', $response_data['message']);
                } else {
                    $error_message = $response_data['message'];
                }
            } else {
                $error_message = sprintf(__('HTTP Status: %d', 'loyalty-program'), $response_code);
            }

            // Add specific error details for common issues
            if (isset($response_data['success']) && $response_data['success'] === false) {
                if (strpos($error_message, 'Invalid') !== false || strpos($error_message, 'email') !== false) {
                    $error_details[] = __('Check if Owner Email is correct', 'loyalty-program');
                }
                if (strpos($error_message, 'auth') !== false || strpos($error_message, 'key') !== false) {
                    $error_details[] = __('Check if API Key and SHA are correct', 'loyalty-program');
                }
                if (strpos($error_message, 'client') !== false) {
                    $error_details[] = __('Check if Client ID is correct', 'loyalty-program');
                }
            }

            $full_message = $error_message;
            if (!empty($error_details)) {
                $full_message .= ' (' . implode(', ', $error_details) . ')';
            }

            Loyalty_Program_Logger::error('SalesManago test failed', array(
                'error_message' => $error_message,
                'error_details' => $error_details,
                'response_data' => $response_data,
            ));

            wp_send_json_error(array(
                'message' => $full_message
            ));
        }
    }

    /**
     * Verify email in SalesManago
     * 
     * @return void
     */
    public function verify_email_salesmanago()
    {
        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Check permission
        if (!current_user_can('manage_options')) {
            Loyalty_Program_Logger::error('Verify email permission denied', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array(
                'message' => __('You do not have permission to verify emails.', 'loyalty-program')
            ));
        }

        // Check nonce
        if (!check_ajax_referer('loyalty_program_verify_email_salesmanago', 'nonce', false)) {
            Loyalty_Program_Logger::error('Nonce verification failed for verify email');
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'loyalty-program')
            ));
        }

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array(
                'message' => __('Please provide a valid email address.', 'loyalty-program')
            ));
        }

        // Load SalesManago class
        if (!class_exists('Loyalty_Program_SalesManago')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/integrations/class-loyalty-program-salesmanago.php';
        }

        Loyalty_Program_Logger::info('Verifying email in SalesManago', array(
            'email' => $email,
            'admin_user_id' => get_current_user_id(),
        ));

        // First check if contact exists (faster)
        $has_contact = Loyalty_Program_SalesManago::has_contact($email);

        if ($has_contact === false) {
            // API error
            wp_send_json_error(array(
                'message' => __('Could not connect to SalesManago. Please check your credentials.', 'loyalty-program')
            ));
        }

        if ($has_contact['exists']) {
            // Contact exists, get full data
            $contact = Loyalty_Program_SalesManago::get_contact($email);

            // Get WordPress user data for comparison
            $wp_user = get_user_by('email', $email);
            $wp_consents = array();
            if ($wp_user) {
                $wp_consents = array(
                    'sms' => get_user_meta($wp_user->ID, 'loyalty_program_sms_consent', true) === 'yes',
                    'newsletter' => get_user_meta($wp_user->ID, 'loyalty_program_newsletter_consent', true) === 'yes',
                );
            }

            Loyalty_Program_Logger::info('Email found in SalesManago', array(
                'email' => $email,
                'contact_id' => $has_contact['contactId'],
                'wp_user_id' => $wp_user ? $wp_user->ID : null,
                'wp_sms_consent' => isset($wp_consents['sms']) ? $wp_consents['sms'] : null,
                'wp_newsletter_consent' => isset($wp_consents['newsletter']) ? $wp_consents['newsletter'] : null,
                'sm_optedOut' => isset($contact['optedOut']) ? $contact['optedOut'] : null,
                'sm_optedOutPhone' => isset($contact['optedOutPhone']) ? $contact['optedOutPhone'] : null,
            ));

            // Add WordPress consents to response for comparison
            if ($contact) {
                $contact['_wp_consents'] = $wp_consents;
            }

            wp_send_json_success(array(
                'message' => sprintf(__('Contact found! Email "%s" exists in SalesManago.', 'loyalty-program'), $email),
                'contact' => $contact ? $contact : array('contactId' => $has_contact['contactId']),
            ));
        } else {
            Loyalty_Program_Logger::info('Email not found in SalesManago', array(
                'email' => $email,
            ));

            wp_send_json_error(array(
                'message' => sprintf(__('Contact not found. Email "%s" does not exist in SalesManago.', 'loyalty-program'), $email)
            ));
        }
    }

    /**
     * Sync consents with SalesManago
     * 
     * @return void
     */
    public function sync_consents_salesmanago()
    {
        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Check permission
        if (!current_user_can('manage_options')) {
            Loyalty_Program_Logger::error('Sync consents permission denied', array(
                'user_id' => get_current_user_id(),
            ));
            wp_send_json_error(array(
                'message' => __('You do not have permission to sync consents.', 'loyalty-program')
            ));
        }

        // Check nonce
        if (!check_ajax_referer('loyalty_program_sync_consents_salesmanago', 'nonce', false)) {
            Loyalty_Program_Logger::error('Nonce verification failed for sync consents');
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'loyalty-program')
            ));
        }

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array(
                'message' => __('Please provide a valid email address.', 'loyalty-program')
            ));
        }

        // Load SalesManago class
        if (!class_exists('Loyalty_Program_SalesManago')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/integrations/class-loyalty-program-salesmanago.php';
        }

        // Check if SalesManago is enabled
        if (!Loyalty_Program_SalesManago::is_enabled()) {
            wp_send_json_error(array(
                'message' => __('SalesManago integration is not enabled.', 'loyalty-program')
            ));
        }

        // Get WordPress user
        $wp_user = get_user_by('email', $email);
        if (!$wp_user) {
            wp_send_json_error(array(
                'message' => sprintf(__('User with email "%s" not found in WordPress.', 'loyalty-program'), $email)
            ));
        }

        $user_id = $wp_user->ID;

        // Get user data
        $first_name = get_user_meta($user_id, 'first_name', true);
        $last_name = get_user_meta($user_id, 'last_name', true);
        $phone = get_user_meta($user_id, 'billing_phone', true);
        $birth_date = get_user_meta($user_id, 'loyalty_program_birth_date', true);
        $sms_consent = get_user_meta($user_id, 'loyalty_program_sms_consent', true) === 'yes';
        $newsletter_consent = get_user_meta($user_id, 'loyalty_program_newsletter_consent', true) === 'yes';

        // Prepare contact data
        $contact_data = array(
            'name' => $first_name,
            'lastName' => $last_name,
            'phone' => $phone,
            'birthday' => $birth_date ? date('Y-m-d', strtotime($birth_date)) : null,
            'streetAddress' => get_user_meta($user_id, 'billing_address_1', true),
            'zipCode' => get_user_meta($user_id, 'billing_postcode', true),
            'city' => get_user_meta($user_id, 'billing_city', true),
            'country' => get_user_meta($user_id, 'billing_country', true),
            'province' => get_user_meta($user_id, 'billing_state', true),
        );

        // Prepare consents
        $consents = array(
            'sms' => $sms_consent,
            'newsletter' => $newsletter_consent,
        );

        Loyalty_Program_Logger::info('🔄 Wymuszona synchronizacja zgód z SalesManago', array(
            'user_id' => $user_id,
            'email' => $email,
            'sms_consent' => $sms_consent,
            'newsletter_consent' => $newsletter_consent,
            'trigger' => 'Admin manual sync',
        ));

        // Sync with SalesManago
        $result = Loyalty_Program_SalesManago::upsert_contact(
            $email,
            $contact_data,
            array('Program lojalnościowy'),
            $consents
        );

        if ($result['success']) {
            Loyalty_Program_Logger::info('✅ Zgody zsynchronizowane z SalesManago (manual)', array(
                'user_id' => $user_id,
                'email' => $email,
                'contact_id' => isset($result['contactId']) ? $result['contactId'] : null,
            ));

            wp_send_json_success(array(
                'message' => sprintf(__('Consents successfully synchronized with SalesManago for email "%s".', 'loyalty-program'), $email)
            ));
        } else {
            $error_message = isset($result['message']) ? $result['message'] : __('Unknown error occurred.', 'loyalty-program');
            
            Loyalty_Program_Logger::error('❌ Synchronizacja zgód nie powiodła się', array(
                'user_id' => $user_id,
                'email' => $email,
                'error' => $error_message,
            ));

            wp_send_json_error(array(
                'message' => sprintf(__('Failed to sync consents: %s', 'loyalty-program'), $error_message)
            ));
        }
    }

    /**
     * Check user consents in SalesManago
     * 
     * @return void
     */
    public function check_salesmanago_consents()
    {
        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            wp_send_json_error(array('message' => __('Loyalty program is currently disabled.', 'loyalty-program')));
        }

        // Check nonce
        if (!check_ajax_referer('loyalty_check_consents', 'nonce', false)) {
            Loyalty_Program_Logger::error('Nonce verification failed for check consents');
            wp_send_json_error(array(
                'message' => __('Security check failed. Please refresh the page and try again.', 'loyalty-program')
            ));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to check consent preferences.', 'loyalty-program')
            ));
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        if (!$user) {
            wp_send_json_error(array(
                'message' => __('User not found.', 'loyalty-program')
            ));
        }

        // Load SalesManago class
        if (!class_exists('Loyalty_Program_SalesManago')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/integrations/class-loyalty-program-salesmanago.php';
        }

        // Check if SalesManago is enabled
        if (!Loyalty_Program_SalesManago::is_enabled()) {
            wp_send_json_error(array(
                'message' => __('SalesManago integration is not enabled.', 'loyalty-program')
            ));
        }

        // Get contact from SalesManago
        $contact = Loyalty_Program_SalesManago::get_contact($user->user_email);

        if (!$contact) {
            Loyalty_Program_Logger::warning('Failed to get contact from SalesManago', array(
                'user_id' => $user_id,
                'email' => $user->user_email,
            ));

            wp_send_json_error(array(
                'message' => __('Could not retrieve your data from SalesManago. Please try again later.', 'loyalty-program')
            ));
        }

        // Extract consent data - using optedOut fields (false = has consent)
        // optedOut: false = Newsletter enabled (has consent)
        // optedOutPhone: false = SMS enabled (has consent)
        $sms_consent = isset($contact['optedOutPhone']) && $contact['optedOutPhone'] === false;
        $newsletter_consent = isset($contact['optedOut']) && $contact['optedOut'] === false;

        Loyalty_Program_Logger::info('User consents retrieved from SalesManago', array(
            'user_id' => $user_id,
            'email' => $user->user_email,
            'optedOut' => isset($contact['optedOut']) ? $contact['optedOut'] : 'not set',
            'optedOutPhone' => isset($contact['optedOutPhone']) ? $contact['optedOutPhone'] : 'not set',
            'sms_consent' => $sms_consent ? 'ENABLED' : 'DISABLED',
            'newsletter_consent' => $newsletter_consent ? 'ENABLED' : 'DISABLED',
        ));

        wp_send_json_success(array(
            'sms_consent' => $sms_consent,
            'newsletter_consent' => $newsletter_consent,
            'email' => $user->user_email,
            'optedOut' => isset($contact['optedOut']) ? $contact['optedOut'] : null,
            'optedOutPhone' => isset($contact['optedOutPhone']) ? $contact['optedOutPhone'] : null,
        ));
    }

    /**
     * Search products and variations for rewards (AJAX)
     * 
     * @return void
     */
    public function search_products()
    {
        // Get data from REQUEST (works with both GET and POST)
        $nonce = isset($_REQUEST['_ajax_nonce']) ? $_REQUEST['_ajax_nonce'] : '';
        $search_term = isset($_REQUEST['search']) ? sanitize_text_field($_REQUEST['search']) : '';

        // Check nonce
        if (!wp_verify_nonce($nonce, 'loyalty_program_search_products')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'loyalty-program')
            ));
        }

        // Check permissions
        if (!current_user_can('manage_loyalty_program')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'loyalty-program')
            ));
        }

        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            wp_send_json_error(array(
                'message' => __('WooCommerce is not active.', 'loyalty-program')
            ));
        }

        // Minimum 3 characters
        if (strlen($search_term) < 3) {
            wp_send_json_success(array('results' => array()));
            return;
        }

        global $wpdb;

        // Search for products and variations
        // Max 50 results, only in-stock products
        $query = $wpdb->prepare(
            "SELECT DISTINCT p.ID, p.post_title, p.post_type, pm.meta_value as stock_status
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_stock_status'
             WHERE p.post_status = 'publish'
             AND (p.post_type = 'product' OR p.post_type = 'product_variation')
             AND p.post_title LIKE %s
             AND (pm.meta_value = 'instock' OR pm.meta_value IS NULL)
             ORDER BY p.post_title ASC
             LIMIT 50",
            '%' . $wpdb->esc_like($search_term) . '%'
        );

        $products = $wpdb->get_results($query);

        $results = array();

        foreach ($products as $product) {
            $product_obj = wc_get_product($product->ID);

            if (!$product_obj) {
                continue;
            }

            $title = $product->post_title;

            // For variations, add parent product name and variation attributes
            if ($product->post_type === 'product_variation') {
                $parent_id = $product_obj->get_parent_id();
                $parent = wc_get_product($parent_id);

                if ($parent) {
                    $variation_attributes = array();
                    foreach ($product_obj->get_variation_attributes() as $attribute_name => $attribute_value) {
                        $variation_attributes[] = ucfirst($attribute_value);
                    }

                    $title = $parent->get_name() . ' - ' . implode(', ', $variation_attributes);
                }
            }

            // Check if product is in stock
            if (!$product_obj->is_in_stock()) {
                continue;
            }

            $results[] = array(
                'id' => $product->ID,
                'text' => $title,
                'type' => $product->post_type,
                'stock_status' => $product_obj->get_stock_status(),
                'price' => $product_obj->get_price()
            );
        }

        wp_send_json_success(array('results' => $results));
    }

    /**
     * Search product categories (AJAX)
     * 
     * @return void
     */
    public function search_categories()
    {
        // Get data from REQUEST (works with both GET and POST)
        $nonce = isset($_REQUEST['_ajax_nonce']) ? $_REQUEST['_ajax_nonce'] : '';
        $search_term = isset($_REQUEST['search']) ? sanitize_text_field($_REQUEST['search']) : '';

        // Check nonce
        if (!wp_verify_nonce($nonce, 'loyalty_program_search_categories')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'loyalty-program')
            ));
        }

        // Check permissions
        if (!current_user_can('manage_loyalty_program')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'loyalty-program')
            ));
        }

        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            wp_send_json_error(array(
                'message' => __('WooCommerce is not active.', 'loyalty-program')
            ));
        }

        // Minimum 2 characters
        if (strlen($search_term) < 2) {
            wp_send_json_success(array('results' => array()));
            return;
        }

        $args = array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'number' => 50,
            'orderby' => 'name',
            'order' => 'ASC',
        );

        // Add search term if provided
        if (!empty($search_term)) {
            $args['search'] = $search_term;
        }

        $categories = get_terms($args);

        $results = array();

        if (!is_wp_error($categories) && !empty($categories)) {
            foreach ($categories as $category) {
                $results[] = array(
                    'id' => $category->term_id,
                    'text' => $category->name,
                );
            }
        }

        wp_send_json_success(array('results' => $results));
    }

    /**
     * Search WordPress pages (AJAX)
     * 
     * @return void
     */
    public function search_pages()
    {
        // Get data from REQUEST (works with both GET and POST)
        $nonce = isset($_REQUEST['_ajax_nonce']) ? $_REQUEST['_ajax_nonce'] : '';
        $search_term = isset($_REQUEST['search']) ? sanitize_text_field($_REQUEST['search']) : '';

        // Check nonce
        if (!wp_verify_nonce($nonce, 'loyalty_program_search_pages')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'loyalty-program')
            ));
        }

        // Check permissions
        if (!current_user_can('manage_loyalty_program')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'loyalty-program')
            ));
        }

        $args = array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'orderby' => 'title',
            'order' => 'ASC',
        );

        // Add search term if provided
        if (!empty($search_term)) {
            $args['s'] = $search_term;
        }

        $pages_query = new WP_Query($args);

        $results = array();

        if ($pages_query->have_posts()) {
            while ($pages_query->have_posts()) {
                $pages_query->the_post();
                $results[] = array(
                    'id' => get_the_ID(),
                    'text' => get_the_title(),
                );
            }
            wp_reset_postdata();
        }

        wp_send_json_success(array('results' => $results));
    }

    /**
     * Clear dashboard cache (AJAX)
     * 
     * @return void
     */
    public function clear_dashboard_cache()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'loyalty_clear_dashboard_cache')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'loyalty-program')
            ));
        }

        // Check permissions
        if (!current_user_can('manage_loyalty_program')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'loyalty-program')
            ));
        }

        // Clear all 3 separate caches
        $deleted_members = delete_transient('loyalty_dashboard_total_members');
        delete_option('loyalty_dashboard_total_members_time');

        $deleted_points = delete_transient('loyalty_dashboard_total_points');
        delete_option('loyalty_dashboard_total_points_time');

        $deleted_top20 = delete_transient('loyalty_dashboard_top_users');
        delete_option('loyalty_dashboard_top_users_time');

        // Log
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        Loyalty_Program_Logger::info('Dashboard cache manually cleared (all 3 caches)', array(
            'user_id' => get_current_user_id(),
            'members_cleared' => $deleted_members,
            'points_cleared' => $deleted_points,
            'top20_cleared' => $deleted_top20
        ));

        wp_send_json_success(array(
            'message' => __('Dashboard stats refreshed successfully!', 'loyalty-program')
        ));
    }

    /**
     * Handle Attendance Action Click
     * Award points when user clicks attendance action
     * 
     * @return void
     */
    public function handle_attendance_click()
    {
        // Verify nonce
        check_ajax_referer('loyalty_program_frontend', 'nonce');

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to participate.', 'loyalty-program')
            ));
        }

        $user_id = get_current_user_id();
        $action_id = isset($_POST['action_id']) ? sanitize_text_field($_POST['action_id']) : '';

        if (empty($action_id)) {
            wp_send_json_error(array(
                'message' => __('Invalid action ID.', 'loyalty-program')
            ));
        }

        // Load required classes
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Check if user is a member
        if (!Loyalty_Program_Points::is_member($user_id)) {
            wp_send_json_error(array(
                'message' => __('You need to join the loyalty program first.', 'loyalty-program')
            ));
        }

        // Get all actions
        $actions = get_option('loyalty_program_attendance_actions', array());

        // Find action by ID
        $action = null;
        foreach ($actions as $a) {
            if ($a['id'] === $action_id) {
                $action = $a;
                break;
            }
        }

        if (!$action) {
            Loyalty_Program_Logger::warning('Attendance action not found', array(
                'action_id' => $action_id,
                'user_id' => $user_id
            ));
            wp_send_json_error(array(
                'message' => __('Action not found.', 'loyalty-program')
            ));
        }

        // Check if action is enabled
        if ($action['enabled'] !== 'yes') {
            wp_send_json_error(array(
                'message' => __('Action is not enabled.', 'loyalty-program')
            ));
        }

        // Check if user already clicked this action
        $user_clicked_actions = get_user_meta($user_id, 'loyalty_program_attendance_actions', true);
        if (!is_array($user_clicked_actions)) {
            $user_clicked_actions = array();
        }

        if (in_array($action_id, $user_clicked_actions)) {
            wp_send_json_error(array(
                'message' => __('You have already completed this action.', 'loyalty-program')
            ));
        }

        // Check time range
        $current_time = current_time('mysql');

        // Convert datetime-local format to MySQL format if needed
        $date_from = str_replace('T', ' ', $action['date_from']);
        $date_to = str_replace('T', ' ', $action['date_to']);

        // Ensure we have seconds in the format
        if (strlen($date_from) === 16) {
            $date_from .= ':00';
        }
        if (strlen($date_to) === 16) {
            $date_to .= ':00';
        }

        $is_active = ($current_time >= $date_from && $current_time <= $date_to);

        Loyalty_Program_Logger::info('Attendance action click validation', array(
            'action_id' => $action_id,
            'user_id' => $user_id,
            'current_time' => $current_time,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'is_active' => $is_active,
        ));

        if (!$is_active) {
            wp_send_json_error(array(
                'message' => __('This action is not currently active.', 'loyalty-program')
            ));
        }

        // Award points
        $points = absint($action['points']);
        $action_desc = sprintf(__('Attendance: %s', 'loyalty-program'), $action['name']);

        Loyalty_Program_Points::add_points($user_id, $points, $action_desc, array(
            'type' => 'attendance_master',
            'action_id' => $action_id,
            'action_name' => $action['name'],
        ));

        // Mark action as clicked
        $user_clicked_actions[] = $action_id;
        update_user_meta($user_id, 'loyalty_program_attendance_actions', $user_clicked_actions);

        Loyalty_Program_Logger::info('Attendance action completed', array(
            'user_id' => $user_id,
            'action_id' => $action_id,
            'action_name' => $action['name'],
            'points' => $points
        ));

        wp_send_json_success(array(
            'message' => sprintf(__('Success! You earned %d points!', 'loyalty-program'), $points),
            'points' => $points
        ));
    }

    /**
     * Handle join program (for logged in users)
     * 
     * @return void
     */
    public function handle_join_program()
    {
        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            wp_send_json_error(array('message' => __('Loyalty program is currently disabled.', 'loyalty-program')));
        }

        // Check nonce
        check_ajax_referer('loyalty_program_frontend', 'nonce');

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to join the program.', 'loyalty-program')));
        }

        $user_id = get_current_user_id();

        // Check if already a member
        if (Loyalty_Program_Points::is_member($user_id)) {
            wp_send_json_error(array('message' => __('You are already enrolled in the loyalty program.', 'loyalty-program')));
        }

        // Check consent
        $consent = isset($_POST['consent']) && $_POST['consent'] === 'yes' ? 'yes' : 'no';
        if ($consent !== 'yes') {
            wp_send_json_error(array('message' => __('You must accept the consent to join the program.', 'loyalty-program')));
        }

        // Enroll user
        $result = Loyalty_Program_Points::enroll_user($user_id);

        if ($result) {
            // Check if auto newsletter is enabled
            $auto_newsletter = get_option('loyalty_program_join_form_logged_auto_newsletter', 'no');
            if ($auto_newsletter === 'yes') {
                update_user_meta($user_id, 'loyalty_program_newsletter_consent', 'yes');
            }

            $signup_points = get_option('loyalty_program_points_signup', 100);
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Welcome to the loyalty program! You have been awarded %d points!', 'loyalty-program'),
                    $signup_points
                )
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to join the program. Please try again.', 'loyalty-program')));
        }
    }

    /**
     * Handle register and join (for not logged in users)
     * 
     * @return void
     */
    public function handle_register_and_join()
    {
        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            wp_send_json_error(array('message' => __('Loyalty program is currently disabled.', 'loyalty-program')));
        }

        // Check nonce
        check_ajax_referer('loyalty_program_frontend', 'nonce');

        // Validate required fields
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $birth_date = isset($_POST['birth_date']) ? sanitize_text_field($_POST['birth_date']) : '';

        if (empty($first_name) || empty($email)) {
            wp_send_json_error(array('message' => __('Please fill in all required fields.', 'loyalty-program')));
        }

        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Please enter a valid email address.', 'loyalty-program')));
        }

        // Check if email already exists
        if (email_exists($email)) {
            wp_send_json_error(array('message' => __('An account with this email already exists. Please log in.', 'loyalty-program')));
        }

        // Get consents
        $sms_consent = isset($_POST['sms_consent']) && $_POST['sms_consent'] === 'yes' ? 'yes' : 'no';
        $newsletter_consent = isset($_POST['newsletter_consent']) && $_POST['newsletter_consent'] === 'yes' ? 'yes' : 'no';
        $terms_consent = isset($_POST['terms_consent']) && $_POST['terms_consent'] === 'yes' ? 'yes' : 'no';

        // Validate required terms consent
        $terms_consent_required = get_option('loyalty_program_join_form_terms_consent_required', 'yes');
        if ($terms_consent_required === 'yes' && $terms_consent !== 'yes') {
            wp_send_json_error(array('message' => __('You must accept the terms to register.', 'loyalty-program')));
        }

        // Get custom consents
        $custom_consents = get_option('loyalty_program_join_form_custom_consents', array());
        $custom_consents_values = array();
        foreach ($custom_consents as $index => $consent) {
            $key = 'custom_consent_' . $index;
            $value = isset($_POST[$key]) && $_POST[$key] === 'yes' ? 'yes' : 'no';
            if ($consent['required'] === 'yes' && $value !== 'yes') {
                wp_send_json_error(array('message' => sprintf(__('You must accept: %s', 'loyalty-program'), $consent['text'])));
            }
            $custom_consents_values[$index] = $value;
        }

        // Create user
        $username = sanitize_user($email);
        $password = wp_generate_password(12, false);

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }

        // Update user meta
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'billing_phone', $phone);
        if (!empty($birth_date)) {
            // Validate date format
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
                update_user_meta($user_id, 'loyalty_program_birth_date', $birth_date);
                // Also save in standard birth_date for compatibility
                update_user_meta($user_id, 'birth_date', $birth_date);
            }
        }

        // Save consents
        update_user_meta($user_id, 'loyalty_program_sms_consent', $sms_consent);
        update_user_meta($user_id, 'loyalty_program_newsletter_consent', $newsletter_consent);
        update_user_meta($user_id, 'loyalty_program_terms_consent', $terms_consent);

        // Save custom consents
        if (!empty($custom_consents_values)) {
            update_user_meta($user_id, 'loyalty_program_custom_consents', $custom_consents_values);
        }

        // Enroll user in loyalty program
        $result = Loyalty_Program_Points::enroll_user($user_id);

        if ($result) {
            // Award birthday points if birth date was provided
            if (!empty($birth_date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
                $points_awarded = get_user_meta($user_id, 'loyalty_program_birthday_points_awarded', true);
                if ($points_awarded !== 'yes') {
                    $birthday_points = get_option('loyalty_program_points_birthday', 25);
                    if ($birthday_points > 0) {
                        Loyalty_Program_Points::add_points(
                            $user_id,
                            $birthday_points,
                            __('Data urodzenia uzupełniona', 'loyalty-program'),
                            array(
                                'type' => 'birthday',
                                'birth_date' => $birth_date,
                            )
                        );
                        update_user_meta($user_id, 'loyalty_program_birthday_points_awarded', 'yes');
                    }
                }
            }

            // Check and award profile completion points if applicable
            if (class_exists('Loyalty_Program_WooCommerce')) {
                $woocommerce = new Loyalty_Program_WooCommerce();
                $reflection = new ReflectionClass($woocommerce);
                $method = $reflection->getMethod('check_and_award_profile_completion_points');
                $method->setAccessible(true);
                $method->invoke($woocommerce, $user_id);
            }

            // Auto-login user
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);

            $signup_points = get_option('loyalty_program_points_signup', 100);
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Account created successfully! Welcome to the loyalty program! You have been awarded %d points!', 'loyalty-program'),
                    $signup_points
                )
            ));
        } else {
            wp_send_json_error(array('message' => __('Account created but failed to join the program. Please contact support.', 'loyalty-program')));
        }
    }
}
