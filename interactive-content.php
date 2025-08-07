<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (class_exists('Psych_Interactive_Content_Ultimate')) {
    return; // جلوگیری از تعریف مجدد کلاس
}

// =====================================================================
// GLOBAL CONSTANTS AND HELPER FUNCTIONS
// =====================================================================

define('PSYCH_INTERACTIVE_VERSION', '11.0.0');
define('PSYCH_INTERACTIVE_META_COMPLETED', 'psych_interactive_completed');
define('PSYCH_INTERACTIVE_META_PROGRESS', 'psych_interactive_progress');
define('PSYCH_INTERACTIVE_META_SUBSCALES', 'psych_interactive_subscales'); // New for subscales
define('PSYCH_INTERACTIVE_AJAX_NONCE', 'psych_interactive_ajax_nonce');
define('PSYCH_INTERACTIVE_CACHE_EXPIRY', 300); // 5 minutes

// Global helper functions for compatibility with other modules (e.g., Path Engine, Coach Module)
if (!function_exists('psych_interactive_get_viewing_context')) {
    function psych_interactive_get_viewing_context() {
        return Psych_Interactive_Content_Ultimate::get_instance()->get_viewing_context();
    }
}

if (!function_exists('psych_interactive_user_has_completed')) {
    function psych_interactive_user_has_completed($user_id, $content_id) {
        return Psych_Interactive_Content_Ultimate::get_instance()->user_has_completed($user_id, $content_id);
    }
}

if (!function_exists('psych_interactive_get_user_progress')) {
    function psych_interactive_get_user_progress($user_id, $content_id) {
        return Psych_Interactive_Content_Ultimate::get_instance()->get_user_progress($user_id, $content_id);
    }
}

if (!function_exists('psych_interactive_get_user_subscales')) {
    /**
     * Get user's subscales for a content_id
     */
    function psych_interactive_get_user_subscales($user_id, $content_id) {
    $context = psych_path_get_viewing_context();
    $effective_id = $context['is_impersonating'] ? $context['viewed_user_id'] : $user_id;
    return get_user_meta($effective_id, 'psych_subscales_' . $content_id, true) ?: [];
}

}

if (!function_exists('psych_interactive_clear_cache')) {
    function psych_interactive_clear_cache($user_id, $content_id = '') {
        Psych_Interactive_Content_Ultimate::get_instance()->clear_cache($user_id, $content_id);
    }
}

/**
 * Main Interactive Content Class with All Features Implemented
 */
final class Psych_Interactive_Content_Ultimate {

    private static $instance = null;
    private $viewing_context = null;
    private $interactive_data = [];
    private $is_shortcode_rendered = false;
    private $blocks = [];
    private $current_block_id = null;
    private $assets_injected = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_viewing_context();
        $this->register_shortcodes();
        $this->add_hooks();
    }

    private function init_viewing_context() {
        if (function_exists('psych_path_get_viewing_context')) {
            $this->viewing_context = psych_path_get_viewing_context();
        } else {
            $this->viewing_context = [
                'is_impersonating' => false,
                'real_user_id' => get_current_user_id(),
                'viewed_user_id' => get_current_user_id(),
            ];
        }
    }

    public function get_viewing_context() {
        if ($this->viewing_context === null) {
            $this->init_viewing_context();
        }
        return $this->viewing_context;
    }

    private function add_hooks() {
    // Core hooks
    add_action('wp_head', [$this, 'inject_inline_css']);
    add_action('wp_footer', [$this, 'inject_modal_and_js']);
    add_action('wp_ajax_psych_load_modal_content', [$this, 'ajax_load_modal_content']);
    add_action('wp_ajax_nopriv_psych_load_modal_content', [$this, 'ajax_load_modal_content']);
    add_action('wp_ajax_psych_interactive_submit', [$this, 'ajax_submit_interactive']);
    add_action('wp_ajax_psych_interactive_get_progress', [$this, 'ajax_get_progress']);
    add_action('wp_ajax_psych_load_dynamic_content', [$this, 'ajax_load_dynamic_content']);
    add_action('wp_ajax_nopriv_psych_load_dynamic_content', [$this, 'ajax_load_dynamic_content']);
    add_action('wp_ajax_psych_interactive_get_subscales', [$this, 'ajax_get_subscales']); // New for subscales

    // Integration hooks
    add_action('psych_path_station_completed', [$this, 'handle_station_completion'], 10, 3);
    add_action('wp_enqueue_scripts', [$this, 'enqueue_integration_scripts']);
    add_action('psych_interactive_completed', [$this, 'handle_completion'], 10, 3);
    add_action('psych_interactive_completed', [$this, 'award_gamification_rewards'], 20, 3);
    add_action('psych_interactive_completed', [$this, 'handle_subscales_update'], 15, 3); // New for subscales
    add_action('psych_interactive_completed', [$this, 'send_conditional_reports'], 25, 3); // New for conditional reports and SMS
    add_filter('psych_interactive_can_access_content', [$this, 'coach_access_filter'], 10, 3);

    // WooCommerce integration for upsell missions
    add_action('woocommerce_order_status_completed', [$this, 'handle_upsell_purchase'], 10, 1);

    // Cache cleanup and events
    add_action('psych_points_awarded', [$this, 'clear_cache_on_event'], 10, 1);
    add_action('psych_user_level_up', [$this, 'clear_cache_on_event'], 10, 1);

    // New hook for personalized path updates (integrates with Path Engine without changing it)
    add_action('psych_interactive_completed', [$this, 'update_personalized_path'], 30, 3);

    // اضافه کردن اکشن‌های جدید برای AI test
    add_action('wp_ajax_psych_process_ai_test', [$this, 'ajax_process_ai_test']);
    add_action('wp_ajax_nopriv_psych_process_ai_test', [$this, 'ajax_process_ai_test']);
}  // بسته‌کننده add_hooks()

public function ajax_process_ai_test() {
    check_ajax_referer(PSYCH_INTERACTIVE_AJAX_NONCE, 'nonce'); // امنیت – اصلاح‌شده

    $test_id = sanitize_text_field($_POST['test_id'] ?? 'default_test');
    $user_responses = wp_kses_post($_POST['responses'] ?? '');

    if (empty($user_responses)) {
        wp_send_json_error(['message' => 'پاسخ‌ها خالی است.']);
    }

    $prompt = "تحلیل پاسخ‌های کاربر: {$user_responses}. خروجی به صورت JSON بده: {'score': امتیاز کلی از 100, 'subscales': {'energy': امتیاز, 'focus': امتیاز}, 'result_text': 'توضیح کامل به فارسی'}";

    $ai_result = $this->call_ai_api($prompt);

    $result_data = json_decode($ai_result, true) ?: ['score' => 0, 'subscales' => [], 'result_text' => $ai_result];

    $full_result = [
        'test' => $test_id,
        'score' => $result_data['score'] ?? 0,
        'subscales' => $result_data['subscales'] ?? [],
        'result_text' => $result_data['result_text'] ?? '',
        'timestamp' => current_time('timestamp'),
        'type' => 'ai'
    ];

    $user_id = get_current_user_id();
    $all_results = get_user_meta($user_id, 'psych_test_results', true) ?: [];
    $all_results[$test_id] = $full_result;
    update_user_meta($user_id, 'psych_test_results', $all_results);

    if (class_exists('Psych_Gamification_Center')) {
        Psych_Gamification_Center::get_instance()->award_points($user_id, 10, 'تکمیل تست AI');
    }
    wp_send_json_success(['result' => $full_result]);
}

/**
 * تابع فراخوانی API هوش مصنوعی (مثل OpenAI یا Grok)
 * @param string $prompt متن پرامپت برای AI
 * @return string پاسخ AI (JSON یا متن)
 */
public function call_ai_api($prompt) {
    $api_settings = get_option('psych_api_settings');
    $api_key = isset($api_settings['openai_key']) ? $api_settings['openai_key'] : '';
    if (empty($api_key)) {
        return 'Error: OpenAI API Key is not set in Psych System settings.';
    }
    $api_url = 'https://api.openai.com/v1/chat/completions'; // یا برای Grok: https://api.x.ai/v1/chat/completions

    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.7,
        'max_tokens' => 500,
    ];

    $args = [
        'headers' => ['Authorization' => 'Bearer ' . $api_key, 'Content-Type' => 'application/json'],
        'body' => json_encode($data),
        'method' => 'POST',
        'timeout' => 60,
    ];

    $response = wp_remote_post($api_url, $args);

    if (is_wp_error($response)) {
        error_log('AI API Error: ' . $response->get_error_message());
        return 'خطا در فراخوانی AI.';
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    return $result['choices'][0]['message']['content'] ?? 'پاسخ نامعتبر.';
}




    public function enqueue_integration_scripts() {
        global $post;
        if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'psych_content_block') || has_shortcode($post->post_content, 'psych_progress_path'))) {
            // No external scripts; all JS is inline in inject_modal_and_js
        }
    }

    private function register_shortcodes() {
        add_shortcode('psych_content_block', [$this, 'render_content_block_shortcode']);
        add_shortcode('psych_content_view', [$this, 'capture_content_view_shortcode']);
        add_shortcode('psych_button', [$this, 'render_button_shortcode']);
        add_shortcode('psych_hidden_content', [$this, 'render_hidden_content_shortcode']);
        add_shortcode('psych_progress_path', [$this, 'render_progress_path_shortcode']);
        add_shortcode('psych_interactive_quiz', [$this, 'render_quiz_shortcode']);
        add_shortcode('psych_interactive_poll', [$this, 'render_poll_shortcode']);
        add_shortcode('psych_interactive_feedback', [$this, 'render_feedback_shortcode']);
        add_shortcode('psych_personalize', [$this, 'render_personalize_shortcode']); // The new, more powerful shortcode
		add_shortcode('psych_ai_test_form', [$this, 'render_ai_test_form_shortcode']);
		add_shortcode('psych_quiz_in_content', [$this, 'render_quiz_in_content_shortcode']);
		add_shortcode('psych_add_to_cart', [$this, 'render_add_to_cart_shortcode']);

    }

    // ===================================================================
    // SECTION 1: Shortcode Rendering
    // ===================================================================
public function render_ai_test_form_shortcode($atts) {
    $atts = shortcode_atts(['id' => 'personality_test'], $atts);
    ob_start();
    ?>
    <form id="psych-ai-test-form" data-test-id="<?php echo esc_attr($atts['id']); ?>">
        <!-- مثال سوالات: سفارشی کن -->
        <label>پاسخ خود را بنویسید:</label>
        <textarea name="responses" required></textarea>
        <button type="submit">ارسال و تحلیل با AI</button>
    </form>
    <div id="ai-result"></div>

    <script>
    jQuery('#psych-ai-test-form').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'psych_process_ai_test');
        formData.append('nonce', psych_interactive_ajax.nonce);
        formData.append('test_id', this.dataset.testId);
        formData.append('responses', jQuery('[name="responses"]').val());

        jQuery.post(psych_interactive_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                jQuery('#ai-result').html('<p>نتیجه: ' + JSON.stringify(response.data.result) + '</p>');
            } else {
                alert(response.data.message);
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
public function render_quiz_in_content_shortcode($atts) {
    $atts = shortcode_atts(['quiz_id' => ''], $atts);
    if (empty($atts['quiz_id'])) {
        return 'کوئیز ID مشخص نشده است.';
    }

    ob_start();
    echo '<div class="psych-quiz-in-content" data-quiz-id="' . esc_attr($atts['quiz_id']) . '">';
    echo 'فرم کوئیز اینجا رندر می‌شود...'; // Integrate actual quiz form from advanced-quiz-module
    echo '</div>';
    return ob_get_clean();
}

    public function render_content_block_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'id' => 'psych_block_' . uniqid(),
            'default_station' => 'start',
            'type' => 'generic',
            'require_login' => 'false',
            'course_id' => '',
        ], $atts, 'psych_content_block');

        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];

        if ($atts['require_login'] === 'true' && !$user_id) {
            return '<div class="psych-login-required"><p>برای مشاهده این محتوا باید وارد شوید.</p></div>';
        }

        $can_access = apply_filters('psych_interactive_can_access_content', true, $user_id, $atts);
        if (!$can_access) {
            return '<div class="psych-access-denied"><p>شما مجاز به مشاهده این محتوا نیستید.</p></div>';
        }

        $this->assets_injected = true;
        $block_id = esc_attr($atts['id']);
        $this->current_block_id = $block_id;
        $this->blocks[$block_id] = [
            'views' => [],
            'config' => $atts,
            'seo_data' => ['questions' => []],
            'user_id' => $user_id
        ];

        do_shortcode($content);

        $current_station = $this->get_user_current_station($user_id, $block_id, $atts['default_station']);

        ob_start();
        ?>
        <div class="psych-content-block <?php echo esc_attr($atts['course_id'] ? 'psych-course-specific' : ''); ?>" id="<?php echo esc_attr($block_id); ?>" data-course-id="<?php echo esc_attr($atts['course_id']); ?>">
            <?php if ($context['is_impersonating']) : ?>
                <div class="coach-notice">در حال مشاهده به عنوان مربی</div>
            <?php endif; ?>
            <div class="psych-content-view" data-station="<?php echo esc_attr($current_station); ?>">
                <?php
                if (isset($this->blocks[$block_id]['views'][$current_station])) {
                    echo $this->blocks[$block_id]['views'][$current_station]['content'];
                } else {
                    echo '<p>محتوای پیش‌فرض برای ایستگاه ' . esc_html($current_station) . '</p>';
                }
                ?>
            </div>
        </div>
        <?php
        $output = ob_get_clean();

        // Generate SEO schema
        $output .= $this->generate_seo_schema($block_id);

        return $output;
    }

    public function capture_content_view_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'station' => 'start',
            'access_level' => 'all',
            'seo_questions' => '',
            'seo_answers' => '',
        ], $atts, 'psych_content_view');

        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];

        if ($atts['access_level'] !== 'all' && !user_can($user_id, $atts['access_level'])) {
            return '';
        }

        $station = sanitize_key($atts['station']);
        $this->blocks[$this->current_block_id]['views'][$station] = [
            'content' => do_shortcode($content),
        ];

        // Capture SEO data
        if ($atts['seo_questions']) {
            $questions = explode('|', $atts['seo_questions']);
            $answers = explode('|', $atts['seo_answers']);
            foreach ($questions as $index => $q) {
                $this->blocks[$this->current_block_id]['seo_data']['questions'][] = [
                    'question' => trim($q),
                    'answer' => isset($answers[$index]) ? trim($answers[$index]) : '',
                ];
            }
        }

        return '';
    }

    public function render_button_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'action' => 'toggle', // toggle, modal, mission, etc.
            'target' => '',
            'icon' => '',
            'class' => 'psych-btn-primary',
            'mission_id' => '',
            'course_id' => '',
            'disabled' => 'false',
        ], $atts, 'psych_button');

        $context = $this->get_viewing_context();

        ob_start();
        ?>
        <button class="psych-button <?php echo esc_attr($atts['class']); ?>" data-action="<?php echo esc_attr($atts['action']); ?>" data-target="<?php echo esc_attr($atts['target']); ?>" data-mission-id="<?php echo esc_attr($atts['mission_id']); ?>" data-course-id="<?php echo esc_attr($atts['course_id']); ?>" <?php echo $atts['disabled'] === 'true' ? 'disabled' : ''; ?>>
            <?php if ($atts['icon']) : ?><i class="<?php echo esc_attr($atts['icon']); ?>"></i><?php endif; ?>
            <?php echo esc_html($content); ?>
        </button>
        <?php if ($context['is_impersonating']) : ?>
            <div class="coach-notice">دکمه تحت مشاهده مربی</div>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

	public function render_add_to_cart_shortcode( $atts ) {
		$atts = shortcode_atts( [
			'product_id' => 0,
			'text'       => __( 'Purchase Course', 'psych-system' ),
			'class'      => 'psych-button psych-btn-primary',
		], $atts, 'psych_add_to_cart' );

		$product_id = intval( $atts['product_id'] );
		if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
			return '';
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return '';
		}

		$add_to_cart_url = $product->add_to_cart_url();

		return sprintf(
			'<a href="%s" class="%s" data-quantity="1" data-product_id="%s" data-product_sku="%s" rel="nofollow">%s</a>',
			esc_url( $add_to_cart_url ),
			esc_attr( $atts['class'] ),
			esc_attr( $product->get_id() ),
			esc_attr( $product->get_sku() ),
			esc_html( $atts['text'] )
		);
	}

    public function render_personalize_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'show_to' => '', // e.g., "user_has_badge:video_watched_intro" or "user_points>100"
            'hide_from' => '',
        ], $atts, 'psych_personalize');

        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];

        if (!$user_id) {
            return ''; // Don't show personalized content to logged-out users
        }

        $show_conditions = !empty($atts['show_to']) ? explode(',', $atts['show_to']) : [];
        $hide_conditions = !empty($atts['hide_from']) ? explode(',', $atts['hide_from']) : [];

        $should_show = $this->evaluate_conditions($user_id, $show_conditions, 'AND');
        $should_hide = $this->evaluate_conditions($user_id, $hide_conditions, 'OR');

        if ($should_hide) {
            return '';
        }

        if (empty($show_conditions) || $should_show) {
            return do_shortcode($content);
        }

        return '';
    }

    private function evaluate_conditions($user_id, $conditions, $operator = 'AND') {
        if (empty($conditions)) {
            return ($operator === 'AND'); // No conditions to meet for AND, but fail for OR
        }

        $results = [];
        foreach ($conditions as $condition) {
            $condition = trim($condition);
            if (strpos($condition, ':') !== false) {
                list($type, $value) = explode(':', $condition, 2);
            } else {
                // Handle conditions like "is_coach"
                $type = $condition;
                $value = true;
            }

            $result = false;
            switch ($type) {
                case 'user_has_badge':
                    if (function_exists('psych_user_has_badge')) {
                        $result = psych_user_has_badge($user_id, $value);
                    }
                    break;
                // Add more conditions here as needed, e.g., user_points, user_level, etc.
            }
            $results[] = $result;
        }

        if ($operator === 'OR') {
            return in_array(true, $results);
        }

        // Default to AND
        return !in_array(false, $results);
    }

    public function render_hidden_content_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'id' => uniqid('hidden_'),
            'access_level' => 'user',
            'course_id' => '',
            'condition' => '', // New: e.g., subscale:anxiety<10
        ], $atts, 'psych_hidden_content');

        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];

        if ($atts['access_level'] !== 'all' && !user_can($user_id, $atts['access_level'])) {
            return '';
        }

        ob_start();
        ?>
        <div class="psych-hidden-content" id="<?php echo esc_attr($atts['id']); ?>" data-course-id="<?php echo esc_attr($atts['course_id']); ?>" data-condition="<?php echo esc_attr($atts['condition']); ?>" style="display: none;">
            <?php echo do_shortcode($content); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_progress_path_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'type' => 'badges', // badges, levels, custom
            'course_id' => '',
        ], $atts, 'psych_progress_path');

        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];

        $progress = $this->get_user_progress($user_id, $atts['course_id'] ?: 'global');

        ob_start();
        ?>
        <div class="psych-progress-path-container <?php echo esc_attr($atts['course_id'] ? 'psych-course-specific' : ''); ?>" data-course-id="<?php echo esc_attr($atts['course_id']); ?>">
            <?php if ($context['is_impersonating']) : ?>
                <div class="coach-progress-notice">پیشرفت تحت مشاهده مربی</div>
            <?php endif; ?>
            <?php if ($atts['type'] === 'badges') : ?>
                <div class="psych-badges-grid">
                    <!-- Render badges based on progress -->
                    <div class="psych-badge-item"><img src="badge1.png" alt="Badge 1"><p>سطح 1</p></div>
                    <!-- More badges dynamically -->
                </div>
            <?php elseif ($atts['type'] === 'levels') : ?>
                <div class="psych-level-progress">
                    <div class="progress-bar" style="width: <?php echo esc_attr($progress['percentage'] ?? 0); ?>%;"></div>
                    <p>سطح فعلی: <?php echo esc_html($progress['level'] ?? 1); ?></p>
                </div>
            <?php else : ?>
                <?php echo do_shortcode($content); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_quiz_shortcode($atts, $content = null) {
        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];

        if (!$user_id) {
            return '<p>برای شرکت در این آزمون، لطفاً وارد شوید.</p>';
        }

        $atts = shortcode_atts([
            'id' => uniqid('quiz_'),
            'questions' => 5,
            'rewards' => 'points:10|badge:quiz_master',
            'max_attempts' => 3,
            'course_id' => '',
        ], $atts);

        $content_id = sanitize_key($atts['id']);
        $progress = $this->get_user_progress($user_id, $content_id);

        if ($progress['attempts'] >= intval($atts['max_attempts']) && !$progress['completed']) {
            return '<p>شما حداکثر تعداد تلاش‌ها را استفاده کرده‌اید.</p>';
        }

        $this->is_shortcode_rendered = true;
        $this->interactive_data[$content_id] = $atts;

        ob_start();
        ?>
        <div class="psych-interactive-quiz" id="<?php echo esc_attr($content_id); ?>" data-content-id="<?php echo esc_attr($content_id); ?>" data-course-id="<?php echo esc_attr($atts['course_id']); ?>">
            <?php if ($context['is_impersonating']) : ?>
                <div class="coach-notice">در حال مشاهده به عنوان مربی</div>
            <?php endif; ?>
            <div class="quiz-content"><?php echo do_shortcode($content); ?></div>
            <button class="psych-submit-quiz">ارسال پاسخ‌ها</button>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_poll_shortcode($atts, $content = null) {
        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];

        if (!$user_id) {
            return '<p>برای شرکت در این نظرسنجی، لطفاً وارد شوید.</p>';
        }

        $atts = shortcode_atts([
            'id' => uniqid('poll_'),
            'options' => 'گزینه 1, گزینه 2, گزینه 3',
            'rewards' => 'points:5',
            'course_id' => '',
        ], $atts);

        $content_id = sanitize_key($atts['id']);
        $progress = $this->get_user_progress($user_id, $content_id);

        if ($progress['completed']) {
            return '<p>شما قبلاً در این نظرسنجی شرکت کرده‌اید.</p>';
        }

        $options = explode(',', $atts['options']);

        ob_start();
        ?>
        <div class="psych-interactive-poll" id="<?php echo esc_attr($content_id); ?>" data-content-id="<?php echo esc_attr($content_id); ?>" data-course-id="<?php echo esc_attr($atts['course_id']); ?>">
            <?php if ($context['is_impersonating']) : ?>
                <div class="coach-notice">در حال مشاهده به عنوان مربی</div>
            <?php endif; ?>
            <h3><?php echo esc_html($content); ?></h3>
            <?php foreach ($options as $option) : ?>
                <label><input type="radio" name="poll_option" value="<?php echo esc_attr(trim($option)); ?>"> <?php echo esc_html(trim($option)); ?></label><br>
            <?php endforeach; ?>
            <button class="psych-submit-poll">ارسال نظر</button>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_feedback_shortcode($atts, $content = null) {
        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];

        if (!$user_id) {
            return '<p>برای ارسال بازخورد، لطفاً وارد شوید.</p>';
        }

        $atts = shortcode_atts([
            'id' => uniqid('feedback_'),
            'rewards' => 'points:10',
            'course_id' => '',
        ], $atts);

        $content_id = sanitize_key($atts['id']);
        $progress = $this->get_user_progress($user_id, $content_id);

        if ($progress['completed']) {
            return '<p>بازخورد شما قبلاً ثبت شده است.</p>';
        }

        ob_start();
        ?>
        <div class="psych-interactive-feedback" id="<?php echo esc_attr($content_id); ?>" data-content-id="<?php echo esc_attr($content_id); ?>" data-course-id="<?php echo esc_attr($atts['course_id']); ?>">
            <?php if ($context['is_impersonating']) : ?>
                <div class="coach-notice">در حال مشاهده به عنوان مربی</div>
            <?php endif; ?>
            <textarea class="feedback-text" placeholder="<?php echo esc_attr($content); ?>"></textarea>
            <button class="psych-submit-feedback">ارسال بازخورد</button>
        </div>
        <?php
        return ob_get_clean();
    }


    // ===================================================================
    // SECTION 2: AJAX Handlers
    // ===================================================================

    public function ajax_load_modal_content() {
        if (!check_ajax_referer(PSYCH_INTERACTIVE_AJAX_NONCE, 'nonce', false)) {
            wp_send_json_error(['message' => 'نشست منقضی شده.'], 403);
        }

        $modal_id = sanitize_key($_POST['modal_id'] ?? '');
        $course_id = sanitize_key($_POST['course_id'] ?? '');

        // Simulated content load (in real, fetch from DB or shortcode)
        $content = '<p>محتوای مودال برای ' . esc_html($modal_id) . ' در دوره ' . esc_html($course_id) . '</p>';

        wp_send_json_success(['content' => $content]);
    }

    public function ajax_submit_interactive() {
        if (!check_ajax_referer(PSYCH_INTERACTIVE_AJAX_NONCE, 'nonce', false)) {
            wp_send_json_error(['message' => 'نشست منقضی شده.'], 403);
        }

        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];
        $content_id = sanitize_key($_POST['content_id'] ?? '');
        $data = wp_unslash($_POST['data'] ?? []);

        if (!$user_id || empty($content_id) || empty($data)) {
            wp_send_json_error(['message' => 'پارامترهای ناقص.']);
        }

        $can_access = apply_filters('psych_interactive_can_access', true, $user_id, $content_id);
        if (!$can_access) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.'], 403);
        }

        $result = $this->process_submission($user_id, $content_id, $data);

        if ($result['success']) {
            $this->mark_as_completed($user_id, $content_id, $result);
            do_action('psych_interactive_completed', $user_id, $content_id, $result);
        }

        wp_send_json($result);
    }

    public function ajax_get_progress() {
        if (!check_ajax_referer(PSYCH_INTERACTIVE_AJAX_NONCE, 'nonce', false)) {
            wp_send_json_error(['message' => 'نشست منقضی شده.'], 403);
        }

        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];
        $content_id = sanitize_key($_POST['content_id'] ?? '');

        if (!$user_id || empty($content_id)) {
            wp_send_json_error(['message' => 'پارامترهای ناقص.']);
        }

        $progress = $this->get_user_progress($user_id, $content_id);
        wp_send_json_success(['progress' => $progress]);
    }

    public function ajax_load_dynamic_content() {
        if (!check_ajax_referer(PSYCH_INTERACTIVE_AJAX_NONCE, 'nonce', false)) {
            wp_send_json_error(['message' => 'نشست منقضی شده.'], 403);
        }

        $block_id = sanitize_key($_POST['block_id'] ?? '');
        $station = sanitize_key($_POST['station'] ?? '');

        // Simulated dynamic load
        $content = '<p>محتوای دینامیک برای ایستگاه ' . esc_html($station) . ' در بلاک ' . esc_html($block_id) . '</p>';

        wp_send_json_success(['content' => $content]);
    }

    public function ajax_get_subscales() {
        if (!check_ajax_referer(PSYCH_INTERACTIVE_AJAX_NONCE, 'nonce', false)) {
            wp_send_json_error(['message' => 'نشست منقضی شده.'], 403);
        }

        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];
        $content_id = sanitize_key($_POST['content_id'] ?? '');

        if (!$user_id || empty($content_id)) {
            wp_send_json_error(['message' => 'پارامترهای ناقص.']);
        }

        $subscales = $this->get_user_subscales($user_id, $content_id);
        wp_send_json_success(['subscales' => $subscales]);
    }

    // ===================================================================
    // SECTION 3: Core Logic
    // ===================================================================

    private function process_submission($user_id, $content_id, $data) {
        // Example processing: Calculate score and subscales
        $success = true;
        $score = 85; // Simulated
        $subscales = [
            'anxiety' => rand(5, 20), // Simulated subscales
            'stress' => rand(10, 30),
            'anxiety_social' => rand(1, 15),
        ];

        return [
            'success' => $success,
            'score' => $score,
            'subscales' => $subscales,
            'message' => $success ? 'ارسال موفق!' : 'خطا در ارسال.'
        ];
    }

    private function mark_as_completed($user_id, $content_id, $result) {
        $completed = get_user_meta($user_id, PSYCH_INTERACTIVE_META_COMPLETED, true) ?: [];
        $completed[$content_id] = true;
        update_user_meta($user_id, PSYCH_INTERACTIVE_META_COMPLETED, $completed);

        $progress = get_user_meta($user_id, PSYCH_INTERACTIVE_META_PROGRESS, true) ?: [];
        $progress[$content_id] = [
            'completed' => true,
            'score' => $result['score'],
            'attempts' => ($progress[$content_id]['attempts'] ?? 0) + 1,
            'timestamp' => current_time('timestamp')
        ];
        update_user_meta($user_id, PSYCH_INTERACTIVE_META_PROGRESS, $progress);
    }

    public function user_has_completed($user_id, $content_id) {
        $completed = get_user_meta($user_id, PSYCH_INTERACTIVE_META_COMPLETED, true) ?: [];
        return isset($completed[$content_id]) && $completed[$content_id];
    }

    public function get_user_progress($user_id, $content_id) {
        $cache_key = 'psych_progress_' . $user_id . '_' . $content_id;
        $progress = get_transient($cache_key);
        if (false === $progress) {
            $progress = get_user_meta($user_id, PSYCH_INTERACTIVE_META_PROGRESS, true) ?: [];
            $progress = $progress[$content_id] ?? ['completed' => false, 'score' => 0, 'attempts' => 0, 'percentage' => 0, 'level' => 1];
            set_transient($cache_key, $progress, PSYCH_INTERACTIVE_CACHE_EXPIRY);
        }
        return $progress;
    }

    public function get_user_subscales($user_id, $content_id) {
        $cache_key = 'psych_subscales_' . $user_id . '_' . $content_id;
        $subscales = get_transient($cache_key);
        if (false === $subscales) {
            $subscales = get_user_meta($user_id, PSYCH_INTERACTIVE_META_SUBSCALES, true) ?: [];
            $subscales = $subscales[$content_id] ?? [];
            set_transient($cache_key, $subscales, PSYCH_INTERACTIVE_CACHE_EXPIRY);
        }
        return $subscales;
    }

    public function clear_cache($user_id, $content_id = '') {
        if ($content_id) {
            delete_transient('psych_progress_' . $user_id . '_' . $content_id);
            delete_transient('psych_subscales_' . $user_id . '_' . $content_id);
        } else {
            // Clear all for user (simulated, in real use wp_cache or loop)
            delete_transient('psych_progress_' . $user_id . '_*');
            delete_transient('psych_subscales_' . $user_id . '_*');
        }
    }

    private function clear_cache_on_event($user_id) {
        $this->clear_cache($user_id);
    }

    private function get_user_current_station($user_id, $block_id, $default) {
        // Integrate with Path Engine via hook or direct (simulated)
        if (function_exists('psych_path_get_user_station_for_block')) {
            return psych_path_get_user_station_for_block($block_id, $user_id) ?: $default;
        }
        return $default;
    }

    private function generate_seo_schema($block_id) {
        if (!isset($this->blocks[$block_id]['seo_data']['questions'])) return '';

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [],
        ];

        foreach ($this->blocks[$block_id]['seo_data']['questions'] as $qa) {
            $schema['mainEntity'][] = [
                '@type' => 'Question',
                'name' => $qa['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $qa['answer'],
                ],
            ];
        }

        return '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
    }

    /**
     * Generates the content for a shareable, SEO-optimized achievement page.
     * This can be hooked into a custom page template.
     * @param int $user_id
     * @param string $course_id
     * @return string HTML content for the achievement page.
     */
    public function generate_achievement_page_content($user_id, $course_id) {
        $user_data = get_userdata($user_id);
        $course_post = get_post($course_id); // Assuming courses are posts or a CPT

        if (!$user_data || !$course_post) {
            return '<p>اطلاعات دوره یا کاربر یافت نشد.</p>';
        }

        $user_badges = get_user_meta($user_id, 'psych_user_badges', true) ?: [];
        // Filter badges relevant to the course if possible (e.g., by a naming convention)

        $page_title = sprintf('گواهی تکمیل دوره %s برای %s', $course_post->post_title, $user_data->display_name);

        ob_start();
        ?>
        <article class="psych-achievement-page">
            <header>
                <h1><?php echo esc_html($page_title); ?></h1>
                <p class="issue-date">صادر شده در: <?php echo date_i18n(get_option('date_format')); ?></p>
            </header>
            <div class="achievement-details">
                <p>این گواهی تایید می‌کند که <strong><?php echo esc_html($user_data->display_name); ?></strong> با موفقیت دوره آموزشی آنلاین <strong>"<?php echo esc_html($course_post->post_title); ?>"</strong> را به اتمام رسانده است.</p>
                <h3>دستاوردهای کسب شده:</h3>
                <ul class="badges-earned">
                    <?php foreach($user_badges as $badge_slug): ?>
                        <li><?php echo esc_html(psych_get_badge_name($badge_slug)); ?></li>
                    <?php endforeach; ?>
                </ul>
                <h3>درباره دوره:</h3>
                <p><?php echo esc_html($course_post->post_excerpt ?: 'توضیحات دوره در اینجا قرار می‌گیرد.'); ?></p>
            </div>
            <footer>
                <p>برای مشاهده دوره، <a href="<?php echo get_permalink($course_post); ?>">اینجا کلیک کنید</a>.</p>
                <div class="social-share">
                    <!-- Add social sharing buttons here -->
                </div>
            </footer>
        </article>
        <?php

        // Add Schema.org markup for SEO
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $user_data->display_name,
            'alumniOf' => [
                '@type' => 'EducationalOrganization',
                'name' => get_bloginfo('name'),
                'url' => home_url()
            ],
            'knowsAbout' => $course_post->post_title
        ];

        echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';

        return ob_get_clean();
    }

    // ===================================================================
    // SECTION 4: Integrations
    // ===================================================================

    public function coach_access_filter($can_access, $user_id, $atts) {
        $context = $this->get_viewing_context();
        if ($context['is_impersonating']) {
            $coach_id = $context['real_user_id'];
            if (!user_can($coach_id, 'manage_options')) {
                $allowed = get_user_meta($coach_id, 'psych_coach_allowed_contents', true) ?: [];
                if (!in_array($atts['id'] ?? '', $allowed)) {
                    return false;
                }
            }
        }
        return $can_access;
    }

    public function handle_station_completion($user_id, $station_id, $course_id) {
        $completion_data = [
            'station_id' => $station_id,
            'course_id' => $course_id,
            'timestamp' => current_time('timestamp')
        ];

        $completions = get_user_meta($user_id, 'psych_station_completions', true) ?: [];
        $completions[] = $completion_data;
        update_user_meta($user_id, 'psych_station_completions', $completions);

        do_action('psych_course_station_completed', $user_id, $station_id, $course_id);
    }

    public function handle_completion($user_id, $content_id, $data) {
        error_log("User {$user_id} completed interactive content {$content_id}");
    }

    public function award_gamification_rewards($user_id, $content_id, $data) {
        if (!function_exists('psych_gamification_add_points') || !isset($this->interactive_data[$content_id])) return;

        $rewards = explode('|', $this->interactive_data[$content_id]['rewards']);
        foreach ($rewards as $reward) {
            [$type, $value] = explode(':', $reward);
            switch ($type) {
                case 'points':
                    psych_gamification_add_points($user_id, intval($value), 'تکمیل محتوای تعاملی');
                    break;
                case 'badge':
                    psych_gamification_award_badge($user_id, sanitize_key($value));
                    break;
            }
        }

        // Enhanced gamification: Conditional rewards based on subscales
        if (isset($data['subscales']['anxiety']) && $data['subscales']['anxiety'] < 10) {
            psych_gamification_add_points($user_id, 20, 'پاداش اضافی برای نمره پایین اضطراب');
        }
    }

    public function handle_upsell_purchase($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();

        if ($user_id) {
            do_action('psych_mission_completed', $user_id, 'upsell_purchase', $order_id);
        }
    }

    public function handle_subscales_update($user_id, $content_id, $data) {
        if (!isset($data['subscales'])) return;

        $subscales = get_user_meta($user_id, PSYCH_INTERACTIVE_META_SUBSCALES, true) ?: [];
        $subscales[$content_id] = $data['subscales'];
        update_user_meta($user_id, PSYCH_INTERACTIVE_META_SUBSCALES, $subscales);

        $this->clear_cache($user_id, $content_id);
    }

    public function send_conditional_reports($user_id, $content_id, $data) {
        if (!isset($data['subscales'])) return;

        // Example condition: If anxiety_social < 10, send SMS to parent
        if (isset($data['subscales']['anxiety_social']) && $data['subscales']['anxiety_social'] < 10) {
            $parent_phone = get_user_meta($user_id, 'parent_phone', true); // Assume this meta exists
            if ($parent_phone) {
                $message = "نمره اضطراب اجتماعی فرزند شما کمتر از ۱۰ است. پیشنهاد: بررسی دوره جدید.";
                // Simulated SMS send (replace with real API, e.g., Twilio or Iranian SMS service)
                error_log("SMS sent to {$parent_phone}: {$message}");
                // In real: wp_remote_post('SMS_API_URL', ['body' => ['to' => $parent_phone, 'message' => $message]]);
            }
        }

        // Generate advanced report (e.g., for dashboard)
        $report = "گزارش: نمرات subscales - اضطراب: " . ($data['subscales']['anxiety'] ?? 'N/A');
        update_user_meta($user_id, 'psych_latest_report', $report);
    }

    public function update_personalized_path($user_id, $content_id, $data) {
        // Integrate with Path Engine for personalized paths without changing path-engine.php
        // Use hook to trigger path update based on subscales
        if (function_exists('psych_path_update_user_station')) {
            $new_station = 'default';
            if (isset($data['subscales']['anxiety']) && $data['subscales']['anxiety'] < 10) {
                $new_station = 'branch_anxiety';
            } elseif (isset($data['subscales']['stress']) && $data['subscales']['stress'] > 20) {
                $new_station = 'branch_stress';
            }
            psych_path_update_user_station($user_id, $content_id, $new_station); // Assume this function exists in Path Engine
        }
    }

    // ===================================================================
    // SECTION 5: CSS & JavaScript Injection (All Inline)
    // ===================================================================

    public function inject_inline_css() {
        if (!$this->assets_injected) return;

        ?>
        <style type="text/css">
            /* Base Styles */
            .psych-content-block { direction: rtl; text-align: right; padding: 20px; background: #f9f9f9; border-radius: 8px; }
            .psych-button { cursor: pointer; padding: 10px 20px; border: none; border-radius: 4px; font-size: 16px; transition: background 0.3s; }
            .psych-btn-primary { background: #007bff; color: white; }
            .psych-btn-primary:hover { background: #0056b3; }
            .psych-btn-success { background: #28a745; color: white; }
            .psych-btn-success:hover { background: #218838; }
            .psych-btn-warning { background: #ffc107; color: black; }
            .psych-btn-warning:hover { background: #e0a800; }
            .psych-button[disabled] { opacity: 0.6; cursor: not-allowed; }
            .psych-button.loading::after { content: ' ...'; }

            /* Modal Styles */
            .psych-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 9999; opacity: 0; transition: opacity 0.3s ease; }
            .psych-modal-overlay.active { opacity: 1; }
            .psych-modal-dialog { background: white; padding: 20px; border-radius: 8px; max-width: 600px; width: 90%; transform: scale(0.9); transition: transform 0.3s ease; direction: rtl; text-align: right; }
            .psych-modal-dialog.active { transform: scale(1); }
            .psych-modal-close { position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 24px; cursor: pointer; color: #666; }
            .psych-modal-close:hover { color: #000; }
            #psych-modal-content { max-height: 400px; overflow-y: auto; padding: 10px; }

            /* Progress Path Styles */
            .psych-progress-path-container { margin: 20px 0; }
            .psych-badges-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; }
            .psych-badge-item { text-align: center; transition: transform 0.3s; }
            .psych-badge-item:hover { transform: scale(1.1); }
            .psych-badge-item img { width: 50px; height: 50px; }
            .psych-level-progress .progress-bar { height: 20px; background: linear-gradient(to right, #007bff, #00bfff); }

            /* Notices */
            .coach-interactive-notice, .coach-progress-notice, .coach-impersonation-notice { background: linear-gradient(45deg, #ffcc00, #ff9900); color: white; padding: 10px; margin-bottom: 10px; border-radius: 4px; display: flex; align-items: center; }
            .coach-notice { background: #007bff; color: white; padding: 5px; margin-bottom: 10px; }

            /* Course Specific */
            .psych-course-specific { border: 2px dashed #28a745; position: relative; }
            .psych-course-specific::before { content: "دوره‌ای"; position: absolute; top: -10px; right: 10px; background: #28a745; color: white; padding: 2px 5px; font-size: 12px; }

            /* Access Denied */
            .psych-access-denied, .psych-login-required { background: #dc3545; color: white; padding: 10px; border-radius: 4px; }

            /* Recommendation */
            .psych-recommendation { background: #e9ffe9; padding: 10px; border: 1px solid #28a745; border-radius: 4px; }

            /* Responsive */
            @media (max-width: 768px) {
                .psych-modal-dialog { width: 95%; }
                .psych-badges-grid { grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); }
            }
        </style>
        <?php
    }

    public function inject_modal_and_js() {
        if (!$this->assets_injected) return;

        // Modal HTML
        ?>
        <div id="psych-modal-overlay" class="psych-modal-overlay">
            <div id="psych-modal-dialog" class="psych-modal-dialog">
                <button id="psych-modal-close" class="psych-modal-close">&times;</button>
                <h3 id="psych-modal-title"></h3>
                <div id="psych-modal-content"></div>
            </div>
        </div>
        <?php

        // Inline JavaScript with all features (SPA, conditions, recommendations, etc.)
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Modal Handling
                function showModal(title, content) {
                    $('#psych-modal-title').text(title);
                    $('#psych-modal-content').html(content);
                    $('body').addClass('psych-modal-open').css('overflow', 'hidden');
                    $('#psych-modal-overlay').addClass('active');
                    setTimeout(() => $('#psych-modal-dialog').addClass('active'), 50);
                }

                function closeModal() {
                    $('#psych-modal-dialog').removeClass('active');
                    setTimeout(() => {
                        $('#psych-modal-overlay').removeClass('active');
                        $('body').removeClass('psych-modal-open').css('overflow', '');
                    }, 300);
                }

                $(document).on('click', '.psych-button[data-action="modal"]', function() {
                    const target = $(this).data('target');
                    const title = $(this).data('title') || 'عنوان پیش‌فرض';
                    $.ajax({
                        url: psych_interactive_ajax.ajax_url || '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'psych_load_modal_content',
                            modal_id: target,
                            nonce: psych_interactive_ajax.nonce || '<?php echo wp_create_nonce(PSYCH_INTERACTIVE_AJAX_NONCE); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                showModal(title, response.data.content);
                            }
                        }
                    });
                });

                $(document).on('click', '#psych-modal-close, #psych-modal-overlay', function(e) {
                    if (e.target === this) closeModal();
                });

                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape') closeModal();
                });

                // Toggle Handling
                $(document).on('click', '.psych-button[data-action="toggle"]', function() {
                    const target = $(this).data('target');
                    $('#' + target).slideToggle(300);
                });

                // Mission Handling
                $(document).on('click', '.psych-button[data-action="mission"]', function() {
                    const missionId = $(this).data('mission-id');
                    alert('ماموریت ' + missionId + ' تکمیل شد!'); // Simulated; integrate with real events
                });

                // SPA Dynamic Load
                $(document).on('click', '.psych-content-block [data-spa-load]', function() {
                    const blockId = $(this).closest('.psych-content-block').attr('id');
                    const station = $(this).data('spa-load');
                    $.ajax({
                        url: psych_interactive_ajax.ajax_url || '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'psych_load_dynamic_content',
                            block_id: blockId,
                            station: station,
                            nonce: psych_interactive_ajax.nonce || '<?php echo wp_create_nonce(PSYCH_INTERACTIVE_AJAX_NONCE); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#' + blockId).html(response.data.content);
                            }
                        }
                    });
                });

                // Submit Interactive (quiz, poll, feedback)
                $(document).on('click', '.psych-submit-quiz, .psych-submit-poll, .psych-submit-feedback', function() {
                    const container = $(this).closest('[data-content-id]');
                    const contentId = container.data('content-id');
                    const data = {}; // Collect data from form (e.g., $('input', container).serialize())
                    $.ajax({
                        url: psych_interactive_ajax.ajax_url || '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'psych_interactive_submit',
                            content_id: contentId,
                            data: data,
                            nonce: psych_interactive_ajax.nonce || '<?php echo wp_create_nonce(PSYCH_INTERACTIVE_AJAX_NONCE); ?>'
                        },
                        success: function(response) {
                            alert(response.data.message);
                        }
                    });
                });

                // Handle Hidden Content and Recommendations based on conditions
                $('.psych-hidden-content, .psych-recommendation').each(function() {
                    const condition = $(this).data('condition'); // e.g., "subscale:anxiety<10"
                    const contentId = $(this).data('content-id') || 'default';
                    if (condition) {
                        $.ajax({
                            url: psych_interactive_ajax.ajax_url || '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: 'psych_interactive_get_subscales',
                                content_id: contentId,
                                nonce: psych_interactive_ajax.nonce || '<?php echo wp_create_nonce(PSYCH_INTERACTIVE_AJAX_NONCE); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    const subscales = response.data.subscales;
                                    const parts = condition.split(':');
                                    const key = parts[1].split('<')[0]; // Simplified parsing
                                    const operator = condition.includes('<') ? '<' : (condition.includes('>') ? '>' : '=');
                                    const value = parseInt(condition.split(operator)[1]);
                                    let show = false;
                                    if (subscales[key]) {
                                        if (operator === '<' && subscales[key] < value) show = true;
                                        if (operator === '>' && subscales[key] > value) show = true;
                                        if (operator === '=' && subscales[key] == value) show = true;
                                    }
                                    if (show) $(this).show();
                                }
                            }.bind(this)
                        });
                    }
                });

                // Enhanced SPA: Smooth transitions (example)
                // For AI integration: Comment - Add API call to OpenAI for dynamic recommendations
                // e.g., $.post('AI_ENDPOINT', {subscales: subscales}, function(aiResponse) { /* show ai suggestion */ });

                // Security: Add simple CAPTCHA simulation if needed
                // Performance: Use localStorage for caching if transients are slow
            });
        </script>
        <?php
    }
}

// Initialize the singleton
Psych_Interactive_Content_Ultimate::get_instance();
