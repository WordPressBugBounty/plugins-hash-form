<?php
defined('ABSPATH') || die();

// Settings saved before this section existed have none of these keys.
$settings = array_merge(HashFormHelper::get_form_settings_default(), (array) $settings);
?>

<div class="hf-form-container">
    <div class="hf-form-row">
        <label><?php esc_html_e('One Entry Per Person', 'hash-form'); ?></label>
        <div class="hf-setting-fields hf-toggle-input-field">
            <input type="hidden" name="one_entry_per_user" value="off">
            <input type="checkbox" name="one_entry_per_user" value="on" data-condition="toggle" id="hf-one-entry" <?php checked($settings['one_entry_per_user'], 'on', true); ?>>
        </div>
        <p class="hf-desc"><?php esc_html_e('Logged in visitors are matched by their account, guests by IP address.', 'hash-form'); ?></p>
    </div>

    <div class="hf-form-row" data-condition-toggle="hf-one-entry" data-condition-val="on">
        <label><?php esc_html_e('Message After Submitting Once', 'hash-form'); ?></label>
        <textarea name="duplicate_message"><?php echo esc_textarea($settings['duplicate_message']); ?></textarea>
    </div>

    <?php
    // Scheduling, entry limits and login requirements live in the Pro plugin,
    // which adds its own section when active.
    if (!defined('HASH_FORM_PRO_VERSION')) {
        ?>
        <div class="hf-form-row">
            <p class="hf-desc">
                <?php esc_html_e('Looking to schedule this form, cap the number of entries or require visitors to log in?', 'hash-form'); ?>
                <a href="https://hashthemes.com/plugin/hash-form-pro/" target="_blank" rel="noopener"><?php esc_html_e('Hash Form Pro', 'hash-form'); ?></a>
                <?php esc_html_e('adds those under Schedule &amp; Restriction.', 'hash-form'); ?>
            </p>
        </div>
        <?php
    }
    ?>
</div>