<?php
/**
 * Template for the timeline display mode.
 *
 * @var array $stations
 * @var array $context
 */
?>
<div class="psych-timeline">
    <?php foreach ($stations as $station) : ?>
        <?php echo $this->render_single_station_node($station, $context); ?>
    <?php endforeach; ?>
</div>
