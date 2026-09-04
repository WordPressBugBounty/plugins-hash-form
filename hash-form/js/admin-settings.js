(function ($) {
    'use strict';

    const ajaxUrl = hashform_admin_js_obj.ajax_url;
    const adminNonce = hashform_admin_js_obj.nonce;

    /* -----------------------------------------------------------------------
     * Shared helpers
     * -------------------------------------------------------------------- */

    function debounce(func, delay) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => {
                func.apply(this, args);
            }, delay);
        };
    }

    /* -----------------------------------------------------------------------
     * Color picker
     * -------------------------------------------------------------------- */

    // The picker updates its input silently; re-fire `change` so the live
    // preview handlers below see the new value.
    function triggerPickerChange(event) {
        const element = $(event.target).closest('.wp-picker-input-wrap').find('.wp-color-picker');
        if (element.length) {
            setTimeout(function () {
                element.trigger('change');
            }, 100);
        }
    }

    $('.hf-color-picker').wpColorPicker({
        change: triggerPickerChange,
        clear: triggerPickerChange
    });

    /* -----------------------------------------------------------------------
     * Icon picker
     * -------------------------------------------------------------------- */

    $('body').on('click', '.hf-icon-box-wrap .hf-icon-list li', function () {
        const $item = $(this);
        const $box = $item.closest('.hf-icon-box');
        const iconClass = $item.find('i').attr('class');

        $box.find('.hf-icon-list li').removeClass('icon-active');
        $item.addClass('icon-active');
        $box.prev('.hf-selected-icon').children('i').attr('class', '').addClass(iconClass);
        $box.next('input').val(iconClass).trigger('change');
        $box.slideUp();
    });

    $('body').on('click', '.hf-icon-box-wrap .hf-selected-icon', function () {
        $(this).next().slideToggle();
    });

    $('body').on('change', '.hf-icon-box-wrap .hf-icon-search select', function () {
        const $box = $(this).closest('.hf-icon-box');
        const selected = $(this).val();

        $box.find('.hf-icon-search-input').val('');
        $box.children('.hf-icon-list').hide().removeClass('active');
        $box.children('.' + selected).fadeIn().addClass('active').find('li').show();
    });

    $('body').on('keyup', '.hf-icon-box-wrap .hf-icon-search input', debounce(function () {
        const keyword = $(this).val().toLowerCase();
        const icons = $(this).closest('.hf-icon-box').find('.hf-icon-list.active i');

        icons.each(function () {
            $(this).parent().toggle($(this).attr('class').indexOf(keyword) > -1);
        });
    }, 500));

    /* -----------------------------------------------------------------------
     * Range sliders
     * -------------------------------------------------------------------- */

    $('.hf-range-input-selector').each(function () {
        const input = $(this);
        input.prev('.hf-range-slider').slider({
            value: input.val(),
            min: parseFloat(input.attr('min')),
            max: parseFloat(input.attr('max')),
            step: parseFloat(input.attr('step')),
            range: 'min',
            slide: function (e, ui) {
                $(this).next().val(ui.value).trigger('change');
            }
        });
    });

    // Update the slider when the input loses focus, as it has most likely changed.
    $('.hf-range-input-selector').on('blur', function () {
        const input = $(this);
        let value = isNaN(input.val()) ? '' : input.val();

        // Keep a manually typed value within the min/max bounds.
        if (value) {
            const min = parseFloat(input.attr('min'));
            const max = parseFloat(input.attr('max'));
            if (value < min) {
                value = min;
            }
            if (value > max) {
                value = max;
            }
        }

        input.val(value);
        input.prev('.hf-range-slider').slider('value', value);
    });

    /* -----------------------------------------------------------------------
     * Typography
     * -------------------------------------------------------------------- */

    const STANDARD_FONTS = ['inherit', 'Helvetica', 'Verdana', 'Arial', 'Times', 'Georgia', 'Courier', 'Trebuchet', 'Tahoma', 'Palatino'];

    $(document).on('change', '.hf-typography-font-family', function () {
        const $select = $(this);
        const fontFamily = $select.val();

        // Anything outside the standard set is a Google font and needs its
        // stylesheet (re)loaded.
        if (!STANDARD_FONTS.includes(fontFamily)) {
            const fontId = $select.attr('id');
            $('link#' + fontId).remove();
            $('head').append('<link rel="stylesheet" id="' + fontId + '" href="https://fonts.googleapis.com/css?family=' + fontFamily + ':100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&subset=latin,latin-ext&display=swap" type="text/css" media="all">');
        }

        const $styleField = $select.closest('.hf-typography-font-family-field').next('.hf-typography-font-style-field');

        $.ajax({
            url: ajaxUrl,
            data: {
                action: 'hashform_get_google_font_variants',
                font_family: fontFamily,
                admin_setting_nonce: adminNonce
            },
            beforeSend: function () {
                $styleField.addClass('hf-typography-loading');
            },
            success: function (response) {
                $styleField.find('select').html(response).trigger('chosen:updated').trigger('change');
            },
            /*
             * Clearing the wait belongs here rather than in success alone. The
             * class had no styling of its own, so a request that failed left
             * behind a mark nobody could see; now that it draws a spinner, the
             * same failure would leave the field spinning for good.
             */
            complete: function () {
                $styleField.removeClass('hf-typography-loading');
            }
        });
    });

    $('body').find('.hf-typography-fields select').chosen({width: '100%'});

    /* -----------------------------------------------------------------------
     * Style template live preview
     * -------------------------------------------------------------------- */

    // Write one <style> tag per control into the preview iframe so a changed
    // setting is reflected immediately without a round trip.
    function hfDynamicCss(control, style, val) {
        const iframe = $('#hf-template-preview-iframe')[0];

        /*
         * The preview is fetched over ajax, so for the first second of the
         * page there is no iframe to write into. Reading .contentDocument off
         * nothing threw, which killed the handler for the control that was
         * touched. The value is picked up anyway: the whole form is
         * serialised when the preview is built.
         */
        if (!iframe) {
            return;
        }

        let doc = iframe.contentDocument || iframe.contentWindow.document;
        if (doc.document) {
            doc = doc.document;
        }

        const formId = $(document).find('#hf-template-preview-form-id').val() || '00';

        const ctrlEscaped = control.replaceAll('(', '\\(').replaceAll(')', '\\)');
        $(doc).find('style.' + ctrlEscaped).remove();

        if (val) {
            $(doc).find('head').append('<style class="' + control + '">body #hf-container-' + formId + '{' + style + '}</style>');
        }
    }

    $('.hf-style-sidebar [name]').on('change', function () {
        const id = $(this).attr('id');
        if (!id) {
            return;
        }

        const to = $(this).val();
        const unit = $(this).attr('data-unit') || '';
        let css;

        if ($(this).attr('data-style')) {
            // Font style selects hold a combined value like `700italic`:
            // digits are the weight, the rest is the style.
            const weight = to.replace(/\D/g, '');
            const style = to.replace(/\d+/g, '') || 'normal';
            css = '--' + id.replace('style', 'weight') + ':' + weight + ';';
            css += '--' + id + ':' + style + ';';
        } else {
            css = '--' + id + ':' + to + unit + ';';
        }

        hfDynamicCss(id, css, to);
    });

    $('#hf-template-preview-form-id').on('change', function () {
        // The canvas header names what is being previewed.
        $('#hf-style-preview-form').text($(this).find(':selected').text().trim());
    });

    $('#hf-template-preview-form-id').on('change', debounce(function () {
        const formData = new FormData($('form#post')[0]);
        formData.append('action', 'hashform_template_style_preview');
        formData.append('form_id', $(this).val());
        formData.append('template_id', $('#post_ID').val());
        formData.append('admin_setting_nonce', adminNonce);

        $('.hf-form-wrap').addClass('hf-content-loading');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (!response.success) {
                    return;
                }

                $('#hf-template-preview-iframe').remove();
                const newIframe = $('<iframe>', {
                    id: 'hf-template-preview-iframe'
                }).appendTo('.hf-template-preview')[0];

                setTimeout(function () {
                    const doc = newIframe.contentDocument || newIframe.contentWindow.document;
                    doc.open();
                    doc.write(response.data);
                    doc.close();

                    // The preview is display-only: swallow interactions.
                    doc.addEventListener('click', (e) => e.preventDefault(), true);
                    doc.addEventListener('mousedown', (e) => e.preventDefault(), true);
                    doc.addEventListener('mouseup', (e) => e.preventDefault(), true);
                }, 0);

                $('.hf-form-wrap').removeClass('hf-content-loading');
            }
        });
    }, 1000)).trigger('change');

    /* -----------------------------------------------------------------------
     * Tabs and accordions
     * -------------------------------------------------------------------- */

    $('body').on('click', '.hf-setting-tab li', function () {
        const $container = $(this).closest('.hf-tab-container');

        $container.find('.hf-setting-tab li').removeClass('hf-tab-active');
        $(this).addClass('hf-tab-active');

        $container.find('.hf-tab-content').hide();
        $container.find('.' + $(this).attr('data-tab')).show();
    });

    $('body').on('click', '.hf-settings-heading', function () {
        if ($(this).hasClass('hf-active')) {
            return;
        }
        $(this).siblings('.hf-form-settings').slideUp();
        $(this).siblings('.hf-settings-heading').removeClass('hf-active');
        $(this).addClass('hf-active');
        $(this).next('.hf-form-settings').slideToggle();
    });

    /* -----------------------------------------------------------------------
     * Linked unit fields (padding/margin style inputs)
     * -------------------------------------------------------------------- */

    $('.hf-linked').on('click', function () {
        $(this).closest('.hf-unit-fields').addClass('hf-not-linked');
    });

    $('.hf-unlinked').on('click', function () {
        $(this).closest('.hf-unit-fields').removeClass('hf-not-linked');
    });

    // While linked, typing into one side mirrors the value to all four.
    $('.hf-unit-fields input').on('input', function () {
        const value = $(this).val();
        $(this).closest('.hf-unit-fields:not(.hf-not-linked)').find('input').each(function () {
            $(this).val(value).change();
        });
    });

    /* -----------------------------------------------------------------------
     * Import dropzone
     * -------------------------------------------------------------------- */

    $('.hf-dropzone').on('change', function () {
        const $input = $(this);
        const input = this;
        if (!input.files || !input.files[0]) {
            return;
        }

        const reader = new FileReader();
        reader.onload = function () {
            $input.parent().removeClass('dragover');
            $input.parent().parent().find('.hf-preview-zone').removeClass('hidden');
            $input.closest('form').find('.hf-box-body').empty().append('<p>' + input.files[0].name + '</p>');
        };
        reader.readAsDataURL(input.files[0]);
    });

    $('.hf-dropzone-wrapper').on('dragover', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });

    $('.hf-dropzone-wrapper').on('dragleave', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });

    $('.hf-remove-preview').on('click', function () {
        try {
            const previewZone = $(this).parents('.hf-preview-zone');
            const dropzone = previewZone.siblings('.hf-dropzone-wrapper').find('.hf-dropzone');

            previewZone.find('.hf-box-body').empty();
            previewZone.addClass('hidden');

            // A file input can only be cleared through a form reset.
            dropzone.wrap('<form>').closest('form').get(0).reset();
            dropzone.unwrap();
        } catch (err) {
            console.log(err);
        }
    });

    /* The embed dialog's copy buttons now go through the shared
       [data-hf-clipboard] handler in backend.js, which already handles the
       insecure-context fallback and the copied confirmation. The bespoke
       #hf-copy-shortcode handler and its copyToClipboard helper that used to
       live here were the only users of either. */

    /* -----------------------------------------------------------------------
     * WP Mail SMTP install/activate
     * -------------------------------------------------------------------- */

    /*
     * Puts the button back the way it was found.
     *
     * Every one of these requests could only ever end well. A stale nonce, a
     * user without the capability, a download that fails, an activation that
     * throws — none of them were handled, so the button kept its spinner and
     * its "Installing…" label for as long as the page stayed open, with no way
     * to try again short of a reload.
     */
    function smtpFailed(button, label) {
        button.removeClass('updating-message').html(hashform_admin_js_obj.error);

        window.setTimeout(function () {
            button.html(label);
        }, 4000);
    }

    function activateSmtpPlugin(button, label) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'hashform_activate_plugin',
                slug: 'wp-mail-smtp',
                file: 'wp_mail_smtp',
                admin_setting_nonce: adminNonce
            }
        }).done(function (response) {
            var result;

            // A capability failure or a fatal answers with something that is
            // not JSON, and parsing it threw inside the success path where
            // nothing was watching.
            try {
                result = (typeof response === 'string') ? JSON.parse(response) : response;
            } catch (e) {
                result = null;
            }

            if (result && result.success) {
                location.reload();
            } else {
                smtpFailed(button, label);
            }
        }).fail(function () {
            smtpFailed(button, label);
        });
    }

    $('.hf-activate-wp-mail-smtp-plugin').on('click', function (e) {
        e.preventDefault();
        const button = $(this);

        // Nothing stopped a second click starting a second request.
        if (button.hasClass('updating-message')) {
            return;
        }

        const label = button.html();
        button.addClass('updating-message').html(hashform_admin_js_obj.activating_text);
        activateSmtpPlugin(button, label);
    });

    $('.hf-install-wp-mail-smtp-plugin').on('click', function (e) {
        e.preventDefault();
        const button = $(this);

        if (button.hasClass('updating-message')) {
            return;
        }

        const label = button.html();
        button.addClass('updating-message').html(hashform_admin_js_obj.installing_text);

        wp.updates.installPlugin({
            slug: 'wp-mail-smtp'
        }).done(function () {
            button.html(hashform_admin_js_obj.activating_text);
            activateSmtpPlugin(button, label);
        }).fail(function () {
            smtpFailed(button, label);
        });
    });

    /* -----------------------------------------------------------------------
     * Misc
     * -------------------------------------------------------------------- */

    setTimeout(function () {
        $('.hf-updated-info').fadeOut('slow', function () {
            this.parentNode.removeChild(this);
        });
    }, 3000);

    $('.hf-field-content input, .hf-field-content select, .hf-field-content textarea').on('focus', function () {
        $(this).parent().addClass('hf-field-focussed');
    }).on('focusout', function () {
        $(this).parent().removeClass('hf-field-focussed');
    });

    /* -----------------------------------------------------------------------
     * Import/Export panel: import over AJAX
     *
     * Was a plain POST, so a rejected file replaced the whole screen with a
     * bare wp_die() page and the panel was lost. The request is reported back
     * here instead: spinner while it runs, message in place if it fails, and
     * a reload only once the form has actually been written.
     * -------------------------------------------------------------------- */

    $(document).on('submit', '.hf-settings-import-form', function (e) {
        e.preventDefault();

        const $form = $(this);
        const $error = $form.closest('.hf-form-row').find('.hf-import-error');

        if ($form.hasClass('is-importing')) {
            return;
        }

        $error.text('').removeClass('is-visible');
        $form.addClass('is-importing');

        const data = new FormData(this);
        data.append('action', 'hashform_import_form_settings');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: data,
            // FormData supplies its own multipart boundary.
            processData: false,
            contentType: false
        }).done(function (res) {
            if (res && res.success) {
                // The imported fields only show once the page is rebuilt, so
                // the spinner is left running into the reload.
                window.location.reload();
                return;
            }

            $form.removeClass('is-importing');
            $error.text((res && res.data && res.data.message) || hashform_admin_js_obj.error).addClass('is-visible');
        }).fail(function () {
            $form.removeClass('is-importing');
            $error.text(hashform_admin_js_obj.error).addClass('is-visible');
        });
    });
})(jQuery);
