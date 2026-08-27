<?php
defined('ABSPATH') || die();
/*
 * A template, included from inside a class method - never loaded on its own.
 * The variables below are locals of the method that includes it, not globals,
 * which is what the prefix sniff assumes about a file-scope assignment.
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from within a method, so these are function locals.
?>

<div class="hf-fields-sidebar">
    <div class="hf-fields-container">
        <ul id="hf-fields-tabs" class="hf-fields-tabs">
            <li class="hf-active-tab"><a href="#hf-add-fields-panel" id="hf-add-fields-tab"><?php esc_html_e('Add Fields', 'hash-form'); ?></a></li>
            <li><a href="#hf-options-panel" id="hf-options-tab"><?php esc_html_e('Field Options', 'hash-form'); ?></a></li>
            <li><a href="#hf-meta-panel" id="hf-design-tab"><?php esc_html_e('Form Title', 'hash-form'); ?></a></li>
        </ul>

        <div class="hf-fields-panels">
            <div id="hf-add-fields-panel" class="ht-fields-panel">
                <?php
                HashFormHelper::show_search_box(array(
                    'input_id' => 'field-list',
                    'placeholder' => esc_html__('Search Fields', 'hash-form'),
                    'tosearch' => 'hf-field-box',
                ));
                ?>

                <div class="hf-layout-section">
                    <h4 class="hf-layout-heading"><?php esc_html_e('Columns', 'hash-form'); ?></h4>
                    <p class="hf-layout-help"><?php esc_html_e('Add a row, then drag fields into its columns.', 'hash-form'); ?></p>
                    <ul class="hf-columns-list">
                        <?php
                        // Only divisors of the 12 column grid, so every column
                        // comes out an equal width.
                        foreach (array(2, 3, 4, 6) as $columns) {
                            ?>
                            <li class="hf-column-box">
                                <a href="#" class="hf-add-columns" data-columns="<?php echo absint($columns); ?>"
                                   title="<?php
                                   /* translators: 1: number of columns */
                                   echo esc_attr(sprintf(_n('%s Column', '%s Columns', $columns, 'hash-form'), $columns));
                                   ?>">
                                       <span class="hf-column-preview" aria-hidden="true">
                                           <?php for ($i = 0; $i < $columns; $i++) { ?>
                                            <span></span>
                                        <?php } ?>
                                    </span>
                                </a>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                </div>

                <div class="hf-layout-section hf-fields-section">
                    <h4 class="hf-layout-heading"><?php esc_html_e('Fields', 'hash-form'); ?></h4>
                    <p class="hf-layout-help hf-drag-hint" id="hf-drag-hint">
                        <?php esc_html_e('Drag a field into your form to add it.', 'hash-form'); ?>
                    </p>

                    <?php
                    /*
                     * Grouped rather than one flat list: there are around fifty
                     * field types, and finding one in a single run of tiles
                     * meant scrolling past everything else.
                     *
                     * grouped_field_selection() applies the palette filter, so
                     * a type whose module is switched off is already gone.
                     */
                    $field_groups = HashFormFields::grouped_field_selection();
                    $first_group = true;

                    foreach ($field_groups as $group_key => $group) {
                        // The first group starts open, the rest closed, so the
                        // panel opens on the fields most forms begin with.
                        $open = $first_group;
                        $first_group = false;
                        ?>
                        <div class="hf-field-group<?php echo $open ? ' hf-open' : ''; ?>" data-group="<?php echo esc_attr($group_key); ?>">
                            <button type="button" class="hf-field-group-toggle" aria-expanded="<?php echo $open ? 'true' : 'false'; ?>">
                                <span class="hf-field-group-name"><?php echo esc_html($group['name']); ?></span>
                                <span class="hf-field-group-count"><?php echo esc_html(number_format_i18n(count($group['fields']))); ?></span>
                                <span class="hf-field-group-chevron" aria-hidden="true"></span>
                            </button>

                            <ul class="hf-fields-list">
                                <?php
                                foreach ($group['fields'] as $field_key => $field_type) {
                                    ?>
                                    <li class="hf-field-box hashform_<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" data-field-name="<?php echo esc_attr(strtolower($field_type['name'])); ?>">
                                        <a class="hf-drag-field" title="<?php
                                        /* translators: 1: field name */
                                        echo esc_attr(sprintf(__('Drag %s into your form', 'hash-form'), $field_type['name']));
                                        ?>">
                                            <?php
                                            $field_icon = HashFormFieldIcons::render($field_key);

                                            if ($field_icon) {
                                                echo wp_kses($field_icon, HashFormFieldIcons::allowed_svg());
                                            } else {
                                                // Anything that has not registered an svg keeps its icon font glyph.
                                                ?>
                                                <i class="<?php echo esc_attr($field_type['icon']); ?>"></i>
                                                <?php
                                            }
                                            ?>
                                            <span><?php echo esc_html($field_type['name']); ?></span>
                                        </a>
                                    </li>
                                    <?php
                                }
                                ?>
                            </ul>
                        </div>
                        <?php
                    }
                    ?>

                    <p class="hf-fields-empty" hidden><?php esc_html_e('No fields match your search.', 'hash-form'); ?></p>
                </div>
            </div>

            <div id="hf-options-panel" class="ht-fields-panel">
                <div class="hf-fields-settings">
                    <div class="hf-no-field-placeholder">
                        <div class="hf-no-field-msg"><?php esc_html_e('Select a field to see the options', 'hash-form'); ?></div>
                    </div>
                </div>

                <form method="post" id="hf-fields-form">
                    <input type="hidden" name="id" id="hf-form-id" value="<?php echo esc_attr($values['id']); ?>" />
                    <?php wp_nonce_field('hashform_save_form_nonce', 'hashform_save_form'); ?>
                    <input type="hidden" id="hf-end-form-marker" />
                </form>
            </div>

            <div id="hf-meta-panel" class="ht-fields-panel">
                <form method="post" id="hf-meta-form">
                    <div class="hf-form-container hf-grid-container">
                        <div class="hf-form-row">
                            <label><?php esc_html_e('Form Title', 'hash-form'); ?></label>
                            <input type="text" name="title" value="<?php echo esc_attr($values['name']); ?>">
                        </div>

                        <div class="hf-form-row">
                            <label>
                                <input type="checkbox" name="show_title" value="on" <?php isset($values['show_title']) ? checked($values['show_title'], 'on') : ''; ?> />
                                <?php esc_html_e('Show the form title', 'hash-form'); ?>
                            </label>
                        </div>

                        <div class="hf-form-row">
                            <label><?php esc_html_e('Form Description', 'hash-form'); ?></label>
                            <textarea name="description"><?php echo esc_textarea($values['description']); ?></textarea>
                        </div>

                        <div class="hf-form-row">
                            <label>
                                <input type="checkbox" name="show_description" value="on" <?php isset($values['show_description']) ? checked($values['show_description'], 'on') : ''; ?> />
                                <?php esc_html_e('Show the form description', 'hash-form'); ?>
                            </label>
                        </div>

                        <div class="hf-form-row">
                            <label><?php esc_html_e('Submit Button Text', 'hash-form'); ?></label>
                            <input type="text" name="submit_value" value="<?php echo isset($values['submit_value']) ? esc_attr($values['submit_value']) : ''; ?>" data-changeme="hf-editor-submit-button">
                        </div>

                        <div class="hf-form-row">
                            <label><?php esc_html_e('Form CSS Class', 'hash-form'); ?></label>
                            <input type="text" name="form_css_class" value="<?php echo isset($values['form_css_class']) ? esc_attr($values['form_css_class']) : ''; ?>">
                        </div>

                        <div class="hf-form-row">
                            <label><?php esc_html_e('Submit Button CSS Class', 'hash-form'); ?></label>
                            <input type="text" name="submit_btn_css_class" value="<?php echo isset($values['submit_btn_css_class']) ? esc_attr($values['submit_btn_css_class']) : ''; ?>">
                        </div>

                        <div class="hf-form-row">
                            <label><?php esc_html_e('Submit Button Alignment', 'hash-form'); ?></label>
                            <select name="submit_btn_alignment">
                                <option value="left" <?php isset($values['submit_btn_alignment']) ? selected($values['submit_btn_alignment'], 'left') : ''; ?>>
                                    <?php esc_html_e('Left', 'hash-form'); ?>
                                </option>
                                <option value="right" <?php isset($values['submit_btn_alignment']) ? selected($values['submit_btn_alignment'], 'right') : ''; ?>>
                                    <?php esc_html_e('Right', 'hash-form'); ?>
                                </option>
                                <option value="center" <?php isset($values['submit_btn_alignment']) ? selected($values['submit_btn_alignment'], 'center') : ''; ?>>
                                    <?php esc_html_e('Center', 'hash-form'); ?>
                                </option>
                                <option value="stretch" <?php isset($values['submit_btn_alignment']) ? selected($values['submit_btn_alignment'], 'center') : ''; ?>>
                                    <?php esc_html_e('Stretch', 'hash-form'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </form>

                <div class="hf-hidden">
                    <?php wp_editor('', 'hf-init-tinymce'); ?>
                </div>
            </div>
        </div>
    </div>
</div>