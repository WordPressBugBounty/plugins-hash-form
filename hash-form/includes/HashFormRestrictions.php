<?php

defined('ABSPATH') || die();

/**
 * Decides whether a form is currently accepting submissions. Checked on render and again on submit.
 *
 * Add-ons, including Pro, add rules through the hashform_form_restrictions filter.
 */
class HashFormRestrictions {

    /**
     * Returns array('allowed' => bool, 'message' => string, 'reason' => string).
     */
    public static function check($form) {
        $allowed = array('allowed' => true, 'message' => '', 'reason' => '');

        if (!$form || !isset($form->settings) || !is_array($form->settings)) {
            return $allowed;
        }

        $settings = array_merge(HashFormHelper::get_form_settings_default(), $form->settings);

        // Form editors bypass restrictions so they can test a scheduled form before it opens.
        if (apply_filters('hashform_restrictions_bypass', HashFormCapabilities::user_can('hashform_edit_forms'), $form)) {
            return $allowed;
        }

        $result = self::check_duplicate($form, $settings);

        if ($result) {
            return $result;
        }

        return apply_filters('hashform_form_restrictions', $allowed, $form, $settings);
    }

    public static function is_allowed($form) {
        $state = self::check($form);
        return !empty($state['allowed']);
    }

    private static function blocked($reason, $message) {
        return array(
            'allowed' => false,
            'reason' => $reason,
            'message' => $message,
        );
    }

    private static function enabled($settings, $key) {
        return isset($settings[$key]) && 'on' === $settings[$key];
    }




    /**
     * One entry per person: logged-in visitors are matched on user id, guests on IP address.
     */
    private static function check_duplicate($form, $settings) {
        if (!self::enabled($settings, 'one_entry_per_user')) {
            return false;
        }

        global $wpdb;
        $user_id = get_current_user_id();

        if ($user_id) {
            $found = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}hashform_entries WHERE form_id = %d AND user_id = %d AND status = 'published' LIMIT 1", $form->id, $user_id));
        } else {
            $ip = HashFormHelper::get_ip();

            if (!$ip) {
                return false;
            }

            $found = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}hashform_entries WHERE form_id = %d AND ip = %s AND status = 'published' LIMIT 1", $form->id, $ip));
        }

        if ($found) {
            return self::blocked('duplicate', $settings['duplicate_message']);
        }

        return false;
    }



    /**
     * The notice shown in place of a form that is not accepting submissions.
     */
    public static function get_closed_html($state) {
        $message = isset($state['message']) ? $state['message'] : '';

        if ('' === $message) {
            return '';
        }

        return '<div class="hf-form-closed-msg">' . esc_html($message) . '</div>';
    }

}
