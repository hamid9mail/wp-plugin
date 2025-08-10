<?php
/**
 * Template for the [psych_gamified_header] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param array $user_data User's gamification data.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>
<div class="psych-gamified-header-container" id="psych-gamified-header-<?php echo esc_attr($atts['instance_id']); ?>">
    <div class="user-info">
        <img src="<?php echo esc_url($user_data['avatar_url']); ?>" alt="<?php echo esc_html__('User Avatar', 'psych-g-plugin'); ?>" class="user-avatar">
        <span class="user-name"><?php echo esc_html($user_data['display_name']); ?></span>
    </div>
    <div class="gamification-stats">
        <div class="stat-item points-stat">
            <i class="fas fa-star"></i>
            <span class="stat-value"><?php echo number_format($user_data['points']); ?></span>
            <span class="stat-label"><?php echo esc_html__('Points', 'psych-g-plugin'); ?></span>
        </div>
        <div class="stat-item level-stat">
            <i class="fas fa-trophy"></i>
            <span class="stat-value"><?php echo esc_html($user_data['level']['name']); ?></span>
            <span class="stat-label"><?php echo esc_html__('Level', 'psych-g-plugin'); ?></span>
        </div>
        <div class="stat-item badges-stat">
            <i class="fas fa-medal"></i>
            <span class="stat-value"><?php echo count($user_data['badges']); ?></span>
            <span class="stat-label"><?php echo esc_html__('Badges', 'psych-g-plugin'); ?></span>
        </div>
    </div>
    <div class="header-actions">
        <a href="<?php echo esc_url($atts['dashboard_url']); ?>" class="dashboard-link"><?php echo esc_html__('My Dashboard', 'psych-g-plugin'); ?></a>
    </div>
</div>
