<?php

defined('ABSPATH') || die();

/**
 * The plugin's one scheduled event.
 *
 * Nothing was ever scheduled before this: there is no wp_schedule_event
 * anywhere in either plugin. That is why abandoned checkouts sat pending
 * forever, and why nothing that accumulates - analytics rows, generated pdfs,
 * uploads whose entry is long gone - was ever cleared up.
 *
 * One daily event rather than one per job, and add-ons hook it. Pro attaches
 * its own housekeeping to the same hook, so a site has a single cron entry to
 * see, disable or reschedule.
 *
 * Everything that DELETES is off unless the site turns it on. A plugin update
 * that quietly starts removing data on a timer is worse than the mess it
 * cleans up, so the default is to do nothing destructive and let the owner
 * decide. What runs unasked is limited to work that can be undone.
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
     * Make sure the event exists.
     *
     * Checked on every load rather than only on activation: a site that
     * updates the plugin without deactivating it never runs the activation
     * hook, and would otherwise never get the event at all.
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
     * The first run, at a quiet hour in the site's own timezone rather than
     * whenever the plugin happened to be activated.
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
     * What is scheduled and what is turned on, for the site health screen,
     * for support, and for tests.
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
         * Daily maintenance.
         *
         * Pro hooks this for payments, analytics and generated files. Runs
         * after the free plugin's own housekeeping.
         */
        do_action('hashform_maintenance', $report);

        return $report;
    }

    /* ---------------------------------------------------------------------
     * Tasks
     * ------------------------------------------------------------------- */

    private static function purging_orphaned_meta() {
        /**
         * Whether to delete answers whose entry no longer exists.
         *
         * Off by default. These rows are unreachable - nothing can display an
         * answer with no entry behind it - but they are still somebody's data,
         * and a site should choose to remove them rather than find out
         * afterwards.
         *
         * @param bool $enabled
         */
        return (bool) apply_filters('hashform_purge_orphaned_meta', false);
    }

    /**
     * Answers left behind by an entry that was deleted without going through
     * destroy_entry() - a manual database edit, or a crash part way through.
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

        /*
         * Found first, then deleted by id. MySQL refuses LIMIT on a
         * multi-table DELETE, so the join and the batch cap cannot be
         * expressed in one statement - and a DELETE that fails on syntax
         * returns false, which reads as "nothing to do" rather than as an
         * error. Two statements, both valid, and the cap survives.
         */
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
