<?php
defined('ABSPATH') || die();

class HashFormPreview {

    public function __construct() {
        /*
         * Logged in only, and only for someone who may look at forms.
         *
         * The preview is opened from the builder and from the forms list, both
         * of which are already behind that capability, so nothing legitimate
         * reaches this without it. It used to be registered for nopriv as well,
         * which let anyone walk the form ids and render each one.
         */
        add_action('wp_ajax_hashform_preview', array($this, 'preview'));
    }

    public static function preview() {
        if (!HashFormCapabilities::user_can('hashform_view_forms')) {
            wp_die(
                    esc_html__('You do not have permission to preview forms.', 'hash-form'),
                    esc_html__('Permission denied', 'hash-form'),
                    array('response' => 403)
            );
        }

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
     * Mark the fields a rule acts on, for the preview only.
     *
     * The preview runs the rules for real, so a hidden field is simply not
     * there and nothing says why. This marks both ends - the field a rule shows
     * or hides, and the field deciding it - and reports anything hidden at the
     * moment in a bar along the bottom.
     *
     * Printed here rather than in the field markup because it must never reach
     * a visitor: this file is only ever included by the preview screen, which
     * is behind a capability check.
     *
     * @param int $form_id
     */
    public static function condition_hints($form_id) {
        $hints = HashFormBuilder::get_condition_hints($form_id);

        if (!$hints) {
            return;
        }

        $payload = array();

        foreach ($hints as $field_id => $roles) {
            $payload[] = array(
                'id' => (int) $field_id,
                'target' => !empty($roles['target']),
                'rules' => array_merge(
                        isset($roles['target']) ? $roles['target'] : array(),
                        isset($roles['trigger']) ? $roles['trigger'] : array()
                ),
            );
        }
        ?>
        <style>
            .hf-preview-rule {
                max-width: 100%;
                background: #fff;
                border: 1px solid #e2e5ea;
                border-radius: 999px;
                color: #646970;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                font-size: 11px;
                line-height: 1;
                margin: 4px 0 0;
                padding: 3px 8px;
                vertical-align: middle;
            }

            .hf-preview-rule::before {
                background: #dd823b;
                border-radius: 50%;
                content: "";
                height: 6px;
                width: 6px;
            }

            .hf-preview-rule-trigger::before {
                background: #8c9196;
            }

            .hf-preview-rule-text {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .hf-preview-rule-more {
                flex: 0 0 auto;
                opacity: .75;
            }

            .hf-preview-hidden-note {
                background: #1e2327;
                border-radius: 6px;
                bottom: 12px;
                color: #fff;
                font-size: 12px;
                left: 12px;
                padding: 8px 12px;
                position: fixed;
                z-index: 99999;
            }
        </style>
        <script>
            (function () {
                var hints = <?php echo wp_json_encode($payload); ?>;

                function chip(rules, isTarget) {
                    var el = document.createElement('span');
                    el.className = 'hf-preview-rule' + (isTarget ? '' : ' hf-preview-rule-trigger');

                    /*
                     * The rule itself, the same as on the canvas: a chip that
                     * only says "Conditional" sends you off to the Settings tab
                     * to find out which rule it meant.
                     *
                     * In its own element because the chip is a flex container,
                     * and text-overflow has nothing to trim on one of those -
                     * it needs a block of its own to ellipsis.
                     */
                    var text = document.createElement('span');
                    text.className = 'hf-preview-rule-text';
                    text.textContent = rules[0];
                    el.appendChild(text);

                    if (rules.length > 1) {
                        var more = document.createElement('span');
                        more.className = 'hf-preview-rule-more';
                        more.textContent = '+' + (rules.length - 1);
                        el.appendChild(more);
                    }

                    el.title = rules.join('\n');
                    return el;
                }

                function paint() {
                    var hidden = 0;

                    hints.forEach(function (hint) {
                        var box = document.getElementById('hf-field-container-' + hint.id);

                        if (!box) {
                            return;
                        }

                        /*
                         * A rule that has already hidden the field leaves
                         * nothing to mark, so it is counted instead.
                         *
                         * The test is the inline display the rule engine
                         * writes - frontend.js hides with jQuery's toggle() -
                         * and not whether the field is on screen. On a
                         * multi-step form every field of every other step is
                         * off screen too, and reporting those as hidden by a
                         * rule would be wrong.
                         */
                        if (box.style.display === 'none') {
                            hidden++;
                            return;
                        }

                        if (!box.querySelector('.hf-preview-rule')) {
                            box.appendChild(chip(hint.rules, hint.target));
                        }
                    });

                    var note = document.getElementById('hf-preview-hidden-note');

                    if (!hidden) {
                        if (note) {
                            note.remove();
                        }
                        return;
                    }

                    if (!note) {
                        note = document.createElement('div');
                        note.id = 'hf-preview-hidden-note';
                        note.className = 'hf-preview-hidden-note';
                        document.body.appendChild(note);
                    }

                    note.textContent = <?php echo wp_json_encode(__('Hidden by a rule right now:', 'hash-form')); ?> + ' ' + hidden;
                }

                /*
                 * Watch the containers rather than listen for events.
                 *
                 * The rules are wired up on jQuery ready and settled with a
                 * trigger('change') that a plain listener on document does not
                 * reliably see, so painting once on DOMContentLoaded marked a
                 * field that was hidden a moment later. The engine hides by
                 * writing an inline style, and that is exactly what this
                 * watches - so the marks follow the rules however they are
                 * re-evaluated, including as the visitor types.
                 */
                function watch() {
                    /*
                     * Coalesced rather than painted per mutation.
                     *
                     * Changing one answer re-evaluates every rule, and each
                     * one that flips writes its own style. Painting on the
                     * first of those counted a state the cascade had not
                     * finished reaching, so the tally could read one high
                     * until something else moved. Waiting for the burst to
                     * stop counts once, on the settled state.
                     */
                    var pending = null;
                    var observer = new MutationObserver(function () {
                        window.clearTimeout(pending);
                        pending = window.setTimeout(paint, 50);
                    });

                    hints.forEach(function (hint) {
                        var box = document.getElementById('hf-field-container-' + hint.id);

                        if (box) {
                            observer.observe(box, { attributes: true, attributeFilter: ['style', 'class'] });
                        }
                    });

                    paint();
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', watch);
                } else {
                    watch();
                }
            }());
        </script>
        <?php
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