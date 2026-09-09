<?php
/**
 * Plugin Name: A La Carte
 * Plugin URI: https://github.com/almazbisenbaev/a-la-carte
 * Description: Useful WordPress utilities to solve common WordPress problems and maximize your productivity
 * Author: Almaz Bissenbayev
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: a-la-carte
 * Domain Path: /languages
 * Author URI: https://helloalmaz.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package ALaCarte
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('ALACARTE_VERSION', '1.0.0');
define('ALACARTE_PLUGIN_FILE', __FILE__);
define('ALACARTE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ALACARTE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ALACARTE_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once ALACARTE_PLUGIN_DIR . 'includes/autoloader.php';

register_activation_hook(__FILE__, array('ALaCarte\\Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('ALaCarte\\Plugin', 'deactivate'));

/**
 * Main instance of the plugin.
 *
 * @return ALaCarte\Plugin
 */
function alacarte() {
    return ALaCarte\Plugin::instance();
}

// Hook everything up once all plugins are loaded, so tools can safely assume
// the rest of the site (and any plugin they filter) is already available.
add_action('plugins_loaded', 'alacarte');
