<?php
/**
 * Template for the [psych_leaderboard] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param array $leaderboard_data The leaderboard data (ranked users).
 * @param int $current_user_rank The rank of the current user.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>
<div class="psych-leaderboard-container">
    <?php if (!empty($atts['title'])) : ?>
        <h3 class="leaderboard-title"><?php echo esc_html($atts['title']); ?></h3>
    <?php endif; ?>

    <div class="leaderboard-table-wrapper">
        <table class="leaderboard-table">
            <thead>
                <tr>
                    <th class="rank-col"><?php echo esc_html__('Rank', 'psych-g-plugin'); ?></th>
                    <th class="user-col"><?php echo esc_html__('User', 'psych-g-plugin'); ?></th>
                    <th class="points-col"><?php echo esc_html__('Points', 'psych-g-plugin'); ?></th>
                    <th class="level-col"><?php echo esc_html__('Level', 'psych-g-plugin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leaderboard_data as $row) : ?>
                    <tr class="<?php echo $row['is_current_user'] ? 'current-user-row' : ''; ?>">
                        <td class="rank-col"><?php echo esc_html($row['rank']); ?></td>
                        <td class="user-col">
                            <img src="<?php echo esc_url($row['avatar_url']); ?>" class="leaderboard-avatar" alt="<?php echo esc_attr($row['display_name']); ?>" />
                            <span class="leaderboard-username"><?php echo esc_html($row['display_name']); ?></span>
                        </td>
                        <td class="points-col"><?php echo number_format($row['points']); ?></td>
                        <td class="level-col"><?php echo esc_html($row['level_name']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($current_user_rank > $atts['limit']) : ?>
        <div class="current-user-rank-footer">
            <p><?php echo sprintf(esc_html__('Your current rank is %d.', 'psych-g-plugin'), $current_user_rank); ?></p>
        </div>
    <?php endif; ?>
</div>
