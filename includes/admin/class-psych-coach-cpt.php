<?php
/**
 * Register the Coach Custom Post Type.
 *
 * @package Psych_Complete_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Psych_Coach_CPT {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_cpt' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post_psych_coach', [ $this, 'save_meta_box_data' ] );
	}

	/**
	 * Register the Custom Post Type.
	 */
	public function register_cpt() {
		$labels = [
			'name'                  => _x( 'Coaches', 'Post Type General Name', 'psych-system' ),
			'singular_name'         => _x( 'Coach', 'Post Type Singular Name', 'psych-system' ),
			'menu_name'             => __( 'Coaches', 'psych-system' ),
			'name_admin_bar'        => __( 'Coach', 'psych-system' ),
			'archives'              => __( 'Coach Archives', 'psych-system' ),
			'attributes'            => __( 'Coach Attributes', 'psych-system' ),
			'parent_item_colon'     => __( 'Parent Coach:', 'psych-system' ),
			'all_items'             => __( 'All Coaches', 'psych-system' ),
			'add_new_item'          => __( 'Add New Coach', 'psych-system' ),
			'add_new'               => __( 'Add New', 'psych-system' ),
			'new_item'              => __( 'New Coach', 'psych-system' ),
			'edit_item'             => __( 'Edit Coach', 'psych-system' ),
			'update_item'           => __( 'Update Coach', 'psych-system' ),
			'view_item'             => __( 'View Coach', 'psych-system' ),
			'view_items'            => __( 'View Coaches', 'psych-system' ),
			'search_items'          => __( 'Search Coach', 'psych-system' ),
			'not_found'             => __( 'Not found', 'psych-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'psych-system' ),
			'featured_image'        => __( 'Coach Image', 'psych-system' ),
			'set_featured_image'    => __( 'Set coach image', 'psych-system' ),
			'remove_featured_image' => __( 'Remove coach image', 'psych-system' ),
			'use_featured_image'    => __( 'Use as coach image', 'psych-system' ),
			'insert_into_item'      => __( 'Insert into coach', 'psych-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this coach', 'psych-system' ),
			'items_list'            => __( 'Coaches list', 'psych-system' ),
			'items_list_navigation' => __( 'Coaches list navigation', 'psych-system' ),
			'filter_items_list'     => __( 'Filter coaches list', 'psych-system' ),
		];
		$args   = [
			'label'               => __( 'Coach', 'psych-system' ),
			'description'         => __( 'Post Type for Coaches', 'psych-system' ),
			'labels'              => $labels,
			'supports'            => [ 'title', 'editor', 'thumbnail' ],
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => false, // We will add it to our custom menu
			'menu_position'       => 5,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'rewrite'             => [ 'slug' => 'coach' ],
		];
		register_post_type( 'psych_coach', $args );
	}

	/**
	 * Add meta boxes.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'psych_coach_details',
			__( 'Coach Details', 'psych-system' ),
			[ $this, 'render_meta_box' ],
			'psych_coach',
			'normal',
			'high'
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'psych_coach_meta_box', 'psych_coach_meta_box_nonce' );
		$user_id = get_post_meta( $post->ID, '_psych_coach_user_id', true );
		?>
		<p>
			<label for="psych_coach_user_id"><?php esc_html_e( 'Linked User Account', 'psych-system' ); ?></label>
			<?php
			wp_dropdown_users( [
				'name'             => 'psych_coach_user_id',
				'id'               => 'psych_coach_user_id',
				'selected'         => $user_id,
				'show_option_none' => __( 'Select a User', 'psych-system' ),
			] );
			?>
		</p>
		<?php
	}

	/**
	 * Save the meta box data.
	 *
	 * @param int $post_id The post ID.
	 */
	public function save_meta_box_data( $post_id ) {
		if ( ! isset( $_POST['psych_coach_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['psych_coach_meta_box_nonce'], 'psych_coach_meta_box' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['psych_coach_user_id'] ) ) {
			update_post_meta( $post_id, '_psych_coach_user_id', intval( $_POST['psych_coach_user_id'] ) );
		}
	}
}

new Psych_Coach_CPT();
