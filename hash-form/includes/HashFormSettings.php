<?php
defined('ABSPATH') || die();

class HashFormSettings {

    const DOCS_URL = 'https://hashthemes.com/documentation/hash-form-drag-and-drop-form-builder-documentation/';

    public function __construct() {
        add_action('admin_menu', array($this, 'menu'), 45);
        add_action('in_admin_header', array($this, 'list_header'));

        add_action('wp_ajax_hashform_test_email_template', array($this, 'send_test_email'), 10, 0);
    }

    public function menu() {
        // With Pro active, every setting is managed from the per-module
        // popups on the Modules screen, so the Settings page is not added.
        // (Pro's OAuth callbacks still run on admin_init and exit before the
        // page would render, so their registered redirect URIs keep working.)
        if (!defined('HASH_FORM_PRO_VERSION')) {
            add_submenu_page('hashform', 'Hash Form | ' . esc_html__('Settings', 'hash-form'), esc_html__('Settings', 'hash-form'), 'hashform_manage_settings', 'hashform-settings', array($this, 'route'));
        }
        add_submenu_page('hashform', esc_html__('Documentation', 'hash-form'), esc_html__('Documentation', 'hash-form'), 'hashform_view_forms', esc_url_raw(self::DOCS_URL));
    }

    /**
     * The bar across the top of the Settings screen.
     *
     * The same component the Forms, Entries and style template lists use, so
     * this screen is headed the way every other one is. It replaces the black
     * uppercase title bar this page carried, which was the only one of its
     * kind left in the plugin.
     */
    public function list_header() {
        if (!self::is_settings_page()) {
            return;
        }

        HashFormHelper::render_list_header(array(
            'title' => esc_html__('Settings', 'hash-form'),
            'docs' => self::DOCS_URL,
        ));
    }

    private static function is_settings_page() {
        return !defined('HASH_FORM_PRO_VERSION')
                && 'hashform-settings' === HashFormHelper::get_var('page', 'sanitize_title');
    }

    public function route() {
        $action = HashFormHelper::get_post('hashform_action', 'sanitize_title');
        if ($action == 'process-form') {
            self::process_form();
        } else {
            self::display_form();
        }
    }

    public static function display_form() {
        $settings = self::get_settings();
        $sections = apply_filters('hash_form_settings_sections', array(
            'captcha-settings' => array(
                'name' => esc_html__('Captcha', 'hash-form'),
                'icon' => 'mdi mdi-security',
                'desc' => esc_html__('The reCAPTCHA keys every captcha field on the site checks against, and the language and score it runs at.', 'hash-form'),
            ),
            'email-settings' => array(
                'name' => esc_html__('Email Settings', 'hash-form'),
                'icon' => 'mdi mdi-email-multiple-outline',
                'desc' => esc_html__('The header image and template used for every email this plugin sends.', 'hash-form'),
            ),
            'general-settings' => array(
                'name' => esc_html__('General', 'hash-form'),
                'icon' => 'mdi mdi-tune',
                'desc' => esc_html__('Site-wide options that apply to every form.', 'hash-form'),
            )
        ));
        $vars = apply_filters('hash_form_settings_vars', array(
            'current' => 'captcha-settings'
        ));
        extract($vars);

        // Deep-link support: honor ?t=<section> and never point at a section
        // that does not exist (e.g. after the pro plugin swaps the list).
        $requested_tab = HashFormHelper::get_var('t', 'sanitize_title');
        if ($requested_tab && isset($sections[$requested_tab])) {
            $current = $requested_tab;
        } elseif (!isset($sections[$current])) {
            $current = key($sections);
        }
        ?>

        <?php // The header bar is printed on in_admin_header; see list_header(). ?>
        <div class="hf-content hf-list-screen hf-settings-screen">
            <div class="hf-list-wrap wrap">
                <h1></h1>

                <?php
                /*
                 * esc_url, not esc_html. esc_html turned the '&amp;' into
                 * '&amp;amp;', which the browser submitted to a literal
                 * '&amp;t=' — so the save landed with $_GET['amp;t'] set and
                 * 't' absent, and every save from a tab that had not been
                 * clicked came back on the first one. esc_url emits '&#038;',
                 * which decodes to a plain '&'.
                 */
                $action_url = '?page=hashform-settings' . ($current ? '&t=' . $current : '');
                ?>
                <form name="hashform_settings_form" method="post" action="<?php echo esc_url($action_url); ?>">
                    <input type="hidden" name="hashform_action" value="process-form" />
                    <input type="hidden" name="hashform_rendered_checkboxes" value="" />
                    <?php wp_nonce_field('hashform_process_form_action', 'hashform_process_form_nonce'); ?>

                    <div class="hf-settings-layout">
                        <div class="hf-settings-nav">
                            <ul class="hf-settings-tab">
                                <?php
                                foreach ($sections as $key => $section) {
                                    ?>
                                    <li class="<?php echo esc_attr($current === $key ? 'hf-active' : ''); ?>">
                                        <a href="#hf-<?php echo esc_attr($key); ?>">
                                            <i class="<?php echo esc_attr($section['icon']); ?>"></i>
                                            <?php echo wp_kses_post($section['name']); ?>
                                        </a>
                                    </li>
                                    <?php
                                }
                                ?>
                            </ul>
                        </div>

                        <div class="hf-settings-panel">
                            <?php HashFormHelper::print_message(); ?>

                            <?php
                            /*
                             * The sections are kept in a wrapper of their own:
                             * the tab script hides the clicked panel's
                             * siblings, and before this the nonce, the hidden
                             * inputs and the saved notice were all siblings
                             * too.
                             */
                            ?>
                            <div class="hf-settings-sections">
                                <?php
                                foreach ($sections as $key => $section) {
                                    ?>
                                    <div id="hf-<?php echo esc_attr($key); ?>" class="hf-settings-section <?php echo ($current === $key) ? '' : 'hf-hidden'; ?>">
                                        <div class="hf-panel-head">
                                            <h2><?php echo esc_html($section['name']); ?></h2>
                                            <?php if (!empty($section['desc'])) { ?>
                                                <p class="hf-panel-desc"><?php echo esc_html($section['desc']); ?></p>
                                            <?php } ?>
                                        </div>
                                        <?php
                                        $path = '';

                                        if (file_exists(HASHFORM_PATH . 'admin/settings/' . $key . '.php')) {
                                            $path = HASHFORM_PATH . 'admin/settings/' . $key . '.php';
                                        } else {
                                            $path = apply_filters('hash_form_settings_sections_path', $key);
                                        }
                                        include($path);
                                        ?>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>

                            <div class="hf-footer">
                                <input class="button button-primary button-large" type="submit" value="<?php esc_attr_e('Save Changes', 'hash-form'); ?>" />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <script>
        (function () {
            // Keep the selected tab in the form action and the address bar so
            // saving (or reloading) returns to the same section.
            var form = document.forms['hashform_settings_form'];
            if (!form) {
                return;
            }
            // Tell the server which checkboxes this page rendered, so it only
            // forces those off when they are unchecked (absent from the POST).
            form.addEventListener('submit', function () {
                var keys = [];
                form.querySelectorAll('input[type="checkbox"]').forEach(function (box) {
                    var match = (box.name || '').match(/^hashform_settings\[([^\]]+)\]$/);
                    if (match) {
                        keys.push(match[1]);
                    }
                });
                var field = form.querySelector('input[name="hashform_rendered_checkboxes"]');
                if (field) {
                    field.value = keys.join(',');
                }
            });
            document.querySelectorAll('.hf-settings-tab a').forEach(function (link) {
                link.addEventListener('click', function () {
                    var tab = (this.getAttribute('href') || '').replace('#hf-', '');
                    if (!tab) {
                        return;
                    }
                    var url = new URL(window.location.href);
                    url.searchParams.set('t', tab);
                    window.history.replaceState(null, '', url.toString());
                    form.setAttribute('action', '?page=hashform-settings&t=' + encodeURIComponent(tab));
                });
            });
        })();
        </script>
        <?php
    }

    public static function process_form() {
        $process_form = HashFormHelper::get_post('hashform_process_form_nonce');
        if (!wp_verify_nonce($process_form, 'hashform_process_form_action')) {
            wp_die(esc_html__('Permission Denied', 'hash-form'));
        }

        $posted = HashFormHelper::get_post('hashform_settings', 'esc_html');
        $posted = is_array($posted) ? $posted : array();

        // Unchecked checkboxes are absent from the POST. Only force off the
        // ones this page actually rendered (listed by the form's JS), so
        // settings managed elsewhere — e.g. the module popups — survive.
        $rendered = HashFormHelper::get_post('hashform_rendered_checkboxes', 'sanitize_text_field');
        $rendered = $rendered ? array_filter(array_map('sanitize_key', explode(',', $rendered))) : array_keys(self::checkbox_settings());
        foreach ($rendered as $checkbox_key) {
            if (!isset($posted[$checkbox_key])) {
                $posted[$checkbox_key] = 'off';
            }
        }

        $posted = HashFormHelper::sanitize_array($posted, self::sanitize_rules());

        // Merge over the saved options instead of replacing them, so fields
        // not present on this page are preserved.
        $settings = array_merge(self::get_settings(), $posted);

        update_option('hashform_options', $settings);
        HashFormHelper::set_message(esc_html__('Settings Saved !', 'hash-form'));

        self::display_form();
    }

    public static function get_settings() {
        $settings = get_option('hashform_options');
        if (!$settings) {
            $settings = self::default_values();
        } else {
            $settings = wp_parse_args($settings, self::default_values());
        }

        return $settings;
    }

    public function send_test_email() {
        HashFormCapabilities::require_cap_ajax('hashform_manage_settings');

        check_ajax_referer('hashform_backend_ajax', 'backend_nonce');

        $settings = self::get_settings();

        $header_image = $settings['header_image'];

        // Whitelist: the value feeds both a callable name and an include path.
        $email_template = HashFormHelper::get_post('email_template');
        if (!in_array($email_template, array('template1', 'template2', 'template3'), true)) {
            $email_template = 'template1';
        }

        $test_email = HashFormHelper::get_post('test_email', 'sanitize_email');
        $email_subject = esc_html__('Test Email', 'hash-form');
        $count = 0;

        $contents = array(
            0 => array(
                'title' => 'Name',
                'value' => 'John Doe'
            ),
            1 => array(
                'title' => 'Email',
                'value' => 'noreply@gmail.com'
            ),
            2 => array(
                'title' => 'Subject',
                'value' => 'Exciting Updates and Important Information Inside!'
            ),
            3 => array(
                'title' => 'Message',
                'value' => '<p>I hope this email finds you well. We are thrilled to share some exciting updates and important information that we believe you will find valuable.</p><p>Your satisfaction is our priority, and we are committed to delivering the best possible experience.</p>'
            )
        );

        $email_message = '<p style="margin-bottom:20px">';
        $email_message .= esc_html__('Hello, this is a test email.', 'hash-form');
        $email_message .= '</p>';
        foreach ($contents as $content) {
            $count++;
            $email_message .= call_user_func('HashFormEmail::' . $email_template, $content['title'], $content['value'], $count);
        }
        ob_start();
        include(HASHFORM_PATH . 'admin/settings/email-templates/' . $email_template . '.php');
        $form_html = ob_get_clean();

        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        $headers = array();
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . esc_attr($site_name) . ' <' . esc_attr($admin_email) . '>';
        $mail = wp_mail($test_email, $email_subject, $form_html, $headers);
        if ($mail) {
            die(wp_json_encode(
                array(
                    'success' => true,
                    'message' => esc_html__('Email Sent Successfully', 'hash-form')
                )
            ));
        }
        die(wp_json_encode(
            array(
                'success' => false,
                'message' => esc_html__('Failed to Send Email', 'hash-form')
            )
        ));
    }

    public static function checkbox_settings() {
        return apply_filters('hash_form_settings_checkbox', array(
            'load_google_fonts' => 'on',
        ));
    }

    public static function default_values() {
        return apply_filters('hash_form_settings_default', array(
            're_type' => 'v2',
            'pubkey_v2' => '',
            'privkey_v2' => '',
            'pubkey_v3' => '',
            'privkey_v3' => '',
            're_lang' => 'en',
            're_threshold' => '0.5',
            /*
             * Read by every captcha field as the message shown when a
             * challenge is not passed, and defined nowhere until now: each of
             * them looked up an array key that did not exist, so a field
             * carrying no message of its own told the visitor "null".
             */
            're_msg' => 'The captcha was not completed correctly. Please try again.',
            'header_image' => '',
            'email_template' => 'template1',
            // Left on so an existing site's typography does not change under
            // it. Sites that would rather not call out to Google can switch it
            // off without touching their style templates.
            'load_google_fonts' => 'on',
        ));
    }

    public static function sanitize_rules() {
        return apply_filters('hash_form_settings_sanitize', array(
            're_type' => 'sanitize_text_field',
            'pubkey_v2' => 'sanitize_text_field',
            'privkey_v2' => 'sanitize_text_field',
            'pubkey_v3' => 'sanitize_text_field',
            'privkey_v3' => 'sanitize_text_field',
            're_lang' => 'sanitize_text_field',
            're_threshold' => 'sanitize_text_field',
            're_msg' => 'sanitize_text_field',
            'header_image' => 'sanitize_text_field',
            'email_template' => 'sanitize_text_field',
            'load_google_fonts' => 'hashform_sanitize_checkbox',
        ));
    }

}

new HashFormSettings();
