<!-- templates/path-engine/treasure_map-mode.php -->
<div class="psych-path-container treasure-map-mode" style="background-image: url('<?php echo esc_url($path_data['map_background_url']); ?>');">
    <div class="psych-path-stations-wrapper">
        <?php foreach ($stations as $station): ?>
            <?php $engine->get_template_part('station-node-map', ['station' => $station]); ?>
        <?php endforeach; ?>
    </div>
</div>
