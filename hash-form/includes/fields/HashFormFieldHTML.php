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

                /*
                 * wp_editor() gives no way to put an attribute on the textarea
                 * it prints, and the canvas has to know where to mirror what is
                 * typed. handleTinyMceChange() already writes the editor back
                 * to this textarea and fires change on it, so naming a target
                 * here is all the live preview needs.
                 */
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

        /*
         * Sanitized again on the way out, not only on the way in: rows saved
         * before that was done still hold whatever was pasted into them.
         */
        $content = HashFormHelper::sanitize_html_field_content($content);
        ?>
        <div class="hf-custom-html-field"<?php echo is_admin() ? ' id="' . esc_attr(self::preview_id($field['id'])) . '" data-empty-text="' . esc_attr__('Custom HTML - nothing added yet', 'hash-form') . '"' : ''; ?>>
            <?php
            if ('' === trim(wp_strip_all_tags($content)) && is_admin()) {
                /*
                 * The canvas used to show this whatever the field held, so
                 * there was no way to see what you had written without saving
                 * and looking at the page. What is drawn now is what the page
                 * will draw, having been through the same sanitizer.
                 */
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
