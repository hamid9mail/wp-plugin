<!-- templates/path-engine/station-node-map.php -->
<?php
// This template is for rendering a single station icon on the treasure map.
// It's simpler than the timeline node.
$position_style = "left: " . esc_attr($station['x_pos']) . "; top: " . esc_attr($station['y_pos']) . ";";
$status_class = 'status-' . esc_attr($station['status']); // open, locked, completed
?>
<div class="station-node-map <?php echo $status_class; ?>" style="<?php echo $position_style; ?>" data-station-id="<?php echo esc_attr($station['station_node_id']); ?>" data-station-details="<?php echo esc_attr(json_encode($station)); ?>">
    <div class="station-map-icon">
        <i class="<?php echo esc_attr($station['icon']); ?>"></i>
    </div>
    <div class="station-map-title"><?php echo esc_html($station['title']); ?></div>
</div>
