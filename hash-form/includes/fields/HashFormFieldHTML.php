<?php
defined('ABSPATH') || die();

class HashFormFieldHTML extends HashFormFieldType {

    protected $type = 'html';

    public function field_settings_for_type() {
        return array(
            'default' => false,
            'required' => false,
            'label' => false,
            'description' => false,
            'field_alignment' => true,
        );
    }

    protected function extra_field_default_opts() {
        return array(
            'field_alignment' => 'left',
        );
    }

    public function show_primary_options() {
        $field = $this->get_field();

        do_action('hashform_before_html_primary_option');
        ?>
        <div class="hf-form-row">
            <label><?php esc_html_e('Content', 'hash-form'); ?></label>
            <div class="hf-form-text-editor">
                <?php
                $args = array(
                    'textarea_name' => 'field_options[description_' . absint($field['id']) . ']',
                    'textarea_rows' => 8,
                );
                $html_id = 'hf-field-desc_' . absint($field['id']);

                // wp_editor() cannot add attributes to its textarea, so the_editor adds
                // the data-changeme target the live canvas preview mirrors into.
                $preview_id = self::preview_id($field['id']);
                $add_target = function ($editor_html) use ($html_id, $preview_id) {
                    return str_replace(
                            '<textarea',
                            '<textarea data-changeme="' . esc_attr($preview_id) . '"',
                            $editor_html
                    );
                };

                add_filter('the_editor', $add_target);
                wp_editor($field['description'], $html_id, $args);
                remove_filter('the_editor', $add_target);
                ?>
            </div>
            <p class="description">
                <?php esc_html_e('Scripts and styles are removed when this is saved. Use the theme or a plugin for anything that has to run.', 'hash-form'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * The id of the block this field draws on the canvas.
     *
     * @param int $field_id
     * @return string
     */
    public static function preview_id($field_id) {
        return 'hf-html-preview-' . absint($field_id);
    }

    public function input_html() {
        $field = $this->get_field();
        $content = isset($field['description']) ? $field['description'] : '';
        $content = apply_filters('hashform_translate_string', $content, 'Hash Form', HashFormBuilder::get_form_title($field['form_id']) . ' - ' . $field['id'] . ' - ' . 'Field Description');

        // Sanitized on output too: older rows may hold unsanitized content.
        $content = HashFormHelper::sanitize_html_field_content($content);
        ?>
        <div class="hf-custom-html-field"<?php echo is_admin() ? ' id="' . esc_attr(self::preview_id($field['id'])) . '" data-empty-text="' . esc_attr__('Custom HTML - nothing added yet', 'hash-form') . '"' : ''; ?>>
            <?php
            // Same test as the builder's live preview (backend.js): text, or an element that shows without any.
            $is_empty = '' === trim(wp_strip_all_tags($content)) && !preg_match('/<(img|hr|br|input|svg)\b/i', $content);

            if ($is_empty && is_admin()) {
                ?>
                <div class="hf-custom-html-preview">
                    <?php esc_html_e('Custom HTML - nothing added yet', 'hash-form'); ?>
                </div>
                <?php
            } else {
                echo wp_kses_post($content);
            }
            ?>
        </div>
        <?php
    }

}
