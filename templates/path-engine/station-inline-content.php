<?php
/**
 * Template for inline station content (e.g., in accordions).
 *
 * @var array $station
 * @var int   $user_id
 * @var array $context
 */
?>
<div class="psych-inline-station-content">
    <?php if (!$station['is_unlocked']) : ?>
        <div class="psych-locked-station-inline">
            <div class="psych-lock-icon"><i class="fas fa-lock"></i></div>
            <div class="psych-lock-message">
                <h4>ایستگاه قفل است</h4>
                <p>برای باز کردن این ایستگاه، ابتدا ایستگاه‌های قبلی را تکمیل کنید.</p>
            </div>
        </div>
    <?php else : ?>
        <?php if (!empty($station['static_content'])) : ?>
            <div class="psych-static-section">
                <?php echo do_shortcode($station['static_content']); ?>
            </div>
        <?php endif; ?>
        <?php if ($station['is_completed']) : ?>
            <div class="psych-result-section">
                <div class="psych-result-badge"><i class="fas fa-check-circle"></i><span>تکمیل شده</span></div>
                <?php if (!empty($station['result_content'])) : ?>
                    <?php echo do_shortcode($station['result_content']); ?>
                <?php else : ?>
                    <p>این ماموریت با موفقیت تکمیل شده است!</p>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <?php if (!empty($station['mission_content'])) : ?>
                <div class="psych-mission-section">
                    <div class="psych-mission-instructions">
                        <?php echo do_shortcode($station['mission_content']); ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="psych-mission-actions">
                <?php echo $this->generate_mission_action_html($user_id, $station, $context); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
