<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Educational_Consulting_App
 * @subpackage Educational_Consulting_App/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Educational_Consulting_App
 * @subpackage Educational_Consulting_App/includes
 * @author     Jules <your-name@example.com>
 */
class Educational_Consulting_App {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Educational_Consulting_App_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if ( defined( 'ECA_VERSION' ) ) {
            $this->version = ECA_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'educational-consulting-app';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Educational_Consulting_App_Loader. Orchestrates the hooks of the plugin.
     * - Educational_Consulting_App_i18n. Defines internationalization functionality.
     * - Educational_Consulting_App_Admin. Defines all hooks for the admin area.
     * - Educational_Consulting_App_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once ECA_PLUGIN_DIR . 'includes/class-educational-consulting-app-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once ECA_PLUGIN_DIR . 'includes/class-educational-consulting-app-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once ECA_PLUGIN_DIR . 'admin/class-educational-consulting-app-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once ECA_PLUGIN_DIR . 'public/class-educational-consulting-app-public.php';

        /**
         * The class responsible for registering custom post types.
         */
        require_once ECA_PLUGIN_DIR . 'includes/class-educational-consulting-app-post-types.php';

        $this->loader = new Educational_Consulting_App_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Educational_Consulting_App_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new Educational_Consulting_App_i18n();

        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new Educational_Consulting_App_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

        // Add menu item
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );

        // Add Settings API hooks
        $this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );

        // Add meta boxes
        $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_meta_boxes' );
        $this->loader->add_action( 'save_post_eca_assessment', $plugin_admin, 'save_assessment_questions' );

    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {

        $plugin_public = new Educational_Consulting_App_Public( $this->get_plugin_name(), $this->get_version() );
        $plugin_cpts = new Educational_Consulting_App_Post_Types();

        $this->loader->add_action( 'init', $plugin_cpts, 'register_cpts' );
        $this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        // Hook to change user role on purchase
        $this->loader->add_action( 'woocommerce_order_status_completed', $plugin_public, 'maybe_change_role_on_purchase', 10, 1 );

        // Hook for test submission
        $this->loader->add_action( 'admin_post_eca_submit_test', $plugin_public, 'handle_test_submission' );

        // Filter for result template
        $this->loader->add_filter( 'template_include', $plugin_public, 'load_result_template' );

        // Hooks for referral system
        $this->loader->add_action( 'register_form', $plugin_public, 'add_referral_field_to_registration' );
        $this->loader->add_action( 'user_register', $plugin_public, 'save_referral_on_registration' );

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Educational_Consulting_App_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}
