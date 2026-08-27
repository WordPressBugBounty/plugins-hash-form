<?php
defined('ABSPATH') || die();
/*
 * A template, included from inside a class method - never loaded on its own.
 * The variables below are locals of the method that includes it, not globals,
 * which is what the prefix sniff assumes about a file-scope assignment.
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from within a method, so these are function locals.
$prev_entry = HashFormEntry::get_prev_entry($entry->id, $entry->form_id);
$prev_entry = isset($prev_entry[0]) ? $prev_entry[0] : '';
$prev_entry_id = isset($prev_entry->id) ? $prev_entry->id : '';
$prev_url = $prev_entry_id ? admin_url('admin.php?page=hashform-entries&hashform_action=view&id=' . $prev_entry_id) : '#';

$next_entry = HashFormEntry::get_next_entry($entry->id, $entry->form_id);
$next_entry = isset($next_entry[0]) ? $next_entry[0] : '';
$next_entry_id = isset($next_entry->id) ? $next_entry->id : '';
$next_url = $next_entry_id ? admin_url('admin.php?page=hashform-entries&hashform_action=view&id=' . $next_entry_id) : '#';

$entries_url = admin_url('admin.php?page=hashform-entries');
$delivery_failed = isset($entry->delivery_status) && !$entry->delivery_status;
?>

<div class="hf-form-entry-details-wrap wrap">
    <h1></h1>
    <div id="hf-form-entry-details">
        <div class="hf-entry-header">
            <div class="hf-entry-toolbar">
                <a class="hf-btn hf-entry-back" href="<?php echo esc_url($entries_url); ?>">
                    <i class="mdi mdi-chevron-left" aria-hidden="true"></i><?php esc_html_e('All Entries', 'hash-form'); ?>
                </a>

                <div class="hf-entry-header-actions">
                    <button type="button" class="hf-entry-star hf-entry-star-large<?php echo empty($entry->is_starred) ? '' : ' hf-starred'; ?>"
                            data-entry="<?php echo absint($entry->id); ?>"
                            data-starred="<?php echo empty($entry->is_starred) ? '0' : '1'; ?>"
                            aria-pressed="<?php echo empty($entry->is_starred) ? 'false' : 'true'; ?>"
                            aria-label="<?php esc_attr_e('Star this entry', 'hash-form'); ?>">
                        <span class="dashicons dashicons-star-<?php echo empty($entry->is_starred) ? 'empty' : 'filled'; ?>"></span>
                    </button>

                    <div class="hf-form-entry-navigation">
                        <?php if ($prev_url == '#') { ?>
                            <span class="hf-btn hf-form-entry-prev hf-disabled" aria-disabled="true">
                                <i class="mdi mdi-chevron-left" aria-hidden="true"></i><?php echo esc_html__('Previous', 'hash-form') ?>
                            </span>
                        <?php } else { ?>
                            <a class="hf-btn hf-form-entry-prev" href="<?php echo esc_url($prev_url); ?>">
                                <i class="mdi mdi-chevron-left" aria-hidden="true"></i><?php echo esc_html__('Previous', 'hash-form') ?>
                            </a>
                        <?php } ?>

                        <?php if ($next_url == '#') { ?>
                            <span class="hf-btn hf-form-entry-next hf-disabled" aria-disabled="true">
                                <?php echo esc_html__('Next', 'hash-form') ?><i class="mdi mdi-chevron-right" aria-hidden="true"></i>
                            </span>
                        <?php } else { ?>
                            <a class="hf-btn hf-form-entry-next" href="<?php echo esc_url($next_url); ?>">
                                <?php echo esc_html__('Next', 'hash-form') ?><i class="mdi mdi-chevron-right" aria-hidden="true"></i>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="hf-entry-identity">
                <h2 class="hf-entry-title">
                    <?php
                    /* translators: %s: numeric entry id. */
                    printf(esc_html__('Entry #%s', 'hash-form'), absint($entry->id));
                    ?>
                </h2>
                <p class="hf-entry-submitted">
                    <?php echo esc_html(HashFormEntry::get_entry_date($entry->id)); ?>
                </p>
            </div>
        </div>

        <table class="hf-entry-fields">
            <tbody>
                <?php
                $file_img_placeholder = HASHFORM_URL . 'img/attachment.png';
                foreach ($entry->metas as $id => $value) {
                    $title = $value['name'];
                    $entry_value = HashFormHelper::unserialize_or_decode($value['value']);
                    $entry_type = $value['type'];

                    if (is_array($entry_value)) {
                        $entry_value = array_filter($entry_value);
                        if ($entry_type == 'name') {
                            $entry_value = implode(' ', $entry_value);
                        } elseif ($entry_type == 'repeater_field') {
                            $entry_value = HashFormHelper::render_repeater_table($entry_value);
                        } else {
                            $entry_value = implode(',<br>', $entry_value);
                        }
                    }

                    // Shared with the notification email, so both read alike.
                    $entry_value = HashFormHelper::format_date_value($entry_value, $entry_type);

                    // A hex code says very little on its own, so the colour is
                    // shown alongside it.
                    if ('color_picker' === $entry_type && $entry_value && is_string($entry_value)) {
                        $entry_value = '<span class="hf-entry-swatch" style="background-color:' . esc_attr($entry_value) . ';"></span>'
                                . '<span class="hf-entry-swatch-value">' . esc_html($entry_value) . '</span>';
                    }

                    // Stored as a bare account id, which says nothing on its own.
                    if ($entry_type == 'user_id') {
                        $entry_value = HashFormFieldUserID::format_value(
                            $entry_value,
                            HashFormFieldUserID::capture_from_options(isset($value['options']) ? $value['options'] : array()),
                            true
                        );
                    }

                    if ($entry_type == 'upload' && $entry_value) {
                        $files_arr = explode(',', $entry_value);
                        $upload_value = '';
                        foreach ($files_arr as $file) {
                            $file_info = pathinfo($file);
                            $file_name = $file_info['basename'];
                            $file_extension = isset($file_info['extension']) ? $file_info['extension'] : '';

                            $upload_value .= '<div class="hf-form-entry-preview">';
                            $upload_value .= '<div class="hf-form-entry-preview-image"><a href="' . esc_url($file) . '" target="_blank">';
                            if (in_array($file_extension, array('jpg', 'jpeg', 'png', 'gif', 'bmp'))) {
                                $upload_value .= '<img src="' . esc_url($file) . '">';
                            } else {
                                $upload_value .= '<img class="hf-attachment-icon" src="' . esc_url($file_img_placeholder) . '">';
                            }
                            $upload_value .= '</a></div>';
                            $upload_value .= '<label><a href="' . esc_url($file) . '" target="_blank">';
                            $upload_value .= esc_html($file_name) . '</a></label>';
                            $upload_value .= '</div>';
                        }
                        $entry_value = $upload_value;
                    }
                    /**
                     * Last word on how a stored value is rendered.
                     *
                     * Lets a field type supplied by an add-on present its own
                     * value without this file having to know the type exists.
                     *
                     * @param string $entry_value Value as rendered so far.
                     * @param string $entry_type  Field type.
                     * @param array  $value       Raw meta row: name, value, type, options.
                     * @param string $context     'detail' or 'email'.
                     */
                    $entry_value = apply_filters('hashform_entry_display_value', $entry_value, $entry_type, $value, 'detail');

                    /*
                     * A field that was left blank used to leave the cell
                     * completely empty, which reads as a rendering fault rather
                     * than as an answer nobody gave. The dash is marked up so it
                     * can be told apart from a value that happens to be one.
                     */
                    $is_blank = !is_string($entry_value) ? empty($entry_value) : ('' === trim($entry_value));

                    echo '<tr>';
                    echo '<th scope="row">' . esc_html($title) . '</th>';

                    if ($is_blank) {
                        echo '<td><span class="hf-entry-blank" aria-label="' . esc_attr__('No answer', 'hash-form') . '">&mdash;</span></td>';
                    } else {
                        echo '<td>' . wp_kses_post(wpautop($entry_value)) . '</td>';
                    }

                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>

        <?php
        /*
         * Fires after the entry's own table has been closed, so anything hooked
         * here is a sibling of it. Pro answers with complete tables of its own,
         * and a table is not legal inside a tbody: printing this from within the
         * loop left the browser to pull them back out, which it did at a
         * position of its own choosing.
         */
        /**
         * After the entry's answers have been printed.
         *
         * @param object $entry
         */
        do_action('hashform_after_entry_detail_view', $entry);

        // The unprefixed name this hook shipped under. Still fired so an
        // add-on written against it keeps working.
        do_action('hf_after_entry_detail_view', $entry); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- kept for backward compatibility; the prefixed hook above is the one to use.
        ?>

        <div class="hf-entry-tools">
            <div class="hf-entry-notes" data-entry="<?php echo absint($entry->id); ?>">
                <h4><?php esc_html_e('Private Note', 'hash-form'); ?></h4>
                <p class="hf-entry-note-desc"><?php esc_html_e('Only visible here in the admin. Never sent to the person who submitted the form.', 'hash-form'); ?></p>
                <textarea rows="4" class="hf-entry-note-field" placeholder="<?php esc_attr_e('Add a note about this entry...', 'hash-form'); ?>"><?php echo esc_textarea(isset($entry->notes) ? $entry->notes : ''); ?></textarea>
                <p class="hf-entry-note-actions">
                    <button type="button" class="hf-btn hf-btn-primary hf-entry-note-save"><?php esc_html_e('Save Note', 'hash-form'); ?></button>
                    <span class="hf-entry-note-status" role="status"></span>
                </p>
            </div>

            <div class="hf-entry-resend-wrap">
                <h4><?php esc_html_e('Notification', 'hash-form'); ?></h4>
                <p>
                    <?php
                    if ($delivery_failed) {
                        echo '<span class="hf-entry-delivery-failed">' . esc_html__('The notification for this entry failed to send.', 'hash-form') . '</span>';
                    } else {
                        esc_html_e('Send the notification emails for this entry again.', 'hash-form');
                    }
                    ?>
                </p>
                <p class="hf-entry-resend-actions">
                    <button type="button" class="hf-btn hf-entry-resend" data-entry="<?php echo absint($entry->id); ?>">
                        <?php esc_html_e('Resend Notification', 'hash-form'); ?>
                    </button>
                    <span class="hf-entry-resend-status" role="status"></span>
                </p>
            </div>
        </div>
    </div>
</div>
