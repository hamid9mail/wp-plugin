<?php
/**
 * Template for the [psych_leaderboard] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param array $top_users Array of top user data.
 * @param int $current_user_id ID of the current user viewing the page.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="psych-leaderboard">
    <h3><?php esc_html_e('Leaderboard', 'psych-system'); ?></h3>
    <?php if (empty($top_users)) : ?>
        <p><?php esc_html_e('No statistics available yet.', 'psych-system'); ?></p>
    <?php else : ?>
        <ol class="psych-leaderboard-list">
            <?php foreach ($top_users as $user_data) :
                $is_current = ($user_data['ID'] == $current_user_id);
                $css_class = $is_current ? ' class="current-user"' : '';
            ?>
                <li<?php echo $css_class; ?>>
                    <span class="user-name"><?php echo esc_html($user_data['display_name']); ?></span>
                    <span class="user-level"><?php echo esc_html($user_data['level']); ?></span>
                    <span class="user-points"><?php echo number_format_i18n($user_data['points']); ?> <?php esc_html_e('Points', 'psych-system'); ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</div>
