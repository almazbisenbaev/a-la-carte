<?php
/**
 * Shared admin markup helpers
 *
 * Every PowerTools screen renders its shell through this class so the page
 * header, notices and toggle switches stay identical across tools. Anything
 * visual belongs in assets/css/admin.css — templates should not
 * carry inline style attributes.
 *
 * @package PowerTools
 */

namespace PowerTools\Admin;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class UI
 */
final class UI {

    /**
     * Dashicon used for each notice type.
     *
     * @var array
     */
    private const NOTICE_ICONS = array(
        'success' => 'dashicons-yes-alt',
        'warning' => 'dashicons-warning',
        'error'   => 'dashicons-dismiss',
    );

    /**
     * Open a PowerTools screen: wrapper, page header and queued notices.
     *
     * @param array $args {
     *     @type string   $title       Page title.
     *     @type string   $description Short intro sentence.
     *     @type string   $tool_id     Tool key, used to look up the icon.
     *     @type string   $icon        Dashicon class, overrides the tool icon.
     *     @type string   $logo        Image URL, used instead of a dashicon.
     *     @type callable $actions     Renders buttons on the right of the header.
     * }
     */
    public static function page_open(array $args = []) {
        $args = array_merge(array(
            'title'       => '',
            'description' => '',
            'tool_id'     => '',
            'icon'        => '',
            'logo'        => '',
            'actions'     => null,
        ), $args);

        $icon = $args['icon'];
        if (!$icon && $args['tool_id']) {
            $icon = Tool_Manager::get_icon($args['tool_id']);
        }
        ?>
        <div class="pt-wrap">
            <header class="pt-page-header">
                <div class="pt-page-header__icon">
                    <?php if ($args['logo']) : ?>
                        <img src="<?php echo esc_url($args['logo']); ?>" alt="" />
                    <?php else : ?>
                        <?php self::dashicon($icon); ?>
                    <?php endif; ?>
                </div>

                <div class="pt-page-header__content">
                    <h1 class="pt-h1"><?php echo esc_html($args['title']); ?></h1>
                    <?php if ($args['description']) : ?>
                        <p class="pt-lead"><?php echo esc_html($args['description']); ?></p>
                    <?php endif; ?>
                </div>

                <?php if (is_callable($args['actions'])) : ?>
                    <div class="pt-page-header__actions">
                        <?php call_user_func($args['actions']); ?>
                    </div>
                <?php endif; ?>
            </header>

            <?php settings_errors('powertools_messages'); ?>
        <?php
    }

    /**
     * Close a PowerTools screen.
     */
    public static function page_close() {
        echo '</div>';
    }

    /**
     * Render an inline notice.
     *
     * @param string $message Already-translated message.
     * @param string $type    success|warning|error.
     */
    public static function notice($message, $type = 'success') {
        $type = isset(self::NOTICE_ICONS[$type]) ? $type : 'success';
        ?>
        <div class="pt-notice pt-notice--<?php echo esc_attr($type); ?>">
            <?php self::dashicon(self::NOTICE_ICONS[$type]); ?>
            <span><?php echo esc_html($message); ?></span>
        </div>
        <?php
    }

    /**
     * Render a decorative dashicon.
     *
     * @param string $icon    Dashicon class, e.g. dashicons-hammer.
     * @param string $classes Extra classes.
     */
    public static function dashicon($icon, $classes = '') {
        if (!$icon) {
            return;
        }

        printf(
            '<span class="dashicons %s" aria-hidden="true"></span>',
            esc_attr(trim($icon . ' ' . $classes))
        );
    }

    /**
     * Render a self-submitting toggle switch inside its own form.
     *
     * The checkbox itself is unnamed: the value being submitted lives in the
     * hidden fields, so the toggle still works when JavaScript is unavailable
     * through the noscript button.
     *
     * @param array $args {
     *     @type string $action       admin-post action name.
     *     @type string $nonce_action Nonce action.
     *     @type string $nonce_name   Nonce field name.
     *     @type array  $fields       Hidden fields, name => value.
     *     @type bool   $checked      Current state.
     *     @type bool   $disabled     Whether the toggle is locked.
     *     @type string $label        Accessible label for the switch.
     * }
     */
    public static function toggle_form(array $args) {
        $args = array_merge(array(
            'action'       => '',
            'nonce_action' => '',
            'nonce_name'   => '_wpnonce',
            'fields'       => array(),
            'checked'      => false,
            'disabled'     => false,
            'label'        => '',
        ), $args);
        ?>
        <form class="pt-inline-form pt-js-autosubmit" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="<?php echo esc_attr($args['action']); ?>" />
            <?php wp_nonce_field($args['nonce_action'], $args['nonce_name']); ?>
            <?php foreach ($args['fields'] as $name => $value) : ?>
                <input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" />
            <?php endforeach; ?>

            <label class="pt-switch">
                <input type="checkbox"
                       aria-label="<?php echo esc_attr($args['label']); ?>"
                       <?php checked($args['checked']); ?>
                       <?php disabled($args['disabled']); ?> />
                <span class="pt-switch__track"></span>
            </label>

            <noscript>
                <button type="submit" class="pt-btn pt-btn--secondary"><?php esc_html_e('Apply', 'powertools'); ?></button>
            </noscript>
        </form>
        <?php
    }
}
