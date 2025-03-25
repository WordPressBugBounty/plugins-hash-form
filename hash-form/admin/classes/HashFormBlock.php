<?php

defined('ABSPATH') || die();

class HashFormBlock {
    public static $stylesheet;
    public static $gfonts = array();

    public function __construct() {
        add_action('init', array($this, 'register_block'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));

        // Load translation files
        add_action('plugins_loaded', array($this, 'load_textdomain'), 99);
        add_action('enqueue_block_editor_assets', array($this, 'block_localization'));
    }

    public function register_block() {
        $asset_file = include(HASHFORM_PATH . 'build/index.asset.php');
        $all_forms = HashFormHelper::get_all_forms_list_options();
        unset($all_forms['']);

        wp_register_style('hfb-style', HASHFORM_URL . 'css/form-block.css', array(), HASHFORM_VERSION);
        wp_register_style('hfb-editor', HASHFORM_URL . 'css/editor.css', array(), HASHFORM_VERSION);
        wp_register_script('hfb-blocks', HASHFORM_URL . 'build/index.js', $asset_file['dependencies'], $asset_file['version']);
        wp_localize_script('hfb-blocks', 'hash_form_block_data', array(
            'forms' => $all_forms,
            'create_form_link' => esc_url(add_query_arg('page', 'hashform', admin_url('admin.php')))
        ));

        register_block_type(
            'hash-form/form-selector', array(
                'api_version' => 2,
                'editor_script' => 'hfb-blocks',
                'editor_style' => 'hfb-editor',
                'style' => 'hfb-style',
                'attributes' => [
                    "id" => ["type" => "string"],
                    "hfStyle" => ["type" => "string"],
                    "formId" => ["type" => "string"],

                    "enableCustomStyle" => ["type" => "boolean"],

                    "formColumnGap" => ["type" => "string"],
                    "formColumnGapUnit" => ["type" => "string", "enum" => ['px', 'em', 'rem'], "default" => 'px'],

                    "formRowGap" => ["type" => "string"],
                    "formRowGapUnit" => ["type" => "string", "enum" => ['px', 'em', 'rem'], "default" => 'px'],

                    "labelTypoFamily" => ["type" => "string"],
                    "labelTypoWeight" => ["type" => "string"],
                    "labelTypoTextTransform" => ["type" => "string"],
                    "labelTypoTextDecoration" => ["type" => "string"],
                    "labelTypoFontSizeSm" => ["type" => "string"],
                    "labelTypoFontSizeMd" => ["type" => "string"],
                    "labelTypoFontSize" => ["type" => "string"],
                    "labelTypoFontSizeUnit" => ["type" => "string", "enum" => ['px', 'em', 'rem', '%'], "default" => 'px'],
                    "labelTypoLetterSpacingSm" => ["type" => "string"],
                    "labelTypoLetterSpacingMd" => ["type" => "string"],
                    "labelTypoLetterSpacing" => ["type" => "string"],
                    "labelTypoLetterSpacingUnit" => ["type" => "string", "enum" => ['px', 'em', 'rem'], "default" => "px"],
                    "labelTypoLineHeightSm" => ["type" => 'string'],
                    "labelTypoLineHeightMd" => ["type" => "string"],
                    "labelTypoLineHeight" => ["type" => "string"],
                    "labelTypoLineHeightUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],
                    "labelTypoFontColor" => ["type" => 'string'],
                    "labelRequiredColor" => ["type" => 'string'],

                    "labelSpacingTop" => ["type" => 'string'],
                    "labelSpacingLeft" => ["type" => 'string'],
                    "labelSpacingRight" => ["type" => 'string'],
                    "labelSpacingBottom" => ["type" => 'string'],
                    "labelSpacingUnit" => ["type" => 'string', "enum" => ['px', 'em', '%'], "default" => 'px'],

                    "descTypoFamily" => ["type" => 'string'],
                    "descTypoWeight" => ["type" => 'string'],
                    "descTypoTextTransform" => ["type" => 'string'],
                    "descTypoTextDecoration" => ["type" => 'string'],
                    "descTypoFontSizeSm" => ["type" => 'string'],
                    "descTypoFontSizeMd" => ["type" => 'string'],
                    "descTypoFontSize" => ["type" => 'string'],
                    "descTypoFontSizeUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem', '%'], "default" => 'px'],
                    "descTypoLetterSpacingSm" => ["type" => 'string'],
                    "descTypoLetterSpacingMd" => ["type" => 'string'],
                    "descTypoLetterSpacing" => ["type" => 'string'],
                    "descTypoLetterSpacingUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],
                    "descTypoLineHeightSm" => ["type" => 'string'],
                    "descTypoLineHeightMd" => ["type" => 'string'],
                    "descTypoLineHeight" => ["type" => 'string'],
                    "descTypoLineHeightUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],

                    "descTypoFontColor" => ["type" => 'string'],

                    "descSpacingTop" => ["type" => 'string'],
                    "descSpacingLeft" => ["type" => 'string'],
                    "descSpacingRight" => ["type" => 'string'],
                    "descSpacingBottom" => ["type" => 'string'],
                    "descSpacingUnit" => ["type" => 'string', "enum" => ['px', 'em', '%'], "default" => 'px'],

                    "fieldTypoFamily" => ["type" => 'string'],
                    "fieldTypoWeight" => ["type" => 'string'],
                    "fieldTypoTextTransform" => ["type" => 'string'],
                    "fieldTypoTextDecoration" => ["type" => 'string'],
                    "fieldTypoFontSizeSm" => ["type" => 'string'],
                    "fieldTypoFontSizeMd" => ["type" => 'string'],
                    "fieldTypoFontSize" => ["type" => 'string'],
                    "fieldTypoFontSizeUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem', '%'], "default" => 'px'],
                    "fieldTypoLetterSpacingSm" => ["type" => 'string'],
                    "fieldTypoLetterSpacingMd" => ["type" => 'string'],
                    "fieldTypoLetterSpacing" => ["type" => 'string'],
                    "fieldTypoLetterSpacingUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],
                    "fieldTypoLineHeightSm" => ["type" => 'string'],
                    "fieldTypoLineHeightMd" => ["type" => 'string'],
                    "fieldTypoLineHeight" => ["type" => 'string'],
                    "fieldTypoLineHeightUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],

                    "fieldColorNormal" => ["type" => 'string'],
                    "fieldBgColorNormal" => ["type" => 'string'],

                    "fieldShadowNormalX" => ["type" => 'string'],
                    "fieldShadowNormalY" => ["type" => 'string'],
                    "fieldShadowNormalBlur" => ["type" => 'string'],
                    "fieldShadowNormalSpread" => ["type" => 'string'],
                    "fieldShadowNormalColor" => ["type" => 'string'],
                    "fieldShadowNormalInset" => ["type" => 'string'],

                    "fieldBorder" => ["type" => 'string'],

                    "fieldBorderTop" => ["type" => 'string'],
                    "fieldBorderLeft" => ["type" => 'string'],
                    "fieldBorderRight" => ["type" => 'string'],
                    "fieldBorderBottom" => ["type" => 'string'],
                    "fieldBorderUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem', 'vw'], "default" => 'px'],

                    "fieldBorderColorNormal" => ["type" => 'string'],

                    "fieldColorFocus" => ["type" => 'string'],
                    "fieldBgColorFocus" => ["type" => 'string'],
                    "fieldShadowFocusX" => ["type" => 'string'],
                    "fieldShadowFocusY" => ["type" => 'string'],
                    "fieldShadowFocusBlur" => ["type" => 'string'],
                    "fieldShadowFocusSpread" => ["type" => 'string'],
                    "fieldShadowFocusColor" => ["type" => 'string'],
                    "fieldShadowFocusInset" => ["type" => 'string'],

                    "fieldBorderFocusColor" => ["type" => 'string'],

                    "fieldBorderRadiusTop" => ["type" => 'string'],
                    "fieldBorderRadiusLeft" => ["type" => 'string'],
                    "fieldBorderRadiusRight" => ["type" => 'string'],
                    "fieldBorderRadiusBottom" => ["type" => 'string'],
                    "fieldBorderRadiusUnit" => ["type" => 'string', "enum" => ['px', 'em', '%'], "default" => 'px'],

                    "fieldPaddingTop" => ["type" => 'string'],
                    "fieldPaddingLeft" => ["type" => 'string'],
                    "fieldPaddingRight" => ["type" => 'string'],
                    "fieldPaddingBottom" => ["type" => 'string'],
                    "fieldPaddingUnit" => ["type" => 'string', "enum" => ['px', 'em', '%'], "default" => 'px'],

                    "uploadTypoFamily" => ["type" => 'string'],
                    "uploadTypoWeight" => ["type" => 'string'],
                    "uploadTypoTextTransform" => ["type" => 'string'],
                    "uploadTypoTextDecoration" => ["type" => 'string'],
                    "uploadTypoFontSizeSm" => ["type" => 'string'],
                    "uploadTypoFontSizeMd" => ["type" => 'string'],
                    "uploadTypoFontSize" => ["type" => 'string'],
                    "uploadTypoFontSizeUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem', '%'], "default" => 'px'],
                    "uploadTypoLetterSpacingSm" => ["type" => 'string'],
                    "uploadTypoLetterSpacingMd" => ["type" => 'string'],
                    "uploadTypoLetterSpacing" => ["type" => 'string'],
                    "uploadTypoLetterSpacingUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],
                    "uploadTypoLineHeightSm" => ["type" => 'string'],
                    "uploadTypoLineHeightMd" => ["type" => 'string'],
                    "uploadTypoLineHeight" => ["type" => 'string'],
                    "uploadTypoLineHeightUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],

                    "uploadColorNormal" => ["type" => 'string'],
                    "uploadBgColorNormal" => ["type" => 'string'],

                    "uploadShadowNormalX" => ["type" => 'string'],
                    "uploadShadowNormalY" => ["type" => 'string'],
                    "uploadShadowNormalBlur" => ["type" => 'string'],
                    "uploadShadowNormalSpread" => ["type" => 'string'],
                    "uploadShadowNormalColor" => ["type" => 'string'],
                    "uploadShadowNormalInset" => ["type" => 'string'],

                    "uploadBorder" => ["type" => 'string'],
                    "uploadBorderTop" => ["type" => 'string'],
                    "uploadBorderLeft" => ["type" => 'string'],
                    "uploadBorderRight" => ["type" => 'string'],
                    "uploadBorderBottom" => ["type" => 'string'],
                    "uploadBorderUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem', 'vw'], "default" => 'px'],

                    "uploadBorderColorNormal" => ["type" => 'string'],

                    "uploadColorHover" => ["type" => 'string'],
                    "uploadBgColorHover" => ["type" => 'string'],

                    "uploadShadowHoverX" => ["type" => 'string'],
                    "uploadShadowHoverY" => ["type" => 'string'],
                    "uploadShadowHoverBlur" => ["type" => 'string'],
                    "uploadShadowHoverSpread" => ["type" => 'string'],
                    "uploadShadowHoverColor" => ["type" => 'string'],
                    "uploadShadowHoverInset" => ["type" => 'string'],

                    "uploadBorderColorHover" => ["type" => 'string'],

                    "uploadBorderRadiusTop" => ["type" => 'string'],
                    "uploadBorderRadiusLeft" => ["type" => 'string'],
                    "uploadBorderRadiusRight" => ["type" => 'string'],
                    "uploadBorderRadiusBottom" => ["type" => 'string'],
                    "uploadBorderRadiusUnit" => ["type" => 'string', "enum" => ['px', 'em', '%'], "default" => 'px'],

                    "uploadPaddingTop" => ["type" => 'string'],
                    "uploadPaddingLeft" => ["type" => 'string'],
                    "uploadPaddingRight" => ["type" => 'string'],
                    "uploadPaddingBottom" => ["type" => 'string'],
                    "uploadPaddingUnit" => ["type" => 'string', "enum" => ['px', 'em', '%'], "default" => 'px'],

                    "buttonTypoFamily" => ["type" => 'string'],
                    "buttonTypoWeight" => ["type" => 'string'],
                    "buttonTypoTextTransform" => ["type" => 'string'],
                    "buttonTypoTextDecoration" => ["type" => 'string'],
                    "buttonTypoFontSizeSm" => ["type" => 'string'],
                    "buttonTypoFontSizeMd" => ["type" => 'string'],
                    "buttonTypoFontSize" => ["type" => 'string'],
                    "buttonTypoFontSizeUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem', '%'], "default" => 'px'],
                    "buttonTypoLetterSpacingSm" => ["type" => 'string'],
                    "buttonTypoLetterSpacingMd" => ["type" => 'string'],
                    "buttonTypoLetterSpacing" => ["type" => 'string'],
                    "buttonTypoLetterSpacingUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],
                    "buttonTypoLineHeightSm" => ["type" => 'string'],
                    "buttonTypoLineHeightMd" => ["type" => 'string'],
                    "buttonTypoLineHeight" => ["type" => 'string'],
                    "buttonTypoLineHeightUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],

                    "buttonColorNormal" => ["type" => 'string'],
                    "buttonBgColorNormal" => ["type" => 'string'],

                    "buttonShadowNormalX" => ["type" => 'string'],
                    "buttonShadowNormalY" => ["type" => 'string'],
                    "buttonShadowNormalBlur" => ["type" => 'string'],
                    "buttonShadowNormalSpread" => ["type" => 'string'],
                    "buttonShadowNormalColor" => ["type" => 'string'],
                    "buttonShadowNormalInset" => ["type" => 'string'],

                    "buttonBorder" => ["type" => 'string'],
                    "buttonBorderTop" => ["type" => 'string'],
                    "buttonBorderLeft" => ["type" => 'string'],
                    "buttonBorderRight" => ["type" => 'string'],
                    "buttonBorderBottom" => ["type" => 'string'],
                    "buttonBorderUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem', 'vw'], "default" => 'px'],
                    "buttonBorderColorNormal" => ["type" => 'string'],

                    "buttonColorHover" => ["type" => 'string'],
                    "buttonBgColorHover" => ["type" => 'string'],

                    "buttonShadowHoverX" => ["type" => 'string'],
                    "buttonShadowHoverY" => ["type" => 'string'],
                    "buttonShadowHoverBlur" => ["type" => 'string'],
                    "buttonShadowHoverSpread" => ["type" => 'string'],
                    "buttonShadowHoverColor" => ["type" => 'string'],
                    "buttonShadowHoverInset" => ["type" => 'string'],

                    "buttonBorderColorHover" => ["type" => 'string'],

                    "buttonBorderRadiusTop" => ["type" => 'string'],
                    "buttonBorderRadiusLeft" => ["type" => 'string'],
                    "buttonBorderRadiusRight" => ["type" => 'string'],
                    "buttonBorderRadiusBottom" => ["type" => 'string'],
                    "buttonBorderRadiusUnit" => ["type" => 'string', "enum" => ['px', 'em', '%'], "default" => 'px'],

                    "buttonPaddingTop" => ["type" => 'string'],
                    "buttonPaddingLeft" => ["type" => 'string'],
                    "buttonPaddingRight" => ["type" => 'string'],
                    "buttonPaddingBottom" => ["type" => 'string'],
                    "buttonPaddingUnit" => ["type" => 'string', "enum" => ['px', 'em', '%'], "default" => 'px'],

                    "validationTypoFamily" => ["type" => 'string'],
                    "validationTypoWeight" => ["type" => 'string'],
                    "validationTypoTextTransform" => ["type" => 'string'],
                    "validationTypoTextDecoration" => ["type" => 'string'],
                    "validationTypoFontSizeSm" => ["type" => 'string'],
                    "validationTypoFontSizeMd" => ["type" => 'string'],
                    "validationTypoFontSize" => ["type" => 'string'],
                    "validationTypoFontSizeUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem', '%'], "default" => 'px'],
                    "validationTypoLetterSpacingSm" => ["type" => 'string'],
                    "validationTypoLetterSpacingMd" => ["type" => 'string'],
                    "validationTypoLetterSpacing" => ["type" => 'string'],
                    "validationTypoLetterSpacingUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],
                    "validationTypoLineHeightSm" => ["type" => 'string'],
                    "validationTypoLineHeightMd" => ["type" => 'string'],
                    "validationTypoLineHeight" => ["type" => 'string'],
                    "validationTypoLineHeightUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],

                    "validationTypoFontColor" => ["type" => 'string'],
                    "validationTextalign" => ["type" => 'string'],

                    "formTitleTypoFamily" => ["type" => 'string'],
                    "formTitleTypoWeight" => ["type" => 'string'],
                    "formTitleTypoTextTransform" => ["type" => 'string'],
                    "formTitleTypoTextDecoration" => ["type" => 'string'],
                    "formTitleTypoFontSizeSm" => ["type" => 'string'],
                    "formTitleTypoFontSizeMd" => ["type" => 'string'],
                    "formTitleTypoFontSize" => ["type" => 'string'],
                    "formTitleTypoFontSizeUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem', '%'], "default" => 'px'],
                    "formTitleTypoLetterSpacingSm" => ["type" => 'string'],
                    "formTitleTypoLetterSpacingMd" => ["type" => 'string'],
                    "formTitleTypoLetterSpacing" => ["type" => 'string'],
                    "formTitleTypoLetterSpacingUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],
                    "formTitleTypoLineHeightSm" => ["type" => 'string'],
                    "formTitleTypoLineHeightMd" => ["type" => 'string'],
                    "formTitleTypoLineHeight" => ["type" => 'string'],
                    "formTitleTypoLineHeightUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],

                    "formTitleTypoFontColor" => ["type" => 'string'],

                    "formTitleSpacingTop" => ["type" => 'string'],
                    "formTitleSpacingLeft" => ["type" => 'string'],
                    "formTitleSpacingRight" => ["type" => 'string'],
                    "formTitleSpacingBottom" => ["type" => 'string'],
                    "formTitleSpacingUnit" => ["type" => 'string', "enum" => ['px', 'em', '%'], "default" => 'px'],

                    "formDescTypoFamily" => ["type" => 'string'],
                    "formDescTypoWeight" => ["type" => 'string'],
                    "formDescTypoTextTransform" => ["type" => 'string'],
                    "formDescTypoTextDecoration" => ["type" => 'string'],
                    "formDescTypoFontSizeSm" => ["type" => 'string'],
                    "formDescTypoFontSizeMd" => ["type" => 'string'],
                    "formDescTypoFontSize" => ["type" => 'string'],
                    "formDescTypoFontSizeUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem', '%'], "default" => 'px'],
                    "formDescTypoLetterSpacingSm" => ["type" => 'string'],
                    "formDescTypoLetterSpacingMd" => ["type" => 'string'],
                    "formDescTypoLetterSpacing" => ["type" => 'string'],
                    "formDescTypoLetterSpacingUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],
                    "formDescTypoLineHeightSm" => ["type" => 'string'],
                    "formDescTypoLineHeightMd" => ["type" => 'string'],
                    "formDescTypoLineHeight" => ["type" => 'string'],
                    "formDescTypoLineHeightUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],

                    "formDescTypoFontColor" => ["type" => 'string'],
                    "formDescSpacingTop" => ["type" => 'string'],
                    "formDescSpacingLeft" => ["type" => 'string'],
                    "formDescSpacingRight" => ["type" => 'string'],
                    "formDescSpacingBottom" => ["type" => 'string'],
                    "formDescSpacingUnit" => ["type" => 'string', "enum" => ['px', 'em', '%'], "default" => 'px'],

                    "headingTypoFamily" => ["type" => 'string'],
                    "headingTypoWeight" => ["type" => 'string'],
                    "headingTypoTextTransform" => ["type" => 'string'],
                    "headingTypoTextDecoration" => ["type" => 'string'],
                    "headingTypoFontSizeSm" => ["type" => 'string'],
                    "headingTypoFontSizeMd" => ["type" => 'string'],
                    "headingTypoFontSize" => ["type" => 'string'],
                    "headingTypoFontSizeUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem', '%'], "default" => 'px'],
                    "headingTypoLetterSpacingSm" => ["type" => 'string'],
                    "headingTypoLetterSpacingMd" => ["type" => 'string'],
                    "headingTypoLetterSpacing" => ["type" => 'string'],
                    "headingTypoLetterSpacingUnit" => ["type" => 'string'],
                    "headingTypoLineHeightSm" => ["type" => 'string'],
                    "headingTypoLineHeightMd" => ["type" => 'string'],
                    "headingTypoLineHeight" => ["type" => 'string'],
                    "headingTypoLineHeightUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],

                    "headingTypoFontColor" => ["type" => 'string'],

                    "paragraphTypoFamily" => ["type" => 'string'],
                    "paragraphTypoWeight" => ["type" => 'string'],
                    "paragraphTypoTextTransform" => ["type" => 'string'],
                    "paragraphTypoTextDecoration" => ["type" => 'string'],
                    "paragraphTypoFontSizeSm" => ["type" => 'string'],
                    "paragraphTypoFontSizeMd" => ["type" => 'string'],
                    "paragraphTypoFontSize" => ["type" => 'string'],
                    "paragraphTypoFontSizeUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem', '%'], "default" => 'px'],
                    "paragraphTypoLetterSpacingSm" => ["type" => 'string'],
                    "paragraphTypoLetterSpacingMd" => ["type" => 'string'],
                    "paragraphTypoLetterSpacing" => ["type" => 'string'],
                    "paragraphTypoLetterSpacingUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],
                    "paragraphTypoLineHeightSm" => ["type" => 'string'],
                    "paragraphTypoLineHeightMd" => ["type" => 'string'],
                    "paragraphTypoLineHeight" => ["type" => 'string'],
                    "paragraphTypoLineHeightUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],

                    "paragraphTypoFontColor" => ["type" => 'string'],
                    "dividerColor" => ["type" => 'string'],
                    "starSize" => ["type" => 'string'],
                    "starSizeUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],

                    "starColor" => ["type" => 'string'],
                    "starColorActive" => ["type" => 'string'],

                    "rangeHeight" => ["type" => 'string'],
                    "rangeHeightUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],

                    "rangeHandleSize" => ["type" => 'string'],
                    "rangeHandleSizeUnit" => ["type" => 'string', "enum" => ['px', 'em', 'rem'], "default" => 'px'],

                    "rangeColor" => ["type" => 'string'],
                    "rangeColorActive" => ["type" => 'string'],
                    "rangeHandleColor" => ["type" => 'string'],
                ],
                'script' => 'hfb-script',
                'render_callback' => array($this, 'get_form_html')
            )
        );
    }

    public function enqueue_block_editor_assets() {
    }

    public function get_form_html($attr) {
        $form_id = !empty($attr['formId']) ? absint($attr['formId']) : 0;
        if (empty($form_id)) {
            return '';
        }
        ob_start();

        $enable_custom_style = isset($attr['enableCustomStyle']) ? $attr['enableCustomStyle'] : 'no';

        if ($enable_custom_style == 'yes') {
            add_filter('hashform_form_classes', array($this, 'modify_class'));
            add_filter('hashform_enable_style', '__return_false');
            self::$stylesheet = $this->get_stylesheet($attr);
            add_action('wp_footer', array($this, 'print_stylesheet'), 11);
        }
        if (!is_admin()) {
            echo '<div ' . get_block_wrapper_attributes(['class' => 'wp-block-hash-form', 'id' => $attr['id']]) . '>';
        }
        echo do_shortcode('[hashform id="' . $form_id . '"]');
        if (!is_admin()) {
            echo '</div>';
        }

        if ($enable_custom_style == 'yes') {
            remove_filter('hashform_form_classes', array($this, 'modify_class'));
            remove_filter('hashform_enable_style', '__return_false');
        }
        return ob_get_clean();
    }

    public function modify_class($classes) {
        $remove_classes = array('hf-form-default-style', 'hf-form-no-style');
        $classes = array_diff($classes, $remove_classes);
        $classes[] = 'hf-hashform-block';
        $classes[] = 'hf-form-custom-style';

        return $classes;
    }

    public function load_textdomain() {
        load_plugin_textdomain('hash-form', false, HASHFORM_PATH . 'languages');
    }

    // Enqueue localization data for our blocks.
    public function block_localization() {
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('hfb-blocks', 'hash-form', HASHFORM_PATH . 'languages');
        }
    }

    public function get_stylesheet($blockAttrs) {
        $block_css_arr = [];
        $block_css = '';
        foreach ($blockAttrs as $attrs => $value) {
            $family = '';
            $weight = '';
            if (str_contains($attrs, 'Family')) {
                $family = $value;
                $weight = $blockAttrs[str_replace('Family', 'Weight', $attrs)];
            }
            if ($family && $family != 'inherit') {
                self::blocks_google_font($family, $weight ? str_replace('italic', 'i', $weight) : 400);
            }
        }
        // Get CSS for the Block.
        if (isset($blockAttrs['hfStyle'])) {
            $block_css_arr[$blockAttrs['id']] = is_array($blockAttrs['hfStyle']) ? '' : $blockAttrs['hfStyle'];
        }

        foreach ($block_css_arr as $val) {
            $block_css .= $val;
        }
        return $block_css;
    }

    public static function blocks_google_font($font_family, $font_weight, $font_subset = NULL) {
        if (strtolower($font_family) != 'inherit') {

            if (!array_key_exists($font_family, self::$gfonts)) {
                $add_font = array(
                    'fontfamily' => $font_family,
                    'fontvariants' => (isset($font_weight) && !empty($font_weight) ? array($font_weight) : array()),
                    'fontsubsets' => (isset($font_subset) && !empty($font_subset) ? array($font_subset) : array()),
                );
                self::$gfonts[$font_family] = $add_font;

            } else {
                if (isset($font_weight) && ($font_weight != 'inherit') && !empty($font_weight)) {
                    if (!in_array($font_weight, self::$gfonts[$font_family]['fontvariants'], true)) {
                        array_push(self::$gfonts[$font_family]['fontvariants'], $font_weight);
                    }
                }
                if (isset($font_subset) && !empty($font_subset)) {
                    if (!in_array($font_subset, self::$gfonts[$font_family]['fontsubsets'], true)) {
                        array_push(self::$gfonts[$font_family]['fontsubsets'], $font_subset);
                    }
                }
            }
        }
    }

    public function print_stylesheet() {

        if (is_null(self::$stylesheet) || '' === self::$stylesheet) {
            return;
        }

        wp_register_style('sb-style-frontend', false, array(), HASHFORM_VERSION);
        wp_enqueue_style('sb-style-frontend');
        wp_add_inline_style('sb-style-frontend', $this->strip_whitespace(self::$stylesheet));
        $frontend_gfonts = $this->frontend_gfonts();
        wp_enqueue_style('sb-fonts-frontend', $frontend_gfonts, array(), NULL);
    }

    public function strip_whitespace($css) {
        $replace = array(
            "#/\*.*?\*/#s" => "", // Strip C style comments.
            "#\s\s+#" => " ", // Strip excess whitespace.
        );
        $search = array_keys($replace);
        $css = preg_replace($search, $replace, $css);

        $replace = array(
            ": " => ":",
            "; " => ";",
            " {" => "{",
            " }" => "}",
            ", " => ",",
            "{ " => "{",
            ";}" => "}", // Strip optional semicolons.
            ",\n" => ",", // Don't wrap multiple selectors.
            "\n}" => "}", // Don't wrap closing braces.
            "} " => "}\n", // Put each rule on it's own line.
        );
        $search = array_keys($replace);
        $css = str_replace($search, $replace, $css);

        return trim($css);
    }

    public function frontend_gfonts() {
        if (empty(self::$gfonts)) {
            return;
        }
        $link = '';
        $subsets = array();

        foreach (self::$gfonts as $key => $gfont_values) {
            if (!empty($link)) {
                $link .= '%7C'; // Append a new font to the string.
            }
            $link .= $gfont_values['fontfamily'];
            if (!empty($gfont_values['fontvariants'])) {
                $link .= ':';
                $link .= implode(',', $gfont_values['fontvariants']);
            }

            if (!empty($gfont_values['fontsubsets'])) {
                foreach ($gfont_values['fontsubsets'] as $subset) {
                    if (!in_array($subset, $subsets, true)) {
                        array_push($subsets, $subset);
                    }
                }
            }
        }

        if (!empty($subsets)) {
            $link .= '&amp;subset=' . implode(',', $subsets);
        }

        return '//fonts.googleapis.com/css?family=' . esc_attr(str_replace('|', '%7C', $link));
    }

}

new HashFormBlock();
