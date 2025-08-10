<?php
/**
 * Template for the Gamification tab in the report card.
 *
 * @param int $user_id The ID of the user.
 * @param array $context The viewing context.
 * @param object $this The instance of the main class.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$badges_data = $this->get_user_badges_data($user_id);
$leaderboard_data = $this->get_leaderboard_data($user_id);
?>
<div class="psych-section">
    <h3><i class="fas fa-trophy"></i> <?php esc_html_e('Badges Earned', 'psych-system'); ?></h3>
    <?php if (!empty($badges_data['earned'])) : ?>
        <div class="psych-badges-list">
            <?php foreach ($badges_data['earned'] as $badge) : ?>
                <div class="psych-badge-item" title="<?php echo esc_attr($badge['description']); ?>">
                    <i class="<?php echo esc_attr($badge['icon']); ?>" style="color: <?php echo esc_attr($badge['color']); ?>"></i>
                    <p class="badge-name"><?php echo esc_html($badge['name']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p><?php esc_html_e('No badges earned yet.', 'psych-system'); ?></p>
    <?php endif; ?>
</div>

<div class="psych-section">
    <h3><i class="fas fa-list-ol"></i> <?php esc_html_e('Leaderboard Ranking', 'psych-system'); ?></h3>
    <div class="psych-user-rank">
        <p>
            <?php printf(
                esc_html__('Your Rank: %s of %s users', 'psych-system'),
                '<strong>' . number_format_i18n($leaderboard_data['user_rank']) . '</strong>',
                number_format_i18n($leaderboard_data['total_users'])
            ); ?>
        </p>
    </div>
    <table class="psych-data-table">
        <thead>
            <tr>
                <th><?php esc_html_e('Rank', 'psych-system'); ?></th>
                <th><?php esc_html_e('User', 'psych-system'); ?></th>
                <th><?php esc_html_e('Points', 'psych-system'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leaderboard_data['top_users'] as $index => $user_data): ?>
                <tr class="<?php echo $user_data['ID'] == $user_id ? 'current-user' : ''; ?>">
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo esc_html($user_data['display_name']); ?></td>
                    <td><?php echo number_format_i18n($user_data['points']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
