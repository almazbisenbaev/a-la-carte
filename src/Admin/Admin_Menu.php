<?php
/**
 * Menu setup for PowerTools plugin
 *
 * @package PowerTools
 */

namespace PowerTools\Admin;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Admin_Menu
 */
class Admin_Menu {
    /**
     * Shared tool registry
     *
     * @var Tool_Manager
     */
    private $tools;

    /**
     * Initialize the class
     *
     * @param Tool_Manager $tools
     */
    public function __construct(Tool_Manager $tools) {
        $this->tools = $tools;

        add_action('admin_menu', array($this, 'setup_menu'));
        add_filter('plugin_action_links_' . POWERTOOLS_PLUGIN_BASENAME, array($this, 'add_action_links'));
    }

    /**
     * Setup the admin menu
     */
    public function setup_menu() {
        add_menu_page(
            __('PowerTools', 'powertools'),
            __('PowerTools', 'powertools'),
            Tool_Manager::CAPABILITY,
            'powertools',
            array($this, 'render_dashboard'),
            'dashicons-hammer',
            100
        );

        $this->add_submenu_pages();
    }

    /**
     * Add a submenu page for every active tool
     */
    private function add_submenu_pages() {
        foreach (Tool_Manager::get_available_tools() as $tool_id => $tool) {
            if (!Tool_Manager::is_tool_active($tool_id)) {
                continue;
            }

            add_submenu_page(
                'powertools',
                $tool['name'],
                $tool['name'],
                Tool_Manager::CAPABILITY,
                Tool_Manager::get_page_slug($tool_id),
                function () use ($tool_id) {
                    $this->render_tool_page($tool_id);
                }
            );
        }
    }

    /**
     * Add a Settings link to the plugin's row on the Plugins screen
     *
     * @param array $links
     * @return array
     */
    public function add_action_links($links) {
        array_unshift($links, sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('admin.php?page=powertools')),
            esc_html__('Settings', 'powertools')
        ));

        return $links;
    }

    /**
     * Render the dashboard
     */
    public function render_dashboard() {
        Tool_Manager::require_capability();

        require POWERTOOLS_PLUGIN_DIR . 'views/dashboard.php';
    }

    /**
     * Render a tool's settings page using the already booted instance
     *
     * @param string $tool_id
     */
    private function render_tool_page($tool_id) {
        Tool_Manager::require_capability();

        $tool = $this->tools->get_tool_instance($tool_id);

        if (!$tool || !method_exists($tool, 'render_settings_page')) {
            UI::page_open(array(
                'tool_id'     => $tool_id,
                'title'       => __('Tool unavailable', 'powertools'),
                'description' => __('This tool could not be loaded. Try deactivating and reactivating it from the PowerTools dashboard.', 'powertools'),
            ));
            UI::page_close();
            return;
        }

        $tool->render_settings_page();
    }
}
