<?php
/**
 * Main template for the [psych_user_report_card] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param array $user_data The user's complete data.
 * @param array $tabs The tabs to be displayed.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$instance_id = 'report-card-' . uniqid();
?>

<div class="psych-report-card-container" id="<?php echo esc_attr($instance_id); ?>">
    <div class="report-card-tabs" data-default-tab="<?php echo esc_attr($instance_id . '-' . $atts['default_tab']); ?>">
        <?php foreach ($tabs as $tab_slug) : ?>
            <button class="tab-link" data-tab="<?php echo esc_attr($instance_id . '-' . $tab_slug); ?>">
                <?php echo esc_html(ucfirst($tab_slug)); // Simple capitalization, can be improved with a lookup array ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="report-card-content">
        <?php foreach ($tabs as $tab_slug) : ?>
            <div class="tab-pane" id="<?php echo esc_attr($instance_id . '-' . $tab_slug); ?>">
                <?php
                // Include a sub-template for each tab pane for better organization
                $template_path = plugin_dir_path(__FILE__) . 'tabs/' . $tab_slug . '-tab.php';
                if (file_exists($template_path)) {
                    // This is a more advanced approach, let's stick to inline for now to avoid creating too many files
                    // include($template_path);
                }

                // For simplicity in this refactoring step, we'll keep the HTML here.
                // A further improvement would be to move each tab's content to its own file.
                switch ($tab_slug) {
                    case 'summary':
                        ?>
                        <h3><?php esc_html_e('My Summary', 'psych-g-plugin'); ?></h3>
                        <div class="summary-grid">
                            <div class="summary-item"><span class="value"><?php echo number_format($user_data['summary']['points']); ?></span><span class="label"><?php esc_html_e('Points', 'psych-g-plugin'); ?></span></div>
                            <div class="summary-item"><span class="value"><?php echo esc_html($user_data['summary']['level']); ?></span><span class="label"><?php esc_html_e('Level', 'psych-g-plugin'); ?></span></div>
                            <div class="summary-item"><span class="value"><?php echo esc_html($user_data['summary']['courses_completed']); ?></span><span class="label"><?php esc_html_e('Courses Completed', 'psych-g-plugin'); ?></span></div>
                            <div class="summary-item"><span class="value"><?php echo esc_html($user_data['summary']['badges_earned']); ?></span><span class="label"><?php esc_html_e('Badges Earned', 'psych-g-plugin'); ?></span></div>
                        </div>
                        <?php
                        break;

                    case 'courses':
                        ?>
                        <h3><?php esc_html_e('My Courses', 'psych-g-plugin'); ?></h3>
                        <table class="report-table">
                            <thead><tr><th><?php esc_html_e('Course', 'psych-g-plugin'); ?></th><th><?php esc_html_e('Progress', 'psych-g-plugin'); ?></th><th><?php esc_html_e('Status', 'psych-g-plugin'); ?></th></tr></thead>
                            <tbody>
                            <?php foreach ($user_data['courses'] as $course): ?>
                                <tr>
                                    <td><?php echo esc_html($course['title']); ?></td>
                                    <td>
                                        <div class="progress-bar-container"><div class="progress-bar" style="width: <?php echo esc_attr($course['progress']); ?>%;"></div></div>
                                        <?php echo esc_html($course['progress']); ?>%
                                    </td>
                                    <td><?php echo $course['completed_date'] ? esc_html__('Completed', 'psych-g-plugin') : esc_html__('In Progress', 'psych-g-plugin'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php
                        break;

                    case 'badges':
                         ?>
                        <h3><?php esc_html_e('My Badges', 'psych-g-plugin'); ?></h3>
                        <table class="report-table">
                            <thead><tr><th><?php esc_html_e('Badge', 'psych-g-plugin'); ?></th><th><?php esc_html_e('Date Earned', 'psych-g-plugin'); ?></th></tr></thead>
                            <tbody>
                            <?php foreach ($user_data['badges'] as $badge): ?>
                                <tr>
                                    <td><img src="<?php echo esc_url($badge['icon_url']); ?>" class="badge-icon" alt=""><?php echo esc_html($badge['name']); ?></td>
                                    <td><?php echo esc_html($badge['date_earned']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php
                        break;

                    case 'tests':
                        ?>
                        <h3><?php esc_html_e('My Tests', 'psych-g-plugin'); ?></h3>
                        <table class="report-table">
                            <thead><tr><th><?php esc_html_e('Test Name', 'psych-g-plugin'); ?></th><th><?php esc_html_e('Result/Score', 'psych-g-plugin'); ?></th><th><?php esc_html_e('Date Taken', 'psych-g-plugin'); ?></th></tr></thead>
                            <tbody>
                            <?php foreach ($user_data['tests'] as $test): ?>
                                <tr>
                                    <td><?php echo esc_html($test['name']); ?></td>
                                    <td><?php echo esc_html($test['score']); ?></td>
                                    <td><?php echo esc_html($test['date_taken']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php
                        break;

                    case 'timeline':
                        ?>
                        <h3><?php esc_html_e('My Timeline', 'psych-g-plugin'); ?></h3>
                        <ul class="timeline">
                            <?php foreach ($user_data['timeline'] as $item): ?>
                                <li class="timeline-item">
                                    <div class="timeline-date"><?php echo esc_html($item['date']); ?></div>
                                    <div class="timeline-event"><?php echo esc_html($item['event']); ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php
                        break;
                }
                ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
