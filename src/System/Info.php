<?php
/**
 * System Info functionality
 *
 * @package AlmazBroSiteUtilities
 */

namespace AlmazBroSiteUtilities\System;

use AlmazBroSiteUtilities\Admin\Tool_Manager;
use AlmazBroSiteUtilities\Admin\UI;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Info
 */
class Info {

    /**
     * Render the system info page
     */
    public function render_settings_page() {
        Tool_Manager::require_capability();

        UI::page_open(array(
            'tool_id'     => 'system_info',
            'title'       => __('System Information', 'almazbro-site-utilities'),
            'description' => __('Detailed overview of your WordPress environment, server configuration, and active plugins.', 'almazbro-site-utilities'),
        ));
        ?>
        <div class="almazbro-site-utilities-stack">
            <div class="almazbro-site-utilities-panel">
                <div class="almazbro-site-utilities-panel__header">
                    <h2 class="almazbro-site-utilities-h2"><?php esc_html_e('Environment Overview', 'almazbro-site-utilities'); ?></h2>
                </div>
                <div class="almazbro-site-utilities-panel__body almazbro-site-utilities-panel__body--flush">
                    <?php $this->render_definition_table($this->get_environment_rows()); ?>
                </div>
            </div>

            <div class="almazbro-site-utilities-cols-2">
                <div class="almazbro-site-utilities-panel">
                    <div class="almazbro-site-utilities-panel__header">
                        <h2 class="almazbro-site-utilities-h2"><?php esc_html_e('PHP Configuration', 'almazbro-site-utilities'); ?></h2>
                    </div>
                    <div class="almazbro-site-utilities-panel__body almazbro-site-utilities-panel__body--flush">
                        <?php $this->render_definition_table($this->get_php_rows()); ?>
                    </div>
                </div>

                <div class="almazbro-site-utilities-panel">
                    <div class="almazbro-site-utilities-panel__header">
                        <h2 class="almazbro-site-utilities-h2"><?php esc_html_e('Server Resources', 'almazbro-site-utilities'); ?></h2>
                    </div>
                    <div class="almazbro-site-utilities-panel__body almazbro-site-utilities-panel__body--flush">
                        <?php $this->render_definition_table($this->get_disk_rows()); ?>
                    </div>
                </div>
            </div>

            <div class="almazbro-site-utilities-panel">
                <div class="almazbro-site-utilities-panel__header">
                    <h2 class="almazbro-site-utilities-h2"><?php esc_html_e('Installed Plugins', 'almazbro-site-utilities'); ?></h2>
                </div>
                <div class="almazbro-site-utilities-panel__body almazbro-site-utilities-panel__body--flush">
                    <?php $this->render_plugins_table(); ?>
                </div>
            </div>
        </div>
        <?php
        UI::page_close();
    }

    /**
     * WordPress / server environment rows
     *
     * @return array label => value
     */
    private function get_environment_rows() {
        global $wpdb;

        return array(
            __('Site URL', 'almazbro-site-utilities')           => get_bloginfo('url'),
            __('WordPress Version', 'almazbro-site-utilities')  => get_bloginfo('version'),
            __('PHP Version', 'almazbro-site-utilities')        => phpversion(),
            __('MySQL Version', 'almazbro-site-utilities')      => $wpdb->db_version(),
            __('Server Software', 'almazbro-site-utilities')    => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : __('Unknown', 'almazbro-site-utilities'),
        );
    }

    /**
     * PHP configuration rows
     *
     * @return array label => value
     */
    private function get_php_rows() {
        return array(
            __('Max Execution Time', 'almazbro-site-utilities')  => ini_get('max_execution_time') . 's',
            __('Memory Limit', 'almazbro-site-utilities')        => ini_get('memory_limit'),
            __('Upload Max Filesize', 'almazbro-site-utilities') => ini_get('upload_max_filesize'),
        );
    }

    /**
     * Disk space rows
     *
     * @return array label => value
     */
    private function get_disk_rows() {
        return array(
            __('Available Disk Space', 'almazbro-site-utilities') => $this->format_bytes($this->disk_space('disk_free_space')),
            __('Total Disk Space', 'almazbro-site-utilities')     => $this->format_bytes($this->disk_space('disk_total_space')),
        );
    }

    /**
     * Read a disk space figure without letting a locked-down host break the page.
     *
     * Shared hosts routinely disable these functions outright, or allow them
     * but trip an open_basedir warning on the call, so both cases are handled.
     *
     * @param string $function Either disk_free_space or disk_total_space.
     * @return float|false Bytes, or false when unavailable.
     */
    private function disk_space($function) {
        if (!function_exists($function)) {
            return false;
        }

        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- open_basedir restrictions warn here on some hosts; false is handled by format_bytes().
        return @$function(ABSPATH);
    }

    /**
     * Render a two column label/value table
     *
     * @param array $rows label => value
     */
    private function render_definition_table(array $rows) {
        ?>
        <table class="almazbro-site-utilities-table almazbro-site-utilities-table--definition">
            <tbody>
                <?php foreach ($rows as $label => $value) : ?>
                    <tr>
                        <th scope="row"><?php echo esc_html($label); ?></th>
                        <td><?php echo esc_html($value); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render the installed plugins table
     */
    private function render_plugins_table() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();
        ?>
        <div class="almazbro-site-utilities-table-scroll">
            <table class="almazbro-site-utilities-table">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e('Plugin Name', 'almazbro-site-utilities'); ?></th>
                        <th scope="col"><?php esc_html_e('Status', 'almazbro-site-utilities'); ?></th>
                        <th scope="col"><?php esc_html_e('Version', 'almazbro-site-utilities'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_plugins as $path => $plugin) : ?>
                        <?php $is_active = is_plugin_active($path); ?>
                        <tr>
                            <td><span class="almazbro-site-utilities-table__name"><?php echo esc_html($plugin['Name']); ?></span></td>
                            <td>
                                <span class="almazbro-site-utilities-pill almazbro-site-utilities-pill--upper <?php echo $is_active ? 'almazbro-site-utilities-pill--success' : 'almazbro-site-utilities-pill--neutral'; ?>">
                                    <?php echo $is_active ? esc_html__('Active', 'almazbro-site-utilities') : esc_html__('Inactive', 'almazbro-site-utilities'); ?>
                                </span>
                            </td>
                            <td><span class="almazbro-site-utilities-mono almazbro-site-utilities-text-muted"><?php echo esc_html($plugin['Version']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Format a byte count for display
     *
     * @param float|false $bytes
     * @return string
     */
    private function format_bytes($bytes) {
        if (!$bytes) {
            return __('Unknown', 'almazbro-site-utilities');
        }

        return size_format($bytes, 2);
    }
}
