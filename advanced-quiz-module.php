<?php
/**
 * Plugin Name: Psycho-logical Complete System (Advanced Quiz Module - Refactored)
 * Description: A powerful quiz engine with multiple question types, scoring, and more.
 * Version: 2.0
 * Author: Jules
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Psych_Advanced_Quiz_Module_Refactored {

    public function __construct() {
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_psych_submit_quiz', [$this, 'handle_ajax_submission']);
        add_action('wp_ajax_nopriv_psych_submit_quiz', [$this, 'handle_ajax_submission']);
    }

    public function enqueue_assets() {
        wp_register_style('psych-quiz-module-css', plugin_dir_url(__FILE__) . 'assets/css/advanced-quiz-module.css');
        wp_register_script('psych-quiz-module-js', plugin_dir_url(__FILE__) . 'assets/js/advanced-quiz-module.js', ['jquery'], null, true);

        wp_localize_script('psych-quiz-module-js', 'psych_quiz_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('psych_quiz_nonce')
        ]);
    }

    public function register_shortcodes() {
        add_shortcode('psych_test', [$this, 'handle_psych_test_shortcode']);
        add_shortcode('quiz', [$this, 'handle_quiz_shortcode']); // Assuming this is the legacy shortcode
    }

    // Handles the modern [psych_test] container shortcode
    public function handle_psych_test_shortcode($atts, $content = null) {
        wp_enqueue_style('psych-quiz-module-css');
        wp_enqueue_script('psych-quiz-module-js');

        $atts = shortcode_atts([
            'title' => 'Psychology Test',
            'subtitle' => '',
            'description' => '',
            'gravity_form_id' => '',
            'badge_id' => '',
            'image_url' => '',
            'main_color' => '#4a6baf',
            // ... add all other attributes from the user's examples
        ], $atts, 'psych_test');

        ob_start();
        // Pass attributes to a template that displays the test "card" or intro screen
        include(plugin_dir_path(__FILE__) . 'templates/quiz-module/psych-test-intro-template.php');
        return ob_get_clean();
    }

    // Handles the [quiz] shortcode for rendering the actual quiz form
    public function handle_quiz_shortcode($atts, $content = null) {
        wp_enqueue_style('psych-quiz-module-css');
        wp_enqueue_script('psych-quiz-module-js');

        $atts = shortcode_atts([
            'quiz_id' => 'default_quiz',
            'count' => 0, // 0 for all questions
            'lang' => 'en',
        ], $atts, 'quiz');

        $questions = $this->parse_quiz_content($content);
        if ($atts['count'] > 0) {
            $questions = array_slice($questions, 0, $atts['count']);
        }

        ob_start();
        // Pass parsed questions and attributes to the main quiz form template
        include(plugin_dir_path(__FILE__) . 'templates/quiz-module/quiz-form-template.php');
        return ob_get_clean();
    }

    public function handle_ajax_submission() {
        check_ajax_referer('psych_quiz_nonce', 'nonce');

        $quiz_id = sanitize_text_field($_POST['quiz_id']);
        $answers = $_POST['answers']; // This needs heavy sanitization

        // --- SCORING LOGIC ---
        // This is where the complex scoring would happen.
        // It would compare submitted answers against correct answers, calculate scores, subscales, etc.
        $score = 0;
        $total = count($answers);
        // Dummy scoring
        foreach($answers as $ans) {
            if (isset($ans['is_correct']) && $ans['is_correct'] == 'true') {
                 $score++;
            }
        }
        $percent = ($total > 0) ? ($score / $total) * 100 : 0;

        // --- AI ANALYSIS (Placeholder) ---
        // if (get_option('psych_enable_ai_analysis')) {
        //     $ai_feedback = $this->get_ai_feedback($answers);
        // }

        // --- RENDER RESULTS ---
        // The results would be passed to a template.
        ob_start();
        ?>
        <div class="psych-quiz-results">
            <h3>Results for <?php echo esc_html($quiz_id); ?></h3>
            <p>You scored <?php echo esc_html($score); ?> out of <?php echo esc_html($total); ?> (<?php echo round($percent, 2); ?>%).</p>
            <?php // if(isset($ai_feedback)) { echo '<div class="ai-feedback">' . $ai_feedback . '</div>'; } ?>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    private function parse_quiz_content($content) {
        // This function would parse the Q/A format from the shortcode content into a structured array.
        // This is a simplified version.
        $lines = explode("\n", trim($content));
        $questions = [];
        $current_q = null;

        foreach($lines as $line) {
            if (strpos($line, 'Q:') === 0) {
                if ($current_q) $questions[] = $current_q;
                $current_q = ['question' => substr($line, 3), 'options' => [], 'type' => 'mcq'];
            } elseif (strpos($line, 'A:') === 0) {
                 if ($current_q) $current_q['options'][] = ['text' => substr($line, 3), 'correct' => true];
            } elseif (strpos($line, 'I:') === 0) {
                 if ($current_q) $current_q['options'][] = ['text' => substr($line, 3), 'correct' => false];
            }
        }
        if ($current_q) $questions[] = $current_q;

        return $questions;
    }
}

new Psych_Advanced_Quiz_Module_Refactored();
