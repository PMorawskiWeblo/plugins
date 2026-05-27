<?php

/**
 * Shortcodes Class
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Loyalty Program Shortcodes Class
 */
class Loyalty_Program_Shortcodes
{

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->register_shortcodes();
        $this->init_hooks();
    }

    /**
     * Register all shortcodes
     * 
     * @return void
     */
    private function register_shortcodes()
    {
        add_shortcode('loyalty_user_coupon', array($this, 'user_coupon_shortcode'));
        add_shortcode('loyalty_current_points', array($this, 'current_points_shortcode'));
        add_shortcode('loyalty_total_points', array($this, 'total_points_shortcode'));
        add_shortcode('loyalty_membership_status', array($this, 'membership_status_shortcode'));
        add_shortcode('loyalty_points_history', array($this, 'points_history_shortcode'));
        add_shortcode('loyalty_wheel_of_fortune', array($this, 'wheel_of_fortune_shortcode'));
        add_shortcode('loyalty_wheel_of_fortune_modal', array($this, 'wheel_of_fortune_modal_shortcode'));
        add_shortcode('loyalty_rewards_list', array($this, 'rewards_list_shortcode'));
        add_shortcode('loyalty_my_rewards', array($this, 'my_rewards_shortcode'));
        add_shortcode('loyalty_consents', array($this, 'consents_shortcode'));
        add_shortcode('loyalty_check_consents', array($this, 'check_consents_shortcode'));
        add_shortcode('loyalty_birth_date', array($this, 'birth_date_shortcode'));
        add_shortcode('loyalty_check_birth_date', array($this, 'check_birth_date_shortcode'));
        add_shortcode('loyalty_check_profile_complete', array($this, 'check_profile_complete_shortcode'));
        add_shortcode('loyalty_check_survey_completed', array($this, 'check_survey_completed_shortcode'));
        add_shortcode('loyalty_check_live_participated', array($this, 'check_live_participated_shortcode'));
        add_shortcode('loyalty_check_attendance_master', array($this, 'check_attendance_master_shortcode'));
        add_shortcode('loyalty_discipline_progress', array($this, 'discipline_progress_shortcode'));
        add_shortcode('loyalty_discipline_products_list', array($this, 'discipline_products_list_shortcode'));
        add_shortcode('loyalty_attendance_action', array($this, 'attendance_action_shortcode'));
        add_shortcode('loyalty_survey', array($this, 'survey_shortcode'));
        add_shortcode('loyalty_join_button', array($this, 'join_button_shortcode'));
        add_shortcode('loyalty_join_button_reload', array($this, 'join_button_reload_shortcode'));
        add_shortcode('loyalty_join_button_modal', array($this, 'join_button_modal_shortcode'));
        add_shortcode('loyalty_account_fields', array($this, 'account_fields_shortcode'));
    }

    /**
     * Initialize hooks
     * 
     * @return void
     */
    private function init_hooks()
    {
        // Enqueue frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        // Add modal to footer
        add_action('wp_footer', array($this, 'render_join_modal_in_footer'));
    }

    /**
     * Enqueue frontend scripts for shortcodes
     * 
     * @return void
     */
    public function enqueue_frontend_scripts()
    {
        // Always enqueue on frontend (not admin)
        if (is_admin()) {
            return;
        }

        // Enqueue dashicons for rating stars
        wp_enqueue_style('dashicons');

        // Enqueue SweetAlert2
        wp_enqueue_script(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11',
            array(),
            '11',
            true
        );

        wp_enqueue_style(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
            array(),
            '11'
        );

        wp_enqueue_style(
            'loyalty-program-frontend',
            LOYALTY_PROGRAM_PLUGIN_URL . 'assets/css/frontend.min.css',
            array('sweetalert2'),
            loyalty_program_get_asset_version()
        );

        wp_enqueue_script(
            'loyalty-program-frontend',
            LOYALTY_PROGRAM_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'sweetalert2'),
            loyalty_program_get_asset_version(),
            true
        );

        wp_localize_script('loyalty-program-frontend', 'loyaltyProgramFrontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('loyalty_program_frontend'),
            'i18n' => array(
                // Survey/Quiz
                'answer_required' => __('Answer required', 'loyalty-program'),

                // Copy button
                'copied' => __('Copied!', 'loyalty-program'),

                // Join program
                'joining' => __('Joining...', 'loyalty-program'),
                'join_now' => __('Join Now', 'loyalty-program'),
                'join' => __('Join', 'loyalty-program'),
                'connection_error' => __('Connection error. Please try again.', 'loyalty-program'),
                'join_consent_required' => __('You must consent to joining the loyalty program.', 'loyalty-program'),
                'saving' => __('Saving...', 'loyalty-program'),

                // Wheel of Fortune
                'spinning' => __('Spinning...', 'loyalty-program'),
                'spin_wheel' => __('Spin the Wheel!', 'loyalty-program'),
                'error_occurred' => __('An error occurred.', 'loyalty-program'),
                'wheel_modal_title' => __('Spin and see what fortune brings you today!', 'loyalty-program'),
                'congratulations' => __('Congratulations!', 'loyalty-program'),
                'close' => __('Close', 'loyalty-program'),
                'next_chance_in' => __('Next chance in:', 'loyalty-program'),
                'hours' => __('hours', 'loyalty-program'),
                'minutes' => __('minutes', 'loyalty-program'),
                'seconds' => __('seconds', 'loyalty-program'),
                'points' => __('points', 'loyalty-program'),
                'pkt' => __('pkt', 'loyalty-program'),

                // Rewards
                'redeem_confirm' => __('Are you sure you want to redeem this reward?', 'loyalty-program'),
                'redeeming' => __('Redeeming...', 'loyalty-program'),
                'redeem' => __('Redeem', 'loyalty-program'),
                'activate_reward' => __('Activate Reward', 'loyalty-program'),

                // Add to cart
                'adding' => __('Adding...', 'loyalty-program'),
                'add_to_cart' => __('Add to Cart', 'loyalty-program'),

                // Attendance Actions
                'processing' => __('Processing...', 'loyalty-program'),
                'attendance_completed' => __('Already completed ✓', 'loyalty-program'),
                'error_general' => __('An error occurred. Please try again.', 'loyalty-program'),

                // Forms
                'save_changes' => __('Save changes', 'loyalty-program'),
                'save' => __('Save', 'loyalty-program'),
                'save_birth_date' => __('Save Birth Date', 'loyalty-program'),
                'submit' => __('Submit', 'loyalty-program'),
                'submitting' => __('Submitting...', 'loyalty-program'),
                'error_saving_preferences' => __('An error occurred while saving your preferences.', 'loyalty-program'),
                'error_saving_birth_date' => __('An error occurred while saving your birth date.', 'loyalty-program'),
                'error_saving_data' => __('An error occurred while saving your data.', 'loyalty-program'),
                'please_select_birth_date' => __('Please select your birth date.', 'loyalty-program'),

                // Survey/Quiz
                'time_is_up' => __('Time is up! Survey has been auto-submitted.', 'loyalty-program'),
                'your_result' => __('Your Result', 'loyalty-program'),
                'out_of' => __('out of', 'loyalty-program'),
                'correct' => __('correct', 'loyalty-program'),
                'points_earned' => __('Points earned:', 'loyalty-program'),
                'you_needed' => __('You needed', 'loyalty-program'),
                'percent_to_earn_points' => __('% to earn points.', 'loyalty-program'),
            ),
        ));
    }

    /**
     * User coupon shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function user_coupon_shortcode($atts)
    {
        // Check if program is enabled
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        if (!is_user_logged_in()) {
            return '<p class="loyalty-message">' . __('Please log in to view your coupon code.', 'loyalty-program') . '</p>';
        }

        $user_id = get_current_user_id();

        // Check if user is a member of the loyalty program
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return '<p class="loyalty-message">' . __('You need to join the loyalty program first to get a personal coupon.', 'loyalty-program') . '</p>';
        }

        // Load WooCommerce class
        if (!class_exists('Loyalty_Program_WooCommerce')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-woocommerce.php';
        }

        $coupon_code = Loyalty_Program_WooCommerce::get_user_coupon($user_id);

        // If user doesn't have a coupon, try to generate one automatically
        if (!$coupon_code) {
            // Load logger
            if (!class_exists('Loyalty_Program_Logger')) {
                require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
            }

            Loyalty_Program_Logger::info('User accessed coupon shortcode without coupon, attempting to generate', array(
                'user_id' => $user_id,
                'trigger' => 'loyalty_user_coupon shortcode',
            ));

            // Try to generate a new coupon
            $coupon_code = Loyalty_Program_WooCommerce::generate_personal_coupon($user_id);

            // If still no coupon, show error
            if (!$coupon_code) {
                Loyalty_Program_Logger::error('Failed to auto-generate personal coupon', array(
                    'user_id' => $user_id,
                ));

                return '<p class="loyalty-message">' . __('No personal coupon available. Join the loyalty program to get your coupon!', 'loyalty-program') . '</p>';
            }

            Loyalty_Program_Logger::info('Personal coupon auto-generated successfully', array(
                'user_id' => $user_id,
                'coupon_code' => $coupon_code,
            ));
        }

        // Get base values from settings
        $coupon_value_base = get_option('loyalty_program_coupon_value', 10);
        $min_amount_base = get_option('loyalty_program_coupon_min_amount', 150);

        // Convert to current currency if WMC is active
        $coupon_value = $this->convert_from_base_currency($coupon_value_base);
        $min_amount = $this->convert_from_base_currency($min_amount_base);

        $html = '<div class="loyalty-coupon-box">';
        $html .= '<div class="loyalty-coupon-code">';
        $html .= '<code id="loyalty-coupon-code" class="loyalty-coupon-code-clickable" data-coupon="' . esc_attr($coupon_code) . '" title="' . esc_attr__('Click to copy', 'loyalty-program') . '">' . esc_html($coupon_code) . '</code>';
        $html .= '</div>';
        $html .= '<p class="loyalty-coupon-info">';
        $html .= sprintf(
            __('Discount: %s | Minimum order: %s', 'loyalty-program'),
            wc_price($coupon_value),
            wc_price($min_amount)
        );
        $html .= '</p>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Current points shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function current_points_shortcode($atts)
    {
        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        if (!is_user_logged_in()) {
            return '<p class="loyalty-message">' . __('Please log in to view your points.', 'loyalty-program') . '</p>';
        }

        $user_id = get_current_user_id();

        // Check if user is a member of the loyalty program
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return '<p class="loyalty-message">' . __('You need to join the loyalty program first.', 'loyalty-program') . '</p>';
        }

        $current_points = Loyalty_Program_Points::get_current_points($user_id);

        return '<span class="loyalty-points-value">' . number_format_i18n($current_points) . '</span>';
    }

    /**
     * Total points shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function total_points_shortcode($atts)
    {
        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        if (!is_user_logged_in()) {
            return '<p class="loyalty-message">' . __('Please log in to view your points.', 'loyalty-program') . '</p>';
        }

        $user_id = get_current_user_id();

        // Check if user is a member of the loyalty program
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return '<p class="loyalty-message">' . __('You need to join the loyalty program first.', 'loyalty-program') . '</p>';
        }

        $total_points = Loyalty_Program_Points::get_total_earned($user_id);

        $html = '<div class="loyalty-points-display">';
        $html .= '<div class="loyalty-points-value">' . number_format_i18n($total_points) . '</div>';
        $html .= '<div class="loyalty-points-label">' . __('Total Points Earned', 'loyalty-program') . '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Membership status shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function membership_status_shortcode($atts)
    {
        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        if (!is_user_logged_in()) {
            return '<p class="loyalty-message">' . __('Please log in to check your membership status.', 'loyalty-program') . '</p>';
        }

        $user_id = get_current_user_id();

        $membership = Loyalty_Program_Points::get_membership_info($user_id);

        $html = '<div class="loyalty-membership-box">';

        if ($membership['is_member']) {
            $html .= '<div class="loyalty-member-active">';
            $html .= '<span class="dashicons dashicons-yes-alt"></span>';
            $html .= '<h3>' . __('You are enrolled in the Loyalty Program!', 'loyalty-program') . '</h3>';
            if ($membership['join_date_formatted']) {
                $html .= '<p>' . sprintf(__('Member since: %s', 'loyalty-program'), $membership['join_date_formatted']) . '</p>';
            }
            $html .= '</div>';
        } else {
            $html .= '<div class="loyalty-member-inactive">';
            $html .= '<h3>' . __('Join our Loyalty Program', 'loyalty-program') . '</h3>';
            $html .= '<p>' . __('Earn points with every purchase and get exclusive rewards!', 'loyalty-program') . '</p>';
            $html .= '<button type="button" class="loyalty-join-btn" data-user-id="' . esc_attr($user_id) . '">';
            $html .= __('Join Now', 'loyalty-program');
            $html .= '</button>';
            $html .= '<div class="loyalty-join-status"></div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Points history shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function points_history_shortcode($atts)
    {
        // Check if program is enabled
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        if (!is_user_logged_in()) {
            return '<p class="loyalty-message">' . __('Please log in to view your points history.', 'loyalty-program') . '</p>';
        }

        $atts = shortcode_atts(array(
            'limit' => 10,
        ), $atts);

        $user_id = get_current_user_id();

        // Load points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        $history = Loyalty_Program_Points::get_points_history($user_id, absint($atts['limit']));

        // Debug logging
        if (get_option('loyalty_program_debug_enabled', 'no') === 'yes') {
            if (!class_exists('Loyalty_Program_Logger')) {
                require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
            }
            // Loyalty_Program_Logger::debug('Points history shortcode - User ID: ' . $user_id . ', History count: ' . count($history))
        }

        $html = '<div class="loyalty-history-box">';

        if (!empty($history)) {
            $html .= '<table class="loyalty-history-table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th class="loyalty-history-date">' . __('Date', 'loyalty-program') . '</th>';
            $html .= '<th class="loyalty-history-mission-name">' . __('Mission name', 'loyalty-program') . '</th>';
            $html .= '<th class="loyalty-history-points">' . __('Points', 'loyalty-program') . '</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            foreach ($history as $transaction) {
                $sign = $transaction['type'] === 'increase' ? '+' : '-';
                $html .= '<tr class="loyalty-transaction-' . esc_attr($transaction['type']) . '">';
                $html .= '<td>' . esc_html(date_i18n('d.m.Y', $transaction['timestamp'])) . '</td>';
                $html .= '<td>' . esc_html($transaction['action']) . '</td>';
                $html .= '<td class="loyalty-points-cell">' . $sign . number_format_i18n($transaction['points']) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
        } else {
            $html .= '<p class="loyalty-message">' . __('No points history yet.', 'loyalty-program') . '</p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Wheel of fortune shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function wheel_of_fortune_shortcode($atts)
    {
        // Load required classes
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        if (!is_user_logged_in()) {
            return '<p class="loyalty-message">' . __('Please log in to spin the wheel.', 'loyalty-program') . '</p>';
        }

        $user_id = get_current_user_id();

        // Check if user is a member
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return '<p class="loyalty-message">' . __('You must be a member of the loyalty program to spin the wheel. Please join first!', 'loyalty-program') . '</p>';
        }

        // Get wheel configuration
        $prizes = get_option('loyalty_program_wheel_prizes', array());
        $days_between_spins = get_option('loyalty_program_wheel_days_between_spins', 7);

        if (empty($prizes)) {
            return '<p class="loyalty-message">' . __('The wheel of fortune is not configured yet. Please check back later!', 'loyalty-program') . '</p>';
        }

        // Filter enabled prizes only
        $enabled_prizes = array_filter($prizes, function ($prize) {
            return isset($prize['enabled']) && $prize['enabled'] === 'yes';
        });

        if (empty($enabled_prizes)) {
            return '<p class="loyalty-message">' . __('No prizes available at the moment. Please check back later!', 'loyalty-program') . '</p>';
        }

        // Check if user can spin
        $last_spin = get_user_meta($user_id, 'loyalty_program_last_wheel_spin', true);
        $can_spin = false;
        $next_spin_date = '';

        if (empty($last_spin)) {
            $can_spin = true;
        } else {
            // Convert MySQL date to timestamp in WordPress timezone
            $last_spin_timestamp = mysql2date('U', $last_spin);
            $next_spin_timestamp = $last_spin_timestamp + ($days_between_spins * DAY_IN_SECONDS);
            $current_timestamp = current_time('timestamp');

            if ($current_timestamp >= $next_spin_timestamp) {
                $can_spin = true;
            } else {
                $next_spin_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_spin_timestamp);
            }
        }

        // Build HTML
        $html = '<div class="loyalty-wheel-container" data-user-id="' . esc_attr($user_id) . '">';

        if ($can_spin) {
            $html .= '<div class="loyalty-wheel-pointer-arrow"></div>';
            $html .= '<div class="loyalty-wheel-wrapper">';
            $html .= '<canvas id="loyalty-wheel-canvas" width="483" height="483"></canvas>';
            $html .= '</div>';
            $html .= '<div class="loyalty-wheel-controls">';
            $html .= '<button type="button" id="loyalty-spin-btn" class="loyalty-spin-button">' . __('Spin the Wheel!', 'loyalty-program') . '</button>';
            $html .= '</div>';
        } else {
            $html .= '<div class="loyalty-wheel-locked">';
            $html .= '<span class="dashicons dashicons-lock"></span>';
            $html .= '<h3>' . __('Wheel Locked', 'loyalty-program') . '</h3>';
            $html .= '<p>' . sprintf(__('You can spin the wheel again on: %s', 'loyalty-program'), $next_spin_date) . '</p>';
            $html .= '</div>';
        }

        $html .= '<div id="loyalty-wheel-result"></div>';
        $html .= '</div>';

        // Add prizes data for JavaScript
        $html .= '<script type="text/javascript">';
        $html .= 'var loyaltyWheelPrizes = ' . json_encode(array_values($enabled_prizes)) . ';';
        $html .= '</script>';

        return $html;
    }

    /**
     * Wheel of fortune modal shortcode - displays button that opens modal
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function wheel_of_fortune_modal_shortcode($atts)
    {
        // Load required classes
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        if (!is_user_logged_in()) {
            return '<p class="loyalty-message">' . __('Please log in to spin the wheel.', 'loyalty-program') . '</p>';
        }

        $user_id = get_current_user_id();

        // Check if user is a member
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return '<p class="loyalty-message">' . __('You must be a member of the loyalty program to spin the wheel. Please join first!', 'loyalty-program') . '</p>';
        }

        // Get wheel configuration
        $prizes = get_option('loyalty_program_wheel_prizes', array());
        $days_between_spins = get_option('loyalty_program_wheel_days_between_spins', 7);

        if (empty($prizes)) {
            return '<p class="loyalty-message">' . __('The wheel of fortune is not configured yet. Please check back later!', 'loyalty-program') . '</p>';
        }

        // Filter enabled prizes only
        $enabled_prizes = array_filter($prizes, function ($prize) {
            return isset($prize['enabled']) && $prize['enabled'] === 'yes';
        });

        if (empty($enabled_prizes)) {
            return '<p class="loyalty-message">' . __('No prizes available at the moment. Please check back later!', 'loyalty-program') . '</p>';
        }

        // Check if user can spin
        $last_spin = get_user_meta($user_id, 'loyalty_program_last_wheel_spin', true);
        $can_spin = false;
        $next_spin_timestamp = 0;

        if (empty($last_spin)) {
            $can_spin = true;
        } else {
            // Convert MySQL date to timestamp in WordPress timezone
            $last_spin_timestamp = mysql2date('U', $last_spin);
            $next_spin_timestamp = $last_spin_timestamp + ($days_between_spins * DAY_IN_SECONDS);
            $current_timestamp = current_time('timestamp');

            if ($current_timestamp >= $next_spin_timestamp) {
                $can_spin = true;
            }
        }

        // Build HTML
        $html = '<div class="loyalty-wheel-modal-trigger" data-user-id="' . esc_attr($user_id) . '" data-can-spin="' . ($can_spin ? '1' : '0') . '" data-next-spin="' . esc_attr($next_spin_timestamp) . '">';

        if ($can_spin) {
            // Show button to open modal with wheel
            $html .= '<button type="button" class="loyalty-open-wheel-modal-btn"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40" fill="none">
  <path d="M19.997 20.8613C20.4012 20.8613 20.7299 21.19 20.7299 21.5942C20.7294 21.9981 20.4009 22.3254 19.997 22.3254C19.5936 22.3248 19.2662 21.9977 19.2658 21.5942C19.2658 21.1904 19.5933 20.862 19.997 20.8613Z" fill="white"/>
  <path fill-rule="evenodd" clip-rule="evenodd" d="M22.6534 3.33328C22.7737 3.33321 22.8932 3.36228 22.9993 3.41892C23.1055 3.47571 23.196 3.5592 23.2628 3.65937C23.3294 3.75933 23.3697 3.87418 23.3814 3.99369C23.3929 4.11356 23.3751 4.235 23.3287 4.34613L22.3455 6.70615L22.5909 6.74732C26.0935 7.33973 29.2722 9.15841 31.5583 11.8774C33.8443 14.5966 35.0892 18.0402 35.0711 21.5926C35.0712 24.4622 34.2516 27.2737 32.7095 29.6937C31.1671 32.114 28.9654 34.045 26.3639 35.257C23.7627 36.4687 20.8689 36.9127 18.024 36.5366C15.1789 36.1604 12.5003 34.9788 10.3033 33.1325C8.10637 31.2861 6.48151 28.851 5.62111 26.1133C4.76077 23.3754 4.70053 20.4475 5.44654 17.6762C6.19258 14.9054 7.71413 12.4051 9.83226 10.4693C11.9508 8.5334 14.5794 7.24128 17.4064 6.74732L17.6485 6.70451L17.5546 6.47723L16.6669 4.34613C16.6206 4.23505 16.6027 4.1135 16.6142 3.99369C16.6259 3.87423 16.6663 3.7593 16.7328 3.65937C16.7996 3.55922 16.8902 3.47575 16.9963 3.41892C17.1024 3.36224 17.2219 3.33328 17.3422 3.33328H22.6534ZM20.8418 25.9602C20.2846 26.0704 19.711 26.0705 19.1538 25.9602L18.994 25.9289L18.9331 26.0787L15.4746 34.4269L15.6755 34.4945C18.4798 35.4379 21.5157 35.4378 24.3201 34.4945L24.521 34.4269L21.0625 26.0787L21.0016 25.9289L20.8418 25.9602ZM16.0724 24.0135L7.92184 27.388L7.72422 27.4687L7.81974 27.6598C9.1438 30.303 11.2883 32.4472 13.9314 33.7715L14.1208 33.867L14.2032 33.6693L17.5777 25.5188L17.6386 25.3706L17.5052 25.28C17.0354 24.9613 16.6299 24.5558 16.3112 24.086L16.2206 23.9526L16.0724 24.0135ZM23.6844 24.086C23.3656 24.556 22.9588 24.9613 22.4888 25.28L22.357 25.3706L22.4179 25.5188L25.7925 33.6693L25.8748 33.867L26.0642 33.7715C28.7072 32.4477 30.8514 30.3041 32.1759 27.6614L32.2714 27.472L32.0738 27.3897L23.9232 24.0135L23.775 23.9526L23.6844 24.086ZM7.08192 17.2661C6.62079 18.6622 6.38705 20.124 6.38857 21.5942C6.38845 23.064 6.62759 24.5245 7.09674 25.9173L7.16427 26.1166L7.36025 26.0359L15.5124 22.6598L15.6623 22.5972L15.631 22.4391C15.5206 21.8812 15.5206 21.3055 15.631 20.7477L15.6623 20.5896L15.5124 20.527L7.34543 17.1459L7.14944 17.0636L7.08192 17.2661ZM32.6502 17.1459L24.4832 20.527L24.3333 20.5896L24.3646 20.7477C24.475 21.3056 24.475 21.8812 24.3646 22.4391L24.3333 22.5972L24.4832 22.6598L32.6354 26.0359L32.8313 26.1166L32.8989 25.9173C33.3681 24.5244 33.6072 23.064 33.607 21.5942C33.6086 20.1239 33.3749 18.6622 32.9137 17.2661L32.8462 17.0636L32.6502 17.1459ZM21.1399 18.834C20.5947 18.6084 19.9944 18.5493 19.4156 18.6644C18.8365 18.7796 18.3032 19.0637 17.8856 19.4812C17.4681 19.8988 17.184 20.432 17.0688 21.0112C16.9537 21.59 17.0128 22.1902 17.2384 22.7355C17.4643 23.281 17.8477 23.748 18.3385 24.0761C18.8293 24.404 19.4084 24.5798 19.9986 24.5801C20.79 24.579 21.5487 24.2634 22.1083 23.7039C22.668 23.1443 22.9834 22.3857 22.9845 21.5942C22.9845 21.0036 22.8087 20.4252 22.4805 19.9341C22.1524 19.4433 21.6854 19.0599 21.1399 18.834ZM13.9248 9.3972C11.2655 10.7095 9.11203 12.8618 7.79833 15.5204L7.70445 15.7098L7.90043 15.7905L16.0724 19.1733L16.2206 19.2342L16.3112 19.1024C16.6299 18.6324 17.0352 18.2256 17.5052 17.9068L17.6386 17.8162L17.5777 17.668L14.2097 9.5339L14.132 9.30044L13.9248 9.3972ZM22.4179 17.668L22.357 17.8162L22.4888 17.9068C22.959 18.2256 23.3656 18.6323 23.6844 19.1024L23.775 19.2342L23.9232 19.1733L32.0952 15.7905L32.2912 15.7098L32.1973 15.5204C30.8839 12.862 28.7312 10.7097 26.0724 9.3972L25.883 9.30333L22.4179 17.668ZM21.7065 8.24107L20.6739 10.7213C20.6184 10.8546 20.5238 10.9689 20.4038 11.0491C20.2837 11.129 20.1412 11.1709 19.997 11.1709C19.8531 11.1707 19.7116 11.1289 19.5918 11.0491C19.4719 10.9689 19.3772 10.8545 19.3217 10.7213L18.2891 8.24107L18.2298 8.09944L18.08 8.12085C17.2625 8.2353 16.4562 8.42411 15.6722 8.68244L15.4696 8.74832L15.5503 8.94595L18.9331 17.108L18.994 17.2579L19.1538 17.2266C19.711 17.1163 20.2846 17.1163 20.8418 17.2266L21.0016 17.2579L21.0625 17.108L24.4453 8.94595L24.526 8.74832L24.3234 8.68244C23.5394 8.42412 22.7331 8.23527 21.9156 8.12085L21.7658 8.09944L21.7065 8.24107ZM18.439 4.79738L18.5543 5.07077L19.997 8.53587L21.555 4.79738H18.439Z" fill="white"/>
  <path d="M11.0049 33.9329C11.0727 33.9823 11.1421 34.0295 11.2107 34.0778C11.1517 34.0362 11.0913 33.9967 11.0329 33.9543L11.0049 33.9329Z" fill="white"/>
  <path d="M10.1765 33.284L10.5981 33.6265C10.4553 33.515 10.3155 33.4008 10.1765 33.284Z" fill="white"/>
</svg><span>' . __('Spin the Wheel of Fortune', 'loyalty-program') . '</span></button>';
        } else {
            // Show countdown directly (no button)
            $html .= '<div class="loyalty-wheel-countdown-wrapper">';
            $html .= '<div class="loyalty-wheel-countdown-wrapper-icon">';
            $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="72" height="72" viewBox="0 0 72 72" fill="none">
  <path d="M35.9944 37.5504C36.722 37.5504 37.3135 38.142 37.3135 38.8696C37.3128 39.5965 36.7215 40.1858 35.9944 40.1858C35.2683 40.1846 34.6789 39.5958 34.6782 38.8696C34.6782 38.1427 35.2678 37.5516 35.9944 37.5504Z" fill="#111111"/>
  <path fill-rule="evenodd" clip-rule="evenodd" d="M40.776 5.99991C40.9925 5.99977 41.2075 6.0521 41.3985 6.15406C41.5897 6.25628 41.7526 6.40656 41.8729 6.58687C41.9927 6.76679 42.0653 6.97352 42.0863 7.18865C42.1071 7.40441 42.0749 7.623 41.9914 7.82304L40.2217 12.0711L40.6634 12.1452C46.9681 13.2115 52.6898 16.4851 56.8047 21.3794C60.9195 26.2739 63.1603 32.4724 63.1279 38.8666C63.128 44.0319 61.6527 49.0926 58.8769 53.4487C56.1006 57.8051 52.1375 61.2809 47.4549 63.4626C42.7726 65.6436 37.5638 66.4429 32.443 65.7659C27.3219 65.0887 22.5004 62.9618 18.5457 59.6384C14.5913 56.315 11.6665 51.9319 10.1178 47.004C8.56918 42.0757 8.46075 36.8055 9.80358 31.8172C11.1464 26.8298 13.8852 22.3293 17.6979 18.8448C21.5113 15.3601 26.2426 13.0343 31.3313 12.1452L31.7671 12.0681L31.5981 11.659L30.0003 7.82304C29.9169 7.62309 29.8846 7.4043 29.9054 7.18865C29.9264 6.97362 29.9992 6.76674 30.1189 6.58687C30.239 6.40659 30.4022 6.25634 30.5932 6.15406C30.7841 6.05203 30.9992 5.9999 31.2157 5.99991H40.776ZM37.5151 46.7283C36.5121 46.9268 35.4796 46.9269 34.4766 46.7283L34.189 46.672L34.0793 46.9417L27.854 61.9685L28.2157 62.09C33.2635 63.7882 38.7281 63.7881 43.776 62.09L44.1377 61.9685L37.9124 46.9417L37.8027 46.672L37.5151 46.7283ZM28.9301 43.2243L14.2591 49.2985L13.9034 49.4437L14.0753 49.7876C16.4586 54.5455 20.3187 58.4049 25.0764 60.7886L25.4173 60.9606L25.5655 60.6048L31.6396 45.9338L31.7493 45.667L31.5092 45.504C30.6635 44.9303 29.9336 44.2005 29.36 43.3548L29.1969 43.1147L28.9301 43.2243ZM42.6317 43.3548C42.0579 44.2008 41.3257 44.9303 40.4796 45.504L40.2424 45.667L40.3521 45.9338L46.4262 60.6048L46.5744 60.9606L46.9154 60.7886C51.6728 58.4059 55.5323 54.5474 57.9164 49.7906L58.0883 49.4497L57.7326 49.3014L43.0616 43.2243L42.7948 43.1147L42.6317 43.3548ZM12.7473 31.079C11.9172 33.5919 11.4965 36.2232 11.4992 38.8696C11.499 41.5151 11.9295 44.1441 12.7739 46.6512L12.8955 47.0099L13.2483 46.8647L27.9222 40.7876L28.192 40.6749L28.1357 40.3903C27.9369 39.3862 27.9369 38.35 28.1357 37.3459L28.192 37.0613L27.9222 36.9486L13.2216 30.8626L12.8688 30.7144L12.7473 31.079ZM58.7701 30.8626L44.0695 36.9486L43.7997 37.0613L43.8561 37.3459C44.0548 38.3501 44.0548 39.3861 43.8561 40.3903L43.7997 40.6749L44.0695 40.7876L58.7435 46.8647L59.0962 47.0099L59.2178 46.6512C60.0623 44.144 60.4927 41.5152 60.4925 38.8696C60.4952 36.2231 60.0745 33.592 59.2444 31.079L59.1229 30.7144L58.7701 30.8626ZM38.0517 33.9012C37.0703 33.4951 35.9897 33.3888 34.9479 33.5958C33.9056 33.8033 32.9455 34.3147 32.194 35.0662C31.4425 35.8179 30.931 36.7777 30.7236 37.8202C30.5165 38.862 30.6228 39.9424 31.0289 40.9239C31.4356 41.9057 32.1257 42.7464 33.0092 43.337C33.8925 43.9272 34.935 44.2437 35.9973 44.2441C37.4217 44.2422 38.7875 43.6741 39.7948 42.667C40.8021 41.6597 41.37 40.2942 41.3719 38.8696C41.3719 37.8065 41.0554 36.7654 40.4647 35.8814C39.8741 34.9979 39.0336 34.3079 38.0517 33.9012ZM25.0645 16.915C20.2777 19.277 16.4015 23.1512 14.0368 27.9367L13.8678 28.2776L14.2206 28.4229L28.9301 34.5119L29.1969 34.6215L29.36 34.3844C29.9337 33.5383 30.6632 32.8061 31.5092 32.2322L31.7493 32.0692L31.6396 31.8024L25.5773 17.161L25.4374 16.7408L25.0645 16.915ZM40.3521 31.8024L40.2424 32.0692L40.4796 32.2322C41.3259 32.8061 42.0578 33.5381 42.6317 34.3844L42.7948 34.6215L43.0616 34.5119L57.7711 28.4229L58.1239 28.2776L57.9549 27.9367C55.5909 23.1517 51.716 19.2775 46.9302 16.915L46.5893 16.746L40.3521 31.8024ZM39.0715 14.8339L37.2128 19.2984C37.1129 19.5382 36.9427 19.7441 36.7266 19.8883C36.5105 20.0322 36.254 20.1077 35.9944 20.1077C35.7353 20.1072 35.4807 20.032 35.2651 19.8883C35.0492 19.7441 34.8787 19.5381 34.779 19.2984L32.9203 14.8339L32.8135 14.579L32.5438 14.6175C31.0723 14.8235 29.6209 15.1634 28.2098 15.6284L27.8451 15.747L27.9904 16.1027L34.0793 30.7945L34.189 31.0642L34.4766 31.0079C35.4795 30.8093 36.5122 30.8094 37.5151 31.0079L37.8027 31.0642L37.9124 30.7945L44.0013 16.1027L44.1466 15.747L43.7819 15.6284C42.3707 15.1634 40.9195 14.8235 39.4479 14.6175L39.1782 14.579L39.0715 14.8339ZM33.19 8.63529L33.3975 9.12739L35.9944 15.3646L38.7987 8.63529H33.19Z" fill="#111111"/>
  <path d="M19.8085 61.0791C19.9307 61.1682 20.0556 61.2531 20.1791 61.34C20.0729 61.2652 19.9642 61.194 19.8589 61.1177L19.8085 61.0791Z" fill="#111111"/>
  <path d="M18.3174 59.9112L19.0763 60.5278C18.8194 60.327 18.5676 60.1214 18.3174 59.9112Z" fill="#111111"/>
</svg>';
            $html .= '</div>';
            $html .= '<div class="loyalty-wheel-countdown-wrapper-content">';
            $html .= '<h3 class="loyalty-countdown-title">' . __('Next chance in:', 'loyalty-program') . '</h3>';
            $html .= '<div class="loyalty-countdown-timer loyalty-countdown-inline" data-next-spin="' . esc_attr($next_spin_timestamp) . '">';
            $html .= '<div class="loyalty-countdown-item">';
            $html .= '<span class="loyalty-countdown-number" data-type="hours">00</span>';
            $html .= '<span class="loyalty-countdown-label">' . __('hours', 'loyalty-program') . '</span>';
            $html .= '</div>';
            $html .= '<div class="loyalty-countdown-item">';
            $html .= '<span class="loyalty-countdown-number" data-type="minutes">00</span>';
            $html .= '<span class="loyalty-countdown-label">' . __('minutes', 'loyalty-program') . '</span>';
            $html .= '</div>';
            $html .= '<div class="loyalty-countdown-item">';
            $html .= '<span class="loyalty-countdown-number" data-type="seconds">00</span>';
            $html .= '<span class="loyalty-countdown-label">' . __('seconds', 'loyalty-program') . '</span>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        // Add prizes data for JavaScript
        $html .= '<script type="text/javascript">';
        $html .= 'var loyaltyWheelModalPrizes = ' . json_encode(array_values($enabled_prizes)) . ';';
        $html .= '</script>';

        return $html;
    }

    /**
     * Render single reward item HTML (common template)
     * 
     * @param array $reward Reward data
     * @param string $type 'list' or 'my_rewards'
     * @param int $index Reward index
     * @param mixed $extra Extra data (current_points for list, item data for my_rewards)
     * @return string
     */
    private function render_reward_item($reward, $type = 'list', $index = 0, $extra = null)
    {
        $html = '';

        // Determine reward type
        $reward_type = isset($reward['_reward_type']) ? $reward['_reward_type'] : 'product';
        $reward_index = isset($reward['_reward_index']) ? $reward['_reward_index'] : $index;

        // Get product info (only for product rewards)
        $product = null;
        $product_name = '';
        $product_thumbnail = '';

        if ($reward_type === 'product' && class_exists('WooCommerce') && !empty($reward['product_id'])) {
            $product = wc_get_product($reward['product_id']);
            if ($product) {
                $product_name = $product->get_name();
                $product_thumbnail = $product->get_image('full');
            }
        }

        // Build HTML
        if ($type === 'list') {
            // For rewards list
            $current_points = $extra;
            $can_redeem = $current_points >= $reward['points'];
            $item_class = $can_redeem ? 'loyalty-reward-item' : 'loyalty-reward-item loyalty-reward-locked';
            if ($reward_type === 'coupon') {
                $item_class .= ' loyalty-reward-coupon';
            }

            $html .= '<li class="' . esc_attr($item_class) . '" data-reward-index="' . esc_attr($reward_index) . '" data-reward-type="' . esc_attr($reward_type) . '">';
            $html .= '<div class="loyalty-reward-card">';

            // Thumbnail (only for products)
            if ($product_thumbnail) {
                $html .= '<div class="loyalty-reward-thumbnail">' . $product_thumbnail . '</div>';
            } elseif ($reward_type === 'coupon') {
                // Coupon image or icon
                $image_id = isset($reward['image_id']) ? absint($reward['image_id']) : 0;
                
                // Debug logging
                if (class_exists('Loyalty_Program_Logger') && Loyalty_Program_Logger::is_enabled()) {
                    Loyalty_Program_Logger::debug('Rendering coupon reward thumbnail', array(
                        'reward_name' => $reward['name'] ?? '',
                        'reward_type' => $reward_type,
                        'image_id' => $image_id,
                        'has_image_id' => !empty($image_id),
                        'reward_data_keys' => array_keys($reward),
                    ));
                }
                
                if ($image_id) {
                    $image_url = wp_get_attachment_image_url($image_id, 'full');
                    
                    // Debug logging
                    if (class_exists('Loyalty_Program_Logger') && Loyalty_Program_Logger::is_enabled()) {
                        Loyalty_Program_Logger::debug('Coupon image URL check', array(
                            'image_id' => $image_id,
                            'image_url' => $image_url,
                            'has_url' => !empty($image_url),
                            'attachment_exists' => wp_attachment_is_image($image_id),
                        ));
                    }
                    
                    if ($image_url) {
                        $html .= '<div class="loyalty-reward-thumbnail"><img src="' . esc_url($image_url) . '" alt="' . esc_attr($reward['name'] ?? '') . '"></div>';
                    } else {
                        // Debug: image_id exists but URL is empty
                        if (class_exists('Loyalty_Program_Logger') && Loyalty_Program_Logger::is_enabled()) {
                            Loyalty_Program_Logger::warning('Coupon has image_id but image_url is empty', array(
                                'image_id' => $image_id,
                                'reward_name' => $reward['name'] ?? '',
                            ));
                        }
                        $html .= '<div class="loyalty-reward-thumbnail loyalty-coupon-icon"><span class="dashicons dashicons-tickets-alt"></span></div>';
                    }
                } else {
                    // Debug: no image_id
                    if (class_exists('Loyalty_Program_Logger') && Loyalty_Program_Logger::is_enabled()) {
                        Loyalty_Program_Logger::debug('Coupon has no image_id, showing icon', array(
                            'reward_name' => $reward['name'] ?? '',
                            'reward_data' => $reward,
                        ));
                    }
                    $html .= '<div class="loyalty-reward-thumbnail loyalty-coupon-icon"><span class="dashicons dashicons-tickets-alt"></span></div>';
                }
            }

            $html .= '<div class="loyalty-reward-content">';

            // Name and Points wrapper
            $html .= '<div class="loyalty-reward-header">';
            $html .= '<h4 class="loyalty-reward-name">' . esc_html($reward['name']) . '</h4>';
            $html .= '<p class="loyalty-reward-points"><strong>' . number_format_i18n($reward['points']) . '</strong> pkt</p>';
            $html .= '</div>';

            // Description
            if (!empty($reward['description'])) {
                $html .= '<p class="loyalty-reward-description">' . esc_html($reward['description']) . '</p>';
            }

            // Coupon details
            if ($reward_type === 'coupon') {
                $coupon_type = isset($reward['type']) ? $reward['type'] : 'fixed_cart';
                $discount_value = isset($reward['discount_value']) ? $reward['discount_value'] : 0;
   
                // $html .= '<p class="loyalty-reward-coupon-details">';
                // if ($coupon_type === 'free_shipping') {
                //     $html .= '<strong>' . __('Free Shipping', 'loyalty-program') . '</strong>';
                // } elseif ($coupon_type === 'percent') {
                //     $html .= '<strong>' . sprintf(__('%s%% discount', 'loyalty-program'), number_format_i18n($discount_value, 1)) . '</strong>';
                // } else {
                //     $html .= '<strong>' . sprintf(__('%s discount', 'loyalty-program'), wc_price($discount_value)) . '</strong>';
                // }
                // if (!empty($reward['min_order_amount']) && $reward['min_order_amount'] > 0) {
                //     $html .= ' - ' . sprintf(__('Min order: %s %s', 'loyalty-program'), number_format_i18n($reward['min_order_amount'], 2), get_woocommerce_currency_symbol());
                // }
                // $html .= '</p>';
            }

            if ($product_name && $product_name !== $reward['name']) {
                $html .= '<p class="loyalty-reward-product">' . esc_html($product_name) . '</p>';
            }

            // Button
            $html .= '<div class="loyalty-reward-action">';
            if ($can_redeem) {
                $button_text = $reward_type === 'coupon' ? __('Activate Reward', 'loyalty-program') : __('Activate Reward', 'loyalty-program');
                $html .= '<button type="button" class="loyalty-redeem-btn" data-reward-index="' . esc_attr($reward_index) . '" data-reward-type="' . esc_attr($reward_type) . '">' . $button_text . '</button>';
            } else {
                $html .= '<button type="button" class="loyalty-redeem-btn" disabled>' . __('Insufficient Points', 'loyalty-program') . '</button>';
            }
            $html .= '</div>';
            $html .= '</div>'; // .loyalty-reward-content
            $html .= '</div>'; // .loyalty-reward-card
            $html .= '</li>';
        } else {
            // For my rewards
            $item_data = $extra;
            $is_used = isset($item_data['used']) && $item_data['used'] === 'yes';
            $unique_reward_id = $item_data['unique_reward_id'] ?? '';
            $item_reward_type = isset($item_data['reward_type']) ? $item_data['reward_type'] : 'product';

            // Check if coupon has reached usage limit
            $coupon_used = false;
            if ($item_reward_type === 'coupon' && !empty($item_data['coupon_code']) && class_exists('WooCommerce')) {
                $coupon = new WC_Coupon($item_data['coupon_code']);
                if ($coupon->get_id()) {
                    $usage_limit = $coupon->get_usage_limit();
                    $usage_count = $coupon->get_usage_count();
                    // If coupon has a limit and it's been reached, mark as used
                    if ($usage_limit > 0 && $usage_count >= $usage_limit) {
                        $coupon_used = true;
                    }
                }
            }

            // Get product thumbnail for my rewards (from item_data if product reward)
            $my_reward_product_thumbnail = '';
            if ($item_reward_type === 'product' && class_exists('WooCommerce') && !empty($item_data['product_id'])) {
                $my_reward_product = wc_get_product($item_data['product_id']);
                if ($my_reward_product) {
                    $my_reward_product_thumbnail = $my_reward_product->get_image('full');
                }
            }

            $item_class = 'loyalty-my-reward-item loyalty-reward-item';
            if ($is_used || $coupon_used) {
                $item_class .= ' loyalty-reward-used';
            }
            if ($item_reward_type === 'coupon') {
                $item_class .= ' loyalty-reward-coupon';
            }

            $html .= '<li class="' . esc_attr($item_class) . '">';
            $html .= '<div class="loyalty-reward-card">';

            // Thumbnail (only for products)
            if ($item_reward_type === 'product' && $my_reward_product_thumbnail) {
                $html .= '<div class="loyalty-reward-thumbnail">' . $my_reward_product_thumbnail . '</div>';
            } elseif ($item_reward_type === 'coupon') {
                // Get coupon image from reward config (passed via $reward parameter)
                $coupon_image_id = isset($reward['image_id']) ? absint($reward['image_id']) : 0;
                
                // Debug logging
                if (class_exists('Loyalty_Program_Logger') && Loyalty_Program_Logger::is_enabled()) {
                    Loyalty_Program_Logger::debug('Rendering coupon reward thumbnail (My Rewards)', array(
                        'reward_name' => $item_data['reward_name'] ?? '',
                        'reward_type' => $item_reward_type,
                        'image_id' => $coupon_image_id,
                        'has_image_id' => !empty($coupon_image_id),
                        'reward_config_keys' => array_keys($reward),
                        'reward_config_image_id' => isset($reward['image_id']) ? $reward['image_id'] : 'not set',
                    ));
                }
                
                if ($coupon_image_id) {
                    $image_url = wp_get_attachment_image_url($coupon_image_id, 'full');
                    
                    // Debug logging
                    if (class_exists('Loyalty_Program_Logger') && Loyalty_Program_Logger::is_enabled()) {
                        Loyalty_Program_Logger::debug('Coupon image URL check (My Rewards)', array(
                            'image_id' => $coupon_image_id,
                            'image_url' => $image_url,
                            'has_url' => !empty($image_url),
                            'attachment_exists' => wp_attachment_is_image($coupon_image_id),
                        ));
                    }
                    
                    if ($image_url) {
                        $html .= '<div class="loyalty-reward-thumbnail"><img src="' . esc_url($image_url) . '" alt="' . esc_attr($item_data['reward_name']) . '"></div>';
                    } else {
                        // Debug: image_id exists but URL is empty
                        if (class_exists('Loyalty_Program_Logger') && Loyalty_Program_Logger::is_enabled()) {
                            Loyalty_Program_Logger::warning('Coupon has image_id but image_url is empty (My Rewards)', array(
                                'image_id' => $coupon_image_id,
                                'reward_name' => $item_data['reward_name'] ?? '',
                            ));
                        }
                        $html .= '<div class="loyalty-reward-thumbnail loyalty-coupon-icon"><span class="dashicons dashicons-tickets-alt"></span></div>';
                    }
                } else {
                    // Debug: no image_id
                    if (class_exists('Loyalty_Program_Logger') && Loyalty_Program_Logger::is_enabled()) {
                        Loyalty_Program_Logger::debug('Coupon has no image_id, showing icon (My Rewards)', array(
                            'reward_name' => $item_data['reward_name'] ?? '',
                            'reward_config' => $reward,
                        ));
                    }
                    $html .= '<div class="loyalty-reward-thumbnail loyalty-coupon-icon"><span class="dashicons dashicons-tickets-alt"></span></div>';
                }
            }

            $html .= '<div class="loyalty-reward-content">';

            // Name and Points wrapper
            $html .= '<div class="loyalty-reward-header">';
            $html .= '<h4 class="loyalty-reward-name">' . esc_html($item_data['reward_name']) . '</h4>';
            $html .= '<p class="loyalty-reward-points"><strong>' . number_format_i18n($item_data['points']) . '</strong> pkt</p>';
            $html .= '</div>';

            // Description (if available in reward config)
            if (!empty($reward['description'])) {
                $html .= '<p class="loyalty-reward-description">' . esc_html($reward['description']) . '</p>';
            }

            // Coupon code display (only if not used)
            if ($item_reward_type === 'coupon' && !empty($item_data['coupon_code']) && !$coupon_used) {
                $html .= '<div class="loyalty-coupon-code-display">';
                $html .= '<p><strong>' . __('Coupon Code:', 'loyalty-program') . '</strong></p>';
                $html .= '<code class="loyalty-coupon-code-clickable" data-coupon="' . esc_attr($item_data['coupon_code']) . '" title="' . esc_attr__('Click to copy', 'loyalty-program') . '">' . esc_html($item_data['coupon_code']) . '</code>';
                if (isset($item_data['coupon_type'])) {
                    $coupon_type_label = '';
                    if ($item_data['coupon_type'] === 'free_shipping') {
                        $coupon_type_label = __('Free Shipping', 'loyalty-program');
                    } elseif ($item_data['coupon_type'] === 'percent') {
                        $coupon_type_label = sprintf(__('%s%% discount', 'loyalty-program'), number_format_i18n($item_data['discount_value'] ?? 0, 1));
                    } else {
                        $coupon_type_label = sprintf(__('%s discount', 'loyalty-program'), wc_price($item_data['discount_value'] ?? 0));
                    }
                    $html .= '<p class="loyalty-coupon-type">' . $coupon_type_label . '</p>';
                }
                $html .= '</div>';
            }

            // Buttons (only for product rewards)
            if ($item_reward_type === 'product') {
                // Check if in cart
                $is_in_cart = false;
                if (!empty($unique_reward_id) && class_exists('WooCommerce') && WC()->cart) {
                    foreach (WC()->cart->get_cart() as $cart_item) {
                        if (
                            isset($cart_item['gift_from_loyalty_program']) &&
                            $cart_item['gift_from_loyalty_program'] === 'yes' &&
                            isset($cart_item['unique_reward_id']) &&
                            $cart_item['unique_reward_id'] === $unique_reward_id
                        ) {
                            $is_in_cart = true;
                            break;
                        }
                    }
                }

                if (!$is_used) {
                    $html .= '<div class="loyalty-my-reward-actions loyalty-reward-action">';
                    if ($is_in_cart) {
                        $html .= '<a href="' . esc_url(wc_get_cart_url()) . '" class="loyalty-add-reward-to-cart" disabled>' . __('In Cart', 'loyalty-program') . '</a>';
                    } else {
                        $html .= '<button type="button" class="loyalty-add-reward-to-cart" data-product-id="' . esc_attr($item_data['product_id']) . '" data-unique-reward-id="' . esc_attr($unique_reward_id) . '">' . __('Add to Cart', 'loyalty-program') . '</button>';
                    }
                    $html .= '</div>';
                } else {
                    $html .= '<div class="loyalty-reward-status loyalty-button-used" disabled>' . __('Used', 'loyalty-program') . '</div>';
                }
            } else {
                // For coupons, show copy button or used status
                if (!empty($item_data['coupon_code'])) {
                    if ($coupon_used) {
                        $html .= '<div class="loyalty-reward-status loyalty-button-used" disabled>' . __('Used', 'loyalty-program') . '</div>';
                    } else {
                        $html .= '<div class="loyalty-my-reward-actions loyalty-reward-action">';
                        $html .= '<button type="button" class="loyalty-copy-coupon-btn" data-coupon="' . esc_attr($item_data['coupon_code']) . '">' . __('Copy Coupon Code', 'loyalty-program') . '</button>';
                        $html .= '</div>';
                    }
                }
            }

            $html .= '</div>'; // .loyalty-reward-content
            $html .= '</div>'; // .loyalty-reward-card
            $html .= '</li>';
        }

        return $html;
    }

    /**
     * Display rewards list shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function rewards_list_shortcode($atts)
    {
        // Load required classes
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        if (!is_user_logged_in()) {
            return '<p class="loyalty-message">' . __('Please log in to view rewards.', 'loyalty-program') . '</p>';
        }

        $user_id = get_current_user_id();

        // Check if user is a member
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return '<p class="loyalty-message">' . __('You must be a member of the loyalty program to view rewards.', 'loyalty-program') . '</p>';
        }

        // Get rewards configuration - both products and coupons
        $product_rewards = get_option('loyalty_program_product_rewards', array());
        $coupon_rewards = get_option('loyalty_program_coupon_rewards', array());

        // Combine and mark type
        $rewards = array();
        
        // Add product rewards
        foreach ($product_rewards as $index => $reward) {
            $reward['_reward_type'] = 'product';
            $reward['_reward_index'] = $index;
            $rewards[] = $reward;
        }
        
        // Add coupon rewards
        foreach ($coupon_rewards as $index => $reward) {
            $reward['_reward_type'] = 'coupon';
            $reward['_reward_index'] = $index;
            
            // Debug logging
            if (class_exists('Loyalty_Program_Logger') && Loyalty_Program_Logger::is_enabled()) {
                Loyalty_Program_Logger::debug('Adding coupon reward to list', array(
                    'index' => $index,
                    'reward_name' => $reward['name'] ?? '',
                    'image_id' => isset($reward['image_id']) ? $reward['image_id'] : 'not set',
                    'has_image_id' => isset($reward['image_id']) && !empty($reward['image_id']),
                    'reward_keys' => array_keys($reward),
                ));
            }
            
            $rewards[] = $reward;
        }

        if (empty($rewards)) {
            return '<p class="loyalty-message">' . __('No rewards available at the moment.', 'loyalty-program') . '</p>';
        }

        // Filter enabled rewards only
        $enabled_rewards = array_filter($rewards, function ($reward) {
            return isset($reward['enabled']) && $reward['enabled'] === 'yes';
        });

        if (empty($enabled_rewards)) {
            return '<p class="loyalty-message">' . __('No rewards available at the moment.', 'loyalty-program') . '</p>';
        }

        // Sort by points (lowest to highest)
        usort($enabled_rewards, function ($a, $b) {
            return $a['points'] - $b['points'];
        });

        // Get user's current points
        $current_points = Loyalty_Program_Points::get_current_points($user_id);

        // Build HTML
        $html = '<div class="loyalty-rewards">';
        $html .= '<div class="loyalty-rewards-header">';
        $html .= '<div class="loyalty-rewards-header-content">';
        $html .= '<h2>' . __('Redeem points for a reward', 'loyalty-program') . '</h2>';
        $html .= '<p class="loyalty-user-points">' . sprintf(__('Current points: %s', 'loyalty-program'), '<strong>' . number_format_i18n($current_points) . '</strong>') . '</p>';
        $html .= '</div>';
        $html .= '<div class="loyalty-rewards-header-content">';
        $points_history_page_id = get_option('loyalty_program_points_history_page_id');
        $points_history_url = $points_history_page_id ? get_permalink($points_history_page_id) : '#';
        $html .= '<a class="points_history" href="' . esc_url($points_history_url) . '" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
  <rect x="0.5" y="2.5" width="19" height="17" stroke="#111111"></rect>
  <path d="M0 7.47656H20" stroke="#111111"></path>
  <path d="M4.49219 4V0" stroke="#111111"></path>
  <path d="M15.4292 4V0" stroke="#111111"></path>
</svg>                        <span class="points_history_text">' . esc_html__('Points history', 'loyalty-program') . '</span>
                    </a>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<ul class="loyalty-rewards-list">';

        foreach ($enabled_rewards as $index => $reward) {
            // Use common template - pass reward type and index
            $html .= $this->render_reward_item($reward, 'list', $index, $current_points);
        }

        $html .= '</ul>';
        $html .= '<div id="loyalty-redeem-result"></div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Display my rewards (redeemed rewards) shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function my_rewards_shortcode($atts)
    {
        // Load required classes
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        if (!is_user_logged_in()) {
            return '<p class="loyalty-message">' . __('Please log in to view your rewards.', 'loyalty-program') . '</p>';
        }

        $user_id = get_current_user_id();

        // Check if user is a member
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return '<p class="loyalty-message">' . __('You must be a member of the loyalty program.', 'loyalty-program') . '</p>';
        }

        // Get user's redeemed rewards
        $redeemed_rewards = get_user_meta($user_id, 'loyalty_program_redeemed_rewards', true);

        if (empty($redeemed_rewards) || !is_array($redeemed_rewards)) {
            return '<div class="loyalty-my-rewards"><p class="loyalty-message">' . __('You have not redeemed any rewards yet.', 'loyalty-program') . '</p></div>';
        }

        // Get rewards configuration for descriptions and points - both products and coupons
        $product_rewards_config = get_option('loyalty_program_product_rewards', array());
        $coupon_rewards_config = get_option('loyalty_program_coupon_rewards', array());
        
        // Combine rewards config
        $rewards_config = array();
        foreach ($product_rewards_config as $reward) {
            $reward['_reward_type'] = 'product';
            $rewards_config[] = $reward;
        }
        foreach ($coupon_rewards_config as $reward) {
            $reward['_reward_type'] = 'coupon';
            $rewards_config[] = $reward;
        }

        // Sort by points (lowest to highest)
        usort($redeemed_rewards, function ($a, $b) {
            // Get points directly from item data
            $points_a = isset($a['points']) ? intval($a['points']) : 0;
            $points_b = isset($b['points']) ? intval($b['points']) : 0;

            // Sort ascending (lowest to highest)
            return $points_a - $points_b;
        });

        // Build HTML
        $html = '<div class="loyalty-my-rewards">';
        $html .= '<h2>' . __('Your rewards', 'loyalty-program') . '</h2>';

        $html .= '<ul class="loyalty-rewards-list">';

        foreach ($redeemed_rewards as $index => $item) {
            // Get unique reward ID for this specific reward
            $unique_reward_id = isset($item['unique_reward_id']) ? $item['unique_reward_id'] : '';

            // Backward compatibility: Generate unique_reward_id if missing
            if (empty($unique_reward_id)) {
                // Use timestamp + product_id for uniqueness (instead of $index which changes after sort)
                $unique_reward_id = get_current_user_id() . '_' . strtotime($item['date']) . '_' . ($item['product_id'] ?? 0);
                // Update the reward with unique_reward_id
                $redeemed_rewards[$index]['unique_reward_id'] = $unique_reward_id;
                update_user_meta(get_current_user_id(), 'loyalty_program_redeemed_rewards', $redeemed_rewards);
            }

            // Add unique_reward_id to item
            $item['unique_reward_id'] = $unique_reward_id;

            // Find original reward config to get description and image
            $item_reward_type = isset($item['reward_type']) ? $item['reward_type'] : 'product';
            $reward_config = array(
                'product_id' => $item['product_id'] ?? 0,
                'description' => '',
                'image_id' => 0
            );

            foreach ($rewards_config as $config_reward) {
                $config_reward_type = isset($config_reward['_reward_type']) ? $config_reward['_reward_type'] : 'product';
                
                // Match by type first
                if ($item_reward_type !== $config_reward_type) {
                    continue;
                }
                
                // Then match by product_id (for products) or name (for coupons)
                if ($item_reward_type === 'product') {
                    if (isset($config_reward['product_id']) && $config_reward['product_id'] == ($item['product_id'] ?? 0)) {
                        $reward_config = $config_reward;
                        break;
                    }
                } else {
                    // For coupons, match by name
                    if (isset($item['reward_name']) && isset($config_reward['name']) && $config_reward['name'] === $item['reward_name']) {
                        $reward_config = $config_reward;
                        break;
                    }
                }
            }

            // Use common template
            $html .= $this->render_reward_item($reward_config, 'my_rewards', $index, $item);
        }

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Consents Shortcode
     * Display SMS and Newsletter consent checkboxes
     * 
     * Usage: [loyalty_consents]
     * 
     * @return string HTML output
     */
    public function consents_shortcode()
    {
        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p class="loyalty-message">' . __('Please log in to manage your consent preferences.', 'loyalty-program') . '</p>';
        }

        $user_id = get_current_user_id();

        // Check if user is a member
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return '<p class="loyalty-message">' . __('You need to join the loyalty program first.', 'loyalty-program') . '</p>';
        }

        // Get current consent values
        $sms_consent = get_user_meta($user_id, 'loyalty_program_sms_consent', true);
        $newsletter_consent = get_user_meta($user_id, 'loyalty_program_newsletter_consent', true);

        ob_start();
?>
<div class="loyalty-consents-container">
    <div class="loyalty-consents-header">
        <h3><?php _e('Consent Preferences', 'loyalty-program'); ?></h3>
        <p class="description">
            <?php _e('Manage your communication preferences. Completing both consents may unlock additional loyalty program benefits.', 'loyalty-program'); ?>
        </p>
    </div>

    <form id="loyalty-consents-form" class="loyalty-consents-form">
        <?php wp_nonce_field('loyalty_save_consents', 'loyalty_consents_nonce'); ?>

        <div class="loyalty-consent-item">
            <label class="loyalty-consent-label">
                <input type="checkbox" name="loyalty_sms_consent" id="loyalty_sms_consent" value="yes"
                    <?php checked($sms_consent, 'yes'); ?>>
                <span class="loyalty-consent-text">
                    <strong><?php _e('SMS Notifications', 'loyalty-program'); ?></strong>
                    <span class="consent-description">
                        <?php _e('I consent to receiving promotional SMS messages and special offers.', 'loyalty-program'); ?>
                    </span>
                </span>
            </label>
        </div>

        <div class="loyalty-consent-item">
            <label class="loyalty-consent-label">
                <input type="checkbox" name="loyalty_newsletter_consent" id="loyalty_newsletter_consent" value="yes"
                    <?php checked($newsletter_consent, 'yes'); ?>>
                <span class="loyalty-consent-text">
                    <strong><?php _e('Email Newsletter', 'loyalty-program'); ?></strong>
                    <span class="consent-description">
                        <?php _e('I consent to receiving email newsletters with exclusive content and offers.', 'loyalty-program'); ?>
                    </span>
                </span>
            </label>
        </div>

        <div class="loyalty-consents-actions">
            <button type="submit" id="save-consents-btn" class="loyalty-save-consents-btn">
                <?php _e('Save Preferences', 'loyalty-program'); ?>
            </button>
        </div>

        <div id="consents-save-status"></div>
    </form>
</div>
<?php
        return ob_get_clean();
    }

    /**
     * Check Consents in SalesManago Shortcode
     * Display button to check user consents in SalesManago
     * 
     * Usage: [loyalty_check_consents]
     * 
     * @return string HTML output
     */
    public function check_consents_shortcode()
    {
        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p class="loyalty-message">' . __('Please log in to check your consents.', 'loyalty-program') . '</p>';
        }

        $user_id = get_current_user_id();

        // Check if user is a member of the loyalty program
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return '<p class="loyalty-message">' . __('You need to join the loyalty program first.', 'loyalty-program') . '</p>';
        }

        $user = get_userdata($user_id);

        ob_start();
    ?>
<div class="loyalty-check-consents-container">
    <div class="loyalty-check-consents-header">
        <h3><?php _e('Check Your Consents in SalesManago', 'loyalty-program'); ?></h3>
        <p class="description">
            <?php _e('Click the button below to check your consent preferences stored in SalesManago.', 'loyalty-program'); ?>
        </p>
    </div>

    <div class="loyalty-check-consents-content">
        <p><strong><?php _e('Your email:', 'loyalty-program'); ?></strong> <?php echo esc_html($user->user_email); ?>
        </p>

        <button type="button" id="check-consents-btn" class="loyalty-check-consents-btn">
            <?php _e('Check Consents', 'loyalty-program'); ?>
        </button>

        <div id="consents-check-result" style="margin-top: 20px;"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#check-consents-btn').on('click', function() {
        const $button = $(this);
        const $result = $('#consents-check-result');

        $button.prop('disabled', true).text('<?php esc_attr_e('Checking...', 'loyalty-program'); ?>');
        $result.html('');

        $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'loyalty_program_check_salesmanago_consents',
            nonce: '<?php echo wp_create_nonce('loyalty_check_consents'); ?>'
        }, function(response) {
            if (response.success) {
                // Newsletter: optedOut = false means ENABLED
                const newsletterStatus = response.data.newsletter_consent ?
                    '<span style="color: #00a32a; font-weight: 600;">✓ <?php esc_html_e('Enabled', 'loyalty-program'); ?></span>' :
                    '<span style="color: #d63638; font-weight: 600;">✗ <?php esc_html_e('Disabled', 'loyalty-program'); ?></span>';

                const newsletterDetail = response.data.optedOut !== null ?
                    `` :
                    '';

                // SMS: optedOutPhone = false means ENABLED
                const smsStatus = response.data.sms_consent ?
                    '<span style="color: #00a32a; font-weight: 600;">✓ <?php esc_html_e('Enabled', 'loyalty-program'); ?></span>' :
                    '<span style="color: #d63638; font-weight: 600;">✗ <?php esc_html_e('Disabled', 'loyalty-program'); ?></span>';

                const smsDetail = response.data.optedOutPhone !== null ?
                    `<span style="color: #646970; font-size: 13px;"> </span>` :
                    '';

                $result.html(`
                            <div class="loyalty-consents-result success">
                                <h4><?php esc_html_e('Your Consents in SalesManago:', 'loyalty-program'); ?></h4>
                                <div style="background: #f9fafb; padding: 15px; border-radius: 4px; margin-top: 15px;">
                                    <p style="margin: 0 0 10px 0;"><strong><?php esc_html_e('Email Newsletter:', 'loyalty-program'); ?></strong><br>${newsletterStatus}${newsletterDetail}</p>
                                    <p style="margin: 0;"><strong><?php esc_html_e('SMS Notifications:', 'loyalty-program'); ?></strong><br>${smsStatus}${smsDetail}</p>
                                </div>
                            </div>
                        `);
            } else {
                $result.html(`
                            <div class="loyalty-consents-result error">
                                <p style="color: #d63638;">❌ ${response.data.message}</p>
                            </div>
                        `);
            }
        }).fail(function() {
            $result.html(`
                        <div class="loyalty-consents-result error">
                            <p style="color: #d63638;">❌ <?php esc_html_e('Connection error. Please try again.', 'loyalty-program'); ?></p>
                        </div>
                    `);
        }).always(function() {
            $button.prop('disabled', false).text(
                '<?php esc_attr_e('Check Consents', 'loyalty-program'); ?>');
        });
    });
});
</script>

<style>
.loyalty-check-consents-container {
    padding: 30px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    max-width: 600px;
    margin: 20px auto;
}

.loyalty-check-consents-header h3 {
    margin: 0 0 10px;
    font-size: 22px;
    color: #1d2327;
}

.loyalty-check-consents-header .description {
    margin: 0 0 20px;
    color: #646970;
    font-size: 14px;
}

.loyalty-check-consents-btn {
    padding: 12px 24px;
    background: #2271b1;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.loyalty-check-consents-btn:hover {
    background: #135e96;
}

.loyalty-check-consents-btn:disabled {
    background: #dcdcde;
    cursor: not-allowed;
}

.loyalty-consents-result {
    padding: 20px;
    border-radius: 4px;
    border-left: 4px solid;
}

.loyalty-consents-result.success {
    background: #f0f6fc;
    border-left-color: #00a32a;
}

.loyalty-consents-result.error {
    background: #fcf0f1;
    border-left-color: #d63638;
}

.loyalty-consents-result h4 {
    margin: 0 0 15px;
    font-size: 18px;
}

.loyalty-consents-result p {
    margin: 8px 0;
    font-size: 15px;
}
</style>
<?php
        return ob_get_clean();
    }

    /**
     * Birth Date Shortcode
     * Display form to set birth date or show if already set
     * 
     * Usage: [loyalty_birth_date]
     * 
     * @return string HTML output
     */
    public function birth_date_shortcode()
    {
        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p class="loyalty-message">' . __('Please log in to manage your birth date.', 'loyalty-program') . '</p>';
        }

        $user_id = get_current_user_id();

        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if user is a member
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return '<p class="loyalty-message">' . __('You need to join the loyalty program first.', 'loyalty-program') . '</p>';
        }

        // Get current birth date
        $birth_date = get_user_meta($user_id, 'loyalty_program_birth_date', true);
        $points_awarded = get_user_meta($user_id, 'loyalty_program_birthday_points_awarded', true);
        $birthday_points = get_option('loyalty_program_points_birthday', 25);

        ob_start();
    ?>
<div class="loyalty-birth-date-container">
    <div class="loyalty-birth-date-header">
        <h3><?php _e('Your Birth Date', 'loyalty-program'); ?></h3>
        <?php if ($birthday_points > 0 && $points_awarded !== 'yes') : ?>
        <p class="loyalty-reward-info">
            <span class="dashicons dashicons-star-filled"></span>
            <?php printf(__('Earn %d points by completing your birth date!', 'loyalty-program'), $birthday_points); ?>
        </p>
        <?php endif; ?>
    </div>

    <?php if (!empty($birth_date)) : ?>
    <!-- Birth date already set -->
    <div class="loyalty-birth-date-completed">
        <div class="birth-date-icon">
            <span class="dashicons dashicons-yes-alt"></span>
        </div>
        <h4><?php _e('Birth Date Completed', 'loyalty-program'); ?></h4>
        <p class="birth-date-value">
            <?php
                        $date_obj = DateTime::createFromFormat('Y-m-d', $birth_date);
                        if ($date_obj) {
                            echo $date_obj->format('d/m/Y');
                        } else {
                            echo esc_html($birth_date);
                        }
                        ?>
        </p>
        <?php if ($points_awarded === 'yes') : ?>
        <p class="birth-date-points-info">
            <span class="dashicons dashicons-saved"></span>
            <?php printf(__('You\'ve already received %d points for this!', 'loyalty-program'), $birthday_points); ?>
        </p>
        <?php endif; ?>
    </div>
    <?php else : ?>
    <!-- Birth date form -->
    <div class="loyalty-birth-date-form-wrapper">
        <p class="description">
            <?php _e('Share your birth date with us to unlock special birthday rewards and earn loyalty points!', 'loyalty-program'); ?>
        </p>

        <form id="loyalty-birth-date-form" class="loyalty-birth-date-form">
            <?php wp_nonce_field('loyalty_save_birth_date', 'loyalty_birth_date_nonce'); ?>

            <div class="birth-date-input-group">
                <label for="loyalty_birth_date_input">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php _e('Select Your Birth Date', 'loyalty-program'); ?>
                </label>
                <input type="date" name="loyalty_birth_date" id="loyalty_birth_date_input" required
                    max="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="birth-date-form-actions">
                <button type="submit" id="save-birth-date-btn" class="loyalty-save-birth-date-btn">
                    <span class="dashicons dashicons-saved"></span>
                    <?php _e('Save Birth Date', 'loyalty-program'); ?>
                </button>
            </div>

            <div id="birth-date-save-status"></div>
        </form>
    </div>
    <?php endif; ?>
</div>
<?php
        return ob_get_clean();
    }

    /**
     * Check Birth Date Shortcode
     * Returns true or false based on whether user has provided birth date
     * 
     * Usage: [loyalty_check_birth_date]
     * Returns: "true" or "false" as string
     * 
     * @param array $atts Shortcode attributes
     * @return string "true" or "false"
     */
    public function check_birth_date_shortcode($atts)
    {
        // Parse attributes
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(), // Default to current user
        ), $atts);

        $user_id = absint($atts['user_id']);

        // Check if user ID is valid
        if ($user_id === 0) {
            return 'false';
        }

        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if user is a member (optional check)
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return 'false';
        }

        // Get birth date from user meta
        $birth_date = get_user_meta($user_id, 'loyalty_program_birth_date', true);

        // Return true if birth date exists and is not empty, false otherwise
        return !empty($birth_date) ? 'completed' : 'not_completed';
    }

    /**
     * Check Profile Complete Shortcode
     * Returns true or false based on whether user has completed their profile
     * 
     * Profile is considered complete when user has:
     * - Birth date filled
     * - SMS consent given (yes)
     * - Newsletter consent given (yes)
     * - Billing phone filled
     * 
     * Usage: [loyalty_check_profile_complete]
     * Returns: "completed" or "not_completed" as string
     * 
     * @param array $atts Shortcode attributes
     * @return string "true" or "false"
     */
    public function check_profile_complete_shortcode($atts)
    {
        // Parse attributes
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(), // Default to current user
        ), $atts);

        $user_id = absint($atts['user_id']);

        // Check if user ID is valid
        if ($user_id === 0) {
            return 'false';
        }

        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if user is a member (optional check)
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return 'false';
        }

        // Get all required data (same logic as check_and_award_profile_completion_points)
        $birth_date = get_user_meta($user_id, 'loyalty_program_birth_date', true);
        $sms_consent = get_user_meta($user_id, 'loyalty_program_sms_consent', true);
        $newsletter_consent = get_user_meta($user_id, 'loyalty_program_newsletter_consent', true);
        $billing_phone = get_user_meta($user_id, 'billing_phone', true);

        // Check if all required fields are filled
        $profile_complete = !empty($birth_date)
            && $sms_consent === 'yes'
            && $newsletter_consent === 'yes'
            && !empty($billing_phone);

        // Return true if profile is complete, false otherwise
        return $profile_complete ? 'completed' : 'not_completed';
    }

    /**
     * Check Survey Completed Shortcode
     * Returns true or false based on whether user has completed specific survey/quiz
     * 
     * Usage: [loyalty_check_survey_completed id="survey_123"]
     * Returns: "true" or "false" as string
     * 
     * @param array $atts Shortcode attributes
     * @return string "true" or "false"
     */
    public function check_survey_completed_shortcode($atts)
    {
        // Parse attributes
        $atts = shortcode_atts(array(
            'id' => '',
            'user_id' => get_current_user_id(), // Default to current user
        ), $atts);

        $survey_id = sanitize_text_field($atts['id']);
        $user_id = absint($atts['user_id']);

        // Check if survey ID is provided
        if (empty($survey_id)) {
            return 'false';
        }

        // Check if user ID is valid
        if ($user_id === 0) {
            return 'false';
        }

        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if user is a member (optional check)
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return 'false';
        }

        // Get completed surveys for this user (same logic as in survey_shortcode)
        $completed_surveys = get_user_meta($user_id, 'loyalty_program_completed_surveys', true);
        if (!is_array($completed_surveys)) {
            $completed_surveys = array();
        }

        // Check if survey ID is in the completed surveys array
        $is_completed = in_array($survey_id, $completed_surveys);

        // Return true if survey is completed, false otherwise
        return $is_completed ? 'completed' : 'not_completed';
    }

    /**
     * Check Live Participated Shortcode
     * Returns true or false based on whether user participated in specific live session (CSV import)
     * 
     * Usage: [loyalty_check_live_participated id="live_123456789_1234"]
     * Returns: "true" or "false" as string
     * 
     * @param array $atts Shortcode attributes
     * @return string "true" or "false"
     */
    public function check_live_participated_shortcode($atts)
    {
        // Parse attributes
        $atts = shortcode_atts(array(
            'id' => '',
            'user_id' => get_current_user_id(), // Default to current user
        ), $atts);

        $csv_id = sanitize_text_field($atts['id']);
        $user_id = absint($atts['user_id']);

        // Check if CSV ID is provided
        if (empty($csv_id)) {
            return 'false';
        }

        // Check if user ID is valid
        if ($user_id === 0) {
            return 'false';
        }

        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if user is a member (optional check)
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return 'false';
        }

        // Get user's live sessions from dedicated meta (simple array of CSV IDs)
        $user_live_sessions = get_user_meta($user_id, 'loyalty_program_live_sessions', true);

        if (!is_array($user_live_sessions)) {
            $user_live_sessions = array();
        }

        // Simple check: is csv_id in the array?
        $participated = in_array($csv_id, $user_live_sessions);

        // Return true if user participated in this live session, false otherwise
        return $participated ? 'completed' : 'not_completed';
    }

    /**
     * Check Attendance Master Shortcode
     * Returns true or false based on whether user has completed specific attendance action
     * 
     * Usage: [loyalty_check_attendance_master id="action_123"]
     * Returns: "true" or "false" as string
     * 
     * @param array $atts Shortcode attributes
     * @return string "true" or "false"
     */
    public function check_attendance_master_shortcode($atts)
    {
        // Parse attributes
        $atts = shortcode_atts(array(
            'id' => '',
            'user_id' => get_current_user_id(), // Default to current user
        ), $atts);

        $action_id = sanitize_text_field($atts['id']);
        $user_id = absint($atts['user_id']);

        // Check if action ID is provided
        if (empty($action_id)) {
            return 'false';
        }

        // Check if user ID is valid
        if ($user_id === 0) {
            return 'false';
        }

        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if user is a member (optional check)
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return 'false';
        }

        // Get completed attendance actions for this user
        $user_clicked_actions = get_user_meta($user_id, 'loyalty_program_attendance_actions', true);
        if (!is_array($user_clicked_actions)) {
            $user_clicked_actions = array();
        }

        // Check if action ID is in the completed actions array
        $is_completed = in_array($action_id, $user_clicked_actions);

        // Return true if action is completed, false otherwise
        return $is_completed ? 'completed' : 'not_completed';
    }

    /**
     * Discipline Progress Shortcode
     * Display supplementation discipline progress for current product
     * 
     * Usage: [loyalty_discipline_progress] or [loyalty_discipline_progress product_id="123"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function discipline_progress_shortcode($atts)
    {
        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return ''; // Don't show anything for non-logged users
        }

        $user_id = get_current_user_id();

        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if user is a member
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return ''; // Don't show for non-members
        }

        // Get product ID from shortcode attribute or current product
        $atts = shortcode_atts(array(
            'product_id' => 0,
        ), $atts);

        $product_id = absint($atts['product_id']);

        // If no product_id provided, try to get current product
        if ($product_id === 0) {
            global $product;
            if (is_object($product) && method_exists($product, 'get_id')) {
                $product_id = $product->get_id();
            }
        }

        // If still no product_id, return empty
        if ($product_id === 0) {
            return '';
        }

        // Get product
        $product = wc_get_product($product_id);
        if (!$product) {
            return '';
        }

        // Determine tracked product ID (variation ID if variable product, otherwise product ID)
        $tracked_product_id = $product_id;
        $is_variable = false;
        $variation_ids = array();

        if ($product->is_type('variable')) {
            $is_variable = true;
            // Get all variations with discipline enabled
            $variations = $product->get_available_variations();
            foreach ($variations as $variation_data) {
                $variation_id = $variation_data['variation_id'];
                $discipline_enabled = get_post_meta($variation_id, '_loyalty_discipline_enabled', true);
                if ($discipline_enabled === 'yes') {
                    $variation_ids[] = $variation_id;
                }
            }

            // If no variations have discipline enabled, don't show
            if (empty($variation_ids)) {
                return '';
            }

            // For variable products, we'll show progress for selected variation via JavaScript
            // Default to first variation with discipline enabled
            $tracked_product_id = $variation_ids[0];
        } else {
            // Simple product - check if discipline is enabled
            $discipline_enabled = get_post_meta($product_id, '_loyalty_discipline_enabled', true);
            if ($discipline_enabled !== 'yes') {
                return ''; // Don't show if discipline is not enabled for this product
            }
        }

        $product_name = $product->get_name();

        // Get detailed purchase history
        $detailed_history = get_user_meta($user_id, 'loyalty_program_detailed_purchase_history', true);
        if (!is_array($detailed_history)) {
            $detailed_history = array();
        }

        // Fix structure - check for wrong nesting: array(0 => array(product_id => timestamps))
        // Correct structure should be: array(product_id => array(timestamps))
        // Wrong structure has keys 0,1,2... containing arrays of products
        $needs_fix = false;

        // Check if first level has sequential numeric keys (0, 1, 2) instead of product IDs
        if (isset($detailed_history[0]) && is_array($detailed_history[0])) {
            // Check if value at key 0 contains product arrays
            foreach ($detailed_history[0] as $inner_key => $inner_value) {
                if (is_array($inner_value) && isset($inner_value[0])) {
                    // This looks like product_id => array(timestamps) which is good
                    // But it's nested under key 0 which is bad
                    $needs_fix = true;
                    break;
                }
            }
        }

        if ($needs_fix) {
            if (!class_exists('Loyalty_Program_Logger')) {
                require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
            }

            Loyalty_Program_Logger::info('Detected bad structure, fixing...', array(
                'user_id' => $user_id,
                'old_structure' => $detailed_history
            ));

            // Flatten the structure
            $detailed_history = $detailed_history[0];
            update_user_meta($user_id, 'loyalty_program_detailed_purchase_history', $detailed_history);

            Loyalty_Program_Logger::info('Fixed purchase history structure', array(
                'user_id' => $user_id,
                'new_structure' => $detailed_history
            ));
        }

        // Get points value
        $discipline_points = get_option('loyalty_program_points_supplementation_discipline', 50);

        // Get text for not purchased products
        $not_purchased_text = get_option('loyalty_program_discipline_not_purchased_text', 'Kup i zrealizuj misję MyBestLife Club');

        // Get current time and 3 months ago
        $current_time = current_time('timestamp');
        $three_months_ago = $current_time - (90 * DAY_IN_SECONDS);

        // Prepare data for all variations (if variable product) or single product
        $variations_data = array();

        if ($is_variable) {
            // Prepare data for each variation with discipline enabled
            foreach ($variation_ids as $var_id) {
                $purchases = isset($detailed_history[$var_id]) ? $detailed_history[$var_id] : array();
                $recent_purchases = array_filter($purchases, function ($timestamp) use ($three_months_ago) {
                    return $timestamp >= $three_months_ago;
                });
                $purchases_count = count($recent_purchases);

                // Calculate days remaining
                $days_remaining = 0;
                if (!empty($recent_purchases)) {
                    $first_purchase = min($recent_purchases);
                    $three_months_from_first = $first_purchase + (90 * DAY_IN_SECONDS);
                    $days_remaining = max(0, floor(($three_months_from_first - $current_time) / DAY_IN_SECONDS));
                }

                $variations_data[$var_id] = array(
                    'purchases_count' => $purchases_count,
                    'days_remaining' => $days_remaining,
                    'progress_percent' => ($purchases_count / 3) * 100,
                    'remaining' => max(0, 3 - $purchases_count),
                    'not_purchased_text' => $not_purchased_text,
                );
            }
        } else {
            // Simple product - prepare data
            $purchases = isset($detailed_history[$tracked_product_id]) ? $detailed_history[$tracked_product_id] : array();
            $recent_purchases = array_filter($purchases, function ($timestamp) use ($three_months_ago) {
                return $timestamp >= $three_months_ago;
            });
            $purchases_count = count($recent_purchases);

            // Calculate days remaining
            $days_remaining = 0;
            if (!empty($recent_purchases)) {
                $first_purchase = min($recent_purchases);
                $three_months_from_first = $first_purchase + (90 * DAY_IN_SECONDS);
                $days_remaining = max(0, floor(($three_months_from_first - $current_time) / DAY_IN_SECONDS));
            }

            $variations_data[$tracked_product_id] = array(
                'purchases_count' => $purchases_count,
                'days_remaining' => $days_remaining,
                'progress_percent' => ($purchases_count / 3) * 100,
                'remaining' => max(0, 3 - $purchases_count),
                'not_purchased_text' => $not_purchased_text,
            );
        }

        // Load logger for debugging
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        Loyalty_Program_Logger::debug('Discipline shortcode check', array(
            'user_id' => $user_id,
            'product_id' => $product_id,
            'is_variable' => $is_variable,
            'variations_data' => $variations_data,
        ));

        // Check if any variation/product has less than 3 purchases
        $has_any_in_progress = false;
        foreach ($variations_data as $var_data) {
            if ($var_data['purchases_count'] < 3) {
                $has_any_in_progress = true;
                break;
            }
        }

        // Don't show if all variations/products already have 3+ purchases
        if (!$has_any_in_progress) {
            return '';
        }

        // Get data for default variation/product
        $default_data = $variations_data[$tracked_product_id];
        $purchases_count = $default_data['purchases_count'];
        $days_remaining = $default_data['days_remaining'];
        $progress_percent = $default_data['progress_percent'];
        $remaining = $default_data['remaining'];
        $not_purchased_text = $default_data['not_purchased_text'];

        // Determine display mode: 0 purchases = text only, 1-2 purchases = progress bar, 3+ = hidden
        $show_text_only = ($purchases_count === 0);
        $show_progress = ($purchases_count >= 1 && $purchases_count < 3);

        // Hide by default for variable products until variation is selected
        // Also hide if default variation has 3+ purchases (JavaScript will show correct one)
        $hide_default = '';
        if ($is_variable) {
            // Always hide for variable products until variation is selected
            $hide_default = 'style="display:none;"';
        } elseif ($purchases_count >= 3) {
            // Hide for simple products with 3+ purchases
            $hide_default = 'style="display:none;"';
        }

        ob_start();
    ?>
<div class="loyalty-discipline-progress" data-is-variable="<?php echo $is_variable ? '1' : '0'; ?>"
    data-product-id="<?php echo esc_attr($product_id); ?>"
    data-variations-data="<?php echo esc_attr(json_encode($variations_data)); ?>"
    data-current-variation="<?php echo esc_attr($tracked_product_id); ?>" <?php echo $hide_default; ?>>

    <?php
            $discipline_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
  <path d="M15.9964 16.6891C16.3198 16.6891 16.5827 16.952 16.5827 17.2754C16.5824 17.5985 16.3196 17.8604 15.9964 17.8604C15.6737 17.8598 15.4118 17.5981 15.4114 17.2754C15.4114 16.9523 15.6735 16.6896 15.9964 16.6891Z" fill="#111111"/>
  <path fill-rule="evenodd" clip-rule="evenodd" d="M18.1216 2.66663C18.2178 2.66657 18.3134 2.68982 18.3983 2.73514C18.4832 2.78057 18.5556 2.84736 18.6091 2.9275C18.6623 3.00746 18.6946 3.09934 18.7039 3.19495C18.7132 3.29085 18.6989 3.388 18.6618 3.47691L17.8752 5.36492L18.0715 5.39786C20.8736 5.87179 23.4166 7.32673 25.2455 9.50196C27.0743 11.6773 28.0702 14.4322 28.0557 17.2741C28.0558 19.5697 27.4001 21.8189 26.1664 23.755C24.9325 25.6912 23.1711 27.236 21.09 28.2056C19.009 29.1749 16.6939 29.5302 14.418 29.2293C12.142 28.9283 9.99908 27.983 8.24145 26.506C6.48392 25.0289 5.18404 23.0808 4.49572 20.8907C3.80744 18.7003 3.75925 16.358 4.35606 14.141C4.95289 11.9243 6.17013 9.92411 7.86464 8.37547C9.55948 6.82672 11.6623 5.79302 13.9239 5.39786L14.1176 5.3636L14.0425 5.18179L13.3324 3.47691C13.2953 3.38804 13.281 3.2908 13.2902 3.19495C13.2995 3.09939 13.3319 3.00744 13.3851 2.9275C13.4385 2.84737 13.511 2.7806 13.5959 2.73514C13.6807 2.68979 13.7764 2.66662 13.8726 2.66663H18.1216ZM16.6723 20.7681C16.2265 20.8563 15.7676 20.8564 15.3218 20.7681L15.194 20.7431L15.1453 20.863L12.3785 27.5415L12.5392 27.5956C14.7827 28.3503 17.2114 28.3503 19.4549 27.5956L19.6157 27.5415L16.8489 20.863L16.8001 20.7431L16.6723 20.7681ZM12.8567 19.2108L6.3363 21.9104L6.1782 21.975L6.25462 22.1278C7.31387 24.2424 9.02945 25.9577 11.144 27.0172L11.2955 27.0936L11.3614 26.9355L14.061 20.415L14.1097 20.2965L14.003 20.224C13.6271 19.969 13.3027 19.6447 13.0478 19.2688L12.9753 19.1621L12.8567 19.2108ZM18.9464 19.2688C18.6913 19.6448 18.3659 19.969 17.9898 20.224L17.8844 20.2965L17.9332 20.415L20.6328 26.9355L20.6987 27.0936L20.8502 27.0172C22.9646 25.9582 24.68 24.2433 25.7395 22.1291L25.8159 21.9776L25.6578 21.9117L19.1374 19.2108L19.0188 19.1621L18.9464 19.2688ZM5.66436 13.8129C5.29546 14.9297 5.10847 16.0992 5.10969 17.2754C5.10959 18.4512 5.3009 19.6196 5.67622 20.7339L5.73024 20.8933L5.88703 20.8287L12.4088 18.1278L12.5287 18.0777L12.5036 17.9513C12.4153 17.505 12.4153 17.0444 12.5036 16.5982L12.5287 16.4717L12.4088 16.4216L5.87517 13.7167L5.71838 13.6509L5.66436 13.8129ZM26.119 13.7167L19.5854 16.4216L19.4655 16.4717L19.4905 16.5982C19.5788 17.0445 19.5788 17.505 19.4905 17.9513L19.4655 18.0777L19.5854 18.1278L26.1071 20.8287L26.2639 20.8933L26.3179 20.7339C26.6933 19.6195 26.8846 18.4512 26.8845 17.2754C26.8857 16.0991 26.6987 14.9298 26.3298 13.8129L26.2758 13.6509L26.119 13.7167ZM16.9108 15.0672C16.4746 14.8867 15.9943 14.8395 15.5313 14.9315C15.0681 15.0237 14.6414 15.251 14.3073 15.585C13.9733 15.9191 13.746 16.3456 13.6539 16.809C13.5618 17.272 13.609 17.7522 13.7896 18.1884C13.9703 18.6248 14.277 18.9984 14.6697 19.2609C15.0623 19.5232 15.5256 19.6639 15.9977 19.664C16.6308 19.6632 17.2378 19.4107 17.6855 18.9631C18.1332 18.5154 18.3856 17.9085 18.3864 17.2754C18.3864 16.8029 18.2457 16.3402 17.9832 15.9473C17.7208 15.5546 17.3472 15.248 16.9108 15.0672ZM11.1387 7.51776C9.01124 8.56757 7.28845 10.2894 6.23749 12.4163L6.16239 12.5678L6.31918 12.6324L12.8567 15.3386L12.9753 15.3874L13.0478 15.282C13.3028 14.9059 13.627 14.5805 14.003 14.3254L14.1097 14.253L14.061 14.1344L11.3666 7.62712L11.3044 7.44036L11.1387 7.51776ZM17.9332 14.1344L17.8844 14.253L17.9898 14.3254C18.366 14.5805 18.6913 14.9058 18.9464 15.282L19.0188 15.3874L19.1374 15.3386L25.675 12.6324L25.8318 12.5678L25.7567 12.4163C24.706 10.2896 22.9838 8.56776 20.8568 7.51776L20.7053 7.44266L17.9332 14.1344ZM17.364 6.59286L16.5379 8.57705C16.4935 8.68364 16.4179 8.77515 16.3218 8.83924C16.2258 8.90319 16.1118 8.93674 15.9964 8.93674C15.8813 8.93655 15.7681 8.9031 15.6723 8.83924C15.5763 8.77514 15.5006 8.6836 15.4562 8.57705L14.6301 6.59286L14.5827 6.47955L14.4628 6.49668C13.8088 6.58824 13.1638 6.73929 12.5366 6.94595L12.3745 6.99866L12.4391 7.15676L15.1453 13.6864L15.194 13.8063L15.3218 13.7813C15.7676 13.693 16.2265 13.6931 16.6723 13.7813L16.8001 13.8063L16.8489 13.6864L19.5551 7.15676L19.6196 6.99866L19.4576 6.94595C18.8303 6.7393 18.1853 6.58822 17.5313 6.49668L17.4114 6.47955L17.364 6.59286ZM14.75 3.83791L14.8423 4.05662L15.9964 6.8287L17.2428 3.83791H14.75Z" fill="#111111"/>
  <path d="M8.80271 27.1463C8.85699 27.1859 8.91253 27.2236 8.96741 27.2622C8.9202 27.229 8.87187 27.1973 8.82511 27.1634L8.80271 27.1463Z" fill="#111111"/>
  <path d="M8.14 26.6272L8.47729 26.9012C8.36311 26.812 8.2512 26.7206 8.14 26.6272Z" fill="#111111"/>
</svg>';

            $always_include_both = $is_variable;
            ?>

    <?php if ($show_text_only || $always_include_both) : ?>
    <!-- Text only mode (0 purchases) -->
    <div class="discipline-text-only"
        <?php echo (!$show_text_only && $always_include_both) ? 'style="display:none;"' : ''; ?>>
        <div class="discipline-text-content">
            <div class="discipline-icon"><?php echo $discipline_icon; ?></div>
            <p class="discipline-not-purchased-text"><?php echo esc_html($not_purchased_text); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($show_progress || $always_include_both) : ?>
    <!-- Progress bar mode (1-2 purchases) -->
    <div class="discipline_progress_bar_mode">


        <div class="discipline-header"
            <?php echo (!$show_progress && $always_include_both) ? 'style="display:none;"' : ''; ?>>
            <div class="discipline-icon">
                <?php echo $discipline_icon; ?>
            </div>
            <div class="discipline-info">
                <h4><?php _e('Supplementation Discipline Challenge', 'loyalty-program'); ?></h4>
                <p class="discipline-description">
                    <?php printf(
                                    __('Purchase this product %d more time(s) within %d days to earn %d loyalty points!', 'loyalty-program'),
                                    3 - $purchases_count,
                                    $days_remaining,
                                    $discipline_points
                                ); ?>
                </p>
            </div>
        </div>

        <div class="discipline-progress-bar"
            <?php echo (!$show_progress && $always_include_both) ? 'style="display:none;"' : ''; ?>>
            <div class="progress-labels">
                <span class="progress-current">
                    <?php printf(__('%d of 3 purchases', 'loyalty-program'), $purchases_count); ?>
                </span>
                <span class="progress-reward">
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php printf(__('%d points', 'loyalty-program'), $discipline_points); ?>
                </span>
            </div>
            <div class="progress-bar-wrapper">
                <div class="progress-bar-fill" style="width: <?php echo esc_attr($progress_percent); ?>%;"></div>
            </div>

        </div>
    </div>
    <?php endif; // End show_progress 
            ?>
</div>

<?php if ($is_variable) : ?>
<script>
jQuery(document).ready(function($) {
    var $container = $('.loyalty-discipline-progress[data-product-id="<?php echo esc_js($product_id); ?>"]');
    if ($container.length === 0) return;

    var variationsData = $container.data('variations-data');
    var productId = $container.data('product-id');

    // Function to update progress display
    function updateProgress(variationId) {
        if (!variationsData || !variationsData[variationId]) {
            // Hide if variation doesn't have discipline enabled
            $container.hide();
            return;
        }

        var data = variationsData[variationId];

        // Hide if already completed (3+ purchases)
        if (data.purchases_count >= 3) {
            $container.hide();
            return;
        }

        $container.show();

        // Determine display mode
        var showTextOnly = (data.purchases_count === 0);
        var showProgress = (data.purchases_count >= 1 && data.purchases_count < 3);

        if (showTextOnly) {
            // Show text only mode
            $container.find('.discipline-text-only').show();
            $container.find('.discipline-header').hide();
            $container.find('.discipline-progress-bar').hide();
            $container.find('.discipline-footer').hide();

            // Update text
            $container.find('.discipline-not-purchased-text').text(data.not_purchased_text);
        } else if (showProgress) {
            // Show progress bar mode
            $container.find('.discipline-text-only').hide();
            $container.find('.discipline-header').show();
            $container.find('.discipline-progress-bar').show();
            $container.find('.discipline-footer').show();

            // Update description
            var moreTimeText = data.remaining === 1 ?
                '<?php echo esc_js(__('more time', 'loyalty-program')); ?>' :
                '<?php echo esc_js(__('more times', 'loyalty-program')); ?>';
            var dayText = data.days_remaining === 1 ?
                '<?php echo esc_js(__('day', 'loyalty-program')); ?>' :
                '<?php echo esc_js(__('days', 'loyalty-program')); ?>';

            $container.find('.discipline-description').html(
                '<?php echo esc_js(__('Purchase this product', 'loyalty-program')); ?> ' +
                data.remaining + ' ' + moreTimeText +
                ' <?php echo esc_js(__('within', 'loyalty-program')); ?> ' +
                data.days_remaining + ' ' + dayText +
                ' <?php echo esc_js(__('to earn', 'loyalty-program')); ?> <?php echo esc_js($discipline_points); ?> <?php echo esc_js(__('loyalty points!', 'loyalty-program')); ?>'
            );

            // Update progress current
            $container.find('.progress-current').text(data.purchases_count +
                ' <?php echo esc_js(__('of 3 purchases', 'loyalty-program')); ?>');

            // Update progress bar
            $container.find('.progress-bar-fill').css('width', data.progress_percent + '%');

            // Update steps
            $container.find('.step').removeClass('completed');
            if (data.purchases_count >= 1) $container.find('.step:eq(0)').addClass('completed');
            if (data.purchases_count >= 2) $container.find('.step:eq(1)').addClass('completed');
            if (data.purchases_count >= 3) $container.find('.step:eq(2)').addClass('completed');

            // Update time remaining
            if (data.days_remaining > 0) {
                var daysRemainingText = data.days_remaining + ' ' +
                    (data.days_remaining === 1 ?
                        '<?php echo esc_js(__('day remaining', 'loyalty-program')); ?>' :
                        '<?php echo esc_js(__('days remaining', 'loyalty-program')); ?>');
                $container.find('.time-remaining').html(
                    '<span class="dashicons dashicons-clock"></span> ' + daysRemainingText
                ).show();
            } else {
                $container.find('.time-remaining').hide();
            }

            // Update purchases left
            var purchasesLeftText = '<?php echo esc_js(__('Buy', 'loyalty-program')); ?> ' +
                data.remaining + ' ' +
                (data.remaining === 1 ?
                    '<?php echo esc_js(__('more time', 'loyalty-program')); ?>' :
                    '<?php echo esc_js(__('more times', 'loyalty-program')); ?>');
            $container.find('.purchases-left').text(purchasesLeftText);
        }
    }

    // Function to check and update based on current variation
    function checkCurrentVariation() {
        var variationId = $('input[name="variation_id"]').val();
        if (variationId) {
            updateProgress(parseInt(variationId));
        } else {
            // No variation selected - hide container
            $container.hide();
        }
    }

    // Listen for variation change (WooCommerce events)
    $(document.body).on('found_variation', function(event, variation) {
        if (variation && variation.variation_id) {
            updateProgress(variation.variation_id);
        } else {
            $container.hide();
        }
    });

    // Listen to WooCommerce variation change event
    $(document.body).on('woocommerce_variation_has_changed', function() {
        setTimeout(checkCurrentVariation, 100);
    });

    // Also listen to change events on variation select/inputs
    $('form.variations_form').on('change', 'select, input', function() {
        setTimeout(checkCurrentVariation, 100);
    });

    // Initial check if variation is already selected (after page load)
    setTimeout(function() {
        checkCurrentVariation();
    }, 200);
});
</script>
<?php endif; ?>
<?php
        return ob_get_clean();
    }

    /**
     * Discipline Products List Shortcode
     * Display list of all products with Supplementation Discipline enabled and user's progress
     * 
     * Usage: [loyalty_discipline_products_list]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function discipline_products_list_shortcode($atts)
    {
        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p class="loyalty-message">' . __('Please log in to see your supplementation discipline progress.', 'loyalty-program') . '</p>';
        }

        $user_id = get_current_user_id();

        // Check if user is a member
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return '<p class="loyalty-message">' . __('You need to join the loyalty program first.', 'loyalty-program') . '</p>';
        }

        // Get all products with discipline enabled
        global $wpdb;
        $product_ids = $wpdb->get_col(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_loyalty_discipline_enabled' 
            AND meta_value = 'yes'"
        );

        if (empty($product_ids)) {
            return '<p class="loyalty-message">' . __('No products available in the supplementation discipline program.', 'loyalty-program') . '</p>';
        }

        // Get user's purchase history
        $detailed_history = get_user_meta($user_id, 'loyalty_program_detailed_purchase_history', true);
        if (!is_array($detailed_history)) {
            $detailed_history = array();
        }

        // Get current time and 3 months ago
        $current_time = current_time('timestamp');
        $three_months_ago = $current_time - (90 * DAY_IN_SECONDS);

        // Get points value
        $discipline_points = get_option('loyalty_program_points_supplementation_discipline', 50);

        ob_start();
    ?>
<div class="loyalty-discipline-products-list">
    <div class="discipline-list-header">
        <h3><?php _e('Supplementation Discipline - Your Progress', 'loyalty-program'); ?></h3>
        <p class="description">
            <?php printf(
                        __('Purchase each product 3 times within 3 months to earn %d bonus points!', 'loyalty-program'),
                        $discipline_points
                    ); ?>
        </p>
    </div>

    <div class="discipline-products-grid">
        <?php
                $has_in_progress = false;
                foreach ($product_ids as $post_id) :
                    $product = wc_get_product($post_id);
                    if (!$product) {
                        continue;
                    }

                    // Skip variable products (parent) - only show variations
                    if ($product->is_type('variable')) {
                        continue;
                    }

                    $tracked_id = $post_id;

                    // Get purchase history for this tracked ID (variant or product)
                    $purchases = isset($detailed_history[$tracked_id]) ? $detailed_history[$tracked_id] : array();

                    // Filter to recent purchases (last 3 months)
                    $recent_purchases = array_filter($purchases, function ($timestamp) use ($three_months_ago) {
                        return $timestamp >= $three_months_ago;
                    });

                    $purchases_count = count($recent_purchases);
                    $remaining = max(0, 3 - $purchases_count);

                    // Calculate days remaining
                    $days_remaining = 0;
                    if (!empty($recent_purchases)) {
                        $first_purchase = min($recent_purchases);
                        $three_months_from_first = $first_purchase + (90 * DAY_IN_SECONDS);
                        $days_remaining = max(0, floor(($three_months_from_first - $current_time) / DAY_IN_SECONDS));
                    }

                    // Calculate progress percentage
                    $progress_percent = min(100, ($purchases_count / 3) * 100);

                    $status_class = '';
                    if ($purchases_count >= 3) {
                        $status_class = 'completed';
                    } elseif ($purchases_count > 0) {
                        $status_class = 'in-progress';
                    } else {
                        $status_class = 'not-started';
                    }

                    // Show only in-progress products
                    if ($status_class !== 'in-progress') {
                        continue;
                    }

                    $has_in_progress = true;
                ?>
        <div class="discipline-product-item <?php echo esc_attr($status_class); ?>">
            <div class="product-thumbnail">
                <?php echo $product->get_image('thumbnail'); ?>
            </div>
            <div class="product-details">
                <h4 class="product-title">
                    <a href="<?php echo esc_url($product->get_permalink()); ?>">
                        <?php echo esc_html($product->get_name()); ?>
                    </a>
                </h4>

                <div class="product-progress">
                    <div class="progress-info">
                        <span class="purchases-count">
                            <?php printf(__('Purchased: %d time(s)', 'loyalty-program'), $purchases_count); ?>
                        </span>
                        <?php if ($purchases_count > 0 && $purchases_count < 3) : ?>
                        <span class="purchases-needed">
                            <?php printf(__('Buy %d more time(s)', 'loyalty-program'), $remaining); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="progress-bar-wrapper">
                        <div class="progress-bar-fill" style="width: <?php echo esc_attr($progress_percent); ?>%;">
                            <span class="progress-text"><?php echo round($progress_percent); ?>%</span>
                        </div>
                    </div>

                    <?php if ($purchases_count > 0 && $purchases_count < 3 && $days_remaining > 0) : ?>
                    <p class="time-remaining">
                        <span class="dashicons dashicons-clock"></span>
                        <?php printf(__('%d days remaining', 'loyalty-program'), $days_remaining); ?>
                    </p>
                    <?php endif; ?>

                    <?php if ($purchases_count >= 3) : ?>
                    <p class="status-completed">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Completed! Bonus awarded', 'loyalty-program'); ?>
                    </p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        <?php endforeach; // End product_ids loop 

                if (!$has_in_progress) {
                    echo '<p class="loyalty-message">' . __('No products are currently in progress.', 'loyalty-program') . '</p>';
                }
                ?>
    </div>
</div>


<?php
        return ob_get_clean();
    }

    /**
     * Attendance Action Shortcode
     * Display clickable element (button or text) that awards points when clicked
     * Only active within specified time range
     * 
     * Usage: [loyalty_attendance_action id="action_123456789"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function attendance_action_shortcode($atts)
    {
        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        // Parse attributes
        $atts = shortcode_atts(array(
            'id' => '',
        ), $atts);

        $action_id = sanitize_text_field($atts['id']);

        if (empty($action_id)) {
            return '<p class="loyalty-error-message">' . __('Action ID is required.', 'loyalty-program') . '</p>';
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
            return '<p class="loyalty-error-message">' . __('Action not found.', 'loyalty-program') . '</p>';
        }

        // Check if action is enabled
        if ($action['enabled'] !== 'yes') {
            return ''; // Don't show disabled actions
        }

        // Load logger for debugging
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
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
        $is_after = ($current_time > $date_to);

        // Debug logging
        Loyalty_Program_Logger::info('=== ATTENDANCE ACTION CHECK ===', array(
            'action_id' => $action_id,
            'action_name' => $action['name'],
            'current_time' => $current_time,
            'date_from_original' => $action['date_from'],
            'date_to_original' => $action['date_to'],
            'date_from_converted' => $date_from,
            'date_to_converted' => $date_to,
            'is_active' => $is_active,
            'is_after' => $is_after,
            'comparison_from' => array(
                'current >= from' => $current_time >= $date_from,
                'current' => $current_time,
                'from' => $date_from,
            ),
            'comparison_to' => array(
                'current <= to' => $current_time <= $date_to,
                'current' => $current_time,
                'to' => $date_to,
            ),
        ));

        // If not visible after time and time has passed, don't show
        if ($is_after && $action['visible_after'] !== 'yes') {
            return '';
        }

        // Check if user is logged in
        $user_id = 0;
        $is_logged_in = is_user_logged_in();

        if ($is_logged_in) {
            $user_id = get_current_user_id();

            // Check if user is a member
            if (!Loyalty_Program_Points::is_member($user_id)) {
                return '<p class="loyalty-message">' . __('Join the loyalty program to participate.', 'loyalty-program') . '</p>';
            }

            // Check if user already clicked this action
            $user_clicked_actions = get_user_meta($user_id, 'loyalty_program_attendance_actions', true);
            if (!is_array($user_clicked_actions)) {
                $user_clicked_actions = array();
            }

            $already_clicked = in_array($action_id, $user_clicked_actions);
        } else {
            $already_clicked = false;
        }

        // Determine status
        $status = 'active';
        $status_message = '';
        $can_click = false;

        if (!$is_logged_in) {
            $status = 'login-required';
            $status_message = __('Log in to participate', 'loyalty-program');
        } elseif ($already_clicked) {
            $status = 'completed';
            $status_message = __('Already completed ✓', 'loyalty-program');
        } elseif (!$is_active && !$is_after) {
            $status = 'not-started';
            $status_message = __('Not yet available', 'loyalty-program');
        } elseif ($is_after) {
            $status = 'expired';
            $status_message = __('Time expired', 'loyalty-program');
        } else {
            $can_click = true;
        }

        $element_type = $action['type']; // 'button' or 'text'
        $button_style = isset($action['button_style']) ? $action['button_style'] : 'dark'; // 'dark' or 'light'
        $element_class = 'loyalty-attendance-' . $element_type;

        ob_start();
    ?>
<span class="loyalty-attendance-action <?php echo esc_attr($status); ?>"
    data-action-id="<?php echo esc_attr($action_id); ?>" data-can-click="<?php echo $can_click ? '1' : '0'; ?>"
    data-points="<?php echo esc_attr($action['points']); ?>">
    <span class="action-response-message" style="display:none;"></span>
    <?php if ($element_type === 'button') : ?>
    <button type="button"
        class="loyalty-attendance-button loyalty-button-<?php echo esc_attr($button_style); ?> <?php echo $can_click ? 'clickable' : 'disabled'; ?>"
        <?php echo !$can_click ? 'disabled' : ''; ?>>
        <?php echo esc_html($action['content']); ?>
    </button>
    <?php else : ?>
    <span class="loyalty-attendance-text <?php echo $can_click ? 'clickable' : 'disabled'; ?>">
        <?php echo esc_html($action['content']); ?>
    </span>
    <?php endif; ?>


</span>

<?php
        return ob_get_clean();
    }

    /**
     * Survey/Quiz Shortcode
     * Display a survey or quiz for users to complete
     * 
     * Usage: [loyalty_survey id="survey_123"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function survey_shortcode($atts)
    {
        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p class="loyalty-message">' . __('Please log in to access this survey/quiz.', 'loyalty-program') . '</p>';
        }

        $user_id = get_current_user_id();

        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if user is a member
        if (!Loyalty_Program_Points::is_member($user_id)) {
            return '<p class="loyalty-message">' . __('You need to join the loyalty program first.', 'loyalty-program') . '</p>';
        }

        // Get survey ID from shortcode
        $atts = shortcode_atts(array(
            'id' => '',
        ), $atts);

        $survey_id = sanitize_text_field($atts['id']);

        if (empty($survey_id)) {
            return '<p class="loyalty-error-message">' . __('Survey ID is required.', 'loyalty-program') . '</p>';
        }

        // Get all surveys
        $surveys = get_option('loyalty_program_surveys', array());

        // Find survey by ID
        $survey = null;
        foreach ($surveys as $s) {
            if ($s['id'] === $survey_id) {
                $survey = $s;
                break;
            }
        }

        if (!$survey) {
            return '<p class="loyalty-error-message">' . __('Survey not found.', 'loyalty-program') . '</p>';
        }

        // Check if survey is enabled
        if ($survey['enabled'] !== 'yes') {
            return '<p class="loyalty-message">' . __('This survey is currently not available.', 'loyalty-program') . '</p>';
        }

        // Check if user already completed this survey
        $completed_surveys = get_user_meta($user_id, 'loyalty_program_completed_surveys', true);
        if (!is_array($completed_surveys)) {
            $completed_surveys = array();
        }

        if (in_array($survey_id, $completed_surveys)) {
            // Show thank you message
            return $this->render_survey_completed($survey);
        }

        // Render survey
        return $this->render_survey_form($survey, $user_id);
    }

    /**
     * Render survey form
     * 
     * @param array $survey Survey data
     * @param int $user_id User ID
     * @return string HTML output
     */
    private function render_survey_form($survey, $user_id)
    {
        $is_quiz = $survey['type'] === 'quiz';

        ob_start();
    ?>
<div id="<?php echo esc_attr($survey['id']); ?>" class="loyalty-survey-container <?php echo esc_attr($survey['id']); ?>"
    data-survey-id="<?php echo esc_attr($survey['id']); ?>"
    data-has-start-button="<?php echo $survey['settings']['showStartButton'] ? '1' : '0'; ?>"
    data-has-timer="<?php echo $survey['settings']['timeLimit'] ? '1' : '0'; ?>"
    data-time-minutes="<?php echo esc_attr($survey['settings']['timeMinutes']); ?>"
    data-pagination="<?php echo isset($survey['settings']['pagination']) && $survey['settings']['pagination'] ? '1' : '0'; ?>"
    data-submit-button-text="<?php echo esc_attr(!empty($survey['settings']['submitButtonText']) ? $survey['settings']['submitButtonText'] : __('Odbierz nagrodę', 'loyalty-program')); ?>"
    data-total-questions="<?php echo count($survey['questions']); ?>">

    <div class="loyalty-survey-header">
        <?php if (!$survey['settings']['showStartButton']) : ?>
        <h3><?php echo esc_html($survey['name']); ?></h3>
        <?php if (!empty($survey['description'])) : ?>
        <p class="survey-description"><?php echo esc_html($survey['description']); ?></p>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($survey['settings']['timeLimit']) : ?>
        <div class="survey-timer" style="<?php echo $survey['settings']['showStartButton'] ? 'display:none;' : ''; ?>">
            <span class="dashicons dashicons-clock"></span>
            <span class="time-remaining"><?php echo esc_html($survey['settings']['timeMinutes']); ?>:00</span>
        </div>
        <?php endif; ?>

        <?php if ($survey['settings']['showStartButton']) : ?>
        <div class="survey-start-wrapper">
            <button type="button" id="survey-start-btn" class="loyalty-start-survey-btn">
                <?php echo esc_html(!empty($survey['settings']['startButtonText']) ? $survey['settings']['startButtonText'] : __('Start', 'loyalty-program')); ?>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <form id="loyalty-survey-form" class="loyalty-survey-form"
        style="<?php echo $survey['settings']['showStartButton'] ? 'display:none;' : ''; ?>">
        <?php wp_nonce_field('loyalty_submit_survey_' . $survey['id'], 'survey_nonce'); ?>
        <input type="hidden" name="survey_id" value="<?php echo esc_attr($survey['id']); ?>">

        <div class="survey-questions">
            <?php
                    $questions = $survey['questions'];

                    // Randomize if enabled
                    if ($survey['settings']['randomOrder']) {
                        shuffle($questions);
                    }

                    foreach ($questions as $q_index => $question) :
                        $question_id = 'q_' . $q_index;
                    ?>
            <div class="survey-question <?php echo !empty($question['required']) ? 'required-question' : ''; ?>"
                data-question-index="<?php echo esc_attr($q_index); ?>"
                data-required="<?php echo !empty($question['required']) ? '1' : '0'; ?>">
                <div class="question-header">
                    <span class="question-number"><?php echo ($q_index + 1); ?>.</span>
                    <h4 class="question-text">
                        <?php echo esc_html($question['text']); ?>
                        <?php if (!empty($question['required'])) : ?>
                        <span class="required-indicator" style="color: #dc2626;">*</span>
                        <?php endif; ?>
                    </h4>
                </div>

                <?php if (!empty($question['description'])) : ?>
                <p class="question-description"><?php echo esc_html($question['description']); ?></p>
                <?php endif; ?>

                <div class="question-answers" data-answer-type="<?php echo esc_attr($question['answerType']); ?>">
                    <?php
                                switch ($question['answerType']) {
                                    case 'radio':
                                        foreach ($question['answers'] as $a_index => $answer) :
                                ?>
                    <label class="answer-option">
                        <input type="radio" name="answers[<?php echo esc_attr($q_index); ?>]"
                            value="<?php echo esc_attr($a_index); ?>"
                            data-correct="<?php echo $is_quiz && isset($answer['correct']) && $answer['correct'] ? '1' : '0'; ?>"
                            data-points="<?php echo $is_quiz && isset($answer['points']) ? esc_attr($answer['points']) : '0'; ?>">
                        <span class="answer-text"><?php echo esc_html($answer['text']); ?></span>
                    </label>
                    <?php
                                        endforeach;
                                        break;

                                    case 'checkbox':
                                        foreach ($question['answers'] as $a_index => $answer) :
                                        ?>
                    <label class="answer-option">
                        <input type="checkbox" name="answers[<?php echo esc_attr($q_index); ?>][]"
                            value="<?php echo esc_attr($a_index); ?>"
                            data-correct="<?php echo $is_quiz && isset($answer['correct']) && $answer['correct'] ? '1' : '0'; ?>"
                            data-points="<?php echo $is_quiz && isset($answer['points']) ? esc_attr($answer['points']) : '0'; ?>">
                        <span class="answer-text"><?php echo esc_html($answer['text']); ?></span>
                    </label>
                    <?php
                                        endforeach;
                                        break;

                                    case 'text':
                                        ?>
                    <input type="text" name="answers[<?php echo esc_attr($q_index); ?>]" class="survey-text-input"
                        placeholder="<?php esc_attr_e('Your answer...', 'loyalty-program'); ?>">
                    <?php
                                        break;

                                    case 'number':
                                    ?>
                    <input type="number" name="answers[<?php echo esc_attr($q_index); ?>]" class="survey-number-input"
                        placeholder="<?php esc_attr_e('Enter a number', 'loyalty-program'); ?>">
                    <?php
                                        break;

                                    case 'textarea':
                                    ?>
                    <textarea name="answers[<?php echo esc_attr($q_index); ?>]" class="survey-textarea-input" rows="5"
                        placeholder="<?php esc_attr_e('Your answer...', 'loyalty-program'); ?>"></textarea>
                    <?php
                                        break;

                                    case 'rating':
                                    ?>
                    <div class="rating-stars" data-question="<?php echo esc_attr($q_index); ?>">
                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <span class="star" data-value="<?php echo $i; ?>">
                            <svg class="star-icon" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"
                                    stroke="#dcdcde" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    fill="#dcdcde" />
                            </svg>
                        </span>
                        <?php endfor; ?>
                        <input type="hidden" name="answers[<?php echo esc_attr($q_index); ?>]" value="">
                    </div>
                    <?php
                                        break;
                                }
                                ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="survey-footer">
            <?php if (isset($survey['settings']['pagination']) && $survey['settings']['pagination']) : ?>
            <!-- Pagination Footer -->
            <div class="survey-pagination-footer">

                <div class="survey-pagination-buttons">
                    <button type="button" class="loyalty-survey-quit-btn">
                        <?php _e('Quit', 'loyalty-program'); ?>
                    </button>
                    <div class="survey-pagination-progress">
                        <span class="survey-question-counter">
                            <?php printf(__('Question %s of %s', 'loyalty-program'), '<span class="current-question-num">1</span>', '<span class="total-questions-num">' . count($survey['questions']) . '</span>'); ?>
                        </span>
                    </div>
                    <button type="button" class="loyalty-survey-next-btn" disabled>
                        <?php _e('Next', 'loyalty-program'); ?>
                    </button>
                    <button type="submit" id="submit-survey-btn" class="loyalty-submit-survey-btn"
                        style="display:none;">
                        <?php echo !empty($survey['settings']['submitButtonText']) ? esc_html($survey['settings']['submitButtonText']) : __('Odbierz nagrodę', 'loyalty-program'); ?>
                    </button>
                </div>
            </div>
            <?php else : ?>
            <!-- Standard Footer -->
            <button type="submit" id="submit-survey-btn" class="loyalty-submit-survey-btn">
                <?php echo $is_quiz ? __('Submit Quiz', 'loyalty-program') : __('Submit Survey', 'loyalty-program'); ?>
            </button>
            <?php endif; ?>
        </div>

        <div id="survey-result"></div>
    </form>
</div>
<?php
        return ob_get_clean();
    }

    /**
     * Render survey completed message
     * 
     * @param array $survey Survey data
     * @return string HTML output
     */
    private function render_survey_completed($survey)
    {
        ob_start();
    ?>
<div class="loyalty-survey-completed">
    <div class="loyalty-survey-completed-content">
        <div class="loyalty-survey-completed-content-header">

            <h3>
                <?php
                        echo !empty($survey['settings']['thankTitle'])
                            ? esc_html($survey['settings']['thankTitle'])
                            : __('Thank you!', 'loyalty-program');
                        ?>
            </h3>
        </div>
        <div class="loyalty-survey-completed-content-body">
            <div class="completed-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="none">
                    <path
                        d="M42.75 13.4636H33.48C34.3819 13.4351 35.2593 13.1636 36.0197 12.6778C36.78 12.192 37.3951 11.5099 37.8 10.7035C40.2292 5.54051 33.2871 1.1839 29.6925 5.62617L24 12.8636L18.3075 5.62605C17.8163 5.01571 17.1846 4.53348 16.4663 4.22063C15.748 3.90778 14.9647 3.77363 14.1832 3.82967C13.4018 3.8857 12.6456 4.13026 11.9793 4.54239C11.3131 4.95452 10.7566 5.52197 10.3575 6.19615C9.97275 6.87921 9.75941 7.6454 9.7358 8.42901C9.71218 9.21262 9.87899 9.99027 10.2219 10.6953C10.5648 11.4002 11.0736 12.0115 11.7047 12.4767C12.3358 12.9419 13.0702 13.247 13.8451 13.366L14.52 13.4636H5.25C4.65341 13.464 4.08138 13.7012 3.65952 14.1231C3.23767 14.5449 3.00046 15.117 3 15.7136V21.7136C3.00175 22.3098 3.23936 22.881 3.66094 23.3026C4.08252 23.7242 4.6538 23.9618 5.25 23.9636H6V41.9636C6.00175 42.5598 6.23936 43.131 6.66094 43.5526C7.08252 43.9742 7.6538 44.2118 8.25 44.2136H39.75C40.3462 44.2118 40.9175 43.9742 41.3391 43.5526C41.7606 43.131 41.9983 42.5598 42 41.9636V23.9636H42.75C43.3462 23.9618 43.9175 23.7242 44.3391 23.3026C44.7606 22.881 44.9983 22.3098 45 21.7136V15.7136C44.9995 15.117 44.7623 14.5449 44.3405 14.1231C43.9186 13.7013 43.3466 13.4641 42.75 13.4636ZM30.87 6.55609C31.2046 6.131 31.6383 5.79439 32.1331 5.5757C32.628 5.35701 33.1688 5.2629 33.7085 5.3016C34.2481 5.34031 34.77 5.51065 35.2285 5.79772C35.687 6.08479 36.0683 6.47985 36.3388 6.94834C36.6093 7.41683 36.761 7.94448 36.7804 8.48512C36.7998 9.02577 36.6865 9.56294 36.4503 10.0496C36.2141 10.5364 35.8622 10.9578 35.4255 11.277C34.9888 11.5963 34.4804 11.8037 33.945 11.881L25.7475 13.0661L30.87 6.55609ZM14.055 11.8811C10.0232 11.2428 10.4573 5.3362 14.5426 5.28859C15.0421 5.2881 15.535 5.4021 15.9835 5.62183C16.4321 5.84155 16.8243 6.16115 17.13 6.55609L22.2525 13.0661L14.055 11.8811ZM20.25 42.7136H8.25C8.05127 42.713 7.86085 42.6338 7.72033 42.4933C7.5798 42.3527 7.50059 42.1623 7.5 41.9636V23.9636H20.25V42.7136ZM20.25 22.4636H5.25C5.05127 22.463 4.86085 22.3838 4.72033 22.2433C4.5798 22.1027 4.50059 21.9123 4.5 21.7136V15.7136C4.49966 15.615 4.51882 15.5173 4.55639 15.4262C4.59396 15.335 4.64919 15.2522 4.71891 15.1825C4.78862 15.1128 4.87143 15.0575 4.96258 15.02C5.05373 14.9824 5.15141 14.9632 5.25 14.9636H19.44C17.3475 16.0061 15.0825 17.6111 14.1225 19.9211C14.0846 20.0122 14.065 20.1099 14.0648 20.2086C14.0646 20.3072 14.0839 20.405 14.1215 20.4962C14.1591 20.5874 14.2143 20.6704 14.284 20.7403C14.3537 20.8102 14.4364 20.8657 14.5275 20.9036C14.6186 20.9415 14.7163 20.9611 14.815 20.9613C14.9136 20.9614 15.0114 20.9422 15.1026 20.9046C15.1939 20.867 15.2768 20.8118 15.3467 20.7421C15.4166 20.6724 15.4721 20.5897 15.51 20.4986C16.3125 18.5561 18.3525 17.1611 20.25 16.2386V22.4636ZM26.0175 42.7136H21.75V15.5936C23.5032 14.8392 24.2934 14.7428 26.0175 15.5037V42.7136ZM40.5 41.9636C40.4994 42.1623 40.4202 42.3527 40.2797 42.4933C40.1392 42.6338 39.9487 42.713 39.75 42.7136H27.5175V23.9636H40.5V41.9636ZM43.5 21.7136C43.4994 21.9123 43.4202 22.1027 43.2797 22.2433C43.1392 22.3838 42.9487 22.463 42.75 22.4636H27.5175V16.1261C29.475 17.0486 31.65 18.4736 32.49 20.4986C32.5688 20.6792 32.7155 20.8217 32.8983 20.8952C33.0812 20.9688 33.2856 20.9675 33.4676 20.8918C33.6496 20.8161 33.7945 20.6719 33.8712 20.4903C33.9478 20.3087 33.9501 20.1043 33.8775 19.9211C32.9175 17.6111 30.6525 16.0061 28.56 14.9636H42.75C42.8486 14.9633 42.9463 14.9825 43.0374 15.02C43.1286 15.0576 43.2114 15.1128 43.2811 15.1825C43.3508 15.2523 43.406 15.3351 43.4436 15.4262C43.4812 15.5174 43.5003 15.6151 43.5 15.7136V21.7136Z"
                        fill="#C29B36" />
                </svg>
            </div>
            <p>
                <?php
                        echo !empty($survey['settings']['thankMessage'])
                            ? esc_html($survey['settings']['thankMessage'])
                            : __('You have already completed this survey. Thank you for your participation!', 'loyalty-program');
                        ?>
            </p>

            <?php
                    // Show points earned if completion points are set
                    $completion_points = isset($survey['settings']['completionPoints']) ? absint($survey['settings']['completionPoints']) : 0;
                    if ($completion_points > 0) :
                    ?>
            <div class="survey-points-earned">
                <strong>+<?php echo number_format_i18n($completion_points); ?></strong> pkt
            </div>
            <?php endif; ?>
        </div>
        <div class="loyalty-survey-completed-content-footer">
            <?php
                    // Get loyalty program page for back button
                    $loyalty_page_id = get_option('loyalty_program_page_id');
                    $loyalty_page_url = $loyalty_page_id ? get_permalink($loyalty_page_id) : home_url();
                    ?>
            <div class="survey-back-button">
                <a href="<?php echo esc_url($loyalty_page_url); ?>" class="loyalty-back-to-program-btn">
                    <?php _e('Back', 'loyalty-program'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
<?php
        return ob_get_clean();
    }

    /**
     * Join button shortcode - Simple button to join loyalty program
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function join_button_shortcode($atts)
    {
        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p class="loyalty-message">' . __('Please log in to join the loyalty program.', 'loyalty-program') . '</p>';
        }

        $user_id = get_current_user_id();

        // Check if already a member
        if (Loyalty_Program_Points::is_member($user_id)) {
            $atts = shortcode_atts(array(
                'show_message' => 'yes',
            ), $atts);

            if ($atts['show_message'] === 'yes') {
                return '<div class="loyalty-join-success"><span class="dashicons dashicons-yes-alt"></span> ' . __('You are already enrolled in the loyalty program!', 'loyalty-program') . '</div>';
            }
            return ''; // Don't show anything if already member and show_message is no
        }

        // Parse attributes
        $atts = shortcode_atts(array(
            'text' => __('Join Loyalty Program', 'loyalty-program'),
            'class' => '',
        ), $atts);

        $html = '<div class="loyalty-join-button-wrapper">';
        $html .= '<button type="button" class="loyalty-join-btn ' . esc_attr($atts['class']) . '" data-user-id="' . esc_attr($user_id) . '">';
        $html .= '<span class="dashicons dashicons-star-filled"></span> ';
        $html .= esc_html($atts['text']);
        $html .= '</button>';
        $html .= '<div class="loyalty-join-status"></div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Join button reload shortcode - Simple button to join loyalty program with immediate reload
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function join_button_reload_shortcode($atts)
    {
        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        // Parse attributes
        $atts = shortcode_atts(array(
            'class' => '',
        ), $atts);

        // Check if user is logged in
        $is_logged_in = is_user_logged_in();
        $user_id = $is_logged_in ? get_current_user_id() : 0;

        // If logged in and already a member, don't show button
        if ($is_logged_in && Loyalty_Program_Points::is_member($user_id)) {
            return ''; // Don't show anything if already member
        }

        // Get current page URL for redirect after registration/login
        $current_url = home_url(add_query_arg(array(), $_SERVER['REQUEST_URI']));
        $redirect_url = esc_url_raw($current_url);

        // Get registration URL
        $registration_url = '';
        if (class_exists('WooCommerce')) {
            // WooCommerce: Use My Account page with registration
            $myaccount_url = wc_get_page_permalink('myaccount');
            if ($myaccount_url) {
                $registration_url = add_query_arg(array(
                    'action' => 'register',
                    'redirect' => $redirect_url
                ), $myaccount_url);
            }
        } else {
            // Standard WordPress registration - add redirect parameter
            $registration_url = add_query_arg('redirect_to', $redirect_url, wp_registration_url());
        }

        // If no registration URL found, use login page with redirect
        if (empty($registration_url)) {
            $registration_url = wp_login_url($redirect_url);
        }

        $html = '<div class="loyalty-join-button-reload-wrapper">';
        $html .= '<button type="button" class="loyalty-join-btn-reload ' . esc_attr($atts['class']) . '" ';

        if ($is_logged_in) {
            $html .= 'data-user-id="' . esc_attr($user_id) . '" ';
        } else {
            $html .= 'data-not-logged-in="1" ';
            $html .= 'data-registration-url="' . esc_attr($registration_url) . '" ';
        }

        $html .= '>';
        $html .= esc_html(__('Join', 'loyalty-program'));
        $html .= '</button>';
        $html .= '<div class="loyalty-join-status-reload"></div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Convert amount from base currency (PLN) to current active currency
     * 
     * @param float $amount Amount in base currency
     * @return float Amount in current currency
     */
    private function convert_from_base_currency($amount)
    {
        // Check if WooCommerce Multi Currency is active
        if (!class_exists('WOOMULTI_CURRENCY')) {
            return $amount; // No conversion needed
        }

        // Get current currency
        $current_currency = get_woocommerce_currency();
        $base_currency = get_option('woocommerce_currency', 'PLN');

        // If same currency, no conversion needed
        if ($current_currency === $base_currency) {
            return $amount;
        }

        // Get WMC currency settings
        $wmc_params = get_option('woo_multi_currency_params', array());

        if (empty($wmc_params['currency']) || !is_array($wmc_params['currency'])) {
            return $amount;
        }

        // Find the currency exchange rate
        $currencies = $wmc_params['currency'];
        $currency_rates = isset($wmc_params['currency_rate']) ? $wmc_params['currency_rate'] : array();

        $currency_index = array_search($current_currency, $currencies);

        if ($currency_index === false) {
            return $amount;
        }

        // Get exchange rate
        $exchange_rate = isset($currency_rates[$currency_index]) ? floatval($currency_rates[$currency_index]) : 1;

        if ($exchange_rate <= 0) {
            return $amount;
        }

        // Convert from base to current currency
        // If rate is 0.2222222 (1 PLN = 0.2222222 EUR), then: amount_in_eur = amount_in_pln * 0.2222222
        $converted_amount = $amount * $exchange_rate;

        return $converted_amount;
    }

    /**
     * Join button modal shortcode - Button with modal for joining program
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function join_button_modal_shortcode($atts)
    {
        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Check if program is enabled
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        // Parse attributes
        $atts = shortcode_atts(array(
            'text' => __('Join', 'loyalty-program'),
            'class' => '',
        ), $atts);

        $is_logged_in = is_user_logged_in();
        $is_member = false;
        $user_id = 0;

        if ($is_logged_in) {
            $user_id = get_current_user_id();
            $is_member = Loyalty_Program_Points::is_member($user_id);
        }

        // If already a member, don't show button
        if ($is_member) {
            return '';
        }

        // Render only button - modal will be in footer
        ob_start();
    ?>
<button type="button" class="loyalty-join-modal-btn <?php echo esc_attr($atts['class']); ?>"
    data-is-logged-in="<?php echo $is_logged_in ? '1' : '0'; ?>">
    <?php echo esc_html($atts['text']); ?>
</button>




<?php
        return ob_get_clean();
    }

    /**
     * Render join modal in footer (only once per page)
     * 
     * @return void
     */
    public function render_join_modal_in_footer()
    {
        // Check if program is enabled
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        if (!Loyalty_Program_Points::is_program_enabled()) {
            return;
        }

        // Check if user is already a member
        $is_logged_in = is_user_logged_in();
        $is_member = false;
        if ($is_logged_in) {
            $user_id = get_current_user_id();
            $is_member = Loyalty_Program_Points::is_member($user_id);
        }

        // If already a member, don't show modal
        if ($is_member) {
            return;
        }

        // Render modal only once per page
        static $modal_rendered = false;
        if ($modal_rendered) {
            return;
        }
        $modal_rendered = true;

        // Get settings
        $logged_consent_text = get_option('loyalty_program_join_form_logged_consent_text', 'Wyrażam zgodę na dołączenie do programu lojalnościowego MyBestLife Club.');
        $form_header = get_option('loyalty_program_join_form_header', 'Dołącz do programu lojalnościowego');
        $form_description = get_option('loyalty_program_join_form_description', 'Zostań członkiem MyBestLife Club i zacznij zbierać punkty lojalnościowe!');
        $form_points_info = get_option('loyalty_program_join_form_points_info', 'Za dołączenie do programu otrzymasz 100 punktów lojalnościowych.');

        // Newsletter consent (first in order)
        $newsletter_consent_enabled = get_option('loyalty_program_join_form_newsletter_consent_enabled', 'yes');
        $newsletter_consent_text = get_option('loyalty_program_join_form_newsletter_consent_text', 'Wyrażam zgodę na otrzymywanie newslettera.');
        $newsletter_consent_required = get_option('loyalty_program_join_form_newsletter_consent_required', 'no');

        // SMS consent
        $sms_consent_enabled = get_option('loyalty_program_join_form_sms_consent_enabled', 'yes');
        $sms_consent_text = get_option('loyalty_program_join_form_sms_consent_text', 'Wyrażam zgodę na otrzymywanie wiadomości SMS.');
        $sms_consent_required = get_option('loyalty_program_join_form_sms_consent_required', 'no');

        // Terms consent (Regulamin)
        $terms_consent_enabled = get_option('loyalty_program_join_form_terms_consent_enabled', 'yes');
        $terms_consent_text = get_option('loyalty_program_join_form_terms_consent_text', 'Akceptuję <a href="#">regulamin</a>.');
        $terms_consent_required = get_option('loyalty_program_join_form_terms_consent_required', 'yes');

        // Custom consents
        $custom_consents = get_option('loyalty_program_join_form_custom_consents', array());

        // Generate unique IDs for form fields
        $field_id_prefix = 'loyalty-join-';
    ?>
<!-- Loyalty Program Join Modal -->
<?php if ($is_logged_in) : ?>
<!-- Modal for logged in users -->
<div id="loyalty-join-modal-logged" class="loyalty-join-modal loyalty-join-modal-logged-in" style="display: none;">
    <div class="loyalty-join-modal-content">
        <div class="loyalty-join-modal-content-header">
            <h2><?php _e('Join Loyalty Program', 'loyalty-program'); ?></h2>
            <span class="loyalty-join-modal-close">x</span>
        </div>
        <div class="loyalty-join-modal-content-form">
            <form class="loyalty-join-form">
                <div class="loyalty-form-group">
                    <label>
                        <input type="checkbox" name="consent" value="yes" required>
                        <div class="consent_text"><?php echo wp_kses_post($logged_consent_text); ?><span
                                class="required">*</span></div>

                    </label>
                </div>
                <div class="loyalty-form-actions">
                    <button type="submit" class="button button-join">
                        <?php _e('Join!', 'loyalty-program'); ?>
                    </button>
                </div>

            </form>
        </div>
        <div class="loyalty-form-message"></div>
    </div>

</div>
<?php else : ?>
<!-- Modal for not logged in users -->
<div id="loyalty-join-modal-register" class="loyalty-join-modal loyalty-join-modal-not-logged-in"
    style="display: none;">
    <div class="loyalty-join-modal-content">
        <div class="loyalty-join-modal-content-header">
            <?php if ($form_header) : ?>
            <h2><?php echo esc_html($form_header); ?></h2>
            <?php endif; ?>
            <span class="loyalty-join-modal-close">x</span>
        </div>
        <div class="loyalty-join-modal-content-body">
            <?php if ($form_description) : ?>
            <p class="loyalty-form-description"><?php echo wp_kses_post($form_description); ?></p>
            <?php endif; ?>
            <?php if ($form_points_info) : ?>
            <p class="loyalty-form-points-info"><?php echo wp_kses_post($form_points_info); ?></p>
            <?php endif; ?>
        </div>
        <div class="loyalty-join-modal-content-form">
            <form class="loyalty-join-form-register">
                <div class="loyalty-form-group-personal-data">
                    <div class="loyalty-form-group">
                        <input type="text" name="first_name" id="<?php echo esc_attr($field_id_prefix); ?>first_name"
                            required placeholder="<?= __('First Name', 'loyalty-program'); ?>*">
                    </div>
                    <div class="loyalty-form-group">

                        <input type="email" name="email" id="<?php echo esc_attr($field_id_prefix); ?>email" required
                            placeholder="<?= __('Email', 'loyalty-program'); ?>*">
                    </div>
                    <div class="loyalty-form-group">
                        <input type="tel" name="phone" id="<?php echo esc_attr($field_id_prefix); ?>phone"
                            placeholder="<?= __('Phone Number', 'loyalty-program'); ?>">
                    </div>
                    <div class="loyalty-form-group">
                        <label for="<?php echo esc_attr($field_id_prefix); ?>birth_date">
                            <?php _e('Enter your date of birth and unlock your first reward today.', 'loyalty-program'); ?>
                        </label>
                        <input type="date" name="birth_date" id="<?php echo esc_attr($field_id_prefix); ?>birth_date"
                            placeholder="<?= __('00.00.0000', 'loyalty-program'); ?>*">
                    </div>
                </div>
                <div class="loyalty-form-group-consents">
                    <!-- Consents (Newsletter, SMS, Regulamin, Custom) -->
                    <?php if ($newsletter_consent_enabled === 'yes') : ?>
                    <div class="loyalty-form-group">
                        <label>
                            <input type="checkbox" name="newsletter_consent" value="yes"
                                <?php echo $newsletter_consent_required === 'yes' ? 'required' : ''; ?>>
                            <div class="consent_text"><?php echo wp_kses_post($newsletter_consent_text); ?>
                                <?php if ($newsletter_consent_required === 'yes') : ?>
                                <span class="required">*</span>
                                <?php endif; ?>
                            </div>

                        </label>
                    </div>
                    <?php endif; ?>

                    <?php if ($sms_consent_enabled === 'yes') : ?>
                    <div class="loyalty-form-group">
                        <label>
                            <input type="checkbox" name="sms_consent" value="yes"
                                <?php echo $sms_consent_required === 'yes' ? 'required' : ''; ?>>
                            <div class="consent_text"><?php echo wp_kses_post($sms_consent_text); ?>
                                <?php if ($sms_consent_required === 'yes') : ?>
                                <span class="required">*</span>
                                <?php endif; ?>
                            </div>

                        </label>
                    </div>
                    <?php endif; ?>

                    <?php if ($terms_consent_enabled === 'yes') : ?>
                    <div class="loyalty-form-group">
                        <label>
                            <input type="checkbox" name="terms_consent" value="yes"
                                <?php echo $terms_consent_required === 'yes' ? 'required' : ''; ?>>
                            <div class="consent_text"><?php echo wp_kses_post($terms_consent_text); ?>
                                <?php if ($terms_consent_required === 'yes') : ?>
                                <span class="required">*</span>
                                <?php endif; ?>
                            </div>

                        </label>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($custom_consents)) : ?>
                    <?php foreach ($custom_consents as $index => $consent) : ?>
                    <div class="loyalty-form-group">
                        <label>
                            <input type="checkbox" name="custom_consent_<?php echo esc_attr($index); ?>" value="yes"
                                <?php echo $consent['required'] === 'yes' ? 'required' : ''; ?>>
                            <div class="consent_text"><?php echo wp_kses_post($consent['text']); ?>
                                <?php if ($consent['required'] === 'yes') : ?>
                                <span class="required">*</span>
                                <?php endif; ?>
                            </div>

                        </label>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p class="loyalty-form-required-text"><?= __('* Required field.', 'loyalty-program'); ?></p>

                <div class="loyalty-form-actions">
                    <button type="submit" class="button button-join">
                        <?php _e('Join!', 'loyalty-program'); ?>
                    </button>
                </div>

            </form>
        </div>
        <div class="loyalty-form-message"></div>
    </div>
</div>
<?php endif; ?>
<?php
    }

    /**
     * Account fields shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function account_fields_shortcode($atts)
    {
        // Check if program is enabled
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }
        if (!Loyalty_Program_Points::is_program_enabled()) {
            return '';
        }

        if (!is_user_logged_in()) {
            return '<p class="loyalty-message">' . __('Please log in to view your account fields.', 'loyalty-program') . '</p>';
        }

        $user_id = get_current_user_id();



        // Check if any fields are enabled
        $enable_birth_date = get_option('loyalty_program_enable_birth_date', 'no');
        $enable_sms_consent = get_option('loyalty_program_enable_sms_consent', 'no');
        $enable_newsletter_consent = get_option('loyalty_program_enable_newsletter_consent', 'no');
        $enable_billing_phone = get_option('loyalty_program_enable_billing_phone', 'yes');
        $enable_user_coupon = get_option('loyalty_program_enable_user_coupon', 'no');

        if ($enable_birth_date === 'no' && $enable_sms_consent === 'no' && $enable_newsletter_consent === 'no' && $enable_billing_phone === 'no' && $enable_user_coupon === 'no') {
            return '';
        }

        ob_start();
    ?>
<div class="loyalty-program-account-fields">
    <div class="loyalty-program-account-fields-notification">
        <?php if (Loyalty_Program_Points::is_member($user_id)) : ?>
        <div class="notification-text">
            <?php echo esc_html__('You have signed up for MyBestLife Club', 'loyalty-program'); ?>
        </div>
        <?php else : ?>
        <div class="notification-text">
            <?php echo esc_html__('Collect MyBestCoins and exchange them for additional discounts, limited-edition supplement sets, accessories, and much more.', 'loyalty-program'); ?>
        </div>
        <?php endif; ?>
    </div>
    <?php if (Loyalty_Program_Points::is_member($user_id)) : ?>
    <div class="loyalty-program-account-fields-row loyalty-program-account-fields-content">
        <div class="loyalty-program-account-fields-col-left">
            <div class="loyalty-program-account-fields-data">
                <div class="loyalty-program-account-fields-data-header">
                    <h2><?= __('Your data', 'loyalty-program'); ?></h2>
                </div>
                <div class="loyalty-program-account-fields-data-body">
                    <?php if ($enable_birth_date === 'yes' || $enable_billing_phone === 'yes') :
                                    $birth_date = $enable_birth_date === 'yes' ? get_user_meta($user_id, 'loyalty_program_birth_date', true) : '';
                                    $birthday_points = $enable_birth_date === 'yes' ? get_option('loyalty_program_points_birthday', 25) : 0;
                                    $billing_phone = $enable_billing_phone === 'yes' ? get_user_meta($user_id, 'billing_phone', true) : '';
                                ?>
                    <div class="account-data-form-item">
                        <!-- połączony formularz daty i telefonu -->
                        <form method="post" class="account-data-form" id="loyalty-account-data-form">
                            <?php wp_nonce_field('loyalty_program_frontend', 'nonce'); ?>
                            <?php if ($enable_birth_date === 'yes') : ?>
                            <div class="form-field birth-date-field">

                                <label for="loyalty_program_birth_date">
                                    <?php esc_html_e('Enter your date of birth and unlock your first reward today.', 'loyalty-program'); ?>

                                    <input type="date" class="woocommerce-Input woocommerce-Input--text input-text"
                                        name="loyalty_program_birth_date" id="loyalty_program_birth_date"
                                        value="<?php echo esc_attr($birth_date); ?>"
                                        max="<?php echo date('Y-m-d'); ?>" />
                                </label>
                            </div>
                            <?php endif; ?>
                            <?php if ($enable_billing_phone === 'yes') : ?>
                            <div class="form-field phone-number-field">
                                <div class="phone-number-item-text item-text">
                                    <?php esc_html_e('Enter your phone number to unlock a phone number to completing your profile', 'loyalty-program'); ?>
                                </div>
                                <label for="billing_phone">
                                    <?php esc_html_e('Enter your phone number', 'loyalty-program'); ?>

                                    <input type="tel" class="woocommerce-Input woocommerce-Input--text input-text"
                                        name="billing_phone" id="billing_phone"
                                        value="<?php echo esc_attr($billing_phone); ?>"
                                        placeholder="<?php esc_attr_e('Enter your phone number', 'loyalty-program'); ?>" />
                                </label>
                            </div>
                            <?php endif; ?>
                            <div class="form-actions">
                                <button type="submit" class="button save-changes-btn" id="save-account-data-btn">
                                    <?php esc_html_e('Save changes', 'loyalty-program'); ?>
                                </button>
                                <span class="form-message" id="account-data-message"></span>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                    <?php if ($enable_user_coupon === 'yes') : ?>
                    <div class="personal-coupun-item">
                        <div class="personal-coupon-label">
                            <?= __('Your personal coupon', 'loyalty-program'); ?>
                        </div>
                        <!-- kod kuponu -->
                        <?php echo do_shortcode('[loyalty_user_coupon]'); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="loyalty-program-account-fields-col-right">
            <div class="loyalty-program-account-fields-consent">
                <div class="loyalty-program-account-fields-consent-header">
                    <h2><?= __('Marketing consent preferences', 'loyalty-program'); ?></h2>
                    <p class="loyalty-program-account-fields-consent-header-description">
                        <?= __('Manage your communication settings. Giving both consents may unlock additional benefits.', 'loyalty-program'); ?>
                    </p>
                </div>
                <div class="loyalty-program-account-fields-consent-body">
                    <!-- formularz consents -->
                    <form id="loyalty-consents-form" class="consents-form">
                        <?php wp_nonce_field('loyalty_save_consents', 'nonce'); ?>
                        <?php
                                    if ($enable_newsletter_consent === 'yes') {
                                        $newsletter_consent = get_user_meta($user_id, 'loyalty_program_newsletter_consent', true);
                                    ?>
                        <div class="consent-item">
                            <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                                <?= __('Newsletter', 'loyalty-program'); ?>
                                <div class="wrap_consent_checkbox">
                                    <input type="checkbox"
                                        class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
                                        name="newsletter_consent" id="loyalty_newsletter_consent" value="yes"
                                        <?php checked($newsletter_consent, 'yes'); ?> />
                                    <span><?php esc_html_e('I consent to receiving newsletter emails', 'loyalty-program'); ?></span>
                                </div>
                            </label>
                        </div>
                        <?php
                                    }
                                    ?>
                        <?php

                                    if ($enable_sms_consent === 'yes') {
                                        $sms_consent = get_user_meta($user_id, 'loyalty_program_sms_consent', true);
                                    ?>
                        <div class="consent-item">
                            <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                                <?= __('SMS notifications', 'loyalty-program'); ?>
                                <div class="wrap_consent_checkbox">
                                    <input type="checkbox"
                                        class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
                                        name="sms_consent" id="loyalty_sms_consent" value="yes"
                                        <?php checked($sms_consent, 'yes'); ?> />
                                    <span><?php esc_html_e('I consent to receiving SMS notifications', 'loyalty-program'); ?></span>
                                </div>
                        </div>
                        </label>
                </div>
                <?php
                                    }
                        ?>

            </div>
            <div class="loyalty-program-account-fields-consent-footer ">
                <!-- przycisk zapisz zmiany -->
                <button type="submit" class="button save-changes-btn" id="save-consents-btn">
                    <?php esc_html_e('Save changes', 'loyalty-program'); ?>
                </button>
                <span class="form-message" id="consents-message"></span>
                </form>
            </div>
        </div>
    </div>
</div>
<?php else : ?>
<div class="loyalty-program-account-fields-row loyalty-program-account-fields-content">
    <?php
                // Get newsletter points from settings
                $newsletter_points = get_option('loyalty_program_points_notifications', 20);
                // Get birthday points from settings
                $birthday_points = $enable_birth_date === 'yes' ? get_option('loyalty_program_points_birthday', 25) : 0;
            ?>
    <div class="loyalty-program-account-fields-non-member">
        <div class="newsletter-signup-message">
            <?php
                    $newsletter_message = __('By signing up for the newsletter, you will receive an additional %s MyBest Coins!', 'loyalty-program');
                    echo sprintf($newsletter_message, $newsletter_points);
                    ?>
        </div>

        <form method="post" class="account-data-form" id="loyalty-account-data-form-non-member">
            <?php wp_nonce_field('loyalty_program_frontend', 'nonce'); ?>

            <?php if ($enable_billing_phone === 'yes') : ?>
            <div class="form-field phone-number-field">


                <input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_phone"
                    id="billing_phone_non_member" value=""
                    placeholder="<?php esc_attr_e('Enter your phone number', 'loyalty-program'); ?>" />

            </div>
            <?php endif; ?>

            <?php if ($enable_birth_date === 'yes') : ?>
            <div class="form-field birth-date-field">

                <label for="loyalty_program_birth_date_non_member">
                    <?php esc_html_e('Enter your date of birth and unlock your first reward today.', 'loyalty-program'); ?>
                    <input type="date" class="woocommerce-Input woocommerce-Input--text input-text"
                        name="loyalty_program_birth_date" id="loyalty_program_birth_date_non_member" value=""
                        max="<?php echo date('Y-m-d'); ?>" />
                </label>
            </div>
            <?php endif; ?>



            <div class="consents-section">
                <?php
                        // Get join consent text from settings
                        $join_consent_text = get_option('loyalty_program_join_form_logged_consent_text', __('I consent to joining the loyalty program.', 'loyalty-program'));
                        ?>
                <div class="consent-item consent-item-required">
                    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                        <div class="wrap_consent_checkbox">
                            <input type="checkbox"
                                class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
                                name="join_consent" id="loyalty_join_consent_non_member" value="yes" required />
                            <span><?php echo wp_kses_post($join_consent_text); ?> <span class="required">*</span></span>
                        </div>
                    </label>
                </div>
                <?php if ($enable_newsletter_consent === 'yes') : ?>
                <div class="consent-item">
                    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">

                        <div class="wrap_consent_checkbox">
                            <input type="checkbox"
                                class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
                                name="newsletter_consent" id="loyalty_newsletter_consent_non_member" value="yes" />
                            <span><?php esc_html_e('I consent to receiving newsletter emails', 'loyalty-program'); ?></span>
                        </div>
                    </label>
                </div>
                <?php endif; ?>

                <?php if ($enable_sms_consent === 'yes') : ?>
                <div class="consent-item">
                    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">

                        <div class="wrap_consent_checkbox">
                            <input type="checkbox"
                                class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
                                name="sms_consent" id="loyalty_sms_consent_non_member" value="yes" />
                            <span><?php esc_html_e('I consent to receiving SMS notifications', 'loyalty-program'); ?></span>
                        </div>
                    </label>
                </div>
                <?php endif; ?>
            </div>
            <p class="loyalty-form-required-text"><?= __('* Required field.', 'loyalty-program'); ?></p>
            <div class="form-actions">
                <button type="submit" class="button save-changes-btn" id="save-account-data-btn-non-member">
                    <?php esc_html_e('Join', 'loyalty-program'); ?>
                </button>
                <span class="form-message" id="account-data-message-non-member"></span>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
</div>
<?php
        return ob_get_clean();
    }
}