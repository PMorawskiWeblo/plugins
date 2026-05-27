<?php

/**
 * Points Management Class
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Loyalty Program Points Class
 */
class Loyalty_Program_Points
{

    /**
     * Meta key for points history
     * 
     * @var string
     */
    const POINTS_HISTORY_META = 'loyalty_program_points_history';

    /**
     * Meta key for current points balance
     * 
     * @var string
     */
    const CURRENT_POINTS_META = 'loyalty_program_current_points';

    /**
     * Meta key for total earned points
     * 
     * @var string
     */
    const TOTAL_EARNED_META = 'loyalty_program_total_earned';

    /**
     * Meta key for program membership status
     * 
     * @var string
     */
    const MEMBER_STATUS_META = 'loyalty_program_member';

    /**
     * Meta key for join date
     * 
     * @var string
     */
    const JOIN_DATE_META = 'loyalty_program_join_date';

    /**
     * Meta key for notifications consent
     * 
     * @var string
     */
    const NOTIFICATIONS_CONSENT_META = 'loyalty_program_notifications_consent';

    /**
     * Meta key for tracking if signup points were awarded
     * 
     * @var string
     */
    const SIGNUP_POINTS_AWARDED_META = 'loyalty_program_signup_points_awarded';

    /**
     * Point transaction types
     */
    const TYPE_INCREASE = 'increase';
    const TYPE_DECREASE = 'decrease';

    /**
     * Check if loyalty program is enabled
     * 
     * @return bool
     */
    public static function is_program_enabled()
    {
        return get_option('loyalty_program_enabled', 'yes') === 'yes';
    }

    /**
     * Add points to user
     * 
     * @param int $user_id User ID
     * @param int $points Points amount
     * @param string $action Action description
     * @param array $extra_data Additional data to store
     * @return bool
     */
    public static function add_points($user_id, $points, $action, $extra_data = array())
    {
        // Check if program is enabled
        if (!self::is_program_enabled()) {
            return false;
        }

        if ($points <= 0) {
            return false;
        }

        return self::add_transaction($user_id, $points, self::TYPE_INCREASE, $action, $extra_data);
    }

    /**
     * Remove points from user
     * 
     * @param int $user_id User ID
     * @param int $points Points amount
     * @param string $action Action description
     * @param array $extra_data Additional data to store
     * @return bool
     */
    public static function remove_points($user_id, $points, $action, $extra_data = array())
    {
        // Check if program is enabled
        if (!self::is_program_enabled()) {
            return false;
        }

        if ($points <= 0) {
            return false;
        }

        return self::add_transaction($user_id, $points, self::TYPE_DECREASE, $action, $extra_data);
    }

    /**
     * Add transaction to user's points history
     * 
     * @param int $user_id User ID
     * @param int $points Points amount
     * @param string $type Transaction type (increase/decrease)
     * @param string $action Action description
     * @param array $extra_data Additional data
     * @return bool
     */
    private static function add_transaction($user_id, $points, $type, $action, $extra_data = array())
    {
        // Get current points history
        $history = self::get_points_history($user_id);

        // Create transaction record
        $transaction = array(
            'date' => current_time('mysql'),
            'timestamp' => current_time('timestamp'),
            'type' => $type,
            'points' => absint($points),
            'action' => sanitize_text_field($action),
            'extra_data' => $extra_data,
        );

        // Add to history
        $history[] = $transaction;

        // Update history
        update_user_meta($user_id, self::POINTS_HISTORY_META, $history);

        // Update current points balance
        $current_points = self::get_current_points($user_id);
        if ($type === self::TYPE_INCREASE) {
            $new_balance = $current_points + $points;
        } else {
            $new_balance = max(0, $current_points - $points); // Don't allow negative
        }
        update_user_meta($user_id, self::CURRENT_POINTS_META, $new_balance);

        // Update total earned (only for increases)
        if ($type === self::TYPE_INCREASE) {
            $total_earned = self::get_total_earned($user_id);
            update_user_meta($user_id, self::TOTAL_EARNED_META, $total_earned + $points);
        }

        // Log the transaction
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        Loyalty_Program_Logger::info('Points transaction', array(
            'user_id' => $user_id,
            'type' => $type,
            'points' => $points,
            'action' => $action,
            'new_balance' => $new_balance,
        ));

        // Fire action hook
        do_action('loyalty_program_points_changed', $user_id, $points, $type, $action);

        // OPTIMIZED: Cache is NOT cleared here - it will auto-refresh on Dashboard if older than 1 hour
        // Manual refresh available via "Refresh Stats" button

        return true;
    }

    /**
     * Get user's current points balance
     * 
     * @param int $user_id User ID
     * @return int
     */
    public static function get_current_points($user_id)
    {
        $points = get_user_meta($user_id, self::CURRENT_POINTS_META, true);
        return $points ? absint($points) : 0;
    }

    /**
     * Get user's total earned points
     * 
     * @param int $user_id User ID
     * @return int
     */
    public static function get_total_earned($user_id)
    {
        $points = get_user_meta($user_id, self::TOTAL_EARNED_META, true);
        return $points ? absint($points) : 0;
    }

    /**
     * Get user's points history
     * 
     * @param int $user_id User ID
     * @param int $limit Limit number of records (0 = all)
     * @return array
     */
    public static function get_points_history($user_id, $limit = 0)
    {
        $history = get_user_meta($user_id, self::POINTS_HISTORY_META, true);

        if (!is_array($history)) {
            $history = array();
        }

        // Sort by date descending (newest first)
        usort($history, function ($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        // Apply limit if specified
        if ($limit > 0) {
            $history = array_slice($history, 0, $limit);
        }

        return $history;
    }

    /**
     * Get points statistics for user
     * 
     * @param int $user_id User ID
     * @return array
     */
    public static function get_user_stats($user_id)
    {
        $history = self::get_points_history($user_id);

        $stats = array(
            'current_points' => self::get_current_points($user_id),
            'total_earned' => self::get_total_earned($user_id),
            'total_spent' => 0,
            'total_transactions' => count($history),
            'last_transaction_date' => null,
        );

        // Calculate total spent
        foreach ($history as $transaction) {
            if ($transaction['type'] === self::TYPE_DECREASE) {
                $stats['total_spent'] += $transaction['points'];
            }
        }

        // Get last transaction date
        if (!empty($history)) {
            $stats['last_transaction_date'] = $history[0]['date'];
        }

        return $stats;
    }

    /**
     * Reset user points (for testing/admin purposes)
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function reset_points($user_id)
    {
        delete_user_meta($user_id, self::CURRENT_POINTS_META);
        delete_user_meta($user_id, self::TOTAL_EARNED_META);
        delete_user_meta($user_id, self::POINTS_HISTORY_META);

        // Log the reset
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        Loyalty_Program_Logger::warning('User points reset', array('user_id' => $user_id));

        return true;
    }

    /**
     * Get formatted points display
     * 
     * @param int $points Points amount
     * @return string
     */
    public static function format_points($points)
    {
        return number_format_i18n($points) . ' ' . _n('point', 'points', $points, 'loyalty-program');
    }

    /**
     * Check if user is a program member
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function is_member($user_id)
    {
        $is_member = get_user_meta($user_id, self::MEMBER_STATUS_META, true);
        return $is_member === 'yes';
    }

    /**
     * Get user's join date
     * 
     * @param int $user_id User ID
     * @return string|null MySQL datetime or null if not a member
     */
    public static function get_join_date($user_id)
    {
        return get_user_meta($user_id, self::JOIN_DATE_META, true);
    }

    /**
     * Enroll user in loyalty program
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function enroll_user($user_id)
    {
        // Check if program is enabled
        if (!self::is_program_enabled()) {
            return false;
        }

        // Check if already a member
        if (self::is_member($user_id)) {
            return false;
        }

        $join_date = current_time('mysql');

        // Set member status
        update_user_meta($user_id, self::MEMBER_STATUS_META, 'yes');
        update_user_meta($user_id, self::JOIN_DATE_META, $join_date);

        // Award signup points - only if not already awarded
        $signup_points_awarded = get_user_meta($user_id, self::SIGNUP_POINTS_AWARDED_META, true);

        if ($signup_points_awarded !== 'yes') {
            $signup_points = get_option('loyalty_program_points_signup', 100);
            if ($signup_points > 0) {
                self::add_points($user_id, $signup_points, __('Joined loyalty program', 'loyalty-program'));
                // Mark that signup points were awarded
                update_user_meta($user_id, self::SIGNUP_POINTS_AWARDED_META, 'yes');
            }
        }

        // Generate personal coupon if WooCommerce is active
        if (class_exists('WooCommerce')) {
            if (!class_exists('Loyalty_Program_WooCommerce')) {
                require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-woocommerce.php';
            }

            $coupon_code = Loyalty_Program_WooCommerce::generate_personal_coupon($user_id);

            if ($coupon_code) {
                // Log coupon generation
                if (!class_exists('Loyalty_Program_Logger')) {
                    require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
                }

                Loyalty_Program_Logger::info('Personal coupon generated for user', array(
                    'user_id' => $user_id,
                    'coupon_code' => $coupon_code,
                ));
            }
        }

        // Log enrollment
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        Loyalty_Program_Logger::info('User enrolled in loyalty program', array(
            'user_id' => $user_id,
            'join_date' => $join_date,
            'signup_points' => $signup_points ?? 0,
            'signup_points_awarded' => $signup_points_awarded !== 'yes',
        ));

        do_action('loyalty_program_user_enrolled', $user_id, $join_date);

        // OPTIMIZED: Cache is NOT cleared here - it will auto-refresh on Dashboard if older than 1 hour
        // Manual refresh available via "Refresh Stats" button

        return true;
    }

    /**
     * Remove user from loyalty program
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function unenroll_user($user_id)
    {
        delete_user_meta($user_id, self::MEMBER_STATUS_META);
        delete_user_meta($user_id, self::JOIN_DATE_META);

        // Log unenrollment
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        Loyalty_Program_Logger::warning('User unenrolled from loyalty program', array('user_id' => $user_id));

        do_action('loyalty_program_user_unenrolled', $user_id);

        return true;
    }

    /**
     * Get membership info for user
     * 
     * @param int $user_id User ID
     * @return array
     */
    public static function get_membership_info($user_id)
    {
        $is_member = self::is_member($user_id);
        $join_date = self::get_join_date($user_id);

        return array(
            'is_member' => $is_member,
            'join_date' => $join_date,
            'join_date_formatted' => $join_date ? date_i18n(get_option('date_format'), strtotime($join_date)) : null,
        );
    }

    /**
     * Set user's notifications consent and award points if first time
     * 
     * @param int $user_id User ID
     * @param bool $consent True if user consents to notifications
     * @return bool
     */
    public static function set_notifications_consent($user_id, $consent = true)
    {
        // Check if user already has consent set
        $existing_consent = get_user_meta($user_id, self::NOTIFICATIONS_CONSENT_META, true);

        // Update consent
        update_user_meta($user_id, self::NOTIFICATIONS_CONSENT_META, $consent ? 'yes' : 'no');

        // Award points only if this is first time setting consent to yes and user is a member
        if ($consent && $existing_consent !== 'yes' && self::is_member($user_id)) {
            $points = get_option('loyalty_program_points_notifications', 20);
            if ($points > 0) {
                self::add_points($user_id, $points, __('Sign up for notifications', 'loyalty-program'));
            }
        }

        // Log the action
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        Loyalty_Program_Logger::info('Notifications consent updated', array(
            'user_id' => $user_id,
            'consent' => $consent ? 'yes' : 'no',
            'was_existing' => $existing_consent === 'yes',
        ));

        return true;
    }

    /**
     * Check if user has consented to notifications
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function has_notifications_consent($user_id)
    {
        // Check both SMS and Newsletter consents
        $sms_consent = get_user_meta($user_id, 'loyalty_program_sms_consent', true);
        $newsletter_consent = get_user_meta($user_id, 'loyalty_program_newsletter_consent', true);

        // Return true only if both consents are 'yes'
        return $sms_consent === 'yes' && $newsletter_consent === 'yes';
    }

    /**
     * Award points for writing a product review
     * 
     * @param int $user_id User ID
     * @param int $comment_id Review/comment ID (optional)
     * @return bool
     */
    public static function award_review_points($user_id, $comment_id = 0)
    {
        // Check if user is a member
        if (!self::is_member($user_id)) {
            return false;
        }

        // Get points amount from settings
        $points = get_option('loyalty_program_points_review', 50);

        if ($points <= 0) {
            return false;
        }

        // Award points
        $action_desc = __('Posting a review of a product', 'loyalty-program');

        $extra_data = array(
            'type' => 'product_review',
        );

        if ($comment_id > 0) {
            $extra_data['comment_id'] = $comment_id;
        }

        self::add_points($user_id, $points, $action_desc, $extra_data);

        // Log the action
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        Loyalty_Program_Logger::info('Review points awarded', array(
            'user_id' => $user_id,
            'comment_id' => $comment_id,
            'points' => $points,
        ));

        return true;
    }

    /**
     * Award points for Attendance Master achievement
     * Mistrz obecności - walidacja do dokończenia potem
     * 
     * @param int $user_id User ID
     * @param array $extra_data Additional data about the achievement
     * @return bool
     */
    public static function award_attendance_master_points($user_id, $extra_data = array())
    {
        // Check if user is a member
        if (!self::is_member($user_id)) {
            return false;
        }

        // Get points amount from settings
        $points = get_option('loyalty_program_points_attendance_master', 50);

        if ($points <= 0) {
            return false;
        }

        // Award points
        $action_desc = __('Attendance Master', 'loyalty-program');

        $transaction_data = array_merge(array(
            'type' => 'attendance_master',
        ), $extra_data);

        self::add_points($user_id, $points, $action_desc, $transaction_data);

        // Log the action
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        Loyalty_Program_Logger::info('Attendance Master points awarded', array(
            'user_id' => $user_id,
            'points' => $points,
            'extra_data' => $extra_data,
        ));

        return true;
    }
}
