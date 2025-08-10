<?php
/**
 * Template for the simple list display mode.
 */
?>
<div class="psych-simple-list">
    <?php foreach ($stations as $index => $station) : ?>
        <div class="psych-list-item <?php echo esc_attr($station['status']); ?>"
             data-station-node-id="<?php echo esc_attr($station['station_node_id']); ?>"
             data-station-details="<?php echo esc_attr(json_encode($station, JSON_UNESCAPED_UNICODE)); ?>">

            <div class="psych-list-number">
                <?php if ($station['is_completed']) : ?>
                    <i class="fas fa-check"></i>
                <?php else : ?>
                    <?php echo $index + 1; ?>
                <?php endif; ?>
            </div>

            <div class="psych-list-content">
                <h3 class="psych-list-title"><?php echo esc_html($station['title']); ?></h3>

                <?php if ($context['is_impersonating']) : ?>
                    <div class="coach-impersonation-indicator">
                        <i class="fas fa-user-tie"></i> نمایش مربی
                    </div>
                <?php endif; ?>
            </div>

            <div class="psych-list-status">
                <?php echo $this->get_status_badge($station['status']); ?>
            </div>

            <div class="psych-list-action">
                <?php echo $this->render_inline_station_content($station); ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
