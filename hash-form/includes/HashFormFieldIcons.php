<?php
defined('ABSPATH') || die();

/**
 * Inline SVG icons for the field sidebar.
 *
 * One consistent stroked set drawn on a 24x24 grid, so the icons stay crisp at
 * any size and inherit colour from the surrounding text. Add-ons register their
 * own through the hashform_field_icons filter.
 */
class HashFormFieldIcons {

    /**
     * Paths only: the wrapping <svg> is added by render().
     *
     * @return array field type => inner svg markup
     */
    public static function get_icons() {
        $icons = array(
            'name' => '<circle cx="12" cy="8" r="3.25"/><path d="M4.75 19.25a7.25 7.25 0 0 1 14.5 0"/>',
            'email' => '<rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="m3.5 7 8.5 6 8.5-6"/>',
            'phone' => '<path d="M8.5 3.75h-2A2.5 2.5 0 0 0 4 6.4c.5 7.3 6.3 13.1 13.6 13.6a2.5 2.5 0 0 0 2.65-2.5v-2a1.5 1.5 0 0 0-1.2-1.47l-2.6-.52a1.5 1.5 0 0 0-1.5.62l-.8 1.1a11.5 11.5 0 0 1-5.4-5.4l1.1-.8a1.5 1.5 0 0 0 .62-1.5l-.52-2.6A1.5 1.5 0 0 0 8.5 3.75Z"/>',
            'url' => '<path d="M10 13.5a3.5 3.5 0 0 0 5 0l3-3a3.54 3.54 0 0 0-5-5l-1.2 1.2"/><path d="M14 10.5a3.5 3.5 0 0 0-5 0l-3 3a3.54 3.54 0 0 0 5 5l1.2-1.2"/>',
            'address' => '<path d="M19 10.2c0 5-7 10.05-7 10.05S5 15.2 5 10.2a7 7 0 0 1 14 0Z"/><circle cx="12" cy="10" r="2.5"/>',
            'text' => '<rect x="3" y="8" width="18" height="8" rx="2"/><path d="M7 10.75v2.5"/>',
            'textarea' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M6.5 9h11M6.5 12h11M6.5 15h6"/>',
            'select' => '<rect x="3" y="7" width="18" height="10" rx="2"/><path d="m14.5 10.75 2 2 2-2"/>',
            'checkbox' => '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="m8 12.25 2.75 2.75L16.5 9.5"/>',
            'radio' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3.25" fill="currentColor" stroke="none"/>',
            'image_select' => '<rect x="3" y="5" width="12" height="10" rx="2"/><path d="m5.5 13 2.75-2.75L11 13"/><path d="M15.5 19.25 18 21.5l3.5-4.5"/>',
            'number' => '<path d="M9 4.5 7 19.5M17 4.5l-2 15M4.75 9h14.5M3.75 15h14.5"/>',
            'range_slider' => '<path d="M3.5 12h17"/><circle cx="9" cy="12" r="3"/>',
            'star' => '<path d="m12 4 2.45 5.2 5.55.77-4.05 3.9 1 5.63L12 16.8l-4.95 2.7 1-5.63-4.05-3.9 5.55-.77Z"/>',
            'spinner' => '<rect x="3" y="6.5" width="18" height="11" rx="2"/><path d="m16 10.5 1.75-1.75L19.5 10.5M16 13.5l1.75 1.75L19.5 13.5"/>',
            'date' => '<rect x="3.5" y="5.5" width="17" height="15" rx="2"/><path d="M3.5 10h17M8 3.5v4M16 3.5v4"/>',
            'time' => '<circle cx="12" cy="12" r="8.25"/><path d="M12 7.25V12l3 2"/>',
            'upload' => '<path d="M4 15.5v2.75a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V15.5"/><path d="M12 3.75v11M8 7.75 12 3.75l4 4"/>',
            'user_id' => '<rect x="2.75" y="5" width="18.5" height="14" rx="2.5"/><circle cx="9" cy="11" r="2.25"/><path d="M5.75 16.25a3.5 3.5 0 0 1 6.5 0M14.5 10.5h4M14.5 14h4"/>',
            'hidden' => '<path d="M3 12s3.6-6 9-6a8.7 8.7 0 0 1 4.5 1.3M21 12s-3.6 6-9 6a8.7 8.7 0 0 1-4.5-1.3"/><path d="m4 4 16 16"/><path d="M10 10a2.75 2.75 0 0 0 4 4"/>',
            'heading' => '<path d="M6 5.5v13M18 5.5v13M6 12h12"/>',
            'paragraph' => '<path d="M4.5 6.5h15M4.5 10.5h15M4.5 14.5h15M4.5 18.5h9"/>',
            'separator' => '<path d="M3 12h3.5M10 12h4M17.5 12H21"/>',
            'spacer' => '<path d="M3.5 5h17M3.5 19h17"/><path d="M12 8.5v7M9.75 10.5 12 8.25l2.25 2.25M9.75 13.5 12 15.75l2.25-2.25"/>',
            'image' => '<rect x="3.5" y="5" width="17" height="14" rx="2"/><circle cx="8.75" cy="10" r="1.5"/><path d="m5 16.5 4-4 3.5 3.5 2.5-2.5 4 4"/>',
            'html' => '<path d="m8.5 8.5-4 3.5 4 3.5M15.5 8.5l4 3.5-4 3.5M13.5 5.5l-3 13"/>',
            'captcha' => '<path d="M12 3.75 5 6.5v5.1c0 4.2 2.85 7.4 7 8.65 4.15-1.25 7-4.45 7-8.65V6.5Z"/><path d="m9 12 2.25 2.25L15.5 10"/>',
        );

        /**
         * Register icons for custom field types.
         *
         * Values are the inner markup of a 24x24 stroked icon; the wrapper is
         * supplied by render().
         */
        return apply_filters('hashform_field_icons', $icons);
    }

    /**
     * The mark for the plugin itself: a form with two fields and a button.
     *
     * Drawn on the same 24x24 grid as the field icons so the Elementor widget
     * sits in the same family as everything else, and kept apart from
     * get_icons() because that map is keyed by field type and add-ons filter it.
     */
    public static function widget_icon() {
        return '<rect x="3.5" y="3.5" width="17" height="17" rx="2.5"/>'
                . '<path d="M7 8.5h10M7 12h10"/>'
                . '<rect x="7" y="14.75" width="5" height="2.75" rx="1"/>';
    }

    /**
     * Icon markup for a field type. Never empty.
     *
     * A type with no icon of its own gets a neutral one rather than nothing:
     * the sidebar used to fall back to an icon font glyph, and that font has
     * been removed, so an unknown type would otherwise draw a blank space.
     */
    public static function render($type, $class = 'hf-field-icon') {
        $icons = self::get_icons();
        $inner = empty($icons[$type])
                ? '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8.5 12h7"/>'
                : $icons[$type];

        return wp_kses(self::wrap($inner, $class), self::allowed_svg());
    }

    /**
     * The shared <svg> wrapper.
     */
    private static function wrap($inner, $class = 'hf-field-icon') {
        return '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $inner . '</svg>';
    }

    /**
     * The widget mark as a css rule.
     *
     * Elementor's get_icon() takes a class name, not markup, so the only way to
     * give it an svg is to mask a box with one. Masking rather than a
     * background image keeps it inheriting currentColor, which is what the icon
     * font it replaces did.
     */
    public static function elementor_icon_css() {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">' . self::widget_icon() . '</svg>';
        $uri = 'data:image/svg+xml,' . rawurlencode($svg);

        return '.hf-elementor-icon{display:inline-block;width:1em;height:1em;vertical-align:-.125em;'
                . 'background-color:currentColor;'
                . '-webkit-mask:url("' . $uri . '") center/contain no-repeat;'
                . 'mask:url("' . $uri . '") center/contain no-repeat;}';
    }

    /**
     * kses needs to be told about svg, which it does not allow by default.
     */
    public static function allowed_svg() {
        $shared = array(
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true,
            'opacity' => true,
        );

        return array(
            'svg' => array_merge($shared, array(
                'class' => true,
                'viewbox' => true,
                'width' => true,
                'height' => true,
                'xmlns' => true,
                'aria-hidden' => true,
                'focusable' => true,
                'role' => true,
            )),
            'path' => array_merge($shared, array('d' => true)),
            'circle' => array_merge($shared, array('cx' => true, 'cy' => true, 'r' => true)),
            'rect' => array_merge($shared, array('x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true)),
            'line' => array_merge($shared, array('x1' => true, 'y1' => true, 'x2' => true, 'y2' => true)),
            'polyline' => array_merge($shared, array('points' => true)),
            'polygon' => array_merge($shared, array('points' => true)),
            'g' => $shared,
        );
    }

}
