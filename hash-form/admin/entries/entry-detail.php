<?php
defined('ABSPATH') || die();
$prev_entry = HashFormEntry::get_prev_entry($entry->id);
$prev_entry = isset($prev_entry[0]) ? $prev_entry[0] : '';
$prev_entry_id = isset($prev_entry->id) ? $prev_entry->id : '';
$prev_url = $prev_entry_id ? admin_url('admin.php?page=hashform-entries&hashform_action=view&id=' . $prev_entry_id) : '#';

$next_entry = HashFormEntry::get_next_entry($entry->id);
$next_entry = isset($next_entry[0]) ? $next_entry[0] : '';
$next_entry_id = isset($next_entry->id) ? $next_entry->id : '';
$next_url = $next_entry_id ? admin_url('admin.php?page=hashform-entries&hashform_action=view&id=' . $next_entry_id) : '#';
?>

<div class="hf-form-entry-details-wrap wrap">
    <h1></h1>
    <div id="hf-form-entry-details">
        <div class="hf-page-title">
            <h3>
                <span><?php esc_html_e('Entry', 'hash-form'); ?></span>
                <span class="hf-sub-label">
                    <?php echo '(' . esc_html__('ID', 'hash-form') . ' ' . absint($entry->id) . ')'; ?>
                </span>
                 - 
                <span class="hf-form-entry-date"><?php echo HashFormEntry::get_entry_date($entry->id) ?></span>
            </h3>
            <div class="hf-form-entry-navigation">
                <button type="button" class="hf-entry-star hf-entry-star-large<?php echo empty($entry->is_starred) ? '' : ' hf-starred'; ?>"
                        data-entry="<?php echo absint($entry->id); ?>"
                        data-starred="<?php echo empty($entry->is_starred) ? '0' : '1'; ?>"
                        aria-pressed="<?php echo empty($entry->is_starred) ? 'false' : 'true'; ?>"
                        aria-label="<?php esc_attr_e('Star this entry', 'hash-form'); ?>">
                    <span class="dashicons dashicons-star-<?php echo empty($entry->is_starred) ? 'empty' : 'filled'; ?>"></span>
                </button>
                <a class="hf-form-entry-prev<?php echo $prev_url == '#' ? ' hf-disabled' : ''; ?>" href="<?php echo esc_url($prev_url); ?>">
                    <i class="mdi mdi-chevron-left"></i><?php echo esc_html__('Previous', 'hash-form') ?>
                </a>
                <a class="hf-form-entry-next<?php echo $next_url == '#' ? ' hf-disabled' : ''; ?>" href="<?php echo esc_url($next_url); ?>">
                    <?php echo esc_html__('Next', 'hash-form') ?><i class="mdi mdi-chevron-right"></i>
                </a>
            </div>
        </div>
        <table>
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

                    echo '<tr>';
                    echo '<th>' . esc_html($title) . '</th>';
                    echo '<td>' . wpautop(wp_kses_post($entry_value)) . '</td>';
                    echo '</tr>';
                }

                do_action('hf_after_entry_detail_view', $entry);
                ?>
            </tbody>
        </table>

        <div class="hf-entry-tools">
            <div class="hf-entry-notes" data-entry="<?php echo absint($entry->id); ?>">
                <h4><?php esc_html_e('Private Note', 'hash-form'); ?></h4>
                <p class="hf-entry-note-desc"><?php esc_html_e('Only visible here in the admin. Never sent to the person who submitted the form.', 'hash-form'); ?></p>
                <textarea rows="4" class="hf-entry-note-field" placeholder="<?php esc_attr_e('Add a note about this entry...', 'hash-form'); ?>"><?php echo esc_textarea(isset($entry->notes) ? $entry->notes : ''); ?></textarea>
                <p class="hf-entry-note-actions">
                    <button type="button" class="button hf-entry-note-save"><?php esc_html_e('Save Note', 'hash-form'); ?></button>
                    <span class="hf-entry-note-status" role="status"></span>
                </p>
            </div>

            <div class="hf-entry-resend-wrap">
                <h4><?php esc_html_e('Notification', 'hash-form'); ?></h4>
                <p>
                    <?php
                    if (isset($entry->delivery_status) && !$entry->delivery_status) {
                        echo '<span class="hf-entry-delivery-failed">' . esc_html__('The notification for this entry failed to send.', 'hash-form') . '</span>';
                    } else {
                        esc_html_e('Send the notification emails for this entry again.', 'hash-form');
                    }
                    ?>
                </p>
                <p>
                    <button type="button" class="button hf-entry-resend" data-entry="<?php echo absint($entry->id); ?>">
                        <?php esc_html_e('Resend Notification', 'hash-form'); ?>
                    </button>
                    <span class="hf-entry-resend-status" role="status"></span>
                </p>
            </div>
        </div>
    </div>
</div>