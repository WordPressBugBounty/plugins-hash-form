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
                wp_editor($field['description'], $html_id, $args);
                ?>
            </div>
            <p class="description">
                <?php esc_html_e('Scripts and styles are removed when this is saved. Use the theme or a plugin for anything that has to run.', 'hash-form'); ?>
            </p>
        </div>
        <?php
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
        <div class="hf-custom-html-field">
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
