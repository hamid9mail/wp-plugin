<?php
/**
 * Template for the "view as user" banner for the [coach_see_as_user] shortcode.
 *
 * @param object $target_user The user object of the student being viewed.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<div class="psych-coach-view-as-banner">
    <p>
        <i class="fas fa-user-secret"></i>
        <?php echo esc_html__('You are currently viewing this page as:', 'psych-g-plugin'); ?>
        <strong><?php echo esc_html($target_user->display_name); ?></strong>
        (ID: <?php echo esc_html($target_user->ID); ?>)
    </p>
    <a href="#" class="exit-view-as-button"><?php echo esc_html__('Exit View', 'psych-g-plugin'); ?></a>
</div>
