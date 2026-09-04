<?php

/*
 * Plugin Name: Hash Form - Drag & Drop Form Builder
 * Description: Design, Embed, Connect: Your Ultimate Form Companion for WordPress
 * Version: 1.4.4
 * Author: HashThemes
 * Author URI: https://hashthemes.com/
 * Text Domain: hash-form
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 */


defined('ABSPATH') || die();

define('HASHFORM_VERSION', '1.4.4');
define('HASHFORM_FILE', __FILE__);
define('HASHFORM_PATH', plugin_dir_path(HASHFORM_FILE));
define('HASHFORM_URL', plugin_dir_url(HASHFORM_FILE));
define('HASHFORM_UPLOAD_DIR', '/hashform');

require HASHFORM_PATH . 'includes/HashFormCapabilities.php';
require HASHFORM_PATH . 'includes/HashFormSerializedStrParser.php';
require HASHFORM_PATH . 'includes/HashFormStrReader.php';
require HASHFORM_PATH . 'includes/HashFormBlock.php';
require HASHFORM_PATH . 'includes/HashFormUploader.php';
require HASHFORM_PATH . 'includes/HashFormCreateTable.php';
require HASHFORM_PATH . 'includes/HashFormMigrations.php';
require HASHFORM_PATH . 'includes/HashFormCron.php';
// Must load before the classes that compose it.
require HASHFORM_PATH . 'includes/HashFormListActions.php';
require HASHFORM_PATH . 'includes/HashFormBuilder.php';
require HASHFORM_PATH . 'includes/HashFormHelper.php';
require HASHFORM_PATH . 'includes/HashFormFields.php';
require HASHFORM_PATH . 'includes/HashFormFieldIcons.php';
require HASHFORM_PATH . 'includes/HashFormLoader.php';
require HASHFORM_PATH . 'includes/HashFormSmtp.php';
require HASHFORM_PATH . 'includes/HashFormEntry.php';
require HASHFORM_PATH . 'includes/HashFormImportExport.php';
require HASHFORM_PATH . 'includes/HashFormListing.php';
require HASHFORM_PATH . 'includes/HashFormEntryListing.php';
require HASHFORM_PATH . 'includes/HashFormValidate.php';
require HASHFORM_PATH . 'includes/HashFormRestrictions.php';
require HASHFORM_PATH . 'includes/HashFormPreview.php';
require HASHFORM_PATH . 'includes/HashFormShortcode.php';
require HASHFORM_PATH . 'includes/HashFormSettings.php';
require HASHFORM_PATH . 'includes/HashFormStyles.php';
require HASHFORM_PATH . 'includes/HashFormStyleBuilder.php';
require HASHFORM_PATH . 'includes/HashFormGridHelper.php';
require HASHFORM_PATH . 'includes/HashFormEmail.php';
require HASHFORM_PATH . 'includes/HashFormPrivacy.php';

/**
 * Bring the schema up to date after a plugin update, not just on activation.
 */
add_action('plugins_loaded', array('HashFormCreateTable', 'maybe_upgrade'));

/**
 * Register widget.
 */
add_action('elementor/widgets/register', 'hashform_elementor_widget_register');

function hashform_elementor_widget_register($widgets_manager) {
    // require_once, because this hook can fire more than once in a request -
    // the editor re-registers widgets, and other plugins trigger it. A plain
    // require made the second pass a fatal: "Cannot declare class".
    require_once HASHFORM_PATH . 'includes/HashFormElement.php';

    $widgets_manager->register(new \HashFormElement());

    // The same widget under the name it had before, so pages already built with
    // it keep rendering. Hidden from the panel.
    $widgets_manager->register(new \HashFormElementLegacy());
}

/**
 * Plugin Activation.
 */
register_activation_hook(HASHFORM_FILE, 'hashform_network_create_table');

function hashform_network_create_table($network_wide) {
    global $wpdb;

    if (is_multisite() && $network_wide) {
        // Get all blogs in the network and activate plugin on each one
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            $db = new HashFormCreateTable();
            $db->upgrade();
            restore_current_blog();
        }
    } else {
        $db = new HashFormCreateTable();
        $db->upgrade();
    }
}

/**
 * Plugin Deactivation.
 *
 * A scheduled event that outlives the plugin keeps firing against a hook
 * nothing answers, so the event goes when the plugin does.
 */
register_deactivation_hook(HASHFORM_FILE, 'hashform_on_deactivate');

function hashform_on_deactivate() {
    if (class_exists('HashFormCron')) {
        HashFormCron::unschedule();
    }
}

/**
 * Create form tables on multisite creation.
 */
add_action('wp_insert_site', 'hashform_on_create_blog');

function hashform_on_create_blog($data) {
    if (is_plugin_active_for_network('hash-form/hash-form.php')) {
        switch_to_blog($data->blog_id);
        $db = new HashFormCreateTable();
        $db->upgrade();
        restore_current_blog();
    }
}

/**
 * Drop form tables on multisite deletion.
 */
add_filter('wpmu_drop_tables', 'hashform_on_delete_blog');

function hashform_on_delete_blog($tables) {
    global $wpdb;
    $id = HashFormHelper::get_request('id');

    $tables[] = $wpdb->get_blog_prefix($id) . 'hashform_fields';
    $tables[] = $wpdb->get_blog_prefix($id) . 'hashform_forms';
    $tables[] = $wpdb->get_blog_prefix($id) . 'hashform_entries';
    $tables[] = $wpdb->get_blog_prefix($id) . 'hashform_entry_meta';

    return $tables;
}
