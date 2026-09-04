<?php

defined('ABSPATH') || die();

/**
 * Granular permissions for the plugin's admin screens and ajax endpoints.
 *
 * Everything used to be gated on manage_options, which is a single switch:
 * either somebody administers the whole site or they cannot look at a form
 * entry. That is the wrong shape for the common cases - a marketing user who
 * should read submissions but not edit forms, a developer who builds forms but
 * should not hold the payment credentials.
 *
 * Backward compatibility is the constraint that shapes this class. Nobody may
 * lose access on upgrade, so anyone with manage_options is granted every one
 * of these capabilities through the user_has_cap filter below, whether or not
 * the role migration has run. Sites that want true least privilege can turn
 * that off with the hashform_grant_caps_to_admins filter and assign the
 * capabilities by hand.
 *
 * The capabilities are also written onto the administrator role so they show
 * up in the role editors people actually use.
 */
class HashFormCapabilities {

    /** Bumped when the capability list changes, to re-run the role migration. */
    const VERSION = 1;

    const OPTION = 'hashform_caps_version';

    public function __construct() {
        // Runs before admin_menu, so a menu registered against one of these
        // capabilities is always visible to an administrator.
        add_filter('user_has_cap', array($this, 'grant_to_admins'), 10, 1);
        add_action('plugins_loaded', array($this, 'maybe_add_role_caps'), 5);
    }

    /**
     * The capability names, with no translation involved.
     *
     * This is what the permission checks use. It must stay free of gettext:
     * the user_has_cap filter below runs on the very first capability check a
     * request makes, which can be long before init, and calling a translation
     * function there loads the text domain too early - WordPress 6.7 reports
     * exactly that as a notice.
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
     * The same capabilities with labels, for anything that displays them.
     *
     * Only call this from a screen: it translates, so it must not run before
     * init.
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
     * Does the current user hold this capability?
     *
     * Use this rather than current_user_can() directly, so an unknown
     * capability cannot silently pass and so the administrator fallback stays
     * in one place.
     *
     * @param string $cap     One of the capabilities above.
     * @param int    $user_id Optional. Defaults to the current user.
     * @return bool
     */
    public static function user_can($cap, $user_id = 0) {
        // A typo in a capability name must fail closed rather than fall back
        // to something more permissive.
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
     * Stop and say so when the current user may not do something.
     *
     * For screen callbacks, where the right answer is the standard WordPress
     * permissions page rather than a blank screen.
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
     * The same check for an ajax endpoint, which needs json rather than a page.
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
     * Anyone who administers the site keeps the access they have always had.
     *
     * Done with a filter rather than only by writing capabilities onto the
     * role, so an install that has not run the migration yet - or a site with
     * a custom administrator-equivalent role - is never locked out of its own
     * forms.
     *
     * @param array $allcaps
     * @return array
     */
    public function grant_to_admins($allcaps) {
        if (empty($allcaps['manage_options'])) {
            return $allcaps;
        }

        /**
         * Whether holding manage_options implies every Hash Form capability.
         *
         * Return false to enforce the capabilities strictly, so an
         * administrator only has what has actually been assigned to them.
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
     * Write the capabilities onto the administrator role once per version.
     *
     * The filter above is what actually grants access; this exists so the
     * capabilities are visible to the role editing plugins site owners use to
     * hand them to other roles.
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
     * Called from uninstall.php, so the plugin does not leave capabilities
     * behind on every role it ever touched.
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
