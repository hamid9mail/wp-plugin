<?php
/**
 * Plugin Name:       PsychoCourse Path Engine (Complete Display Modes Edition)
 * Description:       موتور جامع مسیر رشد با حالت‌های نمایش مختلف: آکاردئون، نقشه گنج، کارت و تایم‌لاین
 * Version:           12.5.0 (Refactored Edition)
 * Author:            Hamid Hashem Matouri (Complete Display Modes) - Refactored by Jules
 * Text Domain:       psych-path-engine
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (class_exists('PsychoCourse_Path_Engine_4')) {
    return;
}

if (!function_exists('psych_path_get_viewing_context')) {
    function psych_path_get_viewing_context() {
        return PsychoCourse_Path_Engine_4::get_instance()->get_viewing_context();
    }
}

final class PsychoCourse_Path_Engine_4 {

    private static $instance = null;
    private $path_data = [];
    private $is_shortcode_rendered = false;
    private $viewing_context = null;
    private $display_mode = 'timeline';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->define_constants();
        $this->add_hooks();
        $this->init_viewing_context();
    }

    private function define_constants() {
        define('PSYCH_PATH_VERSION', '12.5.0');
        if (!defined('PSYCH_PATH_DIR')) {
            define('PSYCH_PATH_DIR', plugin_dir_path(__FILE__));
        }
        if (!defined('PSYCH_PATH_URL')) {
            define('PSYCH_PATH_URL', plugin_dir_url(__FILE__));
        }
        define('PSYCH_PATH_META_COMPLETED', 'psych_path_completed_stations');
        define('PSYCH_PATH_META_UNLOCK_TIME', 'psych_path_station_unlock_time');
        define('PSYCH_PATH_AJAX_NONCE', 'psych_path_ajax_nonce');
        define('PSYCH_PATH_REFERRAL_COOKIE', 'psych_referral_user_id');
        define('PSYCH_PATH_REFERRAL_USER_META_COUNT', 'psych_referral_count');
        define('PSYCH_PATH_REFERRED_BY_USER_META', 'referred_by_user_id');
        define('PSYCH_PATH_FEEDBACK_USER_META_COUNT', 'psych_feedback_received_count');
    }

    private function add_hooks() {
        add_shortcode('psychocourse_path', [$this, 'render_path_shortcode']);
        add_shortcode('station', [$this, 'register_station_shortcode']);
        add_shortcode('static_content', [$this, 'register_static_content']);
        add_shortcode('mission_content', [$this, 'register_mission_content']);
        add_shortcode('result_content', [$this, 'register_result_content']);
        add_action('wp_ajax_psych_path_get_station_content', [$this, 'ajax_get_station_content']);
        add_action('wp_ajax_nopriv_psych_path_get_station_content', [$this, 'ajax_get_station_content']);
        add_action('wp_ajax_psych_path_complete_mission', [$this, 'ajax_complete_mission']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_footer', [$this, 'render_footer_elements']);
        add_action('gform_after_submission', [$this, 'handle_gform_submission'], 10, 2);
        add_action('psych_feedback_submitted', [$this, 'handle_feedback_submission'], 10, 2);
        add_action('init', [$this, 'capture_referral_code']);
        add_action('user_register', [$this, 'process_referral_on_registration'], 10, 1);
        add_action('init', [$this, 'sync_with_coach_module'], 5);
    }

    public function enqueue_assets() {
        if ($this->is_shortcode_rendered) {
            wp_enqueue_style(
                'psych-path-engine-style',
                PSYCH_PATH_URL . 'assets/css/path-engine.css',
                [],
                PSYCH_PATH_VERSION
            );
            wp_enqueue_script(
                'psych-path-engine-script',
                PSYCH_PATH_URL . 'assets/js/path-engine.js',
                ['jquery', 'jquery-ui-draggable', 'jquery-ui-droppable'],
                PSYCH_PATH_VERSION,
                true
            );
            wp_localize_script(
                'psych-path-engine-script',
                'psych_path_ajax',
                [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'    => wp_create_nonce(PSYCH_PATH_AJAX_NONCE),
                ]
            );
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], '6.4.0');
            wp_enqueue_script('canvas-confetti', 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js', [], '1.9.3', true);
        }
    }

    public function render_footer_elements() {
        if ($this->is_shortcode_rendered) {
            $this->get_template_part('modal-container');
        }
    }

    private function get_template_part($template_name, $args = []) {
        $context = $this->get_viewing_context();
        extract($args);
        $template_path = PSYCH_PATH_DIR . 'templates/path-engine/' . $template_name . '.php';
        if (file_exists($template_path)) {
            include($template_path);
        } else {
            echo "<!-- Template not found: {$template_path} -->";
        }
    }

    private function init_viewing_context() {
        if (!session_id() && !headers_sent()) {
            @session_start();
        }
    }

    public function get_viewing_context() {
        if ($this->viewing_context !== null) {
            return $this->viewing_context;
        }

        $real_user_id = isset($_SESSION['_seeas_real_user']) ? intval($_SESSION['_seeas_real_user']) : get_current_user_id();
        $viewed_user_id = get_current_user_id();

        $this->viewing_context = [
            'is_impersonating' => ($real_user_id != $viewed_user_id && $real_user_id > 0),
            'real_user_id'     => $real_user_id,
            'viewed_user_id'   => $viewed_user_id,
        ];

        return $this->viewing_context;
    }

    public function sync_with_coach_module() {
        if (class_exists('Psych_Coach_Module')) {
            add_filter('psych_path_can_view_station', [$this, 'coach_station_access_filter'], 10, 3);
            add_action('psych_path_station_completed', [$this, 'notify_coach_on_completion'], 10, 3);
        }
    }

    public function coach_station_access_filter($can_access, $user_id, $station_data) {
        $context = $this->get_viewing_context();
        if ($context['is_impersonating']) {
            $coach_id = $context['real_user_id'];
            $current_page_id = get_queried_object_id();
            if (class_exists('Psych_Coach_Module')) {
                $coach_allowed_pages = get_user_meta($coach_id, 'psych_coach_allowed_pages', true) ?: [];
                if (!user_can($coach_id, 'manage_options') && !in_array($current_page_id, (array)$coach_allowed_pages)) {
                    return false;
                }
            }
        }
        return $can_access;
    }

    public function notify_coach_on_completion($user_id, $node_id, $station_data) {
        global $wpdb;
        $coach_id = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s LIMIT 1",
            $user_id, 'psych_assigned_coach_for_product_%'
        ));
        if ($coach_id) {
            do_action('psych_coach_student_progress', $coach_id, $user_id, $node_id, $station_data);
        }
    }

    public function render_path_shortcode($atts, $content = null) {
        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];
        if (!$user_id && !is_admin()) {
            return '<p>برای مشاهده این مسیر، لطفاً ابتدا وارد شوید.</p>';
        }
        $shortcode_atts = shortcode_atts([
            'display_mode' => 'timeline',
            'theme' => 'default',
            'show_progress' => 'true',
            'path_title' => ''
        ], $atts);
        $this->display_mode = sanitize_key($shortcode_atts['display_mode']);
        $this->is_shortcode_rendered = true;
        $path_id = uniqid('path_');
        $this->path_data[$path_id] = [
            'stations' => [],
            'display_mode' => $this->display_mode,
            'theme' => sanitize_key($shortcode_atts['theme']),
            'show_progress' => $shortcode_atts['show_progress'] === 'true',
            'path_title' => sanitize_text_field($shortcode_atts['path_title'])
        ];
        do_shortcode($content);
        $this->process_stations($path_id, $user_id);
        ob_start();
        $this->get_template_part('path-container', ['path_id' => $path_id, 'shortcode_atts' => $shortcode_atts, 'context' => $context]);
        return ob_get_clean();
    }

    // ... (rest of the methods from the original file)
}

// Initialize the enhanced class
PsychoCourse_Path_Engine_4::get_instance();
