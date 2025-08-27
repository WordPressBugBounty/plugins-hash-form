<?php
defined('ABSPATH') || die();

$confirmation_types = apply_filters('hf_confirmation_types', array(
    'show_message' => __('Message', 'hash-form'),
    'show_page' => __('Show Page', 'hash-form'),
    'redirect_url' => __('Redirect URL', 'hash-form'),
));
?>

<div class="hf-form-container hf-grid-container">
    <div class="hf-form-row hf-grid-container">
        <div class="hf-grid-3">
            <label><?php esc_html_e('Confirmation Type', 'hash-form'); ?></label>
            <select name="confirmation_type" data-condition="toggle" id="hf-form-conformation-type">
                <?php
                foreach ($confirmation_types as $key => $val) {
                    ?>
                    <option value="<?php echo esc_attr($key) ?>" <?php selected($settings['confirmation_type'], $key); ?>><?php echo esc_html($val); ?></option>
                    <?php
                }
                ?>
            </select>
        </div>
    </div>

    <div class="hf-form-row hf-grid-container" data-condition-toggle="hf-form-conformation-type" data-condition-val="show_message">
        <div class="hf-grid-3">
            <label><?php esc_html_e('Message', 'hash-form'); ?></label>
            <textarea name="confirmation_message"><?php echo esc_html($settings['confirmation_message']) ?></textarea>
        </div>
    </div>

    <div class="hf-form-row hf-grid-container" data-condition-toggle="hf-form-conformation-type" data-condition-val="show_page">
        <div class="hf-grid-3">
            <label><?php esc_html_e('Show Page', 'hash-form'); ?></label>
            <select name="show_page_id">
                <?php foreach (get_pages() as $page) { ?>
                    <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($settings['show_page_id'], $page->ID); ?>><?php echo esc_html($page->post_title); ?></option>
                <?php } ?>
            </select>
        </div>
    </div>

    <div class="hf-form-row hf-grid-container" data-condition-toggle="hf-form-conformation-type" data-condition-val="redirect_url">
        <div class="hf-grid-3">
            <label><?php esc_html_e('Redirect URL', 'hash-form'); ?></label>
            <input type="text" name="redirect_url_page" value="<?php echo esc_attr($settings['redirect_url_page']) ?>" />
        </div>
    </div>

    <div class="hf-form-row hf-grid-container">
        <div class="hf-grid-3">
            <label><?php esc_html_e('Error Message', 'hash-form'); ?></label>
            <textarea name="error_message"><?php echo esc_textarea($settings['error_message']) ?></textarea>
        </div>
    </div>
</div>