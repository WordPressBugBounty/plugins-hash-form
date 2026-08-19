<?php

defined('ABSPATH') || die();

class HashFormValidate {

    public static function arrayValsCompare($compareValue, $arrayVals, $condition) {
        $retCase = false;

        switch ($condition) {
            case 'equal':
                if (in_array($compareValue, $arrayVals, true)) {
                    $retCase = true;
                }
                break;

            case 'less_than':
                $retCase = count($arrayVals) > 0;
                foreach ($arrayVals as $val) {
                    if ($compareValue <= $val) {
                        $retCase = false;
                        break;
                    }
                }
                break;

            case 'less_than_or_equal':
                $retCase = count($arrayVals) > 0;
                foreach ($arrayVals as $val) {
                    if ($compareValue < $val) {
                        $retCase = false;
                        break;
                    }
                }
                break;

            case 'greater_than':
                $retCase = count($arrayVals) > 0;
                foreach ($arrayVals as $val) {
                    if ($compareValue >= $val) {
                        $retCase = false;
                        break;
                    }
                }
                break;

            case 'greater_than_or_equal':
                $retCase = count($arrayVals) > 0;
                foreach ($arrayVals as $val) {
                    if ($compareValue > $val) {
                        $retCase = false;
                        break;
                    }
                }
                break;

            case 'is_like':
                foreach ($arrayVals as $val) {
                    if (strpos($val, $compareValue) !== false) {
                        $retCase = true;
                    }
                }
                break;
        }

        return $retCase;
    }

    public static function validate($values) {
        $errors = array();
        $values = self::sanitize_entries($values);

        if (!isset($values['form_id']) || !isset($values['item_meta'])) {
            $errors['form'] = esc_html__('There was a problem with your submission. Please try again.', 'hash-form');
            return $errors;
        }

        if (HashFormHelper::is_admin_page() && is_user_logged_in() && (!isset($values['hashform_submit_entry_' . $values['form_id']]) || !wp_verify_nonce($values['hashform_submit_entry_' . $values['form_id']], 'hashform_submit_entry_nonce'))) {
            $errors['form'] = esc_html__('Nonce Error', 'hash-form');
        }

        $fields = HashFormFields::get_form_fields($values['form_id']);

        $sh_conditions = HashFormBuilder::get_show_hide_conditions(absint($values['form_id']));
        $hidden_arrays = array();

        foreach ($sh_conditions as $cond) {
            $compare_to = isset($values['item_meta'][$cond['compare_to']]) ? $values['item_meta'][$cond['compare_to']] : '';
            $compareValue = $cond['compare_value'];
            $condition = false;

            switch ($cond['compare_condition']) {
                case 'equal':
                    $condition = is_array($compare_to) ? self::arrayValsCompare($compareValue, $compare_to, 'equal') : self::arrayValsCompare($compare_to, preg_split('/\s*,\s*/', $compareValue), 'equal');
                    break;

                case 'not_equal':
                    $condition = !(is_array($compare_to) ? self::arrayValsCompare($compareValue, $compare_to, 'equal') : self::arrayValsCompare($compare_to, preg_split('/\s*,\s*/', $compareValue), 'equal'));
                    break;

                case 'less_than':
                    if (is_array($compare_to)) {
                        $condition = self::arrayValsCompare($compareValue, $compare_to, 'less_than');
                    } else {
                        $compare_to = ($compare_to === '') ? 0 : (int) $compare_to;
                        $condition = ($compare_to < (int) $compareValue);
                    }
                    break;

                case 'less_than_or_equal':
                    if (is_array($compare_to)) {
                        $condition = self::arrayValsCompare($compareValue, $compare_to, 'less_than_or_equal');
                    } else {
                        $compare_to = ($compare_to === '') ? 0 : (int) $compare_to;
                        $condition = ($compare_to <= (int) $compareValue);
                    }
                    break;

                case 'greater_than':
                    if (is_array($compare_to)) {
                        $condition = self::arrayValsCompare($compareValue, $compare_to, 'greater_than');
                    } else {
                        $compare_to = ($compare_to === '') ? 0 : (int) $compare_to;
                        $condition = ($compare_to > (int) $compareValue);
                    }
                    break;

                case 'greater_than_or_equal':
                    if (is_array($compare_to)) {
                        $condition = self::arrayValsCompare($compareValue, $compare_to, 'greater_than_or_equal');
                    } else {
                        $compare_to = ($compare_to === '') ? 0 : (int) $compare_to;
                        $condition = ($compare_to >= (int) $compareValue);
                    }
                    break;

                case 'is_like':
                    $condition = is_array($compare_to) ? self::arrayValsCompare($compareValue, $compare_to, 'is_like') : (strpos($compare_to, $compareValue) !== false);
                    break;

                case 'is_not_like':
                    $condition = !(is_array($compare_to) ? self::arrayValsCompare($compareValue, $compare_to, 'is_like') : (strpos($compare_to, $compareValue) !== false));
                    break;

                default:
                    $condition = false;
                    break;
            }

            if (!$condition) {
                $hidden_arrays[] = $cond['compare_from'];
            }
        }

        foreach ($fields as $field) {
            self::validate_field($field, $errors, $values, !in_array($field->id, $hidden_arrays));
        }

        return $errors;
    }

    public static function validate_field($field, &$errors, $values, $is_field_visible) {
        $field_id = $field->id;

        if ($field->type == 'captcha') {
            $value = isset($values['g-recaptcha-response']) ? $values['g-recaptcha-response'] : '';
        } else {
            $value = isset($values['item_meta'][$field_id]) ? $values['item_meta'][$field_id] : '';
        }

        $value = apply_filters('hash_form_field_value', $value, $field, $values);

        if (!is_array($value)) {
            $value = trim($value);
        }

        /*
         * Outside the is_array() branch it used to sit in. Any field posting
         * an array — a multiple select, a checkbox set, a composite name or
         * address — skipped the required check entirely. It only appeared to
         * work because a field with nothing chosen posts no key at all and
         * arrives here as an empty string instead.
         */
        if ($is_field_visible && '1' == $field->required && self::is_blank_value($value)) {
            $errors['field' . $field_id] = HashFormFields::get_error_msg($field, 'blank');
        }

        self::validate_field_types($errors, $field, $value, $values);
    }

    /**
     * Whether a submitted value counts as nothing entered.
     *
     * Handles the array shapes fields post as well as plain strings: a
     * multiple select or checkbox set posts a list, and a name or address
     * posts an associative array whose parts may each be blank.
     *
     * @param mixed $value
     * @return bool
     */
    private static function is_blank_value($value) {
        if (is_array($value)) {
            foreach ($value as $part) {
                if (!self::is_blank_value($part)) {
                    return false;
                }
            }

            return true;
        }

        return '' === trim((string) $value);
    }

    public static function validate_field_types(&$errors, $field, $value, $values = array()) {
        $field_obj = HashFormFields::get_field_object($field);
        $args['errors'] = $errors;
        $args['value'] = $value;
        $args['id'] = $field->id;

        /*
         * Seven field classes read this when building their validation
         * message, but nothing ever set it: every failed email, url, phone,
         * number, text, spinner or range validation raised an "Undefined
         * array key" warning on PHP 8 and looked the form title up with null.
         */
        $args['form_id'] = $field->form_id;

        /*
         * The whole submitted payload, so a field can read inputs that sit
         * beside it rather than in item_meta. The front end posts the form as
         * one serialised 'data' parameter, so anything a field printed outside
         * item_meta — a captcha token, for instance — never reaches $_POST on
         * its own and can only be found here.
         */
        $args['values'] = $values;

        $new_errors = $field_obj->validate($args);

        if (!empty($new_errors)) {
            $errors = array_merge($errors, $new_errors);
        }

        self::validate_advanced_rules($errors, $field, $value, $values);
    }

    /**
     * Length, pattern, matching and uniqueness rules.
     *
     * These run for every field type rather than being repeated in each field
     * class, and they run on the server because the matching html attributes
     * are only a convenience: a posted request can ignore them entirely.
     */
    public static function validate_advanced_rules(&$errors, $field, $value, $values = array()) {
        $key = 'field' . $field->id;

        // Don't pile a second complaint on a field that already failed.
        if (isset($errors[$key])) {
            return;
        }

        // Composite values (name, address, checkboxes) are not single answers.
        if (is_array($value) || '' === trim((string) $value)) {
            return;
        }

        $value = trim((string) $value);

        $min_length = HashFormFields::get_option($field, 'min_length');

        if ($min_length !== '' && is_numeric($min_length) && mb_strlen($value) < (int) $min_length) {
            /* translators: %s: minimum number of characters. */
            $errors[$key] = sprintf(esc_html__('Please enter at least %s characters.', 'hash-form'), number_format_i18n((int) $min_length));
            return;
        }

        // The max characters option only set a maxlength attribute until now.
        $max_length = HashFormFields::get_option($field, 'max');

        if ($max_length !== '' && is_numeric($max_length) && mb_strlen($value) > (int) $max_length) {
            /* translators: %s: maximum number of characters. */
            $errors[$key] = sprintf(esc_html__('Please enter no more than %s characters.', 'hash-form'), number_format_i18n((int) $max_length));
            return;
        }

        if (self::fails_pattern($field, $value)) {
            $errors[$key] = self::message($field, 'pattern_message', esc_html__('Please match the requested format.', 'hash-form'));
            return;
        }

        if (self::fails_match($field, $value, $values)) {
            $errors[$key] = self::message($field, 'match_message', esc_html__('These fields do not match.', 'hash-form'));
            return;
        }

        if (self::fails_unique($field, $value)) {
            $errors[$key] = self::message($field, 'unique_message', esc_html__('This value has already been submitted.', 'hash-form'));
        }
    }

    private static function message($field, $option, $fallback) {
        $message = HashFormFields::get_option($field, $option);

        return $message ? $message : $fallback;
    }

    private static function fails_pattern($field, $value) {
        $pattern = HashFormFields::get_option($field, 'pattern');

        if (!is_string($pattern) || '' === trim($pattern)) {
            return false;
        }

        // The pattern is stored without delimiters, the way the html attribute
        // takes it. Anchor it so it has to match the whole value.
        $regex = '/^(?:' . str_replace('/', '\/', trim($pattern)) . ')$/u';

        // An invalid pattern must not block a legitimate submission, so a
        // broken regex is treated as no pattern at all.
        $result = @preg_match($regex, $value);

        if (false === $result) {
            return false;
        }

        return 1 !== $result;
    }

    private static function fails_match($field, $value, $values) {
        $match_field = HashFormFields::get_option($field, 'match_field');

        if (!$match_field) {
            return false;
        }

        $other = isset($values['item_meta'][$match_field]) ? $values['item_meta'][$match_field] : '';

        if (is_array($other)) {
            return false;
        }

        return trim((string) $other) !== $value;
    }

    private static function fails_unique($field, $value) {
        if ('on' !== HashFormFields::get_option($field, 'unique')) {
            return false;
        }

        global $wpdb;

        $found = $wpdb->get_var($wpdb->prepare(
                        "SELECT m.id FROM {$wpdb->prefix}hashform_entry_meta AS m
                INNER JOIN {$wpdb->prefix}hashform_entries AS e ON e.id = m.item_id
                WHERE m.field_id = %d AND m.meta_value = %s AND e.status = 'published' LIMIT 1", $field->id, $value));

        return (bool) $found;
    }

    public static function sanitize_entries($values) {
        $sanitize_method = array(
            'hashform_action' => 'sanitize_title',
            'form_id' => 'absint',
            'form_key' => 'sanitize_title',
            'ip' => 'sanitize_title',
            'delivery_status' => 'rest_sanitize_boolean',
            'user_id' => 'absint',
            'status' => 'sanitize_title',
            // The token must survive intact: sanitize_title would lowercase
            // and strip it, making verification impossible.
            'g-recaptcha-response' => 'sanitize_text_field',
            'h-captcha-response' => 'sanitize_text_field'
        );
        return self::sanitize_request($sanitize_method, $values);
    }

    public static function sanitize_request($sanitize_method, $values) {
        $temp_values = $values;
        foreach ($temp_values as $k => $val) {
            if (isset($sanitize_method[$k])) {
                $values[$k] = call_user_func($sanitize_method[$k], $val);
            }
        }

        return $values;
    }

}
