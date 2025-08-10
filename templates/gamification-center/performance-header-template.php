<?php
/**
 * Template for the [psych_user_performance_header] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param array $user_data User's gamification data.
 * @param array $roadmap_items Items for the roadmap layout.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$style_class = 'performance-header-' . esc_attr($atts['style']); // flat or gradient
$layout_class = 'layout-' . esc_attr($atts['layout']); // default or roadmap
$animation_class = $atts['animation'] === 'yes' ? 'has-animation' : '';

$inline_styles = '';
if ($atts['style'] === 'flat') {
    $inline_styles = sprintf(
        'background-color: %s; color: %s; opacity: %s;',
        esc_attr($atts['main_color']),
        esc_attr($atts['text_color']),
        esc_attr($atts['opacity'])
    );
} elseif ($atts['style'] === 'gradient') {
    $inline_styles = sprintf(
        'background: linear-gradient(135deg, %s, %s); opacity: %s;',
        esc_attr($atts['main_color']),
        esc_attr($atts['secondary_color']),
        esc_attr($atts['opacity'])
    );
}

?>
<div class="psych-performance-header-container <?php echo $style_class; ?> <?php echo $layout_class; ?> <?php echo $animation_class; ?>" style="<?php echo $inline_styles; ?>">

    <div class="performance-header-main">
        <?php if ($atts['show_avatar'] === 'yes') : ?>
        <div class="ph-avatar">
            <img src="<?php echo esc_url($user_data['avatar_url']); ?>" alt="<?php echo esc_attr($user_data['display_name']); ?>" />
        </div>
        <?php endif; ?>

        <div class="ph-user-info">
            <h3><?php echo sprintf(esc_html__('Welcome back, %s!', 'psych-g-plugin'), esc_html($user_data['display_name'])); ?></h3>

            <div class="ph-stats-grid">
                <?php if ($atts['show_rank'] === 'yes') : ?>
                    <div class="ph-stat-item">
                        <i class="fas fa-trophy"></i>
                        <span><?php echo esc_html__('Rank:', 'psych-g-plugin'); ?> #<?php echo esc_html($user_data['rank']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($atts['show_progress'] === 'yes') : ?>
                    <div class="ph-stat-item">
                         <i class="fas fa-tasks"></i>
                        <span><?php echo esc_html__('Progress:', 'psych-g-plugin'); ?> <?php echo esc_html($user_data['overall_progress']); ?>%</span>
                    </div>
                <?php endif; ?>

                <?php if ($atts['show_badges'] === 'yes') : ?>
                    <div class="ph-stat-item">
                        <i class="fas fa-medal"></i>
                        <span><?php echo esc_html__('Badges:', 'psych-g-plugin'); ?> <?php echo count($user_data['badges']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($atts['show_tests'] === 'yes') : ?>
                    <div class="ph-stat-item">
                        <i class="fas fa-vial"></i>
                        <span><?php echo esc_html__('Tests Taken:', 'psych-g-plugin'); ?> <?php echo esc_html($user_data['tests_taken']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($atts['button_text']) : ?>
        <div class="ph-action">
            <a href="<?php echo esc_url($atts['button_url']); ?>" class="ph-button" style="background-color: <?php echo esc_attr($atts['accent_color']); ?>; color: <?php echo esc_attr($atts['text_color']); ?>;"><?php echo esc_html($atts['button_text']); ?></a>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($atts['layout'] === 'roadmap' && $atts['show_roadmap'] === 'yes' && !empty($roadmap_items)) : ?>
    <div class="performance-header-roadmap">
        <h4><?php echo esc_html__('Your Roadmap', 'psych-g-plugin'); ?></h4>
        <div class="roadmap-items">
            <?php foreach ($roadmap_items as $item) : ?>
                <div class="roadmap-item <?php echo $item['completed'] ? 'completed' : 'locked'; ?>">
                    <div class="roadmap-badge" style="width: <?php echo esc_attr($atts['roadmap_badge_size']); ?>; height: <?php echo esc_attr($atts['roadmap_badge_size']); ?>;">
                        <img src="<?php echo esc_url($item['icon_url']); ?>" alt="<?php echo esc_attr($item['title']); ?>" />
                    </div>
                    <span><?php echo esc_html($item['title']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($atts['show_next_steps'] === 'yes') : ?>
            <div class="roadmap-next-step">
                <p><?php echo esc_html__('Next up:', 'psych-g-plugin'); ?> <?php echo esc_html($user_data['next_step']['title']); ?></p>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
