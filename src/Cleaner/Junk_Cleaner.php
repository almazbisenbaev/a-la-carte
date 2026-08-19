<?php
/**
 * Junk Cleaner functionality
 *
 * @package PowerTools
 */

namespace PowerTools\Cleaner;

use PowerTools\Admin\Tool_Manager;
use PowerTools\Admin\UI;

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
        if (isset($_POST['delete_revisions_drafts']) && check_admin_referer('powertools_junk_cleaner')) {
            $this->delete_junk();
        }

        // Get the number of drafts and revisions
        $drafts_count    = count($this->get_draft_ids());
        $revisions_count = count($this->get_revision_ids());
        $is_clean        = ($drafts_count === 0 && $revisions_count === 0);

        $confirm_message = sprintf(
            /* translators: 1: number of drafts, 2: number of revisions */
            __('This will permanently delete %1$d draft(s) and %2$d revision(s). This cannot be undone.', 'powertools'),
            $drafts_count,
            $revisions_count
        );

        UI::page_open(array(
            'tool_id'     => 'junk_cleaner',
            'title'       => __('Junk Cleaner', 'powertools'),
            'description' => __('Free up database space by removing unnecessary drafts and post revisions.', 'powertools'),
        ));
        ?>
        <div class="pt-panel">
            <div class="pt-panel__header">
                <h2 class="pt-h2"><?php esc_html_e('Database Overview', 'powertools'); ?></h2>
            </div>

            <div class="pt-panel__body">
                <div class="pt-stats">
                    <div class="pt-stat">
                        <div class="pt-stat__value"><?php echo esc_html($drafts_count); ?></div>
                        <div class="pt-stat__label"><?php esc_html_e('Draft Posts', 'powertools'); ?></div>
                    </div>
                    <div class="pt-stat">
                        <div class="pt-stat__value"><?php echo esc_html($revisions_count); ?></div>
                        <div class="pt-stat__label"><?php esc_html_e('Post Revisions', 'powertools'); ?></div>
                    </div>
                </div>

                <?php if ($is_clean) : ?>
                    <?php UI::notice(__('Your database is clean. No drafts or revisions found.', 'powertools'), 'success'); ?>
                <?php else : ?>
                    <?php
                    UI::notice(
                        sprintf(
                            /* translators: 1: number of drafts, 2: number of revisions */
                            __('You have %1$d draft(s) and %2$d revision(s) that can be removed to free up database space.', 'powertools'),
                            $drafts_count,
                            $revisions_count
                        ),
                        'warning'
                    );
                    ?>
                <?php endif; ?>
            </div>

            <div class="pt-panel__footer">
                <form method="post" data-pt-confirm="<?php echo esc_attr($confirm_message); ?>">
                    <?php wp_nonce_field('powertools_junk_cleaner'); ?>
                    <input type="hidden" name="delete_revisions_drafts" value="1" />
                    <button type="submit" class="pt-btn pt-btn--danger" <?php disabled($is_clean); ?>>
                        <?php UI::dashicon('dashicons-trash'); ?>
                        <?php esc_html_e('Clean Database Now', 'powertools'); ?>
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
            'powertools_messages',
            'powertools_message',
            sprintf(
                /* translators: %d: number of items permanently deleted */
                __('%d draft(s) and revision(s) have been permanently deleted.', 'powertools'),
                $deleted_count
            ),
            'updated'
        );
    }
}
