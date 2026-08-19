<?php
/**
 * Toolbar Toggler functionality
 *
 * @package PowerTools
 */

namespace PowerTools\Toolbar;

use PowerTools\Admin\Simple_Toggle_Tool;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Toolbar_Toggler
 */
class Toolbar_Toggler extends Simple_Toggle_Tool {
    /**
     * Option name for storing toolbar toggler settings
     *
     * @var string
     */
    private const OPTION_NAME = 'powertools_toolbar_toggler_enabled';

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_toolbar_toggler_assets'));
        add_action('wp_footer', array($this, 'maybe_render_toggle_button'));
    }

    protected function get_tool_id(): string {
        return 'toolbar_toggler';
    }

    protected function get_option_name(): string {
        return self::OPTION_NAME;
    }

    protected function get_nonce_action(): string {
        return 'powertools_toolbar_toggler';
    }

    protected function get_page_title(): string {
        return __('Toolbar Toggler', 'powertools');
    }

    protected function get_page_description(): string {
        return __('Minimize distractions by hiding the admin toolbar behind a sleek toggle button.', 'powertools');
    }

    protected function get_field_name(): string {
        return 'enable_toolbar_toggler';
    }

    protected function get_field_title(): string {
        return __('Enable Toolbar Toggler', 'powertools');
    }

    protected function get_field_description(): string {
        return __('This will hide the default WordPress toolbar and show a small toggle button in the top-left corner instead.', 'powertools');
    }

    /**
     * Check if toolbar toggler is active for current user
     */
    private function is_toggler_active(): bool {
        return current_user_can('manage_options') && get_option(self::OPTION_NAME) === '1';
    }

    /**
     * Enqueue styles and scripts if enabled
     */
    public function maybe_enqueue_toolbar_toggler_assets() {
        if (!$this->is_toggler_active()) {
            return;
        }

        wp_enqueue_style(
            'powertools-toolbar-toggler',
            POWERTOOLS_PLUGIN_URL . 'assets/css/toolbar-toggler.css',
            array('dashicons'),
            POWERTOOLS_VERSION
        );

        wp_enqueue_script(
            'powertools-toolbar-toggler',
            POWERTOOLS_PLUGIN_URL . 'assets/js/toolbar-toggler.js',
            array(),
            POWERTOOLS_VERSION,
            true
        );
    }

    /**
     * Render the toggle button in footer if active
     */
    public function maybe_render_toggle_button() {
        if (!$this->is_toggler_active()) {
            return;
        }

        echo '<div id="toolbar-toggle-button" class="dashicons dashicons-admin-generic"></div>';
    }
}
