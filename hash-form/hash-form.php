<?php

/*
 * Plugin Name: Hash Form - Drag & Drop Form Builder
 * Description: Design, Embed, Connect: Your Ultimate Form Companion for WordPress
 * Version: 1.4.6
 * Author: HashThemes
 * Author URI: https://hashthemes.com/
 * Text Domain: hash-form
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 */


defined('ABSPATH') || die();

define('HASHFORM_VERSION', '1.4.6');
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
require HASHFORM_PATH . 'includes/HashFormUpgrade.php';
require HASHFORM_PATH . 'includes/HashFormStyles.php';
require HASHFORM_PATH . 'includes/HashFormStyleBuilder.php';
require HASHFORM_PATH . 'includes/HashFormGridHelper.php';
require HASHFORM_PATH . 'includes/HashFormEmail.php';
require HASHFORM_PATH . 'includes/HashFormPrivacy.php';

/**
 * Run schema upgrades after plugin updates too, not only on activation.
 */
add_action('plugins_loaded', array('HashFormCreateTable', 'maybe_upgrade'));

/**
 * Register widget.
 */
add_action('elementor/widgets/register', 'hashform_elementor_widget_register');

function hashform_elementor_widget_register($widgets_manager) {
    // require_once: this hook can fire more than once per request.
    require_once HASHFORM_PATH . 'includes/HashFormElement.php';

    $widgets_manager->register(new \HashFormElement());

    // Legacy widget name, hidden from the panel, so existing pages keep rendering.
    $widgets_manager->register(new \HashFormElementLegacy());
}

/**
 * Plugin Activation.
 */
register_activation_hook(HASHFORM_FILE, 'hashform_network_create_table');

function hashform_network_create_table($network_wide) {
    global $wpdb;

    if (is_multisite() && $network_wide) {
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
 * Plugin deactivation: clear the scheduled maintenance event.
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
    // wp_insert_site also fires from front-end signups, where this admin helper is not loaded.
    if (!function_exists('is_plugin_active_for_network')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

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
add_filter('wpmu_drop_tables', 'hashform_on_delete_blog', 10, 2);

function hashform_on_delete_blog($tables, $site_id = 0) {
    global $wpdb;
    // Use the site WordPress is deleting; an empty id would resolve to the main site's prefix.
    $prefix = $wpdb->get_blog_prefix($site_id ? $site_id : null);

    $tables[] = $prefix . 'hashform_fields';
    $tables[] = $prefix . 'hashform_forms';
    $tables[] = $prefix . 'hashform_entries';
    $tables[] = $prefix . 'hashform_entry_meta';

    return $tables;
}
