<?php

/**
 * Admin Survey Results View
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$survey_id = isset($_GET['survey_id']) ? sanitize_text_field($_GET['survey_id']) : '';

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

// Load results table
require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/admin/class-loyalty-program-survey-results-table.php';
$results_table = new Loyalty_Program_Survey_Results_Table($survey_id);
$results_table->prepare_items();

// Calculate statistics efficiently
$is_quiz = $survey['type'] === 'quiz';
$stats = loyalty_get_survey_statistics($survey_id, $is_quiz);

$total_completions = $stats['total_completions'];
$avg_score = $stats['avg_score'];
$total_points = $stats['total_points'];

/**
 * Get survey statistics (optimized for large datasets)
 */
function loyalty_get_survey_statistics($survey_id, $is_quiz)
{
    global $wpdb;

    // Get count efficiently
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) 
        FROM {$wpdb->usermeta} 
        WHERE meta_key = 'loyalty_program_completed_surveys' 
        AND meta_value LIKE %s",
        '%' . $wpdb->esc_like($survey_id) . '%'
    ));

    $stats = array(
        'total_completions' => intval($total),
        'avg_score' => 0,
        'total_points' => 0,
    );

    if ($is_quiz && $total > 0) {
        // Get all user IDs
        $user_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = 'loyalty_program_completed_surveys' 
            AND meta_value LIKE %s",
            '%' . $wpdb->esc_like($survey_id) . '%'
        ));

        $total_score = 0;
        $total_points = 0;
        $count = 0;

        foreach ($user_ids as $user_id) {
            $responses = get_user_meta($user_id, 'loyalty_program_survey_responses', true);
            if (isset($responses[$survey_id])) {
                $response = $responses[$survey_id];
                if (isset($response['score_percentage'])) {
                    $total_score += $response['score_percentage'];
                    $count++;
                }
                if (isset($response['points_earned'])) {
                    $total_points += $response['points_earned'];
                }
            }
        }

        $stats['avg_score'] = $count > 0 ? round($total_score / $count, 1) : 0;
        $stats['total_points'] = $total_points;
    }

    return $stats;
}

?>

<div class="wrap loyalty-survey-results-page">
    <div class="loyalty-survey-results-page-head">
        <h1 class="wp-heading-inline">
            <?php echo esc_html($survey['name']); ?> -
            <?php echo $is_quiz ? __('Quiz Results', 'loyalty-program') : __('Survey Results', 'loyalty-program'); ?>
        </h1>

        <div class="wrap-page-title-action">
            <a href="admin.php?page=loyalty-program-surveys" class="page-title-action">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
                <?php _e('Back to Surveys', 'loyalty-program'); ?>
            </a>

            <a href="<?php echo admin_url('admin-ajax.php?action=export_survey_results&survey_id=' . urlencode($survey_id) . '&nonce=' . wp_create_nonce('export_survey_' . $survey_id)); ?>"
                class="page-title-action" download>
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export to CSV', 'loyalty-program'); ?>
            </a>
        </div>
    </div>
    <hr class="wp-header-end">

    <!-- Statistics -->
    <div class="loyalty-results-stats">
        <div class="results-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($total_completions); ?></div>
                <div class="stat-label"><?php _e('Total Completions', 'loyalty-program'); ?></div>
            </div>
        </div>

        <?php if ($is_quiz) : ?>
        <div class="results-stat-card">
            <div class="stat-icon" style="background: #00a32a;">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($avg_score); ?>%</div>
                <div class="stat-label"><?php _e('Average Score', 'loyalty-program'); ?></div>
            </div>
        </div>

        <div class="results-stat-card">
            <div class="stat-icon" style="background: #d63638;">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($total_points); ?></div>
                <div class="stat-label"><?php _e('Total Points Awarded', 'loyalty-program'); ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Results Table -->
    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
        <input type="hidden" name="survey_id" value="<?php echo esc_attr($survey_id); ?>">
        <?php $results_table->search_box(__('Search users', 'loyalty-program'), 'user'); ?>
        <?php $results_table->display(); ?>
    </form>

    <!-- User Answers Modal -->
    <div id="answers-details-modal" style="display: none;">
        <div class="answers-modal-content">
            <span class="close-answers-modal">&times;</span>
            <h2 id="answers-user-name"></h2>
            <div id="answers-content"></div>
        </div>
    </div>
</div>

<style>
.loyalty-survey-results-page {
    max-width: 1400px;
}

.loyalty-results-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.results-stat-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.results-stat-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: #2271b1;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-icon .dashicons {
    font-size: 30px;
    width: 30px;
    height: 30px;
    color: #fff;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #1d2327;
    line-height: 1;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 13px;
    color: #646970;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

#answers-details-modal {
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

.answers-modal-content {
    background-color: #fff;
    padding: 30px;
    border-radius: 8px;
    max-width: 800px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.close-answers-modal {
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

.close-answers-modal:hover {
    color: #d63638;
}

.answers-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.answers-table th,
.answers-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #dcdcde;
}

.answers-table th {
    background: #f0f0f1;
    font-weight: 600;
}

.answers-table tr:hover {
    background: #f9f9f9;
}

.view-answers-btn .dashicons {
    margin-top: 2px;
}

@media screen and (max-width: 768px) {
    .loyalty-results-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    const survey = <?php echo json_encode($survey); ?>;
    const allResults = <?php
                            global $wpdb;
                            $user_ids = $wpdb->get_col($wpdb->prepare(
                                "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'loyalty_program_completed_surveys' AND meta_value LIKE %s",
                                '%' . $wpdb->esc_like($survey_id) . '%'
                            ));
                            $all_responses = array();
                            foreach ($user_ids as $uid) {
                                $responses = get_user_meta($uid, 'loyalty_program_survey_responses', true);
                                if (isset($responses[$survey_id])) {
                                    $all_responses[$uid] = $responses[$survey_id];
                                }
                            }
                            echo json_encode($all_responses);
                            ?>;

    // View answers
    $('.view-answers-btn').on('click', function() {
        const userId = $(this).data('user-id');
        const userName = $(this).data('user-name');

        const userResponse = allResults[userId];
        if (!userResponse) return;

        let answersHtml = '<table class="answers-table wp-list-table widefat">';
        answersHtml += '<thead><tr><th style="width: 50%;">Question</th><th>Answer</th></tr></thead>';
        answersHtml += '<tbody>';

        survey.questions.forEach((question, index) => {
            answersHtml += '<tr>';
            answersHtml += '<td><strong>' + question.text + '</strong>';
            if (question.description) {
                answersHtml += '<br><small style="color: #646970;">' + question.description +
                    '</small>';
            }
            answersHtml += '</td>';
            answersHtml += '<td>';

            const userAnswer = userResponse.answers[index];

            if (userAnswer !== undefined && userAnswer !== null && userAnswer !== '') {
                if (Array.isArray(userAnswer)) {
                    // Multiple answers (checkbox)
                    userAnswer.forEach(ansIndex => {
                        if (question.answers[ansIndex]) {
                            answersHtml +=
                                '<span style="display: block; margin: 2px 0;">✓ ' +
                                question.answers[ansIndex].text + '</span>';
                        }
                    });
                } else if (['radio', 'checkbox'].includes(question.answerType)) {
                    // Single choice
                    if (question.answers[userAnswer]) {
                        answersHtml += question.answers[userAnswer].text;
                    }
                } else {
                    // Text/number/textarea/rating
                    answersHtml += userAnswer;
                }
            } else {
                answersHtml += '<em style="color: #646970;">No answer</em>';
            }

            answersHtml += '</td>';
            answersHtml += '</tr>';
        });

        answersHtml += '</tbody></table>';

        $('#answers-user-name').text(userName + ' - Answers');
        $('#answers-content').html(answersHtml);
        $('#answers-details-modal').fadeIn(300);
    });

    // Close modal
    $('.close-answers-modal, #answers-details-modal').on('click', function(e) {
        if (e.target === this) {
            $('#answers-details-modal').fadeOut(300);
        }
    });
});
</script>