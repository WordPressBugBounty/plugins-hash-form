<?php
defined('ABSPATH') || die();

class HashFormSmtp {

    public function __construct() {
        add_action('admin_menu', array($this, 'menu'), 45);
        add_action('wp_ajax_hashform_activate_plugin', array($this, 'activate_plugin'));
        add_action('admin_init', array($this, 'redirect_to_smtp_settings'));
    }

    public function menu() {
        add_submenu_page('hashform', 'Hash Form | ' . esc_html__('SMTP', 'hash-form'), esc_html__('SMTP', 'hash-form'), 'hashform_manage_settings', 'hashform-smtp', array($this, 'smtp'));
    }

    public function smtp() {
        ?>
        <div class="hf-smtp-page">
            <div class="hf-require-wp-mail-smtp-notice">
                <h3><?php esc_html_e("Why Use SMTP?", "hash-form"); ?></h3>
                <p><?php esc_html_e("WordPress’ email feature uses the Hypertext Preprocessor (PHP) mail() function by default. However, it is not the most effective tool as it may trigger spam filters and send error messages to its users.", "hash-form"); ?></p>
                <p><?php esc_html_e("The Simple Mail Transfer Protocol (SMTP) server is better for WordPress website owners who frequently exchange emails with their visitors. It offers high security and deliverability to ensure properly sent emails. To use it, connect your email service to a third-party SMTP provider and install an SMTP plugin on your WordPress site.", "hash-form"); ?></p>
                <p>
                    <?php
                    /* translators: 1: link open, 2:link close */
                    echo sprintf(esc_html__('See Detail Article %1$shere%2$s', 'hash-form'), '<a href="https://hashthemes.com/what-is-smtp-and-best-wordpress-smtp-plugins/" target="_blank">', '</a>');
                    ?>
                </p>
                <?php
                $all_plugins = get_plugins();
                if (!array_key_exists('wp-mail-smtp/wp_mail_smtp.php', $all_plugins)) {
                    ?>
                    <a href="#" class="button hf-install-wp-mail-smtp-plugin"><?php echo esc_html__('Install WP Mail SMTP Plugin', 'hash-form') ?></a>
                    <?php
                } else if (!is_plugin_active('wp-mail-smtp/wp_mail_smtp.php')) {
                    ?>
                        <a href="#" class="button hf-activate-wp-mail-smtp-plugin"><?php echo esc_html__('Activate WP Mail SMTP Plugin', 'hash-form') ?></a>
                    <?php
                }
                ?>
            </div>
        </div>

        <?php
    }

    /**
     * The plugin this screen exists to switch on, free edition or Pro.
     *
     * @return string Plugin file of whichever edition is installed, or ''.
     */
    public static function smtp_plugin_file() {
        $installed = get_plugins();

        foreach (array('wp-mail-smtp/wp_mail_smtp.php', 'wp-mail-smtp-pro/wp_mail_smtp.php') as $candidate) {
            if (array_key_exists($candidate, $installed)) {
                return $candidate;
            }
        }

        return '';
    }

    public static function activate_plugin() {
        /*
         * activate_plugins, not manage_options. They are the same person on a
         * single site, and a different one on multisite, where a site
         * administrator has the second and not the first.
         */
        if (!current_user_can('activate_plugins')) {
            wp_send_json(array('success' => false));
        }

        check_ajax_referer('hashform_admin_settings_ajax', 'admin_setting_nonce');

        /*
         * The plugin to switch on is decided here, not taken from the request.
         * The slug and file used to arrive by POST and were joined into a path
         * and activated, so anyone who could reach this endpoint could switch
         * on any plugin already sitting on the site by naming it. The script
         * only ever sent the one value, so nothing is lost by looking it up.
         */
        $plugin_file = self::smtp_plugin_file();
        $success = false;

        if ($plugin_file) {
            $result = activate_plugin($plugin_file);

            if (!is_wp_error($result)) {
                $success = true;
            }
        }

        wp_send_json(array('success' => $success));
    }

    public static function redirect_to_smtp_settings() {
        /*
         * Either edition counts. Only the free plugin's folder was named here
         * and in the markup, so a site running WP Mail SMTP Pro — a different
         * folder, same settings page — was told to install the free one it
         * already had a paid version of.
         *
         * function_exists() stays alongside is_plugin_active(): the plugin
         * only defines it once its own requirement checks have passed, and
         * without that a site where it is active but has bailed would be sent
         * to a settings page that was never registered.
         */
        $plugin_file = self::smtp_plugin_file();
        $is_active = $plugin_file && is_plugin_active($plugin_file);

        if (HashFormHelper::is_admin_page('hashform-smtp') && function_exists('wp_mail_smtp') && $is_active) {
            wp_safe_redirect(admin_url('admin.php?page=wp-mail-smtp'));
            exit;
        }
    }

}

new HashFormSmtp();
