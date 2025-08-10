<?php
/**
 * Template for the Overview tab in the report card.
 *
 * @param int $user_id The ID of the user.
 * @param array $context The viewing context.
 * @param object $this The instance of the main class.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$summary_data = $this->get_user_summary_data($user_id);
?>
<div class="psych-section">
    <h3><i class="fas fa-chart-pie"></i> <?php esc_html_e('Performance Summary', 'psych-system'); ?></h3>
    <div class="psych-summary-grid">
        <div class="psych-summary-item">
            <div class="value"><?php echo number_format_i18n($summary_data['total_points']); ?></div>
            <div class="label"><?php esc_html_e('Total Points', 'psych-system'); ?></div>
        </div>
        <div class="psych-summary-item">
            <div class="value"><?php echo number_format_i18n($summary_data['badges_count']); ?></div>
            <div class="label"><?php esc_html_e('Badges Earned', 'psych-system'); ?></div>
        </div>
        <div class="psych-summary-item">
            <div class="value"><?php echo number_format_i18n($summary_data['completed_stations']); ?></div>
            <div class="label"><?php esc_html_e('Stations Completed', 'psych-system'); ?></div>
        </div>
        <div class="psych-summary-item">
            <div class="value"><?php echo $summary_data['progress_percentage']; ?>%</div>
            <div class="label"><?php esc_html_e('Overall Progress', 'psych-system'); ?></div>
        </div>
    </div>
</div>

<div class="psych-section">
    <h3><i class="fas fa-star"></i> <?php esc_html_e('Recent Achievements', 'psych-system'); ?></h3>
    <?php if (!empty($summary_data['recent_achievements'])) : ?>
        <div class="psych-achievements-list">
            <?php foreach ($summary_data['recent_achievements'] as $achievement) : ?>
                <div class="psych-achievement-item">
                    <i class="<?php echo esc_attr($achievement['icon']); ?>" style="color: <?php echo esc_attr($achievement['color']); ?>"></i>
                    <span><?php echo esc_html($achievement['title']); ?></span>
                    <small><?php echo esc_html($achievement['date']); ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p><?php esc_html_e('No recent achievements.', 'psych-system'); ?></p>
    <?php endif; ?>
</div>
