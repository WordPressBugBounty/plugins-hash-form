<?php
defined('ABSPATH') || die();

class HashFormFieldCaptcha extends HashFormFieldType {

    protected $type = 'captcha';

    protected function field_settings_for_type() {
        return array(
            // No 'captcha_size' here: nothing reads that key, and the size is
            // a setting of its own rather than something field_attrs() prints.
            'required' => false,
            'invalid' => true,
            'default' => false,
            'max_width' => false
        );
    }

    /**
     * Whether the widget is the visible checkbox kind, which is what the size
     * and theme settings describe. A v3 widget draws nothing.
     */
    public static function is_v2() {
        $settings = HashFormSettings::get_settings();

        return 'v3' !== $settings['re_type'];
    }

    /**
     * The sizes and themes reCAPTCHA v2 accepts.
     *
     * Both are written into data attributes the Google script reads, so a
     * value it does not know leaves the widget on its own defaults.
     */
    public static function captcha_sizes() {
        return array(
            'normal' => esc_html__('Normal', 'hash-form'),
            'compact' => esc_html__('Compact', 'hash-form'),
        );
    }

    public static function captcha_themes() {
        return array(
            'light' => esc_html__('Light', 'hash-form'),
            'dark' => esc_html__('Dark', 'hash-form'),
        );
    }

    public static function get_captcha_image_name() {
        $settings = HashFormSettings::get_settings();
        if ($settings['re_type'] === 'v3') {
            $image_name = 'recaptcha_v3';
        } else {
            $image_name = 'recaptcha';
        }
        return $image_name;
    }

    protected function new_field_settings() {
        $settings = HashFormSettings::get_settings();
        return array(
            'invalid' => $settings['re_msg'],
        );
    }

    protected function extra_field_default_opts() {
        return array(
            'label' => 'none',
            'captcha_size' => 'normal',
            'captcha_theme' => 'light',
        );
    }

    public function front_field_input() {
        $settings = HashFormSettings::get_settings();
        if (!self::should_show_captcha())
            return '';

        ?>

        <div id="<?php echo esc_attr($this->html_id()); ?>" class="g-recaptcha" data-sitekey="<?php echo esc_attr(self::site_key()); ?>" data-size="<?php echo esc_attr($this->captcha_size($settings)); ?>" data-theme="<?php echo esc_attr($this->captcha_theme()); ?>"></div>
        <?php
    }

    protected function load_field_scripts() {
        $api_js_url = $this->api_url();
        wp_enqueue_script('captcha-api', $api_js_url, array(), HASHFORM_VERSION, true);
    }

    protected function api_url() {
        $hashform_settings = HashFormSettings::get_settings();
        return $this->recaptcha_api_url($hashform_settings);
    }

    protected function recaptcha_api_url($settings) {
        $api_js_url = 'https://www.google.com/recaptcha/api.js';
        $params = array();

        if ($settings['re_type'] == 'v3') {
            $params['render'] = $settings['pubkey_v3'];
        }

        if (!empty($settings['re_lang'])) {
            $params['hl'] = $settings['re_lang'];
        }
        if ($params) {
            $api_js_url .= '?' . http_build_query($params);
        }
        return $api_js_url;
    }

    protected function captcha_size($settings) {
        if ($settings['re_type'] == 'v3') {
            return 'invisible';
        }

        $size = isset($this->field['captcha_size']) ? $this->field['captcha_size'] : '';

        return array_key_exists($size, self::captcha_sizes()) ? $size : 'normal';
    }

    protected function captcha_theme() {
        $theme = isset($this->field['captcha_theme']) ? $this->field['captcha_theme'] : '';

        // Written out even when empty before, which is not a theme the script
        // recognises.
        return array_key_exists($theme, self::captcha_themes()) ? $theme : 'light';
    }

    protected function validate_against_api($args) {
        $errors = array();
        $settings = HashFormSettings::get_settings();
        $resp = $this->send_api_check($args);
        $response = json_decode(wp_remote_retrieve_body($resp), true);

        if (is_wp_error($resp)) {
            $error_string = $resp->get_error_message();
            $errors['field' . $args['id']] = esc_html__('There was a problem verifying your captcha', 'hash-form');
            $errors['field' . $args['id']] .= ' ' . $error_string;
            return $errors;
        }

        if (!is_array($response)) {
            $errors['field' . $args['id']] = esc_html__('There was a problem verifying your captcha', 'hash-form');
            return $errors;
        }

        if ('v3' === $settings['re_type'] && array_key_exists('score', $response)) {
            $threshold = floatval($settings['re_threshold']);
            $score = floatval($response['score']);
            if ($score < $threshold) {
                $response['success'] = false;
            }
        }

        if (isset($response['success']) && !$response['success']) {
            $invalid_message = HashFormFields::get_option($this->field, 'invalid');
            if ($invalid_message === esc_html__('The reCAPTCHA was not entered correctly', 'hash-form')) {
                $invalid_message = '';
            }
            $errors['field' . $args['id']] = ($invalid_message === '' ? $settings['re_msg'] : $invalid_message);
        }

        return $errors;
    }

    public function validate($args) {
        /*
         * Without a site key no widget is rendered, so there is no response to
         * verify. Failing validation here meant a form carrying this field
         * could never be submitted, and the error pointed at a field that was
         * not on the page. The field is skipped instead.
         */
        if (!self::should_show_captcha()) {
            return array();
        }

        return $this->validate_against_api($args);
    }

    public static function site_key() {
        $settings = HashFormSettings::get_settings();

        return $settings['re_type'] == 'v3' ? $settings['pubkey_v3'] : $settings['pubkey_v2'];
    }

    protected static function secret_key() {
        $settings = HashFormSettings::get_settings();

        return $settings['re_type'] == 'v3' ? $settings['privkey_v3'] : $settings['privkey_v2'];
    }

    public static function should_show_captcha() {
        return '' !== trim((string) self::site_key());
    }

    protected function send_api_check($args) {
        $arg_array = array(
            'body' => array(
                'secret' => self::secret_key(),
                'response' => $args['value'],
                'remoteip' => HashFormHelper::get_ip_address(),
            ),
        );

        return wp_remote_post('https://www.google.com/recaptcha/api/siteverify', $arg_array);
    }

    protected function input_html() {
        $html = '';
        if (is_admin()) {
            if (!HashFormFieldCaptcha::should_show_captcha()) {
                ?>
                <div class="howto">
                    <?php esc_html_e('This field is not set up yet.', 'hash-form'); ?>
                </div>
                <?php
            } else {
                $image_name = HashFormFieldCaptcha::get_captcha_image_name();
                ?>
                <img src="<?php echo esc_url(HASHFORM_URL . 'img/' . $image_name . '.png'); ?>" style="width: 304px;" />
                <input type="hidden" name="<?php echo esc_attr($this->html_name()); ?>" value="1" />
                <?php
            }
        } else {
            $html = self::front_field_input();
        }

        return $html;
    }

}
