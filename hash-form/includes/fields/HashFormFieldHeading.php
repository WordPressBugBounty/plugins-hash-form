<?php
defined('ABSPATH') || die();

class HashFormFieldHeading extends HashFormFieldType {

    protected $type = 'heading';

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
            // Not h1: the page the form sits in already has one.
            'heading_type' => 'h3',
            'content' => 'Heading',
            'text_alignment' => 'left',
            'field_alignment' => 'left',
        );
    }

    /**
     * The tag to use, held to h1-h6.
     */
    private function heading_tag($field) {
        $tag = isset($field['heading_type']) ? strtolower(trim($field['heading_type'])) : '';

        return in_array($tag, hashform_heading_levels(), true) ? $tag : 'h3';
    }

    protected function input_html() {
        $field = $this->get_field();
        $content = isset($field['content']) ? $field['content'] : '';
        $content = apply_filters('hashform_translate_string', $content, 'Hash Form', HashFormBuilder::get_form_title($field['form_id']) . ' - ' . $field['id'] . ' - ' . 'Field Content');

        // Plain text only; the content option is shared with the paragraph field, which allows markup.
        $content = wp_strip_all_tags($content);

        // Skip an empty heading entirely.
        if ('' === trim($content)) {
            return;
        }

        $tag = $this->heading_tag($field);
        ?>
        <<?php echo esc_attr($tag); ?> class="hf-heading-field" id="hf-field-<?php echo absint($field['id']); ?>"><?php echo esc_html($content); ?></<?php echo esc_attr($tag); ?>>
        <?php
    }

}
