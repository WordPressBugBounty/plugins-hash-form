<?php
defined('ABSPATH') || die();

class HashFormEntry {

    use HashFormListActions;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'), 10);
        add_filter('set-screen-option', array($this, 'set_screen_option'), 15, 3);

        add_action('wp_ajax_hashform_process_entry', array($this, 'process_entry'));
        add_action('wp_ajax_nopriv_hashform_process_entry', array($this, 'process_entry'));

        add_action('wp_ajax_hashform_toggle_star', array($this, 'toggle_star'));
        add_action('wp_ajax_hashform_save_entry_note', array($this, 'save_entry_note'));
        add_action('wp_ajax_hashform_resend_notification', array($this, 'resend_notification'));
    }

    /* ===== Entry workflow ===== */

    public static function set_flag($id, $column, $value) {
        global $wpdb;

        if (!in_array($column, array('is_read', 'is_starred'), true)) {
            return false;
        }

        return $wpdb->update($wpdb->prefix . 'hashform_entries', array($column => (int) $value), array('id' => absint($id)));
    }

    public static function mark_read($id) {
        return self::set_flag($id, 'is_read', 1);
    }

    public static function get_unread_count() {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hashform_entries WHERE status='published' AND is_read = 0");
    }

    /**
     * Star and unstar from the entries list without a page load.
     */
    public function toggle_star() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error();
        }

        check_ajax_referer('hashform_entry_action', 'nonce');

        $id = HashFormHelper::get_post('entry_id', 'absint');
        $starred = HashFormHelper::get_post('starred', 'absint') ? 1 : 0;

        if (!$id || !self::entry_exists($id)) {
            wp_send_json_error();
        }

        self::set_flag($id, 'is_starred', $starred);

        wp_send_json_success(array('starred' => $starred));
    }

    /**
     * A private note on an entry, for whoever picks it up next.
     */
    public function save_entry_note() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error();
        }

        check_ajax_referer('hashform_entry_action', 'nonce');

        global $wpdb;
        $id = HashFormHelper::get_post('entry_id', 'absint');
        $note = HashFormHelper::get_post('note', 'sanitize_textarea_field');

        if (!$id || !self::entry_exists($id)) {
            wp_send_json_error();
        }

        $wpdb->update($wpdb->prefix . 'hashform_entries', array('notes' => $note), array('id' => $id));

        wp_send_json_success(array('note' => $note));
    }

    /**
     * Sends the notification emails for an entry again, for when the original
     * bounced or the address was wrong at the time.
     */
    public function resend_notification() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error();
        }

        check_ajax_referer('hashform_entry_action', 'nonce');

        global $wpdb;
        $id = HashFormHelper::get_post('entry_id', 'absint');
        $entry = self::get_entry_vars($id);

        if (!$entry) {
            wp_send_json_error(array('message' => esc_html__('That entry no longer exists.', 'hash-form')));
        }

        $form = HashFormBuilder::get_form_vars($entry->form_id);

        if (!$form) {
            wp_send_json_error(array('message' => esc_html__('The form for this entry no longer exists.', 'hash-form')));
        }

        // Resending must only send the mail. Without this the post submission
        // actions would run again, which for a payment form means dispatching
        // a second charge.
        HashFormEmail::$sending_deferred = true;

        $send_mail = new HashFormEmail($form, $id, '');
        $sent = $send_mail->send_email();

        HashFormEmail::$sending_deferred = false;

        $wpdb->update($wpdb->prefix . 'hashform_entries', array('delivery_status' => $sent ? 1 : 0), array('id' => $id));

        if (!$sent) {
            wp_send_json_error(array('message' => esc_html__('The notification could not be sent. Check your email settings.', 'hash-form')));
        }

        wp_send_json_success(array('message' => esc_html__('Notification sent.', 'hash-form')));
    }

    private static function entry_exists($id) {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}hashform_entries WHERE id = %d", absint($id)));
    }

    public function add_menu() {
        global $hash_entry_listing_page;
        $hash_entry_listing_page = add_submenu_page('hashform', esc_html__('Entries', 'hash-form'), esc_html__('Entries', 'hash-form'), 'manage_options', 'hashform-entries', array($this, 'route'));
        add_action("load-$hash_entry_listing_page", array($this, 'listing_page_screen_options'));
    }

    protected static function list_config() {
        return array(
            'page' => 'hashform-entries',
            'table' => 'hashform_entries',
            'id_key' => 'entry_id',
            'nonce_item' => 'entry',
            'bulk_nonce' => 'bulk-entries',
            'actions' => array('view', 'destroy', 'untrash', 'trash', 'delete_all'),
        );
    }

    protected static function destroy_item($id) {
        return self::destroy_entry($id);
    }

    protected static function render_list($message = '', $class = 'updated') {
        self::display_entry_list($message, $class);
    }

    protected static function message_trashed($count, $undo_open, $undo_close) {
        /* translators: 1: entry count singular & plural, 2: link open, 3: link close */
        return sprintf(_n('%1$s entry moved to the Trash. %2$sUndo%3$s', '%1$s entries moved to the Trash. %2$sUndo%3$s', $count, 'hash-form'), $count, $undo_open, $undo_close);
    }

    protected static function message_untrashed($count) {
        /* translators: 1: entry count singular & plural */
        return sprintf(_n('%1$s entry restored from the Trash.', '%1$s entries restored from the Trash.', $count, 'hash-form'), $count);
    }

    protected static function message_destroyed($count) {
        /* translators: 1: entry count singular & plural */
        return sprintf(_n('%1$s Entry Permanently Deleted', '%1$s Entries Permanently Deleted', $count, 'hash-form'), $count);
    }

    protected static function message_deleted($count) {
        /* translators: 1: entry count singular & plural */
        return sprintf(_n('%1$s entry permanently deleted.', '%1$s entries permanently deleted.', $count, 'hash-form'), $count);
    }

    protected static function message_none_specified() {
        return esc_html__('No Entries were specified', 'hash-form');
    }

    public static function view($id = 0) {
        if (!$id) {
            $id = HashFormHelper::get_var('id', 'absint');
        }
        $entry = self::get_entry_vars($id);

        if (!$entry) {
            ?>
            <div id="message" class="error notice is-dismissible">
                <p><?php esc_html_e('You are trying to view an entry that does not exist.', 'hash-form'); ?></p>
            </div>
            <?php
            return;
        }

        // Opening an entry is what marks it read.
        if (empty($entry->is_read)) {
            self::mark_read($id);
            $entry->is_read = 1;
        }

        include(HASHFORM_PATH . 'admin/entries/entry-detail.php');
    }

    public static function display_entry_list($message = '', $class = 'updated') {
        ?>
        <div class="hf-content hf-list-screen">

            <div class="hf-list-header">
                <div class="hf-list-header-inner">
                    <h2 class="hf-list-title"><?php esc_html_e('Entries', 'hash-form'); ?></h2>
                </div>
            </div>

            <div class="hf-entry-list-wrap wrap">
                <h1></h1>

                <div id="hf-entry-list">
                    <?php
                    self::display_message($message, $class);
                    $entry_table = new HashFormEntryListing();
                    $entry_status = HashFormHelper::get_var('status', 'sanitize_title', 'published');
                    $entry_table->prepare_items();
                    ?>
                    <form id="posts-filter" method="get">
                        <input type="hidden" name="page" value="<?php echo esc_attr(HashFormHelper::get_var('page', 'sanitize_title')); ?>" />
                        <input type="hidden" name="status" value="<?php echo esc_attr($entry_status); ?>" />

                        <div class="hf-list-toolbar">
                            <?php
                            $entry_table->views();
                            $entry_table->search_box(esc_html__('Search', 'hash-form'), 'search');
                            ?>
                        </div>

                        <?php $entry_table->display(); ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public function listing_page_screen_options() {

        global $hash_entry_listing_page;

        $screen = get_current_screen();
        $hashform_action = HashFormHelper::get_var('hashform_action');

        // get out of here if we are not on our settings page
        if (!is_object($screen) || $screen->id != $hash_entry_listing_page || ($hashform_action == 'view'))
            return;

        $args = array(
            'label' => esc_html__('Entries per page', 'hash-form'),
            'default' => 10,
            'option' => 'entries_per_page'
        );

        add_screen_option('per_page', $args);

        //new HashFormEntryListing();
    }

    public function set_screen_option($status, $option, $value) {
        return ('entries_per_page' === $option) ? $value : $status;
    }

    public static function destroy_entry($id) {
        global $wpdb;
        $entry = self::get_entry_vars($id); // Item meta is required for conditional logic in actions with 'delete' events.
        if (!$entry) {
            return false;
        }

        $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->prefix . 'hashform_entry_meta WHERE item_id=%d', $id));
        $result = $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->prefix . 'hashform_entries WHERE id=%d', $id));
        return $result;
    }

    public static function get_entry_vars($id) {
        global $wpdb;
        $entry = $wpdb->get_row($wpdb->prepare("SELECT e.*, f.name AS form_name, f.form_key AS form_key
        FROM {$wpdb->prefix}hashform_entries AS e
        LEFT OUTER JOIN {$wpdb->prefix}hashform_forms AS f ON e.form_id = f.id
        WHERE e.id = %d", $id));
        $entry = self::get_meta($entry);
        return $entry;
    }

    public static function get_meta($entry) {
        if (!$entry) {
            return $entry;
        }

        global $wpdb;
        $metas = $wpdb->get_results($wpdb->prepare("SELECT m.*, f.type AS field_type, f.field_key, f.name, f.field_options FROM {$wpdb->prefix}hashform_entry_meta AS m LEFT JOIN {$wpdb->prefix}hashform_fields AS f ON m.field_id = f.id WHERE m.item_id = %d AND m.field_id != %d ORDER BY m.id ASC", $entry->id, 0));
        $entry->metas = array();

        foreach ($metas as $meta_val) {
            $entry->metas[$meta_val->field_id] = array(
                'name' => $meta_val->name,
                'value' => $meta_val->meta_value,
                'type' => $meta_val->field_type,
                // Carried through so a value can be rendered the way its field
                // was configured, rather than every display path re-querying.
                'options' => maybe_unserialize($meta_val->field_options)
            );
        }

        return $entry;
    }

    public function process_entry() {
        global $wpdb;
        parse_str(htmlspecialchars_decode(HashFormHelper::get_post('data', 'esc_html')), $data);
        $location = esc_url(HashFormHelper::get_post('location', 'esc_html'));

        if (empty($data) || empty($data['form_id']) || !isset($data['form_key'])) {
            return;
        }

        do_action('hash_form_before_submit', $data);

        $form_id = $data['form_id'];
        $form = HashFormBuilder::get_form_vars($form_id);

        if (!$form) {
            return;
        }

        // Checked again here: the form may have closed, filled up or already
        // been submitted since the page was loaded.
        $restriction = HashFormRestrictions::check($form);

        if (empty($restriction['allowed'])) {
            return wp_send_json(array(
                'status' => 'failed',
                'message' => esc_html($restriction['message'])
            ));
        }

        $errors = '';
        $errors = HashFormValidate::validate(wp_unslash($data));

        if (empty($errors)) {
            $form_settings = $form->settings;
            $entry_id = self::create($data);

            $send_mail = new HashFormEmail($form, $entry_id, $location);
            $check_mail = $send_mail->send_email();

            if (!$check_mail) {
                $wpdb->update($wpdb->prefix . 'hashform_entries', array('delivery_status' => 0), array('id' => $entry_id));
                return wp_send_json(array(
                    'status' => 'failed',
                    'message' => esc_html(apply_filters('hashform_translate_string', $form_settings['error_message'], 'Hash Form', $form->name . ' - ' . 'Error Message'))
                ));
            }
        }

        return wp_send_json(array(
            'status' => 'error',
            'message' => $errors
        ));
    }

    public static function create($values) {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $user_id = $current_user_id ? $current_user_id : 0;
        $new_values = array(
            'ip' => sanitize_text_field(HashFormHelper::get_ip()),
            'delivery_status' => 1,
            'form_id' => isset($values['form_id']) ? absint($values['form_id']) : '',
            'created_at' => sanitize_text_field(current_time('mysql')),
            'user_id' => absint($user_id),
            'status' => 'published'
        );

        $query_results = $wpdb->insert($wpdb->prefix . 'hashform_entries', $new_values);
        if (!$query_results) {
            return false;
        } else {
            $entry_id = $wpdb->insert_id;
        }

        if (isset($values['item_meta'])) {
            foreach ($values['item_meta'] as $field_id => $meta_value) {
                if (!empty($meta_value)) {
                    if (!is_array($meta_value)) {
                        $meta_value = sanitize_textarea_field($meta_value);
                    }

                    $meta_values = array(
                        'meta_value' => $meta_value,
                        'item_id' => absint($entry_id),
                        'field_id' => absint($field_id),
                        'created_at' => sanitize_text_field(current_time('mysql')),
                    );

                    /*
                     * The field gets the value in the shape it was posted. It
                     * used to be serialized first, so a field that posts more
                     * than one value — a repeater's rows, an address, a set of
                     * boxes — only ever saw a string and could not put its own
                     * shape on what got stored.
                     */
                    self::sanitize_meta_value($meta_values);

                    if (is_array($meta_values['meta_value'])) {
                        $meta_values['meta_value'] = serialize($meta_values['meta_value']);
                    }

                    $query_results = $wpdb->insert($wpdb->prefix . 'hashform_entry_meta', $meta_values);
                }
            }
        }
        return $entry_id;
    }

    private static function sanitize_meta_value(&$values) {
        $field = HashFormFields::get_field_vars($values['field_id']);
        if ($field) {
            $field_obj = HashFormFields::get_field_object($field);
            $values['meta_value'] = $field_obj->set_value_before_save($values['meta_value']);
            $values['meta_value'] = $field_obj->sanitize_value($values['meta_value']);
        }
    }

    public static function get_count() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT status, COUNT(*) AS count FROM {$wpdb->prefix}hashform_entries GROUP BY status");
        $counts = array('published' => 0, 'trash' => 0, 'unread' => 0, 'starred' => 0);
        foreach ($results as $row) {
            if ('published' == $row->status) {
                $counts['published'] += $row->count;
            } else {
                $counts['trash'] += $row->count;
            }
        }

        $counts['unread'] = self::get_unread_count();
        $counts['starred'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hashform_entries WHERE status='published' AND is_starred = 1");

        return $counts;
    }

    public static function get_entry_count($form_id) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}hashform_entries WHERE form_id=%d AND status='published'", $form_id));
        return $count;
    }

    // Published-entry counts for every form in one query, keyed by form id.
    public static function get_entry_counts() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT form_id, COUNT(*) AS count FROM {$wpdb->prefix}hashform_entries WHERE status='published' GROUP BY form_id");
        $counts = array();
        foreach ($results as $row) {
            $counts[$row->form_id] = (int) $row->count;
        }
        return $counts;
    }

    public static function get_prev_entry($entry_id) {
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$wpdb->prefix}hashform_entries WHERE id < %d AND status='published' ORDER BY id DESC LIMIT 1", $entry_id));
        return $results;
    }

    public static function get_next_entry($entry_id) {
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$wpdb->prefix}hashform_entries WHERE id > %d AND status='published' ORDER BY id ASC LIMIT 1", $entry_id));
        return $results;
    }

    public static function get_entry_date($entry_id) {
        global $wpdb;
        $results = $wpdb->get_var($wpdb->prepare("SELECT created_at FROM {$wpdb->prefix}hashform_entries WHERE id = %d", $entry_id));
        return HashFormHelper::convert_date_format($results);
    }

}

new HashFormEntry();
