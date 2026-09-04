<?php

defined('ABSPATH') || die();

/**
 * Ordered, resumable schema migrations.
 *
 * Before this existed, each schema change carried its own boolean option -
 * hashform_entries_read_migrated, hashform_options_noautoload,
 * hashform_transactions_has_status and so on - so there was no order between
 * them, no record of a partial run, and no single answer to "what shape is
 * this database in".
 *
 * A step here is named, ordered, and idempotent. Each one is recorded the
 * moment it succeeds, so a run interrupted half way through - a timeout on a
 * large table is the usual way - resumes at the step it stopped on rather
 * than starting again.
 *
 * This does not replace HashFormCreateTable. dbDelta still owns creating
 * tables and adding columns; this owns everything dbDelta cannot express or
 * cannot be trusted with, which in practice means indexes and column types.
 *
 * The existing per-change flags are deliberately left where they are. They
 * are idempotent and the code that reads them still works; rewriting that to
 * be tidier would risk a migration re-running on a live site for no gain.
 */
class HashFormMigrations {

    /**
     * Bump when a step is added. Steps themselves are keyed by name, so the
     * number is a fast "is there anything to do" check, not the source of
     * truth about what has run.
     */
    const VERSION = 2;

    const OPTION = 'hashform_schema_version';
    const PROGRESS = 'hashform_schema_progress';
    const LOCK = 'hashform_schema_lock';

    /**
     * How long a run may hold the lock. Long enough for an ALTER on a large
     * table, short enough that a fatal does not wedge migrations forever.
     */
    const LOCK_TTL = 10 * MINUTE_IN_SECONDS;

    public function __construct() {
        /*
         * Schema changes belong to an administrator's request, WP-CLI or
         * cron - never to a visitor loading a page with a form on it. A
         * front-end request must not pay for an ALTER, and must not be the
         * thing that gets killed half way through one.
         */
        add_action('admin_init', array(__CLASS__, 'maybe_migrate'));

        if (defined('WP_CLI') && WP_CLI) {
            add_action('init', array(__CLASS__, 'maybe_migrate'));
        }
    }

    /**
     * Every step, in the order they must run.
     *
     * Steps are named rather than handed over as callables: they are internal
     * to this class, and a callable pointing at a private method only works
     * because of where it happens to be invoked from. Naming them keeps the
     * dispatch explicit and the methods properly private.
     *
     * @return array key => method name on this class
     */
    public static function steps() {
        return array(
            // The entries list orders by created_at within a status. Without
            // this every view of the screen scans the table.
            'entries_status_created' => 'step_entries_status_created',

            // Filtering to one form, still ordered by date.
            'entries_form_status_created' => 'step_entries_form_status_created',

            // The unread and starred views.
            'entries_status_read' => 'step_entries_status_read',
            'entries_status_starred' => 'step_entries_status_starred',

            // Every read of an answer looks up an entry and a field together,
            // but the table only had them indexed separately.
            'entry_meta_item_field' => 'step_entry_meta_item_field',

            // The forms dashboard filters by status.
            'forms_status' => 'step_forms_status',

            // An IP is at most 45 characters; storing it as text means it
            // cannot be indexed and costs an off-page read to compare.
            'entries_ip_varchar' => 'step_entries_ip_varchar',

            // A scheduled event left behind by an older version, which no
            // code has answered for some time.
            'clear_stale_payment_cron' => 'step_clear_stale_payment_cron',
        );
    }

    /* ---------------------------------------------------------------------
     * Running
     * ------------------------------------------------------------------- */

    /**
     * Run anything outstanding. Cheap and safe to call on every admin page.
     *
     * @return bool Whether any step ran.
     */
    public static function maybe_migrate() {
        if ((int) get_option(self::OPTION) === self::VERSION) {
            return false;
        }

        /*
         * Two admins loading wp-admin at the same moment would otherwise both
         * start migrating. The steps are idempotent, so the worst case is
         * wasted work rather than damage, but an ALTER running twice on a
         * large table is worth avoiding.
         */
        if (get_transient(self::LOCK)) {
            return false;
        }

        set_transient(self::LOCK, 1, self::LOCK_TTL);

        try {
            $ran = self::run();
        } catch (Exception $e) {
            self::log('migration aborted: ' . $e->getMessage());
            $ran = false;
        }

        delete_transient(self::LOCK);

        return $ran;
    }

    /**
     * Run every step not already recorded as done.
     *
     * A step that fails stops the run without recording itself, so the next
     * request picks up from there rather than skipping past a change the rest
     * may depend on.
     *
     * @return bool Whether every outstanding step completed.
     */
    public static function run() {
        $done = self::completed();
        $all_ok = true;

        foreach (self::steps() as $key => $method) {
            if (in_array($key, $done, true)) {
                continue;
            }

            if (!method_exists(__CLASS__, $method)) {
                self::log('step names a method that does not exist: ' . $key);
                $all_ok = false;
                break;
            }

            $result = self::$method();

            if (false === $result) {
                self::log('step failed, stopping: ' . $key);
                $all_ok = false;
                break;
            }

            // Recorded immediately. If the request dies on the next step,
            // this one is not repeated.
            $done[] = $key;
            update_option(self::PROGRESS, $done, false);
        }

        if ($all_ok) {
            update_option(self::OPTION, self::VERSION, true);
        }

        return $all_ok;
    }

    /**
     * Steps recorded as complete.
     *
     * @return array
     */
    public static function completed() {
        $done = get_option(self::PROGRESS, array());

        return is_array($done) ? $done : array();
    }

    /**
     * Steps still to run.
     *
     * @return array
     */
    public static function outstanding() {
        return array_values(array_diff(array_keys(self::steps()), self::completed()));
    }

    /* ---------------------------------------------------------------------
     * The steps
     * ------------------------------------------------------------------- */

    private static function step_entries_status_created() {
        return self::add_index('hashform_entries', 'hf_status_created', array('status(20)', 'created_at'));
    }

    private static function step_entries_form_status_created() {
        return self::add_index('hashform_entries', 'hf_form_status_created', array('form_id', 'status(20)', 'created_at'));
    }

    private static function step_entries_status_read() {
        return self::add_index('hashform_entries', 'hf_status_read', array('status(20)', 'is_read'));
    }

    private static function step_entries_status_starred() {
        return self::add_index('hashform_entries', 'hf_status_starred', array('status(20)', 'is_starred'));
    }

    private static function step_entry_meta_item_field() {
        return self::add_index('hashform_entry_meta', 'hf_item_field', array('item_id', 'field_id'));
    }

    private static function step_forms_status() {
        return self::add_index('hashform_forms', 'hf_status', array('status(20)'));
    }

    /**
     * Narrow entries.ip from text to varchar(45).
     *
     * Refuses rather than truncates: if anything stored is longer than an
     * IPv6 address can be, that is a sign the column is being used for
     * something else and silently cutting it would destroy data.
     */
    private static function step_entries_ip_varchar() {
        global $wpdb;

        $table = $wpdb->prefix . 'hashform_entries';
        $type = self::column_type('hashform_entries', 'ip');

        if (null === $type) {
            // No column, nothing to narrow. Not a failure.
            return true;
        }

        if (0 === stripos($type, 'varchar')) {
            return true;
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table is $wpdb->prefix plus a literal; the query carries no values.
        $too_long = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}` WHERE CHAR_LENGTH(ip) > 45");

        if ($too_long > 0) {
            self::log(sprintf('entries.ip left as %s: %d row(s) longer than 45 characters', $type, $too_long));

            // Deliberately a success: the database is in a shape this step
            // will not improve, and retrying forever helps nobody.
            return true;
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.SchemaChange -- $table is $wpdb->prefix plus a literal. A schema change is the whole point of a migration step.
        $wpdb->query("ALTER TABLE `{$table}` MODIFY `ip` VARCHAR(45) DEFAULT NULL");

        return 'varchar' === strtolower(substr((string) self::column_type('hashform_entries', 'ip'), 0, 7));
    }

    /**
     * Remove a cron event nothing listens for any more.
     *
     * hashform_pro_payments_maintenance is scheduled on sites that ran an
     * earlier build, but no code registers or handles it: it fires daily,
     * finds no callback, and reschedules itself forever. Clearing it is not a
     * schema change, but it is exactly the kind of one-off tidy-up that needs
     * to happen once, in order, and be recorded - which is what this is for.
     *
     * @return bool
     */
    private static function step_clear_stale_payment_cron() {
        $hook = 'hashform_pro_payments_maintenance';

        if (has_action($hook)) {
            // Something answers it after all; leave it alone.
            return true;
        }

        wp_clear_scheduled_hook($hook);

        return true;
    }

    /* ---------------------------------------------------------------------
     * Schema helpers
     * ------------------------------------------------------------------- */

    /**
     * Add an index if it is not already there.
     *
     * Identifiers cannot be passed through $wpdb->prepare, so every one is
     * checked against a strict pattern before it reaches a query. They are
     * all literals defined in this file, but a typo should fail loudly here
     * rather than become part of a statement.
     *
     * @param string $table   Unprefixed table name.
     * @param string $name    Index name.
     * @param array  $columns Column names, optionally with a "(20)" prefix length.
     * @return bool
     */
    public static function add_index($table, $name, array $columns) {
        global $wpdb;

        if (!self::table_exists($table)) {
            self::log('index skipped, no such table: ' . $table);

            return true;
        }

        if (!self::valid_identifier($name)) {
            self::log('refused an index name that is not an identifier: ' . $name);

            return false;
        }

        if (self::index_exists($table, $name)) {
            return true;
        }

        $parts = array();

        foreach ($columns as $column) {
            if (!preg_match('/^([A-Za-z0-9_]+)(?:\((\d+)\))?$/', $column, $m)) {
                self::log('refused a column definition that is not an identifier: ' . $column);

                return false;
            }

            $parts[] = isset($m[2]) ? '`' . $m[1] . '`(' . (int) $m[2] . ')' : '`' . $m[1] . '`';
        }

        $full = $wpdb->prefix . $table;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.SchemaChange -- $name passed valid_identifier(), and every entry in $parts was rebuilt from a matched [A-Za-z0-9_] group with a cast length. A schema change is the whole point of a migration step.
        $wpdb->query("ALTER TABLE `{$full}` ADD INDEX `{$name}` (" . implode(', ', $parts) . ')');

        if (!self::index_exists($table, $name)) {
            self::log('index was not created: ' . $table . '.' . $name . ' - ' . $wpdb->last_error);

            return false;
        }

        return true;
    }

    /**
     * @param string $table Unprefixed table name.
     * @param string $name
     * @return bool
     */
    public static function index_exists($table, $name) {
        global $wpdb;

        if (!self::valid_identifier($name) || !self::table_exists($table)) {
            return false;
        }

        $full = $wpdb->prefix . $table;

        // SHOW INDEX takes the table as an identifier but the key name as a
        // value, so that half is prepared.
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $full is $wpdb->prefix plus a table name already checked by table_exists().
        $found = $wpdb->get_results("SHOW INDEX FROM `{$full}`", ARRAY_A);

        foreach ((array) $found as $row) {
            if (isset($row['Key_name']) && $row['Key_name'] === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * The declared type of a column, or null when it does not exist.
     *
     * @param string $table Unprefixed table name.
     * @param string $column
     * @return string|null
     */
    public static function column_type($table, $column) {
        global $wpdb;

        if (!self::table_exists($table)) {
            return null;
        }

        $full = $wpdb->prefix . $table;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $full is $wpdb->prefix plus a table name already checked by table_exists().
        $rows = $wpdb->get_results("SHOW COLUMNS FROM `{$full}`", ARRAY_A);

        foreach ((array) $rows as $row) {
            if (isset($row['Field']) && $row['Field'] === $column) {
                return isset($row['Type']) ? $row['Type'] : '';
            }
        }

        return null;
    }

    /**
     * @param string $table Unprefixed table name.
     * @return bool
     */
    public static function table_exists($table) {
        global $wpdb;

        if (!self::valid_identifier($table)) {
            return false;
        }

        $full = $wpdb->prefix . $table;

        return (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $full));
    }

    private static function valid_identifier($name) {
        return (bool) preg_match('/^[A-Za-z0-9_]+$/', (string) $name);
    }

    private static function log($message) {
        if (class_exists('HashFormHelper') && method_exists('HashFormHelper', 'log')) {
            HashFormHelper::log($message, 'hash-form/schema');
        }
    }

    /* ---------------------------------------------------------------------
     * Testing seam
     * ------------------------------------------------------------------- */

    /**
     * Forget that migrations have run, without touching the schema.
     *
     * Only for tests and for a support case where a step has to be re-run;
     * every step checks the database before changing it, so re-running is
     * safe by construction.
     */
    public static function reset_progress() {
        delete_option(self::PROGRESS);
        delete_option(self::OPTION);
        delete_transient(self::LOCK);
    }

}

new HashFormMigrations();
