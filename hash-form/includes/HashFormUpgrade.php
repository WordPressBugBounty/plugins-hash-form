<?php
defined('ABSPATH') || die();

/**
 * The Upgrade to Pro screen. Only registered while Pro is not installed.
 */
class HashFormUpgrade {

    const PAGE_SLUG = 'hashform-upgrade';
    const PRO_URL = 'https://hashformbuilder.com/';
    const PRO_DOCS_URL = 'https://hashthemes.com/documentation/hash-form-pro-documentation/';

    public function __construct() {
        add_action('admin_menu', array($this, 'menu'), 60);
        add_action('in_admin_header', array($this, 'header'));
        add_action('admin_head', array($this, 'menu_style'));
        add_action('admin_enqueue_scripts', array($this, 'scripts'));
    }

    /**
     * Whether Pro is active. Checks the constant, not the plugin file, so Pro at an unusual path is recognized.
     */
    public static function pro_active() {
        return defined('HASH_FORM_PRO_VERSION');
    }

    /**
     * The Upgrade screen, opened on one topic of the comparison.
     */
    public static function topic_url($topic) {
        return admin_url('admin.php?page=' . self::PAGE_SLUG) . '#' . sanitize_key($topic);
    }

    public static function is_screen() {
        return is_admin() && self::PAGE_SLUG === HashFormHelper::get_var('page', 'sanitize_title');
    }

    public function menu() {
        if (self::pro_active()) {
            return;
        }

        add_submenu_page(
                'hashform',
                esc_html__('Upgrade to Pro', 'hash-form'),
                esc_html__('Upgrade to Pro', 'hash-form'),
                'hashform_view_forms',
                self::PAGE_SLUG,
                array($this, 'render')
        );
    }

    /**
     * Highlight the menu entry.
     */
    public function menu_style() {
        if (self::pro_active()) {
            return;
        }
        ?>
        <style>
            #adminmenu .toplevel_page_hashform a[href$="page=hashform-upgrade"] {
                color: #ffb976;
                font-weight: 600;
            }
        </style>
        <?php
    }

    /**
     * Header bar, as on every other screen.
     */
    public function header() {
        if (!self::is_screen()) {
            return;
        }

        HashFormHelper::render_list_header(array(
            'title' => esc_html__('Upgrade to Pro', 'hash-form'),
            'docs' => self::PRO_DOCS_URL,
            'actions' => array(
                array(
                    'label' => esc_html__('Get Hash Form Pro', 'hash-form'),
                    'url' => self::PRO_URL,
                    'class' => 'hf-upgrade-cta',
                ),
            ),
        ));
    }

    public function scripts() {
        if (self::is_screen()) {
            wp_enqueue_script('hashform-upgrade', HASHFORM_URL . 'js/upgrade.js', array(), HASHFORM_VERSION, true);
        }
    }

    public function render() {
        if (!HashFormCapabilities::user_can('hashform_view_forms')) {
            wp_die(esc_html__('You do not have permission to do that.', 'hash-form'));
        }

        include HASHFORM_PATH . 'admin/upgrade/compare.php';
    }

}

new HashFormUpgrade();
