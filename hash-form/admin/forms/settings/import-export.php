<?php
defined('ABSPATH') || die();

$field_count = is_array($fields) ? count($fields) : 0;
?>

<div class="hf-form-container">
    <div class="hf-form-row">
        <div class="hf-imex-card">
            <div class="hf-imex-head">
                <span class="hf-imex-icon mdi mdi-tray-arrow-down" aria-hidden="true"></span>
                <div>
                    <h3 class="hf-section-title"><?php esc_html_e('Export', 'hash-form'); ?></h3>
                    <p class="hf-section-help">
                        <?php esc_html_e('Download this form as a .json file holding its fields, settings and style. Use it as a backup, or to rebuild the same form on another site.', 'hash-form'); ?>
                    </p>
                </div>
            </div>

            <p class="hf-imex-meta">
                <?php
                printf(
                    /* translators: %s: number of fields in the form. */
                    esc_html(_n('%s field will be included.', '%s fields will be included.', $field_count, 'hash-form')),
                    esc_html(number_format_i18n($field_count))
                );
                ?>
            </p>

            <form method="post">
                <input type="hidden" name="hashform_imex_action" value="export_form" />
                <input type="hidden" name="hashform_form_id" value="<?php echo esc_attr($id); ?>" />
                <?php wp_nonce_field('hashform_imex_export_nonce', 'hashform_imex_export_nonce'); ?>
                <button class="button button-primary" id="hashform_export" name="hashform_export">
                    <span class="mdi mdi-tray-arrow-down" aria-hidden="true"></span>
                    <?php esc_html_e('Export Form', 'hash-form'); ?>
                </button>
            </form>
        </div>
    </div>

    <div class="hf-form-row">
        <div class="hf-imex-card">
            <div class="hf-imex-head">
                <span class="hf-imex-icon mdi mdi-tray-arrow-up" aria-hidden="true"></span>
                <div>
                    <h3 class="hf-section-title"><?php esc_html_e('Import', 'hash-form'); ?></h3>
                    <p class="hf-section-help">
                        <?php esc_html_e('Load a .json file exported from Hash Form into this form.', 'hash-form'); ?>
                    </p>
                </div>
            </div>

            <?php // The importer deletes this form's fields before writing the new ones, so say so before the file is chosen rather than after. ?>
            <p class="hf-imex-warning">
                <span class="mdi mdi-alert-outline" aria-hidden="true"></span>
                <span><?php esc_html_e('Importing replaces this form. Its current fields, settings and style are overwritten and cannot be recovered — export a copy first if you may want it back.', 'hash-form'); ?></span>
            </p>

            <p class="hf-import-error" role="alert"></p>

            <form method="post" enctype="multipart/form-data" class="hf-settings-import-form">
                <div class="hf-preview-zone hidden">
                    <div class="hf-box hf-box-solid">
                        <div class="hf-box-body"></div>
                        <button type="button" class="button hf-remove-preview">
                            <span class="mdi mdi-window-close" aria-hidden="true"></span>
                            <span class="screen-reader-text"><?php esc_html_e('Remove file', 'hash-form'); ?></span>
                        </button>
                    </div>
                </div>
                <div class="hf-dropzone-wrapper">
                    <div class="hf-dropzone-desc">
                        <span class="mdi mdi-tray-arrow-up" aria-hidden="true"></span>
                        <p><?php esc_html_e('Choose a JSON file or drag it here', 'hash-form'); ?></p>
                    </div>
                    <input type="file" name="hashform_import_file" class="hf-dropzone" accept=".json,application/json" />
                </div>
                <button class="button button-primary" id="hashform_import" type="submit" name="hashform_import">
                    <span class="mdi mdi-tray-arrow-up" aria-hidden="true"></span>
                    <?php esc_html_e('Import', 'hash-form'); ?>
                </button>
                <input type="hidden" name="hashform_imex_action" value="import_form" />
                <input type="hidden" name="hashform_form_id" value="<?php echo esc_attr($id); ?>" />
                <?php wp_nonce_field('hashform_imex_import_nonce', 'hashform_imex_import_nonce'); ?>
            </form>
        </div>
    </div>
</div>
