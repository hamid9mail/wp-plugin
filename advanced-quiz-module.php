<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Psych_Advanced_Quiz_Module')) {
    class Psych_Advanced_Quiz_Module {
        private $db_version = '1.4';
        private $table_name;

        public function __construct() {
            global $wpdb;
            $this->table_name = $wpdb->prefix . 'psych_quiz_results';
            // Activation hook is now handled by the main plugin file.
            // Shortcode registration is now handled by the central shortcode manager.
            add_action('wp_ajax_psych_save_quiz_results', array($this, 'save_quiz_results_ajax'));
            add_action('wp_ajax_nopriv_psych_save_quiz_results', array($this, 'save_quiz_results_ajax'));
            add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        }

        public function activate() {
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $this->table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                quiz_id varchar(255) NOT NULL,
                user_id bigint(20) NOT NULL,
                responses text NOT NULL,
                created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        public function render_quiz_shortcode($atts, $content = null) {
            $quiz_id = isset($atts['id']) ? sanitize_key($atts['id']) : uniqid('quiz_');

            $quiz_data_from_content = $this->parse_question_shortcodes($content);
            $quiz_data_from_atts = isset($atts['data']) ? json_decode(urldecode($atts['data']), true) : [];

            $quiz_data = [
                'questions' => !empty($quiz_data_from_content) ? $quiz_data_from_content : ($quiz_data_from_atts['questions'] ?? [])
            ];

            wp_enqueue_script('psych-quiz-script');
            wp_enqueue_style('psych-quiz-style');

            $output = '<div id="psych-quiz-container-' . esc_attr($quiz_id) . '" class="psych-quiz-container" data-quiz-id="' . esc_attr($quiz_id) . '" data-quiz-data=\'' . json_encode($quiz_data) . '\'>';
            $output .= '<h2>' . (isset($atts['title']) ? esc_html($atts['title']) : 'Quiz') . '</h2>';
            $output .= '<div class="psych-quiz-content"></div>';
            $output .= '<button class="psych-quiz-next">Next</button>';
            $output .= '<div class="psych-quiz-results"></div>';
            $output .= '</div>';

            return $output;
        }

        private function parse_question_shortcodes($content) {
            // This is a placeholder for a real shortcode parser.
            return [];
        }

        public function save_quiz_results_ajax() {
            // Logic to save results
            check_ajax_referer('psych_quiz_nonce', 'nonce');
            global $wpdb;
            $wpdb->insert($this->table_name, [
                'quiz_id' => sanitize_key($_POST['quiz_id']),
                'user_id' => get_current_user_id(),
                'responses' => wp_kses_post_deep($_POST['responses']),
                'created_at' => current_time('mysql')
            ]);
            wp_send_json_success();
        }

        public function enqueue_assets() {
            // Register styles and scripts to be enqueued when the shortcode is used.
            wp_register_style('psych-quiz-style', plugin_dir_url(__FILE__) . 'assets/css/quiz-style.css', [], $this->db_version);
            wp_register_script('psych-quiz-script', plugin_dir_url(__FILE__) . 'assets/js/quiz-script.js', ['jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable'], $this->db_version, true);

            wp_localize_script('psych-quiz-script', 'psych_quiz_vars', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('psych_quiz_nonce')
            ]);
        }
    }

    new Psych_Advanced_Quiz_Module();
}
