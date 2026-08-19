<?php
defined('ABSPATH') || die();
?>

<div id="hf-add-form-modal" role="dialog" aria-modal="true" aria-labelledby="hf-add-form-title" aria-hidden="true">
    <div class="hf-add-form-modal-wrap">

        <h3 id="hf-add-form-title"><?php esc_html_e('Create New Form', 'hash-form'); ?></h3>

        <button type="button" class="hf-modal-close hashform-close-form-modal">
            <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
            <span class="screen-reader-text"><?php esc_html_e('Close', 'hash-form'); ?></span>
        </button>

        <form id="hf-add-template" method="post">
            <div class="hf-form-row">
                <label for="hf-form-name"><?php esc_html_e('Form Name', 'hash-form'); ?></label>
                <input type="text" name="template_name" id="hf-form-name" autocomplete="off" placeholder="<?php esc_attr_e('Contact Form', 'hash-form'); ?>" />
                <p class="hf-modal-help"><?php esc_html_e('Used to identify the form in your admin. You can rename it at any time.', 'hash-form'); ?></p>
                <p class="hf-modal-error" role="alert"></p>
            </div>

            <div class="hf-add-form-footer">
                <a href="#" class="hashform-close-form-modal"><?php esc_html_e('Cancel', 'hash-form'); ?></a>
                <button type="submit"><?php esc_html_e('Create Form', 'hash-form'); ?></button>
            </div>
        </form>

    </div>
</div>
