<?php

defined('ABSPATH') || die();

class HashFormGridHelper {

    private $parent_li;
    private $current_list_size;
    private $current_field_count;
    private $field_layout_class;
    private $active_field_size;
    private $field;
    private $section_helper;
    private $nested;
    private $section_is_open = false;
    private $current_group = '';
    private $column_opened_now = false;
    private $pending_close = false;
    private $row_spec = array();
    private $spec_index = 0;
    private $row_has_columns = false;

    public function __construct($nested = false) {
        $this->parent_li = false;
        $this->current_list_size = 0;
        $this->current_field_count = 0;
        $this->nested = $nested;
    }

    public function set_field($field) {
        $this->field = $field;
        $this->field_layout_class = $this->get_field_layout_class();
        $this->active_field_size = $this->get_size_of_class($this->field_layout_class);
    }

    public function maybe_begin_field_wrapper() {
        $group = $this->field_group();

        // Leaving a column ends it, and releases a row close that was held
        // back while that column was still collecting its fields.
        if ('' !== $this->current_group && $group !== $this->current_group) {
            $this->close_column();
            if ($this->pending_close) {
                $this->close_field_wrapper();
            }
        }

        if ($this->should_first_close_the_active_field_wrapper()) {
            $this->close_field_wrapper();
        }

        if (false === $this->parent_li) {
            $this->begin_field_wrapper();
            $this->row_spec = $this->parse_row_spec();
            $this->spec_index = 0;
        }

        if ('' !== $group && '' === $this->current_group) {
            $this->open_columns_up_to($group);
        }

        if (!empty($this->section_helper) && $this->section_is_open) {
            $this->section_helper->maybe_begin_field_wrapper();
        }
    }

    private function field_group() {
        return isset($this->field['column_group']) ? (string) $this->field['column_group'] : '';
    }

    private function parse_row_spec() {
        return self::parse_column_row(isset($this->field['column_row']) ? $this->field['column_row'] : '');
    }

    /**
     * Every field of a column row carries the whole row, as group:width pairs,
     * so the row survives a reload even where nobody dropped a field. Returns
     * the columns in order, each as a group and a width.
     */
    public static function parse_column_row($column_row) {
        if (empty($column_row)) {
            return array();
        }

        $spec = array();
        foreach (explode(',', (string) $column_row) as $column) {
            $parts = explode(':', $column);
            $group = sanitize_key($parts[0]);
            if ('' === $group) {
                continue;
            }

            $spec[] = array(
                'group' => $group,
                'width' => isset($parts[1]) ? self::sanitize_grid_class($parts[1]) : 'hf-grid-12',
            );
        }
        return $spec;
    }

    private static function sanitize_grid_class($class) {
        return in_array($class, self::get_grid_classes()) ? $class : 'hf-grid-12';
    }

    /**
     * Opens the column this field belongs to, laying down any column that comes
     * before it in the row and holds no fields of its own.
     */
    private function open_columns_up_to($group) {
        $index = $this->find_spec_index($group);

        if (false === $index) {
            // No row to work from, so the field's own width has to stand in.
            $this->begin_column($group, $this->field_layout_class);
            return;
        }

        for ($i = $this->spec_index; $i < $index; $i++) {
            $this->echo_empty_column($this->row_spec[$i]);
        }

        $this->begin_column($group, $this->row_spec[$index]['width']);
        $this->spec_index = $index + 1;
    }

    private function find_spec_index($group) {
        $count = count($this->row_spec);
        for ($i = $this->spec_index; $i < $count; $i++) {
            if ($this->row_spec[$i]['group'] === $group) {
                return $i;
            }
        }
        return false;
    }

    // The columns left over once the row has run out of fields.
    private function flush_remaining_columns() {
        $count = count($this->row_spec);
        for ($i = $this->spec_index; $i < $count; $i++) {
            $this->echo_empty_column($this->row_spec[$i]);
        }

        $this->row_spec = array();
        $this->spec_index = 0;
    }

    private function echo_empty_column($column) {
        echo '<li class="hf-editor-form-field hf-editor-column ' . esc_attr($column['width']) . '" data-column-group="' . esc_attr($column['group']) . '">';
        echo '<ul class="hf-editor-column-fields hf-editor-sorting"></ul></li>';
        $this->row_has_columns = true;
    }

    private function begin_column($group, $class) {
        $class = $class ? $class : 'hf-grid-12';
        echo '<li class="hf-editor-form-field hf-editor-column ' . esc_attr($class) . '" data-column-group="' . esc_attr($group) . '">';
        echo '<ul class="hf-editor-column-fields hf-editor-sorting">';
        $this->current_group = $group;
        $this->column_opened_now = true;
        $this->row_has_columns = true;
    }

    private function close_column() {
        if ('' === $this->current_group) {
            return;
        }
        echo '</ul></li>';
        $this->current_group = '';
    }

    private function maybe_close_section_helper() {
        if (empty($this->section_helper)) {
            return;
        }
        $this->section_helper->force_close_field_wrapper();
        $this->section_helper = null;
    }

    private function should_first_close_the_active_field_wrapper() {
        if (false === $this->parent_li || !empty($this->section_helper)) {
            return false;
        }

        // Still stacking fields into an open column: the row that holds it stays
        // open until the column itself ends.
        if ('' !== $this->current_group) {
            return false;
        }

        $group = $this->field_group();

        // A column row holds its own columns and nothing else, so anything that
        // belongs elsewhere starts a fresh row.
        if (!empty($this->row_spec)) {
            return false === $this->find_spec_index($group);
        }

        // Forms saved before the row was written out have only the columns their
        // fields name, so those group by sitting next to each other. Such a row
        // still takes columns, and only columns.
        if ($this->row_has_columns) {
            return '' === $group;
        }

        // A field that belongs to a column cannot join a plain row.
        if ('' !== $group) {
            return true;
        }

        // The row is full once another field of this width would push it past
        // the twelve columns of the grid.
        return $this->current_list_size + $this->active_field_size > 12;
    }

    private function begin_field_wrapper() {
        echo '<li class="hf-editor-field-box"><ul class="hf-editor-grid-container hf-editor-sorting">';
        $this->parent_li = true;
        $this->current_list_size = 0;
        $this->current_field_count = 0;
        $this->row_has_columns = false;
    }

    public function sync_list_size() {
        if (!isset($this->field) || false === $this->parent_li) {
            return;
        }

        // A column takes up its width once, no matter how many fields are
        // stacked inside it, so only the field that opened it counts.
        if ('' !== $this->current_group && !$this->column_opened_now) {
            return;
        }
        $this->column_opened_now = false;

        $this->current_field_count++;
        $this->current_list_size += $this->active_field_size;

        if (12 !== $this->current_list_size) {
            return;
        }

        if ('' === $this->current_group) {
            $this->close_field_wrapper();
        } else {
            // Closing now would strand the rest of the open column outside it.
            $this->pending_close = true;
        }
    }

    public function force_close_field_wrapper() {
        if (false !== $this->parent_li) {
            $this->close_field_wrapper();
        }
    }

    private function close_field_wrapper() {
        $this->close_column();
        $this->flush_remaining_columns();
        $this->maybe_close_section_helper();
        echo '</ul></li>';
        $this->parent_li = false;
        $this->current_list_size = 0;
        $this->current_field_count = 0;
        $this->pending_close = false;
    }

    private static function get_grid_classes() {
        return array(
            'hf-grid-1',
            'hf-grid-2',
            'hf-grid-3',
            'hf-grid-4',
            'hf-grid-5',
            'hf-grid-6',
            'hf-grid-7',
            'hf-grid-8',
            'hf-grid-9',
            'hf-grid-10',
            'hf-grid-11',
            'hf-grid-12',
        );
    }

    private function get_field_layout_class() {
        $field = $this->field;

        if (empty($field['grid_id'])) {
            return '';
        }

        $grid_class = $field['grid_id'];
        $classes = self::get_grid_classes();

        if (in_array($grid_class, $classes)) {
            return $grid_class;
        }
        return '';
    }

    private static function get_size_of_class($class) {
        if (0 === strpos($class, 'hf-grid-')) {
            $substr = substr($class, 8);
            if (is_numeric($substr)) {
                return (int) $substr;
            }
        }
        return 12;
    }

}
