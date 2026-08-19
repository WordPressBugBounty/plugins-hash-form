<?php
defined('ABSPATH') || die();

class HashFormFieldSeparator extends HashFormFieldType {

    protected $type = 'separator';

    public function field_settings_for_type() {
        return array(
            'label' => false,
            'default' => false,
            'description' => false,
            'label_position' => false,
            'required' => false,
            'field_alignment' => true,
        );
    }

    protected function extra_field_default_opts() {
        return array(
            'border_width' => '2',
            'border_style' => 'solid',
            'separator_spacing' => '',
            'field_alignment' => 'left',
        );
    }

    /**
     * The line styles the setting offers.
     */
    public static function border_styles() {
        return array('solid', 'double', 'dotted', 'dashed', 'groove', 'ridge');
    }

    /**
     * The stored style, held to that list.
     *
     * It used to go into the style attribute as it stood, where esc_attr() is
     * no protection: `solid; height:80px` carries no quotes to escape and each
     * declaration after the first simply applies.
     */
    private function border_style($field) {
        $style = isset($field['border_style']) ? strtolower(trim($field['border_style'])) : '';

        return in_array($style, self::border_styles(), true) ? $style : 'solid';
    }

    /**
     * The line's thickness in whole pixels.
     *
     * An empty setting used to be written out as `border-bottom-width:px`,
     * which browsers throw away, leaving the line at whatever `medium` happens
     * to be — 3px — rather than at the 2px the field says it defaults to.
     */
    private function border_width($field) {
        $width = isset($field['border_width']) ? $field['border_width'] : '';

        return is_numeric($width) && $width >= 0 ? (int) $width : 2;
    }

    protected function input_html() {
        $field = $this->get_field();

        $style = array(
            'border-bottom-style:' . $this->border_style($field),
            'border-bottom-width:' . $this->border_width($field) . 'px',
        );

        $spacing = isset($field['separator_spacing']) ? $field['separator_spacing'] : '';

        if (is_numeric($spacing)) {
            $style[] = 'margin-top:' . absint($spacing) . 'px';
            $style[] = 'margin-bottom:' . absint($spacing) . 'px';
        }
        ?>
        <hr class="hf-separator-border" style="<?php echo esc_attr(implode('; ', $style) . ';'); ?>" id="field_change_style_<?php echo absint($field['id']); ?>" />
        <?php
    }

}
