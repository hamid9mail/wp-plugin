<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Psych_Spot_Player_Integration {

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
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('psych_spot_player', [$this, 'render_spot_player_shortcode']);
    }

    public function enqueue_assets() {
        // We will enqueue this script only when the shortcode is used.
        // The actual enqueueing will happen inside the shortcode render function.
    }

    public function render_spot_player_shortcode($atts) {
        $atts = shortcode_atts([
            'video_id' => '', // Or license, or whatever Spot Player uses
            'course_id' => '', // To associate the video with a course
        ], $atts);

        if (empty($atts['video_id'])) {
            return '<div class="psych-alert error">خطا: شناسه ویدیوی Spot Player مشخص نشده است.</div>';
        }

        // Enqueue the handler script here to ensure it only loads when needed
        wp_enqueue_script(
            'psych-spot-player-handler',
            plugin_dir_url(__FILE__) . '../assets/js/spot-player-handler.js',
            ['jquery'],
            PSYCH_SYSTEM_VERSION,
            true
        );

        // Localize script with necessary data
        wp_enqueue_script(
            'psych-mystery-box',
            plugin_dir_url(__FILE__) . '../assets/js/mystery-box.js',
            [],
            PSYCH_SYSTEM_VERSION,
            true
        );
        wp_enqueue_style(
            'psych-mystery-box-style',
            plugin_dir_url(__FILE__) . '../assets/css/mystery-box.css',
            [],
            PSYCH_SYSTEM_VERSION
        );

        wp_localize_script('psych-spot-player-handler', 'spot_player_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('spot_player_nonce'),
            'user_id' => get_current_user_id()
        ]);

        // Generate the HTML for the player.
        // This is a placeholder. The actual implementation will depend on Spot Player's embed method.
        // We assume it needs a container div and the JS will handle the rest.
        return sprintf(
            '<div class="psych-spot-player-container" data-video-id="%s" data-course-id="%s"></div>',
            esc_attr($atts['video_id']),
            esc_attr($atts['course_id'])
        );
    }
}

// Initialize the module
Psych_Spot_Player_Integration::get_instance();
