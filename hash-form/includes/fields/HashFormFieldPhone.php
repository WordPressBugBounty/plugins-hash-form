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

        if ('' !== $pattern && !preg_match($pattern, $args['value'])) {
            $errors['field' . $args['id']] = apply_filters('hashform_translate_string', HashFormFields::get_error_msg($this->field, 'invalid'), 'Hash Form', HashFormBuilder::get_form_title($args['form_id']) . ' - ' . $args['id'] . ' - ' . 'Field Validation Message');
        }

        if ($max_length && strlen($args['value']) > $max_length) {
            $errors['field' . $args['id']] = HashFormFields::get_error_msg($this->field, 'max_char');
        }
        return $errors;
    }

    /**
     * The regex a phone value must match, or '' for no check.
     *
     * The test used to be inverted: a custom format was thrown away and
     * replaced by the built-in one, while no custom format left $pattern
     * empty and produced '//', which matches every string. So the setting did
     * nothing and the field accepted anything at all.
     */
    public static function phone_format($field) {
        $pattern = trim((string) HashFormFields::get_option($field, 'format'));

        if ('' === $pattern) {
            return '';
        }

        return '/' . $pattern . '/';
    }

    /**
     * The pattern offered as the default in the field settings. Kept out of
     * phone_format() so that turning it on is a choice the form makes, not
     * something imposed on every phone field that has never been configured.
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
