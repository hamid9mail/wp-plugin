<?php
/**
 * Template for the [psych_gamified_header] shortcode.
 *
 * @param array $user_data Summary data for the user.
 * @param array $level_info Level information for the user.
 */
if (!defined('ABSPATH')) exit;
$user = wp_get_current_user();
?>
<div class="psych-gamified-header">
    <div class="user-info">
        <?php echo get_avatar($user->ID, 40); ?>
        <span><?php echo esc_html($user->display_name); ?></span>
    </div>
    <div class="stats">
        <span class="stat-item points"><i class="fas fa-star"></i> <?php echo number_format_i18n($user_data['total_points']); ?></span>
        <span class="stat-item level"><i class="fas fa-trophy"></i> <?php echo esc_html($level_info['name']); ?></span>
        <span class="stat-item badges"><i class="fas fa-medal"></i> <?php echo number_format_i18n($user_data['badges_count']); ?></span>
    </div>
</div>
