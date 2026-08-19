<?php
defined('ABSPATH') || die();

/**
 * Adding WP List table class if it's not available.
 */
if (!class_exists(WP_List_Table::class)) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class HashFormEntryListing extends \WP_List_Table {

    private $table_data;
    private $status;
    private $form_names;
    private $previews;
    private $page_entry_ids = array();

    public function __construct() {
        parent::__construct(
            array(
                'singular' => 'Entry',
                'plural' => 'Entries',
                'ajax' => false,
            )
        );
        $this->status = HashFormHelper::get_var('status', 'sanitize_text_field', 'published');
    }

    public function no_items() {
        esc_html_e('No entries found.', 'hash-form');
    }

    public function column_default($item, $column_name) {
        return isset($item[$column_name]) ? $item[$column_name] : '';
    }

    public function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'is_starred' => '<span class="dashicons dashicons-star-filled" title="' . esc_attr__('Starred', 'hash-form') . '"></span>',
            'name' => esc_html__('ID', 'hash-form'),
            'form_id' => esc_html__('Form', 'hash-form'),
            'preview' => esc_html__('Entry', 'hash-form'),
            'user_id' => esc_html__('Created By', 'hash-form'),
            'delivery_status' => esc_html__('Status', 'hash-form'),
            'ip' => esc_html__('IP', 'hash-form'),
            'created_at' => esc_html__('Created At', 'hash-form')
        );

        // Add-ons append their own columns, such as payment status.
        return apply_filters('hashform_entries_columns', $columns);
    }

    /**
     * Unread rows are bold, the way an inbox does it.
     */
    public function single_row($item) {
        $classes = empty($item['is_read']) ? 'hf-entry-unread' : '';
        echo '<tr class="' . esc_attr($classes) . '">';
        $this->single_row_columns($item);
        echo '</tr>';
    }

    private function get_column_star($item) {
        $starred = !empty($item['is_starred']);

        return sprintf(
                '<button type="button" class="hf-entry-star %1$s" data-entry="%2$s" data-starred="%3$s" aria-pressed="%4$s" aria-label="%5$s"><span class="dashicons dashicons-%6$s"></span></button>',
                $starred ? 'hf-starred' : '',
                esc_attr($item['id']),
                $starred ? 1 : 0,
                $starred ? 'true' : 'false',
                esc_attr__('Star this entry', 'hash-form'),
                $starred ? 'star-filled' : 'star-empty'
        );
    }

    /**
     * A couple of values from the entry itself, so the list can be read
     * without opening every row.
     */
    private function get_column_preview($entry_id) {
        if (null === $this->previews) {
            $this->previews = $this->load_previews();
        }

        if (empty($this->previews[$entry_id])) {
            return '<span class="hf-entry-preview-empty">&mdash;</span>';
        }

        $parts = array();

        foreach ($this->previews[$entry_id] as $value) {
            $parts[] = '<span class="hf-entry-preview-value">' . esc_html($value) . '</span>';
        }

        return '<div class="hf-entry-preview">' . implode('', $parts) . '</div>';
    }

    /**
     * Loads the first couple of stored values for every entry on this page in
     * one query, rather than one query per row.
     */
    private function load_previews() {
        global $wpdb;

        if (empty($this->page_entry_ids)) {
            return array();
        }

        $ids = implode(',', array_map('absint', $this->page_entry_ids));

        $rows = $wpdb->get_results(
                "SELECT m.item_id, m.meta_value, f.type
            FROM {$wpdb->prefix}hashform_entry_meta AS m
            LEFT JOIN {$wpdb->prefix}hashform_fields AS f ON f.id = m.field_id
            WHERE m.item_id IN ({$ids})
            ORDER BY m.id ASC", ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        // Layout only fields hold no answer, so they make a poor preview.
        $skip = array('heading', 'paragraph', 'separator', 'spacer', 'image', 'html', 'captcha', 'hidden', 'user_id');
        $previews = array();

        foreach ($rows as $row) {
            $item_id = $row['item_id'];

            if (isset($previews[$item_id]) && count($previews[$item_id]) >= 2) {
                continue;
            }

            if (in_array($row['type'], $skip, true)) {
                continue;
            }

            $value = maybe_unserialize($row['meta_value']);

            if (is_array($value)) {
                $value = implode(', ', array_filter(array_map('strval', $value)));
            }

            $value = trim(wp_strip_all_tags((string) $value));

            if ('' === $value) {
                continue;
            }

            $previews[$item_id][] = wp_html_excerpt($value, 45, '...');
        }

        return $previews;
    }

    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="%1$s_id[]" value="%2$s" />', esc_attr($this->_args['singular']), esc_attr($item['id'])
        );
    }

    public function prepare_items() {
        $this->table_data = $this->get_table_data();

        $hashform_columns = $this->get_columns();
        $hashform_sortable = $this->get_sortable_columns();
        $hashform_hidden = (is_array(get_user_meta(get_current_user_id(), 'managetoplevel_page_hashform-entriescolumnshidden', true))) ? get_user_meta(get_current_user_id(), 'managetoplevel_page_hashform-entriescolumnshidden', true) : array();
        $hashform_primary = 'id';
        $this->_column_headers = array($hashform_columns, $hashform_hidden, $hashform_sortable, $hashform_primary);

        if ($this->table_data) {
            // Sort the raw rows (not the rendered HTML) so ordering works on
            // real values, then render only the rows of the current page.
            usort($this->table_data, array(&$this, 'usort_reorder'));

            /* pagination */
            $per_page = $this->get_items_per_page('entries_per_page', 10);
            $current_page = $this->get_pagenum();
            $total_items = count($this->table_data);

            $page_rows = array_slice($this->table_data, (($current_page - 1) * $per_page), $per_page);

            // Collected first so the previews for the whole page load in one
            // query instead of one per row.
            $this->page_entry_ids = wp_list_pluck($page_rows, 'id');

            $data = array();
            foreach ($page_rows as $item) {
                $id = $item['id'];
                $data[$id] = array(
                    'id' => $item['id'],
                    'is_read' => isset($item['is_read']) ? $item['is_read'] : 1,
                    'is_starred' => $this->get_column_star($item),
                    'name' => $this->get_column_id($item),
                    'form_id' => $this->get_form_link($item['form_id']),
                    'preview' => $this->get_column_preview($item['id']),
                    'user_id' => $this->get_user_link($item['user_id']),
                    'delivery_status' => $item['delivery_status'] ? esc_html__('Success', 'hash-form') : esc_html__('Failed', 'hash-form'),
                    'created_at' => HashFormHelper::convert_date_format($item['created_at']),
                    'ip' => $item['ip']
                );

                $data[$id] = apply_filters('hashform_entries_column_values', $data[$id], $item);
            }

            $this->set_pagination_args(array(
                'total_items' => $total_items,
                'per_page' => $per_page,
                'total_pages' => ceil($total_items / $per_page)
            ));

            $this->items = $data;
        }
    }

    public function get_column_id($item) {
        $entry_id = $item['id'];

        $edit_url = admin_url('admin.php?page=hashform-entries&hashform_action=view&id=' . $entry_id);

        $output = '<strong>';
        if ('trash' == $this->status) {
            $output .= esc_html($entry_id);
        } else {
            /* translators: 1: entry id */
            $output .= '<a class="row-title" href="' . esc_url($edit_url) . '" aria-label="' . sprintf(esc_html__('%s (Edit)', 'hash-form'), $entry_id) . '">' . esc_html($entry_id) . '</a>';
        }
        $output .= '</strong>';

        // Get actions.
        $actions = $this->get_action_links($item);
        $row_actions = array();

        foreach ($actions as $id => $action) {
            $row_actions[] = '<span class="' . esc_attr($id) . '"><a href="' . $action['url'] . '">' . $action['label'] . '</a></span>';
        }


        $output .= '<div class="row-actions">' . implode(' | ', $row_actions) . '</div>';

        return $output;
    }

    public function usort_reorder($a, $b) {
        $numeric = array('id', 'form_id', 'user_id', 'delivery_status');
        $sortable = array_merge($numeric, array('status', 'ip', 'created_at'));

        $orderby = HashFormHelper::get_var('orderby', 'sanitize_text_field', 'created_at');
        if (!in_array($orderby, $sortable, true)) {
            $orderby = 'created_at';
        }

        $order = strtolower(HashFormHelper::get_var('order', 'sanitize_text_field', 'desc'));

        if (in_array($orderby, $numeric, true)) {
            $result = (int) $a[$orderby] < (int) $b[$orderby] ? -1 : (((int) $a[$orderby] > (int) $b[$orderby]) ? 1 : 0);
        } else {
            $result = strcmp($a[$orderby], $b[$orderby]);
        }

        return ($order === 'asc') ? $result : -$result;
    }

    private function get_table_data() {
        global $wpdb;

        // "unread" and "starred" are filters over published entries rather
        // than real statuses of their own.
        $where = array('e.status = %s');
        $params = array(in_array($this->status, array('unread', 'starred'), true) ? 'published' : $this->status);
        $join = '';

        if ('unread' === $this->status) {
            $where[] = 'e.is_read = 0';
        } else if ('starred' === $this->status) {
            $where[] = 'e.is_starred = 1';
        }

        // The form filter and the search box used to be mutually exclusive,
        // so picking a form and then searching silently ignored the form.
        $form_id = HashFormHelper::get_var('form_id', 'absint');

        if ($form_id) {
            $where[] = 'e.form_id = %d';
            $params[] = $form_id;
        }

        $search = trim(htmlspecialchars_decode(HashFormHelper::get_var('s')));

        if ('' !== $search) {
            $like = '%' . $wpdb->esc_like($search) . '%';

            // What was submitted lives in the meta table, which is what people
            // expect a search to look through.
            $join = "LEFT JOIN {$wpdb->prefix}hashform_entry_meta AS m ON m.item_id = e.id
                LEFT JOIN {$wpdb->prefix}hashform_forms AS f ON f.id = e.form_id
                LEFT JOIN {$wpdb->users} AS u ON u.ID = e.user_id";

            $search_where = array(
                'm.meta_value LIKE %s',
                'f.name LIKE %s',
                'e.ip LIKE %s',
                'u.display_name LIKE %s',
                'u.user_email LIKE %s',
            );

            array_push($params, $like, $like, $like, $like, $like);

            // A bare number is most likely an entry id.
            if (is_numeric($search)) {
                $search_where[] = 'e.id = %d';
                $params[] = absint($search);
            }

            $where[] = '(' . implode(' OR ', $search_where) . ')';
        }

        // DISTINCT because the meta join returns one row per stored field.
        $sql = "SELECT DISTINCT e.* FROM {$wpdb->prefix}hashform_entries AS e {$join} WHERE " . implode(' AND ', $where);

        return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    public function get_bulk_actions() {
        if ($this->status == 'published') {
            return array(
                'bulk_trash' => esc_html__('Move to Trash', 'hash-form'),
            );
        } else {
            return array(
                'bulk_untrash' => esc_html__('Restore', 'hash-form'),
                'bulk_delete' => esc_html__('Delete Permanently', 'hash-form')
            );
        }
    }

    protected function display_tablenav($which) {
        if ('top' === $which) {
            // Signs the bulk actions and "Empty Trash" submits in this form.
            wp_nonce_field('bulk-' . $this->_args['plural']);
        }
        ?>
        <div class="tablenav <?php echo esc_attr($which); ?>">
            <?php if ($this->has_items()) { ?>
                <div class="alignleft actions bulkactions">
                    <?php $this->bulk_actions($which); ?>
                </div>
                <?php
            }

            $this->extra_tablenav($which);

            $this->pagination($which);
            ?>
            <br class="clear" />
        </div>
        <?php
    }

    public function extra_tablenav($which) {
        if ($this->has_items()) {
            if ('trash' == $this->status) {
                ?>
                <div class="alignleft actions"><?php submit_button(esc_html__('Empty Trash', 'hash-form'), 'apply', 'delete_all', false); ?></div>
                <?php
            }
        }

        if ($which === 'top') {
            $form_id = HashFormHelper::get_var('form_id', 'absint', 0);
            ?>
            <div class="alignleft actions">
                <?php
                self::forms_dropdown('form_id', $form_id);
                submit_button(esc_html__('Filter', 'hash-form'), 'filter_action', '', false, array('id' => 'post-query-submit'));

                // Where add-ons hang their own entry tools, such as export.
                do_action('hashform_entries_tablenav', $this->status, $form_id);

                $this->export_upsell();
                ?>
            </div>
            <?php
        }
    }

    /**
     * Exporting entries lives in the Pro plugin. Point at it only when it is
     * not already installed.
     */
    private function export_upsell() {
        if (defined('HASH_FORM_PRO_VERSION') || !$this->has_items()) {
            return;
        }
        ?>
        <a class="button hf-export-upsell" href="https://hashthemes.com/plugin/hash-form-pro/" target="_blank" rel="noopener">
            <span class="dashicons dashicons-download"></span>
            <?php esc_html_e('Export to CSV', 'hash-form'); ?>
        </a>
        <?php
    }

    public static function forms_dropdown($field_name, $field_value = '') {
        $forms = HashFormBuilder::get_all_forms();
        ?>
        <select name="<?php echo esc_attr($field_name); ?>">
            <option value=""><?php echo esc_html__('All', 'hash-form'); ?></option>
            <?php foreach ($forms as $form) { ?>
                <option value="<?php echo esc_attr($form->id); ?>" <?php selected($field_value, $form->id); ?>>
                    <?php echo ('' === $form->name ? esc_html__('No Title', 'hash-form') : esc_html($form->name)); ?>
                </option>
            <?php } ?>
        </select>
        <?php
    }

    public function get_sortable_columns() {
        return array(
            'id' => array('id', false),
            'form_id' => array('form_id', false),
            'user_id' => array('user_id', false),
            'status' => array('status', false),
            'created_at' => array('created_at', false),
            'delivery_status' => array('delivery_status', false),
            'ip' => array('ip', false)
        );
    }

    public function get_action_links($item) {
        $entry_id = $item['id'];
        $actions = array();
        $trash_links = self::delete_trash_links($entry_id);
        if ('trash' == $this->status) {
            $actions['restore'] = $trash_links['restore'];
            $actions['delete'] = $trash_links['delete'];
        } else {
            $actions['view'] = array(
                'label' => esc_html__('View', 'hash-form'),
                'url' => admin_url('admin.php?page=hashform-entries&hashform_action=view&id=' . $entry_id)
            );
            $actions['trash'] = $trash_links['trash'];
        }
        return $actions;
    }

    private static function delete_trash_links($id) {
        $base_url = '?page=hashform-entries&id=' . $id;
        return array(
            'restore' => array(
                'label' => esc_html__('Restore', 'hash-form'),
                'url' => wp_nonce_url($base_url . '&hashform_action=untrash', 'untrash_entry_' . absint($id)),
            ),
            'delete' => array(
                'label' => esc_html__('Delete Permanently', 'hash-form'),
                'url' => wp_nonce_url($base_url . '&hashform_action=destroy', 'destroy_entry_' . absint($id)),
            ),
            'trash' => array(
                'label' => esc_html__('Trash', 'hash-form'),
                'url' => wp_nonce_url($base_url . '&hashform_action=trash', 'trash_entry_' . absint($id)),
            )
        );
    }

    public function get_views() {
        $statuses = array(
            'published' => esc_html__('All', 'hash-form'),
            'unread' => esc_html__('Unread', 'hash-form'),
            'starred' => esc_html__('Starred', 'hash-form'),
            'trash' => esc_html__('Trash', 'hash-form'),
        );

        $links = array();

        $counts = HashFormEntry::get_count();

        foreach ($statuses as $status => $name) {
            // All and Unread stay visible at zero: "0 unread" is readable as
            // good news rather than the tab vanishing, and All is the way
            // back from every other view. Starred and Trash have to earn it.
            $always_shown = ('published' === $status || 'unread' === $status);

            if (!$always_shown && !$counts[$status]) {
                continue;
            }

            $links[$status] = HashFormHelper::view_tab(
                            admin_url('admin.php?page=hashform-entries&status=' . $status), $name, $counts[$status], $status == $this->status
            );
        }

        return $links;
    }

    public function views() {
        HashFormHelper::render_view_tabs($this->get_views());
    }

    private function get_form_link($form_id) {
        global $wpdb;

        // One query for all form names instead of one per row.
        if (null === $this->form_names) {
            $this->form_names = array();
            $forms = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}hashform_forms", ARRAY_A);
            foreach ($forms as $form) {
                $this->form_names[$form['id']] = $form['name'];
            }
        }

        $form_name = isset($this->form_names[$form_id]) ? $this->form_names[$form_id] : esc_html__('(deleted form)', 'hash-form');
        return '<a href="' . esc_url(admin_url('admin.php?page=hashform&hashform_action=edit&id=' . $form_id)) . '">' . esc_html($form_name) . '</a>';
    }

    private function get_user_link($user_id) {
        if ($user_id) {
            $user_obj = get_user_by('id', $user_id);
            if ($user_obj) {
                return '<a data-id="' . esc_attr($user_id) . '" href="' . get_edit_user_link($user_id) . '">' . esc_html($user_obj->display_name) . '</a>';
            }
        }
        return esc_html__('Guest', 'hash-form');
    }

}
