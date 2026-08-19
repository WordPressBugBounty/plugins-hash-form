<?php
defined('ABSPATH') || die();
?>

<div class="hf-fields-settings hf-hidden hf-fields-type-<?php echo esc_attr($field_type); ?>" id="hf-fields-settings-<?php echo esc_attr($field_id); ?>" data-fid="<?php echo esc_attr($field_id); ?>">
    <input type="hidden" name="hf-form-submitted[]" value="<?php echo absint($field_id); ?>" />
    <input type="hidden" name="field_options[field_order_<?php echo absint($field_id); ?>]" value="<?php echo esc_attr($field['field_order']); ?>" />
    <input type="hidden" name="field_options[grid_id_<?php echo absint($field_id); ?>]" value="<?php echo isset($field['grid_id']) ? esc_attr($field['grid_id']) : ''; ?>" id="hf-grid-class-<?php echo esc_attr($field_id); ?>" />
    <input type="hidden" name="field_options[column_group_<?php echo absint($field_id); ?>]" value="<?php echo isset($field['column_group']) ? esc_attr($field['column_group']) : ''; ?>" id="hf-column-group-<?php echo esc_attr($field_id); ?>" />
    <input type="hidden" name="field_options[column_row_<?php echo absint($field_id); ?>]" value="<?php echo isset($field['column_row']) ? esc_attr($field['column_row']) : ''; ?>" id="hf-column-row-<?php echo esc_attr($field_id); ?>" />

    <div class="hf-field-panel-header">
        <h3>
            <?php
            /* translators: 1: field name */
            printf(esc_html__('%s Field', 'hash-form'), esc_html($type_name));
            ?>
        </h3>
        <div class="hf-field-panel-id">(ID <?php echo esc_html($field_id); ?>)</div>
    </div>

    <div class="hf-form-container">
        <?php
        if ($field_type === 'captcha' && !HashFormFieldCaptcha::should_show_captcha()) {
            ?>
            <div class="hf-form-row">
                <?php
                /* translators: 1: link open, 2: link close */
                printf(esc_html__('Captchas will not work untill the Site and Secret Keys are set up. Add Keys %1$shere%2$s.', 'hash-form'), '<a href="?page=hashform-settings" target="_blank">', '</a>');
                ?>
                <label class="hf-field-desc">
                    <?php
                    /* translators: 1: link open, 2: link close */
                    printf(esc_html__('Tutorial to %1$sGenerate Site and Secret Keys%2$s', 'hash-form'), '<a href="https://hashthemes.com/articles/generate-site-key-and-secret-key-from-google-recaptcha/" target="_blank">', '</a>');
                    ?>
                </label>
            </div>
            <?php
        }

        /*
         * Only for v2. A v3 widget is invisible, so neither of these describes
         * anything that is drawn; both were stored and read when rendering the
         * widget but had no control anywhere, leaving every captcha on normal
         * and light whatever the site owner wanted.
         */
        if ($field_type === 'captcha' && HashFormFieldCaptcha::should_show_captcha() && HashFormFieldCaptcha::is_v2()) {
            $captcha_size = isset($field['captcha_size']) ? $field['captcha_size'] : 'normal';
            $captcha_theme = isset($field['captcha_theme']) ? $field['captcha_theme'] : 'light';
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Captcha Size', 'hash-form'); ?></label>
                <select name="field_options[captcha_size_<?php echo absint($field_id); ?>]">
                    <?php foreach (HashFormFieldCaptcha::captcha_sizes() as $hashform_size => $hashform_size_label) { ?>
                        <option value="<?php echo esc_attr($hashform_size); ?>" <?php selected($captcha_size, $hashform_size); ?>>
                            <?php echo esc_html($hashform_size_label); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="hf-form-row">
                <label><?php esc_html_e('Captcha Theme', 'hash-form'); ?></label>
                <select name="field_options[captcha_theme_<?php echo absint($field_id); ?>]">
                    <?php foreach (HashFormFieldCaptcha::captcha_themes() as $hashform_theme => $hashform_theme_label) { ?>
                        <option value="<?php echo esc_attr($hashform_theme); ?>" <?php selected($captcha_theme, $hashform_theme); ?>>
                            <?php echo esc_html($hashform_theme_label); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <?php
        }

        if ($display['label']) {
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Field Label', 'hash-form'); ?> </label>
                <input type="text" name="field_options[name_<?php echo absint($field_id); ?>]" value="<?php echo esc_attr($field['name']); ?>" data-changeme="hf-editor-field-label-text-<?php echo esc_attr($field_id); ?>" data-label-show-hide="hf-label-show-hide" />
            </div>

            <div class="hf-form-row hf-grid-3">
                <label><?php esc_html_e('Label Position', 'hash-form'); ?></label>
                <?php // No space after the underscore: the save path looks up label_position_<id> and a stray one made this setting silently unsaveable. ?>
                <select name="field_options[label_position_<?php echo absint($field_id); ?>]">
                    <option value="top" <?php isset($field['label_position']) ? selected($field['label_position'], 'top') : ''; ?>>
                        <?php esc_html_e('Top', 'hash-form'); ?>
                    </option>
                    <option value="left" <?php isset($field['label_position']) ? selected($field['label_position'], 'left') : ''; ?>>
                        <?php esc_html_e('Left', 'hash-form'); ?>
                    </option>
                    <option value="right" <?php isset($field['label_position']) ? selected($field['label_position'], 'right') : ''; ?>>
                        <?php esc_html_e('Right', 'hash-form'); ?>
                    </option>
                    <option value="hide" <?php isset($field['label_position']) ? selected($field['label_position'], 'hide') : ''; ?>>
                        <?php esc_html_e('Hide', 'hash-form'); ?>
                    </option>
                </select>
            </div>

            <div class="hf-form-row hf-grid-3">
                <label><?php esc_html_e('Label Alignment', 'hash-form'); ?></label>
                <select name="field_options[label_alignment_<?php echo absint($field_id); ?>]">
                    <option value="left" <?php selected($field['label_alignment'], 'left'); ?>>
                        <?php esc_html_e('Left', 'hash-form'); ?>
                    </option>
                    <option value="right" <?php selected($field['label_alignment'], 'right'); ?>>
                        <?php esc_html_e('Right', 'hash-form'); ?>
                    </option>
                    <option value="center" <?php selected($field['label_alignment'], 'center'); ?>>
                        <?php esc_html_e('Center', 'hash-form'); ?>
                    </option>
                </select>
            </div>

            <div class="hf-form-row">
                <label for="hf-hide-label-field-<?php echo absint($field_id); ?>">
                    <input id="hf-hide-label-field-<?php echo absint($field_id); ?>" type="checkbox" name="field_options[hide_label_<?php echo absint($field_id); ?>]" value="1" <?php checked((isset($field['hide_label']) && $field['hide_label']), 1); ?> data-label-show-hide-checkbox="hf-label-show-hide" />
                    <?php esc_html_e('Hide Label', 'hash-form'); ?>
                </label>
            </div>
            <?php
        }

        if ($field_type === 'heading') {
            // Looped rather than six near-identical blocks, each of which had
            // to remember to fall back to the default when nothing was saved;
            // none of them did, so an unsaved field always showed H1.
            $heading_type = isset($field['heading_type']) ? $field['heading_type'] : 'h3';
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Select Heading', 'hash-form'); ?></label>
                <select name="field_options[heading_type_<?php echo esc_attr($field_id); ?>]">
                    <?php foreach (hashform_heading_levels() as $level) { ?>
                        <option value="<?php echo esc_attr($level); ?>" <?php selected($heading_type, $level); ?>>
                            <?php echo esc_html(strtoupper($level)); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <?php
        }

        if ($display['content']) {
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Content', 'hash-form'); ?></label>
                <div class="hf-form-textarea">
                    <?php
                    /*
                     * Decoded first. What is stored is already entity-encoded,
                     * so escaping it again for the textarea meant an author who
                     * typed `5 < 10 & "quoted"` came back to
                     * `5 &lt; 10 &amp; &quot;quoted&quot;`.
                     */
                    $content_value = isset($field['content']) ? html_entity_decode($field['content'], ENT_QUOTES, 'UTF-8') : '';
                    ?>
                    <textarea name="field_options[content_<?php echo esc_attr($field_id); ?>]" data-changeme="hf-field-<?php echo esc_attr($field_id) ?>"><?php echo esc_textarea($content_value); ?></textarea>
                </div>
            </div>

            <div class="hf-form-row">
                <label><?php esc_html_e('Text Alignment', 'hash-form'); ?></label>
                <select name="field_options[text_alignment_<?php echo esc_attr($field_id); ?>]">
                    <option value="left" <?php isset($field['text_alignment']) ? selected($field['text_alignment'], 'left') : ''; ?>>
                        <?php esc_html_e('Left', 'hash-form'); ?>
                    </option>
                    <option value="right" <?php isset($field['text_alignment']) ? selected($field['text_alignment'], 'right') : ''; ?>>
                        <?php esc_html_e('Right', 'hash-form'); ?>
                    </option>
                    <option value="center" <?php isset($field['text_alignment']) ? selected($field['text_alignment'], 'center') : ''; ?>>
                        <?php esc_html_e('Center', 'hash-form'); ?>
                    </option>
                </select>
            </div>
            <?php
        }

        if ($field_type === 'image_select') {
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Select Type', 'hash-form'); ?></label>
                <select class="hf-select-image-type" name="field_options[select_option_type_<?php echo esc_attr($field_id); ?>]" data-is-id="<?php echo esc_attr($field_id); ?>">
                    <option value="checkbox" <?php isset($field['select_option_type']) ? selected($field['select_option_type'], 'checkbox') : ''; ?>>
                        <?php esc_html_e('Multiple', 'hash-form'); ?>
                    </option>
                    <option value="radio" <?php isset($field['select_option_type']) ? selected($field['select_option_type'], 'radio') : ''; ?>>
                        <?php esc_html_e('Single', 'hash-form'); ?>
                    </option>
                </select>
            </div>
            <?php
            $hashform_columns = array(
                'small' => esc_html__('Small', 'hash-form'),
                'medium' => esc_html__('Medium', 'hash-form'),
                'large' => esc_html__('Large', 'hash-form'),
                'xlarge' => esc_html__('Extra Large', 'hash-form'),
            );
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Image Size', 'hash-form'); ?></label>
                <select name="field_options[image_size_<?php echo absint($field_id); ?>]">
                    <?php foreach ($hashform_columns as $hashform_col => $hashform_col_label) { ?>
                        <option value="<?php echo esc_attr($hashform_col); ?>" <?php selected($field['image_size'], $hashform_col); ?>>
                            <?php echo esc_html($hashform_col_label); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <?php
        }

        if ($field_type === 'image') {
            $hashform_image_id = $hashform_image = '';
            if (isset($field['image_id'])) {
                $hashform_image_id = $field['image_id'];
                $hashform_image = wp_get_attachment_image_src($field['image_id'], 'full');
                $hashform_image = isset($hashform_image[0]) ? $hashform_image[0] : '';
            }
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Select Image', 'hash-form'); ?></label>
                <div class="hf-image-preview">
                    <input type="hidden" class="hf-image-id" name="field_options[image_id_<?php echo esc_attr($field_id); ?>]" id="hf-field-image-<?php echo absint($field_id); ?>" value="<?php echo esc_attr($hashform_image_id); ?>" />

                    <div class="hf-image-preview-wrap<?php echo ($hashform_image ? '' : ' hf-hidden'); ?>">
                        <div class="hf-image-preview-box">
                            <?php // src is only printed when there is one: an empty src makes the browser re-request the page. ?>
                            <img id="hf-image-preview-<?php echo absint($field_id); ?>" alt="" <?php echo $hashform_image ? 'src="' . esc_url($hashform_image) . '"' : ''; ?> />
                        </div>

                        <div class="hf-image-actions">
                            <?php // A separate class from the empty-state button, which the picker hides once an image is set. ?>
                            <button type="button" class="button hf-replace-image">
                                <span class="mdi mdi-image-sync-outline" aria-hidden="true"></span>
                                <?php esc_html_e('Replace', 'hash-form'); ?>
                            </button>
                            <button type="button" class="button hf-remove-image">
                                <span class="mdi mdi-trash-can-outline" aria-hidden="true"></span>
                                <?php esc_html_e('Remove', 'hash-form'); ?>
                            </button>
                        </div>
                    </div>

                    <button type="button" class="button hf-choose-image<?php echo ($hashform_image ? ' hf-hidden' : ''); ?>">
                        <span class="mdi mdi-image-plus-outline" aria-hidden="true"></span>
                        <span class="hf-choose-image-label"><?php esc_html_e('Choose image', 'hash-form'); ?></span>
                        <span class="hf-choose-image-hint"><?php esc_html_e('From your media library', 'hash-form'); ?></span>
                    </button>
                </div>
            </div>

            <div class="hf-form-row">
                <label><?php esc_html_e('Image Size', 'hash-form'); ?></label>
                <?php $hashform_image_size = isset($field['image_size']) ? $field['image_size'] : 'full'; ?>
                <select name="field_options[image_size_<?php echo absint($field_id); ?>]">
                    <?php foreach (HashFormFieldImage::image_sizes() as $hashform_size => $hashform_size_label) { ?>
                        <option value="<?php echo esc_attr($hashform_size); ?>" <?php selected($hashform_image_size, $hashform_size); ?>>
                            <?php echo esc_html($hashform_size_label); ?>
                        </option>
                    <?php } ?>
                </select>
                <p class="description">
                    <?php esc_html_e('Which of the copies WordPress made to start from. Smaller screens are still served a smaller file whichever is chosen.', 'hash-form'); ?>
                </p>
            </div>

            <div class="hf-form-row">
                <label><?php esc_html_e('Alt Text', 'hash-form'); ?></label>
                <input type="text" name="field_options[image_alt_<?php echo absint($field_id); ?>]" value="<?php echo isset($field['image_alt']) ? esc_attr($field['image_alt']) : ''; ?>" />
                <p class="description">
                    <?php esc_html_e('What someone hears in place of the image. Leave empty to use the alt text set on the image in the media library; if that is empty too, the image is treated as decorative and skipped.', 'hash-form'); ?>
                </p>
            </div>
            <?php
        }

        if ($field_type === 'spacer') {
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Height (px)', 'hash-form'); ?></label>
                <input type="number" min="0" name="field_options[spacer_height_<?php echo absint($field_id); ?>]" value="<?php echo isset($field['spacer_height']) ? esc_attr($field['spacer_height']) : ''; ?>" data-changeheight="field_change_height_<?php echo absint($field_id) ?>" />
                <p class="description">
                    <?php esc_html_e('Left empty, the gap is 50px. Set 0 for no gap of its own.', 'hash-form'); ?>
                </p>
            </div>
            <?php
        }

        if ($field_type === 'time') {
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Step', 'hash-form'); ?></label>
                <input type="number" name="field_options[step_<?php echo absint($field_id); ?>]" value="<?php echo isset($field['step']) ? esc_attr($field['step']) : ''; ?>" min="1" />
                <p class="description">
                    <?php esc_html_e('Minutes between each time in the drop down. 15 lists 9:00 am, 9:15 am, 9:30 am and so on; 60 lists one time per hour.', 'hash-form'); ?>
                    <br /><?php esc_html_e('Leave empty to use 60. Smaller steps make a longer list, so 5 over a full day gives 288 options.', 'hash-form'); ?>
                </p>
            </div>
            <div class="hf-form-row">
                <label><?php esc_html_e('Min Time', 'hash-form'); ?></label>
                <input type="text" class="min-value-field" placeholder="09:00" name="field_options[min_time_<?php echo absint($field_id); ?>]" value="<?php echo isset($field['min_time']) ? esc_attr($field['min_time']) : ''; ?>" />
                <p class="description">
                    <?php esc_html_e('Earliest time the drop down offers, as 24 hour HH:MM. 09:00 is nine in the morning, 17:00 is five in the afternoon.', 'hash-form'); ?>
                </p>
            </div>
            <div class="hf-form-row">
                <label><?php esc_html_e('Max Time', 'hash-form'); ?></label>
                <input type="text" class="max-value-field" placeholder="17:00" name="field_options[max_time_<?php echo absint($field_id); ?>]" value="<?php echo isset($field['max_time']) ? esc_attr($field['max_time']) : ''; ?>" />
                <p class="description">
                    <?php esc_html_e('Latest time offered, in the same format. Leave both empty to offer the whole day.', 'hash-form'); ?>
                </p>
            </div>
            <?php
        }

        if ($field_type === 'date') {
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Date Format', 'hash-form'); ?></label>
                <select name="field_options[date_format_<?php echo esc_attr($field_id); ?>]">
                    <option value="MM dd, yy" <?php isset($field['date_format']) ? selected($field['date_format'], 'MM dd, yy') : ''; ?>>
                        September 19, 2023
                    </option>
                    <option value="yy-mm-dd" <?php isset($field['date_format']) ? selected($field['date_format'], 'yy-mm-dd') : ''; ?>>
                        2023-09-19
                    </option>
                    <option value="mm/dd/yy" <?php isset($field['date_format']) ? selected($field['date_format'], 'mm/dd/yy') : ''; ?>>
                        09/19/2023
                    </option>
                    <option value="dd/mm/yy" <?php isset($field['date_format']) ? selected($field['date_format'], 'dd/mm/yy') : ''; ?>>
                        19/09/2023
                    </option>
                </select>
            </div>
            <?php
        }

        if ($field_type === 'hidden') {
            $hf_sources = HashFormFieldHidden::value_sources();
            $hf_source = HashFormFieldHidden::source_from_options($field);
            $hf_param_id = 'hf-hidden-source-param-' . absint($field_id);
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Value Source', 'hash-form'); ?></label>
                <select name="field_options[value_source_<?php echo absint($field_id); ?>]" data-condition="toggle" id="<?php echo esc_attr($hf_param_id); ?>" data-condition-value="url_param">
                    <?php foreach ($hf_sources as $hf_value => $hf_label) { ?>
                        <option value="<?php echo esc_attr($hf_value); ?>" <?php selected($hf_source, $hf_value); ?>>
                            <?php echo esc_html($hf_label); ?>
                        </option>
                    <?php } ?>
                </select>
                <p class="description">
                    <?php esc_html_e('The value is worked out on the server when the form is submitted, so it cannot be changed from the browser.', 'hash-form'); ?>
                    <br /><?php esc_html_e('The page and URL parameter sources describe the address the visitor submitted from, which their browser supplies. Good for knowing where a lead came from; not proof of it.', 'hash-form'); ?>
                </p>
            </div>

            <div class="hf-form-row">
                <label><?php esc_html_e('URL Parameter Name', 'hash-form'); ?></label>
                <input type="text" placeholder="utm_source" name="field_options[value_param_<?php echo absint($field_id); ?>]" value="<?php echo isset($field['value_param']) ? esc_attr($field['value_param']) : ''; ?>" />
                <p class="description">
                    <?php esc_html_e('Only used when the source above is a URL parameter. For example, entering utm_source records "newsletter" from a link ending ?utm_source=newsletter. Stored empty when the parameter is not in the address.', 'hash-form'); ?>
                </p>
            </div>
            <?php
        }

        if ($field_type === 'user_id') {
            $hf_capture_choices = HashFormFieldUserID::capture_choices();
            $hf_capture = HashFormFieldUserID::capture_from_options($field);
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Show As', 'hash-form'); ?></label>
                <select name="field_options[capture_<?php echo absint($field_id); ?>]">
                    <?php foreach ($hf_capture_choices as $hf_value => $hf_label) { ?>
                        <option value="<?php echo esc_attr($hf_value); ?>" <?php selected($hf_capture, $hf_value); ?>>
                            <?php echo esc_html($hf_label); ?>
                        </option>
                    <?php } ?>
                </select>
                <p class="description">
                    <?php esc_html_e('How the submitter appears in the entry and in notification emails. The account ID is always what gets stored, so a later rename or address change does not leave old entries pointing at something stale.', 'hash-form'); ?>
                    <br /><?php esc_html_e('Someone who is not logged in is recorded as Guest.', 'hash-form'); ?>
                </p>
            </div>
            <?php
        }

        if ($field_type === 'textarea') {
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Rows', 'hash-form'); ?></label>
                <input type="number" name="field_options[rows_<?php echo absint($field_id); ?>]" value="<?php echo (isset($field['rows']) ? esc_attr($field['rows']) : ''); ?>" data-changerows="<?php echo esc_attr($this->html_id()); ?>" />
            </div>
            <?php
        }

        if ($field_type === 'separator') {
            // Looped, like the heading levels: six near-identical blocks, none
            // of which fell back to the default when nothing was saved.
            $border_style = isset($field['border_style']) ? $field['border_style'] : 'solid';
            $border_labels = array(
                'solid' => esc_html__('Solid', 'hash-form'),
                'double' => esc_html__('Double', 'hash-form'),
                'dotted' => esc_html__('Dotted', 'hash-form'),
                'dashed' => esc_html__('Dashed', 'hash-form'),
                'groove' => esc_html__('Groove', 'hash-form'),
                'ridge' => esc_html__('Ridge', 'hash-form'),
            );
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Divider Type', 'hash-form'); ?></label>
                <select name="field_options[border_style_<?php echo esc_attr($field_id); ?>]" data-changebordertype="field_change_style_<?php echo esc_attr($field_id) ?>">
                    <?php foreach (HashFormFieldSeparator::border_styles() as $style_value) { ?>
                        <option value="<?php echo esc_attr($style_value); ?>" <?php selected($border_style, $style_value); ?>>
                            <?php echo esc_html($border_labels[$style_value]); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="hf-form-row">
                <label><?php esc_html_e('Divider Height (px)', 'hash-form'); ?></label>
                <input type="number" min="0" name="field_options[border_width_<?php echo absint($field_id); ?>]" value="<?php echo (isset($field['border_width']) ? esc_attr($field['border_width']) : ''); ?>" data-changeborderwidth="field_change_style_<?php echo absint($field_id) ?>" />
                <p class="description">
                    <?php esc_html_e('Left empty, the line is 2px. Double needs at least 3px to show as two lines.', 'hash-form'); ?>
                </p>
            </div>

            <div class="hf-form-row">
                <label><?php esc_html_e('Spacing (px)', 'hash-form'); ?></label>
                <input type="number" min="0" name="field_options[separator_spacing_<?php echo absint($field_id); ?>]" value="<?php echo (isset($field['separator_spacing']) ? esc_attr($field['separator_spacing']) : ''); ?>" data-changeseparatorspacing="field_change_style_<?php echo absint($field_id) ?>" />
                <p class="description">
                    <?php esc_html_e('Room above and below the line, on top of the gap the form already leaves between fields. Leave empty for none.', 'hash-form'); ?>
                </p>
            </div>
            <?php
        }

        if ($display['required']) {
            ?>
            <div class="hf-form-row">
                <label for="hf-req-field-<?php echo absint($field_id); ?>">
                    <input type="checkbox" class="hf-form-field-required" id="hf-req-field-<?php echo absint($field_id); ?>" name="field_options[required_<?php echo absint($field_id); ?>]" value="1" <?php checked($field['required'], 1); ?> />
                    <?php esc_html_e('Required', 'hash-form'); ?>
                </label>
            </div>
            <?php
        }

        if ($display['range']) {
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Number Range', 'hash-form'); ?></label>
                <div class="hf-grid-container">
                    <div class="hf-form-row hf-grid-2">
                        <label><?php esc_html_e('From', 'hash-form'); ?></label>
                        <input type="number" name="field_options[minnum_<?php echo absint($field_id); ?>]" value="<?php echo esc_attr($field['minnum']); ?>" data-changeme="hf-field-<?php echo esc_attr($field['field_key']); ?>" data-changeatt="min" <?php echo ($field_type === 'range_slider' ? 'data-changemin="field_change_min_' . esc_attr($field['field_key']) . '"' : ''); ?> />
                    </div>

                    <div class="hf-form-row hf-grid-2">
                        <label><?php esc_html_e('To', 'hash-form'); ?></label>
                        <input type="number" name="field_options[maxnum_<?php echo absint($field_id); ?>]" value="<?php echo esc_attr($field['maxnum']); ?>" data-changeme="hf-field-<?php echo esc_attr($field['field_key']); ?>" data-changeatt="max" <?php echo ($field_type === 'range_slider' ? 'data-changemax="field_change_max_' . esc_attr($field['field_key']) . '"' : ''); ?> />
                    </div>

                    <div class="hf-form-row hf-grid-2">
                        <label><?php esc_html_e('Step', 'hash-form'); ?></label>
                        <input type="number" name="field_options[step_<?php echo absint($field_id); ?>]" value="<?php echo esc_attr($field['step']); ?>" data-changeatt="step" data-changeme="hf-field-<?php echo esc_attr($field['field_key']); ?>" />
                    </div>
                </div>
            </div>
            <?php
        }

        $this->show_primary_options();

        if ($field_type === 'upload') {
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Upload Label', 'hash-form'); ?></label>
                <input type="text" name="field_options[upload_label_<?php echo absint($field_id); ?>]" value="<?php echo esc_attr($field['upload_label']); ?>" data-changeme="hf-editor-upload-label-text-<?php echo absint($field_id); ?>" />
            </div>

            <div class="hf-form-row">
                <label><?php esc_html_e('Extensions', 'hash-form'); ?></label>
                <input type="text" name="field_options[extensions_<?php echo absint($field_id); ?>]" value="<?php echo esc_attr($field['extensions']); ?>" />
                <label class="hf-field-desc"><?php esc_html_e('Comma separated. The allowed extensions are pdf, doc, docx, xls, xlsx, odt, ppt, pptx, pps, ppsx, jpg, jpeg, png, gif, bmp, webp, avif, heic, heif, mp3, m4a, mp4, ogg, wav, m4v, mov, wmv, avi, mpg, ogv, webm, 3gp, txt, zip, rar, 7z, csv', 'hash-form'); ?></label>
                <p class="description">
                    <?php esc_html_e('Whatever you list here also has to be a file type this WordPress site accepts. A type the site itself blocks is refused on upload even if it appears above.', 'hash-form'); ?>
                </p>
            </div>

            <div class="hf-form-row">
                <label><?php esc_html_e('Maximum File Size Allowed to Upload (MB)', 'hash-form'); ?></label>
                <input type="number" name="field_options[max_upload_size_<?php echo absint($field_id); ?>]" value="<?php echo esc_attr($field['max_upload_size']); ?>" min="1" />
                <p class="description">
                    <?php
                    /* translators: %s: the server's own upload limit, for example "64 MB". */
                    printf(esc_html__('Your server accepts at most %s, whatever is set here.', 'hash-form'), esc_html(size_format(wp_max_upload_size())));
                    ?>
                </p>
            </div>

            <div class="hf-form-row">
                <label><?php esc_html_e('Minimum File Size Allowed to Upload (KB)', 'hash-form'); ?></label>
                <?php // isset: fields saved before this option existed have no such key until their next save. ?>
                <input type="number" name="field_options[min_upload_size_<?php echo absint($field_id); ?>]" value="<?php echo isset($field['min_upload_size']) ? esc_attr($field['min_upload_size']) : ''; ?>" min="0" />
                <p class="description">
                    <?php esc_html_e('Rejects files smaller than this, which is the quickest way to catch an empty or truncated file. Leave empty for no minimum.', 'hash-form'); ?>
                </p>
            </div>

            <div class="hf-form-row">
                <label>
                    <input type="hidden" name="field_options[multiple_uploads_<?php echo absint($field_id); ?>]" value="off" />
                    <input type="checkbox" name="field_options[multiple_uploads_<?php echo absint($field_id); ?>]" value="on" data-condition="toggle" id="hf-multiple-uploads-<?php echo absint($field_id); ?>" <?php checked($field['multiple_uploads'], 'on'); ?> />
                    <?php esc_html_e('Multiple Uploads', 'hash-form'); ?>
                </label>
            </div>

            <div class="hf-form-row" data-condition-toggle="hf-multiple-uploads-<?php echo absint($field_id); ?>">
                <label>
                    <?php esc_html_e('Multiple Uploads Limit', 'hash-form'); ?>
                    <input type="number" name="field_options[multiple_uploads_limit_<?php echo absint($field_id); ?>]" value="<?php echo esc_attr($field['multiple_uploads_limit']); ?>" />
                </label>
            </div>
            <?php
        }

        if ($display['css']) {
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('CSS Classes', 'hash-form'); ?></label>
                <input type="text" name="field_options[classes_<?php echo absint($field_id); ?>]" value="<?php echo isset($field['classes']) ? esc_attr($field['classes']) : ''; ?>" />
            </div>
            <?php
        }

        if (in_array($field_type, apply_filters('hash_form_choices_fields', array('select', 'radio', 'checkbox', 'image_select')))) {
            $this->show_field_choices();
        }

        if ($display['auto_width']) {
            ?>
            <div class="hf-form-row">
                <label>
                    <input type="hidden" name="field_options[auto_width_<?php echo absint($field_id); ?>]" value="off" />
                    <input type="checkbox" name="field_options[auto_width_<?php echo absint($field_id); ?>]" value="on" <?php checked($field['auto_width'], 'on'); ?> />
                    <?php esc_html_e('Automatic Width', 'hash-form'); ?>
                </label>
            </div>
            <?php
        }

        if ($display['default']) {
            $field_type_attr_val = 'text';
            if ($field_type == 'range_slider' || $field_type == 'number' || $field_type == 'spinner') {
                $field_type_attr_val = 'number';
            }

            if ($field_type == 'email') {
                $field_type_attr_val = 'email';
            }
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Default Value', 'hash-form'); ?></label>
                <input type="<?php echo esc_attr($field_type_attr_val); ?>" name="<?php echo 'default_value_' . absint($field_id); ?>" value="<?php echo esc_attr($field['default_value']); ?>" class="hf-default-value-field" data-changeme="hf-field-<?php echo esc_attr($field['field_key']); ?>" data-changeatt="value" />
            </div>
            <?php
        }

        $this->show_after_default();

        if ($display['clear_on_focus']) {
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Placeholder', 'hash-form'); ?></label>
                <?php
                if ($field_type === 'textarea') {
                    ?>
                    <textarea id="hf-placeholder-<?php echo absint($field_id); ?>" name="field_options[placeholder_<?php echo absint($field_id); ?>]" rows="3" data-changeme="hf-field-<?php echo esc_attr($field['field_key']); ?>" data-changeatt="placeholder"><?php echo isset($field['placeholder']) ? esc_textarea($field['placeholder']) : ''; ?></textarea>
                    <?php
                } else {
                    ?>
                    <input id="hf-placeholder-<?php echo absint($field_id); ?>" type="text" name="field_options[placeholder_<?php echo absint($field_id); ?>]" value="<?php echo isset($field['placeholder']) ? esc_attr($field['placeholder']) : ''; ?>" data-changeme="hf-field-<?php echo esc_attr($field['field_key']); ?>" data-changeatt="placeholder" />
                    <?php
                }
                ?>
            </div>
            <?php
        }

        if ($display['description']) {
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Field Description', 'hash-form'); ?></label>
                <textarea name="field_options[description_<?php echo absint($field_id); ?>]" data-changeme="hf-field-desc-<?php echo absint($field_id); ?>"><?php echo isset($field['description']) ? esc_textarea($field['description']) : ''; ?></textarea>
            </div>
            <?php
        }

        if ($display['format']) {
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Format', 'hash-form'); ?></label>
                <input type="text" class="hf-format-input" value="<?php echo isset($field['format']) ? esc_attr($field['format']) : ''; ?>" name="field_options[format_<?php echo absint($field_id); ?>]" data-fid="<?php echo absint($field_id); ?>" />

                <?php
                /*
                 * Offered rather than imposed. The pattern is written for
                 * numbers ending in a four digit group, which is not how much
                 * of the world writes a phone number, so switching it on for
                 * every phone field would start rejecting values that submit
                 * happily today.
                 */
                if ('phone' === $field_type) {
                    ?>
                    <p class="hf-format-presets">
                        <button type="button" class="hf-format-preset" data-format="<?php echo esc_attr(HashFormFieldPhone::default_phone_pattern()); ?>">
                            <?php esc_html_e('Use the standard phone pattern', 'hash-form'); ?>
                        </button>
                        <button type="button" class="hf-format-preset hf-format-clear" data-format="">
                            <?php esc_html_e('Clear', 'hash-form'); ?>
                        </button>
                    </p>
                    <?php
                }
                ?>

                <p class="description">
                    <?php esc_html_e('Enter a Regex Format to validate.', 'hash-form'); ?>
                    <a href="https://www.phpliveregex.com" target="_blank"><?php esc_html_e('Generate Regex', 'hash-form'); ?></a>
                    <?php if ('phone' === $field_type) { ?>
                        <br /><?php esc_html_e('Leave empty to accept any value, which suits international numbers.', 'hash-form'); ?>
                    <?php } ?>
                </p>
            </div>
            <?php
        }

        if ($display['required']) {
            ?>
            <div class="hf-form-row hf-grid-3 hf-required-detail-<?php echo esc_attr($field_id) . ($field['required'] ? '' : ' hf-hidden'); ?>">
                <label><?php esc_html_e('Required Field Indicator', 'hash-form'); ?></label>
                <input type="text" name="field_options[required_indicator_<?php echo absint($field_id); ?>]" value="<?php echo isset($field['required_indicator']) ? esc_attr($field['required_indicator']) : '*'; ?>" data-changeme="hf-editor-field-required-<?php echo absint($field_id); ?>" />
            </div>
            <?php
        }

        if ($field_type === 'radio' || $field_type === 'checkbox' || $field_type === 'image_select') {
            ?>
            <div class="hf-form-row hf-grid-3">
                <label><?php esc_html_e('Options Layout', 'hash-form'); ?></label>
                <select name="field_options[options_layout_<?php echo absint($field_id); ?>]">
                    <option value="inline" <?php selected($field['options_layout'], 'inline'); ?>>
                        <?php esc_html_e('Inline', 'hash-form'); ?>
                    </option>
                    <option value="1" <?php selected($field['options_layout'], '1'); ?>>
                        <?php esc_html_e('1 Column', 'hash-form'); ?>
                    </option>
                    <option value="2" <?php selected($field['options_layout'], '2'); ?>>
                        <?php esc_html_e('2 Columns', 'hash-form'); ?>
                    </option>
                    <option value="3" <?php selected($field['options_layout'], '3'); ?>>
                        <?php esc_html_e('3 Columns', 'hash-form'); ?>
                    </option>
                    <option value="4" <?php selected($field['options_layout'], '4'); ?>>
                        <?php esc_html_e('4 Columns', 'hash-form'); ?>
                    </option>
                    <option value="5" <?php selected($field['options_layout'], '5'); ?>>
                        <?php esc_html_e('5 Columns', 'hash-form'); ?>
                    </option>
                    <option value="6" <?php selected($field['options_layout'], '6'); ?>>
                        <?php esc_html_e('6 Columns', 'hash-form'); ?>
                    </option>
                </select>
            </div>
            <?php
        }

        if ($display['max']) {
            ?>
            <div class="hf-form-row hf-grid-3">
                <label><?php esc_html_e('Max Characters', 'hash-form'); ?></label>
                <input type="number" name="field_options[max_<?php echo esc_attr($field_id); ?>]" value="<?php echo isset($field['max']) ? esc_attr($field['max']) : ''; ?>" size="5" data-fid="<?php echo absint($field_id); ?>" />
            </div>
            <?php
        }

        if ($display['max_width']) {
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Field Max Width', 'hash-form'); ?></label>
                <div class="hf-form-input-unit">
                    <input type="number" name="field_options[field_max_width_<?php echo esc_attr($field_id); ?>]" value="<?php echo (isset($field['field_max_width']) ? esc_attr($field['field_max_width']) : ''); ?>" />

                    <select name="field_options[field_max_width_unit_<?php echo esc_attr($field_id); ?>]">
                        <option value="%" <?php isset($field['field_max_width_unit']) ? selected($field['field_max_width_unit'], '%') : ''; ?>>
                            <?php echo '%'; ?>
                        </option>
                        <option value="px" <?php isset($field['field_max_width_unit']) ? selected($field['field_max_width_unit'], 'px') : ''; ?>>
                            <?php esc_html_e('px', 'hash-form'); ?>
                        </option>
                    </select>
                </div>
            </div>
            <?php
        }

        if ($display['image_max_width']) {
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Image Max Width', 'hash-form'); ?></label>
                <div class="hf-form-input-unit">
                    <input type="number" name="field_options[image_max_width_<?php echo esc_attr($field_id); ?>]" value="<?php echo (isset($field['image_max_width']) ? esc_attr($field['image_max_width']) : ''); ?>" />

                    <select name="field_options[image_max_width_unit_<?php echo esc_attr($field_id); ?>]">
                        <option value="%" <?php isset($field['image_max_width_unit']) ? selected($field['image_max_width_unit'], '%') : ''; ?>>
                            <?php echo '%'; ?>
                        </option>
                        <option value="px" <?php isset($field['image_max_width_unit']) ? selected($field['image_max_width_unit'], 'px') : ''; ?>>
                            <?php esc_html_e('px', 'hash-form'); ?>
                        </option>
                    </select>
                </div>
            </div>
            <?php
        }

        if ($display['field_alignment']) {
            $field_alignment = isset($field['field_alignment']) ? esc_attr($field['field_alignment']) : '';
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Field Alignment', 'hash-form'); ?></label>
                <select name="field_options[field_alignment_<?php echo esc_attr($field_id); ?>]">
                    <option value="left" <?php selected($field_alignment, 'left'); ?>>
                        <?php esc_html_e('Left', 'hash-form'); ?>
                    </option>
                    <option value="right" <?php selected($field_alignment, 'right'); ?>>
                        <?php esc_html_e('Right', 'hash-form'); ?>
                    </option>
                    <option value="center" <?php selected($field_alignment, 'center'); ?>>
                        <?php esc_html_e('Center', 'hash-form'); ?>
                    </option>
                </select>
                <label class="hf-field-desc"><?php esc_html_e('This option will only work if the Field Max Width is set and width is smaller than container.', 'hash-form'); ?></label>
            </div>
            <?php
        }

        if (!empty($display['advanced_validation'])) {
            // Other fields on this form that a confirmation field could match.
            $match_options = array();

            if (!empty($field['form_id'])) {
                // The builder renders one settings panel per field, so without
                // this the same form-fields query runs once for every field.
                static $form_fields_cache = array();

                if (!isset($form_fields_cache[$field['form_id']])) {
                    $form_fields_cache[$field['form_id']] = HashFormFields::get_form_fields($field['form_id']);
                }

                /*
                 * Only fields this one could ever equal. Offering an email
                 * field the choice of matching a phone field produced a rule
                 * nothing could satisfy: a value that passes email validation
                 * is not one anybody would type into a phone field, so the
                 * form could never be submitted and the error looked like a
                 * bug in the matching itself.
                 *
                 * Two fields are compatible when they are the same type, or
                 * when one of them puts no format constraint on its value.
                 */
                $unconstrained = array('text', 'textarea');
                $matchable = array_merge($unconstrained, array('email', 'url', 'phone', 'number'));

                foreach ($form_fields_cache[$field['form_id']] as $other_field) {
                    if ($other_field->id == $field_id) {
                        continue;
                    }

                    if (!in_array($other_field->type, $matchable, true)) {
                        continue;
                    }

                    $compatible = $other_field->type === $field_type
                            || in_array($other_field->type, $unconstrained, true)
                            || in_array($field_type, $unconstrained, true);

                    if (!$compatible) {
                        continue;
                    }

                    $match_options[$other_field->id] = $other_field->name;
                }
            }
            ?>
            <h4><?php esc_html_e('Advanced Validation', 'hash-form'); ?></h4>

            <div class="hf-form-row hf-grid-3">
                <label><?php esc_html_e('Min Characters', 'hash-form'); ?></label>
                <input type="number" min="0" step="1" name="field_options[min_length_<?php echo esc_attr($field_id); ?>]" value="<?php echo isset($field['min_length']) ? esc_attr($field['min_length']) : ''; ?>" size="5" />
            </div>

            <div class="hf-form-row">
                <label><?php esc_html_e('Pattern', 'hash-form'); ?></label>
                <input type="text" name="field_options[pattern_<?php echo esc_attr($field_id); ?>]" value="<?php echo isset($field['pattern']) ? esc_attr($field['pattern']) : ''; ?>" placeholder="<?php echo esc_attr('^[A-Z]{2}[0-9]{4}$'); ?>" />
                <label class="hf-field-desc"><?php esc_html_e('A regular expression the value must match, without delimiters. Leave empty for no pattern.', 'hash-form'); ?></label>
            </div>

            <div class="hf-form-row">
                <label><?php esc_html_e('Pattern Error Message', 'hash-form'); ?></label>
                <input type="text" name="field_options[pattern_message_<?php echo esc_attr($field_id); ?>]" value="<?php echo isset($field['pattern_message']) ? esc_attr($field['pattern_message']) : ''; ?>" />
            </div>

            <?php if ($match_options) { ?>
                <div class="hf-form-row">
                    <label><?php esc_html_e('Must Match Field', 'hash-form'); ?></label>
                    <select name="field_options[match_field_<?php echo esc_attr($field_id); ?>]">
                        <option value=""><?php esc_html_e('— None —', 'hash-form'); ?></option>
                        <?php foreach ($match_options as $option_id => $option_label) { ?>
                            <option value="<?php echo esc_attr($option_id); ?>" <?php selected(isset($field['match_field']) ? $field['match_field'] : '', $option_id); ?>>
                                <?php echo esc_html($option_label); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <label class="hf-field-desc"><?php esc_html_e('Use for confirm email or confirm password fields.', 'hash-form'); ?></label>
                </div>

                <div class="hf-form-row">
                    <label><?php esc_html_e('Match Error Message', 'hash-form'); ?></label>
                    <input type="text" name="field_options[match_message_<?php echo esc_attr($field_id); ?>]" value="<?php echo isset($field['match_message']) ? esc_attr($field['match_message']) : ''; ?>" />
                </div>
            <?php } ?>

            <div class="hf-form-row">
                <label><?php esc_html_e('No Duplicates', 'hash-form'); ?></label>
                <input type="hidden" name="field_options[unique_<?php echo esc_attr($field_id); ?>]" value="" />
                <label>
                    <input type="checkbox" name="field_options[unique_<?php echo esc_attr($field_id); ?>]" value="on" <?php checked(isset($field['unique']) ? $field['unique'] : '', 'on'); ?> />
                    <?php esc_html_e('Reject a value that has already been submitted to this form.', 'hash-form'); ?>
                </label>
            </div>

            <div class="hf-form-row">
                <label><?php esc_html_e('Duplicate Error Message', 'hash-form'); ?></label>
                <input type="text" name="field_options[unique_message_<?php echo esc_attr($field_id); ?>]" value="<?php echo isset($field['unique_message']) ? esc_attr($field['unique_message']) : ''; ?>" />
            </div>
            <?php
        }

        $has_validation = ($display['invalid'] || $display['required']);
        $has_invalid = $display['invalid'];

        if ($field_type === 'upload') {
            $has_validation = true;
            $has_invalid = true;
        }

        if ($has_validation) {
            ?>
            <h4 class="hf-validation-header <?php echo ($has_invalid ? 'hf-alway-show' : ($field['required'] ? '' : ' hf-hidden')); ?>"> <?php esc_html_e('Validation Messages', 'hash-form'); ?></h4>
            <?php
        }

        if ($display['required']) {
            ?>
            <div class="hf-form-row hf-required-detail-<?php echo esc_attr($field_id) . ($field['required'] ? '' : ' hf-hidden'); ?>">
                <label><?php esc_html_e('Required', 'hash-form'); ?></label>
                <input type="text" name="field_options[blank_<?php echo esc_attr($field_id); ?>]" value="<?php echo esc_attr($field['blank']); ?>" />
            </div>
            <?php
        }

        if ($display['invalid']) {
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Invalid Format', 'hash-form'); ?></label>
                <input type="text" name="field_options[invalid_<?php echo esc_attr($field_id); ?>]" value="<?php echo isset($field['invalid']) ? esc_attr($field['invalid']) : ''; ?>" />
            </div>
            <?php
        }


        if ($field_type === 'upload') {
            ?>
            <div class="hf-form-row">
                <label><?php esc_html_e('Extensions', 'hash-form'); ?></label>
                <input type="text" name="field_options[extensions_error_message_<?php echo absint($field_id); ?>]" value="<?php echo esc_attr($field['extensions_error_message']); ?>" />
            </div>

            <div class="hf-form-row" data-condition-toggle="hf-multiple-uploads-<?php echo absint($field_id); ?>">
                <label><?php esc_html_e('Multiple Uploads', 'hash-form'); ?></label>
                <input type="text" name="field_options[multiple_uploads_error_message_<?php echo absint($field_id); ?>]" value="<?php echo esc_attr($field['multiple_uploads_error_message']); ?>" />
            </div>
            <?php
        }
        ?>
    </div>
</div>