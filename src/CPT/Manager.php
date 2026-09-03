<?php
/**
 * CPT Manager functionality
 *
 * @package PowerTools
 */

namespace PowerTools\CPT;

use PowerTools\Admin\Tool_Manager;
use PowerTools\Admin\UI;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Manager
 */
class Manager {
    /**
     * Option name for storing custom post types
     *
     * @var string
     */
    private const OPTION_NAME = 'powertools_cptm_custom_post_types';

    /**
     * Feedback messages, keyed by the code passed through the redirect.
     *
     * Only codes from this list are ever rendered, so no caller can push
     * arbitrary text into the admin UI through the query string.
     *
     * @var array
     */
    private const MESSAGES = array(
        'added'       => 'success',
        'updated'     => 'success',
        'deleted'     => 'success',
        'security'    => 'error',
        'required'    => 'error',
        'slug_length' => 'error',
        'slug_taken'  => 'error',
        'not_found'   => 'error',
    );

    /**
     * Fields a custom post type can support
     *
     * @var array
     */
    private const SUPPORT_OPTIONS = array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields');

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('init', array($this, 'register_custom_post_types'));
        add_action('admin_post_powertools_cptm_add', array($this, 'handle_add_post_type'));
        add_action('admin_post_powertools_cptm_edit', array($this, 'handle_edit_post_type'));
        add_action('admin_post_powertools_cptm_delete', array($this, 'handle_delete_post_type'));
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        Tool_Manager::require_capability();

        $custom_post_types = $this->get_post_types();

        $edit_slug = isset($_GET['edit']) ? sanitize_key(wp_unslash($_GET['edit'])) : '';
        $editing   = isset($custom_post_types[$edit_slug]) ? $custom_post_types[$edit_slug] : null;
        $page_url  = Tool_Manager::get_page_url('cpt_manager');

        // Values shown in the form: the post type being edited, or defaults.
        $values = array_merge(array(
            'name'           => '',
            'singular_label' => '',
            'plural_label'   => '',
            'menu_icon'      => '',
            'public'         => true,
            'has_archive'    => true,
            'hierarchical'   => false,
            'supports'       => array('title', 'editor', 'thumbnail'),
        ), (array) $editing);

        UI::page_open(array(
            'tool_id'     => 'cpt_manager',
            'title'       => __('CPT Manager', 'powertools'),
            'description' => __('Create and manage custom post types for your WordPress site with ease.', 'powertools'),
        ));

        $this->render_message();
        ?>
        <div class="ptools-split">
            <form class="ptools-panel" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo $editing ? 'powertools_cptm_edit' : 'powertools_cptm_add'; ?>" />
                <?php
                wp_nonce_field(
                    $editing ? 'powertools_cptm_edit_nonce_action' : 'powertools_cptm_add_nonce_action',
                    $editing ? 'powertools_cptm_edit_nonce' : 'powertools_cptm_add_nonce'
                );
                ?>

                <div class="ptools-panel__header">
                    <h2 class="ptools-h2">
                        <?php echo $editing ? esc_html__('Edit Post Type', 'powertools') : esc_html__('Add New Post Type', 'powertools'); ?>
                    </h2>
                </div>

                <div class="ptools-panel__body">
                    <div class="ptools-cols-2">
                        <div class="ptools-field">
                            <label class="ptools-label" for="ptools-cptools-slug">
                                <?php esc_html_e('Post Type Slug', 'powertools'); ?>
                                <span class="ptools-required" aria-hidden="true">*</span>
                            </label>
                            <input class="ptools-form-control ptools-form-control--mono" id="ptools-cptools-slug" type="text"
                                   name="powertools_cptm_post_type_name"
                                   value="<?php echo esc_attr($values['name']); ?>"
                                   placeholder="portfolio"
                                   maxlength="20"
                                   <?php echo $editing ? 'readonly' : 'required'; ?> />
                            <p class="ptools-help"><?php esc_html_e('Lowercase and underscores only, 20 characters max.', 'powertools'); ?></p>
                        </div>

                        <div class="ptools-field">
                            <label class="ptools-label" for="ptools-cptools-icon"><?php esc_html_e('Menu Icon', 'powertools'); ?></label>
                            <input class="ptools-form-control ptools-form-control--mono" id="ptools-cptools-icon" type="text"
                                   name="powertools_cptm_menu_icon"
                                   value="<?php echo esc_attr($values['menu_icon']); ?>"
                                   placeholder="dashicons-admin-post" />
                            <p class="ptools-help">
                                <a href="https://developer.wordpress.org/resource/dashicons/" target="_blank" rel="noopener">
                                    <?php esc_html_e('Browse Dashicons', 'powertools'); ?>
                                </a>
                            </p>
                        </div>
                    </div>

                    <div class="ptools-cols-2">
                        <div class="ptools-field">
                            <label class="ptools-label" for="ptools-cptools-singular">
                                <?php esc_html_e('Singular Label', 'powertools'); ?>
                                <span class="ptools-required" aria-hidden="true">*</span>
                            </label>
                            <input class="ptools-form-control" id="ptools-cptools-singular" type="text"
                                   name="powertools_cptm_singular_label"
                                   value="<?php echo esc_attr($values['singular_label']); ?>"
                                   placeholder="<?php esc_attr_e('Project', 'powertools'); ?>"
                                   required />
                        </div>

                        <div class="ptools-field">
                            <label class="ptools-label" for="ptools-cptools-plural">
                                <?php esc_html_e('Plural Label', 'powertools'); ?>
                                <span class="ptools-required" aria-hidden="true">*</span>
                            </label>
                            <input class="ptools-form-control" id="ptools-cptools-plural" type="text"
                                   name="powertools_cptm_plural_label"
                                   value="<?php echo esc_attr($values['plural_label']); ?>"
                                   placeholder="<?php esc_attr_e('Projects', 'powertools'); ?>"
                                   required />
                        </div>
                    </div>

                    <div class="ptools-panel__section">
                        <p class="ptools-eyebrow"><?php esc_html_e('Behaviour', 'powertools'); ?></p>
                        <div class="ptools-choice-grid">
                            <?php
                            $flags = array(
                                'powertools_cptm_public'       => array(__('Public', 'powertools'), $values['public']),
                                'powertools_cptm_has_archive'  => array(__('Has Archive', 'powertools'), $values['has_archive']),
                                'powertools_cptm_hierarchical' => array(__('Hierarchical', 'powertools'), $values['hierarchical']),
                            );
                            foreach ($flags as $flag_name => $flag) :
                                ?>
                                <label class="ptools-choice ptools-choice--boxed">
                                    <input type="checkbox" name="<?php echo esc_attr($flag_name); ?>" value="1" <?php checked($flag[1]); ?> />
                                    <span class="ptools-choice__text">
                                        <span class="ptools-choice__title"><?php echo esc_html($flag[0]); ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="ptools-panel__section">
                        <p class="ptools-eyebrow"><?php esc_html_e('Supports', 'powertools'); ?></p>
                        <div class="ptools-choice-grid">
                            <?php foreach (self::SUPPORT_OPTIONS as $option) : ?>
                                <label class="ptools-choice ptools-choice--boxed">
                                    <input type="checkbox" name="powertools_cptm_supports[]"
                                           value="<?php echo esc_attr($option); ?>"
                                           <?php checked(in_array($option, (array) $values['supports'], true)); ?> />
                                    <span class="ptools-choice__text">
                                        <span class="ptools-choice__title"><?php echo esc_html(ucfirst(str_replace('-', ' ', $option))); ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="ptools-panel__footer">
                    <?php if ($editing) : ?>
                        <a href="<?php echo esc_url($page_url); ?>" class="ptools-btn ptools-btn--secondary">
                            <?php esc_html_e('Cancel', 'powertools'); ?>
                        </a>
                    <?php endif; ?>
                    <button type="submit" class="ptools-btn ptools-btn--primary">
                        <?php echo $editing ? esc_html__('Update Post Type', 'powertools') : esc_html__('Create Post Type', 'powertools'); ?>
                    </button>
                </div>
            </form>

            <div class="ptools-panel">
                <div class="ptools-panel__header">
                    <h2 class="ptools-h2"><?php esc_html_e('Post Types', 'powertools'); ?></h2>
                    <span class="ptools-pill ptools-pill--brand"><?php echo esc_html(count($custom_post_types)); ?></span>
                </div>

                <div class="ptools-panel__body ptools-panel__body--flush">
                    <?php if (empty($custom_post_types)) : ?>
                        <div class="ptools-empty">
                            <span class="ptools-empty__icon"><?php UI::dashicon('dashicons-category'); ?></span>
                            <p class="ptools-empty__text"><?php esc_html_e('No custom post types yet. Create your first one with the form.', 'powertools'); ?></p>
                        </div>
                    <?php else : ?>
                        <table class="ptools-table">
                            <thead>
                                <tr>
                                    <th scope="col"><?php esc_html_e('Label', 'powertools'); ?></th>
                                    <th scope="col" class="ptools-table__actions"><?php esc_html_e('Actions', 'powertools'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($custom_post_types as $post_type => $data) : ?>
                                    <tr class="<?php echo $edit_slug === $post_type ? 'is-current' : ''; ?>">
                                        <td>
                                            <span class="ptools-table__name"><?php echo esc_html($data['plural_label']); ?></span>
                                            <span class="ptools-table__meta"><?php echo esc_html($post_type); ?></span>
                                        </td>
                                        <td class="ptools-table__actions">
                                            <div class="ptools-table__actions-inner">
                                                <a class="ptools-icon-btn" href="<?php echo esc_url(add_query_arg('edit', $post_type, $page_url)); ?>">
                                                    <?php UI::dashicon('dashicons-edit'); ?>
                                                    <span class="ptools-sr-only">
                                                        <?php
                                                        /* translators: %s: post type label */
                                                        printf(esc_html__('Edit %s', 'powertools'), esc_html($data['plural_label']));
                                                        ?>
                                                    </span>
                                                </a>

                                                <form class="ptools-inline-form" method="post"
                                                      action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                                                      data-ptools-confirm="<?php echo esc_attr(sprintf(
                                                          /* translators: %s: post type label */
                                                          __('Delete the “%s” post type? Its content stays in the database but becomes inaccessible.', 'powertools'),
                                                          $data['plural_label']
                                                      )); ?>">
                                                    <input type="hidden" name="action" value="powertools_cptm_delete" />
                                                    <input type="hidden" name="powertools_cptm_post_type" value="<?php echo esc_attr($post_type); ?>" />
                                                    <?php wp_nonce_field('powertools_cptm_delete_nonce_action', 'powertools_cptm_delete_nonce'); ?>
                                                    <button type="submit" class="ptools-icon-btn ptools-icon-btn--danger">
                                                        <?php UI::dashicon('dashicons-trash'); ?>
                                                        <span class="ptools-sr-only">
                                                            <?php
                                                            /* translators: %s: post type label */
                                                            printf(esc_html__('Delete %s', 'powertools'), esc_html($data['plural_label']));
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        UI::page_close();
    }

    /**
     * Get the stored post type definitions
     *
     * @return array
     */
    private function get_post_types() {
        $post_types = get_option(self::OPTION_NAME, array());
        return is_array($post_types) ? $post_types : array();
    }

    /**
     * Render the feedback message for the current request, if any
     */
    private function render_message() {
        $code = isset($_GET['pt_message']) ? sanitize_key(wp_unslash($_GET['pt_message'])) : '';

        if (!isset(self::MESSAGES[$code])) {
            return;
        }

        $messages = array(
            'added'       => __('Custom post type created.', 'powertools'),
            'updated'     => __('Custom post type updated.', 'powertools'),
            'deleted'     => __('Custom post type deleted.', 'powertools'),
            'security'    => __('Security check failed. Please try again.', 'powertools'),
            'required'    => __('All required fields must be filled.', 'powertools'),
            'slug_length' => __('Post type slug must be 20 characters or fewer.', 'powertools'),
            'slug_taken'  => __('A post type with this slug already exists.', 'powertools'),
            'not_found'   => __('That post type could not be found.', 'powertools'),
        );

        UI::notice($messages[$code], self::MESSAGES[$code]);
    }

    /**
     * Handle add post type
     */
    public function handle_add_post_type() {
        $this->handle_save_post_type(false);
    }

    /**
     * Handle edit post type
     */
    public function handle_edit_post_type() {
        $this->handle_save_post_type(true);
    }

    /**
     * Validate and store a post type definition
     *
     * Add and edit only differ in which nonce they check and whether the slug
     * is expected to exist already, so they share one implementation.
     *
     * @param bool $is_edit
     */
    private function handle_save_post_type($is_edit) {
        Tool_Manager::require_capability();

        $nonce_field  = $is_edit ? 'powertools_cptm_edit_nonce' : 'powertools_cptm_add_nonce';
        $nonce_action = $is_edit ? 'powertools_cptm_edit_nonce_action' : 'powertools_cptm_add_nonce_action';

        if (!isset($_POST[$nonce_field]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$nonce_field])), $nonce_action)) {
            $this->redirect_with_message('security');
        }

        $slug           = isset($_POST['powertools_cptm_post_type_name']) ? sanitize_key(wp_unslash($_POST['powertools_cptm_post_type_name'])) : '';
        $singular_label = isset($_POST['powertools_cptm_singular_label']) ? sanitize_text_field(wp_unslash($_POST['powertools_cptm_singular_label'])) : '';
        $plural_label   = isset($_POST['powertools_cptm_plural_label']) ? sanitize_text_field(wp_unslash($_POST['powertools_cptm_plural_label'])) : '';

        if (empty($slug) || empty($singular_label) || empty($plural_label)) {
            $this->redirect_with_message('required');
        }

        if (strlen($slug) > 20) {
            $this->redirect_with_message('slug_length');
        }

        $custom_post_types = $this->get_post_types();

        if ($is_edit && !isset($custom_post_types[$slug])) {
            $this->redirect_with_message('not_found');
        }

        if (!$is_edit && (isset($custom_post_types[$slug]) || post_type_exists($slug))) {
            $this->redirect_with_message('slug_taken');
        }

        $supports = isset($_POST['powertools_cptm_supports']) ? (array) wp_unslash($_POST['powertools_cptm_supports']) : array();
        $supports = array_values(array_intersect(self::SUPPORT_OPTIONS, array_map('sanitize_key', $supports)));

        $custom_post_types[$slug] = array(
            'name'           => $slug,
            'singular_label' => $singular_label,
            'plural_label'   => $plural_label,
            'public'         => isset($_POST['powertools_cptm_public']),
            'has_archive'    => isset($_POST['powertools_cptm_has_archive']),
            'hierarchical'   => isset($_POST['powertools_cptm_hierarchical']),
            'menu_position'  => isset($_POST['powertools_cptm_menu_position']) ? intval(wp_unslash($_POST['powertools_cptm_menu_position'])) : null,
            'menu_icon'      => isset($_POST['powertools_cptm_menu_icon']) ? sanitize_text_field(wp_unslash($_POST['powertools_cptm_menu_icon'])) : '',
            'supports'       => $supports ? $supports : array('title', 'editor'),
        );

        update_option(self::OPTION_NAME, $custom_post_types);

        // New post types need their rewrite rules generated before their
        // archive URLs resolve.
        flush_rewrite_rules();

        $this->redirect_with_message($is_edit ? 'updated' : 'added');
    }

    /**
     * Handle delete post type
     */
    public function handle_delete_post_type() {
        Tool_Manager::require_capability();

        if (!isset($_POST['powertools_cptm_delete_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['powertools_cptm_delete_nonce'])), 'powertools_cptm_delete_nonce_action')) {
            $this->redirect_with_message('security');
        }

        $post_type = isset($_POST['powertools_cptm_post_type']) ? sanitize_key(wp_unslash($_POST['powertools_cptm_post_type'])) : '';
        $custom_post_types = $this->get_post_types();

        if (!isset($custom_post_types[$post_type])) {
            $this->redirect_with_message('not_found');
        }

        unset($custom_post_types[$post_type]);
        update_option(self::OPTION_NAME, $custom_post_types);
        flush_rewrite_rules();

        $this->redirect_with_message('deleted');
    }

    /**
     * Redirect back to the tool page with a message code
     *
     * @param string $code One of the keys in self::MESSAGES.
     */
    private function redirect_with_message($code) {
        wp_safe_redirect(add_query_arg('pt_message', $code, Tool_Manager::get_page_url('cpt_manager')));
        exit;
    }

    /**
     * Register custom post types
     */
    public function register_custom_post_types() {
        $custom_post_types = $this->get_post_types();

        foreach ($custom_post_types as $post_type => $data) {
            // Definitions saved by older versions may not have every key.
            $data = array_merge(array(
                'singular_label' => $post_type,
                'plural_label'   => $post_type,
                'public'         => true,
                'has_archive'    => true,
                'hierarchical'   => false,
                'menu_position'  => null,
                'menu_icon'      => '',
                'supports'       => array('title', 'editor'),
            ), (array) $data);

            $labels = array(
                'name'               => $data['plural_label'],
                'singular_name'      => $data['singular_label'],
                'menu_name'          => $data['plural_label'],
                'add_new'            => __('Add New', 'powertools'),
                /* translators: %s: singular post type label */
                'add_new_item'       => sprintf(__('Add New %s', 'powertools'), $data['singular_label']),
                /* translators: %s: singular post type label */
                'edit_item'          => sprintf(__('Edit %s', 'powertools'), $data['singular_label']),
                /* translators: %s: singular post type label */
                'new_item'           => sprintf(__('New %s', 'powertools'), $data['singular_label']),
                /* translators: %s: singular post type label */
                'view_item'          => sprintf(__('View %s', 'powertools'), $data['singular_label']),
                /* translators: %s: plural post type label */
                'search_items'       => sprintf(__('Search %s', 'powertools'), $data['plural_label']),
                /* translators: %s: plural post type label */
                'not_found'          => sprintf(__('No %s found', 'powertools'), $data['plural_label']),
                /* translators: %s: plural post type label */
                'not_found_in_trash' => sprintf(__('No %s found in Trash', 'powertools'), $data['plural_label']),
            );

            $args = array(
                'labels'              => $labels,
                'public'              => $data['public'],
                'has_archive'         => $data['has_archive'],
                'hierarchical'        => $data['hierarchical'],
                'menu_position'       => $data['menu_position'],
                'menu_icon'           => $data['menu_icon'],
                'supports'            => $data['supports'],
                'show_in_menu'        => true,
                'show_in_admin_bar'   => true,
                'show_in_nav_menus'   => true,
                'can_export'          => true,
                'exclude_from_search' => false,
                'publicly_queryable'  => true,
                'capability_type'     => 'post',
                'show_in_rest'        => true,
            );

            register_post_type($post_type, $args);
        }
    }
}
