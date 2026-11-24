<?php
/**
 * Draft and revision cleanup.
 *
 * @package AlmazBroSiteUtilities
 */

namespace AlmazBroSiteUtilities\Cleaner;

use AlmazBroSiteUtilities\Admin\Tool_Manager;
use AlmazBroSiteUtilities\Admin\UI;

if (!defined('WPINC')) {
    die;
}

class Junk_Cleaner {
    private const DRAFTS_PER_PAGE = 20;
    private const REVISION_BATCH = 50;

    public function __construct() {
        add_action('admin_post_almazbro_site_utilities_trash_drafts', array($this, 'trash_drafts'));
        add_action('admin_post_almazbro_site_utilities_delete_revisions', array($this, 'delete_revisions'));
    }

    private function draft_types() {
        // Only list types with an editing screen where the draft can be restored.
        return array_values(array_diff(get_post_types(array('show_ui' => true)), array('attachment')));
    }

    public function render_settings_page() {
        global $wpdb;
        Tool_Manager::require_capability();
        $page_url = Tool_Manager::get_page_url('junk_cleaner');
        $draft_page = max(1, absint($_GET['draft_page'] ?? 1));
        $draft_types = $this->draft_types();
        $drafts = new \WP_Query();
        $visible_drafts = array();
        if ($draft_types) {
            $posts = $drafts->query(array(
                'post_type'      => $draft_types,
                'post_status'    => 'draft',
                'posts_per_page' => self::DRAFTS_PER_PAGE,
                'paged'          => $draft_page,
                'orderby'        => array('modified' => 'ASC', 'ID' => 'ASC'),
            ));
            foreach ($posts as $draft) {
                if (current_user_can('read_post', $draft->ID)) {
                    $visible_drafts[] = $draft;
                }
            }
        }

        // A high-water mark keeps revisions created during a run out of that run.
        $revisions = $wpdb->get_row("SELECT COUNT(*) AS total, MAX(ID) AS newest FROM $wpdb->posts WHERE post_type = 'revision'");
        $cursor = absint($_GET['revision_after'] ?? 0);
        $through = absint($_GET['revision_through'] ?? 0);
        $continuing = $cursor && $through > $cursor;
        if (!$continuing) {
            $through = (int) ($revisions->newest ?? 0);
        }

        UI::page_open(array(
            'tool_id'     => 'junk_cleaner',
            'title'       => __('Junk Cleaner', 'almazbro-site-utilities'),
            'description' => __('Clean up revision history and review drafts for the Trash.', 'almazbro-site-utilities'),
        ));

        $done = absint($_GET['cleaned'] ?? 0);
        $skipped = absint($_GET['skipped'] ?? 0);
        $result = sanitize_key(wp_unslash($_GET['cleanup_result'] ?? ''));
        if ($result === 'empty_selection') {
            UI::notice(__('Select at least one draft to move to Trash.', 'almazbro-site-utilities'), 'warning');
        } elseif ($result === 'drafts') {
            /* translators: 1: drafts moved to Trash, 2: drafts skipped. */
            UI::notice(sprintf(__('Drafts moved to Trash: %1$d. Skipped: %2$d.', 'almazbro-site-utilities'), $done, $skipped), $done ? 'success' : 'warning');
        } elseif ($result === 'revisions') {
            UI::notice($this->revision_summary($done, $skipped), $skipped ? 'warning' : 'success');
        }

        require ALMAZBRO_SITE_UTILITIES_PLUGIN_DIR . 'views/junk-cleaner.php';
        UI::page_close();
    }

    public function trash_drafts() {
        Tool_Manager::require_capability();
        check_admin_referer('almazbro_site_utilities_trash_drafts');
        $ids = array_filter((array) ($_POST['draft_ids'] ?? array()), 'is_scalar');
        $selection = array_unique(array_filter(array_map('absint', $ids)));
        if (!$selection) {
            wp_safe_redirect(add_query_arg('cleanup_result', 'empty_selection', Tool_Manager::get_page_url('junk_cleaner')), 303);
            exit;
        }
        if (!EMPTY_TRASH_DAYS || count($selection) > self::DRAFTS_PER_PAGE) {
            wp_die(esc_html__('Select drafts from one page and make sure WordPress Trash is enabled.', 'almazbro-site-utilities'));
        }

        $types = $this->draft_types();
        $trashed = 0;
        foreach ($selection as $id) {
            $post = $id ? get_post($id) : null;
            if ($post && $post->post_status === 'draft' && in_array($post->post_type, $types, true)
                && current_user_can('delete_post', $id) && wp_trash_post($id)) {
                ++$trashed;
            }
        }
        wp_safe_redirect(add_query_arg(array(
            'cleanup_result' => 'drafts',
            'cleaned'        => $trashed,
            'skipped'        => count($selection) - $trashed,
        ), Tool_Manager::get_page_url('junk_cleaner')), 303);
        exit;
    }

    private function revision_summary($deleted, $skipped) {
        /* translators: 1: revisions permanently deleted, 2: revisions skipped. */
        return sprintf(__('Revisions permanently deleted: %1$d. Skipped: %2$d.', 'almazbro-site-utilities'), $deleted, $skipped);
    }

    public function delete_revisions() {
        global $wpdb;
        Tool_Manager::require_capability();
        $through = absint($_POST['through'] ?? 0);
        check_admin_referer('almazbro_site_utilities_delete_revisions_' . $through);
        $cursor = absint($_POST['after'] ?? 0);
        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM $wpdb->posts WHERE post_type = 'revision' AND ID > %d AND ID <= %d ORDER BY ID LIMIT %d",
            $cursor, $through, self::REVISION_BATCH + 1
        ));
        if ($wpdb->last_error) {
            wp_die(esc_html__('Could not load the revision batch. Reload Junk Cleaner to retry.', 'almazbro-site-utilities'));
        }

        $more = count($ids) > self::REVISION_BATCH;
        if ($more) {
            array_pop($ids);
        }
        $deleted = absint($_POST['cleaned'] ?? 0);
        $skipped = absint($_POST['skipped'] ?? 0);
        foreach ($ids as $id) {
            $cursor = (int) $id;
            $parent = wp_is_post_revision($id);
            if ($parent && current_user_can('delete_post', $parent) && wp_delete_post_revision($id)) {
                ++$deleted;
            } else {
                ++$skipped;
            }
        }

        $args = array('cleanup_result' => 'revisions', 'cleaned' => $deleted, 'skipped' => $skipped);
        if ($more) {
            $args['revision_after'] = $cursor;
            $args['revision_through'] = $through;
        }
        $url = add_query_arg($args, Tool_Manager::get_page_url('junk_cleaner'));
        if (isset($_POST['batch_json'])) {
            wp_send_json_success(array(
                'more'    => $more,
                'after'   => $cursor,
                'cleaned' => $deleted,
                'skipped' => $skipped,
                'message' => $this->revision_summary($deleted, $skipped),
                'url'     => $url,
            ));
        }
        wp_safe_redirect($url, 303);
        exit;
    }
}
