<?php

/**
 * Survey Results List Table
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

class Loyalty_Program_Survey_Results_Table extends WP_List_Table
{
    private $survey_id;
    private $survey;

    /**
     * Constructor
     */
    public function __construct($survey_id)
    {
        $this->survey_id = $survey_id;

        // Get survey data
        $surveys = get_option('loyalty_program_surveys', array());
        foreach ($surveys as $s) {
            if ($s['id'] === $survey_id) {
                $this->survey = $s;
                break;
            }
        }

        parent::__construct(array(
            'singular' => __('Result', 'loyalty-program'),
            'plural'   => __('Results', 'loyalty-program'),
            'ajax'     => false
        ));
    }

    /**
     * Get columns
     */
    public function get_columns()
    {
        $columns = array(
            'user'         => __('User', 'loyalty-program'),
            'email'        => __('Email', 'loyalty-program'),
            'completed_at' => __('Completed', 'loyalty-program'),
        );

        if ($this->survey && $this->survey['type'] === 'quiz') {
            $columns['score'] = __('Score', 'loyalty-program');
            $columns['correct'] = __('Correct Answers', 'loyalty-program');
        }

        $columns['points'] = __('Points Earned', 'loyalty-program');
        $columns['actions'] = __('Actions', 'loyalty-program');

        return $columns;
    }

    /**
     * Get sortable columns
     */
    public function get_sortable_columns()
    {
        return array(
            'user'         => array('display_name', false),
            'email'        => array('user_email', false),
            'completed_at' => array('completed_at', true),
            'points'       => array('points_earned', false),
        );
    }

    /**
     * Prepare items
     */
    public function prepare_items()
    {
        global $wpdb;

        $per_page = 50; // Optymalna liczba dla dużych zbiorów danych
        $current_page = $this->get_pagenum();
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';

        // Get user IDs who completed this survey
        $user_ids_query = $wpdb->prepare(
            "SELECT DISTINCT user_id 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = 'loyalty_program_completed_surveys' 
            AND meta_value LIKE %s",
            '%' . $wpdb->esc_like($this->survey_id) . '%'
        );

        // Add search filter
        if ($search) {
            $user_ids_query = $wpdb->prepare(
                "SELECT DISTINCT um.user_id 
                FROM {$wpdb->usermeta} um
                INNER JOIN {$wpdb->users} u ON um.user_id = u.ID
                WHERE um.meta_key = 'loyalty_program_completed_surveys' 
                AND um.meta_value LIKE %s
                AND (u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s)",
                '%' . $wpdb->esc_like($this->survey_id) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        $user_ids = $wpdb->get_col($user_ids_query);
        $total_items = count($user_ids);

        // Collect data
        $items = array();
        foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);
            if (!$user) continue;

            $responses = get_user_meta($user_id, 'loyalty_program_survey_responses', true);

            if (isset($responses[$this->survey_id])) {
                $response = $responses[$this->survey_id];
                $items[] = array(
                    'user_id'          => $user_id,
                    'user_login'       => $user->user_login,
                    'user_email'       => $user->user_email,
                    'display_name'     => $user->display_name,
                    'completed_at'     => $response['completed_at'],
                    'points_earned'    => isset($response['points_earned']) ? $response['points_earned'] : 0,
                    'score_percentage' => isset($response['score_percentage']) ? $response['score_percentage'] : null,
                    'correct_answers'  => isset($response['correct_answers']) ? $response['correct_answers'] : null,
                    'total_questions'  => isset($response['total_questions']) ? $response['total_questions'] : null,
                    'answers'          => $response['answers'],
                );
            }
        }

        // Sorting
        $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'completed_at';
        $order = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'desc';

        usort($items, function ($a, $b) use ($orderby, $order) {
            $result = 0;

            switch ($orderby) {
                case 'display_name':
                    $result = strcmp($a['display_name'], $b['display_name']);
                    break;
                case 'user_email':
                    $result = strcmp($a['user_email'], $b['user_email']);
                    break;
                case 'completed_at':
                    $result = strcmp($a['completed_at'], $b['completed_at']);
                    break;
                case 'points_earned':
                    $result = $a['points_earned'] - $b['points_earned'];
                    break;
                default:
                    $result = 0;
            }

            return ($order === 'asc') ? $result : -$result;
        });

        // Pagination
        $offset = ($current_page - 1) * $per_page;
        $items = array_slice($items, $offset, $per_page);

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
     * Column default
     */
    protected function column_default($item, $column_name)
    {
        return isset($item[$column_name]) ? $item[$column_name] : '';
    }

    /**
     * Column user
     */
    protected function column_user($item)
    {
        return sprintf(
            '<strong>%s</strong><br><small style="color: #646970;">%s</small>',
            esc_html($item['display_name']),
            esc_html($item['user_login'])
        );
    }

    /**
     * Column email
     */
    protected function column_email($item)
    {
        return esc_html($item['user_email']);
    }

    /**
     * Column completed_at
     */
    protected function column_completed_at($item)
    {
        return date_i18n(
            get_option('date_format') . ' ' . get_option('time_format'),
            strtotime($item['completed_at'])
        );
    }

    /**
     * Column score
     */
    protected function column_score($item)
    {
        if ($item['score_percentage'] === null) {
            return 'N/A';
        }
        return sprintf(
            '<strong style="color: #2271b1;">%s%%</strong>',
            esc_html($item['score_percentage'])
        );
    }

    /**
     * Column correct
     */
    protected function column_correct($item)
    {
        $correct = $item['correct_answers'] ?? 0;
        $total = $item['total_questions'] ?? count($this->survey['questions']);

        return sprintf('%d / %d', $correct, $total);
    }

    /**
     * Column points
     */
    protected function column_points($item)
    {
        return sprintf(
            '<strong style="color: #00a32a;">%d</strong>',
            esc_html($item['points_earned'])
        );
    }

    /**
     * Column actions
     */
    protected function column_actions($item)
    {
        return sprintf(
            '<button type="button" class="button button-small view-answers-btn" data-user-id="%d" data-user-name="%s">
                <span class="dashicons dashicons-visibility"></span> %s
            </button>',
            esc_attr($item['user_id']),
            esc_attr($item['display_name']),
            __('View Answers', 'loyalty-program')
        );
    }

    /**
     * No items found
     */
    public function no_items()
    {
        _e('No results found.', 'loyalty-program');
    }
}
