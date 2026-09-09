<?php
/**
 * Simple Toggle Tool abstract base class
 *
 * Base for tools whose whole configuration is a single on/off switch.
 * Subclasses describe themselves; the settings screen is rendered here so
 * every one of them looks and behaves the same.
 *
 * @package ALaCarte
 */

namespace ALaCarte\Admin;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Simple_Toggle_Tool
 */
abstract class Simple_Toggle_Tool {

    /**
     * Get the tool id as registered in Tool_Manager
     */
    abstract protected function get_tool_id(): string;

    /**
     * Get option name
     */
    abstract protected function get_option_name(): string;

    /**
     * Get nonce action name
     */
    abstract protected function get_nonce_action(): string;

    /**
     * Get page title
     */
    abstract protected function get_page_title(): string;

    /**
     * Get page description
     */
    abstract protected function get_page_description(): string;

    /**
     * Get field input name
     */
    abstract protected function get_field_name(): string;

    /**
     * Get field title
     */
    abstract protected function get_field_title(): string;

    /**
     * Get field description
     */
    abstract protected function get_field_description(): string;

    /**
     * Whether the tool is currently switched on
     */
    protected function is_enabled(): bool {
        return get_option($this->get_option_name()) === '1';
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        Tool_Manager::require_capability();

        if (isset($_POST['save']) && check_admin_referer($this->get_nonce_action())) {
            $this->save_settings();
        }

        UI::page_open(array(
            'tool_id'     => $this->get_tool_id(),
            'title'       => $this->get_page_title(),
            'description' => $this->get_page_description(),
        ));
        ?>
        <form class="alacarte-panel" method="post">
            <?php wp_nonce_field($this->get_nonce_action()); ?>

            <div class="alacarte-panel__header">
                <h2 class="alacarte-h2"><?php esc_html_e('Settings', 'a-la-carte'); ?></h2>
            </div>

            <div class="alacarte-panel__body">
                <label class="alacarte-choice alacarte-choice--card">
                    <input type="checkbox"
                           name="<?php echo esc_attr($this->get_field_name()); ?>"
                           value="1"
                           <?php checked($this->is_enabled()); ?> />
                    <span class="alacarte-choice__text">
                        <span class="alacarte-choice__title"><?php echo esc_html($this->get_field_title()); ?></span>
                        <span class="alacarte-choice__desc"><?php echo esc_html($this->get_field_description()); ?></span>
                    </span>
                </label>
            </div>

            <div class="alacarte-panel__footer">
                <button type="submit" name="save" value="1" class="alacarte-btn alacarte-btn--primary">
                    <?php esc_html_e('Save Settings', 'a-la-carte'); ?>
                </button>
            </div>
        </form>
        <?php
        UI::page_close();
    }

    /**
     * Save the tool settings
     */
    protected function save_settings() {
        check_admin_referer($this->get_nonce_action());

        update_option($this->get_option_name(), isset($_POST[$this->get_field_name()]) ? '1' : '0');

        add_settings_error(
            'alacarte_messages',
            'alacarte_message',
            __('Settings saved.', 'a-la-carte'),
            'updated'
        );
    }
}
