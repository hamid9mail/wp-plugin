<?php
/**
 * Register all Custom Post Types for the plugin
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Educational_Consulting_App
 * @subpackage Educational_Consulting_App/includes
 */

/**
 * Register all Custom Post Types for the plugin.
 *
 * @package    Educational_Consulting_App
 * @subpackage Educational_Consulting_App/includes
 * @author     Jules <your-name@example.com>
 */
class Educational_Consulting_App_Post_Types {

    /**
     * Register the Custom Post Types.
     *
     * @since    1.0.0
     */
    public function register_cpts() {
        $this->register_assessment_cpt();
        $this->register_result_cpt();
    }

    /**
     * Register the "Assessment" Custom Post Type.
     */
    private function register_assessment_cpt() {
        $labels = array(
            'name'                  => _x( 'Assessments', 'Post Type General Name', 'educational-consulting-app' ),
            'singular_name'         => _x( 'Assessment', 'Post Type Singular Name', 'educational-consulting-app' ),
            'menu_name'             => __( 'Assessments', 'educational-consulting-app' ),
            'name_admin_bar'        => __( 'Assessment', 'educational-consulting-app' ),
            'archives'              => __( 'Assessment Archives', 'educational-consulting-app' ),
            'attributes'            => __( 'Assessment Attributes', 'educational-consulting-app' ),
            'parent_item_colon'     => __( 'Parent Assessment:', 'educational-consulting-app' ),
            'all_items'             => __( 'All Assessments', 'educational-consulting-app' ),
            'add_new_item'          => __( 'Add New Assessment', 'educational-consulting-app' ),
            'add_new'               => __( 'Add New', 'educational-consulting-app' ),
            'new_item'              => __( 'New Assessment', 'educational-consulting-app' ),
            'edit_item'             => __( 'Edit Assessment', 'educational-consulting-app' ),
            'update_item'           => __( 'Update Assessment', 'educational-consulting-app' ),
            'view_item'             => __( 'View Assessment', 'educational-consulting-app' ),
            'view_items'            => __( 'View Assessments', 'educational-consulting-app' ),
            'search_items'          => __( 'Search Assessment', 'educational-consulting-app' ),
        );
        $args = array(
            'label'                 => __( 'Assessment', 'educational-consulting-app' ),
            'description'           => __( 'For creating and managing educational assessments.', 'educational-consulting-app' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'custom-fields' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 26,
            'menu_icon'             => 'dashicons-chart-pie',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'rewrite'               => false,
        );
        register_post_type( 'eca_assessment', $args );
    }

    /**
     * Register the "Result" Custom Post Type.
     */
    private function register_result_cpt() {
        $labels = array(
            'name'                  => _x( 'Results', 'Post Type General Name', 'educational-consulting-app' ),
            'singular_name'         => _x( 'Result', 'Post Type Singular Name', 'educational-consulting-app' ),
            'menu_name'             => __( 'Results', 'educational-consulting-app' ),
            'all_items'             => __( 'All Results', 'educational-consulting-app' ),
            'edit_item'             => __( 'Edit Result', 'educational-consulting-app' ),
            'view_item'             => __( 'View Result', 'educational-consulting-app' ),
        );
        $args = array(
            'label'                 => __( 'Result', 'educational-consulting-app' ),
            'description'           => __( 'Stores the results of assessments taken by students.', 'educational-consulting-app' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'author', 'custom-fields' ),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => 'edit.php?post_type=eca_assessment',
            'show_in_admin_bar'     => false,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => true, // To allow viewing via a secret link
            'capability_type'       => 'post',
            'capabilities' => array(
                'create_posts' => 'do_not_allow', // prevent manual creation
            ),
            'map_meta_cap' => true, // so that create_posts is properly handled
            'rewrite' => array('slug' => 'assessment-results'),
        );
        register_post_type( 'eca_result', $args );
    }
}
