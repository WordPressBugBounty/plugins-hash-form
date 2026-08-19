<?php

defined('ABSPATH') || die();

/**
 * Shared list-screen behaviour for the Forms and Entries admin pages.
 *
 * Both screens route the same query arguments through the same trash, untrash,
 * delete and bulk flows; only the table, the request keys and the wording
 * differ. Holding the flow in one place is what keeps the nonce checks on the
 * two screens from drifting apart, which is how they came to be missing on the
 * bulk paths in the first place.
 *
 * Message text stays in the using class: every gettext call must keep a literal
 * string so the translation scanner can find it.
 */
trait HashFormListActions {

    /**
     * Per-screen settings.
     *
     * @return array {
     *     @type string   $page       Admin page slug, e.g. 'hashform'.
     *     @type string   $table      Table name without the $wpdb prefix.
     *     @type string   $id_key     Request key holding the bulk ids, e.g. 'form_id'.
     *     @type string   $nonce_item Infix used by single item nonces, e.g. 'form'.
     *     @type string   $bulk_nonce Nonce action guarding the list table form.
     *     @type string[] $actions    Single item actions this screen dispatches by name.
     * }
     */
    abstract protected static function list_config();

    /** Permanently remove one item and everything hanging off it. */
    abstract protected static function destroy_item($id);

    /** Render the screen, optionally with a notice. */
    abstract protected static function render_list($message = '', $class = 'updated');

    abstract protected static function message_trashed($count, $undo_open, $undo_close);

    abstract protected static function message_untrashed($count);

    /** Notice for a single permanent deletion. */
    abstract protected static function message_destroyed($count);

    /** Notice for bulk and empty-trash permanent deletions. */
    abstract protected static function message_deleted($count);

    abstract protected static function message_none_specified();

    public static function route() {
        $config = static::list_config();

        /* Gets hashform_action value else action value */
        $action = htmlspecialchars_decode(HashFormHelper::get_var('hashform_action', 'sanitize_text_field', HashFormHelper::get_var('action')));

        // The bulk dropdown below the table posts as action2, and core sends -1
        // for whichever of the two the user did not submit.
        if ('' === $action || '-1' === $action) {
            $action = htmlspecialchars_decode(HashFormHelper::get_var('action2', 'sanitize_text_field'));
        }

        if (HashFormHelper::get_var('delete_all')) {
            $action = 'delete_all';
        }

        // $action reaches a method name below, so only the values this screen
        // declares may be dispatched.
        if (in_array($action, $config['actions'], true)) {
            return static::$action();
        }

        if (strpos($action, 'bulk_') === 0) {
            static::bulk_actions();
            return;
        }

        static::render_list();
    }

    public static function display_message($message, $class) {
        if ('' !== trim($message)) {
            echo '<div id="message" class="' . esc_attr($class) . ' notice is-dismissible">';
            echo '<p>' . wp_kses_post($message) . '</p>';
            echo '</div>';
        }
    }

    public static function trash() {
        self::change_form_status('trash');
    }

    public static function untrash() {
        self::change_form_status('untrash');
    }

    public static function change_form_status($status) {
        $available_status = array(
            'untrash' => array('new_status' => 'published'),
            'trash' => array('new_status' => 'trash'),
        );

        if (!isset($available_status[$status])) {
            return;
        }

        $config = static::list_config();
        $id = HashFormHelper::get_var('id', 'absint');

        check_admin_referer($status . '_' . $config['nonce_item'] . '_' . $id);

        $count = 0;
        if (static::set_status($id, $available_status[$status]['new_status'])) {
            $count++;
        }

        if ('untrash' === $status) {
            $message = static::message_untrashed($count);
        } else {
            $undo_url = wp_nonce_url(
                '?page=' . $config['page'] . '&hashform_action=untrash&id=' . absint($id),
                'untrash_' . $config['nonce_item'] . '_' . absint($id)
            );
            $message = static::message_trashed($count, '<a href="' . esc_url($undo_url) . '">', '</a>');
        }

        static::render_list($message);
    }

    public static function set_status($id, $status) {
        $statuses = array('published', 'trash');
        if (!in_array($status, $statuses)) {
            return false;
        }

        $id = is_array($id) ? $id : array($id);

        // An empty list would build "IN ()" and error out.
        if (!$id) {
            return false;
        }

        global $wpdb;

        $config = static::list_config();
        $table = $wpdb->prefix . $config['table'];
        $placeholders = implode(',', array_fill(0, count($id), '%d'));
        $prepare_args = array_merge(array($status), $id);

        return $wpdb->query($wpdb->prepare("UPDATE {$table} SET status=%s WHERE id IN ({$placeholders})", $prepare_args));
    }

    public static function delete_all() {
        $config = static::list_config();

        // The "Empty Trash" button submits inside the list table form, which
        // carries the bulk nonce.
        check_admin_referer($config['bulk_nonce']);

        $count = static::delete();
        static::render_list(static::message_deleted($count));
    }

    public static function delete() {
        global $wpdb;

        $config = static::list_config();
        $table = $wpdb->prefix . $config['table'];
        $trashed = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$table} WHERE status=%s", 'trash'));

        if (!$trashed) {
            return 0;
        }

        $count = 0;
        foreach ($trashed as $id) {
            static::destroy_item($id);
            $count++;
        }

        return $count;
    }

    public static function destroy() {
        $config = static::list_config();
        $id = HashFormHelper::get_var('id', 'absint');

        check_admin_referer('destroy_' . $config['nonce_item'] . '_' . $id);

        $count = 0;
        if (static::destroy_item($id)) {
            $count++;
        }

        static::render_list(static::message_destroyed($count));
    }

    public static function bulk_actions() {
        $message = static::process_bulk_actions();
        static::render_list($message);
    }

    public static function process_bulk_actions() {
        if (!$_REQUEST) {
            return;
        }

        $config = static::list_config();

        check_admin_referer($config['bulk_nonce']);

        $bulkaction = HashFormHelper::get_var('action', 'sanitize_text_field');

        if ($bulkaction == -1) {
            $bulkaction = HashFormHelper::get_var('action2', 'sanitize_title');
        }

        if (!empty($bulkaction) && strpos($bulkaction, 'bulk_') === 0) {
            $bulkaction = str_replace('bulk_', '', $bulkaction);
        }

        $ids = HashFormHelper::get_var($config['id_key'], 'sanitize_text_field');

        if (empty($ids)) {
            return static::message_none_specified();
        }

        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }

        $ids = array_filter(array_map('absint', $ids));

        if (!$ids) {
            return static::message_none_specified();
        }

        $message = '';

        switch ($bulkaction) {
            case 'delete':
                $message = static::bulk_destroy($ids);
                break;
            case 'trash':
                $message = static::bulk_trash($ids);
                break;
            case 'untrash':
                $message = static::bulk_untrash($ids);
        }

        if (!empty($message)) {
            return $message;
        }
    }

    public static function bulk_trash($ids) {
        $count = static::set_status($ids, 'trash');
        if (!$count) {
            return '';
        }

        $config = static::list_config();
        $undo_url = wp_nonce_url(
            '?page=' . $config['page'] . '&action=bulk_untrash&status=published&' . $config['id_key'] . '=' . implode(',', $ids),
            $config['bulk_nonce']
        );

        return static::message_trashed($count, '<a href="' . esc_url($undo_url) . '">', '</a>');
    }

    public static function bulk_untrash($ids) {
        $count = static::set_status($ids, 'published');
        if (!$count) {
            return '';
        }

        return static::message_untrashed($count);
    }

    public static function bulk_destroy($ids) {
        $count = 0;
        foreach ($ids as $id) {
            if (static::destroy_item($id)) {
                $count++;
            }
        }

        return static::message_deleted($count);
    }

}
