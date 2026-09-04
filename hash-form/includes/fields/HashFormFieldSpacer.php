<?php
defined('ABSPATH') || die();

class HashFormFieldSpacer extends HashFormFieldType {

    protected $type = 'spacer';

    public function field_settings_for_type() {
        return array(
            // 'id' and 'value' both had to be turned off by hand: the element
            // already carries an id of its own, so a second one was written
            // beside it and thrown away by the parser, and `value` was spelled
            // `valuee` here, so a div went out carrying a value attribute.
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
     * The gap in whole pixels.
     *
     * An empty setting used to be written out as `height:px`, which browsers
     * throw away, collapsing the spacer to nothing. Empty means the 50 the
     * field declares as its default; an author wanting no gap sets 0.
     */
    private function spacer_height($field) {
        $height = isset($field['spacer_height']) ? $field['spacer_height'] : '';

        return is_numeric($height) && $height >= 0 ? (int) $height : 50;
    }

    protected function input_html() {
        $field = $this->get_field();
        $height = $this->spacer_height($field);

        /*
         * On the canvas a spacer is nothing but a gap, which left no way to
         * tell one from another or to click a short one at all. The label is
         * only written in the admin; the stylesheet there draws it.
         */
        $label = is_admin()
            /* translators: %d: the spacer's height in pixels. */
            ? sprintf(esc_html__('Spacer · %dpx', 'hash-form'), $height)
            : '';
        ?>
        <div class="hf-spacer-field" id="field_change_height_<?php echo absint($this->field_id); ?>" style="height:<?php echo absint($height); ?>px;"<?php echo $label ? ' data-label="' . esc_attr($label) . '"' : ''; ?> aria-hidden="true" <?php $this->field_attrs(); ?>></div>
        <?php
    }

}
