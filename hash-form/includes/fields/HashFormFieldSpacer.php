<?php
defined('ABSPATH') || die();

class HashFormFieldSpacer extends HashFormFieldType {

    protected $type = 'spacer';

    public function field_settings_for_type() {
        return array(
            // The div has its own id, and a value attribute is invalid on it.
            'id' => false,
            'name' => false,
            'value' => false,
            'label' => false,
            'default' => false,
            'description' => false,
            'label_position' => false,
            'required' => false,
            'max_width' => false,
        );
    }

    protected function extra_field_default_opts() {
        return array(
            'spacer_height' => '50',
        );
    }

    /**
     * The gap in whole pixels. Empty means the default 50; 0 means no gap.
     */
    private function spacer_height($field) {
        $height = isset($field['spacer_height']) ? $field['spacer_height'] : '';

        return is_numeric($height) && $height >= 0 ? (int) $height : 50;
    }

    protected function input_html() {
        $field = $this->get_field();
        $height = $this->spacer_height($field);

        // Admin-only label, drawn by the admin stylesheet, so spacers can be told apart on the canvas.
        $label = is_admin()
            /* translators: %d: the spacer's height in pixels. */
            ? sprintf(esc_html__('Spacer · %dpx', 'hash-form'), $height)
            : '';
        ?>
        <div class="hf-spacer-field" id="field_change_height_<?php echo absint($this->field_id); ?>" style="height:<?php echo absint($height); ?>px;"<?php echo $label ? ' data-label="' . esc_attr($label) . '"' : ''; ?> aria-hidden="true" <?php $this->field_attrs(); ?>></div>
        <?php
    }

}
