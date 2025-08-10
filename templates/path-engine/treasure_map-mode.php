<?php
/**
 * Template for the treasure map display mode.
 *
 * @param array $stations The array of processed station data.
 * @param array $context The viewing context.
 * @param object $this The instance of the PsychoCourse_Path_Engine_4 class.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="psych-treasure-map-wrapper">
    <div class="treasure-map-background">
        <div class="treasure-map-path-line"></div>
        <?php foreach ($stations as $index => $station) :
            $status_class = 'status-' . esc_attr($station['status']);
            // The main class will have a helper to calculate these positions
            $position_style = $engine->get_treasure_map_position($index, count($stations));
        ?>
            <div class="station-node station-node-treasure-map <?php echo $status_class; ?>" style="<?php echo esc_attr($position_style); ?>" data-node-id="<?php echo esc_attr($station['station_node_id']); ?>">
                <div class="station-treasure-icon" title="<?php echo esc_attr($station['title']); ?>">
                    <i class="<?php echo esc_attr($station['icon']); ?>"></i>
                </div>
                <div class="station-treasure-popup">
                    <h4 class="station-title"><?php echo esc_html($station['title']); ?></h4>
                     <?php
                        $engine->get_template_part('station-inline-content', ['station' => $station, 'context' => $context, 'engine' => $engine]);
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
