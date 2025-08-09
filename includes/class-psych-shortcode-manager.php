<?php
/**
 * Centralized Shortcode Manager for the Psych Complete System plugin.
 *
 * @package Psych_Complete_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Psych_Shortcode_Manager {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_all_shortcodes' ] );
	}

	/**
	 * Register all shortcodes for the plugin.
	 */
	public function register_all_shortcodes() {
        // interactive-content.php
        add_shortcode('psych_content_block', [Psych_Interactive_Content_Ultimate::get_instance(), 'render_content_block_shortcode']);
        add_shortcode('psych_content_view', [Psych_Interactive_Content_Ultimate::get_instance(), 'capture_content_view_shortcode']);
        add_shortcode('psych_button', [Psych_Interactive_Content_Ultimate::get_instance(), 'render_button_shortcode']);
        add_shortcode('psych_hidden_content', [Psych_Interactive_Content_Ultimate::get_instance(), 'render_hidden_content_shortcode']);
        add_shortcode('psych_progress_path', [Psych_Interactive_Content_Ultimate::get_instance(), 'render_progress_path_shortcode']);
        add_shortcode('psych_interactive_quiz', [Psych_Interactive_Content_Ultimate::get_instance(), 'render_quiz_shortcode']);
        add_shortcode('psych_interactive_poll', [Psych_Interactive_Content_Ultimate::get_instance(), 'render_poll_shortcode']);
        add_shortcode('psych_interactive_feedback', [Psych_Interactive_Content_Ultimate::get_instance(), 'render_feedback_shortcode']);
        add_shortcode('psych_personalize', [Psych_Interactive_Content_Ultimate::get_instance(), 'render_personalize_shortcode']);
        add_shortcode('psych_ai_test_form', [Psych_Interactive_Content_Ultimate::get_instance(), 'render_ai_test_form_shortcode']);
        add_shortcode('psych_quiz_in_content', [Psych_Interactive_Content_Ultimate::get_instance(), 'render_quiz_in_content_shortcode']);
        add_shortcode('psych_add_to_cart', [Psych_Interactive_Content_Ultimate::get_instance(), 'render_add_to_cart_shortcode']);

        // coach-module.php
        add_shortcode('coach_see_as_user', [Psych_Coach_Module_Ultimate::get_instance(), 'shortcode_coach_impersonate_form']);
        add_shortcode('coach_only_content', [Psych_Coach_Module_Ultimate::get_instance(), 'shortcode_coach_only_content']);
        add_shortcode('user_product_codes', [Psych_Coach_Module_Ultimate::get_instance(), 'shortcode_user_codes_list']);
        add_shortcode('coach_search_by_code', [Psych_Coach_Module_Ultimate::get_instance(), 'shortcode_coach_search_by_code']);
        add_shortcode('psych_user_dashboard', [Psych_Coach_Module_Ultimate::get_instance(), 'shortcode_user_dashboard']);
        add_shortcode('coach_quiz_view', [Psych_Coach_Module_Ultimate::get_instance(), 'shortcode_coach_quiz_view']);
        add_shortcode('psych_coach_dashboard', [Psych_Coach_Module_Ultimate::get_instance(), 'shortcode_coach_dashboard']);
        add_shortcode('psych_coach_page', [Psych_Coach_Module_Ultimate::get_instance(), 'shortcode_coach_page']);
        add_shortcode('psych_coach_approval_gate', [Psych_Coach_Module_Ultimate::get_instance(), 'shortcode_coach_approval_gate']);
        add_shortcode('psych_feedback_request', [Psych_Coach_Module_Ultimate::get_instance(), 'shortcode_feedback_request']);

        // dashboard-display.php
        add_shortcode('psych_dashboard', [Psych_Dashboard_Display_Enhanced::get_instance(), 'shortcode_dashboard']);
        add_shortcode('psych_gamified_header', [Psych_Dashboard_Display_Enhanced::get_instance(), 'shortcode_gamified_header']);
        add_shortcode('psych_user_performance_header', [Psych_Dashboard_Display_Enhanced::get_instance(), 'shortcode_performance_header']);
        add_shortcode('psych_user_points_display', [Psych_Dashboard_Display_Enhanced::get_instance(), 'shortcode_points_display']);
        add_shortcode('psych_user_level_display', [Psych_Dashboard_Display_Enhanced::get_instance(), 'shortcode_level_display']);
        add_shortcode('psych_user_badges_collection', [Psych_Dashboard_Display_Enhanced::get_instance(), 'shortcode_badges_collection']);
        add_shortcode('psych_user_leaderboard', [Psych_Dashboard_Display_Enhanced::get_instance(), 'shortcode_leaderboard']);
        add_shortcode('psych_achievement_timeline', [Psych_Dashboard_Display_Enhanced::get_instance(), 'shortcode_achievement_timeline']);
        add_shortcode('psych_quiz_results', [Psych_Dashboard_Display_Enhanced::get_instance(), 'shortcode_quiz_results']);

        // gamification-center.php
        add_shortcode('psych_user_points', [Psych_Gamification_Center::get_instance(), 'render_user_points_shortcode']);
        add_shortcode('psych_user_level', [Psych_Gamification_Center::get_instance(), 'render_user_level_shortcode']);
        add_shortcode('psych_user_badges', [Psych_Gamification_Center::get_instance(), 'render_user_badges_shortcode']);
        add_shortcode('psych_leaderboard', [Psych_Gamification_Center::get_instance(), 'render_leaderboard_shortcode']);
        add_shortcode('psych_mission_badge', [Psych_Gamification_Center::get_instance(), 'render_mission_badge_shortcode']);
        add_shortcode('psych_referral_mission', [Psych_Gamification_Center::get_instance(), 'render_referral_mission_shortcode']);
        add_shortcode('psych_social_share', [Psych_Gamification_Center::get_instance(), 'render_social_share_shortcode']);
        add_shortcode('psych_instagram_story_mission', [Psych_Gamification_Center::get_instance(), 'render_instagram_story_mission_shortcode']);

        // advanced-quiz-module.php
        add_shortcode('psych_quiz', [new Psych_Advanced_Quiz_Module(), 'render_quiz_shortcode']);

        // modules/
        if (class_exists('Psych_Secure_Audio')) {
            add_shortcode('psych_secure_audio', [Psych_Secure_Audio::get_instance(), 'render_secure_audio_shortcode']);
        }
        if (class_exists('Spot_Player_Integration')) {
            add_shortcode('psych_spot_player', [Spot_Player_Integration::get_instance(), 'render_spot_player_shortcode']);
        }
        if (class_exists('WC_Assessment_Product')) {
            add_shortcode('psych_assessment_product', [WC_Assessment_Product::get_instance(), 'render_assessment_product_shortcode']);
        }

        // report-card.php
        add_shortcode('psych_report_card', [Psych_Unified_Report_Card_Enhanced::get_instance(), 'render_report_card_shortcode']);

        // path-engine.php
        add_shortcode('psych_path', [PsychoCourse_Path_Engine_Ultimate::get_instance(), 'render_path_shortcode']);
        add_shortcode('psych_station', [PsychoCourse_Path_Engine_Ultimate::get_instance(), 'register_station_shortcode']);
        add_shortcode('psych_mission', [PsychoCourse_Path_Engine_Ultimate::get_instance(), 'register_mission_shortcode']);
	}
}

new Psych_Shortcode_Manager();
