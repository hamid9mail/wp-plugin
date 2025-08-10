<?php
/**
 * Template for the [psych_test] shortcode intro card.
 *
 * @param array $atts The fully parsed shortcode attributes.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$container_styles = sprintf(
    'background-color: %s; border-radius: %s; box-shadow: %s;',
    esc_attr($atts['box_bg_color']),
    esc_attr($atts['border_radius']),
    esc_attr($atts['box_shadow'])
);

$header_styles = sprintf(
    'background-color: %s;',
    esc_attr($atts['main_color'])
);

$button_styles = sprintf(
    'background-color: %s;',
    esc_attr($atts['secondary_color'])
);

?>
<div class="psych-test-intro-container" style="<?php echo $container_styles; ?>">

    <div class="psych-test-intro-header" style="<?php echo $header_styles; ?>">
        <h3><?php echo esc_html($atts['title']); ?></h3>
        <?php if (!empty($atts['subtitle'])) : ?>
            <span class="subtitle"><?php echo esc_html($atts['subtitle']); ?></span>
        <?php endif; ?>
    </div>

    <div class="psych-test-intro-body">
        <?php if (!empty($atts['image_url'])) : ?>
            <img src="<?php echo esc_url($atts['image_url']); ?>" alt="<?php echo esc_attr($atts['title']); ?>" class="test-intro-image">
        <?php endif; ?>
        <p><?php echo esc_html($atts['description']); ?></p>
    </div>

    <div class="psych-test-intro-footer">
        <div class="psych-test-meta-info">
            <?php if (!empty($atts['question_count'])) : ?>
                <span><i class="fas fa-question-circle"></i> <?php echo esc_html($atts['question_count']); ?> <?php esc_html_e('Questions', 'psych-system'); ?></span>
            <?php endif; ?>
            <?php if (!empty($atts['initial_count'])) : ?>
                <span><i class="fas fa-users"></i> <?php echo number_format_i18n($atts['initial_count']); ?>+ <?php esc_html_e('Taken', 'psych-system'); ?></span>
            <?php endif; ?>
        </div>
        <a href="#" class="psych-test-start-button" style="<?php echo $button_styles; ?>">
            <?php echo esc_html($atts['start_button_text']); ?>
        </a>
    </div>
</div>
