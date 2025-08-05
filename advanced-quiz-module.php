<?php
/**
 * Plugin Name: Psych Advanced Quiz Module
 * Plugin URI: https://example.com/psych-quiz
 * Description: Advanced Quiz Module for Psych System with inline JS/CSS, optional AI integration via GapGPT API, various question types including MCQ, Likert, Open, Ranking, Drag and Drop, Matrix, Slider. Supports subscale scoring per option. Use shortcodes like [psych_advanced_quiz] for quizzes. AI is optional via 'ai=true' parameter. Includes form builder capabilities, visual report cards, diverse shortcodes, PDF export for reports, and integration with previous quiz shortcodes as a sub-module. Separate shortcodes for AI input/output.
 * Version: 1.2.1
 * Author: Grok AI
 * Author URI: https://example.com
 * License: GPL2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Psych_Advanced_Quiz_Module {
    private $db_version = '1.2';
    private $table_name = 'wp_psych_quiz_results';
    private $openai_api_key_option = 'psych_gapgpt_api_key'; // Option name for API key
    private $api_base_url = 'https://api.gapgpt.app/v1'; // Change to 'https://api.gapapi.com/v1' if needed

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_ajax_save_quiz_results', array($this, 'save_quiz_results_ajax'));
        add_action('wp_ajax_nopriv_save_quiz_results', array($this, 'save_quiz_results_ajax'));
        add_action('wp_ajax_get_user_rank', array($this, 'get_user_rank_ajax'));
        add_action('wp_ajax_nopriv_get_user_rank', array($this, 'get_user_rank_ajax'));
        add_action('wp_ajax_display_leaderboard', array($this, 'display_leaderboard_ajax'));
        add_action('wp_ajax_nopriv_display_leaderboard', array($this, 'display_leaderboard_ajax'));
        add_action('wp_ajax_generate_pdf_report', array($this, 'generate_pdf_report_ajax')); // New for PDF export

        // Hook for Path Engine integration: Trigger mission completion
        add_action('psych_quiz_completed', array($this, 'integrate_with_path_engine'), 10, 2);

        // Enqueue inline styles and scripts when shortcode is used
        add_action('wp_enqueue_scripts', array($this, 'enqueue_inline_assets'));

        // Admin menu for settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // Integration with Coach Module for Response Mode
        add_action('psych_quiz_completed', function($user_id, $quiz_id, $responses) {
            if (!function_exists('psych_path_get_viewing_context')) return;
            $context = psych_path_get_viewing_context(); // From path-engine.php
            if ($context['is_impersonating']) {
                $student_id = $context['viewed_user_id'];
                // Save responses under student_id instead of coach
                update_user_meta($student_id, 'psych_quiz_responses_' . $quiz_id, $responses);
                do_action('psych_coach_quiz_response_submitted', $context['real_user_id'], $student_id, $quiz_id);
            }
        }, 10, 3);
    }

    public function handle_quiz_submission() {
        check_ajax_referer('psych_quiz_nonce', 'nonce');

        $quiz_id = sanitize_text_field($_POST['quiz_id']);
        $responses = wp_kses_post_deep($_POST['responses']); // Using wp_kses_post_deep for arrays

        if (empty($quiz_id) || empty($responses)) {
            wp_send_json_error(['message' => 'داده‌های نامعتبر.']);
        }

        $user_id = get_current_user_id();
        $score = $this->calculate_quiz_score($quiz_id, $responses);

        global $wpdb;
        $wpdb->insert($this->table_name, [
            'user_id' => $user_id,
            'quiz_id' => $quiz_id,
            'score' => $score,
            'responses' => json_encode($responses),
            'created_at' => current_time('mysql')
        ]);

        if (function_exists('psych_gamification_add_points')) {
            psych_gamification_add_points($user_id, 10, 'تکمیل کوئیز');
        }

        do_action('psych_quiz_completed', $user_id, $quiz_id, $score);

        wp_send_json_success(['message' => 'کوئیز با موفقیت تکمیل شد!', 'score' => $score]);
    }



    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            quiz_id varchar(255) NOT NULL,
            user_id bigint(20) NOT NULL,
            username varchar(255) NOT NULL,
            score int(11) NOT NULL,
            correct_answers int(11) NOT NULL,
            incorrect_answers int(11) NOT NULL,
            time_taken float NOT NULL,
            responses text NOT NULL,  -- JSON for detailed responses, including subscales and values
            ai_analysis text,  -- Optional AI-generated analysis
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('psych_quiz_db_version', $this->db_version);
    }

    // ... (rest of the file is unchanged)
    // ...
}

new Psych_Advanced_Quiz_Module();
