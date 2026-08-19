<?php
/**
 * Admin asset loading
 *
 * The shared admin stylesheet and script are only ever loaded on PowerTools
 * screens: they carry wp-admin overrides that would otherwise leak into other
 * plugins' pages. Tool specific assets are enqueued by the tool itself.
 *
 * @package PowerTools
 */

namespace PowerTools\Admin;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Assets
 */
class Assets {

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue'));
    }

    /**
     * Enqueue the shared admin styles and scripts
     *
     * @param string $hook Current admin page hook suffix.
     */
    public function enqueue($hook) {
        if (!self::is_powertools_screen($hook)) {
            return;
        }

        wp_enqueue_style(
            'powertools-admin',
            POWERTOOLS_PLUGIN_URL . 'assets/css/admin.css',
            array('dashicons'),
            POWERTOOLS_VERSION
        );

        wp_enqueue_script(
            'powertools-admin',
            POWERTOOLS_PLUGIN_URL . 'assets/js/admin.js',
            array(),
            POWERTOOLS_VERSION,
            true
        );
    }

    /**
     * Whether the given admin hook belongs to a PowerTools screen
     *
     * @param string $hook Current admin page hook suffix.
     * @return bool
     */
    public static function is_powertools_screen($hook) {
        return $hook === 'toplevel_page_powertools'
            || strpos($hook, 'powertools_page_powertools-') === 0;
    }

    /**
     * Whether the given admin hook is a specific tool's settings screen
     *
     * @param string $hook    Current admin page hook suffix.
     * @param string $tool_id Tool id as registered in Tool_Manager.
     * @return bool
     */
    public static function is_tool_screen($hook, $tool_id) {
        return $hook === 'powertools_page_' . Tool_Manager::get_page_slug($tool_id);
    }
}
