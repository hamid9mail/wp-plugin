<?php
/**
 * Template for the cards display mode.
 *
 * @param array $stations The array of processed station data.
 * @param array $context The viewing context.
 * @param object $this The instance of the PsychoCourse_Path_Engine_4 class.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="psych-cards-wrapper">
    <?php foreach ($stations as $station) :
        $status_class = 'status-' . esc_attr($station['status']);
    ?>
        <div class="station-node station-node-card <?php echo $status_class; ?>" data-node-id="<?php echo esc_attr($station['station_node_id']); ?>">
            <div class="station-header">
                <span class="station-icon"><i class="<?php echo esc_attr($station['icon']); ?>"></i></span>
                <span class="station-status-badge"><?php echo esc_html(ucfirst($station['status'])); ?></span>
            </div>
            <div class="station-card-body">
                <h4 class="station-title"><?php echo esc_html($station['title']); ?></h4>
                <div class="station-content-wrapper">
                     <?php
                        // We can reuse the inline content template
                        $engine->get_template_part('station-inline-content', ['station' => $station, 'context' => $context, 'engine' => $engine]);
                    ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
