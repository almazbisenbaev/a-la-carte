<?php
/**
 * Tool registry
 *
 * Single source of truth for which tools exist, how they are labelled, which
 * icon represents them and where their settings page lives. Anything that
 * needs to describe a tool should read it from here rather than repeating it.
 *
 * @package Toolwright
 */

namespace Toolwright\Admin;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Tool_Manager
 */
class Tool_Manager {
    /**
     * Capability required to use any Toolwright screen
     *
     * @var string
     */
    public const CAPABILITY = 'manage_options';

    /**
     * Option name for storing active tools
     *
     * @var string
     */
    private const OPTION_NAME = 'toolwright_active_tools';

    /**
     * List of available tools
     *
     * @var array
     */
    private const AVAILABLE_TOOLS = [
        'cpt_manager' => [
            'name' => 'CPT Manager',
            'description' => 'Easily create and manage custom post types',
            'icon' => 'dashicons-category',
            'class' => 'Toolwright\\CPT\\Manager'
        ],
        'toolbar_toggler' => [
            'name' => 'Admin Toolbar Toggler',
            'description' => 'Replaces the admin toolbar with a nice toggler button',
            'icon' => 'dashicons-admin-generic',
            'class' => 'Toolwright\\Toolbar\\Toolbar_Toggler'
        ],
        'gutenberg_disabler' => [
            'name' => 'Gutenberg Disabler',
            'description' => 'Return the legacy editor for specific post types',
            'icon' => 'dashicons-edit',
            'class' => 'Toolwright\\Gutenberg\\Gutenberg_Disabler'
        ],
        'comments_disabler' => [
            'name' => 'Comments Disabler',
            'description' => 'This tool allows you to disable comments globally across your site',
            'icon' => 'dashicons-admin-comments',
            'class' => 'Toolwright\\Comments\\Comments_Disabler'
        ],
        'html_junk_remover' => [
            'name' => 'HTML Junk Remover',
            'description' => 'This tool removes the useless lines of code from HTML (such as WordPress version, emojis, etc.)',
            'icon' => 'dashicons-html',
            'class' => 'Toolwright\\HTML\\Junk_Remover'
        ],
        'junk_cleaner' => [
            'name' => 'Junk Cleaner',
            'description' => 'This tool lets you delete the drafts and revisions that are taking up your disc space',
            'icon' => 'dashicons-trash',
            'class' => 'Toolwright\\Cleaner\\Junk_Cleaner'
        ],
        'system_info' => [
            'name' => 'System Info',
            'description' => 'View and export system info that can be useful for your IT guy or a tech support agent',
            'icon' => 'dashicons-info',
            'class' => 'Toolwright\\System\\Info'
        ]
    ];

    /**
     * Feedback codes the dashboard can render, mapped to notice types
     *
     * Only codes from this list are ever rendered, so no caller can push
     * arbitrary text into the admin UI through the query string.
     *
     * @var array
     */
    private const MESSAGES = array(
        'activated'   => 'success',
        'deactivated' => 'success',
    );

    /**
     * Booted tool instances, keyed by tool id
     *
     * @var array
     */
    private $instances = array();

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('admin_post_toolwright_toggle_tool', array($this, 'handle_tool_toggle'));
    }

    /**
     * Instantiate every active tool once, so both the front end hooks and the
     * admin pages work off the same objects.
     */
    public function boot() {
        foreach (self::get_available_tools() as $tool_id => $tool) {
            if (!self::is_tool_active($tool_id) || !class_exists($tool['class'])) {
                continue;
            }

            $this->instances[$tool_id] = new $tool['class']();
        }
    }

    /**
     * Get the booted instance for a tool
     *
     * @param string $tool_id
     * @return object|null
     */
    public function get_tool_instance($tool_id) {
        return isset($this->instances[$tool_id]) ? $this->instances[$tool_id] : null;
    }

    /**
     * Get all available tools
     *
     * @return array
     */
    public static function get_available_tools() {
        /**
         * Filter the tools Toolwright makes available.
         *
         * @param array $tools Tool definitions keyed by tool id. Each one needs
         *                     a name, description, icon and class.
         */
        $tools = apply_filters('toolwright_tools', self::AVAILABLE_TOOLS);

        return is_array($tools) ? $tools : self::AVAILABLE_TOOLS;
    }

    /**
     * Get a single tool definition
     *
     * @param string $tool_id
     * @return array|null
     */
    public static function get_tool($tool_id) {
        $tools = self::get_available_tools();

        return isset($tools[$tool_id]) ? $tools[$tool_id] : null;
    }

    /**
     * Get the dashicon representing a tool
     *
     * @param string $tool_id
     * @return string
     */
    public static function get_icon($tool_id) {
        $tool = self::get_tool($tool_id);
        return $tool ? $tool['icon'] : 'dashicons-admin-tools';
    }

    /**
     * Get the admin page slug for a tool
     *
     * @param string $tool_id
     * @return string
     */
    public static function get_page_slug($tool_id) {
        return 'toolwright-' . str_replace('_', '-', $tool_id);
    }

    /**
     * Get the admin page URL for a tool
     *
     * @param string $tool_id
     * @return string
     */
    public static function get_page_url($tool_id) {
        return admin_url('admin.php?page=' . self::get_page_slug($tool_id));
    }

    /**
     * Get active tools
     *
     * @return array
     */
    public static function get_active_tools() {
        $active_tools = get_option(self::OPTION_NAME, array());
        if (!is_array($active_tools)) {
            $active_tools = array();
        }
        return $active_tools;
    }

    /**
     * Check if a tool is active
     *
     * @param string $tool_id
     * @return bool
     */
    public static function is_tool_active($tool_id) {
        $active_tools = self::get_active_tools();
        return !empty($active_tools[$tool_id]);
    }

    /**
     * Stop the current request unless the user may manage Toolwright
     */
    public static function require_capability() {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'toolwright'));
        }
    }

    /**
     * Handle tool toggle action
     */
    public function handle_tool_toggle() {
        self::require_capability();

        if (!isset($_POST['toolwright_toggle_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['toolwright_toggle_nonce'])), 'toolwright_toggle_tool')) {
            wp_die(esc_html__('Security check failed.', 'toolwright'));
        }

        $tool_id = isset($_POST['tool_id']) ? sanitize_key(wp_unslash($_POST['tool_id'])) : '';
        if (!self::get_tool($tool_id)) {
            wp_die(esc_html__('Invalid tool.', 'toolwright'));
        }

        $is_active = !empty($_POST['is_active']);

        $active_tools = self::get_active_tools();
        $active_tools[$tool_id] = $is_active;
        update_option(self::OPTION_NAME, $active_tools);

        wp_safe_redirect(add_query_arg(
            array(
                'tw_message' => $is_active ? 'activated' : 'deactivated',
                'tw_tool'    => $tool_id,
            ),
            admin_url('admin.php?page=toolwright')
        ));
        exit;
    }

    /**
     * Render the feedback notice for the tool that was just toggled, if any
     */
    public static function render_toggle_notice() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request state used for display only; no data is changed.
        $code = isset($_GET['tw_message']) ? sanitize_key(wp_unslash($_GET['tw_message'])) : '';

        if (!isset(self::MESSAGES[$code])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request state used for display only; no data is changed.
        $tool = self::get_tool(isset($_GET['tw_tool']) ? sanitize_key(wp_unslash($_GET['tw_tool'])) : '');

        if (!$tool) {
            return;
        }

        $message = 'activated' === $code
            /* translators: %s: tool name */
            ? sprintf(__('%s has been activated and now has its own settings page.', 'toolwright'), $tool['name'])
            /* translators: %s: tool name */
            : sprintf(__('%s has been deactivated.', 'toolwright'), $tool['name']);

        UI::notice($message, self::MESSAGES[$code]);
    }
}
