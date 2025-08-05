<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Psych_Assessment_Product {

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
        add_shortcode('psych_assessment_product', [$this, 'render_assessment_product_shortcode']);
        add_action('woocommerce_before_calculate_totals', [$this, 'apply_smart_pricing'], 10, 1);
        add_action('woocommerce_order_status_completed', [$this, 'grant_bundled_product_access'], 10, 1);
    }

    public function render_assessment_product_shortcode($atts) {
        $atts = shortcode_atts([
            'product_id' => get_the_ID(), // Automatically gets the product ID if on a product page
            'assessment_id' => '', // A unique ID for the assessment itself
        ], $atts, 'psych_assessment_product');

        if (empty($atts['product_id']) || empty($atts['assessment_id'])) {
            return '<div class="psych-alert error">خطا: شناسه محصول یا آزمون مشخص نشده است.</div>';
        }

        $user_id = get_current_user_id();
        $has_taken_assessment = get_user_meta($user_id, '_psych_assessment_taken_' . $atts['assessment_id'], true);
        $has_purchased = false; // This will be replaced with a real WooCommerce check

        if (function_exists('wc_customer_bought_product') && $user_id) {
            $has_purchased = wc_customer_bought_product(get_userdata($user_id)->user_email, $user_id, $atts['product_id']);
        }

        if ($has_purchased) {
            // User has paid, show the premium report
            return $this->display_premium_report($user_id, $atts);
        } elseif ($has_taken_assessment) {
            // User has taken the free assessment, show the free report with nudges
            return $this->display_free_report($user_id, $atts);
        } else {
            // User has not taken the assessment yet, show the pre-assessment info
            return $this->display_pre_assessment_page($atts);
        }
    }

    private function display_pre_assessment_page($atts) {
        $product = wc_get_product($atts['product_id']);
        $respondents_count = get_post_meta($atts['assessment_id'], '_psych_respondents_count', true) ?: 0;

        ob_start();
        ?>
        <div class="psych-pre-assessment-page">
            <h2><?php echo esc_html($product->get_name()); ?></h2>
            <div class="assessment-description">
                <?php echo wp_kses_post($product->get_description()); ?>
            </div>
            <div class="assessment-meta">
                <p><strong>اعتبار آزمون:</strong> <?php echo esc_html(get_post_meta($atts['assessment_id'], '_psych_assessment_validity', true) ?: 'معتبر'); ?></p>
                <p><strong>تعداد پاسخ‌دهندگان:</strong> <?php echo number_format($respondents_count); ?> نفر</p>
            </div>
            <button class="psych-button psych-btn-success start-assessment-btn" data-assessment-id="<?php echo esc_attr($atts['assessment_id']); ?>">
                شروع آزمون رایگان
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    private function display_free_report($user_id, $atts) {
        // This will call the function from the report card module
        if (function_exists('render_free_report')) {
            return render_free_report($user_id, $atts['assessment_id'], $atts['product_id']);
        }
        return '<div class="psych-alert error">تابع گزارش رایگان یافت نشد.</div>';
    }

    private function display_premium_report($user_id, $atts) {
        // This will call the function from the report card module
        if (function_exists('render_premium_report')) {
            return render_premium_report($user_id, $atts['assessment_id']);
        }
        return '<div class="psych-alert error">تابع گزارش پیشرفته یافت نشد.</div>';
    }

    public function apply_smart_pricing($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product_id = $cart_item['product_id'];

            // Check if this product is a "Path Product"
            // This is a simplified check. A real implementation might use a product meta field.
            if ($this->is_path_product($product_id)) {
                $path_id = $this->get_path_id_for_product($product_id);
                $stations = $this->get_path_stations($path_id);

                $discount = 0;
                foreach ($stations as $station) {
                    $station_product_id = $station['product_id'];
                    if (wc_customer_bought_product(get_userdata($user_id)->user_email, $user_id, $station_product_id)) {
                        $station_product = wc_get_product($station_product_id);
                        $discount += $station_product->get_price();
                    }
                }

                if ($discount > 0) {
                    $original_price = $cart_item['data']->get_price();
                    $new_price = $original_price - $discount;
                    $cart_item['data']->set_price($new_price);
                }
            }
        }
    }

    // Helper functions (placeholders)
    private function is_path_product($product_id) {
        // In a real implementation, you'd check a meta field like `_is_psych_path_product`
        return true;
    }

    private function get_path_id_for_product($product_id) {
        // This would retrieve the path ID associated with the product
        return 'default_path';
    }

    private function get_path_stations($path_id) {
        // This would retrieve the stations for a given path from the Path Engine
        if (class_exists('PsychoCourse_Path_Engine_Ultimate')) {
            $path_engine = PsychoCourse_Path_Engine_Ultimate::get_instance();
            if (isset($path_engine->paths[$path_id])) {
                return $path_engine->paths[$path_id]['stations'];
            }
        }
        return [];
    }

    public function grant_bundled_product_access($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();

        if (!$user_id) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if ($this->is_path_product($product_id)) {
                $path_id = $this->get_path_id_for_product($product_id);
                $stations = $this->get_path_stations($path_id);

                foreach ($stations as $station) {
                    $station_product_id = $station['product_id'];
                    // This is a simplified way of granting access.
                    // A real implementation might use a more robust system like WooCommerce Memberships or Groups.
                    update_user_meta($user_id, '_psych_has_access_to_' . $station_product_id, true);
                }
            }
        }
    }
}

// Initialize the module
Psych_Assessment_Product::get_instance();
