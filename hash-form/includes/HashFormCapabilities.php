<?php

defined('ABSPATH') || die();

/**
 * Granular capabilities for the plugin's admin screens and ajax endpoints.
 *
 * Users with manage_options hold all of them through user_has_cap, unless
 * hashform_grant_caps_to_admins returns false.
 */
class HashFormCapabilities {

    /** Bumped when the capability list changes, to re-run the role migration. */
    const VERSION = 1;

    const OPTION = 'hashform_caps_version';

    public function __construct() {
        // Added before admin_menu so menus gated on these capabilities stay visible to administrators.
        add_filter('user_has_cap', array($this, 'grant_to_admins'), 10, 1);
        add_action('plugins_loaded', array($this, 'maybe_add_role_caps'), 5);
    }

    /**
     * Capability names, untranslated.
     *
     * Must not call gettext: user_has_cap can run before init, where loading the text domain triggers a notice.
     *
     * @return string[]
     */
    public static function slugs() {
        return array(
            'hashform_view_forms',
            'hashform_create_forms',
            'hashform_edit_forms',
            'hashform_delete_forms',
            'hashform_view_entries',
            'hashform_edit_entries',
            'hashform_delete_entries',
            'hashform_export_entries',
            'hashform_manage_settings',
            'hashform_manage_integrations',
            'hashform_manage_payments',
        );
    }

    /**
     * Capabilities with translated labels. Call only after init.
     *
     * @return array<string,string>
     */
    public static function all() {
        return array(
            'hashform_view_forms' => esc_html__('View forms', 'hash-form'),
            'hashform_create_forms' => esc_html__('Create forms', 'hash-form'),
            'hashform_edit_forms' => esc_html__('Edit forms', 'hash-form'),
            'hashform_delete_forms' => esc_html__('Delete forms', 'hash-form'),
            'hashform_view_entries' => esc_html__('View entries', 'hash-form'),
            'hashform_edit_entries' => esc_html__('Edit entries', 'hash-form'),
            'hashform_delete_entries' => esc_html__('Delete entries', 'hash-form'),
            'hashform_export_entries' => esc_html__('Export entries', 'hash-form'),
            'hashform_manage_settings' => esc_html__('Manage settings', 'hash-form'),
            'hashform_manage_integrations' => esc_html__('Manage integrations', 'hash-form'),
            'hashform_manage_payments' => esc_html__('Manage payments', 'hash-form'),
        );
    }

    /**
     * Whether the user holds a Hash Form capability. Use instead of current_user_can().
     *
     * @param string $cap     One of the capabilities above.
     * @param int    $user_id Optional. Defaults to the current user.
     * @return bool
     */
    public static function user_can($cap, $user_id = 0) {
        // Unknown capability names fail closed.
        if (!in_array($cap, self::slugs(), true)) {
            return false;
        }

        $allowed = $user_id ? user_can($user_id, $cap) : current_user_can($cap);

        /**
         * Final say on a Hash Form permission check.
         *
         * @param bool   $allowed
         * @param string $cap
         * @param int    $user_id 0 for the current user.
         */
        return (bool) apply_filters('hashform_user_can', $allowed, $cap, $user_id);
    }

    /**
     * Show the standard WordPress permission error if the current user lacks the capability.
     *
     * @param string $cap
     */
    public static function require_cap($cap) {
        if (self::user_can($cap)) {
            return;
        }

        wp_die(
                esc_html__('You do not have permission to do that.', 'hash-form'),
                esc_html__('Permission denied', 'hash-form'),
                array('response' => 403)
        );
    }

    /**
     * Same as require_cap() for ajax endpoints; sends a JSON error.
     *
     * @param string $cap
     */
    public static function require_cap_ajax($cap) {
        if (self::user_can($cap)) {
            return;
        }

        wp_send_json_error(
                array('message' => esc_html__('You do not have permission to do that.', 'hash-form')),
                403
        );
    }

    /**
     * Grant every capability to users with manage_options, so admins are never locked out before the role migration runs.
     *
     * @param array $allcaps
     * @return array
     */
    public function grant_to_admins($allcaps) {
        if (empty($allcaps['manage_options'])) {
            return $allcaps;
        }

        /**
         * Whether manage_options implies every Hash Form capability. Return false to enforce assigned capabilities only.
         */
        if (!apply_filters('hashform_grant_caps_to_admins', true)) {
            return $allcaps;
        }

        foreach (self::slugs() as $cap) {
            if (!isset($allcaps[$cap])) {
                $allcaps[$cap] = true;
            }
        }

        return $allcaps;
    }

    /**
     * Write the capabilities onto the administrator role once per version, so role editors list them.
     */
    public function maybe_add_role_caps() {
        if ((int) get_option(self::OPTION) === self::VERSION) {
            return;
        }

        self::add_caps();
        update_option(self::OPTION, self::VERSION);
    }

    public static function add_caps() {
        $role = get_role('administrator');

        if (!$role) {
            return;
        }

        foreach (self::slugs() as $cap) {
            $role->add_cap($cap);
        }
    }

    /**
     * Remove the capabilities from every role. Called from uninstall.php.
     */
    public static function remove_caps() {
        global $wp_roles;

        if (!isset($wp_roles) && class_exists('WP_Roles')) {
            $wp_roles = new WP_Roles();
        }

        if (!isset($wp_roles)) {
            return;
        }

        foreach (array_keys($wp_roles->roles) as $role_name) {
            $role = get_role($role_name);

            if (!$role) {
                continue;
            }

            foreach (self::slugs() as $cap) {
                $role->remove_cap($cap);
            }
        }
    }

}

new HashFormCapabilities();
