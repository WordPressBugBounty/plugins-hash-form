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
                // Same canvas header as the builder; admin-settings.js keeps the form name in step with the select.
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
    $hashform_is_published = ($post_id && 'publish' === get_post_status($post_id));
    ?>
    <div class="hf-footer">
        <?php
        // Plain buttons, not the post editor's submit box; the template saves over ajax.
        ?>
        <div class="hf-preview-close">
            <a class="button button-secondary" href="<?php echo esc_url(admin_url('edit.php?post_type=hashform-styles')); ?>"><?php esc_html_e('Close', 'hash-form'); ?></a>
        </div>

        <button type="submit" class="button button-primary hf-style-save">
            <?php echo $hashform_is_published ? esc_html__('Update', 'hash-form') : esc_html__('Publish', 'hash-form'); ?>
        </button>
    </div>
</div>