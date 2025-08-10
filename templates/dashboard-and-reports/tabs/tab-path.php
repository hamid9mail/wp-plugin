<?php
/**
 * Template for the Learning Path tab in the report card.
 *
 * @param int $user_id The ID of the user.
 * @param array $context The viewing context.
 * @param object $this The instance of the main class.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$path_data = $this->get_user_path_data($user_id);
?>
<div class="psych-section">
    <h3><i class="fas fa-route"></i> <?php esc_html_e('Learning Path Progress', 'psych-system'); ?></h3>
    <?php if (!empty($path_data['current_path'])) : ?>
        <div class="psych-path-info">
            <h4><?php echo esc_html($path_data['current_path']['title']); ?></h4>
            <div class="psych-progress-bar" data-percent="<?php echo esc_attr($path_data['completion_percentage']); ?>">
                <div class="psych-progress-fill" style="width: <?php echo esc_attr($path_data['completion_percentage']); ?>%;"></div>
            </div>
            <p><?php printf(esc_html__('%s%% complete (%d of %d stations)', 'psych-system'), $path_data['completion_percentage'], $path_data['completed_stations'], $path_data['total_stations']); ?></p>
        </div>
        <div class="psych-path-progress">
            <?php foreach ($path_data['stations'] as $station) : ?>
                <div class="psych-path-station <?php echo esc_attr($station['status']); ?>">
                    <div class="psych-station-icon"><i class="<?php echo esc_attr($station['icon']); ?>"></i></div>
                    <div class="psych-station-details">
                        <h4><?php echo esc_html($station['title']); ?></h4>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p><?php esc_html_e('No active learning path found.', 'psych-system'); ?></p>
    <?php endif; ?>
</div>
