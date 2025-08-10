<?php
/**
 * Template for the [psych_user_level_display] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param array $level_data The user's current level data.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>
<div class="psych-level-display-container <?php echo esc_attr($atts['custom_class']); ?>">
    <?php if (!empty($atts['title'])) : ?>
        <h4 class="level-title"><?php echo esc_html($atts['title']); ?></h4>
    <?php endif; ?>

    <div class="level-info">
        <?php if ($atts['show_icon'] === 'yes' && !empty($level_data['icon_url'])) : ?>
            <img src="<?php echo esc_url($level_data['icon_url']); ?>" alt="<?php echo esc_attr($level_data['name']); ?>" class="level-icon" />
        <?php endif; ?>
        <span class="level-name"><?php echo esc_html($level_data['name']); ?></span>
    </div>

    <?php if ($atts['show_progress_bar'] === 'yes') : ?>
        <div class="level-progress-bar-wrapper">
            <div class="level-progress-bar" style="width: <?php echo esc_attr($level_data['progress']); ?>%;"></div>
        </div>
    <?php endif; ?>

    <?php if ($atts['show_progress_text'] === 'yes') : ?>
        <p class="level-progress-text">
            <?php echo sprintf(
                esc_html__('%s / %s to next level', 'psych-g-plugin'),
                number_format($level_data['current_points']),
                number_format($level_data['next_level_points'])
            ); ?>
        </p>
    <?php endif; ?>
</div>
