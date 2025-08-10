<?php
/**
 * Template for the modal content, loaded via AJAX.
 *
 * @var array $station_details
 * @var bool  $is_completed
 * @var int   $user_id
 * @var array $context
 */

if (!empty($station_details['static_content'])) {
    echo '<div class="psych-static-content">' . wpautop(do_shortcode($station_details['static_content'])) . '</div>';
}

if ($is_completed) {
    echo '<div class="psych-result-content">';
    if (!empty($station_details['result_content'])) {
        echo wpautop(do_shortcode($station_details['result_content']));
    } else {
        echo '<p>این ماموریت با موفقیت تکمیل شده است!</p>';
    }
    echo '</div>';
} else {
    echo '<div class="psych-mission-area">';
    if (!empty($station_details['mission_content'])) {
        echo '<div class="psych-mission-content">' . wpautop(do_shortcode($station_details['mission_content'])) . '</div>';
    }
    echo $this->generate_mission_action_html($user_id, $station_details, $context);
    echo '</div>';
}
?>
