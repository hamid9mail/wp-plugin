<?php
/**
 * Template for the progress indicator.
 *
 * @var string $path_id
 */
$stations = $this->path_data[$path_id]['stations'];
$total = count($stations);
$completed = count(array_filter($stations, function($station) {
    return $station['is_completed'];
}));
$percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
?>
<div class="psych-progress-indicator">
    <div class="psych-progress-stats">
        <span class="psych-progress-text">پیشرفت: <?php echo $completed; ?> از <?php echo $total; ?> ایستگاه</span>
        <span class="psych-progress-percentage"><?php echo $percentage; ?>%</span>
    </div>
    <div class="psych-progress-bar">
        <div class="psych-progress-fill" style="width: <?php echo $percentage; ?>%"></div>
    </div>
</div>
