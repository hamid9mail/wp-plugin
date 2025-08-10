<?php
/**
 * Template for the main quiz container.
 *
 * @param array $atts Shortcode attributes from [psych_advanced_quiz].
 * @param array $questions The parsed questions for the quiz.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="quiz-container"
     data-quiz-id="<?php echo esc_attr($atts['id']); ?>"
     data-lang="<?php echo esc_attr($atts['lang']); ?>"
     data-ai="<?php echo esc_attr($atts['ai']); ?>"
     data-questions='<?php echo esc_attr(json_encode($questions)); ?>'>

    <?php if (!empty($atts['title'])) : ?>
        <div class="quiz-title"><?php echo esc_html($atts['title']); ?></div>
    <?php endif; ?>

    <div class="timer"></div>
    <div class="question"></div>
    <div class="options"></div>
    <div class="feedback"></div>

    <div class="loading-result" style="display:none;">
        <div class="loading-spinner"></div>
        <p><?php esc_html_e('Calculating your result...', 'psych-system'); ?></p>
    </div>

    <div class="result" style="display:none;"></div>

    <button class="start-quiz-button"><?php esc_html_e('Start Quiz', 'psych-system'); ?></button>
</div>
