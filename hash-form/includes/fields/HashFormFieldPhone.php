<?php
defined('ABSPATH') || die();

class HashFormFieldPhone extends HashFormFieldType {

    protected $type = 'phone';

    protected function field_settings_for_type() {
        return array(
            'advanced_validation' => true,
            'clear_on_focus' => true,
            'invalid' => true,
            'format' => true,
            'max' => true,
        );
    }

    public function validate($args) {
        $errors = array();
        $pattern = self::phone_format($this->field);
        $max_length = intval(HashFormFields::get_option($this->field, 'max'));

        // An empty optional field must not fail the pattern.
        if ('' !== $pattern && '' !== (string) $args['value'] && !preg_match($pattern, $args['value'])) {
            $errors['field' . $args['id']] = apply_filters('hashform_translate_string', HashFormFields::get_error_msg($this->field, 'invalid'), 'Hash Form', HashFormBuilder::get_form_title($args['form_id']) . ' - ' . $args['id'] . ' - ' . 'Field Validation Message');
        }

        if ($max_length && strlen($args['value']) > $max_length) {
            $errors['field' . $args['id']] = HashFormFields::get_error_msg($this->field, 'max_char');
        }
        return $errors;
    }

    /**
     * The regex a phone value must match, or '' for no check.
     */
    public static function phone_format($field) {
        $pattern = trim((string) HashFormFields::get_option($field, 'format'));

        if ('' === $pattern) {
            return '';
        }

        // Escape "/" delimiters as HashFormFieldText::format() does, normalizing already-escaped ones first.
        return '/' . str_replace('/', '\/', str_replace('\/', '/', $pattern)) . '/';
    }

    /**
     * The pattern offered as a preset in the field settings; never applied unless chosen.
     */
    public static function default_phone_pattern() {
        return '^((\+\d{1,3}(-|.| )?\(?\d\)?(-| |.)?\d{1,5})|(\(?\d{2,6}\)?))(-|.| )?(\d{3,4})(-|.| )?(\d{4})(( x| ext)\d{1,5}){0,1}$';
    }

    protected function input_html() {
        ?>
        <input type="text" <?php $this->field_attrs(); ?> />
        <?php
    }

}
