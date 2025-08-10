<?php
/**
 * Template for the [psych_user_dashboard] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param string $content Shortcode content.
 * @param array $user_data User's gamification data.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>
<div class="psych-user-dashboard-container" id="psych-user-dashboard">
    <div class="dashboard-header">
        <h2><?php echo esc_html__('My Dashboard', 'psych-g-plugin'); ?></h2>
    </div>
    <div class="dashboard-grid">
        <div class="dashboard-widget points-widget">
            <h3><?php echo esc_html__('My Points', 'psych-g-plugin'); ?></h3>
            <p class="points-total"><?php echo number_format($user_data['points']); ?></p>
        </div>
        <div class="dashboard-widget level-widget">
            <h3><?php echo esc_html__('My Level', 'psych-g-plugin'); ?></h3>
            <p class="level-name"><?php echo esc_html($user_data['level']['name']); ?></p>
            <div class="level-progress">
                <div class="level-progress-bar" style="width: <?php echo esc_attr($user_data['level']['progress']); ?>%;"></div>
            </div>
            <p class="level-progress-text"><?php echo sprintf(esc_html__('%s / %s Points to Next Level', 'psych-g-plugin'), number_format($user_data['points']), number_format($user_data['level']['next_level_points'])); ?></p>
        </div>
        <div class="dashboard-widget badges-widget">
            <h3><?php echo esc_html__('Recent Badges', 'psych-g-plugin'); ?></h3>
            <div class="badges-list">
                <?php if (!empty($user_data['badges'])) : ?>
                    <?php foreach (array_slice($user_data['badges'], 0, 5) as $badge) : ?>
                        <img src="<?php echo esc_url($badge['icon_url']); ?>" alt="<?php echo esc_attr($badge['name']); ?>" title="<?php echo esc_attr($badge['name']); ?>">
                    <?php endforeach; ?>
                <?php else : ?>
                    <p><?php echo esc_html__('No badges earned yet.', 'psych-g-plugin'); ?></p>
                <?php endif; ?>
            </div>
            <a href="<?php echo esc_url($atts['badges_page_url']); ?>" class="view-all-badges"><?php echo esc_html__('View All', 'psych-g-plugin'); ?></a>
        </div>
        <div class="dashboard-widget leaderboard-widget">
            <h3><?php echo esc_html__('My Rank', 'psych-g-plugin'); ?></h3>
            <p class="leaderboard-rank">#<?php echo esc_html($user_data['rank']); ?></p>
            <a href="<?php echo esc_url($atts['leaderboard_page_url']); ?>" class="view-leaderboard"><?php echo esc_html__('View Leaderboard', 'psych-g-plugin'); ?></a>
        </div>
    </div>
</div>
