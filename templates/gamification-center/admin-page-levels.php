<?php
/**
 * Template for the Levels management admin page.
 *
 * @param array $levels The current levels.
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1><?php esc_html_e('Manage Levels', 'psych-system'); ?></h1>
    <form method="post" action="">
        <?php wp_nonce_field('psych_save_levels'); ?>

        <div id="levels-container">
            <?php foreach ($levels as $index => $level): ?>
            <div class="level-row">
                <input type="text" name="levels[<?php echo $index; ?>][name]" value="<?php echo esc_attr($level['name']); ?>" placeholder="Level Name" />
                <input type="number" name="levels[<?php echo $index; ?>][required_points]" value="<?php echo esc_attr($level['required_points']); ?>" placeholder="Required Points" />
                <input type="text" name="levels[<?php echo $index; ?>][icon]" value="<?php echo esc_attr($level['icon']); ?>" placeholder="Icon Class" />
                <input type="color" name="levels[<?php echo $index; ?>][color]" value="<?php echo esc_attr($level['color']); ?>" />
                <button type="button" class="button remove-level"><?php esc_html_e('Remove', 'psych-system'); ?></button>
            </div>
            <?php endforeach; ?>
        </div>

        <p>
            <button type="button" id="add-level" class="button"><?php esc_html_e('Add Level', 'psych-system'); ?></button>
            <button type="submit" name="save_levels" class="button-primary"><?php esc_html_e('Save Levels', 'psych-system'); ?></button>
        </p>
    </form>
</div>
<script>
jQuery(document).ready(function($) {
    var levelIndex = <?php echo count($levels); ?>;
    $("#add-level").click(function() {
        var html = '<div class="level-row">' +
            '<input type="text" name="levels[' + levelIndex + '][name]" placeholder="Level Name" />' +
            '<input type="number" name="levels[' + levelIndex + '][required_points]" placeholder="Required Points" />' +
            '<input type="text" name="levels[' + levelIndex + '][icon]" placeholder="Icon Class" />' +
            '<input type="color" name="levels[' + levelIndex + '][color]" value="#3498db" />' +
            '<button type="button" class="button remove-level">Remove</button>' +
            '</div>';
        $("#levels-container").append(html);
        levelIndex++;
    });
    $(document).on("click", ".remove-level", function() {
        $(this).closest(".level-row").remove();
    });
});
</script>
<style>
.level-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
.level-row input { flex: 1; }
</style>
