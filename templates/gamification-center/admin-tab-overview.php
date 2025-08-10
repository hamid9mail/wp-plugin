<?php
/**
 * Template for the Overview tab in the Gamification Center admin.
 *
 * @param array $stats Array of statistics.
 * @param object $this The instance of the main class.
 */
if (!defined('ABSPATH')) exit;
?>
<div class="psych-dashboard-cards">
    <div class="psych-card">
        <div class="psych-card-icon"><i class="dashicons dashicons-groups"></i></div>
        <div class="psych-card-content">
            <h3><?php echo number_format_i18n($stats['total_users']); ?></h3>
            <p><?php esc_html_e('Total Users', 'psych-system'); ?></p>
        </div>
    </div>
    <div class="psych-card">
        <div class="psych-card-icon"><i class="dashicons dashicons-awards"></i></div>
        <div class="psych-card-content">
            <h3><?php echo number_format_i18n($stats['active_badges']); ?></h3>
            <p><?php esc_html_e('Active Badges', 'psych-system'); ?></p>
        </div>
    </div>
    <div class="psych-card">
        <div class="psych-card-icon"><i class="dashicons dashicons-star-filled"></i></div>
        <div class="psych-card-content">
            <h3><?php echo number_format_i18n($stats['total_points_awarded']); ?></h3>
            <p><?php esc_html_e('Total Points Awarded', 'psych-system'); ?></p>
        </div>
    </div>
</div>
<div class="psych-recent-activities">
    <h2><?php esc_html_e('Recent Activities', 'psych-system'); ?></h2>
    <?php $this->render_recent_activities_list(); ?>
</div>
