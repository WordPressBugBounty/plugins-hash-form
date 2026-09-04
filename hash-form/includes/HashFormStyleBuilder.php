<?php
defined('ABSPATH') || die();

/**
 * The style template builder screen.
 *
 * A style template is a set of panels, not a document, so the post editor
 * around it - title box, publish box, screen options - is chrome nobody uses.
 * Two of those boxes were already being removed one by one; this takes the
 * editor out of the picture instead. The editor is redirected here and this
 * screen owns the whole page.
 *
 * The panel itself is untouched: this includes admin/styles/settings.php, so
 * the sidebar, the live preview, the fields and the footer are exactly what
 * they were. The builder is addressed with the same `post` query argument the
 * editor used, which is what that panel reads to decide between Publish and
 * Update, so it needed no change at all.
 */
class HashFormStyleBuilder {

    const PAGE_SLUG = 'hashform-style-builder';
    const POST_TYPE = 'hashform-styles';

    /** @var WP_Post|null The template the header is describing. */
    private $editing = null;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_page'), 21);
        add_action('admin_head', array($this, 'hide_page'));

        add_action('load-post.php', array($this, 'redirect_edit'));
        add_action('load-post-new.php', array($this, 'redirect_new'));

        add_filter('parent_file', array($this, 'parent_file'));
        add_filter('submenu_file', array($this, 'submenu_file'));
        add_filter('admin_body_class', array($this, 'body_class'));

        add_action('in_admin_header', array($this, 'list_header'));

        /*
         * The admin footer belongs to a document-shaped screen. This one is a
         * full-height editor with its own save bar pinned to the bottom, and
         * "Thank you for creating with WordPress" underneath it just pushes
         * that bar off the fold.
         */
        add_filter('admin_footer_text', array($this, 'footer_text'));
        add_filter('update_footer', array($this, 'footer_text'), 11);
    }

    /**
     * The builder URL for a template.
     *
     * `post` rather than a name of its own: admin/styles/settings.php already
     * reads that argument to work out whether the template is published, and
     * addressing the builder the same way the editor was keeps the panel
     * working untouched.
     *
     * Deliberately nothing else. A new template used to be addressed with
     * post_type as well, the way post-new.php was, and that argument is what
     * wp-admin/admin.php reads into $typenow - so core resolved this screen's
     * hook against `admin.php?post_type=hashform-styles`, a parent with no
     * entry in $admin_page_hooks, looked for admin_page_hashform-style-builder
     * rather than the hash-form_page_ name the submenu is registered under,
     * found nothing and died with "Cannot load hashform-style-builder". The
     * panel does not need it: it reads Publish or Update off the template's
     * own post status.
     *
     * @param int $post_id
     * @return string
     */
    public static function url($post_id = 0) {
        $args = array('page' => self::PAGE_SLUG);

        if ($post_id) {
            $args['post'] = absint($post_id);
        }

        return add_query_arg($args, admin_url('admin.php'));
    }

    /**
     * The style template list.
     *
     * @return string
     */
    public static function list_url() {
        return admin_url('edit.php?post_type=' . self::POST_TYPE);
    }

    /**
     * Whether the screen being rendered is the builder.
     *
     * @return bool
     */
    public static function is_builder() {
        return is_admin() && self::PAGE_SLUG === HashFormHelper::get_var('page', 'sanitize_title');
    }

    /**
     * Registered so it is reachable, hidden because it edits one template.
     */
    public function add_page() {
        add_submenu_page(
                'hashform',
                esc_html__('Edit Style Template', 'hash-form'),
                esc_html__('Edit Style Template', 'hash-form'),
                'edit_hashform_styles',
                self::PAGE_SLUG,
                array($this, 'render')
        );
    }

    public function hide_page() {
        remove_submenu_page('hashform', self::PAGE_SLUG);
    }

    /**
     * Sends the post editor to the builder.
     */
    public function redirect_edit() {
        $post_id = absint(HashFormHelper::get_var('post', 'absint'));

        if (!$post_id || self::POST_TYPE !== get_post_type($post_id)) {
            return;
        }

        // Somebody who cannot edit it should meet the editor's own message
        // rather than a builder that will not save.
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        wp_safe_redirect(self::url($post_id));
        exit;
    }

    /**
     * Sends Add New to the builder.
     */
    public function redirect_new() {
        if (self::POST_TYPE !== HashFormHelper::get_var('post_type', 'sanitize_key')) {
            return;
        }

        if (!current_user_can('edit_hashform_styles')) {
            return;
        }

        wp_safe_redirect(self::url());
        exit;
    }

    /**
     * The style list stays highlighted rather than the hidden builder entry.
     */
    public function parent_file($parent_file) {
        return self::is_builder() ? 'hashform' : $parent_file;
    }

    public function submenu_file($submenu_file) {
        return self::is_builder() ? 'edit.php?post_type=' . self::POST_TYPE : $submenu_file;
    }

    /**
     * Empties the admin footer on this screen, and leaves it alone elsewhere.
     *
     * @param string $text
     * @return string
     */
    public function footer_text($text) {
        return self::is_builder() ? '' : $text;
    }

    public function body_class($classes) {
        return self::is_builder() ? $classes . ' hf-style-builder-screen' : $classes;
    }

    /**
     * The template being edited.
     *
     * A new one is a post object that exists only for this request. post-new.php
     * used to write an auto-draft just for opening the screen, which is why the
     * templates list collects rows nobody asked for; nothing is written here
     * until Save, and the panel reads its defaults from an id of 0 quite
     * happily.
     *
     * @return WP_Post|null
     */
    protected function current_post() {
        $post_id = absint(HashFormHelper::get_var('post', 'absint'));

        if ($post_id) {
            $post = get_post($post_id);

            return ($post && self::POST_TYPE === $post->post_type) ? $post : null;
        }

        return new WP_Post((object) array(
            'ID' => 0,
            'post_title' => '',
            'post_type' => self::POST_TYPE,
            'post_status' => 'auto-draft',
        ));
    }

    public function render() {
        if (!current_user_can('edit_hashform_styles')) {
            wp_die(esc_html__('You are not allowed to edit style templates.', 'hash-form'));
        }

        $post = $this->current_post();

        if (!$post) {
            wp_die(esc_html__('That style template no longer exists.', 'hash-form'));
        }

        // The panel reads the template from the global, exactly as it did
        // inside the metabox.
        $GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        setup_postdata($post);

        $this->editing = $post;
        ?>
        <?php
        /*
         * No .wrap of its own: admin/styles/settings.php opens the .hf-content
         * the panel's styles are scoped to, and the bar above comes from
         * in_admin_header like every other screen. Wrapping the panel in a
         * second frame is what broke the sidebar headings.
         */
        ?>
        <form method="post" id="post" class="hf-style-builder">
            <input type="hidden" id="post_ID" name="post_ID" value="<?php echo absint($post->ID); ?>"/>
            <?php
            // The panel, unchanged. Rendered through HashFormStyles so that
            // `self` inside the template resolves to that class, which is where
            // the helpers it calls live.
            HashFormStyles::render_settings_panel();
            ?>
        </form>
        <?php
        wp_reset_postdata();
    }


    /**
     * The bar across the top, through the shared renderer.
     *
     * Printed on in_admin_header like every other hashform screen, so this
     * reads as the same product as the list it came from rather than carrying
     * a header of its own.
     */
    public function list_header() {
        if (!self::is_builder()) {
            return;
        }

        $post = $this->editing ? $this->editing : $this->current_post();

        if (!$post) {
            return;
        }

        $actions = array();

        if ($post->ID) {
            $trash = get_delete_post_link($post->ID);

            if ($trash) {
                // post.php sends the browser back where it came from, which
                // here is a builder for a template that no longer exists.
                $actions[] = array(
                    'label' => esc_html__('Trash', 'hash-form'),
                    'url' => add_query_arg('_wp_http_referer', rawurlencode(self::list_url()), $trash),
                    'class' => 'hf-list-action-trash',
                );
            }
        }

        $actions[] = array(
            'label' => esc_html__('All Style Templates', 'hash-form'),
            'url' => self::list_url(),
        );

        HashFormHelper::render_list_header(array(
            'title' => $post->ID
                    ? esc_html__('Style template name', 'hash-form')
                    : esc_html__('Add New Style Template', 'hash-form'),
            /*
             * The name lives in the bar rather than in a row of its own. The
             * form attribute is what carries it: this is printed on
             * in_admin_header, outside the builder's form, and a field that is
             * not associated with that form is simply dropped on save.
             */
            'title_field' => array(
                'name' => 'post_title',
                'value' => $post->post_title,
                'placeholder' => esc_html__('Name this style template', 'hash-form'),
                'form' => 'post',
            ),
            'actions' => $actions,
        ));
    }

}

new HashFormStyleBuilder();
