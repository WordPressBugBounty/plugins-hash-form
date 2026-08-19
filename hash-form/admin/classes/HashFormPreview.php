<?php
defined('ABSPATH') || die();

class HashFormPreview {

    public function __construct() {
        add_action('wp_ajax_hashform_preview', array($this, 'preview'));
        add_action('wp_ajax_nopriv_hashform_preview', array($this, 'preview'));
    }

    public static function preview() {
        header('Content-Type: text/html; charset=' . get_option('blog_charset'));
        $id = htmlspecialchars_decode(HashFormHelper::get_var('form', 'absint'));
        $form = HashFormBuilder::get_form_vars($id);

        // This endpoint is public, so an unknown or trashed id must not reach
        // the template, which dereferences $form before it checks anything.
        if (!$form || $form->status === 'trash') {
            wp_die(esc_html__('Please select a valid form', 'hash-form'));
        }

        /*
         * Two documents behind one URL. The outer one is a plain shell — a
         * toolbar and an iframe — and the inner one is the form on its own,
         * rendered with the theme and every front-end asset exactly as a
         * visitor would get it.
         *
         * The width buttons have to change a real viewport to be worth
         * anything: narrowing a div would leave the form's own media queries
         * reading the desktop window and reporting a mobile layout that is
         * not what a phone gets.
         */
        if (HashFormHelper::get_var('hf_frame', 'absint')) {
            require HASHFORM_PATH . 'admin/forms/preview/preview.php';
        } else {
            require HASHFORM_PATH . 'admin/forms/preview/shell.php';
        }

        wp_die();
    }

    /**
     * The URL of the form on its own, without the surrounding toolbar.
     */
    public static function frame_url($id) {
        return add_query_arg(
                array(
                    'action' => 'hashform_preview',
                    'form' => absint($id),
                    'hf_frame' => 1,
                ),
                admin_url('admin-ajax.php')
        );
    }

    /**
     * Widths the preview can be shown at.
     *
     * Values are the iframe width in pixels; 0 means fill the window.
     */
    public static function preview_widths() {
        return array(
            'desktop' => array('label' => esc_html__('Desktop', 'hash-form'), 'width' => 0, 'icon' => 'monitor'),
            'tablet' => array('label' => esc_html__('Tablet', 'hash-form'), 'width' => 768, 'icon' => 'tablet'),
            'mobile' => array('label' => esc_html__('Mobile', 'hash-form'), 'width' => 390, 'icon' => 'mobile'),
        );
    }

    public static function show_form($id) {
        $form = HashFormBuilder::get_form_vars($id);
        if (!$form || $form->status === 'trash') {
            // Callers buffer output, so the message must be echoed.
            echo esc_html__('Please select a valid form', 'hash-form');
            return;
        }

        self::get_form_contents($id, $form);
    }

    public static function get_form_contents($id, $form = null) {
        // Frontend assets are registered globally but only loaded once a form
        // actually renders.
        HashFormLoader::enqueue_form_assets();

        $form = $form ? $form : HashFormBuilder::get_form_vars($id);

        // Scheduled, limited or login-only forms show their notice instead.
        $restriction = HashFormRestrictions::check($form);

        if (empty($restriction['allowed'])) {
            echo wp_kses_post(HashFormRestrictions::get_closed_html($restriction));
            return;
        }

        $values = HashFormHelper::get_fields_array($id);

        $styles = $form->styles ? $form->styles : '';

        $form_class = array('hashform-form');
        $form_class[] = isset($form->options['form_css_class']) ? $form->options['form_css_class'] : '';
        $form_class[] = $styles && isset($styles['form_style']) ? 'hf-form-' . esc_attr($styles['form_style']) : 'hf-form-default-style';
        $form_class = apply_filters('hashform_form_classes', $form_class);
        ?>

        <div class="hf-form-tempate">
            <form enctype="multipart/form-data" method="post" class="<?php echo esc_attr(implode(' ', array_filter($form_class))); ?>" id="hf-form-id-<?php echo esc_attr($form->form_key); ?>" novalidate>
                <?php
                do_action('hash_form_before_form_start', $form);
                if (apply_filters('hash_form_should_show_form', true)) {
                    require HASHFORM_PATH . 'admin/forms/style/form.php';
                    $form_msg = HashFormHelper::get_var('hf_success');
                    if ($form_msg == 'true') {
                        ?>
                        <span class="hf-success-msg"><?php echo esc_html($form->settings['confirmation_message']); ?></span>
                        <?php
                    }
                }
                ?>
            </form>
        </div>
        <?php
    }

}

new HashFormPreview();