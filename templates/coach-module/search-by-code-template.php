<?php
/**
 * Template for the [coach_search_by_code] shortcode.
 *
 * @param string $error An error message to display, if any.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="psych-form-container coach-search-form">
     <h4><i class="fas fa-search"></i> <?php esc_html_e('Search User by Unique Code', 'psych-system'); ?></h4>
     <form method="post">
        <?php wp_nonce_field('coach_search_by_code', 'product_code_search_nonce'); ?>
        <label for="product_code"><?php esc_html_e('Enter the user\'s unique code:', 'psych-system'); ?></label>
        <input type="text" name="product_code" id="product_code" class="ltr-input" required>
        <button type="submit" class="button button-primary"><?php esc_html_e('Search and View', 'psych-system'); ?></button>
        <?php if (!empty($error)) : ?>
            <p class="psych-form-error"><?php echo esc_html($error); ?></p>
        <?php endif; ?>
     </form>
</div>
