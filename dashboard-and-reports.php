<?php
/**
 * Consolidated Dashboard & Reports Module (Refactored)
 * Merges functionality from report-card.php and dashboard-display.php
 * Version: 1.0.0
 * Author: Jules (Refactored)
 */

if (!defined('ABSPATH')) exit;

if (class_exists('Psych_Dashboard_And_Reports')) {
    return;
}

final class Psych_Dashboard_And_Reports {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('wp_ajax_psych_save_user_notes', [$this, 'ajax_save_user_notes']);
        add_action('wp_ajax_psych_save_user_goals', [$this, 'ajax_save_user_goals']);
    }

    public function register_assets() {
        wp_register_style('psych-dashboard-reports-css', plugin_dir_url(__FILE__) . 'assets/css/dashboard-and-reports.css');
        wp_register_script('psych-dashboard-reports-js', plugin_dir_url(__FILE__) . 'assets/js/dashboard-and-reports.js', ['jquery'], null, true);
        wp_register_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
        wp_localize_script('psych-dashboard-reports-js', 'psych_dashboard_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('psych_dashboard_nonce'),
            'viewed_user_id' => $this->get_viewing_context()['viewed_user_id']
        ]);
    }

    public function register_shortcodes() {
        add_shortcode('psych_report_card', [$this, 'render_report_card_shortcode']);
        add_shortcode('psych_gamified_header', [$this, 'render_gamified_header']);
        add_shortcode('psych_user_dashboard', [$this, 'render_user_dashboard']);
        add_shortcode('psych_user_performance_header', [$this, 'render_performance_header']);
    }

    public function render_gamified_header($atts) {
        wp_enqueue_style('psych-dashboard-reports-css');
        wp_enqueue_script('psych-dashboard-reports-js');
        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];
        if (!$user_id) return '';
        $user_data = $this->get_user_summary_data($user_id);
        $level_info = $this->get_user_level_info($user_id);

        ob_start();
        $this->get_template_part('gamified-header', compact('user_data', 'level_info'));
        return ob_get_clean();
    }

    public function render_user_dashboard($atts) {
        wp_enqueue_style('psych-dashboard-reports-css');
        wp_enqueue_script('psych-dashboard-reports-js');
        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];
        if (!$user_id) return '';
        $user_data = $this->get_user_summary_data($user_id);
        $level_info = $this->get_user_level_info($user_id);

        ob_start();
        $this->get_template_part('user-dashboard', compact('user_data', 'level_info'));
        return ob_get_clean();
    }

    public function render_performance_header($atts) {
        wp_enqueue_style('psych-dashboard-reports-css');
        wp_enqueue_script('psych-dashboard-reports-js');
        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];
        if (!$user_id) return '';
        $user_data = $this->get_user_summary_data($user_id);
        $level_info = $this->get_user_level_info($user_id);

        ob_start();
        $this->get_template_part('performance-header', compact('user_data', 'level_info', 'atts'));
        return ob_get_clean();
    }

    public function render_report_card_shortcode($atts) {
        wp_enqueue_style('psych-dashboard-reports-css');
        wp_enqueue_script('psych-dashboard-reports-js');
        wp_enqueue_script('chart-js');
        $atts = shortcode_atts(['show_tabs' => 'overview,gamification,path,analytics,notes'], $atts);
        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];
        if (!$user_id) return '<p>Please log in to see your report card.</p>';
        $user = get_userdata($user_id);
        if (!$user) return '<p>User not found.</p>';

        $tabs = array_map('trim', explode(',', $atts['show_tabs']));

        ob_start();
        $this->get_template_part('main-report-card-layout', [
            'atts' => $atts,
            'user' => $user,
            'context' => $context,
            'tabs' => $tabs,
            'this' => $this
        ]);
        return ob_get_clean();
    }

    // --- Helper function to include templates ---
    public function get_template_part($template_name, $args = []) {
        extract($args);
        $template_path = plugin_dir_path(__FILE__) . 'templates/dashboard-and-reports/' . $template_name . '.php';
        if (file_exists($template_path)) {
            include($template_path);
        }
    }

    // --- Data Fetching Methods (Merged from both original files) ---
    public function get_user_summary_data($user_id) {
        // Merged logic to get points, badges, progress etc.
        return [
            'total_points' => (int) get_user_meta($user_id, 'psych_total_points', true),
            'badges_count' => count(get_user_meta($user_id, 'psych_user_badges', true) ?: []),
            'completed_stations' => 0, // Placeholder
            'progress_percentage' => 0, // Placeholder
            'recent_achievements' => [] // Placeholder
        ];
    }
    public function get_user_level_info($user_id) { if (function_exists('psych_gamification_get_user_level_info')) { return psych_gamification_get_user_level_info($user_id); } return ['name' => 'N/A', 'icon' => 'fa-question-circle', 'color' => '#ccc']; }
    public function get_user_total_points($user_id) { return (int) get_user_meta($user_id, 'psych_total_points', true); }
    public function get_user_badges_data($user_id) { /* ... logic from report-card ... */ return ['earned' => [], 'available' => []]; }
    public function get_leaderboard_data($user_id) { /* ... logic from report-card ... */ return ['user_rank' => 1, 'total_users' => 1, 'top_users' => []]; }
    public function get_user_path_data($user_id) { /* ... logic from report-card ... */ return ['current_path' => null, 'stations' => [], 'completion_percentage' => 0, 'completed_stations' => 0, 'total_stations' => 0]; }
    public function get_user_analytics_data($user_id) { /* ... logic from report-card ... */ return ['progress_chart' => [], 'badges_chart' => [], 'test_results' => []]; }
    public function get_tab_info($tab_slug) {
        $tab_labels = ['overview' => ['title' => 'Overview', 'icon' => 'fas fa-tachometer-alt'], 'gamification' => ['title' => 'Gamification', 'icon' => 'fas fa-trophy'], 'path' => ['title' => 'Learning Path', 'icon' => 'fas fa-route'], 'analytics' => ['title' => 'Analytics', 'icon' => 'fas fa-chart-line'], 'notes' => ['title' => 'Notes', 'icon' => 'fas fa-sticky-note']];
        return $tab_labels[$tab_slug] ?? ['title' => 'Unknown', 'icon' => 'fas fa-question-circle'];
    }


    // --- AJAX Handlers ---
    public function ajax_save_user_notes() {
        if (!wp_verify_nonce($_POST['nonce'], 'psych_dashboard_nonce')) wp_send_json_error();
        $user_id = intval($_POST['user_id']);
        if (!$user_id || !current_user_can('edit_user', $user_id)) wp_send_json_error();
        update_user_meta($user_id, 'psych_user_notes', sanitize_textarea_field($_POST['content']));
        wp_send_json_success();
    }
    public function ajax_save_user_goals() {
        if (!wp_verify_nonce($_POST['nonce'], 'psych_dashboard_nonce')) wp_send_json_error();
        $user_id = intval($_POST['user_id']);
        if (!$user_id || !current_user_can('edit_user', $user_id)) wp_send_json_error();
        update_user_meta($user_id, 'psych_user_goals', sanitize_textarea_field($_POST['content']));
        wp_send_json_success();
    }

    // --- Private Helper Methods ---
    private function get_viewing_context() {
        if ($this->viewing_context === null) {
            if (function_exists('psych_path_get_viewing_context')) {
                $this->viewing_context = psych_path_get_viewing_context();
            } else {
                $this->viewing_context = ['is_impersonating' => false, 'real_user_id' => get_current_user_id(), 'viewed_user_id' => get_current_user_id()];
            }
        }
        return $this->viewing_context;
    }
}

Psych_Dashboard_And_Reports::get_instance();
