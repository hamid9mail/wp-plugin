<?php
/**
 * Template for the [psych_quiz_report_card] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param object|null $result The user's quiz result from the database.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!$result) {
    echo '<p>' . esc_html__('No results found for this quiz.', 'psych-system') . '</p>';
    return;
}
?>
<div class="psych-report-card">
    <h2><?php printf(esc_html__('Report Card for Quiz: %s', 'psych-system'), esc_html($atts['quiz_id'])); ?></h2>

    <div class="section score-section">
        <div class="score-circle"><?php echo esc_html($result->score); ?></div>
        <p><?php esc_html_e('Overall Score', 'psych-system'); ?></p>
    </div>

    <?php if (!empty($result->ai_analysis)) : ?>
        <div class="section ai-section">
            <h3><?php esc_html_e('AI Analysis', 'psych-system'); ?></h3>
            <p><?php echo nl2br(esc_html($result->ai_analysis)); ?></p>
        </div>
    <?php endif; ?>

    <div class="section details-section">
        <h3><?php esc_html_e('Details', 'psych-system'); ?></h3>
        <ul>
            <li><strong><?php esc_html_e('Correct Answers:', 'psych-system'); ?></strong> <?php echo esc_html($result->correct_answers); ?></li>
            <li><strong><?php esc_html_e('Incorrect Answers:', 'psych-system'); ?></strong> <?php echo esc_html($result->incorrect_answers); ?></li>
            <li><strong><?php esc_html_e('Time Taken:', 'psych-system'); ?></strong> <?php echo esc_html($result->time_taken); ?> <?php esc_html_e('seconds', 'psych-system'); ?></li>
        </ul>
    </div>

    <button class="psych-export-pdf-btn" data-quiz-id="<?php echo esc_attr($atts['quiz_id']); ?>">
        <?php esc_html_e('Export to PDF', 'psych-system'); ?>
    </button>
</div>
