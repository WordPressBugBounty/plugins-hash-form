<?php
defined('ABSPATH') || die();

class HashFormFieldImage extends HashFormFieldType {

    protected $type = 'image';

    public function field_settings_for_type() {
        return array(
            'label' => false,
            'default' => false,
            'description' => false,
            'required' => false,
            'image' => true,
            'field_alignment' => true,
        );
    }

    protected function extra_field_default_opts() {
        return array(
            'image_id' => '',
            // 'full' is what the field has always served, so an existing image
            // keeps the size it had; the smaller ones are now a choice.
            'image_size' => 'full',
            'image_alt' => '',
            'field_alignment' => 'left',
        );
    }

    /**
     * The attachment sizes worth offering.
     *
     * WordPress makes these for every upload. A theme's own registered sizes
     * are left out: they are named for where the theme uses them and mean
     * nothing in a form.
     */
    public static function image_sizes() {
        return array(
            'thumbnail' => esc_html__('Thumbnail', 'hash-form'),
            'medium' => esc_html__('Medium', 'hash-form'),
            'large' => esc_html__('Large', 'hash-form'),
            'full' => esc_html__('Full Size', 'hash-form'),
        );
    }

    private function image_size($field) {
        $size = isset($field['image_size']) ? $field['image_size'] : '';

        return array_key_exists($size, self::image_sizes()) ? $size : 'full';
    }

    /**
     * Where each size of this image lives, for the builder to switch between.
     *
     * The canvas is drawn by PHP but the size setting is changed in the
     * browser, so without this the preview kept whichever size was last saved.
     */
    private function size_urls($image_id) {
        $urls = array();

        foreach (array_keys(self::image_sizes()) as $size) {
            $src = wp_get_attachment_image_src($image_id, $size);

            if (isset($src[0])) {
                // Dimensions as well as the file: the preview writes them onto
                // the element, or a thumbnail is drawn stretched to whatever
                // the previous size measured.
                $urls[$size] = array(
                    'url' => $src[0],
                    'width' => isset($src[1]) ? (int) $src[1] : '',
                    'height' => isset($src[2]) ? (int) $src[2] : '',
                );
            }
        }

        return $urls;
    }

    protected function input_html() {
        $field = $this->get_field();
        $image_id = isset($field['image_id']) ? absint($field['image_id']) : 0;
        $has_image = $image_id && wp_attachment_is_image($image_id);

        /*
         * With no image there is nothing to draw on the page. The placeholder
         * used to be written there as well, so every visitor to a form with an
         * unfinished image field read "IMAGE FIELD - NO IMAGE"; it is kept for
         * the builder, where it is the only sign the field is there at all.
         */
        if (!$has_image && !is_admin()) {
            return;
        }

        $attrs = array('class' => 'hf-image-field');
        $alt = isset($field['image_alt']) ? trim($field['image_alt']) : '';

        // Left empty, the alt text set on the attachment itself is used, which
        // is what wp_get_attachment_image() does on its own.
        if ('' !== $alt) {
            $attrs['alt'] = $alt;
        }
        ?>
        <div class="hf-image-preview-front hf-field-image-<?php echo absint($field['id']); ?>"<?php echo ($has_image && is_admin()) ? ' data-sizes="' . esc_attr(wp_json_encode($this->size_urls($image_id))) . '"' : ''; ?>>
            <?php
            if (is_admin()) {
                ?>
                <div class="hf-no-image-field<?php echo $has_image ? ' hf-hidden' : ''; ?>">
                    <?php esc_html_e('Image Field - No Image', 'hash-form'); ?>
                </div>
                <?php
            }

            if ($has_image) {
                /*
                 * wp_get_attachment_image(), rather than an <img> built by
                 * hand from the full-size URL. That tag carried nothing but a
                 * src: no alt for a screen reader to read, no srcset, so a
                 * phone fetched the original at whatever size it happened to
                 * be, and no lazy loading.
                 */
                echo wp_get_attachment_image($image_id, $this->image_size($field), false, $attrs);
            }
            ?>
        </div>
        <?php
    }

}
