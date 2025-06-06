<?php
defined('ABSPATH') || die();

class HashFormFieldNumber extends HashFormFieldType {

    protected $type = 'number';

    protected function field_settings_for_type() {
        return array(
            'clear_on_focus' => true,
            'invalid' => true,
            'range' => true,
        );
    }

    protected function extra_field_default_opts() {
        return array(
            'minnum' => 0,
            'maxnum' => 9999999,
            'step' => 1
        );
    }

    public function validate($args) {
        $errors = array();
        $this->remove_commas_from_number($args);

        if (!is_numeric($args['value']) && '' !== $args['value'])
            $errors['field' . $args['id']] = apply_filters('hf_translate_string', HashFormFields::get_error_msg($this->field, 'invalid'), 'Hash Form', HashFormBuilder::get_form_title($this->args['form_id']) . ' - ' . $args['id'] . ' - ' . 'Field Validation Message');

        if ($args['value'] != '') {
            $minnum = HashFormFields::get_option($this->field, 'minnum');
            $maxnum = HashFormFields::get_option($this->field, 'maxnum');
            if ($maxnum !== '' && $minnum !== '') {
                $value = (float) $args['value'];
                if ($value < $minnum) {
                    $errors['field' . $args['id']] = esc_html__('Please select a higher number', 'hash-form');
                } elseif ($value > $maxnum) {
                    $errors['field' . $args['id']] = esc_html__('Please select a lower number', 'hash-form');
                }
            }
            $this->validate_step($errors, $args);
        }
        return $errors;
    }

    private function validate_step(&$errors, $args) {
        if (isset($errors['field' . $args['id']]))
            return;
        $step = HashFormFields::get_option($this->field, 'step');
        if (!$step || !is_numeric($step))
            return;
        $result = $this->check_value_is_valid_with_step($args['value'], $step);
        if (!$result)
            return;
        $errors['field' . $args['id']] = sprintf(__('Please enter a valid value. Two nearest valid values are %1$s and %2$s', 'hash-form'), floatval($result[0]), floatval($result[1]));
    }

    private function check_value_is_valid_with_step($value, $step) {
        $decimals = max(HashFormHelper::count_decimals($value), HashFormHelper::count_decimals($step));
        $pow = pow(10, $decimals);
        $value = intval($pow * $value);
        $step = intval($pow * $step);
        $div = $value / $step;
        if (is_int($div))
            return 0;
        $div = floor($div);
        return array($div * $step / $pow, ($div + 1) * $step / $pow);
    }

    private function remove_commas_from_number(&$args) {
        if (strpos($args['value'], ',')) {
            $args['value'] = str_replace(',', '', $args['value']);
        }
    }

    public function sanitize_value(&$value) {
        return hashform_sanitize_float($value);
    }

    protected function input_html() {
        $field_type = $this->type;
        ?>
        <input type="<?php echo esc_attr($field_type); ?>" <?php $this->field_attrs(); ?> />
        <?php
    }

}
