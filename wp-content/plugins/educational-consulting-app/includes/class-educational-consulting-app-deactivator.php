<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Educational_Consulting_App
 * @subpackage Educational_Consulting_App/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Educational_Consulting_App
 * @subpackage Educational_Consulting_App/includes
 * @author     Jules <your-name@example.com>
 */
class Educational_Consulting_App_Deactivator {

    /**
     * The code that runs on plugin deactivation.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Remove the custom roles
        if ( get_role( 'student' ) ) {
            remove_role( 'student' );
        }
        if ( get_role( 'consultant' ) ) {
            remove_role( 'consultant' );
        }
    }

}
