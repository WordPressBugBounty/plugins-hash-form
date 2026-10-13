jQuery(function ($) {

    'use strict';

    /* -----------------------------------------------------------------------
     * Range sliders
     * -------------------------------------------------------------------- */

    function initRangeSliders(inputs) {
        inputs.each(function () {
            const input = $(this);
            input.prev('.hf-range-slider').slider({
                value: input.val(),
                min: parseFloat(input.attr('min')),
                max: parseFloat(input.attr('max')),
                step: parseFloat(input.attr('step')),
                range: 'min',
                // Fire input/change like a native range input so calculations see the new value.
                slide: function (e, ui) {
                    $(this).next().val(ui.value).trigger('input');
                },
                stop: function (e, ui) {
                    $(this).next().val(ui.value).trigger('change');
                }
            });
        });
    }

    initRangeSliders($('.hf-range-input-selector'));

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
     * Form submission
     * -------------------------------------------------------------------- */

    // Scroll to the field holding the first validation error.
    function firstErrorField(errors) {
        const firstError = Object.keys(errors)[0];
        const separator = firstError.indexOf('-');

        if (separator > 0) {
            const fieldId = firstError.slice(0, separator).replace('field', '');
            const subField = firstError.slice(separator + 1);
            return $('#hf-subfield-container-' + subField + '-' + fieldId);
        }

        return $('#hf-field-container-' + firstError.replace('field', ''));
    }

    /**
     * Put a captcha back to its unanswered state.
     *
     * The script can be loaded with nothing rendered, in which case reset() throws.
     */
    function resetCaptcha(name) {
        const api = window[name];

        if (!api || typeof api.reset !== 'function') {
            return;
        }

        try {
            api.reset();
        } catch (e) {
            // Nothing rendered to reset.
        }
    }

    function resetRecaptcha() {
        resetCaptcha('grecaptcha');
        resetCaptcha('hcaptcha');
        resetCaptcha('turnstile');
    }

    /**
     * Failure notice text; falls back to the generic error when the form's message is blank.
     */
    function failureText(message) {
        return (typeof message === 'string' && message.trim())
            ? message
            : hashform_vars.generic_error;
    }

    /**
     * Tie an error message to its control (aria-describedby, aria-invalid) so it is announced.
     */
    function attachError(container, key, message) {
        const errorId = 'hf-error-' + key;
        const msg = $('<span/>', {
            'class': 'hf-error-msg',
            id: errorId,
            role: 'alert'
        }).text(message);

        container.addClass('hashform-error-container').append(msg);

        // Every control the field owns - a composite field has several.
        const inputs = container.find('input, select, textarea')
            .not('[type="hidden"], [type="submit"], [type="button"]');

        inputs.attr('aria-invalid', 'true');

        // Keep any describedby the field already had; the help text is still
        // relevant when the answer is wrong.
        inputs.each(function () {
            const input = $(this);
            const described = (input.attr('aria-describedby') || '').split(/\s+/).filter(Boolean);

            if (described.indexOf(errorId) === -1) {
                described.push(errorId);
                input.attr('aria-describedby', described.join(' '));
            }
        });

        return inputs.first();
    }

    function showValidationErrors(errors) {
        let firstInput = $();

        $.each(errors, function (key, message) {
            const container = $('#hf-field-container-' + key.replace('field', ''));

            if (!container.length) {
                return;
            }

            const input = attachError(container, key, message);

            if (!firstInput.length) {
                firstInput = input;
            }
        });

        resetRecaptcha();

        const target = firstErrorField(errors);

        if (!target || !target.length) {
            return;
        }

        $('html, body').animate({
            scrollTop: target.offset().top - 300
        }, 300, function () {
            // Put the caret where the problem is, so a keyboard user is not
            // dropped back at the top of the form to hunt for it.
            if (firstInput.length && firstInput.is(':visible')) {
                firstInput.trigger('focus');
            }
        });
    }

    /**
     * Clear the previous attempt's error state, markers included.
     */
    function clearErrors(form) {
        const scope = form && form.length ? form : $(document);

        scope.find('.hf-error-msg, .hf-success-msg, .hf-failed-msg').remove();
        scope.find('.hashform-error-container').removeClass('hashform-error-container');
        scope.find('[aria-invalid]').removeAttr('aria-invalid');

        // Drop only the ids this script added, leaving the field's own help
        // text still linked.
        scope.find('[aria-describedby]').each(function () {
            const input = $(this);
            const kept = (input.attr('aria-describedby') || '')
                .split(/\s+/)
                .filter(function (id) {
                    return id && id.indexOf('hf-error-') !== 0;
                });

            if (kept.length) {
                input.attr('aria-describedby', kept.join(' '));
            } else {
                input.removeAttr('aria-describedby');
            }
        });
    }

    /**
     * A whole-form notice, announced rather than merely drawn.
     */
    function appendNotice(form, cls, message, assertive) {
        $('<span/>', {
            'class': cls,
            role: assertive ? 'alert' : 'status',
            'aria-live': assertive ? 'assertive' : 'polite'
        }).text(message).appendTo(form);
    }

    function resetForm(form) {
        form.trigger('reset');
        form.find('.hf-star-rating').removeClass('hf-star-checked');
        initRangeSliders(form.find('.hf-range-input-selector'));
        $('body').find('.hf-preview-remove').trigger('click');
    }

    /**
     * A reCAPTCHA v3 token for this form, or '' when there is nothing to fetch.
     *
     * Scoped to this form so two forms on a page do not read each other's captcha.
     */
    function captchaToken(form) {
        const captcha = form.find('.g-recaptcha');
        const siteKey = captcha.attr('data-sitekey');
        const isV3 = captcha.attr('data-size') === 'invisible';

        if (!isV3 || !siteKey || typeof grecaptcha === 'undefined') {
            return $.Deferred().resolve('').promise();
        }

        const pending = $.Deferred();

        grecaptcha.ready(function () {
            grecaptcha.execute(siteKey, { action: 'hashform' }).then(
                (token) => pending.resolve(token),
                // Let the server say what went wrong rather than stalling here.
                () => pending.resolve('')
            );
        });

        return pending.promise();
    }

    $(document).on('submit.hashform-form', '.hashform-form', function (e) {
        e.preventDefault();

        const form = $(this);
        const submitButton = form.find('button.hf-submit-button');

        // Ignore repeat submits while a request is in flight.
        if (submitButton.hasClass('hf-button-loading')) {
            return;
        }
        submitButton.addClass('hf-button-loading');

        clearErrors(form);

        // Post once the captcha token, if any, is ready.
        captchaToken(form).then((token) => {
            const data = form.serializeArray();

            if (token) {
                const existing = data.find((item) => item.name === 'g-recaptcha-response');

                if (existing) {
                    existing.value = existing.value || token;
                } else {
                    data.push({ name: 'g-recaptcha-response', value: token });
                }
            }

            $.ajax({
                type: 'POST',
                url: hashform_vars.ajaxurl,
                dataType: 'json',
                data: {
                    action: 'hashform_process_entry',
                    data: $.param(data),
                    location: window.location.href
                },
                success: function (response) {
                    submitButton.removeClass('hf-button-loading');

                    if (!response || typeof response !== 'object') {
                        resetRecaptcha();
                        form.trigger('hashform:failed');
                        appendNotice(form, 'hf-failed-msg', failureText(''), true);
                        return;
                    }

                    // Announce the outcome on the form so add-ons (e.g. payments)
                    // can follow a submission without replacing this handler.
                    form.trigger('hashform:' + response.status, [response]);

                    if (response.status === 'redirect') {
                        window.location.replace(response.message);
                    } else if (response.status === 'success') {
                        resetForm(form);

                        // An empty confirmation means the server already said
                        // its piece; do not draw an empty box for it.
                        if (typeof response.message === 'string' && response.message.trim()) {
                            appendNotice(form, 'hf-success-msg', response.message, false);
                        }
                    } else if (response.status === 'failed') {
                        resetRecaptcha();
                        appendNotice(form, 'hf-failed-msg', failureText(response.message), true);
                    } else if (response.message && typeof response.message === 'object') {
                        // Reset the captcha so its spent token is not resubmitted.
                        resetRecaptcha();
                        showValidationErrors(response.message);
                    } else {
                        // Spam checks reject a whole submission with a plain string
                        // instead of per-field errors.
                        resetRecaptcha();
                        appendNotice(form, 'hf-failed-msg', failureText(response.message), true);
                    }
                },
                error: function () {
                    submitButton.removeClass('hf-button-loading');
                    resetRecaptcha();
                    form.trigger('hashform:failed');
                    appendNotice(form, 'hf-failed-msg', failureText(''), true);
                }
            });
        });
    });

    /* -----------------------------------------------------------------------
     * Spinner field
     * -------------------------------------------------------------------- */

    // Fire input/change when the value changes, so dependent fields and totals update.
    function stepSpinner(button, direction) {
        const input = $(button).closest('.hashform-field-type-spinner').find('input');
        const min = parseFloat(input.attr('min'));
        const max = parseFloat(input.attr('max'));
        const before = input.val();
        let value = (Number(before) || 0) + direction;

        if (!Number.isNaN(max) && value > max) {
            value = max;
        }

        if (!Number.isNaN(min) && value < min) {
            value = min;
        }

        input.val(value);

        if (String(value) !== before) {
            input.trigger('input').trigger('change');
        }
    }

    $('.hashform-field-type-spinner .hf-quantity .mdi-plus').on('click', function () {
        stepSpinner(this, 1);
    });

    $('.hashform-field-type-spinner .hf-quantity .mdi-minus').on('click', function () {
        stepSpinner(this, -1);
    });

    /* -----------------------------------------------------------------------
     * Star rating field
     * -------------------------------------------------------------------- */

    $(document).on('click', '.hashform-star-group input', function () {
        $(this).closest('.hashform-star-group').find('.hf-star-rating').removeClass('hf-star-checked');
        $(this).parent('.hf-star-rating').prevAll('.hf-star-rating').addBack().addClass('hf-star-checked');
    });

    $(document).on('mouseenter', '.hashform-star-group .hf-star-rating:not(.hf-star-rating-readonly)', function () {
        $(this).prevAll('.hf-star-rating').addBack().addClass('hf-star-hovered');
        $(this).nextAll('.hf-star-rating').addClass('hf-star-non-hovered');
    });

    $(document).on('mouseleave', '.hashform-star-group .hf-star-rating:not(.hf-star-rating-readonly)', function () {
        $(this).closest('.hashform-star-group').find('.hf-star-rating').removeClass('hf-star-hovered hf-star-non-hovered');
    });

    /* -----------------------------------------------------------------------
     * Date and time fields
     * -------------------------------------------------------------------- */

    /**
     * Carry a form's style tokens onto a picker panel.
     *
     * The panel is moved to <body>, so it inherits none of the form's --hf-*
     * properties. Unset ones are skipped so the stylesheet fallback applies.
     */
    function bridgePickerStyles(input, panel) {
        const container = input.closest('[id^="hf-container-"]');
        if (!container.length || !panel || !panel.length) {
            return;
        }

        const from = window.getComputedStyle(container[0]);
        const accent = from.getPropertyValue('--hf-field-border-color-focus').trim()
            || from.getPropertyValue('--hf-button-bg-color-normal').trim();

        const tokens = {
            '--hf-pick-accent': accent,
            '--hf-pick-text': from.getPropertyValue('--hf-field-color-normal').trim(),
            '--hf-pick-font': from.getPropertyValue('--hf-field-typo-font-family').trim()
        };

        Object.keys(tokens).forEach(function (name) {
            if (tokens[name]) {
                panel[0].style.setProperty(name, tokens[name]);
            } else {
                panel[0].style.removeProperty(name);
            }
        });
    }

    $('.hashform-field-type-date input').each(function () {
        const input = $(this);
        const format = input.attr('data-format');
        const value = input.val();

        if (value) {
            const date = new Date(value);
            const momentFormat = format.replace('dd', 'DD').replace('MM', 'MMMM').replace('mm', 'MM');
            input.val(Number.isNaN(date.getTime()) ? '' : moment(date).format(momentFormat));
        }

        input.datepicker({
            changeMonth: true,
            dateFormat: format,
            beforeShow: function () {
                bridgePickerStyles(input, $('#ui-datepicker-div'));
            }
        });
    });

    $('.hashform-field-type-time .hf-timepicker').each(function () {
        const input = $(this);

        // Step, Min Time and Max Time are printed as data attributes, which the
        // library does not read itself.
        const step = parseInt(input.attr('data-step'), 10);
        const minTime = input.attr('data-min-time');
        const maxTime = input.attr('data-max-time');

        const options = {
            showDuration: false,
            timeFormat: 'g:ia'
        };

        // The library reads step as minutes, which is the unit the stored
        // default of 60 already assumes: one slot per hour.
        if (step > 0) {
            options.step = step;
        }
        if (minTime) {
            options.minTime = minTime;
        }
        if (maxTime) {
            options.maxTime = maxTime;
        }

        input.timepicker(options);

        // The library has no beforeShow hook, but it does fire showTimepicker
        // on the input, and only one list is ever open at a time.
        input.on('showTimepicker', function () {
            bridgePickerStyles(input, $('.ui-timepicker-wrapper:visible').first());
        });
    });

    /* -----------------------------------------------------------------------
     * Conditional logic
     * -------------------------------------------------------------------- */

    // Compare a single configured value against the set of checked values.
    function arrayValsCompare(compareValue, arrayVals, condition) {
        switch (condition) {
            case 'equal':
                return arrayVals.includes(compareValue);
            case 'less_than':
                return arrayVals.length > 0 && arrayVals.every((val) => compareValue > val);
            case 'less_than_or_equal':
                return arrayVals.length > 0 && arrayVals.every((val) => compareValue >= val);
            case 'greater_than':
                return arrayVals.length > 0 && arrayVals.every((val) => compareValue < val);
            case 'greater_than_or_equal':
                return arrayVals.length > 0 && arrayVals.every((val) => compareValue <= val);
            case 'is_like':
                return arrayVals.some((val) => val.indexOf(compareValue) >= 0);
            default:
                return false;
        }
    }

    // Returns whether the condition holds, or null when the condition is unknown.
    function conditionMatches(condition, value, compareValue, arrayVals, isArrayVals) {
        switch (condition) {
            case 'equal':
            case 'not_equal': {
                const equal = isArrayVals
                    ? arrayValsCompare(compareValue, arrayVals, 'equal')
                    : arrayValsCompare(value, compareValue.split(/\s*,\s*/), 'equal');
                return condition === 'equal' ? equal : !equal;
            }

            case 'less_than':
            case 'less_than_or_equal':
            case 'greater_than':
            case 'greater_than_or_equal': {
                if (isArrayVals) {
                    return arrayValsCompare(compareValue, arrayVals, condition);
                }
                const number = (value == '') ? 0 : parseInt(value, 10);
                if (condition === 'less_than') {
                    return number < compareValue;
                }
                if (condition === 'less_than_or_equal') {
                    return number <= compareValue;
                }
                if (condition === 'greater_than') {
                    return number > compareValue;
                }
                return number >= compareValue;
            }

            case 'is_like':
            case 'is_not_like': {
                const like = isArrayVals
                    ? arrayValsCompare(compareValue, arrayVals, 'is_like')
                    : value.indexOf(compareValue) >= 0;
                return condition === 'is_like' ? like : !like;
            }

            default:
                return null;
        }
    }

    $('.hashform-form-conditions').each(function () {
        const parentForm = $(this).closest('form');

        $.each(JSON.parse($(this).val()), function (index, val) {
            const actionField = parentForm.find('#hf-field-container-' + val.compare_from);
            const showOnMatch = val.condition_action === 'show';

            // Fields that accept several values post as item_meta[id][].
            let conditionTrigger = parentForm.find('[name="item_meta[' + val.compare_to + ']');
            let isArrayVals = false;
            if (!(conditionTrigger.length > 0)) {
                conditionTrigger = parentForm.find('[name="item_meta[' + val.compare_to + '][]');
                isArrayVals = true;
            }

            conditionTrigger.on('change', function () {
                const trigger = $(this);
                let value = trigger.val();
                let arrayVals = [];

                if (isArrayVals) {
                    arrayVals = conditionTrigger.map(function () {
                        return $(this).is(':checked') ? $(this).val() : null;
                    }).toArray();
                }

                // An unchecked checkbox posts nothing, so treat it as empty.
                if (trigger.attr('type') === 'checkbox' && !trigger.is(':checked')) {
                    value = '';
                }

                const matched = conditionMatches(val.compare_condition, value, val.compare_value, arrayVals, isArrayVals);

                if (matched !== null && actionField.length) {
                    actionField.toggle(matched === showOnMatch);
                }
            }).trigger('change');
        });
    });

    /* -----------------------------------------------------------------------
     * Field focus styling
     * -------------------------------------------------------------------- */

    $('.hf-field-content input, .hf-field-content select, .hf-field-content textarea').on('focus', function () {
        $(this).parent().addClass('hf-field-focussed');
    }).on('focusout', function () {
        $(this).parent().removeClass('hf-field-focussed');
    });

    /* -----------------------------------------------------------------------
     * File uploads
     * -------------------------------------------------------------------- */

    // Which uploads get a thumbnail preview rather than just a filename. Kept
    // in step with the formats the server accepts.
    const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp'];

    // Drawn with currentColor so it follows whatever the dropzone text is.
    const UPLOAD_ICON = '<svg class="hf-upload-dropzone-icon" width="28" height="28" viewBox="0 0 24 24" fill="none"'
        + ' stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"'
        + ' aria-hidden="true" focusable="false">'
        + '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>'
        + '<polyline points="17 8 12 3 7 8"/>'
        + '<line x1="12" y1="3" x2="12" y2="15"/>'
        + '</svg>';

    // The label and extension list are author supplied, and they are being
    // concatenated into a template string rather than set as text.
    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[char];
        });
    }

    // Human-readable size. The library's _formatSize needs an instance, which does not exist yet.
    function formatUploadSize(bytes) {
        const size = Number(bytes);

        if (!size || size < 0) {
            return '';
        }
        if (size >= 1024 * 1024 * 1024) {
            return Math.round(size / (1024 * 1024 * 1024) * 10) / 10 + ' GB';
        }
        if (size >= 1024 * 1024) {
            return Math.round(size / (1024 * 1024)) + ' MB';
        }
        return Math.max(1, Math.round(size / 1024)) + ' KB';
    }

    // Holds each uploader instance for the lifetime of the page.
    const uploaders = [];

    $('.hf-file-uploader').each(function () {
        const element = $(this);
        const elementId = element.attr('id');

        // Static previews in the builder and style preview have no id or settings.
        if (!elementId) {
            return;
        }

        const sizeLimit = element.attr('data-max-upload-size');
        const minSizeLimit = Number(element.attr('data-min-upload-size')) || 0;
        const uploaderLabel = element.attr('data-upload-label');
        const multipleUpload = element.attr('data-multiple-uploads') === 'true';
        const extensions = (element.attr('data-extensions') || '').split(',').filter(Boolean);
        const extensionErrorMessage = element.attr('data-extensions-error-message');

        let uploadLimit = element.attr('data-multiple-uploads-limit');
        let uploadLimitMessage = element.attr('data-multiple-uploads-error-message');
        uploadLimit = uploadLimit < 1 ? 1 : uploadLimit;

        const wrapper = () => $('#' + elementId).closest('.hf-file-uploader-wrapper');

        // Spell out the limits on the dropzone so visitors see them before picking a file.
        const constraints = [];

        if (extensions.length && extensions[0] !== '') {
            constraints.push(extensions.map((ext) => ext.trim().toUpperCase()).join(', '));
        }
        if (minSizeLimit > 0) {
            constraints.push(formatUploadSize(minSizeLimit) + ' to ' + formatUploadSize(sizeLimit));
        } else if (sizeLimit > 0) {
            constraints.push('up to ' + formatUploadSize(sizeLimit));
        }
        if (multipleUpload && uploadLimit > 0) {
            constraints.push(uploadLimit + ' files max');
        }

        // Filters the operating system's file dialog to the allowed extensions.
        const acceptFiles = extensions
            .map((ext) => ext.trim())
            .filter(Boolean)
            .map((ext) => '.' + ext.toLowerCase())
            .join(',');

        // One place for every rejection, shown on the form rather than in an alert().
        const showUploadError = (message) => {
            const box = wrapper().find('.hf-upload-error');

            if (!box.length) {
                window.alert(message);
                return;
            }

            box.text(message).addClass('hf-upload-error-visible');
        };

        const clearUploadError = () => {
            wrapper().find('.hf-upload-error').text('').removeClass('hf-upload-error-visible');
        };

        const dropTitle = multipleUpload
            ? 'Drag and drop your files here'
            : 'Drag and drop your file here';

        uploaders.push(new qq.FileUploader({
            element: document.getElementById(elementId),
            action: hashform_vars.ajaxurl,
            params: {
                action: 'hashform_file_upload_action',
                file_uploader_nonce: hashform_vars.ajax_nounce,
                allowedExtensions: extensions,
                sizeLimit: sizeLimit
            },
            allowedExtensions: extensions,
            sizeLimit: sizeLimit,
            // Default minimum of 50 bytes when the field sets none.
            minSizeLimit: minSizeLimit > 0 ? minSizeLimit : 50,
            acceptFiles: acceptFiles,
            uploadButtonText: uploaderLabel,
            multiple: multipleUpload,

            // .qq-upload-button stays a real button inside the card, so the
            // --hf-upload-* styler settings keep applying to it.
            template: '<div class="qq-uploader">' +
                '<div class="hf-upload-dropzone">' +
                    UPLOAD_ICON +
                    '<span class="hf-upload-dropzone-title">' + escapeHtml(dropTitle) + '</span>' +
                    '<span class="hf-upload-dropzone-or">or</span>' +
                    '<div class="qq-upload-button">{uploadButtonText}</div>' +
                    (constraints.length
                        ? '<span class="hf-upload-dropzone-hint">' + escapeHtml(constraints.join('  ·  ')) + '</span>'
                        : '') +
                    '<div class="qq-upload-drop-area"><span>{dragText}</span></div>' +
                '</div>' +
                '<div class="hf-upload-error" role="alert" aria-live="polite"></div>' +
                '<ul class="qq-upload-list"></ul>' +
                '</div>',

            // _find() resolves by class at any depth, so the library still drives the nested bar.
            fileTemplate: '<li>' +
                '<span class="hf-file-row">' +
                    '<span class="qq-upload-file"></span>' +
                    '<span class="qq-upload-spinner"></span>' +
                    '<span class="qq-upload-size"></span>' +
                    '<span class="qq-upload-failed-text">{failUploadtext}</span>' +
                    '<a class="qq-upload-cancel" href="#">{cancelButtonText}</a>' +
                '</span>' +
                '<span class="hf-progress-track"><span class="qq-progress-bar"></span></span>' +
                '</li>',

            onSubmit: function (id, fileName) {
                clearUploadError();

                if (!multipleUpload || uploadLimit == -1) {
                    return;
                }

                const counter = element.parent().find('.hf-multiple-upload-limit');
                const used = Number(counter.val()) + 1;
                counter.val(used);

                if (used > uploadLimit) {
                    uploadLimitMessage = uploadLimitMessage !== ''
                        ? uploadLimitMessage
                        : 'Maximum number of files allowed is ' + uploadLimit;
                    showUploadError(uploadLimitMessage);
                    counter.val(uploadLimit);
                    return false;
                }
            },

            onComplete: function (id, fileName, responseJSON) {
                if (!responseJSON.success) {
                    // The server rejected it. Its own message is surfaced by
                    // showMessage; this only guards against a silent failure.
                    if (!responseJSON.error) {
                        showUploadError('That file could not be uploaded. Please try again.');
                    }
                    return;
                }

                clearUploadError();

                const extension = fileName.split('.').pop();
                const previewImage = IMAGE_EXTENSIONS.includes(extension.toLowerCase()) ? responseJSON.url : '';

                let previewHtml = '<div class="hf-prev-holder" id="hf-uploaded-' + id + '">';
                if (previewImage) {
                    previewHtml += '<img src="' + previewImage + '" />';
                }
                previewHtml += '<span class="hf-prev-name">' + fileName + '</span></div>';

                const uploadedFiles = wrapper().find('.hf-uploaded-files');

                if (multipleUpload) {
                    const existing = uploadedFiles.val();
                    uploadedFiles.val(existing === '' ? responseJSON.url : existing + ',' + responseJSON.url);
                    wrapper().find('.hf-file-preview').append(previewHtml);
                } else {
                    uploadedFiles.val(responseJSON.url);
                    wrapper().find('.hf-file-preview').html(previewHtml);
                }
            },

            messages: {
                typeError: extensionErrorMessage,
                sizeError: '{file} is too large, maximum file size is {sizeLimit}.',
                minSizeError: '{file} is too small, minimum file size is {minSizeLimit}.',
                emptyError: '{file} is empty, please select files again without it.',
                onLeave: 'The files are being uploaded, if you leave now the upload will be cancelled.'
            },

            showMessage: function (message) {
                showUploadError(message);
            }
        }));
    });

    $('body').on('click', '.hf-preview-remove', function () {
        const button = $(this);

        $.ajax({
            url: hashform_vars.ajaxurl,
            type: 'post',
            data: 'action=hashform_file_delete_action&path=' + button.data('path') + '&_wpnonce=' + hashform_vars.ajax_nounce,
            success: function (res) {
                if (res !== 'success') {
                    return;
                }

                const wrapper = button.closest('.hf-file-uploader-wrapper');
                const uploadedFiles = wrapper.find('.hf-uploaded-files');
                uploadedFiles.val(uploadedFiles.val().replace(button.data('url'), '').replace(',,', ','));

                const counter = wrapper.find('.hf-multiple-upload-limit');
                counter.val(Math.max(0, Number(counter.val()) - 1));

                button.parent().fadeOut('1500', function () {
                    button.parent().remove();
                    wrapper.find('#' + button.attr('data-remove-id')).remove();
                });
            }
        });
    });

});
