<?php
/**
 * Plugin Name: Psycho-logical Complete System (Coach Module - Refactored)
 * Description: Handles coach-specific functionalities, including viewing the course as a user.
 * Version: 2.0
 * Author: Jules
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Psych_Coach_Module_Refactored {

    public function __construct() {
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets() {
        // We will enqueue these only when the shortcode is actually used.
        wp_register_style('psych-coach-module-css', plugin_dir_url(__FILE__) . 'assets/css/coach-module.css');
        wp_register_script('psych-coach-module-js', plugin_dir_url(__FILE__) . 'assets/js/coach-module.js', ['jquery'], null, true);
    }

    public function register_shortcodes() {
        add_shortcode('coach_only_content', [$this, 'handle_coach_only_content']);
        add_shortcode('coach_search_by_code', [$this, 'handle_coach_search_by_code']);
        add_shortcode('coach_see_as_user', [$this, 'handle_coach_see_as_user']);
    }

    private function is_user_a_coach($roles_to_check) {
        if (!is_user_logged_in()) {
            return false;
        }
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        // Check for intersection between user roles and roles specified in the shortcode
        return !empty(array_intersect($roles_to_check, $roles));
    }

    // Shortcode: [coach_only_content]
    public function handle_coach_only_content($atts, $content = null) {
        $atts = shortcode_atts([
            'roles' => 'coach,administrator',
        ], $atts, 'coach_only_content');

        $allowed_roles = array_map('trim', explode(',', $atts['roles']));

        if ($this->is_user_a_coach($allowed_roles)) {
            return do_shortcode($content);
        }
        return ''; // Return nothing if user is not a coach
    }

    // Shortcode: [coach_search_by_code]
    public function handle_coach_search_by_code($atts) {
        // Logic for this shortcode will be added here
        // For now, it's a placeholder
        return "<!-- Coach Search by Code functionality will be implemented here. -->";
    }

    // Shortcode: [coach_see_as_user]
    public function handle_coach_see_as_user($atts, $content = null) {
        wp_enqueue_style('psych-coach-module-css');
        wp_enqueue_script('psych-coach-module-js');

        $atts = shortcode_atts([
            'coach_role' => 'coach,adviser',
            'user_role' => 'customer,subscriber',
            'public_tabs' => '',
            'public_forms' => '',
            'private_tabs' => '',
            'private_forms' => '',
        ], $atts, 'coach_see_as_user');
        
        $coach_roles = array_map('trim', explode(',', $atts['coach_role']));

        if (!$this->is_user_a_coach($coach_roles)) {
            return ''; // Don't display anything if the viewer is not a coach
        }

        // Check if we are currently "seeing as user"
        $target_user_id = isset($_SESSION['coach_see_as_user_id']) ? intval($_SESSION['coach_see_as_user_id']) : 0;

        ob_start();

        if ($target_user_id > 0) {
            // We are in "impersonation" mode
            $target_user = get_userdata($target_user_id);
            // Pass data to a template
            include(plugin_dir_path(__FILE__) . 'templates/coach-module/view-as-banner-template.php');
            
            // Here you would modify the main query or user context before rendering the content
            // For now we just show the banner and the original content for the coach
            echo do_shortcode($content);

        } else {
            // We are in "search" mode
            // Pass data to a template
            include(plugin_dir_path(__FILE__) . 'templates/coach-module/user-search-template.php');
        }
        
        return ob_get_clean();
    }
}

new Psych_Coach_Module_Refactored();
