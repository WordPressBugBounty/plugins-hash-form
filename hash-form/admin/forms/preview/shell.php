<?php
defined('ABSPATH') || die();
/*
 * A template, included from inside a class method - never loaded on its own.
 * The variables below are locals of the method that includes it, not globals,
 * which is what the prefix sniff assumes about a file-scope assignment.
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from within a method, so these are function locals.

/*
 * The preview shell: a toolbar and an iframe holding the form.
 *
 * Deliberately self-contained — no wp_head(), no wp_footer(). The theme and
 * every front-end asset belong to the document inside the frame, which is
 * the thing being previewed; loading them out here as well would style the
 * toolbar with whatever the site happens to use and double the page weight.
 */

$hf_widths = HashFormPreview::preview_widths();
$hf_can_edit = HashFormCapabilities::user_can('hashform_edit_forms');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

    <head>
        <title><?php echo esc_html($form->name); ?> — <?php esc_html_e('Preview', 'hash-form'); ?></title>
        <meta charset="<?php bloginfo('charset'); ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <?php // The endpoint answers to logged-out visitors, so keep it out of the index. ?>
        <meta name="robots" content="noindex, nofollow" />
        <style>
            :root {
                --hfp-bar: #1e2327;
                --hfp-bar-text: #e6e8ea;
                --hfp-muted: #9aa0a6;
                --hfp-line: rgba(255, 255, 255, .14);
                --hfp-active: rgba(255, 255, 255, .14);
                --hfp-stage: #eceef1;
            }

            * { box-sizing: border-box; }

            html, body {
                height: 100%;
                margin: 0;
            }

            body {
                background: var(--hfp-stage);
                display: flex;
                flex-direction: column;
                font: 13px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            }

            .hfp-bar {
                align-items: center;
                background: var(--hfp-bar);
                color: var(--hfp-bar-text);
                display: flex;
                flex: 0 0 auto;
                gap: 16px;
                justify-content: space-between;
                padding: 0 16px;
                min-height: 48px;
            }

            .hfp-id {
                align-items: center;
                display: flex;
                gap: 8px;
                min-width: 0;
            }

            .hfp-tag {
                background: var(--hfp-active);
                border-radius: 999px;
                color: var(--hfp-bar-text);
                font-size: 11px;
                font-weight: 600;
                letter-spacing: .04em;
                padding: 3px 8px;
                text-transform: uppercase;
                white-space: nowrap;
            }

            .hfp-name {
                font-weight: 600;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .hfp-sizes {
                display: flex;
                gap: 2px;
            }

            .hfp-size {
                background: none;
                border: 0;
                border-radius: 6px;
                color: var(--hfp-muted);
                cursor: pointer;
                font: inherit;
                padding: 6px 12px;
                transition: background-color .15s ease, color .15s ease;
            }

            .hfp-size:hover { color: var(--hfp-bar-text); }

            .hfp-size[aria-pressed="true"] {
                background: var(--hfp-active);
                color: #fff;
            }

            .hfp-size:focus-visible {
                outline: 2px solid #fff;
                outline-offset: 1px;
            }

            .hfp-actions {
                align-items: center;
                display: flex;
                gap: 14px;
            }

            .hfp-note { color: var(--hfp-muted); }

            .hfp-edit {
                border: 1px solid var(--hfp-line);
                border-radius: 6px;
                color: var(--hfp-bar-text);
                padding: 6px 12px;
                text-decoration: none;
                transition: background-color .15s ease;
                white-space: nowrap;
            }

            .hfp-edit:hover { background: var(--hfp-active); }

            .hfp-stage {
                display: flex;
                flex: 1 1 auto;
                justify-content: center;
                min-height: 0;
                overflow: auto;
                padding: 0;
            }

            /* Padding only once the frame is narrowed, so a desktop preview is
               edge to edge exactly as the page would be. */
            .hfp-stage.is-constrained { padding: 24px; }

            .hfp-frame {
                background: #fff;
                border: 0;
                flex: 0 0 auto;
                height: 100%;
                width: 100%;
            }

            .hfp-stage.is-constrained .hfp-frame {
                border-radius: 8px;
                box-shadow: 0 10px 30px -10px rgba(16, 24, 40, .25);
                height: 100%;
            }

            @media (max-width: 782px) {
                .hfp-bar { flex-wrap: wrap; padding: 8px 12px; }
                .hfp-note { display: none; }
            }
        </style>
    </head>

    <body>
        <div class="hfp-bar">
            <div class="hfp-id">
                <span class="hfp-tag"><?php esc_html_e('Preview', 'hash-form'); ?></span>
                <span class="hfp-name"><?php echo esc_html($form->name); ?></span>
            </div>

            <div class="hfp-sizes" role="group" aria-label="<?php esc_attr_e('Preview width', 'hash-form'); ?>">
                <?php foreach ($hf_widths as $hf_key => $hf_size) { ?>
                    <button type="button" class="hfp-size" data-width="<?php echo absint($hf_size['width']); ?>" aria-pressed="<?php echo ('desktop' === $hf_key) ? 'true' : 'false'; ?>">
                        <?php echo esc_html($hf_size['label']); ?>
                    </button>
                <?php } ?>
            </div>

            <div class="hfp-actions">
                <?php // The submit button is disabled in preview; say so rather than leaving people to discover it. ?>
                <span class="hfp-note"><?php esc_html_e('Submitting is off in preview', 'hash-form'); ?></span>
                <?php if ($hf_can_edit) { ?>
                    <a class="hfp-edit" href="<?php echo esc_url(admin_url('admin.php?page=hashform&hashform_action=edit&id=' . absint($form->id))); ?>"><?php esc_html_e('Edit form', 'hash-form'); ?></a>
                <?php } ?>
            </div>
        </div>

        <div class="hfp-stage" id="hfp-stage">
            <iframe class="hfp-frame" id="hfp-frame" title="<?php echo esc_attr(sprintf(/* translators: %s: form name. */ __('%s preview', 'hash-form'), $form->name)); ?>" src="<?php echo esc_url(HashFormPreview::frame_url($form->id)); ?>"></iframe>
        </div>

        <script>
            (function () {
                var stage = document.getElementById('hfp-stage');
                var frame = document.getElementById('hfp-frame');
                var buttons = document.querySelectorAll('.hfp-size');

                Array.prototype.forEach.call(buttons, function (button) {
                    button.addEventListener('click', function () {
                        var width = parseInt(button.getAttribute('data-width'), 10) || 0;

                        Array.prototype.forEach.call(buttons, function (other) {
                            other.setAttribute('aria-pressed', String(other === button));
                        });

                        stage.classList.toggle('is-constrained', width > 0);
                        frame.style.width = width ? width + 'px' : '100%';
                    });
                });
            }());
        </script>
    </body>

</html>
