<?php
/**
 * Interactive Content Module (Refactored)
 * Version: 11.0.0
 * Author: Jules (Refactored)
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (class_exists('Psych_Interactive_Content_Module_3')) {
    return;
}

final class Psych_Interactive_Content_Module_3 {

    private static $instance = null;
    private $assets_enqueued = false;
    private $viewing_context = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->register_shortcodes();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

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

    public function enqueue_assets() {
        // We will enqueue assets if any of our shortcodes are present.
        // A better implementation would be to only enqueue if the specific shortcode is on the page.
        if ($this->assets_enqueued) return;

        wp_enqueue_style('psych-interactive-css', plugin_dir_url(__FILE__) . 'assets/css/interactive-content.css');
        wp_enqueue_script('psych-interactive-js', plugin_dir_url(__FILE__) . 'assets/js/interactive-content.js', ['jquery'], null, true);

        $this->assets_enqueued = true;
    }

    private function register_shortcodes() {
        add_shortcode('psych_content_block', [$this, 'render_content_block_shortcode']);
        add_shortcode('psych_button', [$this, 'render_button_shortcode']);
        add_shortcode('psych_hidden_content', [$this, 'render_hidden_content_shortcode']);
        add_shortcode('psych_progress_path', [$this, 'render_progress_path_shortcode']);
    }

    // --- Shortcode Implementations ---

    public function render_content_block_shortcode($atts, $content = null) {
        $this->enqueue_assets();
        return '<div class="psych-content-block">' . do_shortcode($content) . '</div>';
    }

    public function render_button_shortcode($atts, $content = null) {
        $this->enqueue_assets();
        $atts = shortcode_atts([
            'text' => 'Click Me',
            'action' => 'link',
            'target' => '#',
            'class' => 'psych-btn-primary',
            'icon' => '',
        ], $atts);

        $icon_html = $atts['icon'] ? '<i class="' . esc_attr($atts['icon']) . '"></i> ' : '';
        $data_attrs = 'data-action="' . esc_attr($atts['action']) . '" data-target="' . esc_attr($atts['target']) . '"';

        if ($atts['action'] === 'link') {
            return sprintf('<a href="%s" class="psych-button %s" %s>%s%s</a>', esc_url($atts['target']), esc_attr($atts['class']), $data_attrs, $icon_html, esc_html($atts['text']));
        }
        return sprintf('<button type="button" class="psych-button %s" %s>%s%s</button>', esc_attr($atts['class']), $data_attrs, $icon_html, esc_html($atts['text']));
    }

    public function render_hidden_content_shortcode($atts, $content = null) {
        $this->enqueue_assets();
        $atts = shortcode_atts([
            'id' => 'hidden_' . uniqid(),
            'title' => 'Hidden Content',
        ], $atts);

        return sprintf('<div id="%s" class="psych-hidden-content" data-title="%s" style="display: none;">%s</div>', esc_attr($atts['id']), esc_attr($atts['title']), do_shortcode($content));
    }

    public function render_progress_path_shortcode($atts, $content = null) {
        $this->enqueue_assets();
        $atts = shortcode_atts([
            'user_id' => '',
            'display' => 'badges',
        ], $atts);

        $context = $this->get_viewing_context();
        $user_id = !empty($atts['user_id']) ? intval($atts['user_id']) : $context['viewed_user_id'];
        if (!$user_id) return '<p>User not logged in.</p>';

        $items = [];
        if ($atts['display'] === 'badges' && function_exists('psych_gamification_award_badge')) {
            $gamification = Psych_Gamification_Center::get_instance();
            $all_badges = $gamification->get_badges();
            $user_badges = get_user_meta($user_id, 'psych_user_badges', true) ?: [];
            foreach ($all_badges as $slug => $badge) {
                $items[] = [
                    'name' => $badge['name'],
                    'icon' => $badge['icon'], // Assuming icon URL is available
                    'earned' => in_array($slug, $user_badges)
                ];
            }
        }

        ob_start();
        include(plugin_dir_path(__FILE__) . 'templates/interactive-content/progress-path-template.php');
        return ob_get_clean();
    }
}

Psych_Interactive_Content_Module_3::get_instance();
