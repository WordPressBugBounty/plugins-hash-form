<?php
defined('ABSPATH') || die();

class HashFormFieldParagraph extends HashFormFieldType {

    protected $type = 'paragraph';

    public function field_settings_for_type() {
        return array(
            'label' => false,
            'default' => false,
            'description' => false,
            'label_position' => false,
            'required' => false,
            'content' => true,
            'field_alignment' => true,
        );
    }

    protected function extra_field_default_opts() {
        return array(
            'content' => 'Paragraph',
            'text_alignment' => 'left',
            'field_alignment' => 'left',
        );
    }

    protected function input_html() {
        $field = $this->get_field();
        // A paragraph field with no text saved yet has no key for it.
        $content = isset($field['content']) ? $field['content'] : '';
        $content = apply_filters('hashform_translate_string', $content, 'Hash Form', HashFormBuilder::get_form_title($field['form_id']) . ' - ' . $field['id'] . ' - ' . 'Field Content');

        // Nothing to say, nothing in the page.
        if ('' === trim(wp_strip_all_tags($content))) {
            return;
        }
        ?>
        <div class="hf-paragraph-field" id="hf-field-<?php echo absint($field['id']); ?>">
            <?php
            /*
             * wpautop, so a blank line between two thoughts is two paragraphs
             * and a single newline is a line break. The text used to go through
             * esc_html() into a bare div, which threw away every break the
             * author typed and left the field's own <p> styles — the whole
             * Paragraph panel in the styler — matching nothing at all.
             */
            echo wp_kses_post(wpautop($content));
            ?>
        </div>
        <?php
    }

}
