<?php
/**
 * Template for the Statistics tab in the Gamification Center admin.
 *
 * @param array $top_users Array of top user data.
 * @param array $badge_stats Array of badge statistics.
 */
if (!defined('ABSPATH')) exit;
?>
<div class="psych-stats-container">
    <div class="psych-stats-section">
        <h2><?php esc_html_e('Top Users', 'psych-system'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Rank', 'psych-system'); ?></th>
                    <th><?php esc_html_e('User', 'psych-system'); ?></th>
                    <th><?php esc_html_e('Points', 'psych-system'); ?></th>
                    <th><?php esc_html_e('Level', 'psych-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_users as $index => $user_data): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo esc_html($user_data['display_name']); ?></td>
                    <td><?php echo number_format_i18n($user_data['points']); ?></td>
                    <td><?php echo esc_html($user_data['level']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="psych-stats-section">
        <h2><?php esc_html_e('Badge Statistics', 'psych-system'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Badge', 'psych-system'); ?></th>
                    <th><?php esc_html_e('Times Awarded', 'psych-system'); ?></th>
                    <th><?php esc_html_e('User Percentage', 'psych-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($badge_stats as $badge): ?>
                <tr>
                    <td><?php echo esc_html($badge['name']); ?></td>
                    <td><?php echo number_format_i18n($badge['awarded_count']); ?></td>
                    <td><?php echo number_format($badge['percentage'], 1); ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
