<?php
/**
 * Template for the main Gamification Center admin page.
 *
 * @param string $active_tab The currently active tab.
 * @param object $this The instance of the main class.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="wrap psych-admin-wrap">
    <h1><i class="dashicons-before dashicons-star-filled"></i> <?php esc_html_e('Gamification Center', 'psych-system'); ?></h1>
    <p><?php esc_html_e('Manage all aspects of the gamification system, including levels, badges, and user stats.', 'psych-system'); ?></p>

    <nav class="nav-tab-wrapper">
        <a href="?page=<?php echo esc_attr($this->admin_page_slug); ?>&tab=overview" class="nav-tab <?php echo $active_tab == 'overview' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Overview', 'psych-system'); ?></a>
        <a href="?page=<?php echo esc_attr($this->admin_page_slug); ?>&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Settings', 'psych-system'); ?></a>
        <a href="?page=<?php echo esc_attr($this->admin_page_slug); ?>&tab=stats" class="nav-tab <?php echo $active_tab == 'stats' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Statistics', 'psych-system'); ?></a>
    </nav>

    <div class="tab-content">
        <?php
        // The main class will include the correct sub-template based on the active tab.
        switch ($active_tab) {
            case 'settings':
                $this->render_settings_tab();
                break;
            case 'stats':
                $this->render_stats_tab();
                break;
            case 'overview':
            default:
                $this->render_overview_tab();
                break;
        }
        ?>
    </div>
</div>
