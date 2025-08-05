<?php
/**
 * Plugin Name: Psych Complete System - Report Card (Enhanced Integration Edition with Custom Table)
 * Description: ماژول جامع گزارش‌گیری و کارنامه با یکپارچگی کامل و استفاده از جدول سفارشی برای ذخیره داده‌ها
 * Version: 5.2.0 (Refactored Assets)
 * Author: Enhanced Integration Team
 *
 * فایل: report-card.php
 * این نسخه Enhanced شامل:
 * - هماهنگی کامل با Coach Module , Path Engine .2, Interactive Content .3, Gamification Center .5
 * - پشتیبانی کامل از Coach Impersonation
 * - نمایش گزارش‌های دقیق پیشرفت مسیر
 * - سیستم اعلانات پیشرفته
 * - گزارش‌گیری چندبعدی
 * - تغییرات برای استفاده از جدول سفارشی wp_psych_reports به جای user_meta برای مقیاس‌پذیری
 * - دارایی‌های CSS و JS در فایل‌های جداگانه برای عملکرد بهتر
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Prevent double loading
if (class_exists('Psych_Unified_Report_Card_Enhanced')) {
    return;
}

/**
 * Enhanced Report Card Class with full integration support and custom table usage
 */
final class Psych_Unified_Report_Card_Enhanced {

    private static $instance = null;
    const VERSION = '5.2.0';
    const REPORTS_TABLE = 'psych_reports';

    // SMS Settings - Should be configured in the admin panel
    private $sms_settings = [];

    private $viewing_context = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_settings();
        $this->add_hooks();
        $this->register_shortcodes();
    }

    private function load_settings() {
        // In a real plugin, these would come from get_option()
        $this->sms_settings = [
            'active_system' => 'FarazSMS', // FarazSMS, IPPanel, or None
            'api_key'       => 'your_farazsms_api_key_here', // Should be securely stored
            'sender'        => 'your_sender_number'
        ];
    }

    private function init_viewing_context() {
        if (function_exists('psych_path_get_viewing_context')) {
            $this->viewing_context = psych_path_get_viewing_context();
        } else {
            // Fallback for standalone operation
            $this->viewing_context = [
                'is_impersonating' => false,
                'real_user_id'     => get_current_user_id(),
                'viewed_user_id'   => get_current_user_id(),
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
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_psych_save_user_notes', [$this, 'ajax_save_user_data']);
        add_action('wp_ajax_psych_save_user_goals', [$this, 'ajax_save_user_data']);
        add_action('wp_ajax_psych_send_parent_report', [$this, 'ajax_send_parent_report']);
        add_action('psych_send_user_report_sms', [$this, 'trigger_report_sms']);

        // Handle form submissions (non-AJAX fallback)
        add_action('init', [$this, 'handle_form_submissions']);

        // Database table creation on activation
        // Note: Activation hooks should be in the main plugin file. This is for standalone demonstration.
        register_activation_hook(__FILE__, [$this, 'create_custom_tables']);
    }

    public function create_custom_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::REPORTS_TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            test_results longtext DEFAULT NULL,
            parent_mobile varchar(20) DEFAULT NULL,
            user_notes longtext DEFAULT NULL,
            user_goals longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_assets() {
        global $post;
        // Only enqueue if the shortcode exists on the page
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'psych_report_card')) {
            $plugin_url = plugin_dir_url(__FILE__);

            // Enqueue Styles
            wp_enqueue_style(
                'psych-report-card-style',
                $plugin_url . 'assets/css/report-card-style.css',
                [],
                self::VERSION
            );
            // Enqueue Dashicons for icons used in CSS
            wp_enqueue_style('dashicons');

            // Enqueue Scripts
            wp_enqueue_script('jquery');
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.9.1', true);

            wp_enqueue_script(
                'psych-report-card-script',
                $plugin_url . 'assets/js/report-card-script.js',
                ['jquery', 'chart-js'],
                self::VERSION,
                true
            );

            // Localize script with dynamic data
            wp_localize_script('psych-report-card-script', 'psych_report_card', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('psych_report_card_nonce'),
                'viewing_context' => $this->get_viewing_context()
            ]);
        }
    }

    public function ajax_save_user_data() {
        check_ajax_referer('psych_report_card_nonce', 'nonce');

        $context = $this->get_viewing_context();
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : $context['viewed_user_id'];

        // Security check: Only allow coaches to edit impersonated users, or users to edit themselves.
        if (!$context['is_impersonating'] && $context['real_user_id'] !== $user_id) {
            wp_send_json_error(['message' => 'مجوز دسترسی ندارید.']);
        }

        $content = sanitize_textarea_field($_POST['content']);
        $action = $_POST['action']; // e.g., 'psych_save_user_notes'

        $data_key = '';
        if ($action === 'psych_save_user_notes') {
            $data_key = 'user_notes';
        } elseif ($action === 'psych_save_user_goals') {
            $data_key = 'user_goals';
        }

        if (empty($data_key)) {
            wp_send_json_error(['message' => 'عملیات نامعتبر.']);
        }

        $this->update_report_data($user_id, [$data_key => $content]);
        wp_send_json_success(['message' => 'اطلاعات با موفقیت ذخیره شد.']);
    }


    public function ajax_send_parent_report() {
        check_ajax_referer('psych_report_card_nonce', 'nonce');

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : $this->get_viewing_context()['viewed_user_id'];
        $mobile = sanitize_text_field($_POST['mobile']);

        // Basic validation for mobile number
        if (!preg_match('/^[0-9]{10,11}$/', $mobile)) {
             wp_send_json_error(['message' => 'شماره موبایل وارد شده معتبر نیست.']);
        }

        $this->update_report_data($user_id, ['parent_mobile' => $mobile]); // Save mobile for future use
        $report_summary = $this->generate_report_summary($user_id);

        if ($this->send_sms($mobile, $report_summary)) {
            wp_send_json_success(['message' => 'گزارش با موفقیت ارسال شد.']);
        } else {
            wp_send_json_error(['message' => 'خطا در ارسال SMS. لطفاً تنظیمات افزونه را بررسی کنید.']);
        }
    }

    public function trigger_report_sms($user_id) {
        $data = $this->get_report_data($user_id);
        $mobile = $data['parent_mobile'] ?? '';
        if ($mobile) {
            $report = $this->generate_report_summary($user_id);
            $this->send_sms($mobile, $report);
        }
    }

    private function send_sms($mobile, $message) {
        if ($this->sms_settings['active_system'] === 'None') {
            return false;
        }

        $api_key = $this->sms_settings['api_key'];
        $sender = $this->sms_settings['sender'];
        $url = '';
        $args = [];

        if ($this->sms_settings['active_system'] === 'FarazSMS') {
            $url = 'https://farazsms.com/api/send';
            $args = [
                'method' => 'POST',
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['api_key' => $api_key, 'sender' => $sender, 'recipient' => $mobile, 'message' => $message])
            ];
        } elseif ($this->sms_settings['active_system'] === 'IPPanel') {
            $url = 'https://ippanel.com/api/send';
            $args = [
                'method' => 'POST',
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['apikey' => $api_key, 'from' => $sender, 'to' => $mobile, 'text' => $message])
            ];
        }

        if (empty($url)) return false;

        $response = wp_remote_post($url, $args);
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }

    private function generate_report_summary($user_id) {
        // Ensure gamification functions exist before calling them
        $level_info = function_exists('psych_gamification_get_user_level_info')
            ? psych_gamification_get_user_level_info($user_id)
            : ['name' => 'N/A', 'current_points' => 0, 'progress_percentage' => 0];

        $badges_meta = get_user_meta($user_id, 'psych_user_badges', true);
        $badges = is_array($badges_meta) ? $badges_meta : [];

        $badge_names = array_map(function($slug) {
            return function_exists('psych_get_badge_name') ? psych_get_badge_name($slug) : ucfirst($slug);
        }, $badges);

        $summary = sprintf(
            "گزارش پیشرفت کاربر: سطح %s با %d امتیاز. نشان‌های کسب‌شده: %s. پیشرفت به سطح بعدی: %d%%",
            $level_info['name'],
            $level_info['current_points'],
            !empty($badge_names) ? implode(', ', $badge_names) : 'هنوز نشانی کسب نشده',
            $level_info['progress_percentage']
        );
        return $summary;
    }

    public function handle_form_submissions() {
        if (!isset($_POST['psych_report_card_nonce']) || !wp_verify_nonce($_POST['psych_report_card_nonce'], 'psych_report_card_action')) {
            return;
        }

        $user_id = $this->get_viewing_context()['viewed_user_id'];

        if (isset($_POST['psych_submit_notes'])) {
            $notes = sanitize_textarea_field($_POST['psych_user_notes']);
            $this->update_report_data($user_id, ['user_notes' => $notes]);
        }
        if (isset($_POST['psych_submit_goals'])) {
            $goals = sanitize_textarea_field($_POST['psych_user_goals']);
            $this->update_report_data($user_id, ['user_goals' => $goals]);
        }
    }

    private function register_shortcodes() {
        add_shortcode('psych_report_card', [$this, 'render_report_card_shortcode']);
    }

    public function render_report_card_shortcode($atts) {
        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];
        $user = get_userdata($user_id);
        if (!$user) return '<div class="psych-alert error">کاربر یافت نشد.</div>';

        // Get all necessary data
        $report_data = $this->get_report_data($user_id);
        $level_info = function_exists('psych_gamification_get_user_level_info') ? psych_gamification_get_user_level_info($user_id) : ['name' => 'N/A', 'current_points' => 0, 'points_to_next' => 0, 'progress_percentage' => 0];
        $badges = get_user_meta($user_id, 'psych_user_badges', true) ?: [];
        $test_results = json_decode($report_data['test_results'] ?? '[]', true);
        $path_progress = function_exists('psych_path_get_user_progress') ? psych_path_get_user_progress($user_id) : [];

        ob_start();
        ?>
        <div class="psych-report-card-container">
            <?php if ($context['is_impersonating']): ?>
                <div class="psych-impersonation-notice">شما در حال مشاهده گزارش کاربر <strong><?php echo esc_html($user->display_name); ?></strong> به عنوان مربی هستید.</div>
            <?php endif; ?>

            <header class="psych-report-card-header">
                <img src="<?php echo esc_url(get_avatar_url($user_id)); ?>" alt="Avatar" class="avatar">
                <div>
                    <h2><?php echo esc_html($user->display_name); ?></h2>
                    <div class="psych-user-status">سطح: <?php echo esc_html($level_info['name']); ?> (<?php echo esc_html($level_info['progress_percentage']); ?>% تکمیل شده)</div>
                </div>
            </header>

            <nav class="psych-report-card-tabs">
                <button class="tab-link" data-tab="psych-tab-summary">خلاصه</button>
                <button class="tab-link" data-tab="psych-tab-progress">پیشرفت مسیر</button>
                <button class="tab-link" data-tab="psych-tab-tests">نتایج آزمون‌ها</button>
                <button class="tab-link" data-tab="psych-tab-notes">یادداشت و اهداف</button>
                <button class="tab-link" data-tab="psych-tab-analytics">تحلیل‌ها</button>
            </nav>

            <main>
                <div id="psych-tab-summary" class="psych-tab-content">
                    <section class="psych-section">
                        <h3><span class="dashicons dashicons-awards"></span> خلاصه عملکرد</h3>
                        <div class="psych-summary-grid">
                            <div class="psych-summary-item"><div class="value"><?php echo esc_html($level_info['current_points']); ?></div><div class="label">امتیاز کل</div></div>
                            <div class="psych-summary-item"><div class="value"><?php echo count($badges); ?></div><div class="label">نشان کسب‌شده</div></div>
                            <div class="psych-summary-item"><div class="value"><?php echo esc_html($level_info['points_to_next']); ?></div><div class="label">تا سطح بعدی</div></div>
                        </div>
                        <h4>نشان‌ها:</h4>
                        <?php if (!empty($badges)): ?>
                            <div class="psych-badges-list">
                                <?php foreach ($badges as $badge_slug): ?>
                                    <div class="psych-badge-item" title="<?php echo esc_attr(psych_get_badge_name($badge_slug)); ?>">
                                        <span class="dashicons dashicons-awards"></span>
                                        <p class="badge-name"><?php echo esc_html(psych_get_badge_name($badge_slug)); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>هنوز نشانی کسب نشده است.</p>
                        <?php endif; ?>
                    </section>
                </div>

                <div id="psych-tab-progress" class="psych-tab-content">
                    <section class="psych-section">
                        <h3><span class="dashicons dashicons-location-alt"></span> پیشرفت در مسیر</h3>
                        <div class="psych-path-progress">
                            <?php if (empty($path_progress)): ?>
                                <div class="psych-alert info">داده پیشرفت مسیر موجود نیست.</div>
                            <?php else: ?>
                                <?php foreach ($path_progress as $station): ?>
                                    <div class="psych-path-station <?php echo esc_attr($station['status']); ?>">
                                        <div class="psych-station-icon"><span class="dashicons dashicons-location"></span></div>
                                        <div class="psych-station-details">
                                            <h4><?php echo esc_html($station['title']); ?></h4>
                                            <p><?php echo esc_html($station['description']); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>

                <div id="psych-tab-tests" class="psych-tab-content">
                    <section class="psych-section">
                        <h3><span class="dashicons dashicons-forms"></span> نتایج آزمون‌ها</h3>
                        <table class="psych-test-results-table">
                            <thead><tr><th>نام آزمون</th><th>امتیاز</th><th>وضعیت</th></tr></thead>
                            <tbody>
                                <?php if (empty($test_results)): ?>
                                    <tr><td colspan="3" style="text-align:center;">هیچ آزمونی ثبت نشده است.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($test_results as $result): ?>
                                        <tr>
                                            <td><?php echo esc_html($result['test']); ?></td>
                                            <td class="psych-score-cell <?php echo esc_attr($this->get_score_class($result['score'])); ?>"><?php echo esc_html($result['score']); ?>/100</td>
                                            <td><?php echo esc_html($this->get_score_status($result['score'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </section>
                </div>

                <div id="psych-tab-notes" class="psych-tab-content">
                    <form method="post" class="psych-notes-section">
                        <section class="psych-section">
                            <h3><span class="dashicons dashicons-edit"></span> یادداشت‌های مربی</h3>
                            <?php wp_nonce_field('psych_report_card_action', 'psych_report_card_nonce'); ?>
                            <textarea id="psych-user-notes" name="psych_user_notes" placeholder="یادداشت‌های خود را در اینجا وارد کنید..."><?php echo esc_textarea($report_data['user_notes'] ?? ''); ?></textarea>
                            <button type="submit" name="psych_submit_notes" class="psych-button">ذخیره یادداشت‌ها</button>
                        </section>
                    </form>
                     <form method="post" class="psych-notes-section">
                        <section class="psych-section">
                            <h3><span class="dashicons dashicons-flag"></span> اهداف کاربر</h3>
                             <?php wp_nonce_field('psych_report_card_action', 'psych_report_card_nonce'); ?>
                            <textarea id="psych-user-goals" name="psych_user_goals" placeholder="اهداف کاربر را در اینجا مشخص کنید..."><?php echo esc_textarea($report_data['user_goals'] ?? ''); ?></textarea>
                            <button type="submit" name="psych_submit_goals" class="psych-button">ذخیره اهداف</button>
                        </section>
                    </form>
                    <section class="psych-section">
                        <h3><span class="dashicons dashicons-email"></span> ارسال گزارش به والدین</h3>
                        <input type="tel" id="parent-mobile" value="<?php echo esc_attr($report_data['parent_mobile'] ?? ''); ?>" placeholder="شماره موبایل والدین" style="width:100%; padding: 8px; margin-bottom: 10px;">
                        <button id="send-parent-report" class="psych-button warning">ارسال گزارش</button>
                        <div id="psych-parent-report-alert" style="margin-top:10px;"></div>
                    </section>
                </div>

                <div id="psych-tab-analytics" class="psych-tab-content">
                    <section class="psych-section">
                        <h3><span class="dashicons dashicons-chart-line"></span> تحلیل پیشرفت</h3>
                        <div class="psych-chart-container">
                            <canvas id="psych-progress-chart" data-chart-data='{"labels": ["هفته ۱", "هفته ۲", "هفته ۳", "اکنون"], "points": [<?php echo esc_attr($level_info['current_points'] / 4); ?>, <?php echo esc_attr($level_info['current_points'] / 2); ?>, <?php echo esc_attr($level_info['current_points'] * 0.75); ?>, <?php echo esc_attr($level_info['current_points']); ?>]}'></canvas>
                        </div>
                        <div class="psych-chart-container">
                            <canvas id="psych-badges-chart" data-chart-data='{"earned": <?php echo count($badges); ?>, "remaining": <?php echo max(0, 15 - count($badges)); ?>}'></canvas>
                        </div>
                    </section>
                </div>
            </main>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_score_class($score) {
        if ($score >= 90) return 'psych-score-excellent';
        if ($score >= 70) return 'psych-score-good';
        return 'psych-score-needs-improvement';
    }

    private function get_score_status($score) {
        if ($score >= 90) return 'عالی';
        if ($score >= 70) return 'خوب';
        return 'نیاز به بهبود';
    }

    // Custom Table Helpers
    private function get_report_data($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::REPORTS_TABLE;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id), ARRAY_A);
        if (!$row) {
            // Create initial row if not exists to ensure there's always a record to update
            $wpdb->insert($table_name, ['user_id' => $user_id, 'test_results' => '[]', 'user_notes' => '', 'user_goals' => '']);
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id), ARRAY_A);
        }
        return $row;
    }

    private function update_report_data($user_id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::REPORTS_TABLE;
        // Ensure the user row exists before updating
        $this->get_report_data($user_id);
        return $wpdb->update($table_name, $data, ['user_id' => $user_id]);
    }

    // Prevent cloning and unserializing
    private function __clone() {}
    private function __wakeup() {}
}

// Initialize the module
Psych_Unified_Report_Card_Enhanced::get_instance();

// New functions for the two-tiered report system
function render_free_report($user_id, $assessment_id, $product_id) {
    $product = wc_get_product($product_id);
    $results = get_user_meta($user_id, '_psych_assessment_results_' . $assessment_id, true);

    ob_start();
    ?>
    <div class="psych-report-card free-tier">
        <h3>نتایج اولیه آزمون شما</h3>
        <p>در اینجا یک نمای کلی از نتایج شما آورده شده است:</p>

        <div class="psych-summary-grid">
            <div class="psych-summary-item">
                <div class="value"><?php echo esc_html($results['main_score'] ?? 'N/A'); ?></div>
                <div class="label">امتیاز کلی</div>
            </div>
            <div class="psych-summary-item">
                <div class="value"><?php echo esc_html($results['key_trait'] ?? 'N/A'); ?></div>
                <div class="label">ویژگی کلیدی</div>
            </div>
        </div>

        <div class="psych-nudge-section">
            <h4>قفل تحلیل کامل را باز کنید!</h4>
            <p>برای مشاهده تحلیل عمیق هر خرده‌مقیاس، گزارش PDF قابل چاپ و توصیه‌های شخصی‌سازی‌شده، گزارش پیشرفته را خریداری کنید.</p>
            <a href="<?php echo esc_url($product->get_permalink()); ?>" class="psych-button psych-btn-success">خرید گزارش پیشرفته به قیمت <?php echo $product->get_price_html(); ?></a>
        </div>

        <div class="psych-teaser-section">
             <h5>[بخش پیش‌نمایش] تحلیل خرده‌مقیاس اضطراب:</h5>
             <p>نمره شما در این بخش متوسط رو به بالا است. برای درک اینکه این نمره چگونه بر روابط شما... [برای ادامه خرید کنید]</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function render_premium_report($user_id, $assessment_id) {
    $results = get_user_meta($user_id, '_psych_assessment_results_' . $assessment_id, true);

    ob_start();
    ?>
    <div class="psych-report-card premium-tier">
        <h3>تحلیل کامل و پیشرفته آزمون</h3>

        <div class="psych-summary-grid">
             <div class="psych-summary-item">
                <div class="value"><?php echo esc_html($results['main_score'] ?? 'N/A'); ?></div>
                <div class="label">امتیاز کلی</div>
            </div>
             <div class="psych-summary-item">
                <div class="value"><?php echo esc_html($results['key_trait'] ?? 'N/A'); ?></div>
                <div class="label">ویژگی کلیدی</div>
            </div>
        </div>

        <h4>تحلیل خرده‌مقیاس‌ها:</h4>
        <ul>
            <?php foreach($results['subscales'] as $key => $value): ?>
                <li><strong><?php echo esc_html(ucfirst($key)); ?>:</strong> <?php echo esc_html($value); ?> - تحلیل کامل این بخش...</li>
            <?php endforeach; ?>
        </ul>

        <div class="psych-recommendations">
             <h4>توصیه‌های شخصی‌سازی‌شده:</h4>
             <p>بر اساس نتایج شما، توصیه می‌شود روی... تمرکز کنید.</p>
        </div>

        <a href="#" class="psych-button download-pdf-btn">دانلود گزارش PDF</a>
    </div>
    <?php
    return ob_get_clean();
}
?>
