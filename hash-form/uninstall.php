<?php

/**
 * Fired when Hash Form is deleted from the Plugins screen.
 *
 * Only things the plugin added to WordPress itself are removed here. Forms,
 * fields, entries and their uploaded files are deliberately left in place:
 * deleting a plugin should not throw away submissions a site may still need,
 * or be legally required to keep, and a plugin that quietly destroys data on
 * uninstall is one nobody can safely reinstall to try again.
 *
 * @package Hash_Form
 */
// If uninstall was not called by WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/HashFormCapabilities.php';

if (class_exists('HashFormCapabilities')) {
    // Take the plugin's capabilities back off every role, so it does not
    // leave them scattered across roles it once touched.
    HashFormCapabilities::remove_caps();
}

delete_option('hashform_caps_version');

// A scheduled event outlives the plugin file unless it is cleared, and then
// fires forever against a hook nothing answers.
wp_clear_scheduled_hook('hashform_daily_maintenance');

// Migration bookkeeping, not data. The tables and everything in them stay.
delete_option('hashform_schema_version');
delete_option('hashform_schema_progress');
delete_transient('hashform_schema_lock');
