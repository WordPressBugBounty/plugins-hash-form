<?php

defined('ABSPATH') || die();

class HashFormCreateTable {

    public $fields;
    public $forms;
    public $entries;
    public $entry_metas;

    public function __construct() {
        global $wpdb;
        $this->fields = $wpdb->prefix . 'hashform_fields';
        $this->forms = $wpdb->prefix . 'hashform_forms';
        $this->entries = $wpdb->prefix . 'hashform_entries';
        $this->entry_metas = $wpdb->prefix . 'hashform_entry_meta';
    }

    public function upgrade() {
        global $wpdb;
        flush_rewrite_rules();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $this->create_tables();
        update_option('hashform_db_version', HASHFORM_VERSION);
    }

    /**
     * Activation is the only thing that used to run dbDelta, so a site that
     * updated the plugin never received new columns. Run it once per version
     * instead.
     */
    public static function maybe_upgrade() {
        if (get_option('hashform_db_version') === HASHFORM_VERSION) {
            return;
        }

        $db = new self();
        $db->upgrade();
        $db->migrate_existing_entries_to_read();
    }

    /**
     * Entries that predate the read/unread column would all show up as unread,
     * which is noise rather than news. Mark them read once.
     */
    private function migrate_existing_entries_to_read() {
        if (get_option('hashform_entries_read_migrated')) {
            return;
        }

        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $this->entries is $wpdb->prefix concatenated with a table name literal in this class; the query carries no values at all.
        $wpdb->query("UPDATE {$this->entries} SET is_read = 1 WHERE is_read = 0");

        update_option('hashform_entries_read_migrated', 1);
    }

    public function collation() {
        global $wpdb;
        if (!$wpdb->has_cap('collation'))
            return '';
        return $wpdb->get_charset_collate();
    }

    private function create_tables() {
        $charset_collate = $this->collation();
        $sql = array();
        $sql[] = 'CREATE TABLE ' . $this->fields . ' (
		id BIGINT(20) NOT NULL auto_increment,
		field_key varchar(100) default NULL,
                name text default NULL,
                description longtext default NULL,
                type text default NULL,
                default_value longtext default NULL,
                options longtext default NULL,
                field_order int(11) default 0,
                required int(1) default NULL,
                field_options longtext default NULL,
                form_id int(11) default NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY form_id (form_id),
                UNIQUE KEY field_key (field_key)
        )';
        $sql[] = 'CREATE TABLE ' . $this->forms . ' (
                id int(11) NOT NULL auto_increment,
		form_key varchar(100) default NULL,
                name varchar(255) default NULL,
                description text default NULL,
                status varchar(255) default NULL,
                options longtext default NULL,
                settings longtext default NULL,
                styles longtext default NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY form_key (form_key)
        )';
        $sql[] = 'CREATE TABLE ' . $this->entries . ' (
		id BIGINT(20) NOT NULL auto_increment,
                ip text default NULL,
		form_id BIGINT(20) default NULL,
		user_id BIGINT(20) default NULL,
		delivery_status tinyint(1) default 0,
                status varchar(255) default NULL,
                is_read tinyint(1) default 0,
                is_starred tinyint(1) default 0,
                notes longtext default NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY form_id (form_id),
                KEY user_id (user_id)
        )';
        $sql[] = 'CREATE TABLE ' . $this->entry_metas . ' (
		id BIGINT(20) NOT NULL auto_increment,
		meta_value longtext default NULL,
		field_id BIGINT(20) NOT NULL,
		item_id BIGINT(20) NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY field_id (field_id),
                KEY item_id (item_id)
        )';
        foreach ($sql as $q) {
            if (function_exists('dbDelta')) {
                dbDelta($q . $charset_collate . ';');
            } else {
                global $wpdb;
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.SchemaChange -- $q is one of the CREATE TABLE literals defined immediately above and $charset_collate comes from $wpdb; neither can carry input. Only reached where dbDelta() is unavailable.
                $wpdb->query($q . $charset_collate);
            }
            unset($q);
        }
    }

}
