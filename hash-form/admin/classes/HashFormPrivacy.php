<?php

defined('ABSPATH') || die();

/**
 * Hooks form entries into WordPress' own privacy tools.
 *
 * Entries hold exactly the kind of thing a subject access or erasure request
 * is about - names, email addresses, whatever else the form asked for, plus
 * the submitter's ip address - and none of it was reachable from Tools >
 * Export Personal Data or Erase Personal Data. An administrator answering a
 * request had to go through the Entries screen by hand and hope they had
 * found every form.
 *
 * Entries are matched on the email address the request names: the submitter's
 * account email when they were signed in, or any email field on the form.
 * Both exporters and erasers page through the data, as core requires, so a
 * form with a large number of entries does not exhaust memory.
 */
class HashFormPrivacy {

    /** Entries handled per batch. Core calls back until done is true. */
    const PER_PAGE = 50;

    public function __construct() {
        add_filter('wp_privacy_personal_data_exporters', array($this, 'register_exporter'));
        add_filter('wp_privacy_personal_data_erasers', array($this, 'register_eraser'));
        /*
         * wp_add_privacy_policy_content() rather than the
         * wp_get_default_privacy_policy_content filter, which has been
         * deprecated since WordPress 5.7 and emits a notice. It must run on
         * admin_init, which is where core collects the suggested text.
         */
        add_action('admin_init', array($this, 'add_privacy_policy_content'));
    }

    public function register_exporter($exporters) {
        $exporters['hash-form'] = array(
            'exporter_friendly_name' => esc_html__('Hash Form entries', 'hash-form'),
            'callback' => array($this, 'export'),
        );

        return $exporters;
    }

    public function register_eraser($erasers) {
        $erasers['hash-form'] = array(
            'eraser_friendly_name' => esc_html__('Hash Form entries', 'hash-form'),
            'callback' => array($this, 'erase'),
        );

        return $erasers;
    }

    /**
     * Entry ids belonging to an email address, one page at a time.
     *
     * @param string $email
     * @param int    $page 1-based, as core numbers them.
     * @return int[]
     */
    private function find_entry_ids($email, $page) {
        global $wpdb;

        $offset = (max(1, (int) $page) - 1) * self::PER_PAGE;
        $user = get_user_by('email', $email);
        $user_id = $user ? (int) $user->ID : 0;

        /*
         * Two ways an entry can belong to someone: it was submitted while
         * they were signed in, or one of the form's email fields holds their
         * address. The union covers both without returning an entry twice.
         */
        $sql = "SELECT DISTINCT e.id
                FROM {$wpdb->prefix}hashform_entries AS e
                LEFT JOIN {$wpdb->prefix}hashform_entry_meta AS m ON m.item_id = e.id
                LEFT JOIN {$wpdb->prefix}hashform_fields AS f ON f.id = m.field_id
                WHERE (f.type = 'email' AND m.meta_value = %s)";

        $args = array($email);

        if ($user_id) {
            $sql .= ' OR e.user_id = %d';
            $args[] = $user_id;
        }

        $sql .= ' ORDER BY e.id ASC LIMIT %d OFFSET %d';
        $args[] = self::PER_PAGE;
        $args[] = $offset;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $sql is assembled just above from literals and %s/%d placeholders only; every value is bound through $args.
        return array_map('absint', $wpdb->get_col($wpdb->prepare($sql, $args)));
    }

    /**
     * Everything stored against this person's entries, in core's export shape.
     */
    public function export($email, $page = 1) {
        $entry_ids = $this->find_entry_ids($email, $page);
        $export_items = array();

        foreach ($entry_ids as $entry_id) {
            $entry = HashFormEntry::get_entry_vars($entry_id);

            if (!$entry) {
                continue;
            }

            $data = array(
                array(
                    'name' => esc_html__('Entry ID', 'hash-form'),
                    'value' => $entry->id,
                ),
                array(
                    'name' => esc_html__('Form', 'hash-form'),
                    'value' => $entry->form_name,
                ),
                array(
                    'name' => esc_html__('Submitted', 'hash-form'),
                    'value' => $entry->created_at,
                ),
            );

            if (!empty($entry->ip)) {
                $data[] = array(
                    'name' => esc_html__('IP address', 'hash-form'),
                    'value' => $entry->ip,
                );
            }

            foreach ((array) $entry->metas as $meta) {
                $value = HashFormHelper::unserialize_or_decode($meta['value']);

                if (is_array($value)) {
                    $value = implode(', ', array_filter(array_map('strval', $value)));
                }

                $data[] = array(
                    'name' => $meta['name'],
                    'value' => $value,
                );
            }

            $export_items[] = array(
                'group_id' => 'hashform-entries',
                'group_label' => esc_html__('Form entries', 'hash-form'),
                'group_description' => esc_html__('Entries submitted through Hash Form.', 'hash-form'),
                'item_id' => 'hashform-entry-' . $entry->id,
                'data' => $data,
            );
        }

        return array(
            'data' => $export_items,
            // Fewer than a full page means this was the last one.
            'done' => count($entry_ids) < self::PER_PAGE,
        );
    }

    /**
     * Delete this person's entries, uploaded files included.
     */
    public function erase($email, $page = 1) {
        $entry_ids = $this->find_entry_ids($email, $page);
        $removed = 0;
        $messages = array();

        foreach ($entry_ids as $entry_id) {
            // Takes the entry's meta rows and its uploaded files with it.
            if (HashFormEntry::destroy_entry($entry_id)) {
                $removed++;
            } else {
                /* translators: %d: entry id. */
                $messages[] = sprintf(esc_html__('Entry %d could not be removed.', 'hash-form'), $entry_id);
            }
        }

        return array(
            'items_removed' => $removed,
            'items_retained' => false,
            'messages' => $messages,
            'done' => count($entry_ids) < self::PER_PAGE,
        );
    }

    /**
     * Suggested wording for the site's privacy policy.
     *
     * Registered through wp_add_privacy_policy_content(), which is what
     * replaced the filter this used to hook.
     */
    public function add_privacy_policy_content() {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }

        $content = '<p>' . esc_html__('When you submit a form on this site, the answers you give are stored so we can respond to you. Depending on the form, this may include your name, email address, and anything else the form asks for.', 'hash-form') . '</p>'
                . '<p>' . esc_html__('We also record the date of your submission and the IP address it came from, which helps us detect automated abuse.', 'hash-form') . '</p>'
                . '<p>' . esc_html__('You can ask us for a copy of the form submissions we hold about you, or ask us to delete them.', 'hash-form') . '</p>';

        wp_add_privacy_policy_content(esc_html__('Hash Form', 'hash-form'), wp_kses_post($content));
    }

}

new HashFormPrivacy();
