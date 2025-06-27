<?php

defined('ABSPATH') || die();

HashFormPreview::show_form($form_id);

echo '<style>';
echo '#hf-container-' . $form_id . '{';
self::get_style_vars($hashform_styles, '');
echo '}';
echo '</style>';
