<?php
defined('ABSPATH') || die();

class HashFormFieldHidden extends HashFormFieldType {

    protected $type = 'hidden';

    public function field_settings_for_type() {
        return array(
            'max_width' => false,
            'css' => false,
            'description' => false,
            'required' => false,
            'label' => false
        );
    }

    protected function extra_field_default_opts() {
        return array(
            'value_source' => 'static',
            'value_param' => '',
        );
    }

    /**
     * Where a hidden field takes its value from.
     *
     * Split deliberately, because the two halves do not offer the same promise.
     * The account and date sources are worked out on the server at submit time
     * and cannot be influenced by the visitor. The page sources are derived from
     * the address the form was submitted from, which the browser supplies, so
     * they are useful for attribution but must not be trusted as proof of
     * anything. The field description says so in as many words.
     */
    public static function value_sources() {
        return array(
            'static' => esc_html__('Fixed value', 'hash-form'),
            'url_param' => esc_html__('Value from a URL parameter', 'hash-form'),
            'page_url' => esc_html__('Page address', 'hash-form'),
            'post_id' => esc_html__('Page or post ID', 'hash-form'),
            'post_title' => esc_html__('Page or post title', 'hash-form'),
            'user_email' => esc_html__('Logged in user: email', 'hash-form'),
            'user_login' => esc_html__('Logged in user: username', 'hash-form'),
            'user_display_name' => esc_html__('Logged in user: display name', 'hash-form'),
            'submitted_at' => esc_html__('Date and time of submission', 'hash-form'),
        );
    }

    /**
     * Sources the visitor's browser has a hand in.
     */
    public static function page_sources() {
        return array('url_param', 'page_url', 'post_id', 'post_title');
    }

    public static function source_from_options($options) {
        $options = is_array($options) ? $options : array();
        $source = isset($options['value_source']) ? $options['value_source'] : 'static';

        return array_key_exists($source, self::value_sources()) ? $source : 'static';
    }

    /**
     * The address the form was submitted from.
     *
     * Sent alongside the entry by frontend.js. Not the referer header, which on
     * an admin-ajax request only ever points back at the form's own page.
     */
    private static function submitted_from() {
        return HashFormHelper::get_post('location', 'esc_url_raw');
    }

    /**
     * Resolve the value to store.
     *
     * Runs on the server for every source, so what a hidden input carried in the
     * page is never what gets saved.
     */
    public static function resolve_value($field) {
        $options = is_array($field) ? $field : (array) $field;
        $source = self::source_from_options($options);

        if ('static' === $source) {
            return isset($options['default_value']) ? $options['default_value'] : '';
        }

        if ('submitted_at' === $source) {
            return current_time('mysql');
        }

        if (in_array($source, array('user_email', 'user_login', 'user_display_name'), true)) {
            $user = wp_get_current_user();

            if (!$user || !$user->ID) {
                return '';
            }

            switch ($source) {
                case 'user_email':
                    return $user->user_email;
                case 'user_login':
                    return $user->user_login;
                default:
                    return $user->display_name ? $user->display_name : $user->user_login;
            }
        }

        $location = self::submitted_from();

        if (!$location) {
            return '';
        }

        if ('page_url' === $source) {
            return $location;
        }

        if ('url_param' === $source) {
            $param = isset($options['value_param']) ? sanitize_key($options['value_param']) : '';

            if (!$param) {
                return '';
            }

            $query = wp_parse_url($location, PHP_URL_QUERY);

            if (!$query) {
                return '';
            }

            $args = array();
            wp_parse_str($query, $args);

            return isset($args[$param]) ? sanitize_text_field($args[$param]) : '';
        }

        // url_to_postid() returns 0 for anything it cannot match, such as an
        // archive or the front page when that is not a static page.
        $post_id = url_to_postid($location);

        if (!$post_id) {
            return '';
        }

        return ('post_id' === $source) ? (string) $post_id : get_the_title($post_id);
    }

    public function set_value_before_save($value) {
        return self::resolve_value(self::flatten_field($this->get_field()));
    }

    /**
     * The settings as one flat array.
     *
     * get_field_vars() hands back an object keeping its settings under
     * field_options, while the builder has already flattened them. Both reach
     * this class, so neither shape can be assumed.
     */
    private static function flatten_field($field) {
        if (!is_object($field)) {
            return (array) $field;
        }

        $options = get_object_vars($field);
        $nested = isset($options['field_options']) && is_array($options['field_options'])
            ? $options['field_options']
            : array();
        unset($options['field_options']);

        return array_merge($options, $nested);
    }

    protected function input_html() {
        if (is_admin() && !HashFormHelper::is_preview_page()) {
            $field = $this->get_field();
            $source = self::source_from_options($field);
            ?>
            <label class="hf-editor-field-label">
                <span class="hf-editor-field-label-text"><?php esc_html_e('Hidden', 'hash-form'); ?></span>
            </label>
            <?php if ('static' === $source) { ?>
                <input type="text" <?php $this->field_attrs(); ?> />
                <p class="howto">
                    <?php esc_html_e('Note: This field will not show in the form. Enter the value to be hidden.', 'hash-form'); ?>
                </p>
            <?php } else {
                $sources = self::value_sources();
                ?>
                <input type="text" value="<?php echo esc_attr($sources[$source]); ?>" disabled />
                <p class="howto">
                    <?php esc_html_e('Note: This field will not show in the form. The value is worked out when the form is submitted.', 'hash-form'); ?>
                </p>
            <?php } ?>
            <?php
        } else {
            /*
             * No value attribute. set_value_before_save() resolves this on the
             * server and ignores whatever was posted, so printing it into the
             * page only exposed it for no gain.
             */
            ?>
            <input type="hidden" id="<?php echo esc_attr($this->html_id()); ?>" name="<?php echo esc_attr($this->html_name()); ?>" />
            <?php
        }
    }

}
