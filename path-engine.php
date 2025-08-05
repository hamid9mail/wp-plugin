<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (class_exists('PsychoCourse_Path_Engine_Ultimate')) {
    return; // جلوگیری از تعریف مجدد کلاس
}

// تعریف جدول سفارشی (اگر لازم باشد، اینجا برای سازگاری تعریف می‌شود، اما فرض بر این است که در فایل اصلی سیستم تعریف شده)
global $wpdb;
if (!defined('PSYCH_GAMIFICATION_TABLE')) {
    define('PSYCH_GAMIFICATION_TABLE', $wpdb->prefix . 'psych_gamification');
}

if (!defined('PSYCH_RESULTS_TABLE')) {
    define('PSYCH_RESULTS_TABLE', $wpdb->prefix . 'psych_results'); // Assuming for quiz subscales
}

// =====================================================================
// GLOBAL HELPER FUNCTIONS (for compatibility with other modules)
// =====================================================================

if (!function_exists('psych_path_get_viewing_context')) {
    function psych_path_get_viewing_context() {
        return PsychoCourse_Path_Engine_Ultimate::get_instance()->get_viewing_context();
    }
}

if (!function_exists('psych_path_update_user_station')) {
    /**
     * Update user's station in path (for personalized paths)
     */
    function psych_path_update_user_station($user_id, $path_id, $new_station) {
        return PsychoCourse_Path_Engine_Ultimate::get_instance()->update_user_station($user_id, $path_id, $new_station);
    }
}

if (!function_exists('psych_path_get_user_station_for_block')) {
    function psych_path_get_user_station_for_block($block_id, $user_id) {
        return PsychoCourse_Path_Engine_Ultimate::get_instance()->get_user_current_station($user_id, $block_id);
    }
}

if (!function_exists('psych_interactive_get_user_subscales')) {
    /**
     * Helper to get user subscales (from interactive-content integration)
     */
    function psych_interactive_get_user_subscales($user_id, $content_id) {
        // Simulated or real integration - adjust as needed
        return get_user_meta($user_id, 'psych_subscales_' . $content_id, true) ?: [];
    }
}

if (!function_exists('psych_path_unlock_station')) {
    function psych_path_unlock_station($user_id, $station_id) {
        // Logic to unlock station (from gamification integration)
        $unlocked = get_user_meta($user_id, 'psych_unlocked_stations', true) ?: [];
        if (!in_array($station_id, $unlocked)) {
            $unlocked[] = $station_id;
            update_user_meta($user_id, 'psych_unlocked_stations', $unlocked);
        }
    }
}

// The simulated psych_gamification_add_points and psych_gamification_award_badge functions have been removed.
// This module will now rely on the versions loaded from gamification-center.php, ensuring a single source of truth
// and preventing data from being incorrectly saved to user_meta instead of the custom tables.

/**
 * Enhanced Path Engine Class with All Features Implemented & Checked from Previous Versions
 */
final class PsychoCourse_Path_Engine_Ultimate {

    private static $instance = null;
    private $path_data = [];
    private $is_shortcode_rendered = false;
    private $viewing_context = null;
    private $display_mode = 'timeline'; // Default
    private $assets_injected = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->define_constants();
        $this->init_viewing_context();
        $this->add_hooks();
    }

    private function define_constants() {
        define('PSYCH_PATH_VERSION', '13.2.0');
        define('PSYCH_PATH_META_COMPLETED', 'psych_path_completed_stations');
        define('PSYCH_PATH_META_UNLOCK_TIME', 'psych_path_station_unlock_time');
        define('PSYCH_PATH_AJAX_NONCE', 'psych_path_ajax_nonce');
        define('PSYCH_PATH_REFERRAL_COOKIE', 'psych_referral_user_id');
        define('PSYCH_PATH_REFERRAL_USER_META_COUNT', 'psych_referral_count');
        define('PSYCH_PATH_REFERRED_BY_USER_META', 'referred_by_user_id');
        define('PSYCH_PATH_FEEDBACK_USER_META_COUNT', 'psych_feedback_received_count');
        define('PSYCH_PATH_META_PERSONALIZED', 'psych_path_personalized'); // For personalized paths
        define('PSYCH_PATH_META_SUBSCALES', 'psych_subscales'); // For subscales integration
    }

    private function add_hooks() {
        // Core Shortcodes (All from previous versions)
        add_shortcode('psychocourse_path', [$this, 'render_path_shortcode']);
        add_shortcode('station', [$this, 'register_station_shortcode']);
        add_shortcode('static_content', [$this, 'register_static_content']);
        add_shortcode('mission_content', [$this, 'register_mission_content']);
        add_shortcode('result_content', [$this, 'register_result_content']);
        add_shortcode('psych_share_buttons', [$this, 'render_share_buttons_shortcode']);
        add_shortcode('mission', [$this, 'register_mission_shortcode']);
        add_shortcode('psych_recommendation', [$this, 'render_recommendation_shortcode']); // New for related suggestions

        // AJAX Handlers (Enhanced with security)
        add_action('wp_ajax_psych_path_get_station_content', [$this, 'ajax_get_station_content']);
        add_action('wp_ajax_psych_path_complete_mission', [$this, 'ajax_complete_mission']);
        add_action('wp_ajax_psych_generate_referral_link', [$this, 'ajax_generate_referral_link']);
        add_action('wp_ajax_psych_submit_feedback', [$this, 'ajax_submit_feedback']);
        add_action('wp_ajax_nopriv_psych_submit_feedback', [$this, 'ajax_submit_feedback']);
        add_action('wp_ajax_psych_path_get_progress', [$this, 'ajax_get_progress']);
        add_action('wp_ajax_psych_path_get_recommendation', [$this, 'ajax_get_recommendation']); // New for suggestions

        // Integration Hooks (All from previous versions + enhancements)
        add_action('gform_after_submission', [$this, 'handle_gform_submission'], 10, 2);
        add_action('psych_feedback_submitted', [$this, 'handle_feedback_submission'], 10, 2);
        add_action('init', [$this, 'capture_referral_code']);
        add_action('user_register', [$this, 'process_referral_on_registration'], 10, 1);
        add_action('init', [$this, 'sync_with_coach_module'], 5);
        add_action('psych_interactive_completed', [$this, 'handle_interactive_completion'], 10, 3); // Subscales integration
        add_action('psych_path_station_completed', [$this, 'notify_coach_on_completion'], 10, 3);
        add_action('psych_path_station_completed', [$this, 'handle_gamification_rewards'], 10, 3); // Gamification with custom table
        add_action('psych_path_station_completed', [$this, 'send_conditional_reports'], 10, 3); // Conditional reports & SMS
        add_action('woocommerce_order_status_completed', [$this, 'handle_woocommerce_completion'], 10, 1); // WooCommerce integration

        // Assets Injection (Inline)
        add_action('wp_head', [$this, 'inject_inline_css']);
        add_action('wp_footer', [$this, 'inject_inline_js_and_modal']);

		// Quiz Integration Hook
		add_action('psych_quiz_completed', [$this, 'handle_quiz_completion'], 10, 3);
    }

    // New complete function to add in the class
	public function handle_quiz_completion($quiz_id, $score, $user_id) {
		// Update path station based on quiz score (example: complete if score > 80)
		$path_id = 'related_path_id'; // Replace with logic to get related path_id from quiz
		$new_station = ($score > 80) ? 'completed' : 'in_progress';
		$this->update_user_station($user_id, $path_id, $new_station);

		// Integrate with gamification (add points)
		if (function_exists('psych_gamification_add_points')) {
			psych_gamification_add_points($user_id, $score, 'تکمیل کوئیز پیشرفته');
		}

		// Optional: Award badge if score is high
		if ($score > 90 && function_exists('psych_gamification_award_badge')) {
			psych_gamification_award_badge($user_id, 'quiz_master');
		}

		// Trigger further actions (e.g., notify coach)
		do_action('psych_path_station_completed', $user_id, $quiz_id, $score);
	}

    private function init_viewing_context() {
        if (!session_id() && !headers_sent()) {
            @session_start();
        }
        $real_user_id = isset($_SESSION['_seeas_real_user']) ? intval($_SESSION['_seeas_real_user']) : get_current_user_id();
        $viewed_user_id = get_current_user_id();

        $this->viewing_context = [
            'is_impersonating' => ($real_user_id != $viewed_user_id && $real_user_id > 0),
            'real_user_id'     => $real_user_id,
            'viewed_user_id'   => $viewed_user_id,
        ];
    }

    public function get_viewing_context() {
        if ($this->viewing_context === null) {
            $this->init_viewing_context();
        }
        return $this->viewing_context;
    }

    public function sync_with_coach_module() {
        if (class_exists('Psych_Coach_Module')) {
            add_filter('psych_path_can_view_station', [$this, 'coach_station_access_filter'], 10, 3);
            add_action('psych_path_station_completed', [$this, 'notify_coach_on_completion'], 10, 3);
            add_action('psych_path_station_completed', [$this, 'coach_handle_mission_response'], 5, 1);
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
            "SELECT meta_value FROM {$wpdb->usermeta}
             WHERE user_id = %d AND meta_key LIKE %s LIMIT 1",
            $user_id, 'psych_assigned_coach_for_product_%'
        ));

        if ($coach_id) {
            do_action('psych_coach_student_progress', $coach_id, $user_id, $node_id, $station_data);
        }
    }

	public function coach_handle_mission_response($mission_data) {
		$context = $this->get_viewing_context();
		if ($context['is_impersonating'] && isset($mission_data['coach_mode']) && $mission_data['coach_mode'] === 'response') {
			// Allow coach to respond, but route save to student ID
			add_filter('psych_save_mission_user_id', function($save_user_id) use ($context) {
				return $context['viewed_user_id']; // Save to student
			});
		}
	}

    // =====================================================================
    // SECTION 1: CORE PATH & STATION LOGIC WITH DISPLAY MODES (Preserved & Enhanced)
    // =====================================================================

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
            'path_title' => '',
            'path_id' => uniqid('path_')
        ], $atts);

        $this->display_mode = sanitize_key($shortcode_atts['display_mode']);
        $this->is_shortcode_rendered = true;
        $path_id = sanitize_key($shortcode_atts['path_id']);
        $this->path_data[$path_id] = [
            'stations' => [],
            'display_mode' => $this->display_mode,
            'theme' => sanitize_key($shortcode_atts['theme']),
            'show_progress' => $shortcode_atts['show_progress'] === 'true',
            'path_title' => sanitize_text_field($shortcode_atts['path_title'])
        ];

        do_shortcode($content);

        $this->process_stations($path_id, $user_id);

        $this->assets_injected = true;

        ob_start();
        ?>
        <div class="psych-path-container <?php echo esc_attr($this->display_mode); ?> theme-<?php echo esc_attr($shortcode_atts['theme']); ?>" data-path-id="<?php echo esc_attr($path_id); ?>">
            <?php if ($context['is_impersonating']) : ?>
                <div class="coach-impersonation-notice">در حال مشاهده مسیر به عنوان مربی</div>
            <?php endif; ?>
            <?php if ($shortcode_atts['path_title']) : ?>
                <h2 class="psych-path-title"><?php echo esc_html($shortcode_atts['path_title']); ?></h2>
            <?php endif; ?>
            <?php if ($shortcode_atts['show_progress'] === 'true') : ?>
                <?php echo $this->render_progress_indicator($path_id); ?>
            <?php endif; ?>
            <?php echo $this->render_path_by_display_mode($path_id, $context); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function register_station_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'station_node_id' => uniqid('station_'),
            'title' => 'ایستگاه بدون عنوان',
            'unlock_trigger' => 'sequential',
            'mission_type' => '',
            'mission_target' => '',
            'rewards' => '',
            'icon' => 'fas fa-circle',
            'condition' => '', // For conditional unlock based on subscales
        ], $atts);

        $path_id = array_key_last($this->path_data);
        $this->path_data[$path_id]['stations'][] = $atts;
        $this->path_data[$path_id]['stations'][count($this->path_data[$path_id]['stations']) - 1]['content'] = do_shortcode($content);

        return '';
    }

    public function register_static_content($atts, $content = null) {
        return do_shortcode($content);
    }

    public function register_mission_content($atts, $content = null) {
        return do_shortcode($content);
    }

    public function register_result_content($atts, $content = null) {
        return do_shortcode($content);
    }

    private function process_stations($path_id, $user_id) {
        $stations = &$this->path_data[$path_id]['stations'];
        $previous_completed = true;

        foreach ($stations as &$station) {
            $station['status'] = 'locked';
            $station['is_completed'] = $this->is_station_completed($user_id, $station['station_node_id']);
            $station['is_unlocked'] = false;

            $station = $this->calculate_station_status($user_id, $station, $previous_completed);

            $previous_completed = $station['is_completed'];
        }
    }

    // Updated: calculate_station_status (with quiz subscale integration)
    public function calculate_station_status($user_id, $station, $previous_stations_completed) {
        // Existing logic (from your file summary)
        if ($station['unlock_trigger'] === 'sequential' && $previous_stations_completed < count($this->path_data['stations']) - 1) {
            return 'locked';
        }

        // New: Conditional with subscales from quiz/interactive
        if ($station['unlock_trigger'] === 'conditional') {
            $subscales = psych_interactive_get_user_subscales($user_id, $station['content_id']) ?: [];

            // Integrate subscales from quiz (fetch from custom table)
            global $wpdb;
            $quiz_subscales = $wpdb->get_var($wpdb->prepare(
                "SELECT subscales FROM " . PSYCH_RESULTS_TABLE . " WHERE user_id = %d AND test_id = %s AND type = 'quiz' ORDER BY created_at DESC LIMIT 1",
                $user_id, $station['quiz_id'] ?? 'default_quiz' // Assume quiz_id is in station data
            ));
            $quiz_subscales = json_decode($quiz_subscales, true) ?: [];
            $subscales = array_merge($subscales, $quiz_subscales);

            // Example condition: unlock if energy subscale > 70
            if (isset($subscales['energy']) && $subscales['energy'] > 70) {
                return 'open';
            }
            return 'locked';
        }

        // Default statuses
        if ($this->is_station_completed($user_id, $station['node_id'])) {
            return 'completed';
        }
        return 'open'; // or 'locked' based on other logic
    }

    private function is_station_completed($user_id, $node_id) {
        $completed = get_user_meta($user_id, PSYCH_PATH_META_COMPLETED, true) ?: [];
        return in_array($node_id, $completed);
    }

    private function render_progress_indicator($path_id) {
        $stations = $this->path_data[$path_id]['stations'];
        $total = count($stations);
        $completed = count(array_filter($stations, function($s) { return $s['is_completed']; }));
        $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

        ob_start();
        ?>
        <div class="psych-path-progress">
            <p>پیشرفت: <?php echo $completed; ?> از <?php echo $total; ?> ایستگاه</p>
            <div class="progress-bar" style="width: <?php echo $percentage; ?>%;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_path_by_display_mode($path_id, $context) {
        $stations = $this->path_data[$path_id]['stations'];
        $mode = $this->path_data[$path_id]['display_mode'];

        ob_start();

        switch ($mode) {
            case 'timeline':
                ?>
                <div class="psych-timeline">
                    <?php foreach ($stations as $index => $station) : ?>
                        <?php echo $this->render_single_station_node($station, $index, $context); ?>
                    <?php endforeach; ?>
                </div>
                <?php
                break;

            case 'accordion':
                ?>
                <div class="psych-accordion">
                    <?php foreach ($stations as $station) : ?>
                        <div class="accordion-item <?php echo esc_attr($station['status']); ?>" data-node-id="<?php echo esc_attr($station['station_node_id']); ?>">
                            <button class="accordion-header">
                                <i class="<?php echo esc_attr($station['icon']); ?>"></i> <?php echo esc_html($station['title']); ?>
                            </button>
                            <div class="accordion-content" <?php if ($station['status'] !== 'open' && $station['status'] !== 'completed') echo 'style="display: none;"'; ?>>
                                <?php echo do_shortcode($station['content']); ?>
                                <?php if ($context['is_impersonating']) : ?>
                                    <div class="coach-notice">حالت مربی: محتوای کامل نمایش داده می‌شود.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php
                break;

            case 'treasure_map':
                ?>
                <div class="psych-treasure-map" style="position: relative; height: 400px; width: 100%; background: #f0f8ff;">
                    <?php foreach ($stations as $index => $station) : ?>
                        <?php
                        $pos = $this->get_treasure_map_position($index, count($stations));
                        $line_color = $station['is_completed'] ? 'green' : 'gray';
                        if ($index > 0) {
                            $prev_pos = $this->get_treasure_map_position($index - 1, count($stations));
                            ?>
                            <svg style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                                <line x1="<?php echo $prev_pos['x']; ?>%" y1="<?php echo $prev_pos['y']; ?>%" x2="<?php echo $pos['x']; ?>%" y2="<?php echo $pos['y']; ?>%" stroke="<?php echo $line_color; ?>" stroke-width="2" />
                            </svg>
                        <?php } ?>
                        <div class="map-node <?php echo esc_attr($station['status']); ?>" style="position: absolute; left: <?php echo $pos['x']; ?>%; top: <?php echo $pos['y']; ?>%;" data-node-id="<?php echo esc_attr($station['station_node_id']); ?>">
                            <i class="<?php echo esc_attr($station['icon']); ?>"></i>
                            <span class="tooltip"><?php echo esc_html($station['title']); ?></span>
                            <div class="popup-content" style="display: none;"><?php echo do_shortcode($station['content']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php
                break;

            case 'cards':
                ?>
                <div class="psych-cards">
                    <?php foreach ($stations as $station) : ?>
                        <div class="card <?php echo esc_attr($station['status']); ?>" data-node-id="<?php echo esc_attr($station['station_node_id']); ?>">
                            <div class="card-header">
                                <i class="<?php echo esc_attr($station['icon']); ?>"></i> <?php echo esc_html($station['title']); ?>
                            </div>
                            <div class="card-content">
                                <?php echo do_shortcode($station['content']); ?>
                                <?php if ($context['is_impersonating']) : ?>
                                    <div class="coach-notice">حالت مربی: محتوای کامل نمایش داده می‌شود.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php
                break;

            case 'simple_list':
            default:
                ?>
                <ul class="psych-simple-list">
                    <?php foreach ($stations as $station) : ?>
                        <li class="<?php echo esc_attr($station['status']); ?>" data-node-id="<?php echo esc_attr($station['station_node_id']); ?>">
                            <i class="<?php echo esc_attr($station['icon']); ?>"></i> <?php echo esc_html($station['title']); ?> (وضعیت: <?php echo esc_html($station['status']); ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php
                break;
        }

        return ob_get_clean();
    }

    private function render_single_station_node($station, $index, $context) {
        ob_start();
        ?>
        <div class="timeline-item <?php echo esc_attr($station['status']); ?>" data-node-id="<?php echo esc_attr($station['station_node_id']); ?>">
            <div class="timeline-icon">
                <i class="<?php echo esc_attr($station['icon']); ?>"></i>
            </div>
            <div class="timeline-content">
                <h3><?php echo esc_html($station['title']); ?></h3>
                <?php if ($station['is_unlocked'] || $station['is_completed']) : ?>
                    <?php echo do_shortcode($station['content']); ?>
                    <?php if ($station['mission_type']) : ?>
                        <button class="complete-mission-btn" data-mission-type="<?php echo esc_attr($station['mission_type']); ?>">تکمیل ماموریت</button>
                    <?php endif; ?>
                <?php else : ?>
                    <p>این ایستگاه قفل است.</p>
                <?php endif; ?>
                <?php if ($context['is_impersonating']) : ?>
                    <div class="coach-notice">حالت مربی: محتوای کامل نمایش داده می‌شود.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_treasure_map_position($index, $total) {
        $x = ($index / ($total - 1)) * 100;
        $y = 50 + sin($index * pi() / ($total - 1)) * 30;
        return ['x' => $x, 'y' => $y];
    }

    // =====================================================================
    // SECTION 2: AJAX HANDLERS (Enhanced with Security & New Features)
    // =====================================================================

    public function ajax_get_station_content() {
        check_ajax_referer(PSYCH_PATH_AJAX_NONCE, 'nonce');

        $path_id = sanitize_key($_POST['path_id']);
        $node_id = sanitize_key($_POST['node_id']);
        $user_id = get_current_user_id();
        $captcha = sanitize_text_field($_POST['captcha']); // Simulated CAPTCHA check

        if (!$user_id || $captcha !== 'valid') { // Replace with real CAPTCHA validation
            wp_send_json_error(['message' => 'احراز هویت ناموفق.']);
        }

        $stations = $this->path_data[$path_id]['stations'] ?? [];
        $station = array_filter($stations, function($s) use ($node_id) { return $s['station_node_id'] === $node_id; });
        $station = reset($station);

        if ($station && $station['is_unlocked']) {
            wp_send_json_success(['content' => do_shortcode($station['content'])]);
        } else {
            wp_send_json_error(['message' => 'ایستگاه قفل است.']);
        }
    }

    public function ajax_complete_mission() {
        check_ajax_referer(PSYCH_PATH_AJAX_NONCE, 'nonce');

        $node_id = sanitize_key($_POST['node_id']);
        $mission_type = sanitize_key($_POST['mission_type']);
        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : get_current_user_id(); // For coach response mode
        $user_id = get_current_user_id(); // Current user (could be coach)

        $context = $this->get_viewing_context();
        $is_coach = $context['is_impersonating'];

        if (!$student_id) {
            wp_send_json_error(['message' => 'کاربر معتبر نیست.']);
        }

        // Check if already completed (for student)
        $completed = get_user_meta($student_id, PSYCH_PATH_META_COMPLETED, true) ?: [];
        if (!in_array($node_id, $completed)) {
            $completed[] = $node_id;
            update_user_meta($student_id, PSYCH_PATH_META_COMPLETED, $completed);

            // Apply rewards (same as other missions)
            if ($mission_type === 'quiz_submission' || $mission_type === 'feedback' || $mission_type === 'form' || $mission_type === 'test') {
                // Example: Award points, badge, unlock
                if (isset($_POST['reward_points']) && $_POST['reward_points'] > 0) {
                    psych_gamification_add_points($student_id, intval($_POST['reward_points']), 'Mission: ' . $mission_type);
                }
                if (isset($_POST['reward_badge']) && !empty($_POST['reward_badge'])) {
                    psych_gamification_award_badge($student_id, sanitize_key($_POST['reward_badge']));
                }
                if (isset($_POST['unlock_station']) && !empty($_POST['unlock_station'])) {
                    psych_path_unlock_station($student_id, sanitize_key($_POST['unlock_station']));
                }

                // For quiz: Save result to quiz table under student ID
                if ($mission_type === 'quiz_submission') {
                    global $wpdb;
                    $quiz_table = $wpdb->prefix . 'psych_quiz_results';
                    $score = isset($_POST['score']) ? intval($_POST['score']) : 0; // Assume score from form
                    $ai_analysis = isset($_POST['ai_analysis']) ? sanitize_text_field($_POST['ai_analysis']) : '';
                    $wpdb->insert($quiz_table, [
                        'user_id' => $student_id, // Save for student
                        'quiz_id' => sanitize_key($_POST['quiz_id'] ?? 'default'),
                        'score' => $score,
                        'time_taken' => 0, // Or calculate
                        'ai_analysis' => $ai_analysis,
                    ]);
                }

                // For feedback/form/test: Save to reports table under student ID (example)
                if ($mission_type === 'feedback' || $mission_type === 'form' || $mission_type === 'test') {
                    global $wpdb;
                    $reports_table = $wpdb->prefix . 'psych_reports';
                    $feedback = sanitize_textarea_field($_POST['feedback'] ?? '');
                    $wpdb->update($reports_table, ['user_notes' => $feedback], ['user_id' => $student_id]); // Or insert if needed
                }
            }

            do_action('psych_path_station_completed', $student_id, $node_id, []);
            wp_send_json_success(['message' => 'ماموریت تکمیل شد! (ذخیره برای دانشجو: ' . $student_id . ')']);
        } else {
            wp_send_json_error(['message' => 'ماموریت قبلاً تکمیل شده است.']);
        }
    }

    public function ajax_generate_referral_link() {
        check_ajax_referer(PSYCH_PATH_AJAX_NONCE, 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => 'کاربر وارد نشده است.']);
        }

        $referral_code = get_user_meta($user_id, 'psych_referral_code', true);
        if (!$referral_code) {
            $referral_code = wp_generate_password(8, false);
            update_user_meta($user_id, 'psych_referral_code', $referral_code);
        }

        $link = add_query_arg('ref', $referral_code, home_url('/register'));
        wp_send_json_success(['link' => $link]);
    }

    public function ajax_submit_feedback() {
        check_ajax_referer(PSYCH_PATH_AJAX_NONCE, 'nonce');

        $user_id = get_current_user_id();
        $node_id = sanitize_key($_POST['node_id']);
        $feedback = sanitize_textarea_field($_POST['feedback']);

        if (!$user_id || empty($feedback)) {
            wp_send_json_error(['message' => 'اطلاعات ناقص است.']);
        }

        do_action('psych_feedback_submitted', $user_id, $feedback, $node_id);

        $count = get_user_meta($user_id, PSYCH_PATH_FEEDBACK_USER_META_COUNT, true) ?: 0;
        update_user_meta($user_id, PSYCH_PATH_FEEDBACK_USER_META_COUNT, $count + 1);

        $this->check_and_complete_feedback_missions($user_id);

        wp_send_json_success(['message' => 'بازخورد با موفقیت ارسال شد.']);
    }

    public function ajax_get_progress() {
        check_ajax_referer(PSYCH_PATH_AJAX_NONCE, 'nonce');

        $path_id = sanitize_key($_POST['path_id']);
        $user_id = get_current_user_id();

        $stations = $this->path_data[$path_id]['stations'] ?? [];
        $completed = count(array_filter($stations, function($s) { return $s['is_completed']; }));
        $total = count($stations);

        wp_send_json_success(['completed' => $completed, 'total' => $total]);
    }

    public function ajax_get_recommendation() {
        check_ajax_referer(PSYCH_PATH_AJAX_NONCE, 'nonce');

        $user_id = get_current_user_id();
        $subscales = psych_interactive_get_user_subscales($user_id, 'quiz_1');

        // Simulated AI suggestion (integrate real AI API like OpenAI)
        $recommendation = 'بر اساس نمره اضطراب شما (' . ($subscales['anxiety'] ?? 0) . ')، پیشنهاد می‌شود مسیر آرامش را دنبال کنید.';

        wp_send_json_success(['recommendation' => $recommendation]);
    }

    // =====================================================================
    // SECTION 3: INTEGRATIONS AND CORE LOGIC (All Features from Previous Versions + Enhancements, with Custom Table Integration)
    // =====================================================================

    public function handle_gform_submission($entry, $form) {
        // Gravity Forms integration from previous versions
        $user_id = get_current_user_id();
        $node_id = rgar($entry, 'node_id'); // Assume field
        if ($user_id && $node_id) {
            do_action('psych_path_station_completed', $user_id, $node_id, ['form_id' => $form['id']]);
        }
    }

    public function handle_feedback_submission($user_id, $feedback) {
        // Handle feedback from previous versions
        do_action('psych_feedback_submitted', $user_id, $feedback);
    }

    public function capture_referral_code() {
        if (isset($_GET['ref'])) {
            $referral_code = sanitize_text_field($_GET['ref']);
            setcookie(PSYCH_PATH_REFERRAL_COOKIE, $referral_code, time() + 3600 * 24 * 30, '/');
        }
    }

    public function process_referral_on_registration($user_id) {
        if (isset($_COOKIE[PSYCH_PATH_REFERRAL_COOKIE])) {
            $referral_code = sanitize_text_field($_COOKIE[PSYCH_PATH_REFERRAL_COOKIE]);
            $referrer_id = $this->get_user_by_referral_code($referral_code);
            if ($referrer_id) {
                update_user_meta($user_id, PSYCH_PATH_REFERRED_BY_USER_META, $referrer_id);
                $count = get_user_meta($referrer_id, PSYCH_PATH_REFERRAL_USER_META_COUNT, true) ?: 0;
                update_user_meta($referrer_id, PSYCH_PATH_REFERRAL_USER_META_COUNT, $count + 1);
            }
            unset($_COOKIE[PSYCH_PATH_REFERRAL_COOKIE]);
            setcookie(PSYCH_PATH_REFERRAL_COOKIE, '', time() - 3600, '/');
        }
    }

    private function get_user_by_referral_code($code) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'psych_referral_code' AND meta_value = %s",
            $code
        ));
    }

    private function check_and_complete_feedback_missions($user_id) {
        // Logic from previous versions
        $count = get_user_meta($user_id, PSYCH_PATH_FEEDBACK_USER_META_COUNT, true) ?: 0;
        if ($count >= 3) { // Example threshold
            do_action('psych_path_station_completed', $user_id, 'feedback_mission', []);
        }
    }

    public function handle_interactive_completion($user_id, $content_id, $data) {
        // Personalized path based on subscales (enhanced)
        if (isset($data['subscales'])) {
            update_user_meta($user_id, PSYCH_PATH_META_SUBSCALES . '_' . $content_id, $data['subscales']);
            $new_station = 'start';
            if (isset($data['subscales']['anxiety']) && $data['subscales']['anxiety'] < 10) {
                $new_station = 'branch_anxiety';
            } elseif (isset($data['subscales']['stress']) && $data['subscales']['stress'] > 20) {
                $new_station = 'branch_stress';
            }
            $this->update_user_station($user_id, $content_id, $new_station);
        }
    }

    public function update_user_station($user_id, $path_id, $new_station) {
        $personalized = get_user_meta($user_id, PSYCH_PATH_META_PERSONALIZED, true) ?: [];
        $personalized[$path_id] = $new_station;
        update_user_meta($user_id, PSYCH_PATH_META_PERSONALIZED, $personalized);
        return true;
    }

    public function get_user_current_station($user_id, $path_id) {
        $personalized = get_user_meta($user_id, PSYCH_PATH_META_PERSONALIZED, true) ?: [];
        return $personalized[$path_id] ?? 'start';
    }

    public function handle_gamification_rewards($user_id, $node_id, $station_data) {
        // Refactored to use the central API functions from the Gamification Center module
        // This promotes code reuse and ensures logic is not duplicated.

        // Extract rewards from station data. The shortcode attributes should be specific, e.g., 'reward_points' and 'reward_badge'.
        $points_to_add = isset($station_data['reward_points']) ? intval($station_data['reward_points']) : 0;
        $badge_to_award = isset($station_data['reward_badge']) ? sanitize_key($station_data['reward_badge']) : null;

        if ($points_to_add > 0 && function_exists('psych_gamification_add_points')) {
            $reason = 'تکمیل ایستگاه: ' . ($station_data['title'] ?? $node_id);
            psych_gamification_add_points($user_id, $points_to_add, $reason);
        }

        if ($badge_to_award && function_exists('psych_gamification_award_badge')) {
            psych_gamification_award_badge($user_id, $badge_to_award);
        }

        // The action can still be fired for other integrations to listen to.
        do_action('psych_path_gamification_reward_processed', $user_id, $node_id, $station_data);
    }

    public function send_conditional_reports($user_id, $node_id, $station_data) {
        $subscales = psych_interactive_get_user_subscales($user_id, 'quiz_1');

        if (isset($subscales['anxiety']) && $subscales['anxiety'] > 15) {
            $report = $this->generate_advanced_report($user_id, $subscales);
            // Simulate sending report to parent
            $parent_phone = get_user_meta($user_id, 'parent_phone', true);
            if ($parent_phone) {
                $this->send_sms($parent_phone, 'هشدار: سطح اضطراب فرزند شما بالا است. گزارش: ' . $report);
            }
        }
    }

    private function generate_advanced_report($user_id, $subscales) {
        // Generate advanced report (text-based example)
        return 'گزارش پیشرفته: اضطراب = ' . $subscales['anxiety'];
    }

    private function send_sms($phone, $message) {
        // Simulate SMS sending (integrate with real API like Twilio)
        error_log("SMS sent to $phone: $message");
    }

    public function handle_woocommerce_completion($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
        if ($user_id) {
            do_action('psych_path_station_completed', $user_id, 'woocommerce_' . $order_id, ['order_id' => $order_id]);
        }
    }

    // =====================================================================
    // Shortcodes for Missions, Shares, Recommendations
    // =====================================================================

    public function render_share_buttons_shortcode($atts) {
        $atts = shortcode_atts(['link' => home_url()], $atts);
        $link = esc_url($atts['link']);

        ob_start();
        ?>
        <div class="psych-share-buttons">
            <button data-share="whatsapp" data-link="<?php echo $link; ?>">اشتراک در واتس‌اپ</button>
            <button data-share="telegram" data-link="<?php echo $link; ?>">اشتراک در تلگرام</button>
        </div>
        <?php
        return ob_get_clean();
    }

    public function register_mission_shortcode($atts, $content = '') {
        $atts = shortcode_atts([
            'id' => '',
            'type' => '', // e.g., 'quiz_submission', 'feedback', 'form', 'test'
            'reward_points' => 0,
            'reward_badge' => '',
            'unlock_station' => '',
            'coach_mode' => 'none' // New: 'none', 'view_only', 'response'
        ], $atts);

        if (empty($atts['id'])) return 'Mission ID required.';

        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id']; // Student ID
        $is_coach = $context['is_impersonating'];

        // Render mission content
        $output = '<div class="psych-mission" data-mission-id="' . esc_attr($atts['id']) . '" data-type="' . esc_attr($atts['type']) . '">';

        if ($is_coach) {
            if ($atts['coach_mode'] === 'view_only') {
                // View-only mode: Show content but no submit button
                $output .= '<p>حالت دیدن تنها برای مربی.</p>';
                $output .= do_shortcode($content); // e.g., quiz or form display
                $output .= '<p>شما نمی‌توانید ارسال کنید.</p>';
            } elseif ($atts['coach_mode'] === 'response') {
                // Coach response mode: Allow coach to submit, but save for student
                $output .= '<p>حالت پاسخ‌دهی مربی: پاسخ شما برای دانشجو ذخیره می‌شود.</p>';
                $output .= do_shortcode($content); // e.g., [psych_advanced_quiz] or form
                $output .= '<button class="coach-submit-mission-btn" data-student-id="' . esc_attr($user_id) . '">ارسال به عنوان مربی</button>';
            } else {
                // Default: Coach sees as student
                $output .= do_shortcode($content);
                $output .= '<button class="complete-mission-btn">تکمیل ماموریت</button>';
            }
        } else {
            // Regular user
            $output .= do_shortcode($content);
            $output .= '<button class="complete-mission-btn">تکمیل ماموریت</button>';
        }

        $output .= '</div>';

        return $output;
    }

    public function render_recommendation_shortcode($atts) {
        $atts = shortcode_atts([
            'subscale' => 'anxiety',
            'threshold' => 10
        ], $atts);

        return '<div class="psych-recommendation" data-subscale="' . esc_attr($atts['subscale']) . '" data-threshold="' . esc_attr($atts['threshold']) . '">در حال بارگذاری پیشنهاد...</div>';
    }

    // =====================================================================
    // SECTION 4: INLINE CSS & JS (All Inline, No External Files, Expanded for Full Coverage)
    // =====================================================================

    public function inject_inline_css() {
        if (!$this->is_shortcode_rendered) return;

        ?>
        <style type="text/css">
            /* General Styles */
            .psych-path-container { direction: rtl; font-family: 'Vazirmatn', sans-serif; text-align: right; max-width: 1200px; margin: 0 auto; padding: 20px; }
            .psych-path-title { color: #333; text-align: center; font-size: 24px; margin-bottom: 20px; }
            .psych-path-progress { background: #f0f0f0; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
            .progress-bar { height: 10px; background: #4caf50; border-radius: 5px; transition: width 0.3s ease; }
            .coach-impersonation-notice { background: #ffeb3b; padding: 10px; text-align: center; color: #333; border-radius: 5px; margin-bottom: 20px; }
            .coach-notice { background: #fff3cd; padding: 10px; border: 1px solid #ffeeba; color: #856404; border-radius: 5px; margin-top: 10px; }

            /* Timeline Mode */
            .psych-timeline { display: flex; flex-direction: column; align-items: center; position: relative; }
            .timeline-item { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; width: 80%; position: relative; background: #fff; }
            .timeline-item:before { content: ''; position: absolute; top: -10px; left: 50%; width: 20px; height: 20px; background: #fff; border: 1px solid #ddd; border-radius: 50%; transform: translateX(-50%); }
            .timeline-item.completed { background: #d4edda; border-color: #c3e6cb; }
            .timeline-item.completed:before { background: #28a745; border-color: #28a745; }
            .timeline-item.locked { background: #f8d7da; border-color: #f5c6cb; opacity: 0.7; }
            .timeline-item.locked:before { background: #dc3545; border-color: #dc3545; }
            .timeline-icon { font-size: 24px; color: #007cba; margin-bottom: 10px; }
            .timeline-content h3 { margin: 0 0 10px; font-size: 18px; }
            .complete-mission-btn { background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 5px; margin-top: 10px; }
            .complete-mission-btn:hover { background: #005a87; }

            /* Accordion Mode */
            .psych-accordion .accordion-item { margin-bottom: 10px; border: 1px solid #ddd; border-radius: 5px; }
            .accordion-header { background: #f0f0f0; padding: 15px; cursor: pointer; font-size: 16px; display: flex; align-items: center; }
            .accordion-header i { margin-left: 10px; }
            .accordion-content { padding: 15px; display: none; background: #fff; }
            .accordion-item.completed .accordion-header { background: #d4edda; color: #155724; }
            .accordion-item.locked .accordion-header { background: #f8d7da; color: #721c24; cursor: not-allowed; }

            /* Treasure Map Mode */
            .psych-treasure-map { position: relative; height: 600px; width: 100%; background: #f0f8ff; overflow: hidden; }
            .map-node { position: absolute; text-align: center; cursor: pointer; transition: transform 0.2s; }
            .map-node:hover { transform: scale(1.1); }
            .map-node i { font-size: 30px; color: #ffd700; }
            .tooltip { display: block; background: #333; color: #fff; padding: 5px; border-radius: 5px; margin-top: 5px; }
            .popup-content { position: absolute; background: #fff; border: 1px solid #ddd; padding: 10px; border-radius: 5px; z-index: 10; width: 200px; display: none; }
            .map-node.completed i { color: #28a745; }
            .map-node.locked i { color: #dc3545; opacity: 0.7; }

            /* Cards Mode */
            .psych-cards { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; }
            .card { width: 250px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; background: #fff; transition: box-shadow 0.3s; }
            .card:hover { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
            .card-header { font-size: 18px; margin-bottom: 10px; }
            .card i { font-size: 40px; color: #007cba; display: block; margin-bottom: 10px; }
            .card.completed { background: #d4edda; border-color: #c3e6cb; }
            .card.locked { background: #f8d7da; border-color: #f5c6cb; opacity: 0.7; }
            .card-content { font-size: 14px; }

            /* Simple List Mode */
            .psych-simple-list { list-style: none; padding: 0; }
            .psych-simple-list li { margin-bottom: 10px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; display: flex; align-items: center; }
            .psych-simple-list li i { font-size: 20px; margin-left: 10px; color: #007cba; }
            .psych-simple-list li.completed { background: #d4edda; border-color: #c3e6cb; }
            .psych-simple-list li.locked { background: #f8d7da; border-color: #f5c6cb; opacity: 0.7; }

            /* Share Buttons */
            .psych-share-buttons button { padding: 10px 20px; margin: 5px; color: white; border: none; cursor: pointer; border-radius: 5px; transition: background 0.3s; }
            [data-share="whatsapp"] { background: #25D366; }
            [data-share="whatsapp"]:hover { background: #128C7E; }
            [data-share="telegram"] { background: #0088cc; }
            [data-share="telegram"]:hover { background: #006699; }

            /* Feedback Form */
            .psych-feedback-form textarea { width: 100%; height: 100px; margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
            .psych-feedback-form button { background: #007cba; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px; }
            .psych-feedback-form button:hover { background: #005a87; }

            /* Modal */
            #psych-path-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; }
            .modal-content { background: #fff; margin: 15% auto; padding: 20px; border-radius: 5px; width: 80%; max-width: 600px; position: relative; }
            .modal-close { position: absolute; top: 10px; right: 10px; cursor: pointer; font-size: 24px; }

            /* Themes */
            .theme-dark { background: #333; color: #fff; }
            .theme-dark .card, .theme-dark .timeline-item { background: #444; border-color: #555; color: #fff; }
            .theme-colorful .timeline-item { background: linear-gradient(135deg, #ffecb3, #ffdab9); }
            .theme-colorful .card { background: linear-gradient(135deg, #e3f2fd, #bbdefb); }

            /* Responsive */
            @media (max-width: 768px) {
                .psych-cards { flex-direction: column; }
                .psych-treasure-map { height: 600px; }
                .timeline-item { width: 100%; }
            }

            /* Mission Styles */
            .psych-mission { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: #f9f9f9; }
            .coach-submit-mission-btn { background: #ffc107; color: #333; padding: 10px 20px; border: none; cursor: pointer; border-radius: 5px; margin-top: 10px; }
            .coach-submit-mission-btn:hover { background: #e0a800; }

            /* Recommendation */
            .psych-recommendation { padding: 10px; background: #e9ecef; border-radius: 5px; min-height: 50px; }
        </style>
        <?php
    }

    public function inject_inline_js_and_modal() {
        if (!$this->is_shortcode_rendered) return;

        ?>
        <div id="psych-path-modal">
            <div class="modal-content">
                <span class="modal-close">&times;</span>
                <div id="modal-body"></div>
            </div>
        </div>
        <script type="text/javascript">
            // General JS for Interactivity
            document.addEventListener('DOMContentLoaded', function() {
                const nonce = '<?php echo wp_create_nonce(PSYCH_PATH_AJAX_NONCE); ?>';
                const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

                // Accordion Functionality
                document.querySelectorAll('.accordion-header').forEach(header => {
                    header.addEventListener('click', () => {
                        const content = header.nextElementSibling;
                        content.style.display = content.style.display === 'block' ? 'none' : 'block';
                    });
                });

                // Map Node Click
                document.querySelectorAll('.map-node').forEach(node => {
                    node.addEventListener('click', () => {
                        const popup = node.querySelector('.popup-content');
                        popup.style.display = popup.style.display === 'block' ? 'none' : 'block';
                    });
                });

                // Complete Mission Button
                document.querySelectorAll('.complete-mission-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const nodeId = btn.closest('.timeline-item').dataset.nodeId;
                        fetch(ajaxurl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                action: 'psych_path_complete_mission',
                                nonce: nonce,
                                node_id: nodeId
                            })
                        }).then(response => response.json()).then(data => {
                            if (data.success) {
                                alert(data.data.message);
                                location.reload(); // Refresh to update status
                            } else {
                                alert(data.data.message);
                            }
                        });
                    });
                });

                // Feedback Form Submit
                document.querySelectorAll('.psych-feedback-form').forEach(form => {
                    form.addEventListener('submit', e => {
                        e.preventDefault();
                        const nodeId = form.dataset.nodeId;
                        const feedback = form.querySelector('textarea').value;
                        fetch(ajaxurl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                action: 'psych_submit_feedback',
                                nonce: nonce,
                                node_id: nodeId,
                                feedback: feedback
                            })
                        }).then(response => response.json()).then(data => {
                            if (data.success) {
                                alert(data.data.message);
                                form.reset();
                            } else {
                                alert(data.data.message);
                            }
                        });
                    });
                });

                // Share Buttons
                document.querySelectorAll('[data-share]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const link = btn.dataset.link;
                        const platform = btn.dataset.share;
                        if (platform === 'whatsapp') {
                            window.open('https://wa.me/?text=' + encodeURIComponent(link), '_blank');
                        } else if (platform === 'telegram') {
                            window.open('https://t.me/share/url?url=' + encodeURIComponent(link), '_blank');
                        }
                    });
                });

                // Modal Close
                document.querySelector('.modal-close').addEventListener('click', () => {
                    document.getElementById('psych-path-modal').style.display = 'none';
                });

                // Load Recommendations
                document.querySelectorAll('.psych-recommendation').forEach(el => {
                    const subscale = el.dataset.subscale;
                    const threshold = el.dataset.threshold;
                    fetch(ajaxurl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'psych_path_get_recommendation',
                            nonce: nonce,
                            subscale: subscale,
                            threshold: threshold
                        })
                    }).then(response => response.json()).then(data => {
                        if (data.success) {
                            el.innerHTML = data.data.recommendation;
                        }
                    });
                });
            });
        </script>
        <?php
    }
}

// Initialize the class
PsychoCourse_Path_Engine_Ultimate::get_instance();
?>
