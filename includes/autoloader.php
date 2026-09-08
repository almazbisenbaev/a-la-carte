<?php
/**
 * PSR-4 style autoloader for Toolwright classes
 *
 * Maps the `Toolwright\` namespace onto the `src/` directory, e.g.
 * `Toolwright\Admin\Tool_Manager` => `src/Admin/Tool_Manager.php`.
 *
 * @package Toolwright
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

spl_autoload_register(function ($class) {
    $prefix   = 'Toolwright\\';
    $base_dir = TOOLWRIGHT_PLUGIN_DIR . 'src/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative_class = substr($class, strlen($prefix));
    $file           = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
