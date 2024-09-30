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
                    <?php printf(esc_html__('(ID %d)', 'hash-form'), absint($entry->id)); ?>
                </span>
            </h3>
            <div class="hf-form-entry-navigation">
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
                $file_img_placeholder = HASHFORM_URL . '/img/attachment.png';
                foreach ($entry->metas as $id => $value) {
                    $title = $value['name'];
                    $entry_value = HashFormHelper::unserialize_or_decode($value['value']);
                    $entry_type = $value['type'];

                    if (is_array($entry_value)) {
                        $entry_value = array_filter($entry_value);
                        if ($entry_type == 'name') {
                            $entry_value = implode(' ', $entry_value);
                        } elseif ($entry_type == 'repeater_field') {
                            $entry_val = '<table><thead><tr>';
                            foreach (array_keys($entry_value) as $key) {
                                $entry_val .= '<th>' . $key . '</th>';
                            }
                            $entry_val .= '</tr></thead><tbody>';
                            $out = array();
                            foreach ($entry_value as $rowkey => $row) {
                                foreach ($row as $colkey => $col) {
                                    $out[$colkey][$rowkey] = $col;
                                }
                            }
                            foreach ($out as $key => $val) {
                                foreach ($val as $eval) {
                                    $entry_val .= '<td>' . $eval . '</td>';
                                }
                                $entry_val .= '</tr>';
                            }
                            $entry_val .= '</tbody></table>';
                            $entry_value = $entry_val;
                        } else {
                            $entry_value = implode(',<br>', $entry_value);
                        }
                    }

                    if ($entry_type == 'upload' && $entry_value) {
                        $files_arr = explode(',', $entry_value);
                        $upload_value = '';
                        foreach ($files_arr as $file) {
                            $file_info = pathinfo($file);
                            $file_name = $file_info['basename'];
                            $file_extension = $file_info['extension'];

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
                    echo '<tr>';
                    echo '<th>' . esc_html($title) . '</th>';
                    echo '<td>' . wp_kses_post($entry_value) . '</td>';
                    echo '</tr>';
                }

                do_action('hf_after_entry_detail_view', $entry);
                ?>
            </tbody>
        </table>
    </div>
</div>