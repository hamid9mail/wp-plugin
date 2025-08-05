<?php
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
    const VERSION = '5.1.0';

    // Custom Table Name
    const REPORTS_TABLE = 'psych_reports';

    // SMS Settings
    const ACTIVE_SMS_SYSTEM       = 'FarazSMS'; // FarazSMS, IPPanel, or None
    const SMS_API_KEY             = 'your_farazsms_api_key_here'; // جایگزین با کلید واقعی
    const SMS_SENDER              = 'your_sender_number'; // شماره فرستنده

    private $viewing_context = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_viewing_context();
        $this->add_hooks();
        $this->register_shortcodes();
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

    private function get_viewing_context() {
        if ($this->viewing_context === null) {
            $this->init_viewing_context();
        }
        return $this->viewing_context;
    }

    private function add_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_head', [$this, 'print_tab_script']);
        add_action('wp_ajax_psych_save_user_notes', [$this, 'ajax_save_user_notes']);
        add_action('wp_ajax_psych_save_user_goals', [$this, 'ajax_save_user_goals']);
        add_action('wp_ajax_psych_send_parent_report', [$this, 'ajax_send_parent_report']);
        add_action('psych_send_user_report_sms', [$this, 'trigger_report_sms']);

        // Handle form submissions (non-AJAX fallback)
        add_action('init', [$this, 'handle_form_submissions']);

        // Database table creation on activation
        register_activation_hook(__FILE__, [$this, 'create_custom_tables']);
        register_deactivation_hook(__FILE__, [$this, 'deactivation_cleanup']);
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
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function deactivation_cleanup() {
        // Optional: Cleanup if needed, e.g., drop table or archive data
    }

    public function enqueue_assets() {
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'psych_report_card')) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.9.1', true);

            wp_register_script('psych-report-card', false, ['jquery', 'chart-js'], self::VERSION, true);
            wp_enqueue_script('psych-report-card');
            wp_add_inline_script('psych-report-card', $this->get_inline_js());

            wp_localize_script('psych-report-card', 'psych_report_card', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('psych_report_card_nonce'),
                'viewing_context' => $this->get_viewing_context()
            ]);

            add_action('wp_head', [$this, 'print_styles']);
        }
    }

    public function print_styles() {
        ?>
        <style>
            .psych-report-card-container {
                font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                border-radius: 15px;
                padding: 30px;
                margin: 20px auto;
                max-width: 1000px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                position: relative;
                overflow: hidden;
            }

            .psych-report-card-container::before {
                content: '';
                position: absolute;
                top: -50%;
                right: -50%;
                width: 200%;
                height: 200%;
                background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
                pointer-events: none;
            }

            .psych-report-card-header {
                display: flex;
                align-items: center;
                background: rgba(255,255,255,0.9);
                backdrop-filter: blur(10px);
                border-radius: 12px;
                padding: 25px;
                margin-bottom: 30px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
                position: relative;
                z-index: 1;
            }

            .psych-report-card-header .avatar {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                margin-left: 25px;
                border: 4px solid #fff;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }

            .psych-report-card-header h2 {
                margin: 0;
                font-size: 28px;
                color: #2c3e50;
                font-weight: 700;
            }

            .psych-user-status {
                font-size: 14px;
                color: #7f8c8d;
                margin-top: 5px;
            }

            .psych-impersonation-notice {
                background: linear-gradient(135deg, #f39c12, #e67e22);
                color: white;
                padding: 15px 20px;
                border-radius: 10px;
                margin-bottom: 20px;
                font-weight: 600;
                box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
            }

            .psych-report-card-tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                margin-bottom: 30px;
                background: rgba(255,255,255,0.7);
                backdrop-filter: blur(10px);
                padding: 8px;
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            }

            .psych-report-card-tabs .tab-link {
                padding: 12px 20px;
                cursor: pointer;
                border: none;
                background: transparent;
                font-size: 15px;
                color: #555;
                border-radius: 8px;
                transition: all 0.3s ease;
                font-weight: 500;
                flex: 1;
                text-align: center;
                min-width: 120px;
            }

            .psych-report-card-tabs .tab-link:hover {
                background: rgba(52, 152, 219, 0.1);
                color: #3498db;
            }

            .psych-report-card-tabs .tab-link.active {
                background: linear-gradient(135deg, #3498db, #2980b9);
                color: white;
                font-weight: 600;
                box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            }

            .psych-tab-content {
                display: none;
                animation: fadeIn 0.3s ease-in;
            }

            .psych-tab-content.active {
                display: block;
            }

            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .psych-section {
                margin-bottom: 30px;
                background: rgba(255,255,255,0.9);
                backdrop-filter: blur(10px);
                padding: 25px;
                border-radius: 12px;
                border: 1px solid rgba(255,255,255,0.2);
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
                position: relative;
                z-index: 1;
            }

            .psych-section h3 {
                margin: 0 0 20px;
                font-size: 22px;
                color: #34495e;
                padding-bottom: 10px;
                border-bottom: 2px solid rgba(52, 152, 219, 0.2);
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .psych-section h3 i {
                font-size: 24px;
                color: #3498db;
            }

            .psych-summary-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }

            .psych-summary-item {
                background: linear-gradient(135deg, #3498db, #2980b9);
                color: white;
                padding: 20px;
                border-radius: 10px;
                text-align: center;
                box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
                transition: transform 0.3s ease;
            }

            .psych-summary-item:hover {
                transform: translateY(-5px);
            }

            .psych-summary-item .value {
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 5px;
            }

            .psych-summary-item .label {
                font-size: 14px;
                opacity: 0.9;
            }

            .psych-badges-list {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 15px;
            }

            .psych-badge-item {
                background: rgba(255,255,255,0.8);
                padding: 15px;
                border-radius: 10px;
                text-align: center;
                transition: all 0.3s ease;
                cursor: pointer;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }

            .psych-badge-item:hover {
                transform: translateY(-3px);
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                border: 1px solid #3498db;
            }

            .psych-badge-item i {
                font-size: 40px;
                color: #f1c40f;
                margin-bottom: 10px;
            }

            .psych-badge-item .badge-name {
                font-size: 14px;
                color: #2c3e50;
                font-weight: 500;
            }

            .psych-test-results-table,
            .psych-leaderboard-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            }

            .psych-test-results-table th,
            .psych-test-results-table td,
            .psych-leaderboard-table th,
            .psych-leaderboard-table td {
                padding: 15px;
                text-align: right;
                border-bottom: 1px solid rgba(0,0,0,0.05);
            }

            .psych-test-results-table th,
            .psych-leaderboard-table th {
                background: linear-gradient(135deg, #3498db, #2980b9);
                color: white;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 13px;
            }

            .psych-test-results-table tr:hover,
            .psych-leaderboard-table tr:hover {
                background: rgba(52, 152, 219, 0.05);
            }

            .psych-score-cell {
                font-weight: 600;
            }

            .psych-score-excellent {
                color: #27ae60;
            }

            .psych-score-good {
                color: #f39c12;
            }

            .psych-score-needs-improvement {
                color: #e74c3c;
            }

            .psych-progress-bar {
                background: #ecf0f1;
                height: 8px;
                border-radius: 4px;
                overflow: hidden;
                margin-top: 5px;
            }

            .psych-progress-fill {
                height: 100%;
                background: linear-gradient(135deg, #2ecc71, #27ae60);
                transition: width 0.5s ease;
            }

            .psych-chart-container {
                background: white;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
                margin-bottom: 30px;
                height: 300px;
            }

            .psych-notes-section textarea {
                width: 100%;
                min-height: 150px;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-size: 15px;
                resize: vertical;
                transition: all 0.3s ease;
            }

            .psych-notes-section textarea:focus {
                border-color: #3498db;
                box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
                outline: none;
            }

            .psych-button {
                background: linear-gradient(135deg, #3498db, #2980b9);
                color: white;
                padding: 12px 24px;
                border: none;
                border-radius: 8px;
                font-size: 15px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s ease;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }

            .psych-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            }

            .psych-button.success {
                background: linear-gradient(135deg, #2ecc71, #27ae60);
            }

            .psych-button.success:hover {
                box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
            }

            .psych-button.warning {
                background: linear-gradient(135deg, #f39c12, #e67e22);
            }

            .psych-button.warning:hover {
                box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
            }

            .psych-path-progress {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }

            .psych-path-station {
                display: flex;
                align-items: center;
                gap: 15px;
                padding: 15px;
                background: white;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                transition: all 0.3s ease;
            }

            .psych-path-station:hover {
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }

            .psych-station-icon {
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                font-size: 20px;
            }

            .psych-station-details h4 {
                margin: 0;
                font-size: 16px;
                color: #2c3e50;
            }

            .psych-station-details p {
                margin: 5px 0 0;
                font-size: 14px;
                color: #7f8c8d;
            }

            .psych-path-station.completed .psych-station-icon {
                background: linear-gradient(135deg, #2ecc71, #27ae60);
                color: white;
            }

            .psych-path-station.completed {
                border-left: 4px solid #27ae60;
            }

            .psych-path-station.current .psych-station-icon {
                background: linear-gradient(135deg, #f39c12, #e67e22);
                color: white;
            }

            .psych-path-station.current {
                border-left: 4px solid #e67e22;
                box-shadow: 0 4px 15px rgba(243, 156, 18, 0.2);
            }

            .psych-path-station.locked .psych-station-icon {
                background: #ecf0f1;
                color: #bdc3c7;
            }

            .psych-path-station.locked {
                opacity: 0.7;
                border-left: 4px solid #bdc3c7;
            }

            .psych-alert {
                padding: 15px 20px;
                border-radius: 8px;
                margin-bottom: 20px;
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .psych-alert.success {
                background: rgba(46, 204, 113, 0.1);
                color: #27ae60;
                border: 1px solid rgba(46, 204, 113, 0.3);
            }

            .psych-alert.error {
                background: rgba(231, 76, 60, 0.1);
                color: #e74c3c;
                border: 1px solid rgba(231, 76, 60, 0.3);
            }

            .psych-alert.info {
                background: rgba(52, 152, 219, 0.1);
                color: #3498db;
                border: 1px solid rgba(52, 152, 219, 0.3);
            }

            .psych-loading-spinner {
                width: 20px;
                height: 20px;
                border: 3px solid #f3f3f3;
                border-top: 3px solid #3498db;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                display: inline-block;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            @media (max-width: 768px) {
                .psych-report-card-container {
                    padding: 20px;
                }

                .psych-report-card-header {
                    flex-direction: column;
                    text-align: center;
                }

                .psych-report-card-header .avatar {
                    margin: 0 0 15px;
                }

                .psych-report-card-tabs {
                    flex-direction: column;
                }

                .psych-summary-grid {
                    grid-template-columns: 1fr;
                }

                .psych-chart-container {
                    height: 250px;
                }
            }

            @media (max-width: 480px) {
                .psych-summary-item { padding: 15px; }
                .psych-badge-item { padding: 10px; }
            }
        </style>
        <?php
    }

    public function print_tab_script() {
        ?>
        <script type="text/javascript">
            var PsychReportCard = {
                init: function() {
                    this.initTabs();
                    this.initCharts();
                    this.initProgressBars();
                    this.initNotesAutoSave();
                    this.initSendReportButton();
                },

                initTabs: function() {
                    var tabs = document.querySelectorAll('.tab-link');
                    tabs.forEach(function(tab) {
                        tab.addEventListener('click', function() {
                            var target = this.dataset.tab;
                            document.querySelectorAll('.tab-link').forEach(function(t) { t.classList.remove('active'); });
                            this.classList.add('active');
                            document.querySelectorAll('.psych-tab-content').forEach(function(content) { content.classList.remove('active'); });
                            document.getElementById(target).classList.add('active');

                            if (target === 'psych-tab-analytics') {
                                PsychReportCard.initCharts();
                            }
                        });
                    });
                    tabs[0].click(); // Activate first tab
                },

                initCharts: function() {
                    var progressCtx = document.getElementById('psych-progress-chart');
                    if (progressCtx) {
                        var progressData = JSON.parse(progressCtx.dataset.chartData);
                        new Chart(progressCtx, {
                            type: 'line',
                            data: {
                                labels: progressData.labels,
                                datasets: [{
                                    label: 'پیشرفت امتیازات',
                                    data: progressData.points,
                                    borderColor: '#3498db',
                                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                                    tension: 0.4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: { y: { beginAtZero: true } }
                            }
                        });
                    }

                    var badgesCtx = document.getElementById('psych-badges-chart');
                    if (badgesCtx) {
                        var badgesData = JSON.parse(badgesCtx.dataset.chartData);
                        new Chart(badgesCtx, {
                            type: 'doughnut',
                            data: {
                                labels: ['کسب‌شده', 'باقیمانده'],
                                datasets: [{
                                    data: [badgesData.earned, badgesData.remaining],
                                    backgroundColor: ['#2ecc71', '#ecf0f1']
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false
                            }
                        });
                    }
                },

                initProgressBars: function() {
                    document.querySelectorAll('.psych-progress-fill').forEach(function(bar) {
                        bar.style.width = bar.dataset.percent + '%';
                    });
                },

                initNotesAutoSave: function() {
                    var debounce = function(func, delay) {
                        var timer;
                        return function() {
                            clearTimeout(timer);
                            timer = setTimeout(func, delay);
                        };
                    };

                    var notesTextarea = document.getElementById('psych-user-notes');
                    if (notesTextarea) {
                        notesTextarea.addEventListener('input', debounce(function() {
                            PsychReportCard.saveUserData('notes', this.value);
                        }, 1000));
                    }

                    var goalsTextarea = document.getElementById('psych-user-goals');
                    if (goalsTextarea) {
                        goalsTextarea.addEventListener('input', debounce(function() {
                            PsychReportCard.saveUserData('goals', this.value);
                        }, 1000));
                    }
                },

                saveUserData: function(type, content) {
                    var action = type === 'notes' ? 'psych_save_user_notes' : 'psych_save_user_goals';
                    fetch(psych_report_card.ajax_url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: action,
                            nonce: psych_report_card.nonce,
                            content: content
                        })
                    }).then(response => response.json()).then(data => {
                        if (data.success) {
                            console.log('ذخیره شد');
                            // Optional: Show saved indicator
                        }
                    });
                },

                initSendReportButton: function() {
                    var sendButton = document.getElementById('send-parent-report');
                    if (sendButton) {
                        sendButton.addEventListener('click', function() {
                            var mobile = document.getElementById('parent-mobile').value;
                            if (!mobile || mobile.length < 10) {
                                alert('شماره موبایل معتبر وارد کنید.');
                                return;
                            }
                            fetch(psych_report_card.ajax_url, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: new URLSearchParams({
                                    action: 'psych_send_parent_report',
                                    nonce: psych_report_card.nonce,
                                    mobile: mobile
                                })
                            }).then(response => response.json()).then(data => {
                                if (data.success) {
                                    alert(data.data.message);
                                } else {
                                    alert(data.data.message);
                                }
                            });
                        });
                    }
                }
            };

            jQuery(document).ready(function() {
                PsychReportCard.init();
            });
        </script>
        <?php
    }

    public function get_inline_js() {
        return $this->print_tab_script(); // Since it's inline, we can call it here, but actually it's added via wp_head
    }

    public function ajax_save_user_notes() {
        check_ajax_referer('psych_report_card_nonce', 'nonce');
        $context = $this->get_viewing_context();
        if (!$context['is_impersonating'] && $context['real_user_id'] !== $context['viewed_user_id']) {
            wp_send_json_error(['message' => 'مجوز دسترسی ندارید.']);
        }
        $user_id = $context['viewed_user_id'];
        $notes = sanitize_textarea_field($_POST['content']);
        $this->update_report_data($user_id, ['user_notes' => $notes]);
        wp_send_json_success();
    }

    public function ajax_save_user_goals() {
        check_ajax_referer('psych_report_card_nonce', 'nonce');
        $context = $this->get_viewing_context();
        if (!$context['is_impersonating'] && $context['real_user_id'] !== $context['viewed_user_id']) {
            wp_send_json_error(['message' => 'مجوز دسترسی ندارید.']);
        }
        $user_id = $context['viewed_user_id'];
        $goals = sanitize_textarea_field($_POST['content']);
        $this->update_report_data($user_id, ['user_goals' => $goals]);
        wp_send_json_success();
    }

    public function ajax_send_parent_report() {
        check_ajax_referer('psych_report_card_nonce', 'nonce');
        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];
        $mobile = sanitize_text_field($_POST['mobile']);
        $this->update_report_data($user_id, ['parent_mobile' => $mobile]); // Save mobile
        $report = $this->generate_report_summary($user_id);
        if ($this->send_sms($mobile, $report)) {
            wp_send_json_success(['message' => 'گزارش ارسال شد.']);
        }
        wp_send_json_error(['message' => 'خطا در ارسال SMS.']);
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
        if (self::ACTIVE_SMS_SYSTEM === 'FarazSMS') {
            $args = [
                'method' => 'POST',
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode([
                    'api_key' => self::SMS_API_KEY,
                    'sender' => self::SMS_SENDER,
                    'recipient' => $mobile,
                    'message' => $message
                ])
            ];
            $response = wp_remote_post('https://farazsms.com/api/send', $args);
            return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
        } elseif (self::ACTIVE_SMS_SYSTEM === 'IPPanel') {
            $args = [
                'method' => 'POST',
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode([
                    'apikey' => self::SMS_API_KEY,
                    'from' => self::SMS_SENDER,
                    'to' => $mobile,
                    'text' => $message
                ])
            ];
            $response = wp_remote_post('https://ippanel.com/api/send', $args);
            return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
        }
        return false; // No SMS system active
    }

    private function generate_report_summary($user_id) {
        $level_info = psych_gamification_get_user_level_info($user_id);
        $badges = get_user_meta($user_id, 'psych_user_badges', true) ?: [];
        $summary = "گزارش پیشرفت کاربر: سطح {$level_info['name']} با {$level_info['current_points']} امتیاز. نشان‌های کسب‌شده: " . implode(', ', array_map('psych_get_badge_name', $badges)) . ". پیشرفت به سطح بعدی: {$level_info['progress_percentage']}%";
        return $summary;
    }

    public function handle_form_submissions() {
        if (isset($_POST['psych_submit_notes'])) {
            check_admin_referer('psych_report_card_nonce');
            $user_id = $this->get_viewing_context()['viewed_user_id'];
            $notes = sanitize_textarea_field($_POST['psych_user_notes']);
            $this->update_report_data($user_id, ['user_notes' => $notes]);
        }
        if (isset($_POST['psych_submit_goals'])) {
            check_admin_referer('psych_report_card_nonce');
            $user_id = $this->get_viewing_context()['viewed_user_id'];
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

        // Get data from custom table
        $report_data = $this->get_report_data($user_id);

        // Get data from integrations
        $level_info = psych_gamification_get_user_level_info($user_id);
        $badges = get_user_meta($user_id, 'psych_user_badges', true) ?: [];
        $test_results = json_decode($report_data['test_results'], true) ?: []; // Sample: [['test' => 'Test1', 'score' => 85], ...]
        $path_progress = function_exists('psych_path_get_user_progress') ? psych_path_get_user_progress($user_id) : []; // Fallback to empty
        $notes = $report_data['user_notes'] ?: '';
        $goals = $report_data['user_goals'] ?: '';
        $parent_mobile = $report_data['parent_mobile'] ?: '';

        ob_start();
        ?>
        <div class="psych-report-card-container">
            <?php if ($context['is_impersonating']): ?>
                <div class="psych-impersonation-notice">در حال مشاهده به عنوان مربی: کاربر <?php echo esc_html($user->display_name); ?></div>
            <?php endif; ?>

            <div class="psych-report-card-header">
                <img src="<?php echo esc_url(get_avatar_url($user_id)); ?>" alt="Avatar" class="avatar">
                <div>
                    <h2><?php echo esc_html($user->display_name); ?></h2>
                    <div class="psych-user-status">سطح: <?php echo esc_html($level_info['name']); ?> (پیشرفت: <?php echo esc_html($level_info['progress_percentage']); ?>%)</div>
                </div>
            </div>

            <div class="psych-report-card-tabs">
                <button class="tab-link" data-tab="psych-tab-summary">خلاصه</button>
                <button class="tab-link" data-tab="psych-tab-progress">پیشرفت مسیر</button>
                <button class="tab-link" data-tab="psych-tab-tests">نتایج آزمون‌ها</button>
                <button class="tab-link" data-tab="psych-tab-notes">یادداشت‌ها و اهداف</button>
                <button class="tab-link" data-tab="psych-tab-analytics">تحلیل‌ها</button>
            </div>

            <div id="psych-tab-summary" class="psych-tab-content">
                <div class="psych-section">
                    <h3><i class="dashicons dashicons-awards"></i> خلاصه عملکرد</h3>
                    <div class="psych-summary-grid">
                        <div class="psych-summary-item">
                            <div class="value"><?php echo esc_html($level_info['current_points']); ?></div>
                            <div class="label">امتیاز کل</div>
                        </div>
                        <div class="psych-summary-item">
                            <div class="value"><?php echo count($badges); ?></div>
                            <div class="label">نشان‌های کسب‌شده</div>
                        </div>
                        <div class="psych-summary-item">
                            <div class="value"><?php echo esc_html($level_info['points_to_next']); ?></div>
                            <div class="label">امتیاز تا سطح بعدی</div>
                        </div>
                    </div>
                    <h4>نشان‌ها:</h4>
                    <div class="psych-badges-list">
                        <?php foreach ($badges as $badge_slug): ?>
                            <div class="psych-badge-item">
                                <i class="dashicons dashicons-awards"></i>
                                <p class="badge-name"><?php echo esc_html(psych_get_badge_name($badge_slug)); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div id="psych-tab-progress" class="psych-tab-content">
                <div class="psych-section">
                    <h3><i class="dashicons dashicons-location-alt"></i> پیشرفت در مسیر</h3>
                    <div class="psych-path-progress">
                        <?php if (empty($path_progress)): ?>
                            <div class="psych-alert info">داده پیشرفت مسیر موجود نیست.</div>
                        <?php else: ?>
                            <?php foreach ($path_progress as $station): ?>
                                <div class="psych-path-station <?php echo esc_attr($station['status']); ?>">
                                    <div class="psych-station-icon"><i class="dashicons dashicons-location"></i></div>
                                    <div class="psych-station-details">
                                        <h4><?php echo esc_html($station['title']); ?></h4>
                                        <p><?php echo esc_html($station['description']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="psych-tab-tests" class="psych-tab-content">
                <div class="psych-section">
                    <h3><i class="dashicons dashicons-forms"></i> نتایج آزمون‌ها</h3>
                    <table class="psych-test-results-table">
                        <thead>
                            <tr>
                                <th>نام آزمون</th>
                                <th>امتیاز</th>
                                <th>وضعیت</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($test_results)): ?>
                                <tr><td colspan="3">هیچ آزمونی ثبت نشده است.</td></tr>
                            <?php else: ?>
                                <?php foreach ($test_results as $result): ?>
                                    <tr>
                                        <td><?php echo esc_html($result['test']); ?></td>
                                        <td class="psych-score-cell <?php echo $this->get_score_class($result['score']); ?>"><?php echo esc_html($result['score']); ?>/100</td>
                                        <td><?php echo esc_html($this->get_score_status($result['score'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="psych-tab-notes" class="psych-tab-content">
                <div class="psych-section">
                    <h3><i class="dashicons dashicons-edit"></i> یادداشت‌ها</h3>
                    <form method="post">
                        <?php wp_nonce_field('psych_report_card_nonce'); ?>
                        <textarea id="psych-user-notes" name="psych_user_notes"><?php echo esc_textarea($notes); ?></textarea>
                        <button type="submit" name="psych_submit_notes" class="psych-button">ذخیره یادداشت‌ها</button>
                    </form>
                </div>
                <div class="psych-section">
                    <h3><i class="dashicons dashicons-flag"></i> اهداف</h3>
                    <form method="post">
                        <?php wp_nonce_field('psych_report_card_nonce'); ?>
                        <textarea id="psych-user-goals" name="psych_user_goals"><?php echo esc_textarea($goals); ?></textarea>
                        <button type="submit" name="psych_submit_goals" class="psych-button">ذخیره اهداف</button>
                    </form>
                </div>
                <div class="psych-section">
                    <h3><i class="dashicons dashicons-email"></i> ارسال گزارش به والدین</h3>
                    <input type="text" id="parent-mobile" value="<?php echo esc_attr($parent_mobile); ?>" placeholder="شماره موبایل والدین">
                    <button id="send-parent-report" class="psych-button warning">ارسال گزارش به والدین</button>
                </div>
            </div>

            <div id="psych-tab-analytics" class="psych-tab-content">
                <div class="psych-section">
                    <h3><i class="dashicons dashicons-chart-line"></i> تحلیل پیشرفت</h3>
                    <div class="psych-chart-container">
                        <canvas id="psych-progress-chart" data-chart-data='{"labels": ["هفته 1", "هفته 2", "هفته 3"], "points": [<?php echo esc_attr($level_info['current_points'] / 3); ?>, <?php echo esc_attr($level_info['current_points'] / 2); ?>, <?php echo esc_attr($level_info['current_points']); ?>]}'></canvas>
                    </div>
                    <div class="psych-chart-container">
                        <canvas id="psych-badges-chart" data-chart-data='{"earned": <?php echo count($badges); ?>, "remaining": <?php echo 10 - count($badges); ?>}'></canvas>
                    </div>
                </div>
            </div>
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
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT 1", $user_id), ARRAY_A);
        if (!$row) {
            // Create initial row if not exists
            $wpdb->insert($table_name, ['user_id' => $user_id]);
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id), ARRAY_A);
        }
        return $row;
    }

    private function update_report_data($user_id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::REPORTS_TABLE;
        $existing = $this->get_report_data($user_id);
        $update_data = [];
        if (isset($data['test_results'])) $update_data['test_results'] = json_encode($data['test_results']);
        if (isset($data['parent_mobile'])) $update_data['parent_mobile'] = $data['parent_mobile'];
        if (isset($data['user_notes'])) $update_data['user_notes'] = $data['user_notes'];
        if (isset($data['user_goals'])) $update_data['user_goals'] = $data['user_goals'];
        return $wpdb->update($table_name, $update_data, ['id' => $existing['id']]);
    }

    // Prevent cloning and unserializing
    private function __clone() {}
    public function __wakeup() {}
}

// Initialize the module
Psych_Unified_Report_Card_Enhanced::get_instance();

// =====================================================================
// SHORTCODE: psych_report_card (Updated with quiz tab and integration)
// =====================================================================

function psych_shortcode_report_card($atts) {
    $atts = shortcode_atts(['user_id' => get_current_user_id(), 'test_id' => ''], $atts);
    $user_id = intval($atts['user_id']);

    // Fetch results from custom table (PSYCH_RESULTS_TABLE or wp_psych_reports)
    global $wpdb;
    $table_name = $wpdb->prefix . Psych_Unified_Report_Card_Enhanced::REPORTS_TABLE; // Use custom table from class
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d",
        $user_id
    ), ARRAY_A);

    // Also fetch from quiz table for integration
    $quiz_results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM wp_psych_quiz_results WHERE user_id = %d ORDER BY timestamp DESC",
        $user_id
    ));

    // Fetch goals and notes from reports table
    $report_data = $wpdb->get_row($wpdb->prepare(
        "SELECT user_goals, user_notes FROM $table_name WHERE user_id = %d",
        $user_id
    ));
    $goals = $report_data ? json_decode($report_data->user_goals, true) : []; // Assuming stored as JSON array
    $notes = $report_data ? esc_html($report_data->user_notes) : 'هیچ یادداشتی موجود نیست.';

    ob_start();
    ?>
    <div class="psych-report-card-container">
        <?php if (psych_is_coach_impersonating()): ?>
            <div class="psych-impersonation-notice">شما در حال مشاهده گزارش به عنوان مربی هستید.</div>
        <?php endif; ?>

        <div class="psych-report-card-tabs">
            <button class="tab-link active" data-tab="summary">خلاصه</button>
            <button class="tab-link" data-tab="tests">آزمون‌ها</button>
            <button class="tab-link" data-tab="quiz">کوئیزها</button> <!-- New: Quiz tab -->
            <button class="tab-link" data-tab="goals">اهداف</button>
            <button class="tab-link" data-tab="notes">یادداشت‌ها</button>
        </div>

        <div id="psych-tab-summary" class="psych-tab-content active">
            <h3>خلاصه گزارش</h3>
            <!-- Existing summary content, integrate with results -->
            <p>کاربر: <?php echo esc_html(get_userdata($user_id)->display_name); ?></p>
            <p>تعداد آزمون‌ها: <?php echo count($results); ?></p>
            <p>تعداد کوئیزها: <?php echo count($quiz_results); ?></p> <!-- New -->
        </div>

        <div id="psych-tab-tests" class="psych-tab-content">
            <h3>نتایج آزمون‌ها</h3>
            <table class="psych-test-results-table">
                <thead><tr><th>آزمون ID</th><th>امتیاز</th><th>تاریخ</th></tr></thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                        <tr>
                            <td><?php echo esc_html($result['test_id'] ?? 'نامشخص'); ?></td>
                            <td><?php echo esc_html($result['score'] ?? 'N/A'); ?></td>
                            <td><?php echo esc_html($result['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- New: Quiz Tab -->
        <div id="psych-tab-quiz" class="psych-tab-content">
            <h3>نتایج کوئیزهای پیشرفته</h3>
            <?php if (empty($quiz_results)): ?>
                <p>هنوز کوئیزی تکمیل نشده است.</p>
            <?php else: ?>
                <table class="psych-test-results-table">
                    <thead><tr><th>کوئیز ID</th><th>امتیاز</th><th>زمان گرفته‌شده</th><th>تحلیل AI</th></tr></thead>
                    <tbody>
                        <?php foreach ($quiz_results as $quiz): ?>
                            <tr>
                                <td><?php echo esc_html($quiz->quiz_id); ?></td>
                                <td><?php echo esc_html($quiz->score); ?></td>
                                <td><?php echo esc_html($quiz->time_taken); ?> ثانیه</td>
                                <td><?php echo esc_html(substr($quiz->ai_analysis, 0, 100)); ?>...</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Updated: Goals Tab with real data -->
        <div id="psych-tab-goals" class="psych-tab-content">
            <h3>اهداف کاربر</h3>
            <?php if (empty($goals)): ?>
                <p>هیچ هدفی تعریف نشده است.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($goals as $goal): ?>
                        <li><?php echo esc_html($goal); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Updated: Notes Tab with real data -->
        <div id="psych-tab-notes" class="psych-tab-content">
            <h3>یادداشت‌ها</h3>
            <p><?php echo $notes; ?></p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}


// New shortcodes from user request
add_shortcode('psych_test_result', function($atts) {
    $atts = shortcode_atts(['test_id' => '', 'type' => 'score', 'key' => ''], $atts);
    $user_id = get_current_user_id();
    $report_data = Psych_Unified_Report_Card_Enhanced::get_instance()->get_report_data($user_id);
    $results = json_decode($report_data['test_results'], true) ?: [];
    foreach ($results as $result) {
        if ($result['test_id'] == $atts['test_id']) {
            if ($atts['type'] == 'score') return $result['score'];
            if ($atts['type'] == 'subscale' && isset($result['subscales'][$atts['key']])) return $result['subscales'][$atts['key']];
        }
    }
    return 'No result found';
});

add_shortcode('psych_ai_report', function($atts) {
    $atts = shortcode_atts(['test_id' => '', 'format' => 'full'], $atts);
    $user_id = get_current_user_id();
    $report_data = Psych_Unified_Report_Card_Enhanced::get_instance()->get_report_data($user_id);
    $results = json_decode($report_data['test_results'], true) ?: [];
    $ai_report = '';
    foreach ($results as $result) {
        if ($result['test_id'] == $atts['test_id'] && isset($result['ai_analysis'])) {
            $ai_report = $result['ai_analysis'];
            break;
        }
    }
    if (empty($ai_report)) {
        // Call AI API and store
        if (function_exists('call_ai_api')) {
            $ai_report = call_ai_api($atts['test_id'], $user_id);
            // Update in custom table
            $new_results = $results;
            $new_results[] = ['test_id' => $atts['test_id'], 'ai_analysis' => $ai_report];
            Psych_Unified_Report_Card_Enhanced::get_instance()->update_report_data($user_id, ['test_results' => $new_results]);
            do_action('psych_ai_result_stored', $user_id, $atts['test_id']);
        } else {
            $ai_report = 'AI analysis not available.';
        }
    }
    if ($atts['format'] == 'summary') {
        return substr($ai_report, 0, 100) . '...';
    }
    return $ai_report;
});

// Integration with other modules (assuming functions exist)
function psych_integrate_with_coach($user_id) {
    // Placeholder for integration
}

function psych_integrate_with_path_engine($user_id) {
    // Placeholder
}

function psych_integrate_with_gamification($user_id) {
    // Placeholder
}

// Additional functions for full integration (expanded for completeness)
function psych_get_user_progress_summary($user_id) {
    // Expanded logic for progress summary
    $progress = [];
    // Add more detailed calculations here
    return $progress;
}

function psych_generate_detailed_report($user_id) {
    // Generate a detailed report string
    return "Detailed report for user $user_id";
}

// More hooks for advanced notifications
add_action('user_register', function($user_id) {
    // Send welcome report
    do_action('psych_send_user_report_sms', $user_id);
});

add_action('psych_level_up', function($user_id) {
    // Notify on level up
    do_action('psych_send_user_report_sms', $user_id);
});
?>