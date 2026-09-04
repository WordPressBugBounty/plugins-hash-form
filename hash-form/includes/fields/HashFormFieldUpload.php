<?php
defined('ABSPATH') || die();

class HashFormFieldUpload extends HashFormFieldType {

    protected $type = 'upload';

    protected function field_settings_for_type() {
        return array(
            'default' => false,
        );
    }

    protected function extra_field_default_opts() {
        return array(
            'upload_label' => esc_html__('Upload File', 'hash-form'),
            'max_upload_size' => 10,
            'min_upload_size' => '',
            'extensions' => 'jpg,jpeg,gif,png',
            'extensions_error_message' => esc_html__('Invalid Extension', 'hash-form'),
            'multiple_uploads' => 'on',
            'multiple_uploads_limit' => 5,
            'multiple_uploads_error_message' => esc_html__('Maximum file upload limit exceeded', 'hash-form'),
        );
    }

    protected function input_html() {
        $field = $this->get_field();
        $max_size = isset($field['max_upload_size']) ? absint($field['max_upload_size']) : '';
        $max_size = $max_size ? $max_size : 10;
        $max_size = $max_size * 1024 * 1024;
        // Kilobytes in the builder, because the useful values here are small,
        // but bytes on the wire to match the maximum.
        $min_size = isset($field['min_upload_size']) ? absint($field['min_upload_size']) : 0;
        $min_size = $min_size * 1024;
        $new_extensions = isset($field['extensions']) ? hashform_sanitize_allowed_file_extensions($field['extensions']) : 'jpg,jpeg,gif,png';

        if (is_admin() && !HashFormHelper::is_preview_page()) {
            // Static twin of the dropzone frontend.js builds, so the builder
            // shows what the visitor will see. The uploader script never runs
            // on this screen, so there is no list and no drop overlay, and the
            // id has to stay on the button itself because the label setting
            // live updates it by id.
            ?>
            <div class="hf-file-uploader-wrapper">
                <div class="hf-file-uploader">
                    <div class="qq-uploader">
                        <div class="hf-upload-dropzone">
                            <?php self::dropzone_icon(); ?>
                            <span class="hf-upload-dropzone-title">
                                <?php
                                echo isset($field['multiple_uploads']) && $field['multiple_uploads'] == 'on'
                                    ? esc_html__('Drag and drop your files here', 'hash-form')
                                    : esc_html__('Drag and drop your file here', 'hash-form');
                                ?>
                            </span>
                            <span class="hf-upload-dropzone-or"><?php esc_html_e('or', 'hash-form'); ?></span>
                            <div id="hf-editor-upload-label-text-<?php echo absint($field['id']); ?>" class="qq-upload-button"><?php echo isset($field['upload_label']) && $field['upload_label'] ? esc_html($field['upload_label']) : esc_html__('Upload File', 'hash-form'); ?></div>
                            <?php
                            $hint = self::constraints_hint($field, $new_extensions, $max_size, $min_size);
                            if ($hint) {
                                ?>
                                <span class="hf-upload-dropzone-hint"><?php echo esc_html($hint); ?></span>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        } else {
            ?>
            <div class="hf-file-uploader-wrapper">
                <div class="hf-file-uploader" id="hf-file-uploader-<?php echo esc_attr($this->html_id()); ?>" data-upload-label="<?php echo isset($field['upload_label']) && $field['upload_label'] ? esc_attr($field['upload_label']) : esc_html__('Upload File', 'hash-form'); ?>" data-extensions="<?php echo esc_attr($new_extensions); ?>" data-extensions-error-message="<?php echo isset($field['extensions_error_message']) ? esc_attr($field['extensions_error_message']) : ''; ?>" data-multiple-uploads="<?php echo isset($field['multiple_uploads']) && $field['multiple_uploads'] == 'on' ? 'true' : 'false'; ?>" data-multiple-uploads-limit="<?php echo isset($field['multiple_uploads']) && $field['multiple_uploads'] == 'on' ? absint($field['multiple_uploads_limit']) : '-1'; ?>" data-multiple-uploads-error-message="<?php echo isset($field['multiple_uploads_error_message']) ? esc_attr($field['multiple_uploads_error_message']) : ''; ?>" data-max-upload-size="<?php echo esc_attr($max_size); ?>" data-min-upload-size="<?php echo esc_attr($min_size); ?>" data-field-uploader-id="<?php echo esc_attr($this->html_id()); ?>">
                    <div class="qq-uploader qq-fake-uploader">
                        <div class="qq-upload-button" style="position: relative; overflow: hidden; direction: ltr;">
                            <?php echo isset($field['upload_label']) && $field['upload_label'] ? esc_attr($field['upload_label']) : esc_html__('Upload File', 'hash-form'); ?>
                        </div>
                    </div>
                </div>

                <div class="hf-file-preview"></div>

                <input type="hidden" class="hf-uploaded-files" <?php $this->field_attrs(); ?>>
                <input type="hidden" class="hf-multiple-upload-limit" value="0">
            </div>
            <?php
        }
    }

    /**
     * The dropzone icon.
     *
     * Twin of UPLOAD_ICON in frontend.js. It has to exist in both because the
     * uploader script overwrites the element's markup with its own template on
     * the front end, while the builder renders this PHP instead.
     */
    private static function dropzone_icon() {
        ?>
        <svg class="hf-upload-dropzone-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <polyline points="17 8 12 3 7 8" />
            <line x1="12" y1="3" x2="12" y2="15" />
        </svg>
        <?php
    }

    /**
     * "JPG, PNG  ·  up to 10 MB  ·  5 files max".
     *
     * Built from the same field options the uploader is configured with, so the
     * line cannot promise something the field does not actually allow.
     */
    private static function constraints_hint($field, $extensions, $max_size, $min_size = 0) {
        $parts = array();

        $extensions = array_filter(array_map('trim', explode(',', (string) $extensions)));
        if ($extensions) {
            $parts[] = implode(', ', array_map('strtoupper', $extensions));
        }

        if ($min_size > 0) {
            /* translators: 1: minimum file size, 2: maximum file size, both formatted, for example "5 KB" and "10 MB". */
            $parts[] = sprintf(esc_html__('%1$s to %2$s', 'hash-form'), size_format($min_size), size_format($max_size));
        } elseif ($max_size > 0) {
            /* translators: %s: maximum file size, already formatted, for example "10 MB". */
            $parts[] = sprintf(esc_html__('up to %s', 'hash-form'), size_format($max_size));
        }

        if (isset($field['multiple_uploads']) && $field['multiple_uploads'] == 'on') {
            $limit = isset($field['multiple_uploads_limit']) ? absint($field['multiple_uploads_limit']) : 0;
            if ($limit > 0) {
                /* translators: %d: maximum number of files. */
                $parts[] = sprintf(esc_html__('%d files max', 'hash-form'), $limit);
            }
        }

        return implode('  ·  ', $parts);
    }

    public function set_value_before_save($files) {
        $new_files = array();
        $files_arr = explode(',', $files);
        $field = $this->get_field();
        HashFormBuilder::remove_old_temp_files();

        do_action('hashform_file_before_upload_action', array(
            'files_arr' => $files_arr,
            'form_id' => isset($field->form_id) ? $field->form_id : ''
        ));

        if (apply_filters('hashform_store_local', true)) {
            /*
             * The extension is checked again here. What reaches this point is
             * the name the browser was told to post back, and the temp
             * directory is a staging area rather than a trusted one, so a file
             * that got in under a different set of rules does not become
             * permanent on the strength of having been uploaded once.
             *
             * Falls back to the shared list when the field carries no explicit
             * setting, so a field saved before that option existed is still
             * held to something rather than to nothing.
             */
            $field_extensions = hashform_sanitize_allowed_file_extensions((string) HashFormFields::get_option($field, 'extensions'));
            $allowed_extensions = array_filter(array_map('trim', explode(',', $field_extensions)));

            if (!$allowed_extensions) {
                $allowed_extensions = hashform_allowed_file_extensions();
            }

            foreach ($files_arr as $file) {
                $file_info = pathinfo($file);

                // pathinfo() drops any directory part; sanitize_file_name() is
                // idempotent against the name handleUpload() already stored.
                $file_name = isset($file_info['basename']) ? sanitize_file_name(wp_basename($file_info['basename'])) : '';
                $extension = isset($file_info['extension']) ? strtolower($file_info['extension']) : '';

                if ('' === $file_name || '' === $extension) {
                    continue;
                }

                if (!in_array($extension, $allowed_extensions, true)) {
                    continue;
                }

                $upload_dir = wp_upload_dir();

                $file_path = $upload_dir['basedir'] . HASHFORM_UPLOAD_DIR;
                $file_url = $upload_dir['baseurl'] . HASHFORM_UPLOAD_DIR;
                $temp_file_path = $file_path . '/temp/' . $file_name;
                $to_path = $file_path . '/' . $file_name;
                $to_url = $file_url . '/' . $file_name;

                if (!file_exists($temp_file_path)) {
                    continue;
                }

                if (copy($temp_file_path, $to_path)) {
                    $new_files[] = $to_url;
                }
            }
        }
        return implode(',', apply_filters('hashform_file_upload_filters', $new_files));
    }

}
