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

<div class="hf-content">
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
    $hashform_post_class = 'submitbox';
    $hashform_show_publish_button = false;

    if (HashFormHelper::get_var('post_type') == 'hashform-styles') {
        $hashform_show_publish_button = true;
        $hashform_post_class = 'postbox';
    }

    if (HashFormHelper::get_var('post')) {
        $hashform_post_id = htmlspecialchars_decode(HashFormHelper::get_var('post'));
        if ('publish' !== get_post_status($hashform_post_id)) {
            $hashform_show_publish_button = true;
            $hashform_post_class = 'postbox';
        }
    }
    ?>
    <div class="hf-footer">
        <div id="submitpost" class="<?php echo esc_attr($hashform_post_class); ?>">
            <div id="major-publishing-actions">
                <div id="publishing-action">
                    <?php
                    if ($hashform_show_publish_button) {
                        ?>
                        <input name="original_publish" type="hidden" id="original_publish" value="Publish">
                        <button type="submit" name="publish" id="publish" class="button button-primary button-large"><?php esc_html_e('Publish', 'hash-form'); ?></button>
                        <?php
                    } else {
                        ?>
                        <input name="original_publish" type="hidden" id="original_publish" value="Update">
                        <button type="submit" name="save" id="publish" class="button button-primary button-large"><?php esc_html_e('Update', 'hash-form'); ?></button>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="hf-preview-close">
            <a class="button button-secondary button-large" href="<?php echo esc_url(admin_url('/edit.php?post_type=hashform-styles')); ?>"><?php esc_html_e('Close', 'hash-form'); ?></a>
        </div>
    </div>
</div>