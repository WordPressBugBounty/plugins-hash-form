<?php
defined('ABSPATH') || die();
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>

    <head>
        <title><?php bloginfo('name'); ?> | <?php echo esc_html($form->name); ?></title>
        <meta charset="<?php bloginfo('charset'); ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <?php // An admin tool rather than a page of the site, whoever reaches it. ?>
        <meta name="robots" content="noindex, nofollow" />
        <?php wp_head(); ?>
    </head>

    <body class="hashform_preview_page">
        <?php
        HashFormPreview::show_form($form->id);
        HashFormPreview::condition_hints($form->id);
        wp_footer();
        ?>
    </body>

</html>