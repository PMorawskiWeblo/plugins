<?php

/**
 * Admin Menu functionality
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Loyalty Program Admin Menu Class
 */
class Loyalty_Program_Admin_Menu
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'handle_user_export'));
    }

    /**
     * Handle user export before any output
     * 
     * @return void
     */
    public function handle_user_export()
    {
        // Check if this is an export request
        if (!isset($_GET['page']) || $_GET['page'] !== 'loyalty-program-users') {
            return;
        }

        if (!isset($_GET['action']) || $_GET['action'] !== 'export' || empty($_GET['users'])) {
            return;
        }

        // Verify permissions
        if (!current_user_can('manage_loyalty_program')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'loyalty-program'));
        }

        // Load required classes
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        $user_ids = array_map('intval', (array) $_GET['users']);

        $filename = 'loyalty_selected_users_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Header
        fputcsv($output, array(
            'ID',
            'Username',
            'Email',
            'Address',
            'Join Date',
            'Current Points',
            'Total Earned'
        ));

        foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);
            if (!$user) continue;

            $is_member = Loyalty_Program_Points::is_member($user_id);

            // Get billing address from WooCommerce
            $address_parts = array();
            $billing_address_1 = get_user_meta($user_id, 'billing_address_1', true);
            $billing_address_2 = get_user_meta($user_id, 'billing_address_2', true);
            $billing_city = get_user_meta($user_id, 'billing_city', true);
            $billing_postcode = get_user_meta($user_id, 'billing_postcode', true);

            if ($billing_address_1) $address_parts[] = $billing_address_1;
            if ($billing_address_2) $address_parts[] = $billing_address_2;
            if ($billing_postcode) $address_parts[] = $billing_postcode;
            if ($billing_city) $address_parts[] = $billing_city;

            $full_address = !empty($address_parts) ? implode(', ', $address_parts) : '';

            // Format join date
            $join_date_raw = get_user_meta($user_id, 'loyalty_program_join_date', true);
            $join_date = '';
            if ($join_date_raw) {
                $timestamp = strtotime($join_date_raw);
                $join_date = $timestamp ? date('Y-m-d H:i:s', $timestamp) : $join_date_raw;
            }

            $row = array(
                $user_id,
                $user->user_login,
                $user->user_email,
                $full_address,
                $join_date,
                $is_member ? Loyalty_Program_Points::get_current_points($user_id) : 0,
                $is_member ? Loyalty_Program_Points::get_total_earned($user_id) : 0,
            );
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Register admin menu
     * 
     * @return void
     */
    public function register_menu()
    {
        // Main menu page
        add_menu_page(
            __('Loyalty Program', 'loyalty-program'),
            __('Loyalty Program', 'loyalty-program'),
            'manage_loyalty_program',
            'loyalty-program',
            array($this, 'render_dashboard_page'),
            'dashicons-awards',
            56
        );

        // Dashboard submenu - replaces the auto-created first submenu
        add_submenu_page(
            'loyalty-program',
            __('Dashboard', 'loyalty-program'),
            __('Dashboard', 'loyalty-program'),
            'manage_loyalty_program',
            'loyalty-program',
            array($this, 'render_dashboard_page')
        );

        // Integrations submenu
        add_submenu_page(
            'loyalty-program',
            __('Integrations', 'loyalty-program'),
            __('Integrations', 'loyalty-program'),
            'manage_loyalty_program',
            'loyalty-program-integrations',
            array($this, 'render_integrations_page')
        );

        // Users submenu
        add_submenu_page(
            'loyalty-program',
            __('Users', 'loyalty-program'),
            __('Users', 'loyalty-program'),
            'manage_loyalty_program',
            'loyalty-program-users',
            array($this, 'render_users_page')
        );

        // Rewards submenu
        add_submenu_page(
            'loyalty-program',
            __('Rewards', 'loyalty-program'),
            __('Rewards', 'loyalty-program'),
            'manage_loyalty_program',
            'loyalty-program-rewards',
            array($this, 'render_rewards_page')
        );

        // Surveys & Quizzes submenu
        add_submenu_page(
            'loyalty-program',
            __('Surveys & Quizzes', 'loyalty-program'),
            __('Surveys & Quizzes', 'loyalty-program'),
            'manage_loyalty_program',
            'loyalty-program-surveys',
            array($this, 'render_surveys_page')
        );

        // Survey Results submenu (hidden from menu)
        add_submenu_page(
            null, // Hidden from menu
            __('Survey Results', 'loyalty-program'),
            __('Survey Results', 'loyalty-program'),
            'manage_loyalty_program',
            'loyalty-program-survey-results',
            array($this, 'render_survey_results_page')
        );

        // Shortcodes submenu
        add_submenu_page(
            'loyalty-program',
            __('Shortcodes', 'loyalty-program'),
            __('Shortcodes', 'loyalty-program'),
            'manage_loyalty_program',
            'loyalty-program-shortcodes',
            array($this, 'render_shortcodes_page')
        );

        // Live with Expert submenu
        add_submenu_page(
            'loyalty-program',
            __('Live with Expert', 'loyalty-program'),
            __('Live with Expert', 'loyalty-program'),
            'manage_loyalty_program',
            'loyalty-program-live-expert',
            array($this, 'render_live_expert_page')
        );

        // Attendance Master submenu
        add_submenu_page(
            'loyalty-program',
            __('Attendance Master', 'loyalty-program'),
            __('Attendance Master', 'loyalty-program'),
            'manage_loyalty_program',
            'loyalty-program-attendance-master',
            array($this, 'render_attendance_master_page')
        );

        // Join Form submenu
        add_submenu_page(
            'loyalty-program',
            __('Join Form', 'loyalty-program'),
            __('Join Form', 'loyalty-program'),
            'manage_loyalty_program',
            'loyalty-program-join-form',
            array($this, 'render_join_form_page')
        );

        // Settings submenu
        add_submenu_page(
            'loyalty-program',
            __('Settings', 'loyalty-program'),
            __('Settings', 'loyalty-program'),
            'manage_loyalty_program',
            'loyalty-program-settings',
            array($this, 'render_settings_page')
        );

        // Developer Panel submenu
        add_submenu_page(
            'loyalty-program',
            __('Developer Panel', 'loyalty-program'),
            __('Developer Panel', 'loyalty-program'),
            'manage_loyalty_program',
            'loyalty-program-developer',
            array($this, 'render_developer_page')
        );
    }

    /**
     * Render dashboard page
     * 
     * @return void
     */
    public function render_dashboard_page()
    {
        if (!current_user_can('manage_loyalty_program')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'loyalty-program'));
        }

        include LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/admin/views/dashboard.php';
    }

    /**
     * Render integrations page
     * 
     * @return void
     */
    public function render_integrations_page()
    {
        if (!current_user_can('manage_loyalty_program')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'loyalty-program'));
        }

        // Save settings if form submitted
        if (isset($_POST['submit']) || isset($_POST['loyalty_program_integrations_save'])) {
            $this->save_integrations_settings();
        }

        include LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/admin/views/integrations.php';
    }

    /**
     * Render users page
     * 
     * @return void
     */
    public function render_users_page()
    {
        if (!current_user_can('manage_loyalty_program')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'loyalty-program'));
        }

        include LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/admin/views/users.php';
    }

    /**
     * Render rewards page
     * 
     * @return void
     */
    public function render_rewards_page()
    {
        if (!current_user_can('manage_loyalty_program')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'loyalty-program'));
        }

        // Save rewards if form submitted
        if (isset($_POST['loyalty_program_rewards_save'])) {
            $this->save_rewards();
        }

        include LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/admin/views/rewards.php';
    }

    /**
     * Render surveys & quizzes page
     * 
     * @return void
     */
    public function render_surveys_page()
    {
        if (!current_user_can('manage_loyalty_program')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'loyalty-program'));
        }

        // Save surveys if form submitted
        if (isset($_POST['loyalty_program_surveys_save'])) {
            $this->save_surveys();
        }

        include LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/admin/views/surveys.php';
    }

    /**
     * Render survey results page
     * 
     * @return void
     */
    public function render_survey_results_page()
    {
        if (!current_user_can('manage_loyalty_program')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'loyalty-program'));
        }

        include LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/admin/views/survey-results.php';
    }

    /**
     * Render shortcodes page
     * 
     * @return void
     */
    public function render_shortcodes_page()
    {
        if (!current_user_can('manage_loyalty_program')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'loyalty-program'));
        }

        // Save shortcode pages if form submitted
        if (isset($_POST['loyalty_program_shortcode_pages_save'])) {
            $this->save_shortcode_pages();
        }

        include LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/admin/views/shortcodes.php';
    }

    /**
     * Render live with expert page
     * 
     * @return void
     */
    public function render_live_expert_page()
    {
        if (!current_user_can('manage_loyalty_program')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'loyalty-program'));
        }

        // Load logger for debugging
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Debug: ALWAYS log POST data (even if debug is off)
        if (!empty($_POST)) {
            // Force logging by writing directly to file
            $log_message = sprintf(
                "[%s] Live Expert Page - POST data received | Submit: %s | File: %s | Error: %s",
                date('Y-m-d H:i:s'),
                isset($_POST['loyalty_program_live_csv_submit']) ? 'YES' : 'NO',
                isset($_FILES['live_csv_file']) ? 'YES' : 'NO',
                isset($_FILES['live_csv_file']) ? $_FILES['live_csv_file']['error'] : 'N/A'
            );
            error_log($log_message);

            Loyalty_Program_Logger::info('Live Expert Page - POST data received', array(
                'post_keys' => array_keys($_POST),
                'has_submit' => isset($_POST['loyalty_program_live_csv_submit']),
                'has_file' => isset($_FILES['live_csv_file']),
                'file_error' => isset($_FILES['live_csv_file']) ? $_FILES['live_csv_file']['error'] : 'N/A',
            ));
        }

        // Process CSV upload if form submitted
        if (isset($_POST['loyalty_program_live_csv_action']) && $_POST['loyalty_program_live_csv_action'] === 'process' && isset($_FILES['live_csv_file'])) {
            Loyalty_Program_Logger::info('Starting CSV processing...');
            $this->process_live_csv();
        } else if (!empty($_POST)) {
            // Log why processing was skipped
            Loyalty_Program_Logger::warning('CSV processing skipped', array(
                'reason' => 'Missing action or file',
                'has_action' => isset($_POST['loyalty_program_live_csv_action']),
                'action_value' => isset($_POST['loyalty_program_live_csv_action']) ? $_POST['loyalty_program_live_csv_action'] : 'N/A',
                'has_file' => isset($_FILES['live_csv_file']),
            ));
        }

        include LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/admin/views/live-expert.php';
    }

    /**
     * Process CSV file with user emails for live expert points
     * 
     * @return void
     */
    private function process_live_csv()
    {
        // Verify nonce
        if (
            !isset($_POST['loyalty_program_live_csv_nonce']) ||
            !wp_verify_nonce($_POST['loyalty_program_live_csv_nonce'], 'loyalty_program_live_csv')
        ) {
            add_settings_error(
                'loyalty_program_live',
                'nonce_failed',
                __('Security check failed.', 'loyalty-program'),
                'error'
            );
            return;
        }

        // Check if file was uploaded
        if (!isset($_FILES['live_csv_file']) || $_FILES['live_csv_file']['error'] !== UPLOAD_ERR_OK) {
            add_settings_error(
                'loyalty_program_live',
                'upload_failed',
                __('File upload failed. Please try again.', 'loyalty-program'),
                'error'
            );
            return;
        }

        $file = $_FILES['live_csv_file'];

        // Validate file type
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_ext !== 'csv') {
            add_settings_error(
                'loyalty_program_live',
                'invalid_file',
                __('Invalid file type. Please upload a CSV file.', 'loyalty-program'),
                'error'
            );
            return;
        }

        // Load required classes
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        // Get points for live
        $points = absint(get_option('loyalty_program_points_live_expert', 30));
        $live_title = sanitize_text_field($_POST['live_title'] ?? 'Live with Expert');
        
        // Generate unique CSV ID for this import
        $csv_id = 'live_' . time() . '_' . wp_rand(1000, 9999);

        // Log import start
        Loyalty_Program_Logger::info('=== LIVE EXPERT CSV IMPORT STARTED ===', array(
            'csv_id' => $csv_id,
            'filename' => $file['name'],
            'size' => $file['size'],
            'live_title' => $live_title,
            'points_per_user' => $points,
        ));

        // Parse CSV
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            Loyalty_Program_Logger::error('Unable to read CSV file', array('filename' => $file['name']));
            add_settings_error(
                'loyalty_program_live',
                'file_read_error',
                __('Unable to read CSV file.', 'loyalty-program'),
                'error'
            );
            return;
        }

        $results = array(
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => array(),
        );

        $row = 0;
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $row++;

            // Skip header row
            if ($row === 1 && (strtolower($data[0]) === 'email' || strpos(strtolower($data[0]), 'e-mail') !== false)) {
                Loyalty_Program_Logger::debug("CSV Row $row: Header detected, skipping");
                continue;
            }

            $email = isset($data[0]) ? trim($data[0]) : '';

            Loyalty_Program_Logger::debug("CSV Row $row: Processing email", array('email' => $email));

            // Validate email
            if (empty($email) || !is_email($email)) {
                $results['failed']++;
                $error_msg = sprintf(__('Row %d: Invalid email: %s', 'loyalty-program'), $row, $email);
                $results['errors'][] = $error_msg;
                Loyalty_Program_Logger::warning("CSV Row $row: INVALID EMAIL", array(
                    'email' => $email,
                    'reason' => 'Invalid email format'
                ));
                continue;
            }

            // Find user by email
            $user = get_user_by('email', $email);
            if (!$user) {
                $results['failed']++;
                $error_msg = sprintf(__('Row %d: User not found: %s', 'loyalty-program'), $row, $email);
                $results['errors'][] = $error_msg;
                Loyalty_Program_Logger::warning("CSV Row $row: USER NOT FOUND", array(
                    'email' => $email,
                    'reason' => 'No WordPress user exists with this email'
                ));
                continue;
            }

            // Check if user is a member
            if (!Loyalty_Program_Points::is_member($user->ID)) {
                $results['failed']++;
                $error_msg = sprintf(__('Row %d: User not enrolled in loyalty program: %s', 'loyalty-program'), $row, $email);
                $results['errors'][] = $error_msg;
                Loyalty_Program_Logger::warning("CSV Row $row: NOT A MEMBER", array(
                    'email' => $email,
                    'user_id' => $user->ID,
                    'username' => $user->user_login,
                    'reason' => 'User is not enrolled in loyalty program'
                ));
                continue;
            }

            // Award points
            // Use only live_title without prefix
            $action_desc = $live_title;

            Loyalty_Program_Points::add_points($user->ID, $points, $action_desc, array(
                'type' => 'live_expert',
                'live_title' => $live_title,
                'csv_id' => $csv_id,
            ));

            // Add CSV ID to user's live sessions list (for easy checking)
            $user_live_sessions = get_user_meta($user->ID, 'loyalty_program_live_sessions', true);
            if (!is_array($user_live_sessions)) {
                $user_live_sessions = array();
            }
            
            // Add csv_id if not already in the list
            if (!in_array($csv_id, $user_live_sessions)) {
                $user_live_sessions[] = $csv_id;
                update_user_meta($user->ID, 'loyalty_program_live_sessions', $user_live_sessions);
            }

            $results['success']++;
            $results['processed']++;

            Loyalty_Program_Logger::info("CSV Row $row: ✅ SUCCESS - Points awarded", array(
                'user_id' => $user->ID,
                'username' => $user->user_login,
                'display_name' => $user->display_name,
                'email' => $email,
                'points' => $points,
                'live_title' => $live_title,
            ));
        }

        fclose($handle);

        // Save CSV import to history (using the same csv_id generated earlier)
        $csv_history = get_option('loyalty_program_live_csv_history', array());
        
        $csv_history[] = array(
            'id' => $csv_id,
            'title' => $live_title,
            'filename' => $file['name'],
            'date' => current_time('mysql'),
            'timestamp' => current_time('timestamp'),
            'success' => $results['success'],
            'failed' => $results['failed'],
            'total' => $results['processed'],
            'points' => $points,
        );
        
        // Keep only last 50 imports
        $csv_history = array_slice($csv_history, -50);
        update_option('loyalty_program_live_csv_history', $csv_history);

        // Log import summary
        Loyalty_Program_Logger::info('=== LIVE EXPERT CSV IMPORT COMPLETED ===', array(
            'csv_id' => $csv_id,
            'total_rows_processed' => $row,
            'successful_awards' => $results['success'],
            'failed_awards' => $results['failed'],
            'total_points_awarded' => $results['success'] * $points,
        ));

        if (!empty($results['errors'])) {
            Loyalty_Program_Logger::warning('Import errors summary:', array('errors' => $results['errors']));
        }

        // Store results in transient for display
        set_transient('loyalty_program_live_import_results', $results, 300); // 5 minutes

        // Add success message
        add_settings_error(
            'loyalty_program_live',
            'import_complete',
            sprintf(
                __('Import complete! Successfully awarded points to %d users. %d failed.', 'loyalty-program'),
                $results['success'],
                $results['failed']
            ),
            'success'
        );
    }

    /**
     * Render attendance master page
     * 
     * @return void
     */
    public function render_attendance_master_page()
    {
        if (!current_user_can('manage_loyalty_program')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'loyalty-program'));
        }

        // Save actions if form submitted
        if (isset($_POST['loyalty_program_attendance_actions_save'])) {
            $this->save_attendance_actions();
        }

        include LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/admin/views/attendance-master.php';
    }

    /**
     * Save attendance actions configuration
     * 
     * @return void
     */
    private function save_attendance_actions()
    {
        if (
            !isset($_POST['loyalty_program_attendance_nonce']) ||
            !wp_verify_nonce($_POST['loyalty_program_attendance_nonce'], 'loyalty_program_attendance')
        ) {
            return;
        }

        // Get actions data from POST
        $actions_data = isset($_POST['attendance_actions']) ? $_POST['attendance_actions'] : array();

        // Sanitize and validate actions
        $sanitized_actions = array();

        if (!empty($actions_data)) {
            foreach ($actions_data as $index => $action) {
                // Skip if name is empty (deleted row)
                if (empty(trim($action['name']))) {
                    continue;
                }

                // Parse type: button-dark, button-light, text
                $type_value = sanitize_text_field($action['type']);
                $type = 'button';
                $button_style = 'dark';
                
                if ($type_value === 'text') {
                    $type = 'text';
                } elseif ($type_value === 'button-light') {
                    $type = 'button';
                    $button_style = 'light';
                } elseif ($type_value === 'button-dark') {
                    $type = 'button';
                    $button_style = 'dark';
                }

                $sanitized_actions[] = array(
                    'id' => sanitize_text_field($action['id']),
                    'name' => sanitize_text_field($action['name']),
                    'content' => sanitize_textarea_field($action['content']),
                    'type' => $type,
                    'button_style' => $button_style,
                    'points' => absint($action['points']),
                    'date_from' => sanitize_text_field($action['date_from']),
                    'date_to' => sanitize_text_field($action['date_to']),
                    'visible_after' => isset($action['visible_after']) && $action['visible_after'] === 'yes' ? 'yes' : 'no',
                    'enabled' => isset($action['enabled']) && $action['enabled'] === 'yes' ? 'yes' : 'no',
                );
            }
        }

        // Save to options
        update_option('loyalty_program_attendance_actions', $sanitized_actions);

        add_settings_error(
            'loyalty_program_attendance',
            'actions_saved',
            __('Attendance actions saved successfully.', 'loyalty-program'),
            'success'
        );
    }

    /**
     * Render join form page
     * 
     * @return void
     */
    public function render_join_form_page()
    {
        if (!current_user_can('manage_loyalty_program')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'loyalty-program'));
        }

        // Save settings if form submitted
        if (isset($_POST['loyalty_program_join_form_save'])) {
            $this->save_join_form_settings();
        }

        include LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/admin/views/join-form.php';
    }

    /**
     * Render settings page
     * 
     * @return void
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_loyalty_program')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'loyalty-program'));
        }

        // Save settings if form submitted
        if (isset($_POST['loyalty_program_settings_save'])) {
            $this->save_general_settings();
        }

        include LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/admin/views/settings.php';
    }

    /**
     * Render developer panel page
     * 
     * @return void
     */
    public function render_developer_page()
    {
        if (!current_user_can('manage_loyalty_program')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'loyalty-program'));
        }

        // Save settings if form submitted
        if (isset($_POST['loyalty_program_developer_save'])) {
            $this->save_developer_settings();
        }

        // Clear log if requested
        if (isset($_POST['loyalty_program_clear_log'])) {
            $this->clear_debug_log();
        }

        include LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/admin/views/developer-panel.php';
    }

    /**
     * Save integrations settings
     * 
     * @return void
     */
    private function save_integrations_settings()
    {
        if (
            !isset($_POST['loyalty_program_integrations_nonce']) ||
            !wp_verify_nonce($_POST['loyalty_program_integrations_nonce'], 'loyalty_program_integrations')
        ) {
            return;
        }

        // SalesManago integration
        $salesmanago_enabled = isset($_POST['salesmanago_enabled']) ? 'yes' : 'no';

        // Load logger
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        Loyalty_Program_Logger::debug('Saving SalesManago settings', array(
            'salesmanago_enabled_post' => isset($_POST['salesmanago_enabled']) ? $_POST['salesmanago_enabled'] : 'NOT SET',
            'salesmanago_enabled_value' => $salesmanago_enabled,
        ));

        update_option('loyalty_program_salesmanago_enabled', $salesmanago_enabled);

        // Save SalesManago credentials (always save, regardless of enabled status)
        if (isset($_POST['salesmanago_client_id'])) {
            update_option('loyalty_program_salesmanago_client_id', sanitize_text_field($_POST['salesmanago_client_id']));
        }
        if (isset($_POST['salesmanago_sha'])) {
            update_option('loyalty_program_salesmanago_sha', sanitize_text_field($_POST['salesmanago_sha']));
        }
        if (isset($_POST['salesmanago_api_key'])) {
            update_option('loyalty_program_salesmanago_api_key', sanitize_text_field($_POST['salesmanago_api_key']));
        }
        if (isset($_POST['salesmanago_owner'])) {
            update_option('loyalty_program_salesmanago_owner', sanitize_email($_POST['salesmanago_owner']));
        }

        add_settings_error(
            'loyalty_program_integrations',
            'settings_updated',
            __('Integration settings saved successfully.', 'loyalty-program'),
            'success'
        );
    }

    /**
     * Save join form settings
     * 
     * @return void
     */
    private function save_join_form_settings()
    {
        if (
            !isset($_POST['loyalty_program_join_form_nonce']) ||
            !wp_verify_nonce($_POST['loyalty_program_join_form_nonce'], 'loyalty_program_join_form')
        ) {
            return;
        }

        // For logged in users (consent text - required)
        if (isset($_POST['join_form_logged_consent_text'])) {
            update_option('loyalty_program_join_form_logged_consent_text', wp_kses_post($_POST['join_form_logged_consent_text']));
        }

        // Auto newsletter for logged in users
        $logged_auto_newsletter = isset($_POST['join_form_logged_auto_newsletter']) ? 'yes' : 'no';
        update_option('loyalty_program_join_form_logged_auto_newsletter', $logged_auto_newsletter);

        // For not logged in users
        if (isset($_POST['join_form_header'])) {
            update_option('loyalty_program_join_form_header', sanitize_text_field($_POST['join_form_header']));
        }

        if (isset($_POST['join_form_description'])) {
            update_option('loyalty_program_join_form_description', wp_kses_post($_POST['join_form_description']));
        }

        if (isset($_POST['join_form_points_info'])) {
            update_option('loyalty_program_join_form_points_info', wp_kses_post($_POST['join_form_points_info']));
        }

        // Newsletter consent (first in order)
        $newsletter_consent_enabled = isset($_POST['join_form_newsletter_consent_enabled']) ? 'yes' : 'no';
        update_option('loyalty_program_join_form_newsletter_consent_enabled', $newsletter_consent_enabled);

        if (isset($_POST['join_form_newsletter_consent_text'])) {
            update_option('loyalty_program_join_form_newsletter_consent_text', wp_kses_post($_POST['join_form_newsletter_consent_text']));
        }

        $newsletter_consent_required = isset($_POST['join_form_newsletter_consent_required']) ? 'yes' : 'no';
        update_option('loyalty_program_join_form_newsletter_consent_required', $newsletter_consent_required);

        // SMS consent
        $sms_consent_enabled = isset($_POST['join_form_sms_consent_enabled']) ? 'yes' : 'no';
        update_option('loyalty_program_join_form_sms_consent_enabled', $sms_consent_enabled);

        if (isset($_POST['join_form_sms_consent_text'])) {
            update_option('loyalty_program_join_form_sms_consent_text', wp_kses_post($_POST['join_form_sms_consent_text']));
        }

        $sms_consent_required = isset($_POST['join_form_sms_consent_required']) ? 'yes' : 'no';
        update_option('loyalty_program_join_form_sms_consent_required', $sms_consent_required);

        // Terms consent (Regulamin)
        $terms_consent_enabled = isset($_POST['join_form_terms_consent_enabled']) ? 'yes' : 'no';
        update_option('loyalty_program_join_form_terms_consent_enabled', $terms_consent_enabled);

        if (isset($_POST['join_form_terms_consent_text'])) {
            update_option('loyalty_program_join_form_terms_consent_text', wp_kses_post($_POST['join_form_terms_consent_text']));
        }

        $terms_consent_required = isset($_POST['join_form_terms_consent_required']) ? 'yes' : 'no';
        update_option('loyalty_program_join_form_terms_consent_required', $terms_consent_required);

        // Custom consents
        $custom_consents = isset($_POST['custom_consents']) ? $_POST['custom_consents'] : array();
        $sanitized_consents = array();

        if (!empty($custom_consents)) {
            foreach ($custom_consents as $index => $consent) {
                if (!empty(trim($consent['text']))) {
                    $sanitized_consents[] = array(
                        'text' => wp_kses_post($consent['text']),
                        'required' => isset($consent['required']) && $consent['required'] === 'yes' ? 'yes' : 'no',
                    );
                }
            }
        }

        update_option('loyalty_program_join_form_custom_consents', $sanitized_consents);

        add_settings_error(
            'loyalty_program_join_form',
            'settings_saved',
            __('Join form settings saved successfully.', 'loyalty-program'),
            'success'
        );
    }

    /**
     * Save general settings
     * 
     * @return void
     */
    private function save_general_settings()
    {
        if (
            !isset($_POST['loyalty_program_settings_nonce']) ||
            !wp_verify_nonce($_POST['loyalty_program_settings_nonce'], 'loyalty_program_settings')
        ) {
            return;
        }

        // Checkbox - if not set in POST, means it's unchecked
        $loyalty_enabled = isset($_POST['loyalty_program_enabled']) ? 'yes' : 'no';
        update_option('loyalty_program_enabled', $loyalty_enabled);

        // Auto-enroll checkbox
        $auto_enroll = isset($_POST['loyalty_program_auto_enroll']) ? 'yes' : 'no';
        update_option('loyalty_program_auto_enroll', $auto_enroll);

        if (isset($_POST['points_per_currency'])) {
            update_option('loyalty_program_points_per_currency', absint($_POST['points_per_currency']));
        }

        // Points only for products (excluding shipping)
        $points_only_products = isset($_POST['points_only_products']) ? 'yes' : 'no';
        update_option('loyalty_program_points_only_products', $points_only_products);

        // Points for customer actions
        if (isset($_POST['points_signup'])) {
            update_option('loyalty_program_points_signup', absint($_POST['points_signup']));
        }

        if (isset($_POST['points_review'])) {
            update_option('loyalty_program_points_review', absint($_POST['points_review']));
        }

        if (isset($_POST['points_coupon_use'])) {
            update_option('loyalty_program_points_coupon_use', absint($_POST['points_coupon_use']));
        }

        if (isset($_POST['points_birthday'])) {
            update_option('loyalty_program_points_birthday', absint($_POST['points_birthday']));
        }

        if (isset($_POST['points_profile_complete'])) {
            update_option('loyalty_program_points_profile_complete', absint($_POST['points_profile_complete']));
        }

        if (isset($_POST['points_notifications'])) {
            update_option('loyalty_program_points_notifications', absint($_POST['points_notifications']));
        }

        if (isset($_POST['points_return_purchase'])) {
            update_option('loyalty_program_points_return_purchase', absint($_POST['points_return_purchase']));
        }

        if (isset($_POST['points_live_expert'])) {
            update_option('loyalty_program_points_live_expert', absint($_POST['points_live_expert']));
        }

        if (isset($_POST['points_attendance_master'])) {
            update_option('loyalty_program_points_attendance_master', absint($_POST['points_attendance_master']));
        }

        if (isset($_POST['points_flash_hunter'])) {
            update_option('loyalty_program_points_flash_hunter', absint($_POST['points_flash_hunter']));
        }

        if (isset($_POST['points_supplementation_discipline'])) {
            update_option('loyalty_program_points_supplementation_discipline', absint($_POST['points_supplementation_discipline']));
        }

        if (isset($_POST['discipline_not_purchased_text'])) {
            update_option('loyalty_program_discipline_not_purchased_text', sanitize_text_field($_POST['discipline_not_purchased_text']));
        }

        // WooCommerce settings
        if (isset($_POST['coupon_value'])) {
            update_option('loyalty_program_coupon_value', floatval($_POST['coupon_value']));
        }

        if (isset($_POST['coupon_min_amount'])) {
            update_option('loyalty_program_coupon_min_amount', floatval($_POST['coupon_min_amount']));
        }

        // Disable personal coupons checkbox
        $disable_personal_coupons = isset($_POST['disable_personal_coupons']) ? 'yes' : 'no';
        update_option('loyalty_program_disable_personal_coupons', $disable_personal_coupons);

        // Show cart info
        $show_cart_info = isset($_POST['loyalty_program_show_cart_info']) ? 'yes' : 'no';
        update_option('loyalty_program_show_cart_info', $show_cart_info);

        // Wheel of Fortune prizes
        $wheel_prizes_data = isset($_POST['wheel_prizes']) ? $_POST['wheel_prizes'] : array();
        $sanitized_wheel_prizes = array();

        if (!empty($wheel_prizes_data)) {
            foreach ($wheel_prizes_data as $index => $prize) {
                // Skip if name is empty (deleted row)
                if (empty(trim($prize['name']))) {
                    continue;
                }

                $sanitized_wheel_prizes[] = array(
                    'name' => sanitize_text_field($prize['name']),
                    'points' => absint($prize['points']),
                    'probability' => floatval($prize['probability']),
                    'color' => sanitize_hex_color($prize['color'] ?? '#3b82f6'),
                    'enabled' => isset($prize['enabled']) && $prize['enabled'] === 'yes' ? 'yes' : 'no',
                );
            }
        }

        update_option('loyalty_program_wheel_prizes', $sanitized_wheel_prizes);

        // Save wheel of fortune settings
        $wheel_days_between_spins = isset($_POST['wheel_days_between_spins']) ? absint($_POST['wheel_days_between_spins']) : 7;
        update_option('loyalty_program_wheel_days_between_spins', max(1, $wheel_days_between_spins));

        // User account fields settings
        $enable_birth_date = isset($_POST['loyalty_program_enable_birth_date']) ? 'yes' : 'no';
        update_option('loyalty_program_enable_birth_date', $enable_birth_date);

        $enable_sms_consent = isset($_POST['loyalty_program_enable_sms_consent']) ? 'yes' : 'no';
        update_option('loyalty_program_enable_sms_consent', $enable_sms_consent);

        $enable_newsletter_consent = isset($_POST['loyalty_program_enable_newsletter_consent']) ? 'yes' : 'no';
        update_option('loyalty_program_enable_newsletter_consent', $enable_newsletter_consent);

        $enable_billing_phone = isset($_POST['loyalty_program_enable_billing_phone']) ? 'yes' : 'no';
        update_option('loyalty_program_enable_billing_phone', $enable_billing_phone);

        $enable_user_coupon = isset($_POST['loyalty_program_enable_user_coupon']) ? 'yes' : 'no';
        update_option('loyalty_program_enable_user_coupon', $enable_user_coupon);

        if (isset($_POST['loyalty_program_account_custom_page'])) {
            update_option('loyalty_program_account_custom_page', absint($_POST['loyalty_program_account_custom_page']));
        }

        // Points award status
        if (isset($_POST['loyalty_program_points_award_status'])) {
            $points_award_status = sanitize_text_field($_POST['loyalty_program_points_award_status']);
            if (in_array($points_award_status, array('processing', 'completed'))) {
                update_option('loyalty_program_points_award_status', $points_award_status);
            }
        }

        add_settings_error(
            'loyalty_program_settings',
            'settings_updated',
            __('Settings saved successfully.', 'loyalty-program'),
            'success'
        );
    }

    /**
     * Save developer settings
     * 
     * @return void
     */
    private function save_developer_settings()
    {
        if (
            !isset($_POST['loyalty_program_developer_nonce']) ||
            !wp_verify_nonce($_POST['loyalty_program_developer_nonce'], 'loyalty_program_developer')
        ) {
            return;
        }

        // Debug log enabled
        $debug_enabled = isset($_POST['debug_log_enabled']) ? 'yes' : 'no';
        update_option('loyalty_program_debug_enabled', $debug_enabled);

        // Random version enabled
        $random_version = isset($_POST['random_version_enabled']) ? 'yes' : 'no';
        update_option('loyalty_program_random_version', $random_version);

        // Custom version (required if random version is disabled)
        if ($random_version === 'no') {
            if (isset($_POST['custom_version']) && !empty(trim($_POST['custom_version']))) {
                update_option('loyalty_program_custom_version', sanitize_text_field($_POST['custom_version']));
            }
        } else {
            // If random version is enabled, save the custom version anyway for when it's disabled
            if (isset($_POST['custom_version'])) {
                update_option('loyalty_program_custom_version', sanitize_text_field($_POST['custom_version']));
            }
        }

        add_settings_error(
            'loyalty_program_developer',
            'settings_updated',
            __('Developer settings saved successfully.', 'loyalty-program'),
            'success'
        );
    }

    /**
     * Clear debug log
     * 
     * @return void
     */
    private function clear_debug_log()
    {
        if (
            !isset($_POST['loyalty_program_clear_log_nonce']) ||
            !wp_verify_nonce($_POST['loyalty_program_clear_log_nonce'], 'loyalty_program_clear_log')
        ) {
            return;
        }

        // Load logger class
        if (!class_exists('Loyalty_Program_Logger')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
        }

        Loyalty_Program_Logger::clear_log();

        add_settings_error(
            'loyalty_program_developer',
            'log_cleared',
            __('Debug log cleared successfully.', 'loyalty-program'),
            'success'
        );
    }

    /**
     * Save rewards configuration
     * 
     * @return void
     */
    private function save_rewards()
    {
        if (
            !isset($_POST['loyalty_program_rewards_nonce']) ||
            !wp_verify_nonce($_POST['loyalty_program_rewards_nonce'], 'loyalty_program_rewards')
        ) {
            return;
        }

        // Get product rewards data from POST
        $product_rewards_data = isset($_POST['product_rewards']) ? $_POST['product_rewards'] : array();
        $coupon_rewards_data = isset($_POST['coupon_rewards']) ? $_POST['coupon_rewards'] : array();

        // Sanitize and validate product rewards
        $sanitized_product_rewards = array();
        if (!empty($product_rewards_data)) {
            foreach ($product_rewards_data as $index => $reward) {
                // Skip if name is empty (deleted row)
                if (empty(trim($reward['name']))) {
                    continue;
                }
                
                $sanitized_product_rewards[] = array(
                    'name' => sanitize_text_field($reward['name']),
                    'description' => isset($reward['description']) ? sanitize_textarea_field($reward['description']) : '',
                    'product_id' => absint($reward['product_id']),
                    'points' => absint($reward['points']),
                    'price' => floatval($reward['price']),
                    'enabled' => isset($reward['enabled']) && $reward['enabled'] === 'yes' ? 'yes' : 'no',
                );
            }
        }

        // Sanitize and validate coupon rewards
        $sanitized_coupon_rewards = array();
        if (!empty($coupon_rewards_data)) {
            foreach ($coupon_rewards_data as $index => $reward) {
                // Skip if name is empty (deleted row)
                if (empty(trim($reward['name']))) {
                    continue;
                }

                $coupon_type = isset($reward['type']) && in_array($reward['type'], array('fixed_cart', 'percent', 'free_shipping')) 
                    ? $reward['type'] 
                    : 'fixed_cart';
                
                $sanitized_coupon_rewards[] = array(
                    'type' => $coupon_type,
                    'name' => sanitize_text_field($reward['name']),
                    'description' => isset($reward['description']) ? sanitize_textarea_field($reward['description']) : '',
                    'image_id' => isset($reward['image_id']) ? absint($reward['image_id']) : 0,
                    'points' => absint($reward['points']),
                    'discount_value' => $coupon_type !== 'free_shipping' ? floatval($reward['discount_value']) : 0,
                    'min_order_amount' => floatval($reward['min_order_amount'] ?? 0),
                    'enabled' => isset($reward['enabled']) && $reward['enabled'] === 'yes' ? 'yes' : 'no',
                );
            }
        }

        // Save to options
        update_option('loyalty_program_product_rewards', $sanitized_product_rewards);
        update_option('loyalty_program_coupon_rewards', $sanitized_coupon_rewards);

        // Save coupon settings
        if (isset($_POST['coupon_apply_to'])) {
            $coupon_apply_to = sanitize_text_field($_POST['coupon_apply_to']);
            if (in_array($coupon_apply_to, array('cart', 'products'))) {
                update_option('loyalty_program_coupon_apply_to', $coupon_apply_to);
            }
        }

        $coupon_individual_use = isset($_POST['coupon_individual_use']) && $_POST['coupon_individual_use'] === 'yes' ? 'yes' : 'no';
        update_option('loyalty_program_coupon_individual_use', $coupon_individual_use);

        $coupon_excluded_products = isset($_POST['coupon_excluded_products']) && is_array($_POST['coupon_excluded_products']) 
            ? array_map('absint', $_POST['coupon_excluded_products']) 
            : array();
        update_option('loyalty_program_coupon_excluded_products', $coupon_excluded_products);

        $coupon_excluded_categories = isset($_POST['coupon_excluded_categories']) && is_array($_POST['coupon_excluded_categories']) 
            ? array_map('absint', $_POST['coupon_excluded_categories']) 
            : array();
        update_option('loyalty_program_coupon_excluded_categories', $coupon_excluded_categories);

        add_settings_error(
            'loyalty_program_rewards',
            'rewards_saved',
            __('Rewards saved successfully.', 'loyalty-program'),
            'success'
        );
    }

    /**
     * Save surveys and quizzes
     * 
     * @return void
     */
    private function save_surveys()
    {
        if (
            !isset($_POST['loyalty_program_surveys_nonce']) ||
            !wp_verify_nonce($_POST['loyalty_program_surveys_nonce'], 'loyalty_program_surveys')
        ) {
            return;
        }

        // Get surveys data from POST (JSON from JavaScript)
        $surveys_json = isset($_POST['surveys_data']) ? $_POST['surveys_data'] : '';

        // Log for debugging
        if (class_exists('Loyalty_Program_Logger') && Loyalty_Program_Logger::is_enabled()) {
            Loyalty_Program_Logger::debug('Saving surveys - POST data received', array(
                'has_surveys_data' => isset($_POST['surveys_data']),
                'surveys_data_length' => strlen($surveys_json),
                'surveys_data_preview' => substr($surveys_json, 0, 200),
            ));
        }

        if (empty($surveys_json)) {
            if (class_exists('Loyalty_Program_Logger') && Loyalty_Program_Logger::is_enabled()) {
                Loyalty_Program_Logger::warning('Saving surveys - Empty surveys_data received');
            }
            update_option('loyalty_program_surveys', array());
            add_settings_error(
                'loyalty_program_surveys',
                'surveys_saved',
                __('Surveys saved successfully.', 'loyalty-program'),
                'success'
            );
            return;
        }

        $surveys_data = json_decode(stripslashes($surveys_json), true);

        if (!is_array($surveys_data)) {
            $json_error = json_last_error_msg();
            if (class_exists('Loyalty_Program_Logger') && Loyalty_Program_Logger::is_enabled()) {
                Loyalty_Program_Logger::error('Saving surveys - JSON decode failed', array(
                    'json_error' => $json_error,
                    'json_preview' => substr($surveys_json, 0, 500),
                ));
            }
            add_settings_error(
                'loyalty_program_surveys',
                'surveys_error',
                __('Error saving surveys. Invalid data format.', 'loyalty-program') . ' ' . $json_error,
                'error'
            );
            return;
        }

        if (class_exists('Loyalty_Program_Logger') && Loyalty_Program_Logger::is_enabled()) {
            Loyalty_Program_Logger::debug('Saving surveys - JSON decoded successfully', array(
                'surveys_count' => count($surveys_data),
            ));
        }

        // Sanitize surveys data
        $sanitized_surveys = array();

        foreach ($surveys_data as $survey) {
            if (empty($survey['name'])) {
                continue;
            }

            $sanitized_survey = array(
                'id' => sanitize_text_field($survey['id']),
                'name' => sanitize_text_field($survey['name']),
                'description' => sanitize_textarea_field($survey['description'] ?? ''),
                'type' => in_array($survey['type'], array('quiz', 'survey')) ? $survey['type'] : 'survey',
                'enabled' => isset($survey['enabled']) && $survey['enabled'] === 'yes' ? 'yes' : 'no',
                'questions' => array(),
                'settings' => array(
                    'showStartButton' => isset($survey['settings']['showStartButton']) && $survey['settings']['showStartButton'] === true,
                    'startButtonText' => !empty($survey['settings']['startButtonText']) ? sanitize_text_field($survey['settings']['startButtonText']) : __('Start', 'loyalty-program'),
                    'randomOrder' => isset($survey['settings']['randomOrder']) && $survey['settings']['randomOrder'] === true,
                    'timeLimit' => isset($survey['settings']['timeLimit']) && $survey['settings']['timeLimit'] === true,
                    'timeMinutes' => isset($survey['settings']['timeMinutes']) ? absint($survey['settings']['timeMinutes']) : 10,
                    'pagination' => isset($survey['settings']['pagination']) && $survey['settings']['pagination'] === true,
                    'completionPoints' => isset($survey['settings']['completionPoints']) ? absint($survey['settings']['completionPoints']) : 0,
                    'minScorePercentage' => isset($survey['settings']['minScorePercentage']) ? absint($survey['settings']['minScorePercentage']) : 0,
                    'showResult' => isset($survey['settings']['showResult']) && $survey['settings']['showResult'] === true,
                    'redirectUrl' => !empty($survey['settings']['redirectUrl']) ? esc_url_raw($survey['settings']['redirectUrl']) : '',
                    'thankTitle' => sanitize_text_field($survey['settings']['thankTitle'] ?? ''),
                    'thankMessage' => sanitize_textarea_field($survey['settings']['thankMessage'] ?? ''),
                    'submitButtonText' => sanitize_text_field($survey['settings']['submitButtonText'] ?? ''),
                ),
            );

            // Sanitize questions
            if (!empty($survey['questions']) && is_array($survey['questions'])) {
                foreach ($survey['questions'] as $question) {
                    if (empty($question['text'])) {
                        continue;
                    }

                    $sanitized_question = array(
                        'id' => sanitize_text_field($question['id'] ?? ''),
                        'text' => sanitize_text_field($question['text']),
                        'description' => sanitize_textarea_field($question['description'] ?? ''),
                        'required' => isset($question['required']) && $question['required'] === true,
                        'answerType' => in_array($question['answerType'], array('radio', 'checkbox', 'text', 'number', 'textarea', 'rating'))
                            ? $question['answerType']
                            : 'radio',
                        'answers' => array(),
                    );

                    // Sanitize answers
                    if (!empty($question['answers']) && is_array($question['answers'])) {
                        foreach ($question['answers'] as $answer) {
                            if (empty($answer['text'])) {
                                continue;
                            }

                            $sanitized_answer = array(
                                'text' => sanitize_text_field($answer['text']),
                            );

                            // Add quiz-specific fields
                            if ($sanitized_survey['type'] === 'quiz') {
                                $sanitized_answer['correct'] = isset($answer['correct']) && $answer['correct'] === true;
                                $sanitized_answer['points'] = isset($answer['points']) ? absint($answer['points']) : 0;
                            }

                            $sanitized_question['answers'][] = $sanitized_answer;
                        }
                    }

                    $sanitized_survey['questions'][] = $sanitized_question;
                }
            }

            $sanitized_surveys[] = $sanitized_survey;
        }

        // Save to options
        update_option('loyalty_program_surveys', $sanitized_surveys);

        if (class_exists('Loyalty_Program_Logger') && Loyalty_Program_Logger::is_enabled()) {
            Loyalty_Program_Logger::info('Surveys saved successfully', array(
                'surveys_count' => count($sanitized_surveys),
                'survey_ids' => array_column($sanitized_surveys, 'id'),
            ));
        }

        add_settings_error(
            'loyalty_program_surveys',
            'surveys_saved',
            __('Surveys saved successfully.', 'loyalty-program'),
            'success'
        );
    }

    /**
     * Save shortcode pages settings
     * 
     * @return void
     */
    private function save_shortcode_pages()
    {
        if (
            !isset($_POST['loyalty_program_shortcode_pages_nonce']) ||
            !wp_verify_nonce($_POST['loyalty_program_shortcode_pages_nonce'], 'loyalty_program_shortcode_pages')
        ) {
            return;
        }

        // Save page IDs
        if (isset($_POST['loyalty_program_page_id'])) {
            update_option('loyalty_program_page_id', absint($_POST['loyalty_program_page_id']));
        }

        if (isset($_POST['loyalty_program_points_history_page_id'])) {
            update_option('loyalty_program_points_history_page_id', absint($_POST['loyalty_program_points_history_page_id']));
        }

        if (isset($_POST['loyalty_program_rewards_catalog_page_id'])) {
            update_option('loyalty_program_rewards_catalog_page_id', absint($_POST['loyalty_program_rewards_catalog_page_id']));
        }

        add_settings_error(
            'loyalty_program_shortcodes',
            'pages_saved',
            __('Loyalty program pages saved successfully.', 'loyalty-program'),
            'success'
        );
    }

    /**
     * Render a single reward row (helper for rewards.php)
     * 
     * @param int|string $index Row index
     * @param array $reward Reward data
     * @param array $products WooCommerce products
     * @param bool $wc_active WooCommerce status
     * @return string
     */
    public function render_reward_row($index, $reward, $wc_active)
    {
        $enabled = isset($reward['enabled']) ? $reward['enabled'] : 'yes';
        $disabled_class = $enabled === 'no' ? 'disabled' : '';

        $reward_type = isset($reward['type']) ? $reward['type'] : 'product';
        $product_id = isset($reward['product_id']) ? $reward['product_id'] : 0;
        $product_name = '';

        // Get product name if product_id is set
        if ($product_id && $wc_active) {
            $product_obj = wc_get_product($product_id);
            if ($product_obj) {
                $product_name = $product_obj->get_name();

                // For variations, add attributes
                if ($product_obj->is_type('variation')) {
                    $parent = wc_get_product($product_obj->get_parent_id());
                    if ($parent) {
                        $variation_attributes = array();
                        foreach ($product_obj->get_variation_attributes() as $attribute_name => $attribute_value) {
                            $variation_attributes[] = ucfirst($attribute_value);
                        }
                        $product_name = $parent->get_name() . ' - ' . implode(', ', $variation_attributes);
                    }
                }
            }
        }

        ob_start();
?>
        <tr class="reward-row <?php echo esc_attr($disabled_class); ?>" data-reward-type="<?php echo esc_attr($reward_type); ?>">
            <td class="row-number" style="text-align: center;"></td>
            <td class="drag-handle">
                <span class="dashicons dashicons-move"></span>
            </td>
            <td>
                <select
                    name="rewards[<?php echo esc_attr($index); ?>][type]"
                    class="reward-type-select"
                    data-index="<?php echo esc_attr($index); ?>"
                    style="width: 100%;"
                    required>
                    <option value="product" <?php selected($reward_type, 'product'); ?>><?php _e('Product', 'loyalty-program'); ?></option>
                    <option value="coupon" <?php selected($reward_type, 'coupon'); ?>><?php _e('Coupon', 'loyalty-program'); ?></option>
                </select>
            </td>
            <td>
                <input
                    type="text"
                    name="rewards[<?php echo esc_attr($index); ?>][name]"
                    class="reward-name"
                    value="<?php echo esc_attr($reward['name'] ?? ''); ?>"
                    placeholder="<?php esc_attr_e('e.g., Free Product Reward', 'loyalty-program'); ?>"
                    required>
            </td>
            <td>
                <textarea
                    name="rewards[<?php echo esc_attr($index); ?>][description]"
                    class="reward-description"
                    rows="2"
                    placeholder="<?php esc_attr_e('Short description of the reward...', 'loyalty-program'); ?>"
                    style="width: 100%; resize: vertical;"><?php echo esc_textarea($reward['description'] ?? ''); ?></textarea>
            </td>
            <td class="reward-product-cell">
                <?php if ($wc_active) : ?>
                    <select
                        name="rewards[<?php echo esc_attr($index); ?>][product_id]"
                        class="reward-product-select2"
                        data-index="<?php echo esc_attr($index); ?>"
                        style="width: 100%; <?php echo $reward_type === 'coupon' ? 'display: none;' : ''; ?>"
                        <?php echo $reward_type === 'product' ? 'required' : ''; ?>>
                        <?php if ($product_id && $product_name) : ?>
                            <option value="<?php echo esc_attr($product_id); ?>" selected>
                                <?php echo esc_html($product_name); ?>
                            </option>
                        <?php else : ?>
                            <option value=""><?php _e('-- Select Product --', 'loyalty-program'); ?></option>
                        <?php endif; ?>
                    </select>
                    <?php if ($reward_type === 'coupon') : ?>
                        <em style="color: #646970; font-style: italic;"><?php _e('Coupon reward', 'loyalty-program'); ?></em>
                    <?php endif; ?>
                <?php else : ?>
                    <input type="hidden" name="rewards[<?php echo esc_attr($index); ?>][product_id]" value="0">
                    <em style="color: #646970;"><?php _e('WooCommerce not active', 'loyalty-program'); ?></em>
                <?php endif; ?>
            </td>
            <td>
                <input
                    type="number"
                    name="rewards[<?php echo esc_attr($index); ?>][points]"
                    class="reward-points"
                    value="<?php echo esc_attr($reward['points'] ?? 100); ?>"
                    min="1"
                    step="1"
                    required>
            </td>
            <td>
                <input
                    type="number"
                    name="rewards[<?php echo esc_attr($index); ?>][price]"
                    class="reward-price"
                    value="<?php echo esc_attr($reward['price'] ?? 0.01); ?>"
                    min="0"
                    step="0.01"
                    required>
            </td>
            <td>
                <label class="toggle-switch">
                    <input
                        type="checkbox"
                        name="rewards[<?php echo esc_attr($index); ?>][enabled]"
                        class="reward-enabled-toggle"
                        value="yes"
                        <?php checked($enabled, 'yes'); ?>>
                    <span class="toggle-slider"></span>
                </label>
                <button type="button" class="button delete-reward-btn" title="<?php esc_attr_e('Delete', 'loyalty-program'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </td>
        </tr>
<?php
        return ob_get_clean();
    }

    /**
     * Render a single product reward row
     * 
     * @param int|string $index Row index
     * @param array $reward Reward data
     * @param bool $wc_active WooCommerce status
     * @return string
     */
    public function render_product_reward_row($index, $reward, $wc_active)
    {
        $enabled = isset($reward['enabled']) ? $reward['enabled'] : 'yes';
        $disabled_class = $enabled === 'no' ? 'disabled' : '';

        $product_id = isset($reward['product_id']) ? $reward['product_id'] : 0;
        $product_name = '';

        // Get product name if product_id is set
        if ($product_id && $wc_active) {
            $product_obj = wc_get_product($product_id);
            if ($product_obj) {
                $product_name = $product_obj->get_name();

                // For variations, add attributes
                if ($product_obj->is_type('variation')) {
                    $parent = wc_get_product($product_obj->get_parent_id());
                    if ($parent) {
                        $variation_attributes = array();
                        foreach ($product_obj->get_variation_attributes() as $attribute_name => $attribute_value) {
                            $variation_attributes[] = ucfirst($attribute_value);
                        }
                        $product_name = $parent->get_name() . ' - ' . implode(', ', $variation_attributes);
                    }
                }
            }
        }

        ob_start();
?>
        <tr class="reward-row <?php echo esc_attr($disabled_class); ?>">
            <td class="row-number" style="text-align: center;"></td>
            <td class="drag-handle">
                <span class="dashicons dashicons-move"></span>
            </td>
            <td>
                <input
                    type="text"
                    name="product_rewards[<?php echo esc_attr($index); ?>][name]"
                    class="reward-name"
                    value="<?php echo esc_attr($reward['name'] ?? ''); ?>"
                    placeholder="<?php esc_attr_e('e.g., Free Product Reward', 'loyalty-program'); ?>"
                    required>
            </td>
            <td>
                <textarea
                    name="product_rewards[<?php echo esc_attr($index); ?>][description]"
                    class="reward-description"
                    rows="2"
                    placeholder="<?php esc_attr_e('Short description of the reward...', 'loyalty-program'); ?>"
                    style="width: 100%; resize: vertical;"><?php echo esc_textarea($reward['description'] ?? ''); ?></textarea>
            </td>
            <td>
                <?php if ($wc_active) : ?>
                    <select
                        name="product_rewards[<?php echo esc_attr($index); ?>][product_id]"
                        class="reward-product-select2"
                        data-index="<?php echo esc_attr($index); ?>"
                        style="width: 100%;"
                        required>
                        <?php if ($product_id && $product_name) : ?>
                            <option value="<?php echo esc_attr($product_id); ?>" selected>
                                <?php echo esc_html($product_name); ?>
                            </option>
                        <?php else : ?>
                            <option value=""><?php _e('-- Select Product --', 'loyalty-program'); ?></option>
                        <?php endif; ?>
                    </select>
                <?php else : ?>
                    <input type="hidden" name="product_rewards[<?php echo esc_attr($index); ?>][product_id]" value="0">
                    <em style="color: #646970;"><?php _e('WooCommerce not active', 'loyalty-program'); ?></em>
                <?php endif; ?>
            </td>
            <td>
                <input
                    type="number"
                    name="product_rewards[<?php echo esc_attr($index); ?>][points]"
                    class="reward-points"
                    value="<?php echo esc_attr($reward['points'] ?? 100); ?>"
                    min="1"
                    step="1"
                    required>
            </td>
            <td>
                <input
                    type="number"
                    name="product_rewards[<?php echo esc_attr($index); ?>][price]"
                    class="reward-price"
                    value="<?php echo esc_attr($reward['price'] ?? 0.01); ?>"
                    min="0"
                    step="0.01"
                    required>
            </td>
            <td>
                <label class="toggle-switch">
                    <input
                        type="checkbox"
                        name="product_rewards[<?php echo esc_attr($index); ?>][enabled]"
                        class="reward-enabled-toggle"
                        value="yes"
                        <?php checked($enabled, 'yes'); ?>>
                    <span class="toggle-slider"></span>
                </label>
                <button type="button" class="button delete-product-reward-btn" title="<?php esc_attr_e('Delete', 'loyalty-program'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </td>
        </tr>
<?php
        return ob_get_clean();
    }

    /**
     * Render a single coupon reward row
     * 
     * @param int|string $index Row index
     * @param array $reward Reward data
     * @param bool $wc_active WooCommerce status
     * @return string
     */
    public function render_coupon_reward_row($index, $reward, $wc_active)
    {
        $enabled = isset($reward['enabled']) ? $reward['enabled'] : 'yes';
        $disabled_class = $enabled === 'no' ? 'disabled' : '';

        $coupon_type = isset($reward['type']) ? $reward['type'] : 'fixed_cart';
        $discount_value = isset($reward['discount_value']) ? $reward['discount_value'] : 10;
        $show_discount_field = $coupon_type !== 'free_shipping';

        ob_start();
?>
        <tr class="reward-row <?php echo esc_attr($disabled_class); ?>">
            <td class="row-number" style="text-align: center;"></td>
            <td class="drag-handle">
                <span class="dashicons dashicons-move"></span>
            </td>
            <td>
                <select
                    name="coupon_rewards[<?php echo esc_attr($index); ?>][type]"
                    class="coupon-type-select"
                    data-index="<?php echo esc_attr($index); ?>"
                    style="width: 100%;"
                    required>
                    <option value="fixed_cart" <?php selected($coupon_type, 'fixed_cart'); ?>><?php _e('Fixed Amount', 'loyalty-program'); ?></option>
                    <option value="percent" <?php selected($coupon_type, 'percent'); ?>><?php _e('Percentage', 'loyalty-program'); ?></option>
                    <option value="free_shipping" <?php selected($coupon_type, 'free_shipping'); ?>><?php _e('Free Shipping', 'loyalty-program'); ?></option>
                </select>
                <?php if ($coupon_type === 'fixed_cart' || $coupon_type === 'percent') : ?>
                <div class="coupon-discount-value-wrapper" style="margin-top: 5px; <?php echo $coupon_type === 'free_shipping' ? 'display: none;' : ''; ?>">
                    <input
                        type="number"
                        name="coupon_rewards[<?php echo esc_attr($index); ?>][discount_value]"
                        class="coupon-discount-value"
                        value="<?php echo esc_attr($discount_value); ?>"
                        min="0"
                        step="<?php echo $coupon_type === 'percent' ? '0.1' : '0.01'; ?>"
                        <?php echo $coupon_type === 'percent' ? 'max="100"' : ''; ?>
                        required>
                        
                    <span><?php echo $coupon_type === 'percent' ? '%' : 'PLN'; ?></span>
                </div>
                <?php endif; ?>
            </td>
            <td>
                <input
                    type="text"
                    name="coupon_rewards[<?php echo esc_attr($index); ?>][name]"
                    class="coupon-name"
                    value="<?php echo esc_attr($reward['name'] ?? ''); ?>"
                    placeholder="<?php esc_attr_e('e.g., 10% Discount Coupon', 'loyalty-program'); ?>"
                    required>
            </td>
            <td>
                <textarea
                    name="coupon_rewards[<?php echo esc_attr($index); ?>][description]"
                    class="coupon-description"
                    rows="2"
                    placeholder="<?php esc_attr_e('Short description of the coupon...', 'loyalty-program'); ?>"
                    style="width: 100%; resize: vertical;"><?php echo esc_textarea($reward['description'] ?? ''); ?></textarea>
            </td>
            <td>
                <?php
                $image_id = isset($reward['image_id']) ? absint($reward['image_id']) : 0;
                $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
                ?>
                <div class="coupon-image-upload-wrapper">
                    <div class="coupon-image-preview" style="margin-bottom: 10px;">
                        <?php if ($image_url) : ?>
                            <img src="<?php echo esc_url($image_url); ?>" style="max-width: 100px; max-height: 100px; display: block; margin-bottom: 5px;">
                            <button type="button" class="button remove-coupon-image-btn" style="display: block;"><?php _e('Remove Image', 'loyalty-program'); ?></button>
                        <?php else : ?>
                            <div style="width: 100px; height: 100px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; margin-bottom: 5px;">
                                <span class="dashicons dashicons-format-image" style="font-size: 32px; color: #ccc;"></span>
                            </div>
                            <button type="button" class="button upload-coupon-image-btn"><?php _e('Upload Image', 'loyalty-program'); ?></button>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="coupon_rewards[<?php echo esc_attr($index); ?>][image_id]" class="coupon-image-id" value="<?php echo esc_attr($image_id); ?>">
                </div>
            </td>
            <td>
                <input
                    type="number"
                    name="coupon_rewards[<?php echo esc_attr($index); ?>][points]"
                    class="coupon-points"
                    value="<?php echo esc_attr($reward['points'] ?? 1000); ?>"
                    min="1"
                    step="1"
                    required>
            </td>
            <td>
                <input
                    type="number"
                    name="coupon_rewards[<?php echo esc_attr($index); ?>][min_order_amount]"
                    class="coupon-min-order-amount"
                    value="<?php echo esc_attr($reward['min_order_amount'] ?? 0); ?>"
                    min="0"
                    step="0.01"
                    required>
            </td>
            <td>
                <label class="toggle-switch">
                    <input
                        type="checkbox"
                        name="coupon_rewards[<?php echo esc_attr($index); ?>][enabled]"
                        class="reward-enabled-toggle"
                        value="yes"
                        <?php checked($enabled, 'yes'); ?>>
                    <span class="toggle-slider"></span>
                </label>
                <button type="button" class="button delete-coupon-reward-btn" title="<?php esc_attr_e('Delete', 'loyalty-program'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </td>
        </tr>
<?php
        return ob_get_clean();
    }
}
