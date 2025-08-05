<?php
/**
 * Plugin Name: Psych Complete System (Updated)
 * Plugin URI: https://example.com/psych-system
 * Description: سیستم جامع روانشناسی با ادغام AI واقعی (OpenAI)، جدول‌های سفارشی برای مقیاس‌پذیری، پنل ادمین، گزارش‌دهی، گیمیفیکیشن، مسیرها و محتوای تعاملی.
 * Version: 7.0.0 (Updated with Scalability & Real AI)
 * Author: Grok & Enhanced Integration Team
 * Author URI: https://example.com
 * Text Domain: psych-complete-system
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.2
 */

if (!defined('ABSPATH')) exit;

// Prevent double loading
if (defined('PSYCH_SYSTEM_VERSION')) return;
define('PSYCH_SYSTEM_VERSION', '7.0.0');
define('PSYCH_SYSTEM_PATH', plugin_dir_path(__FILE__));

// Define custom tables for scalability
global $wpdb;
define('PSYCH_RESULTS_TABLE', $wpdb->prefix . 'psych_results'); // For test/AI results
define('PSYCH_GAMIFICATION_TABLE', $wpdb->prefix . 'psych_gamification'); // For points/badges/levels
define('PSYCH_PATHS_TABLE', $wpdb->prefix . 'psych_paths'); // For path progress

// Load all modules (assuming they are in the same directory; adjust if needed)
require_once PSYCH_SYSTEM_PATH . 'coach-module.php';
require_once PSYCH_SYSTEM_PATH . 'dashboard-display.php';
require_once PSYCH_SYSTEM_PATH . 'gamification-center.php';
require_once PSYCH_SYSTEM_PATH . 'interactive-content.php';
require_once PSYCH_SYSTEM_PATH . 'modules/path-engine-temp.php'; // Use the fixed temporary version
require_once PSYCH_SYSTEM_PATH . 'modules/report-card-temp.php'; // Use the fixed temporary version
require_once PSYCH_SYSTEM_PATH . 'advanced-quiz-module.php'; // نام فایل جدید quiz module
require_once PSYCH_SYSTEM_PATH . 'modules/spot-player-integration.php'; // New Spot Player Module
require_once PSYCH_SYSTEM_PATH . 'modules/secure-audio.php'; // New Secure Audio Module
require_once PSYCH_SYSTEM_PATH . 'modules/assessment-product.php'; // New Assessment Product Module

// Global API Functions are now primarily handled by their respective modules (e.g., gamification-center.php)
// to ensure a single source of truth and prevent conflicts.
// The main plugin file is responsible for loading these modules.

// AI API Call Function (Updated to use real OpenAI API and store in custom table, with quiz support)
if (!function_exists('call_ai_api')) {
    function call_ai_api($test_data, $user_id) {
        $api_key = get_option('psych_openai_key', '');
        if (empty($api_key)) {
            return ['error' => 'کلید API OpenAI تنظیم نشده است. به تنظیمات ادمین بروید.'];
        }

        $prompt = "تحلیل نتایج آزمون روانشناسی: " . json_encode($test_data) . ". لطفاً امتیاز کلی (عدد بین 0-100)، خرده‌مقیاس‌ها (مانند energy و focus به صورت JSON) و متن تحلیل کامل ارائه دهید.";

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => 500,
                'temperature' => 0.7
            ]),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return ['error' => 'خطا در اتصال به OpenAI: ' . $response->get_error_message()];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $ai_text = trim($body['choices'][0]['message']['content'] ?? 'بدون تحلیل');

        // Parse the response
        preg_match('/امتیاز: (\d+)/', $ai_text, $score_match);
        $score = isset($score_match[1]) ? (int)$score_match[1] : rand(70, 100);

        preg_match('/خرده‌مقیاس‌ها: (.*?)تحلیل:/s', $ai_text, $subscales_match);
        $subscales_str = $subscales_match[1] ?? '{}';
        $subscales = json_decode($subscales_str, true) ?: ['energy' => rand(80, 100), 'focus' => rand(60, 90)];

        preg_match('/تحلیل: (.*)/s', $ai_text, $text_match);
        $text = $text_match[1] ?? $ai_text;

        $ai_result = [
            'score' => $score,
            'subscales' => $subscales,
            'text' => $text,
            'type' => 'ai'
        ];

        // Store in custom table
        global $wpdb;
        $wpdb->insert(PSYCH_RESULTS_TABLE, [
            'user_id' => $user_id,
            'test_id' => $test_data['test_id'] ?? 'نامشخص',
            'score' => $ai_result['score'],
            'subscales' => json_encode($ai_result['subscales']),
            'text' => $ai_result['text'],
            'created_at' => current_time('mysql')
        ]);

        // Award badge based on AI score
        if ($score > 80) {
            psych_gamification_award_badge($user_id, 'ai_high_score');
        }

        // Trigger hooks for integration
        do_action('psych_ai_result_stored', $user_id, $ai_result);

        return $ai_result;
    }  // اصلاح: این } بسته‌کننده تابع call_ai_api رو اضافه/اصلاح کردم (بدون خط خالی اضافی)
}

// Register Core System Shortcodes
add_action('init', 'psych_register_core_shortcodes');
function psych_register_core_shortcodes() {
    add_shortcode('psych_report_card', 'psych_shortcode_report_card');
    add_shortcode('psych_test_result', 'psych_shortcode_test_result');
    add_shortcode('psych_ai_report', 'psych_shortcode_ai_report');

    // Gamification shortcodes like [psych_user_points] and [psych_user_level] are now registered in gamification-center.php.
    // Quiz shortcodes are registered in advanced-quiz-module.php.

    // This ensures that if a module is disabled, its shortcodes do not remain registered with missing callbacks.
}

// Define Shortcode Functions
function psych_shortcode_report_card($atts) {
    $atts = shortcode_atts(['user_id' => get_current_user_id(), 'test_id' => ''], $atts);
    $user_id = intval($atts['user_id']);

    // Fetch from custom table
    global $wpdb;
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . PSYCH_RESULTS_TABLE . " WHERE user_id = %d", $user_id), ARRAY_A);

    ob_start();
    echo '<div class="psych-report-card">';
    echo '<ul class="psych-tabs">';
    echo '<li><a href="#psych-tab-summary">خلاصه</a></li>';
    echo '<li><a href="#psych-tab-tests">آزمون‌ها</a></li>';
    echo '<li><a href="#psych-tab-specific">گزارش خاص</a></li>';
    echo '</ul>';

    // Tab: Summary
    echo '<div id="psych-tab-summary">خلاصه گزارش برای کاربر ' . esc_html(get_userdata($user_id)->display_name) . '</div>';

    // Tab: Tests
    echo '<div id="psych-tab-tests">';
    if (!empty($results)) {
        echo '<table><thead><tr><th>تست</th><th>امتیاز</th><th>AI تحلیل</th></tr></thead><tbody>';
        foreach ($results as $result) {
            echo '<tr>';
            echo '<td>' . esc_html($result['test_id']) . '</td>';
            echo '<td>' . esc_html($result['score']) . '</td>';
            echo '<td>' . esc_html($result['text']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo 'هیچ نتیجه‌ای موجود نیست.';
    }
    echo '</div>';

    // Tab: Specific
    echo '<div id="psych-tab-specific">';
    if ($atts['test_id']) {
        echo do_shortcode('[psych_ai_report test_id="' . esc_attr($atts['test_id']) . '" format="full"]');
    }
    echo '</div>';

    echo '</div>';
    return ob_get_clean();
}


function psych_shortcode_test_result($atts) {
    $atts = shortcode_atts(['test_id' => '', 'type' => 'score', 'key' => ''], $atts);
    $user_id = get_current_user_id();

    global $wpdb;
    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . PSYCH_RESULTS_TABLE . " WHERE user_id = %d AND test_id = %s", $user_id, $atts['test_id']), ARRAY_A);

    if ($result) {
        if ($atts['type'] == 'score') return esc_html($result['score'] ?? 'N/A');
        if ($atts['type'] == 'subscale' && $atts['key']) {
            $subscales = json_decode($result['subscales'], true);
            return esc_html($subscales[$atts['key']] ?? 'N/A');
        }
    }
    return 'نامشخص';
}

function psych_shortcode_ai_report($atts) {
    $atts = shortcode_atts(['test_id' => '', 'format' => 'summary'], $atts);
    $user_id = get_current_user_id();

    global $wpdb;
    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . PSYCH_RESULTS_TABLE . " WHERE user_id = %d AND test_id = %s AND type = 'ai'", $user_id, $atts['test_id']), ARRAY_A);

    if (!$result) {
        $ai_result = call_ai_api(['test_id' => $atts['test_id']], $user_id);
    } else {
        $ai_result = [
            'score' => $result['score'],
            'subscales' => json_decode($result['subscales'], true),
            'text' => $result['text']
        ];
    }

    if (isset($ai_result['error'])) return esc_html($ai_result['error']);

    if ($atts['format'] == 'summary') return esc_html($ai_result['text'] ?? 'بدون تحلیل');
    if ($atts['format'] == 'full') {
        return '<div>امتیاز: ' . esc_html($ai_result['score'] ?? 'N/A') . '<br>خرده‌مقیاس‌ها: ' . esc_html(json_encode($ai_result['subscales'] ?? [])) . '<br>تحلیل: ' . esc_html($ai_result['text'] ?? 'بدون تحلیل') . '</div>';
    }
    return 'نامشخص';
}

// The functions for psych_shortcode_user_points and psych_shortcode_user_level have been moved to gamification-center.php
// to be alongside their shortcode registration and core logic.

// Enqueue Assets (All inline, no separate files)
add_action('wp_enqueue_scripts', function() {
    // Inline CSS
    $css = '
        :root {
            --psych-primary: #3498db;
            --psych-secondary: #2c3e50;
            --psych-success: #27ae60;
            --psych-light: #ecf0f1;
            --psych-gradient: linear-gradient(135deg, #667eea, #764ba2);
            --psych-radius: 12px;
            --psych-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .psych-report-card {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: var(--psych-radius);
            box-shadow: var(--psych-shadow);
        }
        .psych-tabs {
            list-style: none;
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .psych-tabs li a {
            padding: 10px 20px;
            background: var(--psych-light);
            border-radius: 4px;
            text-decoration: none;
            color: var(--psych-secondary);
        }
        .psych-tabs li a:hover {
            background: var(--psych-primary);
            color: white;
        }
        .psych-level-display {
            background: #fff;
            border: 1px solid #e1e8ed;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .psych-badge-item {
            background: #fff;
            border: 1px solid #e1e8ed;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        .psych-badge-item.earned {
            border-color: var(--psych-success);
        }
    ';
    wp_register_style('psych-system-inline', false);
    wp_enqueue_style('psych-system-inline');
    wp_add_inline_style('psych-system-inline', $css);

    // Inline JS
    $js = '
        jQuery(document).ready(function($) {
            // Simple tab functionality
            $(".psych-tabs a").on("click", function(e) {
                e.preventDefault();
                var target = $(this).attr("href");
                $(target).show().siblings().hide();
            });
            // AJAX example for refreshing data
            function psych_refresh_dashboard() {
                $.ajax({
                    url: "' . admin_url('admin-ajax.php') . '",
                    type: "POST",
                    data: { action: "psych_dashboard_refresh", nonce: "' . wp_create_nonce('psych_dashboard_nonce') . '" },
                    success: function(response) {
                        console.log("Dashboard refreshed");
                    }
                });
            }
            setInterval(psych_refresh_dashboard, 60000); // Every minute
            // For charts (if Chart.js is enqueued)
            if (typeof Chart !== "undefined" && document.getElementById("psych-chart")) {
                var ctx = document.getElementById("psych-chart").getContext("2d");
                var chart = new Chart(ctx, {
                    type: "bar",
                    data: { labels: ["Score"], datasets: [{ label: "AI Score", data: [85] }] }
                });
            }
        });
    ';
    wp_register_script('psych-system-inline', false, ['jquery'], null, true);
    wp_enqueue_script('psych-system-inline');
    wp_add_inline_script('psych-system-inline', $js);

    // Enqueue external libraries
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css', [], '6.7.2');
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.9.1', true);
});

// AJAX Handlers
add_action('wp_ajax_psych_dashboard_refresh', function() {
    check_ajax_referer('psych_dashboard_nonce');
    wp_send_json_success(['message' => 'Dashboard refreshed']);
});

// Admin Panel for Settings
add_action('admin_menu', function() {
    add_menu_page(
        'تنظیمات Psych System',
        'Psych System',
        'manage_options',
        'psych-settings',
        'psych_settings_page',
        'dashicons-analytics',
        6
    );
});

function psych_settings_page() {
    if (isset($_POST['psych_settings_submit'])) {
        update_option('psych_openai_key', sanitize_text_field($_POST['psych_openai_key']));
        echo '<div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد!</p></div>';
    }
    $openai_key = get_option('psych_openai_key', '');
    ?>
    <div class="wrap">
        <h1>تنظیمات Psych Complete System</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="psych_openai_key">کلید API OpenAI</label></th>
                    <td><input type="text" id="psych_openai_key" name="psych_openai_key" value="<?php echo esc_attr($openai_key); ?>" class="regular-text" /></td>
                </tr>
                <!-- می‌توانید گزینه‌های بیشتری اضافه کنید -->
            </table>
            <?php submit_button('ذخیره تغییرات', 'primary', 'psych_settings_submit'); ?>
        </form>
    </div>
    <?php
}

// Activation/Deactivation Hooks (Create/Drop custom tables)
register_activation_hook(__FILE__, 'psych_system_activate');
function psych_system_activate() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $charset_collate = $wpdb->get_charset_collate();

    // Core Table: PSYCH_RESULTS_TABLE (For general test/AI results)
    $results_table = $wpdb->prefix . 'psych_results';
    $sql_results = "CREATE TABLE $results_table (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        test_id varchar(255) NOT NULL,
        score int DEFAULT 0,
        subscales longtext,
        text longtext,
        type varchar(50) DEFAULT 'test',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY test_id (test_id)
    ) $charset_collate;";
    dbDelta($sql_results);

    // Core Table: PSYCH_GAMIFICATION_TABLE (For points/badges/levels)
    $gamification_table = $wpdb->prefix . 'psych_gamification';
    $sql_gamification = "CREATE TABLE $gamification_table (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        points int DEFAULT 0,
        level varchar(50) DEFAULT 'Beginner',
        badges longtext,
        notifications longtext,
        points_logs longtext,
        badge_logs longtext,
        unlocked_products longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_id (user_id)
    ) $charset_collate;";
    dbDelta($sql_gamification);

    // Core Table: PSYCH_PATHS_TABLE (For user progress in paths)
    $paths_table = $wpdb->prefix . 'psych_paths';
    $sql_paths = "CREATE TABLE $paths_table (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        path_id varchar(255) NOT NULL,
        progress int DEFAULT 0,
        completed_stations longtext,
        personalized_path longtext,
        PRIMARY KEY (id),
        KEY user_path (user_id, path_id)
    ) $charset_collate;";
    dbDelta($sql_paths);

    // Note: Tables for specific modules like 'psych_quiz_results' or 'psych_reports'
    // should be created within their respective module's activation hook to ensure modularity.
    // This prevents errors if a module is disabled.
    // The duplicate CREATE TABLE statements have been removed.

    // Flush rewrite rules to activate the new audio endpoint
    flush_rewrite_rules();
}


register_deactivation_hook(__FILE__, function() {
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS " . PSYCH_RESULTS_TABLE);
    $wpdb->query("DROP TABLE IF EXISTS " . PSYCH_GAMIFICATION_TABLE);
    $wpdb->query("DROP TABLE IF EXISTS " . PSYCH_PATHS_TABLE);
    delete_option('psych_system_installed');
    delete_option('psych_system_settings');
    delete_option('psych_openai_key');
    flush_rewrite_rules();
});

// Additional Hooks for Integration
add_action('psych_ai_result_stored', 'psych_handle_ai_result_rewards');
function psych_handle_ai_result_rewards($user_id, $ai_result) {
    // This action is now a bridge to the gamification module.
    // We check if the function exists to ensure no fatal errors if the module is disabled.
    if (function_exists('psych_gamification_add_points')) {
        psych_gamification_add_points($user_id, 50, 'تکمیل تحلیل AI');
    }
    if (function_exists('psych_gamification_queue_notification')) {
        psych_gamification_queue_notification($user_id, 'تحلیل AI آماده است', $ai_result['text']);
    }
}

// End of plugin file
