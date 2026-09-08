<?php
/**
 * Gutenberg Disabler functionality
 *
 * @package Toolwright
 */

namespace Toolwright\Gutenberg;

use Toolwright\Admin\Tool_Manager;
use Toolwright\Admin\UI;

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
    private const OPTION_NAME = 'toolwright_gutenberg_settings';

    /**
     * Legacy option name
     *
     * @var string
     */
    private const LEGACY_OPTION_NAME = 'toolwright_disable_gutenberg';

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('init', array($this, 'maybe_migrate_settings'));
        add_action('init', array($this, 'maybe_disable_gutenberg'));
    }

    /**
     * Migrate settings from legacy option
     */
    public function maybe_migrate_settings() {
        $legacy = get_option(self::LEGACY_OPTION_NAME);
        if ($legacy !== false) {
            $settings = array(
                'enabled'    => ($legacy === '1' || $legacy === 1),
                'mode'       => 'all',
                'post_types' => array()
            );
            update_option(self::OPTION_NAME, $settings);
            delete_option(self::LEGACY_OPTION_NAME);
        }
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

        // Merge rather than return the stored value directly: an option saved
        // by an older version may be missing keys the render/filter code reads.
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
        if (isset($_POST['save']) && check_admin_referer('toolwright_gutenberg_disabler')) {
            $this->save_settings();
        }

        $settings   = $this->get_settings();
        $post_types = get_post_types(array('public' => true), 'objects');

        $modes = array(
            'all' => array(
                'title'       => __('Disable Globally', 'toolwright'),
                'description' => __('Completely disable the block editor for all post types and widgets.', 'toolwright'),
                'checked'     => $settings['enabled'] && $settings['mode'] === 'all',
            ),
            'selective' => array(
                'title'       => __('Disable Selectively', 'toolwright'),
                'description' => __('Choose specific post types where you want to use the Classic Editor.', 'toolwright'),
                'checked'     => $settings['enabled'] && $settings['mode'] === 'selective',
            ),
            'none' => array(
                'title'       => __('Keep Gutenberg Enabled', 'toolwright'),
                'description' => __('Do not disable the block editor.', 'toolwright'),
                'checked'     => !$settings['enabled'],
            ),
        );

        UI::page_open(array(
            'tool_id'     => 'gutenberg_disabler',
            'title'       => __('Gutenberg Disabler', 'toolwright'),
            'description' => __('Switch back to the Classic Editor and disable Gutenberg block library assets.', 'toolwright'),
        ));
        ?>
        <form class="ptools-panel" method="post">
            <?php wp_nonce_field('toolwright_gutenberg_disabler'); ?>

            <div class="ptools-panel__header">
                <h2 class="ptools-h2"><?php esc_html_e('Settings', 'toolwright'); ?></h2>
            </div>

            <div class="ptools-panel__body">
                <div class="ptools-panel__section">
                    <?php foreach ($modes as $mode => $option) : ?>
                        <label class="ptools-choice ptools-choice--card">
                            <input type="radio"
                                   name="gutenberg_mode"
                                   value="<?php echo esc_attr($mode); ?>"
                                   <?php echo 'selective' === $mode ? 'data-ptools-reveal="ptools-gutenberg-post-types"' : ''; ?>
                                   <?php checked($option['checked']); ?> />
                            <span class="ptools-choice__text">
                                <span class="ptools-choice__title"><?php echo esc_html($option['title']); ?></span>
                                <span class="ptools-choice__desc"><?php echo esc_html($option['description']); ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="ptools-panel__section" id="ptools-gutenberg-post-types" <?php echo $modes['selective']['checked'] ? '' : 'hidden'; ?>>
                    <p class="ptools-eyebrow"><?php esc_html_e('Post types using the Classic Editor', 'toolwright'); ?></p>
                    <div class="ptools-choice-grid">
                        <?php foreach ($post_types as $post_type) : ?>
                            <label class="ptools-choice ptools-choice--boxed">
                                <input type="checkbox"
                                       name="post_types[]"
                                       value="<?php echo esc_attr($post_type->name); ?>"
                                       <?php checked(in_array($post_type->name, $settings['post_types'], true)); ?> />
                                <span class="ptools-choice__text">
                                    <span class="ptools-choice__title"><?php echo esc_html($post_type->label); ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="ptools-panel__footer">
                <button type="submit" name="save" value="1" class="ptools-btn ptools-btn--primary">
                    <?php esc_html_e('Save Settings', 'toolwright'); ?>
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
        check_admin_referer('toolwright_gutenberg_disabler');

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
            'toolwright_messages',
            'toolwright_message',
            __('Settings saved.', 'toolwright'),
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
