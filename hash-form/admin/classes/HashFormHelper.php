<?php
defined('ABSPATH') || die();

class HashFormHelper {

    public static function get_fields_array($form_id) {
        $fields = HashFormFields::get_form_fields($form_id);

        $values['fields'] = array();

        if (empty($fields))
            return $values;

        foreach ((array) $fields as $field) {
            $field_array = HashFormFields::covert_field_obj_to_array($field);
            $values['fields'][] = $field_array;
        }

        $form_options_defaults = self::get_form_options_default();

        return array_merge($form_options_defaults, $values);
    }

    /* Sanitizes value and returns param value */

    public static function get_var($param, $sanitize = 'sanitize_text_field', $default = '') {
        $value = (($_GET && isset($_GET[$param])) ? wp_unslash($_GET[$param]) : $default);
        return self::sanitize_value($sanitize, $value);
    }

    public static function get_post($param, $sanitize = 'sanitize_text_field', $default = '', $sanitize_array = array()) {
        $value = (isset($_POST[$param]) ? wp_unslash($_POST[$param]) : $default);
        if (!empty($sanitize_array) && is_array($value)) {
            return self::sanitize_array($value, $sanitize_array);
        }
        return self::sanitize_value($sanitize, $value);
    }

    public static function sanitize_value($sanitize, &$value) {
        if (!empty($sanitize)) {
            if (is_array($value)) {
                $temp_values = $value;
                foreach ($temp_values as $k => $v) {
                    $value[$k] = self::sanitize_value($sanitize, $value[$k]);
                }
            } else {
                $value = call_user_func($sanitize, ($value ? htmlspecialchars_decode($value) : ''));
            }
        }

        return $value;
    }

    public static function get_unique_key($table_name, $column_name, $limit = 6) {
        $values = 'ABCDEFGHIJKLMOPQRSTUVXWYZ0123456789';
        $count = strlen($values);
        $count--;
        $key = '';
        for ($x = 1; $x <= $limit; $x++) {
            $rand_var = rand(0, $count);
            $key .= substr($values, $rand_var, 1);
        }

        $key = strtolower($key);
        $existing_keys = self::check_table_keys($table_name, $column_name);

        if (in_array($key, $existing_keys)) {
            self::get_unique_key($table_name, $column_name, $limit = 6);
        }

        return $key;
    }

    public static function check_table_keys($table_name, $column_name) {
        global $wpdb;
        $tbl_name = $wpdb->prefix . $table_name;
        $query = $wpdb->prepare("SELECT {$column_name} FROM {$tbl_name} WHERE id!=%d", 0);
        $results = $wpdb->get_results($query, ARRAY_A);
        return array_column($results, $column_name);
    }

    public static function is_admin_page($page = 'hashform') {
        $get_page = self::get_var('page', 'sanitize_title');
        if (is_admin() && $get_page === $page) {
            return true;
        }

        return false;
    }

    public static function is_preview_page() {
        $action = self::get_var('action', 'sanitize_title');
        return (is_admin() && ($action == 'hashform_preview'));
    }

    public static function is_form_builder_page() {
        $action = self::get_var('hashform_action', 'sanitize_title');
        $builder_actions = self::get_form_builder_actions();
        return self::is_admin_page('hashform') && (in_array($action, $builder_actions));
    }

    public static function is_form_listing_page() {
        if (!self::is_admin_page('hashform')) {
            return false;
        }

        $action = self::get_var('hashform_action', 'sanitize_title');
        $builder_actions = self::get_form_builder_actions();
        return !$action || in_array($action, $builder_actions);
    }

    public static function get_form_builder_actions() {
        return array('edit', 'settings', 'style');
    }

    public static function start_field_array($field) {
        return array(
            'id' => $field->id,
            'default_value' => $field->default_value,
            'name' => $field->name,
            'description' => $field->description,
            'options' => $field->options,
            'required' => $field->required,
            'field_key' => $field->field_key,
            'field_order' => $field->field_order,
            'form_id' => $field->form_id,
        );
    }

    public static function show_search_box($atts) {
        $defaults = array(
            'placeholder' => '',
            'tosearch' => '',
            'text' => esc_html__('Search', 'hash-form'),
            'input_id' => '',
        );
        $atts = array_merge($defaults, $atts);
        $class = 'hf-search-fields-input';
        $input_id = $atts['input_id'] . '-search-input';
        ?>
        <div class="hf-search-fields">
            <span class="mdi mdi-magnify"></span>
            <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s" value="<?php _admin_search_query(); ?>" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" class="<?php echo esc_attr($class); ?>" data-tosearch="<?php echo esc_attr($atts['tosearch']); ?>" <?php if (!empty($atts['tosearch'])) { ?> autocomplete="off" <?php } ?> />
            <?php if (empty($atts['tosearch']))
                submit_button($atts['text'], 'button-secondary', '', false, array('id' => 'search-submit')); ?>
        </div>
        <?php
    }

    public static function convert_date_format($date) {
        $timestamp = strtotime($date);

        $new_date = date('Y/m/d', $timestamp);
        $new_time = date('g:i a', $timestamp);

        return $new_date . ' ' . esc_html__('at', 'hash-form') . ' ' . $new_time;
    }

    public static function parse_json_array($array = array()) {
        $array = json_decode($array, true);
        $fields = array();
        foreach ($array as $val) {
            $name = $val['name'];
            $value = $val['value'];
            if (strpos($name, '[]') !== false) {
                $fields[str_replace('[]', '', $name)][] = $value;
            } else if (strpos($name, '[') !== false) {
                $ids = explode('[', str_replace(']', '', $name));
                $count = count($ids);

                switch ($count):
                    case 1:
                        $fields[$ids[0]] = $value;
                        break;
                    case 2:
                        $fields[$ids[0]][$ids[1]] = $value;
                        break;
                    case 3:
                        $fields[$ids[0]][$ids[1]][$ids[2]] = $value;
                        break;
                    case 4:
                        $fields[$ids[0]][$ids[1]][$ids[2]][$ids[3]] = $value;
                        break;
                    case 5:
                        $fields[$ids[0]][$ids[1]][$ids[2]][$ids[3]][$ids[4]] = $value;
                        break;
                endswitch;
            } else {
                $fields[$name] = $value;
            }
        }
        return $fields;
    }

    public static function process_form_array($form) {
        if (!$form) {
            return;
        }

        $new_values = array(
            'id' => $form->id,
            'form_key' => $form->form_key,
            'name' => $form->name,
            'description' => $form->description,
            'status' => $form->status,
        );

        if (is_array($form->options)) {
            $form_options = wp_parse_args($form->options, self::get_form_options_default());

            foreach ($form_options as $opt => $value) {
                $new_values[$opt] = $value;
            }
        }

        return $new_values;
    }

    public static function recursive_parse_args($args, $defaults) {
        $new_args = (array) $defaults;
        foreach ($args as $key => $value) {
            if (is_array($value) && isset($new_args[$key])) {
                $new_args[$key] = self::recursive_parse_args($value, $new_args[$key]);
            } else {
                $new_args[$key] = $value;
            }
        }
        return $new_args;
    }

    public static function get_form_options_checkbox_settings() {
        return array(
            'show_title' => 'off',
            'show_description' => 'off',
        );
    }

    public static function get_form_settings_checkbox_settings() {
        return array(
            'enable_ar' => 'off',
        );
    }

    public static function get_form_options_default() {
        return array(
            'show_title' => 'on',
            'show_description' => 'off',
            'title' => '',
            'description' => '',
            'submit_value' => esc_html__('Submit', 'hash-form'),
            'form_css_class' => '',
            'submit_btn_css_class' => '',
            'submit_btn_alignment' => 'left',
        );
    }

    public static function get_form_settings_default($name = '') {
        $return = array(
            'email_to' => '[admin_email]',
            'email_from' => '[admin_email]',
            'reply_to_email' => '',
            'email_from_name' => get_bloginfo('name'),
            'email_subject' => esc_html__('New Entry: ', 'hash-form') . esc_html($name),
            'email_message' => '#form_details',
            'enable_ar' => 'off',
            'from_ar' => '[admin_email]',
            'from_ar_name' => get_bloginfo('name'),
            'reply_to_ar' => '',
            'email_subject_ar' => esc_html__('Entry Submitted: ', 'hash-form') . esc_html($name),
            'email_message_ar' => esc_html__('Thank you for sending email. We will get back to you as soon as possible.', 'hash-form'),
            'confirmation_type' => 'show_message',
            'confirmation_message' => esc_html__('Form Submitted Successfully', 'hash-form'),
            'error_message' => esc_html__('Sorry, An error Occurred! Your form cannot be submitted.', 'hash-form'),
            'show_page_id' => '',
            'redirect_url_page' => '',
        );
        return apply_filters('hashform_form_settings_default', $return);
    }

    public static function get_form_styles_default() {
        return array(
            'form_style' => '',
        );
    }

    public static function get_form_options_sanitize_rules() {
        return array(
            'show_title' => 'hashform_sanitize_checkbox',
            'show_description' => 'hashform_sanitize_checkbox',
            'title' => 'sanitize_text_field',
            'description' => 'sanitize_text_field',
            'submit_value' => 'sanitize_text_field',
            'form_css_class' => 'sanitize_text_field',
            'submit_btn_css_class' => 'sanitize_text_field',
            'submit_btn_alignment' => 'sanitize_text_field',
        );
    }

    public static function get_form_settings_sanitize_rules() {
        $return = array(
            'email_to' => 'sanitize_text_field',
            'email_from' => 'sanitize_text_field',
            'reply_to_email' => 'sanitize_text_field',
            'email_from_name' => 'sanitize_text_field',
            'email_subject' => 'sanitize_text_field',
            'email_message' => 'sanitize_text_field',
            'enable_ar' => 'hashform_sanitize_checkbox',
            'from_ar' => 'sanitize_text_field',
            'from_ar_name' => 'sanitize_text_field',
            'reply_to_ar' => 'sanitize_text_field',
            'email_subject_ar' => 'sanitize_text_field',
            'email_message_ar' => 'sanitize_text_field',
            'confirmation_type' => 'sanitize_text_field',
            'confirmation_message' => 'sanitize_text_field',
            'error_message' => 'sanitize_text_field',
            'show_page_id' => 'sanitize_text_field',
            'redirect_url_page' => 'sanitize_url',
            'condition_action' => array(
                'sanitize_text_field'
            ),
            'compare_from' => array(
                'sanitize_text_field'
            ),
            'compare_to' => array(
                'sanitize_text_field'
            ),
            'compare_condition' => array(
                'sanitize_text_field'
            ),
            'compare_value' => array(
                'sanitize_text_field'
            )
        );
        return apply_filters('hashform_settings_sanitize_rules', $return);
    }

    public static function get_form_styles_sanitize_rules() {
        return array(
            'form_style' => 'sanitize_text_field',
            'form_style_template' => 'absint'
        );
    }

    public static function get_form_fields_default() {
        return array(
            'field_order' => 0,
            'field_key' => '',
            'required' => false,
            'type' => '',
            'description' => '',
            'options' => '',
            'name' => '',
        );
    }

    public static function get_countries() {
        $countries = array(
            esc_html_x('Afghanistan', 'Country Name', 'hash-form'),
            esc_html_x('Aland Islands', 'Country Name', 'hash-form'),
            esc_html_x('Albania', 'Country Name', 'hash-form'),
            esc_html_x('Algeria', 'Country Name', 'hash-form'),
            esc_html_x('American Samoa', 'Country Name', 'hash-form'),
            esc_html_x('Andorra', 'Country Name', 'hash-form'),
            esc_html_x('Angola', 'Country Name', 'hash-form'),
            esc_html_x('Anguilla', 'Country Name', 'hash-form'),
            esc_html_x('Antarctica', 'Country Name', 'hash-form'),
            esc_html_x('Antigua and Barbuda', 'Country Name', 'hash-form'),
            esc_html_x('Argentina', 'Country Name', 'hash-form'),
            esc_html_x('Armenia', 'Country Name', 'hash-form'),
            esc_html_x('Aruba', 'Country Name', 'hash-form'),
            esc_html_x('Australia', 'Country Name', 'hash-form'),
            esc_html_x('Austria', 'Country Name', 'hash-form'),
            esc_html_x('Azerbaijan', 'Country Name', 'hash-form'),
            esc_html_x('Bahamas', 'Country Name', 'hash-form'),
            esc_html_x('Bahrain', 'Country Name', 'hash-form'),
            esc_html_x('Bangladesh', 'Country Name', 'hash-form'),
            esc_html_x('Barbados', 'Country Name', 'hash-form'),
            esc_html_x('Belarus', 'Country Name', 'hash-form'),
            esc_html_x('Belgium', 'Country Name', 'hash-form'),
            esc_html_x('Belize', 'Country Name', 'hash-form'),
            esc_html_x('Benin', 'Country Name', 'hash-form'),
            esc_html_x('Bermuda', 'Country Name', 'hash-form'),
            esc_html_x('Bhutan', 'Country Name', 'hash-form'),
            esc_html_x('Bolivia', 'Country Name', 'hash-form'),
            esc_html_x('Bonaire, Sint Eustatius and Saba', 'Country Name', 'hash-form'),
            esc_html_x('Bosnia and Herzegovina', 'Country Name', 'hash-form'),
            esc_html_x('Botswana', 'Country Name', 'hash-form'),
            esc_html_x('Bouvet Island', 'Country Name', 'hash-form'),
            esc_html_x('Brazil', 'Country Name', 'hash-form'),
            esc_html_x('British Indian Ocean Territory', 'Country Name', 'hash-form'),
            esc_html_x('Brunei', 'Country Name', 'hash-form'),
            esc_html_x('Bulgaria', 'Country Name', 'hash-form'),
            esc_html_x('Burkina Faso', 'Country Name', 'hash-form'),
            esc_html_x('Burundi', 'Country Name', 'hash-form'),
            esc_html_x('Cambodia', 'Country Name', 'hash-form'),
            esc_html_x('Cameroon', 'Country Name', 'hash-form'),
            esc_html_x('Canada', 'Country Name', 'hash-form'),
            esc_html_x('Cape Verde', 'Country Name', 'hash-form'),
            esc_html_x('Cayman Islands', 'Country Name', 'hash-form'),
            esc_html_x('Central African Republic', 'Country Name', 'hash-form'),
            esc_html_x('Chad', 'Country Name', 'hash-form'),
            esc_html_x('Chile', 'Country Name', 'hash-form'),
            esc_html_x('China', 'Country Name', 'hash-form'),
            esc_html_x('Christmas Island', 'Country Name', 'hash-form'),
            esc_html_x('Cocos (Keeling) Islands', 'Country Name', 'hash-form'),
            esc_html_x('Colombia', 'Country Name', 'hash-form'),
            esc_html_x('Comoros', 'Country Name', 'hash-form'),
            esc_html_x('Congo', 'Country Name', 'hash-form'),
            esc_html_x('Cook Islands', 'Country Name', 'hash-form'),
            esc_html_x('Costa Rica', 'Country Name', 'hash-form'),
            esc_html_x('C&ocirc;te d\'Ivoire', 'Country Name', 'hash-form'),
            esc_html_x('Croatia', 'Country Name', 'hash-form'),
            esc_html_x('Cuba', 'Country Name', 'hash-form'),
            esc_html_x('Curacao', 'Country Name', 'hash-form'),
            esc_html_x('Cyprus', 'Country Name', 'hash-form'),
            esc_html_x('Czech Republic', 'Country Name', 'hash-form'),
            esc_html_x('Denmark', 'Country Name', 'hash-form'),
            esc_html_x('Djibouti', 'Country Name', 'hash-form'),
            esc_html_x('Dominica', 'Country Name', 'hash-form'),
            esc_html_x('Dominican Republic', 'Country Name', 'hash-form'),
            esc_html_x('East Timor', 'Country Name', 'hash-form'),
            esc_html_x('Ecuador', 'Country Name', 'hash-form'),
            esc_html_x('Egypt', 'Country Name', 'hash-form'),
            esc_html_x('El Salvador', 'Country Name', 'hash-form'),
            esc_html_x('Equatorial Guinea', 'Country Name', 'hash-form'),
            esc_html_x('Eritrea', 'Country Name', 'hash-form'),
            esc_html_x('Estonia', 'Country Name', 'hash-form'),
            esc_html_x('Ethiopia', 'Country Name', 'hash-form'),
            esc_html_x('Falkland Islands (Malvinas)', 'Country Name', 'hash-form'),
            esc_html_x('Faroe Islands', 'Country Name', 'hash-form'),
            esc_html_x('Fiji', 'Country Name', 'hash-form'),
            esc_html_x('Finland', 'Country Name', 'hash-form'),
            esc_html_x('France', 'Country Name', 'hash-form'),
            esc_html_x('French Guiana', 'Country Name', 'hash-form'),
            esc_html_x('French Polynesia', 'Country Name', 'hash-form'),
            esc_html_x('French Southern Territories', 'Country Name', 'hash-form'),
            esc_html_x('Gabon', 'Country Name', 'hash-form'),
            esc_html_x('Gambia', 'Country Name', 'hash-form'),
            esc_html_x('Georgia', 'Country Name', 'hash-form'),
            esc_html_x('Germany', 'Country Name', 'hash-form'),
            esc_html_x('Ghana', 'Country Name', 'hash-form'),
            esc_html_x('Gibraltar', 'Country Name', 'hash-form'),
            esc_html_x('Greece', 'Country Name', 'hash-form'),
            esc_html_x('Greenland', 'Country Name', 'hash-form'),
            esc_html_x('Grenada', 'Country Name', 'hash-form'),
            esc_html_x('Guadeloupe', 'Country Name', 'hash-form'),
            esc_html_x('Guam', 'Country Name', 'hash-form'),
            esc_html_x('Guatemala', 'Country Name', 'hash-form'),
            esc_html_x('Guernsey', 'Country Name', 'hash-form'),
            esc_html_x('Guinea', 'Country Name', 'hash-form'),
            esc_html_x('Guinea-Bissau', 'Country Name', 'hash-form'),
            esc_html_x('Guyana', 'Country Name', 'hash-form'),
            esc_html_x('Haiti', 'Country Name', 'hash-form'),
            esc_html_x('Heard Island and McDonald Islands', 'Country Name', 'hash-form'),
            esc_html_x('Holy See', 'Country Name', 'hash-form'),
            esc_html_x('Honduras', 'Country Name', 'hash-form'),
            esc_html_x('Hong Kong', 'Country Name', 'hash-form'),
            esc_html_x('Hungary', 'Country Name', 'hash-form'),
            esc_html_x('Iceland', 'Country Name', 'hash-form'),
            esc_html_x('India', 'Country Name', 'hash-form'),
            esc_html_x('Indonesia', 'Country Name', 'hash-form'),
            esc_html_x('Iran', 'Country Name', 'hash-form'),
            esc_html_x('Iraq', 'Country Name', 'hash-form'),
            esc_html_x('Ireland', 'Country Name', 'hash-form'),
            esc_html_x('Israel', 'Country Name', 'hash-form'),
            esc_html_x('Isle of Man', 'Country Name', 'hash-form'),
            esc_html_x('Italy', 'Country Name', 'hash-form'),
            esc_html_x('Jamaica', 'Country Name', 'hash-form'),
            esc_html_x('Japan', 'Country Name', 'hash-form'),
            esc_html_x('Jersey', 'Country Name', 'hash-form'),
            esc_html_x('Jordan', 'Country Name', 'hash-form'),
            esc_html_x('Kazakhstan', 'Country Name', 'hash-form'),
            esc_html_x('Kenya', 'Country Name', 'hash-form'),
            esc_html_x('Kiribati', 'Country Name', 'hash-form'),
            esc_html_x('North Korea', 'Country Name', 'hash-form'),
            esc_html_x('South Korea', 'Country Name', 'hash-form'),
            esc_html_x('Kosovo', 'Country Name', 'hash-form'),
            esc_html_x('Kuwait', 'Country Name', 'hash-form'),
            esc_html_x('Kyrgyzstan', 'Country Name', 'hash-form'),
            esc_html_x('Laos', 'Country Name', 'hash-form'),
            esc_html_x('Latvia', 'Country Name', 'hash-form'),
            esc_html_x('Lebanon', 'Country Name', 'hash-form'),
            esc_html_x('Lesotho', 'Country Name', 'hash-form'),
            esc_html_x('Liberia', 'Country Name', 'hash-form'),
            esc_html_x('Libya', 'Country Name', 'hash-form'),
            esc_html_x('Liechtenstein', 'Country Name', 'hash-form'),
            esc_html_x('Lithuania', 'Country Name', 'hash-form'),
            esc_html_x('Luxembourg', 'Country Name', 'hash-form'),
            esc_html_x('Macao', 'Country Name', 'hash-form'),
            esc_html_x('Macedonia', 'Country Name', 'hash-form'),
            esc_html_x('Madagascar', 'Country Name', 'hash-form'),
            esc_html_x('Malawi', 'Country Name', 'hash-form'),
            esc_html_x('Malaysia', 'Country Name', 'hash-form'),
            esc_html_x('Maldives', 'Country Name', 'hash-form'),
            esc_html_x('Mali', 'Country Name', 'hash-form'),
            esc_html_x('Malta', 'Country Name', 'hash-form'),
            esc_html_x('Marshall Islands', 'Country Name', 'hash-form'),
            esc_html_x('Martinique', 'Country Name', 'hash-form'),
            esc_html_x('Mauritania', 'Country Name', 'hash-form'),
            esc_html_x('Mauritius', 'Country Name', 'hash-form'),
            esc_html_x('Mayotte', 'Country Name', 'hash-form'),
            esc_html_x('Mexico', 'Country Name', 'hash-form'),
            esc_html_x('Micronesia', 'Country Name', 'hash-form'),
            esc_html_x('Moldova', 'Country Name', 'hash-form'),
            esc_html_x('Monaco', 'Country Name', 'hash-form'),
            esc_html_x('Mongolia', 'Country Name', 'hash-form'),
            esc_html_x('Montenegro', 'Country Name', 'hash-form'),
            esc_html_x('Montserrat', 'Country Name', 'hash-form'),
            esc_html_x('Morocco', 'Country Name', 'hash-form'),
            esc_html_x('Mozambique', 'Country Name', 'hash-form'),
            esc_html_x('Myanmar', 'Country Name', 'hash-form'),
            esc_html_x('Namibia', 'Country Name', 'hash-form'),
            esc_html_x('Nauru', 'Country Name', 'hash-form'),
            esc_html_x('Nepal', 'Country Name', 'hash-form'),
            esc_html_x('Netherlands', 'Country Name', 'hash-form'),
            esc_html_x('New Caledonia', 'Country Name', 'hash-form'),
            esc_html_x('New Zealand', 'Country Name', 'hash-form'),
            esc_html_x('Nicaragua', 'Country Name', 'hash-form'),
            esc_html_x('Niger', 'Country Name', 'hash-form'),
            esc_html_x('Nigeria', 'Country Name', 'hash-form'),
            esc_html_x('Niue', 'Country Name', 'hash-form'),
            esc_html_x('Norfolk Island', 'Country Name', 'hash-form'),
            esc_html_x('Northern Mariana Islands', 'Country Name', 'hash-form'),
            esc_html_x('Norway', 'Country Name', 'hash-form'),
            esc_html_x('Oman', 'Country Name', 'hash-form'),
            esc_html_x('Pakistan', 'Country Name', 'hash-form'),
            esc_html_x('Palau', 'Country Name', 'hash-form'),
            esc_html_x('Palestine', 'Country Name', 'hash-form'),
            esc_html_x('Panama', 'Country Name', 'hash-form'),
            esc_html_x('Papua New Guinea', 'Country Name', 'hash-form'),
            esc_html_x('Paraguay', 'Country Name', 'hash-form'),
            esc_html_x('Peru', 'Country Name', 'hash-form'),
            esc_html_x('Philippines', 'Country Name', 'hash-form'),
            esc_html_x('Pitcairn', 'Country Name', 'hash-form'),
            esc_html_x('Poland', 'Country Name', 'hash-form'),
            esc_html_x('Portugal', 'Country Name', 'hash-form'),
            esc_html_x('Puerto Rico', 'Country Name', 'hash-form'),
            esc_html_x('Qatar', 'Country Name', 'hash-form'),
            esc_html_x('Reunion', 'Country Name', 'hash-form'),
            esc_html_x('Romania', 'Country Name', 'hash-form'),
            esc_html_x('Russia', 'Country Name', 'hash-form'),
            esc_html_x('Rwanda', 'Country Name', 'hash-form'),
            esc_html_x('Saint Barthelemy', 'Country Name', 'hash-form'),
            esc_html_x('Saint Helena, Ascension and Tristan da Cunha', 'Country Name', 'hash-form'),
            esc_html_x('Saint Kitts and Nevis', 'Country Name', 'hash-form'),
            esc_html_x('Saint Lucia', 'Country Name', 'hash-form'),
            esc_html_x('Saint Martin (French part)', 'Country Name', 'hash-form'),
            esc_html_x('Saint Pierre and Miquelon', 'Country Name', 'hash-form'),
            esc_html_x('Saint Vincent and the Grenadines', 'Country Name', 'hash-form'),
            esc_html_x('Samoa', 'Country Name', 'hash-form'),
            esc_html_x('San Marino', 'Country Name', 'hash-form'),
            esc_html_x('Sao Tome and Principe', 'Country Name', 'hash-form'),
            esc_html_x('Saudi Arabia', 'Country Name', 'hash-form'),
            esc_html_x('Senegal', 'Country Name', 'hash-form'),
            esc_html_x('Serbia', 'Country Name', 'hash-form'),
            esc_html_x('Seychelles', 'Country Name', 'hash-form'),
            esc_html_x('Sierra Leone', 'Country Name', 'hash-form'),
            esc_html_x('Singapore', 'Country Name', 'hash-form'),
            esc_html_x('Sint Maarten (Dutch part)', 'Country Name', 'hash-form'),
            esc_html_x('Slovakia', 'Country Name', 'hash-form'),
            esc_html_x('Slovenia', 'Country Name', 'hash-form'),
            esc_html_x('Solomon Islands', 'Country Name', 'hash-form'),
            esc_html_x('Somalia', 'Country Name', 'hash-form'),
            esc_html_x('South Africa', 'Country Name', 'hash-form'),
            esc_html_x('South Georgia and the South Sandwich Islands', 'Country Name', 'hash-form'),
            esc_html_x('South Sudan', 'Country Name', 'hash-form'),
            esc_html_x('Spain', 'Country Name', 'hash-form'),
            esc_html_x('Sri Lanka', 'Country Name', 'hash-form'),
            esc_html_x('Sudan', 'Country Name', 'hash-form'),
            esc_html_x('Suriname', 'Country Name', 'hash-form'),
            esc_html_x('Svalbard and Jan Mayen', 'Country Name', 'hash-form'),
            esc_html_x('Swaziland', 'Country Name', 'hash-form'),
            esc_html_x('Sweden', 'Country Name', 'hash-form'),
            esc_html_x('Switzerland', 'Country Name', 'hash-form'),
            esc_html_x('Syria', 'Country Name', 'hash-form'),
            esc_html_x('Taiwan', 'Country Name', 'hash-form'),
            esc_html_x('Tajikistan', 'Country Name', 'hash-form'),
            esc_html_x('Tanzania', 'Country Name', 'hash-form'),
            esc_html_x('Thailand', 'Country Name', 'hash-form'),
            esc_html_x('Timor-Leste', 'Country Name', 'hash-form'),
            esc_html_x('Togo', 'Country Name', 'hash-form'),
            esc_html_x('Tokelau', 'Country Name', 'hash-form'),
            esc_html_x('Tonga', 'Country Name', 'hash-form'),
            esc_html_x('Trinidad and Tobago', 'Country Name', 'hash-form'),
            esc_html_x('Tunisia', 'Country Name', 'hash-form'),
            esc_html_x('Turkey', 'Country Name', 'hash-form'),
            esc_html_x('Turkmenistan', 'Country Name', 'hash-form'),
            esc_html_x('Turks and Caicos Islands', 'Country Name', 'hash-form'),
            esc_html_x('Tuvalu', 'Country Name', 'hash-form'),
            esc_html_x('Uganda', 'Country Name', 'hash-form'),
            esc_html_x('Ukraine', 'Country Name', 'hash-form'),
            esc_html_x('United Arab Emirates', 'Country Name', 'hash-form'),
            esc_html_x('United Kingdom', 'Country Name', 'hash-form'),
            esc_html_x('United States', 'Country Name', 'hash-form'),
            esc_html_x('United States Minor Outlying Islands', 'Country Name', 'hash-form'),
            esc_html_x('Uruguay', 'Country Name', 'hash-form'),
            esc_html_x('Uzbekistan', 'Country Name', 'hash-form'),
            esc_html_x('Vanuatu', 'Country Name', 'hash-form'),
            esc_html_x('Vatican City', 'Country Name', 'hash-form'),
            esc_html_x('Venezuela', 'Country Name', 'hash-form'),
            esc_html_x('Vietnam', 'Country Name', 'hash-form'),
            esc_html_x('Virgin Islands, British', 'Country Name', 'hash-form'),
            esc_html_x('Virgin Islands, U.S.', 'Country Name', 'hash-form'),
            esc_html_x('Wallis and Futuna', 'Country Name', 'hash-form'),
            esc_html_x('Western Sahara', 'Country Name', 'hash-form'),
            esc_html_x('Yemen', 'Country Name', 'hash-form'),
            esc_html_x('Zambia', 'Country Name', 'hash-form'),
            esc_html_x('Zimbabwe', 'Country Name', 'hash-form'),
        );

        sort($countries, SORT_LOCALE_STRING);
        return $countries;
    }

    public static function get_ages() {
        return array(
            esc_html__('Under 18', 'hash-form'),
            esc_html__('18-24', 'hash-form'),
            esc_html__('25-34', 'hash-form'),
            esc_html__('35-44', 'hash-form'),
            esc_html__('45-54', 'hash-form'),
            esc_html__('55-64', 'hash-form'),
            esc_html__('65 or Above', 'hash-form'),
            esc_html__('Prefer Not to Answer', 'hash-form'),
        );
    }

    public static function get_satisfaction() {
        return array(
            esc_html__('Very Unsatisfied', 'hash-form'),
            esc_html__('Unsatisfied', 'hash-form'),
            esc_html__('Neutral', 'hash-form'),
            esc_html__('Satisfied', 'hash-form'),
            esc_html__('Very Satisfied', 'hash-form'),
            esc_html__('N/A', 'hash-form'),
        );
    }

    public static function get_agreement() {
        return array(
            esc_html__('Strongly Disagree', 'hash-form'),
            esc_html__('Disagree', 'hash-form'),
            esc_html__('Neutral', 'hash-form'),
            esc_html__('Agree', 'hash-form'),
            esc_html__('Strongly Agree', 'hash-form'),
            esc_html__('N/A', 'hash-form'),
        );
    }

    public static function get_likely() {
        return array(
            esc_html__('Extremely Unlikely', 'hash-form'),
            esc_html__('Unlikely', 'hash-form'),
            esc_html__('Neutral', 'hash-form'),
            esc_html__('Likely', 'hash-form'),
            esc_html__('Extremely Likely', 'hash-form'),
            esc_html__('N/A', 'hash-form'),
        );
    }

    public static function get_importance() {
        return array(
            esc_html__('Not at all Important', 'hash-form'),
            esc_html__('Somewhat Important', 'hash-form'),
            esc_html__('Neutral', 'hash-form'),
            esc_html__('Important', 'hash-form'),
            esc_html__('Very Important', 'hash-form'),
            esc_html__('N/A', 'hash-form'),
        );
    }

    public static function get_options_presets() {
        return array(
            'hf-countries-opts' => array(
                'label' => esc_html__('Countries', 'hash-form'),
                'options' => self::get_countries()
            ),
            'hf-age-opts' => array(
                'label' => esc_html__('Age', 'hash-form'),
                'options' => self::get_ages()
            ),
            'hf-satisfaction-opts' => array(
                'label' => esc_html__('Satisfaction', 'hash-form'),
                'options' => self::get_satisfaction()
            ),
            'hf-importance-opts' => array(
                'label' => esc_html__('Importance', 'hash-form'),
                'options' => self::get_importance()
            ),
            'hf-agreement-opts' => array(
                'label' => esc_html__('Agreement', 'hash-form'),
                'options' => self::get_agreement()
            ),
            'hf-likely-opts' => array(
                'label' => esc_html__('Likely', 'hash-form'),
                'options' => self::get_likely()
            ),
        );
    }

    public static function get_user_id_param($user_id) {
        if (!$user_id || is_numeric($user_id)) {
            return $user_id;
        }
        $user_id = sanitize_text_field($user_id);
        if ($user_id == 'current') {
            $user_id = get_current_user_id();
        } else {
            if (is_email($user_id)) {
                $user = get_user_by('email', $user_id);
            } else {
                $user = get_user_by('login', $user_id);
            }
            if ($user) {
                $user_id = $user->ID;
            }
            unset($user);
        }
        return $user_id;
    }

    public static function get_ip() {
        $ip = self::get_ip_address();
        return $ip;
    }

    public static function get_ip_address() {
        $ip_options = array('REMOTE_ADDR');
        $ip = '';

        foreach ($ip_options as $key) {
            if (!isset($_SERVER[$key])) {
                continue;
            }
            $key = self::get_server_value($key);
            foreach (explode(',', $key) as $ip) {
                $ip = trim($ip); // Just to be safe.
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return sanitize_text_field($ip);
                }
            }
        }
        return sanitize_text_field($ip);
    }

    public static function get_server_value($value) {
        return isset($_SERVER[$value]) ? sanitize_text_field(wp_strip_all_tags(wp_unslash($_SERVER[$value]))) : '';
    }

    public static function count_decimals($num) {
        if (!is_numeric($num)) {
            return false;
        }
        $num = (string) $num;
        $parts = explode('.', $num);
        if (1 === count($parts)) {
            return 0;
        }
        return strlen($parts[count($parts) - 1]);
    }

    public static function print_message() {
        if (isset($_SESSION['hashform_message'])) {
            ?>
            <div class="hf-settings-updated">
                <span class="mdi mdi-check-circle"></span>
                <?php
                echo esc_html(sanitize_text_field($_SESSION['hashform_message']));
                unset($_SESSION['hashform_message']);
                ?>
            </div>
            <?php
        }
    }

    public static function sanitize_array($array = array(), $sanitize_rule = array()) {
        $new_args = (array) $array;

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $new_args[$key] = self::sanitize_array($value, isset($sanitize_rule[$key]) ? $sanitize_rule[$key] : 'sanitize_text_field');
            } else {
                if (isset($sanitize_rule[$key]) && !empty($sanitize_rule[$key]) && function_exists($sanitize_rule[$key])) {
                    $sanitize_type = $sanitize_rule[$key];
                    $new_args[$key] = $sanitize_type($value);
                } else {
                    $new_args[$key] = sanitize_text_field($value);
                }
            }
        }

        return $new_args;
    }

    public static function get_field_options_sanitize_rules() {
        return array(
            'grid_id' => 'sanitize_text_field',
            'name' => 'sanitize_text_field',
            'label' => 'sanitize_text_field',
            'label_position' => 'sanitize_text_field',
            'label_alignment' => 'sanitize_text_field',
            'hide_label' => 'hashform_sanitize_checkbox_boolean',
            'heading_type' => 'sanitize_text_field',
            'text_alignment' => 'sanitize_text_field',
            'content' => 'sanitize_text_field',
            'select_option_type' => 'sanitize_text_field',
            'image_size' => 'sanitize_text_field',
            'image_id' => 'hashform_sanitize_number',
            'spacer_height' => 'hashform_sanitize_number',
            'step' => 'hashform_sanitize_float',
            'min_time' => 'sanitize_text_field',
            'max_time' => 'sanitize_text_field',
            'upload_label' => 'sanitize_text_field',
            'max_upload_size' => 'hashform_sanitize_number',
            'extensions' => 'hashform_sanitize_allowed_file_extensions',
            'extensions_error_message' => 'sanitize_text_field',
            'multiple_uploads' => 'sanitize_text_field',
            'multiple_uploads_limit' => 'hashform_sanitize_number',
            'multiple_uploads_error_message' => 'sanitize_text_field',
            'date_format' => 'sanitize_text_field',
            'border_style' => 'sanitize_text_field',
            'border_width' => 'hashform_sanitize_number',
            'minnum' => 'hashform_sanitize_float',
            'maxnum' => 'hashform_sanitize_float',
            'classes' => 'sanitize_text_field',
            'auto_width' => 'sanitize_text_field',
            'placeholder' => 'sanitize_text_field',
            'format' => 'sanitize_text_field',
            'required_indicator' => 'sanitize_text_field',
            'options_layout' => 'sanitize_text_field',
            'field_max_width' => 'hashform_sanitize_number',
            'field_max_width_unit' => 'sanitize_text_field',
            'image_max_width' => 'hashform_sanitize_number',
            'image_max_width_unit' => 'sanitize_text_field',
            'field_alignment' => 'sanitize_text_field',
            'blank' => 'sanitize_text_field',
            'invalid' => 'sanitize_text_field',
            'rows' => 'hashform_sanitize_number',
            'max' => 'hashform_sanitize_number',
            'disable' => array(
                'line1' => 'sanitize_text_field',
                'line2' => 'sanitize_text_field',
                'city' => 'sanitize_text_field',
                'state' => 'sanitize_text_field',
                'zip' => 'hashform_sanitize_number',
                'country' => 'sanitize_text_field'
            )
        );
    }

    public static function get_all_forms_list_options() {
        $all_forms = array();
        $forms = HashFormBuilder::get_all_forms();
        foreach ($forms as $form) {
            $all_forms[$form->id] = $form->name;
        }
        return $all_forms;
    }

    public static function getSalt() {
        $salt = get_option('_hashform_security_salt');
        if (!$salt) {
            $salt = wp_generate_password();
            update_option('_hashform_security_salt', $salt, 'no');
        }
        return $salt;
    }

    public static function encrypt($text) {
        $key = static::getSalt();
        $cipher = 'AES-128-CBC';
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($text, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);
        return base64_encode($iv . $hmac . $ciphertext_raw);
    }

    public static function decrypt($text) {
        $key = static::getSalt();
        $c = base64_decode($text);
        $cipher = 'AES-128-CBC';
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len = 32);
        $ciphertext_raw = substr($c, $ivlen + $sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);

        if (hash_equals($hmac, $calcmac)) {
            return $original_plaintext;
        }
    }

    public static function get_field_input_value($value) {
        $entry_val = '';
        $entry_value = maybe_unserialize($value['value']);
        $entry_type = maybe_unserialize($value['type']);
        if (is_array($entry_value)) {
            if ($entry_type == 'name') {
                $entry_value = implode(' ', array_filter($entry_value));
            } elseif ($entry_type == 'repeater_field') {
                $entry_val = '<table><thead><tr>';
                foreach (array_keys($entry_value) as $key) {
                    $entry_val .= '<th>' . $key . '</th>';
                }
                $entry_val .= '</tr></thead><tbody>';
                $out = array();
                foreach ($entry_value as $rowkey => $row) {
                    foreach ($row as $colkey => $col) {
                        $out[$colkey][$rowkey] = $col;
                    }
                }
                foreach ($out as $key => $val) {
                    foreach ($val as $eval) {
                        $entry_val .= '<td>' . $eval . '</td>';
                    }
                    $entry_val .= '</tr>';
                }
                $entry_val .= '</tbody></table>';
                $entry_value = $entry_val;
            } else {
                $entry_value = implode(',', array_filter($entry_value));
            }
        }
        return $entry_value;
    }

    public static function unserialize_or_decode($value) {
        if (is_array($value)) {
            return $value;
        }
        if (is_serialized($value)) {
            return self::maybe_unserialize_array($value);
        } else {
            return self::maybe_json_decode($value, false);
        }
    }

    public static function maybe_unserialize_array($value) {
        if (!is_string($value)) {
            return $value;
        }

        if (!is_serialized($value) || 'a:' !== substr($value, 0, 2)) {
            return $value;
        }

        $parsed = HashFormSerializedStrParser::get()->parse($value);
        if (is_array($parsed)) {
            $value = $parsed;
        }
        return $value;
    }

    public static function maybe_json_decode($string, $single_to_array = true) {
        if (is_array($string) || is_null($string)) {
            return $string;
        }

        $new_string = json_decode($string, true);
        if (function_exists('json_last_error')) {
            $single_value = false;
            if (!$single_to_array) {
                $single_value = is_array($new_string) && count($new_string) === 1 && isset($new_string[0]);
            }
            if (json_last_error() == JSON_ERROR_NONE && is_array($new_string) && !$single_value) {
                $string = $new_string;
            }
        }
        return $string;
    }

}
