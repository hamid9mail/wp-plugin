<?php
/**
 * Plugin Name: Psych Complete System - Secure Audio
 * Description: Provides a secure way to serve audio files to authorized users and integrates with the gamification system.
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Psych_Secure_Audio {

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
        add_action('init', [$this, 'add_rewrite_endpoint']);
        add_action('template_redirect', [$this, 'handle_audio_request']);
        add_shortcode('psych_secure_audio', [$this, 'render_secure_audio_shortcode']);
    }

    public function add_rewrite_endpoint() {
        add_rewrite_endpoint('serve-audio', EP_ROOT);
    }

    public function handle_audio_request() {
        global $wp_query;

        if (!isset($wp_query->query_vars['serve-audio'])) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_die('Authentication required.', 'Error', ['response' => 403]);
        }

        $file_path = isset($_GET['file']) ? sanitize_text_field($_GET['file']) : '';
        if (empty($file_path)) {
            wp_die('File not specified.', 'Error', ['response' => 400]);
        }

        // IMPORTANT: This is a simplified security check.
        // In a real-world scenario, you would have more robust permission checks,
        // e.g., check if the user has purchased the course associated with this audio file.
        $can_access = apply_filters('psych_can_access_secure_audio', true, $file_path, get_current_user_id());
        if (!$can_access) {
            wp_die('You do not have permission to access this file.', 'Error', ['response' => 403]);
        }

        // Prevent directory traversal attacks
        $base_dir = wp_get_upload_dir()['basedir'] . '/private_audio/';
        $full_path = realpath($base_dir . $file_path);

        if (!$full_path || strpos($full_path, $base_dir) !== 0 || !file_exists($full_path)) {
            wp_die('File not found.', 'Error', ['response' => 404]);
        }

        header('Content-Type: audio/mpeg');
        header('Content-Length: ' . filesize($full_path));
        header('Content-Disposition: inline; filename="' . basename($full_path) . '"');
        readfile($full_path);
        exit;
    }

    public function render_secure_audio_shortcode($atts) {
        $atts = shortcode_atts([
            'src' => '',
            'audio_id' => '', // For gamification tracking
        ], $atts, 'psych_secure_audio');

        if (empty($atts['src'])) {
            return '<div class="psych-alert error">خطا: منبع فایل صوتی مشخص نشده است.</div>';
        }

        $audio_id = !empty($atts['audio_id']) ? $atts['audio_id'] : basename($atts['src']);
        $secure_url = home_url('/serve-audio?file=' . urlencode($atts['src']));

        wp_enqueue_script(
            'psych-secure-audio-handler',
            plugin_dir_url(__FILE__) . '../assets/js/secure-audio-handler.js',
            ['jquery'],
            PSYCH_SYSTEM_VERSION,
            true
        );
        wp_localize_script('psych-secure-audio-handler', 'secure_audio_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('secure_audio_nonce'),
        ]);

        return sprintf(
            '<audio controls class="psych-secure-audio-player" data-audio-id="%s">
                <source src="%s" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>',
            esc_attr($audio_id),
            esc_url($secure_url)
        );
    }
}

// Initialize the module
Psych_Secure_Audio::get_instance();
