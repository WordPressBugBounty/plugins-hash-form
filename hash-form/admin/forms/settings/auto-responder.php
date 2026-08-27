<?php
defined('ABSPATH') || die();
/*
 * A template, included from inside a class method - never loaded on its own.
 * The variables below are locals of the method that includes it, not globals,
 * which is what the prefix sniff assumes about a file-scope assignment.
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from within a method, so these are function locals.
?>

<div class="hf-form-container">
    <div class="hf-form-row">
        <label><?php esc_html_e('Enable Auto Responder', 'hash-form'); ?></label>
        <div class="hf-setting-fields hf-toggle-input-field">
            <input type="hidden" name="enable_ar" value="off">
            <input type="checkbox" name="enable_ar" value="on" data-condition="toggle" id="hf-enable-ar" <?php checked($settings['enable_ar'], 'on', true); ?>>
        </div>
    </div>

    <?php
    // Everything below only applies once the responder is on, so it is
    // grouped and hung off the switch rather than sitting there looking
    // active while nothing sends. One group, not one rule per row.
    ?>
    <div data-condition-toggle="hf-enable-ar">
        <div class="hf-form-row">
            <label><?php esc_html_e('From Email', 'hash-form'); ?></label>
            <input type="text" name="from_ar" value="<?php echo esc_attr($settings['from_ar']) ?>" />
        </div>

        <div class="hf-form-row">
            <label><?php esc_html_e('From Name', 'hash-form'); ?></label>
            <input type="text" name="from_ar_name" value="<?php echo esc_attr($settings['from_ar_name']) ?>" />
        </div>

        <div class="hf-form-row">
            <label><?php esc_html_e('Reply To Email', 'hash-form'); ?></label>
            <select name="reply_to_ar">
                <option value=""><?php esc_html_e('Choose a Form Field', 'hash-form'); ?></option>
                <?php
                foreach ($fields as $field) {
                    if ($field->type == 'email') {
                        ?>
                        <option value="<?php echo esc_attr($field->id); ?>" <?php selected($settings['reply_to_ar'], $field->id); ?>><?php echo esc_html($field->name); ?></option>
                        <?php
                    }
                }
                ?>
            </select>
        </div>

        <div class="hf-form-row">
            <label> <?php esc_html_e('Subject', 'hash-form'); ?></label>
            <input type="text" name="email_subject_ar" value="<?php echo esc_attr($settings['email_subject_ar']) ?>" />
        </div>

        <div class="hf-form-row">
            <label><?php esc_html_e('Message', 'hash-form'); ?></label>
            <textarea name="email_message_ar" cols="50" rows="5"><?php echo ($settings['email_message_ar'] ? esc_textarea($settings['email_message_ar']) : ''); ?></textarea>
        </div>
    </div>
</div>