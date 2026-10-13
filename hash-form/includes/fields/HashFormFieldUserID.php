<?php
defined('ABSPATH') || die();

class HashFormFieldUserID extends HashFormFieldType {

    protected $type = 'user_id';

    public function field_settings_for_type() {
        return array(
            'max_width' => false,
            'default' => false,
            'css' => false,
            'description' => false,
            'required' => false,
            'label' => false
        );
    }

    protected function extra_field_default_opts() {
        return array(
            'capture' => 'display_name',
        );
    }

    /**
     * How the submitter is shown. The user id is always what is stored.
     */
    public static function capture_choices() {
        return array(
            'display_name' => esc_html__('Display Name', 'hash-form'),
            'username' => esc_html__('Username', 'hash-form'),
            'email' => esc_html__('Email Address', 'hash-form'),
            'id' => esc_html__('User ID', 'hash-form'),
        );
    }

    /**
     * Render a stored user id for display. Returns HTML-safe text.
     *
     * Only entry detail passes $link; emails do not link into wp-admin.
     */
    public static function format_value($stored, $capture = 'display_name', $link = false) {
        $user_id = absint($stored);

        // Logged-out submissions store 0; show Guest, not an id.
        if (!$user_id) {
            return esc_html__('Guest', 'hash-form');
        }

        $user = get_user_by('id', $user_id);

        if (!$user) {
            /* translators: %d: id of an account that has since been deleted. */
            return esc_html(sprintf(esc_html__('Deleted user (#%d)', 'hash-form'), $user_id));
        }

        switch ($capture) {
            case 'id':
                $text = (string) $user_id;
                break;
            case 'username':
                $text = $user->user_login;
                break;
            case 'email':
                $text = $user->user_email;
                break;
            default:
                $text = $user->display_name ? $user->display_name : $user->user_login;
        }

        if (!$link) {
            return esc_html($text);
        }

        // Returns '' for anyone who cannot edit users, so fall back to plain.
        $edit = get_edit_user_link($user_id);

        return $edit
            ? '<a href="' . esc_url($edit) . '">' . esc_html($text) . '</a>'
            : esc_html($text);
    }

    /**
     * The capture setting for a field, defaulting to display_name when missing.
     */
    public static function capture_from_options($options) {
        $options = is_array($options) ? $options : array();
        $capture = isset($options['capture']) ? $options['capture'] : 'display_name';

        return array_key_exists($capture, self::capture_choices()) ? $capture : 'display_name';
    }

    public function get_user_id() {
        $user_ID = get_current_user_id();
        return $user_ID;
    }

    public function set_value_before_save($value) {
        $user_ID = $this->get_user_id();
        return $user_ID;
    }

    protected function input_html() {
        if (is_admin() && !HashFormHelper::is_preview_page()) {
            $field = $this->get_field();
            $choices = self::capture_choices();
            $capture = self::capture_from_options($field);
            ?>
            <label class="hf-editor-field-label">
                <span class="hf-editor-field-label-text"><?php esc_html_e('User ID', 'hash-form'); ?></span>
            </label>
            <input type="text" value="<?php
            /* translators: %s: what the field will show, for example "Display Name". */
            echo esc_attr(sprintf(esc_html__('Hidden on the form. Records who submitted, shown as %s.', 'hash-form'), $choices[$capture]));
            ?>" disabled />
            <?php
        } else {
            ?>
            <input type="hidden" name="<?php echo esc_attr($this->html_name()); ?>" value="<?php echo esc_attr($this->get_user_id()); ?>" />
            <?php
        }
    }

}
