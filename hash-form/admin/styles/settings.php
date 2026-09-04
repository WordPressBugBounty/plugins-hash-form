<?php
defined('ABSPATH') || die();

global $post;
$post_id = $post->ID;
$hashform_styles = get_post_meta($post_id, 'hashform_styles', true);

if (!$hashform_styles) {
    $hashform_styles = HashFormStyles::default_styles();
} else {
    $hashform_styles = HashFormHelper::recursive_parse_args($hashform_styles, HashFormStyles::default_styles());
}

wp_nonce_field('hf-styles-nonce', 'hashform_styles_nonce');
?>

<div class="hf-content hf-style-panel">
    <div class="hf-body">
        <div class="hf-fields-sidebar hf-style-sidebar">
            <div class="hf-sticky-sidebar">
                <?php include HASHFORM_PATH . 'admin/styles/main.php'; ?>
            </div>
        </div>

        <div id="hf-form-panel" class="hf-style-form-panel">
            <div class="hf-form-wrap">
                <?php
                /*
                 * The same canvas header the builder and the form Style tab
                 * carry, so a style template is built on the same surface a
                 * form is. The right slot names the form being previewed;
                 * admin-settings.js keeps it in step with the select.
                 */
                ?>
                <div class="hf-canvas-header">
                    <span class="hf-canvas-title"><?php esc_html_e('Style Preview', 'hash-form'); ?></span>
                    <span class="hf-canvas-count" id="hf-style-preview-form"><?php esc_html_e('Default Demo Form', 'hash-form'); ?></span>
                </div>

                <?php HashFormHelper::print_message(); ?>
                <div class="hf-template-preview"></div>
            </div>
        </div>
    </div>

    <?php
    /*
     * A template that has never been published is offered Publish; one that has
     * is offered Update. Read from the template itself rather than from the
     * query string, which only carried the answer while this panel lived inside
     * the post editor.
     */
    $hashform_is_published = ($post_id && 'publish' === get_post_status($post_id));
    ?>
    <div class="hf-footer">
        <?php
        /*
         * The buttons, and nothing else. This used to be wrapped in #submitpost
         * > #major-publishing-actions > #publishing-action with a hidden
         * original_publish field - the post editor's submit box, reproduced
         * around a form that is not the post editor's and saved over ajax. The
         * ids it brought were styled through a .post-type-hashform-styles body
         * class that only exists on a post screen, so on the builder they drew
         * nothing at all.
         */
        ?>
        <div class="hf-preview-close">
            <a class="button button-secondary" href="<?php echo esc_url(admin_url('edit.php?post_type=hashform-styles')); ?>"><?php esc_html_e('Close', 'hash-form'); ?></a>
        </div>

        <button type="submit" class="button button-primary hf-style-save">
            <?php echo $hashform_is_published ? esc_html__('Update', 'hash-form') : esc_html__('Publish', 'hash-form'); ?>
        </button>
    </div>
</div>