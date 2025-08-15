<?php
/**
 * Plugin Name:       Myth Buster Interactive Module
 * Description:       A highly configurable interactive "Myth or Fact" game module for WordPress. Use the shortcode [myth_buster_game set="default" layout="post"] to display.
 * Version:           1.3.0
 * Author:            Jules
 * Text Domain:       myth-buster-module
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// The main plugin class
if (!class_exists('Myth_Buster_Module')) {
    final class Myth_Buster_Module {

        private static $instance = null;
        private $assets_injected = false;
        private $shortcode_atts = [];

        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct() {
            // Create asset files on activation if they don't exist
            register_activation_hook(__FILE__, [$this, 'create_asset_files']);
            $this->add_hooks();
        }

        private function add_hooks() {
            add_action('init', [$this, 'register_shortcodes']);
            add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
            add_action('admin_menu', [$this, 'add_admin_menu']);
            add_action('admin_init', [$this, 'register_settings']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        }

        public function register_shortcodes() {
            add_shortcode('myth_buster_game', [$this, 'render_shortcode']);
        }

        /**
         * Renders the main game container.
         * Shortcode attributes:
         * @param 'set' - The name of the belief set to use.
         * @param 'layout' - 'default', 'post' (1:1), 'story' (9:16).
         */
        public function render_shortcode($atts) {
            $this->shortcode_atts = shortcode_atts([
                'set' => 'default',
                'layout' => 'default'
            ], $atts);
            $this->assets_injected = true;

            $options = get_option('myth_buster_options', []);
            $avatar_url = $options['general']['avatar_url'] ?? '';
            $layout_class = 'layout-' . sanitize_key($this->shortcode_atts['layout']);

            ob_start();
            ?>
            <div id="myth-buster-container" class="myth-buster-game-wrapper <?php echo esc_attr($layout_class); ?>">
                <div id="body-container" class="text-gray-200 flex items-center justify-center min-h-screen p-4 transition-all duration-500">
                    <div id="app-container" class="w-full h-full bg-gray-800/80 backdrop-blur-sm shadow-2xl text-center transition-all duration-300 relative z-20 flex flex-col justify-center p-6 sm:p-8">
                        <?php if ($avatar_url): ?>
                            <img src="<?php echo esc_url($avatar_url); ?>" class="custom-avatar" alt="Avatar">
                        <?php endif; ?>
                        <div id="main-content">
                            <div id="question-section">
                                <h1 class="text-3xl font-bold text-indigo-400 mb-4"><?php _e('Myth or Fact?', 'myth-buster-module'); ?></h1>
                                <p id="myth-text" class="text-xl font-semibold leading-relaxed my-6 h-24 flex items-center justify-center"></p>
                                <p class="text-sm text-gray-400 mb-6"><?php _e('Do you think this belief is true or false?', 'myth-buster-module'); ?></p>
                            </div>
                            <div id="timer-section" class="mb-6">
                                <div class="relative w-40 h-40 mx-auto mb-4">
                                    <svg class="w-full h-full" viewBox="0 0 100 100"><circle class="text-gray-700/50" stroke-width="8" stroke="currentColor" fill="transparent" r="45" cx="50" cy="50"/><circle id="timer-circle" class="timer-circle-progress text-indigo-500" stroke-width="8" stroke-dasharray="282.6" stroke-dashoffset="0" stroke="currentColor" fill="transparent" r="45" cx="50" cy="50"/></svg>
                                    <div id="countdown-text" class="absolute inset-0 flex items-center justify-center text-5xl font-black"></div>
                                </div>
                                <div id="buttons-container" class="flex justify-center gap-4">
                                    <button onclick="recordGuess('true')" class="px-8 py-3 bg-green-600 text-white font-bold rounded-lg shadow-lg hover:bg-green-700 transition-transform transform hover:scale-105"><?php _e('True', 'myth-buster-module'); ?></button>
                                    <button onclick="recordGuess('false')" class="px-8 py-3 bg-red-600 text-white font-bold rounded-lg shadow-lg hover:bg-red-700 transition-transform transform hover:scale-105"><?php _e('False', 'myth-buster-module'); ?></button>
                                </div>
                            </div>
                             <div id="answer-section" class="hidden text-center">
                                <h2 id="answer-title" class="text-3xl font-black mb-4"></h2>
                                <p id="answer-explanation" class="text-lg leading-relaxed"></p>
                                <button onclick="resetApp()" class="mt-8 px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition-all"><?php _e('Next Belief', 'myth-buster-module'); ?></button>
                            </div>
                        </div>
                    </div>
                    <div id="particles-container"></div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        public function enqueue_assets() {
            if (!$this->assets_injected) return;

            $options = get_option('myth_buster_options', []);
            $set_key = $this->shortcode_atts['set'];

            $myths_to_load = [];
            if (!empty($options['belief_sets'])) {
                $active_set = null;
                foreach ($options['belief_sets'] as $set) {
                    if ($set['name'] === $set_key) {
                        $active_set = $set;
                        break;
                    }
                }
                if (!$active_set) $active_set = reset($options['belief_sets']);
                $myths_to_load = $active_set['myths'] ?? [];
            }

            $css = "
            :root { --aspect-ratio: 16/9; }
            .layout-post { --aspect-ratio: 1/1; }
            .layout-story { --aspect-ratio: 9/16; }
            #myth-buster-container { aspect-ratio: var(--aspect-ratio); max-width: 100%; margin: auto; }
            #myth-buster-container #body-container { font-family: 'Vazirmatn', sans-serif; touch-action: manipulation; overflow: hidden; background-size: cover; background-position: center center; width: 100%; height: 100%; }
            #myth-buster-container .hidden { display: none !important; }
            #myth-buster-container .theme-fire-and-ice { background-color: #0c3483; }
            #myth-buster-container .theme-nature { background-color: #228b22; }
            #myth-buster-container .theme-space { background-color: #000000; }
            #myth-buster-container .timer-circle-progress { stroke-linecap: round; transform-origin: 50% 50%; transform: rotate(-90deg); transition: stroke-dashoffset 1s linear; }
            #myth-buster-container #particles-container, #myth-buster-container .custom-avatar { position: absolute; pointer-events: none; }
            #myth-buster-container .custom-avatar { bottom: 15px; right: 15px; width: 80px; height: 80px; border-radius: 50%; border: 3px solid white; z-index: 30; }
            #myth-buster-container .particle { position: absolute; background-color: #fff; border-radius: 50%; opacity: 0; animation: fly-out 2s ease-out forwards; z-index: 1; }
            @keyframes fly-out { 0% { transform: translate(0, 0) scale(1); opacity: 1; } 100% { transform: translate(var(--x), var(--y)) scale(0); opacity: 0; } }
            ";
            wp_register_style('myth-buster-inline-css', false);
            wp_enqueue_style('myth-buster-inline-css');
            wp_add_inline_style('myth-buster-inline-css', $css);

            wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com');
            wp_enqueue_style('vazirmatn-font', 'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700;900&display=swap');

            wp_register_script('myth-buster-main-js', plugin_dir_url(__FILE__) . 'assets/main.js', [], '1.3.0', true);

            $localized_data = [
                'myths' => $myths_to_load,
                'settings' => $options['general'] ?? [],
                'nonce' => wp_create_nonce('myth_buster_nonce'),
                'text' => [
                    'fact' => __('Fact!', 'myth-buster-module'),
                    'myth' => __('Myth!', 'myth-buster-module'),
                    'add_beliefs_prompt' => __('Please add some beliefs in the admin panel.', 'myth-buster-module'),
                ]
            ];
            wp_localize_script('myth-buster-main-js', 'myth_buster_data', $localized_data);
            wp_enqueue_script('myth-buster-main-js');
        }

        public function add_admin_menu() {
            add_menu_page('Myth Buster Settings', 'Myth Buster', 'manage_options', 'myth-buster-settings', [$this, 'render_admin_page'],'dashicons-forms', 25);
        }

        public function register_settings() {
            register_setting('myth_buster_options_group', 'myth_buster_options', [$this, 'sanitize_options']);
        }

        public function sanitize_options($input) {
            $sanitized_input = [];
            if (isset($input['belief_sets']) && is_array($input['belief_sets'])) {
                foreach ($input['belief_sets'] as $set) {
                    if (empty($set['name'])) continue;
                    $sanitized_set = ['name' => sanitize_text_field($set['name']), 'myths' => []];
                    if (isset($set['myths']) && is_array($set['myths'])) {
                        foreach ($set['myths'] as $myth) {
                            $sanitized_myth = [
                                'myth' => sanitize_textarea_field($myth['myth']), 'explanation' => sanitize_textarea_field($myth['explanation']),
                                'duration' => absint($myth['duration']), 'isFact' => isset($myth['isFact']) ? 'true' : 'false',
                                'image' => esc_url_raw($myth['image'] ?? '')
                            ];
                            $sanitized_set['myths'][] = $sanitized_myth;
                        }
                    }
                    $sanitized_input['belief_sets'][] = $sanitized_set;
                }
            }
            if (isset($input['general'])) {
                $general = $input['general'];
                $sanitized_input['general']['theme'] = sanitize_text_field($general['theme']);
                $sanitized_input['general']['timer_style'] = sanitize_text_field($general['timer_style']);
                $sanitized_input['general']['avatar_url'] = esc_url_raw($general['avatar_url']);
                $sanitized_input['general']['sound_correct'] = esc_url_raw($general['sound_correct']);
                $sanitized_input['general']['sound_incorrect'] = esc_url_raw($general['sound_incorrect']);
                $sanitized_input['general']['sound_tick'] = esc_url_raw($general['sound_tick']);
                $sanitized_input['general']['sound_bg'] = esc_url_raw($general['sound_bg']);
            }
            return $sanitized_input;
        }

        public function enqueue_admin_assets($hook) {
            if ($hook !== 'toplevel_page_myth-buster-settings') return;
            wp_enqueue_media();
            wp_enqueue_style('myth-buster-admin-css', plugin_dir_url(__FILE__) . 'assets/admin.css', [], '1.3.0');
            wp_enqueue_script('myth-buster-admin-js', plugin_dir_url(__FILE__) . 'assets/admin.js', ['jquery', 'jquery-ui-sortable'], '1.3.0', true);
        }

        public function render_admin_page() {
            $options = get_option('myth_buster_options', [
                'belief_sets' => [['name' => 'default', 'myths' => []]],
                'general' => ['theme' => 'theme-fire-and-ice', 'timer_style' => 'circle', 'avatar_url' => '', 'sound_correct' => '', 'sound_incorrect' => '', 'sound_tick' => '', 'sound_bg' => '']
            ]);
            $general = $options['general'];
            ?>
            <div class="wrap myth-buster-admin-wrapper">
                <h1><?php _e('Myth Buster Settings', 'myth-buster-module'); ?></h1>
                <h2 class="nav-tab-wrapper"><a href="#tab-sets" class="nav-tab nav-tab-active">Belief Sets</a><a href="#tab-settings" class="nav-tab">General Settings</a></h2>
                <form method="post" action="options.php">
                    <?php settings_fields('myth_buster_options_group'); ?>
                    <div id="tab-sets" class="tab-content active">
                        <h3><?php _e('Belief Sets', 'myth-buster-module'); ?></h3>
                        <div id="belief-sets-container">
                            <?php if (!empty($options['belief_sets'])) : foreach ($options['belief_sets'] as $s_idx => $set) : ?>
                            <div class="myth-set">
                                <h3 class="handle">Set Name: <input type="text" name="myth_buster_options[belief_sets][<?php echo $s_idx; ?>][name]" value="<?php echo esc_attr($set['name']); ?>"> <button type="button" class="button remove-set">Remove Set</button></h3>
                                <div class="myths-container">
                                    <?php if (!empty($set['myths'])) : foreach ($set['myths'] as $m_idx => $myth) : ?>
                                    <div class="myth-item">
                                        <p><label>Myth/Fact Text:<br><textarea name="myth_buster_options[belief_sets][<?php echo $s_idx; ?>][myths][<?php echo $m_idx; ?>][myth]"><?php echo esc_textarea($myth['myth']); ?></textarea></label></p>
                                        <p><label><input type="checkbox" name="myth_buster_options[belief_sets][<?php echo $s_idx; ?>][myths][<?php echo $m_idx; ?>][isFact]" value="true" <?php checked($myth['isFact'], 'true'); ?>> Is this a fact (true)?</label></p>
                                        <p><label>Timer (sec): <input type="number" name="myth_buster_options[belief_sets][<?php echo $s_idx; ?>][myths][<?php echo $m_idx; ?>][duration]" value="<?php echo esc_attr($myth['duration']); ?>"></label></p>
                                        <p><label>Explanation:<br><textarea name="myth_buster_options[belief_sets][<?php echo $s_idx; ?>][myths][<?php echo $m_idx; ?>][explanation]"><?php echo esc_textarea($myth['explanation']); ?></textarea></label></p>
                                        <p><label>Background Image:<br><input type="text" class="image-url" name="myth_buster_options[belief_sets][<?php echo $s_idx; ?>][myths][<?php echo $m_idx; ?>][image]" value="<?php echo esc_attr($myth['image'] ?? ''); ?>"><button type="button" class="button upload-image">Upload</button></label></p>
                                        <button type="button" class="button remove-myth">Remove Myth</button>
                                    </div>
                                    <?php endforeach; endif; ?>
                                </div>
                                <button type="button" class="button add-myth">Add New Myth</button>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <button type="button" class="button" id="add-set">Add New Belief Set</button>
                    </div>
                    <div id="tab-settings" class="tab-content" style="display:none;">
                        <h3><?php _e('General Settings', 'myth-buster-module'); ?></h3>
                        <table class="form-table">
                            <tr><th scope="row"><label for="theme">Theme</label></th><td><select name="myth_buster_options[general][theme]"><option value="theme-fire-and-ice" <?php selected($general['theme'], 'theme-fire-and-ice'); ?>>Fire & Ice</option><option value="theme-nature" <?php selected($general['theme'], 'theme-nature'); ?>>Nature</option><option value="theme-space" <?php selected($general['theme'], 'theme-space'); ?>>Space</option></select></td></tr>
                            <tr><th scope="row"><label for="timer_style">Timer Style</label></th><td><select name="myth_buster_options[general][timer_style]"><option value="circle" <?php selected($general['timer_style'], 'circle'); ?>>Circle</option><option value="line" <?php selected($general['timer_style'], 'line'); ?>>Line</option></select></td></tr>
                            <tr><th scope="row"><label>Custom Avatar URL</label></th><td><input type="text" name="myth_buster_options[general][avatar_url]" value="<?php echo esc_attr($general['avatar_url']); ?>" class="regular-text image-url"><button type="button" class="button upload-image">Upload</button></td></tr>
                            <tr><th scope="row"><label>Sound - Correct</label></th><td><input type="text" name="myth_buster_options[general][sound_correct]" value="<?php echo esc_attr($general['sound_correct']); ?>" class="regular-text"></td></tr>
                            <tr><th scope="row"><label>Sound - Incorrect</label></th><td><input type="text" name="myth_buster_options[general][sound_incorrect]" value="<?php echo esc_attr($general['sound_incorrect']); ?>" class="regular-text"></td></tr>
                            <tr><th scope="row"><label>Sound - Timer Tick</label></th><td><input type="text" name="myth_buster_options[general][sound_tick]" value="<?php echo esc_attr($general['sound_tick']); ?>" class="regular-text"></td></tr>
                            <tr><th scope="row"><label>Sound - Background</label></th><td><input type="text" name="myth_buster_options[general][sound_bg]" value="<?php echo esc_attr($general['sound_bg']); ?>" class="regular-text"></td></tr>
                        </table>
                    </div>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }

        public function create_asset_files() {
            $assets_dir = plugin_dir_path(__FILE__) . 'assets';
            if (!file_exists($assets_dir)) {
                mkdir($assets_dir, 0755);
            }
            if (!file_exists($assets_dir . '/main.js')) {
                file_put_contents($assets_dir . '/main.js', '/* Main frontend javascript */');
            }
            if (!file_exists($assets_dir . '/admin.js')) {
                file_put_contents($assets_dir . '/admin.js', '/* Admin javascript */');
            }
            if (!file_exists($assets_dir . '/admin.css')) {
                file_put_contents($assets_dir . '/admin.css', '/* Admin css */');
            }
        }
    }

    function myth_buster_module_init() {
        Myth_Buster_Module::get_instance();
    }
    add_action('plugins_loaded', 'myth_buster_module_init');
}
