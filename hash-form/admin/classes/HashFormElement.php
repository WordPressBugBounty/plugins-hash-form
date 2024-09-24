<?php
defined('ABSPATH') || die();

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Plugin;

class HashFormElement extends Widget_Base {

    public function get_name() {
        return 'Hash Form';
    }

    public function get_title() {
        return esc_html__('Hash Form', 'hash-form');
    }

    public function get_icon() {
        return 'hfi hfi-form';
    }

    public function get_categories() {
        return array('basic');
    }

    public function get_keywords() {
        return array('Form', 'Hash Form', 'Hash');
    }

    protected function register_controls() {

        $this->start_controls_section(
            'section_title', [
                'label' => esc_html__('Form', 'hash-form'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'hf_form_id', [
                'label' => esc_html__('Select Form', 'hash-form'),
                'type' => Controls_Manager::SELECT2,
                'options' => HashFormHelper::get_all_forms_list_options(),
                'multiple' => false,
                'label_block' => true,
                'separator' => 'after'
            ]
        );

        $this->add_control(
            'new_form', [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => sprintf(
                    wp_kses(esc_html__('To Create New Form', 'hash-form') . ' <a href="%s" target="_blank">' . esc_html__('Cick Here', 'hash-form') . '</a>', [
                        'b' => [],
                        'br' => [],
                        'a' => [
                            'href' => [],
                            'target' => [],
                        ],
                    ]), esc_url(add_query_arg('page', 'hashform', admin_url('admin.php')))
                )
            ]
        );

        $this->end_controls_section();


        $this->start_controls_section(
            'enable_style', [
                'label' => esc_html__('Custom Style', 'hash-elements'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'enable_custom_style', [
                'label' => __('Enable Custom Style', 'totalplus'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
            ]
        );

        $this->end_controls_section();


        $this->start_controls_section(
            'form_style', [
                'label' => esc_html__('Form', 'hash-elements'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_control(
            'column_gap', [
                'label' => __('Column Gap', 'hash-elements'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 80,
                        'step' => 1,
                    ]
                ],
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-form-column-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'row_gap', [
                'label' => __('Row Gap', 'hash-elements'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 80,
                        'step' => 1,
                    ]
                ],
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-form-row-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'label_style', [
                'label' => esc_html__('Label', 'hash-elements'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(), [
                'name' => 'label_typography',
                'label' => esc_html__('Typography', 'hash-elements'),
                'selector' => '{{WRAPPER}} .hf-container .hf-field-label',
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_control(
            'label_color', [
                'label' => esc_html__('Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-label-typo-font-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'required_color', [
                'label' => esc_html__('Required Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-label-required-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'label_spacing', [
                'label' => esc_html__('Spacing', 'hash-elements'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-label-spacing-top: {{TOP}}{{UNIT}};--hf-label-spacing: {{BOTTOM}}{{UNIT}};--hf-label-spacing-left: {{LEFT}}{{UNIT}};--hf-label-spacing-right: {{RIGHT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'description_style', [
                'label' => esc_html__('Description', 'hash-elements'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(), [
                'name' => 'description_typography',
                'label' => esc_html__('Typography', 'hash-elements'),
                'selector' => '{{WRAPPER}} .hf-container .hf-field-desc',
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_control(
            'description_color', [
                'label' => esc_html__('Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-desc-typo-font-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'description_spacing', [
                'label' => esc_html__('Spacing', 'hash-elements'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-desc-spacing: {{TOP}}{{UNIT}};--hf-desc-spacing-bottom: {{BOTTOM}}{{UNIT}};--hf-desc-spacing-left: {{LEFT}}{{UNIT}};--hf-desc-spacing-right: {{RIGHT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'fields_style', [
                'label' => esc_html__('Fields', 'hash-elements'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(), [
                'name' => 'fields_typography',
                'label' => esc_html__('Typography', 'hash-elements'),
                'selector' => '{{WRAPPER}} .hf-container input[type="text"], {{WRAPPER}} .hf-container input[type="email"], {{WRAPPER}} .hf-container input[type="url"], {{WRAPPER}} .hf-container input[type="password"], {{WRAPPER}} .hf-container input[type="search"], {{WRAPPER}} .hf-container input[type="number"], {{WRAPPER}} .hf-container input[type="tel"], {{WRAPPER}} .hf-container input[type="range"], {{WRAPPER}} .hf-container input[type="date"], {{WRAPPER}} .hf-container input[type="month"], {{WRAPPER}} .hf-container input[type="week"], {{WRAPPER}} .hf-container input[type="time"], {{WRAPPER}} .hf-container input[type="datetime"], {{WRAPPER}} .hf-container input[type="datetime-local"], {{WRAPPER}} .hf-container input[type="color"], {{WRAPPER}} .hf-container textarea, {{WRAPPER}} .hf-container select',
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->start_controls_tabs(
            'fields_style_tabs'
        );

        $this->start_controls_tab(
            'fields_normal_tab', [
                'label' => esc_html__('Normal', 'textdomain'),
            ]
        );

        $this->add_control(
            'fields_color', [
                'label' => esc_html__('Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-field-color-normal: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'fields_bg_color', [
                'label' => esc_html__('Background Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-field-bg-color-normal: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(), [
                'name' => 'fields_box_shadow',
                'selector' => '{{WRAPPER}} .hf-container input[type="text"], {{WRAPPER}} .hf-container input[type="email"], {{WRAPPER}} .hf-container input[type="url"], {{WRAPPER}} .hf-container input[type="password"], {{WRAPPER}} .hf-container input[type="search"], {{WRAPPER}} .hf-container input[type="number"], {{WRAPPER}} .hf-container input[type="tel"], {{WRAPPER}} .hf-container input[type="range"], {{WRAPPER}} .hf-container input[type="date"], {{WRAPPER}} .hf-container input[type="month"], {{WRAPPER}} .hf-container input[type="week"], {{WRAPPER}} .hf-container input[type="time"], {{WRAPPER}} .hf-container input[type="datetime"], {{WRAPPER}} .hf-container input[type="datetime-local"], {{WRAPPER}} .hf-container input[type="color"], {{WRAPPER}} .hf-container textarea, {{WRAPPER}} .hf-container select'
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(), [
                'name' => 'fields_border',
                'selector' => '{{WRAPPER}} .hf-container input[type="text"], {{WRAPPER}} .hf-container input[type="email"], {{WRAPPER}} .hf-container input[type="url"], {{WRAPPER}} .hf-container input[type="password"], {{WRAPPER}} .hf-container input[type="search"], {{WRAPPER}} .hf-container input[type="number"], {{WRAPPER}} .hf-container input[type="tel"], {{WRAPPER}} .hf-container input[type="range"], {{WRAPPER}} .hf-container input[type="date"], {{WRAPPER}} .hf-container input[type="month"], {{WRAPPER}} .hf-container input[type="week"], {{WRAPPER}} .hf-container input[type="time"], {{WRAPPER}} .hf-container input[type="datetime"], {{WRAPPER}} .hf-container input[type="datetime-local"], {{WRAPPER}} .hf-container input[type="color"], {{WRAPPER}} .hf-container textarea, {{WRAPPER}} .hf-container select'
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'fields_hover_tab', [
                'label' => esc_html__('Focus', 'textdomain'),
            ]
        );

        $this->add_control(
            'fields_color_focus', [
                'label' => esc_html__('Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-field-color-focus: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'fields_bg_color_focus', [
                'label' => esc_html__('Background Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-field-bg-color-focus: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(), [
                'name' => 'fields_box_shadow_focus',
                'selector' => '{{WRAPPER}} .hf-container input[type="text"]:focus, {{WRAPPER}} .hf-container input[type="email"]:focus, {{WRAPPER}} .hf-container input[type="url"]:focus, {{WRAPPER}} .hf-container input[type="password"]:focus, {{WRAPPER}} .hf-container input[type="search"]:focus, {{WRAPPER}} .hf-container input[type="number"]:focus, {{WRAPPER}} .hf-container input[type="tel"]:focus, {{WRAPPER}} .hf-container input[type="range"]:focus, {{WRAPPER}} .hf-container input[type="date"]:focus, {{WRAPPER}} .hf-container input[type="month"]:focus, {{WRAPPER}} .hf-container input[type="week"]:focus, {{WRAPPER}} .hf-container input[type="time"]:focus, {{WRAPPER}} .hf-container input[type="datetime"]:focus, {{WRAPPER}} .hf-container input[type="datetime-local"]:focus, {{WRAPPER}} .hf-container input[type="color"]:focus, {{WRAPPER}} .hf-container textarea:focus, {{WRAPPER}} .hf-container select'
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(), [
                'name' => 'fields_border_focus',
                'selector' => '{{WRAPPER}} .hf-container input[type="text"]:focus, {{WRAPPER}} .hf-container input[type="email"]:focus, {{WRAPPER}} .hf-container input[type="url"]:focus, {{WRAPPER}} .hf-container input[type="password"]:focus, {{WRAPPER}} .hf-container input[type="search"]:focus, {{WRAPPER}} .hf-container input[type="number"]:focus, {{WRAPPER}} .hf-container input[type="tel"]:focus, {{WRAPPER}} .hf-container input[type="range"]:focus, {{WRAPPER}} .hf-container input[type="date"]:focus, {{WRAPPER}} .hf-container input[type="month"]:focus, {{WRAPPER}} .hf-container input[type="week"]:focus, {{WRAPPER}} .hf-container input[type="time"]:focus, {{WRAPPER}} .hf-container input[type="datetime"]:focus, {{WRAPPER}} .hf-container input[type="datetime-local"]:focus, {{WRAPPER}} .hf-container input[type="color"]:focus, {{WRAPPER}} .hf-container textarea:focus, {{WRAPPER}} .hf-container select'
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'fields_border_radius', [
                'label' => esc_html__('Border Radius', 'hash-elements'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-field-border-radius-top: {{TOP}}{{UNIT}};--hf-field-border-radius-bottom: {{BOTTOM}}{{UNIT}};--hf-field-border-radius-left: {{LEFT}}{{UNIT}};--hf-field-border-radius-right: {{RIGHT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'fields_padding', [
                'label' => esc_html__('Padding', 'hash-elements'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-field-padding-top: {{TOP}}{{UNIT}};--hf-field-padding-bottom: {{BOTTOM}}{{UNIT}};--hf-field-padding-left: {{LEFT}}{{UNIT}};--hf-field-padding-right: {{RIGHT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'upload_style', [
                'label' => esc_html__('Upload Button', 'hash-elements'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(), [
                'name' => 'upload_typography',
                'label' => esc_html__('Typography', 'hash-elements'),
                'selector' => '{{WRAPPER}} .hf-file-uploader .qq-upload-button',
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->start_controls_tabs(
            'upload_style_tabs'
        );

        $this->start_controls_tab(
            'upload_normal_tab', [
                'label' => esc_html__('Normal', 'textdomain'),
            ]
        );

        $this->add_control(
            'upload_color', [
                'label' => esc_html__('Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-upload-color-normal: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'upload_bg_color', [
                'label' => esc_html__('Background Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-upload-bg-color-normal: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(), [
                'name' => 'upload_box_shadow',
                'selector' => '{{WRAPPER}} .hf-file-uploader .qq-upload-button',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(), [
                'name' => 'upload_border',
                'selector' => '{{WRAPPER}} .hf-file-uploader .qq-upload-button',
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'upload_hover_tab', [
                'label' => esc_html__('Hover', 'textdomain'),
            ]
        );

        $this->add_control(
            'upload_color_hover', [
                'label' => esc_html__('Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-upload-color-hover: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'upload_bg_color_hover', [
                'label' => esc_html__('Background Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-upload-bg-color-hover: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(), [
                'name' => 'upload_box_shadow_hover',
                'selector' => '{{WRAPPER}} .hf-file-uploader .qq-upload-button-hover',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(), [
                'name' => 'upload_border_hover',
                'selector' => '{{WRAPPER}} .hf-file-uploader .qq-upload-button-hover',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'upload_border_radius', [
                'label' => esc_html__('Border Radius', 'hash-elements'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-upload-border-radius-top: {{TOP}}{{UNIT}};--hf-upload-border-radius-bottom: {{BOTTOM}}{{UNIT}};--hf-upload-border-radius-left: {{LEFT}}{{UNIT}};--hf-upload-border-radius-right: {{RIGHT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'upload_padding', [
                'label' => esc_html__('Padding', 'hash-elements'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-upload-border-top: {{TOP}}{{UNIT}};--hf-upload-border-bottom: {{BOTTOM}}{{UNIT}};--hf-upload-border-left: {{LEFT}}{{UNIT}};--hf-upload-border-right: {{RIGHT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'button_style', [
                'label' => esc_html__('Submit Button', 'hash-elements'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(), [
                'name' => 'button_typography',
                'label' => esc_html__('Typography', 'hash-elements'),
                'selector' => '{{WRAPPER}} .hf-container button, .hf-container input[type="button"], .hf-container input[type="reset"], .hf-container input[type="submit"]',
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->start_controls_tabs(
            'button_style_tabs'
        );

        $this->start_controls_tab(
            'button_normal_tab', [
                'label' => esc_html__('Normal', 'textdomain'),
            ]
        );

        $this->add_control(
            'button_color', [
                'label' => esc_html__('Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-button-color-normal: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'button_bg_color', [
                'label' => esc_html__('Background Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-button-bg-color-normal: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(), [
                'name' => 'button_box_shadow',
                'selector' => '{{WRAPPER}} .hf-container button, .hf-container input[type="button"], .hf-container input[type="reset"], .hf-container input[type="submit"]',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(), [
                'name' => 'button_border',
                'selector' => '{{WRAPPER}} .hf-container button, .hf-container input[type="button"], .hf-container input[type="reset"], .hf-container input[type="submit"]',
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'button_hover_tab', [
                'label' => esc_html__('Hover', 'textdomain'),
            ]
        );

        $this->add_control(
            'button_color_hover', [
                'label' => esc_html__('Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-button-color-hover: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'button_bg_color_hover', [
                'label' => esc_html__('Background Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-button-bg-color-hover: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(), [
                'name' => 'button_box_shadow_hover',
                'selector' => '{{WRAPPER}} .hf-container button:hover, .hf-container input[type="button"]:hover, .hf-container input[type="reset"]:hover, .hf-container input[type="submit"]:hover',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(), [
                'name' => 'button_border_hover',
                'selector' => '{{WRAPPER}} .hf-container button:hover, .hf-container input[type="button"]:hover, .hf-container input[type="reset"]:hover, .hf-container input[type="submit"]:hover',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'button_border_radius', [
                'label' => esc_html__('Border Radius', 'hash-elements'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-button-border-radius-top: {{TOP}}{{UNIT}};--hf-button-border-radius-bottom: {{BOTTOM}}{{UNIT}};--hf-button-border-radius-left: {{LEFT}}{{UNIT}};--hf-button-border-radius-right: {{RIGHT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'button_padding', [
                'label' => esc_html__('Padding', 'hash-elements'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-button-padding-top: {{TOP}}{{UNIT}};--hf-button-padding-bottom: {{BOTTOM}}{{UNIT}};--hf-button-padding-left: {{LEFT}}{{UNIT}};--hf-button-padding-right: {{RIGHT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'Validation_style', [
                'label' => esc_html__('Validation Text', 'hash-elements'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(), [
                'name' => 'validation_typography',
                'label' => esc_html__('Typography', 'hash-elements'),
                'selector' => '{{WRAPPER}} .hf-container .hf-error-msg',
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_control(
            'validation_color', [
                'label' => esc_html__('Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-validation-typo-font-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'validation_text_alignment', [
                'label' => __('Text Alignment', 'totalplus'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'totalplus'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'totalplus'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'totalplus'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .hf-container .hf-error-msg{' => '--hf-validation-textalign: {{VALUE}}',
                ],
                'toggle' => true,
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'form_title_style', [
                'label' => esc_html__('Form Title', 'hash-elements'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(), [
                'name' => 'form_title_typography',
                'label' => esc_html__('Typography', 'hash-elements'),
                'selector' => '{{WRAPPER}} .hf-form-title',
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_control(
            'form_title_color', [
                'label' => esc_html__('Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-form-title-typo-font-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'form_title_spacing', [
                'label' => esc_html__('Spacing', 'hash-elements'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-form-title-spacing-top: {{TOP}}{{UNIT}};--hf-form-title-spacing: {{BOTTOM}}{{UNIT}};--hf-form-title-spacing-left: {{LEFT}}{{UNIT}};--hf-form-title-spacing-right: {{RIGHT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'form_desc_style', [
                'label' => esc_html__('Form Description', 'hash-elements'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(), [
                'name' => 'form_desc_typography',
                'label' => esc_html__('Typography', 'hash-elements'),
                'selector' => '{{WRAPPER}} .hf-form-description p',
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_control(
            'form_desc_color', [
                'label' => esc_html__('Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-form-desc-typo-font-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'form_desc_spacing', [
                'label' => esc_html__('Spacing', 'hash-elements'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-form-desc-spacing-top: {{TOP}}{{UNIT}};--hf-form-desc-spacing: {{BOTTOM}}{{UNIT}};--hf-form-desc-spacing-left: {{LEFT}}{{UNIT}};--hf-form-desc-spacing-right: {{RIGHT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'heading_style', [
                'label' => esc_html__('Heading', 'hash-elements'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(), [
                'name' => 'heading_typography',
                'label' => esc_html__('Typography', 'hash-elements'),
                'selector' => '{{WRAPPER}} .hf-form-field.hashform-field-type-heading h1, {{WRAPPER}} .hf-form-field.hashform-field-type-heading h2, {{WRAPPER}} .hf-form-field.hashform-field-type-heading h3, {{WRAPPER}} .hf-form-field.hashform-field-type-heading h4, {{WRAPPER}} .hf-form-field.hashform-field-type-heading h5, {{WRAPPER}} .hf-form-field.hashform-field-type-heading h6',
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_control(
            'heading_color', [
                'label' => esc_html__('Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-heading-typo-font-color: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'paragraph_style', [
                'label' => esc_html__('Paragraph', 'hash-elements'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(), [
                'name' => 'paragraph_typography',
                'label' => esc_html__('Typography', 'hash-elements'),
                'selector' => '{{WRAPPER}} .hf-form-field.hashform-field-type-paragraph p',
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_control(
            'paragraph_color', [
                'label' => esc_html__('Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-paragraph-typo-font-color: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'divider_style', [
                'label' => esc_html__('Divider', 'hash-elements'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_control(
            'divider_color', [
                'label' => esc_html__('Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-divider-color: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'star_style', [
                'label' => esc_html__('Star', 'hash-elements'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_control(
            'star_size', [
                'label' => __('Size', 'hash-elements'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 80,
                        'step' => 1,
                    ]
                ],
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-star-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'star_color', [
                'label' => esc_html__('Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-star-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'star_color_active', [
                'label' => esc_html__('Color (Active)', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-star-color-active: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'range_slider_style', [
                'label' => esc_html__('Range Slider', 'hash-elements'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
            ]
        );

        $this->add_control(
            'range_slider_height', [
                'label' => __('Height', 'hash-elements'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 80,
                        'step' => 1,
                    ]
                ],
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-range-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'range_slider_handle_size', [
                'label' => __('Handle Size', 'hash-elements'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 80,
                        'step' => 1,
                    ]
                ],
                'condition' => [
                    'enable_custom_style' => 'yes'
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-range-handle-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'range_slider_bar_color', [
                'label' => esc_html__('Bar Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-range-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'rangle_slider_bar_color_active', [
                'label' => esc_html__('Color (Active)', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-range-color-active: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'range_handle_color', [
                'label' => esc_html__('Handle Color', 'hash-elements'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--hf-range-handle-color: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_section();
    }

    public function render() {
        $settings = $this->get_settings_for_display();
        $enable_custom_style = isset($settings['enable_custom_style']) ? $settings['enable_custom_style'] : 'no';

        if ($enable_custom_style == 'yes') {
            add_filter('hashform_form_classes', array($this, 'modify_class'));
            add_filter('hashform_enable_style', '__return_false');
        }

        if (isset($settings['hf_form_id']) && !empty($settings['hf_form_id']) && (HashFormListing::get_status($settings['hf_form_id']) == 'published')) {
            echo do_shortcode('[hashform id="' . $settings['hf_form_id'] . '"]');
        } elseif ($this->elementor()->editor->is_edit_mode()) {
            ?>
            <p><?php echo esc_html__('Please select a Form', 'hash-form'); ?></p>
            <?php
        }

        if ($enable_custom_style == 'yes') {
            remove_filter('hashform_form_classes', array($this, 'modify_class'));
            remove_filter('hashform_enable_style', '__return_false');
        }
    }

    public function modify_class($classes) {
        $remove_classes = array('hf-form-default-style', 'hf-form-no-style');
        $classes = array_diff($classes, $remove_classes);
        $classes[] = 'hf-elementor-form';
        $classes[] = 'hf-form-custom-style';

        return $classes;
    }

    protected function elementor() {
        return Plugin::$instance;
    }

}
