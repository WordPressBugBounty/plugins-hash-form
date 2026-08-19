<?php
defined('ABSPATH') || die();

$id = htmlspecialchars_decode(HashFormHelper::get_var('id', 'absint'));
$form = HashFormBuilder::get_form_vars($id);

if (!$form) {
    echo '<h3>' . esc_html__('You are trying to edit a form that does not exist.', 'hash-form') . '</h3>';
    return;
}

$styles = $form->styles ? $form->styles : array();
$form_style = isset($styles['form_style']) ? $styles['form_style'] : 'default-style';
$form_style_template = isset($styles['form_style_template']) ? $styles['form_style_template'] : '';
?>
<div id="hf-wrap" class="hf-content hf-form-style-template">
    <?php
    self::get_admin_header(
        array(
            'form' => $form,
            'class' => 'hf-header-nav',
        )
    );
    ?>
    <div class="hf-body">
        <div class="hf-fields-sidebar">
            <form class="ht-fields-panel" method="post" id="hf-style-form">
                <input type="hidden" name="id" id="hf-form-id" value="<?php echo absint($id); ?>" />
                <div class="hf-form-container">
                    <?php
                    /*
                     * The three modes are a visual choice, so they are shown as
                     * cards rather than hidden in a dropdown. The select below
                     * stays as the value that gets serialised on save and as
                     * the element the data-condition rules watch — the radios
                     * carry no name of their own, so nothing is posted twice.
                     */
                    $style_modes = array(
                        'no-style' => array(
                            'label' => esc_html__('No Style', 'hash-form'),
                            'desc' => esc_html__('Let your theme style the form. The preview here will not match the front end.', 'hash-form'),
                        ),
                        'default-style' => array(
                            'label' => esc_html__('Default Style', 'hash-form'),
                            'desc' => esc_html__('The plugin\'s own minimal styling.', 'hash-form'),
                        ),
                        'custom-style' => array(
                            'label' => esc_html__('Custom Style', 'hash-form'),
                            'desc' => esc_html__('Apply a style template you have built.', 'hash-form'),
                        ),
                    );
                    ?>
                    <div class="hf-form-row hf-layout-section">
                        <fieldset class="hf-style-modes">
                            <?php // Same heading treatment as the builder's Columns and Fields sections. ?>
                            <legend class="hf-layout-heading hf-style-modes-legend"><?php esc_html_e('Form Style', 'hash-form'); ?></legend>
                            <p class="hf-layout-help"><?php esc_html_e('How much of the look comes from the plugin rather than your theme.', 'hash-form'); ?></p>

                            <?php foreach ($style_modes as $mode_value => $mode) { ?>
                                <label class="hf-style-mode<?php echo ($form_style === $mode_value) ? ' hf-selected' : ''; ?>">
                                    <?php
                                    /*
                                     * Named so the three behave as one radio
                                     * group, but pointed at a form id that does
                                     * not exist so they are not part of
                                     * #hf-style-form. update_style() serialises
                                     * whatever the form posts without a
                                     * whitelist, and this control's value is
                                     * already carried by the select below.
                                     */
                                    ?>
                                    <input type="radio" name="hf_style_mode" form="hf-style-mode-detached" class="hf-style-mode-input" value="<?php echo esc_attr($mode_value); ?>" <?php checked($form_style, $mode_value); ?> />
                                    <span class="hf-style-mode-text">
                                        <span class="hf-style-mode-name"><?php echo esc_html($mode['label']); ?></span>
                                        <span class="hf-style-mode-desc"><?php echo esc_html($mode['desc']); ?></span>
                                    </span>
                                </label>
                            <?php } ?>
                        </fieldset>

                        <select name="form_style" id="hf-form-style-select" data-condition="toggle" class="hf-style-mode-value" tabindex="-1" aria-hidden="true">
                            <?php foreach ($style_modes as $mode_value => $mode) { ?>
                                <option value="<?php echo esc_attr($mode_value); ?>" <?php selected($form_style, $mode_value); ?>><?php echo esc_html($mode['label']); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="hf-form-row hf-layout-section" data-condition-toggle="hf-form-style-select" data-condition-val="custom-style">
                        <h4 class="hf-layout-heading"><?php esc_html_e('Style Template', 'hash-form'); ?></h4>
                        <label class="hf-style-template-label"><?php esc_html_e('Choose Template Style', 'hash-form'); ?></label>
                        <select name="form_style_template" id="hf-form-style-template">
                            <option value=""><?php esc_html_e('--Select Style--', 'hash-form'); ?></option>
                            <?php
                            $args = array(
                                'post_type' => 'hashform-styles',
                                'posts_per_page' => -1,
                                'post_status' => 'publish'
                            );
                            $query = new WP_Query($args);
                            $posts = $query->posts;
                            foreach ($posts as $post) {
                                $hashform_styles = get_post_meta($post->ID, 'hashform_styles', true);

                                if (!$hashform_styles) {
                                    $hashform_styles = HashFormStyles::default_styles();
                                } else {
                                    $hashform_styles = HashFormHelper::recursive_parse_args($hashform_styles, HashFormStyles::default_styles());
                                }
                                ob_start();
                                echo '#hf-container-' . absint($id) . '{';
                                HashFormStyles::get_style_vars($hashform_styles, '');
                                echo '}';
                                $tmpl_css_style = ob_get_clean();
                                ?>
                                <option value="<?php echo esc_attr($post->ID); ?>" data-style="<?php echo esc_attr($tmpl_css_style); ?>" <?php selected($post->ID, $form_style_template); ?>><?php echo esc_html($post->post_title); ?></option>
                                <?php
                            }
                            wp_reset_postdata();
                            ?>
                        </select>
                    </div>

                    <?php // Was inline-styled with a hardcoded blue; on the tokens it follows the admin colour scheme. ?>
                    <div class="hf-form-row hf-style-template-cta" data-condition-toggle="hf-form-style-select" data-condition-val="custom-style">
                        <p class="hf-style-template-cta-text"><?php esc_html_e('Build fast with a style template — style your forms in seconds.', 'hash-form'); ?></p>
                        <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=hashform-styles')); ?>" target="_blank"><?php esc_html_e('Create/Edit Style Template', 'hash-form'); ?></a>
                    </div>
                </div>
            </form>
        </div>

        <div id="hf-form-panel" class="hf-style-form-panel">
            <div class="hf-form-wrap">
                <?php
                /*
                 * The same slim header the builder canvas carries, so the two
                 * screens read as one surface. Where the builder counts fields
                 * this names the style in force; backend.js keeps it in step
                 * when a different card is picked.
                 */
                ?>
                <div class="hf-canvas-header">
                    <span class="hf-canvas-title"><?php echo esc_html($form->name); ?></span>
                    <span class="hf-canvas-count" id="hf-style-mode-label"><?php echo esc_html(isset($style_modes[$form_style]) ? $style_modes[$form_style]['label'] : $style_modes['default-style']['label']); ?></span>
                </div>

                <div class="hf-form-preview" data-form="<?php echo esc_attr($id); ?>"></div>
            </div>
        </div>
    </div>
</div>