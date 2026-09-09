<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Removes every option A La Carte creates. Content created through the CPT
 * Manager is deliberately left alone: those are the user's own posts, only the
 * post type registration goes away.
 *
 * @package ALaCarte
 */

// If uninstall not called from WordPress, die.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

/**
 * Options used by A La Carte.
 */
const ALACARTE_UNINSTALL_OPTIONS = [
    'alacarte_active_tools',
    'alacarte_cptm_custom_post_types',
    'alacarte_disable_comments',
    'alacarte_flush_rewrite_rules',
    'alacarte_gutenberg_settings',
    'alacarte_remove_html_junk',
    'alacarte_toolbar_toggler_enabled',
];

/**
 * Delete A La Carte data for the current site.
 */
function alacarte_uninstall_site() {
    foreach (ALACARTE_UNINSTALL_OPTIONS as $option) {
        delete_option($option);
    }
}

if (is_multisite()) {
    $alacarte_site_ids = get_sites(
        array(
            'fields' => 'ids',
            'number' => 0,
        )
    );

    foreach ($alacarte_site_ids as $alacarte_site_id) {
        switch_to_blog((int) $alacarte_site_id);
        alacarte_uninstall_site();
        restore_current_blog();
    }
} else {
    alacarte_uninstall_site();
}
