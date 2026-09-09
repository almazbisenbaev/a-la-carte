<?php
/**
 * A La Carte dashboard
 *
 * @package ALaCarte
 */

use ALaCarte\Admin\Tool_Manager;
use ALaCarte\Admin\UI;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$alacarte_available_tools = Tool_Manager::get_available_tools();

UI::page_open(array(
    'title'       => __('A La Carte', 'a-la-carte'),
    'description' => __('Modern developer utilities to maximize your WordPress productivity.', 'a-la-carte'),
    'logo'        => ALACARTE_PLUGIN_URL . 'assets/images/logo-icon.png',
));

Tool_Manager::render_toggle_notice();
?>

<div class="alacarte-tools">
    <?php foreach ($alacarte_available_tools as $alacarte_tool_id => $alacarte_tool) : ?>
        <?php
        $alacarte_is_active = Tool_Manager::is_tool_active($alacarte_tool_id);
        $alacarte_page_url  = Tool_Manager::get_page_url($alacarte_tool_id);
        ?>
        <div class="alacarte-tool-card">
            <div class="alacarte-tool-card__header">
                <span class="alacarte-tool-card__icon">
                    <?php UI::dashicon($alacarte_tool['icon']); ?>
                </span>

                <?php if ($alacarte_is_active) : ?>
                    <a class="alacarte-tool-card__title" href="<?php echo esc_url($alacarte_page_url); ?>">
                        <?php echo esc_html($alacarte_tool['name']); ?>
                    </a>
                <?php else : ?>
                    <span class="alacarte-tool-card__title"><?php echo esc_html($alacarte_tool['name']); ?></span>
                <?php endif; ?>

                <?php
                UI::toggle_form(array(
                    'action'       => 'alacarte_toggle_tool',
                    'nonce_action' => 'alacarte_toggle_tool',
                    'nonce_name'   => 'alacarte_toggle_nonce',
                    'fields'       => array(
                        'tool_id'   => $alacarte_tool_id,
                        'is_active' => $alacarte_is_active ? '0' : '1',
                    ),
                    'checked'      => $alacarte_is_active,
                    /* translators: %s: tool name */
                    'label'        => sprintf(__('Activate %s', 'a-la-carte'), $alacarte_tool['name']),
                ));
                ?>
            </div>

            <p class="alacarte-tool-card__desc">
                <?php echo esc_html($alacarte_tool['description']); ?>
            </p>

            <div class="alacarte-tool-card__footer">
                <?php if ($alacarte_is_active) : ?>
                    <span class="alacarte-pill alacarte-pill--success"><?php esc_html_e('Active', 'a-la-carte'); ?></span>
                    <a class="alacarte-btn alacarte-btn--secondary" href="<?php echo esc_url($alacarte_page_url); ?>">
                        <?php esc_html_e('Configure', 'a-la-carte'); ?>
                        <?php UI::dashicon('dashicons-arrow-right-alt2'); ?>
                    </a>
                <?php else : ?>
                    <span class="alacarte-pill alacarte-pill--neutral"><?php esc_html_e('Inactive', 'a-la-carte'); ?></span>
                    <span class="alacarte-btn alacarte-btn--secondary is-disabled" aria-disabled="true">
                        <?php esc_html_e('Configure', 'a-la-carte'); ?>
                        <?php UI::dashicon('dashicons-arrow-right-alt2'); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<footer class="alacarte-footer">
    <p>
        <?php
        printf(
            /* translators: %s: Author name with link */
            esc_html__('Developed by %s', 'a-la-carte'),
            '<a target="_blank" rel="noopener" href="https://helloalmaz.com">Almaz Bissenbayev</a>'
        );
        ?>
        &bull;
        <?php
        printf(
            /* translators: %s: Github repository URL */
            esc_html__('View on %s', 'a-la-carte'),
            '<a target="_blank" rel="noopener" href="https://github.com/almazbisenbaev/a-la-carte">GitHub</a>'
        );
        ?>
    </p>
</footer>

<?php UI::page_close(); ?>
