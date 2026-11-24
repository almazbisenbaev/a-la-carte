<?php
/**
 * Gutenberg Disabler functionality
 *
 * @package AlmazBroSiteUtilities
 */

namespace AlmazBroSiteUtilities\Gutenberg;

use AlmazBroSiteUtilities\Admin\Tool_Manager;
use AlmazBroSiteUtilities\Admin\UI;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Gutenberg_Disabler
 */
class Gutenberg_Disabler {
    /**
     * Option name for storing Gutenberg disabler settings
     *
     * @var string
     */
    private const OPTION_NAME = 'almazbro_site_utilities_gutenberg_settings';

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('init', array($this, 'maybe_disable_gutenberg'));
    }

    /**
     * Get settings
     */
    private function get_settings() {
        $defaults = array(
            'enabled'    => false,
            'mode'       => 'all',
            'post_types' => array()
        );

        $settings = get_option(self::OPTION_NAME, array());
        if (!is_array($settings)) {
            $settings = array();
        }

        // Merge rather than return the stored value directly, so a partial or
        // hand-edited option can never leave a key the render/filter code reads.
        $settings = array_merge($defaults, $settings);
        $settings['post_types'] = (array) $settings['post_types'];

        return $settings;
    }

    /**
     * Render the Gutenberg disabler settings page
     */
    public function render_settings_page() {
        Tool_Manager::require_capability();

        // Handle form submission
        if (isset($_POST['save']) && check_admin_referer('almazbro_site_utilities_gutenberg_disabler')) {
            $this->save_settings();
        }

        $settings   = $this->get_settings();
        $post_types = get_post_types(array('public' => true), 'objects');

        $modes = array(
            'all' => array(
                'title'       => __('Disable Globally', 'almazbro-site-utilities'),
                'description' => __('Completely disable the block editor for all post types and widgets.', 'almazbro-site-utilities'),
                'checked'     => $settings['enabled'] && $settings['mode'] === 'all',
            ),
            'selective' => array(
                'title'       => __('Disable Selectively', 'almazbro-site-utilities'),
                'description' => __('Choose specific post types where you want to use the Classic Editor.', 'almazbro-site-utilities'),
                'checked'     => $settings['enabled'] && $settings['mode'] === 'selective',
            ),
            'none' => array(
                'title'       => __('Keep Gutenberg Enabled', 'almazbro-site-utilities'),
                'description' => __('Do not disable the block editor.', 'almazbro-site-utilities'),
                'checked'     => !$settings['enabled'],
            ),
        );

        UI::page_open(array(
            'tool_id'     => 'gutenberg_disabler',
            'title'       => __('Gutenberg Disabler', 'almazbro-site-utilities'),
            'description' => __('Switch back to the Classic Editor and disable Gutenberg block library assets.', 'almazbro-site-utilities'),
        ));
        ?>
        <form class="almazbro-site-utilities-panel" method="post">
            <?php wp_nonce_field('almazbro_site_utilities_gutenberg_disabler'); ?>

            <div class="almazbro-site-utilities-panel__header">
                <h2 class="almazbro-site-utilities-h2"><?php esc_html_e('Settings', 'almazbro-site-utilities'); ?></h2>
            </div>

            <div class="almazbro-site-utilities-panel__body">
                <div class="almazbro-site-utilities-panel__section">
                    <?php foreach ($modes as $mode => $option) : ?>
                        <label class="almazbro-site-utilities-choice almazbro-site-utilities-choice--card">
                            <input type="radio"
                                   name="gutenberg_mode"
                                   value="<?php echo esc_attr($mode); ?>"
                                   <?php echo 'selective' === $mode ? 'data-almazbro-site-utilities-reveal="almazbro-site-utilities-gutenberg-post-types"' : ''; ?>
                                   <?php checked($option['checked']); ?> />
                            <span class="almazbro-site-utilities-choice__text">
                                <span class="almazbro-site-utilities-choice__title"><?php echo esc_html($option['title']); ?></span>
                                <span class="almazbro-site-utilities-choice__desc"><?php echo esc_html($option['description']); ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="almazbro-site-utilities-panel__section" id="almazbro-site-utilities-gutenberg-post-types" <?php echo $modes['selective']['checked'] ? '' : 'hidden'; ?>>
                    <p class="almazbro-site-utilities-eyebrow"><?php esc_html_e('Post types using the Classic Editor', 'almazbro-site-utilities'); ?></p>
                    <div class="almazbro-site-utilities-choice-grid">
                        <?php foreach ($post_types as $post_type) : ?>
                            <label class="almazbro-site-utilities-choice almazbro-site-utilities-choice--boxed">
                                <input type="checkbox"
                                       name="post_types[]"
                                       value="<?php echo esc_attr($post_type->name); ?>"
                                       <?php checked(in_array($post_type->name, $settings['post_types'], true)); ?> />
                                <span class="almazbro-site-utilities-choice__text">
                                    <span class="almazbro-site-utilities-choice__title"><?php echo esc_html($post_type->label); ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="almazbro-site-utilities-panel__footer">
                <button type="submit" name="save" value="1" class="almazbro-site-utilities-btn almazbro-site-utilities-btn--primary">
                    <?php esc_html_e('Save Settings', 'almazbro-site-utilities'); ?>
                </button>
            </div>
        </form>
        <?php
        UI::page_close();
    }

    /**
     * Save the Gutenberg disabler settings
     */
    private function save_settings() {
        check_admin_referer('almazbro_site_utilities_gutenberg_disabler');

        $mode = isset($_POST['gutenberg_mode']) ? sanitize_key(wp_unslash($_POST['gutenberg_mode'])) : 'none';
        if (!in_array($mode, array('all', 'selective', 'none'), true)) {
            $mode = 'none';
        }

        $post_types = isset($_POST['post_types']) ? array_map('sanitize_key', (array) wp_unslash($_POST['post_types'])) : array();
        $post_types = array_values(array_filter($post_types, 'post_type_exists'));

        update_option(self::OPTION_NAME, array(
            'enabled'    => ($mode !== 'none'),
            'mode'       => ($mode === 'selective') ? 'selective' : 'all',
            'post_types' => $post_types
        ));

        add_settings_error(
            'almazbro_site_utilities_messages',
            'almazbro_site_utilities_message',
            __('Settings saved.', 'almazbro-site-utilities'),
            'updated'
        );
    }

    /**
     * Disable Gutenberg if the option is enabled
     */
    public function maybe_disable_gutenberg() {
        $settings = $this->get_settings();
        if (!$settings['enabled']) {
            return;
        }

        // Disable Gutenberg on the back end
        add_filter('use_block_editor_for_post', array($this, 'should_disable_gutenberg'), 10, 2);
        add_filter('use_block_editor_for_post_type', array($this, 'should_disable_gutenberg_for_post_type'), 10, 2);

        // Disable Gutenberg for widgets (only if global)
        if ($settings['mode'] === 'all') {
            add_filter('use_widgets_block_editor', '__return_false');
        }

        // Remove Gutenberg assets on the front end
        add_action('wp_enqueue_scripts', array($this, 'maybe_remove_gutenberg_assets'), 20);
    }

    /**
     * Check if Gutenberg should be disabled for a specific post
     */
    public function should_disable_gutenberg($use_block_editor, $post) {
        $settings = $this->get_settings();
        if (!$settings['enabled']) {
            return $use_block_editor;
        }

        if ($settings['mode'] === 'all') {
            return false;
        }

        if ($settings['mode'] === 'selective' && in_array($post->post_type, $settings['post_types'], true)) {
            return false;
        }

        return $use_block_editor;
    }

    /**
     * Check if Gutenberg should be disabled for a specific post type
     */
    public function should_disable_gutenberg_for_post_type($use_block_editor, $post_type) {
        $settings = $this->get_settings();
        if (!$settings['enabled']) {
            return $use_block_editor;
        }

        if ($settings['mode'] === 'all') {
            return false;
        }

        if ($settings['mode'] === 'selective' && in_array($post_type, $settings['post_types'], true)) {
            return false;
        }

        return $use_block_editor;
    }

    /**
     * Conditionally remove Gutenberg assets from the front end
     */
    public function maybe_remove_gutenberg_assets() {
        $should_remove = false;
        $settings = $this->get_settings();

        if ($settings['mode'] === 'all') {
            $should_remove = true;
        } elseif ($settings['mode'] === 'selective') {
            if (is_singular()) {
                $post = get_post();
                if ($post && in_array($post->post_type, $settings['post_types'], true)) {
                    $should_remove = true;
                }
            } else {
                // For archives, search results, etc., check if the main post type is disabled
                // This is a bit tricky, but we'll check if any of the disabled post types match
                // For simplicity, we'll only disable on singular for selective mode
                // Or we can check the global $post_type if set
                global $post_type;
                if ($post_type && in_array($post_type, $settings['post_types'], true)) {
                    $should_remove = true;
                }
            }
        }

        if ($should_remove) {
            $this->remove_gutenberg_assets();
        }
    }

    /**
     * Remove Gutenberg assets from the front end
     */
    public function remove_gutenberg_assets() {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('global-styles');
        wp_dequeue_style('classic-theme-styles');
    }
}
