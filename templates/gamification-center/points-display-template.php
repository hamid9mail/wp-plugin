<?php
/**
 * Template for the [psych_user_points] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param int $points The user's points.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$label = $atts['show_label'] === 'true' ? __('Points: ', 'psych-system') : '';
?>
<span class="psych-user-points">
    <?php echo esc_html($label) . number_format_i18n($points); ?>
</span>
