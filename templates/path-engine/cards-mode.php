<?php
/**
 * Template for the cards display mode.
 */
?>
<div class="psych-cards">
    <?php foreach ($stations as $station) : ?>
        <div class="psych-card-item <?php echo esc_attr($station['status']); ?>"
             data-station-node-id="<?php echo esc_attr($station['station_node_id']); ?>"
             data-station-details="<?php echo esc_attr(json_encode($station, JSON_UNESCAPED_UNICODE)); ?>">

            <div class="psych-card-header">
                <div class="psych-card-icon">
                    <i class="<?php echo esc_attr($station['is_completed'] ? 'fas fa-check-circle' : $station['icon']); ?>"></i>
                </div>
                <div class="psych-card-status">
                    <?php echo $this->get_status_badge($station['status']); ?>
                </div>
            </div>

            <div class="psych-card-body">
                <h3 class="psych-card-title"><?php echo esc_html($station['title']); ?></h3>

                <?php if ($context['is_impersonating']) : ?>
                    <div class="coach-impersonation-indicator">
                        <i class="fas fa-user-tie"></i> نمایش مربی
                    </div>
                <?php endif; ?>
            </div>

            <div class="psych-card-footer">
                <?php echo $this->render_inline_station_content($station); ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
