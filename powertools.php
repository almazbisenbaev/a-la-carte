<?php
/**
 * Plugin Name: PowerTools – Site Admin Utilities
 * Plugin URI: https://github.com/almazbisenbaev/powertools
 * Description: Useful WordPress utilities to solve common WordPress problems and maximize your productivity
 * Author: Almaz Bissenbayev
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: powertools
 * Domain Path: /languages
 * Author URI: https://helloalmaz.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package PowerTools
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('POWERTOOLS_VERSION', '1.0.0');
define('POWERTOOLS_PLUGIN_FILE', __FILE__);
define('POWERTOOLS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('POWERTOOLS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('POWERTOOLS_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once POWERTOOLS_PLUGIN_DIR . 'includes/autoloader.php';

register_activation_hook(__FILE__, array('PowerTools\\Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('PowerTools\\Plugin', 'deactivate'));

/**
 * Main instance of the plugin.
 *
 * @return PowerTools\Plugin
 */
function powertools() {
    return PowerTools\Plugin::instance();
}

// Hook everything up once all plugins are loaded, so tools can safely assume
// the rest of the site (and any plugin they filter) is already available.
add_action('plugins_loaded', 'powertools');
