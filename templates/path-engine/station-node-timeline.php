<?php
/**
 * Template for a single station node in timeline view.
 *
 * @param array $station The station data.
 * @param array $context The viewing context.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$status_class = 'status-' . esc_attr($station['status']); // completed, open, locked, restricted
$is_disabled = !$station['is_unlocked'];
$button_text = $engine->get_button_text($station); // Helper method in the main class

?>
<div class="station-node station-node-timeline <?php echo $status_class; ?>" data-node-id="<?php echo esc_attr($station['station_node_id']); ?>" data-station-details='<?php echo esc_attr(json_encode($station)); ?>'>
    <div class="station-header">
        <span class="station-icon">
            <i class="<?php echo esc_attr($station['is_completed'] ? 'fas fa-check-circle' : $station['icon']); ?>"></i>
        </span>
        <h4 class="station-title"><?php echo esc_html($station['title']); ?></h4>
        <span class="station-status-icon">
            <?php if ($station['is_completed']) : ?>
                <i class="fas fa-check-circle"></i>
            <?php elseif ($station['is_unlocked']) : ?>
                <i class="fas fa-lock-open"></i>
            <?php else : ?>
                <i class="fas fa-lock"></i>
            <?php endif; ?>
        </span>
    </div>
    <div class="station-timeline-content-wrapper">
        <!-- Content can be loaded via AJAX or be inline -->
        <button class="station-action-button" <?php echo $is_disabled ? 'disabled' : ''; ?>>
            <?php echo esc_html($button_text); ?>
        </button>
    </div>
</div>
