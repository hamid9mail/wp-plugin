<?php
/**
 * Plugin Name:       PsychoCourse Path Engine (Ultimate Enhanced Integration Edition)
 * Description:       موتور جامع مسیر رشد با حالت‌های نمایش مختلف و شخصی‌سازی پیشرفته.
 * Version:           13.4.0 (Personalization Engine Implemented)
 * Author:            Grok AI & Jules
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('PsychoCourse_Path_Engine_Ultimate')) {
    return;
}

final class PsychoCourse_Path_Engine_Ultimate {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->add_hooks();
    }

    private function add_hooks() {
        add_shortcode('psych_personalize', [$this, 'render_personalize_shortcode']);
        add_shortcode('psych_path', [$this, 'render_path_shortcode']);
        add_shortcode('psych_station', [$this, 'render_station_shortcode']);
        add_shortcode('psych_mission', [$this, 'render_mission_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_path_styles']);
        // ... other hooks
    }

    public function render_personalize_shortcode($atts, $content = null) {
        $atts = shortcode_atts(['condition' => ''], $atts);
        $condition_string = $atts['condition'];

        if (empty($condition_string)) {
            return do_shortcode($content); // Show content if no condition is set
        }

        if ($this->evaluate_complex_condition($condition_string)) {
            return do_shortcode($content);
        }

        return ''; // Return empty if condition is not met
    }

    private function evaluate_complex_condition($condition_string) {
        // This is a simplified parser for AND/OR logic. A real implementation might need a more robust solution.
        $or_groups = explode(' OR ', $condition_string);
        foreach ($or_groups as $or_group) {
            $and_rules = explode(' AND ', trim($or_group, '() '));
            $is_and_group_true = true;
            foreach ($and_rules as $rule) {
                if (!$this->evaluate_condition_rule(trim($rule))) {
                    $is_and_group_true = false;
                    break;
                }
            }
            if ($is_and_group_true) {
                return true; // If any OR group is true, the whole condition is true
            }
        }
        return false; // If no OR group was true, the whole condition is false
    }

    private function evaluate_condition_rule($rule) {
        // Rule format: "type:key operator value" e.g., "points > 100" or "role = subscriber"
        preg_match('/^([\w_]+):?([\w_]*)?\s*(!=|>=|<=|>|<|=)\s*(.*)$/', $rule, $matches);

        if (count($matches) !== 5) {
            // Handle simple boolean checks like "is_logged_in"
             if ($rule === 'is_logged_in') return is_user_logged_in();
             if ($rule === 'NOT is_logged_in') return !is_user_logged_in();
            return false;
        }

        list(, $type, $key, $operator, $value) = $matches;
        $user_id = get_current_user_id();
        $user_data = 0;

        switch ($type) {
            case 'role':
                $user_info = get_userdata($user_id);
                $user_roles = $user_info ? $user_info->roles : [];
                return in_array($value, $user_roles);

            case 'points':
                if (function_exists('psych_gamification_get_user_level_info')) {
                    $level_info = psych_gamification_get_user_level_info($user_id);
                    $user_data = $level_info['current_points'] ?? 0;
                }
                break;

            case 'purchased_product':
                if (function_exists('wc_customer_bought_product')) {
                    $user_info = get_userdata($user_id);
                    if ($user_info && wc_customer_bought_product($user_info->user_email, $user_id, $value)) {
                        return true;
                    }
                }
                return false;

            case 'mission_result':
                global $wpdb;
                $results_table = $wpdb->prefix . 'psych_results';
                // This is simplified. A real implementation would need to parse the 'key'
                $user_data = $wpdb->get_var($wpdb->prepare("SELECT score FROM $results_table WHERE user_id = %d AND test_id = %s", $user_id, $key));
                break;
        }

        $value = trim($value, "'\""); // Trim quotes

        switch ($operator) {
            case '=': return $user_data == $value;
            case '!=': return $user_data != $value;
            case '>': return $user_data > $value;
            case '<': return $user_data < $value;
            case '>=': return $user_data >= $value;
            case '<=': return $user_data <= $value;
        }

        return false;
    }

    // ... other class methods from the previous version of path-engine-temp.php

    public function render_path_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'id' => 'default_path',
            'product_id' => '',
            'path_style' => 'subway', // 'subway', 'game_board', 'constellation'
        ], $atts, 'psych_path');

        // Store the product ID associated with this path
        $this->paths[$atts['id']] = [
            'product_id' => $atts['product_id'],
            'stations' => [],
            'path_style' => $atts['path_style'],
        ];

        $stations_content = do_shortcode($content);

        // Add a class to the container to control the styling
        $path_class = 'psych-path psych-path-style-' . esc_attr($atts['path_style']);

        return '<div class="' . $path_class . '" data-path-id="' . esc_attr($atts['id']) . '">' . $stations_content . '</div>';
    }

    public function render_station_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'id' => '',
            'path_id' => 'default_path',
            'product_id' => '',
            'display_mode' => 'checklist', // 'checklist' or 'sequential'
            'mission_content' => 'modal', // 'modal' or 'inline'
        ], $atts, 'psych_station');

        if (empty($atts['id'])) return '';

        // Store station info, including new attributes
        $this->paths[$atts['path_id']]['stations'][$atts['id']] = [
            'product_id' => $atts['product_id'],
            'display_mode' => $atts['display_mode'],
            'mission_content' => $atts['mission_content'],
            'missions' => [],
        ];

        $missions_content = do_shortcode($content);

        return '<div class="psych-station" data-station-id="' . esc_attr($atts['id']) . '" data-display-mode="' . esc_attr($atts['display_mode']) . '" data-mission-content="' . esc_attr($atts['mission_content']) . '">' . $missions_content . '</div>';
    }

    public function render_mission_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'id' => '',
            'title' => 'Mission',
        ], $atts, 'psych_mission');

        if (empty($atts['id'])) {
            return '';
        }

        // The actual rendering logic will be more complex, involving checking completion status,
        // and applying styles for modal/inline display. This is the basic structure.
        return '
            <div class="psych-mission" data-mission-id="' . esc_attr($atts['id']) . '">
                <h4 class="mission-title">' . esc_html($atts['title']) . '</h4>
                <div class="mission-content" style="display:none;">' . do_shortcode($content) . '</div>
            </div>
        ';
    }

    public function enqueue_path_styles() {
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'psych_path')) {
            wp_enqueue_style(
                'psych-path-styles',
                plugin_dir_url(__FILE__) . '../assets/css/path-styles.css',
                [],
                PSYCH_SYSTEM_VERSION
            );
        }
    }
}

PsychoCourse_Path_Engine_Ultimate::get_instance();
?>
