<?php
defined('ABSPATH') || die();

class HashFormFieldStar extends HashFormFieldType {

    protected $type = 'star';
    protected $array_allowed = false;

    public function field_settings_for_type() {
        return array(
            'default' => false,
            'max_width' => false,
        );
    }

    public function show_primary_options() {
        $field = $this->get_field();
        ?>
        <div class="hf-form-row">
            <label>
                <?php esc_html_e('Maximum Rating', 'hash-form'); ?>
            </label>
            <input type="number" name="field_options[maxnum_<?php echo esc_attr($field['id']); ?>]" value="<?php echo esc_attr($field['maxnum']); ?>" min="1" max="50" step="1" data-changestars="hf-field-star-<?php echo esc_attr($field['id']); ?>" />
            <input type="hidden" name="field_options[minnum_<?php echo esc_attr($field['id']); ?>]" />
        </div>
        <?php
    }

    public function sanitize_value(&$value) {
        return HashFormHelper::sanitize_value('intval', $value);
    }

    protected function input_html() {
        $field = $this->get_field();
        $max = isset($field['maxnum']) ? $field['maxnum'] : 5;
        $field['options'] = range(1, $max);
        ?>

        <div class="hashform-star-group" id="hf-field-star-<?php echo esc_attr($field['id']); ?>">
            <?php
            foreach ($field['options'] as $opt_key => $opt) {
                ?>
                <?php
                /*
                 * The only thing inside each label is an icon, so there was
                 * nothing for a screen reader to read out and no way to tell
                 * the stars apart or know which one was chosen. Each radio
                 * now says which rating it is, and the set is announced as a
                 * radio group by the container.
                 */
                /* translators: 1: this star's rating, 2: the highest rating available. */
                $star_label = sprintf(_n('%1$s star out of %2$s', '%1$s stars out of %2$s', (int) $opt, 'hash-form'), number_format_i18n((int) $opt), number_format_i18n((int) $max));
                ?>
                <label class="hf-star-rating">
                    <input type="radio" id="<?php echo esc_attr($this->html_id('-' . (int) $opt)); ?>" name="<?php echo esc_attr($this->html_name()); ?>" value="<?php echo esc_attr($opt); ?>" aria-label="<?php echo esc_attr($star_label); ?>"<?php echo !empty($field['required']) ? ' aria-required="true"' : ''; ?> />
                    <span class="mdi mdi-star-outline" aria-hidden="true"></span>
                </label>
                <?php
            }
            ?>
        </div>
        <?php
    }

}
