<?php

/**
 * Users List Table (Optimized for large databases)
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Loyalty_Program_Users_Table extends WP_List_Table
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(array(
            'singular' => __('User', 'loyalty-program'),
            'plural'   => __('Users', 'loyalty-program'),
            'ajax'     => false
        ));
    }

    /**
     * Get columns
     */
    public function get_columns()
    {
        return array(
            'cb'          => '<input type="checkbox" />',
            'user'        => __('User', 'loyalty-program'),
            'email'       => __('Email', 'loyalty-program'),
            'join_date'   => __('Join Date', 'loyalty-program'),
            'sms'         => __('SMS Notifications', 'loyalty-program'),
            'newsletter'  => __('Email Newsletter', 'loyalty-program'),
            'points'      => __('Current Points', 'loyalty-program'),
            'total'       => __('Total Earned', 'loyalty-program'),
            'actions'     => __('Actions', 'loyalty-program'),
        );
    }

    /**
     * Get sortable columns
     */
    public function get_sortable_columns()
    {
        return array(
            'user'      => array('display_name', false),
            'email'     => array('user_email', false),
            'join_date' => array('join_date', true),
            'points'    => array('points', false),
        );
    }

    /**
     * Get bulk actions
     */
    public function get_bulk_actions()
    {
        return array(
            'export' => __('Export Selected', 'loyalty-program'),
        );
    }

    /**
     * Prepare items - OPTIMIZED for large databases
     */
    public function prepare_items()
    {
        global $wpdb;

        $per_page = 50;
        $current_page = $this->get_pagenum();
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';

        // Load Points class
        if (!class_exists('Loyalty_Program_Points')) {
            require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
        }

        // Build query - ALWAYS show only members
        $where_clauses = array('1=1');
        $join_clauses = array();

        // ALWAYS join to show only members
        $join_clauses[] = "INNER JOIN {$wpdb->usermeta} um_member ON u.ID = um_member.user_id 
                          AND um_member.meta_key = 'loyalty_program_member' 
                          AND um_member.meta_value = 'yes'";

        // Search filter
        if ($search) {
            $where_clauses[] = $wpdb->prepare(
                '(u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s)',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        $join_sql = implode(' ', $join_clauses);
        $where_sql = implode(' AND ', $where_clauses);

        // Count total items
        $total_query = "SELECT COUNT(DISTINCT u.ID) 
                       FROM {$wpdb->users} u 
                       {$join_sql}
                       WHERE {$where_sql}";

        $total_items = $wpdb->get_var($total_query);

        // Get items - determine if we need to sort by points
        $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'ID';
        $order = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'DESC';
        $offset = ($current_page - 1) * $per_page;

        // OPTIMIZED: Sort by points or join_date directly in SQL using LEFT JOIN
        if ($orderby === 'points' || $orderby === 'join_date') {

            // Add LEFT JOIN for the meta data we're sorting by
            if ($orderby === 'points') {
                $join_clauses[] = "LEFT JOIN {$wpdb->usermeta} um_sort ON u.ID = um_sort.user_id 
                                  AND um_sort.meta_key = 'loyalty_program_current_points'";
                $order_clause = "ORDER BY CAST(COALESCE(um_sort.meta_value, 0) AS UNSIGNED) {$order}";
            } else { // join_date
                $join_clauses[] = "LEFT JOIN {$wpdb->usermeta} um_sort ON u.ID = um_sort.user_id 
                                  AND um_sort.meta_key = 'loyalty_program_join_date'";
                $order_clause = "ORDER BY COALESCE(um_sort.meta_value, '1970-01-01') {$order}";
            }

            // Rebuild join SQL with new sort join
            $join_sql = implode(' ', $join_clauses);

            // Get ONLY paginated results - 50 rows instead of 130,000!
            $query = $wpdb->prepare(
                "SELECT DISTINCT u.ID, u.user_login, u.user_email, u.display_name
                FROM {$wpdb->users} u
                {$join_sql}
                WHERE {$where_sql}
                {$order_clause}
                LIMIT %d OFFSET %d",
                $per_page,
                $offset
            );

            $users = $wpdb->get_results($query);

            // Get additional data for ONLY these 50 users (not 130k!)
            $items = array();
            foreach ($users as $user) {
                $user_id = $user->ID;
                $current_points = Loyalty_Program_Points::get_current_points($user_id);
                $total_earned = Loyalty_Program_Points::get_total_earned($user_id);
                $join_date = get_user_meta($user_id, 'loyalty_program_join_date', true);
                $sms_consent = get_user_meta($user_id, 'loyalty_program_sms_consent', true) === 'yes';
                $newsletter_consent = get_user_meta($user_id, 'loyalty_program_newsletter_consent', true) === 'yes';

                $items[] = array(
                    'ID'                 => $user_id,
                    'user_login'         => $user->user_login,
                    'user_email'         => $user->user_email,
                    'display_name'       => $user->display_name,
                    'is_member'          => true,
                    'join_date'          => $join_date,
                    'sms_consent'        => $sms_consent,
                    'newsletter_consent' => $newsletter_consent,
                    'current_points'     => $current_points,
                    'total_earned'       => $total_earned,
                );
            }
        } else {
            // Regular SQL sorting for other columns
            $query = $wpdb->prepare(
                "SELECT DISTINCT u.ID, u.user_login, u.user_email, u.display_name
                FROM {$wpdb->users} u
                {$join_sql}
                WHERE {$where_sql}
                ORDER BY u.{$orderby} {$order}
                LIMIT %d OFFSET %d",
                $per_page,
                $offset
            );

            $users = $wpdb->get_results($query);

            // Prepare items
            $items = array();
            foreach ($users as $user) {
                $user_id = $user->ID;

                $current_points = Loyalty_Program_Points::get_current_points($user_id);
                $total_earned = Loyalty_Program_Points::get_total_earned($user_id);

                // Get consents and join date
                $join_date = get_user_meta($user_id, 'loyalty_program_join_date', true);
                $sms_consent = get_user_meta($user_id, 'loyalty_program_sms_consent', true) === 'yes';
                $newsletter_consent = get_user_meta($user_id, 'loyalty_program_newsletter_consent', true) === 'yes';

                $items[] = array(
                    'ID'                 => $user_id,
                    'user_login'         => $user->user_login,
                    'user_email'         => $user->user_email,
                    'display_name'       => $user->display_name,
                    'is_member'          => true,
                    'join_date'          => $join_date,
                    'sms_consent'        => $sms_consent,
                    'newsletter_consent' => $newsletter_consent,
                    'current_points'     => $current_points,
                    'total_earned'       => $total_earned,
                );
            }
        }

        $this->items = $items;

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
    }

    /**
     * Column checkbox
     */
    protected function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="users[]" value="%s" />',
            $item['ID']
        );
    }

    /**
     * Column user
     */
    protected function column_user($item)
    {
        return sprintf(
            '<strong>%s</strong><br><small style="color: #646970;">%s (ID: %d)</small>',
            esc_html($item['display_name']),
            esc_html($item['user_login']),
            $item['ID']
        );
    }

    /**
     * Column email
     */
    protected function column_email($item)
    {
        return sprintf(
            '<a href="mailto:%s">%s</a>',
            esc_attr($item['user_email']),
            esc_html($item['user_email'])
        );
    }

    /**
     * Column join_date
     */
    protected function column_join_date($item)
    {
        if ($item['join_date']) {
            // Convert MySQL datetime string to timestamp
            $timestamp = strtotime($item['join_date']);
            return date_i18n(
                get_option('date_format') . ' ' . get_option('time_format'),
                $timestamp
            );
        }
        return '<span style="color: #646970;">—</span>';
    }

    /**
     * Column points
     */
    protected function column_points($item)
    {
        return sprintf(
            '<strong style="color: #2271b1; font-size: 16px;">%s</strong>',
            number_format_i18n($item['current_points'])
        );
    }

    /**
     * Column total
     */
    protected function column_total($item)
    {
        return sprintf(
            '<strong>%s</strong>',
            number_format_i18n($item['total_earned'])
        );
    }

    /**
     * Column sms
     */
    protected function column_sms($item)
    {
        if ($item['sms_consent']) {
            return '<span style="color: #00a32a; font-size: 18px;">✓</span>';
        }
        return '<span style="color: #d63638; font-size: 18px;">✗</span>';
    }

    /**
     * Column newsletter
     */
    protected function column_newsletter($item)
    {
        if ($item['newsletter_consent']) {
            return '<span style="color: #00a32a; font-size: 18px;">✓</span>';
        }
        return '<span style="color: #d63638; font-size: 18px;">✗</span>';
    }

    /**
     * Column actions
     */
    protected function column_actions($item)
    {
        return sprintf(
            '<button type="button" class="button button-small view-user-details" data-user-id="%d">
                <span class="dashicons dashicons-visibility"></span> %s
            </button>
            <button type="button" class="button button-small manage-points-btn" data-user-id="%d" data-user-name="%s">
                <span class="dashicons dashicons-star-filled"></span> %s
            </button>',
            $item['ID'],
            __('Details', 'loyalty-program'),
            $item['ID'],
            esc_attr($item['display_name']),
            __('Points', 'loyalty-program')
        );
    }

    /**
     * No items found
     */
    public function no_items()
    {
        _e('No users found.', 'loyalty-program');
    }
}