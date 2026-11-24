<?php
/**
 * Seitenmeister dashboard
 *
 * @package AlmazBroSiteUtilities
 */

use AlmazBroSiteUtilities\Admin\Tool_Manager;
use AlmazBroSiteUtilities\Admin\UI;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$almazbro_site_utilities_available_tools = Tool_Manager::get_available_tools();

UI::page_open(array(
    'title'       => __('Seitenmeister', 'almazbro-site-utilities'),
    'description' => __('Practical tools for everyday site maintenance.', 'almazbro-site-utilities'),
    'logo'        => ALMAZBRO_SITE_UTILITIES_PLUGIN_URL . 'assets/images/logo-icon.png',
));

Tool_Manager::render_toggle_notice();
?>

<div class="almazbro-site-utilities-tools">
    <?php foreach ($almazbro_site_utilities_available_tools as $almazbro_site_utilities_tool_id => $almazbro_site_utilities_tool) : ?>
        <?php
        $almazbro_site_utilities_is_active = Tool_Manager::is_tool_active($almazbro_site_utilities_tool_id);
        $almazbro_site_utilities_page_url  = Tool_Manager::get_page_url($almazbro_site_utilities_tool_id);
        ?>
        <div class="almazbro-site-utilities-tool-card">
            <div class="almazbro-site-utilities-tool-card__header">
                <span class="almazbro-site-utilities-tool-card__icon">
                    <?php UI::dashicon($almazbro_site_utilities_tool['icon']); ?>
                </span>

                <?php if ($almazbro_site_utilities_is_active) : ?>
                    <a class="almazbro-site-utilities-tool-card__title" href="<?php echo esc_url($almazbro_site_utilities_page_url); ?>">
                        <?php echo esc_html($almazbro_site_utilities_tool['name']); ?>
                    </a>
                <?php else : ?>
                    <span class="almazbro-site-utilities-tool-card__title"><?php echo esc_html($almazbro_site_utilities_tool['name']); ?></span>
                <?php endif; ?>

                <?php
                UI::toggle_form(array(
                    'action'       => 'almazbro_site_utilities_toggle_tool',
                    'nonce_action' => 'almazbro_site_utilities_toggle_tool',
                    'nonce_name'   => 'almazbro_site_utilities_toggle_nonce',
                    'fields'       => array(
                        'tool_id'   => $almazbro_site_utilities_tool_id,
                        'is_active' => $almazbro_site_utilities_is_active ? '0' : '1',
                    ),
                    'checked'      => $almazbro_site_utilities_is_active,
                    /* translators: %s: tool name */
                    'label'        => sprintf(__('Activate %s', 'almazbro-site-utilities'), $almazbro_site_utilities_tool['name']),
                ));
                ?>
            </div>

            <p class="almazbro-site-utilities-tool-card__desc">
                <?php echo esc_html($almazbro_site_utilities_tool['description']); ?>
            </p>

            <div class="almazbro-site-utilities-tool-card__footer">
                <?php if ($almazbro_site_utilities_is_active) : ?>
                    <span class="almazbro-site-utilities-pill almazbro-site-utilities-pill--success"><?php esc_html_e('Active', 'almazbro-site-utilities'); ?></span>
                    <a class="almazbro-site-utilities-btn almazbro-site-utilities-btn--secondary" href="<?php echo esc_url($almazbro_site_utilities_page_url); ?>">
                        <?php esc_html_e('Configure', 'almazbro-site-utilities'); ?>
                        <?php UI::dashicon('dashicons-arrow-right-alt2'); ?>
                    </a>
                <?php else : ?>
                    <span class="almazbro-site-utilities-pill almazbro-site-utilities-pill--neutral"><?php esc_html_e('Inactive', 'almazbro-site-utilities'); ?></span>
                    <span class="almazbro-site-utilities-btn almazbro-site-utilities-btn--secondary is-disabled" aria-disabled="true">
                        <?php esc_html_e('Configure', 'almazbro-site-utilities'); ?>
                        <?php UI::dashicon('dashicons-arrow-right-alt2'); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<footer class="almazbro-site-utilities-footer">
    <p>
        <?php
        printf(
            /* translators: %s: Author name with link */
            esc_html__('Developed by %s', 'almazbro-site-utilities'),
            '<a target="_blank" rel="noopener" href="https://helloalmaz.com">Almaz Bissenbayev</a>'
        );
        ?>
    </p>
</footer>

<?php UI::page_close(); ?>
