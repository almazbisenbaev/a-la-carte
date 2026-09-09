<?php
/**
 * Junk Cleaner functionality
 *
 * @package ALaCarte
 */

namespace ALaCarte\Cleaner;

use ALaCarte\Admin\Tool_Manager;
use ALaCarte\Admin\UI;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class Junk_Cleaner
 */
class Junk_Cleaner {

    /**
     * Render the junk cleaner settings page
     */
    public function render_settings_page() {
        Tool_Manager::require_capability();

        // Handle deletion
        if (isset($_POST['delete_revisions_drafts']) && check_admin_referer('alacarte_junk_cleaner')) {
            $this->delete_junk();
        }

        // Get the number of drafts and revisions
        $drafts_count    = count($this->get_draft_ids());
        $revisions_count = count($this->get_revision_ids());
        $is_clean        = ($drafts_count === 0 && $revisions_count === 0);

        $confirm_message = sprintf(
            /* translators: 1: number of drafts, 2: number of revisions */
            __('This will permanently delete %1$d draft(s) and %2$d revision(s). This cannot be undone.', 'a-la-carte'),
            $drafts_count,
            $revisions_count
        );

        UI::page_open(array(
            'tool_id'     => 'junk_cleaner',
            'title'       => __('Junk Cleaner', 'a-la-carte'),
            'description' => __('Free up database space by removing unnecessary drafts and post revisions.', 'a-la-carte'),
        ));
        ?>
        <div class="alacarte-panel">
            <div class="alacarte-panel__header">
                <h2 class="alacarte-h2"><?php esc_html_e('Database Overview', 'a-la-carte'); ?></h2>
            </div>

            <div class="alacarte-panel__body">
                <div class="alacarte-stats">
                    <div class="alacarte-stat">
                        <div class="alacarte-stat__value"><?php echo esc_html($drafts_count); ?></div>
                        <div class="alacarte-stat__label"><?php esc_html_e('Draft Posts', 'a-la-carte'); ?></div>
                    </div>
                    <div class="alacarte-stat">
                        <div class="alacarte-stat__value"><?php echo esc_html($revisions_count); ?></div>
                        <div class="alacarte-stat__label"><?php esc_html_e('Post Revisions', 'a-la-carte'); ?></div>
                    </div>
                </div>

                <?php if ($is_clean) : ?>
                    <?php UI::notice(__('Your database is clean. No drafts or revisions found.', 'a-la-carte'), 'success'); ?>
                <?php else : ?>
                    <?php
                    UI::notice(
                        sprintf(
                            /* translators: 1: number of drafts, 2: number of revisions */
                            __('You have %1$d draft(s) and %2$d revision(s) that can be removed to free up database space.', 'a-la-carte'),
                            $drafts_count,
                            $revisions_count
                        ),
                        'warning'
                    );
                    ?>
                <?php endif; ?>
            </div>

            <div class="alacarte-panel__footer">
                <form method="post" data-alacarte-confirm="<?php echo esc_attr($confirm_message); ?>">
                    <?php wp_nonce_field('alacarte_junk_cleaner'); ?>
                    <input type="hidden" name="delete_revisions_drafts" value="1" />
                    <button type="submit" class="alacarte-btn alacarte-btn--danger" <?php disabled($is_clean); ?>>
                        <?php UI::dashicon('dashicons-trash'); ?>
                        <?php esc_html_e('Clean Database Now', 'a-la-carte'); ?>
                    </button>
                </form>
            </div>
        </div>
        <?php
        UI::page_close();
    }

    /**
     * Get the IDs of all draft posts (any post type)
     *
     * @return int[]
     */
    private function get_draft_ids() {
        return get_posts(array(
            'post_type'      => 'any',
            'post_status'    => 'draft',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ));
    }

    /**
     * Get the IDs of all post revisions
     *
     * @return int[]
     */
    private function get_revision_ids() {
        return get_posts(array(
            'post_type'      => 'revision',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ));
    }

    /**
     * Delete all drafts and revisions
     *
     * Uses wp_delete_post() rather than a raw DELETE query so related
     * postmeta/term relationships are cleaned up and other plugins hooked
     * into post deletion (backups, search indexes, etc.) are notified.
     */
    private function delete_junk() {
        $ids = array_merge($this->get_draft_ids(), $this->get_revision_ids());

        $deleted_count = 0;
        foreach ($ids as $id) {
            if (wp_delete_post($id, true)) {
                $deleted_count++;
            }
        }

        // Show success message
        add_settings_error(
            'alacarte_messages',
            'alacarte_message',
            sprintf(
                /* translators: %d: number of items permanently deleted */
                __('%d draft(s) and revision(s) have been permanently deleted.', 'a-la-carte'),
                $deleted_count
            ),
            'updated'
        );
    }
}
