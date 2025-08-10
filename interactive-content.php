<?php
/**
 * فایل: interactive-content.php
 * ماژول محتوای تعاملی یکپارچه (Interactive Content Module - Enhanced Integration Edition)
 *
 * این ماژول، موتور اصلی برای ساخت و نمایش تمام محتواهای تعاملی، آزمون‌ها،
 * مسیرهای پیشرفت گرافیکی و ویجت‌های مبتنی بر وضعیت کاربر است.
 * تمام کدهای PHP, CSS و JavaScript در همین فایل قرار دارند.
 *
 * نسخه: 10.5.0 (Course-Specific Reporting & AJAX Fix Edition)
 * سازگار با: Coach Module , Path Engine .2, Gamification Center, Report Card
 *
 * قابلیت‌ها:
 * - شورت‌کد [psych_content_block]: برای ساخت بلاک‌های محتوای چندمرحله‌ای (SPA-like).
 * - شورت‌کد [psych_content_view]: برای تعریف "نما" یا "وضعیت"‌های مختلف یک بلاک.
 * - شورت‌کد [psych_button]: دکمه‌های هوشمند برای تکمیل ماموریت، باز کردن مودال، و toggle محتوا.
 * - شورت‌کد [psych_hidden_content]: برای ایجاد محتوای پنهان ( مودال یا toggle).
 * - شورت‌کد [psych_progress_path]: برای نمایش گرافیکی مسیر پیشرفت بج‌ها.
 * - پشتیبانی کامل از Coach Impersonation
 * - تزریق مستقیم CSS و JS برای کاهش درخواست‌های HTTP.
 * - تولید خودکار Schema.org (Quiz & FAQ) برای بهبود SEO.
 * - یکپارچه‌سازی کامل با تمام ماژول‌های دیگر سیستم
 * - پشتیبانی از گزارش‌گیری مختص هر دوره (Course-Specific)
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (class_exists('Psych_Interactive_Content_Module_3')) {
    return;
}

/**
 * Enhanced Interactive Content Module Class
 */
final class Psych_Interactive_Content_Module_3 {

    private static $instance = null;

    // برای نگهداری اطلاعات بلاک‌ها و نماهای آن‌ها در یک صفحه
    private $blocks = [];
    private $current_block_id = null;
    private $assets_injected = false; // برای جلوگیری از تزریق چندباره استایل و اسکریپت
    private $viewing_context = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->register_shortcodes();
        $this->add_hooks();
        $this->init_viewing_context();
    }

    private function init_viewing_context() {
        // Get viewing context from coach module if available
        if (function_exists('psych_path_get_viewing_context')) {
            $this->viewing_context = psych_path_get_viewing_context();
        } else {
            // Fallback to basic context
            $this->viewing_context = [
                'is_impersonating' => false,
                'real_user_id' => get_current_user_id(),
                'viewed_user_id' => get_current_user_id(),
            ];
        }
    }

    private function get_viewing_context() {
        if ($this->viewing_context === null) {
            $this->init_viewing_context();
        }
        return $this->viewing_context;
    }

    private function add_hooks() {
        // هوک‌ها برای تزریق CSS, JS, و کانتینر مودال
        add_action('wp_head', [$this, 'inject_inline_css']);
        add_action('wp_footer', [$this, 'inject_modal_and_js']);

        // هوک‌های AJAX
        add_action('wp_ajax_psych_load_modal_content', [$this, 'ajax_load_modal_content']);
        add_action('wp_ajax_nopriv_psych_load_modal_content', [$this, 'ajax_load_modal_content']);

        // Integration hooks
        add_action('psych_path_station_completed', [$this, 'handle_station_completion'], 10, 3);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_integration_scripts']);

        // Coach module integration
        add_filter('psych_interactive_can_access_content', [$this, 'coach_access_filter'], 10, 3);
    }

    /**
     * Enqueue integration scripts
     */
    public function enqueue_integration_scripts() {
        global $post;
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'psych_content_block') ||
            has_shortcode($post->post_content, 'psychocourse_path')
        )) {
            wp_localize_script('jquery', 'psych_interactive_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('psych_interactive_nonce'),
                'viewing_context' => $this->get_viewing_context()
            ]);
        }
    }

    /**
     * Coach access filter for content restrictions
     */
    public function coach_access_filter($can_access, $user_id, $block_data) {
        $context = $this->get_viewing_context();

        // If coach is impersonating, apply their access rules
        if ($context['is_impersonating'] && class_exists('Psych_Coach_Module')) {
            $coach_id = $context['real_user_id'];
            $current_page_id = get_queried_object_id();

            // Check if coach has access to this page
            $coach_allowed_pages = get_user_meta($coach_id, 'psych_coach_allowed_pages', true) ?: [];
            if (!user_can($coach_id, 'manage_options') && !in_array($current_page_id, (array)$coach_allowed_pages)) {
                return false;
            }
        }

        return $can_access;
    }

    private function register_shortcodes() {
        add_shortcode('psych_content_block', [$this, 'render_content_block_shortcode']);
        add_shortcode('psych_content_view', [$this, 'capture_content_view_shortcode']);
        add_shortcode('psych_button', [$this, 'render_button_shortcode']);
        add_shortcode('psych_hidden_content', [$this, 'render_hidden_content_shortcode']);
        add_shortcode('psych_progress_path', [$this, 'render_progress_path_shortcode']);
    }

    // ===================================================================
    // SECTION 1: Shortcode Rendering
    // ===================================================================

    public function render_content_block_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'id' => 'psych_block_' . uniqid(),
            'default_station' => 'start',
            'type' => 'generic',
            'require_login' => 'false',
            'course_id' => '', // NEW: برای گزارش‌گیری مختص دوره
        ], $atts, 'psych_content_block');

        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];

        // Check login requirement
        if ($atts['require_login'] === 'true' && !$user_id) {
            return '<div class="psych-login-required"><p>برای مشاهده این محتوا باید وارد شوید.</p></div>';
        }

        // Check access permissions
        $can_access = apply_filters('psych_interactive_can_access_content', true, $user_id, $atts);
        if (!$can_access) {
            return '<div class="psych-access-denied"><p>شما مجاز به مشاهده این محتوا نیستید.</p></div>';
        }

        $this->assets_injected = true; // علامت‌گذاری برای تزریق استایل‌ها
        $block_id = esc_attr($atts['id']);
        $this->current_block_id = $block_id;
        $this->blocks[$block_id] = [
            'views' => [],
            'config' => $atts,
            'seo_data' => ['questions' => []],
            'user_id' => $user_id
        ];

        do_shortcode($content);

        $current_station = 'start';

        // Try to get current station from path engine (updated class name)
        if (class_exists('PsychoCourse_Path_Engine_4')) {
            $path_engine = PsychoCourse_Path_Engine_4::get_instance();
            if (method_exists($path_engine, 'get_user_station_for_block')) {
                $current_station = $path_engine->get_user_station_for_block($block_id, $user_id);
            }
        }

        $current_station = $current_station ?: $atts['default_station'];

        ob_start();

        // Add coach impersonation notice if applicable
        if ($context['is_impersonating']) {
            $viewed_user_data = get_userdata($user_id);
            echo '<div class="coach-interactive-notice">
                    <i class="fas fa-user-eye"></i>
                    در حال مشاهده محتوای تعاملی برای: <strong>' .
                    esc_html($viewed_user_data->display_name) . '</strong>
                  </div>';
        }

        if ($atts['type'] === 'quiz' && !empty($this->blocks[$block_id]['seo_data']['questions'])) {
            $this->render_quiz_schema($block_id, $current_station);
        }

        // Add course tracking data
        $course_data = '';
        if (!empty($atts['course_id'])) {
            $course_data = sprintf('data-course-id="%s"', esc_attr($atts['course_id']));
        }

        echo sprintf('<div id="%s" class="psych-content-block" data-station="%s" %s>',
                    $block_id, esc_attr($current_station), $course_data);

        if (isset($this->blocks[$block_id]['views'][$current_station])) {
            echo do_shortcode($this->blocks[$block_id]['views'][$current_station]['content']);
        } else {
            $default_content = $this->blocks[$block_id]['views'][$atts['default_station']]['content'] ??
                             '<p>محتوا در حال آماده‌سازی است...</p>';
            echo do_shortcode($default_content);
        }

        echo '</div>';
        $this->current_block_id = null;
        return ob_get_clean();
    }

    public function capture_content_view_shortcode($atts, $content = null) {
        if (!$this->current_block_id) return '';

        $atts = shortcode_atts([
            'station' => 'start',
            'seo_title' => '',
            'seo_desc' => '',
            'access_level' => 'public' // public, user, coach, admin
        ], $atts, 'psych_content_view');

        $station = sanitize_key($atts['station']);
        $access_level = sanitize_key($atts['access_level']);

        // Check access based on level
        if (!$this->check_access_level($access_level)) {
            return '';
        }

        $this->blocks[$this->current_block_id]['views'][$station] = [
            'content' => $content,
            'seo_title' => sanitize_text_field($atts['seo_title']),
            'seo_desc' => sanitize_textarea_field($atts['seo_desc']),
            'access_level' => $access_level
        ];

        // Collect SEO data if applicable
        if (!empty($atts['seo_title']) && !empty($atts['seo_desc'])) {
            $this->blocks[$this->current_block_id]['seo_data']['questions'][] = [
                'question' => $atts['seo_title'],
                'answer' => $atts['seo_desc']
            ];
        }

        return '';
    }

    public function render_button_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'text' => 'کلیک کنید',
            'action' => 'mission', // mission, modal, toggle, link
            'target' => '',
            'class' => 'psych-btn-primary',
            'icon' => '',
            'mission_id' => '',
            'confirm' => 'false',
            'course_id' => '', // NEW: برای tracking مختص دوره
        ], $atts, 'psych_button');

        $context = $this->get_viewing_context();

        $button_class = 'psych-button ' . esc_attr($atts['class']);
        $icon_html = '';
        if (!empty($atts['icon'])) {
            $icon_html = '<i class="' . esc_attr($atts['icon']) . '"></i> ';
        }

        $data_attrs = [
            'data-action="' . esc_attr($atts['action']) . '"',
            'data-target="' . esc_attr($atts['target']) . '"',
            'data-confirm="' . esc_attr($atts['confirm']) . '"'
        ];

        if (!empty($atts['mission_id'])) {
            $data_attrs[] = 'data-mission-id="' . esc_attr($atts['mission_id']) . '"';
        }

        if (!empty($atts['course_id'])) {
            $data_attrs[] = 'data-course-id="' . esc_attr($atts['course_id']) . '"';
        }

        $data_attrs_string = implode(' ', $data_attrs);

        ob_start();

        // Add coach impersonation notice if applicable
        if ($context['is_impersonating'] && in_array($atts['action'], ['mission'])) {
            echo '<div class="coach-impersonation-notice">
                    <i class="fas fa-user-eye"></i>
                    عملکرد این دکمه در حالت نمایش مربی ممکن است متفاوت باشد
                  </div>';
        }

        if ($atts['action'] === 'link' && !empty($atts['target'])) {
            echo sprintf('<a href="%s" class="%s" %s>%s%s</a>',
                        esc_url($atts['target']),
                        $button_class,
                        $data_attrs_string,
                        $icon_html,
                        esc_html($atts['text']));
        } else {
            echo sprintf('<button type="button" class="%s" %s>%s%s</button>',
                        $button_class,
                        $data_attrs_string,
                        $icon_html,
                        esc_html($atts['text']));
        }

        return ob_get_clean();
    }

    public function render_hidden_content_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'id' => 'hidden_' . uniqid(),
            'type' => 'modal', // modal, toggle
            'title' => 'محتوای پنهان',
            'access_level' => 'public',
            'course_id' => '', // NEW: برای tracking مختص دوره
        ], $atts, 'psych_hidden_content');

        if (!$this->check_access_level($atts['access_level'])) {
            return '<div class="psych-access-denied"><p>شما مجاز به مشاهده این محتوا نیستید.</p></div>';
        }

        $element_id = esc_attr($atts['id']);
        $element_class = 'psych-hidden-content psych-hidden-' . esc_attr($atts['type']);

        // Add course tracking data
        $course_data = '';
        if (!empty($atts['course_id'])) {
            $course_data = sprintf('data-course-id="%s"', esc_attr($atts['course_id']));
        }

        ob_start();
        echo sprintf('<div id="%s" class="%s" data-title="%s" %s style="display: none;">',
                    $element_id,
                    $element_class,
                    esc_attr($atts['title']),
                    $course_data);
        echo do_shortcode($content);
        echo '</div>';

        return ob_get_clean();
    }

    public function render_progress_path_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'user_id' => '',
            'display' => 'badges', // badges, levels, custom
            'course_id' => '', // NEW: برای نمایش پیشرفت مختص دوره
        ], $atts, 'psych_progress_path');

        $context = $this->get_viewing_context();
        $user_id = !empty($atts['user_id']) ? intval($atts['user_id']) : $context['viewed_user_id'];

        if (!$user_id) {
            return '<p>کاربر وارد نشده است.</p>';
        }

        $this->assets_injected = true;

        ob_start();

        // Add coach impersonation notice if applicable
        if ($context['is_impersonating']) {
            $viewed_user_data = get_userdata($user_id);
            echo '<div class="coach-progress-notice">
                    <i class="fas fa-user-eye"></i>
                    نمایش پیشرفت برای: <strong>' . esc_html($viewed_user_data->display_name) . '</strong>
                  </div>';
        }

        // Add course-specific container
        $course_class = !empty($atts['course_id']) ? 'psych-course-specific' : 'psych-global-progress';
        $course_data = !empty($atts['course_id']) ? sprintf('data-course-id="%s"', esc_attr($atts['course_id'])) : '';

        echo sprintf('<div class="psych-progress-path-container %s" %s>', $course_class, $course_data);

        switch ($atts['display']) {
            case 'badges':
                echo $this->render_badges_progress($user_id, $atts['course_id']);
                break;
            case 'levels':
                echo $this->render_levels_progress($user_id, $atts['course_id']);
                break;
            case 'custom':
                echo do_shortcode($content);
                break;
            default:
                echo $this->render_badges_progress($user_id, $atts['course_id']);
        }

        echo '</div>';

        return ob_get_clean();
    }

    // ===================================================================
    // SECTION 2: Helper Methods
    // ===================================================================

    private function check_access_level($access_level) {
        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];

        switch ($access_level) {
            case 'public':
                return true;
            case 'user':
                return $user_id > 0;
            case 'coach':
                return $context['is_impersonating'] || user_can($user_id, 'edit_others_posts');
            case 'admin':
                return user_can($user_id, 'manage_options');
            default:
                return true;
        }
    }

    private function render_badges_progress($user_id, $course_id = '') {
        if (class_exists('Psych_Gamification_Center')) {
            $gamification = Psych_Gamification_Center::get_instance();
            if (method_exists($gamification, 'get_user_badges_for_course')) {
                // Course-specific badges
                $badges = $gamification->get_user_badges_for_course($user_id, $course_id);
            } else {
                // Fallback to all badges
                $badges = $gamification->get_user_badges($user_id);
            }

            if (!empty($badges)) {
                ob_start();
                echo '<div class="psych-badges-grid">';
                foreach ($badges as $badge) {
                    echo sprintf('<div class="psych-badge-item">
                                    <img src="%s" alt="%s" class="psych-badge-icon">
                                    <span class="psych-badge-name">%s</span>
                                  </div>',
                                esc_url($badge['icon']),
                                esc_attr($badge['name']),
                                esc_html($badge['name']));
                }
                echo '</div>';
                return ob_get_clean();
            }
        }

        return '<p>هنوز نشانی کسب نشده است.</p>';
    }

    private function render_levels_progress($user_id, $course_id = '') {
        if (class_exists('Psych_Gamification_Center')) {
            $gamification = Psych_Gamification_Center::get_instance();
            if (method_exists($gamification, 'get_user_level_for_course')) {
                // Course-specific level
                $level_data = $gamification->get_user_level_for_course($user_id, $course_id);
            } else {
                // Fallback to global level
                $level_data = $gamification->get_user_level($user_id);
            }

            if (!empty($level_data)) {
                ob_start();
                echo sprintf('<div class="psych-level-display">
                               <div class="psych-level-info">
                                 <span class="psych-level-number">سطح %d</span>
                                 <span class="psych-level-title">%s</span>
                               </div>
                               <div class="psych-level-progress">
                                 <div class="psych-level-bar">
                                   <div class="psych-level-fill" style="width: %d%%"></div>
                                 </div>
                                 <span class="psych-level-text">%d / %d امتیاز</span>
                               </div>
                             </div>',
                            intval($level_data['level']),
                            esc_html($level_data['title']),
                            intval($level_data['progress_percentage']),
                            intval($level_data['current_points']),
                            intval($level_data['required_points']));
                return ob_get_clean();
            }
        }

        return '<p>اطلاعات سطح در دسترس نیست.</p>';
    }

    // ===================================================================
    // SECTION 3: AJAX Handlers
    // ===================================================================

    public function ajax_load_modal_content() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'psych_interactive_nonce')) {
            wp_send_json_error(['message' => 'امنیت تأیید نشد.']);
        }

        $modal_id = sanitize_text_field($_POST['modal_id']);
        $course_id = sanitize_text_field($_POST['course_id'] ?? '');

        // Generate modal content based on modal_id
        $content = $this->generate_modal_content($modal_id, $course_id);

        if ($content) {
            wp_send_json_success(['content' => $content]);
        } else {
            wp_send_json_error(['message' => 'محتوا یافت نشد.']);
        }
    }

    private function generate_modal_content($modal_id, $course_id = '') {
        // Custom modal content generation
        // This can be extended based on your needs

        switch ($modal_id) {
            case 'help':
                return '<h3>راهنما</h3><p>این بخش راهنمای استفاده از سیستم است.</p>';
            case 'info':
                return '<h3>اطلاعات</h3><p>اطلاعات بیشتر در مورد این بخش.</p>';
            default:
                // Try to find content from hidden_content shortcodes
                $element = "#$modal_id";
                return apply_filters('psych_modal_content', '', $modal_id, $course_id);
        }
    }

    /**
     * Handle station completion from path engine
     */
    public function handle_station_completion($user_id, $station_id, $station_data) {
        // Log completion for course-specific tracking
        if (!empty($station_data['course_id'])) {
            $course_completions = get_user_meta($user_id, 'psych_course_completions_' . $station_data['course_id'], true) ?: [];
            $course_completions[] = [
                'station_id' => $station_id,
                'completed_at' => current_time('mysql'),
                'data' => $station_data
            ];
            update_user_meta($user_id, 'psych_course_completions_' . $station_data['course_id'], $course_completions);
        }

        // Trigger any additional course-specific actions
        do_action('psych_course_station_completed', $user_id, $station_id, $station_data);
    }

    // ===================================================================
    // SECTION 4: CSS & JavaScript Injection
    // ===================================================================

    public function inject_inline_css() {
        if (!$this->assets_injected) return;
        ?>
        <style id="psych-interactive-styles">
            /* Base Styles */
            .psych-content-block {
                font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                direction: rtl;
                margin: 20px 0;
            }

            /* Button Styles */
            .psych-button {
                display: inline-flex;
                align-items: center;
                padding: 12px 24px;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                text-decoration: none;
                cursor: pointer;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }

            .psych-btn-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            }

            .psych-btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            }

            .psych-btn-success {
                background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
                color: white;
                box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
            }

            .psych-btn-warning {
                background: linear-gradient(135deg, #ff9800 0%, #e68900 100%);
                color: white;
                box-shadow: 0 4px 15px rgba(255, 152, 0, 0.4);
            }

            .psych-button:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none;
            }

            .psych-button.loading {
                pointer-events: none;
            }

            /* Modal Styles */
            .psych-modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                z-index: 10000;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }

            .psych-modal-overlay.active {
                opacity: 1;
                visibility: visible;
            }

            .psych-modal-dialog {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) scale(0.8);
                background: white;
                border-radius: 15px;
                max-width: 90vw;
                max-height: 90vh;
                overflow: hidden;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
                transition: transform 0.3s ease;
            }

            .psych-modal-overlay.active .psych-modal-dialog {
                transform: translate(-50%, -50%) scale(1);
            }

            .psych-modal-close {
                position: absolute;
                top: 15px;
                right: 15px;
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                z-index: 1;
                color: #666;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
            }

            .psych-modal-close:hover {
                background: rgba(0, 0, 0, 0.1);
                color: #333;
            }

            #psych-modal-content {
                padding: 30px;
                max-height: 80vh;
                overflow-y: auto;
                direction: rtl;
            }

            /* Progress Styles */
            .psych-progress-path-container {
                margin: 20px 0;
                padding: 20px;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            }

            .psych-badges-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 15px;
                margin-top: 15px;
            }

            .psych-badge-item {
                text-align: center;
                padding: 15px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                transition: transform 0.3s ease;
            }

            .psych-badge-item:hover {
                transform: translateY(-3px);
            }

            .psych-badge-icon {
                width: 60px;
                height: 60px;
                border-radius: 50%;
                margin-bottom: 10px;
            }

            .psych-badge-name {
                display: block;
                font-size: 14px;
                font-weight: 600;
                color: #333;
            }

            /* Level Display */
            .psych-level-display {
                background: white;
                padding: 20px;
                border-radius: 12px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }

            .psych-level-info {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
            }

            .psych-level-number {
                font-size: 24px;
                font-weight: 700;
                color: #667eea;
            }

            .psych-level-title {
                font-size: 16px;
                color: #666;
            }

            .psych-level-bar {
                background: #e0e0e0;
                height: 8px;
                border-radius: 4px;
                overflow: hidden;
                margin-bottom: 10px;
            }

            .psych-level-fill {
                height: 100%;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                transition: width 0.3s ease;
            }

            .psych-level-text {
                font-size: 14px;
                color: #666;
            }

            /* Notice Styles */
            .coach-interactive-notice,
            .coach-progress-notice,
            .coach-impersonation-notice {
                background: linear-gradient(135deg, #f39c12, #e67e22);
                color: white;
                padding: 12px 18px;
                border-radius: 8px;
                margin-bottom: 15px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            /* Course-specific styles */
            .psych-course-specific {
                border: 2px dashed #667eea;
                position: relative;
            }

            .psych-course-specific::before {
                content: "دوره‌ای";
                position: absolute;
                top: -10px;
                right: 15px;
                background: #667eea;
                color: white;
                padding: 2px 8px;
                border-radius: 4px;
                font-size: 12px;
                font-weight: 600;
            }

            /* Access denied */
            .psych-access-denied {
                background: #ff6b6b;
                color: white;
                padding: 15px;
                border-radius: 8px;
                text-align: center;
            }

            .psych-login-required {
                background: #ffeaa7;
                color: #2d3436;
                padding: 15px;
                border-radius: 8px;
                text-align: center;
            }

            /* Responsive */
            @media (max-width: 768px) {
                .psych-modal-dialog {
                    max-width: 95vw;
                    margin: 20px;
                }

                #psych-modal-content {
                    padding: 20px;
                }

                .psych-badges-grid {
                    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                    gap: 10px;
                }

                .psych-button {
                    padding: 10px 18px;
                    font-size: 14px;
                }
            }
        </style>
        <?php
    }

    public function inject_modal_and_js() {
    if (!$this->assets_injected) return;
    ?>
    <div id="psych-modal-overlay" class="psych-modal-overlay" style="display: none;">
        <div class="psych-modal-dialog">
            <button class="psych-modal-close" id="psych-modal-close">&times;</button>
            <div id="psych-modal-content"></div>
        </div>
    </div>

    <script id="psych-interactive-scripts">
    jQuery(document).ready(function($) {
        const modal = $('#psych-modal-overlay');
        const modalContent = $('#psych-modal-content');

        function showModal(content, title) {
            modalContent.html('<h2>' + (title || 'اطلاعات') + '</h2>' + content);
            modal.show().addClass('active');
            $('body').css('overflow', 'hidden');
        }

        function closeModal() {
            modal.removeClass('active');
            setTimeout(() => {
                modal.hide();
                $('body').css('overflow', '');
                modalContent.empty();
            }, 300);
        }

        // Event handler ONLY for non-mission buttons from this module
        $(document).on('click', '.psych-button', function(e) {
            const $this = $(this);
            const action = $this.data('action');

            if (action === 'mission') {
                // IMPORTANT: Do nothing here. Let path-engine.php handle it.
                return;
            }

            const target = $this.data('target');
            e.preventDefault();

            switch (action) {
                case 'modal':
                    const targetElement = $(target);
                    if (targetElement.length) {
                        showModal(targetElement.html(), targetElement.data('title'));
                    }
                    break;
                case 'toggle':
                    $(target).slideToggle(300);
                    break;
            }
        });

        // Modal close handlers
        $('#psych-modal-close').on('click', closeModal);
        modal.on('click', function(e) { if ($(e.target).is(modal)) closeModal(); });
        $(document).on('keydown', function(e) { if (e.key === 'Escape' && modal.hasClass('active')) closeModal(); });

        console.log('Psych Interactive Content Module (Cleaned & Patched) initialized.');
    });
    </script>
    <?php
}
}

// Initialize the enhanced module
Psych_Interactive_Content_Module_3::get_instance();
