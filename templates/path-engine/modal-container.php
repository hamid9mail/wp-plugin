<?php
/**
 * Template for the modal container, typically added to the footer.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<div class="psych-path-modal-overlay" id="psych-path-modal-overlay" style="display: none;">
    <div class="psych-path-modal-container">
        <button class="psych-path-modal-close" aria-label="<?php esc_attr_e('Close', 'psych-path-engine'); ?>">&times;</button>
        <div id="psych-path-modal-content-inner">
            <!-- Content will be loaded here via JavaScript -->
        </div>
    </div>
</div>
