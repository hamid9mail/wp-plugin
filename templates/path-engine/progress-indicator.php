<?php
/**
 * Template for the progress indicator.
 *
 * @param string $path_id The unique ID for this path instance.
 * @param object $this The instance of the PsychoCourse_Path_Engine_4 class.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$stations = $this->path_data[$path_id]['stations'];
$total = count($stations);
$completed = count(array_filter($stations, function($station) {
    return $station['is_completed'];
}));
$percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

?>
<div class="psych-path-progress-indicator">
    <div class="psych-path-progress-bar-container">
        <div class="psych-path-progress-bar" style="width: <?php echo $percentage; ?>%;"></div>
    </div>
    <div class="psych-path-progress-text">
        <?php printf(
            esc_html__('Progress: %d of %d stations completed (%d%%)', 'psych-path-engine'),
            $completed,
            $total,
            $percentage
        ); ?>
    </div>
</div>
