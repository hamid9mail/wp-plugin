<?php
/**
 * Template for the Manual Award admin page.
 *
 * @param array $all_badges All available badges.
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1><?php esc_html_e('Manual Award', 'psych-system'); ?></h1>
    <div class="manual-award-container">
        <form id="manual-award-form">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="award_user_id"><?php esc_html_e('User ID', 'psych-system'); ?></label></th>
                    <td><input type="number" id="award_user_id" name="user_id" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="award_type"><?php esc_html_e('Award Type', 'psych-system'); ?></label></th>
                    <td>
                        <select id="award_type" name="award_type">
                            <option value="points"><?php esc_html_e('Points', 'psych-system'); ?></option>
                            <option value="badge"><?php esc_html_e('Badge', 'psych-system'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="award_value"><?php esc_html_e('Value', 'psych-system'); ?></label></th>
                    <td>
                        <input type="text" id="award_value" name="award_value" class="regular-text" required />
                        <p class="description"><?php esc_html_e('For points, enter a number. For a badge, enter the badge slug.', 'psych-system'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="award_reason"><?php esc_html_e('Reason', 'psych-system'); ?></label></th>
                    <td><input type="text" id="award_reason" name="reason" class="regular-text" placeholder="<?php esc_attr_e('e.g., exceptional participation', 'psych-system'); ?>" /></td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button-primary"><?php esc_html_e('Grant Award', 'psych-system'); ?></button>
            </p>
        </form>
    </div>

    <div class="badges-reference">
        <h3><?php esc_html_e('Available Badge Slugs', 'psych-system'); ?></h3>
        <ul>
            <?php foreach ($all_badges as $slug => $badge): ?>
                <li><strong><?php echo esc_html($badge['name']); ?>:</strong> <code><?php echo esc_html($slug); ?></code></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
