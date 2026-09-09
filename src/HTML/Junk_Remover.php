<?php
/**
 * HTML Junk Remover functionality
 *
 * @package ALaCarte
 */

namespace ALaCarte\HTML;

use ALaCarte\Admin\Simple_Toggle_Tool;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Junk_Remover
 */
class Junk_Remover extends Simple_Toggle_Tool {
    /**
     * Option name for storing HTML junk remover settings
     *
     * @var string
     */
    private const OPTION_NAME = 'alacarte_remove_html_junk';

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('init', array($this, 'maybe_remove_html_junk'));
    }

    protected function get_tool_id(): string {
        return 'html_junk_remover';
    }

    protected function get_option_name(): string {
        return self::OPTION_NAME;
    }

    protected function get_nonce_action(): string {
        return 'alacarte_html_junk_remover';
    }

    protected function get_page_title(): string {
        return __('HTML Junk Remover', 'a-la-carte');
    }

    protected function get_page_description(): string {
        return __('Clean up your website source code by removing unnecessary tags, scripts, and links from the HEAD section.', 'a-la-carte');
    }

    protected function get_field_name(): string {
        return 'enable_html_junk_remover';
    }

    protected function get_field_title(): string {
        return __('Remove HTML Junk', 'a-la-carte');
    }

    protected function get_field_description(): string {
        return __('This will remove WordPress version tags, emojis, REST API links, and other discovery links from your site\'s head.', 'a-la-carte');
    }

    /**
     * Remove HTML junk if enabled
     */
    public function maybe_remove_html_junk() {
        if (get_option(self::OPTION_NAME) !== '1') {
            return;
        }

        $this->remove_html_junk();
    }

    /**
     * Remove various HTML junk from WordPress
     */
    private function remove_html_junk() {
        // Remove WordPress version
        add_filter('the_generator', array($this, 'remove_version'));

        // Remove REST API links
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
        remove_action('template_redirect', 'rest_output_link_header', 11, 0);
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_shortlink_wp_head');

        // Remove WP Emoji
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
    }

    /**
     * Remove WordPress version
     *
     * @return string
     */
    public function remove_version() {
        return '';
    }
}
