<?php
/**
 * Template for the [psych_user_points_display] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param int $points The user's current points.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>
<div class="psych-points-display-container <?php echo esc_attr($atts['custom_class']); ?>">
    <?php if (!empty($atts['title'])) : ?>
        <h4 class="points-title"><?php echo esc_html($atts['title']); ?></h4>
    <?php endif; ?>
    <div class="points-value">
        <?php if (!empty($atts['icon'])) : ?>
            <i class="<?php echo esc_attr($atts['icon']); ?>"></i>
        <?php endif; ?>
        <span><?php echo number_format($points); ?></span>
        <span class="points-label"><?php echo esc_html($atts['label']); ?></span>
    </div>
</div>
