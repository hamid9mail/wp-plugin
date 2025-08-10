<?php
/**
 * Template for a single station node in timeline view.
 *
 * @var array $station
 * @var array $context
 * @var string $status_class
 * @var string $button_text
 * @var bool $is_disabled
 */
?>
<div class="psych-timeline-item <?php echo esc_attr($status_class); ?>"
     data-station-node-id="<?php echo esc_attr($station['station_node_id']); ?>"
     data-station-details="<?php echo esc_attr(json_encode($station, JSON_UNESCAPED_UNICODE)); ?>">
    <div class="psych-timeline-icon">
        <i class="<?php echo esc_attr($station['is_completed'] ? 'fas fa-check-circle' : $station['icon']); ?>"></i>
    </div>
    <div class="psych-timeline-content">
        <h3 class="psych-station-title"><?php echo esc_html($station['title']); ?></h3>
        <?php if ($context['is_impersonating']) : ?>
            <div class="coach-impersonation-indicator">
                <i class="fas fa-user-tie"></i> نمایش مربی
            </div>
        <?php endif; ?>
        <button class="psych-station-action-btn" <?php echo $is_disabled ? 'disabled' : ''; ?>>
            <?php echo esc_html($button_text); ?>
        </button>
    </div>
</div>
