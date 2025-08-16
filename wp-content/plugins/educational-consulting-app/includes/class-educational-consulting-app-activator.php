<?php
/**
 * Fired during plugin activation
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Educational_Consulting_App
 * @subpackage Educational_Consulting_App/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Educational_Consulting_App
 * @subpackage Educational_Consulting_App/includes
 * @author     Jules <your-name@example.com>
 */
class Educational_Consulting_App_Activator {

    /**
     * The code that runs on plugin activation.
     *
     * @since    1.0.0
     */
    public static function activate() {
        self::create_custom_roles();
        self::create_answers_table();
    }

    /**
     * Create the custom user roles.
     */
    private static function create_custom_roles() {
        add_role(
            'student',
            __( 'Student', 'educational-consulting-app' ),
            array( 'read' => true )
        );
        add_role(
            'consultant',
            __( 'Consultant', 'educational-consulting-app' ),
            array( 'read' => true )
        );
    }

    /**
     * Create the custom database table for answers.
     */
    private static function create_answers_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eca_answers';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            answer_id bigint(20) NOT NULL AUTO_INCREMENT,
            result_id bigint(20) NOT NULL,
            question_key varchar(255) NOT NULL,
            answer_value text NOT NULL,
            answered_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (answer_id),
            KEY result_id (result_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

}
