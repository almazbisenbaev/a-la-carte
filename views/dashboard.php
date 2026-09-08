<?php
/**
 * Toolwright dashboard
 *
 * @package Toolwright
 */

use Toolwright\Admin\Tool_Manager;
use Toolwright\Admin\UI;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$toolwright_available_tools = Tool_Manager::get_available_tools();

UI::page_open(array(
    'title'       => __('Toolwright', 'toolwright'),
    'description' => __('Modern developer utilities to maximize your WordPress productivity.', 'toolwright'),
    'logo'        => TOOLWRIGHT_PLUGIN_URL . 'assets/images/logo-icon.png',
));

Tool_Manager::render_toggle_notice();
?>

<div class="ptools-tools">
    <?php foreach ($toolwright_available_tools as $toolwright_tool_id => $toolwright_tool) : ?>
        <?php
        $toolwright_is_active = Tool_Manager::is_tool_active($toolwright_tool_id);
        $toolwright_page_url  = Tool_Manager::get_page_url($toolwright_tool_id);
        ?>
        <div class="ptools-tool-card">
            <div class="ptools-tool-card__header">
                <span class="ptools-tool-card__icon">
                    <?php UI::dashicon($toolwright_tool['icon']); ?>
                </span>

                <?php if ($toolwright_is_active) : ?>
                    <a class="ptools-tool-card__title" href="<?php echo esc_url($toolwright_page_url); ?>">
                        <?php echo esc_html($toolwright_tool['name']); ?>
                    </a>
                <?php else : ?>
                    <span class="ptools-tool-card__title"><?php echo esc_html($toolwright_tool['name']); ?></span>
                <?php endif; ?>

                <?php
                UI::toggle_form(array(
                    'action'       => 'toolwright_toggle_tool',
                    'nonce_action' => 'toolwright_toggle_tool',
                    'nonce_name'   => 'toolwright_toggle_nonce',
                    'fields'       => array(
                        'tool_id'   => $toolwright_tool_id,
                        'is_active' => $toolwright_is_active ? '0' : '1',
                    ),
                    'checked'      => $toolwright_is_active,
                    /* translators: %s: tool name */
                    'label'        => sprintf(__('Activate %s', 'toolwright'), $toolwright_tool['name']),
                ));
                ?>
            </div>

            <p class="ptools-tool-card__desc">
                <?php echo esc_html($toolwright_tool['description']); ?>
            </p>

            <div class="ptools-tool-card__footer">
                <?php if ($toolwright_is_active) : ?>
                    <span class="ptools-pill ptools-pill--success"><?php esc_html_e('Active', 'toolwright'); ?></span>
                    <a class="ptools-btn ptools-btn--secondary" href="<?php echo esc_url($toolwright_page_url); ?>">
                        <?php esc_html_e('Configure', 'toolwright'); ?>
                        <?php UI::dashicon('dashicons-arrow-right-alt2'); ?>
                    </a>
                <?php else : ?>
                    <span class="ptools-pill ptools-pill--neutral"><?php esc_html_e('Inactive', 'toolwright'); ?></span>
                    <span class="ptools-btn ptools-btn--secondary is-disabled" aria-disabled="true">
                        <?php esc_html_e('Configure', 'toolwright'); ?>
                        <?php UI::dashicon('dashicons-arrow-right-alt2'); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<footer class="ptools-footer">
    <p>
        <?php
        printf(
            /* translators: %s: Author name with link */
            esc_html__('Developed by %s', 'toolwright'),
            '<a target="_blank" rel="noopener" href="https://helloalmaz.com">Almaz Bissenbayev</a>'
        );
        ?>
        &bull;
        <?php
        printf(
            /* translators: %s: Github repository URL */
            esc_html__('View on %s', 'toolwright'),
            '<a target="_blank" rel="noopener" href="https://github.com/almazbisenbaev/toolwright">GitHub</a>'
        );
        ?>
    </p>
</footer>

<?php UI::page_close(); ?>
