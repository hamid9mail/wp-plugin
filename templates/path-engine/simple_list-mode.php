<?php
/**
 * Template for the simple list display mode.
 *
 * @param array $stations The array of processed station data.
 * @param array $context The viewing context.
 * @param object $this The instance of the PsychoCourse_Path_Engine_4 class.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="psych-simple-list-wrapper">
    <?php foreach ($stations as $index => $station) :
        $status_class = 'status-' . esc_attr($station['status']);
    ?>
        <div class="station-node station-node-simple-list <?php echo $status_class; ?>" data-node-id="<?php echo esc_attr($station['station_node_id']); ?>">
            <div class="station-list-number">
                <?php if ($station['is_completed']) : ?>
                    <i class="fas fa-check"></i>
                <?php else: ?>
                    <?php echo $index + 1; ?>
                <?php endif; ?>
            </div>
            <div class="station-list-title">
                <?php echo esc_html($station['title']); ?>
            </div>
            <div class="station-list-action">
                 <button class="station-action-button" <?php echo $station['is_unlocked'] ? '' : 'disabled'; ?>>
                    <?php echo esc_html($this->get_button_text($station)); ?>
                </button>
            </div>
        </div>
    <?php endforeach; ?>
</div>
