<?php
defined('ABSPATH') || die();
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from within a method, so these are function locals.

$image_id = '';
$image = '';
if (isset($settings['header_image'])) {
    $image_id = $settings['header_image'];
    $image = wp_get_attachment_image_src($settings['header_image'], 'full');
    $image = isset($image[0]) ? esc_attr($image[0]) : '';
}
?>
<div class="hf-form-container hf-grid-container">
    <div class="hf-settings-row">
        <label class="hf-setting-label"><?php esc_html_e('Header Image', 'hash-form'); ?></label>
        <div class="hf-image-preview">
            <input type="hidden" class="hf-image-id" name="hashform_settings[header_image]" id="header_image" value="<?php echo esc_attr($image_id); ?>" />

            <div class="hf-image-preview-wrap<?php echo ($image ? '' : ' hf-hidden'); ?>">
                <div class="hf-image-preview-box">
                    <?php // No src when empty: an empty src makes the browser re-request the page. ?>
                    <img id="hf-image-preview-header-image" alt="" <?php echo $image ? 'src="' . esc_url($image) . '"' : ''; ?> />
                </div>

                <div class="hf-image-actions">
                    <button type="button" class="button hf-replace-image">
                        <span class="mdi mdi-image-sync-outline" aria-hidden="true"></span>
                        <?php esc_html_e('Replace', 'hash-form'); ?>
                    </button>
                    <button type="button" class="button hf-remove-image">
                        <span class="mdi mdi-trash-can-outline" aria-hidden="true"></span>
                        <?php esc_html_e('Remove', 'hash-form'); ?>
                    </button>
                </div>
            </div>

            <button type="button" class="button hf-choose-image<?php echo ($image ? ' hf-hidden' : ''); ?>">
                <span class="mdi mdi-image-plus-outline" aria-hidden="true"></span>
                <span class="hf-choose-image-label"><?php esc_html_e('Choose image', 'hash-form'); ?></span>
                <span class="hf-choose-image-hint"><?php esc_html_e('From your media library', 'hash-form'); ?></span>
            </button>
        </div>
    </div>

    <div class="hf-settings-row">
        <label class="hf-setting-label"><?php esc_html_e('Email Template', 'hash-form'); ?></label>
        <div class="hf-setting-field">
            <select id="hf-settings-email-template" name="hashform_settings[email_template]">
                <?php foreach (HashFormEmail::templates() as $hf_key => $hf_template) { ?>
                    <option value="<?php echo esc_attr($hf_key); ?>" <?php selected(HashFormEmail::email_template($settings), $hf_key); ?>><?php echo esc_html($hf_template['label']); ?></option>
                <?php } ?>
            </select>
            <a href="#" class="hf-btn" id="hf-preview-email-button" data-url="<?php echo esc_url(add_query_arg(array('action' => 'hashform_preview_email_template', 'backend_nonce' => wp_create_nonce('hashform_backend_ajax')), admin_url('admin-ajax.php'))); ?>"><?php esc_html_e('Preview', 'hash-form'); ?></a>
        </div>
    </div>

    <?php
    /**
     * After the Email Template choice, for options that change how emails look.
     *
     * @param array $settings
     */
    do_action('hashform_email_settings_after_template', $settings);
    ?>

    <div class="hf-settings-row">
        <label class="hf-setting-label"><?php esc_html_e('Send Test Email to', 'hash-form'); ?></label>
        <div class="hf-setting-field">
            <input type="email" id="hf-test-email" />
            <a href="#" class="hf-btn" id="hf-test-email-button"><?php esc_attr_e('Send Test Email', 'hash-form'); ?></a>
        </div>
        <div class="hf-test-email-notice"></div>
    </div>
</div>