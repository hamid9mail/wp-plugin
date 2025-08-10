<?php
/**
 * Template for the timeline display mode.
 *
 * @param array $stations The array of processed station data.
 * @param array $context The viewing context.
 * @param object $this The instance of the PsychoCourse_Path_Engine_4 class.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<div class="psych-timeline-wrapper">
    <?php foreach ($stations as $station) : ?>
        <?php
        // For each station, we include a sub-template to keep the code clean.
        $engine->get_template_part('station-node-timeline', ['station' => $station, 'context' => $context, 'engine' => $engine]);
        ?>
    <?php endforeach; ?>
</div>
