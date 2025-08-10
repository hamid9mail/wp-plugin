<?php
/**
 * Template for the [quiz] shortcode form.
 *
 * @param array $atts Shortcode attributes.
 * @param array $questions The parsed questions to be displayed.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$quiz_id = esc_attr($atts['quiz_id']);
?>

<div class="psych-quiz-form-container">
    <form id="psych-quiz-form-<?php echo $quiz_id; ?>" class="psych-quiz-form" data-quiz-id="<?php echo $quiz_id; ?>">

        <div class="quiz-progress-bar">
            <div class="quiz-progress-bar-inner"></div>
        </div>

        <?php foreach ($questions as $index => $question_data) : ?>
            <div class="quiz-question" data-question-id="<?php echo $index + 1; ?>">
                <p class="question-text"><?php echo esc_html($question_data['question']); ?></p>
                <div class="question-options">
                    <?php if ($question_data['type'] === 'mcq') : ?>
                        <?php foreach ($question_data['options'] as $option_index => $option) : ?>
                            <label>
                                <input type="radio"
                                       name="question_<?php echo $index + 1; ?>"
                                       value="<?php echo esc_attr($option['text']); ?>"
                                       data-correct="<?php echo $option['correct'] ? 'true' : 'false'; ?>"
                                       required>
                                <?php echo esc_html($option['text']); ?>
                            </label>
                        <?php endforeach; ?>
                    <?php elseif ($question_data['type'] === 'drag_drop') : ?>
                        <ul class="drag-drop-options">
                            <?php // Logic for drag/drop questions would go here ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="submit-quiz-button"><?php esc_html_e('Submit Answers', 'psych-g-plugin'); ?></button>
    </form>

    <div id="psych-quiz-results-container-<?php echo $quiz_id; ?>" class="psych-quiz-results-container" style="display:none;">
        <!-- AJAX results will be loaded here -->
    </div>
</div>
