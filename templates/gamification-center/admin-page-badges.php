<?php
/**
 * Template for the Badges management admin page.
 *
 * @param array $badges The current badges.
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1><?php esc_html_e('Manage Badges', 'psych-system'); ?></h1>
    <form method="post" action="">
        <?php wp_nonce_field('psych_save_badges'); ?>

        <div id="badges-container">
            <?php foreach ($badges as $slug => $badge): ?>
            <div class="badge-row">
                <input type="text" value="<?php echo esc_attr($slug); ?>" readonly />
                <input type="text" name="badges[<?php echo esc_attr($slug); ?>][name]" value="<?php echo esc_attr($badge['name']); ?>" placeholder="Badge Name" />
                <textarea name="badges[<?php echo esc_attr($slug); ?>][description]" placeholder="Description"><?php echo esc_textarea($badge['description'] ?? ''); ?></textarea>
                <input type="text" name="badges[<?php echo esc_attr($slug); ?>][icon]" value="<?php echo esc_attr($badge['icon']); ?>" placeholder="Icon Class" />
                <input type="color" name="badges[<?php echo esc_attr($slug); ?>][color]" value="<?php echo esc_attr($badge['color']); ?>" />
                <button type="button" class="button remove-badge"><?php esc_html_e('Remove', 'psych-system'); ?></button>
            </div>
            <?php endforeach; ?>
        </div>

        <p>
            <button type="button" id="add-badge" class="button"><?php esc_html_e('Add Badge', 'psych-system'); ?></button>
            <button type="submit" name="save_badges" class="button-primary"><?php esc_html_e('Save Badges', 'psych-system'); ?></button>
        </p>
    </form>
</div>
<script>
jQuery(document).ready(function($) {
    $("#add-badge").click(function() {
        var slug = prompt("Enter badge slug (e.g., first_quest):");
        if (!slug) return;
        slug = slug.toLowerCase().replace(/[^a-z0-9_]/g, '_');
        var html = '<div class="badge-row">' +
            '<input type="text" value="' + slug + '" readonly />' +
            '<input type="text" name="badges[' + slug + '][name]" placeholder="Badge Name" />' +
            '<textarea name="badges[' + slug + '][description]" placeholder="Description"></textarea>' +
            '<input type="text" name="badges[' + slug + '][icon]" placeholder="Icon Class" value="fa-trophy" />' +
            '<input type="color" name="badges[' + slug + '][color]" value="#FFD700" />' +
            '<button type="button" class="button remove-badge">Remove</button>' +
            '</div>';
        $("#badges-container").append(html);
    });
    $(document).on("click", ".remove-badge", function() {
        if (confirm("Are you sure?")) {
            $(this).closest(".badge-row").remove();
        }
    });
});
</script>
<style>
.badge-row { display: grid; grid-template-columns: 1fr 1fr 2fr 1fr auto auto; gap: 10px; margin-bottom: 10px; align-items: center; }
</style>
