<?php
/**
 * Template for the [psych_user_performance_header] shortcode.
 *
 * @param array $user_data Summary data for the user.
 * @param array $level_info Level information for the user.
 * @param array $atts Shortcode attributes.
 */
if (!defined('ABSPATH')) exit;
$user = wp_get_current_user();
?>
<div class="psych-performance-header">
    <div class="user-info">
        <?php if (isset($atts['show_avatar']) && $atts['show_avatar'] == 'yes'): ?>
            <?php echo get_avatar($user->ID, 60); ?>
        <?php endif; ?>
        <div>
            <h4><?php echo esc_html($user->display_name); ?></h4>
            <p><?php echo esc_html($level_info['name']); ?></p>
        </div>
    </div>
    <div class="stats">
        <?php if (isset($atts['show_rank']) && $atts['show_rank'] == 'yes'): ?>
            <span class="stat-item"><?php esc_html_e('Rank:', 'psych-system'); ?> #...</span>
        <?php endif; ?>
        <?php if (isset($atts['show_badges']) && $atts['show_badges'] == 'yes'): ?>
            <span class="stat-item"><?php echo number_format_i18n($user_data['badges_count']); ?> <?php esc_html_e('Badges', 'psych-system'); ?></span>
        <?php endif; ?>
        <?php if (isset($atts['show_progress']) && $atts['show_progress'] == 'yes'): ?>
            <span class="stat-item"><?php echo esc_html($user_data['progress_percentage']); ?>% <?php esc_html_e('Progress', 'psych-system'); ?></span>
        <?php endif; ?>
    </div>
    <?php if (isset($atts['button_text']) && !empty($atts['button_text'])): ?>
        <a href="<?php echo esc_url($atts['button_url']); ?>" class="button button-primary performance-button"><?php echo esc_html($atts['button_text']); ?></a>
    <?php endif; ?>
</div>
