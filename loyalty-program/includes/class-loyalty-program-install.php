<?php

/**
 * Installation related functions and actions
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Loyalty Program Install Class
 */
class Loyalty_Program_Install
{

    /**
     * Plugin activation
     * 
     * @return void
     */
    public static function activate()
    {
        self::create_tables();
        self::create_options();
        self::create_roles();
        self::create_log_directory();

        // Set default options
        update_option('loyalty_program_version', LOYALTY_PROGRAM_VERSION);
        update_option('loyalty_program_activation_date', current_time('mysql'));

        flush_rewrite_rules();
    }

    /**
     * Create plugin database tables
     * 
     * @return void
     */
    private static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = array();

        // Points table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}loyalty_points (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            points int(11) NOT NULL DEFAULT 0,
            action_type varchar(50) NOT NULL,
            description text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY action_type (action_type)
        ) $charset_collate;";

        // Rewards table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}loyalty_rewards (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            reward_type varchar(50) NOT NULL,
            reward_value text,
            status varchar(20) NOT NULL DEFAULT 'pending',
            redeemed_at datetime,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        foreach ($sql as $query) {
            dbDelta($query);
        }
    }

    /**
     * Create default plugin options
     * 
     * @return void
     */
    private static function create_options()
    {
        $default_options = array(
            'loyalty_program_enabled' => 'yes',
            'loyalty_program_points_per_currency' => '1',
            'loyalty_program_debug_enabled' => 'no',
            'loyalty_program_custom_version' => LOYALTY_PROGRAM_VERSION,
            'loyalty_program_random_version' => 'no',
            'loyalty_program_points_signup' => '100',
            'loyalty_program_points_review' => '50',
            'loyalty_program_points_coupon_use' => '10',
            'loyalty_program_points_birthday' => '25',
            'loyalty_program_points_profile_complete' => '75',
            'loyalty_program_points_return_purchase' => '50',
            'loyalty_program_points_live_expert' => '30',
            'loyalty_program_points_supplementation_discipline' => '50',
            'loyalty_program_coupon_value' => '10',
            'loyalty_program_coupon_min_amount' => '150',
            'loyalty_program_wheel_prizes' => array(),
            'loyalty_program_wheel_days_between_spins' => '7',
        );

        foreach ($default_options as $option_name => $option_value) {
            if (get_option($option_name) === false) {
                add_option($option_name, $option_value);
            }
        }
    }

    /**
     * Create plugin roles
     * 
     * @return void
     */
    private static function create_roles()
    {
        // Add capability to manage loyalty program to administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_loyalty_program');
        }
    }

    /**
     * Create logs directory
     * 
     * @return void
     */
    private static function create_log_directory()
    {
        $log_dir = LOYALTY_PROGRAM_PLUGIN_DIR . 'logs';

        // Create logs directory if it doesn't exist
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);

            // Create .htaccess to protect log files
            $htaccess_file = $log_dir . '/.htaccess';
            if (!file_exists($htaccess_file)) {
                file_put_contents($htaccess_file, "Deny from all\n");
            }

            // Create index.php to prevent directory listing
            $index_file = $log_dir . '/index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, "<?php\n// Silence is golden.\n");
            }
        }
    }
}
