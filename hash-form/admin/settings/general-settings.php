<?php
defined('ABSPATH') || die();
/*
 * A template, included from inside a class method - never loaded on its own.
 * The variables below are locals of the method that includes it, not globals,
 * which is what the prefix sniff assumes about a file-scope assignment.
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from within a method, so these are function locals.

$hf_load_google_fonts = isset($settings['load_google_fonts']) ? $settings['load_google_fonts'] : 'on';
?>

<div class="hf-settings-row">
    <label class="hf-setting-label"><?php esc_html_e('Google Fonts', 'hash-form'); ?></label>
    <div class="hf-setting-fields">
        <label>
            <input type="checkbox" name="hashform_settings[load_google_fonts]" value="on" <?php checked($hf_load_google_fonts, 'on'); ?> />
            <?php esc_html_e('Load fonts from Google', 'hash-form'); ?>
        </label>
        <p class="hf-desc">
            <?php esc_html_e('Style templates can pick a Google font, which the form then requests from fonts.googleapis.com as the page loads. That tells Google the address of every visitor who sees the form.', 'hash-form'); ?>
            <br /><?php esc_html_e('Switch this off to stop those requests. Forms then use whichever font the theme already provides, and any Google font chosen in a style template is ignored.', 'hash-form'); ?>
        </p>
    </div>
</div>
