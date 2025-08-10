<?php
/**
 * Template for the Settings tab in the Gamification Center admin.
 *
 * @param array $settings The current settings.
 */
if (!defined('ABSPATH')) exit;
?>
<form method="post" action="">
    <?php wp_nonce_field('psych_gamification_settings'); ?>

    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Points for Daily Login', 'psych-system'); ?></th>
            <td><input type="number" name="points_per_login" value="<?php echo esc_attr($settings['points_per_login']); ?>" /></td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Points for New Post', 'psych-system'); ?></th>
            <td><input type="number" name="points_per_post" value="<?php echo esc_attr($settings['points_per_post']); ?>" /></td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Points for New Comment', 'psych-system'); ?></th>
            <td><input type="number" name="points_per_comment" value="<?php echo esc_attr($settings['points_per_comment']); ?>" /></td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Enable Frontend Notifications', 'psych-system'); ?></th>
            <td><input type="checkbox" name="enable_notifications" <?php checked($settings['enable_notifications']); ?> /></td>
        </tr>
        <tr class="heading">
            <th colspan="2"><h3><?php esc_html_e('SMS Settings', 'psych-system'); ?></h3></th>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Enable SMS', 'psych-system'); ?></th>
            <td><input type="checkbox" name="sms_enabled" <?php checked($settings['sms_enabled']); ?> /></td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('SMS API Key', 'psych-system'); ?></th>
            <td><input type="text" name="sms_api_key" value="<?php echo esc_attr($settings['sms_api_key']); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('SMS Sender Number', 'psych-system'); ?></th>
            <td><input type="text" name="sms_sender" value="<?php echo esc_attr($settings['sms_sender']); ?>" class="regular-text" /></td>
        </tr>
    </table>

    <?php submit_button(); ?>
</form>
