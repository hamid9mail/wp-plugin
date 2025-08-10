<?php
/**
 * Template for the main path container.
 *
 * @var string $path_id
 * @var array $shortcode_atts
 * @var array $context
 */
?>
<div class="psych-path-container psych-display-<?php echo esc_attr($shortcode_atts['display_mode']); ?> psych-theme-<?php echo esc_attr($shortcode_atts['theme']); ?>"
     id="<?php echo esc_attr($path_id); ?>"
     data-display-mode="<?php echo esc_attr($shortcode_atts['display_mode']); ?>">

    <?php if ($context['is_impersonating']) : ?>
        <div class="coach-path-notice">
            <i class="fas fa-user-eye"></i>
            در حال مشاهده مسیر به جای: <strong><?php echo esc_html(get_userdata($context['viewed_user_id'])->display_name); ?></strong>
        </div>
    <?php endif; ?>

    <?php if (!empty($shortcode_atts['path_title'])) : ?>
        <div class="psych-path-header">
            <h2 class="psych-path-title"><?php echo esc_html($shortcode_atts['path_title']); ?></h2>
        </div>
    <?php endif; ?>

    <?php if ($shortcode_atts['show_progress'] === 'true') : ?>
        <?php $this->get_template_part('progress-indicator', ['path_id' => $path_id]); ?>
    <?php endif; ?>

    <?php echo $this->render_path_by_display_mode($path_id, $context); ?>
</div>
