<?php
defined('ABSPATH') || die();
/*
 * A template, included from inside a class method - never loaded on its own.
 * The variables below are locals of the method that includes it, not globals,
 * which is what the prefix sniff assumes about a file-scope assignment.
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from within a method, so these are function locals.

$form_id = HashFormHelper::get_var('id', 'absint');

$hf_shortcode = '[hashform id="' . absint($form_id) . '"]';
$hf_php_tag = '<?php echo do_shortcode( \'' . $hf_shortcode . '\' ); ?>';
?>
<div id="hf-shortcode-form-modal" role="dialog" aria-modal="true" aria-labelledby="hf-shortcode-title" aria-hidden="true">
    <div class="hf-shortcode-modal-wrap">

        <h3 id="hf-shortcode-title"><?php esc_html_e('Embed this form', 'hash-form'); ?></h3>

        <button type="button" class="hf-modal-close hashform-close-form-modal">
            <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
            <span class="screen-reader-text"><?php esc_html_e('Close', 'hash-form'); ?></span>
        </button>

        <?php
        /*
         * A single wrapper, because the dialog shell treats its one non-heading
         * child as the body and gives it the panel's padding. This replaces a
         * <form> that never submitted anything and shared its id with the
         * Create New Form dialog's form.
         */
        ?>
        <div class="hf-embed-body">

            <p class="hf-embed-intro"><?php esc_html_e('Three ways to place this form. Use whichever suits where it is going.', 'hash-form'); ?></p>

            <div class="hf-embed-method">
                <label class="hf-embed-label" for="hf-embed-shortcode"><?php esc_html_e('Shortcode', 'hash-form'); ?></label>

                <div class="hf-embed-field">
                    <?php // readonly rather than disabled: a disabled input cannot be focused or selected, so it could not be copied by hand if the clipboard was refused. ?>
                    <input type="text" id="hf-embed-shortcode" class="hf-embed-input" readonly value="<?php echo esc_attr($hf_shortcode); ?>" />

                    <button type="button" class="hf-embed-copy" data-hf-clipboard="<?php echo esc_attr($hf_shortcode); ?>">
                        <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                        <span class="screen-reader-text"><?php esc_html_e('Copy shortcode', 'hash-form'); ?></span>
                    </button>
                </div>

                <p class="hf-embed-help"><?php esc_html_e('Paste into any post, page or text widget.', 'hash-form'); ?></p>
            </div>

            <div class="hf-embed-method">
                <label class="hf-embed-label" for="hf-embed-php"><?php esc_html_e('PHP template tag', 'hash-form'); ?></label>

                <div class="hf-embed-field">
                    <input type="text" id="hf-embed-php" class="hf-embed-input" readonly value="<?php echo esc_attr($hf_php_tag); ?>" />

                    <button type="button" class="hf-embed-copy" data-hf-clipboard="<?php echo esc_attr($hf_php_tag); ?>">
                        <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
                        <span class="screen-reader-text"><?php esc_html_e('Copy PHP snippet', 'hash-form'); ?></span>
                    </button>
                </div>

                <p class="hf-embed-help"><?php esc_html_e('For dropping the form straight into a theme template file.', 'hash-form'); ?></p>
            </div>

            <div class="hf-embed-method hf-embed-method-plain">
                <span class="hf-embed-label"><?php esc_html_e('Block editor', 'hash-form'); ?></span>

                <p class="hf-embed-help">
                    <?php
                    printf(
                            /* translators: %s: the name of the block, shown in the block inserter. */
                            esc_html__('Add the %s block and choose this form from the list. No code needed.', 'hash-form'),
                            '<strong>' . esc_html__('Hash Form', 'hash-form') . '</strong>'
                    );
                    ?>
                </p>
            </div>

            <div class="hf-shortcode-footer">
                <a href="#" class="hashform-close-form-modal"><?php esc_html_e('Close', 'hash-form'); ?></a>
            </div>
        </div>
    </div>
</div>
