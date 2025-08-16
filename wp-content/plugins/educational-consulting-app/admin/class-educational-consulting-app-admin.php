<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Educational_Consulting_App
 * @subpackage Educational_Consulting_App/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Educational_Consulting_App
 * @subpackage Educational_Consulting_App/admin
 * @author     Jules <your-name@example.com>
 */
class Educational_Consulting_App_Admin {

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
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . '../assets/css/educational-consulting-app-admin.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . '../assets/js/educational-consulting-app-admin.js', array( 'jquery' ), $this->version, false );
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'Educational Consulting', 'educational-consulting-app' ),
            __( 'Edu Consulting', 'educational-consulting-app' ),
            'manage_options',
            $this->plugin_name,
            array( $this, 'render_settings_page' ),
            'dashicons-awards',
            25
        );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( $this->plugin_name . '_options' );
                do_settings_sections( $this->plugin_name );
                submit_button( __( 'Save Settings', 'educational-consulting-app' ) );
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting(
            $this->plugin_name . '_options',
            'eca_unlocking_product_id',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 0,
            )
        );

        add_settings_section(
            $this->plugin_name . '_woocommerce_section',
            __( 'WooCommerce Integration', 'educational-consulting-app' ),
            array( $this, 'render_settings_section' ),
            $this->plugin_name
        );

        add_settings_field(
            'eca_unlocking_product_id',
            __( 'Unlocking Product', 'educational-consulting-app' ),
            array( $this, 'render_product_dropdown_field' ),
            $this->plugin_name,
            $this->plugin_name . '_woocommerce_section'
        );
    }

    public function render_settings_section() {
        echo '<p>' . esc_html__( 'Select the WooCommerce product that grants access to the assessments upon purchase.', 'educational-consulting-app' ) . '</p>';
    }

    public function render_product_dropdown_field() {
        $selected_product_id = get_option( 'eca_unlocking_product_id', 0 );
        $products = wc_get_products( array( 'status' => 'publish', 'limit' => -1 ) );

        if ( empty( $products ) ) {
            echo '<p>' . esc_html__( 'No products found in WooCommerce. Please create a product first.', 'educational-consulting-app' ) . '</p>';
            return;
        }

        echo '<select id="eca_unlocking_product_id" name="eca_unlocking_product_id">';
        echo '<option value="0">' . esc_html__( '-- Select a Product --', 'educational-consulting-app' ) . '</option>';

        foreach ( $products as $product ) {
            printf(
                '<option value="%1$d" %2$s>%3$s</option>',
                esc_attr( $product->get_id() ),
                selected( $selected_product_id, $product->get_id(), false ),
                esc_html( $product->get_name() )
            );
        }

        echo '</select>';
    }

    /**
     * Adds the meta box for assessment questions.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'eca_questions_meta_box',
            __( 'Assessment Questions', 'educational-consulting-app' ),
            array( $this, 'render_questions_meta_box' ),
            'eca_assessment',
            'normal',
            'high'
        );
    }

    /**
     * Renders the meta box for assessment questions.
     */
    public function render_questions_meta_box( $post ) {
        wp_nonce_field( 'eca_save_questions_meta_box_data', 'eca_questions_meta_box_nonce' );

        $questions = get_post_meta( $post->ID, '_eca_questions', true );
        $riasec_options = array( 'R', 'I', 'A', 'S', 'E', 'C' );
        ?>
        <div id="questions-container">
            <p><?php _e('Add questions for this assessment. Each question should be assigned to a Holland Code (RIASEC) type.', 'educational-consulting-app'); ?></p>
            <div id="question-repeater">
                <?php
                if ( is_array( $questions ) && ! empty( $questions ) ) {
                    foreach ( $questions as $i => $question ) {
                        ?>
                        <div class="question-row">
                            <label><?php _e( 'Question Text:', 'educational-consulting-app' ); ?></label>
                            <input type="text" name="eca_questions[<?php echo $i; ?>][text]" value="<?php echo esc_attr( $question['text'] ); ?>" class="widefat" />
                            <label><?php _e( 'Holland Code:', 'educational-consulting-app' ); ?></label>
                            <select name="eca_questions[<?php echo $i; ?>][code]">
                                <?php foreach ($riasec_options as $code) : ?>
                                    <option value="<?php echo $code; ?>" <?php selected( $question['code'], $code ); ?>><?php echo $code; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="button remove-question"><?php _e( 'Remove', 'educational-consulting-app' ); ?></button>
                        </div>
                        <?php
                    }
                } else {
                    // Show one empty field by default
                    ?>
                    <div class="question-row">
                        <label><?php _e( 'Question Text:', 'educational-consulting-app' ); ?></label>
                        <input type="text" name="eca_questions[0][text]" value="" class="widefat" />
                        <label><?php _e( 'Holland Code:', 'educational-consulting-app' ); ?></label>
                        <select name="eca_questions[0][code]">
                            <?php foreach ($riasec_options as $code) : ?>
                                <option value="<?php echo $code; ?>"><?php echo $code; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="button remove-question"><?php _e( 'Remove', 'educational-consulting-app' ); ?></button>
                    </div>
                    <?php
                }
                ?>
            </div>
            <button type="button" id="add-question" class="button button-primary"><?php _e( 'Add Question', 'educational-consulting-app' ); ?></button>
        </div>
        <style>
            .question-row { border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; background: #f9f9f9; }
            .question-row label { display: block; margin-bottom: 5px; font-weight: bold; }
            .question-row input, .question-row select { margin-bottom: 10px; }
            .question-row .remove-question { color: #a00; border-color: #a00; }
        </style>
        <?php
    }

    /**
     * Saves the questions from the meta box.
     */
    public function save_assessment_questions( $post_id ) {
        if ( ! isset( $_POST['eca_questions_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['eca_questions_meta_box_nonce'], 'eca_save_questions_meta_box_data' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( ! isset( $_POST['eca_questions'] ) ) {
            // If no questions are submitted (e.g., all were removed), save an empty array to delete the meta.
            update_post_meta( $post_id, '_eca_questions', array() );
            return;
        }

        $questions_data = $_POST['eca_questions'];
        $sanitized_questions = array();

        foreach ( $questions_data as $question ) {
            if ( ! empty( $question['text'] ) ) {
                $sanitized_questions[] = array(
                    'text' => sanitize_text_field( $question['text'] ),
                    'code' => sanitize_text_field( $question['code'] ),
                );
            }
        }

        update_post_meta( $post_id, '_eca_questions', $sanitized_questions );
    }
}
