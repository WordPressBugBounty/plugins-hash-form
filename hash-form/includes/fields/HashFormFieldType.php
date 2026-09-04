<?php
defined('ABSPATH') || die();

abstract class HashFormFieldType {

    protected $field;
    protected $field_id = 0;
    protected $type;

    public function __construct($field = 0, $type = '') {
        $this->field = $field;
        $this->set_type($type);
        $this->set_field_id();
    }

    public function get_field() {
        return $this->field;
    }

    protected function set_type($type) {
        if (empty($this->type)) {
            $this->type = $this->get_field_column('type');

            if (empty($this->type) && !empty($type))
                $this->type = $type;
        }
    }

    protected function set_field_id() {
        if (empty($this->get_field()))
            return;

        $field = $this->get_field();

        if (is_array($field)) {
            $this->field_id = isset($field['id']) ? $field['id'] : 0;
        } else if (is_object($field) && property_exists($field, 'id')) {
            $this->field_id = $field->id;
        } elseif (is_numeric($field)) {
            $this->field_id = $field;
        }
    }

    public function get_field_column($column) {
        $field_val = '';
        if (is_object($this->field)) {
            $field_val = $this->field->{$column};
        } elseif (is_array($this->field) && isset($this->field[$column])) {
            $field_val = $this->field[$column];
        }
        return $field_val;
    }

    /* Form builder FrontEnd each elements */

    public function get_frontend_html() {
        $field = $this->get_field();
        $display = $this->display_field_settings();
        $settings = HashFormSettings::get_settings();
        ?>

        <div class="hf-field-container" style="<?php echo esc_attr($this->container_inner_style()); ?>">
            <?php
            $show_label = $display['label'] && !empty(trim($field['name'])) && (!($field['type'] == 'captcha' && $settings['re_type'] === 'v3'));
            $is_group = $this->is_group_field();
            $has_description = isset($display['description']) && $display['description'] && !empty(trim($field['description']));

            if ($show_label) {
                /*
                 * The label carries an id and, for a field that owns a single
                 * control, a matching for. Until now it had neither, so no
                 * label on the form was actually tied to the input beside it:
                 * a screen reader announced an unnamed edit box, and clicking
                 * a label did not focus its field. Fields that render a set of
                 * controls rather than one - radios, checkboxes, a name split
                 * into parts - cannot use for at all, and name the group
                 * below instead.
                 */
                ?>
                <label class="hf-field-label <?php echo (!$field['name'] || ((isset($field['hide_label']) && $field['hide_label']))) ? 'hf-hidden' : ''; ?>"
                       id="<?php echo esc_attr($this->label_id()); ?>"
                       <?php if (!$is_group) { ?>for="<?php echo esc_attr($this->html_id()); ?>"<?php } ?>>
                    <?php
                    echo esc_html(apply_filters('hashform_translate_string', $field['name'], 'Hash Form', HashFormBuilder::get_form_title($field['form_id']) . ' - ' . $field['id'] . ' - ' . 'Field Label'));
                    ?>
                    <?php if (!!$field['required']) { ?>
                        <span class="hf-field-required" aria-hidden="true">
                            <?php echo isset($field['required_indicator']) ? esc_html($field['required_indicator']) : '*'; ?>
                        </span>
                        <span class="hf-screen-reader-text"><?php esc_html_e('(required)', 'hash-form'); ?></span>
                    <?php } ?>
                </label>
            <?php } ?>
            <div class="hf-field-content"<?php
            if ($is_group && $show_label) {
                // A group takes its name from the label instead of a for.
                echo ' role="group" aria-labelledby="' . esc_attr($this->label_id()) . '"';
            }
            ?>>
                <?php
                $this->input_html();

                if ($has_description) {
                    ?>
                    <div class="hf-field-desc" id="<?php echo esc_attr($this->description_id()); ?>">
                        <?php
                        echo esc_html(apply_filters('hashform_translate_string', $field['description'], 'Hash Form', HashFormBuilder::get_form_title($field['form_id']) . ' - ' . $field['id'] . ' - ' . 'Field Description'));
                        ?>
                    </div>
                <?php } ?>
            </div>
        </div>
        <?php
    }

    private function container_classes_array() {
        $global_settings = HashFormSettings::get_settings();
        $field = $this->get_field();
        $container_class = array();
        $container_class[] = ($field['required'] != '0') ? 'hf-form-field-required' : '';
        $container_class[] = 'hashform-field-type-' . esc_attr($field['type']);
        $container_class[] = ($field['type'] == 'captcha' && $global_settings['re_type'] == 'v3' && !is_admin()) ? 'hf-recaptcha-v3 hf-hidden' : '';

        if (in_array($field['type'], array('heading', 'paragraph'))) {
            // 'inline' was the fallback, and no stylesheet has ever defined
            // hf-text-alignment-inline. Left is what the field itself defaults
            // to, so a field saved before the option existed reads the same.
            $text_alignment = isset($field['text_alignment']) && $field['text_alignment'] ? $field['text_alignment'] : 'left';
            $container_class[] = 'hf-text-alignment-' . trim($text_alignment);
        }

        if (in_array($field['type'], array('separator', 'image', 'heading', 'paragraph', 'html'))) {
            $field_alignment = isset($field['field_alignment']) ? $field['field_alignment'] : 'left';
            $container_class[] = 'hf-field-alignment-' . esc_attr($field_alignment);
        }

        if (!in_array($field['type'], array('separator', 'image', 'heading', 'paragraph', 'html'))) {
            $label_position = isset($field['label_position']) && $field['label_position'] ? $field['label_position'] : 'top';
            $label_alignment = isset($field['label_alignment']) && $field['label_alignment'] ? $field['label_alignment'] : 'left';
            $hide_label = isset($field['hide_label']) && $field['hide_label'] ? $field['hide_label'] : '';
            $container_class[] = 'hf-label-position-' . trim($label_position);
            $container_class[] = 'hf-label-alignment-' . trim($label_alignment);
            $container_class[] = isset($field['classes']) && $field['classes'] ? esc_attr($field['classes']) : '';

            if ($field['type'] === 'radio' || $field['type'] === 'checkbox' || $field['type'] === 'image_select') {
                $options_layout = isset($field['options_layout']) && $field['options_layout'] ? $field['options_layout'] : 'inline';
                $container_class[] = 'hf-options-layout-' . trim($options_layout);
            }

            if ($field['type'] === 'select') {
                $container_class[] = isset($field['auto_width']) && $field['auto_width'] == 'on' ? 'hf-auto-width' : '';
            }
        }

        if (isset($field['grid_id']) && $field['grid_id']) {
            $container_class[] = trim($field['grid_id']);
        }
        return array_filter($container_class);
    }

    private function container_inner_style() {
        $field = $this->get_field();
        $field_max_width = isset($field['field_max_width']) ? esc_attr($field['field_max_width']) : '';
        $field_max_width_unit = isset($field['field_max_width_unit']) ? esc_attr($field['field_max_width_unit']) : '%';
        $inline_style = $field_max_width ? ('--hf-width:' . esc_attr($field_max_width) . esc_attr($field_max_width_unit) . ';') : '';
        if ($field['type'] == 'image_select') {
            $image_max_width = isset($field['image_max_width']) ? esc_attr($field['image_max_width']) : '';
            $image_max_width_unit = isset($field['image_max_width_unit']) ? esc_attr($field['image_max_width_unit']) : '%';
            $inline_style .= $image_max_width ? '--hf-image-width: ' . esc_attr($image_max_width) . esc_attr($image_max_width_unit) : '';
        }
        return $inline_style;
    }

    protected function input_html() {
        ?>
        [input]
        <?php
    }

    /* Form builder AdminEnd each elements */

    /**
     * Mark a field the form's rules act on.
     *
     * Conditional logic is set up on the Settings tab, which means the canvas
     * gave no sign that a field is only shown to some visitors - or that a
     * field is the one deciding. Both ends carry a chip, and the rules
     * themselves are in its tooltip.
     */
    protected function condition_hint_html() {
        $field = $this->get_field();

        // A divider reports the row it opens rather than a field of its own, so
        // the id here would not be the one a rule names.
        if (in_array($field['type'], array('divider', 'end_divider'), true)) {
            return;
        }

        $hints = HashFormBuilder::get_condition_hints($field['form_id']);
        $id = (int) $field['id'];

        if (empty($hints[$id])) {
            return;
        }

        $is_target = !empty($hints[$id]['target']);
        $rules = array_merge(
                isset($hints[$id]['target']) ? $hints[$id]['target'] : array(),
                isset($hints[$id]['trigger']) ? $hints[$id]['trigger'] : array()
        );

        /*
         * The rule itself, not just that there is one.
         *
         * "Conditional" alone told you to go and look on the Settings tab,
         * which is the trip this was meant to save. The first rule is written
         * on the chip and trimmed by css when the canvas is narrow; the rest,
         * and the untrimmed text, stay in the tooltip.
         */
        $summary = $rules[0];
        $extra = count($rules) - 1;
        ?>
        <span class="hf-editor-condition-hint<?php echo $is_target ? '' : ' hf-editor-condition-hint-trigger'; ?>"
              title="<?php echo esc_attr(implode("\n", $rules)); ?>">
            <span class="mdi mdi-directions-fork" aria-hidden="true"></span>
            <span class="hf-editor-condition-hint-label"><?php echo esc_html($summary); ?></span>
            <?php if ($extra > 0) { ?>
                <span class="hf-editor-condition-hint-more">
                    <?php
                    printf(
                            /* translators: %s: how many further rules act on this field. */
                            esc_html(_n('+%s more', '+%s more', $extra, 'hash-form')),
                            esc_html(number_format_i18n($extra))
                    );
                    ?>
                </span>
            <?php } ?>
            <span class="screen-reader-text"><?php echo esc_html(implode('. ', $rules)); ?></span>
        </span>
        <?php
    }

    public function load_single_field() {
        $field = $this->get_field();
        $classes = $this->container_classes_array();
        $new_classes = array('hf-editor-form-field', 'hf-editor-field-box', 'hf-editor-field-elements', 'ui-state-default', 'widgets-holder-wrap');
        $classes = array_merge($new_classes, $classes);
        $classes[] = 'hf-editor-field-type-' . $this->type;

        $field_max_width = isset($field['field_max_width']) ? esc_attr($field['field_max_width']) : '';
        $field_max_width_unit = isset($field['field_max_width_unit']) ? esc_attr($field['field_max_width_unit']) : '%';
        if ($field['type'] == 'image_select') {
            $image_max_width = isset($field['image_max_width']) ? $field['image_max_width'] : '';
            $image_max_width_unit = isset($field['image_max_width_unit']) ? esc_attr($field['image_max_width_unit']) : '%';
        }
        ?>
        <li id="hf-editor-field-id-<?php echo esc_attr($field['id']); ?>" class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-fid="<?php echo esc_attr($field['id']); ?>" data-formid="<?php echo esc_attr('divider' === $field['type'] ? esc_attr($field['form_select']) : esc_attr($field['form_id'])); ?>" data-type="<?php echo esc_attr($field['type']); ?>">

            <div id="hf-editor-field-container-<?php echo esc_attr($field['id']); ?>" class="hf-editor-field-container" style="<?php echo ($field_max_width ? ('--hf-width:' . esc_attr($field_max_width) . esc_attr($field_max_width_unit)) : ''); ?><?php echo ((isset($image_max_width) && $image_max_width) ? '--hf-image-width: ' . esc_attr($image_max_width) . esc_attr($image_max_width_unit) : ''); ?>">
                <div class="hf-editor-action-buttons">
                    <a href="#" class="hf-editor-move-action" title="<?php esc_attr_e('Move Field', 'hash-form'); ?>" data-container="body" aria-label="<?php esc_attr_e('Move Field', 'hash-form'); ?>"><span class="mdi mdi-cursor-move"></span></a>
                    <?php if (!in_array($field['type'], array('divider', 'end_divider'), true)) { ?>
                        <a href="#" class="hf-editor-duplicate-action" title="<?php esc_attr_e('Duplicate', 'hash-form'); ?>" data-container="body" aria-label="<?php esc_attr_e('Duplicate', 'hash-form'); ?>" data-duplicatefield="<?php echo esc_attr($field['id']); ?>"><span class="mdi mdi-content-copy"></span></a>
                    <?php } ?>
                    <a href="#" class="hf-editor-delete-action" title="<?php esc_attr_e('Delete', 'hash-form'); ?>" data-container="body" aria-label="<?php esc_attr_e('Delete', 'hash-form'); ?>" data-deletefield="<?php echo esc_attr($field['id']); ?>"><span class="mdi mdi-trash-can-outline"></span></a>
                </div>

                <?php
                $this->condition_hint_html();
                $this->get_builder_html();
                ?>
            </div>

            <?php
            $this->load_single_field_settings();
            ?>
        </li>
        <?php
    }

    public function get_builder_html() {
        $field = $this->get_field();
        $display = $this->display_field_settings();
        $id = $field['id'];

        if ($display['label']) {
            ?>
            <label class="hf-editor-field-label hf-label-show-hide <?php echo (!$field['name'] || ((isset($field['hide_label']) && $field['hide_label']))) ? 'hf-hidden' : ''; ?> ">
                <span id="hf-editor-field-label-text-<?php echo esc_attr($id); ?>" class="hf-editor-field-label-text">
                    <?php
                    echo esc_html($field['name']);
                    ?>
                </span>

                <span id="hf-editor-field-required-<?php echo esc_attr($id); ?>" class="hf-field-required<?php echo (!$field['required'] ? ' hf-hidden' : ''); ?>">
                    <?php echo isset($field['required_indicator']) ? esc_html($field['required_indicator']) : '*'; ?>
                </span>
            </label>
            <?php
        }
        ?>

        <div class="hf-editor-field-content">
            <div class="hf-editor-field-elements">
                <?php $this->input_html(); ?>
            </div>

            <?php
            if (isset($display['description']) && $display['description']) {
                ?>
                <div class="hf-field-desc" id="<?php echo esc_attr($this->description_id()); ?>">
                    <?php
                    echo esc_html($field['description']);
                    ?>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }

    protected function html_name($name = '') {
        $prefix = empty($name) ? 'item_meta' : $name;
        return $prefix . '[' . $this->get_field_column('id') . ']';
    }

    protected function html_id($plus = '') {
        return 'hf-field-' . $this->get_field_column('field_key') . $plus;
    }

    /** Id of this field's visible label, for aria-labelledby. */
    protected function label_id() {
        return 'hf-label-' . $this->get_field_column('field_key');
    }

    /** Id of this field's help text, for aria-describedby. */
    protected function description_id() {
        return 'hf-desc-' . $this->get_field_column('field_key');
    }

    /**
     * Does this field render a set of controls rather than a single one?
     *
     * A group cannot be named with a label's for, because there is no one
     * control for it to point at. Those fields are wrapped in a named group
     * instead, so the question is announced once and each choice keeps its
     * own label.
     *
     * @return bool
     */
    protected function is_group_field() {
        $groups = array(
            'radio',
            'checkbox',
            'image_select',
            'star',
            'name',
            'address',
            'captcha',
            'h_captcha',
            'turnstile',
            'checkbox_grid',
            'matrix_input',
            'like_dislike',
            'sortable',
            'yesno',
            'chained_select',
            'signature',
            'product',
            'payment',
            'repeater_field',
            'date_time',
        );

        /**
         * Field types whose label names a group of controls.
         *
         * An add-on adding a multi-control field should register it here so
         * it is announced the same way.
         */
        $groups = apply_filters('hashform_group_field_types', $groups);

        return in_array($this->get_field_column('type'), $groups, true);
    }

    public function display_field_settings() {
        $default_settings = $this->default_display_field_settings();
        $field_type_settings = $this->field_settings_for_type();
        return wp_parse_args($field_type_settings, $default_settings);
    }

    protected function default_display_field_settings() {
        return array(
            'id' => true,
            'name' => true,
            'value' => true,
            'label' => true,
            'max' => false,
            'invalid' => false,
            'clear_on_focus' => false,
            'classes' => false,
            'range' => false,
            'format' => false,
            'max_width' => true,
            'field_alignment' => false,
            'rows' => false,
            'min_time' => false,
            'max_time' => false,
            'date_format' => false,
            'required' => true,
            'content' => false,
            'css' => true,
            'auto_width' => false,
            'default' => true,
            'description' => true,
            'image_max_width' => false,
            // Length, pattern, matching and uniqueness rules. Only meaningful
            // for fields that hold a single scalar answer.
            'advanced_validation' => false
        );
    }

    protected function field_attrs() {
        $attrs = array();
        $default_attrs = array();
        $display = $this->display_field_settings();

        $field = $this->get_field();
        if (!empty($field['required'])) {
            $default_attrs['aria-required'] = 'true';
        }

        if (isset($display['id']) && $display['id']) {
            $default_attrs['id'] = $this->html_id();
        }

        /*
         * Tie the field's help text to the control, so a screen reader reads
         * the guidance with the field instead of leaving it stranded after
         * it. Only set when there is something to point at.
         */
        $description = $this->get_field_column('description');

        if (isset($display['description']) && $display['description'] && '' !== trim((string) $description)) {
            $default_attrs['aria-describedby'] = $this->description_id();
        }

        if (isset($display['value']) && $display['value']) {
            $default_attrs['value'] = $this->prepare_esc_value();
        }

        if (isset($display['name']) && $display['name']) {
            $default_attrs['name'] = $this->html_name();
        }

        if (isset($display['clear_on_focus']) && $display['clear_on_focus'] && $this->get_field_column('placeholder')) {
            $default_attrs['placeholder'] = $this->get_field_column('placeholder');
        }

        if (isset($display['range']) && $display['range']) {
            $default_attrs['min'] = is_numeric($this->get_field_column('minnum')) ? $this->get_field_column('minnum') : 0;
            $default_attrs['max'] = is_numeric($this->get_field_column('maxnum')) ? $this->get_field_column('maxnum') : 9999999;
            $default_attrs['step'] = is_numeric($this->get_field_column('step')) ? $this->get_field_column('step') : 1;
        }

        if (isset($display['max']) && $display['max']) {
            $default_attrs['maxlength'] = is_numeric($this->get_field_column('max')) ? $this->get_field_column('max') : '';
        }

        $default_attrs = array_merge($default_attrs, $this->extra_field_attrs());

        foreach ($default_attrs as $key => $value) {
            $attrs[] = esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        echo wp_kses_post(implode(' ', $attrs));
    }

    protected function extra_field_attrs() {
        return array();
    }

    protected function field_settings_for_type() {
        return array();
    }

    protected function load_single_field_settings() {
        $field = $this->get_field();
        $display = $this->display_field_settings();
        $field_type = $field['type'];
        $field_id = $field['id'];
        $all_field_types = HashFormFields::field_selection();
        $type_name = isset($all_field_types[$field_type]['name']) ? $all_field_types[$field_type]['name'] : esc_html__('Text', 'hash-form');

        include(HASHFORM_PATH . 'includes/fields/settings.php');
    }

    /* Extra Options */

    public function show_primary_options() {

    }

    public function show_field_choices() {
        $field = $this->get_field();
        $this->field_choices_heading();
        ?>
        <div class="hf-form-row">
            <?php
            if ($field['type'] != 'image_select') {
                ?>
                <span class="hf-bulk-edit-link">
                    <a href="#" class="hf-bulk-edit-link">
                        <?php esc_html_e('Bulk Edit Options', 'hash-form'); ?>
                    </a>
                </span>
            <?php } ?>

            <ul id="hf-field-options-<?php echo esc_attr($field['id']); ?>" class="hf-option-list" data-key="<?php echo esc_attr($field['field_key']); ?>">
                <?php
                $this->show_single_option();
                ?>
            </ul>

            <div class="hf-option-add-list">
                <a href="javascript:void(0);" data-opttype="single" class="hf-add-option">
                    <span class="mdi mdi-plus"></span>
                    <?php esc_html_e('Add Option', 'hash-form'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    public function show_single_option() {
        $field = $this->get_field();
        if (!is_array($field['options']))
            return;
        $html_id = $this->html_id();
        $this->hidden_field_option();
        foreach ($field['options'] as $opt_key => $opt) {
            $field_val = isset($opt['label']) ? $opt['label'] : '';
            $default_value = (array) $field['default_value'];
            $checked = in_array($field_val, $default_value) ? 'checked' : '';
            require(HASHFORM_PATH . 'includes/fields/single-option.php');
        }
    }

    protected function hidden_field_option() {
        $field = $this->get_field();
        $ajax_action = HashFormHelper::get_post('action', 'sanitize_text_field');
        if ($ajax_action === 'hashform_import_options')
            return;
        $opt_key = '000';
        $opt = esc_html__('New Option', 'hash-form');

        $html_id = $this->html_id();
        $field_val = $opt = esc_html__('New Option', 'hash-form');

        $checked = false;

        require(HASHFORM_PATH . 'includes/fields/single-option.php');
    }

    protected function field_choices_heading() {
        $field = $this->get_field();
        ?>
        <h4 class="hf-field-heading">
            <?php
            /* translators: 1: field name */
            printf(esc_html__('%s Options', 'hash-form'), esc_html($field['name']));
            ?>
        </h4>
        <?php
    }

    /* Combo Options */

    protected function show_after_default() {

    }

    public function get_default_field_options() {
        $opts = array(
            'grid_id' => '',
            // Fields sharing a key stack inside one column of a column row.
            'column_group' => '',
            // Every column of that row, as group:width pairs, so the columns
            // nobody dropped a field into can still be rebuilt.
            'column_row' => '',
            'label_position' => '',
            'label_alignment' => '',
            'hide_label' => '',
            'heading_type' => '',
            'text_alignment' => '',
            'content' => '',
            'select_option_type' => 'radio',
            'image_size' => '',
            'image_id' => '',
            'spacer_height' => '50',
            'step' => '1',
            'min_time' => '00:00',
            'max_time' => '23:59',
            'date_format' => 'MM dd, yy',
            'border_style' => 'solid',
            'border_width' => '2',
            'minnum' => '1',
            'maxnum' => '10',
            'classes' => '',
            'auto_width' => 'off',
            'placeholder' => '',
            'format' => '',
            'required_indicator' => '*',
            'options_layout' => 'inline',
            'field_max_width' => '',
            'field_max_width_unit' => '%',
            'image_max_width' => '100',
            'image_max_width_unit' => '%',
            'field_alignment' => 'left',
            'blank' => esc_html__('This field is required.', 'hash-form'),
            'invalid' => esc_html__('This field is invalid.', 'hash-form'),
            'rows' => '10',
            'max' => '',
            // Advanced validation
            'min_length' => '',
            'pattern' => '',
            'pattern_message' => esc_html__('Please match the requested format.', 'hash-form'),
            'match_field' => '',
            'match_message' => esc_html__('These fields do not match.', 'hash-form'),
            'unique' => '',
            'unique_message' => esc_html__('This value has already been submitted.', 'hash-form'),
            'disable' => array(
                'line1' => '',
                'line2' => '',
                'city' => '',
                'state' => '',
                'postal' => '',
                'country' => ''
            )
        );
        $field_opts = $this->extra_field_default_opts();
        $opts = array_merge($opts, $field_opts);
        return $opts;
    }

    protected function extra_field_default_opts() {
        return array();
    }

    /* Front End Display */

    public function show_field() {
        $this->load_field_scripts();
        $field = $this->get_field();
        $classes = $this->container_classes_array();
        $classes[] = 'hf-form-field';
        ?>
        <div id="hf-field-container-<?php echo esc_attr($field['id']); ?>" class="<?php echo esc_attr(implode(' ', $classes)); ?>">
            <?php $this->get_frontend_html(); ?>
        </div>
        <?php
    }

    protected function load_field_scripts() {

    }

    protected function prepare_esc_value() {
        $field = $this->get_field();
        $value = isset($field['default_value']) ? $field['default_value'] : '';
        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        if (strpos($value, '&lt;') !== false)
            $value = htmlentities($value);
        return $value;
    }

    protected function add_min_max() {
        $field = $this->get_field();
        $min = isset($field['minnum']) ? $field['minnum'] : '';
        $max = isset($field['maxnum']) ? $field['maxnum'] : '';
        $step = isset($field['step']) ? $field['step'] : '';

        if (!is_numeric($min))
            $min = 0;

        if (!is_numeric($max))
            $max = 9999999;

        if (!is_numeric($step) && $step !== 'any')
            $step = 1;

        return ' min="' . esc_attr($min) . '" max="' . esc_attr($max) . '" step="' . esc_attr($step) . '"';
    }

    public function validate($args) {
        return array();
    }

    public function set_value_before_save($value) {
        return $value;
    }

    public function sanitize_value(&$value) {
        return HashFormHelper::sanitize_value('sanitize_text_field', $value);
    }

    public function get_new_field_defaults() {
        return array(
            'name' => $this->get_new_field_name(),
            'description' => '',
            'type' => $this->type,
            'default_value' => '',
            'required' => false,
            'field_options' => $this->get_default_field_options(),
            'options' => array(
                array(
                    'label' => esc_html__('Option 1', 'hash-form'),
                ),
                array(
                    'label' => esc_html__('Option 2', 'hash-form'),
                ),
                array(
                    'label' => esc_html__('Option 3', 'hash-form'),
                )
            ),
        );
    }

    protected function get_new_field_name() {
        $name = esc_html__('Untitled', 'hash-form');
        $fields = HashFormFields::field_selection();
        if (isset($fields[$this->type])) {
            $name = is_array($fields[$this->type]) ? $fields[$this->type]['name'] : $fields[$this->type];
        }
        return $name;
    }

}
