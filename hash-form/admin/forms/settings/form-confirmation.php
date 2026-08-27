<?php
defined('ABSPATH') || die();
/*
 * A template, included from inside a class method - never loaded on its own.
 * The variables below are locals of the method that includes it, not globals,
 * which is what the prefix sniff assumes about a file-scope assignment.
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from within a method, so these are function locals.

// The unprefixed filter this file shipped with, still applied so an add-on
// written against it keeps working.
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- kept for backward compatibility; hashform_confirmation_types is the prefixed name to use.
$hf_confirmation_types = apply_filters('hf_confirmation_types', array(
    'show_message' => __('Message', 'hash-form'),
    'show_page' => __('Show Page', 'hash-form'),
    'redirect_url' => __('Redirect URL', 'hash-form'),
));

/**
 * The ways a form can respond once it has been submitted.
 *
 * @param array $hf_confirmation_types Type key => label.
 */
$hf_confirmation_types = apply_filters('hashform_confirmation_types', $hf_confirmation_types);
?>

<div class="hf-form-container">
    <div class="hf-form-row">
        <label><?php esc_html_e('Confirmation Type', 'hash-form'); ?></label>
        <select name="confirmation_type" data-condition="toggle" id="hf-form-conformation-type">
            <?php
            foreach ($hf_confirmation_types as $hf_key => $hf_val) {
                ?>
                <option value="<?php echo esc_attr($hf_key) ?>" <?php selected($settings['confirmation_type'], $hf_key); ?>><?php echo esc_html($hf_val); ?></option>
                <?php
            }
            ?>
        </select>
    </div>

    <div class="hf-form-row" data-condition-toggle="hf-form-conformation-type" data-condition-val="show_message">
        <label><?php esc_html_e('Message', 'hash-form'); ?></label>
        <textarea name="confirmation_message"><?php echo esc_html($settings['confirmation_message']) ?></textarea>
    </div>

    <div class="hf-form-row" data-condition-toggle="hf-form-conformation-type" data-condition-val="show_page">
        <label><?php esc_html_e('Show Page', 'hash-form'); ?></label>
        <select name="show_page_id">
            <?php foreach (get_pages() as $page) { ?>
                <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($settings['show_page_id'], $page->ID); ?>><?php echo esc_html($page->post_title); ?></option>
            <?php } ?>
        </select>
    </div>

    <div class="hf-form-row" data-condition-toggle="hf-form-conformation-type" data-condition-val="redirect_url">
        <label><?php esc_html_e('Redirect URL', 'hash-form'); ?></label>
        <input type="text" name="redirect_url_page" value="<?php echo esc_attr($settings['redirect_url_page']) ?>" />
    </div>

    <div class="hf-form-row">
        <label><?php esc_html_e('Error Message', 'hash-form'); ?></label>
        <textarea name="error_message"><?php echo esc_textarea($settings['error_message']) ?></textarea>
    </div>
</div>