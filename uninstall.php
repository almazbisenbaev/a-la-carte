<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Removes every option Seitenmeister creates. Content created through the CPT
 * Manager is deliberately left alone: those are the user's own posts, only the
 * post type registration goes away.
 *
 * @package AlmazBroSiteUtilities
 */

// If uninstall not called from WordPress, die.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

/**
 * Options used by Seitenmeister.
 */
const ALMAZBRO_SITE_UTILITIES_UNINSTALL_OPTIONS = [
    'almazbro_site_utilities_active_tools',
    'almazbro_site_utilities_cptm_custom_post_types',
    'almazbro_site_utilities_disable_comments',
    'almazbro_site_utilities_flush_rewrite_rules',
    'almazbro_site_utilities_gutenberg_settings',
    'almazbro_site_utilities_remove_html_junk',
    'almazbro_site_utilities_toolbar_toggler_enabled',
];

/**
 * Delete Seitenmeister data for the current site.
 */
function almazbro_site_utilities_uninstall_site() {
    foreach (ALMAZBRO_SITE_UTILITIES_UNINSTALL_OPTIONS as $option) {
        delete_option($option);
    }
}

if (is_multisite()) {
    $almazbro_site_utilities_site_ids = get_sites(
        array(
            'fields' => 'ids',
            'number' => 0,
        )
    );

    foreach ($almazbro_site_utilities_site_ids as $almazbro_site_utilities_site_id) {
        switch_to_blog((int) $almazbro_site_utilities_site_id);
        almazbro_site_utilities_uninstall_site();
        restore_current_blog();
    }
} else {
    almazbro_site_utilities_uninstall_site();
}
