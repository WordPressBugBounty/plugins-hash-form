<?php

defined('ABSPATH') || die();

/**
 * Daily maintenance event. Add-ons hook `hashform_maintenance` rather than scheduling their own.
 *
 * Destructive tasks are opt-in; only reversible work runs by default.
 */
class HashFormCron {

    const HOOK = 'hashform_daily_maintenance';

    /** Never remove more than this in one run, so a huge backlog is paced. */
    const BATCH = 500;

    public function __construct() {
        add_action('init', array(__CLASS__, 'ensure_scheduled'));
        add_action(self::HOOK, array(__CLASS__, 'run'));
    }

    /* ---------------------------------------------------------------------
     * Scheduling
     * ------------------------------------------------------------------- */

    /**
     * Schedule the event if missing. Checked on every load because plugin updates skip the activation hook.
     */
    public static function ensure_scheduled() {
        if (wp_next_scheduled(self::HOOK)) {
            return;
        }

        /**
         * How often maintenance runs.
         *
         * @param string $recurrence A registered cron schedule.
         */
        $recurrence = apply_filters('hashform_maintenance_recurrence', 'daily');

        if (!array_key_exists($recurrence, (array) wp_get_schedules())) {
            $recurrence = 'daily';
        }

        wp_schedule_event(self::first_run(), $recurrence, self::HOOK);
    }

    /**
     * First run time: a quiet hour in the site's timezone.
     *
     * @return int
     */
    private static function first_run() {
        $offset = (int) (get_option('gmt_offset') * HOUR_IN_SECONDS);
        $local_now = time() + $offset;
        $next_local = strtotime('tomorrow 03:00', $local_now);

        return $next_local - $offset;
    }

    /**
     * Remove the event. Called on deactivation and from uninstall.
     */
    public static function unschedule() {
        wp_clear_scheduled_hook(self::HOOK);
    }

    /**
     * What is scheduled and which tasks are enabled, for site health and support.
     *
     * @return array
     */
    public static function status() {
        return array(
            'hook' => self::HOOK,
            'next_run' => wp_next_scheduled(self::HOOK),
            'purge_orphaned_meta' => self::purging_orphaned_meta(),
        );
    }

    /* ---------------------------------------------------------------------
     * The run
     * ------------------------------------------------------------------- */

    /**
     * @return array What each task did, for the log and for tests.
     */
    public static function run() {
        $report = array(
            'orphaned_meta' => self::purge_orphaned_meta(),
        );

        /**
         * Daily maintenance. Runs after the free plugin's own housekeeping.
         */
        do_action('hashform_maintenance', $report);

        return $report;
    }

    /* ---------------------------------------------------------------------
     * Tasks
     * ------------------------------------------------------------------- */

    private static function purging_orphaned_meta() {
        /**
         * Whether to delete answers whose entry no longer exists. Off by default.
         *
         * @param bool $enabled
         */
        return (bool) apply_filters('hashform_purge_orphaned_meta', false);
    }

    /**
     * Delete answers whose entry was removed without going through destroy_entry().
     *
     * @return int Rows removed.
     */
    public static function purge_orphaned_meta() {
        global $wpdb;

        if (!self::purging_orphaned_meta()) {
            return 0;
        }

        $meta = $wpdb->prefix . 'hashform_entry_meta';
        $entries = $wpdb->prefix . 'hashform_entries';

        // Select ids first, then delete: MySQL rejects LIMIT on a multi-table DELETE.
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $meta and $entries are $wpdb->prefix plus table name literals set above; the batch size is bound.
        $ids = $wpdb->get_col($wpdb->prepare(
                        "SELECT m.id FROM {$meta} AS m
                         LEFT JOIN {$entries} AS e ON e.id = m.item_id
                         WHERE e.id IS NULL
                         LIMIT %d", self::BATCH));
        // phpcs:enable

        if (empty($ids)) {
            return 0;
        }

        $in = implode(',', array_map('absint', $ids));

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $meta is a table name literal and $in is the id list mapped through absint() on the line above.
        $removed = (int) $wpdb->query("DELETE FROM {$meta} WHERE id IN ({$in})");

        if ($removed) {
            self::log(sprintf('removed %d orphaned answer row(s)', $removed));
        }

        return $removed;
    }

    private static function log($message) {
        if (class_exists('HashFormHelper') && method_exists('HashFormHelper', 'log')) {
            HashFormHelper::log($message, 'hash-form/maintenance');
        }
    }

}

new HashFormCron();
