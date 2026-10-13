<?php

defined('ABSPATH') || die();

class HashFormLoader {

    public function __construct() {
        add_filter('admin_body_class', array($this, 'add_admin_class'), 999);
        add_action('admin_enqueue_scripts', array($this, 'admin_init'), 11);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'), 11);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('elementor/editor/after_enqueue_styles', array($this, 'elementor_editor_styles'));
        add_action('elementor/preview/enqueue_styles', array($this, 'elementor_preview_styles'));
    }

    public static function add_admin_class($classes) {
        if (HashFormHelper::is_form_builder_page()) {
            $full_screen_on = self::get_full_screen_setting();
            if ($full_screen_on) {
                $classes .= ' is-fullscreen-mode';
                wp_enqueue_style('wp-edit-post'); // Load the CSS for .is-fullscreen-mode.
            }
        }
        return $classes;
    }

    private static function get_full_screen_setting() {
        global $wpdb;
        $meta_key = $wpdb->get_blog_prefix() . 'persisted_preferences';
        $prefs = get_user_meta(get_current_user_id(), $meta_key, true);
        if ($prefs && isset($prefs['core/edit-post']['fullscreenMode']))
            return $prefs['core/edit-post']['fullscreenMode'];
        return true;
    }

    public static function admin_init() {
        $page = HashFormHelper::get_var('page', 'sanitize_title');

        // The style-template editor is a regular post screen but is driven by
        // the same admin assets (color picker, chosen, live preview, ...).
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $is_style_template_screen = ($screen && 'hashform-styles' === $screen->post_type)
                || HashFormStyleBuilder::is_builder();

        // Nothing below is needed outside the plugin's own screens.
        if (strpos($page, 'hashform') !== 0 && !$is_style_template_screen) {
            return;
        }

        // The style builder's slug starts with hashform too, but it is not the form builder.
        if (strpos($page, 'hashform') === 0 && !HashFormStyleBuilder::is_builder()) {
            wp_enqueue_script('hashform-builder', HASHFORM_URL . 'js/builder.js', array('jquery', 'jquery-ui-core', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'wp-i18n', 'wp-hooks', 'jquery-ui-dialog', 'hashform-select2'), HASHFORM_VERSION, true);
            wp_enqueue_script('hashform-backend', HASHFORM_URL . 'js/backend.js', array('jquery', 'jquery-ui-core', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable', 'wp-i18n', 'wp-hooks', 'jquery-ui-dialog', 'jquery-ui-datepicker'), HASHFORM_VERSION, true);

            wp_localize_script('hashform-backend', 'hashform_backend_js', array(
                'nonce' => wp_create_nonce('hashform_backend_ajax'),
                'entry_nonce' => wp_create_nonce('hashform_entry_action'),
                'note_saved' => esc_html__('Note saved.', 'hash-form'),
                'note_error' => esc_html__('The note could not be saved.', 'hash-form'),
                'resend_confirm' => esc_html__('Send the notification emails for this entry again?', 'hash-form'),
                'generic_error' => esc_html__('Something went wrong. Please reload the page and try again.', 'hash-form'),
                'form_name_required' => esc_html__('Please give the form a name.', 'hash-form'),
                /* translators: %s: the spacer's height in pixels, filled in by the builder. */
                'spacer_label' => sprintf(esc_html__('Spacer · %spx', 'hash-form'), '%d'),
                'search_fields' => esc_html__('Search fields', 'hash-form'),
                'no_tag_fields' => esc_html__('This form has no field to insert here yet.', 'hash-form'),
                'no_tag_matches' => esc_html__('No field matches that.', 'hash-form'),
                'invalid_email' => esc_html__('Enter a valid email address.', 'hash-form'),
            ));

            wp_localize_script('hashform-builder', 'hashform_backend_js', array(
                'nonce' => wp_create_nonce('hashform_backend_ajax'),
            ));

            // Separate from hashform_backend_js: the backend script declares that global later and would overwrite it.
            wp_localize_script('hashform-builder', 'hashform_builder_js', array(
                'drop_field_here' => esc_html__('Drop a field here', 'hash-form'),
                'move' => esc_html__('Move Row', 'hash-form'),
                'delete' => esc_html__('Delete Row', 'hash-form'),
                'delete_row' => esc_html__('Delete this row?', 'hash-form'),
                'delete_row_with_fields' => esc_html__('Delete this row and the fields in it?', 'hash-form'),
            ));
        }

        if (strpos($page, 'hashform-smtp') === 0) {
            wp_enqueue_script('plugin-install');
            wp_enqueue_script('updates');
        }

        wp_enqueue_script('hashform-chosen', HASHFORM_URL . 'js/chosen.jquery.js', array('jquery'), HASHFORM_VERSION, true);
        wp_enqueue_script('hashform-select2', HASHFORM_URL . 'js/select2.min.js', array('jquery'), HASHFORM_VERSION, true);
        // Adds a search box to long dropdowns. No dependencies.
        wp_enqueue_script('hashform-searchable-select', HASHFORM_URL . 'js/searchable-select.js', array(), HASHFORM_VERSION, true);
        wp_localize_script('hashform-searchable-select', 'hashformSearchableSelect', array(
            /**
             * How many options a dropdown needs before it opens with a search box.
             *
             * @param int $threshold
             */
            'threshold' => absint(apply_filters('hashform_searchable_select_threshold', 10)),
            'i18n' => array(
                'search' => esc_html__('Search', 'hash-form'),
                'noResults' => esc_html__('No matches', 'hash-form'),
                'oneResult' => esc_html__('1 result', 'hash-form'),
                /* translators: %d: number of matching options. */
                'results' => esc_html__('%d results', 'hash-form'),
            ),
        ));
        wp_enqueue_script('jquery-condition', HASHFORM_URL . 'js/jquery-condition.js', array('jquery'), HASHFORM_VERSION, true);
        wp_enqueue_script('wp-color-picker-alpha', HASHFORM_URL . 'js/wp-color-picker-alpha.js', array('wp-color-picker'), HASHFORM_VERSION, true);
        wp_enqueue_script('hashform-admin-settings', HASHFORM_URL . 'js/admin-settings.js', array('jquery'), HASHFORM_VERSION, true);

        wp_localize_script('hashform-admin-settings', 'hashform_admin_js_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hashform_admin_settings_ajax'),
            'installing_text' => esc_html__('Installing WP Mail SMTP', 'hash-form'),
            'activating_text' => esc_html__('Activating WP Mail SMTP', 'hash-form'),
            'error' => esc_html__('Error! Reload the page and try again.', 'hash-form'),
        ));

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('materialdesignicons', HASHFORM_URL . 'fonts/materialdesignicons.css', array(), HASHFORM_VERSION);
        wp_enqueue_style('hashform-chosen', HASHFORM_URL . 'css/chosen.css', array(), HASHFORM_VERSION);
        wp_enqueue_style('hashform-select2', HASHFORM_URL . 'css/select2.min.css', array(), HASHFORM_VERSION);
        wp_enqueue_style('hashform-searchable-select', HASHFORM_URL . 'css/searchable-select.css', array(), HASHFORM_VERSION);
        // Tokens load first and are a dependency of everything else, so any
        // stylesheet can rely on the custom properties being defined.
        wp_enqueue_style('hashform-tokens', HASHFORM_URL . 'css/design-tokens.css', array(), HASHFORM_VERSION);
        wp_enqueue_style('hashform-admin', HASHFORM_URL . 'css/admin-style.css', array('hashform-tokens'), HASHFORM_VERSION);

        // Loaded after the legacy admin-style.css so the reworked screens win.
        wp_enqueue_style('hashform-builder-ui', HASHFORM_URL . 'css/builder.css', array('hashform-admin'), HASHFORM_VERSION);
        wp_enqueue_style('hashform-file-uploader', HASHFORM_URL . 'css/file-uploader.css', array(), HASHFORM_VERSION);
        wp_enqueue_style('hashform-admin-settings', HASHFORM_URL . 'css/admin-settings.css', array(), HASHFORM_VERSION);
        wp_enqueue_style('hashform-style', HASHFORM_URL . 'css/style.css', array(), HASHFORM_VERSION);

        // Forms and Entries lists. Scoped to .hf-list-screen, so it is inert
        // on the screens that do not carry that wrapper.
        wp_enqueue_style('hashform-list-screens', HASHFORM_URL . 'css/list-screens.css', array('hashform-admin'), HASHFORM_VERSION);

        $fonts_url = HashFormStyles::fonts_url();

        // Load Fonts if necessary.
        if ($fonts_url) {
            wp_enqueue_style('hashform-fonts', $fonts_url, array(), HASHFORM_VERSION);
        }
    }

    public static function elementor_editor_styles() {
        // The widget mark as one inline rule, from the same drawing as the field icons.
        wp_register_style('hashform-elementor-icon', false, array(), HASHFORM_VERSION);
        wp_enqueue_style('hashform-elementor-icon');
        wp_add_inline_style('hashform-elementor-icon', HashFormFieldIcons::elementor_icon_css());
    }

    // Frontend assets are only registered here; enqueue_form_assets() enqueues
    // them when a form is rendered.
    public static function enqueue_styles() {
        // No stock jquery.timepicker stylesheet: the time picker is styled in style.css with the date picker.
        wp_register_style('hashform-file-uploader', HASHFORM_URL . 'css/file-uploader.css', array(), HASHFORM_VERSION);
        wp_register_style('materialdesignicons', HASHFORM_URL . 'fonts/materialdesignicons.css', array(), HASHFORM_VERSION);
        wp_register_style('hashform-style', HASHFORM_URL . 'css/style.css', array(), HASHFORM_VERSION);

        $fonts_url = HashFormStyles::fonts_url();
        if ($fonts_url) {
            wp_register_style('hashform-fonts', $fonts_url, array(), HASHFORM_VERSION);
        }
    }

    public static function enqueue_scripts() {
        wp_register_script('jquery-timepicker', HASHFORM_URL . 'js/jquery.timepicker.min.js', array('jquery'), HASHFORM_VERSION, true);
        wp_register_script('hashform-file-uploader', HASHFORM_URL . 'js/file-uploader.js', array(), HASHFORM_VERSION, true);
        wp_localize_script('hashform-file-uploader', 'hashform_file_vars', array(
            'remove_txt' => esc_html__('Remove', 'hash-form')
        ));
        wp_register_script('frontend', HASHFORM_URL . 'js/frontend.js', array('jquery', 'jquery-ui-datepicker', 'jquery-timepicker', 'hashform-file-uploader'), HASHFORM_VERSION, true);
        wp_localize_script('frontend', 'hashform_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'ajax_nounce' => wp_create_nonce('hashform-upload-ajax-nonce'),
            'preview_img' => '',
            // Shown when a submission is refused without a usable message of
            // its own, so the visitor is never left with a silent form.
            'generic_error' => esc_html__('Your submission could not be sent. Please try again.', 'hash-form'),
        ));
    }

    /**
     * Everything a rendered form needs to look right. Separate from enqueue_form_assets() so the Elementor editor gets styles without scripts.
     */
    public static function enqueue_form_styles() {
        wp_enqueue_style('dashicons');
        wp_enqueue_style('hashform-file-uploader');
        wp_enqueue_style('materialdesignicons');
        wp_enqueue_style('hashform-style');
        wp_enqueue_style('hashform-fonts');
    }

    /**
     * The form stylesheets inside the Elementor editor's preview iframe, where the widget's render() does not enqueue them.
     *
     * Styles only, so submit and conditional logic do not run in the editor. Elementor fires this at
     * wp_enqueue_scripts priority 20, after the handles are registered at 11.
     */
    public static function elementor_preview_styles() {
        self::enqueue_form_styles();
    }

    public static function enqueue_form_assets() {
        self::enqueue_form_styles();

        wp_enqueue_script('jquery-ui-slider');
        wp_enqueue_script('jquery-timepicker');
        wp_enqueue_script('hashform-file-uploader');
        // Core's copy, registered by WordPress since 4.6.
        wp_enqueue_script('moment');
        wp_enqueue_script('frontend');
    }

}

new HashFormLoader();
