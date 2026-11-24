<?php
/**
 * Admin asset loading
 *
 * The shared admin stylesheet and script are only ever loaded on Seitenmeister
 * screens: they carry wp-admin overrides that would otherwise leak into other
 * plugins' pages. Tool specific assets are enqueued by the tool itself.
 *
 * @package AlmazBroSiteUtilities
 */

namespace AlmazBroSiteUtilities\Admin;

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
        if (!self::is_almazbro_site_utilities_screen($hook)) {
            return;
        }

        wp_enqueue_style(
            'almazbro-site-utilities-admin',
            ALMAZBRO_SITE_UTILITIES_PLUGIN_URL . 'assets/css/admin.css',
            array('dashicons'),
            ALMAZBRO_SITE_UTILITIES_VERSION
        );

        wp_enqueue_script(
            'almazbro-site-utilities-admin',
            ALMAZBRO_SITE_UTILITIES_PLUGIN_URL . 'assets/js/admin.js',
            array(),
            ALMAZBRO_SITE_UTILITIES_VERSION,
            true
        );
    }

    /**
     * Whether the given admin hook belongs to one of the plugin screens
     *
     * @param string $hook Current admin page hook suffix.
     * @return bool
     */
    public static function is_almazbro_site_utilities_screen($hook) {
        if ($hook === 'toplevel_page_almazbro-site-utilities') {
            return true;
        }

        foreach (Tool_Manager::get_available_tools() as $tool_id => $tool) {
            if (self::is_tool_screen($hook, $tool_id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the given admin hook is a specific tool's settings screen
     *
     * @param string $hook    Current admin page hook suffix.
     * @param string $tool_id Tool id as registered in Tool_Manager.
     * @return bool
     */
    public static function is_tool_screen($hook, $tool_id) {
        return $hook === get_plugin_page_hookname(
            Tool_Manager::get_page_slug($tool_id),
            'almazbro-site-utilities'
        );
    }
}
