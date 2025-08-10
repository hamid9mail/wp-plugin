<?php
/**
 * Template for the treasure map display mode.
 */
?>
<div class="psych-treasure-map">
    <div class="psych-map-background">
        <div class="psych-map-path">
            <?php foreach ($stations as $index => $station) : ?>
                <div class="psych-treasure-station <?php echo esc_attr($station['status']); ?>"
                     style="<?php echo $this->get_treasure_map_position($index, count($stations)); ?>"
                     data-station-node-id="<?php echo esc_attr($station['station_node_id']); ?>"
                     data-station-details="<?php echo esc_attr(json_encode($station, JSON_UNESCAPED_UNICODE)); ?>">

                    <div class="psych-treasure-icon">
                        <i class="<?php echo esc_attr($station['is_completed'] ? 'fas fa-trophy' : $station['icon']); ?>"></i>
                        <?php if ($station['is_completed']) : ?>
                            <div class="psych-treasure-glow"></div>
                        <?php endif; ?>
                    </div>

                    <div class="psych-treasure-popup">
                        <h4><?php echo esc_html($station['title']); ?></h4>

                        <?php if ($context['is_impersonating']) : ?>
                            <div class="coach-impersonation-indicator">
                                <i class="fas fa-user-tie"></i> نمایش مربی
                            </div>
                        <?php endif; ?>

                        <div class="psych-treasure-content">
                            <?php echo $this->render_inline_station_content($station); ?>
                        </div>
                    </div>

                    <?php if ($index < count($stations) - 1) : ?>
                        <div class="psych-treasure-path-line <?php echo ($station['is_completed'] ? 'completed' : 'incomplete'); ?>"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
