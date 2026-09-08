<?php
/**
 * Main Plugin Class
 *
 * @package Toolwright
 */

namespace Toolwright;

use Toolwright\Admin\Admin_Menu;
use Toolwright\Admin\Assets;
use Toolwright\Admin\Tool_Manager;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Plugin
 */
final class Plugin {

    /**
     * Option flagging that rewrite rules must be regenerated on the next request.
     *
     * @var string
     */
    private const FLUSH_OPTION = 'toolwright_flush_rewrite_rules';

    /**
     * Single plugin instance
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Shared tool registry
     *
     * @var Tool_Manager
     */
    private $tools;

    /**
     * Get the single plugin instance, booting it on first call
     *
     * @return Plugin
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register the plugin's hooks
     */
    private function __construct() {
        $this->tools = new Tool_Manager();

        // Tools boot before the admin menu so its pages can render from the
        // same instances instead of creating throwaway objects per request.
        $this->tools->boot();

        add_action('init', array($this, 'maybe_flush_rewrite_rules'), 100);

        if (is_admin()) {
            new Admin_Menu($this->tools);
            new Assets();
        }
    }

    /**
     * Get the tool registry
     *
     * @return Tool_Manager
     */
    public function tools() {
        return $this->tools;
    }

    /**
     * Regenerate rewrite rules once, after every tool has registered its post
     * types. Flushing during activation itself would happen too early, since
     * `init` has already run by the time the activation hook fires.
     */
    public function maybe_flush_rewrite_rules() {
        if (get_option(self::FLUSH_OPTION) !== 'yes') {
            return;
        }

        delete_option(self::FLUSH_OPTION);
        flush_rewrite_rules();
    }

    /**
     * Run on plugin activation
     */
    public static function activate() {
        update_option(self::FLUSH_OPTION, 'yes');
    }

    /**
     * Run on plugin deactivation
     */
    public static function deactivate() {
        delete_option(self::FLUSH_OPTION);

        // Post types registered by Toolwright are gone from here on, so drop
        // the rules that pointed at them.
        flush_rewrite_rules();
    }
}
