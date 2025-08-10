<?php
/**
 * Template for the accordion display mode.
 *
 * @param array $stations The array of processed station data.
 * @param array $context The viewing context.
 * @param object $this The instance of the PsychoCourse_Path_Engine_4 class.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="psych-accordion-wrapper">
    <?php foreach ($stations as $station) :
        $status_class = 'status-' . esc_attr($station['status']);
    ?>
        <div class="station-node station-node-accordion <?php echo $status_class; ?>" data-node-id="<?php echo esc_attr($station['station_node_id']); ?>">
            <div class="station-header">
                <span class="station-icon"><i class="<?php echo esc_attr($station['icon']); ?>"></i></span>
                <h4 class="station-title"><?php echo esc_html($station['title']); ?></h4>
                <span class="station-status-icon">
                    <?php if ($station['is_completed']) : ?>
                        <i class="fas fa-check-circle"></i>
                    <?php elseif ($station['is_unlocked']) : ?>
                        <i class="fas fa-chevron-down"></i>
                    <?php else : ?>
                        <i class="fas fa-lock"></i>
                    <?php endif; ?>
                </span>
            </div>
            <div class="station-content-wrapper" style="display: none;">
                <?php
                // The inline content will be rendered here.
                // We need a template for the content itself.
                $engine->get_template_part('station-inline-content', ['station' => $station, 'context' => $context, 'engine' => $engine]);
                ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
