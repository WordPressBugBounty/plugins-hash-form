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
        self::sanitize_entries($values);

        if (!isset($values['form_id']) || !isset($values['item_meta'])) {
            $errors['form'] = esc_html__('There was a problem with your submission. Please try again.', 'hash-form');
            return $errors;
        }

        if (HashFormHelper::is_admin_page() && is_user_logged_in() && (!isset($values['hashform_submit_entry_' . $values['form_id']]) || !wp_verify_nonce($values['hashform_submit_entry_' . $values['form_id']], 'hashform_submit_entry_nonce'))) {
            $errors['form'] = esc_html__('Nounce Error', 'hash-form');
        }

        $fields = HashFormFields::get_form_fields($values['form_id']);


        $is_field_visible = true;
        $sh_conditions = HashFormBuilder::get_show_hide_conditions(absint($values['form_id']));
        $hidden_arrays = array();

        foreach ($sh_conditions as $cond) {
            $compare_to = $values['item_meta'][$cond['compare_to']];
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
                    $compare_to = ($compare_to === '') ? 0 : (int) $compare_to;
                    $condition = is_array($compare_to) ? self::arrayValsCompare($compareValue, $compare_to, 'less_than') : ($compare_to < $compareValue);
                    break;

                case 'less_than_or_equal':
                    $compare_to = ($compare_to === '') ? 0 : (int) $compare_to;
                    $condition = is_array($compare_to) ? self::arrayValsCompare($compareValue, $compare_to, 'less_than_or_equal') : ($compare_to <= $compareValue);
                    break;

                case 'greater_than':
                    $compare_to = ($compare_to === '') ? 0 : (int) $compare_to;
                    $condition = is_array($compare_to) ? self::arrayValsCompare($compareValue, $compare_to, 'greater_than') : ($compare_to > $compareValue);
                    break;

                case 'greater_than_or_equal':
                    $compare_to = ($compare_to === '') ? 0 : (int) $compare_to;
                    $condition = is_array($compare_to) ? self::arrayValsCompare($compareValue, $compare_to, 'greater_than_or_equal') : ($compare_to >= $compareValue);
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

            if ($is_field_visible) {
                if ($field->required == '1' && empty($value)) {
                    $errors['field' . $field_id] = HashFormFields::get_error_msg($field, 'blank');
                }
            }
        }

        self::validate_field_types($errors, $field, $value);
    }

    public static function validate_field_types(&$errors, $field, $value) {
        $field_obj = HashFormFields::get_field_object($field);
        $args['errors'] = $errors;
        $args['value'] = $value;
        $args['id'] = $field->id;

        $new_errors = $field_obj->validate($args);

        if (!empty($new_errors)) {
            $errors = array_merge($errors, $new_errors);
        }
    }

    public static function sanitize_entries($values) {
        $sanitize_method = array(
            'hashform_action' => 'sanitize_title',
            'form_id' => 'absint',
            'form_key' => 'sanitize_title',
            'ip' => 'sanitize_title',
            'delivery_status' => 'rest_sanitize_boolean',
            'ip' => 'sanitize_title',
            'user_id' => 'absint',
            'status' => 'sanitize_title',
            'g-recaptcha-response' => 'sanitize_title'
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
