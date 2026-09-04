<?php
defined('ABSPATH') || die();

class HashFormFieldText extends HashFormFieldType {

    protected $type = 'text';

    protected function field_settings_for_type() {
        return array(
            'advanced_validation' => true,
            'clear_on_focus' => true,
            'invalid' => true,
            'format' => true,
            'max' => true
        );
    }

    public function validate($args) {
        $errors = array();

        $format = trim((string) HashFormFields::get_option($this->field, 'format'));
        $max_length = intval(HashFormFields::get_option($this->field, 'max'));

        // Only run the format check when one is configured and a value was
        // submitted — an empty optional field must not fail the pattern.
        if ($format !== '' && $args['value'] !== '' && !preg_match(self::format($this->field), $args['value'])) {
            $errors['field' . $args['id']] = apply_filters('hashform_translate_string', HashFormFields::get_error_msg($this->field, 'invalid'), 'Hash Form', HashFormBuilder::get_form_title($args['form_id']) . ' - ' . $args['id'] . ' - ' . 'Field Validation Message');
        }

        if ($max_length && strlen($args['value']) > $max_length) {
            $errors['field' . $args['id']] = HashFormFields::get_error_msg($this->field, 'max_char');
        }
        return $errors;
    }

    public static function format($field) {
        $pattern = HashFormFields::get_option($field, 'format');
        // Escape raw delimiters so an admin pattern containing "/" cannot
        // break the expression (preg_match would then always fail). Already
        // escaped slashes are normalized first to avoid double escaping.
        $pattern = str_replace('\/', '/', $pattern);
        return '/' . str_replace('/', '\/', $pattern) . '/';
    }

    protected function input_html() {
        ?>
        <input type="text" <?php $this->field_attrs(); ?> />
        <?php
    }

}
