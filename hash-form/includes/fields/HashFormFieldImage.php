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
            // 'full' so existing images keep their original size.
            'image_size' => 'full',
            'image_alt' => '',
            'field_alignment' => 'left',
        );
    }

    /**
     * The core attachment sizes; theme-registered sizes are left out.
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
     * URL and dimensions of each size, so the builder preview can switch size before saving.
     */
    private function size_urls($image_id) {
        $urls = array();

        foreach (array_keys(self::image_sizes()) as $size) {
            $src = wp_get_attachment_image_src($image_id, $size);

            if (isset($src[0])) {
                // Dimensions too, or the preview stretches the new size to the old one's.
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

        // With no image, draw nothing on the front end; the placeholder is builder-only.
        if (!$has_image && !is_admin()) {
            return;
        }

        $attrs = array('class' => 'hf-image-field');
        $alt = isset($field['image_alt']) ? trim($field['image_alt']) : '';

        // Left empty, wp_get_attachment_image() uses the attachment's own alt text.
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
                // wp_get_attachment_image() for alt, srcset and lazy loading.
                echo wp_get_attachment_image($image_id, $this->image_size($field), false, $attrs);
            }
            ?>
        </div>
        <?php
    }

}
