<?php
/**
 * Template for the [psych_progress_path] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param array $items The items to display in the progress path (e.g., badges).
 * @param object $context The viewing context.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="psych-progress-path-container">
    <?php if ($context['is_impersonating']) :
        $viewed_user_data = get_userdata($context['viewed_user_id']);
    ?>
        <div class="coach-progress-notice">
            <i class="fas fa-user-eye"></i>
            <?php printf(esc_html__('Viewing progress for: %s', 'psych-system'), '<strong>' . esc_html($viewed_user_data->display_name) . '</strong>'); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($items)) : ?>
        <div class="psych-badges-grid">
            <?php foreach ($items as $item) : ?>
                <div class="psych-badge-item <?php echo $item['earned'] ? 'earned' : 'not-earned'; ?>">
                    <img src="<?php echo esc_url($item['icon']); ?>" alt="<?php echo esc_attr($item['name']); ?>" class="psych-badge-icon">
                    <span class="psych-badge-name"><?php echo esc_html($item['name']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p><?php esc_html_e('No progress to display.', 'psych-system'); ?></p>
    <?php endif; ?>
</div>
