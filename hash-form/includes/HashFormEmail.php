<?php
defined('ABSPATH') || die();

class HashFormEmail {

    public $form;
    public $entry_id;
    public $location;

    public function __construct($form, $entry_id, $location) {
        $this->form = $form;
        $this->entry_id = $entry_id;
        $this->location = $location;
    }

    private function get_form_settings() {
        return $this->form->settings;
    }

    /**
     * Set while replaying a deferred send, so hashform_send_entry_emails does not defer it again.
     */
    public static $sending_deferred = false;

    public function send_email() {
        $attachments = array();
        $form_settings = $this->get_form_settings();
        $entry = HashFormEntry::get_entry_vars($this->entry_id);
        $metas = $entry->metas;

        /**
         * Whether to send the notification and auto responder now. Payment add-ons return false to defer them.
         *
         * Returning false must still let the submission finish normally.
         */
        if (!self::$sending_deferred && !apply_filters('hashform_send_entry_emails', true, $this->form, $this->entry_id, $metas)) {
            // The emails wait, but the actions (a PDF, a text message) run now, so fill in their tags first.
            list($entry_values, $frm_table) = $this->entry_display_values($metas, self::email_template(HashFormSettings::get_settings()));
            $this->run_after_email($this->fill_tags($form_settings, $entry_values, $frm_table), $metas);

            return true;
        }

        $email_to = isset($form_settings['email_to']) ? explode(',', $form_settings['email_to']) : '';
        $email_from = isset($form_settings['email_from']) ? $form_settings['email_from'] : '';
        $email_from = ($email_from == '[admin_email]') ? get_option('admin_email') : esc_attr($email_from);
        $email_from_name = isset($form_settings['email_from_name']) ? $form_settings['email_from_name'] : '';
        $email_subject = isset($form_settings['email_subject']) ? $form_settings['email_subject'] : '';
        $reply_to_email = isset($form_settings['reply_to_email']) ? $form_settings['reply_to_email'] : '';
        $reply_to_ar = isset($form_settings['reply_to_ar']) ? $form_settings['reply_to_ar'] : '';

        $settings = HashFormSettings::get_settings();
        $email_template = self::email_template($settings);
        $header_image = sanitize_text_field($settings['header_image']);
        $form_title = $this->form->name;

        foreach ($metas as $item => $value) {
            $reply_to_email = str_replace('#field_id_' . absint($item), $value['value'], $reply_to_email);
            $email_subject = str_replace('#field_id_' . absint($item), $value['value'], $email_subject);
        }

        list($entry_values, $frm_table) = $this->entry_display_values($metas, $email_template);
        $form_settings = $this->fill_tags($form_settings, $entry_values, $frm_table);

        // Submitted values were substituted in above; strip line breaks so they
        // cannot smuggle extra mail headers.
        $reply_to_email = str_replace(array("\r", "\n"), '', $reply_to_email);
        $email_subject = str_replace(array("\r", "\n"), ' ', $email_subject);
        // The auto responder setting stores a bare field ID, so look the address up by ID.
        $ar_field_id = absint(str_replace('#field_id_', '', $reply_to_ar));
        $reply_to_ar = ($ar_field_id && isset($metas[$ar_field_id])) ? trim(str_replace(array("\r", "\n"), '', $metas[$ar_field_id]['value'])) : '';

        $email_message = empty($form_settings['email_message']) ? '' : wpautop($form_settings['email_message']);

        $email_context = 'notification';
        $email_entry_id = $this->entry_id;
        ob_start();
        include self::layout_file($email_template);
        $email_message = ob_get_clean();

        $head = array();
        $head[] = 'Content-Type: text/html; charset=UTF-8';
        $head[] = 'From: ' . esc_html($email_from_name) . ' <' . esc_html($email_from) . '>';
        if ($reply_to_email) {
            $head[] = 'Reply-To: ' . esc_html($reply_to_email);
        }

        $recipients = array();

        foreach ($email_to as $row) {
            $recipients[] = (trim($row) == '[admin_email]') ? get_option('admin_email') : $row;
        }

        if (!empty($attachments)) {
            $mail = wp_mail($recipients, $email_subject, $email_message, $head, $attachments);
        } else {
            $mail = wp_mail($recipients, $email_subject, $email_message, $head);
        }

        if ($mail) {
            if (isset($form_settings['enable_ar']) && $form_settings['enable_ar'] == 'on') {
                $attachments = isset($attachments) ? $attachments : array();
                $from_ar = isset($form_settings['from_ar']) ? trim($form_settings['from_ar']) : '';
                $from_ar_name = isset($form_settings['from_ar_name']) && ($form_settings['from_ar_name'] != '') ? esc_html($form_settings['from_ar_name']) : esc_html__('No Name', 'hash-form');
                $email_subject = isset($form_settings['email_subject_ar']) && ($form_settings['email_subject_ar'] != '') ? esc_html(apply_filters('hashform_translate_string', $form_settings['email_subject_ar'], 'Hash Form', $form_title . ' - ' . 'Email Auto Responder Subject')) : esc_html__('New Form Submission', 'hash-form');
                $email_message = wpautop(isset($form_settings['email_message_ar']) ? esc_html(apply_filters('hashform_translate_string', $form_settings['email_message_ar'], 'Hash Form', $form_title . ' - ' . 'Email Auto Responder Message')) : '');
                $settings = HashFormSettings::get_settings();
                $header_image = $settings['header_image'];

                ob_start();
                // Same look as the notification; the reply carries only its message, no answers.
                $email_context = 'auto_responder';
                $email_entry_id = $this->entry_id;
                include self::layout_file($email_template);
                $form_html = ob_get_clean();

                $from_ar = ($from_ar == '[admin_email]') ? get_option('admin_email') : esc_attr($from_ar);

                $head = array();
                $head[] = 'Content-Type: text/html; charset=UTF-8';
                $head[] = 'From: ' . esc_html($from_ar_name) . ' <' . esc_html($from_ar) . '>';
                // The field may be optional and left blank.
                if (is_email($reply_to_ar)) {
                    wp_mail($reply_to_ar, $email_subject, $form_html, $head, $attachments);
                }
            }
            $redirect_url = '';

            if ($form_settings['confirmation_type'] == 'show_page') {
                $redirect_url = get_permalink($form_settings['show_page_id']);
            } else if ($form_settings['confirmation_type'] == 'redirect_url') {
                $redirect_url = $form_settings['redirect_url_page'];
            }

            // A replay (admin resend or deferred payment email) must not rerun the post submission
            // actions, which would charge the customer again. No ajax caller is waiting either.
            if (self::$sending_deferred) {
                return true;
            }

            $this->run_after_email($form_settings, $metas);

            if (!empty($redirect_url)) {
                return wp_send_json(array(
                    'status' => 'redirect',
                    'message' => esc_url($redirect_url)
                ));
            }

            return wp_send_json(array(
                'status' => 'success',
                'message' => esc_html(apply_filters('hashform_translate_string', $form_settings['confirmation_message'], 'Hash Form', $form_title . ' - ' . 'Confirmation Message'))
            ));
        }

        if (self::$sending_deferred) {
            return false;
        }

        /*
         * The entry is stored, so post submission actions (payment hand-off,
         * integrations) still run when the notification fails. Marked undelivered
         * first, because a payment hand-off answers the request itself.
         */
        global $wpdb;
        $wpdb->update($wpdb->prefix . 'hashform_entries', array('delivery_status' => 0), array('id' => $this->entry_id));

        $this->run_after_email($form_settings, $metas);

        return false;
    }

    /**
     * Email templates by key: a label, the layout file that wraps the message, and the callable that renders one answer.
     *
     * @return array
     */
    public static function templates() {
        $dir = HASHFORM_PATH . 'admin/settings/email-templates/';
        $templates = array(
            'template1' => array('label' => esc_html__('Classic', 'hash-form'), 'file' => $dir . 'template1.php', 'row' => array(__CLASS__, 'template1')),
            'template2' => array('label' => esc_html__('Striped Rows', 'hash-form'), 'file' => $dir . 'template2.php', 'row' => array(__CLASS__, 'template2')),
            'template3' => array('label' => esc_html__('Dark Labels', 'hash-form'), 'file' => $dir . 'template3.php', 'row' => array(__CLASS__, 'template3')),
        );

        /**
         * Add email templates. The layout file gets $email_message (the message, answers included),
         * $header_image (attachment id), $email_context ('notification', 'auto_responder' or 'test') and
         * $email_entry_id (0 for a test);
         * the row callable gets ($title, $entry_value, $count) and returns HTML.
         *
         * @param array $templates key => array('label' => string, 'file' => string, 'row' => callable)
         */
        $filtered = apply_filters('hashform_email_templates', $templates);

        foreach ((array) $filtered as $key => $template) {
            if (empty($template['label']) || empty($template['file']) || !is_readable($template['file']) || empty($template['row']) || !is_callable($template['row'])) {
                unset($filtered[$key]);
            }
        }

        return $filtered ? $filtered : $templates;
    }

    /**
     * A known template key; anything else (a template whose plugin is gone) falls back to Classic (template1).
     */
    public static function email_template($settings) {
        $template = is_array($settings) && isset($settings['email_template']) ? $settings['email_template'] : (is_string($settings) ? $settings : '');
        $templates = self::templates();

        return isset($templates[$template]) ? $template : 'template1';
    }

    /**
     * The accent color the templates use, or '' for their own colors. Hash Form Pro sets one.
     */
    public static function accent_color() {
        $color = sanitize_hex_color((string) apply_filters('hashform_email_accent_color', ''));

        return $color ? $color : '';
    }

    /** $color mixed with $with; $weight is how much of $with (0-1). */
    public static function mix_color($color, $with, $weight) {
        $a = array_map('hexdec', str_split(self::long_hex($color), 2));
        $b = array_map('hexdec', str_split(self::long_hex($with), 2));
        $out = '#';

        for ($i = 0; $i < 3; $i++) {
            $out .= sprintf('%02x', (int) round($a[$i] + ($b[$i] - $a[$i]) * $weight));
        }

        return $out;
    }

    /** White or near-black, whichever reads better on $color. */
    public static function text_on_color($color) {
        list($r, $g, $b) = array_map('hexdec', str_split(self::long_hex($color), 2));

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 160 ? '#111827' : '#ffffff';
    }

    private static function long_hex($color) {
        $hex = ltrim((string) $color, '#');

        return 3 === strlen($hex) ? $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2] : str_pad(substr($hex, 0, 6), 6, '0');
    }

    /** One answer rendered by the template. */
    public static function render_row($template, $title, $entry_value, $count) {
        $templates = self::templates();

        return (string) call_user_func($templates[self::email_template($template)]['row'], $title, $entry_value, $count);
    }

    /** The template's layout file. */
    public static function layout_file($template) {
        $templates = self::templates();

        return $templates[self::email_template($template)]['file'];
    }

    /**
     * Each answer as the emails show it, and the #form_details table built from them.
     *
     * @param array  $metas
     * @param string $email_template A key from templates().
     * @return array array(field id => display value, table html)
     */
    private function entry_display_values($metas, $email_template) {
        $file_img_placeholder = HASHFORM_URL . 'img/attachment.png';
        $entry_values = array();
        $frm_table = '';
        $count = 0;

        foreach ($metas as $item => $value) {
            $count++;
            $entry_value = HashFormHelper::unserialize_or_decode($value['value']);
            $entry_type = HashFormHelper::unserialize_or_decode($value['type']);
            if (is_array($entry_value)) {
                if ($entry_type == 'name') {
                    $entry_value = implode(' ', array_filter($entry_value));
                } elseif ($entry_type == 'repeater_field') {
                    $entry_value = HashFormHelper::render_repeater_table($entry_value);
                } else {
                    $entry_value = implode(',<br>', array_filter($entry_value));
                }
            }
            // No profile link here: a recipient may have no access to wp-admin.
            if ($entry_type == 'user_id') {
                $entry_value = HashFormFieldUserID::format_value(
                    $entry_value,
                    HashFormFieldUserID::capture_from_options(isset($value['options']) ? $value['options'] : array()),
                    false
                );
            }

            if ($entry_type == 'upload' && trim($entry_value)) {
                $files_arr = explode(',', $entry_value);
                $upload_value = '';
                foreach ($files_arr as $file) {
                    $file_info = pathinfo($file);
                    $file_name = $file_info['basename'];
                    $file_extension = $file_info['extension'];

                    $upload_value .= '<div style="margin-bottom: 10px;padding-bottom: 10px;border-bottom: 1px solid #EEE;">';
                    $upload_value .= '<div><a href="' . esc_url($file) . '" target="_blank">';
                    if (in_array($file_extension, array('jpg', 'jpeg', 'png', 'gif', 'bmp'))) {
                        $upload_value .= '<img style="width:150px" src="' . esc_url($file) . '">';
                    } else {
                        $upload_value .= '<img style="width: 40px;border: 1px solid #666;border-radius: 6px;padding: 4px;" src="' . esc_url($file_img_placeholder) . '">';
                    }
                    $upload_value .= '</a></div>';
                    $upload_value .= '<label><a href="' . esc_url($file) . '" target="_blank">';
                    $upload_value .= esc_html($file_name) . '</a></label>';
                    $upload_value .= '</div>';
                }
                $entry_value = $upload_value;
            }
            // Same date formatting as the entry screen.
            $entry_value = HashFormHelper::format_date_value($entry_value, $entry_type);

            /** This filter is documented in admin/entries/entry-detail.php */
            $entry_value = apply_filters('hashform_entry_display_value', $entry_value, $entry_type, $value, 'email');

            $frm_table .= self::render_row($email_template, $value['name'], $entry_value, $count);
            $entry_values[$item] = $entry_value;
        }

        return array($entry_values, $frm_table);
    }

    /**
     * Fill #field_id_N, #form_title and #form_details in the settings that take tags.
     *
     * @param array  $form_settings
     * @param array  $entry_values  field id => display value
     * @param string $frm_table
     * @return array
     */
    private function fill_tags($form_settings, $entry_values, $frm_table) {
        $replace_keys = apply_filters('hashform_replace_keys_arr', array('email_message'));

        foreach ($entry_values as $item => $entry_value) {
            foreach ($replace_keys as $key) {
                if (isset($form_settings[$key]) && $form_settings[$key] && is_string($form_settings[$key])) {
                    $form_settings[$key] = str_replace('#field_id_' . $item, $entry_value, $form_settings[$key]);
                }
            }
        }

        foreach ($replace_keys as $key) {
            if (isset($form_settings[$key]) && $form_settings[$key] && is_string($form_settings[$key])) {
                $key_val = str_replace('#form_title', $this->form->name, $form_settings[$key]);
                $form_settings[$key] = str_replace('#form_details', $frm_table, $key_val);
            }
        }

        return $form_settings;
    }

    /**
     * Post submission actions: the payment hand-off, Pro integrations and anything a site adds.
     */
    private function run_after_email($form_settings, $metas) {
        do_action('hashform_after_email', array(
            'form' => $this->form,
            'entry_id' => $this->entry_id,
            'form_settings' => $form_settings,
            'metas' => $metas,
            'location' => $this->location
        ));
    }

    public static function template1($title, $entry_value, $count) {
        ob_start();
        ?>
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; margin-bottom: 25px">
            <tbody>
                <tr>
                    <th style="font-family: sans-serif; font-size: 14px; vertical-align: top;text-align:left; line-height: 18px;" valign="top"><?php echo esc_html($title); ?></th>
                </tr>
                <tr>
                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; padding: 10px 0 0 0; line-height: 18px;" valign="top"><?php echo wp_kses_post($entry_value); ?></td>
                </tr>
            </tbody>
        </table>
        <?php
        $form_html = ob_get_clean();
        return $form_html;
    }

    public static function template2($title, $entry_value, $count) {
        ob_start();
        $border_style = '';

        $accent = self::accent_color();

        if ($count == 1) {
            $border_style = 'border-top: 5px solid ' . ($accent ? $accent : '#4183D7') . ';';
        }

        if ($count % 2 == 0) {
            $style = 'background: ' . ($accent ? self::mix_color($accent, '#ffffff', 0.94) : '#f9f9ff') . ';';
        } else {
            $style = 'background: #FFFFFF;';
        }
        ?>
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;padding: 25px; <?php echo esc_attr($border_style . $style); ?>">
            <tbody>
                <tr>
                    <th style="font-family: sans-serif; font-size: 14px; vertical-align: top;text-align:left; line-height: 18px;" valign="top"><?php echo esc_html($title); ?></th>
                </tr>
                <tr>
                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; padding: 10px 0 0 0; line-height: 18px;" valign="top"><?php echo wp_kses_post($entry_value); ?></td>
                </tr>
            </tbody>
        </table>
        <?php
        $form_html = ob_get_clean();
        return $form_html;
    }

    public static function template3($title, $entry_value, $count) {
        // The bars stay dark: the accent deepened, with whichever text color reads on it.
        $accent = self::accent_color();
        $bar = $accent ? self::mix_color($accent, '#000000', 0.3) : '#000';
        $bar_text = $accent ? self::text_on_color($bar) : '#FFF';
        ob_start();
        ?>
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; background:#FFF; margin-bottom: 25px">
            <tbody>
                <tr>
                    <th style="font-family: sans-serif; font-size: 14px; vertical-align: top;text-align:left; line-height: 18px;background: <?php echo esc_attr($bar); ?>;color: <?php echo esc_attr($bar_text); ?>;text-transform: uppercase;padding: 10px 20px;" valign="top"><?php echo esc_html($title); ?></th>
                </tr>
                <tr>
                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; padding: 10px 0 0 0; line-height: 18px;padding: 20px !important;" valign="top"><?php echo wp_kses_post($entry_value); ?></td>
                </tr>
            </tbody>
        </table>
        <?php
        $form_html = ob_get_clean();
        return $form_html;
    }


}
