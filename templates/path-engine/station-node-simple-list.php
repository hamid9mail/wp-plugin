<!-- templates/path-engine/station-node-simple-list.php -->
<?php
$status_class = 'status-' . esc_attr($station['status']); // open, locked, completed
$is_clickable = $station['status'] !== 'locked';
?>
<li class="station-node-simple-list <?php echo $status_class; ?> <?php echo $is_clickable ? 'clickable' : ''; ?>"
    <?php if ($is_clickable): ?>
        data-station-id="<?php echo esc_attr($station['station_node_id']); ?>"
        data-station-details="<?php echo esc_attr(json_encode($station)); ?>"
    <?php endif; ?>>

    <i class="station-list-icon <?php echo esc_attr($station['icon']); ?>"></i>
    <span class="station-list-title"><?php echo esc_html($station['title']); ?></span>
    <span class="station-list-status-icon">
        <?php if ($station['status'] === 'completed'): ?>
            <i class="fas fa-check-circle"></i>
        <?php elseif ($station['status'] === 'locked'): ?>
            <i class="fas fa-lock"></i>
        <?php else: ?>
            <i class="fas fa-arrow-right"></i>
        <?php endif; ?>
    </span>
</li>
