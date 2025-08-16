<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Educational_Consulting_App
 * @subpackage Educational_Consulting_App/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Educational_Consulting_App
 * @subpackage Educational_Consulting_App/public
 * @author     Jules <your-name@example.com>
 */
class Educational_Consulting_App_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name . '-public', plugin_dir_url( __FILE__ ) . '../assets/css/educational-consulting-app-public.css', array(), $this->version, 'all' );

        // Enqueue print styles only on the result page
        if ( is_singular( 'eca_result' ) ) {
            wp_enqueue_style( $this->plugin_name . '-print', plugin_dir_url( __FILE__ ) . '../assets/css/educational-consulting-app-print.css', array(), $this->version, 'print' );
        }
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . '../assets/js/educational-consulting-app-public.js', array( 'jquery' ), $this->version, false );
    }

    /**
     * Checks if an order contains the unlocking product and changes the user's role.
     *
     * @since    1.0.0
     * @param    int    $order_id    The ID of the completed order.
     */
    public function maybe_change_role_on_purchase( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $user_id = $order->get_customer_id();
        if ( ! $user_id ) {
            return; // Only for registered users.
        }

        $unlocking_product_id = get_option( 'eca_unlocking_product_id', 0 );
        if ( ! $unlocking_product_id ) {
            return; // Admin hasn't set the product.
        }

        $has_purchased = false;
        foreach ( $order->get_items() as $item ) {
            if ( $item->get_product_id() == $unlocking_product_id || $item->get_variation_id() == $unlocking_product_id ) {
                $has_purchased = true;
                break;
            }
        }

        if ( $has_purchased ) {
            // Change role to student
            $user = new WP_User( $user_id );
            $user->set_role( 'student' );

            // Check for referral and log commission
            $referred_by_id = get_user_meta( $user_id, '_eca_referred_by', true );
            if ( ! empty( $referred_by_id ) ) {
                $commission_log = get_option( 'eca_commission_log', array() );

                $commission_log[] = array(
                    'consultant_id' => $referred_by_id,
                    'student_id'    => $user_id,
                    'order_id'      => $order_id,
                    'order_total'   => $order->get_total(),
                    'timestamp'     => time(),
                    'status'        => 'unpaid',
                );

                update_option( 'eca_commission_log', $commission_log );
            }
        }
    }

    /**
     * Registers the shortcodes for the plugin.
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode( 'eca_test', array( $this, 'render_test_shortcode' ) );
        add_shortcode( 'eca_consultant_dashboard', array( $this, 'render_consultant_dashboard_shortcode' ) );
    }

    /**
     * Renders the test shortcode.
     *
     * @since    1.0.0
     */
    public function render_test_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'id' => 0,
        ), $atts, 'eca_test' );

        $assessment_id = intval( $atts['id'] );

        if ( ! $assessment_id || get_post_type( $assessment_id ) !== 'eca_assessment' ) {
            return '<p>' . esc_html__( 'Invalid assessment ID.', 'educational-consulting-app' ) . '</p>';
        }

        // Basic access control
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'You must be logged in to take this assessment.', 'educational-consulting-app' ) . '</p>';
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'student', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) {
             return '<p>' . esc_html__( 'You do not have permission to take this assessment. Please purchase the required product.', 'educational-consulting-app' ) . '</p>';
        }


        $questions = get_post_meta( $assessment_id, '_eca_questions', true );

        if ( empty( $questions ) ) {
            return '<p>' . esc_html__( 'This assessment has no questions yet.', 'educational-consulting-app' ) . '</p>';
        }

        ob_start();
        ?>
        <div id="eca-test-container">
            <form id="eca-test-form" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                <input type="hidden" name="action" value="eca_submit_test">
                <input type="hidden" name="assessment_id" value="<?php echo esc_attr( $assessment_id ); ?>">
                <?php wp_nonce_field( 'eca_submit_test_' . $assessment_id, 'eca_test_nonce' ); ?>

                <?php foreach ( $questions as $index => $question ) : ?>
                    <div class="eca-question-slide" style="<?php echo $index > 0 ? 'display: none;' : ''; ?>">
                        <h3><?php echo esc_html( $question['text'] ); ?></h3>
                        <p><?php _e('Do you agree with this statement?', 'educational-consulting-app'); ?></p>
                        <input type="radio" name="answers[<?php echo $index; ?>]" value="1" required> <?php _e('Yes', 'educational-consulting-app'); ?>
                        <input type="radio" name="answers[<?php echo $index; ?>]" value="0"> <?php _e('No', 'educational-consulting-app'); ?>
                        <input type="hidden" name="question_codes[<?php echo $index; ?>]" value="<?php echo esc_attr($question['code']); ?>">
                    </div>
                <?php endforeach; ?>

                <div class="eca-navigation">
                    <button type="button" id="eca-prev-btn" style="display: none;"><?php _e('Previous', 'educational-consulting-app'); ?></button>
                    <button type="button" id="eca-next-btn"><?php _e('Next', 'educational-consulting-app'); ?></button>
                    <button type="submit" id="eca-submit-btn" style="display: none;"><?php _e('Finish Test', 'educational-consulting-app'); ?></button>
                </div>
            </form>
        </div>
        <style>
            /* Basic styling for the test */
            #eca-test-container { border: 1px solid #eee; padding: 20px; }
            .eca-question-slide { margin-bottom: 20px; }
            .eca-navigation { margin-top: 20px; }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const slides = document.querySelectorAll('.eca-question-slide');
                const prevBtn = document.getElementById('eca-prev-btn');
                const nextBtn = document.getElementById('eca-next-btn');
                const submitBtn = document.getElementById('eca-submit-btn');
                let currentSlide = 0;

                function showSlide(n) {
                    slides.forEach(slide => slide.style.display = 'none');
                    slides[n].style.display = 'block';

                    prevBtn.style.display = n === 0 ? 'none' : 'inline-block';
                    nextBtn.style.display = n === slides.length - 1 ? 'none' : 'inline-block';
                    submitBtn.style.display = n === slides.length - 1 ? 'inline-block' : 'none';
                }

                nextBtn.addEventListener('click', function() {
                    // Check if a radio button is selected before proceeding
                    const currentRadios = slides[currentSlide].querySelectorAll('input[type="radio"]');
                    if (![...currentRadios].some(r => r.checked)) {
                        alert('Please select an answer.');
                        return;
                    }
                    if (currentSlide < slides.length - 1) {
                        currentSlide++;
                        showSlide(currentSlide);
                    }
                });

                prevBtn.addEventListener('click', function() {
                    if (currentSlide > 0) {
                        currentSlide--;
                        showSlide(currentSlide);
                    }
                });

                showSlide(currentSlide);
            });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Handles the submission of the test form.
     *
     * @since    1.0.0
     */
    public function handle_test_submission() {
        $assessment_id = isset( $_POST['assessment_id'] ) ? intval( $_POST['assessment_id'] ) : 0;

        // 1. Security Checks
        if ( ! isset( $_POST['eca_test_nonce'] ) || ! wp_verify_nonce( $_POST['eca_test_nonce'], 'eca_submit_test_' . $assessment_id ) ) {
            wp_die( 'Security check failed.' );
        }

        if ( ! is_user_logged_in() ) {
            wp_die( 'You must be logged in.' );
        }

        // 2. Get Form Data
        $answers = isset( $_POST['answers'] ) ? $_POST['answers'] : array();
        $codes = isset( $_POST['question_codes'] ) ? $_POST['question_codes'] : array();

        // 3. Scoring Logic
        $scores = array( 'R' => 0, 'I' => 0, 'A' => 0, 'S' => 0, 'E' => 0, 'C' => 0 );
        foreach ( $answers as $index => $answer_value ) {
            if ( $answer_value == '1' ) { // User answered "Yes"
                $code = $codes[$index];
                if ( array_key_exists( $code, $scores ) ) {
                    $scores[$code]++;
                }
            }
        }
        arsort( $scores ); // Sort scores from high to low
        $top_codes = array_slice( array_keys( $scores ), 0, 3 );
        $final_code = implode( '', $top_codes );

        // 4. Create Result Post
        $user = wp_get_current_user();
        $assessment_title = get_the_title( $assessment_id );
        $result_post_data = array(
            'post_title'   => sprintf( '%s Results for %s', $assessment_title, $user->display_name ),
            'post_content' => '',
            'post_status'  => 'publish', // Private CPT, so 'publish' is ok.
            'post_author'  => $user->ID,
            'post_type'    => 'eca_result',
        );
        $result_id = wp_insert_post( $result_post_data );

        if ( is_wp_error( $result_id ) ) {
            wp_die( 'Could not save your results. Please try again.' );
        }

        // 5. Save Meta and Answers
        update_post_meta( $result_id, '_assessment_id', $assessment_id );
        update_post_meta( $result_id, '_holland_code', $final_code );
        update_post_meta( $result_id, '_holland_scores', $scores );

        global $wpdb;
        $table_name = $wpdb->prefix . 'eca_answers';
        foreach ( $answers as $index => $answer_value ) {
            $wpdb->insert(
                $table_name,
                array(
                    'result_id'     => $result_id,
                    'question_key'  => $codes[$index] . '_' . $index, // e.g., R_0
                    'answer_value'  => $answer_value,
                    'answered_at'   => current_time( 'mysql' ),
                )
            );
        }

        // 6. Redirect to Result Page
        wp_redirect( get_permalink( $result_id ) );
        exit;
    }

    /**
     * Loads the custom template for a single result.
     *
     * @since    1.0.0
     * @param    string    $template    The path of the template to include.
     * @return   string    The path of the new template.
     */
    public function load_result_template( $template ) {
        if ( is_singular( 'eca_result' ) ) {
            $post = get_post();
            $current_user = wp_get_current_user();
            $student_id = $post->post_author;

            $is_author = ( $student_id == $current_user->ID );
            $is_admin = current_user_can( 'manage_options' );

            $is_authorized_consultant = false;
            if( in_array( 'consultant', (array) $current_user->roles ) ) {
                $referred_by_id = get_user_meta( $student_id, '_eca_referred_by', true );
                if( $referred_by_id == $current_user->ID ) {
                    $is_authorized_consultant = true;
                }
            }

            if ( $is_author || $is_admin || $is_authorized_consultant ) {
                $plugin_template = ECA_PLUGIN_DIR . 'public/templates/single-eca_result.php';
                if ( file_exists( $plugin_template ) ) {
                    return $plugin_template;
                }
            } else {
                // If not authorized, redirect to home.
                wp_redirect( home_url() );
                exit;
            }
        }
        return $template;
    }

    /**
     * Adds a hidden field to the registration form to capture the referral username.
     *
     * @since    1.0.0
     */
    public function add_referral_field_to_registration() {
        if ( isset( $_GET['ref'] ) ) {
            $ref_username = sanitize_text_field( $_GET['ref'] );
            echo '<input type="hidden" name="eca_ref" id="eca_ref" value="' . esc_attr( $ref_username ) . '" />';
        }
    }

    /**
     * Saves the consultant referral meta when a new user registers.
     *
     * @since    1.0.0
     * @param    int    $user_id    The ID of the newly registered user.
     */
    public function save_referral_on_registration( $user_id ) {
        if ( isset( $_POST['eca_ref'] ) ) {
            $ref_username = sanitize_text_field( $_POST['eca_ref'] );
            $consultant = get_user_by( 'login', $ref_username );

            if ( $consultant && in_array( 'consultant', (array) $consultant->roles ) ) {
                // The referrer is a valid consultant, save the link
                update_user_meta( $user_id, '_eca_referred_by', $consultant->ID );
            }
        }
    }

    /**
     * Renders the consultant dashboard shortcode.
     *
     * @since    1.0.0
     */
    public function render_consultant_dashboard_shortcode() {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'You must be logged in to view this page.', 'educational-consulting-app' ) . '</p>';
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'consultant', (array) $user->roles ) ) {
            return '<p>' . esc_html__( 'You do not have permission to view this dashboard.', 'educational-consulting-app' ) . '</p>';
        }

        ob_start();

        // Display Referral Link
        $registration_url = wp_registration_url();
        $ref_link = add_query_arg( 'ref', $user->user_login, $registration_url );
        ?>
        <div class="consultant-dashboard">
            <h2><?php _e( 'Consultant Dashboard', 'educational-consulting-app' ); ?></h2>

            <div class="referral-link-section">
                <h3><?php _e( 'Your Referral Link', 'educational-consulting-app' ); ?></h3>
                <p><?php _e( 'Share this link with students to have them assigned to you when they register.', 'educational-consulting-app' ); ?></p>
                <input type="text" value="<?php echo esc_url( $ref_link ); ?>" readonly style="width: 100%;">
            </div>

            <div class="referred-students-section">
                <h3><?php _e( 'Your Referred Students', 'educational-consulting-app' ); ?></h3>
                <?php
                $args = array(
                    'role' => 'student',
                    'meta_key' => '_eca_referred_by',
                    'meta_value' => $user->ID,
                );
                $students = get_users( $args );

                if ( ! empty( $students ) ) {
                    echo '<ul>';
                    foreach ( $students as $student ) {
                        echo '<li>';
                        echo esc_html( $student->display_name );

                        // Find the latest result for this student
                        $result_args = array(
                            'post_type' => 'eca_result',
                            'author' => $student->ID,
                            'posts_per_page' => 1,
                            'orderby' => 'date',
                            'order' => 'DESC',
                        );
                        $latest_result = get_posts( $result_args );

                        if ( ! empty( $latest_result ) ) {
                            $result_url = get_permalink( $latest_result[0]->ID );
                            echo ' - <a href="' . esc_url( $result_url ) . '">View Latest Result</a>';
                        } else {
                            echo ' - <em>No results yet</em>';
                        }

                        echo '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p>' . esc_html__( 'You have not referred any students yet.', 'educational-consulting-app' ) . '</p>';
                }
                ?>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }
}
