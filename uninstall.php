<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Removes every option PowerTools creates. Content created through the CPT
 * Manager is deliberately left alone: those are the user's own posts, only the
 * post type registration goes away.
 *
 * @package PowerTools
 */

// If uninstall not called from WordPress, die.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

/**
 * Options used by PowerTools, including legacy names kept for older installs.
 */
const POWERTOOLS_UNINSTALL_OPTIONS = [
    'powertools_active_tools',
    'powertools_cptm_custom_post_types',
    'powertools_disable_comments',
    'powertools_disable_gutenberg',
    'powertools_flush_rewrite_rules',
    'powertools_gutenberg_settings',
    'powertools_remove_html_junk',
    'powertools_toolbar_toggler_enabled',
];

/**
 * Delete PowerTools data for the current site.
 */
function powertools_uninstall_site() {
    foreach (POWERTOOLS_UNINSTALL_OPTIONS as $option) {
        delete_option($option);
    }
}

if (is_multisite()) {
    global $wpdb;

    $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

    foreach ($blog_ids as $blog_id) {
        switch_to_blog((int) $blog_id);
        powertools_uninstall_site();
        restore_current_blog();
    }
} else {
    powertools_uninstall_site();
}
