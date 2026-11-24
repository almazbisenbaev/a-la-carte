<?php
/**
 * Plugin Name: Seitenmeister
 * Description: Useful WordPress utilities to solve common WordPress problems and maximize your productivity
 * Author: Almaz Bissenbayev
 * Version: 1.0.2
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: almazbro-site-utilities
 * Author URI: https://helloalmaz.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package AlmazBroSiteUtilities
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('ALMAZBRO_SITE_UTILITIES_VERSION', '1.0.2');
define('ALMAZBRO_SITE_UTILITIES_PLUGIN_FILE', __FILE__);
define('ALMAZBRO_SITE_UTILITIES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ALMAZBRO_SITE_UTILITIES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ALMAZBRO_SITE_UTILITIES_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once ALMAZBRO_SITE_UTILITIES_PLUGIN_DIR . 'includes/autoloader.php';

register_activation_hook(__FILE__, array('AlmazBroSiteUtilities\\Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('AlmazBroSiteUtilities\\Plugin', 'deactivate'));

/**
 * Main instance of the plugin.
 *
 * @return AlmazBroSiteUtilities\Plugin
 */
function almazbro_site_utilities() {
    return AlmazBroSiteUtilities\Plugin::instance();
}

// Hook everything up once all plugins are loaded, so tools can safely assume
// the rest of the site (and any plugin they filter) is already available.
add_action('plugins_loaded', 'almazbro_site_utilities');
