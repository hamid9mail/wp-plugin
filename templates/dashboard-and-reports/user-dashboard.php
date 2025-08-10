<?php
/**
 * Template for the [psych_user_dashboard] shortcode.
 *
 * @param array $user_data Summary data for the user.
 * @param array $level_info Level information for the user.
 */
if (!defined('ABSPATH')) exit;
?>
<div class="psych-user-dashboard">
    <div class="dashboard-grid">
        <div class="dashboard-widget">
            <h4><?php esc_html_e('Points', 'psych-system'); ?></h4>
            <p class="large-text"><?php echo number_format_i18n($user_data['total_points']); ?></p>
        </div>
        <div class="dashboard-widget">
            <h4><?php esc_html_e('Current Level', 'psych-system'); ?></h4>
            <p class="large-text"><?php echo esc_html($level_info['name']); ?></p>
        </div>
        <div class="dashboard-widget">
            <h4><?php esc_html_e('Badges', 'psych-system'); ?></h4>
            <p class="large-text"><?php echo number_format_i18n($user_data['badges_count']); ?></p>
        </div>
        <div class="dashboard-widget">
            <h4><?php esc_html_e('Progress', 'psych-system'); ?></h4>
            <p class="large-text"><?php echo esc_html($user_data['progress_percentage']); ?>%</p>
        </div>
    </div>
</div>
