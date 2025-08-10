<?php
/**
 * Template for the user search form for the [coach_see_as_user] shortcode.
 *
 * @param array $atts Shortcode attributes passed from the handler.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<div class="psych-coach-user-search-container">
    <h3><?php echo esc_html__('View as Student', 'psych-g-plugin'); ?></h3>
    <p><?php echo esc_html__('Enter a student\'s name, email, or user ID to see their progress and view the course as they see it.', 'psych-g-plugin'); ?></p>

    <form id="coach-user-search-form" class="psych-coach-user-search-form">
        <div class="form-group">
            <label for="coach-search-query"><?php echo esc_html__('Search for Student', 'psych-g-plugin'); ?></label>
            <input type="text" id="coach-search-query" name="search_query" placeholder="<?php echo esc_html__('e.g., John Doe, john@example.com, 123', 'psych-g-plugin'); ?>" required />
        </div>
        <button type="submit" class="search-button"><?php echo esc_html__('Search', 'psych-g-plugin'); ?></button>
    </form>

    <div id="coach-search-results">
        <!-- AJAX search results will be loaded here -->
    </div>
</div>
