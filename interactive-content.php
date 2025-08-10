<?php
/**
 * Plugin Name: Psycho-logical Complete System (Interactive Content Module - Refactored)
 * Description: Provides a suite of shortcodes for creating interactive content elements within learning paths.
 * Version: 2.0
 * Author: Jules
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Psych_Interactive_Content_Refactored {

    public function __construct() {
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets() {
        wp_register_style('psych-interactive-content-css', plugin_dir_url(__FILE__) . 'assets/css/interactive-content.css');
        wp_register_script('psych-interactive-content-js', plugin_dir_url(__FILE__) . 'assets/js/interactive-content.js', ['jquery'], null, true);
    }

    public function register_shortcodes() {
        add_shortcode('psych_content_block', [$this, 'handle_content_block']);
        add_shortcode('psych_content_view', [$this, 'handle_content_view']);
        add_shortcode('psych_button', [$this, 'handle_button']);
        add_shortcode('psych_hidden_content', [$this, 'handle_hidden_content']);
        add_shortcode('psych_progress_path', [$this, 'handle_progress_path']);
        add_shortcode('psych_report_card', [$this, 'handle_report_card']);
        add_shortcode('psych_user_summary', [$this, 'handle_user_summary']);
        add_shortcode('psych_path_progress', [$this, 'handle_path_progress']);
        add_shortcode('psych_achievement_timeline', [$this, 'handle_achievement_timeline']);
    }

    private function enqueue_scripts() {
        wp_enqueue_style('psych-interactive-content-css');
        wp_enqueue_script('psych-interactive-content-js');
    }

    // [psych_content_block] - A simple styled content container
    public function handle_content_block($atts, $content = null) {
        $this->enqueue_scripts();
        $atts = shortcode_atts(['class' => 'default'], $atts, 'psych_content_block');
        return '<div class="psych-content-block ' . esc_attr($atts['class']) . '">' . do_shortcode($content) . '</div>';
    }

    // [psych_content_view] - A container for other content, often forms
    public function handle_content_view($atts, $content = null) {
        $this->enqueue_scripts();
        return '<div class="psych-content-view">' . do_shortcode($content) . '</div>';
    }

    // [psych_button] - A styled button that can trigger actions
    public function handle_button($atts, $content = null) {
        $this->enqueue_scripts();
        $atts = shortcode_atts([
            'link' => '#',
            'target' => '_self',
            'action' => '',
            'class' => 'psych-btn-default'
        ], $atts, 'psych_button');
        return '<a href="' . esc_url($atts['link']) . '" target="' . esc_attr($atts['target']) . '" class="psych-interactive-button ' . esc_attr($atts['class']) . '" data-action="' . esc_attr($atts['action']) . '">' . do_shortcode($content) . '</a>';
    }

    // [psych_hidden_content] - Content revealed by an action (e.g., button click)
    public function handle_hidden_content($atts, $content = null) {
        $this->enqueue_scripts();
        $atts = shortcode_atts([
            'trigger_id' => '', // an ID of an element that triggers the reveal
        ], $atts, 'psych_hidden_content');
        return '<div class="psych-hidden-content" data-trigger-id="' . esc_attr($atts['trigger_id']) . '" style="display:none;">' . do_shortcode($content) . '</div>';
    }

    // The following are placeholders for more complex components.
    // Their full implementation would require data retrieval and templating.

    public function handle_progress_path($atts, $content = null) {
        $this->enqueue_scripts();
        return "<!-- [psych_progress_path] refactored output will go here. -->";
    }

    public function handle_report_card($atts, $content = null) {
        $this->enqueue_scripts();
        return "<!-- [psych_report_card] refactored output will go here. -->";
    }

    public function handle_user_summary($atts, $content = null) {
        $this->enqueue_scripts();
        return "<!-- [psych_user_summary] refactored output will go here. -->";
    }

    public function handle_path_progress($atts, $content = null) {
        $this->enqueue_scripts();
        return "<!-- [psych_path_progress] refactored output will go here. -->";
    }

    public function handle_achievement_timeline($atts, $content = null) {
        $this->enqueue_scripts();
        return "<!-- [psych_achievement_timeline] refactored output will go here. -->";
    }
}

new Psych_Interactive_Content_Refactored();
