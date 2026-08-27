<?php
defined('ABSPATH') || die();
/*
 * A template, included from inside a class method - never loaded on its own.
 * The variables below are locals of the method that includes it, not globals,
 * which is what the prefix sniff assumes about a file-scope assignment.
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from within a method, so these are function locals.

$id = htmlspecialchars_decode(HashFormHelper::get_var('id', 'absint'));
$form = HashFormBuilder::get_form_vars($id);

if (!$form) {
    ?>
    <h3><?php esc_html_e('You are trying to edit a form that does not exist.', 'hash-form'); ?></h3>
    <?php
    return;
}
$fields = HashFormFields::get_form_fields($form->id);
$values = HashFormHelper::process_form_array($form);

$edit_message = '<span class="mdi mdi-check-circle"></span>' . esc_html__('Form was successfully updated.', 'hash-form');
$has_fields = isset($fields) && !empty($fields);

if (!empty($fields)) {
    $vars = HashFormHelper::get_fields_array($id);
}

if (defined('DOING_AJAX')) {
    wp_die();
} else {
    ?>
    <div id="hf-wrap" class="hf-content">
        <?php
        self::get_admin_header(
            array(
                'form' => $form,
                'class' => 'hf-header-nav',
            )
        );
        ?>
        <div class="hf-body">
            <?php require(HASHFORM_PATH . 'admin/forms/build/sidebar.php'); ?>

            <div id="hf-form-panel">
                <div class="hf-form-wrap">
                    <?php
                    /*
                     * The canvas was an unbroken white rectangle with no anchor
                     * of its own. A slim header names what is being edited and
                     * gives the card a top edge to sit under.
                     */
                    $hf_field_count = count(HashFormFields::get_form_fields($form->id));
                    ?>
                    <div class="hf-canvas-header">
                        <span class="hf-canvas-title"><?php echo esc_html($form->name); ?></span>
                        <span class="hf-canvas-count">
                            <?php
                            printf(
                                    /* translators: %s: number of fields on the form. */
                                    esc_html(_n('%s field', '%s fields', $hf_field_count, 'hash-form')),
                                    esc_html(number_format_i18n($hf_field_count))
                            );
                            ?>
                        </span>
                    </div>

                    <form method="post">
                        <?php require(HASHFORM_PATH . 'admin/forms/build/builder.php'); ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
}