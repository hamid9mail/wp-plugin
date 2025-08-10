<?php
/**
 * Plugin Name: Psycho-logical Complete System (Dashboard & Reports Module - Refactored)
 * Description: Handles the display of comprehensive user dashboards and report cards.
 * Version: 2.0
 * Author: Jules
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Psych_Dashboard_And_Reports_Refactored {

    public function __construct() {
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets() {
        wp_register_style('psych-dashboard-reports-css', plugin_dir_url(__FILE__) . 'assets/css/dashboard-and-reports.css');
        wp_register_script('psych-dashboard-reports-js', plugin_dir_url(__FILE__) . 'assets/js/dashboard-and-reports.js', ['jquery'], null, true);
    }

    public function register_shortcodes() {
        // This single shortcode will be a more powerful, consolidated version
        // of the old [psych_report_card] and other display shortcodes.
        add_shortcode('psych_user_report_card', [$this, 'handle_user_report_card']);
    }

    // [psych_user_report_card]
    public function handle_user_report_card($atts) {
        wp_enqueue_style('psych-dashboard-reports-css');
        wp_enqueue_script('psych-dashboard-reports-js');

        $atts = shortcode_atts([
            'user_id' => get_current_user_id(),
            'show_tabs' => 'summary,courses,badges,tests,timeline',
            'default_tab' => 'summary',
        ], $atts, 'psych_user_report_card');

        if (empty($atts['user_id'])) {
            return '<p>' . esc_html__('You must be logged in to view your report card.', 'psych-g-plugin') . '</p>';
        }

        // --- Data Fetching ---
        // In a real implementation, you would fetch all necessary data here.
        // For now, we'll use placeholder data.
        $user_data = $this->get_placeholder_user_data($atts['user_id']);
        $tabs = array_map('trim', explode(',', $atts['show_tabs']));

        // --- Rendering ---
        ob_start();

        // Pass all data and attributes to a single, powerful template
        include(plugin_dir_path(__FILE__) . 'templates/dashboard-and-reports/main-report-card-template.php');

        return ob_get_clean();
    }

    private function get_placeholder_user_data($user_id) {
        // This function would fetch real data from the database.
        return [
            'summary' => [
                'name' => 'John Doe',
                'join_date' => '2023-01-15',
                'points' => 1250,
                'level' => 'Expert Learner',
                'courses_completed' => 5,
                'badges_earned' => 12,
            ],
            'courses' => [
                ['title' => 'Introduction to Psychology', 'progress' => 100, 'completed_date' => '2023-03-20'],
                ['title' => 'Cognitive Behavioral Therapy', 'progress' => 75, 'completed_date' => null],
                ['title' => 'Social Psychology', 'progress' => 90, 'completed_date' => null],
            ],
            'badges' => [
                ['name' => 'Initiator', 'icon_url' => 'path/to/icon.png', 'date_earned' => '2023-01-16'],
                ['name' => 'Course Completer', 'icon_url' => 'path/to/icon.png', 'date_earned' => '2023-03-20'],
                ['name' => 'Top Ranker', 'icon_url' => 'path/to/icon.png', 'date_earned' => '2023-04-05'],
            ],
            'tests' => [
                ['name' => 'MBTI Personality Test', 'score' => 'INFP', 'date_taken' => '2023-02-10'],
                ['name' => 'Beck Depression Inventory', 'score' => '8 (Minimal)', 'date_taken' => '2023-05-01'],
            ],
            'timeline' => [
                ['event' => 'Joined the platform', 'date' => '2023-01-15'],
                ['event' => 'Earned "Initiator" badge', 'date' => '2023-01-16'],
                ['event' => 'Completed "Introduction to Psychology"', 'date' => '2023-03-20'],
            ]
        ];
    }
}

new Psych_Dashboard_And_Reports_Refactored();
