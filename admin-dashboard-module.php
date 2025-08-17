<?php
/**
 * Admin User Dashboard Module for Psych Complete System
 *
 * Provides a centralized dashboard for admins to view and manage individual user data,
 * including path progress, gamification stats, and personal notes.
 *
 * @package Psych_Complete_System
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
    /**
     * AJAX handler to get all data for a selected user's dashboard.
     */
    public function ajax_get_user_dashboard_data() {
        check_ajax_referer('psych_user_dashboard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) {
            wp_send_json_error(['message' => 'شناسه کاربر نامعتبر است.']);
        }

        $user_data = get_userdata($user_id);
        if (!$user_data) {
            wp_send_json_error(['message' => 'کاربر یافت نشد.']);
        }

        // --- Path Progress Data ---
        // NOTE: This is a simplified implementation due to architectural limitations.
        // We can only see completed stations, not the full path structure.
        $completed_stations_raw = get_user_meta($user_id, 'psych_path_completed_stations', true) ?: [];
        $completed_stations_html = '<h4>ایستگاه‌های تکمیل شده</h4>';
        if (!empty($completed_stations_raw)) {
            $completed_stations_html .= '<ul class="psych-station-list">';
            foreach ($completed_stations_raw as $node_id => $details) {
                $completed_at = $details['completed_at'] ?? 'N/A';
                $completed_stations_html .= sprintf(
                    '<li><code>%s</code> (تکمیل در: %s) <button class="button button-small psych-mark-incomplete" data-node-id="%s">علامت به عنوان تکمیل نشده</button></li>',
                    esc_html($node_id),
                    esc_html($completed_at),
                    esc_attr($node_id)
                );
            }
            $completed_stations_html .= '</ul>';
        } else {
            $completed_stations_html .= '<p>این کاربر هنوز هیچ ایستگاهی را تکمیل نکرده است.</p>';
        }

        $completed_stations_html .= '
            <hr>
            <h4>تکمیل دستی ایستگاه</h4>
            <p>شناسه ایستگاه (Node ID) را برای تکمیل وارد کنید:</p>
            <input type="text" id="psych-manual-node-id" placeholder="e.g., st_path_xyz_1">
            <button class="button button-secondary" id="psych-mark-complete-manual">تکمیل کن</button>
            <p class="description">محدودیت: به دلیل اینکه مسیرها به صورت داینامیک در صفحات ساخته می‌شوند، لیستی از تمام ایستگاه‌های موجود وجود ندارد. برای تکمیل دستی، باید شناسه دقیق ایستگاه را بدانید.</p>
        ';


        // --- Gamification Data ---
        $gamification_html = '<h4>امتیازات و سطوح</h4>';
        if (class_exists('Psych_Gamification_Center')) {
            $gamification_instance = Psych_Gamification_Center::get_instance();
            $points = (int) get_user_meta($user_id, 'psych_total_points', true);
            $level_info = $gamification_instance->get_user_level($user_id);

            $gamification_html .= "<p><strong>امتیاز کل:</strong> " . number_format_i18n($points) . "</p>";
            $gamification_html .= "<p><strong>سطح فعلی:</strong> " . esc_html($level_info['name']) . "</p>";
            $gamification_html .= '
                <div class="psych-admin-form-inline">
                    <input type="number" id="psych-points-change" placeholder="مثلا: 50 یا -50">
                    <input type="text" id="psych-points-reason" placeholder="دلیل (اختیاری)">
                    <button class="button button-secondary" id="psych-update-points-btn">اعمال تغییر امتیاز</button>
                </div>
            ';

            $gamification_html .= '<hr><h4>نشان‌ها</h4>';
            $all_badges = $gamification_instance->get_badges();
            $user_badges = get_user_meta($user_id, 'psych_user_badges', true) ?: [];

            $gamification_html .= '<div class="psych-badges-grid">';
            foreach ($all_badges as $slug => $badge) {
                $has_badge = in_array($slug, $user_badges);
                $gamification_html .= sprintf(
                    '<div class="psych-badge-item">
                        <span class="psych-badge-icon" style="color:%s;"><i class="%s"></i></span>
                        <span class="psych-badge-name">%s</span>
                        <button class="button %s" data-badge-slug="%s">%s</button>
                    </div>',
                    esc_attr($badge['color']),
                    esc_attr($badge['icon']),
                    esc_html($badge['name']),
                    $has_badge ? 'button-secondary psych-toggle-badge owned' : 'button-primary psych-toggle-badge',
                    esc_attr($slug),
                    $has_badge ? 'حذف نشان' : 'اعطای نشان'
                );
            }
            $gamification_html .= '</div>';

        } else {
            $gamification_html = '<p>ماژول گیمیفیکیشن فعال نیست.</p>';
        }


        // --- User Details & Notes ---
        $referral_count = (int) get_user_meta($user_id, 'psych_referral_count', true);
        $feedback_count = (int) get_user_meta($user_id, 'psych_feedback_received_count', true);
        $notebook_content = get_user_meta($user_id, 'psych_user_notebook', true);

        $details_html = sprintf(
            '<h4>آمار دیگر</h4><ul><li>تعداد دعوت موفق: %d</li><li>تعداد بازخورد دریافتی: %d</li></ul><hr>',
            $referral_count,
            $feedback_count
        );

        $details_html .= sprintf(
            '<h4>دفترچه یادداشت کاربر</h4>
            <textarea id="psych-user-notebook-content" rows="10" style="width:100%%;">%s</textarea>
            <button class="button button-primary" id="psych-save-notebook-btn">ذخیره یادداشت</button>',
            esc_textarea($notebook_content)
        );


        $data = [
            'userName' => $user_data->display_name . ' (' . $user_data->user_email . ')',
            'tabs' => [
                'path_progress' => $completed_stations_html,
                'gamification' => $gamification_html,
                'details' => $details_html
            ]
        ];

        wp_send_json_success($data);
    }

    /**
     * AJAX handler to mark a station as complete or incomplete for a user.
     */
    public function ajax_mark_station_status() {
        check_ajax_referer('psych_user_dashboard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $node_id = isset($_POST['node_id']) ? sanitize_key($_POST['node_id']) : '';
        $action  = isset($_POST['action']) ? sanitize_key($_POST['action']) : '';

        if (!$user_id || empty($node_id) || empty($action)) {
            wp_send_json_error(['message' => 'اطلاعات ناقص است.']);
        }

        $completed_stations = get_user_meta($user_id, 'psych_path_completed_stations', true) ?: [];

        if ($action === 'psych_mark_station_complete') {
            if (!isset($completed_stations[$node_id])) {
                $completed_stations[$node_id] = ['completed_at' => current_time('mysql'), 'completed_by' => 'admin'];
            }
        } elseif ($action === 'psych_mark_station_incomplete') {
            if (isset($completed_stations[$node_id])) {
                unset($completed_stations[$node_id]);
            }
        }

        update_user_meta($user_id, 'psych_path_completed_stations', $completed_stations);

        // Clear cache to ensure frontend reflects the change immediately.
        wp_cache_delete($user_id, 'user_meta');

        wp_send_json_success(['message' => 'وضعیت ایستگاه با موفقیت تغییر کرد.']);
    }

    /**
     * AJAX handler to update user points.
     */
    public function ajax_admin_update_points() {
        check_ajax_referer('psych_user_dashboard_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $points  = isset($_POST['points']) ? intval($_POST['points']) : 0;
        $reason  = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : 'تغییر توسط مدیر';

        if (!$user_id || $points === 0) {
            wp_send_json_error(['message' => 'اطلاعات ناقص است.']);
        }

        if (class_exists('Psych_Gamification_Center')) {
            Psych_Gamification_Center::get_instance()->add_points($user_id, $points, $reason);
            wp_send_json_success(['message' => 'امتیاز کاربر با موفقیت بروزرسانی شد.']);
        } else {
            wp_send_json_error(['message' => 'ماژول گیمیفیکیشن فعال نیست.']);
        }
    }

    /**
     * AJAX handler to award or revoke a badge.
     */
    public function ajax_admin_toggle_badge() {
        check_ajax_referer('psych_user_dashboard_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        $user_id    = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $badge_slug = isset($_POST['badge_slug']) ? sanitize_key($_POST['badge_slug']) : '';

        if (!$user_id || empty($badge_slug)) {
            wp_send_json_error(['message' => 'اطلاعات ناقص است.']);
        }

        if (class_exists('Psych_Gamification_Center')) {
            $user_badges = get_user_meta($user_id, 'psych_user_badges', true) ?: [];
            $has_badge = in_array($badge_slug, $user_badges);

            if ($has_badge) {
                // Revoke badge
                $new_badges = array_diff($user_badges, [$badge_slug]);
                update_user_meta($user_id, 'psych_user_badges', $new_badges);
                wp_cache_delete($user_id, 'user_meta');
                wp_send_json_success(['message' => 'نشان با موفقیت حذف شد.']);
            } else {
                // Award badge
                Psych_Gamification_Center::get_instance()->award_badge($user_id, $badge_slug);
                wp_send_json_success(['message' => 'نشان با موفقیت اعطا شد.']);
            }
        } else {
            wp_send_json_error(['message' => 'ماژول گیمیفیکیشن فعال نیست.']);
        }
    }

    /**
     * AJAX handler to save user's notebook content.
     */
    public function ajax_admin_save_note() {
        check_ajax_referer('psych_user_dashboard_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';

        if (!$user_id) {
            wp_send_json_error(['message' => 'کاربر نامعتبر است.']);
        }

        update_user_meta($user_id, 'psych_user_notebook', $content);
        wp_cache_delete($user_id, 'user_meta');

        wp_send_json_success(['message' => 'یادداشت با موفقیت ذخیره شد.']);
    }
}

// Prevent direct execution.
if (!defined('PSYCH_SYSTEM_LOADED')) {
    return;
}

/**
 * Psych_Admin_Dashboard_Module Class
 */
final class Psych_Admin_Dashboard_Module {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get single instance of the class
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // This is a placeholder. The main system will call the init method.
    }

    /**
     * Initialize hooks
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_admin_submenu_page'], 99);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_psych_search_users', [$this, 'ajax_search_users']);
        add_action('wp_ajax_psych_get_user_dashboard_data', [$this, 'ajax_get_user_dashboard_data']);
        add_action('wp_ajax_psych_mark_station_complete', [$this, 'ajax_mark_station_status']);
        add_action('wp_ajax_psych_mark_station_incomplete', [$this, 'ajax_mark_station_status']);
        add_action('wp_ajax_psych_admin_update_points', [$this, 'ajax_admin_update_points']);
        add_action('wp_ajax_psych_admin_toggle_badge', [$this, 'ajax_admin_toggle_badge']);
        add_action('wp_ajax_psych_admin_save_note', [$this, 'ajax_admin_save_note']);
    }

    /**
     * Add the submenu page under the main plugin menu.
     */
    public function add_admin_submenu_page() {
        add_submenu_page(
            'psych-system', // Parent slug
            'داشبورد کاربر', // Page title
            'داشبورد کاربر', // Menu title
            'manage_options', // Capability
            'psych-user-dashboard', // Menu slug
            [$this, 'render_dashboard_page'] // Callback function
        );
    }

    /**
     * Render the main dashboard page content.
     */
    public function render_dashboard_page() {
        ?>
        <div class="wrap psych-admin-dashboard">
            <h1><span class="dashicons dashicons-dashboard" style="color:#2271b1;"></span> داشبورد کاربر</h1>
            <p>در این صفحه می‌توانید اطلاعات یک کاربر خاص را مشاهده و مدیریت کنید.</p>

            <!-- User Search Section -->
            <div class="psych-admin-card">
                <h3>جستجوی کاربر</h3>
                <p>برای شروع، کاربر مورد نظر را با نام، ایمیل یا شناسه کاربری جستجو کنید.</p>
                <input type="text" id="psych-user-search-input" placeholder="جستجو..." style="width: 100%; max-width: 400px; padding: 8px;">
                <div id="psych-user-search-results"></div>
            </div>

            <!-- User Data Section (hidden by default) -->
            <div id="psych-user-data-container" style="display: none;">
                <h2 id="psych-selected-user-name"></h2>
                <div class="nav-tab-wrapper">
                    <a href="#path-progress" class="nav-tab nav-tab-active">مسیر رشد</a>
                    <a href="#gamification" class="nav-tab">گیمیفیکیشن</a>
                    <a href="#details" class="nav-tab">جزئیات و یادداشت</a>
                </div>

                <div class="psych-tab-content active" id="tab-path-progress">
                    <!-- Path Progress content will be loaded here -->
                </div>
                <div class="psych-tab-content" id="tab-gamification">
                    <!-- Gamification content will be loaded here -->
                </div>
                <div class="psych-tab-content" id="tab-details">
                    <!-- Details content will be loaded here -->
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue scripts and styles for the admin dashboard.
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our specific admin page.
        if ('روان-گستر_page_psych-user-dashboard' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'psych-admin-dashboard-js',
            plugin_dir_url(__FILE__) . 'assets/admin-dashboard.js',
            ['jquery'],
            PSYCH_SYSTEM_VERSION,
            true
        );

        wp_localize_script('psych-admin-dashboard-js', 'psychAdminDashboard', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('psych_user_dashboard_nonce')
        ]);
    }

    /**
     * Handle the AJAX request for searching users.
     */
    public function ajax_search_users() {
        check_ajax_referer('psych_user_dashboard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';

        if (empty($search_term)) {
            wp_send_json_success([]);
            return;
        }

        $user_query = new WP_User_Query([
            'search'         => '*' . esc_attr($search_term) . '*',
            'search_columns' => ['ID', 'user_login', 'user_email', 'user_nicename', 'display_name'],
            'number'         => 10, // Limit results for performance
        ]);

        $users_found = [];
        foreach ($user_query->get_results() as $user) {
            $users_found[] = [
                'id'           => $user->ID,
                'display_name' => $user->display_name,
                'email'        => $user->user_email,
            ];
        }

        wp_send_json_success($users_found);
    }
}
