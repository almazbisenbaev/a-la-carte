<?php
/**
 * Comments Disabler functionality
 *
 * @package PowerTools
 */

namespace PowerTools\Comments;

use PowerTools\Admin\Simple_Toggle_Tool;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Comments_Disabler
 */
class Comments_Disabler extends Simple_Toggle_Tool {
    /**
     * Option name for storing comments disabler settings
     *
     * @var string
     */
    private const OPTION_NAME = 'powertools_disable_comments';

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('init', array($this, 'maybe_disable_comments'));
    }

    protected function get_tool_id(): string {
        return 'comments_disabler';
    }

    protected function get_option_name(): string {
        return self::OPTION_NAME;
    }

    protected function get_nonce_action(): string {
        return 'powertools_comments_disabler';
    }

    protected function get_page_title(): string {
        return __('Disable Comments', 'powertools');
    }

    protected function get_page_description(): string {
        return __('Completely disable the WordPress comments feature and clean up your admin.', 'powertools');
    }

    protected function get_field_name(): string {
        return 'disable_comments';
    }

    protected function get_field_title(): string {
        return __('Disable Comments Globally', 'powertools');
    }

    protected function get_field_description(): string {
        return __('This will disable comments on all post types, remove comment links from the admin bar, and hide the comments menu.', 'powertools');
    }

    /**
     * Disable comments if the option is enabled
     */
    public function maybe_disable_comments() {
        if (get_option(self::OPTION_NAME) !== '1') {
            return;
        }

        // Disable support for comments and trackbacks in post types
        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);

        // Hide existing comments
        add_filter('comments_array', '__return_empty_array', 10, 2);

        // Remove comments page from admin menu
        add_action('admin_menu', array($this, 'remove_comments_menu'));

        // Remove comments links from admin bar
        add_action('admin_bar_menu', array($this, 'remove_comments_admin_bar'), 999);

        // Remove comments metabox from dashboard
        add_action('admin_init', array($this, 'remove_comments_dashboard'));

        // Remove comments from admin bar
        add_action('wp_before_admin_bar_render', array($this, 'remove_comments_admin_bar_render'));

        // Disable comments widget
        add_action('widgets_init', array($this, 'disable_comments_widget'));

        // Redirect any user trying to access comments page
        add_action('admin_init', array($this, 'disable_comments_page'));

        // Remove comments metabox from post types
        add_action('admin_init', array($this, 'remove_comments_metabox'));

        // Disable REST API support for comments
        add_filter('rest_endpoints', array($this, 'disable_comments_rest_endpoints'));

        // Disable XML-RPC for comments
        add_filter('xmlrpc_methods', array($this, 'disable_xmlrpc_comments'));
    }

    /**
     * Disable REST API endpoints for comments
     */
    public function disable_comments_rest_endpoints($endpoints) {
        if (isset($endpoints['/wp/v2/comments'])) {
            unset($endpoints['/wp/v2/comments']);
        }
        if (isset($endpoints['/wp/v2/comments/(?P<id>[\d]+)'])) {
            unset($endpoints['/wp/v2/comments/(?P<id>[\d]+)']);
        }
        return $endpoints;
    }

    /**
     * Disable XML-RPC methods for comments
     */
    public function disable_xmlrpc_comments($methods) {
        unset($methods['wp.getCommentCount']);
        unset($methods['wp.getComment']);
        unset($methods['wp.getComments']);
        unset($methods['wp.newComment']);
        unset($methods['wp.editComment']);
        unset($methods['wp.deleteComment']);
        unset($methods['wp.getCommentStatusList']);
        return $methods;
    }

    /**
     * Remove comments menu from admin
     */
    public function remove_comments_menu() {
        remove_menu_page('edit-comments.php');
    }

    /**
     * Remove comments links from admin bar
     */
    public function remove_comments_admin_bar() {
        global $wp_admin_bar;
        if ($wp_admin_bar) {
            $wp_admin_bar->remove_menu('comments');
        }
    }

    /**
     * Remove comments metabox from dashboard
     */
    public function remove_comments_dashboard() {
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    }

    /**
     * Remove comments from admin bar
     */
    public function remove_comments_admin_bar_render() {
        global $wp_admin_bar;
        if ($wp_admin_bar) {
            $wp_admin_bar->remove_menu('comments');
        }
    }

    /**
     * Disable comments widget
     */
    public function disable_comments_widget() {
        unregister_widget('WP_Widget_Recent_Comments');
        add_filter('show_recent_comments_widget_style', '__return_false');
    }

    /**
     * Redirect any user trying to access comments page
     */
    public function disable_comments_page() {
        if (is_admin() && function_exists('get_current_screen')) {
            $screen = get_current_screen();
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request state used for display only; no data is changed.
            if ($screen && $screen->id === 'edit-comments' && !isset($_GET['action'])) {
                wp_safe_redirect(admin_url());
                exit;
            }
        }
    }

    /**
     * Remove comments metabox from post types
     */
    public function remove_comments_metabox() {
        $post_types = get_post_types();
        foreach ($post_types as $post_type) {
            if (post_type_supports($post_type, 'comments')) {
                remove_post_type_support($post_type, 'comments');
                remove_post_type_support($post_type, 'trackbacks');
            }
        }
    }
}