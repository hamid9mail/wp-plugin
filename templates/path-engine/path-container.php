<?php
/**
 * Template for the main path container.
 *
 * @param string $path_id The unique ID for this path instance.
 * @param array $shortcode_atts The attributes from the [psychocourse_path] shortcode.
 * @param array $context The viewing context (impersonation status, user IDs).
 * @param object $this The instance of the PsychoCourse_Path_Engine_4 class.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$path_data = $engine->path_data[$path_id];
$theme_class = 'psych-theme-' . esc_attr($path_data['theme']);
$display_mode_class = 'psych-display-' . esc_attr($path_data['display_mode']);
$user_id = $context['viewed_user_id'];

?>
<div class="psych-path-container <?php echo $display_mode_class; ?> <?php echo $theme_class; ?>" id="<?php echo esc_attr($path_id); ?>" data-display-mode="<?php echo esc_attr($path_data['display_mode']); ?>">

    <?php if ($context['is_impersonating']) : ?>
        <div class="coach-path-notice">
            <i class="fas fa-user-shield"></i>
            <?php printf(
                esc_html__('Viewing as: %s', 'psych-path-engine'),
                '<strong>' . esc_html(get_userdata($user_id)->display_name) . '</strong>'
            ); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($path_data['path_title'])) : ?>
        <div class="psych-path-header">
            <h2 class="psych-path-title"><?php echo esc_html($path_data['path_title']); ?></h2>
        </div>
    <?php endif; ?>

    <?php if ($path_data['show_progress']) :
        // We need a separate template for the progress indicator
        $engine->get_template_part('progress-indicator', ['path_id' => $path_id]);
    endif; ?>

    <div class="psych-path-body">
        <?php
        // The main class will call the appropriate template for the display mode
        // For example: $this->get_template_part('timeline-mode', ['stations' => $path_data['stations'], 'context' => $context]);
        // This is handled in the main class logic. We just provide the container here.
        echo $path_body_content; // This variable will be passed in from the main class
        ?>
    </div>

</div>
