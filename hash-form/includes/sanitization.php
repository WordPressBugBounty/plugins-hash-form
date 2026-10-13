<?php

defined('ABSPATH') || die();

function hashform_sanitize_checkbox($input) {
    if ($input == 'on') {
        return 'on';
    } else {
        return 'off';
    }
}

function hashform_sanitize_number($input) {
    if (is_numeric($input)) {
        return intval($input);
    } else {
        return '';
    }
}

/**
 * Restrict a heading level to h1-h6, falling back to h3.
 *
 * The value becomes the tag name, where escaping gives no protection.
 */
function hashform_sanitize_heading_type($input) {
    $tag = strtolower(trim((string) $input));

    return in_array($tag, hashform_heading_levels(), true) ? $tag : 'h3';
}

function hashform_heading_levels() {
    return array('h1', 'h2', 'h3', 'h4', 'h5', 'h6');
}

function hashform_sanitize_float($input) {
    if (is_numeric($input)) {
        return (float) $input;
    } else {
        return '';
    }
}

function hashform_sanitize_color($color) {
    $mode = (false === strpos($color, 'rgba')) ? 'hex' : 'rgba';
    if ('rgba' === $mode) {
        $color = str_replace(' ', '', $color);
        sscanf($color, 'rgba(%d,%d,%d,%f)', $red, $green, $blue, $alpha);
        return 'rgba(' . $red . ',' . $green . ',' . $blue . ',' . $alpha . ')';
    } else {
        return sanitize_hex_color($color);
    }
}

function hashform_sanitize_url($url) {
    $sanitized_url = wp_strip_all_tags(stripslashes(filter_var($url, FILTER_VALIDATE_URL)));
    return $sanitized_url;
}

function hashform_sanitize_checkbox_boolean($input) {
    if (true == $input) {
        return true;
    } else {
        return false;
    }
}

/**
 * File extensions an upload field may be configured with. Single source for the upload handler too.
 *
 * Outer bound only; get_allowed_mime_types() is still applied when the file arrives.
 */
function hashform_allowed_file_extensions() {
    return apply_filters('hashform_allowed_file_extensions', array(
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ppt', 'pptx', 'pps', 'ppsx',
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'avif', 'heic', 'heif',
        'mp3', 'm4a', 'mp4', 'ogg', 'wav', 'm4v', 'mov', 'wmv', 'avi', 'mpg',
        'ogv', 'webm', '3gp',
        'txt', 'zip', 'rar', '7z', 'csv',
    ));
}

function hashform_sanitize_allowed_file_extensions($extensions) {
    $new_extensions = array();
    $extensions = explode(',', $extensions);
    $allowed_extensions = hashform_allowed_file_extensions();
    foreach ($extensions as $row) {
        $extension = trim($row);
        if (in_array($extension, $allowed_extensions)) {
            $new_extensions[] = $extension;
        }
    }
    return implode(',', $new_extensions);
}
