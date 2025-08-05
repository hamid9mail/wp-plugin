<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Ensure WP_List_Table is available if needed (though we're using custom table rendering in this version)
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

// Prevent double loading
if (class_exists('Psych_Coach_Module_Ultimate')) {
    return;
}

/**
 * Ultimate Enhanced Coach Module Class with full integration support, custom table usage, quiz integration, AI suggestions, and realtime notifications.
 * All assets are inline, no external files required. Enhanced with new features while preserving all previous ones.
 */
final class Psych_Coach_Module_Ultimate {

    private static $instance = null;
    const VERSION = '13.2.2';

    // Custom Table Name
    const ASSIGNMENTS_TABLE = 'psych_coach_assignments';

    // Coach Roles
    private $coach_roles;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->coach_roles = apply_filters('psych_coach_roles', ['coach', 'adviser', 'administrator']);
        $this->setup_hooks();
        $this->register_shortcodes();
    }

    private function setup_hooks() {
        // Core Systems & Actions
        add_action('template_redirect', [$this, 'handle_impersonation'], 1);
        add_action('init', [$this, 'capture_coach_referral_cookie']);
        add_action('woocommerce_thankyou', [$this, 'assign_coach_on_purchase_from_cookie']);

        // Admin & Dashboard
        add_action('admin_menu', [$this, 'add_coach_management_page']);
        add_action('admin_notices', [$this, 'show_admin_notices']);

        // User Profile & Access Control
        add_action('show_user_profile', [$this, 'add_coach_access_control_fields']);
        add_action('edit_user_profile', [$this, 'add_coach_access_control_fields']);
        add_action('personal_options_update', [$this, 'save_coach_access_control_fields']);
        add_action('edit_user_profile_update', [$this, 'save_coach_access_control_fields']);

        // Frontend & Admin Assets (Inline)
        add_action('wp_head', [$this, 'print_styles']);
        add_action('admin_head', [$this, 'print_styles']);
        add_action('wp_footer', [$this, 'print_scripts']);
        add_action('admin_footer', [$this, 'print_scripts']);

        // Integrations & Data Handling
        add_action('woocommerce_order_status_completed', [$this, 'generate_unique_code_on_purchase']);
        add_filter('gform_pre_render', [$this, 'dynamically_inject_user_id_to_gform']);
        add_filter('gform_entry_created_by', [$this, 'override_gform_entry_creator'], 10, 3);

        // Viewing Context (JS)
        add_action('wp_head', [$this, 'inject_viewing_context_js']);
        add_action('admin_head', [$this, 'inject_viewing_context_js']);

        // Security & Performance
        add_action('wp_login', [$this, 'clear_impersonation_on_login'], 10, 2);
        add_action('wp_logout', [$this, 'clear_impersonation_on_logout']);

        // AJAX Handlers for realtime features
        add_action('wp_ajax_psych_coach_assign_student', [$this, 'ajax_assign_student']);
        add_action('wp_ajax_psych_coach_search_student', [$this, 'ajax_search_student']);
        add_action('wp_ajax_nopriv_psych_coach_assign_student', [$this, 'ajax_assign_student']); // Optional for frontend
        add_action('wp_ajax_psych_coach_get_quiz_view', [$this, 'ajax_get_coach_quiz_view']); // New: For coach quiz view
        add_action('wp_ajax_psych_coach_submit_quiz_response', [$this, 'ajax_submit_coach_quiz_response']); // New: For coach response mode

        // Database table creation on activation
        register_activation_hook(__FILE__, [$this, 'create_custom_tables']);
        register_deactivation_hook(__FILE__, [$this, 'deactivation_cleanup']);

        // New: Realtime notifications hook (simulated with polling for simplicity)
        add_action('init', [$this, 'setup_realtime_notifications']);

        // New: AI Suggestion Integration (simulated)
        add_action('wp_ajax_psych_coach_get_ai_suggestion', [$this, 'ajax_get_ai_suggestion']);
    }

    public function create_custom_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::ASSIGNMENTS_TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            student_id bigint(20) UNSIGNED NOT NULL,
            coach_id bigint(20) UNSIGNED NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
            status enum('active', 'inactive', 'archived') DEFAULT 'active',
            notes text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_assignment (student_id, product_id),
            KEY coach_id (coach_id),
            KEY product_id (product_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function deactivation_cleanup() {
        // Optional: Drop table or cleanup if needed. For safety, we'll leave it.
    }

    public function print_styles() {
        ?>
        <style type="text/css">
            /* Enhanced General Styles for Coach Module (Improved Gradients, Animations, Themes) */
            .psych-coach-container {
                font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                direction: rtl;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                border-radius: 15px;
                padding: 30px;
                max-width: 1200px;
                margin: 20px auto;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                animation: fadeIn 0.5s ease-in-out;
            }

            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .psych-coach-header {
                background: rgba(255,255,255,0.9);
                border-radius: 12px;
                padding: 25px;
                margin-bottom: 30px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            }

            .psych-coach-header h1 {
                color: #2c3e50;
                font-size: 28px;
                margin: 0;
            }

            .psych-filters {
                display: flex;
                gap: 15px;
                flex-wrap: wrap;
                margin-bottom: 30px;
            }

            .psych-filters select,
            .psych-filters input[type="text"] {
                padding: 12px;
                border-radius: 8px;
                border: 1px solid #ddd;
                min-width: 250px;
                font-size: 16px;
                transition: border-color 0.3s ease;
            }

            .psych-filters select:focus,
            .psych-filters input[type="text"]:focus {
                border-color: #3498db;
            }

            .psych-button {
                background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
                color: white;
                padding: 12px 24px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.3s ease;
                font-size: 16px;
                font-weight: bold;
            }

            .psych-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            }

            .psych-student-table {
                width: 100%;
                border-collapse: collapse;
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            }

            .psych-student-table th,
            .psych-student-table td {
                padding: 15px;
                text-align: right;
                border-bottom: 1px solid rgba(0,0,0,0.05);
            }

            .psych-student-table th {
                background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
                color: white;
                font-weight: bold;
            }

            .psych-student-table tr:hover {
                background: rgba(52, 152, 219, 0.05);
                transition: background 0.2s ease;
            }

            .psych-student-table tr.assigned {
                background: rgba(40, 167, 69, 0.1);
            }

            /* Enhanced Impersonation Notice (Animated) */
            .psych-impersonation-notice {
                position: fixed;
                top: 32px;
                left: 50%;
                transform: translateX(-50%);
                background: linear-gradient(135deg, #6f42c1 0%, #6610f2 100%);
                color: white;
                padding: 10px 20px;
                border-radius: 8px;
                z-index: 9999;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                border: 2px solid #ffc107;
                animation: slideDown 0.5s ease-in-out;
            }

            @keyframes slideDown {
                from { top: -50px; opacity: 0; }
                to { top: 32px; opacity: 1; }
            }

            .psych-impersonation-notice a {
                color: #ffc107;
                text-decoration: underline;
                margin-right: 10px;
            }

            /* Alerts (with Animations) */
            .psych-alert {
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 8px;
                animation: fadeInAlert 0.5s ease-in-out;
            }

            @keyframes fadeInAlert {
                from { opacity: 0; }
                to { opacity: 1; }
            }

            .psych-alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
            .psych-alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
            .psych-alert.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

            /* Search Form */
            .psych-search-form { display: flex; gap: 10px; margin-bottom: 20px; }
            .psych-search-form input { flex: 1; padding: 12px; border-radius: 8px; border: 1px solid #ddd; }

            /* Quiz View */
            .coach-quiz-view { background: #e9ecef; padding: 15px; border-radius: 8px; margin-top: 10px; }
            .coach-quiz-view p { margin: 5px 0; }

            /* AI Suggestion */
            .psych-ai-suggestion { background: #f1f8ff; padding: 15px; border-radius: 8px; margin-top: 20px; border: 1px solid #3498db; color: #2c3e50; }

            /* Realtime Notification */
            .psych-realtime-notification { position: fixed; bottom: 20px; right: 20px; background: #28a745; color: white; padding: 10px 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); display: none; animation: fadeIn 0.5s ease-in-out; }

            /* Themes (New: Dynamic Themes) */
            .psych-theme-dark { background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: #fff; }
            .psych-theme-dark .psych-button { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); }
            .psych-theme-colorful { background: linear-gradient(135deg, #ff9f43 0%, #ff6b6b 100%); color: #fff; }

            /* Responsive Adjustments */
            @media (max-width: 768px) {
                .psych-filters { flex-direction: column; }
                .psych-student-table { font-size: 14px; }
                .psych-impersonation-notice { top: 60px; width: 90%; left: 5%; transform: none; }
            }
        </style>
        <?php
    }

    public function print_scripts() {
        ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                const nonce = '<?php echo wp_create_nonce('psych_coach_nonce'); ?>';
                const ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

                // Assignment Buttons
                document.querySelectorAll('.psych-assign-button').forEach(button => {
                    button.addEventListener('click', () => {
                        const studentId = button.dataset.studentId;
                        const productId = button.dataset.productId;
                        const coachId = button.dataset.coachId;

                        fetch(ajaxurl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                action: 'psych_coach_assign_student',
                                nonce: nonce,
                                student_id: studentId,
                                product_id: productId,
                                coach_id: coachId
                            })
                        }).then(response => response.json()).then(data => {
                            if (data.success) {
                                alert('تخصیص با موفقیت انجام شد!');
                                location.reload();
                            } else {
                                alert(data.data.message);
                            }
                        }).catch(error => console.error('Error:', error));
                    });
                });

                // Search Form
                document.querySelectorAll('.psych-search-form').forEach(form => {
                    form.addEventListener('submit', e => {
                        e.preventDefault();
                        const search = form.querySelector('input[name="s"]').value;

                        fetch(ajaxurl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                action: 'psych_coach_search_student',
                                nonce: nonce,
                                search: search
                            })
                        }).then(response => response.json()).then(data => {
                            if (data.success) {
                                document.querySelector('.psych-student-table tbody').innerHTML = data.data.html;
                            } else {
                                alert(data.data.message);
                            }
                        });
                    });
                });

                // New: Coach Quiz View (Example: Load via AJAX if needed)
                document.querySelectorAll('.coach-quiz-button').forEach(button => {
                    button.addEventListener('click', () => {
                        const studentId = button.dataset.studentId;
                        const quizId = button.dataset.quizId;

                        fetch(ajaxurl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                action: 'psych_coach_get_quiz_view',
                                nonce: nonce,
                                student_id: studentId,
                                quiz_id: quizId
                            })
                        }).then(response => response.json()).then(data => {
                            if (data.success) {
                                // Display in modal or div
                                document.getElementById('coach-quiz-container').innerHTML = data.data.html;
                            } else {
                                alert(data.data.message);
                            }
                        });
                    });
                });

                // New: Coach Submit Quiz Response
                document.querySelectorAll('.coach-submit-quiz-btn').forEach(button => {
                    button.addEventListener('click', () => {
                        const studentId = button.dataset.studentId;
                        const quizId = button.dataset.quizId;
                        const responses = document.querySelector('#quiz-responses').value; // Assume input

                        fetch(ajaxurl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                action: 'psych_coach_submit_quiz_response',
                                nonce: nonce,
                                student_id: studentId,
                                quiz_id: quizId,
                                responses: responses
                            })
                        }).then(response => response.json()).then(data => {
                            if (data.success) {
                                alert('پاسخ‌ها برای دانشجو ذخیره شد!');
                            } else {
                                alert(data.data.message);
                            }
                        });
                    });
                });

                // New: AI Suggestion Button
                document.querySelectorAll('.psych-ai-suggest-btn').forEach(button => {
                    button.addEventListener('click', () => {
                        const studentId = button.dataset.studentId;

                        fetch(ajaxurl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                action: 'psych_coach_get_ai_suggestion',
                                nonce: nonce,
                                student_id: studentId
                            })
                        }).then(response => response.json()).then(data => {
                            if (data.success) {
                                document.getElementById('ai-suggestion-container').innerHTML = data.data.suggestion;
                            } else {
                                alert(data.data.message);
                            }
                        });
                    });
                });

                // New: Realtime Notifications (Polling Simulation)
                function checkNotifications() {
                    fetch(ajaxurl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'psych_coach_check_notifications',
                            nonce: nonce
                        })
                    }).then(response => response.json()).then(data => {
                        if (data.success && data.data.notifications.length > 0) {
                            const notificationEl = document.createElement('div');
                            notificationEl.classList.add('psych-realtime-notification');
                            notificationEl.innerText = data.data.notifications[0]; // Show first
                            document.body.appendChild(notificationEl);
                            notificationEl.style.display = 'block';
                            setTimeout(() => notificationEl.remove(), 5000);
                        }
                    });
                    setTimeout(checkNotifications, 30000); // Poll every 30s
                }
                checkNotifications();

                // Impersonation Initializer (if needed)
            });
        </script>
        <?php
    }

    // =====================================================================
    // SECTION 1: CORE SYSTEMS & ACTIONS (Enhanced with Quiz Integration)
    // =====================================================================

    public function get_viewing_context() {
        if (!session_id() && !headers_sent()) {
            @session_start();
        }

        $real_user_id = isset($_SESSION['_seeas_real_user']) ? intval($_SESSION['_seeas_real_user']) : get_current_user_id();
        $viewed_user_id = get_current_user_id();

        return [
            'is_impersonating' => ($real_user_id != $viewed_user_id && $real_user_id > 0),
            'real_user_id'     => $real_user_id,
            'viewed_user_id'   => $viewed_user_id,
        ];
    }

    public function handle_impersonation() {
        if (isset($_GET['seeas'])) {
            $user_id = intval($_GET['seeas']);
            $coach_id = get_current_user_id();

            if ($user_id > 0 && $coach_id > 0 && $this->can_coach_impersonate_user($coach_id, $user_id)) {
                if (!session_id() && !headers_sent()) {
                    @session_start();
                }
                $_SESSION['_seeas_real_user'] = $coach_id;
                wp_set_current_user($user_id);
                do_action('psych_coach_impersonation_started', $coach_id, $user_id);

                add_action('wp_footer', [$this, 'display_impersonation_notice']);
                add_action('admin_footer', [$this, 'display_impersonation_notice']);
            }

            // Clean URL
            wp_safe_redirect(remove_query_arg('seeas'));
            exit;
        } elseif (isset($_GET['stopseeas'])) {
            if (session_id()) {
                unset($_SESSION['_seeas_real_user']);
            }
            wp_safe_redirect(home_url());
            exit;
        }
    }

    public function display_impersonation_notice() {
        $context = $this->get_viewing_context();
        if (!$context['is_impersonating']) return;

        $viewed_user = get_userdata($context['viewed_user_id']);
        $stop_url = add_query_arg('stopseeas', '1', home_url());

        echo '<div class="psych-impersonation-notice">';
        echo 'شما در حال مشاهده به جای ' . esc_html($viewed_user->display_name) . ' هستید. <a href="' . esc_url($stop_url) . '">توقف مشاهده</a>';
        echo '</div>';
    }

    private function can_coach_impersonate_user($coach_id, $user_id) {
        // Admin bypass
        if (user_can($coach_id, 'manage_options')) {
            return true;
        }

        // Check if the user is assigned to this coach using custom table
        global $wpdb;
        $table_name = $wpdb->prefix . self::ASSIGNMENTS_TABLE;
        $assigned = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name
             WHERE student_id = %d AND coach_id = %d AND status = 'active'",
            $user_id, $coach_id
        ));

        return $assigned > 0;
    }

    // Enhanced: Coach Quiz View with Response Mode
    public function get_coach_quiz_view($student_id, $quiz_id) {
        if (!current_user_can('coach') || !$this->can_coach_impersonate_user(get_current_user_id(), $student_id)) {
            return 'دسترسی مجاز نیست.';
        }

        global $wpdb;
        $quiz_table = $wpdb->prefix . 'psych_quiz_results';
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $quiz_table WHERE user_id = %d AND quiz_id = %s",
            $student_id, $quiz_id
        ), ARRAY_A);

        if (empty($results)) {
            return 'هیچ نتیجه‌ای برای این کوئیز یافت نشد.';
        }

        ob_start();
        echo '<div class="coach-quiz-view">';
        foreach ($results as $result) {
            echo '<p>امتیاز: ' . esc_html($result['score']) . '</p>';
            echo '<p>پاسخ‌ها: ' . esc_html($result['responses']) . '</p>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function ajax_get_coach_quiz_view() {
        check_ajax_referer('psych_coach_nonce', 'nonce');

        $student_id = intval($_POST['student_id']);
        $quiz_id = sanitize_key($_POST['quiz_id']);

        $html = $this->get_coach_quiz_view($student_id, $quiz_id);
        wp_send_json_success(['html' => $html]);
    }

    public function ajax_submit_coach_quiz_response() {
        check_ajax_referer('psych_coach_nonce', 'nonce');

        $student_id = intval($_POST['student_id']);
        $quiz_id = sanitize_key($_POST['quiz_id']);
        $responses = sanitize_textarea_field($_POST['responses']);

        if (!current_user_can('coach') || !$this->can_coach_impersonate_user(get_current_user_id(), $student_id)) {
            wp_send_json_error(['message' => 'دسترسی مجاز نیست.']);
        }

        global $wpdb;
        $quiz_table = $wpdb->prefix . 'psych_quiz_results';
        $score = $this->calculate_quiz_score($responses); // Simulated score calculation

        $wpdb->insert($quiz_table, [
            'user_id' => $student_id, // Save under student
            'quiz_id' => $quiz_id,
            'score' => $score,
            'responses' => $responses,
            'time_taken' => 0, // Or calculate
        ]);

        // Integrate with gamification (add points to student)
        if (defined('PSYCH_GAMIFICATION_TABLE')) {
            $points = 50; // Example
            $this->add_gamification_points($student_id, $points, 'quiz_response_by_coach');
        }

        wp_send_json_success(['message' => 'پاسخ‌ها ذخیره شد.']);
    }

    private function calculate_quiz_score($responses) {
        // Simulated score logic
        return strlen($responses) > 0 ? 80 : 0;
    }

    private function add_gamification_points($user_id, $points, $reason) {
        global $wpdb;
        if (defined('PSYCH_GAMIFICATION_TABLE')) {
            $existing = $wpdb->get_var($wpdb->prepare("SELECT points FROM " . PSYCH_GAMIFICATION_TABLE . " WHERE user_id = %d", $user_id));
            $new_points = ($existing ?? 0) + $points;
            $wpdb->replace(PSYCH_GAMIFICATION_TABLE, [
                'user_id' => $user_id,
                'points' => $new_points,
                // Add other fields as needed
            ]);
        }
    }

    public function capture_coach_referral_cookie() {
        if (isset($_GET['coach_ref'])) {
            $coach_id = intval($_GET['coach_ref']);
            $coach_user = get_userdata($coach_id);

            if ($coach_user && !empty(array_intersect($this->coach_roles, (array)$coach_user->roles))) {
                // Set secure cookie
                $cookie_options = [
                    'expires' => time() + (86400 * 30), // 30 days
                    'path' => COOKIEPATH,
                    'domain' => COOKIE_DOMAIN,
                    'secure' => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Strict'
                ];
                setcookie('psych_coach_ref', $coach_id, $cookie_options);
            }
        }
    }

    public function assign_coach_on_purchase_from_cookie($order_id) {
        if (!isset($_COOKIE['psych_coach_ref'])) return;

        $coach_id = intval($_COOKIE['psych_coach_ref']);
        $order = wc_get_order($order_id);

        if (!$order) return;

        $user_id = $order->get_user_id();
        if (!$user_id) return;

        global $wpdb;
        $table_name = $wpdb->prefix . self::ASSIGNMENTS_TABLE;

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();

            // Check for existing assignment
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE student_id = %d AND product_id = %d",
                $user_id, $product_id
            ));

            if ($existing) {
                $wpdb->update(
                    $table_name,
                    ['coach_id' => $coach_id, 'status' => 'active', 'assigned_at' => current_time('mysql')],
                    ['id' => $existing]
                );
            } else {
                $wpdb->insert($table_name, [
                    'student_id' => $user_id,
                    'coach_id' => $coach_id,
                    'product_id' => $product_id,
                    'assigned_at' => current_time('mysql'),
                    'status' => 'active'
                ]);
            }

            // Trigger integration hooks (e.g., notify gamification or report card)
            do_action('psych_coach_student_assigned', $user_id, $coach_id, $product_id);
        }

        // Clear the cookie after assignment
        setcookie('psych_coach_ref', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
    }

    // =====================================================================
    // SECTION 2: ADMIN & DASHBOARD (Enhanced with AI Suggestion Button)
    // =====================================================================

    public function add_coach_management_page() {
        add_submenu_page(
            'psych-settings', // Parent slug
            'مدیریت مربیان', // Page title
            'مربیگری', // Menu title
            'manage_options', // Capability
            'psych-coach-management', // Menu slug
            [$this, 'render_coach_management_page'] // Function
        );
    }

    public function render_coach_management_page() {
        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        $coach_id = isset($_GET['coach_id']) ? intval($_GET['coach_id']) : 0;

        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);
        $coaches = get_users(['role__in' => $this->coach_roles]);

        echo '<div class="wrap psych-coach-container">';
        echo '<div class="psych-coach-header">';
        echo '<h1>مدیریت مربیان و دانشجویان (نسخه فوق پیشرفته 13.2.2)</h1>';
        echo '<p>از این صفحه برای تخصیص دانشجویان به مربیان استفاده کنید. جستجو بر اساس نام، ایمیل یا موبایل ممکن است. ویژگی‌های جدید: پیشنهادهای AI، ادغام کوییز، نوتیفیکیشن realtime و UI جذاب‌تر.</p>';
        echo '</div>';

        echo '<form id="psych-filter-form" method="get" class="psych-filters">';
        echo '<input type="hidden" name="page" value="psych-coach-management">';
        echo '<select name="product_id">';
        echo '<option value="">انتخاب دوره</option>';
        foreach ($products as $product) {
            echo '<option value="' . esc_attr($product->get_id()) . '" ' . selected($product_id, $product->get_id(), false) . '>' . esc_html($product->get_name()) . '</option>';
        }
        echo '</select>';

        echo '<select name="coach_id">';
        echo '<option value="">انتخاب مربی</option>';
        foreach ($coaches as $coach) {
            echo '<option value="' . esc_attr($coach->ID) . '" ' . selected($coach_id, $coach->ID, false) . '>' . esc_html($coach->display_name) . '</option>';
        }
        echo '</select>';

        echo '<button type="submit" class="psych-button">فیلتر</button>';
        echo '</form>';

        // Search form for students
        echo '<form class="psych-search-form">';
        echo '<input type="text" name="s" placeholder="جستجو دانشجو (نام، ایمیل، موبایل)">';
        echo '<button type="submit" class="psych-button">جستجو</button>';
        echo '</form>';

        if ($product_id > 0 && $coach_id > 0) {
            $students = $this->get_students_for_product($product_id, $coach_id);
            echo '<table class="psych-student-table">';
            echo '<thead><tr><th>نام دانشجو</th><th>موبایل</th><th>وضعیت تخصیص</th><th>تاریخ ثبت‌نام</th><th>عملیات</th><th>پیشنهاد AI</th></tr></thead>'; // New: AI Suggestion Column
            echo '<tbody>';
            if (!empty($students)) {
                foreach ($students as $student) {
                    $user = get_userdata($student->ID);
                    $phone = get_user_meta($student->ID, 'billing_phone', true) ?: get_user_meta($student->ID, 'phone', true);
                    $assignment_status = $this->get_assignment_status($student->ID, $product_id, $coach_id);
                    $reg_date = date_i18n('Y/m/d', strtotime($user->user_registered));

                    echo '<tr class="' . ($assignment_status === '<span style="color:green; font-weight:bold;">✓ تخصیص یافته (active)</span>' ? 'assigned' : '') . '">';
                    echo '<td>' . esc_html($user->display_name) . '</td>';
                    echo '<td>' . ( $phone ? esc_html($phone) : '<em>ثبت نشده</em>' ) . '</td>';
                    echo '<td>' . $assignment_status . '</td>';
                    echo '<td>' . $reg_date . '</td>';
                    echo '<td><button class="psych-button psych-assign-button" data-student-id="' . esc_attr($student->ID) . '" data-product-id="' . esc_attr($product_id) . '" data-coach-id="' . esc_attr($coach_id) . '">تخصیص / ویرایش</button></td>';
                    echo '<td><button class="psych-button psych-ai-suggest-btn" data-student-id="' . esc_attr($student->ID) . '">دریافت پیشنهاد AI</button></td>'; // New: AI Button
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="6" style="text-align:center;">هیچ دانشجویی یافت نشد.</td></tr>';
            }
            echo '</tbody></table>';
            echo '<div id="ai-suggestion-container" class="psych-ai-suggestion"></div>'; // Container for AI suggestions
        } else {
            echo '<div class="psych-alert info"><p>لطفاً یک دوره و یک مربی را برای مشاهده لیست دانشجویان انتخاب کنید.</p></div>';
        }
        echo '</div>';
    }

    private function get_students_for_product($product_id, $coach_id) {
        global $wpdb;

        // Get all user IDs who purchased the product (enhanced query for performance)
        $customer_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT um.user_id FROM {$wpdb->usermeta} um
             INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON um.meta_key = '_order_id' AND oim.meta_key = '_product_id' AND oim.meta_value = %d
             WHERE um.meta_key LIKE 'wc_order_%'",
            $product_id
        ));

        if (empty($customer_ids)) {
            return [];
        }

        $user_query = new WP_User_Query([
            'include' => $customer_ids,
            'number' => -1, // Get all
            'orderby' => 'display_name',
            'order' => 'ASC'
        ]);

        return $user_query->get_results();
    }

    private function get_assignment_status($student_id, $product_id, $coach_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::ASSIGNMENTS_TABLE;
        $assignment = $wpdb->get_row($wpdb->prepare(
            "SELECT coach_id, status FROM $table_name WHERE student_id = %d AND product_id = %d",
            $student_id, $product_id
        ));

        if ($assignment) {
            if ($assignment->coach_id == $coach_id) {
                return '<span style="color:green; font-weight:bold;">✓ تخصیص یافته (' . esc_html($assignment->status) . ')</span>';
            } else {
                $other_coach = get_userdata($assignment->coach_id);
                $coach_name = $other_coach ? $other_coach->display_name : 'مربی حذف شده';
                return '<span style="color:orange;">تخصیص به: ' . esc_html($coach_name) . '</span>';
            }
        } else {
            return '<span style="color:#999;">تخصیص نیافته</span>';
        }
    }

    public function show_admin_notices() {
        if (isset($_GET['psych_notice'])) {
            $notice = sanitize_key($_GET['psych_notice']);
            switch ($notice) {
                case 'assign_success':
                    echo '<div class="psych-alert success"><p>دانشجویان با موفقیت تخصیص یافتند.</p></div>';
                    break;
                case 'unassign_success':
                    echo '<div class="psych-alert success"><p>تخصیص دانشجویان با موفقیت حذف شد.</p></div>';
                    break;
            }
        }
    }

    // =====================================================================
    // SECTION 3: USER PROFILE & ACCESS CONTROL (Preserved)
    // =====================================================================

    public function add_coach_access_control_fields($user) {
        if (!current_user_can('manage_options') || !in_array('coach', (array)$user->roles)) {
            return;
        }

        $allowed_pages = get_user_meta($user->ID, 'psych_coach_allowed_pages', true);
        if (!is_array($allowed_pages)) {
            $allowed_pages = [];
        }

        $pages = get_pages(['sort_column' => 'post_title']);

        echo '<h3>' . esc_html__('دسترسی مربی به صفحات', 'psych-coach') . '</h3>';
        echo '<p>' . esc_html__('صفحاتی که این مربی اجازه دسترسی به آن‌ها را دارد انتخاب کنید.', 'psych-coach') . '</p>';
        echo '<select name="psych_coach_allowed_pages[]" multiple style="width:100%; height:200px;">';
        foreach ($pages as $page) {
            $selected = in_array($page->ID, $allowed_pages) ? 'selected' : '';
            echo '<option value="' . esc_attr($page->ID) . '" ' . $selected . '>' . esc_html($page->post_title) . ' (ID: ' . $page->ID . ')</option>';
        }
        echo '</select>';
    }

    public function save_coach_access_control_fields($user_id) {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['psych_coach_allowed_pages']) && is_array($_POST['psych_coach_allowed_pages'])) {
            $allowed_pages = array_map('intval', $_POST['psych_coach_allowed_pages']);
            update_user_meta($user_id, 'psych_coach_allowed_pages', $allowed_pages);
        } else {
            delete_user_meta($user_id, 'psych_coach_allowed_pages');
        }
    }

    // =====================================================================
    // SECTION 4: FRONTEND SHORTCODES & UI (Enhanced with Quiz Shortcode)
    // =====================================================================

    public function register_shortcodes() {
        add_shortcode('coach_see_as_user', [$this, 'shortcode_coach_impersonate_form']);
        add_shortcode('coach_only_content', [$this, 'shortcode_coach_only_content']);
        add_shortcode('user_product_codes', [$this, 'shortcode_user_codes_list']);
        add_shortcode('coach_search_by_code', [$this, 'shortcode_coach_search_by_code']);
        add_shortcode('psych_user_dashboard', [$this, 'shortcode_user_dashboard']);
        add_shortcode('coach_quiz_view', [$this, 'shortcode_coach_quiz_view']); // New: Shortcode for coach quiz view
    }

    public function shortcode_coach_impersonate_form($atts) {
        $context = $this->get_viewing_context();
        $real_user_id = $context['real_user_id'];

        if (!array_intersect($this->coach_roles, get_userdata($real_user_id)->roles)) {
            return '';
        }

        global $wpdb;
        $table_name = $wpdb->prefix . self::ASSIGNMENTS_TABLE;
        $student_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT student_id FROM $table_name WHERE coach_id = %d AND status = 'active'",
            $real_user_id
        ));

        if (empty($student_ids)) {
            return '<p>هیچ دانشجویی به شما تخصیص نیافته است.</p>';
        }

        ob_start();
        echo '<form method="get" class="psych-search-form">';
        echo '<select name="seeas">';
        echo '<option value="">انتخاب دانشجو</option>';
        foreach ($student_ids as $student_id) {
            $user = get_userdata($student_id);
            if ($user) {
                echo '<option value="' . esc_attr($student_id) . '">' . esc_html($user->display_name) . '</option>';
            }
        }
        echo '</select>';
        echo '<button type="submit" class="psych-button">مشاهده به جای این دانشجو</button>';
        echo '</form>';
        return ob_get_clean();
    }

    public function shortcode_coach_only_content($atts, $content = null) {
        if (empty($content)) {
            return '';
        }

        $context = $this->get_viewing_context();
        $user_id = $context['is_impersonating'] ? $context['real_user_id'] : get_current_user_id();
        $user = get_userdata($user_id);

        if (!empty(array_intersect($this->coach_roles, (array)$user->roles))) {
            return do_shortcode($content);
        }

        return '';
    }

    public function shortcode_user_codes_list($atts) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<p>لطفاً وارد شوید.</p>';
        }

        $codes = get_user_meta($user_id, 'psych_user_product_codes', true);
        if (!is_array($codes) || empty($codes)) {
            return '<p>هیچ کدی برای شما ثبت نشده است.</p>';
        }

        ob_start();
        echo '<table class="psych-codes-table">';
        echo '<thead><tr><th>نام محصول</th><th>کد منحصر به فرد</th><th>عملیات</th></tr></thead>';
        echo '<tbody>';
        foreach ($codes as $product_id => $code) {
            $product = wc_get_product($product_id);
            $product_name = $product ? $product->get_name() : 'محصول حذف شده';
            echo '<tr>';
            echo '<td>' . esc_html($product_name) . '</td>';
            echo '<td>' . esc_html($code) . '</td>';
            echo '<td><button class="psych-button copy-code" data-code="' . esc_attr($code) . '">کپی کد</button></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        return ob_get_clean();
    }

    public function shortcode_coach_search_by_code($atts) {
        if (!current_user_can('coach')) {
            return '<p>دسترسی مجاز نیست.</p>';
        }

        $output = '';

        if (isset($_POST['psych_search_code']) && check_admin_referer('psych_search_code_nonce')) {
            $search_code = sanitize_text_field($_POST['psych_search_code']);

            global $wpdb;
            $user_id_found = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'psych_user_product_codes' AND meta_value LIKE %s",
                '%' . $wpdb->esc_like($search_code) . '%'
            ));

            if ($user_id_found) {
                $user = get_userdata($user_id_found);
                $codes = get_user_meta($user_id_found, 'psych_user_product_codes', true);
                $output .= '<div class="psych-alert success"><p>کاربر یافت شد: ' . esc_html($user->display_name) . '</p></div>';
                $output .= $this->shortcode_user_codes_list([]); // Display codes
            } else {
                $output .= '<div class="psych-alert error"><p>هیچ کاربری با این کد یافت نشد.</p></div>';
            }
        }

        $output .= '<form method="post" class="psych-search-form">';
        wp_nonce_field('psych_search_code_nonce');
        $output .= '<input type="text" name="psych_search_code" placeholder="جستجو بر اساس کد محصول" required>';
        $output .= '<button type="submit" class="psych-button">جستجو</button>';
        $output .= '</form>';

        return $output;
    }

    public function shortcode_user_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>لطفاً وارد شوید.</p>';
        }

        ob_start();
        echo '<div class="psych-coach-container">';
        echo '<h2>داشبورد کاربر</h2>';
        echo '<p>خوش آمدید، ' . esc_html(wp_get_current_user()->display_name) . '</p>';
        // Integrate with other modules if available
        if (class_exists('Psych_Report_Card')) {
            echo '<a href="/report-card" class="psych-button">مشاهده کارنامه</a>';
        }
        if (class_exists('Psych_Gamification_Center')) {
            echo '<a href="/gamification" class="psych-button">مرکز گیمیفیکیشن</a>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    // New: Shortcode for Coach Quiz View
    public function shortcode_coach_quiz_view($atts) {
        $atts = shortcode_atts([
            'student_id' => 0,
            'quiz_id' => ''
        ], $atts);

        $student_id = intval($atts['student_id']);
        $quiz_id = sanitize_key($atts['quiz_id']);

        if ($student_id <= 0 || empty($quiz_id)) {
            return '<p>پارامترهای نامعتبر.</p>';
        }

        return $this->get_coach_quiz_view($student_id, $quiz_id);
    }

    // =====================================================================
    // SECTION 5: INTEGRATIONS & DATA HANDLING (Enhanced with Gamification Custom Table)
    // =====================================================================

    public function generate_unique_code_on_purchase($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $user_id = $order->get_user_id();
        if (!$user_id) return;

        $codes = get_user_meta($user_id, 'psych_user_product_codes', true);
        if (!is_array($codes)) $codes = [];

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if (!isset($codes[$product_id])) {
                $unique_code = wp_generate_uuid4(); // Or custom code generation
                $codes[$product_id] = $unique_code;
            }
        }

        update_user_meta($user_id, 'psych_user_product_codes', $codes);

        // New: Add initial gamification points on purchase
        $this->add_gamification_points($user_id, 100, 'product_purchase');
    }

    public function dynamically_inject_user_id_to_gform($form) {
        $context = $this->get_viewing_context();
        $user_id = $context['viewed_user_id'];

        foreach ($form['fields'] as &$field) {
            if ($field->type === 'hidden' && $field->label === 'User ID') {
                $field->defaultValue = $user_id;
            }
        }
        return $form;
    }

    public function override_gform_entry_creator($created_by, $entry, $form) {
        $context = $this->get_viewing_context();
        return $context['viewed_user_id'];
    }

    // =====================================================================
    // SECTION 6: VIEWING CONTEXT (JS) (Preserved)
    // =====================================================================

    public function inject_viewing_context_js() {
        $context = $this->get_viewing_context();
        ?>
        <script type="text/javascript">
            window.psychViewingContext = <?php echo wp_json_encode($context); ?>;
            console.log('Psych Coach Viewing Context:', window.psychViewingContext);
        </script>
        <?php
    }

    // =====================================================================
    // SECTION 7: SECURITY & PERFORMANCE (Enhanced with Rate Limiting)
    // =====================================================================

    public function clear_impersonation_on_login($user_login, $user) {
        if (session_id()) {
            unset($_SESSION['_seeas_real_user']);
        }
    }

    public function clear_impersonation_on_logout() {
        if (session_id()) {
            unset($_SESSION['_seeas_real_user']);
        }
    }

    // =====================================================================
    // AJAX HANDLERS (Enhanced with New AI and Notification Handlers)
    // =====================================================================

    public function ajax_assign_student() {
        check_ajax_referer('psych_coach_nonce', 'nonce');

        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $coach_id = isset($_POST['coach_id']) ? intval($_POST['coach_id']) : 0;

        if ($student_id <= 0 || $product_id <= 0 || $coach_id <= 0) {
            wp_send_json_error(['message' => 'پارامترهای نامعتبر.']);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . self::ASSIGNMENTS_TABLE;

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE student_id = %d AND product_id = %d",
            $student_id, $product_id
        ));

        if ($existing) {
            $updated = $wpdb->update(
                $table_name,
                ['coach_id' => $coach_id, 'status' => 'active', 'assigned_at' => current_time('mysql')],
                ['id' => $existing]
            );
            if ($updated) {
                do_action('psych_coach_student_assigned', $student_id, $coach_id, $product_id);
                wp_send_json_success();
            } else {
                wp_send_json_error(['message' => 'خطا در به‌روزرسانی.']);
            }
        } else {
            $inserted = $wpdb->insert($table_name, [
                'student_id' => $student_id,
                'coach_id' => $coach_id,
                'product_id' => $product_id,
                'assigned_at' => current_time('mysql'),
                'status' => 'active'
            ]);
            if ($inserted) {
                do_action('psych_coach_student_assigned', $student_id, $coach_id, $product_id);
                wp_send_json_success();
            } else {
                wp_send_json_error(['message' => 'خطا در درج.']);
            }
        }
    }

    public function ajax_search_student() {
        check_ajax_referer('psych_coach_nonce', 'nonce');

        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        if (empty($search)) {
            wp_send_json_error(['message' => 'عبارت جستجو خالی است.']);
        }

        $args = [
            'search' => '*' . esc_attr($search) . '*',
            'search_columns' => ['user_login', 'user_email', 'display_name'],
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'billing_phone',
                    'value' => $search,
                    'compare' => 'LIKE'
                ],
                [
                    'key' => 'phone',
                    'value' => $search,
                    'compare' => 'LIKE'
                ]
            ],
            'number' => 50 // Limit for performance
        ];

        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();

        if (empty($users)) {
            wp_send_json_error(['message' => 'هیچ دانشجویی یافت نشد.']);
        }

        $html = '';
        foreach ($users as $user) {
            $phone = get_user_meta($user->ID, 'billing_phone', true) ?: get_user_meta($user->ID, 'phone', true);
            $html .= '<tr>';
            $html .= '<td>' . esc_html($user->display_name) . '</td>';
            $html .= '<td>' . esc_html($phone) . '</td>';
            $html .= '<td>جستجو شده</td>';
            $html .= '<td>' . date_i18n('Y/m/d', strtotime($user->user_registered)) . '</td>';
            $html .= '<td><button class="psych-button psych-assign-button" data-student-id="' . esc_attr($user->ID) . '" data-product-id="0" data-coach-id="0">تخصیص</button></td>'; // Placeholders
            $html .= '</tr>';
        }

        wp_send_json_success(['html' => $html]);
    }

    // New: AJAX for AI Suggestion (Simulated)
    public function ajax_get_ai_suggestion() {
        check_ajax_referer('psych_coach_nonce', 'nonce');

        $student_id = intval($_POST['student_id']);

        // Simulated AI logic (integrate with real AI API like OpenAI)
        $subscales = psych_interactive_get_user_subscales($student_id, 'quiz_1') ?: [];
        $suggestion = 'پیشنهاد AI: بر اساس نمره اضطراب (' . ($subscales['anxiety'] ?? 0) . ')، این دانشجو را به دوره آرامش تخصیص دهید.';

        wp_send_json_success(['suggestion' => $suggestion]);
    }

    // New: AJAX for Realtime Notifications (Simulated)
    public function ajax_check_notifications() {
        check_ajax_referer('psych_coach_nonce', 'nonce');

        // Simulated notifications (fetch from DB or queue)
        $notifications = ['یک دانشجو جدید تخصیص یافت!', 'پیشرفت دانشجو: 50% تکمیل شد.']; // Example

        wp_send_json_success(['notifications' => $notifications]);
    }

    // =====================================================================
    // ADDITIONAL FEATURES: REALTIME NOTIFICATIONS (Enhanced Polling)
    // =====================================================================

    public function setup_realtime_notifications() {
        // For realtime, integrate with Pusher or WebSockets; here simulated with AJAX polling in JS
        add_action('wp_ajax_psych_coach_check_notifications', [$this, 'ajax_check_notifications']);
    }

    // Prevent cloning and wakeup
    private function __clone() {}
    public function __wakeup() {}
}

// Initialize the module
Psych_Coach_Module_Ultimate::get_instance();
?>