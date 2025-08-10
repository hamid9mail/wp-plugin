<?php
/**
 * Template for the [psych_user_level] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param array $level The user's level data.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$icon = $atts['show_icon'] === 'true' ? '<i class="' . esc_attr($level['icon']) . '"></i> ' : '';
?>
<span class="psych-user-level" style="color: <?php echo esc_attr($level['color']); ?>;">
    <?php echo $icon . esc_html($level['name']); ?>
</span>
