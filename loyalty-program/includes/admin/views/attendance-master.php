<?php

/**
 * Admin Attendance Master View
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current points setting
$points_attendance_master = get_option('loyalty_program_points_attendance_master', 30);

// Get saved actions
$attendance_actions = get_option('loyalty_program_attendance_actions', array());

// Check debug mode
$debug_enabled = get_option('loyalty_program_debug_enabled', 'no');

// Prepare debug info about active actions
$current_time = current_time('mysql');
$debug_actions = array();

foreach ($attendance_actions as $action) {
    $date_from = str_replace('T', ' ', $action['date_from']);
    $date_to = str_replace('T', ' ', $action['date_to']);

    if (strlen($date_from) === 16) $date_from .= ':00';
    if (strlen($date_to) === 16) $date_to .= ':00';

    $is_active = ($current_time >= $date_from && $current_time <= $date_to);
    $is_before = ($current_time < $date_from);
    $is_after = ($current_time > $date_to);

    $status_text = '';
    if ($is_active) {
        $status_text = '✅ AKTYWNA';
    } elseif ($is_before) {
        $status_text = '⏳ Jeszcze nie aktywna';
    } elseif ($is_after) {
        $status_text = '⏰ Czas minął';
    }

    $debug_actions[] = array(
        'name' => $action['name'],
        'id' => $action['id'],
        'date_from' => $date_from,
        'date_to' => $date_to,
        'status' => $status_text,
        'is_active' => $is_active,
        'enabled' => $action['enabled'],
    );
}

settings_errors('loyalty_program_attendance');
?>

<div class="wrap loyalty-program-attendance-master">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <p class="description">
        <?php _e('Mistrz obecności - twórz interaktywne elementy (przyciski/teksty) na stronie, które użytkownicy mogą kliknąć aby zdobyć punkty. Każdy element jest aktywny w określonym przedziale czasowym.', 'loyalty-program'); ?>
    </p>

    <!-- DEBUG INFO -->
    <div class="notice notice-info" style="padding: 15px; margin-top: 20px;">
        <h3 style="margin-top: 0;">🐛 Status akcji "Mistrz obecności"</h3>
        <p style="margin-bottom: 15px;">
            <strong>⏰ Aktualna data i czas serwera:</strong>
            <code
                style="font-size: 14px; background: #fff; padding: 5px 10px; border-radius: 3px;"><?php echo esc_html($current_time); ?></code>
        </p>

        <?php if (!empty($debug_actions)) : ?>
        <table class="wp-list-table widefat striped" style="max-width: 100%;">
            <thead>
                <tr>
                    <th style="width: 30px;">Status</th>
                    <th>Nazwa akcji</th>
                    <th style="width: 400px;">Shortcode</th>
                    <th style="width: 180px;">Aktywna od</th>
                    <th style="width: 180px;">Aktywna do</th>
                    <th style="width: 80px;">Włączona</th>
                    <th style="width: 150px;">Stan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($debug_actions as $debug_action) : ?>
                <tr style="<?php echo $debug_action['is_active'] ? 'background: #d4edda;' : ''; ?>">
                    <td style="text-align: center; font-size: 18px;">
                        <?php
                                if ($debug_action['is_active']) {
                                    echo '✅';
                                } elseif (strpos($debug_action['status'], 'Jeszcze') !== false) {
                                    echo '⏳';
                                } else {
                                    echo '⏰';
                                }
                                ?>
                    </td>
                    <td><strong><?php echo esc_html($debug_action['name']); ?></strong></td>
                    <td><code
                            style="font-size: 11px;">[loyalty_attendance_action id="<?php echo esc_html($debug_action['id']); ?>"]</code>
                    </td>
                    <td><code><?php echo esc_html($debug_action['date_from']); ?></code></td>
                    <td><code><?php echo esc_html($debug_action['date_to']); ?></code></td>
                    <td style="text-align: center;">
                        <?php echo $debug_action['enabled'] === 'yes' ? '✓' : '✗'; ?>
                    </td>
                    <td>
                        <strong style="<?php echo $debug_action['is_active'] ? 'color: #00a32a;' : 'color: #999;'; ?>">
                            <?php echo esc_html($debug_action['status']); ?>
                        </strong>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p style="margin-top: 15px; font-size: 13px; color: #666;">
            💡 <strong>Legenda:</strong>
            ✅ = Aktywna teraz |
            ⏳ = Jeszcze nie rozpoczęta |
            ⏰ = Zakończona
        </p>
        <?php else : ?>
        <p><em>Brak utworzonych akcji do sprawdzenia.</em></p>
        <?php endif; ?>
    </div>
    <!-- END DEBUG INFO -->

    <!-- Current Settings Info -->
    <div class="loyalty-info-banner">
        <span class="dashicons dashicons-star-filled"></span>
        <div class="loyalty-info-banner-content">
            <strong><?php _e('Aktualne ustawienie punktów:', 'loyalty-program'); ?></strong>
            <?php echo sprintf(__('Domyślnie %d punktów za akcję.', 'loyalty-program'), $points_attendance_master); ?>
            <a href="<?php echo admin_url('admin.php?page=loyalty-program-settings'); ?>" class="button button-small">
                <?php _e('Zmień punkty', 'loyalty-program'); ?>
            </a>
        </div>
    </div>

    <!-- Actions Management Form -->
    <div class="loyalty-attendance-section">
        <h2><?php _e('Zarządzanie akcjami obecności', 'loyalty-program'); ?></h2>

        <form method="post" action="" id="attendance-actions-form">
            <?php wp_nonce_field('loyalty_program_attendance', 'loyalty_program_attendance_nonce'); ?>

            <div class="actions-table-wrapper">
                <table class="wp-list-table widefat fixed attendance-actions-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><?php _e('#', 'loyalty-program'); ?></th>
                            <th style="width: 300px;"><?php _e('Nazwa i treść', 'loyalty-program'); ?> <span
                                    class="required">*</span></th>
                            <th style="width: 150px;"><?php _e('Typ', 'loyalty-program'); ?></th>
                            <th style="width: 80px;"><?php _e('Punkty', 'loyalty-program'); ?></th>
                            <th style="width: 180px;"><?php _e('Aktywny od', 'loyalty-program'); ?></th>
                            <th style="width: 180px;"><?php _e('Aktywny do', 'loyalty-program'); ?></th>
                            <th style="width: 105px;"><?php _e('Widoczny po', 'loyalty-program'); ?></th>
                            <th style="width: 150px;"><?php _e('Akcje', 'loyalty-program'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="attendance-actions-tbody">
                        <?php if (!empty($attendance_actions)) : ?>
                        <?php foreach ($attendance_actions as $index => $action) :
                                $enabled = isset($action['enabled']) ? $action['enabled'] : 'yes';
                                $disabled_class = $enabled === 'no' ? 'disabled' : '';
                            ?>
                        <tr class="action-row <?php echo esc_attr($disabled_class); ?>"
                            data-index="<?php echo esc_attr($index); ?>">
                            <td class="row-number" style="text-align: center;"></td>
                            <td class="action-name-content-cell">
                                <input type="hidden" name="attendance_actions[<?php echo esc_attr($index); ?>][id]"
                                    value="<?php echo esc_attr($action['id']); ?>" class="action-id">
                                <div class="action-field-group">
                                    <label class="action-field-label"><?php _e('Nazwa:', 'loyalty-program'); ?></label>
                                    <input type="text" name="attendance_actions[<?php echo esc_attr($index); ?>][name]"
                                        class="action-name widefat" value="<?php echo esc_attr($action['name']); ?>"
                                        placeholder="<?php esc_attr_e('np. Dzienny bonus', 'loyalty-program'); ?>"
                                        required>
                                </div>
                                <div class="action-field-group">
                                    <label class="action-field-label"><?php _e('Treść:', 'loyalty-program'); ?></label>
                                    <textarea name="attendance_actions[<?php echo esc_attr($index); ?>][content]"
                                        class="action-content widefat" rows="2"
                                        placeholder="<?php esc_attr_e('Treść przycisku/tekstu...', 'loyalty-program'); ?>"
                                        required><?php echo esc_textarea($action['content']); ?></textarea>
                                </div>
                            </td>
                            <td>
                                <?php
                                        // Backwards compatibility: combine type and button_style
                                        $current_type = $action['type'];
                                        if ($current_type === 'button') {
                                            $button_style = isset($action['button_style']) ? $action['button_style'] : 'dark';
                                            $current_type = 'button-' . $button_style;
                                        }
                                        ?>
                                <select name="attendance_actions[<?php echo esc_attr($index); ?>][type]"
                                    class="action-type">
                                    <option value="button-dark" <?php selected($current_type, 'button-dark'); ?>>
                                        <?php _e('Przycisk ciemny', 'loyalty-program'); ?></option>
                                    <option value="button-light" <?php selected($current_type, 'button-light'); ?>>
                                        <?php _e('Przycisk jasny', 'loyalty-program'); ?></option>
                                    <option value="text" <?php selected($current_type, 'text'); ?>>
                                        <?php _e('Tekst', 'loyalty-program'); ?></option>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="attendance_actions[<?php echo esc_attr($index); ?>][points]"
                                    class="action-points small-text" value="<?php echo esc_attr($action['points']); ?>"
                                    min="1" step="1" required>
                            </td>
                            <td>
                                <input type="datetime-local"
                                    name="attendance_actions[<?php echo esc_attr($index); ?>][date_from]"
                                    class="action-date-from" value="<?php echo esc_attr($action['date_from']); ?>"
                                    required>
                            </td>
                            <td>
                                <input type="datetime-local"
                                    name="attendance_actions[<?php echo esc_attr($index); ?>][date_to]"
                                    class="action-date-to" value="<?php echo esc_attr($action['date_to']); ?>" required>
                            </td>
                            <td style="text-align: center;">
                                <label class="checkbox-label">
                                    <input type="checkbox"
                                        name="attendance_actions[<?php echo esc_attr($index); ?>][visible_after]"
                                        class="action-visible-after" value="yes"
                                        <?php checked($action['visible_after'], 'yes'); ?>>
                                    <span><?php _e('Tak', 'loyalty-program'); ?></span>
                                </label>
                            </td>
                            <td>
                                <button type="button" class="button button-small copy-shortcode-btn"
                                    data-shortcode="[loyalty_attendance_action id=&quot;<?php echo esc_attr($action['id']); ?>&quot;]"
                                    title="<?php esc_attr_e('Kopiuj shortcode', 'loyalty-program'); ?>">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                                <label class="toggle-switch">
                                    <input type="checkbox"
                                        name="attendance_actions[<?php echo esc_attr($index); ?>][enabled]"
                                        class="action-enabled-toggle" value="yes" <?php checked($enabled, 'yes'); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <button type="button" class="button button-small delete-action-btn"
                                    title="<?php esc_attr_e('Usuń', 'loyalty-program'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <p class="add-action-wrapper">
                    <button type="button" id="add-action-btn" class="button button-secondary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Dodaj nową akcję', 'loyalty-program'); ?>
                    </button>
                </p>
            </div>

            <p class="submit">
                <button type="submit" name="loyalty_program_attendance_actions_save" class="button button-primary">
                    <span class="dashicons dashicons-saved"></span>
                    <?php _e('Zapisz akcje', 'loyalty-program'); ?>
                </button>
            </p>
        </form>
    </div>

    <!-- Instructions -->
    <div class="loyalty-attendance-section">
        <h2><?php _e('Instrukcja użycia', 'loyalty-program'); ?></h2>

        <div class="instruction-box">
            <h3><?php _e('Jak to działa?', 'loyalty-program'); ?></h3>
            <ol style="line-height: 1.8; margin-left: 20px;">
                <li><?php _e('Utwórz akcję - nadaj nazwę, treść, wybierz typ i ustaw punkty', 'loyalty-program'); ?>
                </li>
                <li><?php _e('Ustaw przedział czasowy (od-do) kiedy element ma być aktywny', 'loyalty-program'); ?></li>
                <li><?php _e('Zdecyduj czy element ma być widoczny po zakończeniu czasu', 'loyalty-program'); ?></li>
                <li><?php _e('Skopiuj wygenerowany shortcode (przycisk kopiowania)', 'loyalty-program'); ?></li>
                <li><?php _e('Wklej shortcode na stronie gdzie ma się wyświetlić', 'loyalty-program'); ?></li>
                <li><?php _e('Użytkownicy klikają element i otrzymują punkty (tylko raz!)', 'loyalty-program'); ?></li>
            </ol>
        </div>

        <div class="instruction-box" style="background: #fff3cd; border-left-color: #f0b849;">
            <h3><?php _e('Ważne informacje:', 'loyalty-program'); ?></h3>
            <ul style="line-height: 1.8; margin-left: 20px;">
                <li><strong><?php _e('Typ "Przycisk":', 'loyalty-program'); ?></strong>
                    <?php _e('Wyświetla się jako klikalny przycisk z animacją', 'loyalty-program'); ?></li>
                <li><strong><?php _e('Typ "Tekst":', 'loyalty-program'); ?></strong>
                    <?php _e('Wyświetla się jako klikalny tekst/link', 'loyalty-program'); ?></li>
                <li><strong><?php _e('Aktywność:', 'loyalty-program'); ?></strong>
                    <?php _e('Element jest klikalny tylko w określonym czasie', 'loyalty-program'); ?></li>
                <li><strong><?php _e('Widoczność po czasie:', 'loyalty-program'); ?></strong>
                    <?php _e('Jeśli zaznaczone, element pozostaje widoczny (ale nieaktywny) po zakończeniu czasu', 'loyalty-program'); ?>
                </li>
                <li><strong><?php _e('Jednorazowość:', 'loyalty-program'); ?></strong>
                    <?php _e('Każdy użytkownik może kliknąć akcję tylko raz', 'loyalty-program'); ?></li>
            </ul>
        </div>

        <div class="instruction-box" style="background: #f0f6fc; border-left-color: #2271b1;">
            <h3><?php _e('Przykład shortcode:', 'loyalty-program'); ?></h3>
            <pre
                style="background: #23282d; color: #f0f0f1; padding: 15px; border-radius: 4px; font-family: monospace;">
[loyalty_attendance_action id="action_123456789"]
            </pre>
            <p style="margin-top: 10px;">
                <?php _e('Każda akcja ma unikalny shortcode który możesz skopiować przyciskiem w tabeli.', 'loyalty-program'); ?>
            </p>
        </div>
    </div>
</div>

<style>
.loyalty-program-attendance-master {
    max-width: 1400px;
}

.loyalty-info-banner {
    background: #f0f6fc;
    border-left: 4px solid #2271b1;
    padding: 15px 20px;
    margin: 20px 0;
    display: flex;
    align-items: center;
    gap: 15px;
    border-radius: 4px;
}

.loyalty-info-banner .dashicons {
    color: #2271b1;
    font-size: 24px;
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

.loyalty-info-banner .button-small {
    margin-left: 10px;
}

.loyalty-attendance-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 25px;
    margin: 20px 0;
    border-radius: 4px;
}

.loyalty-attendance-section h2 {
    margin-top: 0;
    border-bottom: 1px solid #dcdcde;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.instruction-box {
    background: #f6f7f7;
    padding: 20px;
    margin: 15px 0;
    border-radius: 4px;
    border-left: 4px solid #2271b1;
}

.instruction-box h3 {
    margin-top: 0;
    color: #1d2327;
}

.actions-table-wrapper {
    overflow-x: auto;
    margin: 20px 0;
}

.attendance-actions-table {
    margin: 0;
}

.attendance-actions-table th {
    background: #f0f0f1;
    font-weight: 600;
}

.attendance-actions-table td {
    vertical-align: top;
    padding: 12px 8px;
}

.action-name-content-cell {
    padding: 8px !important;
}

.action-field-group {
    margin-bottom: 8px;
}

.action-field-group:last-child {
    margin-bottom: 0;
}

.action-field-label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #646970;
    margin-bottom: 3px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.attendance-actions-table input[type="text"],
.attendance-actions-table input[type="number"],
.attendance-actions-table input[type="datetime-local"],
.attendance-actions-table select,
.attendance-actions-table textarea {
    width: 100%;
    box-sizing: border-box;
}

.attendance-actions-table textarea {
    resize: vertical;
    min-height: 45px;
}

.attendance-actions-table input.small-text {
    width: 70px;
}

.action-row.disabled {
    opacity: 0.5;
    background: #f6f7f7;
}

.row-number {
    font-weight: 600;
    color: #646970;
}

.add-action-wrapper {
    margin: 15px 0;
}

#add-action-btn .dashicons {
    margin-top: 3px;
}

.copy-shortcode-btn {
    margin-right: 5px;
}

.copy-shortcode-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    margin-top: 2px;
}

.delete-action-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    margin-top: 2px;
    color: #d63638;
}

.required {
    color: #d63638;
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    margin: 0 5px;
    vertical-align: middle;
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
    background-color: #ccc;
    transition: .4s;
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
    transition: .4s;
    border-radius: 50%;
}

input:checked+.toggle-slider {
    background-color: #2271b1;
}

input:checked+.toggle-slider:before {
    transform: translateX(20px);
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 5px;
    justify-content: center;
}

.checkbox-label input[type="checkbox"] {
    margin: 0;
}

/* Copied message */
.shortcode-copied-message {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #00a32a;
    color: white;
    padding: 15px 30px;
    border-radius: 4px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    z-index: 999999;
    font-weight: 600;
    display: none;
}
</style>

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
    
    // Update row numbers
    function updateRowNumbers() {
        $('#attendance-actions-tbody tr.action-row').each(function(index) {
            $(this).find('.row-number').text(index + 1);

            // Update input names to maintain proper indexing
            var newIndex = index;
            $(this).attr('data-index', newIndex);

            $(this).find('input, select, textarea').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    var newName = name.replace(/\[\d+\]/, '[' + newIndex + ']');
                    $(this).attr('name', newName);
                }
            });
        });
    }

    // Initial row numbering
    updateRowNumbers();

    // Add new action
    $('#add-action-btn').on('click', function() {
        var rowCount = $('#attendance-actions-tbody tr.action-row').length;
        var newIndex = rowCount;
        var actionId = 'action_' + Date.now() + '_' + Math.floor(Math.random() * 10000);

        // Get current date for defaults
        var now = new Date();
        var dateFrom = now.toISOString().slice(0, 16);
        var tomorrow = new Date(now.getTime() + 24 * 60 * 60 * 1000);
        var dateTo = tomorrow.toISOString().slice(0, 16);

        var newRow = `
                <tr class="action-row" data-index="${newIndex}">
                    <td class="row-number" style="text-align: center;"></td>
                    <td class="action-name-content-cell">
                        <input type="hidden" name="attendance_actions[${newIndex}][id]" value="${actionId}" class="action-id">
                        <div class="action-field-group">
                            <label class="action-field-label"><?php _e('Nazwa:', 'loyalty-program'); ?></label>
                            <input type="text" name="attendance_actions[${newIndex}][name]" class="action-name widefat" 
                                value="" placeholder="<?php esc_attr_e('np. Dzienny bonus', 'loyalty-program'); ?>" required>
                        </div>
                        <div class="action-field-group">
                            <label class="action-field-label"><?php _e('Treść:', 'loyalty-program'); ?></label>
                            <textarea name="attendance_actions[${newIndex}][content]" class="action-content widefat" 
                                rows="2" placeholder="<?php esc_attr_e('Treść przycisku/tekstu...', 'loyalty-program'); ?>" required></textarea>
                        </div>
                    </td>
                    <td>
                        <select name="attendance_actions[${newIndex}][type]" class="action-type">
                            <option value="button-dark" selected><?php _e('Przycisk ciemny', 'loyalty-program'); ?></option>
                            <option value="button-light"><?php _e('Przycisk jasny', 'loyalty-program'); ?></option>
                            <option value="text"><?php _e('Tekst', 'loyalty-program'); ?></option>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="attendance_actions[${newIndex}][points]" 
                            class="action-points small-text" value="<?php echo esc_attr($points_attendance_master); ?>" 
                            min="1" step="1" required>
                    </td>
                    <td>
                        <input type="datetime-local" name="attendance_actions[${newIndex}][date_from]" 
                            class="action-date-from" value="${dateFrom}" required>
                    </td>
                    <td>
                        <input type="datetime-local" name="attendance_actions[${newIndex}][date_to]" 
                            class="action-date-to" value="${dateTo}" required>
                    </td>
                    <td style="text-align: center;">
                        <label class="checkbox-label">
                            <input type="checkbox" name="attendance_actions[${newIndex}][visible_after]" 
                                class="action-visible-after" value="yes">
                            <span><?php _e('Tak', 'loyalty-program'); ?></span>
                        </label>
                    </td>
                    <td>
                        <button type="button" class="button button-small copy-shortcode-btn" 
                            data-shortcode="[loyalty_attendance_action id=&quot;${actionId}&quot;]" 
                            title="<?php esc_attr_e('Kopiuj shortcode', 'loyalty-program'); ?>">
                            <span class="dashicons dashicons-admin-page"></span>
                        </button>
                        <label class="toggle-switch">
                            <input type="checkbox" name="attendance_actions[${newIndex}][enabled]" 
                                class="action-enabled-toggle" value="yes" checked>
                            <span class="toggle-slider"></span>
                        </label>
                        <button type="button" class="button button-small delete-action-btn" 
                            title="<?php esc_attr_e('Usuń', 'loyalty-program'); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </td>
                </tr>
            `;

        $('#attendance-actions-tbody').append(newRow);
        updateRowNumbers();

        // Scroll to new row
        $('html, body').animate({
            scrollTop: $('#attendance-actions-tbody tr:last').offset().top - 100
        }, 500);
    });

    // Delete action
    $(document).on('click', '.delete-action-btn', function() {
        var $btn = $(this);
        SwalConfig.confirm('<?php esc_attr_e('Czy na pewno chcesz usunąć tę akcję?', 'loyalty-program'); ?>').then(function(result) {
            if (result.isConfirmed) {
                $btn.closest('tr').remove();
                updateRowNumbers();
            }
        });
    });

    // Toggle enabled/disabled
    $(document).on('change', '.action-enabled-toggle', function() {
        var $row = $(this).closest('tr');
        if ($(this).is(':checked')) {
            $row.removeClass('disabled');
        } else {
            $row.addClass('disabled');
        }
    });

    // Copy shortcode to clipboard
    $(document).on('click', '.copy-shortcode-btn', function() {
        var shortcode = $(this).data('shortcode');

        // Create temporary textarea
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(shortcode).select();
        document.execCommand('copy');
        $temp.remove();

        // Show copied message
        var $message = $('<div class="shortcode-copied-message">✓ Shortcode skopiowany!</div>');
        $('body').append($message);
        $message.fadeIn(200).delay(1500).fadeOut(200, function() {
            $(this).remove();
        });

        // Highlight button temporarily
        var $btn = $(this);
        $btn.css('background', '#00a32a').css('color', '#fff');
        setTimeout(function() {
            $btn.css('background', '').css('color', '');
        }, 1000);
    });

    // Form validation
    $('#attendance-actions-form').on('submit', function(e) {
        var hasActions = $('#attendance-actions-tbody tr.action-row').length > 0;

        if (!hasActions) {
            e.preventDefault();
            SwalConfig.warning('<?php esc_attr_e('Dodaj przynajmniej jedną akcję przed zapisaniem.', 'loyalty-program'); ?>');
            return false;
        }

        // Validate dates
        var isValid = true;
        $('#attendance-actions-tbody tr.action-row').each(function() {
            var dateFrom = $(this).find('.action-date-from').val();
            var dateTo = $(this).find('.action-date-to').val();

            if (dateFrom && dateTo && new Date(dateFrom) >= new Date(dateTo)) {
                SwalConfig.warning('<?php esc_attr_e('Data zakończenia musi być późniejsza niż data rozpoczęcia!', 'loyalty-program'); ?>');
                isValid = false;
                return false;
            }
        });

        if (!isValid) {
            e.preventDefault();
            return false;
        }
    });
});
</script>