<?php

defined('ABSPATH') || die();

class HashFormImportExport {

    public function __construct() {
        // Process a settings export that generates a .json file of the form settings
        add_action('admin_init', array($this, 'process_settings_export'));
        // Process a settings export that generates a .json file of the form style
        add_action('admin_init', array($this, 'process_style_export'));
        // Process a settings import from a json file
        add_action('admin_init', array($this, 'process_settings_import'));
        // Process a style import from a json file
        add_action('admin_init', array($this, 'process_style_import'));

        // The panel imports through this, so it can hold a spinner and report
        // a bad file in place. The admin_init handler stays as the no-JS path.
        add_action('wp_ajax_hashform_import_form_settings', array($this, 'ajax_import_form_settings'));
    }

    /**
     * Form import over AJAX. Same checks as the plain POST path; the outcome
     * is JSON so the panel can stay on screen when a file is rejected.
     */
    public function ajax_import_form_settings() {
        if (!HashFormCapabilities::user_can('hashform_edit_forms')) {
            wp_send_json_error(array('message' => esc_html__('You are not allowed to import forms.', 'hash-form')), 403);
        }

        if (!wp_verify_nonce(HashFormHelper::get_post('hashform_imex_import_nonce'), 'hashform_imex_import_nonce')) {
            wp_send_json_error(array('message' => esc_html__('Your session has expired. Reload the page and try again.', 'hash-form')), 403);
        }

        $form_id = HashFormHelper::get_post('hashform_form_id', 'absint');

        if (!$form_id) {
            wp_send_json_error(array('message' => esc_html__('No form was specified.', 'hash-form')));
        }

        $upload = self::read_uploaded_export('hashform_import_file');

        if (is_wp_error($upload)) {
            wp_send_json_error(array('message' => $upload->get_error_message()));
        }

        self::apply_export_to_form($form_id, $upload['data']);

        HashFormHelper::set_message(esc_html__('Settings Imported Successfully', 'hash-form'));
        wp_send_json_success(array('reload' => true));
    }

    public function process_settings_export() {
        /*
         * These four run on admin_init, so they see every admin page load and
         * must fall through quietly when the request is not theirs. Dying here
         * would take out the whole of wp-admin for anyone without the
         * capability.
         */
        if (!HashFormCapabilities::user_can('hashform_edit_forms')) {
            return;
        }

        $id = HashFormHelper::get_post('hashform_form_id', 'absint');

        if ('export_form' != HashFormHelper::get_post('hashform_imex_action') || !$id) {
            return;
        }

        if (!wp_verify_nonce(HashFormHelper::get_post('hashform_imex_export_nonce'), 'hashform_imex_export_nonce')) {
            return;
        }

        global $wpdb;

        $forms = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}hashform_forms WHERE id=%d", $id));

        foreach ($forms as $form) {
            $form_styles = $form->styles ? unserialize($form->styles, array('allowed_classes' => false)) : [];
            $exdat['form_key'] = $form->form_key ? $form->form_key : '';
            $exdat['options'] = $form->options ? unserialize($form->options, array('allowed_classes' => false)) : [];
            $exdat['status'] = $form->status ? $form->status : 'published';
            $exdat['settings'] = $form->settings ? unserialize($form->settings, array('allowed_classes' => false)) : [];
            $exdat['styles'] = $form_styles;
            $exdat['created_at'] = $form->created_at ? $form->created_at : '';
            $fields = HashFormFields::get_form_fields($form->id);
            $exfield = array();
            foreach ($fields as $field) {
                $efield = array();
                $efield['name'] = $field->name;
                $efield['description'] = $field->description;
                $efield['type'] = $field->type;
                $efield['default_value'] = $field->default_value;
                $efield['options'] = $field->options;
                $efield['field_order'] = absint($field->field_order);
                $efield['required'] = absint($field->required);
                $efield['field_options'] = $field->field_options;
                $exfield[] = $efield;
            }
            $exdat['field'] = $exfield;

            $form_style = isset($form_styles['form_style']) && $form_styles['form_style'] ? $form_styles['form_style'] : 'default-style';

            if ($form_style == 'custom-style') {
                $form_style_id = $form_styles['form_style_template'];
                $hashform_styles = get_post_meta($form_style_id, 'hashform_styles', true);
                $hashform_styles = HashFormHelper::sanitize_array($hashform_styles, HashFormStyles::get_styles_sanitize_array());
                if ($hashform_styles) {
                    $exdat['style'] = $hashform_styles;
                }
            }

            ignore_user_abort(true);

            nocache_headers();
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename=hf-' . $id . '-' . gmdate('m-d-Y') . '.json');
            header("Expires: 0");

            echo wp_json_encode($exdat);
            exit;
        }
    }

    public function process_style_export() {
        /*
         * These four run on admin_init, so they see every admin page load and
         * must fall through quietly when the request is not theirs. Dying here
         * would take out the whole of wp-admin for anyone without the
         * capability.
         */
        if (!HashFormCapabilities::user_can('hashform_edit_forms')) {
            return;
        }

        $id = HashFormHelper::get_post('hashform_style_id', 'absint');

        if ('export_style' != HashFormHelper::get_post('hashform_imex_action') || !$id) {
            return;
        }

        if (!wp_verify_nonce(HashFormHelper::get_post('hashform_imex_export_nonce'), 'hashform_imex_export_nonce')) {
            return;
        }

        $hashform_styles = get_post_meta($id, 'hashform_styles', true);
        $hashform_styles = HashFormHelper::sanitize_array($hashform_styles, HashFormStyles::get_styles_sanitize_array());

        if ($hashform_styles) {

            ignore_user_abort(true);

            nocache_headers();
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename=hf-style-' . $id . '-' . gmdate('m-d-Y') . '.json');
            header("Expires: 0");

            echo wp_json_encode($hashform_styles);
            exit;
        }
    }

    /**
     * Reads an uploaded .json export and returns it as an array.
     *
     * Shared with the Pro plugin's Create New Form dialog, which uploads the
     * same kind of file under a different field name. Every caller used to
     * carry its own copy of these checks, and they drifted: the hardening
     * here had to be applied three times, and twice it was not.
     *
     * @param string $file_key Key within $_FILES.
     * @return array|WP_Error
     */
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- every call site verifies its own nonce immediately before calling this; see import_form(), import_form_submit() and the Pro plugin's run_file_import().
    public static function read_uploaded_export($file_key) {
        $upload_error = isset($_FILES[$file_key]['error']) ? (int) $_FILES[$file_key]['error'] : UPLOAD_ERR_NO_FILE;

        if (UPLOAD_ERR_NO_FILE === $upload_error) {
            return new WP_Error('hashform_no_file', esc_html__('Please upload a file to import', 'hash-form'));
        }

        if (UPLOAD_ERR_OK !== $upload_error) {
            return new WP_Error('hashform_upload_failed', esc_html__('The file could not be uploaded. It may be larger than this server allows.', 'hash-form'));
        }

        $filename = isset($_FILES[$file_key]['name']) ? sanitize_text_field(wp_unslash($_FILES[$file_key]['name'])) : '';
        $extension = explode('.', $filename);
        $extension = strtolower(end($extension));

        if ('json' !== $extension) {
            return new WP_Error('hashform_bad_extension', esc_html__('Please upload a valid .json file', 'hash-form'));
        }

        $tmp = isset($_FILES[$file_key]['tmp_name']) ? sanitize_text_field($_FILES[$file_key]['tmp_name']) : '';

        // Confirms the path came from this request's upload rather than being
        // any readable file on the server.
        if (empty($tmp) || !is_uploaded_file($tmp)) {
            return new WP_Error('hashform_no_file', esc_html__('Please upload a file to import', 'hash-form'));
        }
        // phpcs:enable

        $contents = file_get_contents($tmp);
        $imdat = (false === $contents) ? null : json_decode($contents, true);

        if (!self::is_valid_export($imdat)) {
            return new WP_Error('hashform_bad_file', esc_html__('Please upload a valid file to import', 'hash-form'));
        }

        return array('data' => $imdat, 'filename' => $filename);
    }

    /**
     * The three keys every importer relies on being present.
     */
    public static function is_valid_export($imdat) {
        return is_array($imdat) && isset($imdat['options'], $imdat['settings'], $imdat['styles']);
    }

    /**
     * Writes a decoded export onto an existing form, replacing its fields.
     *
     * The single copy of what used to live in three places. Assumes the
     * caller has already checked capability, nonce and that $imdat is valid.
     *
     * @param int   $form_id Form to write onto.
     * @param array $imdat   Decoded export.
     */
    public static function apply_export_to_form($form_id, $imdat) {
        global $wpdb;

        $form_id = absint($form_id);

        $options = HashFormHelper::recursive_parse_args($imdat['options'], HashFormHelper::get_form_options_default());
        $options = HashFormHelper::sanitize_array($options, HashFormHelper::get_form_options_sanitize_rules());

        $settings = HashFormHelper::recursive_parse_args($imdat['settings'], HashFormHelper::get_form_settings_default());
        $settings = HashFormHelper::sanitize_array($settings, HashFormHelper::get_form_settings_sanitize_rules());

        $styles = HashFormHelper::recursive_parse_args($imdat['styles'], array('form_style' => 'default-style', 'form_style_template' => ''));
        $styles = HashFormHelper::sanitize_array($styles, HashFormHelper::get_form_styles_sanitize_rules());

        if (isset($imdat['style'])) {
            $new_post = array(
                'post_type' => 'hashform-styles',
                'post_title' => 'hashform-style-' . $form_id,
                'post_status' => 'publish',
            );
            $style_id = wp_insert_post($new_post);
            $hashform_styles = HashFormHelper::recursive_parse_args($imdat['style'], HashFormStyles::default_styles());
            $hashform_styles = HashFormHelper::sanitize_array($hashform_styles, HashFormStyles::get_styles_sanitize_array());
            update_post_meta($style_id, 'hashform_styles', $hashform_styles);
            $styles['form_style_template'] = $style_id;
        }

        // An export can carry any status string; only these two leave the
        // form reachable in the list.
        $status = isset($imdat['status']) && in_array($imdat['status'], array('published', 'trash'), true) ? $imdat['status'] : 'published';

        $form = array(
            'options' => serialize($options),
            'status' => $status,
            'settings' => serialize($settings),
            'styles' => serialize($styles),
            'created_at' => current_time('mysql'),
        );

        if (!empty($imdat['created_at']) && strtotime($imdat['created_at'])) {
            $form['created_at'] = gmdate('Y-m-d H:i:s', strtotime($imdat['created_at']));
        }

        $wpdb->update($wpdb->prefix . 'hashform_forms', $form, array('id' => $form_id));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}hashform_fields WHERE form_id=%d", $form_id));

        if (isset($imdat['field']) && is_array($imdat['field']) && !empty($imdat['field'])) {
            foreach ($imdat['field'] as $field) {
                HashFormFields::create_row(array(
                    'name' => isset($field['name']) ? $field['name'] : '',
                    'description' => isset($field['description']) ? $field['description'] : '',
                    'type' => isset($field['type']) ? $field['type'] : 'text',
                    'default_value' => isset($field['default_value']) ? $field['default_value'] : '',
                    'options' => isset($field['options']) ? $field['options'] : '',
                    'field_order' => isset($field['field_order']) ? $field['field_order'] : '',
                    'form_id' => $form_id,
                    'required' => isset($field['required']) ? $field['required'] : false,
                    'field_options' => isset($field['field_options']) ? $field['field_options'] : array()
                ));
            }
        }
    }

    /**
     * Plain POST entry point for the per-form Import/Export panel.
     */
    public function process_settings_import() {
        /*
         * These four run on admin_init, so they see every admin page load and
         * must fall through quietly when the request is not theirs. Dying here
         * would take out the whole of wp-admin for anyone without the
         * capability.
         */
        if (!HashFormCapabilities::user_can('hashform_edit_forms')) {
            return;
        }

        if (wp_doing_ajax()) {
            return;
        }

        $form_id = HashFormHelper::get_post('hashform_form_id', 'absint');

        if ('import_form' != HashFormHelper::get_post('hashform_imex_action') || !$form_id) {
            return;
        }

        if (!wp_verify_nonce(HashFormHelper::get_post('hashform_imex_import_nonce'), 'hashform_imex_import_nonce')) {
            return;
        }

        $upload = self::read_uploaded_export('hashform_import_file');

        if (is_wp_error($upload)) {
            wp_die(esc_html($upload->get_error_message()));
        }

        self::apply_export_to_form($form_id, $upload['data']);

        HashFormHelper::set_message(esc_html__('Settings Imported Successfully', 'hash-form'));
    }

    public function process_style_import() {
        /*
         * These four run on admin_init, so they see every admin page load and
         * must fall through quietly when the request is not theirs. Dying here
         * would take out the whole of wp-admin for anyone without the
         * capability.
         */
        if (!HashFormCapabilities::user_can('hashform_edit_forms')) {
            return;
        }

        $style_id = HashFormHelper::get_post('hashform_style_id', 'absint');

        if ('import_style' != HashFormHelper::get_post('hashform_imex_action') || !$style_id) {
            return;
        }

        if (!wp_verify_nonce(HashFormHelper::get_post('hashform_imex_import_nonce'), 'hashform_imex_import_nonce')) {
            return;
        }

        $filename = isset($_FILES['hashform_import_file']['name']) ? sanitize_text_field(wp_unslash($_FILES['hashform_import_file']['name'])) : '';
        $extension = explode('.', $filename);
        $extension = end($extension);

        if ($extension != 'json') {
            wp_die(esc_html__('Please upload a valid .json file', 'hash-form'));
        }

        $hashform_import_file = isset($_FILES['hashform_import_file']['tmp_name']) ? sanitize_text_field($_FILES['hashform_import_file']['tmp_name']) : '';

        if (empty($hashform_import_file)) {
            wp_die(esc_html__('Please upload a file to import', 'hash-form'));
        }

        // Retrieve the settings from the file and convert the json object to an array.
        $imdat = json_decode(file_get_contents($hashform_import_file), true);

        if (!is_array($imdat)) {
            wp_die(esc_html__('Please upload a valid file to import', 'hash-form'));
        }

        $hashform_styles = HashFormHelper::recursive_parse_args($imdat, HashFormStyles::default_styles());
        $hashform_styles = HashFormHelper::sanitize_array($hashform_styles, HashFormStyles::get_styles_sanitize_array());
        update_post_meta($style_id, 'hashform_styles', $hashform_styles);

        HashFormHelper::set_message(esc_html__('Form Style Imported Successfully', 'hash-form'));
    }

}

new HashFormImportExport();
