<?php
/**
 * Template for the content loaded into the modal via AJAX.
 *
 * @param array $station The station data.
 * @param array $context The viewing context.
 * @param object $this The instance of the PsychoCourse_Path_Engine_4 class.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$user_id = $context['viewed_user_id'];
?>

<div class="psych-modal-header">
    <h3 class="psych-modal-title"><?php echo esc_html($station['title']); ?></h3>
</div>

<div class="psych-modal-body">
    <?php if ($station['is_completed']) : ?>
        <div class="result-content-wrapper">
            <h4><?php esc_html_e('Result', 'psych-path-engine'); ?></h4>
            <?php echo wpautop(do_shortcode($station['result_content'])); ?>
        </div>
    <?php else: ?>
        <div class="static-content-wrapper">
            <?php echo wpautop(do_shortcode($station['static_content'])); ?>
        </div>
        <div class="mission-content-wrapper">
            <h4><?php esc_html_e('Mission', 'psych-path-engine'); ?></h4>
            <?php echo wpautop(do_shortcode($station['mission_content'])); ?>
        </div>
    <?php endif; ?>
</div>

<div class="psych-modal-footer">
     <?php if (!$station['is_completed']) : ?>
        <div class="mission-action-wrapper">
            <?php
            echo $this->generate_mission_action_html($user_id, $station, $context);
            ?>
        </div>
    <?php endif; ?>
</div>
