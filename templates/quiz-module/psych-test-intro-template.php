<?php
/**
 * Template for the [psych_test] shortcode intro card.
 *
 * @param array $atts Shortcode attributes.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Logic to determine what the start button should do.
// For now, it might just be a link to the page with the quiz form,
// or it could use JS to reveal the form on the same page.
$start_button_link = '#'; // Placeholder
$start_button_text = !empty($atts['start_button_text']) ? $atts['start_button_text'] : __('Start Test', 'psych-g-plugin');

?>

<div class="psych-test-intro-container" style="background-color: <?php echo esc_attr($atts['box_bg_color']); ?>; border-radius: <?php echo esc_attr($atts['border_radius']); ?>; box-shadow: <?php echo esc_attr($atts['box_shadow']); ?>;">

    <div class="psych-test-intro-header" style="background-color: <?php echo esc_attr($atts['main_color']); ?>;">
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
                <span><i class="fas fa-question-circle"></i> <?php echo esc_html($atts['question_count']); ?> <?php esc_html_e('Questions', 'psych-g-plugin'); ?></span>
            <?php endif; ?>
            <?php if (!empty($atts['initial_count'])) : ?>
                <span><i class="fas fa-users"></i> <?php echo number_format($atts['initial_count']); ?>+ <?php esc_html_e('Taken', 'psych-g-plugin'); ?></span>
            <?php endif; ?>
        </div>
        <a href="<?php echo esc_url($start_button_link); ?>" class="psych-test-start-button" style="background-color: <?php echo esc_attr($atts['secondary_color']); ?>;">
            <?php echo esc_html($start_button_text); ?>
        </a>
    </div>

</div>

<!-- The actual quiz form might be placed here, initially hidden, and revealed by the start button -->
<div id="quiz-form-for-<?php echo esc_attr($atts['gravity_form_id']); ?>" class="quiz-form-wrapper" style="display:none;">
    <?php
        // This is where the [quiz] or [gravityform] shortcode would be rendered.
        // echo do_shortcode('[quiz quiz_id="' . $atts['gravity_form_id'] . '"]...[/quiz]');
    ?>
</div>
