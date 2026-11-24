<?php
/**
 * Junk Cleaner forms.
 *
 * @package AlmazBroSiteUtilities
 */

if (!defined('WPINC')) {
    die;
}
?>
<section class="almazbro-site-utilities-panel">
    <div class="almazbro-site-utilities-panel__header">
        <h2 class="almazbro-site-utilities-h2"><?php esc_html_e('Post revisions', 'almazbro-site-utilities'); ?></h2>
        <?php if ($revisions) : ?>
            <span class="almazbro-site-utilities-pill"><?php echo esc_html(number_format_i18n($revisions->total)); ?></span>
        <?php endif; ?>
    </div>
    <div class="almazbro-site-utilities-panel__body">
        <p><?php esc_html_e('Permanently delete saved revisions, including autosaves. Current posts and drafts keep their content.', 'almazbro-site-utilities'); ?></p>
        <div role="status" id="almazbro-revision-progress"></div>
    </div>
    <?php if (!$revisions) : ?>
        <p><?php esc_html_e('Could not load the revision count. Reload this page to retry.', 'almazbro-site-utilities'); ?></p>
    <?php elseif ($revisions->total) : ?>
        <form class="almazbro-site-utilities-panel__footer" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
              data-revision-cleanup
              data-error="<?php esc_attr_e('Cleanup interrupted. Reload this page to see the remaining revisions and retry.', 'almazbro-site-utilities'); ?>"
              data-almazbro-site-utilities-confirm="<?php esc_attr_e('Permanently delete these revisions, including autosaves?', 'almazbro-site-utilities'); ?>">
            <input type="hidden" name="action" value="almazbro_site_utilities_delete_revisions" />
            <input type="hidden" name="through" value="<?php echo esc_attr($through); ?>" />
            <input type="hidden" name="after" value="<?php echo esc_attr($continuing ? $cursor : 0); ?>" />
            <input type="hidden" name="cleaned" value="<?php echo esc_attr($continuing ? $done : 0); ?>" />
            <input type="hidden" name="skipped" value="<?php echo esc_attr($continuing ? $skipped : 0); ?>" />
            <?php wp_nonce_field('almazbro_site_utilities_delete_revisions_' . $through); ?>
            <button type="submit" class="almazbro-site-utilities-btn almazbro-site-utilities-btn--danger"><?php echo $continuing ? esc_html__('Continue revision cleanup', 'almazbro-site-utilities') : esc_html__('Delete revisions', 'almazbro-site-utilities'); ?></button>
            <noscript><?php esc_html_e('Submit once per batch of up to 50 revisions.', 'almazbro-site-utilities'); ?></noscript>
        </form>
    <?php endif; ?>
</section>

<section class="almazbro-site-utilities-panel">
    <div class="almazbro-site-utilities-panel__header">
        <h2 class="almazbro-site-utilities-h2"><?php esc_html_e('Drafts', 'almazbro-site-utilities'); ?></h2>
    </div>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <div class="almazbro-site-utilities-panel__body">
            <?php if (!EMPTY_TRASH_DAYS) : ?>
                <p><?php esc_html_e('WordPress Trash is disabled on this site. Draft cleanup is unavailable.', 'almazbro-site-utilities'); ?></p>
            <?php endif; ?>
            <?php if ($visible_drafts) : ?>
                <table class="almazbro-site-utilities-table">
                    <thead><tr>
                        <th scope="col"><?php esc_html_e('Draft title', 'almazbro-site-utilities'); ?></th>
                        <th scope="col"><?php esc_html_e('Post type', 'almazbro-site-utilities'); ?></th>
                        <th scope="col"><?php esc_html_e('Last modified', 'almazbro-site-utilities'); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($visible_drafts as $draft) : ?>
                        <tr>
                            <td><label><input type="checkbox" name="draft_ids[]" value="<?php echo esc_attr($draft->ID); ?>" <?php disabled(!EMPTY_TRASH_DAYS || !current_user_can('delete_post', $draft->ID)); ?> />
                                <?php echo esc_html($draft->post_title !== '' ? $draft->post_title : __('(no title)', 'almazbro-site-utilities')); ?></label></td>
                            <td><?php echo esc_html(get_post_type_object($draft->post_type)->labels->singular_name); ?></td>
                            <td><?php echo esc_html(get_the_modified_date('', $draft)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No drafts on this page.', 'almazbro-site-utilities'); ?></p>
            <?php endif; ?>
            <?php if ($draft_page > 1) : ?>
                <a href="<?php echo esc_url(add_query_arg('draft_page', $draft_page - 1, $page_url)); ?>"><?php esc_html_e('Previous drafts', 'almazbro-site-utilities'); ?></a>
            <?php endif; ?>
            <?php if ($draft_page < $drafts->max_num_pages) : ?>
                <a href="<?php echo esc_url(add_query_arg('draft_page', $draft_page + 1, $page_url)); ?>"><?php esc_html_e('Next drafts', 'almazbro-site-utilities'); ?></a>
            <?php endif; ?>
        </div>
        <?php if ($visible_drafts && EMPTY_TRASH_DAYS) : ?>
            <div class="almazbro-site-utilities-panel__footer">
                <input type="hidden" name="action" value="almazbro_site_utilities_trash_drafts" />
                <?php wp_nonce_field('almazbro_site_utilities_trash_drafts'); ?>
                <button type="submit" class="almazbro-site-utilities-btn almazbro-site-utilities-btn--secondary"><?php esc_html_e('Move selected drafts to Trash', 'almazbro-site-utilities'); ?></button>
            </div>
        <?php endif; ?>
    </form>
</section>
