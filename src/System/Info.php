<?php
/**
 * System Info functionality
 *
 * @package ALaCarte
 */

namespace ALaCarte\System;

use ALaCarte\Admin\Tool_Manager;
use ALaCarte\Admin\UI;

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
            'title'       => __('System Information', 'a-la-carte'),
            'description' => __('Detailed overview of your WordPress environment, server configuration, and active plugins.', 'a-la-carte'),
        ));
        ?>
        <div class="alacarte-stack">
            <div class="alacarte-panel">
                <div class="alacarte-panel__header">
                    <h2 class="alacarte-h2"><?php esc_html_e('Environment Overview', 'a-la-carte'); ?></h2>
                </div>
                <div class="alacarte-panel__body alacarte-panel__body--flush">
                    <?php $this->render_definition_table($this->get_environment_rows()); ?>
                </div>
            </div>

            <div class="alacarte-cols-2">
                <div class="alacarte-panel">
                    <div class="alacarte-panel__header">
                        <h2 class="alacarte-h2"><?php esc_html_e('PHP Configuration', 'a-la-carte'); ?></h2>
                    </div>
                    <div class="alacarte-panel__body alacarte-panel__body--flush">
                        <?php $this->render_definition_table($this->get_php_rows()); ?>
                    </div>
                </div>

                <div class="alacarte-panel">
                    <div class="alacarte-panel__header">
                        <h2 class="alacarte-h2"><?php esc_html_e('Server Resources', 'a-la-carte'); ?></h2>
                    </div>
                    <div class="alacarte-panel__body alacarte-panel__body--flush">
                        <?php $this->render_definition_table($this->get_disk_rows()); ?>
                    </div>
                </div>
            </div>

            <div class="alacarte-panel">
                <div class="alacarte-panel__header">
                    <h2 class="alacarte-h2"><?php esc_html_e('Installed Plugins', 'a-la-carte'); ?></h2>
                </div>
                <div class="alacarte-panel__body alacarte-panel__body--flush">
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
            __('Site URL', 'a-la-carte')           => get_bloginfo('url'),
            __('WordPress Version', 'a-la-carte')  => get_bloginfo('version'),
            __('PHP Version', 'a-la-carte')        => phpversion(),
            __('MySQL Version', 'a-la-carte')      => $wpdb->db_version(),
            __('Server Software', 'a-la-carte')    => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : __('Unknown', 'a-la-carte'),
        );
    }

    /**
     * PHP configuration rows
     *
     * @return array label => value
     */
    private function get_php_rows() {
        return array(
            __('Max Execution Time', 'a-la-carte')  => ini_get('max_execution_time') . 's',
            __('Memory Limit', 'a-la-carte')        => ini_get('memory_limit'),
            __('Upload Max Filesize', 'a-la-carte') => ini_get('upload_max_filesize'),
        );
    }

    /**
     * Disk space rows
     *
     * @return array label => value
     */
    private function get_disk_rows() {
        return array(
            __('Available Disk Space', 'a-la-carte') => $this->format_bytes($this->disk_space('disk_free_space')),
            __('Total Disk Space', 'a-la-carte')     => $this->format_bytes($this->disk_space('disk_total_space')),
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
        <table class="alacarte-table alacarte-table--definition">
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
        <div class="alacarte-table-scroll">
            <table class="alacarte-table">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e('Plugin Name', 'a-la-carte'); ?></th>
                        <th scope="col"><?php esc_html_e('Status', 'a-la-carte'); ?></th>
                        <th scope="col"><?php esc_html_e('Version', 'a-la-carte'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_plugins as $path => $plugin) : ?>
                        <?php $is_active = is_plugin_active($path); ?>
                        <tr>
                            <td><span class="alacarte-table__name"><?php echo esc_html($plugin['Name']); ?></span></td>
                            <td>
                                <span class="alacarte-pill alacarte-pill--upper <?php echo $is_active ? 'alacarte-pill--success' : 'alacarte-pill--neutral'; ?>">
                                    <?php echo $is_active ? esc_html__('Active', 'a-la-carte') : esc_html__('Inactive', 'a-la-carte'); ?>
                                </span>
                            </td>
                            <td><span class="alacarte-mono alacarte-text-muted"><?php echo esc_html($plugin['Version']); ?></span></td>
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
            return __('Unknown', 'a-la-carte');
        }

        return size_format($bytes, 2);
    }
}
