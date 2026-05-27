<?php

/**
 * Admin Dashboard View
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load points class
if (!class_exists('Loyalty_Program_Points')) {
    require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-points.php';
}

global $wpdb;

// CLEANUP: Remove old unified cache (one-time, backward compatibility)
if (get_transient('loyalty_dashboard_stats') !== false) {
    delete_transient('loyalty_dashboard_stats');
    if (!class_exists('Loyalty_Program_Logger')) {
        require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
    }
    Loyalty_Program_Logger::info('Old unified cache removed (migrated to separate caches)');
}

// OPTIMIZED: Separate cache for each stat (refresh only when page loads AND cache > 1 hour)
$cache_duration = HOUR_IN_SECONDS; // 1 hour
$current_time = time();

// Cache key 1: Total members
$cache_key_members = 'loyalty_dashboard_total_members';
$cached_members = get_transient($cache_key_members);
$members_cache_time = get_option($cache_key_members . '_time', 0);

if ($cached_members === false || ($current_time - $members_cache_time) > $cache_duration) {
    // Cache expired or missing - recalculate
    $total_members = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
            Loyalty_Program_Points::MEMBER_STATUS_META,
            'yes'
        )
    );
    set_transient($cache_key_members, $total_members, 0); // No expiration, manual control
    update_option($cache_key_members . '_time', $current_time, false);

    // Log cache rebuild
    if (!class_exists('Loyalty_Program_Logger')) {
        require_once LOYALTY_PROGRAM_PLUGIN_DIR . 'includes/class-loyalty-program-logger.php';
    }
    Loyalty_Program_Logger::debug('Cache rebuilt: total_members', array(
        'value' => $total_members,
        'reason' => $cached_members === false ? 'missing' : 'expired (>1h)',
        'age_seconds' => $cached_members === false ? 0 : ($current_time - $members_cache_time)
    ));
} else {
    $total_members = $cached_members;
}

// Cache key 2: Total points awarded
$cache_key_points = 'loyalty_dashboard_total_points';
$cached_points = get_transient($cache_key_points);
$points_cache_time = get_option($cache_key_points . '_time', 0);

if ($cached_points === false || ($current_time - $points_cache_time) > $cache_duration) {
    // Cache expired or missing - recalculate
    $total_points_awarded = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->usermeta} WHERE meta_key = %s",
            Loyalty_Program_Points::TOTAL_EARNED_META
        )
    );
    set_transient($cache_key_points, $total_points_awarded, 0); // No expiration, manual control
    update_option($cache_key_points . '_time', $current_time, false);

    Loyalty_Program_Logger::debug('Cache rebuilt: total_points', array(
        'value' => $total_points_awarded,
        'reason' => $cached_points === false ? 'missing' : 'expired (>1h)',
        'age_seconds' => $cached_points === false ? 0 : ($current_time - $points_cache_time)
    ));
} else {
    $total_points_awarded = $cached_points;
}

// Cache key 3: Top 20 users
$cache_key_top20 = 'loyalty_dashboard_top_users';
$cached_top20 = get_transient($cache_key_top20);
$top20_cache_time = get_option($cache_key_top20 . '_time', 0);

if ($cached_top20 === false || ($current_time - $top20_cache_time) > $cache_duration) {
    // Cache expired or missing - recalculate
    $top_users_meta = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT um1.user_id, 
                    CAST(um1.meta_value AS UNSIGNED) as total_earned,
                    CAST(COALESCE(um3.meta_value, 0) AS UNSIGNED) as current_points
             FROM {$wpdb->usermeta} um1
             INNER JOIN {$wpdb->usermeta} um2 ON um1.user_id = um2.user_id
             LEFT JOIN {$wpdb->usermeta} um3 ON um1.user_id = um3.user_id AND um3.meta_key = %s
             WHERE um1.meta_key = %s 
             AND CAST(um1.meta_value AS UNSIGNED) > 0
             AND um2.meta_key = %s
             AND um2.meta_value = %s
             ORDER BY CAST(um1.meta_value AS UNSIGNED) DESC 
             LIMIT 20",
            Loyalty_Program_Points::CURRENT_POINTS_META,
            Loyalty_Program_Points::TOTAL_EARNED_META,
            Loyalty_Program_Points::MEMBER_STATUS_META,
            'yes'
        )
    );
    set_transient($cache_key_top20, $top_users_meta, 0); // No expiration, manual control
    update_option($cache_key_top20 . '_time', $current_time, false);

    Loyalty_Program_Logger::debug('Cache rebuilt: top_users', array(
        'count' => count($top_users_meta),
        'top_user_id' => !empty($top_users_meta) ? $top_users_meta[0]->user_id : 'none',
        'top_total_earned' => !empty($top_users_meta) ? $top_users_meta[0]->total_earned : 0,
        'reason' => $cached_top20 === false ? 'missing' : 'expired (>1h)',
        'age_seconds' => $cached_top20 === false ? 0 : ($current_time - $top20_cache_time)
    ));
} else {
    $top_users_meta = $cached_top20;
}

// Log cache summary
Loyalty_Program_Logger::debug('Dashboard cache summary', array(
    'total_members' => array(
        'value' => $total_members,
        'cached' => $cached_members !== false,
        'age_seconds' => $cached_members !== false ? ($current_time - $members_cache_time) : 0
    ),
    'total_points' => array(
        'value' => $total_points_awarded,
        'cached' => $cached_points !== false,
        'age_seconds' => $cached_points !== false ? ($current_time - $points_cache_time) : 0
    ),
    'top_users' => array(
        'count' => count($top_users_meta),
        'cached' => $cached_top20 !== false,
        'age_seconds' => $cached_top20 !== false ? ($current_time - $top20_cache_time) : 0
    )
));

// Get user details for top users (sorted by total_earned)
$top_users = array();
foreach ($top_users_meta as $user_meta) {
    $user = get_userdata($user_meta->user_id);
    if ($user) {
        $membership_info = Loyalty_Program_Points::get_membership_info($user_meta->user_id);

        $top_users[] = array(
            'user' => $user,
            'current_points' => absint($user_meta->current_points),
            'total_earned' => absint($user_meta->total_earned),
            'join_date' => $membership_info['join_date_formatted'],
        );
    }
}
?>

<div class="wrap loyalty-program-dashboard">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h1 style="margin: 0;"><?php echo esc_html(get_admin_page_title()); ?></h1>

        <button type="button" id="refresh-dashboard-stats" class="button button-secondary">
            <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
            <?php _e('Refresh Stats', 'loyalty-program'); ?>
        </button>
    </div>

    <div class="loyalty-program-welcome-panel">
        <h2><?php _e('Welcome to Loyalty Program', 'loyalty-program'); ?></h2>
        <p><?php _e('Manage your loyalty program, track points, and configure integrations all in one place.', 'loyalty-program'); ?>
        </p>

        <?php
        // Show cache info if data is cached
        if ($cached_members !== false || $cached_points !== false || $cached_top20 !== false) {
            // Get the oldest cache time (first created)
            $cache_times = array_filter(array($members_cache_time, $points_cache_time, $top20_cache_time));
            $oldest_cache_time = !empty($cache_times) ? min($cache_times) : 0;

            if ($oldest_cache_time > 0) {
                $cache_age_seconds = $current_time - $oldest_cache_time;
                $cache_age_minutes = floor($cache_age_seconds / 60);
                $cache_age_hours = floor($cache_age_minutes / 60);

                if ($cache_age_hours > 0) {
                    $cache_age_text = sprintf(_n('%s hour ago', '%s hours ago', $cache_age_hours, 'loyalty-program'), number_format_i18n($cache_age_hours));
                } elseif ($cache_age_minutes > 0) {
                    $cache_age_text = sprintf(_n('%s minute ago', '%s minutes ago', $cache_age_minutes, 'loyalty-program'), number_format_i18n($cache_age_minutes));
                } else {
                    $cache_age_text = __('just now', 'loyalty-program');
                }
        ?>
                <div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 12px 15px; margin-top: 15px;">
                    <p style="margin: 0; color: #2c3338; font-size: 13px;">
                        <span class="dashicons dashicons-info" style="font-size: 16px; margin-right: 5px; color: #2271b1;"></span>
                        <strong><?php _e('Statistics are cached for performance.', 'loyalty-program'); ?></strong>
                        <?php
                        printf(
                            __('Last updated: %s. To fetch current data, click the "%s" button.', 'loyalty-program'),
                            '<strong>' . esc_html($cache_age_text) . '</strong>',
                            __('Refresh Stats', 'loyalty-program')
                        );
                        ?>
                    </p>
                </div>
        <?php
            }
        }
        ?>
    </div>

    <div class="loyalty-program-stats">
        <div class="loyalty-stat-box">
            <div class="loyalty-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="loyalty-stat-content">
                <h3><?php echo number_format_i18n($total_members ?: 0); ?></h3>
                <p><?php _e('Active Members', 'loyalty-program'); ?></p>
            </div>
        </div>

        <div class="loyalty-stat-box">
            <div class="loyalty-stat-icon">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <div class="loyalty-stat-content">
                <h3><?php echo number_format_i18n($total_points_awarded ?: 0); ?></h3>
                <p><?php _e('Total Points Awarded', 'loyalty-program'); ?></p>
            </div>
        </div>

    </div>

    <div class="loyalty-program-recent-activity">
        <h2><?php _e('Top 20 Users by Total Earned', 'loyalty-program'); ?></h2>

        <?php if (!empty($top_users)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php _e('Rank', 'loyalty-program'); ?></th>
                        <th><?php _e('User', 'loyalty-program'); ?></th>
                        <th><?php _e('Current Points', 'loyalty-program'); ?></th>
                        <th><?php _e('Total Earned', 'loyalty-program'); ?></th>
                        <th><?php _e('Join Date', 'loyalty-program'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rank = 1;
                    foreach ($top_users as $user_data) :
                        $user = $user_data['user'];
                    ?>
                        <tr>
                            <td style="text-align: center;">
                                <?php if ($rank <= 3) : ?>
                                    <span class="loyalty-rank rank-<?php echo $rank; ?>"><?php echo $rank; ?></span>
                                <?php else : ?>
                                    <span class="loyalty-rank"><?php echo $rank; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php echo get_avatar($user->ID, 32, '', '', array('class' => 'avatar-small')); ?>
                                    <div>
                                        <strong><?php echo esc_html($user->display_name); ?></strong>
                                        <br>
                                        <small style="color: #646970;"><?php echo esc_html($user->user_email); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="loyalty-points-badge current">
                                    <?php echo number_format_i18n($user_data['current_points']); ?>
                                    <?php _e('pts', 'loyalty-program'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="loyalty-points-badge total">
                                    <?php echo number_format_i18n($user_data['total_earned']); ?>
                                    <?php _e('pts', 'loyalty-program'); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $user_data['join_date'] ? esc_html($user_data['join_date']) : '<em>' . __('N/A', 'loyalty-program') . '</em>'; ?>
                            </td>
                        </tr>
                    <?php
                        $rank++;
                    endforeach;
                    ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php _e('No members yet. Users will appear here once they join the loyalty program and earn points.', 'loyalty-program'); ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="loyalty-program-quick-links">
        <h2><?php _e('Quick Links', 'loyalty-program'); ?></h2>
        <div class="loyalty-quick-links-grid">
            <a href="<?php echo admin_url('admin.php?page=loyalty-program-users'); ?>" class="loyalty-quick-link">
                <span class="dashicons dashicons-groups"></span>
                <span><?php _e('Users', 'loyalty-program'); ?></span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=loyalty-program-rewards'); ?>" class="loyalty-quick-link">
                <span class="dashicons dashicons-awards"></span>
                <span><?php _e('Rewards', 'loyalty-program'); ?></span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=loyalty-program-surveys'); ?>" class="loyalty-quick-link">
                <span class="dashicons dashicons-feedback"></span>
                <span><?php _e('Surveys & Quizzes', 'loyalty-program'); ?></span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=loyalty-program-shortcodes'); ?>" class="loyalty-quick-link">
                <span class="dashicons dashicons-shortcode"></span>
                <span><?php _e('Shortcodes', 'loyalty-program'); ?></span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=loyalty-program-live-expert'); ?>" class="loyalty-quick-link">
                <span class="dashicons dashicons-video-alt3"></span>
                <span><?php _e('Live Expert Session', 'loyalty-program'); ?></span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=loyalty-program-integrations'); ?>"
                class="loyalty-quick-link">
                <span class="dashicons dashicons-admin-plugins"></span>
                <span><?php _e('Integrations', 'loyalty-program'); ?></span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=loyalty-program-settings'); ?>" class="loyalty-quick-link">
                <span class="dashicons dashicons-admin-settings"></span>
                <span><?php _e('Settings', 'loyalty-program'); ?></span>
            </a>
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
        
        $('#refresh-dashboard-stats').on('click', function() {
            var $button = $(this);
            var originalText = $button.html();

            // Disable button and show loading
            $button.prop('disabled', true).html(
                '<span class="dashicons dashicons-update spin" style="margin-top: 3px;"></span> ' +
                '<?php esc_html_e('Refreshing...', 'loyalty-program'); ?>'
            );

            $.post(ajaxurl, {
                action: 'loyalty_program_clear_dashboard_cache',
                nonce: '<?php echo wp_create_nonce('loyalty_clear_dashboard_cache'); ?>'
            }, function(response) {
                if (response.success) {
                    // Show success message
                    $button.html(
                        '<span class="dashicons dashicons-yes" style="margin-top: 3px;"></span> ' +
                        '<?php esc_html_e('Refreshed!', 'loyalty-program'); ?>'
                    );

                    // Reload page after 500ms to show fresh data
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                } else {
                    // Show error
                    SwalConfig.error(response.data.message ||
                        '<?php esc_html_e('Error refreshing stats.', 'loyalty-program'); ?>');
                    $button.prop('disabled', false).html(originalText);
                }
            }).fail(function() {
                SwalConfig.error('<?php esc_html_e('Network error. Please try again.', 'loyalty-program'); ?>');
                $button.prop('disabled', false).html(originalText);
            });
        });
    });
</script>

<style>
    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .dashicons.spin {
        animation: spin 1s linear infinite;
    }
</style>