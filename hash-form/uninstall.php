<?php

/**
 * Fired when Hash Form is deleted from the Plugins screen.
 *
 * Removes options, capabilities and the cron event only; forms, entries and uploads are kept on purpose.
 *
 * @package Hash_Form
 */
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/HashFormCapabilities.php';

if (class_exists('HashFormCapabilities')) {
    HashFormCapabilities::remove_caps();
}

delete_option('hashform_caps_version');

wp_clear_scheduled_hook('hashform_daily_maintenance');

// Migration bookkeeping only; the tables stay.
delete_option('hashform_schema_version');
delete_option('hashform_schema_progress');
delete_transient('hashform_schema_lock');
