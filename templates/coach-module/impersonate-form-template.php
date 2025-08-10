<?php
/**
 * Template for the coach impersonation form ([coach_see_as_user]).
 *
 * @param array $assigned_students Array of student user objects.
 * @param int $current_page_id The ID of the current page.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="psych-form-container coach-impersonate-form">
    <h4><i class="fas fa-user-secret"></i> <?php esc_html_e('View as Student', 'psych-system'); ?></h4>

    <?php if (!empty($assigned_students)) : ?>
        <form method="get" action="<?php echo esc_url(get_permalink($current_page_id)); ?>">
            <p><?php esc_html_e('Select one of your students for this course to view their progress:', 'psych-system'); ?></p>
            <select name="seeas" required>
                <option value=""><?php esc_html_e('-- Select Student --', 'psych-system'); ?></option>
                <?php foreach ($assigned_students as $student) : ?>
                    <option value="<?php echo esc_attr($student->ID); ?>">
                        <?php echo esc_html($student->display_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button button-primary"><?php esc_html_e('View as this User', 'psych-system'); ?></button>
        </form>
    <?php else : ?>
        <p><em><?php esc_html_e('No students are currently assigned to you for this course.', 'psych-system'); ?></em></p>
    <?php endif; ?>
</div>
