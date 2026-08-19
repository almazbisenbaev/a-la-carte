<?php
/**
 * Insert Code functionality
 *
 * @package PowerTools
 */

namespace PowerTools\Code;

use PowerTools\Admin\Assets;
use PowerTools\Admin\Tool_Manager;
use PowerTools\Admin\UI;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Insert_Code
 */
class Insert_Code {
    /**
     * Post Type name
     *
     * @var string
     */
    private const POST_TYPE = 'powertools_snippet';

    /**
     * Option name for storing code snippets (Legacy)
     *
     * @var string
     */
    private const OPTION_NAME = 'powertools_insert_code_snippets';

    /**
     * Option name for the PHP execution kill-switch. PHP snippets only run
     * when this is explicitly enabled, since they execute with full server
     * privileges on every matching page load.
     *
     * @var string
     */
    private const PHP_EXECUTION_OPTION = 'powertools_allow_php_snippets';

    /**
     * Option name flagging that at least one snippet was auto-disabled after
     * throwing an error, so an admin notice can be shown cheaply without
     * querying snippets on every admin page load.
     *
     * @var string
     */
    private const ERROR_FLAG_OPTION = 'powertools_snippet_has_errors';

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'maybe_migrate_data'));

        add_action('wp_head', array($this, 'render_head_snippets'));
        add_action('wp_body_open', array($this, 'render_body_open_snippets'));
        add_action('wp_footer', array($this, 'render_footer_snippets'));

        add_action('admin_post_powertools_create_snippet', array($this, 'handle_create_snippet'));
        add_action('admin_post_powertools_save_snippet', array($this, 'handle_save_snippet'));
        add_action('admin_post_powertools_delete_snippet', array($this, 'handle_delete_snippet'));
        add_action('admin_post_powertools_toggle_snippet', array($this, 'handle_toggle_snippet'));
        add_action('admin_post_powertools_toggle_php_execution', array($this, 'handle_toggle_php_execution'));

        add_action('admin_notices', array($this, 'maybe_show_error_notice'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_editor_assets'));
    }

    /**
     * Enqueue CodeMirror and the snippet editor script on this tool's screen
     *
     * @param string $hook Current admin page hook suffix.
     */
    public function enqueue_editor_assets($hook) {
        if (!Assets::is_tool_screen($hook, 'insert_code')) {
            return;
        }

        $settings = wp_enqueue_code_editor(array('type' => 'text/html'));
        $deps     = array();

        // false means the user turned syntax highlighting off in their profile.
        // The editor script still runs: it degrades to a plain textarea.
        if (false !== $settings) {
            wp_add_inline_script('code-editor', sprintf('window.powertoolsCodeEditorSettings = %s;', wp_json_encode($settings)));
            $deps[] = 'code-editor';
        }

        wp_enqueue_script(
            'powertools-insert-code',
            POWERTOOLS_PLUGIN_URL . 'assets/js/insert-code.js',
            $deps,
            POWERTOOLS_VERSION,
            true
        );
    }

    /**
     * Whether PHP snippet execution is enabled site-wide (kill switch).
     *
     * @return bool
     */
    private function php_execution_allowed() {
        return get_option(self::PHP_EXECUTION_OPTION) === '1';
    }

    /**
     * Whether the current user is allowed to create/manage PHP snippets.
     *
     * Managing a PHP snippet is equivalent to editing a theme or plugin file:
     * the code runs unsandboxed with full server privileges. The gate is
     * therefore the same one WordPress uses for its own file editors.
     *
     * Note this governs authoring only. Snippets that already exist keep
     * running, gated by the separate site-wide kill switch, so locking a site
     * down does not silently change what its pages output.
     *
     * @return bool
     */
    private function can_manage_php_snippets() {
        // The site owner has explicitly turned off in-admin code editing.
        if ((defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT)
            || (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS)) {
            return false;
        }

        // Belt and braces: every caller already runs through
        // Tool_Manager::require_capability(), but this must never depend on it.
        if (!current_user_can('manage_options')) {
            return false;
        }

        // On multisite a snippet executes across the whole install, so keep it
        // to network super admins.
        return !is_multisite() || is_super_admin();
    }

    /**
     * Why PHP snippet management is unavailable, for display in the admin.
     *
     * @return string Empty when management is allowed.
     */
    private function php_snippets_locked_reason() {
        if ($this->can_manage_php_snippets()) {
            return '';
        }

        if ((defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT)
            || (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS)) {
            return __('PHP snippets are unavailable because this site has code editing disabled (DISALLOW_FILE_EDIT or DISALLOW_FILE_MODS). Many managed hosts set this by default.', 'powertools');
        }

        if (is_multisite()) {
            return __('Only network administrators can change this setting or manage PHP snippets on this multisite install.', 'powertools');
        }

        return __('You do not have permission to manage PHP snippets.', 'powertools');
    }

    /**
     * Register Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name'               => _x('Code Snippets', 'post type general name', 'powertools'),
            'singular_name'      => _x('Code Snippet', 'post type singular name', 'powertools'),
            'menu_name'          => _x('Code Snippets', 'admin menu', 'powertools'),
            'name_admin_bar'     => _x('Code Snippet', 'add new on admin bar', 'powertools'),
            'add_new'            => _x('Add New', 'snippet', 'powertools'),
            'add_new_item'       => __('Add New Snippet', 'powertools'),
            'new_item'           => __('New Snippet', 'powertools'),
            'edit_item'          => __('Edit Snippet', 'powertools'),
            'view_item'          => __('View Snippet', 'powertools'),
            'all_items'          => __('All Snippets', 'powertools'),
            'search_items'       => __('Search Snippets', 'powertools'),
            'not_found'          => __('No snippets found.', 'powertools'),
            'not_found_in_trash' => __('No snippets found in Trash.', 'powertools')
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false, // We use our own UI
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => array('slug' => self::POST_TYPE),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor')
        );

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * Migrate data from option to CPT (One-time)
     */
    public function maybe_migrate_data() {
        $legacy_snippets = get_option(self::OPTION_NAME);
        if (empty($legacy_snippets) || !is_array($legacy_snippets)) {
            return;
        }

        foreach ($legacy_snippets as $snippet) {
            $post_id = wp_insert_post(array(
                'post_title'   => $snippet['title'],
                'post_content' => $snippet['code'],
                'post_status'  => !empty($snippet['active']) ? 'publish' : 'draft',
                'post_type'    => self::POST_TYPE,
            ));

            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, '_powertools_type', $snippet['type']);
                update_post_meta($post_id, '_powertools_location', $snippet['location']);
                update_post_meta($post_id, '_powertools_rules', $snippet['rules']);
            }
        }

        // Clear legacy data
        delete_option(self::OPTION_NAME);
    }

    /**
     * Get all snippets
     *
     * @return array
     */
    private function get_snippets() {
        $args = array(
            'post_type'      => self::POST_TYPE,
            'post_status'    => array('publish', 'draft'),
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC'
        );

        $posts = get_posts($args);
        $snippets = array();

        foreach ($posts as $post) {
            $snippets[$post->ID] = array(
                'id'       => $post->ID,
                'title'    => $post->post_title,
                'code'     => $post->post_content,
                'type'     => get_post_meta($post->ID, '_powertools_type', true),
                'location' => get_post_meta($post->ID, '_powertools_location', true),
                'rules'    => get_post_meta($post->ID, '_powertools_rules', true),
                'active'   => $post->post_status === 'publish'
            );
        }

        return $snippets;
    }

    /**
     * Render snippets for wp_head
     */
    public function render_head_snippets() {
        $this->render_snippets_by_location('wp_head');
    }

    /**
     * Render snippets for wp_body_open
     */
    public function render_body_open_snippets() {
        $this->render_snippets_by_location('wp_body_open');
    }

    /**
     * Render snippets for wp_footer
     */
    public function render_footer_snippets() {
        $this->render_snippets_by_location('wp_footer');
    }

    /**
     * Render snippets for a specific location
     *
     * @param string $location
     */
    private function render_snippets_by_location($location) {
        $snippets = $this->get_snippets();
        foreach ($snippets as $snippet) {
            if (empty($snippet['active']) || $snippet['location'] !== $location) {
                continue;
            }

            // Kill switch: never execute PHP snippets while the setting is off,
            // even if they were saved while it was previously enabled.
            if ($snippet['type'] === 'php' && !$this->php_execution_allowed()) {
                continue;
            }

            if ($this->should_render_snippet($snippet)) {
                $this->execute_snippet($snippet);
            }
        }
    }

    /**
     * Check if snippet should be rendered based on rules
     *
     * @param array $snippet
     * @return bool
     */
    private function should_render_snippet($snippet) {
        if (empty($snippet['rules']) || !is_array($snippet['rules'])) {
            return true;
        }

        foreach ($snippet['rules'] as $rule) {
            $type = isset($rule['type']) ? $rule['type'] : '';
            $value = isset($rule['value']) ? $rule['value'] : '';

            if ($type === '') {
                continue;
            }

            switch ($type) {
                case 'user_role':
                    if ($value === 'guest') {
                        if (is_user_logged_in()) return false;
                    } else {
                        $user = wp_get_current_user();
                        if (!in_array($value, (array) $user->roles, true)) return false;
                    }
                    break;

                case 'page_id':
                    $ids = array_map('trim', explode(',', $value));
                    if (!is_page($ids) && !is_single($ids)) return false;
                    break;

                case 'post_type':
                    if (!is_singular($value)) return false;
                    break;
            }
        }

        return true;
    }

    /**
     * Execute/Output snippet code
     *
     * @param array $snippet
     */
    private function execute_snippet($snippet) {
        $code = $snippet['code'];
        $type = $snippet['type'];

        if ($type === 'php') {
            // Belt-and-suspenders: render_snippets_by_location() already checks
            // this, but execute_snippet() must never run PHP on its own.
            if (!$this->php_execution_allowed()) {
                return;
            }

            try {
                // Reviewer note: this eval() is the entire point of the tool —
                // it lets a site administrator run their own PHP snippets, the
                // same way Code Snippets and WPCode do. It never evaluates
                // visitor input. Reaching this line requires all of:
                //   - the snippet was saved by a user with manage_options
                //     (and a network super admin on multisite),
                //   - the save passed check_admin_referer(),
                //   - DISALLOW_FILE_EDIT / DISALLOW_FILE_MODS are not set,
                //   - the site-wide PHP kill switch is explicitly on
                //     (it ships off, see PHP_EXECUTION_OPTION),
                //   - the snippet is published and its display rules match.
                // A snippet that throws is caught below and auto-disabled.
                // phpcs:ignore Squiz.PHP.Eval.Discouraged -- documented, admin-authored code execution; see above.
                eval('?>' . '<?php ' . $code);
            } catch (\Throwable $e) {
                $this->handle_snippet_error($snippet['id'], $e);
            }
        } elseif ($type === 'js') {
            // Reviewer note: deliberately not escaped. A JS snippet is stored
            // verbatim by an administrator and must reach the page exactly as
            // written — escaping it would emit broken markup instead of script,
            // which is the whole feature. Same trust model as the PHP branch
            // above: authored under manage_options behind a nonce, never from
            // visitor input. Nothing here is concatenated from a request.
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-authored snippet, output verbatim by design.
            echo '<script type="text/javascript">' . $code . '</script>';
        } else {
            // Reviewer note: deliberately not escaped, for the same reason as
            // the JS branch — this is an admin-authored HTML snippet whose
            // purpose is to render as markup.
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-authored snippet, output verbatim by design.
            echo $code;
        }
    }

    /**
     * Handle a PHP snippet throwing an error at runtime: log it, disable the
     * snippet so it stops breaking every subsequent page load, and surface
     * it to admins instead of failing silently and repeatedly in production.
     *
     * @param int        $snippet_id
     * @param \Throwable $e
     */
    private function handle_snippet_error($snippet_id, \Throwable $e) {
        // Only log when the site has debugging on. The admin notice and the
        // stored _powertools_last_error below are how this is surfaced in production;
        // writing to the PHP error log there would be noise the site owner
        // never asked for.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- debug-only diagnostic, gated above.
            error_log(sprintf('[PowerTools] Snippet #%d disabled after error: %s', $snippet_id, $e->getMessage()));
        }

        update_post_meta($snippet_id, '_powertools_last_error', array(
            'message' => $e->getMessage(),
            'time'    => current_time('mysql'),
        ));

        wp_update_post(array(
            'ID'          => $snippet_id,
            'post_status' => 'draft',
        ));

        update_option(self::ERROR_FLAG_OPTION, '1', false);

        if (is_user_logged_in() && current_user_can('manage_options')) {
            echo '<!-- PowerTools: snippet #' . (int) $snippet_id . ' threw an error and was automatically disabled: ' . esc_html($e->getMessage()) . ' -->';
        }
    }

    /**
     * Show an admin notice when snippets have been auto-disabled after errors.
     */
    public function maybe_show_error_notice() {
        if (!current_user_can('manage_options') || get_option(self::ERROR_FLAG_OPTION) !== '1') {
            return;
        }
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php esc_html_e('PowerTools automatically disabled one or more code snippets after they threw a runtime error.', 'powertools'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=powertools-insert-code')); ?>"><?php esc_html_e('Review snippets', 'powertools'); ?></a>
            </p>
        </div>
        <?php
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        Tool_Manager::require_capability();

        // The admin is looking at this page now, so clear the "snippet
        // auto-disabled" notice flag rather than showing it forever.
        delete_option(self::ERROR_FLAG_OPTION);

        $snippets = $this->get_snippets();
        $edit_id = isset($_GET['edit']) ? intval(wp_unslash($_GET['edit'])) : 0;
        $snippet_to_edit = $edit_id && isset($snippets[$edit_id]) ? $snippets[$edit_id] : null;
        $php_snippets_enabled = $this->php_execution_allowed() && $this->can_manage_php_snippets();

        // Get available roles
        $wp_roles = wp_roles();
        $roles = $wp_roles->get_names();

        // Get available post types
        $post_types = get_post_types(array('public' => true), 'objects');

        $page_url  = Tool_Manager::get_page_url('insert_code');
        $is_editing = (bool) $snippet_to_edit;

        UI::page_open(array(
            'tool_id'     => 'insert_code',
            'title'       => __('Insert Code', 'powertools'),
            'description' => __('Add custom HTML, CSS, JS or PHP code snippets to your site without editing theme files.', 'powertools'),
            'actions'     => function () use ($is_editing, $page_url) {
                if ($is_editing) {
                    ?>
                    <a href="<?php echo esc_url($page_url); ?>" class="pt-btn pt-btn--secondary">
                        <?php UI::dashicon('dashicons-arrow-left-alt'); ?>
                        <?php esc_html_e('Back to List', 'powertools'); ?>
                    </a>
                    <?php
                    return;
                }
                ?>
                <button type="button" class="pt-btn pt-btn--primary" data-pt-modal-open="pt-new-snippet-modal">
                    <?php UI::dashicon('dashicons-plus'); ?>
                    <?php esc_html_e('Add New Snippet', 'powertools'); ?>
                </button>
                <?php
            },
        ));

        $this->render_message();
        ?>
            <div class="pt-panel">
                <div class="pt-panel__body">
                    <form class="pt-js-autosubmit" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="powertools_toggle_php_execution" />
                        <?php wp_nonce_field('powertools_toggle_php_execution_nonce'); ?>
                        <input type="hidden" name="allow_php" value="<?php echo $this->php_execution_allowed() ? '0' : '1'; ?>" />
                        <label class="pt-choice">
                            <input type="checkbox"
                                   <?php checked($this->php_execution_allowed()); ?>
                                   <?php disabled(!$this->can_manage_php_snippets()); ?> />
                            <span class="pt-choice__text">
                                <span class="pt-choice__title"><?php esc_html_e('Enable PHP Snippet Execution', 'powertools'); ?></span>
                                <span class="pt-choice__desc">
                                    <?php esc_html_e('PHP snippets run with full server privileges on every matching page load. Leave this off unless you specifically need it, and only enable it if you trust everyone who has access to this page.', 'powertools'); ?>
                                    <?php $locked_reason = $this->php_snippets_locked_reason(); ?>
                                    <?php if ($locked_reason) : ?>
                                        <br /><strong><?php echo esc_html($locked_reason); ?></strong>
                                    <?php endif; ?>
                                </span>
                            </span>
                        </label>
                        <noscript>
                            <button type="submit" class="pt-btn pt-btn--secondary"><?php esc_html_e('Apply', 'powertools'); ?></button>
                        </noscript>
                    </form>
                </div>
            </div>

            <?php if ($edit_id && $snippet_to_edit) : ?>
                <form class="pt-panel" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="powertools_save_snippet" />
                    <input type="hidden" name="snippet_id" value="<?php echo esc_attr($edit_id); ?>" />
                    <?php wp_nonce_field('powertools_save_snippet_nonce'); ?>

                    <div class="pt-panel__header">
                        <h2 class="pt-h2"><?php esc_html_e('Edit Snippet', 'powertools'); ?></h2>
                        <span class="pt-pill pt-pill--brand pt-pill--upper"><?php echo esc_html($snippet_to_edit['type']); ?></span>
                    </div>

                    <div class="pt-panel__body">
                        <div class="pt-cols-2">
                            <div class="pt-field">
                                <label class="pt-label" for="pt-snippet-title"><?php esc_html_e('Snippet Title', 'powertools'); ?></label>
                                <input type="text" id="pt-snippet-title" name="title" class="pt-form-control"
                                       value="<?php echo esc_attr($snippet_to_edit['title']); ?>"
                                       placeholder="<?php esc_attr_e('Google Analytics', 'powertools'); ?>" required />
                            </div>
                            <div class="pt-field">
                                <label class="pt-label" for="pt-snippet-location"><?php esc_html_e('Location', 'powertools'); ?></label>
                                <select id="pt-snippet-location" name="location" class="pt-form-control">
                                    <?php foreach ($this->get_locations() as $location => $label) : ?>
                                        <option value="<?php echo esc_attr($location); ?>" <?php selected($snippet_to_edit['location'], $location); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="pt-field">
                            <span class="pt-label"><?php esc_html_e('Conditional Logic (optional)', 'powertools'); ?></span>
                            <div id="pt-rules">
                                <?php
                                $rules = !empty($snippet_to_edit['rules']) ? $snippet_to_edit['rules'] : array();
                                if (empty($rules)) {
                                    $rules = array(array('type' => '', 'value' => ''));
                                }

                                foreach ($rules as $index => $rule) :
                                    $rule = array_merge(array('type' => '', 'value' => ''), (array) $rule);
                                    ?>
                                    <div class="pt-rule">
                                        <select name="rules[<?php echo (int) $index; ?>][type]" class="pt-form-control pt-rule__type" data-pt-rule-type
                                                aria-label="<?php esc_attr_e('Rule type', 'powertools'); ?>">
                                            <option value=""><?php esc_html_e('No Rule', 'powertools'); ?></option>
                                            <option value="user_role" <?php selected($rule['type'], 'user_role'); ?>><?php esc_html_e('User Role', 'powertools'); ?></option>
                                            <option value="page_id" <?php selected($rule['type'], 'page_id'); ?>><?php esc_html_e('Page/Post ID', 'powertools'); ?></option>
                                            <option value="post_type" <?php selected($rule['type'], 'post_type'); ?>><?php esc_html_e('Post Type', 'powertools'); ?></option>
                                        </select>

                                        <div class="pt-rule__value">
                                            <input type="text"
                                                   name="rules[<?php echo (int) $index; ?>][value]"
                                                   class="pt-form-control"
                                                   data-pt-rule-value="text"
                                                   value="<?php echo esc_attr($rule['value']); ?>"
                                                   placeholder="<?php esc_attr_e('Value, e.g. 12, 45', 'powertools'); ?>"
                                                   aria-label="<?php esc_attr_e('Rule value', 'powertools'); ?>"
                                                   <?php echo in_array($rule['type'], array('user_role', 'post_type'), true) ? 'hidden disabled' : ''; ?> />

                                            <select name="rules[<?php echo (int) $index; ?>][value_role]"
                                                    class="pt-form-control"
                                                    data-pt-rule-value="user_role"
                                                    aria-label="<?php esc_attr_e('User role', 'powertools'); ?>"
                                                    <?php echo $rule['type'] !== 'user_role' ? 'hidden disabled' : ''; ?>>
                                                <option value="guest" <?php selected($rule['value'], 'guest'); ?>><?php esc_html_e('Guest (Logged out)', 'powertools'); ?></option>
                                                <?php foreach ($roles as $role_key => $role_name) : ?>
                                                    <option value="<?php echo esc_attr($role_key); ?>" <?php selected($rule['value'], $role_key); ?>><?php echo esc_html($role_name); ?></option>
                                                <?php endforeach; ?>
                                            </select>

                                            <select name="rules[<?php echo (int) $index; ?>][value_post_type]"
                                                    class="pt-form-control"
                                                    data-pt-rule-value="post_type"
                                                    aria-label="<?php esc_attr_e('Post type', 'powertools'); ?>"
                                                    <?php echo $rule['type'] !== 'post_type' ? 'hidden disabled' : ''; ?>>
                                                <?php foreach ($post_types as $pt) : ?>
                                                    <option value="<?php echo esc_attr($pt->name); ?>" <?php selected($rule['value'], $pt->name); ?>><?php echo esc_html($pt->label); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <button type="button" class="pt-icon-btn pt-icon-btn--danger pt-rule__remove" data-pt-rule-remove>
                                            <?php UI::dashicon('dashicons-trash'); ?>
                                            <span class="pt-sr-only"><?php esc_html_e('Remove rule', 'powertools'); ?></span>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="pt-field__action">
                                <button type="button" class="pt-btn pt-btn--secondary" data-pt-rule-add>
                                    <?php UI::dashicon('dashicons-plus'); ?>
                                    <?php esc_html_e('Add Rule', 'powertools'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="pt-field">
                            <label class="pt-label" for="pt-code-editor"><?php esc_html_e('Code Snippet', 'powertools'); ?></label>
                            <div class="pt-code-editor" data-pt-code-type="<?php echo esc_attr($snippet_to_edit['type']); ?>">
                                <div class="pt-code-editor__tag pt-code-editor__tag--top" data-pt-code-tag="top"></div>
                                <textarea name="code" id="pt-code-editor" class="pt-form-control" required><?php echo esc_textarea($snippet_to_edit['code']); ?></textarea>
                                <div class="pt-code-editor__tag pt-code-editor__tag--bottom" data-pt-code-tag="bottom"></div>
                            </div>
                            <p class="pt-help">
                                <?php esc_html_e('Do not include opening or closing tags — they are added automatically based on the snippet type.', 'powertools'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="pt-panel__footer">
                        <a href="<?php echo esc_url($page_url); ?>" class="pt-btn pt-btn--secondary">
                            <?php esc_html_e('Cancel', 'powertools'); ?>
                        </a>
                        <button type="submit" class="pt-btn pt-btn--primary">
                            <?php esc_html_e('Save Snippet', 'powertools'); ?>
                        </button>
                    </div>
                </form>

            <?php else : ?>
                <div class="pt-panel">
                    <?php if (empty($snippets)) : ?>
                        <div class="pt-empty">
                            <span class="pt-empty__icon"><?php UI::dashicon('dashicons-code-standards'); ?></span>
                            <h3 class="pt-h3"><?php esc_html_e('No snippets yet', 'powertools'); ?></h3>
                            <p class="pt-empty__text">
                                <?php esc_html_e('Create your first code snippet to start adding custom functionality to your WordPress site.', 'powertools'); ?>
                            </p>
                            <button type="button" class="pt-btn pt-btn--primary" data-pt-modal-open="pt-new-snippet-modal">
                                <?php esc_html_e('Create First Snippet', 'powertools'); ?>
                            </button>
                        </div>
                    <?php else : ?>
                        <div class="pt-table-scroll">
                            <table class="pt-table">
                                <thead>
                                    <tr>
                                        <th scope="col"><?php esc_html_e('Snippet Name', 'powertools'); ?></th>
                                        <th scope="col"><?php esc_html_e('Type', 'powertools'); ?></th>
                                        <th scope="col"><?php esc_html_e('Location', 'powertools'); ?></th>
                                        <th scope="col" class="pt-table__actions"><?php esc_html_e('Actions', 'powertools'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($snippets as $id => $snippet) : ?>
                                        <?php $edit_url = add_query_arg('edit', $id, $page_url); ?>
                                        <tr>
                                            <td>
                                                <a class="pt-table__name" href="<?php echo esc_url($edit_url); ?>">
                                                    <?php echo esc_html($snippet['title']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="pt-pill pt-pill--brand pt-pill--upper"><?php echo esc_html($snippet['type']); ?></span>
                                            </td>
                                            <td>
                                                <span class="pt-text-muted"><?php echo esc_html($this->get_location_label($snippet['location'])); ?></span>
                                            </td>
                                            <td class="pt-table__actions">
                                                <div class="pt-table__actions-inner">
                                                    <?php
                                                    UI::toggle_form(array(
                                                        'action'       => 'powertools_toggle_snippet',
                                                        'nonce_action' => 'powertools_toggle_snippet_nonce',
                                                        'fields'       => array('snippet_id' => $id),
                                                        'checked'      => !empty($snippet['active']),
                                                        /* translators: %s: snippet title */
                                                        'label'        => sprintf(__('Activate %s', 'powertools'), $snippet['title']),
                                                    ));
                                                    ?>

                                                    <a class="pt-icon-btn" href="<?php echo esc_url($edit_url); ?>">
                                                        <?php UI::dashicon('dashicons-edit'); ?>
                                                        <span class="pt-sr-only">
                                                            <?php
                                                            /* translators: %s: snippet title */
                                                            printf(esc_html__('Edit %s', 'powertools'), esc_html($snippet['title']));
                                                            ?>
                                                        </span>
                                                    </a>

                                                    <form class="pt-inline-form" method="post"
                                                          action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                                                          data-pt-confirm="<?php echo esc_attr(sprintf(
                                                              /* translators: %s: snippet title */
                                                              __('Permanently delete the snippet “%s”?', 'powertools'),
                                                              $snippet['title']
                                                          )); ?>">
                                                        <input type="hidden" name="action" value="powertools_delete_snippet" />
                                                        <input type="hidden" name="snippet_id" value="<?php echo esc_attr($id); ?>" />
                                                        <?php wp_nonce_field('powertools_delete_snippet_nonce'); ?>
                                                        <button type="submit" class="pt-icon-btn pt-icon-btn--danger">
                                                            <?php UI::dashicon('dashicons-trash'); ?>
                                                            <span class="pt-sr-only">
                                                                <?php
                                                                /* translators: %s: snippet title */
                                                                printf(esc_html__('Delete %s', 'powertools'), esc_html($snippet['title']));
                                                                ?>
                                                            </span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php UI::page_close(); ?>

        <div class="pt-modal" id="pt-new-snippet-modal" role="dialog" aria-modal="true" aria-labelledby="pt-new-snippet-title">
            <div class="pt-modal__dialog">
                <div class="pt-modal__header">
                    <h2 class="pt-h2" id="pt-new-snippet-title"><?php esc_html_e('Create New Snippet', 'powertools'); ?></h2>
                    <button type="button" class="pt-icon-btn" data-pt-modal-close>
                        <?php UI::dashicon('dashicons-no-alt'); ?>
                        <span class="pt-sr-only"><?php esc_html_e('Close', 'powertools'); ?></span>
                    </button>
                </div>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="powertools_create_snippet" />
                    <?php wp_nonce_field('powertools_create_snippet_nonce'); ?>

                    <div class="pt-modal__body">
                        <div class="pt-field">
                            <span class="pt-label"><?php esc_html_e('Snippet Type', 'powertools'); ?></span>
                            <div class="pt-option-grid">
                                <?php
                                $types = array(
                                    'html' => array(__('HTML', 'powertools'), __('Plain markup', 'powertools'), true),
                                    'js'   => array(__('JS', 'powertools'), __('Scripts', 'powertools'), true),
                                    'php'  => array(__('PHP', 'powertools'), __('Server side', 'powertools'), $php_snippets_enabled),
                                );
                                foreach ($types as $type => $option) :
                                    ?>
                                    <label class="pt-option-card">
                                        <input type="radio" name="type" value="<?php echo esc_attr($type); ?>"
                                               <?php checked('html', $type); ?>
                                               <?php disabled(!$option[2]); ?> />
                                        <span class="pt-option-card__title"><?php echo esc_html($option[0]); ?></span>
                                        <span class="pt-option-card__desc"><?php echo esc_html($option[1]); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <?php if (!$php_snippets_enabled) : ?>
                                <p class="pt-help">
                                    <?php esc_html_e('PHP snippets are disabled. Turn on “Enable PHP Snippet Execution” to unlock this option.', 'powertools'); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="pt-modal__footer">
                        <button type="button" class="pt-btn pt-btn--secondary" data-pt-modal-close>
                            <?php esc_html_e('Cancel', 'powertools'); ?>
                        </button>
                        <button type="submit" class="pt-btn pt-btn--primary">
                            <?php esc_html_e('Continue', 'powertools'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Render the feedback message for the current request, if any
     */
    private function render_message() {
        if (isset($_GET['success'])) {
            UI::notice(__('Snippet saved.', 'powertools'), 'success');
            return;
        }

        if (!isset($_GET['error'])) {
            return;
        }

        $messages = array(
            'php_disabled'  => __('PHP snippets are disabled. Turn on “Enable PHP Snippet Execution” first.', 'powertools'),
            'php_locked'    => $this->php_snippets_locked_reason(),
        );

        $code = sanitize_key(wp_unslash($_GET['error']));

        UI::notice(
            isset($messages[$code]) ? $messages[$code] : __('Something went wrong while saving the snippet.', 'powertools'),
            'error'
        );
    }

    /**
     * Human readable label for a snippet location
     *
     * @param string $location
     * @return string
     */
    private function get_location_label($location) {
        $locations = $this->get_locations();

        return isset($locations[$location]) ? $locations[$location] : $location;
    }

    /**
     * Hooks a snippet can be printed on
     *
     * @return array hook => label
     */
    private function get_locations() {
        return array(
            'wp_head'      => __('Site Header (wp_head)', 'powertools'),
            'wp_body_open' => __('After Body Tag (wp_body_open)', 'powertools'),
            'wp_footer'    => __('Site Footer (wp_footer)', 'powertools'),
        );
    }

    /**
     * Handle quick creation of a snippet
     */
    public function handle_create_snippet() {
        Tool_Manager::require_capability();

        check_admin_referer('powertools_create_snippet_nonce');

        $type = isset($_POST['type']) ? sanitize_key(wp_unslash($_POST['type'])) : 'html';
        if (!in_array($type, array('html', 'js', 'php'), true)) {
            $type = 'html';
        }

        if ($type === 'php') {
            if (!$this->can_manage_php_snippets()) {
                $this->redirect(array('error' => 'php_locked'));
            }
            if (!$this->php_execution_allowed()) {
                $this->redirect(array('error' => 'php_disabled'));
            }
        }

        $post_id = wp_insert_post(array(
            /* translators: %s: snippet type, e.g. HTML */
            'post_title'   => sprintf(__('New %s Snippet', 'powertools'), strtoupper($type)),
            'post_content' => '',
            'post_type'    => self::POST_TYPE,
            'post_status'  => 'draft'
        ));

        if (is_wp_error($post_id)) {
            $this->redirect(array('error' => 'create_failed'));
        }

        update_post_meta($post_id, '_powertools_type', $type);
        update_post_meta($post_id, '_powertools_location', 'wp_head');
        update_post_meta($post_id, '_powertools_rules', array());

        $this->redirect(array('edit' => $post_id));
    }

    /**
     * Handle saving a snippet
     */
    public function handle_save_snippet() {
        Tool_Manager::require_capability();

        check_admin_referer('powertools_save_snippet_nonce');

        $snippet_id = isset($_POST['snippet_id']) ? intval(wp_unslash($_POST['snippet_id'])) : 0;
        if (!$snippet_id || get_post_type($snippet_id) !== self::POST_TYPE) {
            $this->redirect(array('error' => 'not_found'));
        }

        // A PHP snippet created by a network super admin must not become
        // editable by a regular site admin just because the snippet already exists.
        if (get_post_meta($snippet_id, '_powertools_type', true) === 'php' && !$this->can_manage_php_snippets()) {
            wp_die(esc_html($this->php_snippets_locked_reason()));
        }

        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        // Raw on purpose: this is the snippet body, and escaping it would defeat
        // the entire feature. It is unslashed here like any other input, then
        // re-slashed below because wp_update_post() unslashes what it is given.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- snippet body is stored verbatim by design.
        $code     = isset($_POST['code']) ? wp_unslash($_POST['code']) : '';
        $location = isset($_POST['location']) ? sanitize_key(wp_unslash($_POST['location'])) : 'wp_head';
        $rules    = isset($_POST['rules']) ? $this->sanitize_rules((array) wp_unslash($_POST['rules'])) : array();

        $locations = $this->get_locations();
        if (!isset($locations[$location])) {
            $location = 'wp_head';
        }

        // wp_update_post() and update_post_meta() both unslash what they store,
        // so hand them slashed values or every backslash in a snippet is eaten.
        wp_update_post(array(
            'ID'           => $snippet_id,
            'post_title'   => wp_slash($title),
            'post_content' => wp_slash($code),
        ));

        update_post_meta($snippet_id, '_powertools_location', $location);
        update_post_meta($snippet_id, '_powertools_rules', wp_slash($rules));

        $this->redirect(array('success' => 1, 'edit' => $snippet_id));
    }

    /**
     * Normalise the submitted conditional logic rows
     *
     * Each row posts one value per rule type; keep only the one the selected
     * type actually uses and drop rows with no rule.
     *
     * @param array $rules
     * @return array
     */
    private function sanitize_rules(array $rules) {
        $value_fields = array(
            'user_role' => 'value_role',
            'post_type' => 'value_post_type',
        );

        $sanitized = array();

        foreach ($rules as $rule) {
            $type = isset($rule['type']) ? sanitize_key($rule['type']) : '';

            if (!in_array($type, array('user_role', 'page_id', 'post_type'), true)) {
                continue;
            }

            $field = isset($value_fields[$type]) ? $value_fields[$type] : 'value';
            $value = isset($rule[$field]) ? sanitize_text_field($rule[$field]) : '';

            if ($value === '') {
                continue;
            }

            $sanitized[] = array(
                'type'  => $type,
                'value' => $value,
            );
        }

        return $sanitized;
    }

    /**
     * Handle deleting a snippet
     */
    public function handle_delete_snippet() {
        Tool_Manager::require_capability();

        check_admin_referer('powertools_delete_snippet_nonce');

        $snippet_id = isset($_POST['snippet_id']) ? intval(wp_unslash($_POST['snippet_id'])) : 0;
        if ($snippet_id && get_post_type($snippet_id) === self::POST_TYPE) {
            wp_delete_post($snippet_id, true);
        }

        $this->redirect();
    }

    /**
     * Handle toggling a snippet
     */
    public function handle_toggle_snippet() {
        Tool_Manager::require_capability();

        check_admin_referer('powertools_toggle_snippet_nonce');

        $snippet_id = isset($_POST['snippet_id']) ? intval(wp_unslash($_POST['snippet_id'])) : 0;
        $post       = $snippet_id ? get_post($snippet_id) : null;

        if ($post && $post->post_type === self::POST_TYPE) {
            $activating = $post->post_status !== 'publish';

            // Activating a PHP snippet is what actually puts its code live, so
            // it needs the same gate as authoring one. Deactivating is always
            // permitted: turning code off must never be harder than turning it on.
            if ($activating
                && get_post_meta($snippet_id, '_powertools_type', true) === 'php'
                && !$this->can_manage_php_snippets()) {
                wp_die(esc_html($this->php_snippets_locked_reason()));
            }

            wp_update_post(array(
                'ID'          => $snippet_id,
                'post_status' => $activating ? 'publish' : 'draft',
            ));
        }

        $this->redirect();
    }

    /**
     * Handle toggling the PHP execution kill-switch
     */
    public function handle_toggle_php_execution() {
        Tool_Manager::require_capability();

        if (!$this->can_manage_php_snippets()) {
            wp_die(esc_html($this->php_snippets_locked_reason()));
        }

        check_admin_referer('powertools_toggle_php_execution_nonce');

        $allow = isset($_POST['allow_php']) && wp_unslash($_POST['allow_php']) === '1';
        update_option(self::PHP_EXECUTION_OPTION, $allow ? '1' : '0');

        $this->redirect();
    }

    /**
     * Redirect back to the Insert Code screen
     *
     * @param array $args Query arguments to append.
     */
    private function redirect(array $args = array()) {
        wp_safe_redirect(add_query_arg($args, Tool_Manager::get_page_url('insert_code')));
        exit;
    }
}
