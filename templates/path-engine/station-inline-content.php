<?php
/**
 * Template for the content inside a station (for accordion, cards, etc.).
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
<div class="station-inner-content">
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
        <div class="mission-action-wrapper">
            <?php
            // The main class has a helper function to generate the correct button or form
            // based on the mission type (e.g., button, gform, purchase).
            echo $this->generate_mission_action_html($user_id, $station, $context);
            ?>
        </div>
    <?php endif; ?>
</div>
