<?php
/**
 * Template for the accordion display mode.
 */
?>
<div class="psych-accordion">
    <?php foreach ($stations as $index => $station) : ?>
        <div class="psych-accordion-item <?php echo esc_attr($station['status']); ?>"
             data-station-node-id="<?php echo esc_attr($station['station_node_id']); ?>"
             data-station-details="<?php echo esc_attr(json_encode($station, JSON_UNESCAPED_UNICODE)); ?>">

            <div class="psych-accordion-header">
                <div class="psych-accordion-icon">
                    <i class="<?php echo esc_attr($station['is_completed'] ? 'fas fa-check-circle' : $station['icon']); ?>"></i>
                </div>
                <h3 class="psych-accordion-title"><?php echo esc_html($station['title']); ?></h3>
                <div class="psych-accordion-status">
                    <?php echo $this->get_status_badge($station['status']); ?>
                </div>
                <button class="psych-accordion-toggle" aria-expanded="false">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>

            <div class="psych-accordion-content">
                <div class="psych-accordion-inner">
                    <?php if ($context['is_impersonating']) : ?>
                        <div class="coach-impersonation-indicator">
                            <i class="fas fa-user-tie"></i> نمایش مربی
                        </div>
                    <?php endif; ?>

                    <div class="psych-accordion-mission-content">
                        <?php echo $this->render_inline_station_content($station); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
