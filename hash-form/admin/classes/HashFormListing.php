<?php
defined('ABSPATH') || die();

/**
 * Adding WP List table class if it's not available.
 */
if (!class_exists(WP_List_Table::class)) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class HashFormListing extends \WP_List_Table {

    private $table_data;
    private $status;

    /**
     * One GROUP BY for every form on the screen instead of a COUNT(*) per
     * row. Static because the header stats and the table both want it, and
     * the screen builds more than one instance of this class per request.
     */
    private static $entry_counts;

    public function __construct() {
        parent::__construct(
            array(
                'singular' => 'Form',
                'plural' => 'Forms',
                'ajax' => false,
            )
        );
        $this->status = htmlspecialchars_decode(HashFormHelper::get_var('status', 'sanitize_text_field', 'published'));
    }

    /**
     * Only reached when a search or filter came back empty; the "no forms at
     * all" case is handled by empty_state() before the table is drawn.
     */
    public function no_items() {
        esc_html_e('No forms matched your search.', 'hash-form');
    }

    /**
     * A bare table with an empty body is a poor first impression, so the
     * whole table is swapped for a panel when there is genuinely nothing to
     * list. A search that returns nothing still gets the table, so the search
     * box and column headers stay where the user left them.
     */
    public function display() {
        $searching = '' !== (string) HashFormHelper::get_var('s');

        if ($this->has_items() || $searching) {
            parent::display();
            return;
        }

        $this->empty_state();
    }

    private function empty_state() {
        if ('trash' == $this->status) {
            ?>
            <div class="hf-empty-state">
                <span class="hf-empty-icon dashicons dashicons-trash" aria-hidden="true"></span>
                <h3 class="hf-empty-title"><?php esc_html_e('The trash is empty', 'hash-form'); ?></h3>
                <p class="hf-empty-text"><?php esc_html_e('Forms you move to the trash stay here until you delete them permanently.', 'hash-form'); ?></p>
                <a class="hf-btn" href="<?php echo esc_url(admin_url('admin.php?page=hashform')); ?>"><?php esc_html_e('Back to all forms', 'hash-form'); ?></a>
            </div>
            <?php
            return;
        }
        ?>
        <div class="hf-empty-state">
            <span class="hf-empty-icon dashicons dashicons-feedback" aria-hidden="true"></span>
            <h3 class="hf-empty-title"><?php esc_html_e('No forms yet', 'hash-form'); ?></h3>
            <p class="hf-empty-text"><?php esc_html_e('Create your first form, then drop it on any page with the shortcode or the Hash Form block.', 'hash-form'); ?></p>
            <a href="#" class="hf-btn hf-btn-primary hf-trigger-modal"><?php esc_html_e('Create your first form', 'hash-form'); ?></a>
            <?php
            // Ready-made templates and the AI generator live in the Pro
            // plugin, which replaces this same modal with its own.
            if (!defined('HASH_FORM_PRO_VERSION')) {
                ?>
                <p class="hf-empty-upsell">
                    <?php esc_html_e('Prefer to start from a template?', 'hash-form'); ?>
                    <a href="https://hashthemes.com/plugin/hash-form-pro/" target="_blank" rel="noopener"><?php esc_html_e('Hash Form Pro', 'hash-form'); ?></a>
                    <?php esc_html_e('adds a form template library and an AI form generator.', 'hash-form'); ?>
                </p>
                <?php
            }
            ?>
        </div>
        <?php
    }

    public function column_default($item, $column_name) {
        return isset($item[$column_name]) ? $item[$column_name] : '';
    }

    public function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />',
            'name' => esc_html__('Form Title', 'hash-form'),
            'entries' => esc_html__('Entries', 'hash-form'),
            'id' => 'ID',
            'shortcode' => esc_html__('Shortcode', 'hash-form'),
            'created_at' => esc_html__('Date', 'hash-form')
        );
    }

    public function column_title($item) {
        $form_name = $item['name'];
        $form_id = $item['id'];
        if (trim($form_name) == '') {
            $form_name = esc_html__('No Title', 'hash-form');
        }
        $edit_url = admin_url('admin.php?page=hashform&hashform_action=edit&id=' . absint($form_id));

        $output = '<strong>';
        if ('trash' == $this->status) {
            $output .= esc_html($form_name);
        } else {
            /* translators: 1: form name */
            $output .= '<a class="row-title" href="' . esc_url($edit_url) . '" aria-label="' . sprintf(esc_html__('%s (Edit)', 'hash-form'), $form_name) . '">' . esc_html($form_name) . '</a>';
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

    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="%1$s_id[]" value="%2$s" />', esc_attr($this->_args['singular']), esc_attr($item['id'])
        );
    }

    public function prepare_items() {
        $this->table_data = $this->get_table_data();

        $hashform_columns = $this->get_columns();
        $hashform_sortable = $this->get_sortable_columns();
        $hashform_hidden = (is_array(get_user_meta(get_current_user_id(), 'managetoplevel_page_hashformcolumnshidden', true))) ? get_user_meta(get_current_user_id(), 'managetoplevel_page_hashformcolumnshidden', true) : array();
        // The title column carries the row actions, so it is the one that
        // must stay visible and hold the toggle on narrow screens.
        $hashform_primary = 'name';
        $this->_column_headers = array($hashform_columns, $hashform_hidden, $hashform_sortable, $hashform_primary);

        if ($this->table_data) {
            // Sort the raw rows (not the rendered HTML) so ordering works on
            // real values, then render only the rows of the current page.
            usort($this->table_data, array(&$this, 'usort_reorder'));

            /* pagination */
            $per_page = $this->get_items_per_page('forms_per_page', 10);
            $current_page = $this->get_pagenum();
            $total_items = count($this->table_data);

            $page_rows = array_slice($this->table_data, (($current_page - 1) * $per_page), $per_page);

            $data = array();
            foreach ($page_rows as $item) {
                $id = $item['id'];
                $data[$id] = array(
                    'name' => $this->column_title($item),
                    'entries' => $this->get_entry_link($id),
                    'id' => $id,
                    'form_key' => $item['form_key'],
                    'shortcode' => $this->get_shortcode_chip($id),
                    'created_at' => HashFormHelper::convert_date_format($item['created_at'])
                );
            }

            $this->set_pagination_args(array(
                'total_items' => $total_items,
                'per_page' => $per_page,
                'total_pages' => ceil($total_items / $per_page)
            ));

            $this->items = $data;
        }
    }

    private function usort_reorder($a, $b) {
        $sortable = array('name', 'id', 'form_key', 'created_at');

        $orderby = HashFormHelper::get_var('orderby', 'sanitize_text_field', 'created_at');
        if (!in_array($orderby, $sortable, true)) {
            $orderby = 'created_at';
        }

        $order = strtolower(HashFormHelper::get_var('order', 'sanitize_text_field', 'desc'));

        if ('id' === $orderby) {
            $result = (int) $a['id'] < (int) $b['id'] ? -1 : (((int) $a['id'] > (int) $b['id']) ? 1 : 0);
        } else {
            $result = strcmp($a[$orderby], $b[$orderby]);
        }

        return ($order === 'asc') ? $result : -$result;
    }

    private function get_table_data() {
        global $wpdb;
        $status = $this->status;
        $search = htmlspecialchars_decode(HashFormHelper::get_var('s'));

        if ($search) {
            return $wpdb->get_results($wpdb->prepare("SELECT * from {$wpdb->prefix}hashform_forms WHERE status=%s AND name Like %s", $status, '%' . $wpdb->esc_like($search) . '%'), ARRAY_A);
        } else {
            return $wpdb->get_results($wpdb->prepare("SELECT * from {$wpdb->prefix}hashform_forms WHERE status=%s", $status), ARRAY_A);
        }
    }

    public static function get_published_table_data() {
        global $wpdb;
        $status = 'published';
        $search = htmlspecialchars_decode(HashFormHelper::get_var('s'));

        if ($search) {
            return $wpdb->get_results($wpdb->prepare("SELECT * from {$wpdb->prefix}hashform_forms WHERE status=%s AND name Like %s", $status, '%' . $wpdb->esc_like($search) . '%'), ARRAY_A);
        } else {
            return $wpdb->get_results($wpdb->prepare("SELECT * from {$wpdb->prefix}hashform_forms WHERE status=%s", $status), ARRAY_A);
        }
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
                $this->extra_tablenav($which);
            }

            $this->pagination($which);
            ?>
            <br class="clear" />
        </div>
        <?php
    }

    public function extra_tablenav($which) {
        if ('trash' == $this->status) {
            ?>
            <div class="alignleft actions"><?php submit_button(esc_html__('Empty Trash', 'hash-form'), 'apply', 'delete_all', false); ?></div>
            <?php
        }

        if ('top' === $which) {
            // Where add-ons hang their own form tools, mirroring the entries
            // list. Pro uses it; nothing in the free plugin does.
            do_action('hashform_forms_tablenav', $this->status);
        }
    }

    public function get_sortable_columns() {
        return array(
            'name' => array('name', false),
            'id' => array('id', false),
            'entries' => array('form_key', false),
            'created_at' => array('created_at', false),
        );
    }

    public function get_action_links($item) {
        $form_id = $item['id'];
        $actions = array();
        $trash_links = $this->delete_trash_links($form_id);
        if ('trash' == $this->status) {
            $actions['restore'] = $trash_links['restore'];
            $actions['delete'] = $trash_links['delete'];
        } else {
            $actions['duplicate'] = array(
                'label' => esc_html__('Duplicate', 'hash-form'),
                'url' => wp_nonce_url('?page=hashform&hashform_action=duplicate&id=' . $form_id, 'duplicate_form_' . absint($form_id))
            );
            $actions['edit'] = array(
                'label' => esc_html__('Edit', 'hash-form'),
                'url' => admin_url('admin.php?page=hashform&hashform_action=edit&id=' . $form_id)
            );
            $actions['view'] = array(
                'label' => esc_html__('Preview', 'hash-form'),
                'url' => admin_url('admin-ajax.php?action=hashform_preview&form=' . $form_id)
            );
            $actions['trash'] = $trash_links['trash'];
        }
        return $actions;
    }

    private function delete_trash_links($id) {
        $base_url = '?page=hashform&id=' . $id;
        return array(
            'restore' => array(
                'label' => esc_html__('Restore', 'hash-form'),
                'url' => wp_nonce_url($base_url . '&hashform_action=untrash', 'untrash_form_' . absint($id)),
            ),
            'delete' => array(
                'label' => esc_html__('Delete Permanently', 'hash-form'),
                'url' => wp_nonce_url($base_url . '&hashform_action=destroy', 'destroy_form_' . absint($id)),
            ),
            'trash' => array(
                'label' => esc_html__('Trash', 'hash-form'),
                'url' => wp_nonce_url($base_url . '&hashform_action=trash', 'trash_form_' . absint($id)),
            )
        );
    }

    public function get_entry_link($id) {
        $count = $this->get_entry_count($id);

        // Dimmed at zero so the rows that actually have submissions are the
        // ones the eye lands on when scanning the column.
        $class = 'hf-entry-badge' . ($count ? '' : ' is-empty');

        return '<a class="' . esc_attr($class) . '" href="' . esc_url(admin_url('admin.php?page=hashform-entries&form_id=' . $id)) . '">' . esc_html(number_format_i18n($count)) . '</a>';
    }

    private static function get_entry_counts() {
        if (null === self::$entry_counts) {
            self::$entry_counts = HashFormEntry::get_entry_counts();
        }
        return self::$entry_counts;
    }

    private function get_entry_count($id) {
        $counts = self::get_entry_counts();
        return isset($counts[$id]) ? (int) $counts[$id] : 0;
    }

    /**
     * The shortcode as a click-to-copy chip. A plain <button> rather than a
     * link because this row sits inside the GET filter form, where a stray
     * submit would reload the screen.
     */
    private function get_shortcode_chip($id) {
        $shortcode = '[hashform id="' . $id . '"]';

        return '<button type="button" class="hf-shortcode-chip" data-hf-clipboard="' . esc_attr($shortcode) . '">'
                . '<code>' . esc_html($shortcode) . '</code>'
                . '<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>'
                . '<span class="screen-reader-text">' . esc_html__('Copy shortcode', 'hash-form') . '</span>'
                . '</button>';
    }

    /**
     * Totals for the header bar. Static so the screen can print them without
     * having to prepare the table first.
     */
    public static function get_stats() {
        $counts = self::get_count();

        return array(
            'forms' => (int) $counts->published,
            'entries' => array_sum(self::get_entry_counts()),
            'trash' => (int) $counts->trash,
        );
    }

    public function get_views() {
        $statuses = array(
            'published' => esc_html__('All', 'hash-form'),
            'trash' => esc_html__('Trash', 'hash-form'),
        );

        $links = array();

        $counts = self::get_count();

        foreach ($statuses as $status => $name) {
            // Trash only earns a tab once something is in it. All keeps its
            // tab either way, so a lone Trash link never sits there on its
            // own with no way back.
            if ('published' !== $status && !$counts->{$status}) {
                continue;
            }

            $links[$status] = HashFormHelper::view_tab(
                            admin_url('admin.php?page=hashform&status=' . $status), $name, $counts->{$status}, $status == $this->status
            );
        }

        return $links;
    }

    public function views() {
        HashFormHelper::render_view_tabs($this->get_views());
    }

    public static function get_count() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT status, COUNT(*) AS count FROM {$wpdb->prefix}hashform_forms GROUP BY status");
        $counts = array('published' => 0, 'trash' => 0);
        foreach ($results as $row) {
            if ('trash' != $row->status) {
                $counts['published'] += $row->count;
            } else {
                $counts['trash'] += $row->count;
            }
        }
        return (object) $counts;
    }

    public static function get_status($id = 0) {
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare("SELECT status FROM {$wpdb->prefix}hashform_forms WHERE id=%d", $id));
        return isset($results[0]) ? $results[0]->status : 'unavailable';
    }

}
