<?php
/**
 * Template for the Notes tab in the report card.
 *
 * @param int $user_id The ID of the user.
 * @param array $context The viewing context.
 * @param object $this The instance of the main class.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$can_edit = ($context['real_user_id'] == $user_id) || current_user_can('manage_options');
$user_notes = get_user_meta($user_id, 'psych_user_notes', true);
$user_goals = get_user_meta($user_id, 'psych_user_goals', true);
?>
<div class="psych-section">
    <h3><i class="fas fa-sticky-note"></i> <?php esc_html_e('Personal Notes', 'psych-system'); ?></h3>
    <div class="psych-notes-section">
        <textarea id="psych-user-notes"
                  placeholder="<?php esc_attr_e('Write your personal notes here...', 'psych-system'); ?>"
                  <?php echo $can_edit ? '' : 'readonly'; ?>
        ><?php echo esc_textarea($user_notes); ?></textarea>
    </div>
</div>

<div class="psych-section">
    <h3><i class="fas fa-bullseye"></i> <?php esc_html_e('Goals & Plans', 'psych-system'); ?></h3>
    <div class="psych-notes-section">
        <textarea id="psych-user-goals"
                  placeholder="<?php esc_attr_e('Define your goals and plans here...', 'psych-system'); ?>"
                  <?php echo $can_edit ? '' : 'readonly'; ?>
        ><?php echo esc_textarea($user_goals); ?></textarea>
    </div>
</div>
