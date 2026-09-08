<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Removes every option Toolwright creates. Content created through the CPT
 * Manager is deliberately left alone: those are the user's own posts, only the
 * post type registration goes away.
 *
 * @package Toolwright
 */

// If uninstall not called from WordPress, die.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

/**
 * Options used by Toolwright, including legacy names kept for older installs.
 */
const TOOLWRIGHT_UNINSTALL_OPTIONS = [
    'toolwright_active_tools',
    'toolwright_cptm_custom_post_types',
    'toolwright_disable_comments',
    'toolwright_disable_gutenberg',
    'toolwright_flush_rewrite_rules',
    'toolwright_gutenberg_settings',
    'toolwright_remove_html_junk',
    'toolwright_toolbar_toggler_enabled',
];

/**
 * Delete Toolwright data for the current site.
 */
function toolwright_uninstall_site() {
    foreach (TOOLWRIGHT_UNINSTALL_OPTIONS as $option) {
        delete_option($option);
    }
}

if (is_multisite()) {
    $toolwright_site_ids = get_sites(
        array(
            'fields' => 'ids',
            'number' => 0,
        )
    );

    foreach ($toolwright_site_ids as $toolwright_site_id) {
        switch_to_blog((int) $toolwright_site_id);
        toolwright_uninstall_site();
        restore_current_blog();
    }
} else {
    toolwright_uninstall_site();
}
