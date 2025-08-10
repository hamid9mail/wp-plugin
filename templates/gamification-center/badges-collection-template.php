<?php
/**
 * Template for the [psych_user_badges_collection] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param array $badges The user's earned badges.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$grid_columns = 'columns-' . intval($atts['columns']);
$inline_styles = sprintf('border-radius: %s;', esc_attr($atts['border_radius']));

?>
<div class="psych-badges-collection-container" style="<?php echo $inline_styles; ?>">
    <?php if (!empty($atts['title'])) : ?>
        <h3 class="badges-collection-title" style="color: <?php echo esc_attr($atts['main_color']); ?>;"><?php echo esc_html($atts['title']); ?></h3>
    <?php endif; ?>

    <?php if (!empty($badges)) : ?>
        <div class="badges-grid <?php echo $grid_columns; ?>">
            <?php foreach ($badges as $badge) : ?>
                <div class="badge-item">
                    <div class="badge-icon-wrapper">
                        <img src="<?php echo esc_url($badge['icon_url']); ?>" alt="<?php echo esc_attr($badge['name']); ?>" />
                    </div>
                    <div class="badge-info">
                        <h4 class="badge-name"><?php echo esc_html($badge['name']); ?></h4>
                        <p class="badge-description"><?php echo esc_html($badge['description']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="badges-empty-message">
            <p><?php echo esc_html($atts['empty_message']); ?></p>
        </div>
    <?php endif; ?>
</div>
