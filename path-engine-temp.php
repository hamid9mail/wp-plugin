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
}

PsychoCourse_Path_Engine_Ultimate::get_instance();
?>
