<?php
/**
 * Template for the main coach management admin page.
 *
 * @param array $products Array of WooCommerce products.
 * @param array $coaches Array of user objects for coaches.
 * @param int $selected_product_id The currently selected product ID.
 * @param int $selected_coach_id The currently selected coach ID.
 * @param object $student_list_table The instance of the WP_List_Table.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="wrap">
    <h1><i class="dashicons dashicons-groups" style="color:#8E44AD;"></i> <?php esc_html_e('Coach & Student Management', 'psych-system'); ?></h1>

    <div class="psych-admin-header">
        <p><?php esc_html_e('Here you can assign students who have purchased a specific course to the desired coach.', 'psych-system'); ?></p>
        <ol>
            <li><?php esc_html_e('Select a course (WooCommerce product) to display its students.', 'psych-system'); ?></li>
            <li><?php esc_html_e('Select a coach for assignment.', 'psych-system'); ?></li>
            <li><?php esc_html_e('Click "Show Students".', 'psych-system'); ?></li>
        </ol>
    </div>

    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
        <div class="psych-filters">
            <select name="product_id" required>
                <option value=""><?php esc_html_e('-- Select Course/Test --', 'psych-system'); ?></option>
                <?php foreach ($products as $product) : ?>
                    <option value="<?php echo esc_attr($product->get_id()); ?>" <?php selected($selected_product_id, $product->get_id()); ?>>
                        <?php echo esc_html($product->get_name()); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="coach_id" required>
                <option value=""><?php esc_html_e('-- Select Coach --', 'psych-system'); ?></option>
                <?php foreach ($coaches as $coach) : ?>
                    <option value="<?php echo esc_attr($coach->ID); ?>" <?php selected($selected_coach_id, $coach->ID); ?>>
                        <?php echo esc_html($coach->display_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php submit_button(__('Show Students', 'psych-system'), 'primary', 'filter_action', false); ?>
        </div>
    </form>

    <?php if ($selected_product_id && $selected_coach_id && $student_list_table) : ?>
        <form method="post">
             <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
             <input type="hidden" name="product_id" value="<?php echo esc_attr($selected_product_id); ?>">
             <input type="hidden" name="coach_id" value="<?php echo esc_attr($selected_coach_id); ?>">
            <?php
            $student_list_table->prepare_items();
            $student_list_table->search_box(__('Search (Name, Email, Phone)', 'psych-system'), 'student_search');
            $student_list_table->display();
            ?>
        </form>
    <?php endif; ?>
</div>
