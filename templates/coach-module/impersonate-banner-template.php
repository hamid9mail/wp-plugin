<?php
/**
 * Template for the banner shown within [coach_only_content].
 *
 * @param object $user_data The user object of the student being viewed.
 * @param string $stop_link The URL to stop the impersonation session.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="coach-impersonation-notice">
    <?php printf(
        wp_kses_post(__('You are currently viewing as <strong>%s</strong>. <a href="%s">Return to your account</a>.', 'psych-system')),
        esc_html($user_data->display_name),
        esc_url($stop_link)
    ); ?>
</div>
