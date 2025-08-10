<?php
/**
 * Template for the [psych_user_badges] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @param array $user_badges Array of badge slugs the user has earned.
 * @param array $all_badges Array of all available badges.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (empty($user_badges)) {
    echo '<p class="psych-no-badges">' . esc_html__('No badges earned yet.', 'psych-system') . '</p>';
    return;
}

$limit = intval($atts['limit']);
$count = 0;
?>
<div class="psych-user-badges-list">
    <?php foreach ($user_badges as $badge_slug) :
        if ($limit > 0 && $count >= $limit) break;
        if (isset($all_badges[$badge_slug])) :
            $badge = $all_badges[$badge_slug];
            $count++;
    ?>
            <span class="psych-badge" style="color: <?php echo esc_attr($badge['color']); ?>;" title="<?php echo esc_attr($badge['description'] ?? ''); ?>">
                <i class="<?php echo esc_attr($badge['icon']); ?>"></i> <?php echo esc_html($badge['name']); ?>
            </span>
    <?php
        endif;
    endforeach;
    ?>
</div>
