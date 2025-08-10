<?php
/**
 * Template for the Analytics tab in the report card.
 *
 * @param int $user_id The ID of the user.
 * @param array $context The viewing context.
 * @param object $this The instance of the main class.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$analytics_data = $this->get_user_analytics_data($user_id);
?>
<div class="psych-section">
    <h3><i class="fas fa-chart-line"></i> <?php esc_html_e('Progress Over Time', 'psych-system'); ?></h3>
    <div class="psych-chart-container">
        <canvas id="psych-progress-chart" data-chart-data='<?php echo esc_attr(json_encode($analytics_data['progress_chart'])); ?>'></canvas>
    </div>
</div>

<div class="psych-section">
    <h3><i class="fas fa-clipboard-list"></i> <?php esc_html_e('Test Results', 'psych-system'); ?></h3>
    <?php if (!empty($analytics_data['test_results'])) : ?>
        <table class="psych-data-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Test Title', 'psych-system'); ?></th>
                    <th><?php esc_html_e('Date', 'psych-system'); ?></th>
                    <th><?php esc_html_e('Score', 'psych-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($analytics_data['test_results'] as $result) : ?>
                    <tr>
                        <td><?php echo esc_html($result['title']); ?></td>
                        <td><?php echo esc_html(date_i18n('Y/m/d', strtotime($result['date']))); ?></td>
                        <td><?php echo esc_html($result['score']); ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p><?php esc_html_e('No test results found.', 'psych-system'); ?></p>
    <?php endif; ?>
</div>
