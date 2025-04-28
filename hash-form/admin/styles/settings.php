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

        <div id="hf-form-panel">
            <div class="hf-form-wrap">
                <?php HashFormHelper::print_message(); ?>
                <?php include HASHFORM_PATH . 'admin/styles/demo-preview.php'; ?>
            </div>
        </div>
    </div>

    <?php
    $hashform_post_class = 'submitbox';
    $show_publish_button = false;

    if (isset($_GET['post_type']) && $_GET['post_type'] == 'hashform-styles') {
        $show_publish_button = true;
        $hashform_post_class = 'postbox';
    }

    if (isset($_GET['post'])) {
        $hashform_post_id = htmlspecialchars_decode(HashFormHelper::get_var('post'));
        if ('publish' !== get_post_status($hashform_post_id)) {
            $show_publish_button = true;
            $hashform_post_class = 'postbox';
        }
    }
    ?>
    <div class="hf-footer">
        <div id="submitpost" class="<?php echo esc_attr($hashform_post_class); ?>">
            <div id="major-publishing-actions">
                <div id="publishing-action">
                    <span class="spinner"></span>
                    <?php if ($show_publish_button) { ?>
                        <input name="original_publish" type="hidden" id="original_publish" value="Publish">
                        <input type="submit" name="publish" id="publish" class="button button-primary button-large" value="<?php esc_html_e('Publish', 'hash-form'); ?>">
                    <?php } else { ?>
                        <input name="original_publish" type="hidden" id="original_publish" value="Update">
                        <input type="submit" name="save" id="publish" class="button button-primary button-large" value="<?php esc_html_e('Update', 'hash-form'); ?>">
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="hf-preview-close">
            <a class="button button-secondary button-large" href="<?php echo esc_url(admin_url('/edit.php?post_type=hashform-styles')); ?>"><?php esc_html_e('Close', 'hash-form'); ?></a>
        </div>
    </div>
</div>