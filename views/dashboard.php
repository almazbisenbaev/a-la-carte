<?php
/**
 * PowerTools dashboard
 *
 * @package PowerTools
 */

use PowerTools\Admin\Tool_Manager;
use PowerTools\Admin\UI;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$available_tools = Tool_Manager::get_available_tools();

UI::page_open(array(
    'title'       => __('PowerTools', 'powertools'),
    'description' => __('Modern developer utilities to maximize your WordPress productivity.', 'powertools'),
    'logo'        => POWERTOOLS_PLUGIN_URL . 'assets/images/logo-icon.png',
));

Tool_Manager::render_toggle_notice();
?>

<div class="pt-tools">
    <?php foreach ($available_tools as $tool_id => $tool) : ?>
        <?php
        $is_active = Tool_Manager::is_tool_active($tool_id);
        $page_url  = Tool_Manager::get_page_url($tool_id);
        ?>
        <div class="pt-tool-card">
            <div class="pt-tool-card__header">
                <span class="pt-tool-card__icon">
                    <?php UI::dashicon($tool['icon']); ?>
                </span>

                <?php if ($is_active) : ?>
                    <a class="pt-tool-card__title" href="<?php echo esc_url($page_url); ?>">
                        <?php echo esc_html($tool['name']); ?>
                    </a>
                <?php else : ?>
                    <span class="pt-tool-card__title"><?php echo esc_html($tool['name']); ?></span>
                <?php endif; ?>

                <?php
                UI::toggle_form(array(
                    'action'       => 'powertools_toggle_tool',
                    'nonce_action' => 'powertools_toggle_tool',
                    'nonce_name'   => 'powertools_toggle_nonce',
                    'fields'       => array(
                        'tool_id'   => $tool_id,
                        'is_active' => $is_active ? '0' : '1',
                    ),
                    'checked'      => $is_active,
                    /* translators: %s: tool name */
                    'label'        => sprintf(__('Activate %s', 'powertools'), $tool['name']),
                ));
                ?>
            </div>

            <p class="pt-tool-card__desc">
                <?php echo esc_html($tool['description']); ?>
            </p>

            <div class="pt-tool-card__footer">
                <?php if ($is_active) : ?>
                    <span class="pt-pill pt-pill--success"><?php esc_html_e('Active', 'powertools'); ?></span>
                    <a class="pt-btn pt-btn--secondary" href="<?php echo esc_url($page_url); ?>">
                        <?php esc_html_e('Configure', 'powertools'); ?>
                        <?php UI::dashicon('dashicons-arrow-right-alt2'); ?>
                    </a>
                <?php else : ?>
                    <span class="pt-pill pt-pill--neutral"><?php esc_html_e('Inactive', 'powertools'); ?></span>
                    <span class="pt-btn pt-btn--secondary is-disabled" aria-disabled="true">
                        <?php esc_html_e('Configure', 'powertools'); ?>
                        <?php UI::dashicon('dashicons-arrow-right-alt2'); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<footer class="pt-footer">
    <p>
        <?php
        printf(
            /* translators: %s: Author name with link */
            esc_html__('Developed by %s', 'powertools'),
            '<a target="_blank" rel="noopener" href="https://helloalmaz.com">Almaz Bissenbayev</a>'
        );
        ?>
        &bull;
        <?php
        printf(
            /* translators: %s: Github repository URL */
            esc_html__('View on %s', 'powertools'),
            '<a target="_blank" rel="noopener" href="https://github.com/almazbisenbaev/powertools">GitHub</a>'
        );
        ?>
    </p>
</footer>

<?php UI::page_close(); ?>
