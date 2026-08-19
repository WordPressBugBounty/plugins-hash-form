<?php
defined('ABSPATH') || die();

$conditional_logics = HashFormBuilder::get_show_hide_conditions($id);
?>
<div class="hf-form-container">
    <div class="hf-form-row">
        <p class="hf-section-help">
            <?php esc_html_e('Show or hide a field based on what the visitor has answered elsewhere in this form. Rules are checked as they type, and again when the form is submitted.', 'hash-form'); ?>
        </p>

        <div class="hf-condition-list<?php echo empty($conditional_logics) ? ' hf-condition-list-empty' : ''; ?>">
            <div class="hf-condition-empty">
                <span class="mdi mdi-directions-fork" aria-hidden="true"></span>
                <p class="hf-condition-empty-title"><?php esc_html_e('No rules yet', 'hash-form'); ?></p>
                <p class="hf-condition-empty-text"><?php esc_html_e('Every field is shown to everyone until you add one.', 'hash-form'); ?></p>
            </div>

            <div class="hf-condition-rows">
                <?php
                foreach ($conditional_logics as $row) {
                    HashFormBuilder::condition_row_html($fields, $row);
                }
                ?>
            </div>
        </div>

        <button type="button" class="hf-add-more-condition">
            <span class="mdi mdi-plus" aria-hidden="true"></span>
            <?php esc_html_e('Add Condition', 'hash-form'); ?>
        </button>
    </div>
</div>
