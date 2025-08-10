<?php
/**
 * Template for the [user_product_codes] shortcode.
 *
 * @param array $codes Array of product codes for the current user.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (empty($codes)) {
    echo '<p>' . esc_html__('You have not received any unique codes for any products yet.', 'psych-system') . '</p>';
    return;
}
?>
<div class="user-codes-table-wrapper">
    <h4><?php esc_html_e('Your Unique Codes', 'psych-system'); ?></h4>
    <table class="user-codes-table">
        <thead>
            <tr>
                <th><?php esc_html_e('Product/Test Name', 'psych-system'); ?></th>
                <th><?php esc_html_e('Your Unique Code', 'psych-system'); ?></th>
                <th><?php esc_html_e('Action', 'psych-system'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($codes as $product_id => $code) :
            $product_title = get_the_title($product_id);
            if (empty($product_title)) continue;
        ?>
        <tr>
            <td><?php echo esc_html($product_title); ?></td>
            <td><code id="user-code-<?php echo esc_attr($product_id); ?>"><?php echo esc_html($code); ?></code></td>
            <td><button class="copy-code-btn" data-target="#user-code-<?php echo esc_attr($product_id); ?>"><?php esc_html_e('Copy', 'psych-system'); ?></button></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
