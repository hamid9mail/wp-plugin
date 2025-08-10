<?php
/**
 * Template for the main report card layout.
 *
 * @param array $atts Shortcode attributes.
 * @param object $user The user object.
 * @param array $context The viewing context.
 * @param array $tabs The tabs to be displayed.
 * @param object $this The instance of the main class.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="psych-report-card-container">
    <?php if ($context['is_impersonating']): ?>
        <div class="psych-impersonation-notice">
            <i class="fas fa-eye"></i>
            <?php printf(esc_html__('You are viewing the report card for %s', 'psych-system'), '<strong>' . esc_html($user->display_name) . '</strong>'); ?>
        </div>
    <?php endif; ?>

    <div class="psych-report-card-header">
        <img src="<?php echo esc_url(get_avatar_url($user->ID, ['size' => 80])); ?>" alt="Avatar" class="avatar">
        <div class="user-info">
            <h2><?php echo esc_html($user->display_name); ?></h2>
            <div class="psych-user-status">
                <?php
                // This shows how we can call methods from the main class within the template
                $level_info = $this->get_user_level_info($user->ID);
                $total_points = $this->get_user_total_points($user->ID);
                ?>
                <span class="level" style="color: <?php echo esc_attr($level_info['color']); ?>">
                    <i class="<?php echo esc_attr($level_info['icon']); ?>"></i>
                    <?php echo esc_html($level_info['name']); ?>
                </span>
                •
                <span class="points"><?php echo number_format_i18n($total_points); ?> <?php esc_html_e('Points', 'psych-system'); ?></span>
            </div>
        </div>
    </div>

    <div class="psych-report-card-tabs">
        <?php foreach ($tabs as $index => $tab_slug):
            $tab_info = $this->get_tab_info($tab_slug);
        ?>
            <button class="tab-link <?php echo $index === 0 ? 'active' : ''; ?>" data-tab="psych-tab-<?php echo esc_attr($tab_slug); ?>">
                <i class="<?php echo esc_attr($tab_info['icon']); ?>"></i>
                <?php echo esc_html($tab_info['title']); ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="psych-report-card-body">
        <?php foreach ($tabs as $index => $tab_slug):
            $is_active = $index === 0;
        ?>
            <div id="psych-tab-<?php echo esc_attr($tab_slug); ?>" class="psych-tab-content <?php echo $is_active ? 'active' : ''; ?>">
                <?php
                // Include the specific template for this tab
                $this->get_template_part('tabs/tab-' . $tab_slug, [
                    'user_id' => $user->ID,
                    'context' => $context,
                    'this' => $this
                ]);
                ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
