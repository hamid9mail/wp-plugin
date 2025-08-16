<?php
/**
 * Plugin Name:       Educational Consulting App
 * Plugin URI:        https://example.com/
 * Description:       A comprehensive application for educational consulting, featuring tests, reports, and a referral system for consultants.
 * Version:           1.0.0
 * Author:            Jules
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       educational-consulting-app
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define plugin constants
define( 'ECA_VERSION', '1.0.0' );
define( 'ECA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ECA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-educational-consulting-app-activator.php
 */
function activate_educational_consulting_app() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-educational-consulting-app-activator.php';
	Educational_Consulting_App_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-educational-consulting-app-deactivator.php
 */
function deactivate_educational_consulting_app() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-educational-consulting-app-deactivator.php';
	Educational_Consulting_App_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_educational_consulting_app' );
register_deactivation_hook( __FILE__, 'deactivate_educational_consulting_app' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require ECA_PLUGIN_DIR . 'includes/class-educational-consulting-app.php';

/**
 * Begins execution of the plugin.
 */
function run_educational_consulting_app() {
    $plugin = new Educational_Consulting_App();
    $plugin->run();
}

/**
 * Check if WooCommerce is active before running the plugin.
 */
function eca_run_on_plugins_loaded() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'eca_missing_woocommerce_notice' );
        return;
    }

    run_educational_consulting_app();
}
add_action( 'plugins_loaded', 'eca_run_on_plugins_loaded' );

/**
 * Display an admin notice if WooCommerce is not active.
 */
function eca_missing_woocommerce_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php esc_html_e( 'The "Educational Consulting App" plugin requires WooCommerce to be installed and active. Please install or activate WooCommerce.', 'educational-consulting-app' ); ?></p>
    </div>
    <?php
}
