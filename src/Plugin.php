<?php
/**
 * Main Plugin Class
 *
 * @package AlmazBroSiteUtilities
 */

namespace AlmazBroSiteUtilities;

use AlmazBroSiteUtilities\Admin\Admin_Menu;
use AlmazBroSiteUtilities\Admin\Assets;
use AlmazBroSiteUtilities\Admin\Tool_Manager;
use AlmazBroSiteUtilities\CPT\Manager;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Plugin
 */
final class Plugin {

    /**
     * Last applied CPT route signature, or 'yes' to force a refresh after activation.
     *
     * @var string
     */
    private const FLUSH_OPTION = 'almazbro_site_utilities_flush_rewrite_rules';

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

        add_action('wp_loaded', array($this, 'maybe_flush_rewrite_rules'), 100);

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
        $manager = $this->tools->get_tool_instance('cpt_manager');
        $signature = $manager instanceof Manager ? $manager->get_rewrite_signature() : '';
        if (get_option(self::FLUSH_OPTION, '') === $signature) {
            return;
        }

        flush_rewrite_rules(false);
        update_option(self::FLUSH_OPTION, $signature);
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

        // This request still has the old registrations. Let core rebuild on
        // the next request, when the plugin has actually been unloaded.
        delete_option('rewrite_rules');
    }
}
