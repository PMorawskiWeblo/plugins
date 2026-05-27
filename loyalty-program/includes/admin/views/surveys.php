<?php

/**
 * Admin Surveys & Quizzes View
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get saved surveys
$surveys = get_option('loyalty_program_surveys', array());

settings_errors('loyalty_program_surveys');
?>

<div class="wrap loyalty-program-surveys">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <p class="description">
        <?php _e('Manage surveys and quizzes for your loyalty program. Configure point rewards and track user participation.', 'loyalty-program'); ?>
    </p>

    <div class="loyalty-surveys-section">
        <form method="post" action="" id="surveys-form">
            <?php wp_nonce_field('loyalty_program_surveys', 'loyalty_program_surveys_nonce'); ?>

            <div class="loyalty-surveys-header">
                <h2><?php _e('Surveys & Quizzes', 'loyalty-program'); ?></h2>
                <button type="button" id="add-survey-btn" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Add New', 'loyalty-program'); ?>
                </button>
            </div>

            <div class="loyalty-surveys-table-wrapper">
                <table class="wp-list-table widefat fixed striped loyalty-surveys-table">
                    <thead>
                        <tr>
                            <th style="width: 30px;"><?php _e('#', 'loyalty-program'); ?></th>
                            <th style="width: 30px;"></th>
                            <th style="width: 250px;"><?php _e('Name', 'loyalty-program'); ?></th>
                            <th style="width: 90px;"><?php _e('Type', 'loyalty-program'); ?></th>
                            <th style="width: 90px; text-align: center;"><?php _e('Completions', 'loyalty-program'); ?>
                            </th>
                            <th><?php _e('Shortcode', 'loyalty-program'); ?></th>
                            <th style="width: 220px; text-align: center;"><?php _e('Actions', 'loyalty-program'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="surveys-tbody" class="surveys-sortable">
                        <?php if (!empty($surveys)) : ?>
                        <?php foreach ($surveys as $index => $survey) : ?>
                        <?php echo render_survey_row($index, $survey); ?>
                        <?php endforeach; ?>
                        <?php else : ?>
                        <tr class="no-surveys-row">
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <span class="dashicons dashicons-clipboard"
                                    style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php _e('No surveys or quizzes yet. Click "Add New" to create your first one.', 'loyalty-program'); ?>
                                </p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <input type="hidden" name="surveys_data" id="surveys-data-input" value="">

            <p class="submit">
            <div class="save-changes-notice"
                style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin-bottom: 15px;">
                <span class="dashicons dashicons-info" style="color: #dba617; vertical-align: middle;"></span>
                <strong><?php _e('Important:', 'loyalty-program'); ?></strong>
                <?php _e('You must click "Save Changes" after adding a new survey/quiz or making changes to save them permanently.', 'loyalty-program'); ?>
            </div>
            <button type="submit" name="loyalty_program_surveys_save" class="button button-primary">
                <?php _e('Save Changes', 'loyalty-program'); ?>
            </button>
            </p>
        </form>
    </div>

</div>

<!-- Survey Row Template -->
<script type="text/template" id="survey-row-template">
    <?php echo render_survey_row('{{INDEX}}', array(
        'name' => '',
        'type' => 'survey',
        'enabled' => 'yes',
    )); ?>
</script>

<?php
/**
 * Get completion count for survey
 */
function get_survey_completion_count($survey_id)
{
    global $wpdb;

    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) 
        FROM {$wpdb->usermeta} 
        WHERE meta_key = 'loyalty_program_completed_surveys' 
        AND meta_value LIKE %s",
        '%' . $wpdb->esc_like($survey_id) . '%'
    ));

    return intval($count);
}

/**
 * Render a single survey row
 */
function render_survey_row($index, $survey)
{
    $enabled = isset($survey['enabled']) && $survey['enabled'] === 'yes' ? 'yes' : 'no';
    $type = isset($survey['type']) ? $survey['type'] : 'survey';
    $survey_id = isset($survey['id']) ? $survey['id'] : 'survey_' . time() . '_' . rand(1000, 9999);
    $completion_count = get_survey_completion_count($survey_id);

    ob_start();
?>
<tr class="survey-row" data-index="<?php echo esc_attr($index); ?>"
    data-survey-id="<?php echo esc_attr($survey_id); ?>">
    <td class="survey-number" style="text-align: center;"></td>
    <td class="drag-handle">
        <span class="dashicons dashicons-move"></span>
    </td>
    <td>
        <strong
            class="survey-name-display"><?php echo esc_html($survey['name'] ?? __('Untitled', 'loyalty-program')); ?></strong>
        <input type="hidden" class="survey-data" value='<?php echo esc_attr(json_encode($survey)); ?>'>
    </td>
    <td>
        <span class="survey-type-badge <?php echo esc_attr($type); ?>">
            <?php echo $type === 'quiz' ? __('Quiz', 'loyalty-program') : __('Survey', 'loyalty-program'); ?>
        </span>
    </td>
    <td style="text-align: center;">
        <strong class="completion-count"><?php echo esc_html($completion_count); ?></strong>
        <?php if ($completion_count > 0) : ?>
        <?php endif; ?>
    </td>
    <td>
        <code class="survey-shortcode">[loyalty_survey id="<?php echo esc_attr($survey_id); ?>"]</code>
    </td>
    <td style="text-align: center;">
        <div class="survey-actions">
            <button type="button" class="button button-small survey-icon-btn edit-survey-btn"
                data-index="<?php echo esc_attr($index); ?>" title="<?php esc_attr_e('Edit', 'loyalty-program'); ?>">
                <span class="dashicons dashicons-edit"></span>
            </button>
            <button type="button" class="button button-small survey-icon-btn view-results-btn"
                data-survey-id="<?php echo esc_attr($survey_id); ?>"
                data-survey-name="<?php echo esc_attr($survey['name'] ?? 'Untitled'); ?>"
                data-survey-type="<?php echo esc_attr($type); ?>"
                title="<?php esc_attr_e('View Results', 'loyalty-program'); ?>">
                <span class="dashicons dashicons-chart-bar"></span>
            </button>
            <button type="button" class="button button-small survey-icon-btn copy-survey-shortcode-btn"
                data-shortcode='[loyalty_survey id="<?php echo esc_attr($survey_id); ?>"]'
                title="<?php esc_attr_e('Copy Shortcode', 'loyalty-program'); ?>">
                <span class="dashicons dashicons-admin-page"></span>
            </button>
            <label class="toggle-switch" title="<?php esc_attr_e('Enable/Disable', 'loyalty-program'); ?>">
                <input type="checkbox" class="survey-enabled-toggle" value="yes" <?php checked($enabled, 'yes'); ?>>
                <span class="toggle-slider"></span>
            </label>
            <button type="button" class="button survey-icon-btn delete-survey-btn"
                title="<?php esc_attr_e('Delete', 'loyalty-program'); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>
    </td>
</tr>
<?php
    return ob_get_clean();
}
?>

<style>
.loyalty-program-surveys {
    max-width: 1200px;
}

.loyalty-surveys-placeholder {
    background: #fff;
    border: 1px solid #dcdcde;
    border-radius: 8px;
    padding: 60px 40px;
    margin: 30px 0;
    text-align: center;
}

.surveys-empty-state {
    max-width: 600px;
    margin: 0 auto;
}

.surveys-empty-state .dashicons {
    font-size: 80px;
    width: 80px;
    height: 80px;
    color: #c3c4c7;
    margin-bottom: 20px;
}

.surveys-empty-state h2 {
    margin: 20px 0 10px;
    color: #1d2327;
    font-size: 24px;
}

.surveys-empty-state p {
    color: #646970;
    font-size: 16px;
    line-height: 1.6;
    margin: 0;
}

/* Survey Card Styles (for future use) */
.loyalty-survey-card {
    background: #fff;
    border: 1px solid #dcdcde;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
}

.loyalty-survey-card:hover {
    border-color: #2271b1;
    box-shadow: 0 2px 8px rgba(34, 113, 177, 0.1);
}

.loyalty-survey-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #dcdcde;
}

.loyalty-survey-title {
    font-size: 18px;
    font-weight: 600;
    color: #1d2327;
    margin: 0;
}

.loyalty-survey-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.loyalty-survey-status.active {
    background: #d5f4e6;
    color: #00a32a;
}

.loyalty-survey-status.inactive {
    background: #f0f0f1;
    color: #646970;
}

.loyalty-survey-body {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.loyalty-survey-stat {
    padding: 15px;
    background: #f9fafb;
    border-radius: 4px;
}

.loyalty-survey-stat-label {
    font-size: 12px;
    color: #646970;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.loyalty-survey-stat-value {
    font-size: 20px;
    font-weight: 700;
    color: #1d2327;
}

.loyalty-survey-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.loyalty-survey-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.loyalty-surveys-section {
    background: #fff;
    padding: 20px;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    margin-top: 20px;
}

.loyalty-surveys-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f1;
}

.loyalty-surveys-header h2 {
    margin: 0;
    font-size: 20px;
}

.loyalty-surveys-table .drag-handle {
    cursor: move;
    text-align: center;
    color: #c3c4c7;
}

.loyalty-surveys-table .drag-handle:hover {
    color: #2271b1;
}

.survey-type-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.survey-type-badge.quiz {
    background: #e6f2ff;
    color: #2271b1;
}

.survey-type-badge.survey {
    background: #f0f0f1;
    color: #646970;
}

.shortcode-display {
    display: flex;
    align-items: center;
    gap: 5px;
}

.shortcode-display code {
    background: #f6f7f7;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
}

.copy-shortcode-btn {
    padding: 4px 8px !important;
    height: auto !important;
    min-height: 0 !important;
}

.survey-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    margin: 0;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #c3c4c7;
    transition: .3s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
}

.toggle-switch input:checked+.toggle-slider {
    background-color: #2271b1;
}

.toggle-switch input:checked+.toggle-slider:before {
    transform: translateX(20px);
}

.survey-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.survey-icon-btn {
    min-width: 32px !important;
    height: 32px !important;
    padding: 4px !important;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.survey-icon-btn .dashicons {
    margin: 0 !important;
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.survey-icon-btn.edit-survey-btn {
    color: #2271b1 !important;
    border-color: #2271b1 !important;
}

.survey-icon-btn.edit-survey-btn:hover {
    background: #2271b1 !important;
    color: #fff !important;
}

.survey-icon-btn.view-results-btn {
    color: #00a32a !important;
    border-color: #00a32a !important;
}

.survey-icon-btn.view-results-btn:hover {
    background: #00a32a !important;
    color: #fff !important;
}

.survey-icon-btn.copy-survey-shortcode-btn {
    color: #f59e0b !important;
    border-color: #f59e0b !important;
}

.survey-icon-btn.copy-survey-shortcode-btn:hover {
    background: #f59e0b !important;
    color: #fff !important;
}

.delete-survey-btn {
    color: #d63638 !important;
    border-color: #d63638 !important;
}

.delete-survey-btn:hover {
    background: #d63638 !important;
    color: #fff !important;
}

.survey-shortcode {
    background: #f0f6fc;
    padding: 4px 8px;
    border-radius: 3px;
    color: #2271b1;
    font-size: 13px;
}

/* Survey Editor Modal */
.survey-editor-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 999;
}

.survey-editor-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90%;
    max-width: 1000px;
    max-height: 90vh;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
}

.survey-editor-header {
    padding: 20px 25px;
    border-bottom: 1px solid #dcdcde;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.survey-editor-header h2 {
    margin: 0;
    font-size: 20px;
}

.survey-editor-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #646970;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.survey-editor-close:hover {
    background: #f0f0f1;
    color: #d63638;
}

.survey-editor-body {
    padding: 25px;
    overflow-y: auto;
    flex: 1;
}

.survey-editor-footer {
    padding: 15px 25px;
    border-top: 1px solid #dcdcde;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.survey-field-group {
    margin-bottom: 20px;
}

.survey-field-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #1d2327;
}

.survey-field-group input[type="text"],
.survey-field-group textarea,
.survey-field-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
}

.survey-field-group textarea {
    min-height: 80px;
    resize: vertical;
}

.survey-tabs {
    display: flex;
    gap: 0;
    border-bottom: 1px solid #dcdcde;
    margin-bottom: 20px;
}

.survey-tab {
    padding: 12px 20px;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    font-weight: 600;
    color: #646970;
    transition: all 0.3s;
}

.survey-tab:hover {
    color: #2271b1;
}

.survey-tab.active {
    color: #2271b1;
    border-bottom-color: #2271b1;
}

.survey-tab-content {
    display: none;
}

.survey-tab-content.active {
    display: block;
}

/* Questions Section */
.questions-list {
    margin-top: 20px;
}

.question-item {
    background: #f9fafb;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
}

.question-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    cursor: move;
}

.question-drag-handle {
    color: #c3c4c7;
    margin-right: 10px;
}

.question-drag-handle:hover {
    color: #2271b1;
}

.question-title-bar {
    flex: 1;
    font-weight: 600;
    color: #1d2327;
}

.question-actions {
    display: flex;
    gap: 5px;
}

.question-body {
    padding-left: 30px;
}

.answers-list {
    margin-top: 10px;
    padding-left: 20px;
}

.answer-item {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 10px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.answer-drag {
    color: #c3c4c7;
    cursor: move;
}

.answer-drag:hover {
    color: #2271b1;
}

.answer-content {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
}

.answer-content input[type="text"] {
    flex: 1;
}

.answer-correct-check {
    margin: 0;
}

.answer-points {
    width: 80px;
}

.add-question-btn,
.add-answer-btn {
    margin-top: 10px;
}

.answer-preview-section {
    margin-top: 15px;
    padding: 15px;
    background: #fff;
    border: 1px solid #dcdcde;
    border-radius: 4px;
}

.answer-preview-section label {
    font-weight: 600;
    margin-bottom: 10px;
    display: block;
    color: #646970;
}

.rating-preview {
    display: flex;
    gap: 5px;
}

.rating-preview .dashicons {
    font-size: 32px !important;
    width: 32px !important;
    height: 32px !important;
    color: #f59e0b !important;
}

/* Settings Section */
.survey-settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.setting-field {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 4px;
}

.setting-field label {
    margin: 0;
    cursor: pointer;
}

.setting-field input[type="checkbox"] {
    margin: 0;
}
</style>

<!-- Survey Editor Modal -->
<div class="survey-editor-overlay" id="survey-editor-overlay">
    <div class="survey-editor-modal">
        <div class="survey-editor-header">
            <h2 id="survey-editor-title"><?php _e('Add Survey/Quiz', 'loyalty-program'); ?></h2>
            <button type="button" class="survey-editor-close" id="close-survey-editor">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>

        <div class="survey-editor-body">
            <!-- Tabs -->
            <div class="survey-tabs">
                <button type="button" class="survey-tab active" data-tab="basic">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php _e('Basic Info', 'loyalty-program'); ?>
                </button>
                <button type="button" class="survey-tab" data-tab="questions">
                    <span class="dashicons dashicons-editor-help"></span>
                    <?php _e('Questions', 'loyalty-program'); ?>
                </button>
                <button type="button" class="survey-tab" data-tab="settings">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Settings', 'loyalty-program'); ?>
                </button>
            </div>

            <!-- Basic Info Tab -->
            <div class="survey-tab-content active" data-tab-content="basic">
                <div class="survey-field-group">
                    <label for="survey-type"><?php _e('Type', 'loyalty-program'); ?> *</label>
                    <select id="survey-type" required>
                        <option value="survey"><?php _e('Survey (Ankieta)', 'loyalty-program'); ?></option>
                        <option value="quiz"><?php _e('Quiz', 'loyalty-program'); ?></option>
                    </select>
                    <p class="description">
                        <?php _e('Quiz allows scoring and correct answers, Survey is for gathering opinions.', 'loyalty-program'); ?>
                    </p>
                </div>

                <div class="survey-field-group">
                    <label for="survey-name"><?php _e('Name', 'loyalty-program'); ?> *</label>
                    <input type="text" id="survey-name" required
                        placeholder="<?php esc_attr_e('Enter survey/quiz name', 'loyalty-program'); ?>">
                </div>

                <div class="survey-field-group">
                    <label for="survey-description"><?php _e('Description', 'loyalty-program'); ?></label>
                    <textarea id="survey-description"
                        placeholder="<?php esc_attr_e('Enter description (optional)', 'loyalty-program'); ?>"></textarea>
                </div>
            </div>

            <!-- Questions Tab -->
            <div class="survey-tab-content" data-tab-content="questions">
                <div class="questions-section">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="margin: 0;"><?php _e('Questions', 'loyalty-program'); ?></h3>
                        <button type="button" class="button button-secondary add-question-btn" id="add-question-btn">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e('Add Question', 'loyalty-program'); ?>
                        </button>
                    </div>

                    <div class="questions-list" id="questions-list">
                        <p class="description" style="text-align: center; padding: 40px; color: #646970;">
                            <?php _e('No questions yet. Click "Add Question" to start building your survey/quiz.', 'loyalty-program'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Settings Tab -->
            <div class="survey-tab-content" data-tab-content="settings">
                <h3><?php _e('Survey/Quiz Settings', 'loyalty-program'); ?></h3>

                <div class="survey-settings-grid">
                    <div class="setting-field">
                        <input type="checkbox" id="setting-show-start-button" value="yes">
                        <label
                            for="setting-show-start-button"><?php _e('Show Start Button', 'loyalty-program'); ?></label>
                    </div>

                    <div class="setting-field">
                        <input type="checkbox" id="setting-random-order" value="yes">
                        <label
                            for="setting-random-order"><?php _e('Random Question Order', 'loyalty-program'); ?></label>
                    </div>
                </div>

                <div class="survey-field-group" id="start-button-text-field" style="display: none;">
                    <label for="setting-start-button-text"><?php _e('Start Button Text', 'loyalty-program'); ?></label>
                    <input type="text" id="setting-start-button-text" value="<?php esc_attr_e('Start', 'loyalty-program'); ?>" placeholder="<?php esc_attr_e('Start', 'loyalty-program'); ?>">
                    <p class="description"><?php _e('Enter the text to display on the start button. Default: "Start"', 'loyalty-program'); ?></p>
                </div>

                <div class="survey-settings-grid">
                    <div class="setting-field">
                        <input type="checkbox" id="setting-time-limit" value="yes">
                        <label for="setting-time-limit"><?php _e('Enable Time Limit', 'loyalty-program'); ?></label>
                    </div>

                    <div class="setting-field">
                        <input type="checkbox" id="setting-pagination" value="yes">
                        <label
                            for="setting-pagination"><?php _e('Enable Pagination (1 question per page)', 'loyalty-program'); ?></label>
                    </div>
                </div>

                <div class="survey-field-group" id="time-limit-field" style="display: none;">
                    <label for="setting-time-minutes"><?php _e('Time Limit (minutes)', 'loyalty-program'); ?></label>
                    <input type="number" id="setting-time-minutes" min="1" value="10">
                </div>

                <div class="survey-field-group">
                    <label
                        for="setting-completion-points"><?php _e('Points for Completion', 'loyalty-program'); ?></label>
                    <input type="number" id="setting-completion-points" min="0" value="0">
                    <p class="description">
                        <?php _e('Points awarded for completing the survey/quiz (regardless of score).', 'loyalty-program'); ?>
                    </p>
                </div>

                <div id="quiz-only-settings" style="display: none;">
                    <h4 style="margin-top: 30px; border-top: 1px solid #dcdcde; padding-top: 20px;">
                        <?php _e('Quiz-Only Settings', 'loyalty-program'); ?></h4>

                    <div class="survey-field-group">
                        <label
                            for="setting-min-score-percentage"><?php _e('Minimum Score Percentage', 'loyalty-program'); ?></label>
                        <input type="number" id="setting-min-score-percentage" min="0" max="100" value="0" step="1">
                        <p class="description">
                            <?php _e('Minimum percentage of correct answers required to earn points (0-100%). Set to 0 to award points regardless of score.', 'loyalty-program'); ?>
                        </p>
                    </div>

                    <div class="survey-settings-grid">
                        <div class="setting-field">
                            <input type="checkbox" id="setting-show-result" value="yes">
                            <label
                                for="setting-show-result"><?php _e('Show Result to User', 'loyalty-program'); ?></label>
                        </div>
                    </div>
                </div>

                <div class="survey-field-group">
                    <label
                        for="setting-redirect-url"><?php _e('Redirect URL After Completion', 'loyalty-program'); ?></label>
                    <input type="url" id="setting-redirect-url" placeholder="https://example.com/thank-you">
                    <p class="description">
                        <?php _e('Leave empty to show thank you message instead.', 'loyalty-program'); ?></p>
                </div>

                <div class="survey-field-group">
                    <label for="setting-thank-title"><?php _e('Thank You Message Title', 'loyalty-program'); ?></label>
                    <input type="text" id="setting-thank-title"
                        placeholder="<?php esc_attr_e('Thank you!', 'loyalty-program'); ?>">
                </div>

                <div class="survey-field-group">
                    <label for="setting-thank-message"><?php _e('Thank You Message Text', 'loyalty-program'); ?></label>
                    <textarea id="setting-thank-message"
                        placeholder="<?php esc_attr_e('Thank you for completing this survey!', 'loyalty-program'); ?>"></textarea>
                </div>

                <div class="survey-field-group">
                    <label
                        for="setting-submit-button-text"><?php _e('Submit Button Text', 'loyalty-program'); ?></label>
                    <input type="text" id="setting-submit-button-text"
                        placeholder="<?php esc_attr_e('Odbierz nagrodę', 'loyalty-program'); ?>">
                    <p class="description">
                        <?php _e('Text for the submit button (used in last question when pagination is enabled).', 'loyalty-program'); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="survey-editor-footer">
            <button type="button" class="button"
                id="cancel-survey-btn"><?php _e('Cancel', 'loyalty-program'); ?></button>
            <button type="button" class="button button-primary"
                id="save-survey-btn"><?php _e('Save Survey/Quiz', 'loyalty-program'); ?></button>
        </div>
    </div>
</div>

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
        },
        warning: function(message, title) {
            return this.alert(message, title || 'Ostrzeżenie', 'warning');
        }
    };
    let currentSurveyIndex = null;
    let currentSurveyData = null;
    let questionCounter = 0;

    // Get hint text for answer type
    function getAnswerTypeHint(type) {
        const hints = {
            'radio': '<?php _e('Users will select ONE answer from multiple options', 'loyalty-program'); ?>',
            'checkbox': '<?php _e('Users can select MULTIPLE answers', 'loyalty-program'); ?>',
            'text': '<?php _e('Users will enter a SHORT text answer', 'loyalty-program'); ?>',
            'number': '<?php _e('Users will enter a NUMERIC value', 'loyalty-program'); ?>',
            'textarea': '<?php _e('Users will enter a LONG text answer', 'loyalty-program'); ?>',
            'rating': '<?php _e('Users will rate from 1 to 5 stars', 'loyalty-program'); ?>'
        };
        return hints[type] || '';
    }

    // Initialize sortable for surveys table
    if ($.fn.sortable) {
        $('.surveys-sortable').sortable({
            handle: '.drag-handle',
            helper: function(e, tr) {
                var $originals = tr.children();
                var $helper = tr.clone();
                $helper.children().each(function(index) {
                    $(this).width($originals.eq(index).width());
                });
                return $helper;
            },
            update: function() {
                updateSurveyNumbers();
            }
        });
    }

    // Update survey row numbers
    function updateSurveyNumbers() {
        $('#surveys-tbody .survey-row').each(function(index) {
            $(this).find('.survey-number').text(index + 1);
        });
    }

    updateSurveyNumbers();

    // View Results Button - redirect to results page
    $(document).on('click', '.view-results-btn', function() {
        const surveyId = $(this).data('survey-id');
        window.location.href = 'admin.php?page=loyalty-program-survey-results&survey_id=' + surveyId;
    });

    // Tab switching
    $(document).on('click', '.survey-tab', function() {
        const tab = $(this).data('tab');
        $('.survey-tab').removeClass('active');
        $(this).addClass('active');
        $('.survey-tab-content').removeClass('active');
        $(`.survey-tab-content[data-tab-content="${tab}"]`).addClass('active');
    });

    // Show/hide quiz-only settings based on type
    $('#survey-type').on('change', function() {
        if ($(this).val() === 'quiz') {
            $('#quiz-only-settings').slideDown();
        } else {
            $('#quiz-only-settings').slideUp();
        }
    });

    // Show/hide time limit field
    $('#setting-time-limit').on('change', function() {
        if ($(this).is(':checked')) {
            $('#time-limit-field').slideDown();
        } else {
            $('#time-limit-field').slideUp();
        }
    });

    // Show/hide start button text field
    $('#setting-show-start-button').on('change', function() {
        if ($(this).is(':checked')) {
            $('#start-button-text-field').slideDown();
        } else {
            $('#start-button-text-field').slideUp();
        }
    });

    // Add new survey
    $('#add-survey-btn').on('click', function() {
        currentSurveyIndex = null;
        currentSurveyData = null;
        questionCounter = 0;
        openSurveyEditor();
    });

    // Edit survey
    $(document).on('click', '.edit-survey-btn', function() {
        const index = $(this).data('index');
        const surveyData = JSON.parse($('.survey-row').eq(index).find('.survey-data').val());
        currentSurveyIndex = index;
        currentSurveyData = surveyData;
        loadSurveyData(surveyData);
        openSurveyEditor('edit');
    });

    // Copy survey shortcode
    $(document).on('click', '.copy-survey-shortcode-btn', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const shortcode = $btn.data('shortcode');

        // Create temp input
        const $temp = $('<input>');
        $('body').append($temp);
        $temp.val(shortcode).select();
        document.execCommand('copy');
        $temp.remove();

        // Visual feedback
        const originalIcon = $btn.find('.dashicons').attr('class');
        $btn.css('background', '#00a32a').css('border-color', '#00a32a');
        $btn.find('.dashicons').removeClass().addClass('dashicons dashicons-yes');

        setTimeout(function() {
            $btn.css('background', '').css('border-color', '');
            $btn.find('.dashicons').removeClass().addClass(originalIcon);
        }, 1500);
    });

    // Delete survey
    $(document).on('click', '.delete-survey-btn', function() {
        var $btn = $(this);
        SwalConfig.confirm('<?php esc_attr_e('Are you sure you want to delete this survey/quiz?', 'loyalty-program'); ?>').then(function(result) {
            if (!result.isConfirmed) {
                return;
            }

            const $row = $btn.closest('.survey-row');
            $row.fadeOut(300, function() {
                $row.remove();
                updateSurveyNumbers();

                if ($('#surveys-tbody .survey-row').length === 0) {
                    $('#surveys-tbody').html(
                        '<tr class="no-surveys-row">' +
                        '<td colspan="6" style="text-align: center; padding: 40px;">' +
                        '<span class="dashicons dashicons-clipboard" style="font-size: 48px; opacity: 0.3;"></span>' +
                        '<p><?php _e('No surveys or quizzes yet. Click "Add New" to create your first one.', 'loyalty-program'); ?></p>' +
                        '</td>' +
                        '</tr>'
                    );
                }
            });
        });
    });

    // Copy shortcode
    $(document).on('click', '.copy-shortcode-btn', function() {
        const shortcode = $(this).data('shortcode');
        navigator.clipboard.writeText(shortcode).then(() => {
            const $btn = $(this);
            const originalHtml = $btn.html();
            $btn.html('<span class="dashicons dashicons-yes"></span>');
            setTimeout(() => {
                $btn.html(originalHtml);
            }, 2000);
        });
    });

    // Open survey editor
    function openSurveyEditor(mode = 'add') {
        if (mode === 'add') {
            $('#survey-editor-title').text('<?php esc_attr_e('Add Survey/Quiz', 'loyalty-program'); ?>');
            clearSurveyEditor();
        } else {
            $('#survey-editor-title').text('<?php esc_attr_e('Edit Survey/Quiz', 'loyalty-program'); ?>');
        }
        $('.survey-editor-overlay').fadeIn(200);
        $('.survey-tab[data-tab="basic"]').click();
    }

    // Close survey editor
    function closeSurveyEditor() {
        $('.survey-editor-overlay').fadeOut(200);
        clearSurveyEditor();
    }

    $('#close-survey-editor, #cancel-survey-btn').on('click', closeSurveyEditor);

    // Clear survey editor
    function clearSurveyEditor() {
        $('#survey-type').val('survey');
        $('#survey-name').val('');
        $('#survey-description').val('');
        $('#questions-list').html(
            '<p class="description" style="text-align: center; padding: 40px; color: #646970;"><?php _e('No questions yet. Click "Add Question" to start building your survey/quiz.', 'loyalty-program'); ?></p>'
        );
        $('#setting-show-start-button').prop('checked', false);
        $('#setting-start-button-text').val('<?php esc_attr_e('Start', 'loyalty-program'); ?>');
        $('#setting-random-order').prop('checked', false);
        $('#setting-time-limit').prop('checked', false);
        $('#setting-time-minutes').val(10);
        $('#setting-completion-points').val(0);
        $('#setting-min-score-percentage').val(0);
        $('#setting-show-result').prop('checked', false);
        $('#setting-redirect-url').val('');
        $('#setting-thank-title').val('');
        $('#setting-thank-message').val('');
        $('#time-limit-field').hide();
        $('#start-button-text-field').hide();
        $('#quiz-only-settings').hide();
        questionCounter = 0;
    }

    // Load survey data into editor
    function loadSurveyData(data) {
        $('#survey-type').val(data.type || 'survey');
        $('#survey-name').val(data.name || '');
        $('#survey-description').val(data.description || '');

        // Reset question counter
        questionCounter = 0;

        // Load questions
        $('#questions-list').empty();
        if (data.questions && data.questions.length > 0) {
            data.questions.forEach(function(question, index) {
                addQuestion(question);
            });
        } else {
            $('#questions-list').html(
                '<p class="description" style="text-align: center; padding: 40px; color: #646970;"><?php _e('No questions yet.', 'loyalty-program'); ?></p>'
            );
        }

        // Load settings
        $('#setting-show-start-button').prop('checked', data.settings?.showStartButton === true);
        $('#setting-start-button-text').val(data.settings?.startButtonText || '<?php esc_attr_e('Start', 'loyalty-program'); ?>');
        $('#setting-random-order').prop('checked', data.settings?.randomOrder === true);
        $('#setting-time-limit').prop('checked', data.settings?.timeLimit === true);
        $('#setting-time-minutes').val(data.settings?.timeMinutes || 10);
        $('#setting-pagination').prop('checked', data.settings?.pagination === true);
        
        // Show/hide start button text field based on checkbox
        if (data.settings?.showStartButton === true) {
            $('#start-button-text-field').show();
        } else {
            $('#start-button-text-field').hide();
        }
        $('#setting-completion-points').val(data.settings?.completionPoints || 0);
        $('#setting-min-score-percentage').val(data.settings?.minScorePercentage || 0);
        $('#setting-show-result').prop('checked', data.settings?.showResult === true);
        $('#setting-redirect-url').val(data.settings?.redirectUrl || '');
        $('#setting-thank-title').val(data.settings?.thankTitle || '');
        $('#setting-thank-message').val(data.settings?.thankMessage || '');
        $('#setting-submit-button-text').val(data.settings?.submitButtonText || '');

        if (data.settings?.timeLimit) {
            $('#time-limit-field').show();
        }

        // Show/hide quiz-only settings
        if (data.type === 'quiz') {
            $('#quiz-only-settings').show();
        } else {
            $('#quiz-only-settings').hide();
        }
    }

    // Add question
    $('#add-question-btn').on('click', function() {
        addQuestion();
    });

    function addQuestion(questionData = null) {
        questionCounter++;
        // Create unique question ID: timestamp + random number + counter
        const uniqueId = questionData?.id || 'q_' + Date.now() + '_' + questionCounter + '_' + Math.floor(Math
            .random() * 100000);
        const qIndex = questionCounter;
        const isQuiz = $('#survey-type').val() === 'quiz';

        // ONLY remove placeholder text, not the entire list!
        if ($('#questions-list p.description').length) {
            $('#questions-list p.description').remove();
        }

        const questionHtml = `
            <div class="question-item" data-question-id="${uniqueId}" data-question-index="${qIndex}">
                <input type="hidden" class="question-unique-id" value="${uniqueId}">
                <div class="question-header">
                    <div style="display: flex; align-items: center; flex: 1;">
                        <span class="dashicons dashicons-move question-drag-handle"></span>
                        <span class="question-title-bar">
                            ${questionData?.text || '<?php esc_attr_e('New Question', 'loyalty-program'); ?>'}
                        </span>
                    </div>
                    <div class="question-actions">
                        <button type="button" class="button button-small edit-question-btn">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="button button-small delete-question-btn">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
                <div class="question-body" style="display: none;">
                    <div class="survey-field-group">
                        <label><?php _e('Question Text', 'loyalty-program'); ?> *</label>
                        <input type="text" class="question-text" value="${questionData?.text || ''}" required>
                    </div>
                    
                    <div class="survey-field-group">
                        <label><?php _e('Question Description (optional)', 'loyalty-program'); ?></label>
                        <textarea class="question-description">${questionData?.description || ''}</textarea>
                    </div>

                    <div class="survey-field-group">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" class="question-required" ${questionData?.required ? 'checked' : ''}>
                            <?php _e('Required Question', 'loyalty-program'); ?>
                        </label>
                        <p class="description"><?php _e('User must answer this question to submit the survey/quiz.', 'loyalty-program'); ?></p>
                    </div>

                    <div class="survey-field-group">
                        <label><?php _e('Answer Type', 'loyalty-program'); ?></label>
                        <select class="answer-type">
                            <option value="radio" ${questionData?.answerType === 'radio' ? 'selected' : ''}><?php _e('Radio (Single Choice)', 'loyalty-program'); ?></option>
                            <option value="checkbox" ${questionData?.answerType === 'checkbox' ? 'selected' : ''}><?php _e('Checkbox (Multiple Choice)', 'loyalty-program'); ?></option>
                            <option value="text" ${questionData?.answerType === 'text' ? 'selected' : ''}><?php _e('Text Input', 'loyalty-program'); ?></option>
                            <option value="number" ${questionData?.answerType === 'number' ? 'selected' : ''}><?php _e('Number Input', 'loyalty-program'); ?></option>
                            <option value="textarea" ${questionData?.answerType === 'textarea' ? 'selected' : ''}><?php _e('Text Area', 'loyalty-program'); ?></option>
                            <option value="rating" ${questionData?.answerType === 'rating' ? 'selected' : ''}><?php _e('Star Rating (1-5)', 'loyalty-program'); ?></option>
                        </select>
                        <p class="description answer-type-hint" style="margin-top: 8px; font-style: italic;">
                            ${getAnswerTypeHint(questionData?.answerType || 'radio')}
                        </p>
                    </div>

                    <div class="answers-section" style="${['radio', 'checkbox'].includes(questionData?.answerType || 'radio') ? '' : 'display:none;'}">
                        <label><?php _e('Answers', 'loyalty-program'); ?></label>
                        <div class="answers-list" data-question-id="${uniqueId}">
                            <!-- Answers will be added here -->
                        </div>
                        <button type="button" class="button button-small add-answer-btn" data-question-id="${uniqueId}">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e('Add Answer', 'loyalty-program'); ?>
                        </button>
                    </div>

                    <div class="answer-preview-section" style="display:none; margin-top: 15px; padding: 15px; background: #fff; border: 1px solid #dcdcde; border-radius: 4px;">
                        <label style="font-weight: 600; margin-bottom: 10px; display: block; color: #646970;">
                            <?php _e('Preview:', 'loyalty-program'); ?>
                        </label>
                        <div class="answer-preview-content"></div>
                    </div>

                    <div class="question-footer" style="margin-top: 15px; display: flex; justify-content: flex-end;">
                        <button type="button" class="button save-question-btn"><?php _e('Save Question', 'loyalty-program'); ?></button>
                    </div>
                </div>
            </div>
        `;

        // Check if question with this ID already exists (shouldn't happen)
        if ($(`[data-question-id="${uniqueId}"]`).length > 0) {
            SwalConfig.error('Error: Question ID conflict. Please try again.');
            return;
        }

        $('#questions-list').append(questionHtml);

        // Load answers if editing
        if (questionData && questionData.answers && questionData.answers.length > 0) {
            questionData.answers.forEach(function(answer) {
                addAnswer(uniqueId, answer);
            });
        } else if (!questionData || ['radio', 'checkbox'].includes(questionData?.answerType || 'radio')) {
            // Add 2 default answers only for new questions with radio/checkbox type
            const currentType = questionData?.answerType || 'radio';
            if (['radio', 'checkbox'].includes(currentType)) {
                addAnswer(uniqueId);
                addAnswer(uniqueId);
            }
        }

        // Show question editor immediately for new questions
        if (!questionData) {
            const $newQuestion = $(`[data-question-id="${uniqueId}"]`);
            $newQuestion.find('.question-body').slideDown();

            // Show appropriate section for default type (radio)
            $newQuestion.find('.answers-section').show();
            $newQuestion.find('.answer-preview-section').hide();
        }

        // Trigger visibility check for answers section
        if (questionData) {
            const $newQuestion = $(`[data-question-id="${uniqueId}"]`);
            const answerType = questionData.answerType || 'radio';
            const $answersSection = $newQuestion.find('.answers-section');
            const $previewSection = $newQuestion.find('.answer-preview-section');
            const $previewContent = $newQuestion.find('.answer-preview-content');

            if (['radio', 'checkbox'].includes(answerType)) {
                $answersSection.show();
                $previewSection.hide();
            } else {
                $answersSection.hide();
                $previewSection.show();

                // Show preview
                let previewHtml = '';
                switch (answerType) {
                    case 'text':
                        previewHtml =
                            '<input type="text" placeholder="<?php esc_attr_e('User will type here...', 'loyalty-program'); ?>" disabled style="width: 100%; padding: 8px; border: 1px solid #dcdcde; border-radius: 4px;">';
                        break;
                    case 'number':
                        previewHtml =
                            '<input type="number" placeholder="<?php esc_attr_e('User will enter a number...', 'loyalty-program'); ?>" disabled style="width: 200px; padding: 8px; border: 1px solid #dcdcde; border-radius: 4px;">';
                        break;
                    case 'textarea':
                        previewHtml =
                            '<textarea placeholder="<?php esc_attr_e('User will type a longer answer here...', 'loyalty-program'); ?>" disabled style="width: 100%; padding: 8px; min-height: 100px; border: 1px solid #dcdcde; border-radius: 4px;"></textarea>';
                        break;
                    case 'rating':
                        previewHtml = '<div class="rating-preview" style="display: flex; gap: 5px;">';
                        for (let i = 1; i <= 5; i++) {
                            previewHtml +=
                                '<span class="dashicons dashicons-star-empty" style="font-size: 32px; color: #f59e0b;"></span>';
                        }
                        previewHtml +=
                            '</div><p style="margin: 5px 0 0; color: #646970; font-size: 12px;"><?php _e('Users will click stars to rate 1-5', 'loyalty-program'); ?></p>';
                        break;
                }
                $previewContent.html(previewHtml);
            }
        }

        initQuestionSortable();
    }

    // Toggle answer type visibility
    $(document).on('change', '.answer-type', function() {
        const $questionItem = $(this).closest('.question-item');
        const answerType = $(this).val();
        const $answersSection = $questionItem.find('.answers-section');
        const $answersList = $questionItem.find('.answers-list');
        const $hint = $questionItem.find('.answer-type-hint');
        const $previewSection = $questionItem.find('.answer-preview-section');
        const $previewContent = $questionItem.find('.answer-preview-content');

        // Update hint text
        $hint.text(getAnswerTypeHint(answerType));

        if (['radio', 'checkbox'].includes(answerType)) {
            $answersSection.slideDown();
            $previewSection.hide();

            // Add default answers if none exist
            if ($answersList.find('.answer-item').length === 0) {
                const questionId = $questionItem.data('question-id');
                addAnswer(questionId);
                addAnswer(questionId);
            }
        } else {
            $answersSection.slideUp();
            $previewSection.slideDown();

            // Show preview based on type
            let previewHtml = '';
            switch (answerType) {
                case 'text':
                    previewHtml =
                        '<input type="text" placeholder="<?php esc_attr_e('User will type here...', 'loyalty-program'); ?>" disabled style="width: 100%; padding: 8px; border: 1px solid #dcdcde; border-radius: 4px;">';
                    break;
                case 'number':
                    previewHtml =
                        '<input type="number" placeholder="<?php esc_attr_e('User will enter a number...', 'loyalty-program'); ?>" disabled style="width: 200px; padding: 8px; border: 1px solid #dcdcde; border-radius: 4px;">';
                    break;
                case 'textarea':
                    previewHtml =
                        '<textarea placeholder="<?php esc_attr_e('User will type a longer answer here...', 'loyalty-program'); ?>" disabled style="width: 100%; padding: 8px; min-height: 100px; border: 1px solid #dcdcde; border-radius: 4px;"></textarea>';
                    break;
                case 'rating':
                    previewHtml = '<div class="rating-preview" style="display: flex; gap: 5px;">';
                    for (let i = 1; i <= 5; i++) {
                        previewHtml +=
                            '<span class="dashicons dashicons-star-empty" style="font-size: 32px; color: #f59e0b;"></span>';
                    }
                    previewHtml +=
                        '</div><p style="margin: 5px 0 0; color: #646970; font-size: 12px;"><?php _e('Users will click stars to rate 1-5', 'loyalty-program'); ?></p>';
                    break;
            }
            $previewContent.html(previewHtml);
        }
    });

    // Edit question (toggle)
    $(document).on('click', '.edit-question-btn', function() {
        const $questionBody = $(this).closest('.question-item').find('.question-body');
        $questionBody.slideToggle();
    });

    // Save question
    $(document).on('click', '.save-question-btn', function() {
        const $question = $(this).closest('.question-item');
        const questionText = $question.find('.question-text').val();

        if (!questionText.trim()) {
            SwalConfig.warning('<?php esc_attr_e('Please enter question text', 'loyalty-program'); ?>');
            return;
        }

        $question.find('.question-title-bar').text(questionText);
        $question.find('.question-body').slideUp();
    });

    // Update question numbers (if needed)
    function updateQuestionNumbers() {
        $('.question-item').each(function(index) {
            $(this).attr('data-question-index', index + 1);
        });
    }

    // Delete question
    $(document).on('click', '.delete-question-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('DELETE QUESTION BUTTON CLICKED');
        
        var $btn = $(this);
        var $questionItem = $btn.closest('.question-item');
        
        console.log('Button element:', $btn);
        console.log('Question item found:', $questionItem.length);
        console.log('Question item:', $questionItem);
        
        if (!$questionItem.length) {
            console.error('Question item not found');
            return;
        }
        
        console.log('Showing confirmation dialog...');
        
        SwalConfig.confirm('<?php esc_attr_e('Delete this question?', 'loyalty-program'); ?>').then(function(result) {
            console.log('Confirmation result:', result);
            
            if (!result.isConfirmed) {
                console.log('User cancelled deletion');
                return;
            }
            
            console.log('User confirmed deletion, removing question...');
            
            $questionItem.fadeOut(300, function() {
                console.log('FadeOut complete, removing element...');
                $questionItem.remove();
                
                console.log('Question removed. Remaining questions:', $('.question-item').length);
                
                // Update question numbers after removal
                updateQuestionNumbers();
                
                if ($('.question-item').length === 0) {
                    console.log('No questions left, showing placeholder');
                    $('#questions-list').html(
                        '<p class="description" style="text-align: center; padding: 40px; color: #646970;"><?php _e('No questions yet. Click "Add Question" to start building your survey/quiz.', 'loyalty-program'); ?></p>'
                    );
                }
            });
        }).catch(function(error) {
            console.error('Error in delete question:', error);
        });
    });

    // Add answer
    $(document).on('click', '.add-answer-btn', function() {
        const questionId = $(this).data('question-id');
        addAnswer(questionId);
    });

    function addAnswer(questionId, answerData = null) {
        const isQuiz = $('#survey-type').val() === 'quiz';
        const answerIndex = $(`.answers-list[data-question-id="${questionId}"] .answer-item`).length + 1;

        const answerHtml = `
            <div class="answer-item">
                <span class="dashicons dashicons-move answer-drag"></span>
                <div class="answer-content">
                    <input type="text" class="answer-text" value="${answerData?.text || ''}" placeholder="<?php esc_attr_e('Answer text', 'loyalty-program'); ?>" required>
                    ${isQuiz ? `
                        <label class="answer-correct-check">
                            <input type="checkbox" class="answer-correct" ${answerData?.correct ? 'checked' : ''}>
                            <span><?php _e('Correct', 'loyalty-program'); ?></span>
                        </label>
                        <input type="number" class="answer-points" value="${answerData?.points || 0}" min="0" placeholder="<?php esc_attr_e('Points', 'loyalty-program'); ?>">
                    ` : ''}
                </div>
                <button type="button" class="button button-small delete-answer-btn">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        `;

        $(`.answers-list[data-question-id="${questionId}"]`).append(answerHtml);
        initAnswersSortable(questionId);
    }

    // Delete answer
    $(document).on('click', '.delete-answer-btn', function() {
        $(this).closest('.answer-item').fadeOut(200, function() {
            $(this).remove();
        });
    });

    // Initialize sortable for questions
    function initQuestionSortable() {
        if ($.fn.sortable && !$('#questions-list').hasClass('ui-sortable')) {
            $('#questions-list').sortable({
                handle: '.question-drag-handle',
                items: '.question-item',
                opacity: 0.8
            });
        }
    }

    // Initialize sortable for answers
    function initAnswersSortable(questionId) {
        const selector = `.answers-list[data-question-id="${questionId}"]`;
        if ($.fn.sortable && !$(selector).hasClass('ui-sortable')) {
            $(selector).sortable({
                handle: '.answer-drag',
                items: '.answer-item',
                opacity: 0.8
            });
        }
    }

    // Save survey/quiz
    $('#save-survey-btn').on('click', function() {
        // Validate basic info
        const surveyName = $('#survey-name').val().trim();
        if (!surveyName) {
            SwalConfig.warning('<?php esc_attr_e('Please enter survey name', 'loyalty-program'); ?>');
            $('.survey-tab[data-tab="basic"]').click();
            return;
        }

        // Collect survey data
        const surveyData = {
            id: currentSurveyData?.id || 'survey_' + Date.now() + '_' + Math.floor(Math.random() *
                10000),
            type: $('#survey-type').val(),
            name: surveyName,
            description: $('#survey-description').val(),
            enabled: currentSurveyData?.enabled || 'yes',
            questions: [],
            settings: {
                showStartButton: $('#setting-show-start-button').is(':checked'),
                startButtonText: $('#setting-start-button-text').val().trim() || '<?php esc_attr_e('Start', 'loyalty-program'); ?>',
                randomOrder: $('#setting-random-order').is(':checked'),
                timeLimit: $('#setting-time-limit').is(':checked'),
                timeMinutes: parseInt($('#setting-time-minutes').val()) || 10,
                pagination: $('#setting-pagination').is(':checked'),
                completionPoints: parseInt($('#setting-completion-points').val()) || 0,
                minScorePercentage: parseInt($('#setting-min-score-percentage').val()) || 0,
                showResult: $('#setting-show-result').is(':checked'),
                redirectUrl: $('#setting-redirect-url').val(),
                thankTitle: $('#setting-thank-title').val(),
                thankMessage: $('#setting-thank-message').val(),
                submitButtonText: $('#setting-submit-button-text').val()
            }
        };

        // Collect questions
        $('.question-item').each(function(index) {
            const $question = $(this);
            const uniqueQuestionId = $question.find('.question-unique-id').val();
            const questionText = $question.find('.question-text').val();

            if (!questionText.trim()) {
                return;
            }

            const questionData = {
                id: uniqueQuestionId, // Save unique ID
                text: questionText,
                description: $question.find('.question-description').val(),
                required: $question.find('.question-required').is(':checked'),
                answerType: $question.find('.answer-type').val(),
                answers: []
            };

            // Collect answers (only for radio/checkbox types)
            if (['radio', 'checkbox'].includes(questionData.answerType)) {
                $question.find('.answer-item').each(function() {
                    const $answer = $(this);
                    const answerText = $answer.find('.answer-text').val();

                    if (!answerText.trim()) return;

                    const answerData = {
                        text: answerText
                    };

                    if (surveyData.type === 'quiz') {
                        answerData.correct = $answer.find('.answer-correct').is(
                            ':checked');
                        answerData.points = parseInt($answer.find('.answer-points')
                            .val()) || 0;
                    }

                    questionData.answers.push(answerData);
                });
            }
            // For other types (text, number, textarea, rating), answers array stays empty

            surveyData.questions.push(questionData);
        });

        // Save to table
        if (currentSurveyIndex !== null) {
            // Update existing
            const $row = $('.survey-row').eq(currentSurveyIndex);
            $row.find('.survey-name-display').text(surveyData.name);
            $row.find('.survey-type-badge')
                .removeClass('quiz survey')
                .addClass(surveyData.type)
                .text(surveyData.type === 'quiz' ? '<?php _e('Quiz', 'loyalty-program'); ?>' :
                    '<?php _e('Survey', 'loyalty-program'); ?>');
            $row.find('.survey-data').val(JSON.stringify(surveyData));
            $row.find('.survey-shortcode').text(`[loyalty_survey id="${surveyData.id}"]`);
            $row.find('.copy-shortcode-btn').data('shortcode',
                `[loyalty_survey id="${surveyData.id}"]`);
        } else {
            // Add new
            const newIndex = $('.survey-row').length;
            const rowHtml = createSurveyRow(newIndex, surveyData);

            $('.no-surveys-row').remove();
            $('#surveys-tbody').append(rowHtml);
            updateSurveyNumbers();
        }

        // Close modal
        closeSurveyEditor();

        // Show save notice to remind user to save changes
        $('.save-changes-notice').fadeIn(300);
    });

    // Create survey row HTML
    function createSurveyRow(index, survey) {
        return `
            <tr class="survey-row" data-index="${index}" data-survey-id="${survey.id}">
                <td class="survey-number" style="text-align: center;">${index + 1}</td>
                <td class="drag-handle">
                    <span class="dashicons dashicons-move"></span>
                </td>
                <td>
                    <strong class="survey-name-display">${survey.name}</strong>
                    <input type="hidden" class="survey-data" value="${JSON.stringify(survey).replace(/"/g, '&quot;').replace(/'/g, '&#39;')}">
                </td>
                <td>
                    <span class="survey-type-badge ${survey.type}">
                        ${survey.type === 'quiz' ? '<?php _e('Quiz', 'loyalty-program'); ?>' : '<?php _e('Survey', 'loyalty-program'); ?>'}
                    </span>
                </td>
                <td style="text-align: center;">
                    <strong class="completion-count">0</strong>
                </td>
                <td>
                    <code class="survey-shortcode">[loyalty_survey id="${survey.id}"]</code>
                </td>
                <td style="text-align: center;">
                    <div class="survey-actions">
                        <button type="button" class="button button-small survey-icon-btn edit-survey-btn" 
                            data-index="${index}"
                            title="<?php esc_attr_e('Edit', 'loyalty-program'); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="button button-small survey-icon-btn view-results-btn" 
                            data-survey-id="${survey.id}"
                            data-survey-name="${survey.name}"
                            data-survey-type="${survey.type}"
                            title="<?php esc_attr_e('View Results', 'loyalty-program'); ?>">
                            <span class="dashicons dashicons-chart-bar"></span>
                        </button>
                        <button type="button" class="button button-small survey-icon-btn copy-survey-shortcode-btn" 
                            data-shortcode='[loyalty_survey id="${survey.id}"]'
                            title="<?php esc_attr_e('Copy Shortcode', 'loyalty-program'); ?>">
                            <span class="dashicons dashicons-admin-page"></span>
                        </button>
                        <label class="toggle-switch" title="<?php esc_attr_e('Enable/Disable', 'loyalty-program'); ?>">
                            <input type="checkbox" class="survey-enabled-toggle" value="yes" ${survey.enabled === 'yes' ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                        <button type="button" class="button survey-icon-btn delete-survey-btn" 
                            title="<?php esc_attr_e('Delete', 'loyalty-program'); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    // Save surveys on form submit
    $('#surveys-form').on('submit', function(e) {
        // Prepare data from rows
        const surveysData = [];
        $('.survey-row').each(function() {
            let surveyDataJson = $(this).find('.survey-data').val();

            if (!surveyDataJson || surveyDataJson.trim() === '') {
                return;
            }

            // Decode HTML entities if needed
            surveyDataJson = surveyDataJson.replace(/&quot;/g, '"').replace(/&#39;/g, "'");

            try {
                const surveyData = JSON.parse(surveyDataJson);
                surveyData.enabled = $(this).find('.survey-enabled-toggle').is(':checked') ?
                    'yes' : 'no';
                surveysData.push(surveyData);
            } catch (e) {
                // Error parsing survey data - skip this row
            }
        });

        $('#surveys-data-input').val(JSON.stringify(surveysData));

        // Hide save notice
        $('.save-changes-notice').fadeOut(300);
    });
});
</script>